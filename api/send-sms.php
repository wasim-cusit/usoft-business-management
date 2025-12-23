<?php
/**
 * SMS API Endpoint
 * Handles AJAX requests for sending SMS
 */

require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => t('invalid_request')]);
    exit;
}

$mobile = sanitizeInput($_POST['mobile'] ?? '');
$message = sanitizeInput($_POST['message'] ?? '');
$accountId = intval($_POST['account_id'] ?? 0);
$type = sanitizeInput($_POST['type'] ?? '');

if (empty($mobile)) {
    echo json_encode(['success' => false, 'message' => t('mobile') . ' ' . t('required')]);
    exit;
}

if (empty($message) && empty($accountId)) {
    echo json_encode(['success' => false, 'message' => t('please_enter_message')]);
    exit;
}

try {
    $db = getDB();
    
    // If account ID is provided, get account details
    if ($accountId > 0) {
        $stmt = $db->prepare("SELECT * FROM accounts WHERE id = ?");
        $stmt->execute([$accountId]);
        $account = $stmt->fetch();
        
        if ($account) {
            $mobile = $account['mobile'] ?? $account['phone'] ?? $mobile;
            
            if (empty($message)) {
                // Generate message based on type
                require_once '../includes/send-sms.php';
                
                if ($type == 'balance') {
                    // Get account balance
                    $stmt = $db->prepare("SELECT 
                        COALESCE(SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END), 0) as total_debit,
                        COALESCE(SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END), 0) as total_credit
                        FROM transactions WHERE account_id = ?");
                    $stmt->execute([$accountId]);
                    $trans = $stmt->fetch();
                    
                    $balance = $account['opening_balance'];
                    if ($account['balance_type'] == 'credit') {
                        $balance = -$balance;
                    }
                    $balance += $trans['total_debit'] - $trans['total_credit'];
                    
                    $message = formatSMSMessage('account_balance', [
                        'account_name' => displayAccountNameFull($account),
                        'balance' => $balance
                    ]);
                }
            }
        }
    }
    
    if (empty($mobile)) {
        echo json_encode(['success' => false, 'message' => t('mobile') . ' ' . t('not_found')]);
        exit;
    }
    
    // Send SMS
    require_once '../includes/send-sms.php';
    $result = sendSMS($mobile, $message);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => t('sms_sent_success')]);
    } else {
        echo json_encode(['success' => false, 'message' => t('sms_send_error')]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => t('error') . ': ' . $e->getMessage()]);
}

