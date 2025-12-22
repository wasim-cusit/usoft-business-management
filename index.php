<?php
require_once 'config/config.php';
requireLogin();

$pageTitle = 'dashboard';

// Get date range
$dateFrom = $_GET['date_from'] ?? date('Y-m-d');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Get statistics
try {
    $db = getDB();
    
    // Today's Sales
    $stmt = $db->prepare("SELECT 
        COALESCE(SUM(net_amount), 0) as total_sales,
        COALESCE(SUM(paid_amount), 0) as credit_sales,
        COALESCE(SUM(balance_amount), 0) as debit_amount
        FROM sales WHERE sale_date BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateTo]);
    $salesStats = $stmt->fetch();
    
    // Today's Purchases
    $stmt = $db->prepare("SELECT 
        COALESCE(SUM(net_amount), 0) as total_purchases,
        COALESCE(SUM(paid_amount), 0) as credit_purchases,
        COALESCE(SUM(balance_amount), 0) as debit_purchases
        FROM purchases WHERE purchase_date BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateTo]);
    $purchaseStats = $stmt->fetch();
    
    // Total Accounts
    $stmt = $db->query("SELECT COUNT(*) as total FROM accounts WHERE status = 'active'");
    $totalAccounts = $stmt->fetch()['total'];
    
    // Total Items
    $stmt = $db->query("SELECT COUNT(*) as total FROM items WHERE status = 'active'");
    $totalItems = $stmt->fetch()['total'];
    
    // Low Stock Items
    $stmt = $db->query("SELECT COUNT(*) as total FROM items WHERE current_stock <= min_stock AND status = 'active'");
    $lowStock = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    $salesStats = ['total_sales' => 0, 'credit_sales' => 0, 'debit_amount' => 0];
    $purchaseStats = ['total_purchases' => 0, 'credit_purchases' => 0, 'debit_purchases' => 0];
    $totalAccounts = 0;
    $totalItems = 0;
    $lowStock = 0;
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-home"></i> ڈیش بورڈ</h1>
        <div class="d-flex gap-2">
            <input type="date" class="form-control" id="dateFrom" value="<?php echo $dateFrom; ?>" style="width: 180px;">
            <input type="date" class="form-control" id="dateTo" value="<?php echo $dateTo; ?>" style="width: 180px;">
            <button class="btn btn-primary" onclick="loadDashboard()">
                <i class="fas fa-check"></i> چیک کریں
            </button>
        </div>
    </div>
</div>

<!-- Quick Access Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="text-muted mb-1">نیا کھاتہ</h6>
                    <h4 class="mb-0">پارٹی شامل کریں</h4>
                </div>
                <div class="icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <a href="<?php echo BASE_URL; ?>accounts/create.php" class="btn btn-primary btn-sm w-100">
                <i class="fas fa-plus"></i> <?php echo t('add_new'); ?>
            </a>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="text-muted mb-1"><?php echo t('create_item'); ?></h6>
                    <h4 class="mb-0"><?php echo t('add_new'); ?> <?php echo t('items'); ?></h4>
                </div>
                <div class="icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                    <i class="fas fa-box"></i>
                </div>
            </div>
            <a href="<?php echo BASE_URL; ?>items/create.php" class="btn btn-success btn-sm w-100">
                <i class="fas fa-plus"></i> <?php echo t('add_new'); ?>
            </a>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="text-muted mb-1"><?php echo t('purchases'); ?></h6>
                    <h4 class="mb-0"><?php echo t('add_purchase'); ?></h4>
                </div>
                <div class="icon" style="background: linear-gradient(135deg, #3494E6 0%, #EC6EAD 100%);">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
            <a href="<?php echo BASE_URL; ?>purchases/create.php" class="btn btn-info btn-sm w-100 text-white">
                <i class="fas fa-plus"></i> <?php echo t('add_new'); ?>
            </a>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="text-muted mb-1"><?php echo t('sales'); ?></h6>
                    <h4 class="mb-0"><?php echo t('add_sale'); ?></h4>
                </div>
                <div class="icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="fas fa-cash-register"></i>
                </div>
            </div>
            <a href="<?php echo BASE_URL; ?>sales/create.php" class="btn btn-warning btn-sm w-100">
                <i class="fas fa-plus"></i> <?php echo t('add_new'); ?>
            </a>
        </div>
    </div>
</div>

<!-- Statistics Row -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2">کریڈٹ سیل</h6>
                    <h3 class="text-primary mb-0"><?php echo formatCurrency($salesStats['credit_sales']); ?></h3>
                </div>
                <div class="icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2"><?php echo t('net_amount'); ?> <?php echo t('sales'); ?></h6>
                    <h3 class="text-success mb-0"><?php echo formatCurrency($salesStats['total_sales']); ?></h3>
                </div>
                <div class="icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                    <i class="fas fa-chart-bar"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2">ڈیبٹ رقم</h6>
                    <h3 class="text-danger mb-0"><?php echo formatCurrency($salesStats['debit_amount']); ?></h3>
                </div>
                <div class="icon" style="background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);">
                    <i class="fas fa-arrow-down"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2">کریڈٹ رقم</h6>
                    <h3 class="text-info mb-0"><?php echo formatCurrency($purchaseStats['credit_purchases']); ?></h3>
                </div>
                <div class="icon" style="background: linear-gradient(135deg, #3494E6 0%, #EC6EAD 100%);">
                    <i class="fas fa-arrow-up"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Stats -->
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> سسٹم کا جائزہ</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-3 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-users text-primary"></i> کل کھاتے</span>
                        <strong class="badge bg-primary"><?php echo $totalAccounts; ?></strong>
                    </li>
                    <li class="mb-3 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-box text-success"></i> کل جنس</span>
                        <strong class="badge bg-success"><?php echo $totalItems; ?></strong>
                    </li>
                    <li class="mb-3 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-exclamation-triangle text-warning"></i> کم سٹاک</span>
                        <strong class="badge bg-warning"><?php echo $lowStock; ?></strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-link"></i> فوری لنکس</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <a href="<?php echo BASE_URL; ?>reports/cash-book.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-book"></i> کیش بک
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="<?php echo BASE_URL; ?>reports/balance-sheet.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-file-alt"></i> بیلنس شیٹ
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="<?php echo BASE_URL; ?>reports/party-ledger.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-file-invoice"></i> پارٹی لیجر
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="<?php echo BASE_URL; ?>reports/stock-detail.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-warehouse"></i> سٹاک کھاتہ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadDashboard() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    window.location.href = '<?php echo BASE_URL; ?>index.php?date_from=' + dateFrom + '&date_to=' + dateTo;
}
</script>

<?php include 'includes/footer.php'; ?>
