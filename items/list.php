<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'all_items';

$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

try {
    $db = getDB();
    
    $where = "WHERE status = 'active'";
    $params = [];
    
    if (!empty($search)) {
        $where .= " AND (item_name LIKE ? OR item_name_urdu LIKE ? OR item_code LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam];
    }
    
    // Get total count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM items $where");
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get items
    $stmt = $db->prepare("SELECT * FROM items $where ORDER BY id DESC LIMIT ? OFFSET ?");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $items = [];
    $totalPages = 0;
    $totalRecords = 0;
}

include '../includes/header.php';
?>

<style>
/* Remove animations from card-header and card-body */
.card {
    transition: none !important;
}
.card:hover {
    transform: none !important;
}
.card-header {
    padding: 20px 25px !important;
    font-size: 18px !important;
}
.card-body {
    animation: none !important;
}
.table tbody tr {
    transition: none !important;
}
.table tbody tr:hover {
    transform: none !important;
}
.btn {
    transition: none !important;
}
.btn:hover {
    transform: none !important;
}
</style>

<div class="row">
    <div class="col-md-12">
        <div style="background: #f5f5f5; padding: 10px 15px; margin-bottom: 10px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <h5 style="margin: 0; font-size: 16px; font-weight: 600; color: #333;">
                <i class="fas fa-list"></i> <?php echo t('all_items'); ?>
            </h5>
            <a href="<?php echo BASE_URL; ?>items/create.php" class="btn btn-primary btn-sm" style="padding: 8px 16px; font-size: 14px; border-radius: 6px;">
                <i class="fas fa-plus"></i> <?php echo t('add_item'); ?>
            </a>
        </div>
        
        <div class="card">
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-bordered table-hover mb-0" style="margin-bottom: 0;">
                        <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 10;">
                            <tr>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;">#</th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo getLang() == 'ur' ? t('name') . ' Name' : 'Name'; ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo getLang() == 'ur' ? t('name') : 'Name Urdu'; ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo t('weight'); ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo t('quantity'); ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo t('purchase_rate'); ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo t('sale_rate'); ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo t('credit_amount'); ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo t('debit'); ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo t('company_name'); ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="11" class="text-center" style="padding: 20px;"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                foreach ($items as $item):
                                    // Use item ID as row number (descending order)
                                    $rowNumber = $item['id']; 
                                    // Extract company name from description
                                    $companyName = '';
                                    if (!empty($item['description'])) {
                                        if (preg_match('/Company:\s*(.+?)(?:\s*\||$)/i', $item['description'], $matches)) {
                                            $companyName = trim($matches[1]);
                                        }
                                    }
                                    
                                    // Get weight and quantity from opening_stock or current_stock
                                    $weight = floatval($item['opening_stock'] ?? $item['current_stock'] ?? 0);
                                    $quantity = floatval($item['opening_stock'] ?? $item['current_stock'] ?? 0);
                                    $purchaseRate = floatval($item['purchase_rate'] ?? 0);
                                    $saleRate = floatval($item['sale_rate'] ?? 0);
                                    $total = $quantity * $saleRate; // جمع (Total/Credit Amount)
                                    $debit = $quantity * $purchaseRate; // بنام (Debit)
                                    
                                    $itemName = htmlspecialchars($item['item_name'] ?? '');
                                    $itemNameUrdu = htmlspecialchars($item['item_name_urdu'] ?? '');
                                ?>
                                    <tr>
                                        <td style="padding: 8px 10px; font-size: 13px;"><?php echo $rowNumber; ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px;"><?php echo $itemName; ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px;"><?php echo $itemNameUrdu; ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px; text-align: right;"><?php echo $weight > 0 ? formatNumber($weight) : ''; ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px; text-align: right;"><?php echo $quantity > 0 ? formatNumber($quantity) : '0'; ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px; text-align: right;"><?php echo $purchaseRate > 0 ? formatNumber($purchaseRate) : ''; ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px; text-align: right;"><?php echo $saleRate > 0 ? formatNumber($saleRate) : ''; ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px; text-align: right;"><?php echo $total > 0 ? formatNumber($total) : ($quantity > 0 ? '0' : ''); ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px; text-align: right;"><?php echo $debit > 0 ? formatNumber($debit) : ''; ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px;"><?php echo htmlspecialchars($companyName); ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px; text-align: center;">
                                            <button type="button" class="btn btn-sm btn-danger delete-item-btn" data-item-id="<?php echo $item['id']; ?>" data-item-name="<?php echo htmlspecialchars(displayItemName($item)); ?>" title="<?php echo t('delete'); ?>" style="padding: 2px 6px; font-size: 12px; margin-right: 3px;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <a href="<?php echo BASE_URL; ?>items/edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo t('edit'); ?>" style="padding: 2px 6px; font-size: 12px;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>"><?php echo t('previous'); ?></a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>"><?php echo t('next'); ?></a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- New Item Modal -->
<div class="modal fade" id="newItemModal" tabindex="-1" aria-labelledby="newItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newItemModalLabel">
                    <i class="fas fa-box"></i> <?php echo t('create_item'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="itemFormMessage"></div>
                <form id="newItemForm">
                    <div class="row">
                        <!-- Row 1: Category, Item Number, Name Urdu -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('category'); ?></label>
                            <input type="text" class="form-control" name="category" id="modal_category" placeholder="<?php echo t('category'); ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php 
                                try {
                                    $stmt = $db->query("SELECT MAX(id) as max_id FROM items");
                                    $maxId = $stmt->fetch()['max_id'] ?? 0;
                                    $nextNumber = $maxId + 1;
                                    echo $nextNumber . ' ' . t('item_code');
                                } catch (PDOException $e) {
                                    echo '1 ' . t('item_code');
                                }
                            ?></label>
                            <input type="text" class="form-control" name="item_code" id="modal_item_code" placeholder="<?php echo t('auto_generate'); ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('item_name_urdu'); ?></label>
                            <input type="text" class="form-control" name="item_name_urdu" id="modal_item_name_urdu" placeholder="<?php echo t('item_name_urdu'); ?>">
                        </div>
                        
                        <!-- Row 2: Purchase Rate Kilo, Sale Rate Kilo, Purchase Rate Mann -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('purchase_rate_kilo'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="purchase_rate" id="modal_purchase_rate" placeholder="0" oninput="calculateModalAmounts()">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('sale_rate_kilo'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="sale_rate" id="modal_sale_rate" placeholder="0" oninput="calculateModalAmounts()">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('purchase_rate_mann'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="purchase_rate_mann" id="modal_purchase_rate_mann" placeholder="0" oninput="calculateModalAmounts()">
                        </div>
                        
                        <!-- Row 3: Sale Rate Mann, Weight, Quantity -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('sale_rate_mann'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="sale_rate_mann" id="modal_sale_rate_mann" placeholder="0" oninput="calculateModalAmounts()">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('weight_weight'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="weight" id="modal_weight" placeholder="0" oninput="calculateModalAmounts()">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('quantity'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="opening_stock" id="modal_opening_stock" placeholder="0" oninput="calculateModalAmounts()">
                        </div>
                        
                        <!-- Row 4: Total Amount, Amount, Company Name -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('grand_amount'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="total_amount" id="modal_total_amount" placeholder="0" readonly>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('amount'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="amount" id="modal_amount" placeholder="0" readonly>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('company_name_company_name'); ?></label>
                            <input type="text" class="form-control" name="company_name" id="modal_company_name" placeholder="<?php echo t('company_name'); ?>">
                        </div>
                        
                        <!-- Row 5: Unit, Min Stock -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('unit'); ?></label>
                            <select class="form-select" name="unit" id="modal_unit">
                                <option value="kg"><?php echo t('kg'); ?></option>
                                <option value="mann"><?php echo t('mann'); ?></option>
                                <option value="pcs"><?php echo t('pcs'); ?></option>
                                <option value="gram"><?php echo t('gram'); ?></option>
                                <option value="liter"><?php echo t('liter'); ?></option>
                                <option value="meter"><?php echo t('meter'); ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('min_stock'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="min_stock" id="modal_min_stock" placeholder="0">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label"><?php echo t('description'); ?></label>
                            <textarea class="form-control" name="description" id="modal_description" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                </button>
                <button type="button" class="btn btn-success" onclick="saveNewItem()">
                    <i class="fas fa-save"></i> <?php echo t('save'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function formatNumber(num) {
    return parseFloat(num) || 0;
}

function calculateModalAmounts() {
    var purchaseRate = formatNumber(document.getElementById('modal_purchase_rate').value);
    var saleRate = formatNumber(document.getElementById('modal_sale_rate').value);
    var weight = formatNumber(document.getElementById('modal_weight').value);
    var quantity = formatNumber(document.getElementById('modal_opening_stock').value);
    
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
    
    document.getElementById('modal_amount').value = amount.toFixed(2);
    document.getElementById('modal_total_amount').value = totalAmount.toFixed(2);
}

function saveNewItem() {
    const form = document.getElementById('newItemForm');
    const formData = new FormData(form);
    const messageDiv = document.getElementById('itemFormMessage');
    
    // Clear previous messages
    messageDiv.innerHTML = '';
    
    // Show loading
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo t('save'); ?>...';
    
    fetch('<?php echo BASE_URL; ?>items/create-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal immediately
            const modal = bootstrap.Modal.getInstance(document.getElementById('newItemModal'));
            modal.hide();
            
            // Reset form
            form.reset();
            
            // Show notification only in fixed position (not in modal)
            showNotification(data.message, 'success');
            
            // Reload after 2 seconds to show notification
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            // Show error only in modal (not in fixed position for validation errors)
            messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        // Show error in modal for network errors
        messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('error_adding_item'); ?></div>';
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

// Reset form when modal is closed
document.getElementById('newItemModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('newItemForm').reset();
    document.getElementById('itemFormMessage').innerHTML = '';
    // Reset calculated fields
    document.getElementById('modal_amount').value = '';
    document.getElementById('modal_total_amount').value = '';
});

// Delete item functionality
document.querySelectorAll('.delete-item-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const itemId = this.getAttribute('data-item-id');
        const itemName = this.getAttribute('data-item-name');
        
        if (confirm('<?php echo t('are_you_sure_delete'); ?> "' + itemName + '"?')) {
            // Show loading
            this.disabled = true;
            const originalHtml = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            fetch('<?php echo BASE_URL; ?>items/delete-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + itemId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message, 'error');
                    this.disabled = false;
                    this.innerHTML = originalHtml;
                }
            })
            .catch(error => {
                showNotification('<?php echo t('error_deleting_item'); ?>', 'error');
                this.disabled = false;
                this.innerHTML = originalHtml;
            });
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>

