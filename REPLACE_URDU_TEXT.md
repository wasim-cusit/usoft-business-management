# Replace All Hardcoded Urdu Text

This document tracks the replacement of all hardcoded Urdu text with translation functions.

## Strategy

1. Replace all hardcoded Urdu strings with `t('key')` function calls
2. Add missing translation keys to `config/language.php`
3. Ensure both Urdu and English work when switching languages

## Files to Update

### Sales Module
- [ ] sales/create.php
- [ ] sales/list.php  
- [ ] sales/view.php

### Purchases Module
- [ ] purchases/create.php
- [ ] purchases/list.php
- [ ] purchases/view.php

### Items Module
- [ ] items/create.php
- [ ] items/list.php
- [ ] items/edit.php

### Accounts Module
- [ ] accounts/view.php
- [ ] accounts/edit.php
- [ ] accounts/user-types.php

### Transactions Module
- [ ] transactions/list.php

### Reports Module
- [ ] reports/all-bills.php
- [ ] reports/stock-check.php
- [ ] reports/loan-slip.php
- [ ] reports/party-ledger.php
- [ ] reports/stock-detail.php
- [ ] reports/stock-ledger.php
- [ ] reports/balance-sheet.php
- [ ] reports/cash-book.php
- [ ] reports/daily-book.php

### Users Module
- [ ] users/create.php

## Common Replacements

| Urdu Text | Translation Key |
|-----------|----------------|
| سیل شامل کریں | add_sale |
| فروخت کی معلومات | sale_info |
| جنس | items |
| مقدار | quantity |
| قیمت | rate |
| رقم | amount |
| کل رقم | total |
| نیٹ رقم | net_amount |
| ڈسکاؤنٹ | discount |
| وصولی | paid_amount |
| بیلنس | balance_amount |
| تاریخ | date |
| کسٹمر | customer |
| ریمارکس | remarks |
| جنس کی تفصیلات | item_details |
| محفوظ کریں | save |
| فہرست دیکھیں | view_list |
| کوئی ریکارڈ نہیں ملا | no_records |
| منتخب کریں | select |
| واپس | back |
| پچھلا | previous |
| اگلا | next |

