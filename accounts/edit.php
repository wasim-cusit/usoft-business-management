<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'edit_account';
$success = '';
$error = '';

$id = intval($_GET['id'] ?? 0);

if (empty($id)) {
    header('Location: ' . BASE_URL . 'accounts/list.php');
    exit;
}

// Get user types
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM user_types ORDER BY type_name");
    $userTypes = $stmt->fetchAll();
    
    // Get account
    $stmt = $db->prepare("SELECT * FROM accounts WHERE id = ?");
    $stmt->execute([$id]);
    $account = $stmt->fetch();
    
    if (!$account) {
        header('Location: ' . BASE_URL . 'accounts/list.php');
        exit;
    }
} catch (PDOException $e) {
    header('Location: ' . BASE_URL . 'accounts/list.php');
    exit;
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
    $status = $_POST['status'] ?? 'active';
    
    if (empty($accountName)) {
        $error = t('please_enter_account_name');
    } else {
        try {
            $stmt = $db->prepare("UPDATE accounts SET account_name = ?, account_name_urdu = ?, account_type = ?, user_type_id = ?, contact_person = ?, phone = ?, mobile = ?, email = ?, address = ?, city = ?, opening_balance = ?, balance_type = ?, status = ? WHERE id = ?");
            $stmt->execute([$accountName, $accountNameUrdu, $accountType, $userTypeId ?: null, $contactPerson, $phone, $mobile, $email, $address, $city, $openingBalance, $balanceType, $status, $id]);
            
            $success = t('account_updated_success');
            // Refresh account data
            $stmt = $db->prepare("SELECT * FROM accounts WHERE id = ?");
            $stmt->execute([$id]);
            $account = $stmt->fetch();
        } catch (PDOException $e) {
            $error = t('error_updating_account') . ': ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-edit"></i> <?php echo t('edit_account'); ?></h1>
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
                            <input type="text" class="form-control" name="account_name" value="<?php echo htmlspecialchars($account['account_name']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('account_name_urdu'); ?></label>
                            <input type="text" class="form-control" name="account_name_urdu" value="<?php echo htmlspecialchars($account['account_name_urdu'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('account_type'); ?></label>
                            <select class="form-select" name="account_type">
                                <option value="customer" <?php echo $account['account_type'] == 'customer' ? 'selected' : ''; ?>><?php echo t('customer'); ?></option>
                                <option value="supplier" <?php echo $account['account_type'] == 'supplier' ? 'selected' : ''; ?>><?php echo t('supplier'); ?></option>
                                <option value="both" <?php echo $account['account_type'] == 'both' ? 'selected' : ''; ?>><?php echo t('both'); ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('user_type_label'); ?></label>
                            <select class="form-select" name="user_type_id">
                                <option value="">-- <?php echo t('select'); ?> --</option>
                                <?php foreach ($userTypes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>" <?php echo $account['user_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                        <?php echo displayTypeNameFull($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('contact_person'); ?></label>
                            <input type="text" class="form-control" name="contact_person" value="<?php echo htmlspecialchars($account['contact_person'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('phone'); ?></label>
                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($account['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('mobile'); ?></label>
                            <input type="text" class="form-control" name="mobile" value="<?php echo htmlspecialchars($account['mobile'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('email'); ?></label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($account['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label"><?php echo t('address'); ?></label>
                            <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($account['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('city'); ?></label>
                            <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($account['city'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('opening_balance'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="opening_balance" value="<?php echo $account['opening_balance']; ?>">
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label class="form-label"><?php echo t('balance'); ?> <?php echo t('type'); ?></label>
                            <select class="form-select" name="balance_type">
                                <option value="debit" <?php echo $account['balance_type'] == 'debit' ? 'selected' : ''; ?>><?php echo t('debit'); ?></option>
                                <option value="credit" <?php echo $account['balance_type'] == 'credit' ? 'selected' : ''; ?>><?php echo t('credit'); ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label class="form-label"><?php echo t('status'); ?></label>
                            <select class="form-select" name="status">
                                <option value="active" <?php echo $account['status'] == 'active' ? 'selected' : ''; ?>><?php echo t('active'); ?></option>
                                <option value="inactive" <?php echo $account['status'] == 'inactive' ? 'selected' : ''; ?>><?php echo t('inactive'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> <?php echo t('save'); ?>
                        </button>
                        <a href="<?php echo BASE_URL; ?>accounts/view.php?id=<?php echo $id; ?>" class="btn btn-info btn-lg">
                            <i class="fas fa-eye"></i> <?php echo t('view'); ?>
                        </a>
                        <a href="<?php echo BASE_URL; ?>accounts/list.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-arrow-right"></i> <?php echo t('back'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

