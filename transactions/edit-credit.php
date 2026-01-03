<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'edit_credit';

$id = intval($_GET['id'] ?? 0);

if (empty($id)) {
    header('Location: ' . BASE_URL . 'transactions/credit.php');
    exit;
}

// Get accounts
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM accounts WHERE status = 'active' ORDER BY account_name");
    $accounts = $stmt->fetchAll();
    
    // Get transaction details
    $stmt = $db->prepare("SELECT * FROM transactions WHERE id = ? AND transaction_type = 'credit'");
    $stmt->execute([$id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        header('Location: ' . BASE_URL . 'transactions/credit.php');
        exit;
    }
} catch (PDOException $e) {
    header('Location: ' . BASE_URL . 'transactions/credit.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transactionNo = trim(sanitizeInput($_POST['transaction_no'] ?? ''));
    $transactionDate = $_POST['transaction_date'] ?? date('Y-m-d');
    $accountId = intval($_POST['account_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $narration = sanitizeInput($_POST['narration'] ?? '');
    
    if (empty($accountId)) {
        $error = t('please_select_account');
    } elseif ($amount <= 0) {
        $error = t('please_enter_amount');
    } else {
        try {
            $db = getDB();
            
            // Check if transaction number already exists (excluding current transaction)
            if (!empty($transactionNo)) {
                $stmt = $db->prepare("SELECT id FROM transactions WHERE transaction_no = ? AND id != ?");
                $stmt->execute([$transactionNo, $id]);
                if ($stmt->fetch()) {
                    $error = t('transaction_no_already_exists');
                } else {
                    // Update transaction
                    $stmt = $db->prepare("UPDATE transactions SET transaction_no = ?, transaction_date = ?, account_id = ?, amount = ?, narration = ? WHERE id = ?");
                    $stmt->execute([$transactionNo, $transactionDate, $accountId, $amount, $narration, $id]);
                    $success = t('transaction_updated_success');
                }
            } else {
                // Update transaction without changing transaction_no
                $stmt = $db->prepare("UPDATE transactions SET transaction_date = ?, account_id = ?, amount = ?, narration = ? WHERE id = ?");
                $stmt->execute([$transactionDate, $accountId, $amount, $narration, $id]);
                $success = t('transaction_updated_success');
            }
            
            if ($success) {
                header('Location: ' . BASE_URL . 'transactions/credit.php?success=' . urlencode($success));
                exit;
            }
        } catch (PDOException $e) {
            $error = t('error_updating_transaction');
        }
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-edit"></i> <?php echo t('edit'); ?> <?php echo t('credit'); ?></h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('edit'); ?> <?php echo t('credit'); ?> <?php echo t('transaction'); ?></h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><?php echo t('transaction_no'); ?></label>
                            <input type="text" class="form-control" name="transaction_no" value="<?php echo htmlspecialchars($transaction['transaction_no'] ?? ''); ?>" readonly>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><?php echo t('date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="transaction_date" value="<?php echo htmlspecialchars($transaction['transaction_date'] ?? date('Y-m-d')); ?>" required>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><?php echo t('select_account'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" name="account_id" required>
                                <option value="">-- <?php echo t('select'); ?> --</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>" <?php echo ($transaction['account_id'] == $account['id']) ? 'selected' : ''; ?>>
                                        <?php echo displayAccountNameFull($account); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><?php echo t('amount'); ?> <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="amount" value="<?php echo htmlspecialchars($transaction['amount'] ?? 0); ?>" required min="0.01">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('narration'); ?></label>
                        <textarea class="form-control" name="narration" rows="3"><?php echo htmlspecialchars($transaction['narration'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo t('update'); ?>
                        </button>
                        <a href="<?php echo BASE_URL; ?>transactions/credit.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

