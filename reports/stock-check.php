<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'stock_check';

$checkType = $_GET['check_type'] ?? 'low_stock';

try {
    $db = getDB();
    
    if ($checkType == 'low_stock') {
        $stmt = $db->query("SELECT * FROM items WHERE current_stock <= min_stock AND status = 'active' ORDER BY (current_stock - min_stock) ASC");
        $items = $stmt->fetchAll();
        $title = t('low_stock_check');
    } elseif ($checkType == 'out_of_stock') {
        $stmt = $db->query("SELECT * FROM items WHERE current_stock <= 0 AND status = 'active' ORDER BY item_name");
        $items = $stmt->fetchAll();
        $title = t('out_of_stock_check');
    } else {
        $stmt = $db->query("SELECT * FROM items WHERE status = 'active' ORDER BY item_name");
        $items = $stmt->fetchAll();
        $title = t('all_check');
    }
    
} catch (PDOException $e) {
    $items = [];
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-exclamation-triangle"></i> <?php echo t('stock_check'); ?></h1>
        <form method="GET" class="d-flex gap-2">
            <select class="form-select" name="check_type" style="width: 200px;">
                <option value="low_stock" <?php echo $checkType == 'low_stock' ? 'selected' : ''; ?>><?php echo t('low_stock_check'); ?></option>
                <option value="out_of_stock" <?php echo $checkType == 'out_of_stock' ? 'selected' : ''; ?>><?php echo t('out_of_stock_check'); ?></option>
                <option value="all" <?php echo $checkType == 'all' ? 'selected' : ''; ?>><?php echo t('all_check'); ?></option>
            </select>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> <?php echo t('view'); ?>
            </button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo $title; ?></h5>
            </div>
            <div class="card-body">
                <?php if (empty($items)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> تمام جنس کا سٹاک مناسب ہے!
                    </div>
                <?php else: ?>
                    <div class="alert alert-<?php echo $checkType == 'out_of_stock' ? 'danger' : 'warning'; ?>">
                        <i class="fas fa-exclamation-triangle"></i> کل <?php echo count($items); ?> جنس <?php echo $checkType == 'out_of_stock' ? 'ختم' : 'کم'; ?> سٹاک میں ہیں
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>کوڈ</th>
                                    <th>جنس کا نام</th>
                                    <th>یونٹ</th>
                                    <th>موجودہ سٹاک</th>
                                    <th>کم از کم سٹاک</th>
                                    <th>فرق</th>
                                    <th>خرید کی قیمت</th>
                                    <th>فروخت کی قیمت</th>
                                    <th>حالت</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <?php
                                    $difference = $item['current_stock'] - $item['min_stock'];
                                    $statusClass = $item['current_stock'] <= 0 ? 'bg-danger' : ($difference < 0 ? 'bg-warning' : 'bg-success');
                                    $statusText = $item['current_stock'] <= 0 ? 'ختم' : ($difference < 0 ? 'کم' : 'عام');
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                                        <td><?php echo displayItemNameFull($item); ?></td>
                                        <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                        <td><strong><?php echo number_format($item['current_stock'], 2); ?></strong></td>
                                        <td><?php echo number_format($item['min_stock'], 2); ?></td>
                                        <td>
                                            <span class="badge <?php echo $difference < 0 ? 'bg-danger' : 'bg-success'; ?>">
                                                <?php echo number_format($difference, 2); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatCurrency($item['purchase_rate']); ?></td>
                                        <td><?php echo formatCurrency($item['sale_rate']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

