<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'create_user';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $userType = $_POST['user_type'] ?? 'user';
    
    if (empty($username) || empty($password)) {
        $error = t('username_password_required');
    } elseif ($password !== $confirmPassword) {
        $error = t('passwords_not_match');
    } elseif (strlen($password) < 6) {
        $error = t('password_min_length');
    } else {
        try {
            $db = getDB();
            
            // Check if username exists
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = t('username_exists');
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email, user_type) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $hashedPassword, $fullName, $email, $userType]);
                
                $success = t('user_created_success');
                $_POST = [];
            }
        } catch (PDOException $e) {
            $error = t('error_creating_user') . ': ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-user-plus"></i> <?php echo t('create_user'); ?></h1>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('user_info'); ?></h5>
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
                            <label class="form-label"><?php echo t('username'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" value="<?php echo $_POST['username'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('full_name'); ?></label>
                            <input type="text" class="form-control" name="full_name" value="<?php echo $_POST['full_name'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('password'); ?> <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" required minlength="6">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('confirm_password'); ?> <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="confirm_password" required minlength="6">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('email'); ?></label>
                            <input type="email" class="form-control" name="email" value="<?php echo $_POST['email'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('user_type'); ?></label>
                            <select class="form-select" name="user_type">
                                <option value="user" <?php echo (($_POST['user_type'] ?? 'user') == 'user') ? 'selected' : ''; ?>><?php echo t('user'); ?></option>
                                <option value="admin" <?php echo (($_POST['user_type'] ?? '') == 'admin') ? 'selected' : ''; ?>><?php echo t('admin'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> <?php echo t('save'); ?>
                        </button>
                        <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

