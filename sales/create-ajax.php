<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => t('invalid_request_method')]);
    exit;
}

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

if (empty($accountId)) {
    echo json_encode(['success' => false, 'message' => t('please_select_customer')]);
    exit;
}

if (empty($itemIds) || !is_array($itemIds)) {
    echo json_encode(['success' => false, 'message' => t('please_add_item')]);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Calculate totals and check stock
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
    
    // Check if cash sale
    $isCashSale = false;
    $stmt = $db->query("SELECT id FROM accounts WHERE account_name = 'Cash Sale' OR account_name_urdu = 'کیش فروخت' LIMIT 1");
    $cashAccount = $stmt->fetch();
    $cashAccountId = $cashAccount ? $cashAccount['id'] : 0;
    if ($cashAccountId > 0 && $accountId == $cashAccountId) {
        $isCashSale = true;
        $paidAmount = $netAmount; // Auto-set paid amount for cash sales
        $balanceAmount = 0;
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
    
    // Generate sale number (Sal01, Sal02, etc.)
    $stmt = $db->query("SELECT MAX(id) as max_id FROM sales");
    $maxId = $stmt->fetch()['max_id'] ?? 0;
    $nextNumber = $maxId + 1;
    $saleNo = 'Sal' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
    
    // Insert sale - try with new fields first, fallback if columns don't exist
    try {
        $stmt = $db->prepare("INSERT INTO sales (sale_no, sale_date, account_id, location, details, phone, bilti, total_amount, discount, net_amount, paid_amount, balance_amount, bardana, netcash, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$saleNo, $saleDate, $accountId, $location, $details, $phone, $bilti, $totalAmount, $discount, $netAmount, $paidAmount, $balanceAmount, $bardana, $netcash, $remarks, $_SESSION['user_id']]);
    } catch (PDOException $e) {
        // Fallback if new columns don't exist yet
        if (strpos($e->getMessage(), 'Unknown column') !== false) {
            $stmt = $db->prepare("INSERT INTO sales (sale_no, sale_date, account_id, total_amount, discount, net_amount, paid_amount, balance_amount, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$saleNo, $saleDate, $accountId, $totalAmount, $discount, $netAmount, $paidAmount, $balanceAmount, $remarks, $_SESSION['user_id']]);
        } else {
            throw $e;
        }
    }
    
    $saleId = $db->lastInsertId();
    
    // Collect stock warnings
    $stockWarnings = [];
    
    // Insert sale items and update stock
    foreach ($validItems as $item) {
                // Insert with new calculation fields structure (quantity = wt2 for consistency)
                $stmt = $db->prepare("INSERT INTO sale_items (sale_id, item_id, qty, narch, bag, wt, kate, wt2, rate, amount, quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$saleId, $item['item_id'], $item['qty'], $item['narch'], $item['bag'], $item['wt'], $item['kate'], $item['wt2'], $item['rate'], $item['amount'], $item['wt2']]);
        
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
        $stmt->execute([$item['item_id'], $saleDate, $saleId, $item['quantity'], $currentStock]);
    }
    
    // Handle transactions properly for credit/debit accounting:
    // 1. Create DEBIT transaction for the sale receivable (net_amount - what customer owes)
    // 2. Create CREDIT transaction for payment received (reduces receivable)
    // This ensures proper double-entry: DEBIT (receivable) - CREDIT (payment) = Balance
    
    // Create DEBIT transaction for receivable (customer owes us this amount)
    // DEBIT increases receivable (asset) - customer owes us
    if ($netAmount > 0) {
        $stmt = $db->prepare("INSERT INTO transactions (transaction_date, transaction_type, account_id, amount, narration, reference_type, reference_id, created_by) VALUES (?, 'debit', ?, ?, ?, 'sale', ?, ?)");
        $stmt->execute([$saleDate, $accountId, $netAmount, "Sale Receivable: $saleNo", $saleId, $_SESSION['user_id']]);
    }
    
    // Create CREDIT transaction for payment received (reduces receivable)
    // CREDIT reduces receivable - customer paid us, reduces what they owe
    if ($paidAmount > 0) {
        $stmt = $db->prepare("INSERT INTO transactions (transaction_date, transaction_type, account_id, amount, narration, reference_type, reference_id, created_by) VALUES (?, 'credit', ?, ?, ?, 'sale', ?, ?)");
        $stmt->execute([$saleDate, $accountId, $paidAmount, "Sale Payment: $saleNo", $saleId, $_SESSION['user_id']]);
    }
    
    // Net effect: DEBIT (net_amount) - CREDIT (paid_amount) = balance_amount (what customer still owes)
    
    $db->commit();
    
    // Build response message
    $message = t('sale_added_success');
    if (!empty($stockWarnings)) {
        $warningMsg = '<br><strong>' . t('stock_warning') . ':</strong><br>';
        foreach ($stockWarnings as $warning) {
            $warningMsg .= $warning['item_name'] . ': ' . t('available_stock') . ' = ' . $warning['available_stock'] . ', ' . t('required_quantity') . ' = ' . $warning['required_quantity'] . ', ' . t('stock_shortage') . ' = ' . $warning['shortage'] . '<br>';
        }
        $message .= $warningMsg;
    }
    
    echo json_encode(['success' => true, 'message' => $message, 'warnings' => $stockWarnings]);
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => t('error_adding_sale')]);
}
?>

