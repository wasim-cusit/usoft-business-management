<?php
require_once '../../config/config.php';
requireLogin();

$pageTitle = 'کیش جمع';
$success = '';
$error = '';

// Get accounts
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM accounts WHERE status = 'active' ORDER BY account_name");
    $accounts = $stmt->fetchAll();
} catch (PDOException $e) {
    $accounts = [];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transactionDate = $_POST['transaction_date'] ?? date('Y-m-d');
    $accountId = intval($_POST['account_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $narration = sanitizeInput($_POST['narration'] ?? '');
    
    if (empty($accountId)) {
        $error = 'براہ کرم اکاؤنٹ منتخب کریں';
    } elseif ($amount <= 0) {
        $error = 'رقم درست درج کریں';
    } else {
        try {
            $db->beginTransaction();
            
            // Generate transaction number
            $stmt = $db->query("SELECT MAX(id) as max_id FROM transactions");
            $maxId = $stmt->fetch()['max_id'] ?? 0;
            $transactionNo = generateCode('CRD', $maxId);
            
            // Insert transaction
            $stmt = $db->prepare("INSERT INTO transactions (transaction_no, transaction_date, transaction_type, account_id, amount, narration, created_by) VALUES (?, ?, 'credit', ?, ?, ?, ?)");
            $stmt->execute([$transactionNo, $transactionDate, $accountId, $amount, $narration, $_SESSION['user_id']]);
            
            $db->commit();
            $success = 'کیش جمع کامیابی سے ریکارڈ ہو گیا';
            $_POST = [];
        } catch (PDOException $e) {
            $db->rollBack();
            $error = 'لین دین ریکارڈ کرنے میں خرابی';
        }
    }
}

include '../../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-arrow-up"></i> کیش جمع</h1>
</div>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">کیش جمع کی معلومات</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">تاریخ <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="transaction_date" value="<?php echo $_POST['transaction_date'] ?? date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">اکاؤنٹ <span class="text-danger">*</span></label>
                        <select class="form-select" name="account_id" required>
                            <option value="">-- منتخب کریں --</option>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>" <?php echo (($_POST['account_id'] ?? '') == $account['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($account['account_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">رقم <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" name="amount" value="<?php echo $_POST['amount'] ?? ''; ?>" required min="0.01">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">تفصیل</label>
                        <textarea class="form-control" name="narration" rows="3"><?php echo $_POST['narration'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-save"></i> محفوظ کریں
                        </button>
                        <a href="<?php echo BASE_URL; ?>transactions/list.php" class="btn btn-secondary btn-lg w-100 mt-2">
                            <i class="fas fa-list"></i> فہرست دیکھیں
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
