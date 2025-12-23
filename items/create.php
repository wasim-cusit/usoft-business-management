<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'create_item';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $itemCode = sanitizeInput($_POST['item_code'] ?? '');
    $itemName = sanitizeInput($_POST['item_name'] ?? '');
    $itemNameUrdu = sanitizeInput($_POST['item_name_urdu'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $unit = sanitizeInput($_POST['unit'] ?? 'pcs');
    $purchaseRate = floatval($_POST['purchase_rate'] ?? 0);
    $saleRate = floatval($_POST['sale_rate'] ?? 0);
    $openingStock = floatval($_POST['opening_stock'] ?? 0);
    $minStock = floatval($_POST['min_stock'] ?? 0);
    $description = sanitizeInput($_POST['description'] ?? '');
    
    if (empty($itemName)) {
        $error = t('please_enter_item_name');
    } else {
        try {
            $db = getDB();
            
            // Generate item code if not provided
            if (empty($itemCode)) {
                $stmt = $db->query("SELECT MAX(id) as max_id FROM items");
                $maxId = $stmt->fetch()['max_id'] ?? 0;
                $itemCode = generateCode('ITM', $maxId);
            }
            
            $stmt = $db->prepare("INSERT INTO items (item_code, item_name, item_name_urdu, category, unit, purchase_rate, sale_rate, opening_stock, current_stock, min_stock, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$itemCode, $itemName, $itemNameUrdu, $category, $unit, $purchaseRate, $saleRate, $openingStock, $openingStock, $minStock, $description]);
            
            // Add to stock movements
            $itemId = $db->lastInsertId();
            $stmt = $db->prepare("INSERT INTO stock_movements (item_id, movement_date, movement_type, quantity_in, balance_quantity) VALUES (?, ?, 'opening', ?, ?)");
            $stmt->execute([$itemId, date('Y-m-d'), $openingStock, $openingStock]);
            
            $success = t('item_added_success');
            $_POST = [];
        } catch (PDOException $e) {
            $error = t('error_adding_item') . ': ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-box"></i> <?php echo t('create_item'); ?></h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('item_info'); ?></h5>
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
                            <label class="form-label"><?php echo t('item_code'); ?></label>
                            <input type="text" class="form-control" name="item_code" value="<?php echo $_POST['item_code'] ?? ''; ?>" placeholder="<?php echo t('auto_generate'); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('item_name_required'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="item_name" value="<?php echo $_POST['item_name'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('item_name_urdu'); ?></label>
                            <input type="text" class="form-control" name="item_name_urdu" value="<?php echo $_POST['item_name_urdu'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('category'); ?></label>
                            <input type="text" class="form-control" name="category" value="<?php echo $_POST['category'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('unit'); ?></label>
                            <select class="form-select" name="unit">
                                <option value="pcs" <?php echo (($_POST['unit'] ?? 'pcs') == 'pcs') ? 'selected' : ''; ?>><?php echo t('pcs'); ?></option>
                                <option value="kg" <?php echo (($_POST['unit'] ?? '') == 'kg') ? 'selected' : ''; ?>><?php echo t('kg'); ?></option>
                                <option value="gram" <?php echo (($_POST['unit'] ?? '') == 'gram') ? 'selected' : ''; ?>><?php echo t('gram'); ?></option>
                                <option value="liter" <?php echo (($_POST['unit'] ?? '') == 'liter') ? 'selected' : ''; ?>><?php echo t('liter'); ?></option>
                                <option value="meter" <?php echo (($_POST['unit'] ?? '') == 'meter') ? 'selected' : ''; ?>><?php echo t('meter'); ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('purchase_rate'); ?></label>
                            <input type="number" step="0.01" class="form-control currency-input" name="purchase_rate" value="<?php echo $_POST['purchase_rate'] ?? '0'; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('sale_rate'); ?></label>
                            <input type="number" step="0.01" class="form-control currency-input" name="sale_rate" value="<?php echo $_POST['sale_rate'] ?? '0'; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('opening_stock'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="opening_stock" value="<?php echo $_POST['opening_stock'] ?? '0'; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('min_stock'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="min_stock" value="<?php echo $_POST['min_stock'] ?? '0'; ?>">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label"><?php echo t('description'); ?></label>
                            <textarea class="form-control" name="description" rows="3"><?php echo $_POST['description'] ?? ''; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> <?php echo t('save'); ?>
                        </button>
                        <a href="<?php echo BASE_URL; ?>items/list.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-list"></i> <?php echo t('view_list'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

