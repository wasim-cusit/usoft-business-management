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

// Currency - Static values
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
    // Static currency symbol - never change
    static $currencySymbol = 'Rs.';
    
    // Ensure amount is numeric and handle edge cases
    if ($amount === null || $amount === '' || $amount === false) {
        $amount = 0;
    }
    
    // Convert to string first to handle any type
    $amountStr = (string)$amount;
    
    // Extract only numeric characters and decimal point
    // This removes any extra characters, IDs, or concatenated values
    $amountStr = preg_replace('/[^0-9\.\-]/', '', $amountStr);
    
    // Handle empty result
    if ($amountStr === '' || $amountStr === '-') {
        $amountStr = '0';
    }
    
    // Convert to float
    $amount = floatval($amountStr);
    
    // Format with proper number formatting (no thousands separator issues)
    // Remove .00 if decimal part is zero
    if (fmod($amount, 1) == 0) {
        // If it's a whole number, remove .00
        $formatted = number_format($amount, 0, '.', ',');
    } else {
        // Has decimals, show 2 decimal places
        $formatted = number_format($amount, 2, '.', ',');
    }
    
    // Return with static currency symbol (always use the static variable)
    return $currencySymbol . ' ' . $formatted;
}

// Helper function to format numbers without .00 for whole numbers
function formatNumber($number, $decimals = 2) {
    $number = floatval($number);
    if (fmod($number, 1) == 0) {
        return number_format($number, 0, '.', ',');
    } else {
        return number_format($number, $decimals, '.', ',');
    }
}

// Helper function to format stock quantities (no commas, 0 shows as "0", whole numbers without decimals)
function formatStock($stock) {
    $stock = floatval($stock);
    if ($stock == 0) {
        return '0';
    }
    if (fmod($stock, 1) == 0) {
        // Whole number - show without decimals and without commas
        return number_format($stock, 0, '.', '');
    } else {
        // Has decimals - show with 2 decimal places but no commas
        return number_format($stock, 2, '.', '');
    }
}

function formatDate($date) {
    if (empty($date)) return '';
    return date(DATE_DISPLAY_FORMAT, strtotime($date));
}

function sanitizeInput($data) {
    if ($data === null) return '';
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data ?? '');
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
        return htmlspecialchars($account['account_name_urdu'] ?? '');
    }
    return htmlspecialchars($account['account_name'] ?? '');
}

/**
 * Display account name with both English and Urdu (for dropdowns and lists)
 */
function displayAccountNameFull($account) {
    if (empty($account)) return '';
    $name = htmlspecialchars($account['account_name'] ?? '');
    if (!empty($account['account_name_urdu'])) {
        $name .= ' / ' . htmlspecialchars($account['account_name_urdu'] ?? '');
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
        return htmlspecialchars($item['item_name_urdu'] ?? '');
    }
    return htmlspecialchars($item['item_name'] ?? '');
}

/**
 * Display item name with both English and Urdu (for dropdowns and lists)
 */
function displayItemNameFull($item) {
    if (empty($item)) return '';
    $name = htmlspecialchars($item['item_name'] ?? '');
    if (!empty($item['item_name_urdu'])) {
        $name .= ' / ' . htmlspecialchars($item['item_name_urdu'] ?? '');
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
        return htmlspecialchars($type['type_name_urdu'] ?? '');
    }
    return htmlspecialchars($type['type_name'] ?? '');
}

/**
 * Display user type name with both English and Urdu (for dropdowns)
 */
function displayTypeNameFull($type) {
    if (empty($type)) return '';
    $name = htmlspecialchars($type['type_name'] ?? '');
    if (!empty($type['type_name_urdu'])) {
        $name .= ' / ' . htmlspecialchars($type['type_name_urdu'] ?? '');
    }
    return $name;
}
?>

