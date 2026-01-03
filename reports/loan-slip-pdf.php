<?php
require_once '../config/config.php';
requireLogin();

// Helper function to get label based on current language setting
function getBilingualLabel($key) {
    global $translations;
    $currentLang = getLang(); // Get current language setting (ur or en)
    return $translations[$currentLang][$key] ?? $key;
}

$accountId = $_GET['account_id'] ?? '';
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
            
            $stmt = $db->prepare("SELECT * FROM transactions $where ORDER BY transaction_date DESC");
            $stmt->execute($params);
            $loans = $stmt->fetchAll();
            
            foreach ($loans as $loan) {
                if ($loan['transaction_type'] == 'debit') {
                    $totalLoan += $loan['amount'];
                } else {
                    $totalReturned += $loan['amount'];
                }
            }
            
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

// Check if TCPDF is available
$tcpdfPath = __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
if (!file_exists($tcpdfPath)) {
    // Try alternative path
    $tcpdfPath = __DIR__ . '/../tcpdf/tcpdf.php';
}

if (file_exists($tcpdfPath)) {
    require_once $tcpdfPath;
    
    // Create PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Business Management System');
    $pdf->SetAuthor('Yusuf & Co');
    $pdf->SetTitle('Loan Slip Report');
    $pdf->SetSubject('Loan Slip Report');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font - using DejaVu Sans for Unicode support (includes Urdu)
    $pdf->SetFont('dejavusans', '', 10);
    
    // Report Header
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 10, getBilingualLabel('loan_slip'), 0, 1, 'C');
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(0, 5, 'Business Management System - Yusuf & Co', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Date and Time
    $pdf->SetFont('dejavusans', '', 9);
    $generatedLabel = getBilingualLabel('generated') . ': ' . date('d-m-Y h:i A');
    $pdf->Cell(0, 5, $generatedLabel, 0, 1, 'R');
    
    // Date Range
    if (!empty($dateFrom) || !empty($dateTo)) {
        $dateRangeLabel = getBilingualLabel('date_range') . ': ';
        if (!empty($dateFrom)) {
            $dateRangeLabel .= getBilingualLabel('date_from') . ' ' . formatDate($dateFrom);
        }
        if (!empty($dateTo)) {
            if (!empty($dateFrom)) $dateRangeLabel .= ' ';
            $dateRangeLabel .= getBilingualLabel('date_to') . ' ' . formatDate($dateTo);
        }
        $pdf->Cell(0, 5, $dateRangeLabel, 0, 1, 'L');
    }
    
    $pdf->Ln(5);
    
    if ($viewAll && !empty($allAccountsLoans)) {
        // All Accounts View
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 8, getBilingualLabel('all_accounts_label_table') . ' - ' . getBilingualLabel('loan_slip'), 0, 1, 'L');
        $pdf->Ln(3);
        
        $pdf->SetFont('dejavusans', '', 9);
        
        foreach ($allAccountsLoans as $accId => $accData) {
            // Account Header
            $pdf->SetFillColor(230, 230, 230);
            $pdf->SetFont('dejavusans', 'B', 10);
            $accountName = displayAccountNameFull($accData['account']);
            $pdf->Cell(0, 7, getBilingualLabel('account') . ': ' . $accountName, 1, 1, 'L', true);
            
            if (!empty($accData['loans'])) {
                // Table Header
                $pdf->SetFont('dejavusans', 'B', 8);
                $pdf->SetFillColor(240, 240, 240);
                $pdf->Cell(30, 6, getBilingualLabel('date'), 1, 0, 'C', true);
                $pdf->Cell(25, 6, getBilingualLabel('type'), 1, 0, 'C', true);
                $pdf->Cell(40, 6, getBilingualLabel('amount'), 1, 0, 'C', true);
                $pdf->Cell(95, 6, getBilingualLabel('description'), 1, 1, 'C', true);
                
                $pdf->SetFont('dejavusans', '', 8);
                foreach ($accData['loans'] as $loan) {
                    $pdf->Cell(30, 6, formatDate($loan['transaction_date']), 1, 0, 'L');
                    $type = ($loan['transaction_type'] == 'debit') ? getBilingualLabel('loan_given') : getBilingualLabel('loan_returned');
                    $pdf->Cell(25, 6, $type, 1, 0, 'L');
                    $pdf->Cell(40, 6, formatCurrency($loan['amount']), 1, 0, 'R');
                    $pdf->Cell(95, 6, substr($loan['narration'] ?? '-', 0, 50), 1, 1, 'L');
                }
                
                // Account Summary
                $pdf->SetFont('dejavusans', 'B', 8);
                $pdf->SetFillColor(250, 250, 250);
                $accountTotalLabel = getBilingualLabel('account') . ' ' . getBilingualLabel('total') . ':';
                $pdf->Cell(55, 6, $accountTotalLabel, 1, 0, 'R', true);
                $pdf->SetFont('dejavusans', '', 8);
                $summaryText = getBilingualLabel('total_loan') . ': ' . formatCurrency($accData['totalLoan']) . 
                               ' | ' . getBilingualLabel('returned') . ': ' . formatCurrency($accData['totalReturned']) . 
                               ' | ' . getBilingualLabel('balance') . ': ' . formatCurrency($accData['totalLoan'] - $accData['totalReturned']);
                $pdf->Cell(135, 6, $summaryText, 1, 1, 'L');
            } else {
                $pdf->SetFont('dejavusans', '', 8);
                $pdf->Cell(0, 6, getBilingualLabel('no_loan_records'), 1, 1, 'C');
            }
            
            $pdf->Ln(3);
        }
        
        // Grand Total
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('dejavusans', 'B', 10);
        $grandTotalLabel = getBilingualLabel('grand_total') . ' ' . getBilingualLabel('details');
        $pdf->Cell(0, 8, $grandTotalLabel, 1, 1, 'C', true);
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->Cell(0, 6, getBilingualLabel('total_loan') . ': ' . formatCurrency($grandTotalLoan), 0, 1, 'L');
        $pdf->Cell(0, 6, getBilingualLabel('total') . ' ' . getBilingualLabel('returned') . ': ' . formatCurrency($grandTotalReturned), 0, 1, 'L');
        $pdf->Cell(0, 6, getBilingualLabel('total') . ' ' . getBilingualLabel('balance') . ': ' . formatCurrency($grandTotalLoan - $grandTotalReturned), 0, 1, 'L');
        $pdf->Cell(0, 6, getBilingualLabel('total') . ' ' . getBilingualLabel('accounts') . ': ' . count($allAccountsLoans), 0, 1, 'L');
        
    } elseif (!empty($accountId) && !empty($account) && !empty($loans)) {
        // Single Account View
        $pdf->SetFont('dejavusans', 'B', 12);
        $accountName = displayAccountNameFull($account);
        $pdf->Cell(0, 8, getBilingualLabel('account') . ': ' . $accountName, 0, 1, 'L');
        $pdf->Ln(3);
        
        // Table Header
        $pdf->SetFont('dejavusans', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(40, 7, getBilingualLabel('date'), 1, 0, 'C', true);
        $pdf->Cell(35, 7, getBilingualLabel('type'), 1, 0, 'C', true);
        $pdf->Cell(50, 7, getBilingualLabel('amount'), 1, 0, 'C', true);
        $pdf->Cell(65, 7, getBilingualLabel('description'), 1, 1, 'C', true);
        
        $pdf->SetFont('dejavusans', '', 8);
        foreach ($loans as $loan) {
            $pdf->Cell(40, 6, formatDate($loan['transaction_date']), 1, 0, 'L');
            $type = ($loan['transaction_type'] == 'debit') ? getBilingualLabel('loan_given') : getBilingualLabel('loan_returned');
            $pdf->Cell(35, 6, $type, 1, 0, 'L');
            $pdf->Cell(50, 6, formatCurrency($loan['amount']), 1, 0, 'R');
            $pdf->Cell(65, 6, substr($loan['narration'] ?? '-', 0, 40), 1, 1, 'L');
        }
        
        // Summary
        $pdf->SetFont('dejavusans', 'B', 9);
        $pdf->SetFillColor(250, 250, 250);
        $pdf->Cell(75, 7, getBilingualLabel('total') . ':', 1, 0, 'R', true);
        $pdf->SetFont('dejavusans', '', 9);
        $summaryText = getBilingualLabel('total_loan') . ': ' . formatCurrency($totalLoan) . 
                       ' | ' . getBilingualLabel('returned') . ': ' . formatCurrency($totalReturned) . 
                       ' | ' . getBilingualLabel('balance') . ': ' . formatCurrency($totalLoan - $totalReturned);
        $pdf->Cell(115, 7, $summaryText, 1, 1, 'L');
    } else {
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(0, 10, getBilingualLabel('no_loan_records'), 0, 1, 'C');
    }
    
    // Output PDF
    $filename = 'loan-slip-report-' . date('Y-m-d-His') . '.pdf';
    $pdf->Output($filename, 'D'); // 'D' for download
    
} else {
    // Fallback: HTML to PDF using browser print
    // This will work if TCPDF is not installed
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Loan Slip Report - Print</title>
        <style>
            @media print {
                body { margin: 0; padding: 20px; }
                .no-print { display: none; }
                @page { margin: 1cm; }
            }
            body {
                font-family: Arial, sans-serif;
                padding: 20px;
                direction: ltr;
            }
            .header {
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
            }
            .header h2 {
                margin: 5px 0;
                font-size: 16px;
                font-weight: normal;
            }
            .info {
                margin-bottom: 20px;
                text-align: right;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            table th, table td {
                border: 1px solid #000;
                padding: 8px;
                text-align: left;
            }
            table th {
                background-color: #f0f0f0;
                font-weight: bold;
            }
            .account-section {
                margin-bottom: 30px;
                page-break-inside: avoid;
            }
            .account-header {
                background-color: #e0e0e0;
                padding: 10px;
                font-weight: bold;
                border: 1px solid #000;
            }
            .summary {
                background-color: #f5f5f5;
                padding: 10px;
                border: 1px solid #000;
                margin-top: 10px;
            }
            .grand-total {
                background-color: #d0d0d0;
                padding: 15px;
                border: 2px solid #000;
                margin-top: 20px;
                text-align: center;
            }
            .btn-print {
                background-color: #007bff;
                color: white;
                padding: 10px 20px;
                border: none;
                cursor: pointer;
                margin-bottom: 20px;
            }
            .btn-print:hover {
                background-color: #0056b3;
            }
        </style>
    </head>
    <body>
        <div class="no-print">
            <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
        </div>
        
        <div class="header">
            <h1><?php echo getBilingualLabel('loan_slip'); ?></h1>
            <h2>Business Management System - Yusuf & Co</h2>
        </div>
        
        <div class="info">
            <strong><?php echo getBilingualLabel('generated'); ?>:</strong> <?php echo date('d-m-Y h:i A'); ?><br>
            <?php if (!empty($dateFrom) || !empty($dateTo)): ?>
                <strong><?php echo getBilingualLabel('date_range'); ?>:</strong> 
                <?php if (!empty($dateFrom)) echo getBilingualLabel('date_from') . ' ' . formatDate($dateFrom); ?>
                <?php if (!empty($dateTo)) echo ' ' . getBilingualLabel('date_to') . ' ' . formatDate($dateTo); ?>
            <?php endif; ?>
        </div>
        
        <?php if ($viewAll && !empty($allAccountsLoans)): ?>
            <h2><?php echo getBilingualLabel('all_accounts_label_table') . ' - ' . getBilingualLabel('loan_slip'); ?></h2>
            
            <?php foreach ($allAccountsLoans as $accId => $accData): ?>
                <div class="account-section">
                    <div class="account-header">
                        <strong><?php echo getBilingualLabel('account'); ?>:</strong> <?php echo htmlspecialchars(displayAccountNameFull($accData['account'])); ?>
                    </div>
                    
                    <?php if (!empty($accData['loans'])): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th><?php echo getBilingualLabel('date'); ?></th>
                                    <th><?php echo getBilingualLabel('type'); ?></th>
                                    <th><?php echo getBilingualLabel('amount'); ?></th>
                                    <th><?php echo getBilingualLabel('description'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($accData['loans'] as $loan): ?>
                                    <tr>
                                        <td><?php echo formatDate($loan['transaction_date']); ?></td>
                                        <td><?php echo ($loan['transaction_type'] == 'debit') ? getBilingualLabel('loan_given') : getBilingualLabel('loan_returned'); ?></td>
                                        <td><?php echo formatCurrency($loan['amount']); ?></td>
                                        <td><?php echo htmlspecialchars($loan['narration'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="summary">
                            <strong><?php echo getBilingualLabel('account') . ' ' . getBilingualLabel('total'); ?>:</strong> 
                            <?php echo getBilingualLabel('total_loan'); ?>: <?php echo formatCurrency($accData['totalLoan']); ?> | 
                            <?php echo getBilingualLabel('returned'); ?>: <?php echo formatCurrency($accData['totalReturned']); ?> | 
                            <?php echo getBilingualLabel('balance'); ?>: <?php echo formatCurrency($accData['totalLoan'] - $accData['totalReturned']); ?>
                        </div>
                    <?php else: ?>
                        <p><?php echo getBilingualLabel('no_loan_records'); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="grand-total">
                <h3><?php echo getBilingualLabel('grand_total') . ' ' . getBilingualLabel('details'); ?></h3>
                <p><strong><?php echo getBilingualLabel('total_loan'); ?>:</strong> <?php echo formatCurrency($grandTotalLoan); ?></p>
                <p><strong><?php echo getBilingualLabel('total') . ' ' . getBilingualLabel('returned'); ?>:</strong> <?php echo formatCurrency($grandTotalReturned); ?></p>
                <p><strong><?php echo getBilingualLabel('total') . ' ' . getBilingualLabel('balance'); ?>:</strong> <?php echo formatCurrency($grandTotalLoan - $grandTotalReturned); ?></p>
                <p><strong><?php echo getBilingualLabel('total') . ' ' . getBilingualLabel('accounts'); ?>:</strong> <?php echo count($allAccountsLoans); ?></p>
            </div>
            
        <?php elseif (!empty($accountId) && !empty($account) && !empty($loans)): ?>
            <h2><?php echo getBilingualLabel('account'); ?>: <?php echo htmlspecialchars(displayAccountNameFull($account)); ?></h2>
            
            <table>
                <thead>
                    <tr>
                        <th><?php echo getBilingualLabel('date'); ?></th>
                        <th><?php echo getBilingualLabel('type'); ?></th>
                        <th><?php echo getBilingualLabel('amount'); ?></th>
                        <th><?php echo getBilingualLabel('description'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td><?php echo formatDate($loan['transaction_date']); ?></td>
                            <td><?php echo ($loan['transaction_type'] == 'debit') ? getBilingualLabel('loan_given') : getBilingualLabel('loan_returned'); ?></td>
                            <td><?php echo formatCurrency($loan['amount']); ?></td>
                            <td><?php echo htmlspecialchars($loan['narration'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="summary">
                <strong><?php echo getBilingualLabel('total'); ?>:</strong> 
                <?php echo getBilingualLabel('total_loan'); ?>: <?php echo formatCurrency($totalLoan); ?> | 
                <?php echo getBilingualLabel('returned'); ?>: <?php echo formatCurrency($totalReturned); ?> | 
                <?php echo getBilingualLabel('balance'); ?>: <?php echo formatCurrency($totalLoan - $totalReturned); ?>
            </div>
        <?php else: ?>
            <p><?php echo getBilingualLabel('no_loan_records'); ?></p>
        <?php endif; ?>
    </body>
    </html>
    <?php
}
?>

