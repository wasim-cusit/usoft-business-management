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
    animation: none !important;
    padding: 15px 20px !important;
    font-size: 16px !important;
}
.card-header::before {
    animation: none !important;
    display: none !important;
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

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-box"></i> <?php echo t('all_items'); ?></h1>
        <button type="button" class="btn btn-primary mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#newItemModal">
            <i class="fas fa-plus"></i> <?php echo t('new_item'); ?>
        </button>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0"><?php echo t('all_items'); ?></h5>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="text" class="form-control form-control-sm" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo t('search'); ?>...">
                            <button type="submit" class="btn btn-primary btn-sm ms-2">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php echo t('code'); ?></th>
                                <th><?php echo t('item_name'); ?> (<?php echo getLang() == 'ur' ? t('urdu') : t('english'); ?>)</th>
                                <th><?php echo t('item_name'); ?> (<?php echo getLang() == 'ur' ? t('english') : t('urdu'); ?>)</th>
                                <th><?php echo t('category'); ?></th>
                                <th><?php echo t('unit'); ?></th>
                                <th><?php echo t('purchase_rate'); ?></th>
                                <th><?php echo t('sale_rate'); ?></th>
                                <th><?php echo t('current_stock'); ?></th>
                                <th><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="9" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                                        <td><?php echo displayItemName($item); ?></td>
                                        <td><?php echo getLang() == 'ur' ? htmlspecialchars($item['item_name'] ?? '') : htmlspecialchars($item['item_name_urdu'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($item['category'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                        <td><?php echo formatCurrency($item['purchase_rate']); ?></td>
                                        <td><?php echo formatCurrency($item['sale_rate']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $item['current_stock'] <= $item['min_stock'] ? 'bg-warning' : 'bg-success'; ?>">
                                                <?php echo number_format($item['current_stock'], 2); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>items/edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning">
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
    <div class="modal-dialog modal-dialog-centered modal-lg">
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
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('item_code'); ?></label>
                            <input type="text" class="form-control" name="item_code" id="item_code" placeholder="<?php echo t('auto_generate'); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('item_name_required'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="item_name" id="item_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('item_name_urdu'); ?></label>
                            <input type="text" class="form-control" name="item_name_urdu" id="item_name_urdu">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('category'); ?></label>
                            <input type="text" class="form-control" name="category" id="category">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('unit'); ?></label>
                            <select class="form-select" name="unit" id="unit">
                                <option value="pcs"><?php echo t('pcs'); ?></option>
                                <option value="kg"><?php echo t('kg'); ?></option>
                                <option value="gram"><?php echo t('gram'); ?></option>
                                <option value="liter"><?php echo t('liter'); ?></option>
                                <option value="meter"><?php echo t('meter'); ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('purchase_rate'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="purchase_rate" id="purchase_rate" value="0">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('sale_rate'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="sale_rate" id="sale_rate" value="0">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('opening_stock'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="opening_stock" id="opening_stock" value="0">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('min_stock'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="min_stock" id="min_stock" value="0">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label"><?php echo t('description'); ?></label>
                            <textarea class="form-control" name="description" id="description" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                </button>
                <button type="button" class="btn btn-primary" onclick="saveNewItem()">
                    <i class="fas fa-save"></i> <?php echo t('save'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function saveNewItem() {
    const form = document.getElementById('newItemForm');
    const formData = new FormData(form);
    const messageDiv = document.getElementById('itemFormMessage');
    
    // Clear previous messages
    messageDiv.innerHTML = '';
    
    // Validate required field
    if (!formData.get('item_name').trim()) {
        messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('please_enter_item_name'); ?></div>';
        return;
    }
    
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
});
</script>

<?php include '../includes/footer.php'; ?>

