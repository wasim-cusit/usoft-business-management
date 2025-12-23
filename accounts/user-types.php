<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'add_user_type_title';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $typeName = sanitizeInput($_POST['type_name'] ?? '');
    $typeNameUrdu = sanitizeInput($_POST['type_name_urdu'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    if (empty($typeName)) {
        $error = t('type_name_required');
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO user_types (type_name, type_name_urdu, description) VALUES (?, ?, ?)");
            $stmt->execute([$typeName, $typeNameUrdu, $description]);
            
            $success = t('user_type_added_success');
            $_POST = [];
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $error = t('type_already_exists');
            } else {
                $error = t('error_adding_user_type');
            }
        }
    }
}

// Get all user types
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM user_types ORDER BY id DESC");
    $userTypes = $stmt->fetchAll();
} catch (PDOException $e) {
    $userTypes = [];
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-tags"></i> <?php echo t('add_user_type_title'); ?></h1>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('new_user_type'); ?></h5>
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
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('type_name_english'); ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="type_name" value="<?php echo $_POST['type_name'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('type_name_urdu_label'); ?></label>
                        <input type="text" class="form-control" name="type_name_urdu" value="<?php echo $_POST['type_name_urdu'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('description'); ?></label>
                        <textarea class="form-control" name="description" rows="3"><?php echo $_POST['description'] ?? ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-save"></i> <?php echo t('save'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('all_user_types'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo t('type_name'); ?></th>
                                <th><?php echo t('type_name_urdu'); ?></th>
                                <th><?php echo t('description'); ?></th>
                                <th><?php echo t('date'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($userTypes)): ?>
                                <tr>
                                    <td colspan="5" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($userTypes as $index => $type): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo displayTypeName($type); ?></td>
                                        <td><?php echo getLang() == 'ur' ? htmlspecialchars($type['type_name'] ?? '-') : htmlspecialchars($type['type_name_urdu'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($type['description'] ?? '-'); ?></td>
                                        <td><?php echo formatDate($type['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

