<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'all_items';

$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

try {
    $db = getDB();
    
    $where = "WHERE status = 'active'";
    $params = [];
    
    if (!empty($search)) {
        $where .= " AND (item_name LIKE ? OR item_name_urdu LIKE ? OR item_code LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam];
    }
    
    // Get total count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM items $where");
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get items
    $stmt = $db->prepare("SELECT * FROM items $where ORDER BY id DESC LIMIT ? OFFSET ?");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $items = [];
    $totalPages = 0;
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-box"></i> <?php echo t('all_items'); ?></h1>
        <a href="<?php echo BASE_URL; ?>items/create.php" class="btn btn-primary mt-2 mt-md-0">
            <i class="fas fa-plus"></i> <?php echo t('new_item'); ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-0"><?php echo t('all_items'); ?></h5>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo t('search'); ?>...">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php echo t('code'); ?></th>
                                <th><?php echo t('item_name'); ?> (<?php echo getLang() == 'ur' ? t('urdu') : t('english'); ?>)</th>
                                <th><?php echo t('item_name'); ?> (<?php echo getLang() == 'ur' ? t('english') : t('urdu'); ?>)</th>
                                <th><?php echo t('category'); ?></th>
                                <th><?php echo t('unit'); ?></th>
                                <th><?php echo t('purchase_rate'); ?></th>
                                <th><?php echo t('sale_rate'); ?></th>
                                <th><?php echo t('current_stock'); ?></th>
                                <th><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="9" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                                        <td><?php echo displayItemName($item); ?></td>
                                        <td><?php echo getLang() == 'ur' ? htmlspecialchars($item['item_name'] ?? '') : htmlspecialchars($item['item_name_urdu'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($item['category'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                        <td><?php echo formatCurrency($item['purchase_rate']); ?></td>
                                        <td><?php echo formatCurrency($item['sale_rate']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $item['current_stock'] <= $item['min_stock'] ? 'bg-warning' : 'bg-success'; ?>">
                                                <?php echo number_format($item['current_stock'], 2); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>items/edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>"><?php echo t('previous'); ?></a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>"><?php echo t('next'); ?></a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

