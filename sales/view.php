<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'sale_details_title';

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
    
    // Get sale items - handle both old and new structure
    $stmt = $db->prepare("SELECT si.*, i.item_name, i.item_name_urdu, i.unit FROM sale_items si 
                         LEFT JOIN items i ON si.item_id = i.id 
                         WHERE si.sale_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();
    
    // Use new structure only - ensure all fields have values
    foreach ($items as &$item) {
        $item['qty'] = floatval($item['qty'] ?? 0);
        $item['narch'] = floatval($item['narch'] ?? 0);
        $item['bag'] = floatval($item['bag'] ?? 0);
        $item['wt'] = floatval($item['wt'] ?? ($item['qty'] + $item['narch'] + $item['bag']));
        $item['kate'] = floatval($item['kate'] ?? 0);
        $item['wt2'] = floatval($item['wt2'] ?? ($item['wt'] - $item['kate']));
    }
    unset($item);
    
} catch (PDOException $e) {
    header('Location: ' . BASE_URL . 'sales/list.php');
    exit;
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-cash-register"></i> <?php echo t('sale_details_title'); ?></h1>
        <div class="d-flex gap-2">
            <a href="<?php echo BASE_URL; ?>sales/print.php?id=<?php echo $id; ?>" class="btn btn-danger" target="_blank">
                <i class="fas fa-print"></i> <?php echo t('print'); ?>
            </a>
            <a href="<?php echo BASE_URL; ?>sales/list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> <?php echo t('back'); ?>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('sale_info'); ?></h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 40%;"><?php echo t('bill_no'); ?></th>
                                <td><?php echo htmlspecialchars($sale['sale_no']); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo t('date'); ?></th>
                                <td><?php echo formatDate($sale['sale_date']); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo t('customer'); ?></th>
                                <td><?php echo displayAccountNameFull($sale); ?></td>
                            </tr>
                            <?php if (!empty($sale['location'])): ?>
                            <tr>
                                <th>لوکیشن اڈا</th>
                                <td><?php echo htmlspecialchars($sale['location']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($sale['details'])): ?>
                            <tr>
                                <th>Details / تفصیل</th>
                                <td><?php echo htmlspecialchars($sale['details']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($sale['phone'])): ?>
                            <tr>
                                <th>Phn#</th>
                                <td><?php echo htmlspecialchars($sale['phone']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($sale['bilti'])): ?>
                            <tr>
                                <th>Bilti#</th>
                                <td><?php echo htmlspecialchars($sale['bilti']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php /* Commented out Remarks row - user requested
                            <tr>
                                <th><?php echo t('remarks'); ?></th>
                                <td><?php echo htmlspecialchars($sale['remarks'] ?? '-'); ?></td>
                            </tr>
                            */ ?>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 40%;"><?php echo t('grand_total'); ?></th>
                                <td><?php echo formatCurrency($sale['total_amount']); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo t('discount'); ?></th>
                                <td><?php echo formatCurrency($sale['discount']); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo t('net_amount'); ?></th>
                                <td><strong><?php echo formatCurrency($sale['net_amount']); ?></strong></td>
                            </tr>
                            <tr>
                                <th><?php echo t('receipt'); ?></th>
                                <td><?php echo formatCurrency($sale['paid_amount']); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo t('balance'); ?></th>
                                <td>
                                    <span class="badge <?php echo $sale['balance_amount'] > 0 ? 'bg-warning' : 'bg-success'; ?>">
                                        <?php echo formatCurrency($sale['balance_amount']); ?>
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
                                <th><?php echo t('item_name'); ?></th>
                                <th>Qty (نگ)</th>
                                <th>توڈا</th>
                                <th>بھرتی</th>
                                <th>وزن</th>
                                <th>کاٹ</th>
                                <th>صافی</th>
                                <th><?php echo t('rate'); ?></th>
                                <th><?php echo t('amount'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="10" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $index => $item): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo displayItemNameFull($item); ?></td>
                                        <td><?php echo formatNumber($item['qty'] ?? 0); ?></td>
                                        <td><?php echo formatNumber($item['narch'] ?? 0); ?></td>
                                        <td><?php echo formatNumber($item['bag'] ?? 0); ?></td>
                                        <td><?php echo formatNumber($item['wt'] ?? 0); ?></td>
                                        <td><?php echo formatNumber($item['kate'] ?? 0); ?></td>
                                        <td><strong><?php echo formatNumber($item['wt2'] ?? 0); ?></strong></td>
                                        <td><?php echo formatCurrency($item['rate']); ?></td>
                                        <td><strong><?php echo formatCurrency($item['amount']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="9" class="text-end"><strong><?php echo t('total'); ?>:</strong></td>
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

