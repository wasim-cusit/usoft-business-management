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
    echo json_encode(['success' => false, 'message' => t('invalid_transaction_id')]);
    exit;
}

try {
    $db = getDB();
    
    // Get transaction details
    $stmt = $db->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->execute([$id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => t('transaction_not_found')]);
        exit;
    }
    
    // Check if transaction is referenced in sales or purchases
    // Note: Transactions can be referenced, but we'll allow deletion
    // If needed, you can add checks here
    
    // Delete the transaction
    $stmt = $db->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'message' => t('transaction_deleted_success')
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => t('error_deleting_transaction')
    ]);
}
?>

