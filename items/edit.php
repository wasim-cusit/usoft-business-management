<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'edit_item';
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

// Extract additional fields from description
$purchaseRateMann = 0;
$saleRateMann = 0;
$companyName = '';
$weight = 0;
$totalAmount = 0;
$amount = 0;

if (!empty($item['description'])) {
    if (preg_match('/Purchase Rate Mann:\s*([0-9.]+)/', $item['description'], $matches)) {
        $purchaseRateMann = floatval($matches[1]);
    }
    if (preg_match('/Sale Rate Mann:\s*([0-9.]+)/', $item['description'], $matches)) {
        $saleRateMann = floatval($matches[1]);
    }
    if (preg_match('/Company:\s*(.+?)(?:\s*\||$)/i', $item['description'], $matches)) {
        $companyName = trim($matches[1]);
    }
}

$weight = floatval($item['opening_stock'] ?? $item['current_stock'] ?? 0);
$quantity = floatval($item['opening_stock'] ?? $item['current_stock'] ?? 0);
$purchaseRate = floatval($item['purchase_rate'] ?? 0);
$saleRate = floatval($item['sale_rate'] ?? 0);
$totalAmount = $quantity * $saleRate;
$amount = $quantity * $purchaseRate;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $itemCode = sanitizeInput($_POST['item_code'] ?? '');
    $itemName = sanitizeInput($_POST['item_name'] ?? '');
    $itemNameUrdu = sanitizeInput($_POST['item_name_urdu'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $unit = sanitizeInput($_POST['unit'] ?? 'kg');
    $purchaseRate = floatval($_POST['purchase_rate'] ?? 0);
    $saleRate = floatval($_POST['sale_rate'] ?? 0);
    $purchaseRateMann = floatval($_POST['purchase_rate_mann'] ?? 0);
    $saleRateMann = floatval($_POST['sale_rate_mann'] ?? 0);
    $weight = floatval($_POST['weight'] ?? 0);
    $quantity = floatval($_POST['opening_stock'] ?? 0);
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
            // Use Urdu name as item name if English name is empty
            if (empty($itemName) && !empty($itemNameUrdu)) {
                $itemName = $itemNameUrdu;
            }
            
            $stmt = $db->prepare("UPDATE items SET item_code = ?, item_name = ?, item_name_urdu = ?, category = ?, unit = ?, purchase_rate = ?, sale_rate = ?, opening_stock = ?, current_stock = ?, description = ? WHERE id = ?");
            $stmt->execute([$itemCode, $itemName, $itemNameUrdu, $category, $unit, $purchaseRate, $saleRate, $quantity, $quantity, $fullDescription, $id]);
            
            $success = t('item_updated_success');
            // Refresh item data
            $stmt = $db->prepare("SELECT * FROM items WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            
            // Re-extract fields
            $purchaseRateMann = 0;
            $saleRateMann = 0;
            $companyName = '';
            if (!empty($item['description'])) {
                if (preg_match('/Purchase Rate Mann:\s*([0-9.]+)/', $item['description'], $matches)) {
                    $purchaseRateMann = floatval($matches[1]);
                }
                if (preg_match('/Sale Rate Mann:\s*([0-9.]+)/', $item['description'], $matches)) {
                    $saleRateMann = floatval($matches[1]);
                }
                if (preg_match('/Company:\s*(.+?)(?:\s*\||$)/i', $item['description'], $matches)) {
                    $companyName = trim($matches[1]);
                }
            }
            $weight = floatval($item['opening_stock'] ?? $item['current_stock'] ?? 0);
            $quantity = floatval($item['opening_stock'] ?? $item['current_stock'] ?? 0);
            $purchaseRate = floatval($item['purchase_rate'] ?? 0);
            $saleRate = floatval($item['sale_rate'] ?? 0);
            $totalAmount = $quantity * $saleRate;
            $amount = $quantity * $purchaseRate;
        } catch (PDOException $e) {
            $error = t('error_updating_item') . ': ' . $e->getMessage();
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
                <i class="fas fa-edit"></i> <?php echo t('edit_item'); ?>
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
                    <input type="text" class="form-control" name="item_code" id="item_code" value="<?php echo htmlspecialchars($item['item_code'] ?? ''); ?>" placeholder="<?php echo t('auto_generate'); ?>">
                </div>
                <div class="form-field">
                    <label><?php echo t('category'); ?></label>
                    <input type="text" class="form-control" name="category" id="category" value="<?php echo htmlspecialchars($item['category'] ?? ''); ?>" placeholder="<?php echo t('category'); ?>">
                </div>
            </div>
            
            <!-- Row 2: Item Name and Name Urdu -->
            <div class="form-row-2">
                <div class="form-field">
                    <label><?php echo t('item_name'); ?></label>
                    <input type="text" class="form-control" name="item_name" id="item_name" value="<?php echo htmlspecialchars($item['item_name'] ?? ''); ?>" placeholder="<?php echo t('item_name'); ?>">
                </div>
                <div class="form-field">
                    <label><?php echo t('item_name_urdu'); ?></label>
                    <input type="text" class="form-control" name="item_name_urdu" id="item_name_urdu" value="<?php echo htmlspecialchars($item['item_name_urdu'] ?? ''); ?>" placeholder="<?php echo t('item_name_urdu'); ?>">
                </div>
            </div>
            
            <!-- Row 3: Purchase Rate Kilo and Sale Rate Kilo -->
            <div class="form-row-2">
                <div class="form-field">
                    <label><?php echo t('rate_kilo'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="purchase_rate" id="purchase_rate" value="<?php echo $purchaseRate; ?>" placeholder="0" oninput="calculateAmounts()">
                </div>
                <div class="form-field">
                    <label><?php echo t('sale_rate_kilo'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="sale_rate" id="sale_rate" value="<?php echo $saleRate; ?>" placeholder="0" oninput="calculateAmounts()">
                </div>
            </div>
            
            <!-- Row 4: Purchase Rate Mann and Sale Rate Mann -->
            <div class="form-row-2">
                <div class="form-field">
                    <label><?php echo t('rate_mann'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="purchase_rate_mann" id="purchase_rate_mann" value="<?php echo $purchaseRateMann; ?>" placeholder="0" oninput="calculateAmounts()">
                </div>
                <div class="form-field">
                    <label><?php echo t('sale_rate_mann'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="sale_rate_mann" id="sale_rate_mann" value="<?php echo $saleRateMann; ?>" placeholder="0" oninput="calculateAmounts()">
                </div>
            </div>
            
            <!-- Row 5: Weight and Quantity -->
            <div class="form-row-2">
                <div class="form-field">
                    <label><?php echo t('weight'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="weight" id="weight" value="<?php echo $weight; ?>" placeholder="0" oninput="calculateAmounts()">
                </div>
                <div class="form-field">
                    <label><?php echo t('quantity'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="opening_stock" id="opening_stock" value="<?php echo $quantity; ?>" placeholder="0" oninput="calculateAmounts()">
                </div>
            </div>
            
            <!-- Row 6: Total Amount and Amount -->
            <div class="form-row-2">
                <div class="form-field">
                    <label><?php echo t('credit_amount'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="total_amount" id="total_amount" value="<?php echo $totalAmount; ?>" placeholder="0" readonly>
                </div>
                <div class="form-field">
                    <label><?php echo t('amount'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="amount" id="amount" value="<?php echo $amount; ?>" placeholder="0" readonly>
                </div>
            </div>
            
            <!-- Row 7: Company Name (Full Width) -->
            <div class="form-row-full">
                <label><?php echo t('company_name'); ?></label>
                <input type="text" class="form-control" name="company_name" id="company_name" value="<?php echo htmlspecialchars($companyName); ?>" placeholder="<?php echo t('company_name'); ?>">
            </div>
            
            <!-- Hidden fields for unit -->
            <input type="hidden" name="unit" value="<?php echo htmlspecialchars($item['unit'] ?? 'kg'); ?>">
            
            <div class="btn-submit">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> <?php echo t('save'); ?>
                </button>
                <a href="<?php echo BASE_URL; ?>items/list.php" class="btn btn-secondary" style="margin-left: 10px;">
                    <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
                </a>
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

