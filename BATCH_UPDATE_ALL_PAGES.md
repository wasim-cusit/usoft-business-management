# Batch Update All Pages - Bilingual Support

Due to the large number of pages (35+), I'll update them systematically using a comprehensive approach.

## Update Strategy

1. **Update pageTitle variables** - Replace hardcoded Urdu with translation keys
2. **Update error/success messages** - Use conditional based on language
3. **Update all labels and text** - Replace with t() function
4. **Update form placeholders** - Use translation function
5. **Update table headers** - Use translation function

## Pages to Update

### Accounts (5 pages)
- accounts/create.php
- accounts/list.php ✅ (partially done)
- accounts/view.php
- accounts/edit.php
- accounts/user-types.php

### Items (3 pages)
- items/create.php
- items/list.php
- items/edit.php

### Purchases (3 pages)
- purchases/create.php
- purchases/list.php
- purchases/view.php

### Sales (3 pages)
- sales/create.php
- sales/list.php
- sales/view.php

### Transactions (4 pages)
- transactions/debit.php
- transactions/credit.php
- transactions/journal.php
- transactions/list.php

### Reports (9 pages)
- reports/party-ledger.php
- reports/stock-detail.php
- reports/stock-ledger.php
- reports/all-bills.php
- reports/stock-check.php
- reports/balance-sheet.php
- reports/cash-book.php
- reports/daily-book.php
- reports/loan-slip.php

### Users (1 page)
- users/create.php

### Dashboard (1 page)
- index.php ✅ (partially done)

## Pattern for Updates

### 1. Page Title
```php
// Before
$pageTitle = 'نیا کھاتہ بنائیں';

// After
$pageTitle = 'new_account';
```

### 2. Error/Success Messages
```php
// Before
$error = 'براہ کرم کھاتہ کا نام درج کریں';

// After
$error = getLang() == 'ur' ? t('please_enter_account_name') : t('please_enter_account_name');
// Or simply:
$error = t('please_enter_account_name');
```

### 3. HTML Labels
```php
// Before
<label>کھاتہ کا نام</label>

// After
<label><?php echo t('account_name'); ?></label>
```

### 4. Form Placeholders
```php
// Before
<input placeholder="تلاش کریں...">

// After
<input placeholder="<?php echo t('search'); ?>...">
```

## Status

- ✅ Language system created
- ✅ Translation keys added (200+)
- ✅ Header updated
- ✅ Login updated
- ⏳ All other pages - IN PROGRESS

## Next Steps

Update each page following the pattern above.

