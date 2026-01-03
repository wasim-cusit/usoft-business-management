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
    
    // Check if cash sale account exists, if not create it
    $stmt = $db->query("SELECT id FROM accounts WHERE account_name = 'Cash Sale' OR account_name_urdu = 'کیش فروخت' LIMIT 1");
    $cashAccount = $stmt->fetch();
    if (!$cashAccount) {
        // Create cash sale account
        try {
            $stmt = $db->prepare("INSERT INTO accounts (account_name, account_name_urdu, account_type, status) VALUES (?, ?, 'customer', 'active')");
            $stmt->execute(['Cash Sale', 'کیش فروخت']);
            $cashAccountId = $db->lastInsertId();
        } catch (PDOException $e) {
            // If creation fails, try to get existing one
            $stmt = $db->query("SELECT id FROM accounts WHERE account_name LIKE '%Cash%' OR account_name_urdu LIKE '%کیش%' LIMIT 1");
            $cashAccount = $stmt->fetch();
            $cashAccountId = $cashAccount ? $cashAccount['id'] : 0;
        }
    } else {
        $cashAccountId = $cashAccount['id'];
    }
    
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
    
    // Get sales with item quantities
    $paramsForQuery = $params;
    $paramsForQuery[] = $limit;
    $paramsForQuery[] = $offset;
    $stmt = $db->prepare("SELECT s.*, a.account_name, a.account_name_urdu, a.mobile, a.phone,
                         COALESCE(SUM(si.wt2), 0) as total_qty
                         FROM sales s 
                         LEFT JOIN accounts a ON s.account_id = a.id 
                         LEFT JOIN sale_items si ON s.id = si.sale_id
                         $where 
                         GROUP BY s.id
                         ORDER BY s.id DESC LIMIT ? OFFSET ?");
    $stmt->execute($paramsForQuery);
    $sales = $stmt->fetchAll();
    
    // Calculate totals
    $stmt = $db->prepare("SELECT 
                            COALESCE(SUM(s.total_amount), 0) as total_amount,
                            COALESCE(SUM(s.discount), 0) as total_discount,
                            COALESCE(SUM(s.net_amount), 0) as total_net_amount,
                            COALESCE(SUM(s.paid_amount), 0) as total_paid_amount,
                            COALESCE(SUM(s.balance_amount), 0) as total_balance_amount
                         FROM sales s 
                         LEFT JOIN accounts a ON s.account_id = a.id 
                         $where");
    $stmt->execute($params);
    $totals = $stmt->fetch();
    $totalAmount = $totals['total_amount'] ?? 0;
    $totalDiscount = $totals['total_discount'] ?? 0;
    $totalNetAmount = $totals['total_net_amount'] ?? 0;
    $totalPaidAmount = $totals['total_paid_amount'] ?? 0;
    $totalBalanceAmount = $totals['total_balance_amount'] ?? 0;
    
} catch (PDOException $e) {
    $customers = [];
    $items = [];
    $sales = [];
    $totalPages = 0;
    $totalRecords = 0;
    $totalAmount = 0;
    $totalDiscount = 0;
    $totalNetAmount = 0;
    $totalPaidAmount = 0;
    $totalBalanceAmount = 0;
    $cashAccountId = 0;
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
/* Ensure footer displays in one row */
.table tfoot tr {
    display: table-row !important;
}
.table tfoot td {
    white-space: nowrap !important;
    vertical-align: middle !important;
}
/* Stock warning notification styling - clear background and text */
#saleFormMessage .alert-danger,
.modal .alert-danger {
    background-color: #f8d7da !important;
    border: 1px solid #f5c2c7 !important;
    color: #842029 !important;
    box-shadow: 0 4px 6px rgba(0,0,0,0.15) !important;
    opacity: 1 !important;
}
#saleFormMessage .alert-danger strong,
#saleFormMessage .alert-danger span,
#saleFormMessage .alert-danger small,
.modal .alert-danger strong,
.modal .alert-danger span,
.modal .alert-danger small {
    color: #842029 !important;
    font-weight: 600;
}
/* Success notification styling */
#saleFormMessage .alert-success,
.modal .alert-success {
    background-color: #d1e7dd !important;
    border: 1px solid #badbcc !important;
    color: #0f5132 !important;
    box-shadow: 0 4px 6px rgba(0,0,0,0.15) !important;
    opacity: 1 !important;
}
#saleFormMessage .alert-success strong,
#saleFormMessage .alert-success span,
.modal .alert-success strong,
.modal .alert-success span {
    color: #0f5132 !important;
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
                        <h5 class="mb-0"><?php echo t('all_sales_list'); ?> <span class="badge bg-primary"><?php echo $totalRecords ?? 0; ?></span></h5>
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
                    <table class="table table-hover" id="salesTable">
                        <thead>
                            <tr>
                                <th><?php echo t('inv_number'); ?></th>
                                <th><?php echo t('date'); ?></th>
                                <th><?php echo t('name'); ?></th>
                                <th><?php echo t('adda'); ?></th>
                                <th><?php echo t('details'); ?></th>
                                <th><?php echo t('phone'); ?></th>
                                <th><?php echo t('bilti'); ?></th>
                                <th><?php echo t('quantity'); ?></th>
                                <th><?php echo t('amount'); ?></th>
                                <th><?php echo t('bardana'); ?></th>
                                <th><?php echo t('netcash'); ?></th>
                                <th><?php echo t('grand_amount'); ?></th>
                                <th><?php echo t('delete'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sales)): ?>
                                <tr>
                                    <td colspan="13" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sales as $sale): 
                                    // Use sale phone if available, otherwise use account phone/mobile
                                    $displayPhone = $sale['phone'] ?? $sale['phone'] ?? $sale['mobile'] ?? '';
                                    $displayLocation = $sale['location'] ?? '';
                                    $displayDetails = $sale['details'] ?? '';
                                    $displayBilti = $sale['bilti'] ?? '';
                                    $displayBardana = $sale['bardana'] ?? 0;
                                    $displayNetcash = $sale['netcash'] ?? 0;
                                    $grandAmount = $sale['net_amount'] ?? $sale['total_amount'] ?? 0;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sale['sale_no'] ?? ''); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($sale['sale_date'])); ?></td>
                                        <td><?php echo displayAccountNameFull($sale); ?></td>
                                        <td><?php echo htmlspecialchars($displayLocation); ?></td>
                                        <td><?php echo htmlspecialchars($displayDetails); ?></td>
                                        <td><?php echo htmlspecialchars($displayPhone); ?></td>
                                        <td><?php echo htmlspecialchars($displayBilti); ?></td>
                                        <td><?php echo number_format($sale['total_qty'] ?? 0, 2); ?></td>
                                        <td><?php echo formatCurrency($sale['total_amount'] ?? 0); ?></td>
                                        <td><?php echo formatCurrency($displayBardana); ?></td>
                                        <td><?php echo formatCurrency($displayNetcash); ?></td>
                                        <td><strong><?php echo formatCurrency($grandAmount); ?></strong></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>sales/view.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-info" title="<?php echo t('view'); ?>">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>sales/edit.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-warning ms-1" title="<?php echo t('edit'); ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>sales/print.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-secondary ms-1" target="_blank" title="<?php echo t('print'); ?>">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger delete-sale-btn ms-1" data-sale-id="<?php echo $sale['id']; ?>" data-sale-no="<?php echo htmlspecialchars($sale['sale_no']); ?>" title="<?php echo t('delete'); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <td colspan="7" class="text-end" style="white-space: nowrap;"><strong>Total:</strong></td>
                                <td class="text-end" style="white-space: nowrap;"><strong><?php 
                                    $totalQty = 0;
                                    foreach ($sales as $s) {
                                        $totalQty += ($s['total_qty'] ?? 0);
                                    }
                                    echo number_format($totalQty, 2);
                                ?></strong></td>
                                <td class="text-end" style="white-space: nowrap;"><strong><?php echo formatCurrency($totalAmount); ?></strong></td>
                                <td class="text-end" style="white-space: nowrap;"><strong><?php 
                                    $totalBardana = 0;
                                    foreach ($sales as $s) {
                                        $totalBardana += ($s['bardana'] ?? 0);
                                    }
                                    echo formatCurrency($totalBardana);
                                ?></strong></td>
                                <td class="text-end" style="white-space: nowrap;"><strong><?php 
                                    $totalNetcash = 0;
                                    foreach ($sales as $s) {
                                        $totalNetcash += ($s['netcash'] ?? 0);
                                    }
                                    echo formatCurrency($totalNetcash);
                                ?></strong></td>
                                <td class="text-end" style="white-space: nowrap;"><strong><?php echo formatCurrency($totalNetAmount); ?></strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
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
                    <div class="row align-items-end">
                        <div class="col-md-2 mb-3">
                            <label class="form-label"><?php echo t('date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="sale_date" id="sale_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label class="form-label"><?php echo t('name_party'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" name="account_id" id="sale_account_id" required>
                                <option value="">-- <?php echo t('select'); ?> --</option>
                                <option value="<?php echo $cashAccountId ?? 0; ?>" style="font-weight: bold; color: #0d6efd;">
                                    <?php echo t('cash_sale'); ?>
                                </option>
                                <?php 
                                foreach ($customers as $customer): 
                                    // Skip cash account if it's already in the list
                                    if (isset($cashAccountId) && $customer['id'] == $cashAccountId) continue;
                                ?>
                                    <option value="<?php echo $customer['id']; ?>">
                                        <?php echo displayAccountNameFull($customer); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label class="form-label"><?php echo t('location'); ?></label>
                            <input type="text" class="form-control" name="location" id="sale_location" placeholder="<?php echo t('location'); ?>">
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label class="form-label"><?php echo t('details'); ?></label>
                            <input type="text" class="form-control" name="details" id="sale_details" placeholder="<?php echo t('details'); ?>">
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label class="form-label"><?php echo t('phone'); ?></label>
                            <input type="text" class="form-control" name="phone" id="sale_phone" placeholder="<?php echo t('phone'); ?>">
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label class="form-label"><?php echo t('bilti'); ?></label>
                            <input type="text" class="form-control" name="bilti" id="sale_bilti" placeholder="<?php echo t('bilti'); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><strong><?php echo t('items'); ?></strong></h6>
                            <button type="button" class="btn btn-sm btn-success" id="addSaleRow">
                                <i class="fas fa-plus"></i> <?php echo t('add'); ?>
                            </button>
                        </div>
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                                    <tr>
                                        <th style="width: 20%;"><?php echo t('item_name'); ?></th>
                                        <th style="width: 8%;"><?php echo t('qty'); ?></th>
                                        <th style="width: 8%;"><?php echo t('toda'); ?></th>
                                        <th style="width: 8%;"><?php echo t('bharti'); ?></th>
                                        <th style="width: 8%;"><?php echo t('weight'); ?></th>
                                        <th style="width: 8%;"><?php echo t('cut'); ?></th>
                                        <th style="width: 8%;"><?php echo t('net'); ?></th>
                                        <th style="width: 10%;"><?php echo t('rate'); ?></th>
                                        <th style="width: 12%;"><?php echo t('amount'); ?></th>
                                        <th style="width: 10%;"><?php echo t('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="saleItemsBody">
                                    <tr class="sale-item-row">
                                        <td>
                                            <input type="text" class="form-control form-control-sm sale-itemname" name="itemname[]" list="saleItemList" placeholder="<?php echo t('item_name'); ?>" autocomplete="off">
                                            <input type="hidden" class="sale-item-id" name="item_id[]">
                                            <datalist id="saleItemList">
                                                <?php foreach ($items as $item): ?>
                                                    <option value="<?php echo htmlspecialchars($item['item_name']); ?>" data-id="<?php echo $item['id']; ?>" data-rate="<?php echo $item['sale_rate']; ?>" data-stock="<?php echo $item['current_stock']; ?>">
                                                <?php endforeach; ?>
                                            </datalist>
                                        </td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm sale-qty" name="qty[]" placeholder="<?php echo t('qty'); ?>" onkeyup="saleCalwe(this);" oninput="saleCalwe(this);"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm sale-narch" name="narch[]" placeholder="<?php echo t('toda'); ?>" onkeyup="saleCalwe(this);" oninput="saleCalwe(this);"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm sale-bag" name="bag[]" placeholder="<?php echo t('bharti'); ?>" onkeyup="saleCalwe(this);" oninput="saleCalwe(this);"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm sale-wt" name="wt[]" placeholder="<?php echo t('weight'); ?>" readonly></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm sale-kate" name="kate[]" placeholder="<?php echo t('cut'); ?>" onkeyup="saleCalamo(this);" oninput="saleCalamo(this);"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm sale-wt2" name="wt2[]" placeholder="<?php echo t('net'); ?>" readonly></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm sale-rate" name="rate[]" placeholder="<?php echo t('rate'); ?>" onkeyup="saleCalamo(this);" oninput="saleCalamo(this);"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm sale-amount" name="amount[]" placeholder="<?php echo t('amount'); ?>" readonly></td>
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
                            <input type="number" step="0.01" class="form-control" name="discount" id="sale_discount" placeholder="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('net_amount'); ?></label>
                            <input type="text" class="form-control" id="sale_net_amount" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('receipt'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="paid_amount" id="sale_paid_amount" placeholder="0">
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
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
// Format number helper
function formatNumber(num) {
    if (!num && num !== 0) return '0.00';
    num = parseFloat(num);
    if (isNaN(num)) return '0.00';
    if (num % 1 === 0) {
        return num.toString();
    }
    return num.toFixed(2);
}

// Calculate weight: wt = qty + narch + bag
// Formula: Weight = Qty + Toda + Bharti
function saleCalwe(input) {
    const row = input.closest('tr');
    const qty = parseFloat(row.querySelector('.sale-qty').value) || 0;
    const narch = parseFloat(row.querySelector('.sale-narch').value) || 0;
    const bag = parseFloat(row.querySelector('.sale-bag').value) || 0;
    
    // Formula: wt = qty + narch + bag
    const wt = qty + narch + bag;
    row.querySelector('.sale-wt').value = formatNumber(wt);
    
    // Also trigger saleCalamo to update net weight and amount
    saleCalamo(input);
}

// Calculate net weight and amount: wt2 = wt - kate, amount = wt2 * rate
// Formula: Net = Weight - Cut, Amount = Net * Rate
function saleCalamo(input) {
    const row = input.closest('tr');
    const wt = parseFloat(row.querySelector('.sale-wt').value) || 0;
    const kate = parseFloat(row.querySelector('.sale-kate').value) || 0;
    const rate = parseFloat(row.querySelector('.sale-rate').value) || 0;
    
    // Formula: wt2 = wt - kate (Net = Weight - Cut)
    const wt2 = Math.max(0, wt - kate); // Ensure non-negative
    row.querySelector('.sale-wt2').value = formatNumber(wt2);
    
    // Formula: amount = wt2 * rate (Amount = Net * Rate)
    const amount = wt2 * rate;
    row.querySelector('.sale-amount').value = formatNumber(amount);
    
    // Update grand total
    calculateSaleTotal();
}

// Handle item name selection
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('sale-itemname')) {
        const row = e.target.closest('tr');
        const itemName = e.target.value;
        const option = document.querySelector('#saleItemList option[value="' + itemName + '"]');
        if (option) {
            row.querySelector('.sale-item-id').value = option.dataset.id;
        } else {
            row.querySelector('.sale-item-id').value = '';
        }
    }
});

// Add new row
const addSaleRowEl = document.getElementById('addSaleRow');
if (addSaleRowEl) {
    addSaleRowEl.addEventListener('click', function() {
        const tbody = document.getElementById('saleItemsBody');
        if (tbody) {
            const firstRow = tbody.querySelector('tr');
            if (firstRow) {
                const newRow = firstRow.cloneNode(true);
                
                // Clear values
                newRow.querySelectorAll('input').forEach(input => {
                    if (input.type !== 'hidden') input.value = '';
                });
                newRow.querySelector('.sale-item-id').value = '';
                newRow.querySelector('.remove-sale-row').disabled = false;
                
                // Insert new row at the top instead of bottom
                tbody.insertBefore(newRow, firstRow);
                updateSaleRemoveButtons();
            }
        }
    });
}

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

// Check stock when wt2 changes - show warning but allow
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('sale-wt2')) {
        const row = e.target.closest('tr');
        const wt2 = parseFloat(e.target.value) || 0;
        const itemNameInput = row.querySelector('.sale-itemname');
        const itemName = itemNameInput.value;
        const option = document.querySelector('#saleItemList option[value="' + itemName + '"]');
        const stock = option ? parseFloat(option.dataset.stock) || 0 : 0;
        
        // Show warning if stock is low but don't block
        if (wt2 > stock && wt2 > 0 && itemName) {
            const shortage = wt2 - stock;
            const warningMsg = itemName + ' - <?php echo t('available_stock'); ?>: ' + stock + ', <?php echo t('required_quantity'); ?>: ' + wt2 + ', <?php echo t('stock_shortage'); ?>: ' + shortage;
            showNotification('<?php echo t('stock_warning'); ?>: ' + warningMsg, 'error', 5000);
        }
    }
});

function calculateSaleTotal() {
    let total = 0;
    document.querySelectorAll('.sale-amount').forEach(function(input) {
        total += parseFloat(String(input.value).replace(/,/g, '')) || 0;
    });
    
    const totalAmountEl = document.getElementById('sale_total_amount');
    if (totalAmountEl) {
        totalAmountEl.value = formatNumber(total);
    }
    
    const discountEl = document.getElementById('sale_discount');
    const discount = discountEl ? parseFloat(String(discountEl.value).replace(/,/g, '')) || 0 : 0;
    const netAmount = total - discount;
    
    const netAmountEl = document.getElementById('sale_net_amount');
    if (netAmountEl) {
        netAmountEl.value = formatNumber(netAmount);
    }
    
    const paidAmountEl = document.getElementById('sale_paid_amount');
    const paid = paidAmountEl ? parseFloat(String(paidAmountEl.value).replace(/,/g, '')) || 0 : 0;
    const balance = netAmount - paid;
    
    const balanceAmountEl = document.getElementById('sale_balance_amount');
    if (balanceAmountEl) {
        balanceAmountEl.value = formatNumber(balance);
    }
}

// Calculate totals when discount or paid amount changes
const saleDiscountEl = document.getElementById('sale_discount');
if (saleDiscountEl) {
    saleDiscountEl.addEventListener('input', function() {
        calculateSaleTotal();
        // Update paid amount if cash on sale is checked
        const cashOnSaleCheckbox = document.getElementById('sale_cash_on_sale');
        if (cashOnSaleCheckbox && cashOnSaleCheckbox.checked) {
            setTimeout(function() {
                const netAmount = parseFloat(String(document.getElementById('sale_net_amount').value).replace(/,/g, '')) || 0;
                const paidAmountEl = document.getElementById('sale_paid_amount');
                if (paidAmountEl) {
                    paidAmountEl.value = formatNumber(netAmount);
                }
                calculateSaleTotal();
            }, 100);
        }
    });
}

const salePaidAmountEl = document.getElementById('sale_paid_amount');
if (salePaidAmountEl) {
    salePaidAmountEl.addEventListener('input', calculateSaleTotal);
}

// Customer data for auto-filling phone
const customers = <?php echo json_encode(array_map(function($c) { return ['id' => $c['id'], 'phone' => $c['phone'] ?? '', 'mobile' => $c['mobile'] ?? '']; }, $customers)); ?>;

// Handle customer selection - auto-fill phone and auto-set paid amount for cash sale
const saleAccountIdEl = document.getElementById('sale_account_id');
if (saleAccountIdEl) {
    saleAccountIdEl.addEventListener('change', function() {
        const selectedValue = this.value;
        const cashAccountId = <?php echo $cashAccountId ?? 0; ?>;
        
        // Auto-fill phone from customer data
        if (selectedValue && selectedValue != cashAccountId) {
            const customer = customers.find(function(c) { return c.id == selectedValue; });
            if (customer) {
                const phone = customer.phone || customer.mobile || '';
                const phoneEl = document.getElementById('sale_phone');
                if (phoneEl) phoneEl.value = phone;
            }
        } else {
            const phoneEl = document.getElementById('sale_phone');
            if (phoneEl) phoneEl.value = '';
        }
        
        // Auto-set paid amount for cash sale
        if (selectedValue == cashAccountId && cashAccountId > 0) {
            setTimeout(function() {
                calculateSaleTotal(); // Calculate totals first
                const netAmount = parseFloat(String(document.getElementById('sale_net_amount').value).replace(/,/g, '')) || 0;
                const paidAmountEl = document.getElementById('sale_paid_amount');
                if (paidAmountEl) {
                    paidAmountEl.value = formatNumber(netAmount);
                }
                calculateSaleTotal(); // Recalculate balance
            }, 100);
        } else {
            // Reset paid amount for credit sale
            const paidAmountEl = document.getElementById('sale_paid_amount');
            if (paidAmountEl) {
                paidAmountEl.value = '0';
            }
            calculateSaleTotal();
        }
    });
}

// Handle cash on sale checkbox
const saleCashOnSaleEl = document.getElementById('sale_cash_on_sale');
if (saleCashOnSaleEl) {
    saleCashOnSaleEl.addEventListener('change', function() {
        if (this.checked) {
            // Set paid amount equal to net amount
            const netAmountEl = document.getElementById('sale_net_amount');
            const paidAmountEl = document.getElementById('sale_paid_amount');
            if (netAmountEl && paidAmountEl) {
                const netAmount = parseFloat(netAmountEl.value) || 0;
                paidAmountEl.value = formatNumber(netAmount);
            }
            calculateSaleTotal();
        } else {
            // Clear paid amount if unchecked
            const paidAmountEl = document.getElementById('sale_paid_amount');
            if (paidAmountEl) {
                paidAmountEl.value = '';
            }
            calculateSaleTotal();
        }
    });
}

// Save new sale - make it globally accessible
window.saveNewSale = function() {
    const form = document.getElementById('newSaleForm');
    if (!form) return;
    
    const formData = new FormData(form);
    const messageDiv = document.getElementById('saleFormMessage');
    
    // Clear previous messages in modal (but notifications will show in top-right)
    if (messageDiv) {
        messageDiv.innerHTML = '';
    }
    
    // Validate required fields
    if (!formData.get('account_id')) {
        showNotification('<?php echo t('please_select_customer'); ?>', 'error', 5000);
        return;
    }
    
    // Validate items
    const itemIds = formData.getAll('item_id[]');
    const wt2s = formData.getAll('wt2[]');
    const rates = formData.getAll('rate[]');
    
    let hasValidItem = false;
    for (let i = 0; i < itemIds.length; i++) {
        if (itemIds[i] && wt2s[i] && rates[i]) {
            hasValidItem = true;
            break;
        }
    }
    
    if (!hasValidItem) {
        showNotification('<?php echo t('please_add_item'); ?>', 'error', 5000);
        return;
    }
    
    // Get save button - find it by selector since event.target is not available
    const saveBtn = document.querySelector('#newSaleModal .btn-primary[onclick*="saveNewSale"]');
    const originalText = saveBtn ? saveBtn.innerHTML : '';
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo t('save'); ?>...';
    }
    
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
            
            // Show warning if there are stock warnings - in top-right notification only
            if (data.warnings && data.warnings.length > 0) {
                let warningMsg = '<?php echo t('stock_warning'); ?>: ';
                data.warnings.forEach(function(warning, index) {
                    if (index > 0) warningMsg += ', ';
                    warningMsg += warning.item_name + ' (<?php echo t('available_stock'); ?>: ' + warning.available_stock + 
                        ', <?php echo t('required_quantity'); ?>: ' + warning.required_quantity + 
                        ', <?php echo t('stock_shortage'); ?>: ' + warning.shortage + ')';
                });
                // Show error notification for stock warnings (red/danger style)
                showNotification(data.message + ' - ' + warningMsg, 'error', 5000);
            } else {
                // Show success notification
                showNotification(data.message, 'success', 3000);
            }
            
            // Reload after 2 seconds to show notification
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            // Show error in top-right notification only
            showNotification(data.message, 'error', 5000);
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            }
        }
    })
    .catch(error => {
        // Show error in top-right notification only
        showNotification('<?php echo t('error_adding_sale'); ?>', 'error', 5000);
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    });
}

// Reset form on modal close
const newSaleModalEl = document.getElementById('newSaleModal');
if (newSaleModalEl) {
    newSaleModalEl.addEventListener('hidden.bs.modal', function() {
        const newSaleFormEl = document.getElementById('newSaleForm');
        if (newSaleFormEl) {
            newSaleFormEl.reset();
        }
        const saleFormMessageEl = document.getElementById('saleFormMessage');
        if (saleFormMessageEl) {
            saleFormMessageEl.innerHTML = '';
        }
        const tbody = document.getElementById('saleItemsBody');
        if (tbody) {
            while (tbody.children.length > 1) {
                tbody.removeChild(tbody.lastChild);
            }
            tbody.querySelectorAll('input').forEach(input => {
                if (input.type !== 'hidden') input.value = '';
            });
            const itemId = tbody.querySelector('.sale-item-id');
            if (itemId) itemId.value = '';
        }
        const saleDateEl = document.getElementById('sale_date');
        if (saleDateEl) {
            saleDateEl.value = '<?php echo date('Y-m-d'); ?>';
        }
        calculateSaleTotal();
        updateSaleRemoveButtons();
    });
}

// Initialize - only if elements exist
const saleItemsBodyEl = document.getElementById('saleItemsBody');
if (saleItemsBodyEl) {
    updateSaleRemoveButtons();
    calculateSaleTotal();
}

// Delete sale functionality - use event delegation
document.addEventListener('click', function(e) {
    if (e.target.closest('.delete-sale-btn')) {
        const btn = e.target.closest('.delete-sale-btn');
        const saleId = btn.getAttribute('data-sale-id');
        const saleNo = btn.getAttribute('data-sale-no');
        
        if (confirm('<?php echo t('are_you_sure_delete'); ?> Sale "' + saleNo + '"?')) {
            // Show loading
            btn.disabled = true;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            fetch('<?php echo BASE_URL; ?>sales/delete-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + saleId
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
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            })
            .catch(error => {
                showNotification('<?php echo t('error_deleting_sale'); ?>', 'error');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        }
    }
});
}); // End DOMContentLoaded

// Save new sale - defined outside DOMContentLoaded to be accessible globally
window.saveNewSale = function() {
    const form = document.getElementById('newSaleForm');
    if (!form) return;
    
    const formData = new FormData(form);
    const messageDiv = document.getElementById('saleFormMessage');
    
    // Clear previous messages in modal (but notifications will show in top-right)
    if (messageDiv) {
        messageDiv.innerHTML = '';
    }
    
    // Validate required fields
    if (!formData.get('account_id')) {
        showNotification('<?php echo t('please_select_customer'); ?>', 'error', 5000);
        return;
    }
    
    // Validate items
    const itemIds = formData.getAll('item_id[]');
    const wt2s = formData.getAll('wt2[]');
    const rates = formData.getAll('rate[]');
    
    let hasValidItem = false;
    for (let i = 0; i < itemIds.length; i++) {
        if (itemIds[i] && wt2s[i] && rates[i]) {
            hasValidItem = true;
            break;
        }
    }
    
    if (!hasValidItem) {
        showNotification('<?php echo t('please_add_item'); ?>', 'error', 5000);
        return;
    }
    
    // Get save button - find it by selector since event.target is not available
    const saveBtn = document.querySelector('#newSaleModal .btn-primary[onclick*="saveNewSale"]');
    const originalText = saveBtn ? saveBtn.innerHTML : '';
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo t('save'); ?>...';
    }
    
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
            
            // Show warning if there are stock warnings - in top-right notification only
            if (data.warnings && data.warnings.length > 0) {
                let warningMsg = '<?php echo t('stock_warning'); ?>: ';
                data.warnings.forEach(function(warning, index) {
                    if (index > 0) warningMsg += ', ';
                    warningMsg += warning.item_name + ' (<?php echo t('available_stock'); ?>: ' + warning.available_stock + 
                        ', <?php echo t('required_quantity'); ?>: ' + warning.required_quantity + 
                        ', <?php echo t('stock_shortage'); ?>: ' + warning.shortage + ')';
                });
                // Show error notification for stock warnings (red/danger style)
                showNotification(data.message + ' - ' + warningMsg, 'error', 5000);
            } else {
                // Show success notification
                showNotification(data.message, 'success', 3000);
            }
            
            // Reload after 2 seconds to show notification
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            // Show error in top-right notification only
            showNotification(data.message, 'error', 5000);
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            }
        }
    })
    .catch(error => {
        // Show error in top-right notification only
        showNotification('<?php echo t('error_adding_sale'); ?>', 'error', 5000);
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    });
};

// WhatsApp share functionality
document.addEventListener('click', function(e) {
    if (e.target.closest('.whatsapp-share-btn')) {
        const btn = e.target.closest('.whatsapp-share-btn');
        const saleId = btn.getAttribute('data-sale-id');
        const saleNo = btn.getAttribute('data-sale-no');
        const mobile = btn.getAttribute('data-mobile') || '';
        const phone = btn.getAttribute('data-phone') || '';
        const phoneNumber = mobile || phone;
        
        document.getElementById('whatsapp_sale_id').value = saleId;
        document.getElementById('whatsapp_sale_no').textContent = saleNo;
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('whatsappShareModal'));
        modal.show();
        
        // Set phone number if available (after modal is shown)
        setTimeout(() => {
            const phoneInput = document.getElementById('whatsapp_phone');
            if (phoneInput) {
                // Remove any existing mask
                if (phoneInput.inputmask) {
                    phoneInput.inputmask.remove();
                }
                // Set phone number if available, otherwise clear
                if (phoneNumber) {
                    phoneInput.value = phoneNumber;
                } else {
                    phoneInput.value = '';
                }
                phoneInput.focus();
            }
        }, 100);
    }
});

// Handle WhatsApp share button in modal using event delegation
document.addEventListener('click', function(e) {
    if (e.target.closest('#whatsappShareBtn')) {
        e.preventDefault();
        e.stopPropagation();
        
        const phoneInput = document.getElementById('whatsapp_phone');
        if (!phoneInput) {
            alert('<?php echo t('please_enter_phone_number'); ?>');
            return;
        }
        
        // Get phone number (remove any non-digit characters except +)
        let phoneNumber = phoneInput.value.trim().replace(/[^0-9+]/g, '');
        
        const saleId = document.getElementById('whatsapp_sale_id').value;
        
        if (!phoneNumber || phoneNumber.length < 10) {
            alert('<?php echo t('please_enter_phone_number'); ?>');
            return;
        }
        
        // Format phone number: ensure it starts with +92
        let cleanPhone = phoneNumber;
        if (cleanPhone.startsWith('0')) {
            // Local format: 03001234567 -> +923001234567
            cleanPhone = '+92' + cleanPhone.substring(1);
        } else if (cleanPhone.startsWith('92') && !cleanPhone.startsWith('+92')) {
            // 92XXXXXXXXXX -> +92XXXXXXXXXX
            cleanPhone = '+' + cleanPhone;
        } else if (!cleanPhone.startsWith('+')) {
            // If no country code, assume Pakistan
            cleanPhone = '+92' + cleanPhone;
        }
        
        // Validate phone number (should be 13 characters: +92XXXXXXXXXX)
        if (cleanPhone.length < 13 || !cleanPhone.startsWith('+92')) {
            alert('<?php echo t('invalid_phone_number'); ?>');
            return;
        }
        
        // Disable button during fetch
        const btn = e.target.closest('#whatsappShareBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo t('sending'); ?>...';
        
        // Fetch invoice details
        fetch('<?php echo BASE_URL; ?>sales/whatsapp-details-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + encodeURIComponent(saleId)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Open WhatsApp with message (remove + from phone number for wa.me)
                const whatsappUrl = 'https://wa.me/' + cleanPhone.substring(1) + '?text=' + encodeURIComponent(data.message);
                window.open(whatsappUrl, '_blank');
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('whatsappShareModal'));
                if (modal) {
                    modal.hide();
                }
                // Reset button
                btn.disabled = false;
                btn.innerHTML = originalText;
            } else {
                alert(data.message || '<?php echo t('error_fetching_invoice'); ?>');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('<?php echo t('error_fetching_invoice'); ?>');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
});
</script>

<!-- WhatsApp Share Modal -->
<div class="modal fade" id="whatsappShareModal" tabindex="-1" aria-labelledby="whatsappShareModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="whatsappShareModalLabel">
                    <i class="fab fa-whatsapp text-success"></i> <?php echo t('share_via_whatsapp'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><?php echo t('invoice'); ?>: <strong id="whatsapp_sale_no"></strong></p>
                <div class="mb-3">
                    <label for="whatsapp_phone" class="form-label"><?php echo t('phone_number'); ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="whatsapp_phone" placeholder="<?php echo t('enter_phone_number'); ?>" required>
                    <small class="form-text text-muted"><?php echo t('format'); ?>: +92-300-0000000</small>
                </div>
                <input type="hidden" id="whatsapp_sale_id" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                <button type="button" class="btn btn-success" id="whatsappShareBtn">
                    <i class="fab fa-whatsapp"></i> <?php echo t('send'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

