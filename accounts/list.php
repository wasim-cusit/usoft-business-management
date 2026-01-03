<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'customer_list';

$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

try {
    $db = getDB();
    $where = "WHERE status = 'active'";
    $params = [];
    
    if (!empty($search)) {
        $where .= " AND (account_name LIKE ? OR account_name_urdu LIKE ? OR account_code LIKE ? OR mobile LIKE ? OR phone LIKE ? OR email LIKE ? OR address LIKE ? OR city LIKE ? OR contact_person LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
    }
    
    // Get total count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accounts $where");
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get accounts
    $stmt = $db->prepare("SELECT a.* FROM accounts a 
                         $where ORDER BY a.id DESC LIMIT ? OFFSET ?");
    $paramsForQuery = $params;
    $paramsForQuery[] = $limit;
    $paramsForQuery[] = $offset;
    $stmt->execute($paramsForQuery);
    $accounts = $stmt->fetchAll();
    
    // Calculate totals for Opening Balance Debit and Credit
    $debitWhere = $where . " AND balance_type = 'debit'";
    $stmt = $db->prepare("SELECT COALESCE(SUM(opening_balance), 0) as total FROM accounts $debitWhere");
    $stmt->execute($params);
    $openingDebitTotal = $stmt->fetch()['total'] ?? 0;
    
    $creditWhere = $where . " AND balance_type = 'credit'";
    $stmt = $db->prepare("SELECT COALESCE(SUM(opening_balance), 0) as total FROM accounts $creditWhere");
    $stmt->execute($params);
    $openingCreditTotal = $stmt->fetch()['total'] ?? 0;
    
} catch (PDOException $e) {
    $accounts = [];
    $totalPages = 0;
    $totalRecords = 0;
    $openingDebitTotal = 0;
    $openingCreditTotal = 0;
}

include '../includes/header.php';
?>

<style>
/* Remove animations from card and table, but keep card-header animation */
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
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-users"></i> <?php echo t('customer_list'); ?></h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAccountModal">
            <i class="fas fa-plus"></i> <?php echo t('new_account'); ?>
        </button>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0"><?php echo t('accounts'); ?> <span class="badge bg-primary"><?php echo $totalRecords ?? 0; ?></span></h5>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="text" class="form-control form-control-sm" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo t('search'); ?>...">
                            <button type="submit" class="btn btn-primary btn-sm ms-2">
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
                                <!--<th><?php echo t('name'); ?></th>-->
                                <th><?php echo t('name'); ?> (<?php echo t('urdu'); ?>)</th>
                                <!--<th><?php echo t('type'); ?></th>-->
                                <th><?php echo t('mobile'); ?></th>
                                <th><?php echo t('debit'); ?></th>
                                <th><?php echo t('credit'); ?></th>
                                <th><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($accounts)): ?>
                                <tr>
                                    <td colspan="6" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($accounts as $account): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($account['account_code'] ?? ''); ?></td>
                                        <!--<td><?php echo displayAccountName($account); ?></td>-->
                                        <td><?php echo getLang() == 'ur' ? htmlspecialchars($account['account_name'] ?? '') : htmlspecialchars($account['account_name_urdu'] ?? ''); ?></td>
                                        <!--
                                        <td>
                                            <?php
                                            $typeLabels = [
                                                'customer' => t('customer'), 
                                                'supplier' => t('supplier'), 
                                                'both' => t('both')
                                            ];
                                            echo $typeLabels[$account['account_type']] ?? $account['account_type'];
                                            ?>
                                        </td>
                                        -->
                                        <td><?php echo htmlspecialchars($account['mobile'] ?? '-'); ?></td>
                                        <td>
                                            <?php if ($account['balance_type'] == 'debit' && $account['opening_balance'] > 0): ?>
                                                <span class="badge bg-danger"><?php echo formatCurrency($account['opening_balance']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($account['balance_type'] == 'credit' && $account['opening_balance'] > 0): ?>
                                                <span class="badge bg-success"><?php echo formatCurrency($account['opening_balance']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>accounts/view.php?id=<?php echo $account['id']; ?>" class="btn btn-sm btn-info" title="<?php echo t('view'); ?>">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>accounts/edit.php?id=<?php echo $account['id']; ?>" class="btn btn-sm btn-warning ms-1" title="<?php echo t('edit'); ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger delete-account-btn ms-1" data-account-id="<?php echo $account['id']; ?>" data-account-name="<?php echo htmlspecialchars(displayAccountName($account)); ?>" title="<?php echo t('delete'); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end" style="background-color: #cfe2ff;"><strong><?php echo t('total'); ?>:</strong></td>
                                <td style="background-color: <?php echo $openingDebitTotal > 0 ? '#f8d7da' : '#cfe2ff'; ?>;">
                                    <strong><?php echo $openingDebitTotal > 0 ? formatCurrency($openingDebitTotal) : '-'; ?></strong>
                                </td>
                                <td style="background-color: <?php echo $openingCreditTotal > 0 ? '#d1e7dd' : '#cfe2ff'; ?>;">
                                    <strong><?php echo $openingCreditTotal > 0 ? formatCurrency($openingCreditTotal) : '-'; ?></strong>
                                </td>
                                <td style="background-color: #cfe2ff;"></td>
                            </tr>
                        </tfoot>
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

<!-- New Account Modal -->
<div class="modal fade" id="newAccountModal" tabindex="-1" aria-labelledby="newAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newAccountModalLabel">
                    <i class="fas fa-user-plus"></i> <?php echo t('new_account'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="accountFormMessage"></div>
                <form id="newAccountForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('account_code'); ?></label>
                            <input type="text" class="form-control" name="account_code" id="account_code" placeholder="<?php 
                                try {
                                    $stmt = $db->query("SELECT MAX(id) as max_id FROM accounts");
                                    $maxId = $stmt->fetch()['max_id'] ?? 0;
                                    $nextNumber = $maxId + 1;
                                    echo 'Acc' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
                                } catch (PDOException $e) {
                                    echo 'Acc01';
                                }
                            ?>">
                            <small class="text-muted"><?php echo t('leave_empty_for_auto'); ?></small>
                        </div>
                        
                        <!--
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('account_name'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="account_name" id="account_name" required>
                        </div>
                        -->
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('account_name_urdu'); ?></label>
                            <input type="text" class="form-control" name="account_name_urdu" id="account_name_urdu">
                        </div>
                        
                        <!-- Hidden field: Account type defaults to 'both' since accounts can be both customer and supplier -->
                        <input type="hidden" name="account_type" value="both">
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('contact_person'); ?></label>
                            <input type="text" class="form-control" name="contact_person" id="contact_person">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('phone'); ?></label>
                            <input type="text" class="form-control" name="phone" id="phone">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('mobile'); ?></label>
                            <input type="text" class="form-control" name="mobile" id="mobile">
                        </div>
                        
                        <!--
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('email'); ?></label>
                            <input type="email" class="form-control" name="email" id="email">
                        </div>
                        -->
                        
                        <!--
                        <div class="col-md-12 mb-3">
                            <label class="form-label"><?php echo t('address'); ?></label>
                            <textarea class="form-control" name="address" id="address" rows="3"></textarea>
                        </div>
                        -->
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('city'); ?></label>
                            <input type="text" class="form-control" name="city" id="city">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><?php echo t('opening_balance'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="opening_balance" id="opening_balance" placeholder="0">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><?php echo t('balance'); ?> <?php echo t('type'); ?></label>
                            <select class="form-select" name="balance_type" id="balance_type">
                                <option value="debit"><?php echo t('debit'); ?></option>
                                <option value="credit"><?php echo t('credit'); ?></option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                </button>
                <button type="button" class="btn btn-primary" onclick="saveNewAccount()">
                    <i class="fas fa-save"></i> <?php echo t('save'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function saveNewAccount() {
    const form = document.getElementById('newAccountForm');
    const formData = new FormData(form);
    const messageDiv = document.getElementById('accountFormMessage');
    
    // Clear previous messages
    messageDiv.innerHTML = '';
    
    // Validate required field
    // Account Name validation commented out as field is hidden
    // if (!formData.get('account_name').trim()) {
    //     messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('please_enter_account_name'); ?></div>';
    //     return;
    // }
    
    // Show loading
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo t('save'); ?>...';
    
    fetch('<?php echo BASE_URL; ?>accounts/create-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
            form.reset();
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('newAccountModal'));
                modal.hide();
                location.reload();
            }, 1500);
        } else {
            messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('error_adding_account'); ?></div>';
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

// Reset form when modal is closed
document.getElementById('newAccountModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('newAccountForm').reset();
    document.getElementById('accountFormMessage').innerHTML = '';
});

// Delete account functionality
document.querySelectorAll('.delete-account-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const accountId = this.getAttribute('data-account-id');
        const accountName = this.getAttribute('data-account-name');
        
        if (confirm('<?php echo t('are_you_sure_delete'); ?> "' + accountName + '"?')) {
            // Show loading
            this.disabled = true;
            const originalHtml = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            fetch('<?php echo BASE_URL; ?>accounts/delete-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + accountId
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
                    this.disabled = false;
                    this.innerHTML = originalHtml;
                }
            })
            .catch(error => {
                showNotification('<?php echo t('error_deleting_account'); ?>', 'error');
                this.disabled = false;
                this.innerHTML = originalHtml;
            });
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>

