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
    
    // Get purchase details
    $stmt = $db->prepare("SELECT p.*, a.account_name, a.account_name_urdu FROM purchases p 
                         LEFT JOIN accounts a ON p.account_id = a.id 
                         WHERE p.id = ?");
    $stmt->execute([$id]);
    $purchase = $stmt->fetch();
    
    if (!$purchase) {
        echo json_encode(['success' => false, 'message' => t('purchase_not_found')]);
        exit;
    }
    
    // Get purchase items
    $stmt = $db->prepare("SELECT pi.*, i.item_name, i.item_name_urdu, i.unit FROM purchase_items pi 
                         LEFT JOIN items i ON pi.item_id = i.id 
                         WHERE pi.purchase_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();
    
    // Format invoice message
    $message = "*" . t('purchase_invoice') . "*\n";
    $message .= t('bill_no') . ": " . $purchase['purchase_no'] . "\n";
    $message .= t('date') . ": " . formatDate($purchase['purchase_date']) . "\n";
    $message .= t('supplier') . ": " . displayAccountNameFull($purchase) . "\n\n";
    $message .= "*" . t('items') . ":*\n";
    
    foreach ($items as $item) {
        $itemName = !empty($item['item_name_urdu']) ? $item['item_name'] . ' / ' . $item['item_name_urdu'] : $item['item_name'];
        $message .= "• " . $itemName . "\n";
        $message .= "  " . t('quantity') . ": " . formatStock($item['quantity']) . " " . ($item['unit'] ?? '') . "\n";
        $message .= "  " . t('rate') . ": " . formatCurrency($item['rate']) . "\n";
        $message .= "  " . t('amount') . ": " . formatCurrency($item['amount']) . "\n\n";
    }
    
    $message .= "*" . t('total') . ":* " . formatCurrency($purchase['total_amount']) . "\n";
    if ($purchase['discount'] > 0) {
        $message .= t('discount') . ": " . formatCurrency($purchase['discount']) . "\n";
    }
    $message .= "*" . t('net_amount') . ":* " . formatCurrency($purchase['net_amount']) . "\n";
    $message .= t('paid_amount') . ": " . formatCurrency($purchase['paid_amount']) . "\n";
    if ($purchase['balance_amount'] > 0) {
        $message .= t('balance') . ": " . formatCurrency($purchase['balance_amount']) . "\n";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'purchase_no' => $purchase['purchase_no']
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => t('error_fetching_invoice')]);
}


