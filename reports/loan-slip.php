<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'قرضہ سلیپ & اگراھی';

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
        $stmt = $db->prepare("SELECT * FROM transactions $where AND narration LIKE '%قرض%' ORDER BY transaction_date DESC");
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
    <h1><i class="fas fa-hand-holding-usd"></i> قرضہ سلیپ & اگراھی</h1>
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
                                    <?php echo displayAccountNameFull($acc); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="تاریخ سے">
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="تاریخ تک">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> دیکھیں
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
                                <strong>کل قرضہ:</strong> 
                                <span class="badge bg-danger"><?php echo formatCurrency($totalLoan); ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong>واپس:</strong> 
                                <span class="badge bg-success"><?php echo formatCurrency($totalReturned); ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong>باقی بیلنس:</strong> 
                                <span class="badge bg-warning"><?php echo formatCurrency($totalLoan - $totalReturned); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($loans)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>تاریخ</th>
                                        <th>قسم</th>
                                        <th>رقم</th>
                                        <th>تفصیل</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loans as $loan): ?>
                                        <tr>
                                            <td><?php echo formatDate($loan['transaction_date']); ?></td>
                                            <td>
                                                <?php if ($loan['transaction_type'] == 'debit'): ?>
                                                    <span class="badge bg-danger">قرضہ دیا</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">واپس کیا</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatCurrency($loan['amount']); ?></td>
                                            <td><?php echo htmlspecialchars($loan['narration'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light">
                                        <td colspan="2"><strong>کل:</strong></td>
                                        <td colspan="2">
                                            <strong>قرضہ:</strong> <?php echo formatCurrency($totalLoan); ?> | 
                                            <strong>واپس:</strong> <?php echo formatCurrency($totalReturned); ?> | 
                                            <strong>باقی:</strong> <?php echo formatCurrency($totalLoan - $totalReturned); ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> اس مدت میں کوئی قرضہ کا ریکارڈ نہیں ملا
                        </div>
                    <?php endif; ?>
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

