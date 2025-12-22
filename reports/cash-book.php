<?php
require_once '../../config/config.php';
requireLogin();

$pageTitle = 'کیش بک';

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

try {
    $db = getDB();
    
    // Get opening balance (sum of all transactions before date_from)
    $stmt = $db->prepare("SELECT 
        COALESCE(SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END), 0) as total_debit,
        COALESCE(SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END), 0) as total_credit
        FROM transactions WHERE transaction_date < ?");
    $stmt->execute([$dateFrom]);
    $opening = $stmt->fetch();
    $openingBalance = $opening['total_debit'] - $opening['total_credit'];
    
    // Get period transactions
    $stmt = $db->prepare("SELECT t.*, a.account_name FROM transactions t
                         LEFT JOIN accounts a ON t.account_id = a.id
                         WHERE t.transaction_date BETWEEN ? AND ?
                         ORDER BY t.transaction_date, t.id");
    $stmt->execute([$dateFrom, $dateTo]);
    $transactions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $transactions = [];
    $openingBalance = 0;
}

include '../../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-book"></i> کیش بک</h1>
        <form method="GET" class="d-flex gap-2">
            <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" required>
            <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" required>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> دیکھیں
            </button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">کیش بک رپورٹ</h5>
            </div>
            <div class="card-body">
                <?php
                $balance = $openingBalance;
                $totalDebit = 0;
                $totalCredit = 0;
                ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>تاریخ</th>
                                <th>تفصیل</th>
                                <th>اکاؤنٹ</th>
                                <th>کیش بنام</th>
                                <th>کیش جمع</th>
                                <th>بیلنس</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-light">
                                <td colspan="3"><strong>افتتاحی بیلنس</strong></td>
                                <td><?php echo $openingBalance > 0 ? formatCurrency($openingBalance) : '-'; ?></td>
                                <td><?php echo $openingBalance < 0 ? formatCurrency(abs($openingBalance)) : '-'; ?></td>
                                <td><strong><?php echo formatCurrency($balance); ?></strong></td>
                            </tr>
                            <?php foreach ($transactions as $trans): ?>
                                <?php
                                if ($trans['transaction_type'] == 'debit') {
                                    $debit = $trans['amount'];
                                    $credit = 0;
                                    $balance += $debit;
                                    $totalDebit += $debit;
                                } else {
                                    $debit = 0;
                                    $credit = $trans['amount'];
                                    $balance -= $credit;
                                    $totalCredit += $credit;
                                }
                                ?>
                                <tr>
                                    <td><?php echo formatDate($trans['transaction_date']); ?></td>
                                    <td><?php echo htmlspecialchars($trans['narration'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($trans['account_name'] ?? '-'); ?></td>
                                    <td><?php echo $debit > 0 ? formatCurrency($debit) : '-'; ?></td>
                                    <td><?php echo $credit > 0 ? formatCurrency($credit) : '-'; ?></td>
                                    <td><strong><?php echo formatCurrency($balance); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="bg-light">
                                <td colspan="3"><strong>کل</strong></td>
                                <td><strong><?php echo formatCurrency($totalDebit); ?></strong></td>
                                <td><strong><?php echo formatCurrency($totalCredit); ?></strong></td>
                                <td></td>
                            </tr>
                            <tr class="bg-light">
                                <td colspan="5"><strong>اختتامی بیلنس</strong></td>
                                <td><strong><?php echo formatCurrency($balance); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

