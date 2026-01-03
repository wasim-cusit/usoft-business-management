<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'party_ledger';

$accountId = $_GET['account_id'] ?? 'all';
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
$allAccountsData = [];
$account = null;
$viewAll = ($accountId === 'all');

if ($viewAll) {
    // Get ledger data for all accounts
    try {
        foreach ($accounts as $acc) {
            $accId = $acc['id'];
            $accOpeningBalance = $acc['opening_balance'];
            if ($acc['balance_type'] == 'credit') {
                $accOpeningBalance = -$accOpeningBalance;
            }
            
            // Get opening balance transactions
            $stmt = $db->prepare("SELECT purchase_date as date, net_amount as amount, 'purchase' as type, purchase_no as ref_no FROM purchases WHERE account_id = ? AND purchase_date < ?");
            $stmt->execute([$accId, $dateFrom]);
            $openingPurchases = $stmt->fetchAll();
            foreach ($openingPurchases as $p) {
                $accOpeningBalance += $p['amount'];
            }
            
            $stmt = $db->prepare("SELECT sale_date as date, net_amount as amount, 'sale' as type, sale_no as ref_no FROM sales WHERE account_id = ? AND sale_date < ?");
            $stmt->execute([$accId, $dateFrom]);
            $openingSales = $stmt->fetchAll();
            foreach ($openingSales as $s) {
                $accOpeningBalance -= $s['amount'];
            }
            
            $stmt = $db->prepare("SELECT transaction_date as date, amount, transaction_type, narration, transaction_no as ref_no FROM transactions WHERE account_id = ? AND transaction_date < ?");
            $stmt->execute([$accId, $dateFrom]);
            $openingTrans = $stmt->fetchAll();
            foreach ($openingTrans as $t) {
                if ($t['transaction_type'] == 'debit') {
                    $accOpeningBalance += $t['amount'];
                } else {
                    $accOpeningBalance -= $t['amount'];
                }
            }
            
            // Get period transactions
            $stmt = $db->prepare("SELECT purchase_date as date, net_amount as amount, 'purchase' as type, purchase_no as ref_no, '' as narration, id FROM purchases WHERE account_id = ? AND purchase_date BETWEEN ? AND ? 
                                 UNION ALL
                                 SELECT sale_date as date, net_amount as amount, 'sale' as type, sale_no as ref_no, '' as narration, id FROM sales WHERE account_id = ? AND sale_date BETWEEN ? AND ?
                                 UNION ALL
                                 SELECT transaction_date as date, amount, transaction_type as type, transaction_no as ref_no, narration, id FROM transactions WHERE account_id = ? AND transaction_date BETWEEN ? AND ?
                                 ORDER BY date, id");
            $stmt->execute([$accId, $dateFrom, $dateTo, $accId, $dateFrom, $dateTo, $accId, $dateFrom, $dateTo]);
            $accLedgerData = $stmt->fetchAll();
            
            if (!empty($accLedgerData) || $accOpeningBalance != 0) {
                $allAccountsData[$accId] = [
                    'account' => $acc,
                    'opening_balance' => $accOpeningBalance,
                    'ledger_data' => $accLedgerData
                ];
            }
        }
    } catch (PDOException $e) {
        $error = t('error') . ': ' . $e->getMessage();
    }
} elseif (!empty($accountId)) {
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
            $stmt = $db->prepare("SELECT purchase_date as date, net_amount as amount, 'purchase' as type, purchase_no as ref_no, '' as narration, id FROM purchases WHERE account_id = ? AND purchase_date BETWEEN ? AND ? 
                                 UNION ALL
                                 SELECT sale_date as date, net_amount as amount, 'sale' as type, sale_no as ref_no, '' as narration, id FROM sales WHERE account_id = ? AND sale_date BETWEEN ? AND ?
                                 UNION ALL
                                 SELECT transaction_date as date, amount, transaction_type as type, transaction_no as ref_no, narration, id FROM transactions WHERE account_id = ? AND transaction_date BETWEEN ? AND ?
                                 ORDER BY date, id");
            $stmt->execute([$accountId, $dateFrom, $dateTo, $accountId, $dateFrom, $dateTo, $accountId, $dateFrom, $dateTo]);
            $ledgerData = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $error = t('error') . ': ' . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-file-invoice"></i> <?php echo t('party_ledger'); ?></h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <form method="GET" class="row g-2" id="partyLedgerForm">
                    <div class="col-md-4">
                        <select class="form-select" name="account_id" id="accountSelect" required>
                            <option value="">-- <?php echo t('please_select_account'); ?> --</option>
                            <option value="all" <?php echo $accountId === 'all' ? 'selected' : ''; ?>>
                                <?php echo t('all'); ?> <?php echo t('accounts'); ?>
                            </option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?php echo $acc['id']; ?>" <?php echo $accountId == $acc['id'] ? 'selected' : ''; ?>>
                                    <?php echo displayAccountNameFull($acc); ?>
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
                            <i class="fas fa-search"></i> <?php echo t('view'); ?>
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-body" style="min-height: 200px;">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif ($viewAll && !empty($allAccountsData)): ?>
                    <?php foreach ($allAccountsData as $accId => $accData): ?>
                        <?php
                        $account = $accData['account'];
                        $balance = $accData['opening_balance'];
                        ?>
                        <h5 class="mb-3 mt-4">
                            <i class="fas fa-user"></i> <?php echo displayAccountNameFull($account); ?>
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><?php echo t('date'); ?></th>
                                        <th><?php echo t('description'); ?></th>
                                        <th><?php echo t('reference'); ?></th>
                                        <th><?php echo t('debit'); ?></th>
                                        <th><?php echo t('credit'); ?></th>
                                        <th><?php echo t('balance'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="bg-light">
                                        <td colspan="3"><strong><?php echo t('opening_balance'); ?></strong></td>
                                        <td><?php echo $balance > 0 ? formatCurrency($balance) : '-'; ?></td>
                                        <td><?php echo $balance < 0 ? formatCurrency(abs($balance)) : '-'; ?></td>
                                        <td><strong><?php echo formatCurrency($balance); ?></strong></td>
                                    </tr>
                                    <?php foreach ($accData['ledger_data'] as $row): ?>
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
                                            <td><?php echo htmlspecialchars($row['narration'] ?? ucfirst($row['type'] ?? '')); ?></td>
                                            <td><?php echo htmlspecialchars($row['ref_no'] ?? ''); ?></td>
                                            <td><?php echo $debit > 0 ? formatCurrency($debit) : '-'; ?></td>
                                            <td><?php echo $credit > 0 ? formatCurrency($credit) : '-'; ?></td>
                                            <td><strong><?php echo formatCurrency($balance); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="bg-light">
                                        <td colspan="5"><strong><?php echo t('closing_balance'); ?></strong></td>
                                        <td><strong><?php echo formatCurrency($balance); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php elseif ($viewAll && empty($allAccountsData)): ?>
                    <div class="alert alert-info alert-dismissible fade show">
                        <i class="fas fa-info-circle"></i> <strong><?php echo t('no_transactions_found'); ?></strong> <?php echo t('for'); ?> <?php echo t('all'); ?> <?php echo t('accounts'); ?> <?php echo t('in'); ?> <?php echo t('selected'); ?> <?php echo t('period'); ?>.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif (!empty($accountId) && !empty($account)): ?>
                    <?php if (!empty($ledgerData)): ?>
                    <?php
                    $balance = $openingBalance;
                    ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><?php echo t('date'); ?></th>
                                    <th><?php echo t('description'); ?></th>
                                    <th><?php echo t('reference'); ?></th>
                                    <th><?php echo t('debit'); ?></th>
                                    <th><?php echo t('credit'); ?></th>
                                    <th><?php echo t('balance'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bg-light">
                                    <td colspan="3"><strong><?php echo t('opening_balance'); ?></strong></td>
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
                                        <td><?php echo htmlspecialchars($row['narration'] ?? ucfirst($row['type'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars($row['ref_no'] ?? ''); ?></td>
                                        <td><?php echo $debit > 0 ? formatCurrency($debit) : '-'; ?></td>
                                        <td><?php echo $credit > 0 ? formatCurrency($credit) : '-'; ?></td>
                                        <td><strong><?php echo formatCurrency($balance); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="bg-light">
                                    <td colspan="5"><strong><?php echo t('closing_balance'); ?></strong></td>
                                    <td><strong><?php echo formatCurrency($balance); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php elseif ($openingBalance != 0): ?>
                    <?php
                    $balance = $openingBalance;
                    ?>
                    <div class="alert alert-info alert-dismissible fade show mb-3">
                        <i class="fas fa-info-circle"></i> <strong><?php echo t('no_transactions_found'); ?></strong> <?php echo t('in'); ?> <?php echo t('selected'); ?> <?php echo t('period'); ?>. <?php echo t('showing'); ?> <?php echo t('opening_balance'); ?> <?php echo t('only'); ?>.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><?php echo t('date'); ?></th>
                                    <th><?php echo t('description'); ?></th>
                                    <th><?php echo t('reference'); ?></th>
                                    <th><?php echo t('debit'); ?></th>
                                    <th><?php echo t('credit'); ?></th>
                                    <th><?php echo t('balance'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bg-light">
                                    <td colspan="3"><strong><?php echo t('opening_balance'); ?></strong></td>
                                    <td><?php echo $openingBalance > 0 ? formatCurrency($openingBalance) : '-'; ?></td>
                                    <td><?php echo $openingBalance < 0 ? formatCurrency(abs($openingBalance)) : '-'; ?></td>
                                    <td><strong><?php echo formatCurrency($balance); ?></strong></td>
                                </tr>
                                <tr class="bg-light">
                                    <td colspan="5"><strong><?php echo t('closing_balance'); ?></strong></td>
                                    <td><strong><?php echo formatCurrency($balance); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <div class="alert alert-info alert-dismissible fade show">
                            <i class="fas fa-info-circle"></i> <strong><?php echo t('no_transactions_found'); ?></strong> <?php echo t('for'); ?> <strong><?php echo displayAccountNameFull($account); ?></strong> <?php echo t('in'); ?> <?php echo t('selected'); ?> <?php echo t('period'); ?>.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                <?php elseif (!empty($accountId) && empty($account)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <strong><?php echo t('account_not_found'); ?></strong>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning alert-dismissible fade show" id="selectAccountAlert">
                        <i class="fas fa-exclamation-triangle"></i> <strong><?php echo t('please_select_account'); ?></strong> <?php echo t('or'); ?> <strong><?php echo t('all'); ?> <?php echo t('accounts'); ?></strong> <?php echo t('to'); ?> <?php echo t('view'); ?> <?php echo t('party_ledger'); ?>.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Only hide the "please select account" alert when account is selected (not info alerts)
document.getElementById('accountSelect')?.addEventListener('change', function() {
    // Hide only the "please select account" warning alert when an account is selected
    const selectAlert = document.getElementById('selectAccountAlert');
    if (selectAlert && (this.value === 'all' || this.value)) {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(selectAlert);
        bsAlert.close();
    }
});

// Only hide the "please select account" alert when form is submitted (not info alerts)
document.getElementById('partyLedgerForm')?.addEventListener('submit', function() {
    // Hide only the "please select account" warning alert
    const selectAlert = document.getElementById('selectAccountAlert');
    if (selectAlert) {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(selectAlert);
        bsAlert.close();
    }
});
</script>

<?php include '../includes/footer.php'; ?>

