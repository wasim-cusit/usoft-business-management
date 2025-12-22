<?php
require_once '../../config/config.php';
requireLogin();

$pageTitle = 'روزنامچہ';

$dateFrom = $_GET['date_from'] ?? date('Y-m-d');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

try {
    $db = getDB();
    
    // Get all transactions
    $stmt = $db->prepare("SELECT t.*, a.account_name FROM transactions t
                         LEFT JOIN accounts a ON t.account_id = a.id
                         WHERE t.transaction_date BETWEEN ? AND ?
                         ORDER BY t.transaction_date, t.id");
    $stmt->execute([$dateFrom, $dateTo]);
    $transactions = $stmt->fetchAll();
    
    // Get purchases
    $stmt = $db->prepare("SELECT p.*, a.account_name FROM purchases p
                         LEFT JOIN accounts a ON p.account_id = a.id
                         WHERE p.purchase_date BETWEEN ? AND ?
                         ORDER BY p.purchase_date, p.id");
    $stmt->execute([$dateFrom, $dateTo]);
    $purchases = $stmt->fetchAll();
    
    // Get sales
    $stmt = $db->prepare("SELECT s.*, a.account_name FROM sales s
                         LEFT JOIN accounts a ON s.account_id = a.id
                         WHERE s.sale_date BETWEEN ? AND ?
                         ORDER BY s.sale_date, s.id");
    $stmt->execute([$dateFrom, $dateTo]);
    $sales = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $transactions = [];
    $purchases = [];
    $sales = [];
}

include '../../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-book-open"></i> روزنامچہ</h1>
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
                <h5 class="mb-0">روزنامچہ رپورٹ</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>تاریخ</th>
                                <th>تفصیل</th>
                                <th>حوالہ</th>
                                <th>ڈیبٹ</th>
                                <th>کریڈٹ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions) && empty($purchases) && empty($sales)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">اس مدت میں کوئی لین دین نہیں ملا</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($purchases as $purchase): ?>
                                    <tr>
                                        <td><?php echo formatDate($purchase['purchase_date']); ?></td>
                                        <td>خرید - <?php echo htmlspecialchars($purchase['account_name']); ?></td>
                                        <td><?php echo htmlspecialchars($purchase['purchase_no']); ?></td>
                                        <td><?php echo formatCurrency($purchase['net_amount']); ?></td>
                                        <td>-</td>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <?php foreach ($sales as $sale): ?>
                                    <tr>
                                        <td><?php echo formatDate($sale['sale_date']); ?></td>
                                        <td>فروخت - <?php echo htmlspecialchars($sale['account_name']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['sale_no']); ?></td>
                                        <td>-</td>
                                        <td><?php echo formatCurrency($sale['net_amount']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <?php foreach ($transactions as $trans): ?>
                                    <tr>
                                        <td><?php echo formatDate($trans['transaction_date']); ?></td>
                                        <td><?php echo htmlspecialchars($trans['narration'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($trans['transaction_no'] ?? '-'); ?></td>
                                        <td><?php echo $trans['transaction_type'] == 'debit' ? formatCurrency($trans['amount']) : '-'; ?></td>
                                        <td><?php echo $trans['transaction_type'] == 'credit' ? formatCurrency($trans['amount']) : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

