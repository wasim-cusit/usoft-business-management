<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'تمام لین دین لسٹ';

$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$type = $_GET['type'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

try {
    $db = getDB();
    
    $where = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $where .= " AND (t.transaction_no LIKE ? OR a.account_name LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($type)) {
        $where .= " AND t.transaction_type = ?";
        $params[] = $type;
    }
    
    if (!empty($dateFrom)) {
        $where .= " AND t.transaction_date >= ?";
        $params[] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $where .= " AND t.transaction_date <= ?";
        $params[] = $dateTo;
    }
    
    // Get total count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM transactions t LEFT JOIN accounts a ON t.account_id = a.id $where");
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get transactions
    $stmt = $db->prepare("SELECT t.*, a.account_name FROM transactions t 
                         LEFT JOIN accounts a ON t.account_id = a.id 
                         $where ORDER BY t.id DESC LIMIT ? OFFSET ?");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $transactions = [];
    $totalPages = 0;
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-money-bill-wave"></i> تمام لین دین لسٹ</h1>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <a href="<?php echo BASE_URL; ?>transactions/debit.php" class="btn btn-danger btn-sm">
                <i class="fas fa-arrow-down"></i> کیش بنام
            </a>
            <a href="<?php echo BASE_URL; ?>transactions/credit.php" class="btn btn-success btn-sm">
                <i class="fas fa-arrow-up"></i> کیش جمع
            </a>
            <a href="<?php echo BASE_URL; ?>transactions/journal.php" class="btn btn-info btn-sm">
                <i class="fas fa-exchange-alt"></i> JV
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <form method="GET" class="row g-2">
                            <div class="col-md-2">
                                <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="تلاش کریں...">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="type">
                                    <option value="">تمام قسمیں</option>
                                    <option value="debit" <?php echo $type == 'debit' ? 'selected' : ''; ?>>کیش بنام</option>
                                    <option value="credit" <?php echo $type == 'credit' ? 'selected' : ''; ?>>کیش جمع</option>
                                    <option value="journal" <?php echo $type == 'journal' ? 'selected' : ''; ?>>جرنل</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> تلاش
                                </button>
                            </div>
                            <div class="col-md-2">
                                <a href="<?php echo BASE_URL; ?>transactions/list.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-redo"></i> ریسیٹ
                                </a>
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
                                <th>ٹرانزیکشن نمبر</th>
                                <th>تاریخ</th>
                                <th>قسم</th>
                                <th>اکاؤنٹ</th>
                                <th>رقم</th>
                                <th>تفصیل</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">کوئی ریکارڈ نہیں ملا</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($transaction['transaction_no'] ?? '-'); ?></td>
                                        <td><?php echo formatDate($transaction['transaction_date']); ?></td>
                                        <td>
                                            <?php
                                            $typeLabels = [
                                                'debit' => '<span class="badge bg-danger">کیش بنام</span>',
                                                'credit' => '<span class="badge bg-success">کیش جمع</span>',
                                                'journal' => '<span class="badge bg-info">جرنل</span>'
                                            ];
                                            echo $typeLabels[$transaction['transaction_type']] ?? $transaction['transaction_type'];
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($transaction['account_name'] ?? '-'); ?></td>
                                        <td><strong><?php echo formatCurrency($transaction['amount']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($transaction['narration'] ?? '-'); ?></td>
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
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">پچھلا</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">اگلا</a>
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

