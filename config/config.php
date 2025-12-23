<?php
/**
 * Application Configuration
 * Business Management System - Yusuf & Co
 */

// Session Configuration
session_start();

// Timezone
date_default_timezone_set('Asia/Karachi');

// Base URL - Auto-detect based on server environment
// This will work on both localhost and live server
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || 
             (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Detect base path: config.php is always in /config/ directory
// So we go up one level to get the project root
$configFile = __FILE__;
$configDir = dirname($configFile); // /path/to/project/config
$projectRoot = dirname($configDir); // /path/to/project
$documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot = str_replace('\\', '/', $projectRoot);

// Get relative path from document root
if (!empty($documentRoot) && strpos($projectRoot, $documentRoot) === 0) {
    $basePath = substr($projectRoot, strlen($documentRoot));
    $basePath = str_replace('\\', '/', $basePath);
    if ($basePath === '' || $basePath === '/') {
        $basePath = '/';
    } else {
        if (substr($basePath, 0, 1) !== '/') {
            $basePath = '/' . $basePath;
        }
        if (substr($basePath, -1) !== '/') {
            $basePath .= '/';
        }
    }
} else {
    // Fallback: use SCRIPT_NAME to detect path
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $scriptDir = dirname($scriptName);
    $basePath = str_replace('\\', '/', $scriptDir);
    if ($basePath === '/' || $basePath === '.') {
        $basePath = '/';
    } else {
        if (substr($basePath, -1) !== '/') {
            $basePath .= '/';
        }
    }
}

define('BASE_URL', $protocol . $host . $basePath);
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

/**
 * Display account name based on current language
 * Shows Urdu name if language is Urdu and Urdu name exists, otherwise shows English name
 */
function displayAccountName($account) {
    if (empty($account)) return '';
    $lang = getLang();
    if ($lang == 'ur' && !empty($account['account_name_urdu'])) {
        return htmlspecialchars($account['account_name_urdu']);
    }
    return htmlspecialchars($account['account_name']);
}

/**
 * Display account name with both English and Urdu (for dropdowns and lists)
 */
function displayAccountNameFull($account) {
    if (empty($account)) return '';
    $name = htmlspecialchars($account['account_name']);
    if (!empty($account['account_name_urdu'])) {
        $name .= ' / ' . htmlspecialchars($account['account_name_urdu']);
    }
    return $name;
}

/**
 * Display item name based on current language
 */
function displayItemName($item) {
    if (empty($item)) return '';
    $lang = getLang();
    if ($lang == 'ur' && !empty($item['item_name_urdu'])) {
        return htmlspecialchars($item['item_name_urdu']);
    }
    return htmlspecialchars($item['item_name']);
}

/**
 * Display item name with both English and Urdu (for dropdowns and lists)
 */
function displayItemNameFull($item) {
    if (empty($item)) return '';
    $name = htmlspecialchars($item['item_name']);
    if (!empty($item['item_name_urdu'])) {
        $name .= ' / ' . htmlspecialchars($item['item_name_urdu']);
    }
    return $name;
}

/**
 * Display user type name based on current language
 */
function displayTypeName($type) {
    if (empty($type)) return '';
    $lang = getLang();
    if ($lang == 'ur' && !empty($type['type_name_urdu'])) {
        return htmlspecialchars($type['type_name_urdu']);
    }
    return htmlspecialchars($type['type_name']);
}

/**
 * Display user type name with both English and Urdu (for dropdowns)
 */
function displayTypeNameFull($type) {
    if (empty($type)) return '';
    $name = htmlspecialchars($type['type_name']);
    if (!empty($type['type_name_urdu'])) {
        $name .= ' / ' . htmlspecialchars($type['type_name_urdu']);
    }
    return $name;
}
?>

