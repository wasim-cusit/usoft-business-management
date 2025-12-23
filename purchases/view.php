<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'purchase_details_title';

$id = intval($_GET['id'] ?? 0);

if (empty($id)) {
    header('Location: ' . BASE_URL . 'purchases/list.php');
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT p.*, a.account_name, a.account_name_urdu FROM purchases p 
                         LEFT JOIN accounts a ON p.account_id = a.id 
                         WHERE p.id = ?");
    $stmt->execute([$id]);
    $purchase = $stmt->fetch();
    
    if (!$purchase) {
        header('Location: ' . BASE_URL . 'purchases/list.php');
        exit;
    }
    
    // Get purchase items
    $stmt = $db->prepare("SELECT pi.*, i.item_name, i.item_name_urdu, i.unit FROM purchase_items pi 
                         LEFT JOIN items i ON pi.item_id = i.id 
                         WHERE pi.purchase_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    header('Location: ' . BASE_URL . 'purchases/list.php');
    exit;
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-shopping-cart"></i> <?php echo t('purchase_details_title'); ?></h1>
        <a href="<?php echo BASE_URL; ?>purchases/list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> <?php echo t('back'); ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">خرید کی معلومات</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 40%;"><?php echo t('bill_no'); ?></th>
                                <td><?php echo htmlspecialchars($purchase['purchase_no']); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo t('date'); ?></th>
                                <td><?php echo formatDate($purchase['purchase_date']); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo t('supplier'); ?></th>
                                <td><?php echo displayAccountNameFull($purchase); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo t('remarks'); ?></th>
                                <td><?php echo htmlspecialchars($purchase['remarks'] ?? '-'); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 40%;"><?php echo t('grand_total'); ?></th>
                                <td><?php echo formatCurrency($purchase['total_amount']); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo t('discount'); ?></th>
                                <td><?php echo formatCurrency($purchase['discount']); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo t('net_amount'); ?></th>
                                <td><strong><?php echo formatCurrency($purchase['net_amount']); ?></strong></td>
                            </tr>
                            <tr>
                                <th><?php echo t('payment'); ?></th>
                                <td><?php echo formatCurrency($purchase['paid_amount']); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo t('balance'); ?></th>
                                <td>
                                    <span class="badge <?php echo $purchase['balance_amount'] > 0 ? 'bg-warning' : 'bg-success'; ?>">
                                        <?php echo formatCurrency($purchase['balance_amount']); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <h5 class="mb-3"><?php echo t('item_details'); ?></h5>
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
                                        <td><?php echo displayItemNameFull($item); ?></td>
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
                                <td><strong><?php echo formatCurrency($purchase['total_amount']); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

