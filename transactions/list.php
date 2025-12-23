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
    
    // Get accounts for modals
    $stmt = $db->query("SELECT * FROM accounts WHERE status = 'active' ORDER BY account_name");
    $accounts = $stmt->fetchAll();
    
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
    $accounts = [];
    $transactions = [];
    $totalPages = 0;
}

include '../includes/header.php';
?>

<style>
/* Remove animations from card-header and card-body */
.card {
    transition: none !important;
}
.card:hover {
    transform: none !important;
}
.card-header {
    animation: none !important;
    padding: 12px 20px !important;
    font-size: 16px !important;
}
.card-header .form-label {
    margin-bottom: 2px !important;
    font-size: 12px !important;
}
.card-header::before {
    animation: none !important;
    display: none !important;
}
.card-body {
    animation: none !important;
}
.table tbody tr {
    transition: none !important;
}
.table tbody tr:hover {
    transform: none !important;
}
.btn {
    transition: none !important;
}
.btn:hover {
    transform: none !important;
}
</style>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-money-bill-wave"></i> <?php echo t('all_transactions'); ?></h1>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#debitModal">
                <i class="fas fa-arrow-down"></i> <?php echo t('cash_debit_type'); ?>
            </button>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#creditModal">
                <i class="fas fa-arrow-up"></i> <?php echo t('cash_credit_type'); ?>
            </button>
            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#journalModal">
                <i class="fas fa-exchange-alt"></i> <?php echo t('journal_type'); ?>
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <h5 class="mb-0"><?php echo t('all_transactions'); ?></h5>
                    </div>
                    <div class="col-md-8">
                        <form method="GET" class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small mb-1"><?php echo t('search'); ?></label>
                                <input type="text" class="form-control form-control-sm" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo t('search'); ?>...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1"><?php echo t('type'); ?></label>
                                <select class="form-select form-select-sm" name="type">
                                    <option value=""><?php echo t('all_types'); ?></option>
                                    <option value="debit" <?php echo $type == 'debit' ? 'selected' : ''; ?>><?php echo t('cash_debit_type'); ?></option>
                                    <option value="credit" <?php echo $type == 'credit' ? 'selected' : ''; ?>><?php echo t('cash_credit_type'); ?></option>
                                    <option value="journal" <?php echo $type == 'journal' ? 'selected' : ''; ?>><?php echo t('journal_type'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1"><?php echo t('date_from'); ?></label>
                                <input type="date" class="form-control form-control-sm" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1"><?php echo t('date_to'); ?></label>
                                <input type="date" class="form-control form-control-sm" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-search"></i>
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

<!-- Debit Modal -->
<div class="modal fade" id="debitModal" tabindex="-1" aria-labelledby="debitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="debitModalLabel">
                    <i class="fas fa-arrow-down"></i> <?php echo t('debit'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="debitFormMessage"></div>
                <form id="debitForm">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="transaction_date" id="debit_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('select_account'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" name="account_id" id="debit_account_id" required>
                                <option value="">-- <?php echo t('select'); ?> --</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>">
                                        <?php echo displayAccountNameFull($account); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('amount'); ?> <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="amount" id="debit_amount" required min="0.01">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('narration'); ?></label>
                        <textarea class="form-control" name="narration" id="debit_narration" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                </button>
                <button type="button" class="btn btn-danger" onclick="saveDebit()">
                    <i class="fas fa-save"></i> <?php echo t('save'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Credit Modal -->
<div class="modal fade" id="creditModal" tabindex="-1" aria-labelledby="creditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="creditModalLabel">
                    <i class="fas fa-arrow-up"></i> <?php echo t('credit'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="creditFormMessage"></div>
                <form id="creditForm">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="transaction_date" id="credit_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('select_account'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" name="account_id" id="credit_account_id" required>
                                <option value="">-- <?php echo t('select'); ?> --</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>">
                                        <?php echo displayAccountNameFull($account); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo t('amount'); ?> <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="amount" id="credit_amount" required min="0.01">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('narration'); ?></label>
                        <textarea class="form-control" name="narration" id="credit_narration" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                </button>
                <button type="button" class="btn btn-success" onclick="saveCredit()">
                    <i class="fas fa-save"></i> <?php echo t('save'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Journal Modal -->
<div class="modal fade" id="journalModal" tabindex="-1" aria-labelledby="journalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="journalModalLabel">
                    <i class="fas fa-exchange-alt"></i> <?php echo t('journal'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="journalFormMessage"></div>
                <form id="journalForm">
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label"><?php echo t('date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="transaction_date" id="journal_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><?php echo t('debit'); ?> <?php echo t('select_account'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" name="debit_account_id" id="debit_account_id_journal" required>
                                <option value="">-- <?php echo t('select'); ?> --</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>">
                                        <?php echo displayAccountNameFull($account); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><?php echo t('credit'); ?> <?php echo t('select_account'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" name="credit_account_id" id="credit_account_id_journal" required>
                                <option value="">-- <?php echo t('select'); ?> --</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>">
                                        <?php echo displayAccountNameFull($account); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><?php echo t('amount'); ?> <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="amount" id="journal_amount" required min="0.01">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('narration'); ?></label>
                        <textarea class="form-control" name="narration" id="journal_narration" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                </button>
                <button type="button" class="btn btn-info" onclick="saveJournal()">
                    <i class="fas fa-save"></i> <?php echo t('save'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function saveDebit() {
    const form = document.getElementById('debitForm');
    const formData = new FormData(form);
    const messageDiv = document.getElementById('debitFormMessage');
    
    messageDiv.innerHTML = '';
    
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo t('save'); ?>...';
    
    fetch('<?php echo BASE_URL; ?>transactions/debit-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('debitModal'));
            modal.hide();
            form.reset();
            document.getElementById('debit_date').value = '<?php echo date('Y-m-d'); ?>';
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('error_recording_transaction'); ?></div>';
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

function saveCredit() {
    const form = document.getElementById('creditForm');
    const formData = new FormData(form);
    const messageDiv = document.getElementById('creditFormMessage');
    
    messageDiv.innerHTML = '';
    
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo t('save'); ?>...';
    
    fetch('<?php echo BASE_URL; ?>transactions/credit-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('creditModal'));
            modal.hide();
            form.reset();
            document.getElementById('credit_date').value = '<?php echo date('Y-m-d'); ?>';
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('error_recording_transaction'); ?></div>';
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

function saveJournal() {
    const form = document.getElementById('journalForm');
    const formData = new FormData(form);
    const messageDiv = document.getElementById('journalFormMessage');
    
    messageDiv.innerHTML = '';
    
    const debitAccountId = formData.get('debit_account_id');
    const creditAccountId = formData.get('credit_account_id');
    
    if (debitAccountId == creditAccountId) {
        messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('accounts_cannot_same'); ?></div>';
        return;
    }
    
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo t('save'); ?>...';
    
    fetch('<?php echo BASE_URL; ?>transactions/journal-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('journalModal'));
            modal.hide();
            form.reset();
            document.getElementById('journal_date').value = '<?php echo date('Y-m-d'); ?>';
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('error_recording_journal'); ?></div>';
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

// Reset forms on modal close
document.getElementById('debitModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('debitForm').reset();
    document.getElementById('debitFormMessage').innerHTML = '';
    document.getElementById('debit_date').value = '<?php echo date('Y-m-d'); ?>';
});

document.getElementById('creditModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('creditForm').reset();
    document.getElementById('creditFormMessage').innerHTML = '';
    document.getElementById('credit_date').value = '<?php echo date('Y-m-d'); ?>';
});

document.getElementById('journalModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('journalForm').reset();
    document.getElementById('journalFormMessage').innerHTML = '';
    document.getElementById('journal_date').value = '<?php echo date('Y-m-d'); ?>';
});
</script>

<?php include '../includes/footer.php'; ?>

