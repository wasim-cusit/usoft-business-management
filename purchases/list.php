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
    $stmt = $db->prepare("SELECT COUNT(DISTINCT p.id) as total FROM purchases p LEFT JOIN accounts a ON p.account_id = a.id $where");
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get purchases first
    $paramsForQuery = $params;
    $paramsForQuery[] = $limit;
    $paramsForQuery[] = $offset;
    $stmt = $db->prepare("SELECT p.*, a.account_name, a.account_name_urdu, a.mobile, a.phone as account_phone
                         FROM purchases p 
                         LEFT JOIN accounts a ON p.account_id = a.id 
                         $where 
                         ORDER BY p.id DESC LIMIT ? OFFSET ?");
    $stmt->execute($paramsForQuery);
    $purchases = $stmt->fetchAll();
    
    // Get item totals for each purchase
    foreach ($purchases as &$purchase) {
        $stmt = $db->prepare("SELECT 
                             COALESCE(SUM(pi.qty + pi.bag), 0) as total_qty,
                             COALESCE(SUM(pi.wt), 0) as total_weight,
                             COALESCE(SUM(pi.amount), 0) as total_amount
                             FROM purchase_items pi 
                             WHERE pi.purchase_id = ?");
        $stmt->execute([$purchase['id']]);
        $totals = $stmt->fetch();
        $purchase['total_qty'] = floatval($totals['total_qty'] ?? 0);
        $purchase['total_weight'] = floatval($totals['total_weight'] ?? 0);
        $purchase['total_amount'] = floatval($totals['total_amount'] ?? 0);
    }
    unset($purchase);
    
    // Calculate totals
    $stmt = $db->prepare("SELECT 
                            COALESCE(SUM(p.total_amount), 0) as total_amount,
                            COALESCE(SUM(p.discount), 0) as total_discount,
                            COALESCE(SUM(p.net_amount), 0) as total_net_amount,
                            COALESCE(SUM(p.paid_amount), 0) as total_paid_amount,
                            COALESCE(SUM(p.balance_amount), 0) as total_balance_amount
                         FROM purchases p 
                         LEFT JOIN accounts a ON p.account_id = a.id 
                         $where");
    $stmt->execute($params);
    $totals = $stmt->fetch();
    $totalAmount = $totals['total_amount'] ?? 0;
    $totalDiscount = $totals['total_discount'] ?? 0;
    $totalNetAmount = $totals['total_net_amount'] ?? 0;
    $totalPaidAmount = $totals['total_paid_amount'] ?? 0;
    $totalBalanceAmount = $totals['total_balance_amount'] ?? 0;
    
} catch (PDOException $e) {
    $purchases = [];
    $totalPages = 0;
    $totalRecords = 0;
    $totalAmount = 0;
    $totalDiscount = 0;
    $totalNetAmount = 0;
    $totalPaidAmount = 0;
    $totalBalanceAmount = 0;
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
/* Ensure footer displays in one row */
.table tfoot tr {
    display: table-row !important;
}
.table tfoot td {
    white-space: nowrap !important;
    vertical-align: middle !important;
}
</style>

<div class="row">
    <div class="col-md-12">
        <div style="background: #f5f5f5; padding: 10px 15px; margin-bottom: 10px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <h5 style="margin: 0; font-size: 16px; font-weight: 600; color: #333;">
                <i class="fas fa-shopping-cart"></i> <?php echo t('all_purchases'); ?>
            </h5>
            <a href="<?php echo BASE_URL; ?>purchases/create.php" class="btn btn-primary btn-sm" style="padding: 8px 16px; font-size: 14px; border-radius: 6px;">
                <i class="fas fa-plus"></i> <?php echo t('add_purchase'); ?>
            </a>
        </div>
        
        <div class="card">
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-bordered table-hover mb-0" style="margin-bottom: 0;">
                        <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 10;">
                            <tr>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo t('inv_number'); ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo t('date'); ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo getLang() == 'ur' ? t('name') . ' Name' : 'Name'; ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo t('location'); ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo t('details'); ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo t('phone'); ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo t('bilti'); ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo t('qty'); ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo t('weight'); ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo t('amount'); ?></th>
                                <th style="padding: 10px; font-size: 13px; font-weight: 600;"><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($purchases)): ?>
                                <tr>
                                    <td colspan="11" class="text-center" style="padding: 20px;"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($purchases as $purchase): 
                                    $qty = floatval($purchase['total_qty'] ?? 0);
                                    $weight = floatval($purchase['total_weight'] ?? 0);
                                    $amount = floatval($purchase['total_amount'] ?? 0);
                                    $accountName = displayAccountNameFull($purchase);
                                ?>
                                    <tr>
                                        <td style="padding: 8px 10px; font-size: 13px;"><?php echo htmlspecialchars($purchase['purchase_no']); ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px;"><?php echo date('Y-m-d', strtotime($purchase['purchase_date'])); ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px;"><?php echo htmlspecialchars($accountName); ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px;"><?php echo htmlspecialchars($purchase['location'] ?? ''); ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px;"><?php echo htmlspecialchars($purchase['details'] ?? ''); ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px;"><?php echo htmlspecialchars($purchase['phone'] ?? $purchase['account_phone'] ?? $purchase['mobile'] ?? ''); ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px;"><?php echo htmlspecialchars($purchase['bilti'] ?? ''); ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px; text-align: right;"><?php echo $qty > 0 ? formatNumber($qty) : ''; ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px; text-align: right;"><?php echo $weight > 0 ? formatNumber($weight) : ''; ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px; text-align: right;"><?php echo $amount > 0 ? formatNumber($amount) : ''; ?></td>
                                        <td style="padding: 8px 10px; font-size: 13px; text-align: center;">
                                            <a href="<?php echo BASE_URL; ?>purchases/edit.php?id=<?php echo $purchase['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo t('edit'); ?>" style="padding: 2px 6px; font-size: 12px; margin-right: 3px;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger delete-purchase-btn" data-purchase-id="<?php echo $purchase['id']; ?>" data-purchase-no="<?php echo htmlspecialchars($purchase['purchase_no']); ?>" title="<?php echo t('delete'); ?>" style="padding: 2px 6px; font-size: 12px; margin-right: 3px;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <a href="<?php echo BASE_URL; ?>purchases/print.php?id=<?php echo $purchase['id']; ?>" class="btn btn-sm btn-secondary" target="_blank" title="<?php echo t('print'); ?>" style="padding: 2px 6px; font-size: 12px;">
                                                <i class="fas fa-print"></i>
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
                    <!-- Top Section: Date, Number, Name Party, Location, Details, Phone, Bilti -->
                    <div class="row mb-2">
                        <div class="col-md-2 mb-2">
                            <label class="form-label"><?php echo t('date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" name="purchase_date" id="purchase_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-1 mb-2">
                            <label class="form-label"><?php echo t('inv_number'); ?></label>
                            <input type="text" class="form-control form-control-sm" name="purchase_no" id="purchase_no" placeholder="4">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label"><?php echo t('name_party'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="account_id" id="account_id" required>
                                <option value="">-- <?php echo t('select'); ?> --</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>">
                                        <?php echo displayAccountNameFull($supplier); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label"><?php echo t('location'); ?></label>
                            <input type="text" class="form-control form-control-sm" name="location" placeholder="<?php echo t('location'); ?>">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label"><?php echo t('details'); ?></label>
                            <input type="text" class="form-control form-control-sm" name="details" placeholder="<?php echo t('details'); ?>">
                        </div>
                        <div class="col-md-1 mb-2">
                            <label class="form-label"><?php echo t('phone'); ?></label>
                            <input type="text" class="form-control form-control-sm" name="phone" placeholder="<?php echo t('phone'); ?>">
                        </div>
                        <div class="col-md-1 mb-2">
                            <label class="form-label"><?php echo t('bilti'); ?></label>
                            <input type="text" class="form-control form-control-sm" name="bilti" placeholder="<?php echo t('bilti'); ?>">
                        </div>
                        <div class="col-md-1 mb-2">
                            <label class="form-label"><?php echo t('rate_type'); ?></label>
                            <div class="form-check form-check-sm">
                                <input class="form-check-input" type="radio" name="rate_type" id="modal_rate_kilo" value="kilo" checked>
                                <label class="form-check-label" for="modal_rate_kilo"><?php echo t('rate_kilo'); ?></label>
                            </div>
                            <div class="form-check form-check-sm">
                                <input class="form-check-input" type="radio" name="rate_type" id="modal_rate_mann" value="mann">
                                <label class="form-check-label" for="modal_rate_mann"><?php echo t('rate_mann'); ?></label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Item Entry Section -->
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><strong><?php echo t('items'); ?></strong></h6>
                        </div>
                        <!-- Item Entry Row -->
                        <div class="row mb-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label small"><?php echo t('item_name'); ?></label>
                                <select class="form-select form-select-sm item-select-modal" id="modalItemSelect">
                                    <option value="">-- <?php echo t('select'); ?> --</option>
                                    <?php 
                                    $stmt = $db->query("SELECT *, 
                                        CASE 
                                            WHEN description LIKE '%Purchase Rate Mann:%' 
                                            THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(description, 'Purchase Rate Mann: ', -1), '|', 1) AS DECIMAL(15,2))
                                            ELSE purchase_rate * 40 
                                        END as purchase_rate_mann
                                        FROM items WHERE status = 'active' ORDER BY item_name");
                                    $modalItems = $stmt->fetchAll();
                                    foreach ($modalItems as $item): 
                                        $purchaseRateMann = $item['purchase_rate_mann'] ?? ($item['purchase_rate'] * 40);
                                        if (isset($item['description']) && preg_match('/Purchase Rate Mann:\s*([0-9.]+)/', $item['description'], $matches)) {
                                            $purchaseRateMann = floatval($matches[1]);
                                        }
                                    ?>
                                        <option value="<?php echo $item['id']; ?>" data-rate-kilo="<?php echo $item['purchase_rate']; ?>" data-rate-mann="<?php echo $purchaseRateMann; ?>">
                                            <?php echo htmlspecialchars($item['item_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label small"><?php echo t('qty'); ?></label>
                                <input type="number" step="0.01" class="form-control form-control-sm" id="modalItemQty" placeholder="0">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label small"><?php echo t('bharti'); ?></label>
                                <input type="number" step="0.01" class="form-control form-control-sm" id="modalItemBag" placeholder="0">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label small"><?php echo t('weight'); ?></label>
                                <input type="number" step="0.01" class="form-control form-control-sm" id="modalItemWt" readonly>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label small"><?php echo t('cut'); ?></label>
                                <input type="number" step="0.01" class="form-control form-control-sm" id="modalItemKate" placeholder="0">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label small"><?php echo t('rate'); ?></label>
                                <input type="number" step="0.01" class="form-control form-control-sm" id="modalItemRate" placeholder="0">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label small"><?php echo t('amount'); ?></label>
                                <input type="text" class="form-control form-control-sm" id="modalItemAmount" readonly>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label small">&nbsp;</label>
                                <button type="button" class="btn btn-success btn-sm w-100" id="addModalItemBtn"><?php echo t('enter'); ?></button>
                            </div>
                        </div>
                        
                        <!-- Items Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="modalItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th><?php echo t('goods_account'); ?></th>
                                        <th><?php echo t('quantity'); ?></th>
                                        <th><?php echo t('weight'); ?></th>
                                        <th><?php echo t('cut'); ?></th>
                                        <th><?php echo t('rate'); ?></th>
                                        <th><?php echo t('amount'); ?></th>
                                        <th>D/E</th>
                                        <th><?php echo t('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="purchaseItemsBody">
                                    <!-- Items will be added here dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Summary and Expenses Section -->
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Left side empty for now -->
                        </div>
                        <div class="col-md-4">
                            <div class="card card-sm mb-2">
                                <div class="card-header bg-light py-1">
                                    <h6 class="mb-0 small"><?php echo t('expenses'); ?></h6>
                                </div>
                                <div class="card-body p-2">
                                    <div class="mb-1">
                                        <label class="form-label small"><?php echo t('rent'); ?></label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" name="rent" id="modal_rent" value="0" placeholder="0">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label small"><?php echo t('loading'); ?></label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" name="loading" id="modal_loading" value="0" placeholder="0">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label small"><?php echo t('labor'); ?></label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" name="labor" id="modal_labor" value="0" placeholder="0">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label small"><?php echo t('brokerage'); ?></label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" name="brokerage" id="modal_brokerage" value="0" placeholder="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card card-sm">
                                <div class="card-header bg-light py-1">
                                    <h6 class="mb-0 small"><?php echo t('summary'); ?></h6>
                                </div>
                                <div class="card-body p-2">
                                    <div class="mb-1">
                                        <label class="form-label small"><?php echo t('total_qty'); ?></label>
                                        <input type="text" class="form-control form-control-sm" id="modal_total_qty" readonly value="0.00">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label small"><?php echo t('total_weight'); ?></label>
                                        <input type="text" class="form-control form-control-sm" id="modal_total_weight" readonly value="0.00">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label small"><?php echo t('total_amount_label'); ?></label>
                                        <input type="text" class="form-control form-control-sm" id="modal_total_amount" readonly value="0.00">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label small"><?php echo t('total_exp'); ?></label>
                                        <input type="text" class="form-control form-control-sm" id="modal_total_expenses" readonly value="0.00">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label small"><strong><?php echo t('grand_total'); ?></strong></label>
                                        <input type="text" class="form-control form-control-sm" id="modal_grand_total" readonly value="0.00">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label small"><?php echo t('discount'); ?></label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" name="discount" id="modal_discount" value="0" placeholder="0">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label small"><?php echo t('paid_amount'); ?></label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" name="paid_amount" id="modal_paid_amount" value="0" placeholder="0">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label small"><?php echo t('balance_amount'); ?></label>
                                        <input type="text" class="form-control form-control-sm" id="modal_balance_amount" readonly value="0.00">
                                    </div>
                                </div>
                            </div>
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
// Calculate weight (qty + bag) for modal
document.getElementById('modalItemQty')?.addEventListener('input', calculateModalItemAmount);
document.getElementById('modalItemBag')?.addEventListener('input', calculateModalItemAmount);

function calculateModalItemAmount() {
    const qty = parseFloat(document.getElementById('modalItemQty')?.value) || 0;
    const bag = parseFloat(document.getElementById('modalItemBag')?.value) || 0;
    const wt = qty + bag;
    document.getElementById('modalItemWt').value = formatNumber(wt);
    
    const kate = parseFloat(document.getElementById('modalItemKate')?.value) || 0;
    const rate = parseFloat(document.getElementById('modalItemRate')?.value) || 0;
    const netWeight = wt - kate;
    const amount = netWeight * rate;
    document.getElementById('modalItemAmount').value = formatNumber(amount);
}

document.getElementById('modalItemWt')?.addEventListener('input', calculateModalItemAmount);
document.getElementById('modalItemKate')?.addEventListener('input', calculateModalItemAmount);
document.getElementById('modalItemRate')?.addEventListener('input', calculateModalItemAmount);

// Set rate when item selected in modal
document.getElementById('modalItemSelect')?.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const rateType = document.querySelector('input[name="rate_type"]:checked')?.value || 'kilo';
    const rate = rateType == 'mann' ? selectedOption.getAttribute('data-rate-mann') : selectedOption.getAttribute('data-rate-kilo');
    if (rate) {
        document.getElementById('modalItemRate').value = rate;
        calculateModalItemAmount();
    }
});

// Update rate when rate type changes in modal
document.querySelectorAll('input[name="rate_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const selectedOption = document.getElementById('modalItemSelect')?.options[document.getElementById('modalItemSelect')?.selectedIndex];
        if (selectedOption?.value) {
            const rateType = this.value;
            const rate = rateType == 'mann' ? selectedOption.getAttribute('data-rate-mann') : selectedOption.getAttribute('data-rate-kilo');
            if (rate) {
                document.getElementById('modalItemRate').value = rate;
                calculateModalItemAmount();
            }
        }
    });
});

// Add item to modal table
document.getElementById('addModalItemBtn')?.addEventListener('click', function() {
    const itemId = document.getElementById('modalItemSelect')?.value;
    const itemName = document.getElementById('modalItemSelect')?.options[document.getElementById('modalItemSelect')?.selectedIndex]?.text;
    const qty = parseFloat(document.getElementById('modalItemQty')?.value) || 0;
    const bag = parseFloat(document.getElementById('modalItemBag')?.value) || 0;
    const wt = parseFloat(document.getElementById('modalItemWt')?.value) || 0;
    const kate = parseFloat(document.getElementById('modalItemKate')?.value) || 0;
    const rate = parseFloat(document.getElementById('modalItemRate')?.value) || 0;
    const amount = parseFloat(document.getElementById('modalItemAmount')?.value.replace(/,/g, '')) || 0;
    const netWeight = wt - kate;
    
    if (!itemId || !wt || !rate) {
        alert('<?php echo t('please_enter_item_details'); ?>');
        return;
    }
    
    const tbody = document.getElementById('purchaseItemsBody');
    const newRow = document.createElement('tr');
    newRow.setAttribute('data-item-id', itemId);
    newRow.setAttribute('data-qty', qty);
    newRow.setAttribute('data-bag', bag);
    newRow.setAttribute('data-wt', wt);
    newRow.setAttribute('data-kate', kate);
    newRow.setAttribute('data-rate', rate);
    newRow.setAttribute('data-amount', amount);
    
    newRow.innerHTML = `
        <td>${itemName}</td>
        <td>${formatNumber(netWeight)}</td>
        <td>${formatNumber(wt)}</td>
        <td>${formatNumber(kate)}</td>
        <td>${formatNumber(rate)}</td>
        <td>${formatNumber(amount)}</td>
        <td>D</td>
        <td>
            <input type="hidden" name="item_id[]" value="${itemId}">
            <input type="hidden" name="qty[]" value="${qty}">
            <input type="hidden" name="bag[]" value="${bag}">
            <input type="hidden" name="wt[]" value="${wt}">
            <input type="hidden" name="kate[]" value="${kate}">
            <input type="hidden" name="rate[]" value="${rate}">
            <input type="hidden" name="amount[]" value="${amount}">
            <button type="button" class="btn btn-danger btn-sm remove-purchase-row"><i class="fas fa-times"></i></button>
        </td>
    `;
    tbody.appendChild(newRow);
    
    // Clear input fields
    document.getElementById('modalItemSelect').value = '';
    document.getElementById('modalItemQty').value = '';
    document.getElementById('modalItemBag').value = '';
    document.getElementById('modalItemWt').value = '';
    document.getElementById('modalItemKate').value = '';
    document.getElementById('modalItemRate').value = '';
    document.getElementById('modalItemAmount').value = '';
    
    calculateModalTotals();
});

// Remove row from modal table
document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-purchase-row')) {
        e.target.closest('tr').remove();
        calculateModalTotals();
    }
});

// Calculate modal totals
function calculateModalTotals() {
    const rows = document.querySelectorAll('#purchaseItemsBody tr');
    let totalQty = 0;
    let totalWeight = 0;
    let totalAmount = 0;
    
    rows.forEach(row => {
        const qty = parseFloat(row.getAttribute('data-qty')) || 0;
        const bag = parseFloat(row.getAttribute('data-bag')) || 0;
        const wt = parseFloat(row.getAttribute('data-wt')) || 0;
        const kate = parseFloat(row.getAttribute('data-kate')) || 0;
        const amount = parseFloat(row.getAttribute('data-amount')) || 0;
        
        const netWeight = wt - kate;
        totalQty += netWeight;
        totalWeight += wt;
        totalAmount += amount;
    });
    
    if (document.getElementById('modal_total_qty')) document.getElementById('modal_total_qty').value = formatNumber(totalQty);
    if (document.getElementById('modal_total_weight')) document.getElementById('modal_total_weight').value = formatNumber(totalWeight);
    if (document.getElementById('modal_total_amount')) document.getElementById('modal_total_amount').value = formatNumber(totalAmount);
    
    // Calculate expenses
    const rent = parseFloat(document.getElementById('modal_rent')?.value) || 0;
    const loading = parseFloat(document.getElementById('modal_loading')?.value) || 0;
    const labor = parseFloat(document.getElementById('modal_labor')?.value) || 0;
    const brokerage = parseFloat(document.getElementById('modal_brokerage')?.value) || 0;
    const totalExpenses = rent + loading + labor + brokerage;
    if (document.getElementById('modal_total_expenses')) document.getElementById('modal_total_expenses').value = formatNumber(totalExpenses);
    
    // Calculate grand total
    const discount = parseFloat(document.getElementById('modal_discount')?.value) || 0;
    const netAmount = totalAmount - discount;
    const grandTotal = netAmount + totalExpenses;
    if (document.getElementById('modal_grand_total')) document.getElementById('modal_grand_total').value = formatNumber(grandTotal);
    
    // Calculate balance
    const paid = parseFloat(document.getElementById('modal_paid_amount')?.value) || 0;
    const balance = grandTotal - paid;
    if (document.getElementById('modal_balance_amount')) document.getElementById('modal_balance_amount').value = formatNumber(balance);
}

// Update totals when expenses, discount, or paid amount changes
document.getElementById('modal_rent')?.addEventListener('input', calculateModalTotals);
document.getElementById('modal_loading')?.addEventListener('input', calculateModalTotals);
document.getElementById('modal_labor')?.addEventListener('input', calculateModalTotals);
document.getElementById('modal_brokerage')?.addEventListener('input', calculateModalTotals);
document.getElementById('modal_discount')?.addEventListener('input', calculateModalTotals);
document.getElementById('modal_paid_amount')?.addEventListener('input', calculateModalTotals);

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
    const wts = formData.getAll('wt[]');
    const rates = formData.getAll('rate[]');
    
    let hasValidItem = false;
    for (let i = 0; i < itemIds.length; i++) {
        if (itemIds[i] && wts[i] && rates[i]) {
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

// Delete purchase functionality - use event delegation
document.addEventListener('click', function(e) {
    if (e.target.closest('.delete-purchase-btn')) {
        const btn = e.target.closest('.delete-purchase-btn');
        const purchaseId = btn.getAttribute('data-purchase-id');
        const purchaseNo = btn.getAttribute('data-purchase-no');
        
        if (confirm('<?php echo t('are_you_sure_delete'); ?> Purchase "' + purchaseNo + '"?')) {
            // Show loading
            btn.disabled = true;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            fetch('<?php echo BASE_URL; ?>purchases/delete-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + purchaseId
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
                showNotification('<?php echo t('error_deleting_purchase'); ?>', 'error');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        }
    }
});

// WhatsApp share functionality for purchases
document.addEventListener('click', function(e) {
    if (e.target.closest('.whatsapp-share-purchase-btn')) {
        const btn = e.target.closest('.whatsapp-share-purchase-btn');
        const purchaseId = btn.getAttribute('data-purchase-id');
        const purchaseNo = btn.getAttribute('data-purchase-no');
        const mobile = btn.getAttribute('data-mobile') || '';
        const phone = btn.getAttribute('data-phone') || '';
        const phoneNumber = mobile || phone;
        
        document.getElementById('whatsapp_purchase_id').value = purchaseId;
        document.getElementById('whatsapp_purchase_no').textContent = purchaseNo;
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('whatsappPurchaseShareModal'));
        modal.show();
        
        // Set phone number if available (after modal is shown)
        setTimeout(() => {
            const phoneInput = document.getElementById('whatsapp_purchase_phone');
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

// Handle WhatsApp share button in modal for purchases using event delegation
document.addEventListener('click', function(e) {
    if (e.target.closest('#whatsappPurchaseShareBtn')) {
        e.preventDefault();
        e.stopPropagation();
        
        const phoneInput = document.getElementById('whatsapp_purchase_phone');
        if (!phoneInput) {
            alert('<?php echo t('please_enter_phone_number'); ?>');
            return;
        }
        
        // Get phone number (remove any non-digit characters except +)
        let phoneNumber = phoneInput.value.trim().replace(/[^0-9+]/g, '');
        
        const purchaseId = document.getElementById('whatsapp_purchase_id').value;
        
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
        const btn = e.target.closest('#whatsappPurchaseShareBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo t('sending'); ?>...';
        
        // Fetch invoice details
        fetch('<?php echo BASE_URL; ?>purchases/whatsapp-details-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + encodeURIComponent(purchaseId)
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
                const modal = bootstrap.Modal.getInstance(document.getElementById('whatsappPurchaseShareModal'));
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

<!-- WhatsApp Share Modal for Purchases -->
<div class="modal fade" id="whatsappPurchaseShareModal" tabindex="-1" aria-labelledby="whatsappPurchaseShareModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="whatsappPurchaseShareModalLabel">
                    <i class="fab fa-whatsapp text-success"></i> <?php echo t('share_via_whatsapp'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><?php echo t('invoice'); ?>: <strong id="whatsapp_purchase_no"></strong></p>
                <div class="mb-3">
                    <label for="whatsapp_purchase_phone" class="form-label"><?php echo t('phone_number'); ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="whatsapp_purchase_phone" placeholder="<?php echo t('enter_phone_number'); ?>" required>
                    <small class="form-text text-muted"><?php echo t('format'); ?>: +92-300-0000000</small>
                </div>
                <input type="hidden" id="whatsapp_purchase_id" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                <button type="button" class="btn btn-success" id="whatsappPurchaseShareBtn">
                    <i class="fab fa-whatsapp"></i> <?php echo t('send'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

