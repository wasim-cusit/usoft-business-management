# انسٹالیشن گائیڈ

## ضروریات

- PHP 7.4 یا اس سے زیادہ
- MySQL 5.7 یا اس سے زیادہ
- Apache Web Server
- XAMPP/WAMP (Windows کے لیے)

## قدم بہ قدم انسٹالیشن

### 1. فائلیں کاپی کریں

پروجیکٹ کی تمام فائلیں `htdocs/usoft` فولڈر میں کاپی کریں۔

### 2. ڈیٹا بیس بنائیں

1. phpMyAdmin کھولیں
2. نیا ڈیٹا بیس بنائیں: `usoft_business`
3. `database/schema.sql` فائل کو ایمپورٹ کریں

یا کمانڈ لائن سے:
```bash
mysql -u root -p < database/schema.sql
```

### 3. کنفیگریشن

`config/database.php` فائل میں ڈیٹا بیس کی تفصیلات درج کریں:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'usoft_business');
```

`config/config.php` میں BASE_URL درست کریں:

```php
define('BASE_URL', 'http://localhost/usoft/');
```

### 4. ویب سرور شروع کریں

XAMPP/WAMP میں:
- Apache شروع کریں
- MySQL شروع کریں

### 5. لاگ ان

براؤزر میں کھولیں: `http://localhost/usoft/login.php`

**ڈیفالٹ لاگ ان:**
- یوزرنیم: `adil`
- پاس ورڈ: `sana25`

## پہلی بار استعمال

1. لاگ ان کریں
2. یوزر ٹائپس شامل کریں (Accounts > Add User Type)
3. کھاتے بنائیں (Accounts > New Account)
4. جنس شامل کریں (Items > Add Item)
5. خرید/فروخت شروع کریں

## مسائل کا حل

### ڈیٹا بیس کنکشن خرابی
- چیک کریں کہ MySQL چل رہا ہے
- `config/database.php` میں تفصیلات درست ہیں

### صفحہ نہیں کھلتا
- Apache چل رہا ہے؟
- BASE_URL درست ہے؟
- فائلز صحیح جگہ پر ہیں؟

### اردو فونٹس نہیں دکھائی دیتے
- انٹرنیٹ کنکشن چیک کریں (Google Fonts کے لیے)
- براؤزر cache صاف کریں

## اپ گریڈ

نئی ورژن انسٹال کرنے کے لیے:
1. ڈیٹا بیس کا بیک اپ لیں
2. نئی فائلیں کاپی کریں
3. ڈیٹا بیس اپ ڈیٹ کریں (اگر کوئی تبدیلی ہو)

## سپورٹ

کسی بھی مسئلے کے لیے رابطہ کریں۔

