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
    echo json_encode(['success' => false, 'message' => t('invalid_account_id')]);
    exit;
}

try {
    $db = getDB();
    
    // Check if account exists
    $stmt = $db->prepare("SELECT id FROM accounts WHERE id = ?");
    $stmt->execute([$id]);
    $account = $stmt->fetch();
    
    if (!$account) {
        echo json_encode(['success' => false, 'message' => t('account_not_found')]);
        exit;
    }
    
    // Check if account is used in transactions, sales, or purchases
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM transactions WHERE account_id = ?");
    $stmt->execute([$id]);
    $transactionCount = $stmt->fetch()['count'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM sales WHERE account_id = ?");
    $stmt->execute([$id]);
    $saleCount = $stmt->fetch()['count'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM purchases WHERE account_id = ?");
    $stmt->execute([$id]);
    $purchaseCount = $stmt->fetch()['count'];
    
    if ($transactionCount > 0 || $saleCount > 0 || $purchaseCount > 0) {
        // Soft delete - set status to inactive
        $stmt = $db->prepare("UPDATE accounts SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => t('account_deleted_success')]);
    } else {
        // Hard delete if not used
        $stmt = $db->prepare("DELETE FROM accounts WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => t('account_deleted_success')]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => t('error_deleting_account') . ': ' . $e->getMessage()]);
}
?>

