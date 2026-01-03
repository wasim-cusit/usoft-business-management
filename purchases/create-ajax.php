<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => t('invalid_request_method')]);
    exit;
}

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
    echo json_encode(['success' => false, 'message' => t('please_select_supplier')]);
    exit;
}

if (empty($itemIds) || !is_array($itemIds)) {
    echo json_encode(['success' => false, 'message' => t('please_add_item')]);
    exit;
}

try {
    $db = getDB();
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
    echo json_encode(['success' => true, 'message' => t('purchase_added_success')]);
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => t('error_adding_purchase')]);
}
?>

