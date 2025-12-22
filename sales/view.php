<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'فروخت کی تفصیلات';

$id = intval($_GET['id'] ?? 0);

if (empty($id)) {
    header('Location: ' . BASE_URL . 'sales/list.php');
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, a.account_name, a.account_name_urdu FROM sales s 
                         LEFT JOIN accounts a ON s.account_id = a.id 
                         WHERE s.id = ?");
    $stmt->execute([$id]);
    $sale = $stmt->fetch();
    
    if (!$sale) {
        header('Location: ' . BASE_URL . 'sales/list.php');
        exit;
    }
    
    // Get sale items
    $stmt = $db->prepare("SELECT si.*, i.item_name, i.item_name_urdu, i.unit FROM sale_items si 
                         LEFT JOIN items i ON si.item_id = i.id 
                         WHERE si.sale_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    header('Location: ' . BASE_URL . 'sales/list.php');
    exit;
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-cash-register"></i> فروخت کی تفصیلات</h1>
        <a href="<?php echo BASE_URL; ?>sales/list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> واپس
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">فروخت کی معلومات</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 40%;">بل نمبر</th>
                                <td><?php echo htmlspecialchars($sale['sale_no']); ?></td>
                            </tr>
                            <tr>
                                <th>تاریخ</th>
                                <td><?php echo formatDate($sale['sale_date']); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo t('customer'); ?></th>
                                <td><?php echo displayAccountNameFull($sale); ?></td>
                            </tr>
                            <tr>
                                <th>ریمارکس</th>
                                <td><?php echo htmlspecialchars($sale['remarks'] ?? '-'); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 40%;">کل رقم</th>
                                <td><?php echo formatCurrency($sale['total_amount']); ?></td>
                            </tr>
                            <tr>
                                <th>ڈسکاؤنٹ</th>
                                <td><?php echo formatCurrency($sale['discount']); ?></td>
                            </tr>
                            <tr>
                                <th>نیٹ رقم</th>
                                <td><strong><?php echo formatCurrency($sale['net_amount']); ?></strong></td>
                            </tr>
                            <tr>
                                <th>وصولی</th>
                                <td><?php echo formatCurrency($sale['paid_amount']); ?></td>
                            </tr>
                            <tr>
                                <th>بیلنس</th>
                                <td>
                                    <span class="badge <?php echo $sale['balance_amount'] > 0 ? 'bg-warning' : 'bg-success'; ?>">
                                        <?php echo formatCurrency($sale['balance_amount']); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <h5 class="mb-3">جنس کی تفصیلات</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>جنس</th>
                                <th>مقدار</th>
                                <th>قیمت</th>
                                <th>رقم</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">کوئی جنس نہیں ملی</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $index => $item): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td><?php echo number_format($item['quantity'], 2) . ' ' . htmlspecialchars($item['unit']); ?></td>
                                        <td><?php echo formatCurrency($item['rate']); ?></td>
                                        <td><?php echo formatCurrency($item['amount']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end"><strong>کل:</strong></td>
                                <td><strong><?php echo formatCurrency($sale['total_amount']); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

