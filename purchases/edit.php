<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'edit_purchase';
$success = '';
$error = '';

$id = intval($_GET['id'] ?? 0);

if (empty($id)) {
    header('Location: ' . BASE_URL . 'purchases/list.php');
    exit;
}

// Get accounts and items
try {
    $db = getDB();
    
    // Get purchase data
    $stmt = $db->prepare("SELECT p.*, a.account_name, a.account_name_urdu FROM purchases p 
                         LEFT JOIN accounts a ON p.account_id = a.id 
                         WHERE p.id = ?");
    $stmt->execute([$id]);
    $purchase = $stmt->fetch();
    
    if (!$purchase) {
        header('Location: ' . BASE_URL . 'purchases/list.php');
        exit;
    }
    
    // Get purchase items
    $stmt = $db->prepare("SELECT pi.*, i.item_name, i.item_name_urdu, i.current_stock, i.purchase_rate, i.unit 
                         FROM purchase_items pi 
                         LEFT JOIN items i ON pi.item_id = i.id 
                         WHERE pi.purchase_id = ?");
    $stmt->execute([$id]);
    $purchaseItems = $stmt->fetchAll();
    
    // Calculate stock before purchase for each item (subtract the purchased quantity)
    foreach ($purchaseItems as &$purchaseItem) {
        $purchaseItem['stock_before_purchase'] = floatval($purchaseItem['current_stock']) - floatval($purchaseItem['quantity']);
    }
    unset($purchaseItem);
    
    $stmt = $db->query("SELECT * FROM accounts WHERE account_type IN ('supplier', 'both') AND status = 'active' ORDER BY account_name");
    $suppliers = $stmt->fetchAll();
    
    $stmt = $db->query("SELECT * FROM items WHERE status = 'active' ORDER BY item_name");
    $items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    header('Location: ' . BASE_URL . 'purchases/list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $purchaseDate = $_POST['purchase_date'] ?? date('Y-m-d');
    $accountId = intval($_POST['account_id'] ?? 0);
    $discount = floatval($_POST['discount'] ?? 0);
    $paidAmount = floatval($_POST['paid_amount'] ?? 0);
    $remarks = sanitizeInput($_POST['remarks'] ?? '');
    $itemIds = $_POST['item_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $rates = $_POST['rate'] ?? [];
    
    if (empty($accountId)) {
        $error = t('please_select_supplier');
    } elseif (empty($itemIds) || !is_array($itemIds)) {
        $error = t('please_add_item');
    } else {
        try {
            $db->beginTransaction();
            
            // First, revert old stock changes (subtract what was purchased)
            foreach ($purchaseItems as $oldItem) {
                // Revert stock (subtract what was purchased, since purchases add stock)
                $stmt = $db->prepare("UPDATE items SET current_stock = GREATEST(0, current_stock - ?) WHERE id = ?");
                $stmt->execute([$oldItem['quantity'], $oldItem['item_id']]);
            }
            
            // Delete old purchase items
            $stmt = $db->prepare("DELETE FROM purchase_items WHERE purchase_id = ?");
            $stmt->execute([$id]);
            
            // Delete old stock movements
            $stmt = $db->prepare("DELETE FROM stock_movements WHERE reference_type = 'purchase' AND reference_id = ?");
            $stmt->execute([$id]);
            
            // Delete old transaction if exists
            $stmt = $db->prepare("DELETE FROM transactions WHERE reference_type = 'purchase' AND reference_id = ?");
            $stmt->execute([$id]);
            
            // Calculate totals for new items
            $totalAmount = 0;
            $validItems = [];
            for ($i = 0; $i < count($itemIds); $i++) {
                if (!empty($itemIds[$i]) && !empty($quantities[$i]) && !empty($rates[$i])) {
                    $itemId = intval($itemIds[$i]);
                    $qty = floatval($quantities[$i]);
                    $rate = floatval($rates[$i]);
                    
                    // Get item info
                    $stmt = $db->prepare("SELECT current_stock, item_name FROM items WHERE id = ?");
                    $stmt->execute([$itemId]);
                    $item = $stmt->fetch();
                    
                    if (!$item) {
                        throw new Exception(t('item_not_found'));
                    }
                    
                    $amount = $qty * $rate;
                    $totalAmount += $amount;
                    $validItems[] = [
                        'item_id' => $itemId,
                        'quantity' => $qty,
                        'rate' => $rate,
                        'amount' => $amount,
                        'item_name' => $item['item_name']
                    ];
                }
            }
            
            if (empty($validItems)) {
                throw new Exception(t('please_enter_item_details'));
            }
            
            $netAmount = $totalAmount - $discount;
            $balanceAmount = $netAmount - $paidAmount;
            
            // Update purchase
            $stmt = $db->prepare("UPDATE purchases SET purchase_date = ?, account_id = ?, total_amount = ?, discount = ?, net_amount = ?, paid_amount = ?, balance_amount = ?, remarks = ? WHERE id = ?");
            $stmt->execute([$purchaseDate, $accountId, $totalAmount, $discount, $netAmount, $paidAmount, $balanceAmount, $remarks, $id]);
            
            // Insert new purchase items and update stock
            foreach ($validItems as $item) {
                $stmt = $db->prepare("INSERT INTO purchase_items (purchase_id, item_id, quantity, rate, amount) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id, $item['item_id'], $item['quantity'], $item['rate'], $item['amount']]);
                
                // Update item stock (add for purchase)
                $stmt = $db->prepare("UPDATE items SET current_stock = current_stock + ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['item_id']]);
                
                // Add to stock movements
                $stmt = $db->prepare("SELECT current_stock FROM items WHERE id = ?");
                $stmt->execute([$item['item_id']]);
                $currentStock = $stmt->fetch()['current_stock'];
                
                $stmt = $db->prepare("INSERT INTO stock_movements (item_id, movement_date, movement_type, reference_type, reference_id, quantity_in, balance_quantity) VALUES (?, ?, 'purchase', 'purchase', ?, ?, ?)");
                $stmt->execute([$item['item_id'], $purchaseDate, $id, $item['quantity'], $currentStock]);
            }
            
            // Add transaction if paid
            if ($paidAmount > 0) {
                $stmt = $db->prepare("INSERT INTO transactions (transaction_date, transaction_type, account_id, amount, narration, reference_type, reference_id, created_by) VALUES (?, 'debit', ?, ?, ?, 'purchase', ?, ?)");
                $stmt->execute([$purchaseDate, $accountId, $paidAmount, "Purchase: " . $purchase['purchase_no'], $id, $_SESSION['user_id']]);
            }
            
            $db->commit();
            
            $success = t('purchase_updated_success');
            
            // Reload purchase data after update
            $stmt = $db->prepare("SELECT p.*, a.account_name, a.account_name_urdu FROM purchases p 
                                 LEFT JOIN accounts a ON p.account_id = a.id 
                                 WHERE p.id = ?");
            $stmt->execute([$id]);
            $purchase = $stmt->fetch();
            
            $stmt = $db->prepare("SELECT pi.*, i.item_name, i.item_name_urdu, i.current_stock, i.purchase_rate, i.unit 
                                 FROM purchase_items pi 
                                 LEFT JOIN items i ON pi.item_id = i.id 
                                 WHERE pi.purchase_id = ?");
            $stmt->execute([$id]);
            $purchaseItems = $stmt->fetchAll();
            
            // Recalculate stock before purchase for each item
            foreach ($purchaseItems as &$purchaseItem) {
                $purchaseItem['stock_before_purchase'] = floatval($purchaseItem['current_stock']) - floatval($purchaseItem['quantity']);
            }
            unset($purchaseItem);
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<style>
.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    white-space: nowrap;
}
.form-label .text-danger {
    margin-left: 2px;
}
[dir="rtl"] .form-label .text-danger {
    margin-left: 0;
    margin-right: 2px;
}
/* Add row button container styling */
.add-row-btn-container {
    display: flex;
    gap: 5px;
    align-items: center;
}
[dir="rtl"] .add-row-btn-container {
    flex-direction: row-reverse;
}
/* Hide add button in rows after first row */
#itemsBody tr:not(:first-child) .add-row-btn-container .add-row-btn {
    display: none !important;
}
</style>

<div class="page-header">
    <h1><i class="fas fa-edit"></i> <?php echo t('edit'); ?> <?php echo t('purchase'); ?></h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('purchase_info'); ?></h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="purchaseForm">
                    <div class="row mb-4 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label"><?php echo t('date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="purchase_date" value="<?php echo $_POST['purchase_date'] ?? $purchase['purchase_date']; ?>" required>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label"><?php echo t('bill_no'); ?></label>
                            <input type="text" class="form-control" name="purchase_no" id="purchase_no" value="<?php echo $_POST['purchase_no'] ?? $purchase['purchase_no']; ?>" readonly>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><?php echo t('supplier'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" name="account_id" id="account_id" required>
                                <option value="">-- <?php echo t('select'); ?> --</option>
                                <?php 
                                $selectedAccountId = $_POST['account_id'] ?? $purchase['account_id'] ?? '';
                                foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>" <?php echo ($selectedAccountId == $supplier['id']) ? 'selected' : ''; ?>>
                                        <?php echo displayAccountNameFull($supplier); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <?php /* Commented out Remarks field - user requested
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label class="form-label"><?php echo t('remarks'); ?></label>
                            <input type="text" class="form-control" name="remarks" value="<?php echo $_POST['remarks'] ?? htmlspecialchars($purchase['remarks'] ?? ''); ?>">
                        </div>
                    </div>
                    */ ?>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><?php echo t('item_details'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table" id="itemsTable">
                                    <thead>
                                        <tr>
                                            <th><?php echo t('items'); ?></th>
                                            <th><?php echo t('quantity'); ?></th>
                                            <th><?php echo t('rate'); ?></th>
                                            <th><?php echo t('amount'); ?></th>
                                            <th><?php echo t('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsBody">
                                        <?php if (!empty($purchaseItems)): ?>
                                            <?php foreach ($purchaseItems as $index => $purchaseItem): ?>
                                                <tr>
                                                    <td>
                                                        <select class="form-select item-select" name="item_id[]" required>
                                                            <option value="">-- <?php echo t('select'); ?> --</option>
                                                            <?php foreach ($items as $item): 
                                                                // For the selected item, show stock before purchase; for others, show current stock
                                                                $displayStock = ($purchaseItem['item_id'] == $item['id']) ? $purchaseItem['stock_before_purchase'] : $item['current_stock'];
                                                            ?>
                                                                <option value="<?php echo $item['id']; ?>" data-rate="<?php echo $item['purchase_rate']; ?>" data-stock="<?php echo $displayStock; ?>" <?php echo ($purchaseItem['item_id'] == $item['id']) ? 'selected' : ''; ?>>
                                                                    <?php echo displayItemNameFull($item); ?> (<?php echo t('stock_label'); ?>: <?php echo formatNumber($displayStock); ?>) 
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>
                                                    <td><input type="number" step="0.01" class="form-control quantity" name="quantity[]" value="<?php echo number_format(floatval($purchaseItem['quantity']), 2, '.', ''); ?>" required></td>
                                                    <td><input type="number" step="0.01" class="form-control rate" name="rate[]" value="<?php echo number_format(floatval($purchaseItem['rate']), 2, '.', ''); ?>" required placeholder="<?php echo $purchaseItem['purchase_rate'] ?? ''; ?>"></td>
                                                    <td><input type="text" class="form-control amount" readonly value="<?php echo formatNumber(floatval($purchaseItem['amount'])); ?>" data-amount="<?php echo floatval($purchaseItem['amount']); ?>"></td>
                                                    <td>
                                                        <div class="add-row-btn-container">
                                                            <?php if ($index === 0): ?>
                                                                <button type="button" class="btn btn-success btn-sm" id="addRow" title="<?php echo t('add_item'); ?>">
                                                                    <i class="fas fa-plus"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="button" class="btn btn-success btn-sm add-row-btn" title="<?php echo t('add_item'); ?>" style="display: none;">
                                                                    <i class="fas fa-plus"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-danger btn-sm remove-row" <?php echo (count($purchaseItems) == 1) ? 'disabled' : ''; ?>><i class="fas fa-times"></i></button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td>
                                                    <select class="form-select item-select" name="item_id[]" required>
                                                        <option value="">-- <?php echo t('select'); ?> --</option>
                                                        <?php foreach ($items as $item): ?>
                                                            <option value="<?php echo $item['id']; ?>" data-rate="<?php echo $item['purchase_rate']; ?>" data-stock="<?php echo $item['current_stock']; ?>">
                                                                <?php echo displayItemNameFull($item); ?> (<?php echo t('stock_label'); ?>: <?php echo $item['current_stock']; ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td><input type="number" step="0.01" class="form-control quantity" name="quantity[]" required></td>
                                                <td><input type="number" step="0.01" class="form-control rate" name="rate[]" required></td>
                                                <td><input type="text" class="form-control amount" readonly></td>
                                                <td>
                                                    <div class="add-row-btn-container">
                                                        <button type="button" class="btn btn-success btn-sm" id="addRow" title="<?php echo t('add_item'); ?>">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-danger btn-sm remove-row" disabled><i class="fas fa-times"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong><?php echo t('total'); ?>:</strong></td>
                                            <td><input type="text" class="form-control" id="total_amount" readonly value="<?php echo formatNumber($purchase['total_amount'] ?? 0); ?>"></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong><?php echo t('discount'); ?>:</strong></td>
                                            <td><input type="number" step="0.01" class="form-control" name="discount" id="discount" placeholder="0" value="<?php echo number_format(floatval($purchase['discount'] ?? 0), 2, '.', ''); ?>"></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong><?php echo t('net_amount'); ?>:</strong></td>
                                            <td><input type="text" class="form-control" id="net_amount" readonly value="<?php echo formatNumber($purchase['net_amount'] ?? 0); ?>"></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong><?php echo t('paid_amount'); ?>:</strong></td>
                                            <td><input type="number" step="0.01" class="form-control" name="paid_amount" id="paid_amount" placeholder="0" value="<?php echo number_format(floatval($purchase['paid_amount'] ?? 0), 2, '.', ''); ?>"></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong><?php echo t('balance_amount'); ?>:</strong></td>
                                            <td><input type="text" class="form-control" id="balance_amount" readonly value="<?php echo formatNumber($purchase['balance_amount'] ?? 0); ?>"></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> <?php echo t('update'); ?>
                        </button>
                        <a href="<?php echo BASE_URL; ?>purchases/view.php?id=<?php echo $id; ?>" class="btn btn-info btn-lg">
                            <i class="fas fa-eye"></i> <?php echo t('view'); ?>
                        </a>
                        <a href="<?php echo BASE_URL; ?>purchases/list.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-list"></i> <?php echo t('view_list'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    // Add new row
    $('#addRow').click(function() {
        var newRow = `
            <tr>
                <td>
                    <select class="form-select item-select" name="item_id[]" required>
                        <option value="">-- <?php echo t('select'); ?> --</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?php echo $item['id']; ?>" data-rate="<?php echo $item['purchase_rate']; ?>" data-stock="<?php echo $item['current_stock']; ?>">
                                <?php echo htmlspecialchars($item['item_name']); ?> (<?php echo t('stock_label'); ?>: <?php echo $item['current_stock']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" step="0.01" class="form-control quantity" name="quantity[]" required></td>
                <td><input type="number" step="0.01" class="form-control rate" name="rate[]" required></td>
                <td><input type="text" class="form-control amount" readonly></td>
                <td>
                    <div class="add-row-btn-container">
                        <button type="button" class="btn btn-success btn-sm add-row-btn" title="<?php echo t('add_item'); ?>" style="display: none;">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-times"></i></button>
                    </div>
                </td>
            </tr>
        `;
        $('#itemsBody').append(newRow);
        updateAddButtons();
    });
    
    // Handle add button clicks (both #addRow and .add-row-btn)
    $(document).on('click', '.add-row-btn', function() {
        $('#addRow').trigger('click');
    });
    
    // Remove row
    $(document).on('click', '.remove-row', function() {
        if ($('#itemsBody tr').length > 1) {
            $(this).closest('tr').remove();
            calculateTotal();
            updateAddButtons();
        } else {
            alert('<?php echo t('please_add_item'); ?>');
        }
    });
    
    // Update add button visibility
    function updateAddButtons() {
        $('#itemsBody tr').each(function(index) {
            if (index === 0) {
                $(this).find('#addRow, .add-row-btn').show();
            } else {
                $(this).find('#addRow, .add-row-btn').hide();
            }
        });
    }
    
    // Calculate row amount
    $(document).on('input', '.quantity, .rate', function() {
        var row = $(this).closest('tr');
        var qty = parseNumber(row.find('.quantity').val());
        var rate = parseNumber(row.find('.rate').val());
        var amount = qty * rate;
        var amountInput = row.find('.amount');
        amountInput.val(formatNumber(amount));
        amountInput.data('amount', amount); // Store numeric value for calculation
        calculateTotal();
    });
    
    // Set rate placeholder when item selected - user must enter manually
    $(document).on('change', '.item-select', function() {
        var row = $(this).closest('tr');
        var selectedOption = $(this).find('option:selected');
        var rate = selectedOption.data('rate');
        var rateInput = row.find('.rate');
        
        // Only clear and set placeholder if rate field is empty or user wants to change
        // If rate already has a value (from existing purchase item), keep it
        if (rate && !rateInput.val()) {
            rateInput.val(''); // Clear any existing value
            rateInput.attr('placeholder', ': ' + rate); // Show rate as placeholder suggestion
        } else if (rate && rateInput.val()) {
            // If field has value, just update placeholder as suggestion
            rateInput.attr('placeholder', ': ' + rate);
            calculateRowAmount(row);
        }
    });
    
    // Allow manual rate entry and recalculate
    $(document).on('input blur', '.rate', function() {
        var row = $(this).closest('tr');
        if ($(this).val()) {
            calculateRowAmount(row);
        }
    });
    
    function calculateRowAmount(row) {
        var qty = parseNumber(row.find('.quantity').val());
        var rate = parseNumber(row.find('.rate').val());
        var amount = qty * rate;
        var amountInput = row.find('.amount');
        amountInput.val(formatNumber(amount));
        amountInput.data('amount', amount); // Store numeric value for calculation
        calculateTotal();
    }
    
    function parseNumber(value) {
        // Remove commas and parse as float
        return parseFloat(String(value).replace(/,/g, '')) || 0;
    }
    
    function formatNumber(value) {
        // Format number with commas for display, remove .00 if whole number
        var num = parseFloat(value);
        if (num % 1 === 0) {
            // Whole number - no decimals
            return num.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
        } else {
            // Has decimals - show 2 decimal places
            return num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }
    
    function calculateTotal() {
        var total = 0;
        $('#itemsBody .amount').each(function() {
            // Try to get from data-amount attribute first, otherwise parse the displayed value
            var amountValue = $(this).data('amount');
            if (amountValue === undefined || amountValue === null) {
                amountValue = parseNumber($(this).val());
            }
            total += parseFloat(amountValue) || 0;
        });
        $('#total_amount').val(formatNumber(total));
        
        var discount = parseNumber($('#discount').val());
        var netAmount = total - discount;
        $('#net_amount').val(formatNumber(netAmount));
        
        var paidAmount = parseNumber($('#paid_amount').val());
        var balance = netAmount - paidAmount;
        $('#balance_amount').val(formatNumber(balance));
    }
    
    // Calculate totals when discount or paid amount changes
    $('#discount, #paid_amount').on('input', function() {
        calculateTotal();
    });
    
    // Initial calculation
    calculateTotal();
});
</script>

<?php include '../includes/footer.php'; ?>

