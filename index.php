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
    
    // Sales Statistics
    $stmt = $db->prepare("SELECT 
        COALESCE(SUM(net_amount), 0) as total_sales,
        COALESCE(SUM(paid_amount), 0) as credit_sales,
        COALESCE(SUM(balance_amount), 0) as debit_amount
        FROM sales WHERE sale_date BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateTo]);
    $salesStats = $stmt->fetch();
    
    // Purchases Statistics
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
    
    // Get daily sales data for last 7 days for chart
    $chartDateFrom = date('Y-m-d', strtotime('-6 days', strtotime($dateTo)));
    $stmt = $db->prepare("SELECT 
        sale_date as date,
        COALESCE(SUM(net_amount), 0) as amount
        FROM sales 
        WHERE sale_date BETWEEN ? AND ?
        GROUP BY sale_date
        ORDER BY sale_date");
    $stmt->execute([$chartDateFrom, $dateTo]);
    $dailySales = $stmt->fetchAll();
    
    // Get daily purchases data for last 7 days for chart
    $stmt = $db->prepare("SELECT 
        purchase_date as date,
        COALESCE(SUM(net_amount), 0) as amount
        FROM purchases 
        WHERE purchase_date BETWEEN ? AND ?
        GROUP BY purchase_date
        ORDER BY purchase_date");
    $stmt->execute([$chartDateFrom, $dateTo]);
    $dailyPurchases = $stmt->fetchAll();
    
    // Prepare chart data
    $chartLabels = [];
    $chartSalesData = [];
    $chartPurchasesData = [];
    
    // Generate all dates in range
    $currentDate = strtotime($chartDateFrom);
    $endDate = strtotime($dateTo);
    while ($currentDate <= $endDate) {
        $dateStr = date('Y-m-d', $currentDate);
        $chartLabels[] = date('d M', $currentDate);
        
        // Find sales for this date
        $salesAmount = 0;
        foreach ($dailySales as $sale) {
            if ($sale['date'] == $dateStr) {
                $salesAmount = floatval($sale['amount']);
                break;
            }
        }
        $chartSalesData[] = $salesAmount;
        
        // Find purchases for this date
        $purchaseAmount = 0;
        foreach ($dailyPurchases as $purchase) {
            if ($purchase['date'] == $dateStr) {
                $purchaseAmount = floatval($purchase['amount']);
                break;
            }
        }
        $chartPurchasesData[] = $purchaseAmount;
        
        $currentDate = strtotime('+1 day', $currentDate);
    }
    
} catch (PDOException $e) {
    $salesStats = ['total_sales' => 0, 'credit_sales' => 0, 'debit_amount' => 0];
    $purchaseStats = ['total_purchases' => 0, 'credit_purchases' => 0, 'debit_purchases' => 0];
    $totalAccounts = 0;
    $totalItems = 0;
    $lowStock = 0;
    $chartLabels = [];
    $chartSalesData = [];
    $chartPurchasesData = [];
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-home"></i> <?php echo t('dashboard'); ?></h1>
        <div class="d-flex gap-2">
            <input type="date" class="form-control" id="dateFrom" value="<?php echo $dateFrom; ?>" style="width: 180px;">
            <input type="date" class="form-control" id="dateTo" value="<?php echo $dateTo; ?>" style="width: 180px;">
            <button class="btn btn-primary" onclick="loadDashboard()">
                <i class="fas fa-check"></i> <?php echo t('check'); ?>
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
                    <h6 class="text-muted mb-1"><?php echo t('new_account'); ?></h6>
                    <h4 class="mb-0"><?php echo t('add_new'); ?> <?php echo t('party'); ?></h4>
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
                    <h6 class="text-muted mb-2"><?php echo t('credit'); ?> <?php echo t('sales'); ?></h6>
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
                    <h6 class="text-muted mb-2"><?php echo t('debit_amount'); ?></h6>
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
                    <h6 class="text-muted mb-2"><?php echo t('credit_amount'); ?></h6>
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
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> <?php echo t('system_overview'); ?></h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-3 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-users text-primary"></i> <?php echo t('all_accounts_label'); ?></span>
                        <strong class="badge bg-primary"><?php echo $totalAccounts; ?></strong>
                    </li>
                    <li class="mb-3 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-box text-success"></i> <?php echo t('all_items_label'); ?></span>
                        <strong class="badge bg-success"><?php echo $totalItems; ?></strong>
                    </li>
                    <li class="mb-3 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-exclamation-triangle text-warning"></i> <?php echo t('low_stock_label'); ?></span>
                        <strong class="badge bg-warning"><?php echo $lowStock; ?></strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-link"></i> <?php echo t('quick_links'); ?></h5>
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

<!-- Charts Section -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> <?php echo t('sales'); ?> & <?php echo t('purchases'); ?> <?php echo t('report'); ?></h5>
            </div>
            <div class="card-body">
                <canvas id="salesPurchasesChart" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function loadDashboard() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    window.location.href = '<?php echo BASE_URL; ?>index.php?date_from=' + dateFrom + '&date_to=' + dateTo;
}

// Initialize Chart
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesPurchasesChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: '<?php echo t('sales'); ?>',
                    data: <?php echo json_encode($chartSalesData); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: '<?php echo t('purchases'); ?>',
                    data: <?php echo json_encode($chartPurchasesData); ?>,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + '<?php echo CURRENCY_SYMBOL; ?>' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '<?php echo CURRENCY_SYMBOL; ?>' + value.toLocaleString('en-US');
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
