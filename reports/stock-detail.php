<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'stock_detail';

$search = $_GET['search'] ?? '';

try {
    $db = getDB();
    
    $where = "WHERE i.status = 'active'";
    $params = [];
    
    if (!empty($search)) {
        $where .= " AND (i.item_name LIKE ? OR i.item_code LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $stmt = $db->prepare("SELECT i.*, 
                         COALESCE(SUM(CASE WHEN sm.movement_type = 'purchase' THEN sm.quantity_in ELSE 0 END), 0) as total_purchased,
                         COALESCE(SUM(CASE WHEN sm.movement_type = 'sale' THEN sm.quantity_out ELSE 0 END), 0) as total_sold
                         FROM items i
                         LEFT JOIN stock_movements sm ON i.id = sm.item_id
                         $where
                         GROUP BY i.id
                         ORDER BY i.item_name");
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $items = [];
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-warehouse"></i> <?php echo t('stock_detail'); ?></h1>
        <form method="GET" class="d-flex">
            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo t('search'); ?>...">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('stock_detail_all_items'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php echo t('code'); ?></th>
                                <th><?php echo t('item_name'); ?></th>
                                <th><?php echo t('unit'); ?></th>
                                <th><?php echo t('opening_stock'); ?></th>
                                <th><?php echo t('total_purchased'); ?></th>
                                <th><?php echo t('total_sold'); ?></th>
                                <th><?php echo t('current_stock'); ?></th>
                                <th><?php echo t('min_stock'); ?></th>
                                <th><?php echo t('status'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="9" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <?php
                                    $currentStock = $item['current_stock'];
                                    $minStock = $item['min_stock'];
                                    $statusClass = $currentStock <= $minStock ? 'bg-warning' : 'bg-success';
                                    $statusText = $currentStock <= $minStock ? t('low_stock_status') : t('normal_status');
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_code'] ?? ''); ?></td>
                                        <td><?php echo displayItemNameFull($item); ?></td>
                                        <td><?php echo htmlspecialchars($item['unit'] ?? ''); ?></td>
                                        <td><?php echo formatNumber($item['opening_stock']); ?></td>
                                        <td><?php echo formatNumber($item['total_purchased']); ?></td>
                                        <td><?php echo formatNumber($item['total_sold']); ?></td>
                                        <td><strong><?php echo formatNumber($currentStock); ?></strong></td>
                                        <td><?php echo formatNumber($minStock); ?></td>
                                        <td>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
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

