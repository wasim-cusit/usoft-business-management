# یوسف اینڈ کو - پروجیکٹ شروع کرنے کا اسکرپٹ
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "یوسف اینڈ کو - بزنس مینجمنٹ سسٹم" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check XAMPP path
$xamppPath = "C:\xampp"
if (-not (Test-Path $xamppPath)) {
    Write-Host "ERROR: XAMPP نہیں ملا! براہ کرم XAMPP انسٹال کریں" -ForegroundColor Red
    pause
    exit
}

Write-Host "[1/4] XAMPP چیک کر رہے ہیں..." -ForegroundColor Yellow

# Check MySQL
$mysqlPath = "$xamppPath\mysql\bin\mysql.exe"
if (-not (Test-Path $mysqlPath)) {
    Write-Host "ERROR: MySQL executable نہیں ملا!" -ForegroundColor Red
    pause
    exit
}

# Check schema file
$schemaPath = "C:\xampp\htdocs\usoft\database\schema.sql"
if (-not (Test-Path $schemaPath)) {
    Write-Host "ERROR: schema.sql فائل نہیں ملی!" -ForegroundColor Red
    pause
    exit
}

Write-Host "[2/4] ڈیٹا بیس بنایا جا رہا ہے..." -ForegroundColor Yellow
try {
    & $mysqlPath -u root -e "CREATE DATABASE IF NOT EXISTS usoft_business CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1 | Out-Null
    Write-Host "✅ ڈیٹا بیس بنایا گیا" -ForegroundColor Green
} catch {
    Write-Host "⚠️ ڈیٹا بیس بنانے میں مسئلہ - ممکن ہے پہلے سے موجود ہے" -ForegroundColor Yellow
}

Write-Host "[3/4] ڈیٹا بیس اسکیمہ ایمپورٹ کی جا رہی ہے..." -ForegroundColor Yellow
try {
    & $mysqlPath -u root usoft_business -e "source $schemaPath" 2>&1 | Out-Null
    Write-Host "✅ ڈیٹا بیس اسکیمہ ایمپورٹ ہو گئی" -ForegroundColor Green
} catch {
    Write-Host "⚠️ براہ راست ایمپورٹ ناکام - براہ کرم phpMyAdmin استعمال کریں" -ForegroundColor Yellow
    Write-Host "   یا کمانڈ: mysql -u root usoft_business < $schemaPath" -ForegroundColor Yellow
}

Write-Host "[4/4] پروجیکٹ شروع کر رہے ہیں..." -ForegroundColor Yellow
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✅ سیٹ اپ مکمل!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "براہ کرم:" -ForegroundColor Yellow
Write-Host "1. XAMPP Control Panel کھولیں" -ForegroundColor White
Write-Host "2. Apache Start کریں" -ForegroundColor White
Write-Host "3. MySQL Start کریں" -ForegroundColor White
Write-Host ""
Write-Host "پھر براؤزر میں کھولیں:" -ForegroundColor Yellow
Write-Host "   http://localhost/usoft/test-connection.php" -ForegroundColor Cyan
Write-Host "   یا" -ForegroundColor White
Write-Host "   http://localhost/usoft/login.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "لاگ ان:" -ForegroundColor Yellow
Write-Host "   یوزرنیم: adil" -ForegroundColor White
Write-Host "   پاس ورڈ: sana25" -ForegroundColor White
Write-Host ""
pause

