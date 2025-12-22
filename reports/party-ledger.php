<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'پارٹی لیجر';

$accountId = $_GET['account_id'] ?? '';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Get accounts
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM accounts WHERE status = 'active' ORDER BY account_name");
    $accounts = $stmt->fetchAll();
} catch (PDOException $e) {
    $accounts = [];
}

$ledgerData = [];
$openingBalance = 0;

if (!empty($accountId)) {
    try {
        // Get account info
        $stmt = $db->prepare("SELECT * FROM accounts WHERE id = ?");
        $stmt->execute([$accountId]);
        $account = $stmt->fetch();
        
        if ($account) {
            $openingBalance = $account['opening_balance'];
            if ($account['balance_type'] == 'credit') {
                $openingBalance = -$openingBalance;
            }
            
            // Get purchases
            $stmt = $db->prepare("SELECT purchase_date as date, net_amount as amount, 'purchase' as type, purchase_no as ref_no FROM purchases WHERE account_id = ? AND purchase_date < ?");
            $stmt->execute([$accountId, $dateFrom]);
            $openingPurchases = $stmt->fetchAll();
            foreach ($openingPurchases as $p) {
                $openingBalance += $p['amount']; // Debit
            }
            
            // Get sales
            $stmt = $db->prepare("SELECT sale_date as date, net_amount as amount, 'sale' as type, sale_no as ref_no FROM sales WHERE account_id = ? AND sale_date < ?");
            $stmt->execute([$accountId, $dateFrom]);
            $openingSales = $stmt->fetchAll();
            foreach ($openingSales as $s) {
                $openingBalance -= $s['amount']; // Credit
            }
            
            // Get transactions
            $stmt = $db->prepare("SELECT transaction_date as date, amount, transaction_type, narration, transaction_no as ref_no FROM transactions WHERE account_id = ? AND transaction_date < ?");
            $stmt->execute([$accountId, $dateFrom]);
            $openingTrans = $stmt->fetchAll();
            foreach ($openingTrans as $t) {
                if ($t['transaction_type'] == 'debit') {
                    $openingBalance += $t['amount'];
                } else {
                    $openingBalance -= $t['amount'];
                }
            }
            
            // Get period transactions
            $stmt = $db->prepare("SELECT purchase_date as date, net_amount as amount, 'purchase' as type, purchase_no as ref_no, '' as narration FROM purchases WHERE account_id = ? AND purchase_date BETWEEN ? AND ? 
                                 UNION ALL
                                 SELECT sale_date as date, net_amount as amount, 'sale' as type, sale_no as ref_no, '' as narration FROM sales WHERE account_id = ? AND sale_date BETWEEN ? AND ?
                                 UNION ALL
                                 SELECT transaction_date as date, amount, transaction_type as type, transaction_no as ref_no, narration FROM transactions WHERE account_id = ? AND transaction_date BETWEEN ? AND ?
                                 ORDER BY date, id");
            $stmt->execute([$accountId, $dateFrom, $dateTo, $accountId, $dateFrom, $dateTo, $accountId, $dateFrom, $dateTo]);
            $ledgerData = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $error = 'ڈیٹا لانے میں خرابی';
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-file-invoice"></i> پارٹی لیجر</h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <form method="GET" class="row g-2">
                    <div class="col-md-4">
                        <select class="form-select" name="account_id" required>
                            <option value="">-- اکاؤنٹ منتخب کریں --</option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?php echo $acc['id']; ?>" <?php echo $accountId == $acc['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($acc['account_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> دیکھیں
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <?php if (!empty($accountId) && !empty($ledgerData)): ?>
                    <?php
                    $balance = $openingBalance;
                    ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>تاریخ</th>
                                    <th>تفصیل</th>
                                    <th>حوالہ</th>
                                    <th>ڈیبٹ</th>
                                    <th>کریڈٹ</th>
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
                                <?php foreach ($ledgerData as $row): ?>
                                    <?php
                                    if ($row['type'] == 'purchase' || ($row['type'] == 'debit' && $row['type'] != 'sale')) {
                                        $debit = $row['amount'];
                                        $credit = 0;
                                        $balance += $debit;
                                    } else {
                                        $debit = 0;
                                        $credit = $row['amount'];
                                        $balance -= $credit;
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo formatDate($row['date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['narration'] ?: ucfirst($row['type'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['ref_no']); ?></td>
                                        <td><?php echo $debit > 0 ? formatCurrency($debit) : '-'; ?></td>
                                        <td><?php echo $credit > 0 ? formatCurrency($credit) : '-'; ?></td>
                                        <td><strong><?php echo formatCurrency($balance); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="bg-light">
                                    <td colspan="5"><strong>اختتامی بیلنس</strong></td>
                                    <td><strong><?php echo formatCurrency($balance); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php elseif (!empty($accountId)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> اس مدت میں کوئی لین دین نہیں ملا
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> براہ کرم اکاؤنٹ منتخب کریں
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

