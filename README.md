# یوسف اینڈ کو - بزنس مینجمنٹ سسٹم

## Business Management System - Yusuf & Co

ایک مکمل بزنس مینجمنٹ سسٹم جو خرید، فروخت، کھاتے، جنس، لین دین، اور رپورٹس کا انتظام کرتا ہے۔

A complete business management system for managing purchases, sales, accounts, items, transactions, and reports.

## ✨ خصوصیات / Features

- ✅ مکمل اردو انٹرفیس / Complete Urdu Interface
- ✅ موبائل ریسپانسیو / Mobile Responsive
- ✅ پیشہ ورانہ ڈیزائن / Professional Design
- ✅ کھاتے مینجمنٹ / Accounts Management
- ✅ جنس مینجمنٹ / Items Management
- ✅ خرید و فروخت / Purchases & Sales
- ✅ لین دین / Transactions
- ✅ مکمل رپورٹس / Complete Reports
- ✅ سٹاک مینجمنٹ / Stock Management

## 🚀 انسٹالیشن / Installation

### ضروریات / Requirements

- PHP 7.4 یا اس سے زیادہ / PHP 7.4 or higher
- MySQL 5.7 یا اس سے زیادہ / MySQL 5.7 or higher
- Apache Web Server
- XAMPP/WAMP (Windows کے لیے / For Windows)

### قدم بہ قدم / Step by Step

1. **فائلیں کاپی کریں / Copy Files**
   ```
   تمام فائلیں htdocs/usoft فولڈر میں کاپی کریں
   Copy all files to htdocs/usoft folder
   ```

2. **ڈیٹا بیس بنائیں / Create Database**
   ```bash
   mysql -u root -p < database/schema.sql
   ```
   یا phpMyAdmin سے / Or via phpMyAdmin:
   - نیا ڈیٹا بیس بنائیں: `usoft_business`
   - `database/schema.sql` فائل ایمپورٹ کریں

3. **کنفیگریشن / Configuration**
   - `config/database.php` میں ڈیٹا بیس کی تفصیلات درج کریں
   - `config/config.php` میں BASE_URL درست کریں

4. **ویب سرور شروع کریں / Start Web Server**
   - XAMPP/WAMP میں Apache اور MySQL شروع کریں

5. **لاگ ان / Login**
   - براؤزر میں کھولیں: `http://localhost/usoft/login.php`
   - یوزرنیم: `adil`
   - پاس ورڈ: `sana25`

## 📁 پروجیکٹ سٹرکچر / Project Structure

```
usoft/
├── accounts/          # کھاتے ماڈیول
├── items/             # جنس ماڈیول
├── purchases/         # خرید ماڈیول
├── sales/             # فروخت ماڈیول
├── transactions/      # لین دین ماڈیول
├── reports/           # رپورٹس ماڈیول
├── users/             # صارف مینجمنٹ
├── config/            # کنفیگریشن
├── database/          # ڈیٹا بیس اسکیمہ
├── includes/          # شامل فائلیں
├── assets/            # CSS/JS فائلیں
└── index.php          # ڈیش بورڈ
```

## 🗄️ ڈیٹا بیس / Database

### ٹیبلز / Tables

- `users` - صارفین
- `user_types` - یوزر ٹائپس
- `accounts` - کھاتے
- `items` - جنس
- `purchases` - خرید
- `purchase_items` - خرید کی اشیاء
- `sales` - فروخت
- `sale_items` - فروخت کی اشیاء
- `transactions` - لین دین
- `stock_movements` - سٹاک کی حرکت

## 📱 صفحات / Pages

### بنیادی / Basic
- `login.php` - لاگ ان
- `logout.php` - لاگ آؤٹ
- `index.php` - ڈیش بورڈ

### کھاتے / Accounts (5 صفحات)
- `accounts/create.php` - نیا کھاتہ
- `accounts/list.php` - کسٹمر لسٹ
- `accounts/view.php` - کھاتہ دیکھیں
- `accounts/edit.php` - کھاتہ ایڈٹ کریں
- `accounts/user-types.php` - یوزر ٹائپس

### جنس / Items (3 صفحات)
- `items/create.php` - جنس بنائیں
- `items/list.php` - تمام جنس لسٹ
- `items/edit.php` - جنس ایڈٹ کریں

### خرید / Purchases (3 صفحات)
- `purchases/create.php` - خرید شامل کریں
- `purchases/list.php` - تمام خرید لسٹ
- `purchases/view.php` - خرید کی تفصیلات

### فروخت / Sales (3 صفحات)
- `sales/create.php` - فروخت شامل کریں
- `sales/list.php` - تمام فروخت لسٹ
- `sales/view.php` - فروخت کی تفصیلات

### لین دین / Transactions (4 صفحات)
- `transactions/debit.php` - کیش بنام
- `transactions/credit.php` - کیش جمع
- `transactions/journal.php` - جرنل واؤچر
- `transactions/list.php` - تمام لین دین لسٹ

### رپورٹس / Reports (9 صفحات)
- `reports/party-ledger.php` - پارٹی لیجر
- `reports/stock-detail.php` - سٹاک کھاتہ
- `reports/stock-ledger.php` - سٹاک لیجر
- `reports/all-bills.php` - تمام بل چٹھہ
- `reports/stock-check.php` - مال چیک رپورٹ
- `reports/balance-sheet.php` - بیلنس شیٹ
- `reports/cash-book.php` - کیش بک
- `reports/daily-book.php` - روزنامچہ
- `reports/loan-slip.php` - قرضہ سلیپ & اگراھی

## 🔒 سیکورٹی / Security

- SQL Injection Protection (PDO Prepared Statements)
- XSS Protection
- Password Hashing
- Session Management
- Input Validation

## 🛠️ ٹیکنالوجیز / Technologies

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript
- **Framework:** Bootstrap 5
- **Icons:** Font Awesome 6
- **Fonts:** Noto Nastaliq Urdu, Almarai

## 📝 لائسنس / License

یہ پروجیکٹ ذاتی استعمال کے لیے ہے۔
This project is for personal use.

## 👤 مصنف / Author

یوسف اینڈ کو - بزنس مینجمنٹ سسٹم
Yusuf & Co - Business Management System

## 📞 سپورٹ / Support

کسی بھی مسئلے کے لیے رابطہ کریں۔
Contact for any issues.

---

**تاریخ / Date:** 23 دسمبر 2025 / December 23, 2025
**ورژن / Version:** 1.0.0
