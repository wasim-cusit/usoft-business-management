<?php
/**
 * Send SMS Handler
 * This file handles SMS sending functionality
 * You can integrate with SMS gateway APIs like Twilio, Nexmo, etc.
 */

function sendSMS($mobile, $message) {
    // TODO: Integrate with SMS gateway
    // Example: Twilio, Nexmo, or local SMS gateway
    
    // For now, just log the SMS (you can implement actual SMS sending later)
    $logFile = __DIR__ . '/../logs/sms.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logMessage = date('Y-m-d H:i:s') . " | To: $mobile | Message: $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Return success (in real implementation, check SMS gateway response)
    return true;
}

function formatSMSMessage($type, $data) {
    $message = '';
    
    switch ($type) {
        case 'account_balance':
            $accountName = $data['account_name'] ?? '';
            $balance = $data['balance'] ?? 0;
            $message = "Dear $accountName, Your account balance is: " . formatCurrency($balance);
            break;
            
        case 'daily_book':
            $accountName = $data['account_name'] ?? '';
            $debit = $data['debit'] ?? 0;
            $credit = $data['credit'] ?? 0;
            $balance = $data['balance'] ?? 0;
            $message = "Dear $accountName, Debit: " . formatCurrency($debit) . ", Credit: " . formatCurrency($credit) . ", Balance: " . formatCurrency($balance);
            break;
            
        case 'party_ledger':
            $accountName = $data['account_name'] ?? '';
            $balance = $data['balance'] ?? 0;
            $message = "Dear $accountName, Your ledger balance is: " . formatCurrency($balance);
            break;
            
        default:
            $message = $data['message'] ?? '';
    }
    
    return $message;
}

