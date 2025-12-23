<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => t('invalid_request_method')]);
    exit;
}

$transactionDate = $_POST['transaction_date'] ?? date('Y-m-d');
$accountId = intval($_POST['account_id'] ?? 0);
$amount = floatval($_POST['amount'] ?? 0);
$narration = sanitizeInput($_POST['narration'] ?? '');

if (empty($accountId)) {
    echo json_encode(['success' => false, 'message' => t('please_select_account')]);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => t('please_enter_amount')]);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Generate transaction number
    $stmt = $db->query("SELECT MAX(id) as max_id FROM transactions");
    $maxId = $stmt->fetch()['max_id'] ?? 0;
    $transactionNo = generateCode('CRD', $maxId);
    
    // Insert transaction
    $stmt = $db->prepare("INSERT INTO transactions (transaction_no, transaction_date, transaction_type, account_id, amount, narration, created_by) VALUES (?, ?, 'credit', ?, ?, ?, ?)");
    $stmt->execute([$transactionNo, $transactionDate, $accountId, $amount, $narration, $_SESSION['user_id']]);
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => t('transaction_recorded_success')]);
} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => t('error_recording_transaction')]);
}
?>

