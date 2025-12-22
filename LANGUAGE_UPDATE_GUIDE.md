# Language Update Guide - English Support

## ✅ Language System Created

A complete bilingual (Urdu/English) language system has been added to the project.

## 📋 What's Been Done

### ✅ Created Files
1. **`config/language.php`** - Complete language system with:
   - Urdu and English translations
   - Language switching functionality
   - Helper functions: `t()`, `getLang()`, `getDir()`, `getLangName()`

### ✅ Updated Files
1. **`config/config.php`** - Added language.php include
2. **`includes/header.php`** - Updated with:
   - Dynamic language direction (RTL/LTR)
   - Language switcher dropdown
   - All menu items use `t()` function
3. **`login.php`** - Updated with:
   - Language switcher
   - All labels use `t()` function
4. **`index.php`** - Updated page title

## 🔄 How to Update Remaining Pages

### Step 1: Add Language Support at Top

Add this after `require_once 'config/config.php';`:

```php
$pageTitle = 'page_key'; // Use translation key, not hardcoded text
```

### Step 2: Replace Hardcoded Text

Replace Urdu text with translation function:

**Before:**
```php
<h1>کسٹمر لسٹ</h1>
<label>نام</label>
<button>محفوظ کریں</button>
```

**After:**
```php
<h1><?php echo t('customer_list'); ?></h1>
<label><?php echo t('name'); ?></label>
<button><?php echo t('save'); ?></button>
```

### Step 3: Common Translations Available

All common translations are available in `config/language.php`:

- `t('home')` - ہوم / Home
- `t('dashboard')` - ڈیش بورڈ / Dashboard
- `t('accounts')` - کھاتے / Accounts
- `t('new_account')` - نیا کھاتہ / New Account
- `t('customer_list')` - کسٹمر لسٹ / Customer List
- `t('items')` - جنس / Items
- `t('create_item')` - جنس بنائیں / Create Item
- `t('purchases')` - مال آمد / Purchases
- `t('sales')` - مال فروخت / Sales
- `t('transactions')` - روزنامچہ / Transactions
- `t('reports')` - رپورٹس / Reports
- `t('search')` - تلاش کریں / Search
- `t('save')` - محفوظ کریں / Save
- `t('cancel')` - منسوخ کریں / Cancel
- `t('view')` - دیکھیں / View
- `t('edit')` - ایڈٹ کریں / Edit
- `t('delete')` - حذف کریں / Delete
- And 100+ more...

## 📝 Pages That Need Updates

### High Priority (Main Pages)
- [ ] accounts/create.php
- [ ] accounts/list.php
- [ ] accounts/view.php
- [ ] accounts/edit.php
- [ ] items/create.php
- [ ] items/list.php
- [ ] items/edit.php
- [ ] purchases/create.php
- [ ] purchases/list.php
- [ ] purchases/view.php
- [ ] sales/create.php
- [ ] sales/list.php
- [ ] sales/view.php

### Medium Priority (Transactions)
- [ ] transactions/debit.php
- [ ] transactions/credit.php
- [ ] transactions/journal.php
- [ ] transactions/list.php

### Low Priority (Reports)
- [ ] reports/party-ledger.php
- [ ] reports/stock-detail.php
- [ ] reports/stock-ledger.php
- [ ] reports/all-bills.php
- [ ] reports/stock-check.php
- [ ] reports/balance-sheet.php
- [ ] reports/cash-book.php
- [ ] reports/daily-book.php
- [ ] reports/loan-slip.php

## 🎯 Quick Update Pattern

For each page:

1. **Find hardcoded Urdu text**
2. **Replace with `t('key')`**
3. **Add key to language.php if missing**

Example:
```php
// Old
<h1>کسٹمر لسٹ</h1>
<button>نیا کھاتہ بنائیں</button>

// New
<h1><?php echo t('customer_list'); ?></h1>
<button><?php echo t('new_account'); ?></button>
```

## 🌐 Language Switching

Users can switch language using:
- **Language dropdown** in navbar (top right)
- **URL parameter**: `?lang=ur` or `?lang=en`
- **Login page** language buttons

## ✅ Current Status

- ✅ Language system created
- ✅ Header updated
- ✅ Login page updated
- ✅ Dashboard partially updated
- ⏳ Other pages need updates (use this guide)

## 🚀 Next Steps

1. Update all pages using the pattern above
2. Test language switching
3. Add any missing translations to language.php
4. Commit changes to Git

## 📚 Translation Keys Reference

See `config/language.php` for complete list of available translation keys.

