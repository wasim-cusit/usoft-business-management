@echo off
echo ========================================
echo یوسف اینڈ کو - بزنس مینجمنٹ سسٹم
echo ========================================
echo.

echo [1/3] ڈیٹا بیس بنایا جا رہا ہے...
cd C:\xampp\mysql\bin
mysql -u root -e "CREATE DATABASE IF NOT EXISTS usoft_business CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if %errorlevel% neq 0 (
    echo ERROR: MySQL چل رہا ہے؟ براہ کرم XAMPP Control Panel سے MySQL شروع کریں
    pause
    exit /b 1
)

echo [2/3] ڈیٹا بیس اسکیمہ ایمپورٹ کی جا رہی ہے...
cd C:\xampp\htdocs\usoft\database
mysql -u root usoft_business < schema.sql
if %errorlevel% neq 0 (
    echo ERROR: ڈیٹا بیس ایمپورٹ ناکام
    pause
    exit /b 1
)

echo [3/3] چیک کر رہے ہیں...
cd C:\xampp\htdocs\usoft
echo.
echo ========================================
echo ✅ سیٹ اپ مکمل!
echo ========================================
echo.
echo براہ کرم:
echo 1. XAMPP Control Panel سے Apache شروع کریں
echo 2. براؤزر میں کھولیں: http://localhost/usoft/login.php
echo 3. لاگ ان: یوزرنیم = adil, پاس ورڈ = sana25
echo.
pause

