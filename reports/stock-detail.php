<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'سٹاک کھاتہ';

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
        <h1><i class="fas fa-warehouse"></i> سٹاک کھاتہ</h1>
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
                <h5 class="mb-0">تمام جنس کی سٹاک تفصیلات</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>کوڈ</th>
                                <th>جنس کا نام</th>
                                <th>یونٹ</th>
                                <th>افتتاحی سٹاک</th>
                                <th>کل خرید</th>
                                <th>کل فروخت</th>
                                <th>موجودہ سٹاک</th>
                                <th>کم از کم سٹاک</th>
                                <th>حالت</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">کوئی ریکارڈ نہیں ملا</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <?php
                                    $currentStock = $item['current_stock'];
                                    $minStock = $item['min_stock'];
                                    $statusClass = $currentStock <= $minStock ? 'bg-warning' : 'bg-success';
                                    $statusText = $currentStock <= $minStock ? 'کم سٹاک' : 'عام';
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                        <td><?php echo number_format($item['opening_stock'], 2); ?></td>
                                        <td><?php echo number_format($item['total_purchased'], 2); ?></td>
                                        <td><?php echo number_format($item['total_sold'], 2); ?></td>
                                        <td><strong><?php echo number_format($currentStock, 2); ?></strong></td>
                                        <td><?php echo number_format($minStock, 2); ?></td>
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

