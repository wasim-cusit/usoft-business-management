<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'edit_sale';
$success = '';
$error = '';

$id = intval($_GET['id'] ?? 0);

if (empty($id)) {
    header('Location: ' . BASE_URL . 'sales/list.php');
    exit;
}

// Get accounts and items
try {
    $db = getDB();
    
    // Check if cash sale account exists, if not create it
    $stmt = $db->query("SELECT id FROM accounts WHERE account_name = 'Cash Sale' OR account_name_urdu = 'کیش فروخت' LIMIT 1");
    $cashAccount = $stmt->fetch();
    if (!$cashAccount) {
        try {
            $stmt = $db->prepare("INSERT INTO accounts (account_name, account_name_urdu, account_type, status) VALUES (?, ?, 'customer', 'active')");
            $stmt->execute(['Cash Sale', 'کیش فروخت']);
            $cashAccountId = $db->lastInsertId();
        } catch (PDOException $e) {
            $stmt = $db->query("SELECT id FROM accounts WHERE account_name LIKE '%Cash%' OR account_name_urdu LIKE '%کیش%' LIMIT 1");
            $cashAccount = $stmt->fetch();
            $cashAccountId = $cashAccount ? $cashAccount['id'] : 0;
        }
    } else {
        $cashAccountId = $cashAccount['id'];
    }
    
    // Get sale data
    $stmt = $db->prepare("SELECT s.*, a.account_name, a.account_name_urdu FROM sales s 
                         LEFT JOIN accounts a ON s.account_id = a.id 
                         WHERE s.id = ?");
    $stmt->execute([$id]);
    $sale = $stmt->fetch();
    
    if (!$sale) {
        header('Location: ' . BASE_URL . 'sales/list.php');
        exit;
    }
    
    // Get sale items - handle both old and new structure
    $stmt = $db->prepare("SELECT si.*, i.item_name, i.item_name_urdu, i.current_stock, i.sale_rate, i.unit 
                         FROM sale_items si 
                         LEFT JOIN items i ON si.item_id = i.id 
                         WHERE si.sale_id = ?");
    $stmt->execute([$id]);
    $saleItems = $stmt->fetchAll();
    
    // Calculate stock before sale for each item - use new structure only
    foreach ($saleItems as &$saleItem) {
        // Ensure all new fields have values (default to 0 if NULL)
        $saleItem['qty'] = floatval($saleItem['qty'] ?? 0);
        $saleItem['narch'] = floatval($saleItem['narch'] ?? 0);
        $saleItem['bag'] = floatval($saleItem['bag'] ?? 0);
        $saleItem['wt'] = floatval($saleItem['wt'] ?? ($saleItem['qty'] + $saleItem['narch'] + $saleItem['bag']));
        $saleItem['kate'] = floatval($saleItem['kate'] ?? 0);
        $saleItem['wt2'] = floatval($saleItem['wt2'] ?? ($saleItem['wt'] - $saleItem['kate']));
        $saleItem['stock_before_sale'] = floatval($saleItem['current_stock']) + floatval($saleItem['wt2']);
    }
    unset($saleItem);
    
    $stmt = $db->query("SELECT * FROM accounts WHERE account_type IN ('customer', 'both') AND status = 'active' ORDER BY account_name");
    $customers = $stmt->fetchAll();
    
    $stmt = $db->query("SELECT * FROM items WHERE status = 'active' ORDER BY item_name");
    $items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    header('Location: ' . BASE_URL . 'sales/list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $saleDate = $_POST['sale_date'] ?? date('Y-m-d');
    $accountId = intval($_POST['account_id'] ?? 0);
    $location = sanitizeInput($_POST['location'] ?? '');
    $details = sanitizeInput($_POST['details'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $bilti = sanitizeInput($_POST['bilti'] ?? '');
    $discount = floatval($_POST['discount'] ?? 0);
    $paidAmount = floatval($_POST['paid_amount'] ?? 0);
    $bardana = floatval($_POST['bardana'] ?? 0);
    $netcash = floatval($_POST['netcash'] ?? 0);
    $remarks = sanitizeInput($_POST['remarks'] ?? '');
    $itemIds = $_POST['item_id'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $narchs = $_POST['narch'] ?? [];
    $bags = $_POST['bag'] ?? [];
    $wts = $_POST['wt'] ?? [];
    $kates = $_POST['kate'] ?? [];
    $wt2s = $_POST['wt2'] ?? [];
    $rates = $_POST['rate'] ?? [];
    $amounts = $_POST['amount'] ?? [];
    
    // Check if cash sale
    $isCashSale = false;
    if (isset($cashAccountId) && $accountId == $cashAccountId) {
        $isCashSale = true;
    }
    
    if (empty($accountId)) {
        $error = t('please_select_customer');
    } elseif (empty($itemIds) || !is_array($itemIds)) {
        $error = t('please_add_item');
    } else {
        try {
            $db->beginTransaction();
            
            // First, revert old stock changes - use wt2 (new structure)
            foreach ($saleItems as $oldItem) {
                $oldQty = floatval($oldItem['wt2'] ?? 0);
                // Revert stock (add back what was sold)
                $stmt = $db->prepare("UPDATE items SET current_stock = current_stock + ? WHERE id = ?");
                $stmt->execute([$oldQty, $oldItem['item_id']]);
            }
            
            // Delete old sale items
            $stmt = $db->prepare("DELETE FROM sale_items WHERE sale_id = ?");
            $stmt->execute([$id]);
            
            // Delete old stock movements
            $stmt = $db->prepare("DELETE FROM stock_movements WHERE reference_type = 'sale' AND reference_id = ?");
            $stmt->execute([$id]);
            
            // Delete old transactions if exist (will recreate with correct debit/credit)
            $stmt = $db->prepare("DELETE FROM transactions WHERE reference_type = 'sale' AND reference_id = ?");
            $stmt->execute([$id]);
            
            // Calculate totals and check stock for new items
            $totalAmount = 0;
            $validItems = [];
            for ($i = 0; $i < count($itemIds); $i++) {
                if (!empty($itemIds[$i]) && !empty($wt2s[$i]) && !empty($rates[$i])) {
                    $itemId = intval($itemIds[$i]);
                    $qty = floatval($qtys[$i] ?? 0);
                    $narch = floatval($narchs[$i] ?? 0);
                    $bag = floatval($bags[$i] ?? 0);
                    $wt = floatval($wts[$i] ?? 0);
                    $kate = floatval($kates[$i] ?? 0);
                    $wt2 = floatval($wt2s[$i] ?? 0);
                    $rate = floatval($rates[$i] ?? 0);
                    $amount = floatval($amounts[$i] ?? 0);
                    
                    // Check stock
                    $stmt = $db->prepare("SELECT current_stock, item_name FROM items WHERE id = ?");
                    $stmt->execute([$itemId]);
                    $item = $stmt->fetch();
                    
                    if (!$item) {
                        throw new Exception(t('item_not_found'));
                    }
                    
                    // Check stock - allow sale but track warnings (use wt2 as quantity for stock check)
                    $currentStock = floatval($item['current_stock']);
                    $stockShortage = 0;
                    if ($currentStock < $wt2) {
                        $stockShortage = $wt2 - $currentStock;
                    }
                    
                    $totalAmount += $amount;
                    $validItems[] = [
                        'item_id' => $itemId,
                        'qty' => $qty,
                        'narch' => $narch,
                        'bag' => $bag,
                        'wt' => $wt,
                        'kate' => $kate,
                        'wt2' => $wt2,
                        'rate' => $rate,
                        'amount' => $amount,
                        'quantity' => $wt2, // Use net weight for stock tracking
                        'current_stock' => $currentStock,
                        'stock_shortage' => $stockShortage,
                        'item_name' => $item['item_name']
                    ];
                }
            }
            
            if (empty($validItems)) {
                throw new Exception(t('please_enter_item_details'));
            }
            
            $netAmount = $totalAmount - $discount;
            $balanceAmount = $netAmount - $paidAmount;
            
            // Handle cash on sale or cash sale (walking customer)
            $cashOnSale = isset($_POST['cash_on_sale']) && $_POST['cash_on_sale'] == 'on';
            if ($cashOnSale || $isCashSale) {
                $paidAmount = $netAmount; // Set paid amount equal to net amount for cash sales
            }
            
            // Get account phone if not provided
            if (empty($phone)) {
                $stmt = $db->prepare("SELECT phone, mobile FROM accounts WHERE id = ?");
                $stmt->execute([$accountId]);
                $accountInfo = $stmt->fetch();
                if ($accountInfo && !empty($accountInfo['phone'])) {
                    $phone = $accountInfo['phone'];
                } elseif ($accountInfo && !empty($accountInfo['mobile'])) {
                    $phone = $accountInfo['mobile'];
                }
            }
            
            // Update sale - try with new fields first, fallback if columns don't exist
            try {
                $stmt = $db->prepare("UPDATE sales SET sale_date = ?, account_id = ?, location = ?, details = ?, phone = ?, bilti = ?, total_amount = ?, discount = ?, net_amount = ?, paid_amount = ?, balance_amount = ?, bardana = ?, netcash = ?, remarks = ? WHERE id = ?");
                $stmt->execute([$saleDate, $accountId, $location, $details, $phone, $bilti, $totalAmount, $discount, $netAmount, $paidAmount, $balanceAmount, $bardana, $netcash, $remarks, $id]);
            } catch (PDOException $e) {
                // Fallback if new columns don't exist yet
                if (strpos($e->getMessage(), 'Unknown column') !== false) {
                    $stmt = $db->prepare("UPDATE sales SET sale_date = ?, account_id = ?, total_amount = ?, discount = ?, net_amount = ?, paid_amount = ?, balance_amount = ?, remarks = ? WHERE id = ?");
                    $stmt->execute([$saleDate, $accountId, $totalAmount, $discount, $netAmount, $paidAmount, $balanceAmount, $remarks, $id]);
                } else {
                    throw $e;
                }
            }
            
            // Collect stock warnings
            $stockWarnings = [];
            
            // Insert new sale items and update stock
            foreach ($validItems as $item) {
                // Insert with new calculation fields structure (quantity = wt2 for consistency)
                $stmt = $db->prepare("INSERT INTO sale_items (sale_id, item_id, qty, narch, bag, wt, kate, wt2, rate, amount, quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id, $item['item_id'], $item['qty'], $item['narch'], $item['bag'], $item['wt'], $item['kate'], $item['wt2'], $item['rate'], $item['amount'], $item['wt2']]);
                
                // Check for stock shortage before updating
                if ($item['stock_shortage'] > 0) {
                    $stockWarnings[] = [
                        'item_name' => $item['item_name'],
                        'available_stock' => $item['current_stock'],
                        'required_quantity' => $item['quantity'],
                        'shortage' => $item['stock_shortage']
                    ];
                }
                
                // Update item stock (reduce for sale) - prevent negative stock (minimum 0)
                $stmt = $db->prepare("UPDATE items SET current_stock = GREATEST(0, current_stock - ?) WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['item_id']]);
                
                // Add to stock movements
                $stmt = $db->prepare("SELECT current_stock FROM items WHERE id = ?");
                $stmt->execute([$item['item_id']]);
                $currentStock = $stmt->fetch()['current_stock'];
                
                $stmt = $db->prepare("INSERT INTO stock_movements (item_id, movement_date, movement_type, reference_type, reference_id, quantity_out, balance_quantity) VALUES (?, ?, 'sale', 'sale', ?, ?, ?)");
                $stmt->execute([$item['item_id'], $saleDate, $id, $item['quantity'], $currentStock]);
            }
            
            // Handle transactions properly for credit/debit accounting:
            // 1. Create DEBIT transaction for the sale receivable (net_amount - what customer owes)
            // 2. Create CREDIT transaction for payment received (reduces receivable)
            // This ensures proper double-entry: DEBIT (receivable) - CREDIT (payment) = Balance
            
            // Create DEBIT transaction for receivable (customer owes us this amount)
            // DEBIT increases receivable (asset) - customer owes us
            if ($netAmount > 0) {
                $stmt = $db->prepare("INSERT INTO transactions (transaction_date, transaction_type, account_id, amount, narration, reference_type, reference_id, created_by) VALUES (?, 'debit', ?, ?, ?, 'sale', ?, ?)");
                $stmt->execute([$saleDate, $accountId, $netAmount, "Sale Receivable: " . $sale['sale_no'], $id, $_SESSION['user_id']]);
            }
            
            // Create CREDIT transaction for payment received (reduces receivable)
            // CREDIT reduces receivable - customer paid us, reduces what they owe
            if ($paidAmount > 0) {
                $stmt = $db->prepare("INSERT INTO transactions (transaction_date, transaction_type, account_id, amount, narration, reference_type, reference_id, created_by) VALUES (?, 'credit', ?, ?, ?, 'sale', ?, ?)");
                $stmt->execute([$saleDate, $accountId, $paidAmount, "Sale Payment: " . $sale['sale_no'], $id, $_SESSION['user_id']]);
            }
            
            // Net effect: DEBIT (net_amount) - CREDIT (paid_amount) = balance_amount (what customer still owes)
            
            $db->commit();
            
            // Build success message with warnings if any
            $success = t('sale_updated_success');
            if (!empty($stockWarnings)) {
                $warningMsg = '<br><strong>' . t('stock_warning') . ':</strong><br>';
                foreach ($stockWarnings as $warning) {
                    $warningMsg .= $warning['item_name'] . ': ' . t('available_stock') . ' = ' . $warning['available_stock'] . ', ' . t('required_quantity') . ' = ' . $warning['required_quantity'] . ', ' . t('stock_shortage') . ' = ' . $warning['shortage'] . '<br>';
                }
                $warningMsg .= '<small>' . t('low_stock_warning') . '</small>';
                $success .= $warningMsg;
            }
            
            // Reload sale data after update
            $stmt = $db->prepare("SELECT s.*, a.account_name, a.account_name_urdu FROM sales s 
                                 LEFT JOIN accounts a ON s.account_id = a.id 
                                 WHERE s.id = ?");
            $stmt->execute([$id]);
            $sale = $stmt->fetch();
            
            $stmt = $db->prepare("SELECT si.*, i.item_name, i.item_name_urdu, i.current_stock, i.sale_rate, i.unit 
                                 FROM sale_items si 
                                 LEFT JOIN items i ON si.item_id = i.id 
                                 WHERE si.sale_id = ?");
            $stmt->execute([$id]);
            $saleItems = $stmt->fetchAll();
            
                // Recalculate stock before sale for each item - use wt2 (new structure)
            foreach ($saleItems as &$saleItem) {
                $saleItem['stock_before_sale'] = floatval($saleItem['current_stock']) + floatval($saleItem['wt2'] ?? 0);
            }
            unset($saleItem);
            
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
/* Stock warning notification styling - clear background and text */
#stockWarningMessage {
    background-color: #f8d7da !important;
    border: 1px solid #f5c2c7 !important;
    color: #842029 !important;
    box-shadow: 0 4px 6px rgba(0,0,0,0.15) !important;
    opacity: 1 !important;
}
#stockWarningMessage strong,
#stockWarningMessage span,
#stockWarningMessage small {
    color: #842029 !important;
    font-weight: 600;
}
#stockWarningMessage .btn-close {
    filter: brightness(0.5);
    opacity: 1;
}
/* Item row styling */
.item-row {
    margin-bottom: 10px;
    padding: 5px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    background-color: #fff;
}
.item-row:hover {
    background-color: #f8f9fa;
}
.item-row input[readonly] {
    background-color: #e9ecef;
}
/* Header row styling */
.row.mb-2:first-of-type {
    font-weight: 600;
    background-color: #f8f9fa !important;
}
/* Button styling */
.enter-btn {
    width: 100%;
}
.remove-row-btn {
    width: 100%;
    margin-top: 5px;
}
/* Input field styling */
.item-row input.form-control {
    font-size: 14px;
}
</style>

<div class="page-header">
    <h1><i class="fas fa-edit"></i> <?php echo t('edit'); ?> <?php echo t('sale'); ?></h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('sale_info'); ?></h5>
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
                    <div class="row mb-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label"><?php echo t('date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="sale_date" value="<?php echo $_POST['sale_date'] ?? $sale['sale_date']; ?>" required>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label"><?php echo t('inv_number'); ?></label>
                            <input type="text" class="form-control" name="sale_no" id="sale_no" value="<?php echo $_POST['sale_no'] ?? $sale['sale_no']; ?>" readonly>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label"><?php echo t('name_party'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" name="account_id" id="account_id" required>
                                <option value="">-- <?php echo t('select'); ?> --</option>
                                <option value="<?php echo $cashAccountId ?? 0; ?>" style="font-weight: bold; color: #0d6efd;" <?php echo ($sale['account_id'] == $cashAccountId) ? 'selected' : ''; ?>>
                                    <?php echo t('cash_sale'); ?>
                                </option>
                                <?php 
                                $selectedAccountId = $_POST['account_id'] ?? $sale['account_id'] ?? '';
                                foreach ($customers as $customer): 
                                    if (isset($cashAccountId) && $customer['id'] == $cashAccountId) continue;
                                ?>
                                    <option value="<?php echo $customer['id']; ?>" <?php echo ($selectedAccountId == $customer['id']) ? 'selected' : ''; ?>>
                                        <?php echo displayAccountNameFull($customer); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label"><?php echo t('location'); ?></label>
                            <input type="text" class="form-control" name="location" id="location" value="<?php echo $_POST['location'] ?? ($sale['location'] ?? ''); ?>" placeholder="<?php echo t('location'); ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label"><?php echo t('details'); ?></label>
                            <input type="text" class="form-control" name="details" id="details" value="<?php echo $_POST['details'] ?? ($sale['details'] ?? ''); ?>" placeholder="<?php echo t('details'); ?>">
                        </div>
                        
                        <div class="col-md-1">
                            <label class="form-label"><?php echo t('phone'); ?></label>
                            <input type="text" class="form-control" name="phone" id="phone" value="<?php echo $_POST['phone'] ?? ($sale['phone'] ?? ''); ?>" placeholder="<?php echo t('phone'); ?>">
                        </div>
                        
                        <div class="col-md-1">
                            <label class="form-label"><?php echo t('bilti'); ?></label>
                            <input type="text" class="form-control" name="bilti" id="bilti" value="<?php echo $_POST['bilti'] ?? ($sale['bilti'] ?? ''); ?>" placeholder="<?php echo t('bilti'); ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <?php /* Commented out Remarks field - user requested
                        <div class="col-md-12">
                            <label class="form-label"><?php echo t('remarks'); ?></label>
                            <input type="text" class="form-control" name="remarks" value="<?php echo $_POST['remarks'] ?? htmlspecialchars($sale['remarks'] ?? ''); ?>">
                        </div>
                        */ ?>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><?php echo t('item_details'); ?></h6>
                        </div>
                        <div class="card-body">
                            <!-- Header Row -->
                            <div class="row mb-2" style="background-color: #f8f9fa; padding: 10px; border-radius: 5px;">
                                <div class="col-md-2 text-center"><strong><?php echo t('item_name'); ?></strong></div>
                                <div class="col-md-1 text-center"><strong><?php echo t('qty'); ?></strong></div>
                                <div class="col-md-1 text-center"><strong><?php echo t('toda'); ?></strong></div>
                                <div class="col-md-1 text-center"><strong><?php echo t('bharti'); ?></strong></div>
                                <div class="col-md-1 text-center"><strong><?php echo t('weight'); ?></strong></div>
                                <div class="col-md-1 text-center"><strong><?php echo t('cut'); ?></strong></div>
                                <div class="col-md-1 text-center"><strong><?php echo t('net'); ?></strong></div>
                                <div class="col-md-1 text-center"><strong><?php echo t('rate'); ?></strong></div>
                                <div class="col-md-2 text-center"><strong><?php echo t('amount'); ?></strong></div>
                                <div class="col-md-1 text-center"><strong><?php echo t('actions'); ?></strong></div>
                            </div>
                            
                            <!-- Items Container -->
                            <div id="itemsContainer">
                                <?php if (!empty($saleItems)): ?>
                                    <?php foreach ($saleItems as $index => $saleItem): ?>
                                        <div class="row mb-2 item-row">
                                            <div class="col-md-2">
                                                <input type="text" class="form-control itemname" name="itemname[]" list="pname" value="<?php echo htmlspecialchars($saleItem['item_name'] ?? ''); ?>" placeholder="Item Name / جنس کا نام" autocomplete="off">
                                                <input type="hidden" class="item-id" name="item_id[]" value="<?php echo $saleItem['item_id']; ?>">
                                            </div>
                                            <div class="col-md-1">
                                                <input type="number" step="0.01" class="form-control qty" name="qty[]" value="<?php echo number_format(floatval($saleItem['qty'] ?? 0), 2, '.', ''); ?>" placeholder="<?php echo t('qty'); ?>" onkeyup="calwe(this);" oninput="calwe(this);">
                                            </div>
                                            <div class="col-md-1">
                                                <input type="number" step="0.01" class="form-control narch" name="narch[]" value="<?php echo number_format(floatval($saleItem['narch'] ?? 0), 2, '.', ''); ?>" placeholder="<?php echo t('toda'); ?>" onkeyup="calwe(this);" oninput="calwe(this);">
                                            </div>
                                            <div class="col-md-1">
                                                <input type="number" step="0.01" class="form-control bag" name="bag[]" value="<?php echo number_format(floatval($saleItem['bag'] ?? 0), 2, '.', ''); ?>" placeholder="<?php echo t('bharti'); ?>" onkeyup="calwe(this);" oninput="calwe(this);">
                                            </div>
                                            <div class="col-md-1">
                                                <input type="number" step="0.01" class="form-control wt" name="wt[]" value="<?php echo number_format(floatval($saleItem['wt'] ?? 0), 2, '.', ''); ?>" placeholder="<?php echo t('weight'); ?>" readonly>
                                            </div>
                                            <div class="col-md-1">
                                                <input type="number" step="0.01" class="form-control kate" name="kate[]" value="<?php echo number_format(floatval($saleItem['kate'] ?? 0), 2, '.', ''); ?>" placeholder="<?php echo t('cut'); ?>" onkeyup="calamo(this);" oninput="calamo(this);">
                                            </div>
                                            <div class="col-md-1">
                                                <input type="number" step="0.01" class="form-control wt2" name="wt2[]" value="<?php echo number_format(floatval($saleItem['wt2'] ?? 0), 2, '.', ''); ?>" placeholder="<?php echo t('net'); ?>" readonly>
                                            </div>
                                            <div class="col-md-1">
                                                <input type="number" step="0.01" class="form-control rate" name="rate[]" value="<?php echo number_format(floatval($saleItem['rate']), 2, '.', ''); ?>" placeholder="<?php echo t('rate'); ?>" onkeyup="calamo(this);" oninput="calamo(this);">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" step="0.01" class="form-control amount" name="amount[]" value="<?php echo number_format(floatval($saleItem['amount']), 2, '.', ''); ?>" placeholder="<?php echo t('amount'); ?>" readonly>
                                            </div>
                                            <div class="col-md-1">
                                                <?php if ($index === 0): ?>
                                                    <button type="button" class="btn btn-success btn-sm enter-btn" onclick="addNewRow(this);">
                                                        <i class="fas fa-plus"></i> Enter
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-success btn-sm enter-btn" onclick="addNewRow(this);" style="display:none;">
                                                        <i class="fas fa-plus"></i> Enter
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="removeRow(this);" <?php echo (count($saleItems) == 1) ? 'style="display:none;"' : ''; ?>>
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="row mb-2 item-row">
                                        <div class="col-md-2">
                                            <input type="text" class="form-control itemname" name="itemname[]" list="pname" placeholder="Item Name" autocomplete="off">
                                            <input type="hidden" class="item-id" name="item_id[]">
                                        </div>
                                        <div class="col-md-1">
                                            <input type="number" step="0.01" class="form-control qty" name="qty[]" placeholder="Qty" onkeyup="calwe(this); calamo(this);">
                                        </div>
                                        <div class="col-md-1">
                                            <input type="number" step="0.01" class="form-control narch" name="narch[]" placeholder="توڈا" onkeyup="calwe(this); calamo(this);">
                                        </div>
                                        <div class="col-md-1">
                                            <input type="number" step="0.01" class="form-control bag" name="bag[]" placeholder="بھرتی" onkeyup="calwe(this); calamo(this);">
                                        </div>
                                        <div class="col-md-1">
                                            <input type="number" step="0.01" class="form-control wt" name="wt[]" placeholder="وزن" readonly>
                                        </div>
                                        <div class="col-md-1">
                                            <input type="number" step="0.01" class="form-control kate" name="kate[]" placeholder="کاٹ" onkeyup="calamo(this);">
                                        </div>
                                        <div class="col-md-1">
                                            <input type="number" step="0.01" class="form-control wt2" name="wt2[]" placeholder="صافی" readonly>
                                        </div>
                                        <div class="col-md-1">
                                            <input type="number" step="0.01" class="form-control rate" name="rate[]" placeholder="ریٹ" onkeyup="calamo(this);">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" step="0.01" class="form-control amount" name="amount[]" placeholder="ٹوٹل رقم" readonly>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-success btn-sm enter-btn" onclick="addNewRow(this);">
                                                <i class="fas fa-plus"></i> Enter
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="removeRow(this);" style="display:none;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Datalist for items -->
                            <datalist id="pname">
                                <?php foreach ($items as $item): ?>
                                    <option value="<?php echo htmlspecialchars($item['item_name']); ?>" data-id="<?php echo $item['id']; ?>" data-rate="<?php echo $item['sale_rate']; ?>" data-stock="<?php echo $item['current_stock']; ?>">
                                <?php endforeach; ?>
                            </datalist>
                            
                        </div>
                    </div>
                    
                    <!-- Hidden fields for backend processing -->
                    <input type="hidden" name="discount" id="discount" value="<?php echo number_format(floatval($sale['discount'] ?? 0), 2, '.', ''); ?>">
                    <input type="hidden" name="paid_amount" id="paid_amount" value="<?php echo number_format(floatval($sale['paid_amount'] ?? 0), 2, '.', ''); ?>">
                    <input type="hidden" name="total_amount" id="total_amount" value="<?php echo formatNumber($sale['total_amount'] ?? 0); ?>">
                    <input type="hidden" name="net_amount" id="net_amount" value="<?php echo formatNumber($sale['net_amount'] ?? 0); ?>">
                    <input type="hidden" name="balance_amount" id="balance_amount" value="<?php echo formatNumber($sale['balance_amount'] ?? 0); ?>">
                    <input type="hidden" name="bardana" id="bardana" value="<?php echo number_format(floatval($sale['bardana'] ?? 0), 2, '.', ''); ?>">
                    <input type="hidden" name="netcash" id="netcash" value="<?php echo number_format(floatval($sale['netcash'] ?? 0), 2, '.', ''); ?>">
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> <?php echo t('update'); ?>
                        </button>
                        <a href="<?php echo BASE_URL; ?>sales/view.php?id=<?php echo $id; ?>" class="btn btn-info btn-lg">
                            <i class="fas fa-eye"></i> <?php echo t('view'); ?>
                        </a>
                        <a href="<?php echo BASE_URL; ?>sales/list.php" class="btn btn-secondary btn-lg">
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
// Format number helper
function formatNumber(num) {
    if (!num) return '0.00';
    num = parseFloat(num);
    if (isNaN(num)) return '0.00';
    if (num % 1 === 0) {
        return num.toString();
    }
    return num.toFixed(2);
}

// Calculate weight: wt = qty + narch + bag
// Formula: Weight = Qty + Toda + Bharti
function calwe(input) {
    var row = $(input).closest('.item-row');
    var qty = parseFloat(row.find('.qty').val()) || 0;
    var narch = parseFloat(row.find('.narch').val()) || 0;
    var bag = parseFloat(row.find('.bag').val()) || 0;
    
    // Formula: wt = qty + narch + bag
    var wt = qty + narch + bag;
    row.find('.wt').val(formatNumber(wt));
    
    // Also trigger calamo to update net weight and amount
    calamo(input);
}

// Calculate net weight and amount: wt2 = wt - kate, amount = wt2 * rate
// Formula: Net = Weight - Cut, Amount = Net * Rate
function calamo(input) {
    var row = $(input).closest('.item-row');
    var wt = parseFloat(row.find('.wt').val()) || 0;
    var kate = parseFloat(row.find('.kate').val()) || 0;
    var rate = parseFloat(row.find('.rate').val()) || 0;
    
    // Formula: wt2 = wt - kate (Net = Weight - Cut)
    var wt2 = wt - kate;
    if (wt2 < 0) wt2 = 0; // Ensure non-negative
    row.find('.wt2').val(formatNumber(wt2));
    
    // Formula: amount = wt2 * rate (Amount = Net * Rate)
    var amount = wt2 * rate;
    row.find('.amount').val(formatNumber(amount));
    
    // Update grand total
    calculateTotal();
}

// Handle item name selection
function cal_gamo(input) {
    var row = $(input).closest('.item-row');
    var itemName = $(input).val();
    
    // Find matching option in datalist
    var option = $('#pname option').filter(function() {
        return $(this).val().toLowerCase() === itemName.toLowerCase();
    });
    
    if (option.length > 0) {
        var itemId = option.data('id');
        var rate = option.data('rate') || 0;
        var stock = option.data('stock') || 0;
        
        row.find('.item-id').val(itemId);
    } else {
        // Clear item ID if no match found
        row.find('.item-id').val('');
    }
}

// Add new row
function addNewRow(btn) {
    var currentRow = $(btn).closest('.item-row');
    var newRow = currentRow.clone();
    
    // Clear all input values in new row
    newRow.find('input[type="text"], input[type="number"]').val('');
    newRow.find('.item-id').val('');
    
    // Show remove button in new row
    newRow.find('.remove-row-btn').show();
    
    // Hide enter button in current row, show remove button
    currentRow.find('.enter-btn').hide();
    currentRow.find('.remove-row-btn').show();
    
    // Insert new row after current row
    currentRow.after(newRow);
    
    // Focus on item name in new row
    newRow.find('.itemname').focus();
}

// Remove row
function removeRow(btn) {
    var row = $(btn).closest('.item-row');
    if ($('.item-row').length > 1) {
        row.remove();
        calculateTotal();
    } else {
        alert('<?php echo t('please_add_item'); ?>');
    }
}

// Calculate grand total
function calculateTotal() {
    var total = 0;
    $('.amount').each(function() {
        var val = parseFloat($(this).val()) || 0;
        total += val;
    });
    
    $('#total_amount').val(formatNumber(total));
    
    var discount = parseFloat($('#discount').val()) || 0;
    var netAmount = total - discount;
    $('#net_amount').val(formatNumber(netAmount));
    
    var paid = parseFloat($('#paid_amount').val()) || 0;
    var balance = netAmount - paid;
    $('#balance_amount').val(formatNumber(balance));
}

$(document).ready(function() {
    // Handle item name input with datalist
    $(document).on('input change blur', '.itemname', function() {
        cal_gamo(this);
    });
    
    // Also handle when user selects from datalist dropdown
    $(document).on('change', '.itemname', function() {
        setTimeout(function() {
            cal_gamo(this);
        }.bind(this), 100);
    });
    
    // Handle customer selection - auto-fill paid amount for cash sale
    $('#account_id').on('change', function() {
        var selectedValue = $(this).val();
        var cashAccountId = <?php echo $cashAccountId ?? 0; ?>;
        
        if (selectedValue == cashAccountId && cashAccountId > 0) {
            setTimeout(function() {
                var netAmount = parseFloat($('#net_amount').val()) || 0;
                $('#paid_amount').val(formatNumber(netAmount));
                calculateTotal();
            }, 100);
        }
    });
    
    // Update paid amount when net amount changes (for cash sale)
    $('#discount').on('input', function() {
        var cashAccountId = <?php echo $cashAccountId ?? 0; ?>;
        if ($('#account_id').val() == cashAccountId && cashAccountId > 0) {
            setTimeout(function() {
                var netAmount = parseFloat($('#net_amount').val()) || 0;
                $('#paid_amount').val(formatNumber(netAmount));
                calculateTotal();
            }, 100);
        }
    });
    
    // Calculate initial totals on page load
    calculateTotal();
    
    // Prevent form submission if no items
    $('#saleForm').on('submit', function(e) {
        var hasItems = false;
        $('.item-row').each(function() {
            var itemId = $(this).find('.item-id').val();
            var wt2 = parseFloat($(this).find('.wt2').val()) || 0;
            var rate = parseFloat($(this).find('.rate').val()) || 0;
            if (itemId && wt2 > 0 && rate > 0) {
                hasItems = true;
                return false;
            }
        });
        
        if (!hasItems) {
            e.preventDefault();
            alert('<?php echo t('please_add_item'); ?>');
            return false;
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>

