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
$unit = sanitizeInput($_POST['unit'] ?? 'kg');
$purchaseRate = floatval($_POST['purchase_rate'] ?? 0);
$saleRate = floatval($_POST['sale_rate'] ?? 0);
$openingStock = floatval($_POST['opening_stock'] ?? 0);
$minStock = floatval($_POST['min_stock'] ?? 0);
// Store additional info in description if needed
$purchaseRateMann = floatval($_POST['purchase_rate_mann'] ?? 0);
$saleRateMann = floatval($_POST['sale_rate_mann'] ?? 0);
$companyName = sanitizeInput($_POST['company_name'] ?? '');
$description = sanitizeInput($_POST['description'] ?? '');

// Build description with additional fields
$descParts = [];
if (!empty($description)) {
    $descParts[] = $description;
}
if ($purchaseRateMann > 0) {
    $descParts[] = 'Purchase Rate Mann: ' . $purchaseRateMann;
}
if ($saleRateMann > 0) {
    $descParts[] = 'Sale Rate Mann: ' . $saleRateMann;
}
if (!empty($companyName)) {
    $descParts[] = 'Company: ' . $companyName;
}
$fullDescription = implode(' | ', $descParts);

// Item name validation commented out as field is hidden
// if (empty($itemName)) {
//     echo json_encode(['success' => false, 'message' => t('please_enter_item_name')]);
//     exit;
// }

// Use fallback if item_name is empty
if (empty($itemName)) {
    if (!empty($itemNameUrdu)) {
        $itemName = $itemNameUrdu;
    } elseif (!empty($itemCode)) {
        $itemName = $itemCode;
    } else {
        $itemName = 'Item';
    }
}

try {
    $db = getDB();
    
    // Generate item code if not provided (Itm01, Itm02, etc.)
    if (empty($itemCode)) {
        $stmt = $db->query("SELECT MAX(id) as max_id FROM items");
        $maxId = $stmt->fetch()['max_id'] ?? 0;
        $nextNumber = $maxId + 1;
        $itemCode = 'Itm' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
    }
    
    $stmt = $db->prepare("INSERT INTO items (item_code, item_name, item_name_urdu, category, unit, purchase_rate, sale_rate, opening_stock, current_stock, min_stock, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$itemCode, $itemName, $itemNameUrdu, $category, $unit, $purchaseRate, $saleRate, $openingStock, $openingStock, $minStock, $fullDescription]);
    
    // Add to stock movements
    $itemId = $db->lastInsertId();
    $stmt = $db->prepare("INSERT INTO stock_movements (item_id, movement_date, movement_type, quantity_in, balance_quantity) VALUES (?, ?, 'opening', ?, ?)");
    $stmt->execute([$itemId, date('Y-m-d'), $openingStock, $openingStock]);
    
    echo json_encode(['success' => true, 'message' => t('item_added_success')]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => t('error_adding_item')]);
}
?>

