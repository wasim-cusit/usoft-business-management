<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'جنس ایڈٹ کریں';
$success = '';
$error = '';

$id = intval($_GET['id'] ?? 0);

if (empty($id)) {
    header('Location: ' . BASE_URL . 'items/list.php');
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        header('Location: ' . BASE_URL . 'items/list.php');
        exit;
    }
} catch (PDOException $e) {
    header('Location: ' . BASE_URL . 'items/list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $itemCode = sanitizeInput($_POST['item_code'] ?? '');
    $itemName = sanitizeInput($_POST['item_name'] ?? '');
    $itemNameUrdu = sanitizeInput($_POST['item_name_urdu'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $unit = sanitizeInput($_POST['unit'] ?? 'pcs');
    $purchaseRate = floatval($_POST['purchase_rate'] ?? 0);
    $saleRate = floatval($_POST['sale_rate'] ?? 0);
    $minStock = floatval($_POST['min_stock'] ?? 0);
    $description = sanitizeInput($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    if (empty($itemName)) {
        $error = 'براہ کرم جنس کا نام درج کریں';
    } else {
        try {
            $stmt = $db->prepare("UPDATE items SET item_code = ?, item_name = ?, item_name_urdu = ?, category = ?, unit = ?, purchase_rate = ?, sale_rate = ?, min_stock = ?, description = ?, status = ? WHERE id = ?");
            $stmt->execute([$itemCode, $itemName, $itemNameUrdu, $category, $unit, $purchaseRate, $saleRate, $minStock, $description, $status, $id]);
            
            $success = 'جنس کامیابی سے اپ ڈیٹ ہو گئی';
            // Refresh item data
            $stmt = $db->prepare("SELECT * FROM items WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();
        } catch (PDOException $e) {
            $error = 'جنس اپ ڈیٹ کرنے میں خرابی: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-edit"></i> جنس ایڈٹ کریں</h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">جنس کی معلومات</h5>
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
                            <label class="form-label">جنس کوڈ</label>
                            <input type="text" class="form-control" name="item_code" value="<?php echo htmlspecialchars($item['item_code']); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">جنس کا نام <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="item_name" value="<?php echo htmlspecialchars($item['item_name']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">جنس کا نام (اردو)</label>
                            <input type="text" class="form-control" name="item_name_urdu" value="<?php echo htmlspecialchars($item['item_name_urdu'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">قسم</label>
                            <input type="text" class="form-control" name="category" value="<?php echo htmlspecialchars($item['category'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">یونٹ</label>
                            <select class="form-select" name="unit">
                                <option value="pcs" <?php echo $item['unit'] == 'pcs' ? 'selected' : ''; ?>>عدد</option>
                                <option value="kg" <?php echo $item['unit'] == 'kg' ? 'selected' : ''; ?>>کلو</option>
                                <option value="gram" <?php echo $item['unit'] == 'gram' ? 'selected' : ''; ?>>گرام</option>
                                <option value="liter" <?php echo $item['unit'] == 'liter' ? 'selected' : ''; ?>>لیٹر</option>
                                <option value="meter" <?php echo $item['unit'] == 'meter' ? 'selected' : ''; ?>>میٹر</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">خرید کی قیمت</label>
                            <input type="number" step="0.01" class="form-control currency-input" name="purchase_rate" value="<?php echo $item['purchase_rate']; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">فروخت کی قیمت</label>
                            <input type="number" step="0.01" class="form-control currency-input" name="sale_rate" value="<?php echo $item['sale_rate']; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">کم از کم سٹاک</label>
                            <input type="number" step="0.01" class="form-control" name="min_stock" value="<?php echo $item['min_stock']; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">موجودہ سٹاک</label>
                            <input type="text" class="form-control" value="<?php echo number_format($item['current_stock'], 2); ?>" readonly>
                            <small class="text-muted">سٹاک صرف خرید/فروخت کے ذریعے تبدیل ہو سکتا ہے</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">حالت</label>
                            <select class="form-select" name="status">
                                <option value="active" <?php echo $item['status'] == 'active' ? 'selected' : ''; ?>>فعال</option>
                                <option value="inactive" <?php echo $item['status'] == 'inactive' ? 'selected' : ''; ?>>غیر فعال</option>
                            </select>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">تفصیل</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> محفوظ کریں
                        </button>
                        <a href="<?php echo BASE_URL; ?>items/list.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-arrow-right"></i> واپس
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

