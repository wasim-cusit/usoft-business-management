<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'stock_ledger';

$itemId = $_GET['item_id'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Get items
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM items WHERE status = 'active' ORDER BY item_name");
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $items = [];
}

$movements = [];

if (!empty($itemId)) {
    try {
        $where = "WHERE item_id = ?";
        $params = [$itemId];
        
        if (!empty($dateFrom)) {
            $where .= " AND movement_date >= ?";
            $params[] = $dateFrom;
        }
        
        if (!empty($dateTo)) {
            $where .= " AND movement_date <= ?";
            $params[] = $dateTo;
        }
        
        $stmt = $db->prepare("SELECT * FROM stock_movements $where ORDER BY movement_date, id");
        $stmt->execute($params);
        $movements = $stmt->fetchAll();
        
        // Get item info
        $stmt = $db->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        
    } catch (PDOException $e) {
        $movements = [];
        $item = null;
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-chart-line"></i> <?php echo t('stock_ledger'); ?></h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <form method="GET" class="row g-2">
                    <div class="col-md-4">
                        <select class="form-select" name="item_id" required>
                            <option value="">-- <?php echo t('select_item'); ?> --</option>
                            <?php foreach ($items as $it): ?>
                                <option value="<?php echo $it['id']; ?>" <?php echo $itemId == $it['id'] ? 'selected' : ''; ?>>
                                    <?php echo displayItemNameFull($it); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> <?php echo t('view'); ?>
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <?php if (!empty($itemId) && !empty($movements)): ?>
                    <?php if ($item): ?>
                        <div class="alert alert-info">
                            <strong><?php echo t('items'); ?>:</strong> <?php echo displayItemNameFull($item); ?> | 
                            <strong>موجودہ سٹاک:</strong> <?php echo number_format($item['current_stock'], 2); ?> <?php echo htmlspecialchars($item['unit']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>تاریخ</th>
                                    <th>قسم</th>
                                    <th>حوالہ</th>
                                    <th>داخل</th>
                                    <th>خارج</th>
                                    <th>بیلنس</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movements as $movement): ?>
                                    <tr>
                                        <td><?php echo formatDate($movement['movement_date']); ?></td>
                                        <td>
                                            <?php
                                            $typeLabels = [
                                                'purchase' => '<span class="badge bg-success">خرید</span>',
                                                'sale' => '<span class="badge bg-danger">فروخت</span>',
                                                'adjustment' => '<span class="badge bg-warning">ایڈجسٹمنٹ</span>',
                                                'opening' => '<span class="badge bg-info">افتتاحی</span>'
                                            ];
                                            echo $typeLabels[$movement['movement_type']] ?? $movement['movement_type'];
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($movement['reference_id'] ?? '-'); ?></td>
                                        <td><?php echo $movement['quantity_in'] > 0 ? number_format($movement['quantity_in'], 2) : '-'; ?></td>
                                        <td><?php echo $movement['quantity_out'] > 0 ? number_format($movement['quantity_out'], 2) : '-'; ?></td>
                                        <td><strong><?php echo number_format($movement['balance_quantity'], 2); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif (!empty($itemId)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> اس مدت میں کوئی حرکت نہیں ملی
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> براہ کرم جنس منتخب کریں
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

