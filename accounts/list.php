<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'customer_list';

$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Get user types for modal
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM user_types ORDER BY type_name");
    $userTypes = $stmt->fetchAll();
} catch (PDOException $e) {
    $userTypes = [];
}

try {
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
    padding: 15px 20px !important;
    font-size: 16px !important;
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
                        <h5 class="mb-0"><?php echo t('accounts'); ?></h5>
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
                                <th><?php echo t('name'); ?></th>
                                <th><?php echo t('name'); ?> (<?php echo t('urdu'); ?>)</th>
                                <th><?php echo t('type'); ?></th>
                                <th><?php echo t('user_types'); ?></th>
                                <th><?php echo t('mobile'); ?></th>
                                <th><?php echo t('opening_balance'); ?></th>
                                <th><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($accounts)): ?>
                                <tr>
                                    <td colspan="8" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($accounts as $account): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($account['account_code']); ?></td>
                                        <td><?php echo displayAccountName($account); ?></td>
                                        <td><?php echo getLang() == 'ur' ? htmlspecialchars($account['account_name'] ?? '') : htmlspecialchars($account['account_name_urdu'] ?? ''); ?></td>
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
                            <label class="form-label"><?php echo t('account_name'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="account_name" id="account_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('account_name_urdu'); ?></label>
                            <input type="text" class="form-control" name="account_name_urdu" id="account_name_urdu">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('account_type'); ?></label>
                            <select class="form-select" name="account_type" id="account_type">
                                <option value="customer"><?php echo t('customer'); ?></option>
                                <option value="supplier"><?php echo t('supplier'); ?></option>
                                <option value="both"><?php echo t('both'); ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('user_type'); ?></label>
                            <select class="form-select" name="user_type_id" id="user_type_id">
                                <option value="">-- <?php echo t('select'); ?> --</option>
                                <?php foreach ($userTypes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>">
                                        <?php echo displayTypeNameFull($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
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
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('email'); ?></label>
                            <input type="email" class="form-control" name="email" id="email">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label"><?php echo t('address'); ?></label>
                            <textarea class="form-control" name="address" id="address" rows="3"></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('city'); ?></label>
                            <input type="text" class="form-control" name="city" id="city">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><?php echo t('opening_balance'); ?></label>
                            <input type="number" step="0.01" class="form-control" name="opening_balance" id="opening_balance" value="0">
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
    if (!formData.get('account_name').trim()) {
        messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('please_enter_account_name'); ?></div>';
        return;
    }
    
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
</script>

<?php include '../includes/footer.php'; ?>

