<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'نیا کھاتہ بنائیں';
$success = '';
$error = '';

// Get user types
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM user_types ORDER BY type_name");
    $userTypes = $stmt->fetchAll();
} catch (PDOException $e) {
    $userTypes = [];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accountName = sanitizeInput($_POST['account_name'] ?? '');
    $accountNameUrdu = sanitizeInput($_POST['account_name_urdu'] ?? '');
    $accountType = $_POST['account_type'] ?? 'customer';
    $userTypeId = $_POST['user_type_id'] ?? null;
    $contactPerson = sanitizeInput($_POST['contact_person'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $mobile = sanitizeInput($_POST['mobile'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $openingBalance = floatval($_POST['opening_balance'] ?? 0);
    $balanceType = $_POST['balance_type'] ?? 'debit';
    
    if (empty($accountName)) {
        $error = 'براہ کرم کھاتہ کا نام درج کریں';
    } else {
        try {
            // Generate account code
            $stmt = $db->query("SELECT MAX(id) as max_id FROM accounts");
            $maxId = $stmt->fetch()['max_id'] ?? 0;
            $accountCode = generateCode('ACC', $maxId);
            
            $stmt = $db->prepare("INSERT INTO accounts (account_code, account_name, account_name_urdu, account_type, user_type_id, contact_person, phone, mobile, email, address, city, opening_balance, balance_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$accountCode, $accountName, $accountNameUrdu, $accountType, $userTypeId ?: null, $contactPerson, $phone, $mobile, $email, $address, $city, $openingBalance, $balanceType]);
            
            $success = 'کھاتہ کامیابی سے شامل ہو گیا';
            $_POST = []; // Clear form
        } catch (PDOException $e) {
            $error = 'کھاتہ شامل کرنے میں خرابی: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-user-plus"></i> نیا کھاتہ بنائیں</h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">کھاتہ کی معلومات</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">کھاتہ کا نام <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="account_name" value="<?php echo $_POST['account_name'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">کھاتہ کا نام (اردو)</label>
                            <input type="text" class="form-control" name="account_name_urdu" value="<?php echo $_POST['account_name_urdu'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">کھاتہ کی قسم</label>
                            <select class="form-select" name="account_type">
                                <option value="customer" <?php echo (($_POST['account_type'] ?? '') == 'customer') ? 'selected' : ''; ?>>کسٹمر</option>
                                <option value="supplier" <?php echo (($_POST['account_type'] ?? '') == 'supplier') ? 'selected' : ''; ?>>سپلائر</option>
                                <option value="both" <?php echo (($_POST['account_type'] ?? '') == 'both') ? 'selected' : ''; ?>>دونوں</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">یوزر ٹائپ</label>
                            <select class="form-select" name="user_type_id">
                                <option value="">-- منتخب کریں --</option>
                                <?php foreach ($userTypes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>" <?php echo (($_POST['user_type_id'] ?? '') == $type['id']) ? 'selected' : ''; ?>>
                                        <?php echo $type['type_name_urdu'] ?? $type['type_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">رابطہ شخص</label>
                            <input type="text" class="form-control" name="contact_person" value="<?php echo $_POST['contact_person'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">فون</label>
                            <input type="text" class="form-control" name="phone" value="<?php echo $_POST['phone'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">موبائل</label>
                            <input type="text" class="form-control" name="mobile" value="<?php echo $_POST['mobile'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ای میل</label>
                            <input type="email" class="form-control" name="email" value="<?php echo $_POST['email'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">پتہ</label>
                            <textarea class="form-control" name="address" rows="3"><?php echo $_POST['address'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">شہر</label>
                            <input type="text" class="form-control" name="city" value="<?php echo $_POST['city'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">افتتاحی بیلنس</label>
                            <input type="number" step="0.01" class="form-control" name="opening_balance" value="<?php echo $_POST['opening_balance'] ?? '0'; ?>">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">بیلنس کی قسم</label>
                            <select class="form-select" name="balance_type">
                                <option value="debit" <?php echo (($_POST['balance_type'] ?? 'debit') == 'debit') ? 'selected' : ''; ?>>ڈیبٹ</option>
                                <option value="credit" <?php echo (($_POST['balance_type'] ?? '') == 'credit') ? 'selected' : ''; ?>>کریڈٹ</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> محفوظ کریں
                        </button>
                        <a href="<?php echo BASE_URL; ?>accounts/list.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-list"></i> فہرست دیکھیں
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

