<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'daily_book';

$dateFrom = $_GET['date_from'] ?? date('Y-m-d');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

try {
    $db = getDB();
    
    // Get account summaries for daily book
    $stmt = $db->prepare("SELECT 
        a.id,
        a.account_name,
        a.account_name_urdu,
        a.mobile,
        a.phone,
        COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) as total_debit,
        COALESCE(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0) as total_credit,
        COALESCE(SUM(CASE WHEN p.id IS NOT NULL THEN p.net_amount ELSE 0 END), 0) as purchase_amount,
        COALESCE(SUM(CASE WHEN s.id IS NOT NULL THEN s.net_amount ELSE 0 END), 0) as sale_amount
        FROM accounts a
        LEFT JOIN transactions t ON a.id = t.account_id AND t.transaction_date BETWEEN ? AND ?
        LEFT JOIN purchases p ON a.id = p.account_id AND p.purchase_date BETWEEN ? AND ?
        LEFT JOIN sales s ON a.id = s.account_id AND s.sale_date BETWEEN ? AND ?
        WHERE a.status = 'active'
        GROUP BY a.id
        HAVING total_debit > 0 OR total_credit > 0 OR purchase_amount > 0 OR sale_amount > 0
        ORDER BY a.account_name");
    $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo]);
    $accountSummaries = $stmt->fetchAll();
    
    // Calculate balances
    foreach ($accountSummaries as &$acc) {
        $acc['debit'] = $acc['total_debit'] + $acc['purchase_amount'];
        $acc['credit'] = $acc['total_credit'] + $acc['sale_amount'];
        $acc['balance'] = $acc['debit'] - $acc['credit'];
    }
    
} catch (PDOException $e) {
    $accountSummaries = [];
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-book-open"></i> <?php echo t('daily_book'); ?></h1>
        <form method="GET" class="d-flex gap-2">
            <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" required>
            <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" required>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> <?php echo t('check'); ?>
            </button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('daily_book'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><?php echo t('account_name'); ?></th>
                                <th class="text-end"><?php echo t('debit'); ?></th>
                                <th class="text-end"><?php echo t('credit'); ?></th>
                                <th class="text-end"><?php echo t('balance'); ?></th>
                                <th><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalDebit = 0;
                            $totalCredit = 0;
                            $totalBalance = 0;
                            
                            if (empty($accountSummaries)): ?>
                                <tr>
                                    <td colspan="5" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($accountSummaries as $acc): 
                                    $totalDebit += $acc['debit'];
                                    $totalCredit += $acc['credit'];
                                    $totalBalance += $acc['balance'];
                                    $mobile = $acc['mobile'] ?? $acc['phone'] ?? '';
                                ?>
                                    <tr>
                                        <td><?php echo displayAccountNameFull($acc); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($acc['debit']); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($acc['credit']); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($acc['balance']); ?></td>
                                        <td>
                                            <?php if (!empty($mobile)): ?>
                                                <button type="button" class="btn btn-info btn-sm send-sms-btn" 
                                                        data-account-id="<?php echo $acc['id']; ?>"
                                                        data-mobile="<?php echo htmlspecialchars($mobile); ?>"
                                                        data-account-name="<?php echo htmlspecialchars(displayAccountNameFull($acc)); ?>"
                                                        data-balance="<?php echo $acc['balance']; ?>">
                                                    <i class="fas fa-sms"></i> <?php echo t('send_sms'); ?>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted"><?php echo t('mobile') . ' ' . t('not_found'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-info">
                                    <td><strong><?php echo t('total'); ?></strong></td>
                                    <td class="text-end"><strong><?php echo formatCurrency($totalDebit); ?></strong></td>
                                    <td class="text-end"><strong><?php echo formatCurrency($totalCredit); ?></strong></td>
                                    <td class="text-end"><strong><?php echo formatCurrency($totalBalance); ?></strong></td>
                                    <td></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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

