<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => t('invalid_request_method')]);
    exit;
}

$accountCode = trim(sanitizeInput($_POST['account_code'] ?? ''));
$accountName = sanitizeInput($_POST['account_name'] ?? '');
$accountNameUrdu = sanitizeInput($_POST['account_name_urdu'] ?? '');
$accountType = $_POST['account_type'] ?? 'both';
$contactPerson = sanitizeInput($_POST['contact_person'] ?? '');
$phone = sanitizeInput($_POST['phone'] ?? '');
$mobile = sanitizeInput($_POST['mobile'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$address = sanitizeInput($_POST['address'] ?? '');
$city = sanitizeInput($_POST['city'] ?? '');
$openingBalance = floatval($_POST['opening_balance'] ?? 0);
$balanceType = $_POST['balance_type'] ?? 'debit';

// Account name is optional - use Urdu name or account code as fallback
if (empty($accountName)) {
    if (!empty($accountNameUrdu)) {
        $accountName = $accountNameUrdu;
    } elseif (!empty($accountCode)) {
        $accountName = $accountCode;
    } else {
        // Generate a default name if nothing is provided
        $accountName = 'Account ' . date('YmdHis');
    }
}

try {
    $db = getDB();
    
    // Generate account code if not provided
    if (empty($accountCode)) {
        $stmt = $db->query("SELECT MAX(id) as max_id FROM accounts");
        $maxId = $stmt->fetch()['max_id'] ?? 0;
        $nextNumber = $maxId + 1;
        $accountCode = 'Acc' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
    } else {
        // Check if account code already exists
        $stmt = $db->prepare("SELECT id FROM accounts WHERE account_code = ?");
        $stmt->execute([$accountCode]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => t('account_code_already_exists')]);
            exit;
        }
    }
    
    $stmt = $db->prepare("INSERT INTO accounts (account_code, account_name, account_name_urdu, account_type, contact_person, phone, mobile, email, address, city, opening_balance, balance_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$accountCode, $accountName, $accountNameUrdu, $accountType, $contactPerson, $phone, $mobile, $email, $address, $city, $openingBalance, $balanceType]);
    
    echo json_encode(['success' => true, 'message' => t('account_added_success')]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => t('error_adding_account') . ': ' . $e->getMessage()]);
}
?>

