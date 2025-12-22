# پروجیکٹ چلانے کے لیے ہدایات

## فوری شروع

### طریقہ 1: خودکار سیٹ اپ (آسان)

1. **XAMPP Control Panel کھولیں**
2. **Apache شروع کریں** (Start button)
3. **MySQL شروع کریں** (Start button)
4. **setup.bat فائل پر ڈبل کلک کریں**
   - یہ خودکار طور پر ڈیٹا بیس بنائے گا

5. **براؤزر میں کھولیں:**
   ```
   http://localhost/usoft/login.php
   ```

6. **لاگ ان:**
   - یوزرنیم: `adil`
   - پاس ورڈ: `sana25`

### طریقہ 2: دستی سیٹ اپ

#### مرحلہ 1: XAMPP شروع کریں
1. XAMPP Control Panel کھولیں
2. Apache کا Start button دبائیں
3. MySQL کا Start button دبائیں

#### مرحلہ 2: ڈیٹا بیس بنائیں

**آپشن A: phpMyAdmin سے (آسان)**
1. براؤزر میں کھولیں: `http://localhost/phpmyadmin`
2. بائیں طرف "New" پر کلک کریں
3. Database name: `usoft_business`
4. Collation: `utf8mb4_unicode_ci`
5. "Create" پر کلک کریں
6. اوپر "Import" ٹیب پر کلک کریں
7. "Choose File" پر کلک کریں
8. `C:\xampp\htdocs\usoft\database\schema.sql` فائل منتخب کریں
9. نیچے "Go" پر کلک کریں

**آپشن B: کمانڈ لائن سے**
```bash
cd C:\xampp\mysql\bin
mysql -u root -e "CREATE DATABASE IF NOT EXISTS usoft_business CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root usoft_business < C:\xampp\htdocs\usoft\database\schema.sql
```

#### مرحلہ 3: پروجیکٹ کھولیں

براؤزر میں کھولیں:
```
http://localhost/usoft/login.php
```

#### مرحلہ 4: لاگ ان

- **یوزرنیم:** `adil`
- **پاس ورڈ:** `sana25`

## پہلی بار استعمال

1. ✅ لاگ ان کریں
2. ✅ یوزر ٹائپس شامل کریں (Accounts > یوزر ٹائپ شامل کریں)
3. ✅ کھاتے بنائیں (Accounts > نیا کھاتہ)
4. ✅ جنس شامل کریں (Items > جنس بنائیں)
5. ✅ خرید/فروخت شروع کریں

## مسائل کا حل

### Apache شروع نہیں ہوتا
- پورٹ 80 استعمال میں ہے؟
- XAMPP Control Panel میں "Config" > "httpd.conf" کھولیں
- پورٹ تبدیل کریں (مثلاً 8080)

### MySQL شروع نہیں ہوتا
- پورٹ 3306 استعمال میں ہے؟
- XAMPP Control Panel میں "Config" > "my.ini" کھولیں
- پورٹ تبدیل کریں

### ڈیٹا بیس کنکشن خرابی
- MySQL چل رہا ہے؟
- `config/database.php` میں تفصیلات درست ہیں؟
- ڈیٹا بیس بنایا گیا ہے؟

### صفحہ نہیں کھلتا
- Apache چل رہا ہے؟
- URL درست ہے؟ `http://localhost/usoft/login.php`
- فائلز صحیح جگہ پر ہیں؟ `C:\xampp\htdocs\usoft\`

### اردو فونٹس نہیں دکھائی دیتے
- انٹرنیٹ کنکشن چیک کریں (Google Fonts)
- براؤزر cache صاف کریں (Ctrl+F5)

## چیک لسٹ

- [ ] XAMPP انسٹال ہے
- [ ] Apache شروع ہے
- [ ] MySQL شروع ہے
- [ ] ڈیٹا بیس بنایا گیا ہے
- [ ] schema.sql ایمپورٹ کیا گیا ہے
- [ ] براؤزر میں صفحہ کھل رہا ہے

## کامیابی!

اگر سب کچھ ٹھیک ہے تو آپ کو لاگ ان صفحہ نظر آنا چاہیے۔

**مزید مدد:**
- `INSTALLATION.md` دیکھیں
- `README.md` دیکھیں

