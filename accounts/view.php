<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'account_details';

$id = intval($_GET['id'] ?? 0);

if (empty($id)) {
    header('Location: ' . BASE_URL . 'accounts/list.php');
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT a.*, ut.type_name_urdu as user_type_name FROM accounts a 
                         LEFT JOIN user_types ut ON a.user_type_id = ut.id 
                         WHERE a.id = ?");
    $stmt->execute([$id]);
    $account = $stmt->fetch();
    
    if (!$account) {
        header('Location: ' . BASE_URL . 'accounts/list.php');
        exit;
    }
    
    // Get account balance
    $stmt = $db->prepare("SELECT 
        COALESCE(SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END), 0) as total_debit,
        COALESCE(SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END), 0) as total_credit
        FROM transactions WHERE account_id = ?");
    $stmt->execute([$id]);
    $trans = $stmt->fetch();
    
    $balance = $account['opening_balance'];
    if ($account['balance_type'] == 'credit') {
        $balance = -$balance;
    }
    $balance += $trans['total_debit'] - $trans['total_credit'];
    
} catch (PDOException $e) {
    header('Location: ' . BASE_URL . 'accounts/list.php');
    exit;
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-user"></i> <?php echo t('account_details'); ?></h1>
        <div>
            <a href="<?php echo BASE_URL; ?>accounts/edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> <?php echo t('edit'); ?>
            </a>
            <a href="<?php echo BASE_URL; ?>accounts/list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> <?php echo t('back'); ?>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('basic_info'); ?></h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 40%;"><?php echo t('account_code'); ?></th>
                        <td><?php echo htmlspecialchars($account['account_code']); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('account_name_required'); ?></th>
                        <td><?php echo displayAccountName($account); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('account_name_required'); ?> (<?php echo getLang() == 'ur' ? t('english') : t('urdu'); ?>)</th>
                        <td><?php echo getLang() == 'ur' ? htmlspecialchars($account['account_name'] ?? '-') : htmlspecialchars($account['account_name_urdu'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('account_type'); ?></th>
                        <td>
                            <?php
                            $typeLabels = ['customer' => t('customer'), 'supplier' => t('supplier'), 'both' => t('both')];
                            echo $typeLabels[$account['account_type']] ?? $account['account_type'];
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo t('user_types'); ?></th>
                        <td><?php echo htmlspecialchars($account['user_type_name'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('contact_person'); ?></th>
                        <td><?php echo htmlspecialchars($account['contact_person'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('phone'); ?></th>
                        <td><?php echo htmlspecialchars($account['phone'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('mobile'); ?></th>
                        <td><?php echo htmlspecialchars($account['mobile'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('email'); ?></th>
                        <td><?php echo htmlspecialchars($account['email'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('address'); ?></th>
                        <td><?php echo htmlspecialchars($account['address'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('city'); ?></th>
                        <td><?php echo htmlspecialchars($account['city'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('opening_balance'); ?></th>
                        <td>
                            <span class="badge <?php echo $account['balance_type'] == 'debit' ? 'bg-danger' : 'bg-success'; ?>">
                                <?php echo formatCurrency($account['opening_balance']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo t('current_balance'); ?></th>
                        <td>
                            <span class="badge <?php echo $balance >= 0 ? 'bg-danger' : 'bg-success'; ?>">
                                <?php echo formatCurrency(abs($balance)); ?>
                                <?php echo $balance >= 0 ? '(' . t('debit') . ')' : '(' . t('credit') . ')'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo t('status'); ?></th>
                        <td>
                            <span class="badge <?php echo $account['status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo $account['status'] == 'active' ? t('active') : t('inactive'); ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('quick_links'); ?></h5>
            </div>
            <div class="card-body">
                <a href="<?php echo BASE_URL; ?>reports/party-ledger.php?account_id=<?php echo $id; ?>" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-file-invoice"></i> <?php echo t('party_ledger'); ?>
                </a>
                <a href="<?php echo BASE_URL; ?>purchases/create.php?account_id=<?php echo $id; ?>" class="btn btn-info w-100 mb-2 text-white">
                    <i class="fas fa-shopping-cart"></i> <?php echo t('add_purchase'); ?>
                </a>
                <a href="<?php echo BASE_URL; ?>sales/create.php?account_id=<?php echo $id; ?>" class="btn btn-success w-100 mb-2">
                    <i class="fas fa-cash-register"></i> <?php echo t('add_sale'); ?>
                </a>
                <?php if (!empty($account['mobile']) || !empty($account['phone'])): ?>
                    <button type="button" class="btn btn-info w-100 mb-2 send-sms-btn" 
                            data-account-id="<?php echo $id; ?>"
                            data-mobile="<?php echo htmlspecialchars($account['mobile'] ?? $account['phone'] ?? ''); ?>"
                            data-account-name="<?php echo htmlspecialchars(displayAccountNameFull($account)); ?>"
                            data-balance="<?php echo $balance; ?>">
                        <i class="fas fa-sms"></i> <?php echo t('send_sms'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- SMS Modal -->
<div class="modal fade" id="smsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo t('send_sms'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="smsForm">
                    <input type="hidden" id="sms_account_id" name="account_id">
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('mobile'); ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sms_mobile" name="mobile" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('message'); ?></label>
                        <textarea class="form-control" id="sms_message" name="message" rows="4"></textarea>
                        <small class="text-muted"><?php echo t('leave_empty_for_balance_sms'); ?></small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                <button type="button" class="btn btn-primary" id="sendSMSBtn"><?php echo t('send_sms'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const smsModal = new bootstrap.Modal(document.getElementById('smsModal'));
    const sendSMSBtn = document.getElementById('sendSMSBtn');
    
    document.querySelectorAll('.send-sms-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const accountId = this.getAttribute('data-account-id');
            const mobile = this.getAttribute('data-mobile');
            const accountName = this.getAttribute('data-account-name');
            const balance = this.getAttribute('data-balance');
            
            document.getElementById('sms_account_id').value = accountId;
            document.getElementById('sms_mobile').value = mobile;
            document.getElementById('sms_message').value = '';
            
            smsModal.show();
        });
    });
    
    sendSMSBtn.addEventListener('click', function() {
        const form = document.getElementById('smsForm');
        const formData = new FormData(form);
        formData.append('type', 'balance');
        
        sendSMSBtn.disabled = true;
        sendSMSBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo t('sending'); ?>...';
        
        fetch('<?php echo BASE_URL; ?>api/send-sms.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                smsModal.hide();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            alert('<?php echo t('error'); ?>: ' + error);
        })
        .finally(() => {
            sendSMSBtn.disabled = false;
            sendSMSBtn.innerHTML = '<?php echo t('send_sms'); ?>';
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>

