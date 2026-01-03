<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'new_purchase';
$success = '';
$error = '';

// Get accounts and items
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM accounts WHERE account_type IN ('supplier', 'both') AND status = 'active' ORDER BY account_name");
    $suppliers = $stmt->fetchAll();
    
    $stmt = $db->query("SELECT *, 
                        CASE 
                            WHEN description LIKE '%Purchase Rate Mann:%' 
                            THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(description, 'Purchase Rate Mann: ', -1), '|', 1) AS DECIMAL(15,2))
                            ELSE purchase_rate * 40 
                        END as purchase_rate_mann
                        FROM items WHERE status = 'active' ORDER BY item_name");
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $suppliers = [];
    $items = [];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $purchaseDate = $_POST['purchase_date'] ?? date('Y-m-d');
    $accountId = intval($_POST['account_id'] ?? 0);
    $location = sanitizeInput($_POST['location'] ?? '');
    $details = sanitizeInput($_POST['details'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $bilti = sanitizeInput($_POST['bilti'] ?? '');
    $rateType = sanitizeInput($_POST['rate_type'] ?? 'kilo');
    $rent = floatval($_POST['rent'] ?? 0);
    $loading = floatval($_POST['loading'] ?? 0);
    $labor = floatval($_POST['labor'] ?? 0);
    $brokerage = floatval($_POST['brokerage'] ?? 0);
    $discount = floatval($_POST['discount'] ?? 0);
    $paidAmount = floatval($_POST['paid_amount'] ?? 0);
    $remarks = sanitizeInput($_POST['remarks'] ?? '');
    $itemIds = $_POST['item_id'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $bags = $_POST['bag'] ?? [];
    $wts = $_POST['wt'] ?? [];
    $kates = $_POST['kate'] ?? [];
    $rates = $_POST['rate'] ?? [];
    $amounts = $_POST['amount'] ?? [];
    
    if (empty($accountId)) {
        $error = t('please_select_supplier');
    } elseif (empty($itemIds) || !is_array($itemIds)) {
        $error = t('please_add_item');
    } else {
        try {
            $db->beginTransaction();
            
            // Calculate totals
            $totalAmount = 0;
            $totalQty = 0;
            $totalWeight = 0;
            $validItems = [];
            for ($i = 0; $i < count($itemIds); $i++) {
                if (!empty($itemIds[$i]) && !empty($wts[$i]) && !empty($rates[$i])) {
                    $itemId = intval($itemIds[$i]);
                    $qty = floatval($qtys[$i] ?? 0);
                    $bag = floatval($bags[$i] ?? 0);
                    $wt = floatval($wts[$i] ?? 0);
                    $kate = floatval($kates[$i] ?? 0);
                    $rate = floatval($rates[$i] ?? 0);
                    $amount = floatval($amounts[$i] ?? 0);
                    
                    // Calculate net weight (wt - kate) for quantity
                    $netWeight = $wt - $kate;
                    $totalQty += $netWeight;
                    $totalWeight += $wt;
                    $totalAmount += $amount;
                    
                    $validItems[] = [
                        'item_id' => $itemId,
                        'qty' => $qty,
                        'bag' => $bag,
                        'wt' => $wt,
                        'kate' => $kate,
                        'rate' => $rate,
                        'amount' => $amount,
                        'quantity' => $netWeight // Use net weight for stock
                    ];
                }
            }
            
            if (empty($validItems)) {
                throw new Exception(t('please_enter_item_details'));
            }
            
            // Calculate expenses and grand total
            $totalExpenses = $rent + $loading + $labor + $brokerage;
            $netAmount = $totalAmount - $discount;
            $grandTotal = $netAmount + $totalExpenses;
            $balanceAmount = $grandTotal - $paidAmount;
            
            // Generate purchase number (Pur01, Pur02, etc.)
            $stmt = $db->query("SELECT MAX(id) as max_id FROM purchases");
            $maxId = $stmt->fetch()['max_id'] ?? 0;
            $nextNumber = $maxId + 1;
            $purchaseNo = 'Pur' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
            
            // Insert purchase
            $stmt = $db->prepare("INSERT INTO purchases (purchase_no, purchase_date, account_id, location, details, phone, bilti, total_amount, discount, net_amount, rent, loading, labor, brokerage, total_expenses, grand_total, paid_amount, balance_amount, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$purchaseNo, $purchaseDate, $accountId, $location, $details, $phone, $bilti, $totalAmount, $discount, $netAmount, $rent, $loading, $labor, $brokerage, $totalExpenses, $grandTotal, $paidAmount, $balanceAmount, $remarks, $_SESSION['user_id']]);
            
            $purchaseId = $db->lastInsertId();
            
            // Insert purchase items and update stock
            foreach ($validItems as $item) {
                $stmt = $db->prepare("INSERT INTO purchase_items (purchase_id, item_id, qty, bag, wt, kate, quantity, rate, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$purchaseId, $item['item_id'], $item['qty'], $item['bag'], $item['wt'], $item['kate'], $item['quantity'], $item['rate'], $item['amount']]);
                
                // Update item stock
                $stmt = $db->prepare("UPDATE items SET current_stock = current_stock + ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['item_id']]);
                
                // Add to stock movements
                $stmt = $db->prepare("SELECT current_stock FROM items WHERE id = ?");
                $stmt->execute([$item['item_id']]);
                $currentStock = $stmt->fetch()['current_stock'];
                
                $stmt = $db->prepare("INSERT INTO stock_movements (item_id, movement_date, movement_type, reference_type, reference_id, quantity_in, balance_quantity) VALUES (?, ?, 'purchase', 'purchase', ?, ?, ?)");
                $stmt->execute([$item['item_id'], $purchaseDate, $purchaseId, $item['quantity'], $currentStock]);
            }
            
            // Add transaction if paid
            if ($paidAmount > 0) {
                $stmt = $db->prepare("INSERT INTO transactions (transaction_date, transaction_type, account_id, amount, narration, reference_type, reference_id, created_by) VALUES (?, 'debit', ?, ?, ?, 'purchase', ?, ?)");
                $stmt->execute([$purchaseDate, $accountId, $paidAmount, "Purchase: $purchaseNo", $purchaseId, $_SESSION['user_id']]);
            }
            
            $db->commit();
            $success = t('purchase_added_success');
            $_POST = [];
            
            // Send message to parent window if in iframe
            echo '<script>
                if (window.parent !== window) {
                    window.parent.postMessage("purchase_created", "*");
                }
            </script>';
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<style>
/* Professional Purchase Form Styling - Compact */
#purchaseForm .form-label {
    display: block;
    width: 100%;
    margin-bottom: 4px;
    font-weight: 600;
    font-size: 12px;
    color: #495057;
    white-space: nowrap;
}

[dir="rtl"] #purchaseForm .form-label {
    text-align: right;
}

[dir="ltr"] #purchaseForm .form-label {
    text-align: left;
}

/* Top Section - Compact spacing */
#purchaseForm .row.mb-3.align-items-end > div {
    margin-bottom: 0.5rem;
    padding-left: 4px;
    padding-right: 4px;
}

#purchaseForm .row.align-items-end > div {
    margin: 0 !important;
    padding-left: 4px;
    padding-right: 4px;
}

#purchaseForm .form-control,
#purchaseForm .form-select {
    height: 32px;
    font-size: 13px;
    padding: 6px 12px;
    border: 1px solid #ddd !important;
    margin: 0 !important;
    box-shadow: none !important;
    border-radius: 0 !important;
}

#purchaseForm .form-control:focus,
#purchaseForm .form-select:focus {
    border: 1px solid #80bdff !important;
    box-shadow: none !important;
    outline: none !important;
}

/* Rate Type Radio Buttons - Compact */
#purchaseForm .form-check {
    margin-bottom: 0.25rem;
    padding-left: 0;
}

#purchaseForm .form-check-input {
    margin-top: 0.25rem;
    cursor: pointer;
    width: 14px;
    height: 14px;
}

#purchaseForm .form-check-label {
    margin-left: 0.5rem;
    cursor: pointer;
    font-weight: 500;
    font-size: 12px;
}

[dir="rtl"] #purchaseForm .form-check-label {
    margin-left: 0;
    margin-right: 0.5rem;
}

/* Item Entry Section */
#purchaseForm .card-header.bg-light {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    border-bottom: 2px solid #dee2e6;
    padding: 12px 20px;
}

#purchaseForm .card-header.bg-light h6 {
    margin: 0;
    font-weight: 600;
    color: #495057;
    font-size: 15px;
}

/* Items Table */
#itemsTable {
    margin-top: 1rem;
    font-size: 14px;
}

#itemsTable thead th {
    background: #f8f9fa;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
    padding: 12px 10px;
    vertical-align: middle;
    border-bottom: 2px solid #dee2e6;
}

#itemsTable tbody td {
    padding: 12px 10px;
    vertical-align: middle;
}

/* Hover effect removed */
#itemsTable tbody tr {
    background-color: transparent;
}

/* Expenses and Summary Cards */
#purchaseForm .card.mt-3 {
    margin-top: 1.5rem !important;
}

#purchaseForm .card-body .form-label {
    font-size: 13px;
    margin-bottom: 6px;
    color: #6c757d;
}

#purchaseForm .card-body .form-control[readonly] {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #495057;
    border-color: #dee2e6;
}

#purchaseForm #grand_total {
    font-size: 18px;
    font-weight: 700;
    color: #667eea;
}

#purchaseForm #balance_amount {
    font-size: 16px;
    font-weight: 600;
    color: #eb3349;
}

/* Better spacing for form sections */
#purchaseForm .row.mb-3 {
    margin-bottom: 2rem !important;
}

/* Item Entry Row */
#purchaseForm .row.mb-3.align-items-end .col-md-1,
#purchaseForm .row.mb-3.align-items-end .col-md-2 {
    padding-left: 8px;
    padding-right: 8px;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    #purchaseForm .row.mb-3.align-items-end > div {
        margin-bottom: 1rem;
    }
    
    #purchaseForm .col-md-4 {
        margin-top: 1rem;
    }
}

/* Button styling - Icon only */
#purchaseForm #addItemBtn {
    height: 32px;
    width: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
}

/* Make all numeric fields the same width and professional */
#purchaseForm .numeric-field {
    width: 100%;
}

#purchaseForm .numeric-field .form-control {
    width: 100%;
    text-align: right;
    font-weight: 500;
}

#purchaseForm .numeric-field .form-control[readonly] {
    background-color: #f8f9fa;
    font-weight: 600;
}

/* Professional item entry section */
#purchaseForm .row.align-items-end {
    margin-bottom: 1rem;
    display: flex;
    flex-wrap: nowrap;
}

#purchaseForm .row.align-items-end > div {
    padding-left: 8px;
    padding-right: 8px;
    margin-bottom: 0;
    flex-shrink: 0;
}

/* Ensure numeric fields stay in row */
#purchaseForm .numeric-field {
    flex: 0 0 auto;
    min-width: 80px;
}

/* Item entry row styling */
#purchaseForm .item-select {
    font-weight: 500;
}

#purchaseForm #addItemBtn {
    height: 32px;
    font-size: 14px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
}

#purchaseForm #addItemBtn.w-100 {
    width: 100%;
}

/* Summary section highlight */
#purchaseForm .card-header.bg-light + .card-body {
    background: #fafbfc;
}

/* Table action buttons */
#itemsTable .btn-sm {
    padding: 6px 10px;
    font-size: 12px;
}

/* Better spacing for form sections */
#purchaseForm .card {
    margin-bottom: 1.5rem;
}

/* Remove hover effects from cards, tables, and form elements in purchase form */
#purchaseForm .card:hover {
    transform: none !important;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08) !important;
}

#purchaseForm .table tbody tr:hover {
    background-color: transparent !important;
    transform: none !important;
}

#purchaseForm .form-control:hover,
#purchaseForm .form-select:hover {
    transform: none !important;
}

/* Keep button hover effects for better UX */

/* Form action buttons */
#purchaseForm .d-flex.gap-2 {
    gap: 1rem !important;
}

/* Empty state for items table */
#itemsTable tbody:empty::after {
    content: 'No items added yet';
    display: block;
    text-align: center;
    padding: 2rem;
    color: #6c757d;
    font-style: italic;
}

/* Better input focus states */
#purchaseForm .form-control:focus,
#purchaseForm .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Summary section borders */
#purchaseForm .card-body .border-top {
    border-top: 2px solid #dee2e6 !important;
    margin-top: 1rem;
}
</style>

<?php /*
<div class="page-header">
    <h1><i class="fas fa-shopping-cart"></i> <?php echo t('add_purchase'); ?></h1>
</div>
*/ ?>

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
                    <!-- Top Section: Basic Information -->
                    <!-- Row 1: Date, Inv#, Name Party, Location -->
                    <div class="row align-items-end mb-2">
                        <div class="col-md-2">
                            <label class="form-label"><?php echo t('date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="purchase_date" value="<?php echo $_POST['purchase_date'] ?? date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label"><?php echo t('inv_number'); ?></label>
                            <input type="text" class="form-control" name="purchase_no" id="purchase_no" value="<?php echo $_POST['purchase_no'] ?? ''; ?>" placeholder="Auto">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label"><?php echo t('name_party'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" name="account_id" id="account_id" required>
                                <option value="">-- <?php echo t('select'); ?> --</option>
                                <?php 
                                $selectedAccountId = $_POST['account_id'] ?? $_GET['account_id'] ?? '';
                                foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>" <?php echo ($selectedAccountId == $supplier['id']) ? 'selected' : ''; ?>>
                                        <?php echo displayAccountNameFull($supplier); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label"><?php echo t('location'); ?></label>
                            <input type="text" class="form-control" name="location" value="<?php echo $_POST['location'] ?? ''; ?>" placeholder="<?php echo t('location'); ?>">
                        </div>
                        
                    </div>
                    
                    <!-- Row 2: Details, Phone, Bilti, Rate Type -->
                    <div class="row align-items-end mb-2">
                       
                  
                        <div class="col-md-2">
                            <label class="form-label"><?php echo t('phone'); ?></label>
                            <input type="text" class="form-control" name="phone" value="<?php echo $_POST['phone'] ?? ''; ?>" placeholder="<?php echo t('phone'); ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label"><?php echo t('bilti'); ?></label>
                            <input type="text" class="form-control" name="bilti" value="<?php echo $_POST['bilti'] ?? ''; ?>" placeholder="<?php echo t('bilti'); ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label"><?php echo t('details'); ?></label>
                            <input type="text" class="form-control" name="details" value="<?php echo $_POST['details'] ?? ''; ?>" placeholder="<?php echo t('details'); ?>">
                        </div>
                        <!-- <div class="col-md-2">
                            <label class="form-label"><?php echo t('rate_type'); ?></label>
                            <div class="d-flex flex-column gap-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="rate_type" id="rate_kilo" value="kilo" <?php echo ($_POST['rate_type'] ?? 'kilo') == 'kilo' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="rate_kilo"><?php echo t('rate_kilo'); ?></label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="rate_type" id="rate_mann" value="mann" <?php echo ($_POST['rate_type'] ?? '') == 'mann' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="rate_mann"><?php echo t('rate_mann'); ?></label>
                                </div>
                            </div>
                        </div> -->
                    </div>
                    
                    <!-- Item Entry Section -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-box"></i> <?php echo t('item_info'); ?></h6>
                                </div>
                                <div class="card-body">
                                    <!-- Item Entry Row -->
                                    <div class="row align-items-end mb-3" style="display: flex; flex-wrap: nowrap;">
                                        <div class="col-md-3" style="flex: 0 0 25%; max-width: 25%;">
                                            <label class="form-label"><?php echo t('item_name'); ?></label>
                                            <select class="form-select item-select" id="itemSelect" name="item_id_temp">
                                                <option value="">-- <?php echo t('select'); ?> --</option>
                                                <?php foreach ($items as $item): 
                                                    // Extract purchase_rate_mann from description if available
                                                    $purchaseRateMann = $item['purchase_rate_mann'] ?? ($item['purchase_rate'] * 40);
                                                    if (isset($item['description']) && preg_match('/Purchase Rate Mann:\s*([0-9.]+)/', $item['description'], $matches)) {
                                                        $purchaseRateMann = floatval($matches[1]);
                                                    }
                                                ?>
                                                    <option value="<?php echo $item['id']; ?>" data-rate-kilo="<?php echo $item['purchase_rate']; ?>" data-rate-mann="<?php echo $purchaseRateMann; ?>">
                                                        <?php echo htmlspecialchars($item['item_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-1 numeric-field" style="flex: 0 0 10%; max-width: 10%;">
                                            <label class="form-label"><?php echo t('qty'); ?></label>
                                            <input type="number" step="0.01" class="form-control" id="itemQty" placeholder="0.00">
                                        </div>
                                        <div class="col-md-1 numeric-field" style="flex: 0 0 10%; max-width: 10%;">
                                            <label class="form-label"><?php echo t('bharti'); ?></label>
                                            <input type="number" step="0.01" class="form-control" id="itemBag" placeholder="0.00">
                                        </div>
                                        <div class="col-md-1 numeric-field" style="flex: 0 0 10%; max-width: 10%;">
                                            <label class="form-label"><?php echo t('weight'); ?></label>
                                            <input type="number" step="0.01" class="form-control" id="itemWt">
                                        </div>
                                        <div class="col-md-1 numeric-field" style="flex: 0 0 10%; max-width: 10%;">
                                            <label class="form-label"><?php echo t('cut'); ?></label>
                                            <input type="number" step="0.01" class="form-control" id="itemKate" placeholder="0.00">
                                        </div>
                                        <div class="col-md-1 numeric-field" style="flex: 0 0 10%; max-width: 10%;">
                                            <label class="form-label"><?php echo t('rate'); ?></label>
                                            <input type="number" step="0.01" class="form-control" id="itemRate" placeholder="0.00">
                                        </div>
                                        <div class="col-md-1 numeric-field" style="flex: 0 0 10%; max-width: 10%;">
                                            <label class="form-label"><?php echo t('amount'); ?></label>
                                            <input type="text" class="form-control" id="itemAmount" readonly>
                                        </div>
                                        <div class="col-md-1" style="flex: 0 0 10%; max-width: 10%;">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-success w-100" id="addItemBtn" title="<?php echo t('enter'); ?>">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Items Table -->
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="itemsTable">
                                            <thead>
                                                <tr>
                                                    <th width="25%"><?php echo t('goods_account'); ?></th>
                                                    <th width="12%"><?php echo t('quantity'); ?></th>
                                                    <th width="12%"><?php echo t('weight'); ?></th>
                                                    <th width="10%"><?php echo t('cut'); ?></th>
                                                    <th width="12%"><?php echo t('rate'); ?></th>
                                                    <th width="15%"><?php echo t('amount'); ?></th>
                                                    <?php /* <th width="6%">D/E</th> */ ?>
                                                    <th width="8%"><?php echo t('actions'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody id="itemsBody">
                                                <!-- Items will be added here dynamically -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Expenses Section -->
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-calculator"></i> <?php echo t('expenses'); ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 mb-2">
                                                <label class="form-label"><?php echo t('rent'); ?></label>
                                                <input type="number" step="0.01" class="form-control" name="rent" id="rent" value="<?php echo $_POST['rent'] ?? 0; ?>" placeholder="0.00">
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <label class="form-label"><?php echo t('loading'); ?></label>
                                                <input type="number" step="0.01" class="form-control" name="loading" id="loading" value="<?php echo $_POST['loading'] ?? 0; ?>" placeholder="0.00">
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <label class="form-label"><?php echo t('labor'); ?></label>
                                                <input type="number" step="0.01" class="form-control" name="labor" id="labor" value="<?php echo $_POST['labor'] ?? 0; ?>" placeholder="0.00">
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <label class="form-label"><?php echo t('brokerage'); ?></label>
                                                <input type="number" step="0.01" class="form-control" name="brokerage" id="brokerage" value="<?php echo $_POST['brokerage'] ?? 0; ?>" placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Summary Section -->
                    <div class="row mb-3">
                        <?php /*
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-sticky-note"></i> <?php echo t('remarks'); ?></h6>
                                </div>
                                <div class="card-body">
                                    <textarea class="form-control" name="remarks" rows="4" placeholder="<?php echo t('remarks'); ?>..."><?php echo $_POST['remarks'] ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                        */ ?>
                        
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-receipt"></i> <?php echo t('summary'); ?></h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label"><?php echo t('total_qty'); ?></label>
                                            <input type="text" class="form-control" id="total_qty" readonly value="0.00">
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label"><?php echo t('total_weight'); ?></label>
                                            <input type="text" class="form-control" id="total_weight" readonly value="0.00">
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label"><?php echo t('total_amount_label'); ?></label>
                                            <input type="text" class="form-control" id="total_amount" readonly value="0.00">
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label"><?php echo t('total_exp'); ?></label>
                                            <input type="text" class="form-control" id="total_expenses" readonly value="0.00">
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label"><strong><?php echo t('grand_total'); ?></strong></label>
                                            <input type="text" class="form-control" id="grand_total" readonly value="0.00">
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label"><?php echo t('discount'); ?></label>
                                            <input type="number" step="0.01" class="form-control" name="discount" id="discount" value="<?php echo $_POST['discount'] ?? 0; ?>" placeholder="0.00">
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label"><?php echo t('paid_amount'); ?></label>
                                            <input type="number" step="0.01" class="form-control" name="paid_amount" id="paid_amount" value="<?php echo $_POST['paid_amount'] ?? 0; ?>" placeholder="0.00">
                                        </div>
                                        <div class="col-md-3 mb-0">
                                            <label class="form-label"><strong><?php echo t('balance_amount'); ?></strong></label>
                                            <input type="text" class="form-control" id="balance_amount" readonly value="0.00">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="<?php echo BASE_URL; ?>purchases/list.php" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> <?php echo t('save'); ?> <?php echo t('purchase'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
// Format number with commas
function formatNumber(num) {
    if (isNaN(num) || num === null || num === undefined) {
        return '0.00';
    }
    num = parseFloat(num);
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

$(document).ready(function() {
    var itemCounter = 0;
    
    // Calculate weight (qty × bharti) - auto-fill
    // Formula: Weight = Qty × Bharti
    $('#itemQty, #itemBag').on('input', function() {
        var qty = parseFloat($('#itemQty').val()) || 0;
        var bag = parseFloat($('#itemBag').val()) || 0;
        
        // Always calculate weight: Qty × Bharti
        var calculatedWt = qty * bag;
        $('#itemWt').val(calculatedWt > 0 ? formatNumber(calculatedWt) : '');
        calculateItemAmount();
    });
    
    // Also trigger calculation when weight is manually edited
    $('#itemWt').on('input', function() {
        calculateItemAmount();
    });
    
    // Calculate amount (net weight * rate) - also trigger when weight is manually edited
    $('#itemWt, #itemKate, #itemRate').on('input', function() {
        calculateItemAmount();
    });
    
    function calculateItemAmount() {
        var wt = parseFloat($('#itemWt').val()) || 0;
        var kate = parseFloat($('#itemKate').val()) || 0;
        var rate = parseFloat($('#itemRate').val()) || 0;
        var netWeight = wt - kate;
        var amount = netWeight * rate;
        $('#itemAmount').val(formatNumber(amount));
    }
    
    // Rate must be entered manually - no auto-population
    // Removed automatic rate setting when item is selected
    // Removed automatic rate update when rate type changes
    
    // Add item to table
    $('#addItemBtn').on('click', function() {
        var itemId = $('#itemSelect').val();
        var itemName = $('#itemSelect option:selected').text();
        var qty = parseFloat($('#itemQty').val()) || 0;
        var bag = parseFloat($('#itemBag').val()) || 0;
        var wt = parseFloat($('#itemWt').val()) || 0;
        var kate = parseFloat($('#itemKate').val()) || 0;
        var rate = parseFloat($('#itemRate').val()) || 0;
        var amount = parseFloat($('#itemAmount').val().replace(/,/g, '')) || 0;
        var netWeight = wt - kate;
        
        if (!itemId || !wt || !rate) {
            alert('<?php echo t('please_enter_item_details'); ?>');
            return;
        }
        
        var newRow = `
            <tr data-item-id="${itemId}" data-qty="${qty}" data-bag="${bag}" data-wt="${wt}" data-kate="${kate}" data-rate="${rate}" data-amount="${amount}">
                <td>${itemName}</td>
                <td>${formatNumber(netWeight)}</td>
                <td>${formatNumber(wt)}</td>
                <td>${formatNumber(kate)}</td>
                <td>${formatNumber(rate)}</td>
                <td>${formatNumber(amount)}</td>
                <!-- <td>D</td> -->
                <td>
                    <input type="hidden" name="item_id[]" value="${itemId}">
                    <input type="hidden" name="qty[]" value="${qty}">
                    <input type="hidden" name="bag[]" value="${bag}">
                    <input type="hidden" name="wt[]" value="${wt}">
                    <input type="hidden" name="kate[]" value="${kate}">
                    <input type="hidden" name="rate[]" value="${rate}">
                    <input type="hidden" name="amount[]" value="${amount}">
                    <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-times"></i></button>
                </td>
            </tr>
        `;
        $('#itemsBody').append(newRow);
        
        // Clear input fields
        $('#itemSelect').val('');
        $('#itemQty').val('');
        $('#itemBag').val('');
        $('#itemWt').val('');
        $('#itemKate').val('');
        $('#itemRate').val('');
        $('#itemAmount').val('');
        
        calculateTotals();
    });
    
    // Remove row
    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        calculateTotals();
    });
    
    // Calculate totals
    function calculateTotals() {
        var totalQty = 0;
        var totalWeight = 0;
        var totalAmount = 0;
        
        $('#itemsBody tr').each(function() {
            var qty = parseFloat($(this).data('qty')) || 0;
            var bag = parseFloat($(this).data('bag')) || 0;
            var wt = parseFloat($(this).data('wt')) || 0;
            var kate = parseFloat($(this).data('kate')) || 0;
            var amount = parseFloat($(this).data('amount')) || 0;
            
            var netWeight = wt - kate;
            totalQty += netWeight;
            totalWeight += wt;
            totalAmount += amount;
        });
        
        $('#total_qty').val(formatNumber(totalQty));
        $('#total_weight').val(formatNumber(totalWeight));
        $('#total_amount').val(formatNumber(totalAmount));
        
        // Calculate expenses
        var rent = parseFloat($('#rent').val()) || 0;
        var loading = parseFloat($('#loading').val()) || 0;
        var labor = parseFloat($('#labor').val()) || 0;
        var brokerage = parseFloat($('#brokerage').val()) || 0;
        var totalExpenses = rent + loading + labor + brokerage;
        $('#total_expenses').val(formatNumber(totalExpenses));
        
        // Calculate grand total
        var discount = parseFloat(String($('#discount').val()).replace(/,/g, '')) || 0;
        var netAmount = totalAmount - discount;
        var grandTotal = netAmount + totalExpenses;
        $('#grand_total').val(formatNumber(grandTotal));
        
        // Calculate balance
        var paid = parseFloat(String($('#paid_amount').val()).replace(/,/g, '')) || 0;
        var balance = grandTotal - paid;
        $('#balance_amount').val(formatNumber(balance));
    }
    
    // Update totals when expenses, discount, or paid amount changes
    $('#rent, #loading, #labor, #brokerage, #discount, #paid_amount').on('input', calculateTotals);
});
</script>

<?php include '../includes/footer.php'; ?>

