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
$discount = floatval($_POST['discount'] ?? 0);
$paidAmount = floatval($_POST['paid_amount'] ?? 0);
$remarks = sanitizeInput($_POST['remarks'] ?? '');
$itemIds = $_POST['item_id'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$rates = $_POST['rate'] ?? [];

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
    $validItems = [];
    for ($i = 0; $i < count($itemIds); $i++) {
        if (!empty($itemIds[$i]) && !empty($quantities[$i]) && !empty($rates[$i])) {
            $qty = floatval($quantities[$i]);
            $rate = floatval($rates[$i]);
            $amount = $qty * $rate;
            $totalAmount += $amount;
            $validItems[] = [
                'item_id' => intval($itemIds[$i]),
                'quantity' => $qty,
                'rate' => $rate,
                'amount' => $amount
            ];
        }
    }
    
    if (empty($validItems)) {
        throw new Exception(t('please_enter_item_details'));
    }
    
    $netAmount = $totalAmount - $discount;
    $balanceAmount = $netAmount - $paidAmount;
    
    // Generate purchase number
    $stmt = $db->query("SELECT MAX(id) as max_id FROM purchases");
    $maxId = $stmt->fetch()['max_id'] ?? 0;
    $purchaseNo = generateCode('PUR', $maxId);
    
    // Insert purchase
    $stmt = $db->prepare("INSERT INTO purchases (purchase_no, purchase_date, account_id, total_amount, discount, net_amount, paid_amount, balance_amount, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$purchaseNo, $purchaseDate, $accountId, $totalAmount, $discount, $netAmount, $paidAmount, $balanceAmount, $remarks, $_SESSION['user_id']]);
    
    $purchaseId = $db->lastInsertId();
    
    // Insert purchase items and update stock
    foreach ($validItems as $item) {
        $stmt = $db->prepare("INSERT INTO purchase_items (purchase_id, item_id, quantity, rate, amount) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$purchaseId, $item['item_id'], $item['quantity'], $item['rate'], $item['amount']]);
        
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

