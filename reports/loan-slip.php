<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'loan_slip';

$accountId = $_GET['account_id'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$viewAll = ($accountId === 'all');

// Get accounts
try {
    $db = getDB();
    $stmt = $db->query("SELECT id, account_name, account_name_urdu FROM accounts WHERE status = 'active' ORDER BY account_name");
    $accounts = $stmt->fetchAll();
} catch (PDOException $e) {
    $accounts = [];
}

$loans = [];
$allAccountsLoans = [];
$totalLoan = 0;
$totalReturned = 0;
$grandTotalLoan = 0;
$grandTotalReturned = 0;

if (!empty($accountId)) {
    try {
        if ($viewAll) {
            // Get loan transactions for all accounts
            $where = "WHERE 1=1";
            $params = [];
            
            if (!empty($dateFrom)) {
                $where .= " AND transaction_date >= ?";
                $params[] = $dateFrom;
            }
            
            if (!empty($dateTo)) {
                $where .= " AND transaction_date <= ?";
                $params[] = $dateTo;
            }
            
            // Get loan transactions grouped by account
            $stmt = $db->prepare("
                SELECT t.*, a.account_name, a.account_name_urdu 
                FROM transactions t 
                INNER JOIN accounts a ON t.account_id = a.id 
                $where 
                ORDER BY a.account_name, t.transaction_date DESC
            ");
            $stmt->execute($params);
            $allLoans = $stmt->fetchAll();
            
            // Group loans by account
            foreach ($allLoans as $loan) {
                $accId = $loan['account_id'];
                if (!isset($allAccountsLoans[$accId])) {
                    $allAccountsLoans[$accId] = [
                        'account' => [
                            'id' => $loan['account_id'],
                            'account_name' => $loan['account_name'],
                            'account_name_urdu' => $loan['account_name_urdu']
                        ],
                        'loans' => [],
                        'totalLoan' => 0,
                        'totalReturned' => 0
                    ];
                }
                
                $allAccountsLoans[$accId]['loans'][] = $loan;
                
                if ($loan['transaction_type'] == 'debit') {
                    $allAccountsLoans[$accId]['totalLoan'] += $loan['amount'];
                    $grandTotalLoan += $loan['amount'];
                } else {
                    $allAccountsLoans[$accId]['totalReturned'] += $loan['amount'];
                    $grandTotalReturned += $loan['amount'];
                }
            }
        } else {
            // Get loan transactions for single account
            $where = "WHERE account_id = ?";
            $params = [$accountId];
            
            if (!empty($dateFrom)) {
                $where .= " AND transaction_date >= ?";
                $params[] = $dateFrom;
            }
            
            if (!empty($dateTo)) {
                $where .= " AND transaction_date <= ?";
                $params[] = $dateTo;
            }
            
            // Get loan transactions (debit = loan given, credit = loan returned)
            // Note: In real system, you might want to use a specific transaction type or flag for loans
            $stmt = $db->prepare("SELECT * FROM transactions $where ORDER BY transaction_date DESC");
            $stmt->execute($params);
            $loans = $stmt->fetchAll();
            
            // Calculate totals
            foreach ($loans as $loan) {
                if ($loan['transaction_type'] == 'debit') {
                    $totalLoan += $loan['amount'];
                } else {
                    $totalReturned += $loan['amount'];
                }
            }
            
            // Get account info
            $stmt = $db->prepare("SELECT * FROM accounts WHERE id = ?");
            $stmt->execute([$accountId]);
            $account = $stmt->fetch();
        }
        
    } catch (PDOException $e) {
        $loans = [];
        $allAccountsLoans = [];
        $account = null;
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-hand-holding-usd"></i> <?php echo t('loan_slip'); ?></h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <form method="GET" class="row g-2">
                    <div class="col-md-4">
                        <?php /* Commented out label - user requested to hide this
                        <label class="form-label mb-1"><?php echo t('select_account'); ?></label>
                        */ ?>
                        <?php /* Commented out search input - user requested to remove this
                        <input type="text" class="form-control mb-2" id="accountSearch" placeholder="<?php echo t('search'); ?> <?php echo t('select_account'); ?>..." autocomplete="off">
                        */ ?>
                        <select class="form-select" name="account_id" id="accountSelect" required>
                            <option value="">-- <?php echo t('please_select_account'); ?> --</option>
                            <option value="all" <?php echo $viewAll ? 'selected' : ''; ?>>
                                -- <?php echo t('all_accounts_label_table'); ?> --
                            </option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?php echo $acc['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars(strtolower($acc['account_name'] ?? '')); ?>"
                                    data-name-urdu="<?php echo htmlspecialchars(strtolower($acc['account_name_urdu'] ?? '')); ?>"
                                    <?php echo $accountId == $acc['id'] ? 'selected' : ''; ?>>
                                    <?php echo displayAccountNameFull($acc); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="<?php echo t('date_from'); ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="<?php echo t('date_to'); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> <?php echo t('view'); ?>
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <?php if ($viewAll && !empty($allAccountsLoans)): ?>
                    <!-- Display all accounts loan details -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0"><i class="fas fa-list"></i> <?php echo t('all_accounts_label_table'); ?></h4>
                            <a href="loan-slip-pdf.php?account_id=all<?php echo !empty($dateFrom) ? '&date_from=' . urlencode($dateFrom) : ''; ?><?php echo !empty($dateTo) ? '&date_to=' . urlencode($dateTo) : ''; ?>" 
                               class="btn btn-danger" target="_blank">
                                <i class="fas fa-file-pdf"></i> <?php echo t('export_pdf'); ?>
                            </a>
                        </div>
                        
                        <?php foreach ($allAccountsLoans as $accId => $accData): ?>
                            <div class="card mb-4 border">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user"></i> <?php echo displayAccountNameFull($accData['account']); ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($accData['loans'])): ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm">
                                                <thead>
                                                    <tr>
                                                        <th><?php echo t('date'); ?></th>
                                                        <th><?php echo t('type'); ?></th>
                                                        <th><?php echo t('amount'); ?></th>
                                                        <th><?php echo t('description'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($accData['loans'] as $loan): ?>
                                                        <tr>
                                                            <td><?php echo formatDate($loan['transaction_date']); ?></td>
                                                            <td>
                                                                <?php if ($loan['transaction_type'] == 'debit'): ?>
                                                                    <span class="badge bg-danger"><?php echo t('loan_given'); ?></span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-success"><?php echo t('loan_returned'); ?></span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo formatCurrency($loan['amount']); ?></td>
                                                            <td><?php echo htmlspecialchars($loan['narration'] ?? '-'); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="bg-light">
                                                        <td colspan="2"><strong><?php echo t('total'); ?>:</strong></td>
                                                        <td colspan="2">
                                                            <strong><?php echo t('total_loan'); ?>:</strong> <?php echo formatCurrency($accData['totalLoan']); ?> | 
                                                            <strong><?php echo t('returned'); ?>:</strong> <?php echo formatCurrency($accData['totalReturned']); ?> | 
                                                            <strong><?php echo t('balance'); ?>:</strong> <?php echo formatCurrency($accData['totalLoan'] - $accData['totalReturned']); ?>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        
                                        <!-- Account Summary -->
                                        <div class="row mt-3">
                                            <div class="col-md-3">
                                                <div class="text-center p-2 bg-danger bg-opacity-10 rounded">
                                                    <h6 class="text-muted mb-1"><?php echo t('total_loan'); ?></h6>
                                                    <h5 class="text-danger mb-0"><?php echo formatCurrency($accData['totalLoan']); ?></h5>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center p-2 bg-success bg-opacity-10 rounded">
                                                    <h6 class="text-muted mb-1"><?php echo t('returned'); ?></h6>
                                                    <h5 class="text-success mb-0"><?php echo formatCurrency($accData['totalReturned']); ?></h5>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center p-2 bg-warning bg-opacity-10 rounded">
                                                    <h6 class="text-muted mb-1"><?php echo t('balance'); ?></h6>
                                                    <h5 class="text-warning mb-0"><?php echo formatCurrency($accData['totalLoan'] - $accData['totalReturned']); ?></h5>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center p-2 bg-info bg-opacity-10 rounded">
                                                    <h6 class="text-muted mb-1"><?php echo t('transactions'); ?></h6>
                                                    <h5 class="text-info mb-0"><?php echo count($accData['loans']); ?></h5>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle"></i> <?php echo t('no_loan_records'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Grand Total Summary -->
                        <div class="card bg-primary bg-opacity-10 border-primary mt-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><i class="fas fa-calculator"></i> <?php echo t('total'); ?> <?php echo t('details'); ?></h5>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="text-center p-3 bg-danger bg-opacity-10 rounded">
                                            <h6 class="text-muted mb-2"><?php echo t('total'); ?> <?php echo t('total_loan'); ?></h6>
                                            <h4 class="text-danger mb-0"><?php echo formatCurrency($grandTotalLoan); ?></h4>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                                            <h6 class="text-muted mb-2"><?php echo t('total'); ?> <?php echo t('returned'); ?></h6>
                                            <h4 class="text-success mb-0"><?php echo formatCurrency($grandTotalReturned); ?></h4>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center p-3 bg-warning bg-opacity-10 rounded">
                                            <h6 class="text-muted mb-2"><?php echo t('total'); ?> <?php echo t('balance'); ?></h6>
                                            <h4 class="text-warning mb-0"><?php echo formatCurrency($grandTotalLoan - $grandTotalReturned); ?></h4>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center p-3 bg-info bg-opacity-10 rounded">
                                            <h6 class="text-muted mb-2"><?php echo t('total'); ?> <?php echo t('accounts'); ?></h6>
                                            <h4 class="text-info mb-0"><?php echo count($allAccountsLoans); ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($viewAll && empty($allAccountsLoans)): ?>
                    <div class="alert alert-info no-auto-hide">
                        <i class="fas fa-info-circle"></i> <?php echo t('no_loan_records'); ?>
                    </div>
                <?php elseif (!empty($accountId) && !empty($account)): ?>
                    <?php /* Commented out alert - user requested to hide this
                    <div class="alert alert-info mb-4 no-auto-hide">
                        <h5><strong><?php echo t('select_account'); ?>:</strong> <?php echo displayAccountNameFull($account); ?></h5>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <strong><?php echo t('total_loan'); ?>:</strong> 
                                <span class="badge bg-danger"><?php echo formatCurrency($totalLoan); ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong><?php echo t('returned'); ?>:</strong> 
                                <span class="badge bg-success"><?php echo formatCurrency($totalReturned); ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong><?php echo t('balance'); ?>:</strong> 
                                <span class="badge bg-warning"><?php echo formatCurrency($totalLoan - $totalReturned); ?></span>
                            </div>
                        </div>
                    </div>
                    */ ?>
                    
                    <?php if (!empty($loans)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><?php echo t('date'); ?></th>
                                        <th><?php echo t('type'); ?></th>
                                        <th><?php echo t('amount'); ?></th>
                                        <th><?php echo t('description'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loans as $loan): ?>
                                        <tr>
                                            <td><?php echo formatDate($loan['transaction_date']); ?></td>
                                            <td>
                                                <?php if ($loan['transaction_type'] == 'debit'): ?>
                                                    <span class="badge bg-danger"><?php echo t('loan_given'); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><?php echo t('loan_returned'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatCurrency($loan['amount']); ?></td>
                                            <td><?php echo htmlspecialchars($loan['narration'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light">
                                        <td colspan="2"><strong><?php echo t('total'); ?>:</strong></td>
                                        <td colspan="2">
                                            <strong><?php echo t('total_loan'); ?>:</strong> <?php echo formatCurrency($totalLoan); ?> | 
                                            <strong><?php echo t('returned'); ?>:</strong> <?php echo formatCurrency($totalReturned); ?> | 
                                            <strong><?php echo t('balance'); ?>:</strong> <?php echo formatCurrency($totalLoan - $totalReturned); ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <!-- Summary Totals Section -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3"><i class="fas fa-calculator"></i> <?php echo t('details'); ?></h5>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="text-center p-3 bg-danger bg-opacity-10 rounded">
                                                    <h6 class="text-muted mb-2"><?php echo t('total_loan'); ?></h6>
                                                    <h4 class="text-danger mb-0"><?php echo formatCurrency($totalLoan); ?></h4>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                                                    <h6 class="text-muted mb-2"><?php echo t('returned'); ?></h6>
                                                    <h4 class="text-success mb-0"><?php echo formatCurrency($totalReturned); ?></h4>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center p-3 bg-warning bg-opacity-10 rounded">
                                                    <h6 class="text-muted mb-2"><?php echo t('balance'); ?></h6>
                                                    <h4 class="text-warning mb-0"><?php echo formatCurrency($totalLoan - $totalReturned); ?></h4>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center p-3 bg-info bg-opacity-10 rounded">
                                                    <h6 class="text-muted mb-2"><?php echo t('total'); ?> <?php echo t('transactions'); ?></h6>
                                                    <h4 class="text-info mb-0"><?php echo count($loans); ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info no-auto-hide">
                            <i class="fas fa-info-circle"></i> <?php echo t('no_loan_records'); ?>
                        </div>
                    <?php endif; ?>
                <?php /* Commented out warning alert - user requested to hide this
                else: ?>
                    <div class="alert alert-warning no-auto-hide">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo t('please_select_account'); ?>
                    </div>
                */ ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Prevent auto-hide for alerts with no-auto-hide class
document.addEventListener('DOMContentLoaded', function() {
    // Override auto-hide for specific alerts
    $('.no-auto-hide').off('fadeOut');
    
    // Account search filter with enhanced functionality (commented out - search input removed)
    const accountSearch = document.getElementById('accountSearch');
    const accountSelect = document.getElementById('accountSelect');
    
    // Only run search filter if search input exists
    if (accountSearch && accountSelect) {
        // Enhanced search filter - filters options as you type
        accountSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const options = accountSelect.querySelectorAll('option');
            let visibleCount = 0;
            
            options.forEach(function(option) {
                // Always show the placeholder option
                if (option.value === '') {
                    option.style.display = '';
                    return;
                }
                
                const name = option.getAttribute('data-name') || '';
                const nameUrdu = option.getAttribute('data-name-urdu') || '';
                const text = option.textContent.toLowerCase();
                
                // Check if search term matches any part of the account name
                if (searchTerm === '' || 
                    name.includes(searchTerm) || 
                    nameUrdu.includes(searchTerm) || 
                    text.includes(searchTerm)) {
                    option.style.display = '';
                    visibleCount++;
                } else {
                    option.style.display = 'none';
                }
            });
            
            // If only one option visible (besides placeholder), auto-select it
            if (visibleCount === 1 && searchTerm !== '') {
                const visibleOption = Array.from(options).find(opt => 
                    opt.value !== '' && opt.style.display !== 'none'
                );
                if (visibleOption) {
                    accountSelect.value = visibleOption.value;
                }
            }
        });
        
        // Keep search value for reference (don't clear on selection)
        accountSelect.addEventListener('change', function() {
            // Search stays visible for user reference
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>

