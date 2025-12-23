<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'all_sales_list';

$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

try {
    $db = getDB();
    
    // Get customers and items for modal
    $stmt = $db->query("SELECT * FROM accounts WHERE account_type IN ('customer', 'both') AND status = 'active' ORDER BY account_name");
    $customers = $stmt->fetchAll();
    
    $stmt = $db->query("SELECT * FROM items WHERE status = 'active' ORDER BY item_name");
    $items = $stmt->fetchAll();
    
    $where = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $where .= " AND (s.sale_no LIKE ? OR a.account_name LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($dateFrom)) {
        $where .= " AND s.sale_date >= ?";
        $params[] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $where .= " AND s.sale_date <= ?";
        $params[] = $dateTo;
    }
    
    // Get total count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM sales s LEFT JOIN accounts a ON s.account_id = a.id $where");
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get sales
    $stmt = $db->prepare("SELECT s.*, a.account_name, a.account_name_urdu FROM sales s 
                         LEFT JOIN accounts a ON s.account_id = a.id 
                         $where ORDER BY s.id DESC LIMIT ? OFFSET ?");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $sales = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $customers = [];
    $items = [];
    $sales = [];
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
    min-height: auto !important;
    height: auto !important;
}
.card-header .row {
    margin: 0 !important;
}
.card-header form {
    margin: 0 !important;
}
.card-header .form-control-sm {
    height: calc(1.5em + 0.5rem + 2px) !important;
}
.card-header .btn-sm {
    padding: 0.25rem 0.5rem !important;
    font-size: 0.875rem !important;
    line-height: 1.5 !important;
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
        <h1><i class="fas fa-cash-register"></i> <?php echo t('all_sales_list'); ?></h1>
        <button type="button" class="btn btn-primary mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#newSaleModal">
            <i class="fas fa-plus"></i> <?php echo t('new_sale'); ?>
        </button>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0"><?php echo t('all_sales_list'); ?></h5>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="row g-2">
                            <div class="col-md-5">
                                <input type="text" class="form-control form-control-sm" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo t('search'); ?>...">
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control form-control-sm" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="<?php echo t('date_from'); ?>">
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control form-control-sm" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="<?php echo t('date_to'); ?>">
                            </div>
                            <div class="col-md-1">
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
                                <th><?php echo t('customer'); ?></th>
                                <th><?php echo t('total'); ?></th>
                                <th><?php echo t('discount'); ?></th>
                                <th><?php echo t('net_amount'); ?></th>
                                <th><?php echo t('paid_amount'); ?></th>
                                <th><?php echo t('balance'); ?></th>
                                <th><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sales)): ?>
                                <tr>
                                    <td colspan="9" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sales as $sale): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sale['sale_no']); ?></td>
                                        <td><?php echo formatDate($sale['sale_date']); ?></td>
                                        <td><?php echo displayAccountNameFull($sale); ?></td>
                                        <td><?php echo formatCurrency($sale['total_amount']); ?></td>
                                        <td><?php echo formatCurrency($sale['discount']); ?></td>
                                        <td><strong><?php echo formatCurrency($sale['net_amount']); ?></strong></td>
                                        <td><?php echo formatCurrency($sale['paid_amount']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $sale['balance_amount'] > 0 ? 'bg-warning' : 'bg-success'; ?>">
                                                <?php echo formatCurrency($sale['balance_amount']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>sales/view.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-info">
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

<!-- New Sale Modal -->
<div class="modal fade" id="newSaleModal" tabindex="-1" aria-labelledby="newSaleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newSaleModalLabel">
                    <i class="fas fa-cash-register"></i> <?php echo t('add_sale'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="saleFormMessage"></div>
                <form id="newSaleForm">
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label"><?php echo t('date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="sale_date" id="sale_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('customer'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" name="account_id" id="sale_account_id" required>
                                <option value="">-- <?php echo t('select'); ?> --</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['id']; ?>">
                                        <?php echo displayAccountNameFull($customer); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('remarks'); ?></label>
                            <input type="text" class="form-control" name="remarks" id="sale_remarks">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><strong><?php echo t('items'); ?></strong></h6>
                            <button type="button" class="btn btn-sm btn-success" id="addSaleRow">
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
                                <tbody id="saleItemsBody">
                                    <tr>
                                        <td>
                                            <select class="form-select form-select-sm sale-item-select" name="item_id[]" required>
                                                <option value="">-- <?php echo t('select'); ?> --</option>
                                                <?php foreach ($items as $item): ?>
                                                    <option value="<?php echo $item['id']; ?>" data-rate="<?php echo $item['sale_rate']; ?>" data-stock="<?php echo $item['current_stock']; ?>">
                                                        <?php echo htmlspecialchars($item['item_name']); ?> (<?php echo t('stock_label'); ?>: <?php echo $item['current_stock']; ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm sale-quantity" name="quantity[]" required></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm sale-rate" name="rate[]" required></td>
                                        <td><input type="text" class="form-control form-control-sm sale-amount" readonly></td>
                                        <td><button type="button" class="btn btn-danger btn-sm remove-sale-row" disabled><i class="fas fa-times"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('total'); ?></label>
                            <input type="text" class="form-control" id="sale_total_amount" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('discount'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="discount" id="sale_discount" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('net_amount'); ?></label>
                            <input type="text" class="form-control" id="sale_net_amount" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('receipt'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="paid_amount" id="sale_paid_amount" value="0">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label"><?php echo t('balance'); ?></label>
                            <input type="text" class="form-control" id="sale_balance_amount" readonly>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                </button>
                <button type="button" class="btn btn-primary" onclick="saveNewSale()">
                    <i class="fas fa-save"></i> <?php echo t('save'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Add new row
document.getElementById('addSaleRow').addEventListener('click', function() {
    const tbody = document.getElementById('saleItemsBody');
    const firstRow = tbody.querySelector('tr');
    const newRow = firstRow.cloneNode(true);
    
    // Clear values
    newRow.querySelector('.sale-item-select').value = '';
    newRow.querySelector('.sale-quantity').value = '';
    newRow.querySelector('.sale-rate').value = '';
    newRow.querySelector('.sale-amount').value = '';
    newRow.querySelector('.remove-sale-row').disabled = false;
    
    tbody.appendChild(newRow);
    updateSaleRemoveButtons();
});

// Remove row
document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-sale-row')) {
        const tbody = document.getElementById('saleItemsBody');
        if (tbody.querySelectorAll('tr').length > 1) {
            e.target.closest('tr').remove();
            calculateSaleTotal();
            updateSaleRemoveButtons();
        }
    }
});

// Update remove buttons state
function updateSaleRemoveButtons() {
    const rows = document.querySelectorAll('#saleItemsBody tr');
    rows.forEach((row, index) => {
        const btn = row.querySelector('.remove-sale-row');
        btn.disabled = rows.length === 1;
    });
}

// Calculate row amount
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('sale-quantity') || e.target.classList.contains('sale-rate')) {
        const row = e.target.closest('tr');
        calculateSaleRowAmount(row);
    }
});

// Check stock when quantity changes
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('sale-quantity')) {
        const row = e.target.closest('tr');
        const qty = parseFloat(e.target.value) || 0;
        const stock = parseFloat(row.querySelector('.sale-item-select option:selected')?.dataset.stock) || 0;
        
        if (qty > stock) {
            alert('<?php echo t('insufficient_stock'); ?>! <?php echo t('current_stock_label'); ?>: ' + stock);
            e.target.value = stock;
            calculateSaleRowAmount(row);
        }
    }
});

// Set rate when item selected
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('sale-item-select')) {
        const row = e.target.closest('tr');
        const selectedOption = e.target.options[e.target.selectedIndex];
        const rate = selectedOption.dataset.rate;
        const stock = selectedOption.dataset.stock;
        
        if (rate) {
            row.querySelector('.sale-rate').value = rate;
        }
        
        if (stock !== undefined) {
            row.querySelector('.sale-quantity').setAttribute('max', stock);
        }
        
        calculateSaleRowAmount(row);
    }
});

function calculateSaleRowAmount(row) {
    const qty = parseFloat(row.querySelector('.sale-quantity').value) || 0;
    const rate = parseFloat(row.querySelector('.sale-rate').value) || 0;
    const amount = qty * rate;
    row.querySelector('.sale-amount').value = amount.toFixed(2);
    calculateSaleTotal();
}

function calculateSaleTotal() {
    let total = 0;
    document.querySelectorAll('.sale-amount').forEach(function(input) {
        total += parseFloat(input.value) || 0;
    });
    
    document.getElementById('sale_total_amount').value = total.toFixed(2);
    
    const discount = parseFloat(document.getElementById('sale_discount').value) || 0;
    const netAmount = total - discount;
    document.getElementById('sale_net_amount').value = netAmount.toFixed(2);
    
    const paid = parseFloat(document.getElementById('sale_paid_amount').value) || 0;
    const balance = netAmount - paid;
    document.getElementById('sale_balance_amount').value = balance.toFixed(2);
}

// Calculate totals when discount or paid amount changes
document.getElementById('sale_discount').addEventListener('input', calculateSaleTotal);
document.getElementById('sale_paid_amount').addEventListener('input', calculateSaleTotal);

// Save new sale
function saveNewSale() {
    const form = document.getElementById('newSaleForm');
    const formData = new FormData(form);
    const messageDiv = document.getElementById('saleFormMessage');
    
    // Clear previous messages
    messageDiv.innerHTML = '';
    
    // Validate required fields
    if (!formData.get('account_id')) {
        messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('please_select_customer'); ?></div>';
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
    
    fetch('<?php echo BASE_URL; ?>sales/create-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal immediately
            const modal = bootstrap.Modal.getInstance(document.getElementById('newSaleModal'));
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
        messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('error_adding_sale'); ?></div>';
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

// Reset form on modal close
document.getElementById('newSaleModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('newSaleForm').reset();
    document.getElementById('saleFormMessage').innerHTML = '';
    const tbody = document.getElementById('saleItemsBody');
    while (tbody.children.length > 1) {
        tbody.removeChild(tbody.lastChild);
    }
    tbody.querySelector('.sale-item-select').value = '';
    tbody.querySelector('.sale-quantity').value = '';
    tbody.querySelector('.sale-rate').value = '';
    tbody.querySelector('.sale-amount').value = '';
    document.getElementById('sale_date').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('sale_discount').value = '0';
    document.getElementById('sale_paid_amount').value = '0';
    calculateSaleTotal();
    updateSaleRemoveButtons();
});

// Initialize
updateSaleRemoveButtons();
calculateSaleTotal();
</script>

<?php include '../includes/footer.php'; ?>

