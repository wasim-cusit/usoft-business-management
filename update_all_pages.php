<?php
/**
 * Script to update all pages with English language support
 * This script will help identify which pages need translation updates
 */

$pages = [
    'accounts/create.php',
    'accounts/list.php',
    'accounts/view.php',
    'accounts/edit.php',
    'accounts/user-types.php',
    'items/create.php',
    'items/list.php',
    'items/edit.php',
    'purchases/create.php',
    'purchases/list.php',
    'purchases/view.php',
    'sales/create.php',
    'sales/list.php',
    'sales/view.php',
    'transactions/debit.php',
    'transactions/credit.php',
    'transactions/journal.php',
    'transactions/list.php',
    'reports/party-ledger.php',
    'reports/stock-detail.php',
    'reports/stock-ledger.php',
    'reports/all-bills.php',
    'reports/stock-check.php',
    'reports/balance-sheet.php',
    'reports/cash-book.php',
    'reports/daily-book.php',
    'reports/loan-slip.php',
    'users/create.php',
];

echo "Pages that need translation updates:\n";
foreach ($pages as $page) {
    if (file_exists($page)) {
        echo "✓ $page\n";
    } else {
        echo "✗ $page (not found)\n";
    }
}

echo "\nTotal pages: " . count($pages) . "\n";
echo "To update: Replace hardcoded Urdu text with t() function\n";
echo "Example: 'نیا کھاتہ' becomes <?php echo t('new_account'); ?>\n";
?>

