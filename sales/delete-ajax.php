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
    echo json_encode(['success' => false, 'message' => t('invalid_sale_id')]);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Check if sale exists
    $stmt = $db->prepare("SELECT id, sale_no FROM sales WHERE id = ?");
    $stmt->execute([$id]);
    $sale = $stmt->fetch();
    
    if (!$sale) {
        echo json_encode(['success' => false, 'message' => t('sale_not_found')]);
        exit;
    }
    
    // Get sale items to restore stock - use wt2 (new structure)
    $stmt = $db->prepare("SELECT item_id, wt2 FROM sale_items WHERE sale_id = ?");
    $stmt->execute([$id]);
    $saleItems = $stmt->fetchAll();
    
    // Restore stock for each item (sales reduce stock, so deletion should add back)
    foreach ($saleItems as $item) {
        // Restore stock (add back what was sold) - use wt2
        $qtyToRestore = floatval($item['wt2'] ?? 0);
        $stmt = $db->prepare("UPDATE items SET current_stock = current_stock + ? WHERE id = ?");
        $stmt->execute([$qtyToRestore, $item['item_id']]);
        
        // Remove stock movements related to this sale
        $stmt = $db->prepare("DELETE FROM stock_movements WHERE reference_type = 'sale' AND reference_id = ?");
        $stmt->execute([$id]);
    }
    
    // Delete related transactions
    $stmt = $db->prepare("DELETE FROM transactions WHERE reference_type = 'sale' AND reference_id = ?");
    $stmt->execute([$id]);
    
    // Delete sale items
    $stmt = $db->prepare("DELETE FROM sale_items WHERE sale_id = ?");
    $stmt->execute([$id]);
    
    // Delete sale
    $stmt = $db->prepare("DELETE FROM sales WHERE id = ?");
    $stmt->execute([$id]);
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => t('sale_deleted_success')]);
} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => t('error_deleting_sale') . ': ' . $e->getMessage()]);
}
?>

