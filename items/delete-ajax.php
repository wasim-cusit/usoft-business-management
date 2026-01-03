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
    echo json_encode(['success' => false, 'message' => t('invalid_item_id')]);
    exit;
}

try {
    $db = getDB();
    
    // Check if item exists
    $stmt = $db->prepare("SELECT id FROM items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        echo json_encode(['success' => false, 'message' => t('item_not_found')]);
        exit;
    }
    
    // Check if item is used in sales or purchases
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM sale_items WHERE item_id = ?");
    $stmt->execute([$id]);
    $saleCount = $stmt->fetch()['count'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM purchase_items WHERE item_id = ?");
    $stmt->execute([$id]);
    $purchaseCount = $stmt->fetch()['count'];
    
    if ($saleCount > 0 || $purchaseCount > 0) {
        // Soft delete - set status to inactive
        $stmt = $db->prepare("UPDATE items SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => t('item_deleted_success')]);
    } else {
        // Hard delete if not used
        $stmt = $db->prepare("DELETE FROM items WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => t('item_deleted_success')]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => t('error_deleting_item') . ': ' . $e->getMessage()]);
}
?>

