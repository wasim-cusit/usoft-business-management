<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'سیل شامل کریں';
$success = '';
$error = '';

// Get accounts and items
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM accounts WHERE account_type IN ('customer', 'both') AND status = 'active' ORDER BY account_name");
    $customers = $stmt->fetchAll();
    
    $stmt = $db->query("SELECT * FROM items WHERE status = 'active' ORDER BY item_name");
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $customers = [];
    $items = [];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $saleDate = $_POST['sale_date'] ?? date('Y-m-d');
    $accountId = intval($_POST['account_id'] ?? 0);
    $discount = floatval($_POST['discount'] ?? 0);
    $paidAmount = floatval($_POST['paid_amount'] ?? 0);
    $remarks = sanitizeInput($_POST['remarks'] ?? '');
    $itemIds = $_POST['item_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $rates = $_POST['rate'] ?? [];
    
    if (empty($accountId)) {
        $error = 'براہ کرم کسٹمر منتخب کریں';
    } elseif (empty($itemIds) || !is_array($itemIds)) {
        $error = 'براہ کرم کم از کم ایک جنس شامل کریں';
    } else {
        try {
            $db->beginTransaction();
            
            // Calculate totals and check stock
            $totalAmount = 0;
            $validItems = [];
            for ($i = 0; $i < count($itemIds); $i++) {
                if (!empty($itemIds[$i]) && !empty($quantities[$i]) && !empty($rates[$i])) {
                    $itemId = intval($itemIds[$i]);
                    $qty = floatval($quantities[$i]);
                    $rate = floatval($rates[$i]);
                    
                    // Check stock
                    $stmt = $db->prepare("SELECT current_stock, item_name FROM items WHERE id = ?");
                    $stmt->execute([$itemId]);
                    $item = $stmt->fetch();
                    
                    if (!$item) {
                        throw new Exception('جنس نہیں ملی');
                    }
                    
                    if ($item['current_stock'] < $qty) {
                        throw new Exception($item['item_name'] . ' کا سٹاک ناکافی ہے');
                    }
                    
                    $amount = $qty * $rate;
                    $totalAmount += $amount;
                    $validItems[] = [
                        'item_id' => $itemId,
                        'quantity' => $qty,
                        'rate' => $rate,
                        'amount' => $amount
                    ];
                }
            }
            
            if (empty($validItems)) {
                throw new Exception('براہ کرم جنس کی تفصیلات درج کریں');
            }
            
            $netAmount = $totalAmount - $discount;
            $balanceAmount = $netAmount - $paidAmount;
            
            // Generate sale number
            $stmt = $db->query("SELECT MAX(id) as max_id FROM sales");
            $maxId = $stmt->fetch()['max_id'] ?? 0;
            $saleNo = generateCode('SAL', $maxId);
            
            // Insert sale
            $stmt = $db->prepare("INSERT INTO sales (sale_no, sale_date, account_id, total_amount, discount, net_amount, paid_amount, balance_amount, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$saleNo, $saleDate, $accountId, $totalAmount, $discount, $netAmount, $paidAmount, $balanceAmount, $remarks, $_SESSION['user_id']]);
            
            $saleId = $db->lastInsertId();
            
            // Insert sale items and update stock
            foreach ($validItems as $item) {
                $stmt = $db->prepare("INSERT INTO sale_items (sale_id, item_id, quantity, rate, amount) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$saleId, $item['item_id'], $item['quantity'], $item['rate'], $item['amount']]);
                
                // Update item stock
                $stmt = $db->prepare("UPDATE items SET current_stock = current_stock - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['item_id']]);
                
                // Add to stock movements
                $stmt = $db->prepare("SELECT current_stock FROM items WHERE id = ?");
                $stmt->execute([$item['item_id']]);
                $currentStock = $stmt->fetch()['current_stock'];
                
                $stmt = $db->prepare("INSERT INTO stock_movements (item_id, movement_date, movement_type, reference_type, reference_id, quantity_out, balance_quantity) VALUES (?, ?, 'sale', 'sale', ?, ?, ?)");
                $stmt->execute([$item['item_id'], $saleDate, $saleId, $item['quantity'], $currentStock]);
            }
            
            // Add transaction if paid
            if ($paidAmount > 0) {
                $stmt = $db->prepare("INSERT INTO transactions (transaction_date, transaction_type, account_id, amount, narration, reference_type, reference_id, created_by) VALUES (?, 'credit', ?, ?, ?, 'sale', ?, ?)");
                $stmt->execute([$saleDate, $accountId, $paidAmount, "Sale: $saleNo", $saleId, $_SESSION['user_id']]);
            }
            
            $db->commit();
            $success = 'فروخت کامیابی سے ریکارڈ ہو گئی';
            $_POST = [];
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-cash-register"></i> سیل شامل کریں</h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">فروخت کی معلومات</h5>
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
                
                <form method="POST" action="" id="saleForm">
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">تاریخ <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="sale_date" value="<?php echo $_POST['sale_date'] ?? date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="col-md-5 mb-3">
                            <label class="form-label">کسٹمر <span class="text-danger">*</span></label>
                            <select class="form-select" name="account_id" id="account_id" required>
                                <option value="">-- منتخب کریں --</option>
                                <?php 
                                $selectedAccountId = $_POST['account_id'] ?? $_GET['account_id'] ?? '';
                                foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['id']; ?>" <?php echo ($selectedAccountId == $customer['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($customer['account_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">ریمارکس</label>
                            <input type="text" class="form-control" name="remarks" value="<?php echo $_POST['remarks'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">جنس کی تفصیلات</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table" id="itemsTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 35%;">جنس</th>
                                            <th style="width: 15%;">مقدار</th>
                                            <th style="width: 15%;">قیمت</th>
                                            <th style="width: 15%;">رقم</th>
                                            <th style="width: 20%;">عمل</th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsBody">
                                        <tr>
                                            <td>
                                                <select class="form-select item-select" name="item_id[]" required>
                                                    <option value="">-- منتخب کریں --</option>
                                                    <?php foreach ($items as $item): ?>
                                                        <option value="<?php echo $item['id']; ?>" data-rate="<?php echo $item['sale_rate']; ?>" data-stock="<?php echo $item['current_stock']; ?>">
                                                            <?php echo htmlspecialchars($item['item_name']); ?> (سٹاک: <?php echo $item['current_stock']; ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td><input type="number" step="0.01" class="form-control quantity" name="quantity[]" required></td>
                                            <td><input type="number" step="0.01" class="form-control rate" name="rate[]" required></td>
                                            <td><input type="text" class="form-control amount" readonly></td>
                                            <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-times"></i></button></td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>کل رقم:</strong></td>
                                            <td><input type="text" class="form-control" id="total_amount" readonly value="0.00"></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>ڈسکاؤنٹ:</strong></td>
                                            <td><input type="number" step="0.01" class="form-control" name="discount" id="discount" value="0"></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>نیٹ رقم:</strong></td>
                                            <td><input type="text" class="form-control" id="net_amount" readonly value="0.00"></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>وصولی:</strong></td>
                                            <td><input type="number" step="0.01" class="form-control" name="paid_amount" id="paid_amount" value="0"></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>بیلنس:</strong></td>
                                            <td><input type="text" class="form-control" id="balance_amount" readonly value="0.00"></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <button type="button" class="btn btn-success btn-sm" id="addRow">
                                <i class="fas fa-plus"></i> جنس شامل کریں
                            </button>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> محفوظ کریں
                        </button>
                        <a href="<?php echo BASE_URL; ?>sales/list.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-list"></i> فہرست دیکھیں
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Add new row
    $('#addRow').click(function() {
        var newRow = `
            <tr>
                <td>
                    <select class="form-select item-select" name="item_id[]" required>
                        <option value="">-- منتخب کریں --</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?php echo $item['id']; ?>" data-rate="<?php echo $item['sale_rate']; ?>" data-stock="<?php echo $item['current_stock']; ?>">
                                <?php echo htmlspecialchars($item['item_name']); ?> (سٹاک: <?php echo $item['current_stock']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" step="0.01" class="form-control quantity" name="quantity[]" required></td>
                <td><input type="number" step="0.01" class="form-control rate" name="rate[]" required></td>
                <td><input type="text" class="form-control amount" readonly></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-times"></i></button></td>
            </tr>
        `;
        $('#itemsBody').append(newRow);
    });
    
    // Remove row
    $(document).on('click', '.remove-row', function() {
        if ($('#itemsBody tr').length > 1) {
            $(this).closest('tr').remove();
            calculateTotal();
        } else {
            alert('کم از کم ایک جنس ضروری ہے');
        }
    });
    
    // Check stock when quantity changes
    $(document).on('input', '.quantity', function() {
        var row = $(this).closest('tr');
        var qty = parseFloat($(this).val()) || 0;
        var stock = parseFloat(row.find('.item-select option:selected').data('stock')) || 0;
        
        if (qty > stock) {
            alert('سٹاک ناکافی ہے! موجودہ سٹاک: ' + stock);
            $(this).val(stock);
            qty = stock;
        }
        
        calculateRowAmount(row);
    });
    
    // Calculate amount
    function calculateRowAmount(row) {
        var qty = parseFloat(row.find('.quantity').val()) || 0;
        var rate = parseFloat(row.find('.rate').val()) || 0;
        var amount = qty * rate;
        row.find('.amount').val(amount.toFixed(2));
        calculateTotal();
    }
    
    $(document).on('input', '.rate', function() {
        calculateRowAmount($(this).closest('tr'));
    });
    
    // Set rate when item selected
    $(document).on('change', '.item-select', function() {
        var row = $(this).closest('tr');
        var rate = $(this).find('option:selected').data('rate');
        var stock = $(this).find('option:selected').data('stock');
        
        if (rate) {
            row.find('.rate').val(rate);
        }
        
        if (stock !== undefined) {
            row.find('.quantity').attr('max', stock);
        }
        
        calculateRowAmount(row);
    });
    
    // Calculate totals
    function calculateTotal() {
        var total = 0;
        $('.amount').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#total_amount').val(total.toFixed(2));
        
        var discount = parseFloat($('#discount').val()) || 0;
        var netAmount = total - discount;
        $('#net_amount').val(netAmount.toFixed(2));
        
        var paid = parseFloat($('#paid_amount').val()) || 0;
        var balance = netAmount - paid;
        $('#balance_amount').val(balance.toFixed(2));
    }
    
    $('#discount, #paid_amount').on('input', calculateTotal);
});
</script>

<?php include '../includes/footer.php'; ?>

