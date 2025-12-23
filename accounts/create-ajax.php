<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => t('invalid_request_method')]);
    exit;
}

$accountName = sanitizeInput($_POST['account_name'] ?? '');
$accountNameUrdu = sanitizeInput($_POST['account_name_urdu'] ?? '');
$accountType = $_POST['account_type'] ?? 'customer';
$userTypeId = $_POST['user_type_id'] ?? null;
$contactPerson = sanitizeInput($_POST['contact_person'] ?? '');
$phone = sanitizeInput($_POST['phone'] ?? '');
$mobile = sanitizeInput($_POST['mobile'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$address = sanitizeInput($_POST['address'] ?? '');
$city = sanitizeInput($_POST['city'] ?? '');
$openingBalance = floatval($_POST['opening_balance'] ?? 0);
$balanceType = $_POST['balance_type'] ?? 'debit';

if (empty($accountName)) {
    echo json_encode(['success' => false, 'message' => t('please_enter_account_name')]);
    exit;
}

try {
    $db = getDB();
    
    // Generate account code
    $stmt = $db->query("SELECT MAX(id) as max_id FROM accounts");
    $maxId = $stmt->fetch()['max_id'] ?? 0;
    $accountCode = generateCode('ACC', $maxId);
    
    $stmt = $db->prepare("INSERT INTO accounts (account_code, account_name, account_name_urdu, account_type, user_type_id, contact_person, phone, mobile, email, address, city, opening_balance, balance_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$accountCode, $accountName, $accountNameUrdu, $accountType, $userTypeId ?: null, $contactPerson, $phone, $mobile, $email, $address, $city, $openingBalance, $balanceType]);
    
    echo json_encode(['success' => true, 'message' => t('account_added_success')]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => t('error_adding_account') . ': ' . $e->getMessage()]);
}
?>

