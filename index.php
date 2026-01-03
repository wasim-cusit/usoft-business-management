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
    // Ensure numeric values - extract only numeric part
    $salesStats['total_sales'] = floatval(preg_replace('/[^0-9\.\-]/', '', (string)($salesStats['total_sales'] ?? 0)));
    $salesStats['credit_sales'] = floatval(preg_replace('/[^0-9\.\-]/', '', (string)($salesStats['credit_sales'] ?? 0)));
    $salesStats['debit_amount'] = floatval(preg_replace('/[^0-9\.\-]/', '', (string)($salesStats['debit_amount'] ?? 0)));
    
    // Purchases Statistics
    $stmt = $db->prepare("SELECT 
        COALESCE(SUM(net_amount), 0) as total_purchases,
        COALESCE(SUM(paid_amount), 0) as credit_purchases,
        COALESCE(SUM(balance_amount), 0) as debit_purchases
        FROM purchases WHERE purchase_date BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateTo]);
    $purchaseStats = $stmt->fetch();
    // Ensure numeric values - extract only numeric part
    $purchaseStats['total_purchases'] = floatval(preg_replace('/[^0-9\.\-]/', '', (string)($purchaseStats['total_purchases'] ?? 0)));
    $purchaseStats['credit_purchases'] = floatval(preg_replace('/[^0-9\.\-]/', '', (string)($purchaseStats['credit_purchases'] ?? 0)));
    $purchaseStats['debit_purchases'] = floatval(preg_replace('/[^0-9\.\-]/', '', (string)($purchaseStats['debit_purchases'] ?? 0)));
    
    // Total Accounts (all time, not filtered by date)
    $stmt = $db->query("SELECT COUNT(*) as total FROM accounts WHERE status = 'active'");
    $totalAccounts = $stmt->fetch()['total'];
    
    // Total Items (all time, not filtered by date)
    $stmt = $db->query("SELECT COUNT(*) as total FROM items WHERE status = 'active'");
    $totalItems = $stmt->fetch()['total'];
    
    // Low Stock Items (all time, not filtered by date)
    $stmt = $db->query("SELECT COUNT(*) as total FROM items WHERE current_stock <= min_stock AND status = 'active'");
    $lowStock = $stmt->fetch()['total'];
    
    // Purchases Statistics (filtered by date range) - This overwrites the previous query
    $stmt = $db->prepare("SELECT 
        COALESCE(SUM(net_amount), 0) as total_purchases,
        COALESCE(SUM(paid_amount), 0) as credit_purchases,
        COALESCE(SUM(balance_amount), 0) as debit_purchases
        FROM purchases WHERE purchase_date BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateTo]);
    $purchaseStats = $stmt->fetch();
    // Ensure numeric values (re-cast after overwrite) - extract only numeric part
    $purchaseStats['total_purchases'] = floatval(preg_replace('/[^0-9\.\-]/', '', (string)($purchaseStats['total_purchases'] ?? 0)));
    $purchaseStats['credit_purchases'] = floatval(preg_replace('/[^0-9\.\-]/', '', (string)($purchaseStats['credit_purchases'] ?? 0)));
    $purchaseStats['debit_purchases'] = floatval(preg_replace('/[^0-9\.\-]/', '', (string)($purchaseStats['debit_purchases'] ?? 0)));
    
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
    
    // Get sales list for dashboard (default to current date if no filter)
    $salesDateFrom = $_GET['sales_date_from'] ?? date('Y-m-d');
    $salesDateTo = $_GET['sales_date_to'] ?? date('Y-m-d');
    
    $salesWhere = "WHERE s.sale_date BETWEEN ? AND ?";
    $salesParams = [$salesDateFrom, $salesDateTo];
    
    // Get sales for dashboard
    $stmt = $db->prepare("SELECT s.*, a.account_name, a.account_name_urdu, a.mobile, a.phone FROM sales s 
                         LEFT JOIN accounts a ON s.account_id = a.id 
                         $salesWhere ORDER BY s.id DESC LIMIT 50");
    $stmt->execute($salesParams);
    $dashboardSales = $stmt->fetchAll();
    
    // Calculate totals for dashboard sales
    $stmt = $db->prepare("SELECT 
                            COALESCE(SUM(s.total_amount), 0) as total_amount,
                            COALESCE(SUM(s.discount), 0) as total_discount,
                            COALESCE(SUM(s.net_amount), 0) as total_net_amount,
                            COALESCE(SUM(s.paid_amount), 0) as total_paid_amount,
                            COALESCE(SUM(s.balance_amount), 0) as total_balance_amount
                         FROM sales s 
                         LEFT JOIN accounts a ON s.account_id = a.id 
                         $salesWhere");
    $stmt->execute($salesParams);
    $salesTotals = $stmt->fetch();
    $dashboardTotalAmount = $salesTotals['total_amount'] ?? 0;
    $dashboardTotalDiscount = $salesTotals['total_discount'] ?? 0;
    $dashboardTotalNetAmount = $salesTotals['total_net_amount'] ?? 0;
    $dashboardTotalPaidAmount = $salesTotals['total_paid_amount'] ?? 0;
    $dashboardTotalBalanceAmount = $salesTotals['total_balance_amount'] ?? 0;
    
    // Get transactions list for dashboard (default to current date if no filter)
    $transactionsDateFrom = $_GET['transactions_date_from'] ?? date('Y-m-d');
    $transactionsDateTo = $_GET['transactions_date_to'] ?? date('Y-m-d');
    
    $transactionsWhere = "WHERE t.transaction_date BETWEEN ? AND ?";
    $transactionsParams = [$transactionsDateFrom, $transactionsDateTo];
    
    // Get transactions for dashboard
    $stmt = $db->prepare("SELECT t.*, a.account_name, a.account_name_urdu, t.reference_type FROM transactions t 
                         LEFT JOIN accounts a ON t.account_id = a.id 
                         $transactionsWhere ORDER BY t.id DESC LIMIT 50");
    $stmt->execute($transactionsParams);
    $dashboardTransactions = $stmt->fetchAll();
    
    // Calculate totals for dashboard transactions
    // Cash Debit
    $stmt = $db->prepare("SELECT COALESCE(SUM(t.amount), 0) as total, COUNT(*) as count FROM transactions t 
                         LEFT JOIN accounts a ON t.account_id = a.id 
                         $transactionsWhere AND t.transaction_type = 'debit' 
                         AND (t.reference_type IS NULL OR t.reference_type != 'journal')");
    $stmt->execute($transactionsParams);
    $cashDebitResult = $stmt->fetch();
    $dashboardCashDebitTotal = $cashDebitResult['total'] ?? 0;
    $dashboardCashDebitCount = $cashDebitResult['count'] ?? 0;
    
    // Cash Credit
    $stmt = $db->prepare("SELECT COALESCE(SUM(t.amount), 0) as total, COUNT(*) as count FROM transactions t 
                         LEFT JOIN accounts a ON t.account_id = a.id 
                         $transactionsWhere AND t.transaction_type = 'credit' 
                         AND (t.reference_type IS NULL OR t.reference_type != 'journal')");
    $stmt->execute($transactionsParams);
    $cashCreditResult = $stmt->fetch();
    $dashboardCashCreditTotal = $cashCreditResult['total'] ?? 0;
    $dashboardCashCreditCount = $cashCreditResult['count'] ?? 0;
    
    // Journal
    $stmt = $db->prepare("SELECT COALESCE(SUM(t.amount), 0) as total, COUNT(DISTINCT SUBSTRING_INDEX(t.transaction_no, '-', 1)) as count FROM transactions t 
                         LEFT JOIN accounts a ON t.account_id = a.id 
                         $transactionsWhere AND t.reference_type = 'journal'");
    $stmt->execute($transactionsParams);
    $journalResult = $stmt->fetch();
    $dashboardJournalTotal = $journalResult['total'] ?? 0;
    $dashboardJournalCount = $journalResult['count'] ?? 0;
    
    // Get purchases list for dashboard (default to current date if no filter)
    $purchasesDateFrom = $_GET['purchases_date_from'] ?? date('Y-m-d');
    $purchasesDateTo = $_GET['purchases_date_to'] ?? date('Y-m-d');
    
    $purchasesWhere = "WHERE p.purchase_date BETWEEN ? AND ?";
    $purchasesParams = [$purchasesDateFrom, $purchasesDateTo];
    
    // Get purchases for dashboard
    $stmt = $db->prepare("SELECT p.*, a.account_name, a.account_name_urdu, a.mobile, a.phone FROM purchases p 
                         LEFT JOIN accounts a ON p.account_id = a.id 
                         $purchasesWhere ORDER BY p.id DESC LIMIT 50");
    $stmt->execute($purchasesParams);
    $dashboardPurchases = $stmt->fetchAll();
    
    // Calculate totals for dashboard purchases
    $stmt = $db->prepare("SELECT 
                            COALESCE(SUM(p.total_amount), 0) as total_amount,
                            COALESCE(SUM(p.discount), 0) as total_discount,
                            COALESCE(SUM(p.net_amount), 0) as total_net_amount,
                            COALESCE(SUM(p.paid_amount), 0) as total_paid_amount,
                            COALESCE(SUM(p.balance_amount), 0) as total_balance_amount
                         FROM purchases p 
                         LEFT JOIN accounts a ON p.account_id = a.id 
                         $purchasesWhere");
    $stmt->execute($purchasesParams);
    $purchasesTotals = $stmt->fetch();
    $dashboardPurchaseTotalAmount = $purchasesTotals['total_amount'] ?? 0;
    $dashboardPurchaseTotalDiscount = $purchasesTotals['total_discount'] ?? 0;
    $dashboardPurchaseTotalNetAmount = $purchasesTotals['total_net_amount'] ?? 0;
    $dashboardPurchaseTotalPaidAmount = $purchasesTotals['total_paid_amount'] ?? 0;
    $dashboardPurchaseTotalBalanceAmount = $purchasesTotals['total_balance_amount'] ?? 0;
    
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
    // Ensure numeric values for chart data
    foreach ($dailySales as &$sale) {
        $sale['amount'] = floatval($sale['amount'] ?? 0);
    }
    unset($sale);
    
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
    // Ensure numeric values for chart data
    foreach ($dailyPurchases as &$purchase) {
        $purchase['amount'] = floatval($purchase['amount'] ?? 0);
    }
    unset($purchase);
    
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
                // Clean numeric value - remove any non-numeric characters
                $salesAmount = floatval(preg_replace('/[^0-9\.\-]/', '', (string)($sale['amount'] ?? 0)));
                break;
            }
        }
        $chartSalesData[] = $salesAmount;
        
        // Find purchases for this date
        $purchaseAmount = 0;
        foreach ($dailyPurchases as $purchase) {
            if ($purchase['date'] == $dateStr) {
                // Clean numeric value - remove any non-numeric characters
                $purchaseAmount = floatval(preg_replace('/[^0-9\.\-]/', '', (string)($purchase['amount'] ?? 0)));
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
        $topItemsLabels[] = displayItemNameFull(['item_name' => $item['item_name'] ?? '', 'item_name_urdu' => $item['item_name_urdu'] ?? '']);
        // Clean numeric value - remove any non-numeric characters
        $topItemsData[] = floatval(preg_replace('/[^0-9\.\-]/', '', (string)($item['total_amount'] ?? 0)));
    }
    
    // Prepare top customers data
    $topCustomersLabels = [];
    $topCustomersData = [];
    foreach ($topCustomers as $customer) {
        $topCustomersLabels[] = displayAccountNameFull(['account_name' => $customer['account_name'] ?? '', 'account_name_urdu' => $customer['account_name_urdu'] ?? '']);
        // Clean numeric value - remove any non-numeric characters
        $topCustomersData[] = floatval(preg_replace('/[^0-9\.\-]/', '', (string)($customer['total_amount'] ?? 0)));
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
    $dashboardSales = [];
    $dashboardTotalAmount = 0;
    $dashboardTotalDiscount = 0;
    $dashboardTotalNetAmount = 0;
    $dashboardTotalPaidAmount = 0;
    $dashboardTotalBalanceAmount = 0;
    $salesDateFrom = date('Y-m-d');
    $salesDateTo = date('Y-m-d');
    $dashboardTransactions = [];
    $dashboardCashDebitTotal = 0;
    $dashboardCashDebitCount = 0;
    $dashboardCashCreditTotal = 0;
    $dashboardCashCreditCount = 0;
    $dashboardJournalTotal = 0;
    $dashboardJournalCount = 0;
    $transactionsDateFrom = date('Y-m-d');
    $transactionsDateTo = date('Y-m-d');
    $dashboardPurchases = [];
    $dashboardPurchaseTotalAmount = 0;
    $dashboardPurchaseTotalDiscount = 0;
    $dashboardPurchaseTotalNetAmount = 0;
    $dashboardPurchaseTotalPaidAmount = 0;
    $dashboardPurchaseTotalBalanceAmount = 0;
    $purchasesDateFrom = date('Y-m-d');
    $purchasesDateTo = date('Y-m-d');
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
<!--
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
-->

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
    <div class="col-md-12 mb-4">
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
    <!--
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
    -->
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

<!-- Sales List Section -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0"><i class="fas fa-cash-register"></i> <?php echo t('all_sales_list'); ?></h5>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="row g-2" id="salesFilterForm">
                            <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                            <input type="hidden" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                            <div class="col-md-4">
                                <input type="date" class="form-control form-control-sm" name="sales_date_from" id="sales_date_from" value="<?php echo htmlspecialchars($salesDateFrom); ?>">
                            </div>
                            <div class="col-md-4">
                                <input type="date" class="form-control form-control-sm" name="sales_date_to" id="sales_date_to" value="<?php echo htmlspecialchars($salesDateTo); ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-search"></i> <?php echo t('filter'); ?>
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
                            <?php if (empty($dashboardSales)): ?>
                                <tr>
                                    <td colspan="9" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dashboardSales as $sale): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sale['sale_no'] ?? ''); ?></td>
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
                                            <a href="<?php echo BASE_URL; ?>sales/view.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-info" title="<?php echo t('view'); ?>">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>sales/edit.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo t('edit'); ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>sales/print.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-secondary" target="_blank" title="<?php echo t('print'); ?>">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-success whatsapp-share-btn" 
                                                    data-sale-id="<?php echo $sale['id']; ?>"
                                                    data-sale-no="<?php echo htmlspecialchars($sale['sale_no'] ?? ''); ?>"
                                                    data-mobile="<?php echo htmlspecialchars($sale['mobile'] ?? ''); ?>"
                                                    data-phone="<?php echo htmlspecialchars($sale['phone'] ?? ''); ?>"
                                                    title="<?php echo t('share_via_whatsapp'); ?>">
                                                <i class="fab fa-whatsapp"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <td colspan="3" class="text-end"><strong><?php echo t('total'); ?>:</strong></td>
                                <td><strong><?php echo formatCurrency($dashboardTotalAmount); ?></strong></td>
                                <td><strong><?php echo formatCurrency($dashboardTotalDiscount); ?></strong></td>
                                <td><strong><?php echo formatCurrency($dashboardTotalNetAmount); ?></strong></td>
                                <td><strong><?php echo formatCurrency($dashboardTotalPaidAmount); ?></strong></td>
                                <td>
                                    <span class="badge <?php echo $dashboardTotalBalanceAmount > 0 ? 'bg-warning' : 'bg-success'; ?>">
                                        <strong><?php echo formatCurrency($dashboardTotalBalanceAmount); ?></strong>
                                    </span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="mt-3 text-center">
                    <a href="<?php echo BASE_URL; ?>sales/list.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> <?php echo t('view_all_sales'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transactions List Section -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0"><i class="fas fa-money-bill-wave"></i> <?php echo t('all_transactions'); ?></h5>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="row g-2" id="transactionsFilterForm">
                            <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                            <input type="hidden" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                            <input type="hidden" name="sales_date_from" value="<?php echo htmlspecialchars($salesDateFrom); ?>">
                            <input type="hidden" name="sales_date_to" value="<?php echo htmlspecialchars($salesDateTo); ?>">
                            <div class="col-md-4">
                                <input type="date" class="form-control form-control-sm" name="transactions_date_from" id="transactions_date_from" value="<?php echo htmlspecialchars($transactionsDateFrom); ?>">
                            </div>
                            <div class="col-md-4">
                                <input type="date" class="form-control form-control-sm" name="transactions_date_to" id="transactions_date_to" value="<?php echo htmlspecialchars($transactionsDateTo); ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-search"></i> <?php echo t('filter'); ?>
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
                                <th>T.No</th>
                                <th><?php echo t('date'); ?></th>
                                <th><?php echo t('account_name'); ?></th>
                                <th><?php echo t('cash_debit_type'); ?></th>
                                <th><?php echo t('cash_credit_type'); ?></th>
                                <?php /* Commented out JOURNAL column - user requested
                                <th><?php echo t('journal_type'); ?></th>
                                */ ?>
                                <th><?php echo t('amount'); ?></th>
                                <th><?php echo t('description'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dashboardTransactions)): ?>
                                <tr>
                                    <td colspan="7" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dashboardTransactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($transaction['transaction_no'] ?? '-'); ?></td>
                                        <td><?php echo formatDate($transaction['transaction_date']); ?></td>
                                        <td><?php echo !empty($transaction['account_name']) ? displayAccountNameFull($transaction) : '-'; ?></td>
                                        <td>
                                            <?php
                                            // Show Cash Debit if transaction_type is debit and not journal
                                            if ($transaction['transaction_type'] == 'debit' && ($transaction['reference_type'] ?? '') != 'journal') {
                                                echo '<span class="badge bg-danger">' . formatCurrency($transaction['amount']) . '</span>';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            // Show Cash Credit if transaction_type is credit and not journal
                                            if ($transaction['transaction_type'] == 'credit' && ($transaction['reference_type'] ?? '') != 'journal') {
                                                echo '<span class="badge bg-success">' . formatCurrency($transaction['amount']) . '</span>';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <?php /* Commented out JOURNAL column - user requested
                                        <td>
                                            <?php
                                            // Show Journal if reference_type is journal
                                            if (($transaction['reference_type'] ?? '') == 'journal') {
                                                echo '<span class="badge bg-info">' . formatCurrency($transaction['amount']) . '</span>';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        */ ?>
                                        <td><strong><?php echo formatCurrency($transaction['amount']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($transaction['narration'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end" style="background-color: #cfe2ff;"><strong><?php echo t('total'); ?>:</strong></td>
                                <td style="background-color: #f8d7da; color: #842029;">
                                    <strong><?php echo formatCurrency($dashboardCashDebitTotal); ?></strong>
                                    <br><small>(<?php echo $dashboardCashDebitCount ?? 0; ?> <?php echo t('transactions'); ?>)</small>
                                </td>
                                <td style="background-color: #d1e7dd; color: #0f5132;">
                                    <strong><?php echo formatCurrency($dashboardCashCreditTotal); ?></strong>
                                    <br><small>(<?php echo $dashboardCashCreditCount ?? 0; ?> <?php echo t('transactions'); ?>)</small>
                                </td>
                                <?php /* Commented out JOURNAL footer - user requested
                                <td style="background-color: #cff4fc; color: #055160;">
                                    <strong><?php echo formatCurrency($dashboardJournalTotal); ?></strong>
                                    <br><small>(<?php echo $dashboardJournalCount ?? 0; ?> <?php echo t('transactions'); ?>)</small>
                                </td>
                                */ ?>
                                <td style="background-color: #cfe2ff;"></td>
                                <td style="background-color: #cfe2ff;"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="mt-3 text-center">
                    <a href="<?php echo BASE_URL; ?>transactions/list.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> <?php echo t('view_all_transactions'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Purchases List Section -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> <?php echo t('all_purchases_list'); ?></h5>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="row g-2" id="purchasesFilterForm">
                            <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                            <input type="hidden" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                            <input type="hidden" name="sales_date_from" value="<?php echo htmlspecialchars($salesDateFrom); ?>">
                            <input type="hidden" name="sales_date_to" value="<?php echo htmlspecialchars($salesDateTo); ?>">
                            <input type="hidden" name="transactions_date_from" value="<?php echo htmlspecialchars($transactionsDateFrom); ?>">
                            <input type="hidden" name="transactions_date_to" value="<?php echo htmlspecialchars($transactionsDateTo); ?>">
                            <div class="col-md-4">
                                <input type="date" class="form-control form-control-sm" name="purchases_date_from" id="purchases_date_from" value="<?php echo htmlspecialchars($purchasesDateFrom); ?>">
                            </div>
                            <div class="col-md-4">
                                <input type="date" class="form-control form-control-sm" name="purchases_date_to" id="purchases_date_to" value="<?php echo htmlspecialchars($purchasesDateTo); ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-search"></i> <?php echo t('filter'); ?>
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
                            <?php if (empty($dashboardPurchases)): ?>
                                <tr>
                                    <td colspan="9" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dashboardPurchases as $purchase): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($purchase['purchase_no'] ?? ''); ?></td>
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
                                            <a href="<?php echo BASE_URL; ?>purchases/view.php?id=<?php echo $purchase['id']; ?>" class="btn btn-sm btn-info" title="<?php echo t('view'); ?>">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>purchases/print.php?id=<?php echo $purchase['id']; ?>" class="btn btn-sm btn-secondary" target="_blank" title="<?php echo t('print'); ?>">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-success whatsapp-share-purchase-btn" 
                                                    data-purchase-id="<?php echo $purchase['id']; ?>"
                                                    data-purchase-no="<?php echo htmlspecialchars($purchase['purchase_no'] ?? ''); ?>"
                                                    data-mobile="<?php echo htmlspecialchars($purchase['mobile'] ?? ''); ?>"
                                                    data-phone="<?php echo htmlspecialchars($purchase['phone'] ?? ''); ?>"
                                                    title="<?php echo t('share_via_whatsapp'); ?>">
                                                <i class="fab fa-whatsapp"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <td colspan="3" class="text-end"><strong><?php echo t('total'); ?>:</strong></td>
                                <td><strong><?php echo formatCurrency($dashboardPurchaseTotalAmount); ?></strong></td>
                                <td><strong><?php echo formatCurrency($dashboardPurchaseTotalDiscount); ?></strong></td>
                                <td><strong><?php echo formatCurrency($dashboardPurchaseTotalNetAmount); ?></strong></td>
                                <td><strong><?php echo formatCurrency($dashboardPurchaseTotalPaidAmount); ?></strong></td>
                                <td>
                                    <span class="badge <?php echo $dashboardPurchaseTotalBalanceAmount > 0 ? 'bg-warning' : 'bg-success'; ?>">
                                        <strong><?php echo formatCurrency($dashboardPurchaseTotalBalanceAmount); ?></strong>
                                    </span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="mt-3 text-center">
                    <a href="<?php echo BASE_URL; ?>purchases/list.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> <?php echo t('view_all_purchases'); ?>
                    </a>
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
    // Static currency symbol - never change (Rs.)
    const currencySymbol = 'Rs.';
    
    // Helper function to clean numeric values
    function cleanNumericValue(value) {
        if (value === null || value === undefined || value === '') {
            return 0;
        }
        // Convert to string and remove any non-numeric characters except decimal and minus
        const cleaned = String(value).replace(/[^0-9\.\-]/g, '');
        return parseFloat(cleaned) || 0;
    }
    
    // Helper function to format currency - remove .00 for whole numbers
    function formatCurrencyValue(value) {
        const cleaned = cleanNumericValue(value);
        if (cleaned % 1 === 0) {
            // Whole number - no decimals
            return cleaned.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
        } else {
            // Has decimals - show 2 decimal places
            return cleaned.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }
    
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
                    data: <?php echo json_encode(array_map(function($v) { return floatval(preg_replace('/[^0-9\.\-]/', '', (string)$v)); }, $chartSalesData)); ?>,
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
                    data: <?php echo json_encode(array_map(function($v) { return floatval(preg_replace('/[^0-9\.\-]/', '', (string)$v)); }, $chartPurchasesData)); ?>,
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
                                const value = cleanNumericValue(context.parsed.y);
                                return context.dataset.label + ': ' + currencySymbol + ' ' + formatCurrencyValue(value);
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
                    data: <?php echo json_encode(array_map(function($v) { return floatval(preg_replace('/[^0-9\.\-]/', '', (string)$v)); }, $chartProfitData)); ?>,
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
                                const value = cleanNumericValue(context.parsed.y);
                                const sign = value >= 0 ? '+' : '';
                                return context.dataset.label + ': ' + sign + currencySymbol + ' ' + formatCurrencyValue(value);
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
                                const cleaned = cleanNumericValue(value);
                                return currencySymbol + ' ' + cleaned.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
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
                    data: <?php echo json_encode(array_map(function($v) { return floatval(preg_replace('/[^0-9\.\-]/', '', (string)$v)); }, $topItemsData)); ?>,
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
                                const value = cleanNumericValue(context.parsed || 0);
                                const total = context.dataset.data.reduce((a, b) => cleanNumericValue(a) + cleanNumericValue(b), 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                                return label + ': ' + currencySymbol + ' ' + formatCurrencyValue(value) + ' (' + percentage + '%)';
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
                    data: <?php echo json_encode(array_map(function($v) { return floatval(preg_replace('/[^0-9\.\-]/', '', (string)$v)); }, $topCustomersData)); ?>,
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
                                const value = cleanNumericValue(context.parsed.x);
                                return currencySymbol + ' ' + formatCurrencyValue(value);
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
                                const cleaned = cleanNumericValue(value);
                                return currencySymbol + ' ' + cleaned.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
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

<!-- WhatsApp Share Modal for Sales -->
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

<script>
// WhatsApp share functionality for sales
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

// Handle WhatsApp share button in modal for sales
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
            cleanPhone = '+92' + cleanPhone.substring(1);
        } else if (cleanPhone.startsWith('92') && !cleanPhone.startsWith('+92')) {
            cleanPhone = '+' + cleanPhone;
        } else if (!cleanPhone.startsWith('+92')) {
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

// Handle WhatsApp share button in modal for purchases
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
            cleanPhone = '+92' + cleanPhone.substring(1);
        } else if (cleanPhone.startsWith('92') && !cleanPhone.startsWith('+92')) {
            cleanPhone = '+' + cleanPhone;
        } else if (!cleanPhone.startsWith('+92')) {
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

<?php include 'includes/footer.php'; ?>
