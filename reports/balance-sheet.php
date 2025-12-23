<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'balance_sheet';

$asOnDate = $_GET['as_on_date'] ?? date('Y-m-d');

try {
    $db = getDB();
    
    // Get assets (debit balances)
    $stmt = $db->prepare("SELECT 
        COALESCE(SUM(CASE WHEN balance_type = 'debit' THEN opening_balance ELSE 0 END), 0) as opening_debit,
        COALESCE(SUM(CASE WHEN balance_type = 'credit' THEN opening_balance ELSE 0 END), 0) as opening_credit
        FROM accounts WHERE status = 'active'");
    $stmt->execute();
    $opening = $stmt->fetch();
    
    // Get purchases (debit)
    $stmt = $db->prepare("SELECT COALESCE(SUM(balance_amount), 0) as total FROM purchases WHERE purchase_date <= ?");
    $stmt->execute([$asOnDate]);
    $purchaseBalance = $stmt->fetch()['total'];
    
    // Get sales (credit)
    $stmt = $db->prepare("SELECT COALESCE(SUM(balance_amount), 0) as total FROM sales WHERE sale_date <= ?");
    $stmt->execute([$asOnDate]);
    $saleBalance = $stmt->fetch()['total'];
    
    // Get transactions
    $stmt = $db->prepare("SELECT 
        COALESCE(SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END), 0) as total_debit,
        COALESCE(SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END), 0) as total_credit
        FROM transactions WHERE transaction_date <= ?");
    $stmt->execute([$asOnDate]);
    $trans = $stmt->fetch();
    
    $totalAssets = $opening['opening_debit'] + $purchaseBalance + $trans['total_debit'];
    $totalLiabilities = $opening['opening_credit'] + $saleBalance + $trans['total_credit'];
    $netWorth = $totalAssets - $totalLiabilities;
    
} catch (PDOException $e) {
    $totalAssets = 0;
    $totalLiabilities = 0;
    $netWorth = 0;
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-file-alt"></i> <?php echo t('balance_sheet'); ?></h1>
        <form method="GET" class="d-flex gap-2">
            <input type="date" class="form-control" name="as_on_date" value="<?php echo htmlspecialchars($asOnDate); ?>" required>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> <?php echo t('check'); ?>
            </button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('balance_sheet'); ?> - <?php echo formatDate($asOnDate); ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-primary"><?php echo t('assets'); ?></h5>
                        <table class="table table-bordered">
                            <tr>
                                <td><strong><?php echo t('opening_debit_balance'); ?></strong></td>
                                <td class="text-end"><?php echo formatCurrency($opening['opening_debit']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo t('purchase_balance'); ?></strong></td>
                                <td class="text-end"><?php echo formatCurrency($purchaseBalance); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo t('debit'); ?></strong></td>
                                <td class="text-end"><?php echo formatCurrency($trans['total_debit']); ?></td>
                            </tr>
                            <tr class="bg-light">
                                <td><strong><?php echo t('total_assets'); ?></strong></td>
                                <td class="text-end"><strong><?php echo formatCurrency($totalAssets); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h5 class="text-success"><?php echo t('liabilities'); ?></h5>
                        <table class="table table-bordered">
                            <tr>
                                <td><strong><?php echo t('opening_credit_balance'); ?></strong></td>
                                <td class="text-end"><?php echo formatCurrency($opening['opening_credit']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo t('sale_balance'); ?></strong></td>
                                <td class="text-end"><?php echo formatCurrency($saleBalance); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo t('credit'); ?></strong></td>
                                <td class="text-end"><?php echo formatCurrency($trans['total_credit']); ?></td>
                            </tr>
                            <tr class="bg-light">
                                <td><strong><?php echo t('total_liabilities'); ?></strong></td>
                                <td class="text-end"><strong><?php echo formatCurrency($totalLiabilities); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <table class="table table-bordered">
                            <tr class="bg-info text-white">
                                <td><strong><?php echo t('net_worth'); ?></strong></td>
                                <td class="text-end"><strong><?php echo formatCurrency($netWorth); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

