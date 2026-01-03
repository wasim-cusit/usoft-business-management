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
    
    // Get sale details
    $stmt = $db->prepare("SELECT s.*, a.account_name, a.account_name_urdu FROM sales s 
                         LEFT JOIN accounts a ON s.account_id = a.id 
                         WHERE s.id = ?");
    $stmt->execute([$id]);
    $sale = $stmt->fetch();
    
    if (!$sale) {
        echo json_encode(['success' => false, 'message' => t('sale_not_found')]);
        exit;
    }
    
    // Get sale items
    $stmt = $db->prepare("SELECT si.*, i.item_name, i.item_name_urdu, i.unit FROM sale_items si 
                         LEFT JOIN items i ON si.item_id = i.id 
                         WHERE si.sale_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();
    
    // Format invoice message
    $message = "*" . t('sale_invoice') . "*\n";
    $message .= t('bill_no') . ": " . $sale['sale_no'] . "\n";
    $message .= t('date') . ": " . formatDate($sale['sale_date']) . "\n";
    $message .= t('customer') . ": " . displayAccountNameFull($sale) . "\n\n";
    $message .= "*" . t('items') . ":*\n";
    
    foreach ($items as $item) {
        $itemName = !empty($item['item_name_urdu']) ? $item['item_name'] . ' / ' . $item['item_name_urdu'] : $item['item_name'];
        $message .= "• " . $itemName . "\n";
        $message .= "  " . t('quantity') . ": " . formatStock($item['quantity']) . " " . ($item['unit'] ?? '') . "\n";
        $message .= "  " . t('rate') . ": " . formatCurrency($item['rate']) . "\n";
        $message .= "  " . t('amount') . ": " . formatCurrency($item['amount']) . "\n\n";
    }
    
    $message .= "*" . t('total') . ":* " . formatCurrency($sale['total_amount']) . "\n";
    if ($sale['discount'] > 0) {
        $message .= t('discount') . ": " . formatCurrency($sale['discount']) . "\n";
    }
    $message .= "*" . t('net_amount') . ":* " . formatCurrency($sale['net_amount']) . "\n";
    $message .= t('paid_amount') . ": " . formatCurrency($sale['paid_amount']) . "\n";
    if ($sale['balance_amount'] > 0) {
        $message .= t('balance') . ": " . formatCurrency($sale['balance_amount']) . "\n";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'sale_no' => $sale['sale_no']
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => t('error_fetching_invoice')]);
}


