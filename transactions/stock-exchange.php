<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'stock_exchange';
$success = '';
$error = '';

// Get accounts and items
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM accounts WHERE status = 'active' ORDER BY account_name");
    $accounts = $stmt->fetchAll();
    
    $stmt = $db->query("SELECT * FROM items WHERE status = 'active' ORDER BY item_name");
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $accounts = [];
    $items = [];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exchangeDate = $_POST['exchange_date'] ?? date('Y-m-d');
    $fromAccountId = intval($_POST['from_account_id'] ?? 0);
    $toAccountId = intval($_POST['to_account_id'] ?? 0);
    $narration = sanitizeInput($_POST['narration'] ?? '');
    $exchangeItems = $_POST['exchange_items'] ?? [];
    
    if (empty($fromAccountId) || empty($toAccountId)) {
        $error = t('both_accounts_required');
    } elseif ($fromAccountId == $toAccountId) {
        $error = t('accounts_cannot_same');
    } elseif (empty($exchangeItems) || !is_array($exchangeItems)) {
        $error = t('please_add_item');
    } else {
        try {
            $db->beginTransaction();
            
            // Generate transaction number (Se01, Se02, etc.)
            $stmt = $db->query("SELECT MAX(id) as max_id FROM transactions");
            $maxId = $stmt->fetch()['max_id'] ?? 0;
            $nextNumber = $maxId + 1;
            $transactionNo = 'Se' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
            
            $totalAmount = 0;
            
            foreach ($exchangeItems as $item) {
                $itemId = intval($item['item_id'] ?? 0);
                $quantity = floatval($item['quantity'] ?? 0);
                $rate = floatval($item['rate'] ?? 0);
                $weight = floatval($item['weight'] ?? 0);
                $packing = sanitizeInput($item['packing'] ?? '');
                $amount = floatval($item['amount'] ?? 0);
                
                if ($itemId > 0 && $quantity > 0) {
                    // Check stock availability in from account
                    $stmt = $db->prepare("SELECT current_stock FROM items WHERE id = ?");
                    $stmt->execute([$itemId]);
                    $itemData = $stmt->fetch();
                    
                    if (!$itemData || $itemData['current_stock'] < $quantity) {
                        throw new Exception(t('insufficient_stock') . ' - ' . t('item_name'));
                    }
                    
                    // Update stock - decrease from account, increase to account
                    // Note: In a real system, you'd track stock per account
                    // For now, we'll just record the transaction
                    
                    $totalAmount += $amount;
                }
            }
            
            if ($totalAmount > 0) {
                // Create journal entry for stock exchange
                // Debit: To Account (receiving stock)
                $stmt = $db->prepare("INSERT INTO transactions (transaction_no, transaction_date, transaction_type, account_id, amount, narration, reference_type, created_by) VALUES (?, ?, 'debit', ?, ?, ?, 'stock_exchange', ?)");
                $stmt->execute([$transactionNo . '-D', $exchangeDate, $toAccountId, $totalAmount, $narration, $_SESSION['user_id']]);
                
                // Credit: From Account (giving stock)
                $stmt = $db->prepare("INSERT INTO transactions (transaction_no, transaction_date, transaction_type, account_id, amount, narration, reference_type, created_by) VALUES (?, ?, 'credit', ?, ?, ?, 'stock_exchange', ?)");
                $stmt->execute([$transactionNo . '-C', $exchangeDate, $fromAccountId, $totalAmount, $narration, $_SESSION['user_id']]);
                
                // Record stock movements
                foreach ($exchangeItems as $item) {
                    $itemId = intval($item['item_id'] ?? 0);
                    $quantity = floatval($item['quantity'] ?? 0);
                    $rate = floatval($item['rate'] ?? 0);
                    $weight = floatval($item['weight'] ?? 0);
                    $packing = sanitizeInput($item['packing'] ?? '');
                    
                    if ($itemId > 0 && $quantity > 0) {
                        // Decrease stock from from_account
                        $stmt = $db->prepare("UPDATE items SET current_stock = current_stock - ? WHERE id = ?");
                        $stmt->execute([$quantity, $itemId]);
                        
                        // Note: In a real system with per-account stock tracking,
                        // you would also increase stock for to_account
                        // For now, we're just recording the movement
                    }
                }
            }
            
            $db->commit();
            $success = t('exchange_success');
            $_POST = [];
        } catch (Exception $e) {
            $db->rollBack();
            $error = t('exchange_error') . ': ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-exchange-alt"></i> <?php echo t('stock_exchange'); ?></h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('stock_exchange'); ?></h5>
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
                
                <form method="POST" action="" id="exchangeForm">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label"><?php echo t('exchange_date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="exchange_date" value="<?php echo $_POST['exchange_date'] ?? date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?php echo t('from_account'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" name="from_account_id" id="from_account_id" required>
                                <option value="">-- <?php echo t('select'); ?> --</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>" <?php echo (($_POST['from_account_id'] ?? '') == $account['id']) ? 'selected' : ''; ?>>
                                        <?php echo displayAccountNameFull($account); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?php echo t('to_account'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" name="to_account_id" id="to_account_id" required>
                                <option value="">-- <?php echo t('select'); ?> --</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>" <?php echo (($_POST['to_account_id'] ?? '') == $account['id']) ? 'selected' : ''; ?>>
                                        <?php echo displayAccountNameFull($account); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label"><?php echo t('narration'); ?></label>
                            <textarea class="form-control" name="narration" rows="2" placeholder="<?php echo t('enter_narration'); ?>"><?php echo htmlspecialchars($_POST['narration'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><?php echo t('from_account'); ?> - <?php echo t('items'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-md-3">
                                    <label class="form-label"><?php echo t('item_name'); ?></label>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><?php echo t('quantity'); ?></label>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><?php echo t('packing'); ?></label>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><?php echo t('rate'); ?></label>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label"><?php echo t('weight'); ?></label>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><?php echo t('amount'); ?></label>
                                </div>
                            </div>
                            <div id="fromItemsContainer">
                                <div class="row mb-2 from-item-row">
                                    <div class="col-md-3">
                                        <select class="form-select item-select" name="from_item_id[]" required>
                                            <option value="">-- <?php echo t('select'); ?> --</option>
                                            <?php foreach ($items as $item): ?>
                                                <option value="<?php echo $item['id']; ?>" data-rate="<?php echo $item['sale_rate']; ?>">
                                                    <?php echo displayItemNameFull($item); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" step="0.01" class="form-control from-quantity" name="from_quantity[]" placeholder="0" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control" name="from_packing[]" placeholder="<?php echo t('packing'); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" step="0.01" class="form-control from-rate" name="from_rate[]" placeholder="0" required>
                                    </div>
                                    <div class="col-md-1">
                                        <input type="number" step="0.01" class="form-control from-weight" name="from_weight[]" placeholder="0">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control from-amount" name="from_amount[]" readonly placeholder="0" style="font-weight: bold; text-align: right; font-size: 14px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><?php echo t('to_account'); ?> - <?php echo t('items'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm" id="toItemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="25%"><?php echo t('item_name'); ?></th>
                                            <th width="12%"><?php echo t('quantity'); ?></th>
                                            <th width="12%"><?php echo t('packing'); ?></th>
                                            <th width="12%"><?php echo t('rate'); ?></th>
                                            <th width="12%"><?php echo t('weight'); ?></th>
                                            <th width="12%"><?php echo t('amount'); ?></th>
                                            <th width="8%"><?php echo t('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="toItemsBody">
                                        <tr>
                                            <td>
                                                <select class="form-select form-select-sm item-select" name="exchange_items[0][item_id]" required>
                                                    <option value="">-- <?php echo t('select'); ?> --</option>
                                                    <?php foreach ($items as $item): ?>
                                                        <option value="<?php echo $item['id']; ?>" data-rate="<?php echo $item['sale_rate']; ?>">
                                                            <?php echo displayItemNameFull($item); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm quantity" name="exchange_items[0][quantity]" placeholder="0" required></td>
                                            <td><input type="text" class="form-control form-control-sm" name="exchange_items[0][packing]" placeholder="<?php echo t('packing'); ?>"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm rate" name="exchange_items[0][rate]" placeholder="0" required></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm weight" name="exchange_items[0][weight]" placeholder="0"></td>
                                            <td><input type="text" class="form-control form-control-sm amount" name="exchange_items[0][amount]" readonly placeholder="0" style="font-weight: bold; text-align: right; font-size: 14px;"></td>
                                            <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-times"></i></button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm mt-2" id="addToItemRow"><i class="fas fa-plus"></i> <?php echo t('add_new'); ?></button>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo t('save'); ?></button>
                        <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-secondary ms-2"><?php echo t('cancel'); ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 1;
    
    // Add row to "To Account" items table
    document.getElementById('addToItemRow').addEventListener('click', function() {
        const tbody = document.getElementById('toItemsBody');
        const newRow = tbody.querySelector('tr').cloneNode(true);
        
        // Update input names with new index
        newRow.querySelectorAll('input, select').forEach(function(input) {
            if (input.name) {
                input.name = input.name.replace(/\[0\]/, '[' + rowIndex + ']');
            }
            if (input.name && input.name.includes('exchange_items')) {
                input.value = '';
            }
        });
        
        tbody.appendChild(newRow);
        rowIndex++;
        attachRowEvents(newRow);
    });
    
    // Remove row
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row')) {
            const tbody = document.getElementById('toItemsBody');
            if (tbody.children.length > 1) {
                e.target.closest('tr').remove();
            } else {
                alert('<?php echo t('please_add_item'); ?>');
            }
        }
    });
    
    // Calculate amount for "To Account" items
    function attachRowEvents(row) {
        const quantity = row.querySelector('.quantity');
        const rate = row.querySelector('.rate');
        const amount = row.querySelector('.amount');
        
        function calculateAmount() {
            const qty = parseFloat(quantity.value) || 0;
            const rt = parseFloat(rate.value) || 0;
            const calculatedAmount = qty * rt;
            // Format number - remove .00 for whole numbers
            if (typeof formatNumber === 'function') {
                amount.value = formatNumber(calculatedAmount);
            } else {
                if (calculatedAmount % 1 === 0) {
                    amount.value = calculatedAmount.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
                } else {
                    amount.value = calculatedAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
            }
        }
        
        quantity.addEventListener('input', calculateAmount);
        rate.addEventListener('input', calculateAmount);
        
        // Auto-fill rate from item selection
        row.querySelector('.item-select').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const itemRate = selectedOption.getAttribute('data-rate');
            if (itemRate && !rate.value) {
                rate.value = itemRate;
                calculateAmount();
            }
        });
    }
    
    // Attach events to existing rows
    document.querySelectorAll('#toItemsBody tr').forEach(function(row) {
        attachRowEvents(row);
    });
    
    // Calculate amount for "From Account" items
    document.querySelectorAll('.from-item-row').forEach(function(row) {
        const quantity = row.querySelector('.from-quantity');
        const rate = row.querySelector('.from-rate');
        const amount = row.querySelector('.from-amount');
        
        function calculateAmount() {
            const qty = parseFloat(quantity.value) || 0;
            const rt = parseFloat(rate.value) || 0;
            const calculatedAmount = qty * rt;
            // Format number - remove .00 for whole numbers
            if (typeof formatNumber === 'function') {
                amount.value = formatNumber(calculatedAmount);
            } else {
                if (calculatedAmount % 1 === 0) {
                    amount.value = calculatedAmount.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
                } else {
                    amount.value = calculatedAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
            }
        }
        
        quantity.addEventListener('input', calculateAmount);
        rate.addEventListener('input', calculateAmount);
        
        // Auto-fill rate from item selection
        row.querySelector('.item-select').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const itemRate = selectedOption.getAttribute('data-rate');
            if (itemRate && !rate.value) {
                rate.value = itemRate;
                calculateAmount();
            }
        });
    });
});

// Add RTL support for amount fields
if (document.documentElement.dir === 'rtl') {
    document.querySelectorAll('.from-amount, .amount').forEach(function(input) {
        input.style.textAlign = 'left';
    });
}
</script>

<style>
.from-amount, .amount {
    font-weight: bold !important;
    font-size: 14px !important;
}
[dir="rtl"] .from-amount, [dir="rtl"] .amount {
    text-align: left !important;
}

/* Remove padding/margin from from-item-row columns and increase input width */
.from-item-row {
    margin-left: 0 !important;
    margin-right: 0 !important;
}

.from-item-row > div[class*="col-"] {
    padding-left: 2px !important;
    padding-right: 2px !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
}

.from-item-row .form-control,
.from-item-row .form-select {
    width: 100% !important;
    min-width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
}

/* Make all columns in from-item-row equal width so inputs have same width */
.from-item-row > div[class*="col-"] {
    flex: 1 1 0% !important;
    min-width: 0 !important;
    max-width: 100% !important;
    width: auto !important;
}

/* Remove padding/margin from To Account items table and increase input width */
#toItemsTable td {
    padding: 2px !important;
    vertical-align: middle !important;
}

#toItemsTable th {
    padding: 8px 2px !important;
}

#toItemsTable .form-control,
#toItemsTable .form-select {
    width: 100% !important;
    min-width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    margin: 0 !important;
}

#toItemsTable .amount {
    font-weight: bold !important;
    text-align: right !important;
    font-size: 14px !important;
}

[dir="rtl"] #toItemsTable .amount {
    text-align: left !important;
}
</style>

<?php include '../includes/footer.php'; ?>

