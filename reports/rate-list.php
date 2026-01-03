<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'rate_list';

try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM items WHERE status = 'active' ORDER BY item_name");
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $items = [];
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-list-alt"></i> <?php echo t('rate_list'); ?></h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('rate_list'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?php echo t('item_code'); ?></th>
                                <th><?php echo t('item_name'); ?></th>
                                <th><?php echo t('unit'); ?></th>
                                <th class="text-end"><?php echo t('purchase_rate'); ?></th>
                                <th class="text-end"><?php echo t('sale_rate'); ?></th>
                                <th class="text-end"><?php echo t('current_stock'); ?></th>
                                <th><?php echo t('status'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="7" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_code'] ?? '-'); ?></td>
                                        <td><?php echo displayItemNameFull($item); ?></td>
                                        <td><?php echo htmlspecialchars($item['unit'] ?? '-'); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($item['purchase_rate'] ?? 0); ?></td>
                                        <td class="text-end"><strong><?php echo formatCurrency($item['sale_rate'] ?? 0); ?></strong></td>
                                        <td class="text-end">
                                            <?php 
                                            $stock = floatval($item['current_stock'] ?? 0);
                                            $minStock = floatval($item['min_stock'] ?? 0);
                                            $class = ($stock <= $minStock) ? 'text-danger' : '';
                                            echo '<span class="' . $class . '">' . formatNumber($stock) . '</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($item['status'] == 'active'): ?>
                                                <span class="badge bg-success"><?php echo t('active'); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo t('inactive'); ?></span>
                                            <?php endif; ?>
                                        </td>
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

<?php include '../includes/footer.php'; ?>

