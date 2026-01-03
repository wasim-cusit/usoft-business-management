<?php
/**
 * Dummy Data Seeder
 * This script populates the database with sample data for testing and understanding the system
 * 
 * Usage: Run this file directly in browser or via command line: php seed-dummy-data.php
 */

require_once 'config/config.php';

// Set timezone
date_default_timezone_set('Asia/Karachi');

echo "<h2>Seeding Dummy Data...</h2>";
echo "<pre>";

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Get admin user ID
    $stmt = $db->query("SELECT id FROM users WHERE username = 'adil' LIMIT 1");
    $adminUser = $stmt->fetch();
    $adminUserId = $adminUser['id'] ?? 1;
    
    echo "✓ Admin user found (ID: $adminUserId)\n";
    
    // 1. User Types (if not exists)
    echo "\n1. Adding User Types...\n";
    $userTypes = [
        ['Customer', 'کسٹمر', 'Regular customer type'],
        ['Supplier', 'سپلائر', 'Supplier type'],
        ['Both', 'دونوں', 'Both customer and supplier'],
        ['Retailer', 'ریٹیلر', 'Retail customer'],
        ['Wholesaler', 'ہول سیلر', 'Wholesale customer']
    ];
    
    foreach ($userTypes as $type) {
        $stmt = $db->prepare("INSERT IGNORE INTO user_types (type_name, type_name_urdu, description) VALUES (?, ?, ?)");
        $stmt->execute($type);
    }
    echo "✓ User types added\n";
    
    // Get user type IDs
    $stmt = $db->query("SELECT id, type_name FROM user_types");
    $userTypeMap = [];
    while ($row = $stmt->fetch()) {
        $userTypeMap[$row['type_name']] = $row['id'];
    }
    
    // 2. Additional Users
    echo "\n2. Adding Users...\n";
    $users = [
        ['manager', password_hash('manager123', PASSWORD_DEFAULT), 'Manager User', 'manager@example.com', 'admin'],
        ['staff1', password_hash('staff123', PASSWORD_DEFAULT), 'Staff Member 1', 'staff1@example.com', 'user'],
        ['staff2', password_hash('staff123', PASSWORD_DEFAULT), 'Staff Member 2', 'staff2@example.com', 'user']
    ];
    
    foreach ($users as $user) {
        $stmt = $db->prepare("INSERT IGNORE INTO users (username, password, full_name, email, user_type, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $stmt->execute($user);
    }
    echo "✓ Users added\n";
    
    // 3. Accounts (Customers & Suppliers)
    echo "\n3. Adding Accounts...\n";
    $accounts = [
        // Customers
        ['CUST001', 'Ahmed Traders', 'احمد ٹریڈرز', 'customer', $userTypeMap['Customer'] ?? 1, 'Ahmed Khan', '021-1234567', '0300-1234567', 'ahmed@example.com', 'Shop 5, Main Market, Karachi', 'Karachi', 50000, 'debit'],
        ['CUST002', 'Hassan Store', 'حسن اسٹور', 'customer', $userTypeMap['Retailer'] ?? 1, 'Hassan Ali', '021-2345678', '0301-2345678', 'hassan@example.com', 'Shop 12, Clifton, Karachi', 'Karachi', 30000, 'debit'],
        ['CUST003', 'Ali Enterprises', 'علی انٹرپرائزز', 'customer', $userTypeMap['Wholesaler'] ?? 1, 'Ali Ahmed', '021-3456789', '0302-3456789', 'ali@example.com', 'Plot 45, Industrial Area, Lahore', 'Lahore', 75000, 'debit'],
        ['CUST004', 'Zain Trading', 'زین ٹریڈنگ', 'customer', $userTypeMap['Customer'] ?? 1, 'Zain Malik', '042-1234567', '0303-4567890', 'zain@example.com', 'Shop 8, Model Town, Lahore', 'Lahore', 25000, 'credit'],
        ['CUST005', 'Usman & Co', 'عثمان اینڈ کو', 'customer', $userTypeMap['Retailer'] ?? 1, 'Usman Sheikh', '021-4567890', '0304-5678901', 'usman@example.com', 'Shop 20, Saddar, Karachi', 'Karachi', 40000, 'debit'],
        
        // Suppliers
        ['SUPP001', 'ABC Suppliers', 'اے بی سی سپلائرز', 'supplier', $userTypeMap['Supplier'] ?? 2, 'John Smith', '021-5678901', '0305-6789012', 'john@abc.com', 'Warehouse 1, Port Area, Karachi', 'Karachi', 0, 'credit'],
        ['SUPP002', 'XYZ Trading Company', 'ایکس وائی زیڈ ٹریڈنگ کمپنی', 'supplier', $userTypeMap['Supplier'] ?? 2, 'David Brown', '042-2345678', '0306-7890123', 'david@xyz.com', 'Plot 100, Ferozepur Road, Lahore', 'Lahore', 0, 'credit'],
        ['SUPP003', 'Global Imports', 'گلوبل امپورٹس', 'supplier', $userTypeMap['Supplier'] ?? 2, 'Michael Wilson', '021-6789012', '0307-8901234', 'michael@global.com', 'Building 5, I.I Chundrigar Road, Karachi', 'Karachi', 0, 'credit'],
        
        // Both
        ['BOTH001', 'Multi Trade', 'ملٹی ٹریڈ', 'both', $userTypeMap['Both'] ?? 3, 'Sarah Khan', '042-3456789', '0308-9012345', 'sarah@multi.com', 'Shop 15, Gulberg, Lahore', 'Lahore', 10000, 'debit']
    ];
    
    $accountIds = [];
    foreach ($accounts as $account) {
        $stmt = $db->prepare("INSERT IGNORE INTO accounts (account_code, account_name, account_name_urdu, account_type, user_type_id, contact_person, phone, mobile, email, address, city, opening_balance, balance_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute($account);
        $accountIds[] = $db->lastInsertId();
    }
    echo "✓ Accounts added (" . count($accounts) . " accounts)\n";
    
    // Get account IDs
    $stmt = $db->query("SELECT id, account_code, account_type FROM accounts WHERE status = 'active' ORDER BY id");
    $allAccounts = $stmt->fetchAll();
    $customerIds = [];
    $supplierIds = [];
    foreach ($allAccounts as $acc) {
        if (in_array($acc['account_type'], ['customer', 'both'])) {
            $customerIds[] = $acc['id'];
        }
        if (in_array($acc['account_type'], ['supplier', 'both'])) {
            $supplierIds[] = $acc['id'];
        }
    }
    
    // 4. Items (Products)
    echo "\n4. Adding Items...\n";
    $items = [
        ['ITEM001', 'Rice Basmati', 'چاول بسمتی', 'Food', 'kg', 150.00, 180.00, 500, 500, 100],
        ['ITEM002', 'Sugar', 'چینی', 'Food', 'kg', 80.00, 100.00, 1000, 1000, 200],
        ['ITEM003', 'Tea', 'چائے', 'Food', 'kg', 400.00, 500.00, 200, 200, 50],
        ['ITEM004', 'Cooking Oil', 'ککنگ آئل', 'Food', 'liter', 250.00, 300.00, 300, 300, 100],
        ['ITEM005', 'Wheat Flour', 'آٹا', 'Food', 'kg', 60.00, 75.00, 800, 800, 150],
        ['ITEM006', 'Soap', 'صابن', 'Personal Care', 'pcs', 50.00, 70.00, 500, 500, 100],
        ['ITEM007', 'Shampoo', 'شیمپو', 'Personal Care', 'pcs', 200.00, 280.00, 200, 200, 50],
        ['ITEM008', 'Toothpaste', 'ٹوتھ پیسٹ', 'Personal Care', 'pcs', 120.00, 160.00, 300, 300, 75],
        ['ITEM009', 'Detergent', 'ڈیٹرجنٹ', 'Cleaning', 'pcs', 150.00, 200.00, 400, 400, 100],
        ['ITEM010', 'Biscuits', 'بسکٹ', 'Food', 'pcs', 80.00, 120.00, 600, 600, 150],
        ['ITEM011', 'Milk', 'دودھ', 'Food', 'liter', 120.00, 150.00, 200, 200, 50],
        ['ITEM012', 'Eggs', 'انڈے', 'Food', 'dozen', 180.00, 220.00, 100, 100, 25],
        ['ITEM013', 'Bread', 'روٹی', 'Food', 'pcs', 40.00, 60.00, 200, 200, 50],
        ['ITEM014', 'Salt', 'نمک', 'Food', 'kg', 30.00, 45.00, 500, 500, 100],
        ['ITEM015', 'Spices Mix', 'مصالحہ', 'Food', 'kg', 500.00, 650.00, 150, 150, 30]
    ];
    
    $itemIds = [];
    foreach ($items as $item) {
        $stmt = $db->prepare("INSERT IGNORE INTO items (item_code, item_name, item_name_urdu, category, unit, purchase_rate, sale_rate, opening_stock, current_stock, min_stock, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute($item);
        $itemIds[] = $db->lastInsertId();
    }
    echo "✓ Items added (" . count($items) . " items)\n";
    
    // Get all item IDs
    $stmt = $db->query("SELECT id FROM items WHERE status = 'active' ORDER BY id");
    $allItemIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 5. Purchases
    echo "\n5. Adding Purchases...\n";
    $purchaseDates = [
        date('Y-m-d', strtotime('-30 days')),
        date('Y-m-d', strtotime('-25 days')),
        date('Y-m-d', strtotime('-20 days')),
        date('Y-m-d', strtotime('-15 days')),
        date('Y-m-d', strtotime('-10 days')),
        date('Y-m-d', strtotime('-5 days')),
        date('Y-m-d', strtotime('-2 days'))
    ];
    
    $purchaseNos = [];
    $purchaseIds = [];
    
    foreach ($purchaseDates as $idx => $purchaseDate) {
        $supplierId = $supplierIds[array_rand($supplierIds)];
        $purchaseNo = 'PUR' . str_pad($idx + 1, 6, '0', STR_PAD_LEFT);
        $purchaseNos[] = $purchaseNo;
        
        // Select random items for this purchase
        $numItems = rand(3, 6);
        $selectedItems = array_rand($allItemIds, min($numItems, count($allItemIds)));
        if (!is_array($selectedItems)) {
            $selectedItems = [$selectedItems];
        }
        
        $totalAmount = 0;
        $purchaseItems = [];
        
        foreach ($selectedItems as $itemIdx) {
            $itemId = $allItemIds[$itemIdx];
            $stmt = $db->prepare("SELECT purchase_rate FROM items WHERE id = ?");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch();
            $rate = $item['purchase_rate'];
            $quantity = rand(10, 100);
            $amount = $rate * $quantity;
            $totalAmount += $amount;
            
            $purchaseItems[] = [$itemId, $quantity, $rate, $amount];
        }
        
        $discount = rand(0, 5) * 100; // 0 to 500 discount
        $netAmount = $totalAmount - $discount;
        $paidAmount = $netAmount * (rand(50, 100) / 100); // 50% to 100% paid
        $balanceAmount = $netAmount - $paidAmount;
        
        $stmt = $db->prepare("INSERT INTO purchases (purchase_no, purchase_date, account_id, total_amount, discount, net_amount, paid_amount, balance_amount, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$purchaseNo, $purchaseDate, $supplierId, $totalAmount, $discount, $netAmount, $paidAmount, $balanceAmount, "Purchase from supplier", $adminUserId]);
        $purchaseId = $db->lastInsertId();
        $purchaseIds[] = $purchaseId;
        
        // Add purchase items
        foreach ($purchaseItems as $pItem) {
            $stmt = $db->prepare("INSERT INTO purchase_items (purchase_id, item_id, quantity, rate, amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$purchaseId, $pItem[0], $pItem[1], $pItem[2], $pItem[3]]);
            
            // Update item stock
            $stmt = $db->prepare("UPDATE items SET current_stock = current_stock + ? WHERE id = ?");
            $stmt->execute([$pItem[1], $pItem[0]]);
            
            // Add stock movement
            $stmt = $db->prepare("SELECT COALESCE(MAX(balance_quantity), 0) as last_balance FROM stock_movements WHERE item_id = ?");
            $stmt->execute([$pItem[0]]);
            $lastBalance = $stmt->fetch()['last_balance'] ?? 0;
            $newBalance = $lastBalance + $pItem[1];
            
            $stmt = $db->prepare("INSERT INTO stock_movements (item_id, movement_date, movement_type, reference_type, reference_id, quantity_in, quantity_out, rate, balance_quantity) VALUES (?, ?, 'purchase', 'purchase', ?, ?, 0, ?, ?)");
            $stmt->execute([$pItem[0], $purchaseDate, $purchaseId, $pItem[1], $pItem[2], $newBalance]);
        }
    }
    echo "✓ Purchases added (" . count($purchaseIds) . " purchases)\n";
    
    // 6. Sales
    echo "\n6. Adding Sales...\n";
    $saleDates = [
        date('Y-m-d', strtotime('-28 days')),
        date('Y-m-d', strtotime('-22 days')),
        date('Y-m-d', strtotime('-18 days')),
        date('Y-m-d', strtotime('-12 days')),
        date('Y-m-d', strtotime('-8 days')),
        date('Y-m-d', strtotime('-4 days')),
        date('Y-m-d', strtotime('-1 day')),
        date('Y-m-d')
    ];
    
    $saleIds = [];
    
    foreach ($saleDates as $idx => $saleDate) {
        $customerId = $customerIds[array_rand($customerIds)];
        $saleNo = 'SAL' . str_pad($idx + 1, 6, '0', STR_PAD_LEFT);
        
        // Select random items for this sale
        $numItems = rand(2, 5);
        $selectedItems = array_rand($allItemIds, min($numItems, count($allItemIds)));
        if (!is_array($selectedItems)) {
            $selectedItems = [$selectedItems];
        }
        
        $totalAmount = 0;
        $saleItems = [];
        
        foreach ($selectedItems as $itemIdx) {
            $itemId = $allItemIds[$itemIdx];
            $stmt = $db->prepare("SELECT sale_rate, current_stock FROM items WHERE id = ?");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch();
            $rate = $item['sale_rate'];
            $maxQty = min($item['current_stock'], 50); // Don't sell more than available or 50
            $quantity = rand(1, max(1, $maxQty));
            $amount = $rate * $quantity;
            $totalAmount += $amount;
            
            $saleItems[] = [$itemId, $quantity, $rate, $amount];
        }
        
        $discount = rand(0, 3) * 50; // 0 to 150 discount
        $netAmount = $totalAmount - $discount;
        $paidAmount = $netAmount * (rand(60, 100) / 100); // 60% to 100% paid
        $balanceAmount = $netAmount - $paidAmount;
        
        $stmt = $db->prepare("INSERT INTO sales (sale_no, sale_date, account_id, total_amount, discount, net_amount, paid_amount, balance_amount, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$saleNo, $saleDate, $customerId, $totalAmount, $discount, $netAmount, $paidAmount, $balanceAmount, "Sale to customer", $adminUserId]);
        $saleId = $db->lastInsertId();
        $saleIds[] = $saleId;
        
        // Add sale items
        foreach ($saleItems as $sItem) {
            $stmt = $db->prepare("INSERT INTO sale_items (sale_id, item_id, quantity, rate, amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$saleId, $sItem[0], $sItem[1], $sItem[2], $sItem[3]]);
            
            // Update item stock
            $stmt = $db->prepare("UPDATE items SET current_stock = current_stock - ? WHERE id = ?");
            $stmt->execute([$sItem[1], $sItem[0]]);
            
            // Add stock movement
            $stmt = $db->prepare("SELECT COALESCE(MAX(balance_quantity), 0) as last_balance FROM stock_movements WHERE item_id = ?");
            $stmt->execute([$sItem[0]]);
            $lastBalance = $stmt->fetch()['last_balance'] ?? 0;
            $newBalance = max(0, $lastBalance - $sItem[1]);
            
            $stmt = $db->prepare("INSERT INTO stock_movements (item_id, movement_date, movement_type, reference_type, reference_id, quantity_in, quantity_out, rate, balance_quantity) VALUES (?, ?, 'sale', 'sale', ?, 0, ?, ?, ?)");
            $stmt->execute([$sItem[0], $saleDate, $saleId, $sItem[1], $sItem[2], $newBalance]);
        }
    }
    echo "✓ Sales added (" . count($saleIds) . " sales)\n";
    
    // 7. Transactions (Cash Book)
    echo "\n7. Adding Transactions...\n";
    $transactionDates = array_merge($purchaseDates, $saleDates);
    $transactionDates = array_unique($transactionDates);
    sort($transactionDates);
    
    $transactionCount = 0;
    foreach ($transactionDates as $transDate) {
        // Add some debit transactions (payments)
        if (rand(0, 1)) {
            $accountId = $supplierIds[array_rand($supplierIds)];
            $amount = rand(5000, 50000);
            $transNo = 'CD' . str_pad(++$transactionCount, 6, '0', STR_PAD_LEFT);
            
            $stmt = $db->prepare("INSERT INTO transactions (transaction_no, transaction_date, transaction_type, account_id, amount, narration, reference_type, created_by) VALUES (?, ?, 'debit', ?, ?, ?, 'payment', ?)");
            $stmt->execute([$transNo, $transDate, $accountId, $amount, "Cash payment to supplier", $adminUserId]);
        }
        
        // Add some credit transactions (receipts)
        if (rand(0, 1)) {
            $accountId = $customerIds[array_rand($customerIds)];
            $amount = rand(3000, 40000);
            $transNo = 'CC' . str_pad(++$transactionCount, 6, '0', STR_PAD_LEFT);
            
            $stmt = $db->prepare("INSERT INTO transactions (transaction_no, transaction_date, transaction_type, account_id, amount, narration, reference_type, created_by) VALUES (?, ?, 'credit', ?, ?, ?, 'receipt', ?)");
            $stmt->execute([$transNo, $transDate, $accountId, $amount, "Cash receipt from customer", $adminUserId]);
        }
    }
    echo "✓ Transactions added ($transactionCount transactions)\n";
    
    $db->commit();
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✓ SUCCESS! All dummy data has been seeded.\n";
    echo str_repeat("=", 50) . "\n\n";
    
    // Summary
    $stmt = $db->query("SELECT COUNT(*) as count FROM accounts WHERE status = 'active'");
    $accountCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM items WHERE status = 'active'");
    $itemCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM purchases");
    $purchaseCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM sales");
    $saleCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM transactions");
    $transCount = $stmt->fetch()['count'];
    
    echo "Summary:\n";
    echo "- Accounts: $accountCount\n";
    echo "- Items: $itemCount\n";
    echo "- Purchases: $purchaseCount\n";
    echo "- Sales: $saleCount\n";
    echo "- Transactions: $transCount\n";
    echo "\nYou can now login and explore the system with this sample data!\n";
    echo "Login credentials:\n";
    echo "  Username: adil\n";
    echo "  Password: sana25\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

