<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'create_item';
$success = '';
$error = '';

// Get next item number for display
try {
    $db = getDB();
    $stmt = $db->query("SELECT MAX(id) as max_id FROM items");
    $maxId = $stmt->fetch()['max_id'] ?? 0;
    $nextNumber = $maxId + 1;
    $nextItemNumber = $nextNumber;
} catch (PDOException $e) {
    $nextItemNumber = 1;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $itemCode = sanitizeInput($_POST['item_code'] ?? '');
    $itemName = sanitizeInput($_POST['item_name'] ?? '');
    $itemNameUrdu = sanitizeInput($_POST['item_name_urdu'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $unit = sanitizeInput($_POST['unit'] ?? 'pcs');
    $itemCode = sanitizeInput($_POST['item_code'] ?? '');
    $itemName = sanitizeInput($_POST['item_name'] ?? '');
    $itemNameUrdu = sanitizeInput($_POST['item_name_urdu'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $unit = sanitizeInput($_POST['unit'] ?? 'kg');
    $purchaseRate = floatval($_POST['purchase_rate'] ?? 0);
    $saleRate = floatval($_POST['sale_rate'] ?? 0);
    $openingStock = floatval($_POST['opening_stock'] ?? 0);
    $minStock = floatval($_POST['min_stock'] ?? 0);
    // Store additional info in description if needed
    $purchaseRateMann = floatval($_POST['purchase_rate_mann'] ?? 0);
    $saleRateMann = floatval($_POST['sale_rate_mann'] ?? 0);
    $companyName = sanitizeInput($_POST['company_name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    // Build description with additional fields
    $descParts = [];
    if (!empty($description)) {
        $descParts[] = $description;
    }
    if ($purchaseRateMann > 0) {
        $descParts[] = 'Purchase Rate Mann: ' . $purchaseRateMann;
    }
    if ($saleRateMann > 0) {
        $descParts[] = 'Sale Rate Mann: ' . $saleRateMann;
    }
    if (!empty($companyName)) {
        $descParts[] = 'Company: ' . $companyName;
    }
    $fullDescription = implode(' | ', $descParts);
    
    if (empty($itemName) && empty($itemNameUrdu)) {
        $error = t('please_enter_item_name');
    } else {
        try {
            $db = getDB();
            
            // Generate item code if not provided
            if (empty($itemCode)) {
                $stmt = $db->query("SELECT MAX(id) as max_id FROM items");
                $maxId = $stmt->fetch()['max_id'] ?? 0;
                $nextNumber = $maxId + 1;
                $itemCode = 'Itm' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
            }
            
            // Use Urdu name as item name if English name is empty
            if (empty($itemName) && !empty($itemNameUrdu)) {
                $itemName = $itemNameUrdu;
            }
            
            $stmt = $db->prepare("INSERT INTO items (item_code, item_name, item_name_urdu, category, unit, purchase_rate, sale_rate, opening_stock, current_stock, min_stock, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$itemCode, $itemName, $itemNameUrdu, $category, $unit, $purchaseRate, $saleRate, $openingStock, $openingStock, $minStock, $fullDescription, $_SESSION['user_id']]);
            
            // Add to stock movements
            $itemId = $db->lastInsertId();
            $stmt = $db->prepare("INSERT INTO stock_movements (item_id, movement_date, movement_type, quantity_in, balance_quantity) VALUES (?, ?, 'opening', ?, ?)");
            $stmt->execute([$itemId, date('Y-m-d'), $openingStock, $openingStock]);
            
            $success = t('item_added_success');
            $_POST = [];
            // Recalculate next item number
            $stmt = $db->query("SELECT MAX(id) as max_id FROM items");
            $maxId = $stmt->fetch()['max_id'] ?? 0;
            $nextItemNumber = $maxId + 1;
        } catch (PDOException $e) {
            $error = t('error_adding_item') . ': ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<style>
/* Compact Item Form Styling - Side by Side */
#itemForm {
    background: #f5f5f5;
    padding: 15px;
    border-radius: 8px;
}

#itemForm .form-row-2 {
    display: flex;
    gap: 10px;
    margin-bottom: 8px;
    background: white;
    padding: 6px 10px;
    border-radius: 4px;
}

#itemForm .form-row-2 .form-field {
    flex: 1;
    display: flex;
    align-items: center;
}

#itemForm .form-row-2 .form-field label {
    width: 140px;
    margin: 0;
    padding-right: 10px;
    font-size: 13px;
    font-weight: 500;
    color: #333;
    flex-shrink: 0;
}

#itemForm .form-row-2 .form-field .form-control {
    flex: 1;
    height: 32px;
    font-size: 13px;
    padding: 6px 10px;
    background-color: #fff5e6;
    border: 1px solid #ffd699;
    border-radius: 4px;
}

#itemForm .form-row-2 .form-field .form-control:focus {
    background-color: #fff;
    border-color: #667eea;
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
    outline: none;
}

#itemForm .form-row-2 .form-field .form-control[readonly] {
    background-color: #f0f0f0;
    border-color: #ddd;
}

#itemForm .form-row-full {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    background: white;
    padding: 6px 10px;
    border-radius: 4px;
}

#itemForm .form-row-full label {
    width: 180px;
    margin: 0;
    padding-right: 15px;
    font-size: 13px;
    font-weight: 500;
    color: #333;
    flex-shrink: 0;
}

#itemForm .form-row-full .form-control {
    flex: 1;
    height: 32px;
    font-size: 13px;
    padding: 6px 10px;
    background-color: #fff5e6;
    border: 1px solid #ffd699;
    border-radius: 4px;
}

#itemForm .form-row-full .form-control:focus {
    background-color: #fff;
    border-color: #667eea;
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
    outline: none;
}

#itemForm .btn-submit {
    margin-top: 15px;
    text-align: center;
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}
#itemForm .btn-submit .btn {
    min-width: 120px;
}

#itemForm .btn-success {
    background: #28a745;
    border: none;
    padding: 10px 30px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 4px;
}

[dir="rtl"] #itemForm .form-row-2 .form-field label,
[dir="rtl"] #itemForm .form-row-full label {
    text-align: right;
    padding-right: 0;
    padding-left: 10px;
}
</style>

<div class="row">
    <div class="col-md-12">
        <div style="background: #f5f5f5; padding: 10px 15px; margin-bottom: 10px; border-radius: 4px;">
            <h5 style="margin: 0; font-size: 16px; font-weight: 600; color: #333;">
                <i class="fas fa-box"></i> <?php echo t('add_item'); ?>
            </h5>
        </div>
        
        <?php if ($success): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showNotification('<?php echo addslashes($success); ?>', 'success');
                });
            </script>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showNotification('<?php echo addslashes($error); ?>', 'error');
                });
            </script>
        <?php endif; ?>
        
        <form method="POST" action="" id="itemForm">
            <!-- Row 1: Item Code and Category -->
            <div class="form-row-2">
                <div class="form-field">
                    <label><?php echo t('item_code'); ?></label>
                    <input type="text" class="form-control" name="item_code" id="item_code" value="<?php echo htmlspecialchars($_POST['item_code'] ?? ''); ?>" placeholder="<?php echo $nextItemNumber; ?> - <?php echo t('auto_generate'); ?>">
                </div>
                <div class="form-field">
                    <label><?php echo t('category'); ?></label>
                    <input type="text" class="form-control" name="category" id="category" value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>" placeholder="<?php echo t('category'); ?>">
                </div>
            </div>
            
            <!-- Row 2: Item Name and Name Urdu -->
            <div class="form-row-2">
                <div class="form-field">
                    <label><?php echo t('item_name'); ?></label>
                    <input type="text" class="form-control" name="item_name" id="item_name" value="<?php echo htmlspecialchars($_POST['item_name'] ?? ''); ?>" placeholder="<?php echo t('item_name'); ?>">
                </div>
                <div class="form-field">
                    <label><?php echo t('item_name_urdu'); ?></label>
                    <input type="text" class="form-control" name="item_name_urdu" id="item_name_urdu" value="<?php echo htmlspecialchars($_POST['item_name_urdu'] ?? ''); ?>" placeholder="<?php echo t('item_name_urdu'); ?>">
                </div>
            </div>
            
            <!-- Row 3: Purchase Rate Kilo and Sale Rate Kilo -->
            <div class="form-row-2">
                <div class="form-field">
                    <label><?php echo t('rate_kilo'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="purchase_rate" id="purchase_rate" value="<?php echo $_POST['purchase_rate'] ?? ''; ?>" placeholder="0" oninput="calculateAmounts()">
                </div>
                <div class="form-field">
                    <label><?php echo t('sale_rate_kilo'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="sale_rate" id="sale_rate" value="<?php echo $_POST['sale_rate'] ?? ''; ?>" placeholder="0" oninput="calculateAmounts()">
                </div>
            </div>
            
            <!-- Row 4: Purchase Rate Mann and Sale Rate Mann -->
            <div class="form-row-2">
                <div class="form-field">
                    <label><?php echo t('rate_mann'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="purchase_rate_mann" id="purchase_rate_mann" value="<?php echo $_POST['purchase_rate_mann'] ?? ''; ?>" placeholder="0" oninput="calculateAmounts()">
                </div>
                <div class="form-field">
                    <label><?php echo t('sale_rate_mann'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="sale_rate_mann" id="sale_rate_mann" value="<?php echo $_POST['sale_rate_mann'] ?? ''; ?>" placeholder="0" oninput="calculateAmounts()">
                </div>
            </div>
            
            <!-- Row 5: Weight and Quantity -->
            <div class="form-row-2">
                <div class="form-field">
                    <label><?php echo t('weight'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="weight" id="weight" value="<?php echo $_POST['weight'] ?? ''; ?>" placeholder="0" oninput="calculateAmounts()">
                </div>
                <div class="form-field">
                    <label><?php echo t('quantity'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="opening_stock" id="opening_stock" value="<?php echo $_POST['opening_stock'] ?? ''; ?>" placeholder="0" oninput="calculateAmounts()">
                </div>
            </div>
            
            <!-- Row 6: Total Amount and Amount -->
            <div class="form-row-2">
                <div class="form-field">
                    <label><?php echo t('credit_amount'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="total_amount" id="total_amount" value="<?php echo $_POST['total_amount'] ?? ''; ?>" placeholder="0" readonly>
                </div>
                <div class="form-field">
                    <label><?php echo t('amount'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="amount" id="amount" value="<?php echo $_POST['amount'] ?? ''; ?>" placeholder="0" readonly>
                </div>
            </div>
            
            <!-- Row 7: Company Name (Full Width) -->
            <div class="form-row-full">
                <label><?php echo t('company_name'); ?></label>
                <input type="text" class="form-control" name="company_name" id="company_name" value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>" placeholder="<?php echo t('company_name'); ?>">
            </div>
            
            <!-- Hidden fields for unit and min_stock -->
            <input type="hidden" name="unit" value="kg">
            <input type="hidden" name="min_stock" value="0">
            
            <div class="btn-submit">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> <?php echo t('save'); ?>
                </button>
                <a href="<?php echo BASE_URL; ?>items/list.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
                </a>
                <?php /* <button type="reset" class="btn btn-warning">
                    <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                </button> */ ?>
            </div>
        </form>
    </div>
</div>

<script>
function formatNumber(num) {
    return parseFloat(num) || 0;
}

function calculateAmounts() {
    var purchaseRate = formatNumber(document.getElementById('purchase_rate').value);
    var saleRate = formatNumber(document.getElementById('sale_rate').value);
    var purchaseRateMann = formatNumber(document.getElementById('purchase_rate_mann').value);
    var saleRateMann = formatNumber(document.getElementById('sale_rate_mann').value);
    var weight = formatNumber(document.getElementById('weight').value);
    var quantity = formatNumber(document.getElementById('opening_stock').value);
    
    // Calculate amount based on weight and rate (using purchase rate as default)
    var amount = 0;
    if (weight > 0 && purchaseRate > 0) {
        amount = weight * purchaseRate;
    } else if (quantity > 0 && purchaseRate > 0) {
        amount = quantity * purchaseRate;
    }
    
    // Total amount (can be sum of multiple calculations)
    var totalAmount = amount;
    if (weight > 0 && saleRate > 0) {
        totalAmount = weight * saleRate;
    } else if (quantity > 0 && saleRate > 0) {
        totalAmount = quantity * saleRate;
    }
    
    document.getElementById('amount').value = amount.toFixed(2);
    document.getElementById('total_amount').value = totalAmount.toFixed(2);
}
</script>

<?php include '../includes/footer.php'; ?>

