<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => t('invalid_request_method')]);
    exit;
}

$transactionDate = $_POST['transaction_date'] ?? date('Y-m-d');
$debitAccountId = intval($_POST['debit_account_id'] ?? 0);
$creditAccountId = intval($_POST['credit_account_id'] ?? 0);
$amount = floatval($_POST['amount'] ?? 0);
$narration = sanitizeInput($_POST['narration'] ?? '');

if (empty($debitAccountId) || empty($creditAccountId)) {
    echo json_encode(['success' => false, 'message' => t('both_accounts_required')]);
    exit;
}

if ($debitAccountId == $creditAccountId) {
    echo json_encode(['success' => false, 'message' => t('accounts_cannot_same')]);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => t('please_enter_amount')]);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Generate transaction number (Jv01, Jv02, etc.)
    $stmt = $db->query("SELECT MAX(id) as max_id FROM transactions");
    $maxId = $stmt->fetch()['max_id'] ?? 0;
    $nextNumber = $maxId + 1;
    $transactionNo = 'Jv' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
    
    // Insert debit transaction
    $stmt = $db->prepare("INSERT INTO transactions (transaction_no, transaction_date, transaction_type, account_id, amount, narration, reference_type, created_by) VALUES (?, ?, 'debit', ?, ?, ?, 'journal', ?)");
    $stmt->execute([$transactionNo . '-D', $transactionDate, $debitAccountId, $amount, $narration, $_SESSION['user_id']]);
    
    // Insert credit transaction
    $stmt = $db->prepare("INSERT INTO transactions (transaction_no, transaction_date, transaction_type, account_id, amount, narration, reference_type, created_by) VALUES (?, ?, 'credit', ?, ?, ?, 'journal', ?)");
    $stmt->execute([$transactionNo . '-C', $transactionDate, $creditAccountId, $amount, $narration, $_SESSION['user_id']]);
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => t('journal_voucher_success')]);
} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => t('error_recording_journal')]);
}
?>

