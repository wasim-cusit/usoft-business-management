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
    
    // Total Accounts (all time, not filtered by date)
    $stmt = $db->query("SELECT COUNT(*) as total FROM accounts WHERE status = 'active'");
    $totalAccounts = $stmt->fetch()['total'];
    
    // Total Items (all time, not filtered by date)
    $stmt = $db->query("SELECT COUNT(*) as total FROM items WHERE status = 'active'");
    $totalItems = $stmt->fetch()['total'];
    
    // Low Stock Items (all time, not filtered by date)
    $stmt = $db->query("SELECT COUNT(*) as total FROM items WHERE current_stock <= min_stock AND status = 'active'");
    $lowStock = $stmt->fetch()['total'];
    
    // Purchases Statistics (filtered by date range)
    $stmt = $db->prepare("SELECT 
        COALESCE(SUM(net_amount), 0) as total_purchases,
        COALESCE(SUM(paid_amount), 0) as credit_purchases,
        COALESCE(SUM(balance_amount), 0) as debit_purchases
        FROM purchases WHERE purchase_date BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateTo]);
    $purchaseStats = $stmt->fetch();
    
    // Additional filtered statistics
    // Total Sales Count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM sales WHERE sale_date BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateTo]);
    $totalSalesCount = $stmt->fetch()['total'];
    
    // Total Purchases Count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM purchases WHERE purchase_date BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateTo]);
    $totalPurchasesCount = $stmt->fetch()['total'];
    
    // Profit Calculation
    $totalProfit = $salesStats['total_sales'] - $purchaseStats['total_purchases'];
    
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
    
    // Get top 5 selling items
    $stmt = $db->prepare("SELECT 
        i.item_name,
        i.item_name_urdu,
        COALESCE(SUM(si.quantity), 0) as total_quantity,
        COALESCE(SUM(si.amount), 0) as total_amount
        FROM sale_items si
        INNER JOIN items i ON si.item_id = i.id
        INNER JOIN sales s ON si.sale_id = s.id
        WHERE s.sale_date BETWEEN ? AND ?
        GROUP BY si.item_id, i.item_name, i.item_name_urdu
        ORDER BY total_amount DESC
        LIMIT 5");
    $stmt->execute([$dateFrom, $dateTo]);
    $topItems = $stmt->fetchAll();
    
    // Get top 5 customers
    $stmt = $db->prepare("SELECT 
        a.account_name,
        a.account_name_urdu,
        COALESCE(SUM(s.net_amount), 0) as total_amount
        FROM sales s
        INNER JOIN accounts a ON s.account_id = a.id
        WHERE s.sale_date BETWEEN ? AND ?
        GROUP BY s.account_id, a.account_name, a.account_name_urdu
        ORDER BY total_amount DESC
        LIMIT 5");
    $stmt->execute([$dateFrom, $dateTo]);
    $topCustomers = $stmt->fetchAll();
    
    // Get profit data (Sales - Purchases)
    $profitData = [];
    $profitLabels = [];
    $profitAmounts = [];
    
    // Prepare chart data
    $chartLabels = [];
    $chartSalesData = [];
    $chartPurchasesData = [];
    $chartProfitData = [];
    
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
        
        // Calculate profit
        $profit = $salesAmount - $purchaseAmount;
        $chartProfitData[] = $profit;
        
        $currentDate = strtotime('+1 day', $currentDate);
    }
    
    // Prepare top items data for pie chart
    $topItemsLabels = [];
    $topItemsData = [];
    foreach ($topItems as $item) {
        $topItemsLabels[] = displayItemNameFull(['item_name' => $item['item_name'], 'item_name_urdu' => $item['item_name_urdu']]);
        $topItemsData[] = floatval($item['total_amount']);
    }
    
    // Prepare top customers data
    $topCustomersLabels = [];
    $topCustomersData = [];
    foreach ($topCustomers as $customer) {
        $topCustomersLabels[] = displayAccountNameFull(['account_name' => $customer['account_name'], 'account_name_urdu' => $customer['account_name_urdu']]);
        $topCustomersData[] = floatval($customer['total_amount']);
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
    $chartProfitData = [];
    $topItems = [];
    $topCustomers = [];
    $topItemsLabels = [];
    $topItemsData = [];
    $topCustomersLabels = [];
    $topCustomersData = [];
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h1><i class="fas fa-home"></i> <?php echo t('dashboard'); ?></h1>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0" style="white-space: nowrap; font-weight: 600;"><?php echo t('date_from'); ?>:</label>
                <input type="date" class="form-control" id="dateFrom" value="<?php echo $dateFrom; ?>" style="width: 160px;">
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0" style="white-space: nowrap; font-weight: 600;"><?php echo t('date_to'); ?>:</label>
                <input type="date" class="form-control" id="dateTo" value="<?php echo $dateTo; ?>" style="width: 160px;">
            </div>
            <button class="btn btn-primary" onclick="loadDashboard()">
                <i class="fas fa-filter"></i> <?php echo t('filter'); ?>
            </button>
            <?php if ($dateFrom != date('Y-m-d') || $dateTo != date('Y-m-d')): ?>
                <button class="btn btn-warning" onclick="clearFilter()" title="<?php echo t('clear_filter'); ?>">
                    <i class="fas fa-times-circle"></i> <?php echo t('clear_filter'); ?>
                </button>
            <?php endif; ?>
            <?php /* <button class="btn btn-secondary" onclick="resetFilter()">
                <i class="fas fa-redo"></i> <?php echo t('reset'); ?>
            </button> */ ?>
        </div>
    </div>
    <?php /* if ($dateFrom != date('Y-m-d') || $dateTo != date('Y-m-d')): ?>
        <div class="mt-3">
            <div class="alert alert-info mb-0 d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-info-circle"></i> 
                    <strong><?php echo t('showing_data_from'); ?>:</strong> 
                    <?php echo date('d M Y', strtotime($dateFrom)); ?> 
                    <strong><?php echo t('to'); ?></strong> 
                    <?php echo date('d M Y', strtotime($dateTo)); ?>
                </span>
                <button class="btn btn-sm btn-outline-light ms-3" onclick="clearFilter()">
                    <i class="fas fa-times"></i> <?php echo t('clear_filter'); ?>
                </button>
            </div>
        </div>
    <?php endif; */ ?>
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
<div class="row mb-4 equal-height">
    <div class="col-md-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-center h-100">
                <div class="flex-grow-1" style="min-width: 0;">
                    <h6 class="text-muted mb-2"><?php echo t('credit'); ?> <?php echo t('sales'); ?></h6>
                    <h3 class="text-primary mb-0" style="font-size: 18px; word-break: break-word;"><?php echo formatCurrency($salesStats['credit_sales']); ?></h3>
                </div>
                <div class="icon flex-shrink-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-center h-100">
                <div class="flex-grow-1" style="min-width: 0;">
                    <h6 class="text-muted mb-2"><?php echo t('net_amount'); ?> <?php echo t('sales'); ?></h6>
                    <h3 class="text-success mb-0" style="font-size: 18px; word-break: break-word;"><?php echo formatCurrency($salesStats['total_sales']); ?></h3>
                </div>
                <div class="icon flex-shrink-0" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                    <i class="fas fa-chart-bar"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-center h-100">
                <div class="flex-grow-1" style="min-width: 0;">
                    <h6 class="text-muted mb-2"><?php echo t('debit_amount'); ?></h6>
                    <h3 class="text-danger mb-0" style="font-size: 18px; word-break: break-word;"><?php echo formatCurrency($salesStats['debit_amount']); ?></h3>
                </div>
                <div class="icon flex-shrink-0" style="background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);">
                    <i class="fas fa-arrow-down"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-center h-100">
                <div class="flex-grow-1" style="min-width: 0;">
                    <h6 class="text-muted mb-2"><?php echo t('credit_amount'); ?></h6>
                    <h3 class="text-info mb-0" style="font-size: 18px; word-break: break-word;"><?php echo formatCurrency($purchaseStats['credit_purchases']); ?></h3>
                </div>
                <div class="icon flex-shrink-0" style="background: linear-gradient(135deg, #3494E6 0%, #EC6EAD 100%);">
                    <i class="fas fa-arrow-up"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Stats -->
<div class="row equal-height">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> <?php echo t('system_overview'); ?></h5>
            </div>
            <div class="card-body d-flex flex-column">
                <ul class="list-unstyled flex-grow-1">
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
            <div class="card-body d-flex flex-column">
                <div class="row flex-grow-1">
                    <div class="col-md-6 mb-2">
                        <a href="<?php echo BASE_URL; ?>reports/cash-book.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-book"></i> <?php echo t('cash_book'); ?>
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="<?php echo BASE_URL; ?>reports/balance-sheet.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-file-alt"></i> <?php echo t('balance_sheet'); ?>
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="<?php echo BASE_URL; ?>reports/party-ledger.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-file-invoice"></i> <?php echo t('party_ledger'); ?>
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="<?php echo BASE_URL; ?>reports/stock-detail.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-warehouse"></i> <?php echo t('stock_detail'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row mt-4 equal-height">
    <!-- Sales & Purchases Line Chart -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> <?php echo t('sales'); ?> & <?php echo t('purchases'); ?> <?php echo t('report'); ?></h5>
            </div>
            <div class="card-body d-flex flex-column">
                <div class="flex-grow-1" style="position: relative; height: 350px;">
                    <canvas id="salesPurchasesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Profit Chart -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-area"></i> <?php echo t('profit'); ?></h5>
            </div>
            <div class="card-body d-flex flex-column">
                <div class="flex-grow-1" style="position: relative; height: 350px;">
                    <canvas id="profitChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row equal-height">
    <!-- Top Selling Items Pie Chart -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> <?php echo t('top_items'); ?></h5>
            </div>
            <div class="card-body d-flex flex-column">
                <div class="flex-grow-1" style="position: relative; height: 350px;">
                    <canvas id="topItemsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Customers Bar Chart -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> <?php echo t('top_customers'); ?></h5>
            </div>
            <div class="card-body d-flex flex-column">
                <div class="flex-grow-1" style="position: relative; height: 350px;">
                    <canvas id="topCustomersChart"></canvas>
                </div>
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
    
    if (!dateFrom || !dateTo) {
        alert('<?php echo t('please_select_both_dates'); ?>');
        return;
    }
    
    if (dateFrom > dateTo) {
        alert('<?php echo t('date_from_must_be_before_date_to'); ?>');
        return;
    }
    
    window.location.href = '<?php echo BASE_URL; ?>index.php?date_from=' + dateFrom + '&date_to=' + dateTo;
}

function resetFilter() {
    const today = new Date().toISOString().split('T')[0];
    window.location.href = '<?php echo BASE_URL; ?>index.php?date_from=' + today + '&date_to=' + today;
}

function clearFilter() {
    const today = new Date().toISOString().split('T')[0];
    window.location.href = '<?php echo BASE_URL; ?>index.php?date_from=' + today + '&date_to=' + today;
}

// Chart.js default configuration
Chart.defaults.font.family = '<?php echo getLang() == "ur" ? "Almarai, Noto Nastaliq Urdu" : "Inter, Arial"; ?>';
Chart.defaults.font.size = 12;
Chart.defaults.color = '#495057';
Chart.defaults.plugins.legend.display = true;
Chart.defaults.plugins.legend.position = 'top';
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.padding = 15;
Chart.defaults.animation.duration = 1500;
Chart.defaults.animation.easing = 'easeInOutQuart';

// Gradient helper function
function createGradient(ctx, color1, color2) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, color1);
    gradient.addColorStop(1, color2);
    return gradient;
}

// Initialize Charts
document.addEventListener('DOMContentLoaded', function() {
    const currencySymbol = '<?php echo CURRENCY_SYMBOL; ?>';
    
    // Sales & Purchases Line Chart
    const salesPurchasesCtx = document.getElementById('salesPurchasesChart');
    if (salesPurchasesCtx) {
        const ctx = salesPurchasesCtx.getContext('2d');
        const salesGradient = createGradient(ctx, 'rgba(102, 126, 234, 0.3)', 'rgba(102, 126, 234, 0.05)');
        const purchasesGradient = createGradient(ctx, 'rgba(118, 75, 162, 0.3)', 'rgba(118, 75, 162, 0.05)');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: '<?php echo t('sales'); ?>',
                    data: <?php echo json_encode($chartSalesData); ?>,
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: salesGradient,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: 'rgb(102, 126, 234)',
                    pointBorderWidth: 2
                }, {
                    label: '<?php echo t('purchases'); ?>',
                    data: <?php echo json_encode($chartPurchasesData); ?>,
                    borderColor: 'rgb(118, 75, 162)',
                    backgroundColor: purchasesGradient,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: 'rgb(118, 75, 162)',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: { size: 13, weight: '600' },
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + currencySymbol + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: { size: 11 },
                            callback: function(value) {
                                return currencySymbol + value.toLocaleString('en-US');
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }
    
    // Profit Area Chart
    const profitCtx = document.getElementById('profitChart');
    if (profitCtx) {
        const ctx = profitCtx.getContext('2d');
        const profitGradient = createGradient(ctx, 'rgba(17, 153, 142, 0.4)', 'rgba(56, 239, 125, 0.1)');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: '<?php echo t('profit'); ?>',
                    data: <?php echo json_encode($chartProfitData); ?>,
                    borderColor: 'rgb(17, 153, 142)',
                    backgroundColor: profitGradient,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: 'rgb(17, 153, 142)',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: { size: 13, weight: '600' },
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y;
                                const sign = value >= 0 ? '+' : '';
                                return context.dataset.label + ': ' + sign + currencySymbol + value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: { size: 11 },
                            callback: function(value) {
                                return currencySymbol + value.toLocaleString('en-US');
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }
    
    // Top Items Pie Chart
    const topItemsCtx = document.getElementById('topItemsChart');
    if (topItemsCtx && <?php echo count($topItemsData) > 0 ? 'true' : 'false'; ?>) {
        const colors = [
            'rgba(102, 126, 234, 0.8)',
            'rgba(118, 75, 162, 0.8)',
            'rgba(17, 153, 142, 0.8)',
            'rgba(56, 239, 125, 0.8)',
            'rgba(240, 147, 251, 0.8)'
        ];
        
        new Chart(topItemsCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($topItemsLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($topItemsData); ?>,
                    backgroundColor: colors,
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            font: { size: 11 },
                            padding: 15,
                            boxWidth: 12
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ' + currencySymbol + value.toLocaleString('en-US', {minimumFractionDigits: 2}) + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    } else if (topItemsCtx) {
        topItemsCtx.parentElement.innerHTML = '<div class="text-center p-4 text-muted"><i class="fas fa-info-circle"></i> <?php echo t('no_data_available'); ?></div>';
    }
    
    // Top Customers Bar Chart
    const topCustomersCtx = document.getElementById('topCustomersChart');
    if (topCustomersCtx && <?php echo count($topCustomersData) > 0 ? 'true' : 'false'; ?>) {
        const ctx = topCustomersCtx.getContext('2d');
        const customerGradient = createGradient(ctx, 'rgba(102, 126, 234, 0.8)', 'rgba(118, 75, 162, 0.8)');
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($topCustomersLabels); ?>,
                datasets: [{
                    label: '<?php echo t('total_amount'); ?>',
                    data: <?php echo json_encode($topCustomersData); ?>,
                    backgroundColor: customerGradient,
                    borderColor: 'rgb(102, 126, 234)',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return currencySymbol + context.parsed.x.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: { size: 11 },
                            callback: function(value) {
                                return currencySymbol + value.toLocaleString('en-US');
                            }
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    } else if (topCustomersCtx) {
        topCustomersCtx.parentElement.innerHTML = '<div class="text-center p-4 text-muted"><i class="fas fa-info-circle"></i> <?php echo t('no_data_available'); ?></div>';
    }
});
</script>

<?php include 'includes/footer.php'; ?>
