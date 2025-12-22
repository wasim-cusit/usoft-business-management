<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'تمام بل چٹھہ';

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$type = $_GET['type'] ?? 'all';

try {
    $db = getDB();
    
    $purchases = [];
    $sales = [];
    
    if ($type == 'all' || $type == 'purchase') {
        $stmt = $db->prepare("SELECT p.*, a.account_name, a.account_name_urdu FROM purchases p 
                             LEFT JOIN accounts a ON p.account_id = a.id 
                             WHERE p.purchase_date BETWEEN ? AND ? 
                             ORDER BY p.purchase_date DESC, p.id DESC");
        $stmt->execute([$dateFrom, $dateTo]);
        $purchases = $stmt->fetchAll();
    }
    
    if ($type == 'all' || $type == 'sale') {
        $stmt = $db->prepare("SELECT s.*, a.account_name, a.account_name_urdu FROM sales s 
                             LEFT JOIN accounts a ON s.account_id = a.id 
                             WHERE s.sale_date BETWEEN ? AND ? 
                             ORDER BY s.sale_date DESC, s.id DESC");
        $stmt->execute([$dateFrom, $dateTo]);
        $sales = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    $purchases = [];
    $sales = [];
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-file-invoice"></i> تمام بل چٹھہ</h1>
        <form method="GET" class="d-flex gap-2">
            <select class="form-select" name="type" style="width: 150px;">
                <option value="all" <?php echo $type == 'all' ? 'selected' : ''; ?>>سب</option>
                <option value="purchase" <?php echo $type == 'purchase' ? 'selected' : ''; ?>>خرید</option>
                <option value="sale" <?php echo $type == 'sale' ? 'selected' : ''; ?>>فروخت</option>
            </select>
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
        <?php if ($type == 'all' || $type == 'purchase'): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> خرید کے بل</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>بل نمبر</th>
                                    <th>تاریخ</th>
                                    <th>سپلائر</th>
                                    <th>نیٹ رقم</th>
                                    <th>ادائیگی</th>
                                    <th>بیلنس</th>
                                    <th>عمل</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($purchases)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">کوئی ریکارڈ نہیں ملا</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($purchases as $purchase): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($purchase['purchase_no']); ?></td>
                                            <td><?php echo formatDate($purchase['purchase_date']); ?></td>
                                            <td><?php echo displayAccountNameFull($purchase); ?></td>
                                            <td><?php echo formatCurrency($purchase['net_amount']); ?></td>
                                            <td><?php echo formatCurrency($purchase['paid_amount']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $purchase['balance_amount'] > 0 ? 'bg-warning' : 'bg-success'; ?>">
                                                    <?php echo formatCurrency($purchase['balance_amount']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>purchases/view.php?id=<?php echo $purchase['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($type == 'all' || $type == 'sale'): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cash-register"></i> فروخت کے بل</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>بل نمبر</th>
                                    <th>تاریخ</th>
                                    <th>کسٹمر</th>
                                    <th>نیٹ رقم</th>
                                    <th>وصولی</th>
                                    <th>بیلنس</th>
                                    <th>عمل</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sales)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">کوئی ریکارڈ نہیں ملا</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($sales as $sale): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($sale['sale_no']); ?></td>
                                            <td><?php echo formatDate($sale['sale_date']); ?></td>
                                            <td><?php echo displayAccountNameFull($sale); ?></td>
                                            <td><?php echo formatCurrency($sale['net_amount']); ?></td>
                                            <td><?php echo formatCurrency($sale['paid_amount']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $sale['balance_amount'] > 0 ? 'bg-warning' : 'bg-success'; ?>">
                                                    <?php echo formatCurrency($sale['balance_amount']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>sales/view.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

