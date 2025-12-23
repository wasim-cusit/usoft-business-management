<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'all_purchases_list';

$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

try {
    $db = getDB();
    
    $where = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $where .= " AND (p.purchase_no LIKE ? OR a.account_name LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($dateFrom)) {
        $where .= " AND p.purchase_date >= ?";
        $params[] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $where .= " AND p.purchase_date <= ?";
        $params[] = $dateTo;
    }
    
    // Get total count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM purchases p LEFT JOIN accounts a ON p.account_id = a.id $where");
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get purchases
    $stmt = $db->prepare("SELECT p.*, a.account_name, a.account_name_urdu FROM purchases p 
                         LEFT JOIN accounts a ON p.account_id = a.id 
                         $where ORDER BY p.id DESC LIMIT ? OFFSET ?");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $purchases = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $purchases = [];
    $totalPages = 0;
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-shopping-cart"></i> <?php echo t('all_purchases_list'); ?></h1>
        <a href="<?php echo BASE_URL; ?>purchases/create.php" class="btn btn-primary mt-2 mt-md-0">
            <i class="fas fa-plus"></i> <?php echo t('new_purchase'); ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <form method="GET" class="row g-2">
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo t('search'); ?>...">
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="<?php echo t('date_from'); ?>">
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="<?php echo t('date_to'); ?>">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> <?php echo t('search'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php echo t('bill_no'); ?></th>
                                <th><?php echo t('date'); ?></th>
                                <th><?php echo t('supplier'); ?></th>
                                <th><?php echo t('total'); ?></th>
                                <th><?php echo t('discount'); ?></th>
                                <th><?php echo t('net_amount'); ?></th>
                                <th><?php echo t('paid_amount'); ?></th>
                                <th><?php echo t('balance'); ?></th>
                                <th><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($purchases)): ?>
                                <tr>
                                    <td colspan="9" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($purchases as $purchase): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($purchase['purchase_no']); ?></td>
                                        <td><?php echo formatDate($purchase['purchase_date']); ?></td>
                                        <td><?php echo displayAccountNameFull($purchase); ?></td>
                                        <td><?php echo formatCurrency($purchase['total_amount']); ?></td>
                                        <td><?php echo formatCurrency($purchase['discount']); ?></td>
                                        <td><strong><?php echo formatCurrency($purchase['net_amount']); ?></strong></td>
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
                
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>"><?php echo t('previous'); ?></a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>"><?php echo t('next'); ?></a>
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

