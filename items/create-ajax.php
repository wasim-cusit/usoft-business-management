<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => t('invalid_request_method')]);
    exit;
}

$itemCode = sanitizeInput($_POST['item_code'] ?? '');
$itemName = sanitizeInput($_POST['item_name'] ?? '');
$itemNameUrdu = sanitizeInput($_POST['item_name_urdu'] ?? '');
$category = sanitizeInput($_POST['category'] ?? '');
$unit = sanitizeInput($_POST['unit'] ?? 'pcs');
$purchaseRate = floatval($_POST['purchase_rate'] ?? 0);
$saleRate = floatval($_POST['sale_rate'] ?? 0);
$openingStock = floatval($_POST['opening_stock'] ?? 0);
$minStock = floatval($_POST['min_stock'] ?? 0);
$description = sanitizeInput($_POST['description'] ?? '');

if (empty($itemName)) {
    echo json_encode(['success' => false, 'message' => t('please_enter_item_name')]);
    exit;
}

try {
    $db = getDB();
    
    // Generate item code if not provided
    if (empty($itemCode)) {
        $stmt = $db->query("SELECT MAX(id) as max_id FROM items");
        $maxId = $stmt->fetch()['max_id'] ?? 0;
        $itemCode = generateCode('ITM', $maxId);
    }
    
    $stmt = $db->prepare("INSERT INTO items (item_code, item_name, item_name_urdu, category, unit, purchase_rate, sale_rate, opening_stock, current_stock, min_stock, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$itemCode, $itemName, $itemNameUrdu, $category, $unit, $purchaseRate, $saleRate, $openingStock, $openingStock, $minStock, $description]);
    
    // Add to stock movements
    $itemId = $db->lastInsertId();
    $stmt = $db->prepare("INSERT INTO stock_movements (item_id, movement_date, movement_type, quantity_in, balance_quantity) VALUES (?, ?, 'opening', ?, ?)");
    $stmt->execute([$itemId, date('Y-m-d'), $openingStock, $openingStock]);
    
    echo json_encode(['success' => true, 'message' => t('item_added_success')]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => t('error_adding_item')]);
}
?>

