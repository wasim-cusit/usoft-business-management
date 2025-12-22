<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'کسٹمر لسٹ';

$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

try {
    $db = getDB();
    
    $where = "WHERE status = 'active'";
    $params = [];
    
    if (!empty($search)) {
        $where .= " AND (account_name LIKE ? OR account_name_urdu LIKE ? OR account_code LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam];
    }
    
    // Get total count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accounts $where");
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get accounts
    $stmt = $db->prepare("SELECT a.*, ut.type_name_urdu as user_type_name FROM accounts a 
                         LEFT JOIN user_types ut ON a.user_type_id = ut.id 
                         $where ORDER BY a.id DESC LIMIT ? OFFSET ?");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $accounts = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $accounts = [];
    $totalPages = 0;
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-users"></i> کسٹمر لسٹ</h1>
        <a href="<?php echo BASE_URL; ?>accounts/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> نیا کھاتہ بنائیں
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-0">تمام کھاتے</h5>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="تلاش کریں...">
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
                                <th>کوڈ</th>
                                <th>کھاتہ کا نام</th>
                                <th>کھاتہ کا نام (اردو)</th>
                                <th>قسم</th>
                                <th>یوزر ٹائپ</th>
                                <th>موبائل</th>
                                <th>افتتاحی بیلنس</th>
                                <th>عمل</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($accounts)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">کوئی ریکارڈ نہیں ملا</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($accounts as $account): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($account['account_code']); ?></td>
                                        <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                                        <td><?php echo htmlspecialchars($account['account_name_urdu'] ?? ''); ?></td>
                                        <td>
                                            <?php
                                            $typeLabels = ['customer' => 'کسٹمر', 'supplier' => 'سپلائر', 'both' => 'دونوں'];
                                            echo $typeLabels[$account['account_type']] ?? $account['account_type'];
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($account['user_type_name'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($account['mobile'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge <?php echo $account['balance_type'] == 'debit' ? 'bg-danger' : 'bg-success'; ?>">
                                                <?php echo formatCurrency($account['opening_balance']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>accounts/view.php?id=<?php echo $account['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>accounts/edit.php?id=<?php echo $account['id']; ?>" class="btn btn-sm btn-warning">
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
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">پچھلا</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">اگلا</a>
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

