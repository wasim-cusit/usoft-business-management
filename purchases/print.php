<?php
require_once '../config/config.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);

if (empty($id)) {
    header('Location: ' . BASE_URL . 'purchases/list.php');
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT p.*, a.account_name, a.account_name_urdu, a.address, a.city, a.phone, a.mobile FROM purchases p 
                         LEFT JOIN accounts a ON p.account_id = a.id 
                         WHERE p.id = ?");
    $stmt->execute([$id]);
    $purchase = $stmt->fetch();
    
    if (!$purchase) {
        header('Location: ' . BASE_URL . 'purchases/list.php');
        exit;
    }
    
    // Get purchase items
    $stmt = $db->prepare("SELECT pi.*, i.item_name, i.item_name_urdu, i.unit FROM purchase_items pi 
                         LEFT JOIN items i ON pi.item_id = i.id 
                         WHERE pi.purchase_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    header('Location: ' . BASE_URL . 'purchases/list.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo getLang(); ?>" dir="<?php echo getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('purchase_details_title'); ?> - <?php echo htmlspecialchars($purchase['purchase_no']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media print {
            body { margin: 0; padding: 20px; }
            .no-print { display: none !important; }
            @page { margin: 1cm; }
            .print-btn { display: none !important; }
        }
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            direction: <?php echo getDir(); ?>;
        }
        [dir="rtl"] body {
            direction: rtl;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            font-weight: normal;
        }
        .company-info {
            text-align: center;
            margin-bottom: 20px;
        }
        .bill-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .bill-info-left, .bill-info-right {
            flex: 1;
            min-width: 300px;
        }
        [dir="rtl"] .bill-info {
            flex-direction: row-reverse;
        }
        .info-box {
            background: #f9f9f9;
            padding: 15px;
            border: 1px solid #ddd;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dotted #ccc;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            min-width: 120px;
        }
        [dir="rtl"] .info-row {
            flex-direction: row-reverse;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #000;
            padding: 10px;
            text-align: <?php echo getDir() == 'rtl' ? 'right' : 'left'; ?>;
        }
        table th {
            background-color: #333;
            color: white;
            font-weight: bold;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table tfoot {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .total-section {
            margin-top: 20px;
            text-align: <?php echo getDir() == 'rtl' ? 'left' : 'right'; ?>;
        }
        [dir="rtl"] .total-section {
            text-align: left;
        }
        .total-row {
            display: flex;
            justify-content: <?php echo getDir() == 'rtl' ? 'flex-start' : 'flex-end'; ?>;
            padding: 8px 0;
            font-size: 16px;
        }
        .total-label {
            min-width: 150px;
            font-weight: bold;
            text-align: <?php echo getDir() == 'rtl' ? 'left' : 'right'; ?>;
        }
        [dir="rtl"] .total-label {
            text-align: left;
        }
        .total-value {
            min-width: 120px;
            text-align: <?php echo getDir() == 'rtl' ? 'left' : 'right'; ?>;
        }
        [dir="rtl"] .total-value {
            text-align: left;
        }
        .grand-total {
            font-size: 20px;
            border-top: 3px solid #000;
            border-bottom: 3px solid #000;
            padding: 10px 0;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #000;
            text-align: center;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            <?php echo getDir() == 'rtl' ? 'left' : 'right'; ?>: 20px;
            z-index: 1000;
            padding: 10px 20px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .print-btn:hover {
            background: #c82333;
        }
        [dir="rtl"] .print-btn {
            left: 20px;
            right: auto;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">
        <i class="fas fa-print"></i> <?php echo t('print'); ?>
    </button>
    
    <div class="header">
        <h1><?php echo t('purchase_invoice'); ?></h1>
        <h2>Business Management System - Yusuf & Co</h2>
    </div>
    
    <div class="bill-info">
        <div class="bill-info-left">
            <div class="info-box">
                <h3><?php echo t('bill_info'); ?></h3>
                <div class="info-row">
                    <span class="info-label"><?php echo t('bill_no'); ?>:</span>
                    <span><?php echo htmlspecialchars($purchase['purchase_no']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?php echo t('date'); ?>:</span>
                    <span><?php echo formatDate($purchase['purchase_date']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="bill-info-right">
            <div class="info-box">
                <h3><?php echo t('supplier_info'); ?></h3>
                <div class="info-row">
                    <span class="info-label"><?php echo t('supplier'); ?>:</span>
                    <span><?php echo displayAccountName($purchase); ?></span>
                </div>
                <?php if (!empty($purchase['address'])): ?>
                <div class="info-row">
                    <span class="info-label"><?php echo t('address'); ?>:</span>
                    <span><?php echo htmlspecialchars($purchase['address']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($purchase['city'])): ?>
                <div class="info-row">
                    <span class="info-label"><?php echo t('city'); ?>:</span>
                    <span><?php echo htmlspecialchars($purchase['city']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($purchase['phone']) || !empty($purchase['mobile'])): ?>
                <div class="info-row">
                    <span class="info-label"><?php echo t('phone'); ?>:</span>
                    <span><?php echo htmlspecialchars($purchase['phone'] ?? $purchase['mobile'] ?? '-'); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;"><?php echo t('item_name'); ?></th>
                <th style="width: 15%;"><?php echo t('quantity'); ?></th>
                <th style="width: 15%;"><?php echo t('rate'); ?></th>
                <th style="width: 15%;"><?php echo t('amount'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="5" class="text-center"><?php echo t('no_records'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $index => $item): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo displayItemName($item); ?></td>
                        <td><?php echo formatNumber($item['quantity']) . ' ' . htmlspecialchars($item['unit']); ?></td>
                        <td><?php echo formatCurrency($item['rate']); ?></td>
                        <td><?php echo formatCurrency($item['amount']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align: <?php echo getDir() == 'rtl' ? 'left' : 'right'; ?>;"><strong><?php echo t('total'); ?>:</strong></td>
                <td><strong><?php echo formatCurrency($purchase['total_amount']); ?></strong></td>
            </tr>
        </tfoot>
    </table>
    
    <div class="total-section">
        <div class="total-row">
            <span class="total-label"><?php echo t('grand_total'); ?>:</span>
            <span class="total-value"><?php echo formatCurrency($purchase['total_amount']); ?></span>
        </div>
        <div class="total-row">
            <span class="total-label"><?php echo t('discount'); ?>:</span>
            <span class="total-value"><?php echo formatCurrency($purchase['discount']); ?></span>
        </div>
        <div class="total-row grand-total">
            <span class="total-label"><?php echo t('net_amount'); ?>:</span>
            <span class="total-value"><?php echo formatCurrency($purchase['net_amount']); ?></span>
        </div>
        <div class="total-row">
            <span class="total-label"><?php echo t('payment'); ?>:</span>
            <span class="total-value"><?php echo formatCurrency($purchase['paid_amount']); ?></span>
        </div>
        <div class="total-row">
            <span class="total-label"><?php echo t('balance'); ?>:</span>
            <span class="total-value"><?php echo formatCurrency($purchase['balance_amount']); ?></span>
        </div>
    </div>
    
    <?php if (!empty($purchase['remarks'])): ?>
    <div class="info-box" style="margin-top: 20px;">
        <h3><?php echo t('remarks'); ?></h3>
        <p><?php echo nl2br(htmlspecialchars($purchase['remarks'])); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="footer">
        <p><strong><?php echo t('generated'); ?>:</strong> <?php echo date('d-m-Y h:i A'); ?></p>
        <p><?php echo t('thank_you_message'); ?></p>
    </div>
    
    <script>
        // Auto-print when page loads (optional - can be removed if not needed)
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>

