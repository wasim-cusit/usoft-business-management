<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'loan_slip';

$accountId = $_GET['account_id'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Get accounts
try {
    $db = getDB();
    $stmt = $db->query("SELECT id, account_name, account_name_urdu FROM accounts WHERE status = 'active' ORDER BY account_name");
    $accounts = $stmt->fetchAll();
} catch (PDOException $e) {
    $accounts = [];
}

$loans = [];
$totalLoan = 0;
$totalReturned = 0;

if (!empty($accountId)) {
    try {
        $where = "WHERE account_id = ?";
        $params = [$accountId];
        
        if (!empty($dateFrom)) {
            $where .= " AND transaction_date >= ?";
            $params[] = $dateFrom;
        }
        
        if (!empty($dateTo)) {
            $where .= " AND transaction_date <= ?";
            $params[] = $dateTo;
        }
        
        // Get loan transactions (debit = loan given, credit = loan returned)
        // Note: In real system, you might want to use a specific transaction type or flag for loans
        $stmt = $db->prepare("SELECT * FROM transactions $where ORDER BY transaction_date DESC");
        $stmt->execute($params);
        $loans = $stmt->fetchAll();
        
        // Calculate totals
        foreach ($loans as $loan) {
            if ($loan['transaction_type'] == 'debit') {
                $totalLoan += $loan['amount'];
            } else {
                $totalReturned += $loan['amount'];
            }
        }
        
        // Get account info
        $stmt = $db->prepare("SELECT * FROM accounts WHERE id = ?");
        $stmt->execute([$accountId]);
        $account = $stmt->fetch();
        
    } catch (PDOException $e) {
        $loans = [];
        $account = null;
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-hand-holding-usd"></i> <?php echo t('loan_slip'); ?></h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <form method="GET" class="row g-2">
                    <div class="col-md-4">
                        <select class="form-select" name="account_id" required>
                            <option value="">-- <?php echo t('please_select_account'); ?> --</option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?php echo $acc['id']; ?>" <?php echo $accountId == $acc['id'] ? 'selected' : ''; ?>>
                                    <?php echo displayAccountNameFull($acc); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="<?php echo t('date_from'); ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="<?php echo t('date_to'); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> <?php echo t('view'); ?>
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <?php if (!empty($accountId) && !empty($account)): ?>
                    <div class="alert alert-info mb-4">
                        <h5><strong><?php echo t('select_account'); ?>:</strong> <?php echo displayAccountNameFull($account); ?></h5>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <strong><?php echo t('total_loan'); ?>:</strong> 
                                <span class="badge bg-danger"><?php echo formatCurrency($totalLoan); ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong><?php echo t('returned'); ?>:</strong> 
                                <span class="badge bg-success"><?php echo formatCurrency($totalReturned); ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong><?php echo t('balance'); ?>:</strong> 
                                <span class="badge bg-warning"><?php echo formatCurrency($totalLoan - $totalReturned); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($loans)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><?php echo t('date'); ?></th>
                                        <th><?php echo t('type'); ?></th>
                                        <th><?php echo t('amount'); ?></th>
                                        <th><?php echo t('description'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loans as $loan): ?>
                                        <tr>
                                            <td><?php echo formatDate($loan['transaction_date']); ?></td>
                                            <td>
                                                <?php if ($loan['transaction_type'] == 'debit'): ?>
                                                    <span class="badge bg-danger"><?php echo t('loan_given'); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><?php echo t('loan_returned'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatCurrency($loan['amount']); ?></td>
                                            <td><?php echo htmlspecialchars($loan['narration'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light">
                                        <td colspan="2"><strong><?php echo t('total'); ?>:</strong></td>
                                        <td colspan="2">
                                            <strong><?php echo t('total_loan'); ?>:</strong> <?php echo formatCurrency($totalLoan); ?> | 
                                            <strong><?php echo t('returned'); ?>:</strong> <?php echo formatCurrency($totalReturned); ?> | 
                                            <strong><?php echo t('balance'); ?>:</strong> <?php echo formatCurrency($totalLoan - $totalReturned); ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <?php echo t('no_loan_records'); ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo t('please_select_account'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

