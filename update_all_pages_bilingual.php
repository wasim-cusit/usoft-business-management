<?php
/**
 * Script to batch update all pages with bilingual support
 * This will help identify patterns and update systematically
 */

echo "Bilingual Update Script\n";
echo "======================\n\n";

$pages_to_update = [
    'accounts/create.php' => [
        'pageTitle' => 'new_account',
        'replacements' => [
            'نیا کھاتہ بنائیں' => 't(\'new_account\')',
            'کھاتہ کی معلومات' => 't(\'account_info\')',
            'کھاتہ کا نام' => 't(\'account_name\')',
            'کھاتہ کا نام (اردو)' => 't(\'account_name_urdu\')',
            'کھاتہ کی قسم' => 't(\'account_type\')',
            'محفوظ کریں' => 't(\'save\')',
            'منسوخ کریں' => 't(\'cancel\')',
        ]
    ],
    // Add more pages...
];

echo "Total pages to update: " . count($pages_to_update) . "\n";
echo "Use this as reference for manual updates.\n";
?>

