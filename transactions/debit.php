<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'debit';

$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Get accounts for modal
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM accounts WHERE status = 'active' ORDER BY account_name");
    $accounts = $stmt->fetchAll();
    
    // Get next transaction number for display
    $stmt = $db->query("SELECT MAX(id) as max_id FROM transactions WHERE transaction_type = 'debit' AND (reference_type IS NULL OR reference_type != 'journal')");
    $maxId = $stmt->fetch()['max_id'] ?? 0;
    $nextNumber = $maxId + 1;
    $nextTransactionNo = 'Dbt' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
    
    $where = "WHERE t.transaction_type = 'debit'";
    $params = [];
    
    if (!empty($search)) {
        $where .= " AND (t.transaction_no LIKE ? OR a.account_name LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
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
    
    // Get debit transactions
    $stmt = $db->prepare("SELECT t.*, a.account_name, a.account_name_urdu FROM transactions t 
                         LEFT JOIN accounts a ON t.account_id = a.id 
                         $where ORDER BY t.id DESC LIMIT ? OFFSET ?");
    $paramsForQuery = $params;
    $paramsForQuery[] = $limit;
    $paramsForQuery[] = $offset;
    $stmt->execute($paramsForQuery);
    $transactions = $stmt->fetchAll();
    
    // Calculate total for debit transactions
    $stmt = $db->prepare("SELECT COALESCE(SUM(t.amount), 0) as total FROM transactions t 
                         LEFT JOIN accounts a ON t.account_id = a.id 
                         $where");
    $stmt->execute($params);
    $debitTotal = $stmt->fetch()['total'] ?? 0;
    
} catch (PDOException $e) {
    $accounts = [];
    $transactions = [];
    $totalPages = 0;
    $totalRecords = 0;
    $debitTotal = 0;
    $nextTransactionNo = 'Dbt01';
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
    padding: 20px 25px !important;
    font-size: 18px !important;
}
.card-body {
    animation: none !important;
}
</style>

<div class="page-header">
    <h1><i class="fas fa-arrow-down"></i> <?php echo t('debit'); ?></h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0"><?php echo t('debit'); ?> <?php echo t('transactions'); ?> <span class="badge bg-primary"><?php echo $totalRecords ?? 0; ?></span></h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newDebitModal">
                            <i class="fas fa-plus"></i> <?php echo t('add'); ?> <?php echo t('debit'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Search and Filter Form -->
                <form method="GET" action="" class="row g-2 mb-4">
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo t('search'); ?>...">
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control form-control-sm" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="<?php echo t('date_from'); ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control form-control-sm" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="<?php echo t('date_to'); ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="fas fa-search"></i> <?php echo t('search'); ?>
                        </button>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php echo t('transaction_no'); ?></th>
                                <th><?php echo t('date'); ?></th>
                                <th><?php echo t('account_name'); ?></th>
                                <th><?php echo t('amount'); ?></th>
                                <th><?php echo t('narration'); ?></th>
                                <th><?php echo t('actions'); ?></th>
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
                                        <td><?php echo !empty($transaction['account_name']) ? displayAccountNameFull($transaction) : '-'; ?></td>
                                        <td><strong><?php echo formatCurrency($transaction['amount']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($transaction['narration'] ?? '-'); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning edit-debit-btn" data-transaction-id="<?php echo $transaction['id']; ?>" data-transaction-no="<?php echo htmlspecialchars($transaction['transaction_no']); ?>" title="<?php echo t('edit'); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-debit-btn ms-1" data-transaction-id="<?php echo $transaction['id']; ?>" data-transaction-no="<?php echo htmlspecialchars($transaction['transaction_no']); ?>" title="<?php echo t('delete'); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-danger">
                                <td colspan="3" class="text-end"><strong><?php echo t('total'); ?>:</strong></td>
                                <td><strong><?php echo formatCurrency($debitTotal); ?></strong></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="<?php echo t('page_navigation'); ?>">
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

<!-- New Debit Modal -->
<div class="modal fade" id="newDebitModal" tabindex="-1" aria-labelledby="newDebitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newDebitModalLabel">
                    <i class="fas fa-arrow-down"></i> <?php echo t('add'); ?> <?php echo t('debit'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="debitFormMessage"></div>
                <form id="debitForm">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><?php echo t('transaction_no'); ?></label>
                            <input type="text" class="form-control" name="transaction_no" id="debit_transaction_no" placeholder="<?php echo $nextTransactionNo ?? 'Dbt01'; ?>">
                            <small class="text-muted"><?php echo t('leave_empty_for_auto'); ?></small>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><?php echo t('date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="transaction_date" id="debit_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="col-md-3 mb-3">
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
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><?php echo t('amount'); ?> <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="amount" id="debit_amount" required min="0.01" placeholder="0">
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
                <button type="button" class="btn btn-primary" onclick="saveNewDebit()">
                    <i class="fas fa-save"></i> <?php echo t('save'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function saveNewDebit() {
    const form = document.getElementById('debitForm');
    const formData = new FormData(form);
    const messageDiv = document.getElementById('debitFormMessage');
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    
    // Validate
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Disable button and show loading
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo t('saving'); ?>...';
    messageDiv.innerHTML = '';
    
    fetch('<?php echo BASE_URL; ?>transactions/debit-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal immediately
            const modal = bootstrap.Modal.getInstance(document.getElementById('newDebitModal'));
            modal.hide();
            
            // Reset form
            form.reset();
            document.getElementById('debit_date').value = '<?php echo date('Y-m-d'); ?>';
            
            // Show notification only in fixed position (not in modal)
            showNotification(data.message, 'success');
            
            // Reload after 2 seconds to show notification
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            // Show error only in modal (not in fixed position for validation errors)
            messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        // Show error in modal for network errors
        messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('error_recording_transaction'); ?></div>';
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

// Reset form when modal is closed
document.getElementById('newDebitModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('debitForm').reset();
    document.getElementById('debitFormMessage').innerHTML = '';
    document.getElementById('debit_date').value = '<?php echo date('Y-m-d'); ?>';
});

// Delete debit transaction
document.addEventListener('click', function(e) {
    if (e.target.closest('.delete-debit-btn')) {
        const btn = e.target.closest('.delete-debit-btn');
        const transactionId = btn.getAttribute('data-transaction-id');
        const transactionNo = btn.getAttribute('data-transaction-no');
        
        if (confirm('<?php echo t('are_you_sure_delete'); ?> Transaction "' + transactionNo + '"?')) {
            btn.disabled = true;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            fetch('<?php echo BASE_URL; ?>transactions/delete-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + transactionId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            })
            .catch(error => {
                showNotification('<?php echo t('error_deleting_transaction'); ?>', 'error');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        }
    }
    
    // Edit debit transaction - open edit modal
    if (e.target.closest('.edit-debit-btn')) {
        const btn = e.target.closest('.edit-debit-btn');
        const transactionId = btn.getAttribute('data-transaction-id');
        // For now, redirect to edit page - can be changed to modal later
        window.location.href = '<?php echo BASE_URL; ?>transactions/edit-debit.php?id=' + transactionId;
    }
});
</script>

<?php include '../includes/footer.php'; ?>
