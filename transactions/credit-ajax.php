<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => t('invalid_request_method')]);
    exit;
}

$transactionNo = trim(sanitizeInput($_POST['transaction_no'] ?? ''));
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
    
    // Generate transaction number if not provided
    if (empty($transactionNo)) {
        $stmt = $db->query("SELECT MAX(id) as max_id FROM transactions WHERE transaction_type = 'credit' AND (reference_type IS NULL OR reference_type != 'journal')");
        $maxId = $stmt->fetch()['max_id'] ?? 0;
        $nextNumber = $maxId + 1;
        $transactionNo = 'Crd' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
    } else {
        // Check if transaction number already exists
        $stmt = $db->prepare("SELECT id FROM transactions WHERE transaction_no = ?");
        $stmt->execute([$transactionNo]);
        if ($stmt->fetch()) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => t('transaction_no_already_exists')]);
            exit;
        }
    }
    
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

