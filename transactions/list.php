<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'all_transactions_list';

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
    $stmt = $db->prepare("SELECT t.*, a.account_name, a.account_name_urdu FROM transactions t 
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
        <h1><i class="fas fa-money-bill-wave"></i> <?php echo t('all_transactions'); ?></h1>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <a href="<?php echo BASE_URL; ?>transactions/debit.php" class="btn btn-danger btn-sm">
                <i class="fas fa-arrow-down"></i> <?php echo t('cash_debit_type'); ?>
            </a>
            <a href="<?php echo BASE_URL; ?>transactions/credit.php" class="btn btn-success btn-sm">
                <i class="fas fa-arrow-up"></i> <?php echo t('cash_credit_type'); ?>
            </a>
            <a href="<?php echo BASE_URL; ?>transactions/journal.php" class="btn btn-info btn-sm">
                <i class="fas fa-exchange-alt"></i> <?php echo t('journal_type'); ?>
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
                                <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo t('search'); ?>...">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="type">
                                    <option value=""><?php echo t('all_types'); ?></option>
                                    <option value="debit" <?php echo $type == 'debit' ? 'selected' : ''; ?>><?php echo t('cash_debit_type'); ?></option>
                                    <option value="credit" <?php echo $type == 'credit' ? 'selected' : ''; ?>><?php echo t('cash_credit_type'); ?></option>
                                    <option value="journal" <?php echo $type == 'journal' ? 'selected' : ''; ?>><?php echo t('journal_type'); ?></option>
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
                                    <i class="fas fa-search"></i> <?php echo t('search'); ?>
                                </button>
                            </div>
                            <div class="col-md-2">
                                <a href="<?php echo BASE_URL; ?>transactions/list.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-redo"></i> <?php echo t('reset'); ?>
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
                                <th><?php echo t('transaction_no'); ?></th>
                                <th><?php echo t('date'); ?></th>
                                <th><?php echo t('type'); ?></th>
                                <th><?php echo t('account_name'); ?></th>
                                <th><?php echo t('amount'); ?></th>
                                <th><?php echo t('description'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($transaction['transaction_no'] ?? '-'); ?></td>
                                        <td><?php echo formatDate($transaction['transaction_date']); ?></td>
                                        <td>
                                            <?php
                                            $typeLabels = [
                                                'debit' => '<span class="badge bg-danger">' . t('debit') . '</span>',
                                                'credit' => '<span class="badge bg-success">' . t('credit') . '</span>',
                                                'journal' => '<span class="badge bg-info">' . t('journal') . '</span>'
                                            ];
                                            echo $typeLabels[$transaction['transaction_type']] ?? $transaction['transaction_type'];
                                            ?>
                                        </td>
                                        <td><?php echo !empty($transaction['account_name']) ? displayAccountNameFull($transaction) : '-'; ?></td>
                                        <td><strong><?php echo formatCurrency($transaction['amount']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($transaction['narration'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="<?php echo t('page_navigation'); ?>">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>"><?php echo t('previous'); ?></a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>"><?php echo t('next'); ?></a>
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

