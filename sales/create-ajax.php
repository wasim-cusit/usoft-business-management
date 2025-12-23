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
$discount = floatval($_POST['discount'] ?? 0);
$paidAmount = floatval($_POST['paid_amount'] ?? 0);
$remarks = sanitizeInput($_POST['remarks'] ?? '');
$itemIds = $_POST['item_id'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$rates = $_POST['rate'] ?? [];

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
        if (!empty($itemIds[$i]) && !empty($quantities[$i]) && !empty($rates[$i])) {
            $itemId = intval($itemIds[$i]);
            $qty = floatval($quantities[$i]);
            $rate = floatval($rates[$i]);
            
            // Check stock
            $stmt = $db->prepare("SELECT current_stock, item_name FROM items WHERE id = ?");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch();
            
            if (!$item) {
                throw new Exception(t('item_not_found'));
            }
            
            if ($item['current_stock'] < $qty) {
                throw new Exception($item['item_name'] . ' ' . t('insufficient_stock'));
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
        throw new Exception(t('please_enter_item_details'));
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
        
        // Update item stock (reduce for sale)
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
    echo json_encode(['success' => true, 'message' => t('sale_added_success')]);
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

