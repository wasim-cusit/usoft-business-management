<?php
/**
 * Application Configuration
 * Business Management System - Yusuf & Co
 */

// Session Configuration
session_start();

// Timezone
date_default_timezone_set('Asia/Karachi');

// Base URL
define('BASE_URL', 'http://localhost/usoft/');
define('APP_NAME', 'یوسف اینڈ کو');
define('APP_NAME_EN', 'Yusuf & Co');

// Application Paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// Security
define('SESSION_TIMEOUT', 3600); // 1 hour

// Pagination
define('RECORDS_PER_PAGE', 20);

// Date Format
define('DATE_FORMAT', 'Y-m-d');
define('DATE_DISPLAY_FORMAT', 'd-m-Y');

// Currency
define('CURRENCY_SYMBOL', 'Rs.');
define('CURRENCY_CODE', 'PKR');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database
require_once ROOT_PATH . '/config/database.php';

// Include language
require_once ROOT_PATH . '/config/language.php';

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function formatCurrency($amount) {
    return CURRENCY_SYMBOL . ' ' . number_format($amount, 2);
}

function formatDate($date) {
    if (empty($date)) return '';
    return date(DATE_DISPLAY_FORMAT, strtotime($date));
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateCode($prefix, $lastId) {
    $number = str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    return $prefix . $number;
}
?>

