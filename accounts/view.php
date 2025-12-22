<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'کھاتہ کی تفصیلات';

$id = intval($_GET['id'] ?? 0);

if (empty($id)) {
    header('Location: ' . BASE_URL . 'accounts/list.php');
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT a.*, ut.type_name_urdu as user_type_name FROM accounts a 
                         LEFT JOIN user_types ut ON a.user_type_id = ut.id 
                         WHERE a.id = ?");
    $stmt->execute([$id]);
    $account = $stmt->fetch();
    
    if (!$account) {
        header('Location: ' . BASE_URL . 'accounts/list.php');
        exit;
    }
    
    // Get account balance
    $stmt = $db->prepare("SELECT 
        COALESCE(SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END), 0) as total_debit,
        COALESCE(SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END), 0) as total_credit
        FROM transactions WHERE account_id = ?");
    $stmt->execute([$id]);
    $trans = $stmt->fetch();
    
    $balance = $account['opening_balance'];
    if ($account['balance_type'] == 'credit') {
        $balance = -$balance;
    }
    $balance += $trans['total_debit'] - $trans['total_credit'];
    
} catch (PDOException $e) {
    header('Location: ' . BASE_URL . 'accounts/list.php');
    exit;
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-user"></i> کھاتہ کی تفصیلات</h1>
        <div>
            <a href="<?php echo BASE_URL; ?>accounts/edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> ایڈٹ کریں
            </a>
            <a href="<?php echo BASE_URL; ?>accounts/list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> واپس
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">بنیادی معلومات</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 40%;">کھاتہ کوڈ</th>
                        <td><?php echo htmlspecialchars($account['account_code']); ?></td>
                    </tr>
                    <tr>
                        <th>کھاتہ کا نام</th>
                        <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                    </tr>
                    <tr>
                        <th>کھاتہ کا نام (اردو)</th>
                        <td><?php echo htmlspecialchars($account['account_name_urdu'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>کھاتہ کی قسم</th>
                        <td>
                            <?php
                            $typeLabels = ['customer' => 'کسٹمر', 'supplier' => 'سپلائر', 'both' => 'دونوں'];
                            echo $typeLabels[$account['account_type']] ?? $account['account_type'];
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>یوزر ٹائپ</th>
                        <td><?php echo htmlspecialchars($account['user_type_name'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>رابطہ شخص</th>
                        <td><?php echo htmlspecialchars($account['contact_person'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>فون</th>
                        <td><?php echo htmlspecialchars($account['phone'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>موبائل</th>
                        <td><?php echo htmlspecialchars($account['mobile'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>ای میل</th>
                        <td><?php echo htmlspecialchars($account['email'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>پتہ</th>
                        <td><?php echo htmlspecialchars($account['address'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>شہر</th>
                        <td><?php echo htmlspecialchars($account['city'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>افتتاحی بیلنس</th>
                        <td>
                            <span class="badge <?php echo $account['balance_type'] == 'debit' ? 'bg-danger' : 'bg-success'; ?>">
                                <?php echo formatCurrency($account['opening_balance']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>موجودہ بیلنس</th>
                        <td>
                            <span class="badge <?php echo $balance >= 0 ? 'bg-danger' : 'bg-success'; ?>">
                                <?php echo formatCurrency(abs($balance)); ?>
                                <?php echo $balance >= 0 ? '(ڈیبٹ)' : '(کریڈٹ)'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>حالت</th>
                        <td>
                            <span class="badge <?php echo $account['status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo $account['status'] == 'active' ? 'فعال' : 'غیر فعال'; ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">فوری عمل</h5>
            </div>
            <div class="card-body">
                <a href="<?php echo BASE_URL; ?>reports/party-ledger.php?account_id=<?php echo $id; ?>" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-file-invoice"></i> پارٹی لیجر دیکھیں
                </a>
                <a href="<?php echo BASE_URL; ?>purchases/create.php?account_id=<?php echo $id; ?>" class="btn btn-info w-100 mb-2 text-white">
                    <i class="fas fa-shopping-cart"></i> خرید شامل کریں
                </a>
                <a href="<?php echo BASE_URL; ?>sales/create.php?account_id=<?php echo $id; ?>" class="btn btn-success w-100 mb-2">
                    <i class="fas fa-cash-register"></i> فروخت شامل کریں
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

