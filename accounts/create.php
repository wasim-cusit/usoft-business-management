<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'new_account';
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
        $error = t('please_enter_account_name');
    } else {
        try {
            // Generate account code
            $stmt = $db->query("SELECT MAX(id) as max_id FROM accounts");
            $maxId = $stmt->fetch()['max_id'] ?? 0;
            $accountCode = generateCode('ACC', $maxId);
            
            $stmt = $db->prepare("INSERT INTO accounts (account_code, account_name, account_name_urdu, account_type, user_type_id, contact_person, phone, mobile, email, address, city, opening_balance, balance_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$accountCode, $accountName, $accountNameUrdu, $accountType, $userTypeId ?: null, $contactPerson, $phone, $mobile, $email, $address, $city, $openingBalance, $balanceType]);
            
            $success = t('account_added_success');
            $_POST = []; // Clear form
        } catch (PDOException $e) {
            $error = t('error_adding_account') . ': ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-user-plus"></i> <?php echo t('new_account'); ?></h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('account_info'); ?></h5>
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
                            <label class="form-label"><?php echo t('account_name'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="account_name" value="<?php echo $_POST['account_name'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('account_name_urdu'); ?></label>
                            <input type="text" class="form-control" name="account_name_urdu" value="<?php echo $_POST['account_name_urdu'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('account_type'); ?></label>
                            <select class="form-select" name="account_type">
                                <option value="customer" <?php echo (($_POST['account_type'] ?? '') == 'customer') ? 'selected' : ''; ?>><?php echo t('customer'); ?></option>
                                <option value="supplier" <?php echo (($_POST['account_type'] ?? '') == 'supplier') ? 'selected' : ''; ?>><?php echo t('supplier'); ?></option>
                                <option value="both" <?php echo (($_POST['account_type'] ?? '') == 'both') ? 'selected' : ''; ?>><?php echo t('both'); ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('user_type'); ?></label>
                            <select class="form-select" name="user_type_id">
                                <option value="">-- <?php echo t('select'); ?> --</option>
                                <?php foreach ($userTypes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>" <?php echo (($_POST['user_type_id'] ?? '') == $type['id']) ? 'selected' : ''; ?>>
                                        <?php echo $type['type_name_urdu'] ?? $type['type_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('contact_person'); ?></label>
                            <input type="text" class="form-control" name="contact_person" value="<?php echo $_POST['contact_person'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('phone'); ?></label>
                            <input type="text" class="form-control" name="phone" value="<?php echo $_POST['phone'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('mobile'); ?></label>
                            <input type="text" class="form-control" name="mobile" value="<?php echo $_POST['mobile'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('email'); ?></label>
                            <input type="email" class="form-control" name="email" value="<?php echo $_POST['email'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label"><?php echo t('address'); ?></label>
                            <textarea class="form-control" name="address" rows="3"><?php echo $_POST['address'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('city'); ?></label>
                            <input type="text" class="form-control" name="city" value="<?php echo $_POST['city'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><?php echo t('opening_balance'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="opening_balance" value="<?php echo $_POST['opening_balance'] ?? '0'; ?>">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><?php echo t('balance'); ?> <?php echo t('type'); ?></label>
                            <select class="form-select" name="balance_type">
                                <option value="debit" <?php echo (($_POST['balance_type'] ?? 'debit') == 'debit') ? 'selected' : ''; ?>><?php echo t('debit'); ?></option>
                                <option value="credit" <?php echo (($_POST['balance_type'] ?? '') == 'credit') ? 'selected' : ''; ?>><?php echo t('credit'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> <?php echo t('save'); ?>
                        </button>
                        <a href="<?php echo BASE_URL; ?>accounts/list.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-list"></i> <?php echo t('view'); ?> <?php echo t('customer_list'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

