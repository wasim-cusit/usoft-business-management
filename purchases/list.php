<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'all_purchases_list';

$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

try {
    $db = getDB();
    
    // Get suppliers and items for modal
    $stmt = $db->query("SELECT * FROM accounts WHERE account_type IN ('supplier', 'both') AND status = 'active' ORDER BY account_name");
    $suppliers = $stmt->fetchAll();
    
    $stmt = $db->query("SELECT * FROM items WHERE status = 'active' ORDER BY item_name");
    $items = $stmt->fetchAll();
    
    $where = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $where .= " AND (p.purchase_no LIKE ? OR a.account_name LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($dateFrom)) {
        $where .= " AND p.purchase_date >= ?";
        $params[] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $where .= " AND p.purchase_date <= ?";
        $params[] = $dateTo;
    }
    
    // Get total count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM purchases p LEFT JOIN accounts a ON p.account_id = a.id $where");
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get purchases
    $stmt = $db->prepare("SELECT p.*, a.account_name, a.account_name_urdu FROM purchases p 
                         LEFT JOIN accounts a ON p.account_id = a.id 
                         $where ORDER BY p.id DESC LIMIT ? OFFSET ?");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $purchases = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $purchases = [];
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
        <h1><i class="fas fa-shopping-cart"></i> <?php echo t('all_purchases_list'); ?></h1>
        <button type="button" class="btn btn-primary mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#newPurchaseModal">
            <i class="fas fa-plus"></i> <?php echo t('new_purchase'); ?>
        </button>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0"><?php echo t('all_purchases'); ?></h5>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="row g-2">
                            <div class="col-md-4">
                                <input type="text" class="form-control form-control-sm" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo t('search'); ?>...">
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control form-control-sm" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="<?php echo t('date_from'); ?>">
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control form-control-sm" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="<?php echo t('date_to'); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php echo t('bill_no'); ?></th>
                                <th><?php echo t('date'); ?></th>
                                <th><?php echo t('supplier'); ?></th>
                                <th><?php echo t('total'); ?></th>
                                <th><?php echo t('discount'); ?></th>
                                <th><?php echo t('net_amount'); ?></th>
                                <th><?php echo t('paid_amount'); ?></th>
                                <th><?php echo t('balance'); ?></th>
                                <th><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($purchases)): ?>
                                <tr>
                                    <td colspan="9" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($purchases as $purchase): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($purchase['purchase_no']); ?></td>
                                        <td><?php echo formatDate($purchase['purchase_date']); ?></td>
                                        <td><?php echo displayAccountNameFull($purchase); ?></td>
                                        <td><?php echo formatCurrency($purchase['total_amount']); ?></td>
                                        <td><?php echo formatCurrency($purchase['discount']); ?></td>
                                        <td><strong><?php echo formatCurrency($purchase['net_amount']); ?></strong></td>
                                        <td><?php echo formatCurrency($purchase['paid_amount']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $purchase['balance_amount'] > 0 ? 'bg-warning' : 'bg-success'; ?>">
                                                <?php echo formatCurrency($purchase['balance_amount']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>purchases/view.php?id=<?php echo $purchase['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="<?php echo t('page_navigation'); ?>">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>"><?php echo t('previous'); ?></a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>"><?php echo t('next'); ?></a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- New Purchase Modal -->
<div class="modal fade" id="newPurchaseModal" tabindex="-1" aria-labelledby="newPurchaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newPurchaseModalLabel">
                    <i class="fas fa-shopping-cart"></i> <?php echo t('add_purchase'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="purchaseFormMessage"></div>
                <form id="newPurchaseForm">
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label"><?php echo t('date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="purchase_date" id="purchase_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('supplier'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" name="account_id" id="account_id" required>
                                <option value="">-- <?php echo t('select'); ?> --</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>">
                                        <?php echo displayAccountNameFull($supplier); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('remarks'); ?></label>
                            <input type="text" class="form-control" name="remarks" id="remarks">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><strong><?php echo t('items'); ?></strong></h6>
                            <button type="button" class="btn btn-sm btn-success" id="addPurchaseRow">
                                <i class="fas fa-plus"></i> <?php echo t('add'); ?>
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th><?php echo t('item_name'); ?></th>
                                        <th width="18%"><?php echo t('quantity'); ?></th>
                                        <th width="18%"><?php echo t('rate'); ?></th>
                                        <th width="18%"><?php echo t('amount'); ?></th>
                                        <th width="8%"><?php echo t('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="purchaseItemsBody">
                                    <tr>
                                        <td>
                                            <select class="form-select form-select-sm item-select" name="item_id[]" required>
                                                <option value="">-- <?php echo t('select'); ?> --</option>
                                                <?php foreach ($items as $item): ?>
                                                    <option value="<?php echo $item['id']; ?>" data-rate="<?php echo $item['purchase_rate']; ?>">
                                                        <?php echo htmlspecialchars($item['item_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm quantity" name="quantity[]" required></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm rate" name="rate[]" required></td>
                                        <td><input type="text" class="form-control form-control-sm amount" readonly></td>
                                        <td><button type="button" class="btn btn-danger btn-sm remove-purchase-row" disabled><i class="fas fa-times"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('total'); ?></label>
                            <input type="text" class="form-control" id="total_amount" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('discount'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="discount" id="discount" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('net_amount'); ?></label>
                            <input type="text" class="form-control" id="net_amount" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('paid_amount'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="paid_amount" id="paid_amount" value="0">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label"><?php echo t('balance'); ?></label>
                            <input type="text" class="form-control" id="balance_amount" readonly>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                </button>
                <button type="button" class="btn btn-primary" onclick="saveNewPurchase()">
                    <i class="fas fa-save"></i> <?php echo t('save'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Add new row
document.getElementById('addPurchaseRow').addEventListener('click', function() {
    const tbody = document.getElementById('purchaseItemsBody');
    const firstRow = tbody.querySelector('tr');
    const newRow = firstRow.cloneNode(true);
    
    // Clear values
    newRow.querySelector('.item-select').value = '';
    newRow.querySelector('.quantity').value = '';
    newRow.querySelector('.rate').value = '';
    newRow.querySelector('.amount').value = '';
    newRow.querySelector('.remove-purchase-row').disabled = false;
    
    tbody.appendChild(newRow);
    updateRemoveButtons();
});

// Remove row
document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-purchase-row')) {
        const tbody = document.getElementById('purchaseItemsBody');
        if (tbody.querySelectorAll('tr').length > 1) {
            e.target.closest('tr').remove();
            calculatePurchaseTotal();
            updateRemoveButtons();
        }
    }
});

// Update remove buttons state
function updateRemoveButtons() {
    const tbody = document.getElementById('purchaseItemsBody');
    const rows = tbody.querySelectorAll('tr');
    rows.forEach((row, index) => {
        const btn = row.querySelector('.remove-purchase-row');
        btn.disabled = rows.length === 1;
    });
}

// Calculate amount for a row
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('quantity') || e.target.classList.contains('rate')) {
        const row = e.target.closest('tr');
        const qty = parseFloat(row.querySelector('.quantity').value) || 0;
        const rate = parseFloat(row.querySelector('.rate').value) || 0;
        const amount = qty * rate;
        row.querySelector('.amount').value = amount.toFixed(2);
        calculatePurchaseTotal();
    }
});

// Set rate when item selected
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('item-select')) {
        const selectedOption = e.target.options[e.target.selectedIndex];
        const rate = selectedOption.getAttribute('data-rate');
        if (rate) {
            e.target.closest('tr').querySelector('.rate').value = rate;
            const qty = parseFloat(e.target.closest('tr').querySelector('.quantity').value) || 0;
            const amount = qty * parseFloat(rate);
            e.target.closest('tr').querySelector('.amount').value = amount.toFixed(2);
            calculatePurchaseTotal();
        }
    }
});

// Calculate totals
function calculatePurchaseTotal() {
    const rows = document.querySelectorAll('#purchaseItemsBody tr');
    let total = 0;
    
    rows.forEach(row => {
        const amount = parseFloat(row.querySelector('.amount').value) || 0;
        total += amount;
    });
    
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0;
    const netAmount = total - discount;
    const balance = netAmount - paidAmount;
    
    document.getElementById('total_amount').value = total.toFixed(2);
    document.getElementById('net_amount').value = netAmount.toFixed(2);
    document.getElementById('balance_amount').value = balance.toFixed(2);
}

// Calculate on discount/paid amount change
document.getElementById('discount').addEventListener('input', calculatePurchaseTotal);
document.getElementById('paid_amount').addEventListener('input', calculatePurchaseTotal);

function saveNewPurchase() {
    const form = document.getElementById('newPurchaseForm');
    const formData = new FormData(form);
    const messageDiv = document.getElementById('purchaseFormMessage');
    
    // Clear previous messages
    messageDiv.innerHTML = '';
    
    // Validate required fields
    if (!formData.get('account_id')) {
        messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('please_select_supplier'); ?></div>';
        return;
    }
    
    // Validate items
    const itemIds = formData.getAll('item_id[]');
    const quantities = formData.getAll('quantity[]');
    const rates = formData.getAll('rate[]');
    
    let hasValidItem = false;
    for (let i = 0; i < itemIds.length; i++) {
        if (itemIds[i] && quantities[i] && rates[i]) {
            hasValidItem = true;
            break;
        }
    }
    
    if (!hasValidItem) {
        messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('please_add_item'); ?></div>';
        return;
    }
    
    // Show loading
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo t('save'); ?>...';
    
    fetch('<?php echo BASE_URL; ?>purchases/create-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal immediately
            const modal = bootstrap.Modal.getInstance(document.getElementById('newPurchaseModal'));
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
        messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('error_adding_purchase'); ?></div>';
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

// Reset form when modal is closed
document.getElementById('newPurchaseModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('newPurchaseForm').reset();
    document.getElementById('purchaseFormMessage').innerHTML = '';
    // Reset to single row
    const tbody = document.getElementById('purchaseItemsBody');
    while (tbody.children.length > 1) {
        tbody.removeChild(tbody.lastChild);
    }
    updateRemoveButtons();
    calculatePurchaseTotal();
});
</script>

<?php include '../includes/footer.php'; ?>

