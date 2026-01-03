<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => t('invalid_request_method')]);
    exit;
}

$id = intval($_POST['id'] ?? 0);

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => t('invalid_purchase_id')]);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Check if purchase exists
    $stmt = $db->prepare("SELECT id, purchase_no FROM purchases WHERE id = ?");
    $stmt->execute([$id]);
    $purchase = $stmt->fetch();
    
    if (!$purchase) {
        echo json_encode(['success' => false, 'message' => t('purchase_not_found')]);
        exit;
    }
    
    // Get purchase items to restore stock
    $stmt = $db->prepare("SELECT item_id, quantity FROM purchase_items WHERE purchase_id = ?");
    $stmt->execute([$id]);
    $purchaseItems = $stmt->fetchAll();
    
    // Reverse stock changes for each item (purchases add stock, so deletion should subtract)
    foreach ($purchaseItems as $item) {
        // Prevent negative stock
        $stmt = $db->prepare("UPDATE items SET current_stock = GREATEST(0, current_stock - ?) WHERE id = ?");
        $stmt->execute([$item['quantity'], $item['item_id']]);
        
        // Remove stock movements related to this purchase
        $stmt = $db->prepare("DELETE FROM stock_movements WHERE reference_type = 'purchase' AND reference_id = ?");
        $stmt->execute([$id]);
    }
    
    // Delete related transactions
    $stmt = $db->prepare("DELETE FROM transactions WHERE reference_type = 'purchase' AND reference_id = ?");
    $stmt->execute([$id]);
    
    // Delete purchase items
    $stmt = $db->prepare("DELETE FROM purchase_items WHERE purchase_id = ?");
    $stmt->execute([$id]);
    
    // Delete purchase
    $stmt = $db->prepare("DELETE FROM purchases WHERE id = ?");
    $stmt->execute([$id]);
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => t('purchase_deleted_success')]);
} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => t('error_deleting_purchase') . ': ' . $e->getMessage()]);
}
?>

