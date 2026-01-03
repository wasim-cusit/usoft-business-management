-- ============================================
-- DUMMY DATA FOR USOFT ACCOUNTING SYSTEM
-- 10 records for each entity
-- ============================================

-- Clear existing data (optional - uncomment if needed)
-- SET FOREIGN_KEY_CHECKS = 0;
-- TRUNCATE TABLE sale_items;
-- TRUNCATE TABLE purchase_items;
-- TRUNCATE TABLE sales;
-- TRUNCATE TABLE purchases;
-- TRUNCATE TABLE transactions;
-- TRUNCATE TABLE stock_movements;
-- TRUNCATE TABLE items;
-- TRUNCATE TABLE accounts;
-- SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- 0. ENSURE USER TYPES EXIST (Required for Foreign Key)
-- ============================================
-- Insert user types if they don't exist (using INSERT IGNORE to avoid errors if they already exist)
-- If your user_types table already has different IDs, you may need to adjust the user_type_id values below
-- OR set user_type_id to NULL in the accounts INSERT statements

-- Option 1: Insert user types with specific IDs (if table is empty)
INSERT IGNORE INTO `user_types` (`id`, `type_name`, `type_name_urdu`, `description`) VALUES
(1, 'Customer', 'کسٹمر', 'Regular customer type'),
(2, 'Supplier', 'سپلائر', 'Supplier type'),
(3, 'Both', 'دونوں', 'Both customer and supplier');

-- Option 2: If user_types already exist with different IDs, 
-- you can either:
-- A) Update the user_type_id values in accounts below to match your existing IDs
-- B) Or set user_type_id to NULL in accounts (change 1, 2, 3 to NULL below)

-- ============================================
-- 1. ACCOUNTS (10 Accounts)
-- ============================================
INSERT INTO `accounts` (`account_code`, `account_name`, `account_name_urdu`, `account_type`, `user_type_id`, `contact_person`, `phone`, `mobile`, `email`, `address`, `city`, `opening_balance`, `balance_type`, `status`) VALUES
('Acc01', 'ABC Suppliers', 'اے بی سی سپلائرز', 'supplier', 2, 'Ahmed Khan', '021-1234567', '0300-1234567', 'abc@suppliers.com', 'Shop 5, Main Market', 'Karachi', 50000.00, 'credit', 'active'),
('Acc02', 'Hassan Store', 'حسن اسٹور', 'both', 3, 'Hassan Ali', '021-2345678', '0301-2345678', 'hassan@store.com', 'Plot 10, Commercial Area', 'Lahore', 75000.00, 'debit', 'active'),
('Acc03', 'Multi Trade', 'ملٹی ٹریڈ', 'both', 3, 'Usman Malik', '021-3456789', '0302-3456789', 'multitrade@email.com', 'Street 20, Business Hub', 'Islamabad', 100000.00, 'credit', 'active'),
('Acc04', 'Global Imports', 'گلوبل امپورٹس', 'supplier', 2, 'Zain Abbas', '021-4567890', '0303-4567890', 'global@imports.com', 'Warehouse 15, Industrial Zone', 'Karachi', 25000.00, 'credit', 'active'),
('Acc05', 'City Traders', 'سٹی ٹریڈرز', 'customer', 1, 'Bilal Ahmed', '021-5678901', '0304-5678901', 'city@traders.com', 'Shop 25, City Center', 'Lahore', 30000.00, 'debit', 'active'),
('Acc06', 'Prime Wholesale', 'پرائم ہول سیل', 'supplier', 2, 'Faisal Khan', '021-6789012', '0305-6789012', 'prime@wholesale.com', 'Building 30, Wholesale Market', 'Karachi', 60000.00, 'credit', 'active'),
('Acc07', 'Best Deal Store', 'بسٹ ڈیل اسٹور', 'customer', 1, 'Kamran Ali', '021-7890123', '0306-7890123', 'bestdeal@store.com', 'Shop 40, Main Bazaar', 'Rawalpindi', 15000.00, 'debit', 'active'),
('Acc08', 'Super Mart', 'سپر مارٹ', 'both', 3, 'Tariq Hussain', '021-8901234', '0307-8901234', 'super@mart.com', 'Mall Floor 2, Shopping Center', 'Lahore', 80000.00, 'credit', 'active'),
('Acc09', 'Elite Distributors', 'ایلائٹ ڈسٹری بیوٹرز', 'supplier', 2, 'Sajid Mehmood', '021-9012345', '0308-9012345', 'elite@dist.com', 'Office 50, Trade Center', 'Karachi', 45000.00, 'credit', 'active'),
('Acc10', 'Quick Sales', 'کوئک سیلز', 'customer', 1, 'Nadeem Sheikh', '021-0123456', '0309-0123456', 'quick@sales.com', 'Shop 60, Market Street', 'Faisalabad', 20000.00, 'debit', 'active');

-- ============================================
-- 2. ITEMS (10 Items)
-- ============================================
INSERT INTO `items` (`item_code`, `item_name`, `item_name_urdu`, `category`, `unit`, `purchase_rate`, `sale_rate`, `opening_stock`, `current_stock`, `min_stock`, `description`, `status`) VALUES
('Itm01', 'Rice Basmati', 'چاول بسمتی', 'Food', 'kg', 250.00, 300.00, 500.00, 500.00, 100.00, 'Premium Basmati Rice', 'active'),
('Itm02', 'Cooking Oil', 'ککنگ آئل', 'Food', 'liter', 350.00, 420.00, 300.00, 300.00, 50.00, 'Vegetable Cooking Oil', 'active'),
('Itm03', 'Sugar', 'شکر', 'Food', 'kg', 120.00, 150.00, 400.00, 400.00, 80.00, 'White Granulated Sugar', 'active'),
('Itm04', 'Milk', 'دودھ', 'Dairy', 'liter', 180.00, 220.00, 200.00, 200.00, 40.00, 'Fresh Milk', 'active'),
('Itm05', 'Biscuits', 'بسکٹ', 'Snacks', 'pack', 80.00, 100.00, 500.00, 500.00, 100.00, 'Assorted Biscuits', 'active'),
('Itm06', 'Bread', 'روٹی', 'Bakery', 'pack', 50.00, 60.00, 300.00, 300.00, 50.00, 'Fresh White Bread', 'active'),
('Itm07', 'Detergent', 'ڈیٹرجنٹ', 'Cleaning', 'pack', 200.00, 250.00, 200.00, 200.00, 30.00, 'Laundry Detergent', 'active'),
('Itm08', 'Soap', 'صابن', 'Personal Care', 'pcs', 30.00, 40.00, 600.00, 600.00, 100.00, 'Bath Soap', 'active'),
('Itm09', 'Shampoo', 'شیمپو', 'Personal Care', 'bottle', 250.00, 320.00, 150.00, 150.00, 25.00, 'Hair Shampoo', 'active'),
('Itm10', 'Spices Mix', 'مصالحہ', 'Food', 'pack', 150.00, 200.00, 250.00, 250.00, 50.00, 'Mixed Spices Pack', 'active');

-- ============================================
-- 3. PURCHASES (10 Purchases)
-- ============================================
INSERT INTO `purchases` (`purchase_no`, `purchase_date`, `account_id`, `total_amount`, `discount`, `net_amount`, `paid_amount`, `balance_amount`, `remarks`, `created_by`) VALUES
('Pur01', '2025-12-22', 1, 125000.00, 5000.00, 120000.00, 100000.00, 20000.00, 'Monthly stock purchase', 1),
('Pur02', '2025-12-22', 4, 87500.00, 0.00, 87500.00, 87500.00, 0.00, 'Regular supply', 1),
('Pur03', '2025-12-22', 6, 156000.00, 6000.00, 150000.00, 100000.00, 50000.00, 'Bulk order', 1),
('Pur04', '2025-12-23', 1, 98000.00, 3000.00, 95000.00, 50000.00, 45000.00, 'Weekly purchase', 1),
('Pur05', '2025-12-23', 9, 112000.00, 2000.00, 110000.00, 110000.00, 0.00, 'Premium items', 1),
('Pur06', '2025-12-23', 4, 134000.00, 4000.00, 130000.00, 80000.00, 50000.00, 'New stock arrival', 1),
('Pur07', '2025-12-24', 6, 89000.00, 0.00, 89000.00, 89000.00, 0.00, 'Regular supply', 1),
('Pur08', '2025-12-24', 1, 167000.00, 7000.00, 160000.00, 120000.00, 40000.00, 'Monthly bulk order', 1),
('Pur09', '2025-12-25', 9, 103000.00, 3000.00, 100000.00, 100000.00, 0.00, 'Special items', 1),
('Pur10', '2025-12-25', 4, 145000.00, 5000.00, 140000.00, 90000.00, 50000.00, 'Quarterly purchase', 1);

-- Purchase Items for Pur01
INSERT INTO `purchase_items` (`purchase_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(1, 1, 200.00, 250.00, 50000.00),
(1, 2, 150.00, 350.00, 52500.00),
(1, 3, 187.50, 120.00, 22500.00);

-- Purchase Items for Pur02
INSERT INTO `purchase_items` (`purchase_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(2, 4, 200.00, 180.00, 36000.00),
(2, 5, 300.00, 80.00, 24000.00),
(2, 6, 550.00, 50.00, 27500.00);

-- Purchase Items for Pur03
INSERT INTO `purchase_items` (`purchase_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(3, 7, 300.00, 200.00, 60000.00),
(3, 8, 400.00, 30.00, 12000.00),
(3, 9, 200.00, 250.00, 50000.00),
(3, 10, 226.67, 150.00, 34000.00);

-- Purchase Items for Pur04
INSERT INTO `purchase_items` (`purchase_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(4, 1, 150.00, 250.00, 37500.00),
(4, 2, 100.00, 350.00, 35000.00),
(4, 3, 212.50, 120.00, 25500.00);

-- Purchase Items for Pur05
INSERT INTO `purchase_items` (`purchase_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(5, 4, 250.00, 180.00, 45000.00),
(5, 5, 400.00, 80.00, 32000.00),
(5, 6, 700.00, 50.00, 35000.00);

-- Purchase Items for Pur06
INSERT INTO `purchase_items` (`purchase_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(6, 7, 250.00, 200.00, 50000.00),
(6, 8, 350.00, 30.00, 10500.00),
(6, 9, 150.00, 250.00, 37500.00),
(6, 10, 240.00, 150.00, 36000.00);

-- Purchase Items for Pur07
INSERT INTO `purchase_items` (`purchase_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(7, 1, 180.00, 250.00, 45000.00),
(7, 2, 125.71, 350.00, 44000.00);

-- Purchase Items for Pur08
INSERT INTO `purchase_items` (`purchase_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(8, 3, 300.00, 120.00, 36000.00),
(8, 4, 300.00, 180.00, 54000.00),
(8, 5, 500.00, 80.00, 40000.00),
(8, 6, 720.00, 50.00, 36000.00);

-- Purchase Items for Pur09
INSERT INTO `purchase_items` (`purchase_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(9, 7, 200.00, 200.00, 40000.00),
(9, 8, 300.00, 30.00, 9000.00),
(9, 9, 200.00, 250.00, 50000.00),
(9, 10, 26.67, 150.00, 4000.00);

-- Purchase Items for Pur10
INSERT INTO `purchase_items` (`purchase_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(10, 1, 250.00, 250.00, 62500.00),
(10, 2, 200.00, 350.00, 70000.00),
(10, 3, 104.17, 120.00, 12500.00);

-- Update stock for purchases
UPDATE `items` SET `current_stock` = `current_stock` + 200.00 WHERE `id` = 1;
UPDATE `items` SET `current_stock` = `current_stock` + 150.00 WHERE `id` = 2;
UPDATE `items` SET `current_stock` = `current_stock` + 187.50 WHERE `id` = 3;
UPDATE `items` SET `current_stock` = `current_stock` + 200.00 WHERE `id` = 4;
UPDATE `items` SET `current_stock` = `current_stock` + 300.00 WHERE `id` = 5;
UPDATE `items` SET `current_stock` = `current_stock` + 550.00 WHERE `id` = 6;
UPDATE `items` SET `current_stock` = `current_stock` + 300.00 WHERE `id` = 7;
UPDATE `items` SET `current_stock` = `current_stock` + 400.00 WHERE `id` = 8;
UPDATE `items` SET `current_stock` = `current_stock` + 200.00 WHERE `id` = 9;
UPDATE `items` SET `current_stock` = `current_stock` + 226.67 WHERE `id` = 10;
UPDATE `items` SET `current_stock` = `current_stock` + 150.00 WHERE `id` = 1;
UPDATE `items` SET `current_stock` = `current_stock` + 100.00 WHERE `id` = 2;
UPDATE `items` SET `current_stock` = `current_stock` + 212.50 WHERE `id` = 3;
UPDATE `items` SET `current_stock` = `current_stock` + 250.00 WHERE `id` = 4;
UPDATE `items` SET `current_stock` = `current_stock` + 400.00 WHERE `id` = 5;
UPDATE `items` SET `current_stock` = `current_stock` + 700.00 WHERE `id` = 6;
UPDATE `items` SET `current_stock` = `current_stock` + 250.00 WHERE `id` = 7;
UPDATE `items` SET `current_stock` = `current_stock` + 350.00 WHERE `id` = 8;
UPDATE `items` SET `current_stock` = `current_stock` + 150.00 WHERE `id` = 9;
UPDATE `items` SET `current_stock` = `current_stock` + 240.00 WHERE `id` = 10;
UPDATE `items` SET `current_stock` = `current_stock` + 180.00 WHERE `id` = 1;
UPDATE `items` SET `current_stock` = `current_stock` + 125.71 WHERE `id` = 2;
UPDATE `items` SET `current_stock` = `current_stock` + 300.00 WHERE `id` = 3;
UPDATE `items` SET `current_stock` = `current_stock` + 300.00 WHERE `id` = 4;
UPDATE `items` SET `current_stock` = `current_stock` + 500.00 WHERE `id` = 5;
UPDATE `items` SET `current_stock` = `current_stock` + 720.00 WHERE `id` = 6;
UPDATE `items` SET `current_stock` = `current_stock` + 200.00 WHERE `id` = 7;
UPDATE `items` SET `current_stock` = `current_stock` + 300.00 WHERE `id` = 8;
UPDATE `items` SET `current_stock` = `current_stock` + 200.00 WHERE `id` = 9;
UPDATE `items` SET `current_stock` = `current_stock` + 26.67 WHERE `id` = 10;
UPDATE `items` SET `current_stock` = `current_stock` + 250.00 WHERE `id` = 1;
UPDATE `items` SET `current_stock` = `current_stock` + 200.00 WHERE `id` = 2;
UPDATE `items` SET `current_stock` = `current_stock` + 104.17 WHERE `id` = 3;

-- ============================================
-- 4. SALES (10 Sales)
-- ============================================
INSERT INTO `sales` (`sale_no`, `sale_date`, `account_id`, `total_amount`, `discount`, `net_amount`, `paid_amount`, `balance_amount`, `remarks`, `created_by`) VALUES
('Sal01', '2025-12-22', 2, 96000.00, 2000.00, 94000.00, 94000.00, 0.00, 'Regular customer order', 1),
('Sal02', '2025-12-22', 5, 67200.00, 1200.00, 66000.00, 50000.00, 16000.00, 'Bulk sale', 1),
('Sal03', '2025-12-22', 3, 120000.00, 5000.00, 115000.00, 100000.00, 15000.00, 'Monthly order', 1),
('Sal04', '2025-12-23', 7, 75000.00, 0.00, 75000.00, 75000.00, 0.00, 'Quick sale', 1),
('Sal05', '2025-12-23', 2, 88000.00, 3000.00, 85000.00, 60000.00, 25000.00, 'Regular supply', 1),
('Sal06', '2025-12-23', 8, 105600.00, 2600.00, 103000.00, 103000.00, 0.00, 'Premium customer', 1),
('Sal07', '2025-12-24', 5, 72000.00, 0.00, 72000.00, 50000.00, 22000.00, 'Weekly order', 1),
('Sal08', '2025-12-24', 10, 96000.00, 4000.00, 92000.00, 70000.00, 22000.00, 'New customer', 1),
('Sal09', '2025-12-25', 3, 112000.00, 2000.00, 110000.00, 110000.00, 0.00, 'Regular order', 1),
('Sal10', '2025-12-25', 2, 128000.00, 3000.00, 125000.00, 100000.00, 25000.00, 'Large order', 1);

-- Sale Items for Sal01
INSERT INTO `sale_items` (`sale_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(1, 1, 150.00, 300.00, 45000.00),
(1, 2, 100.00, 420.00, 42000.00),
(1, 3, 46.67, 150.00, 7000.00),
(1, 4, 9.09, 220.00, 2000.00);

-- Sale Items for Sal02
INSERT INTO `sale_items` (`sale_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(2, 4, 150.00, 220.00, 33000.00),
(2, 5, 200.00, 100.00, 20000.00),
(2, 6, 233.33, 60.00, 14000.00),
(2, 7, 8.00, 250.00, 2000.00);

-- Sale Items for Sal03
INSERT INTO `sale_items` (`sale_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(3, 7, 200.00, 250.00, 50000.00),
(3, 8, 300.00, 40.00, 12000.00),
(3, 9, 150.00, 320.00, 48000.00),
(3, 10, 100.00, 200.00, 20000.00);

-- Sale Items for Sal04
INSERT INTO `sale_items` (`sale_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(4, 1, 100.00, 300.00, 30000.00),
(4, 2, 80.00, 420.00, 33600.00),
(4, 3, 76.00, 150.00, 11400.00);

-- Sale Items for Sal05
INSERT INTO `sale_items` (`sale_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(5, 4, 120.00, 220.00, 26400.00),
(5, 5, 180.00, 100.00, 18000.00),
(5, 6, 300.00, 60.00, 18000.00),
(5, 7, 100.00, 250.00, 25000.00),
(5, 8, 50.00, 40.00, 2000.00);

-- Sale Items for Sal06
INSERT INTO `sale_items` (`sale_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(6, 7, 150.00, 250.00, 37500.00),
(6, 8, 200.00, 40.00, 8000.00),
(6, 9, 120.00, 320.00, 38400.00),
(6, 10, 98.50, 200.00, 19700.00);

-- Sale Items for Sal07
INSERT INTO `sale_items` (`sale_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(7, 1, 120.00, 300.00, 36000.00),
(7, 2, 85.71, 420.00, 36000.00);

-- Sale Items for Sal08
INSERT INTO `sale_items` (`sale_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(8, 3, 200.00, 150.00, 30000.00),
(8, 4, 150.00, 220.00, 33000.00),
(8, 5, 200.00, 100.00, 20000.00),
(8, 6, 216.67, 60.00, 13000.00);

-- Sale Items for Sal09
INSERT INTO `sale_items` (`sale_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(9, 7, 180.00, 250.00, 45000.00),
(9, 8, 250.00, 40.00, 10000.00),
(9, 9, 140.00, 320.00, 44800.00),
(9, 10, 61.00, 200.00, 12200.00);

-- Sale Items for Sal10
INSERT INTO `sale_items` (`sale_id`, `item_id`, `quantity`, `rate`, `amount`) VALUES
(10, 1, 200.00, 300.00, 60000.00),
(10, 2, 150.00, 420.00, 63000.00),
(10, 3, 33.33, 150.00, 5000.00);

-- Update stock for sales (decrease stock)
UPDATE `items` SET `current_stock` = `current_stock` - 150.00 WHERE `id` = 1;
UPDATE `items` SET `current_stock` = `current_stock` - 100.00 WHERE `id` = 2;
UPDATE `items` SET `current_stock` = `current_stock` - 46.67 WHERE `id` = 3;
UPDATE `items` SET `current_stock` = `current_stock` - 9.09 WHERE `id` = 4;
UPDATE `items` SET `current_stock` = `current_stock` - 150.00 WHERE `id` = 4;
UPDATE `items` SET `current_stock` = `current_stock` - 200.00 WHERE `id` = 5;
UPDATE `items` SET `current_stock` = `current_stock` - 233.33 WHERE `id` = 6;
UPDATE `items` SET `current_stock` = `current_stock` - 8.00 WHERE `id` = 7;
UPDATE `items` SET `current_stock` = `current_stock` - 200.00 WHERE `id` = 7;
UPDATE `items` SET `current_stock` = `current_stock` - 300.00 WHERE `id` = 8;
UPDATE `items` SET `current_stock` = `current_stock` - 150.00 WHERE `id` = 9;
UPDATE `items` SET `current_stock` = `current_stock` - 100.00 WHERE `id` = 10;
UPDATE `items` SET `current_stock` = `current_stock` - 100.00 WHERE `id` = 1;
UPDATE `items` SET `current_stock` = `current_stock` - 80.00 WHERE `id` = 2;
UPDATE `items` SET `current_stock` = `current_stock` - 76.00 WHERE `id` = 3;
UPDATE `items` SET `current_stock` = `current_stock` - 120.00 WHERE `id` = 4;
UPDATE `items` SET `current_stock` = `current_stock` - 180.00 WHERE `id` = 5;
UPDATE `items` SET `current_stock` = `current_stock` - 300.00 WHERE `id` = 6;
UPDATE `items` SET `current_stock` = `current_stock` - 100.00 WHERE `id` = 7;
UPDATE `items` SET `current_stock` = `current_stock` - 50.00 WHERE `id` = 8;
UPDATE `items` SET `current_stock` = `current_stock` - 150.00 WHERE `id` = 7;
UPDATE `items` SET `current_stock` = `current_stock` - 200.00 WHERE `id` = 8;
UPDATE `items` SET `current_stock` = `current_stock` - 120.00 WHERE `id` = 9;
UPDATE `items` SET `current_stock` = `current_stock` - 98.50 WHERE `id` = 10;
UPDATE `items` SET `current_stock` = `current_stock` - 120.00 WHERE `id` = 1;
UPDATE `items` SET `current_stock` = `current_stock` - 85.71 WHERE `id` = 2;
UPDATE `items` SET `current_stock` = `current_stock` - 200.00 WHERE `id` = 3;
UPDATE `items` SET `current_stock` = `current_stock` - 150.00 WHERE `id` = 4;
UPDATE `items` SET `current_stock` = `current_stock` - 200.00 WHERE `id` = 5;
UPDATE `items` SET `current_stock` = `current_stock` - 216.67 WHERE `id` = 6;
UPDATE `items` SET `current_stock` = `current_stock` - 180.00 WHERE `id` = 7;
UPDATE `items` SET `current_stock` = `current_stock` - 250.00 WHERE `id` = 8;
UPDATE `items` SET `current_stock` = `current_stock` - 140.00 WHERE `id` = 9;
UPDATE `items` SET `current_stock` = `current_stock` - 61.00 WHERE `id` = 10;
UPDATE `items` SET `current_stock` = `current_stock` - 200.00 WHERE `id` = 1;
UPDATE `items` SET `current_stock` = `current_stock` - 150.00 WHERE `id` = 2;
UPDATE `items` SET `current_stock` = `current_stock` - 33.33 WHERE `id` = 3;

-- ============================================
-- 5. CASH DEBIT TRANSACTIONS (10 Transactions)
-- ============================================
INSERT INTO `transactions` (`transaction_no`, `transaction_date`, `transaction_type`, `account_id`, `amount`, `narration`, `reference_type`, `reference_id`, `created_by`) VALUES
('Dbt01', '2025-12-22', 'debit', 1, 100000.00, 'Payment for purchase Pur01', 'purchase', 1, 1),
('Dbt02', '2025-12-22', 'debit', 4, 87500.00, 'Payment for purchase Pur02', 'purchase', 2, 1),
('Dbt03', '2025-12-22', 'debit', 6, 100000.00, 'Payment for purchase Pur03', 'purchase', 3, 1),
('Dbt04', '2025-12-23', 'debit', 1, 50000.00, 'Payment for purchase Pur04', 'purchase', 4, 1),
('Dbt05', '2025-12-23', 'debit', 9, 110000.00, 'Payment for purchase Pur05', 'purchase', 5, 1),
('Dbt06', '2025-12-23', 'debit', 4, 80000.00, 'Payment for purchase Pur06', 'purchase', 6, 1),
('Dbt07', '2025-12-24', 'debit', 6, 89000.00, 'Payment for purchase Pur07', 'purchase', 7, 1),
('Dbt08', '2025-12-24', 'debit', 1, 120000.00, 'Payment for purchase Pur08', 'purchase', 8, 1),
('Dbt09', '2025-12-25', 'debit', 9, 100000.00, 'Payment for purchase Pur09', 'purchase', 9, 1),
('Dbt10', '2025-12-25', 'debit', 4, 90000.00, 'Payment for purchase Pur10', 'purchase', 10, 1);

-- ============================================
-- 6. CASH CREDIT TRANSACTIONS (10 Transactions)
-- ============================================
INSERT INTO `transactions` (`transaction_no`, `transaction_date`, `transaction_type`, `account_id`, `amount`, `narration`, `reference_type`, `reference_id`, `created_by`) VALUES
('Crd01', '2025-12-22', 'credit', 2, 94000.00, 'Receipt from sale Sal01', 'sale', 1, 1),
('Crd02', '2025-12-22', 'credit', 5, 50000.00, 'Receipt from sale Sal02', 'sale', 2, 1),
('Crd03', '2025-12-22', 'credit', 3, 100000.00, 'Receipt from sale Sal03', 'sale', 3, 1),
('Crd04', '2025-12-23', 'credit', 7, 75000.00, 'Receipt from sale Sal04', 'sale', 4, 1),
('Crd05', '2025-12-23', 'credit', 2, 60000.00, 'Receipt from sale Sal05', 'sale', 5, 1),
('Crd06', '2025-12-23', 'credit', 8, 103000.00, 'Receipt from sale Sal06', 'sale', 6, 1),
('Crd07', '2025-12-24', 'credit', 5, 50000.00, 'Receipt from sale Sal07', 'sale', 7, 1),
('Crd08', '2025-12-24', 'credit', 10, 70000.00, 'Receipt from sale Sal08', 'sale', 8, 1),
('Crd09', '2025-12-25', 'credit', 3, 110000.00, 'Receipt from sale Sal09', 'sale', 9, 1),
('Crd10', '2025-12-25', 'credit', 2, 100000.00, 'Receipt from sale Sal10', 'sale', 10, 1);

-- ============================================
-- 7. JOURNAL TRANSACTIONS (10 Journal Vouchers)
-- Each journal has 2 transactions (debit and credit)
-- ============================================
INSERT INTO `transactions` (`transaction_no`, `transaction_date`, `transaction_type`, `account_id`, `amount`, `narration`, `reference_type`, `reference_id`, `created_by`) VALUES
-- Jv01
('Jv01-D', '2025-12-22', 'debit', 2, 50000.00, 'Journal entry - Transfer', 'journal', NULL, 1),
('Jv01-C', '2025-12-22', 'credit', 3, 50000.00, 'Journal entry - Transfer', 'journal', NULL, 1),
-- Jv02
('Jv02-D', '2025-12-22', 'debit', 5, 30000.00, 'Journal entry - Adjustment', 'journal', NULL, 1),
('Jv02-C', '2025-12-22', 'credit', 7, 30000.00, 'Journal entry - Adjustment', 'journal', NULL, 1),
-- Jv03
('Jv03-D', '2025-12-22', 'debit', 8, 75000.00, 'Journal entry - Transfer', 'journal', NULL, 1),
('Jv03-C', '2025-12-22', 'credit', 2, 75000.00, 'Journal entry - Transfer', 'journal', NULL, 1),
-- Jv04
('Jv04-D', '2025-12-23', 'debit', 3, 40000.00, 'Journal entry - Adjustment', 'journal', NULL, 1),
('Jv04-C', '2025-12-23', 'credit', 5, 40000.00, 'Journal entry - Adjustment', 'journal', NULL, 1),
-- Jv05
('Jv05-D', '2025-12-23', 'debit', 10, 25000.00, 'Journal entry - Transfer', 'journal', NULL, 1),
('Jv05-C', '2025-12-23', 'credit', 7, 25000.00, 'Journal entry - Transfer', 'journal', NULL, 1),
-- Jv06
('Jv06-D', '2025-12-23', 'debit', 2, 60000.00, 'Journal entry - Adjustment', 'journal', NULL, 1),
('Jv06-C', '2025-12-23', 'credit', 8, 60000.00, 'Journal entry - Adjustment', 'journal', NULL, 1),
-- Jv07
('Jv07-D', '2025-12-24', 'debit', 3, 45000.00, 'Journal entry - Transfer', 'journal', NULL, 1),
('Jv07-C', '2025-12-24', 'credit', 10, 45000.00, 'Journal entry - Transfer', 'journal', NULL, 1),
-- Jv08
('Jv08-D', '2025-12-24', 'debit', 5, 35000.00, 'Journal entry - Adjustment', 'journal', NULL, 1),
('Jv08-C', '2025-12-24', 'credit', 2, 35000.00, 'Journal entry - Adjustment', 'journal', NULL, 1),
-- Jv09
('Jv09-D', '2025-12-25', 'debit', 7, 55000.00, 'Journal entry - Transfer', 'journal', NULL, 1),
('Jv09-C', '2025-12-25', 'credit', 3, 55000.00, 'Journal entry - Transfer', 'journal', NULL, 1),
-- Jv10
('Jv10-D', '2025-12-25', 'debit', 8, 80000.00, 'Journal entry - Adjustment', 'journal', NULL, 1),
('Jv10-C', '2025-12-25', 'credit', 5, 80000.00, 'Journal entry - Adjustment', 'journal', NULL, 1);

-- ============================================
-- 8. STOCK EXCHANGE TRANSACTIONS (10 Transactions)
-- Each stock exchange has 2 transactions (debit and credit)
-- ============================================
INSERT INTO `transactions` (`transaction_no`, `transaction_date`, `transaction_type`, `account_id`, `amount`, `narration`, `reference_type`, `created_by`) VALUES
-- Se01
('Se01-D', '2025-12-22', 'debit', 2, 45000.00, 'Stock Exchange - Transfer', 'other', 1),
('Se01-C', '2025-12-22', 'credit', 3, 45000.00, 'Stock Exchange - Transfer', 'other', 1),
-- Se02
('Se02-D', '2025-12-22', 'debit', 5, 30000.00, 'Stock Exchange - Transfer', 'other', 1),
('Se02-C', '2025-12-22', 'credit', 7, 30000.00, 'Stock Exchange - Transfer', 'other', 1),
-- Se03
('Se03-D', '2025-12-22', 'debit', 8, 60000.00, 'Stock Exchange - Transfer', 'other', 1),
('Se03-C', '2025-12-22', 'credit', 2, 60000.00, 'Stock Exchange - Transfer', 'other', 1),
-- Se04
('Se04-D', '2025-12-23', 'debit', 3, 40000.00, 'Stock Exchange - Transfer', 'other', 1),
('Se04-C', '2025-12-23', 'credit', 5, 40000.00, 'Stock Exchange - Transfer', 'other', 1),
-- Se05
('Se05-D', '2025-12-23', 'debit', 10, 25000.00, 'Stock Exchange - Transfer', 'other', 1),
('Se05-C', '2025-12-23', 'credit', 7, 25000.00, 'Stock Exchange - Transfer', 'other', 1),
-- Se06
('Se06-D', '2025-12-23', 'debit', 2, 55000.00, 'Stock Exchange - Transfer', 'other', 1),
('Se06-C', '2025-12-23', 'credit', 8, 55000.00, 'Stock Exchange - Transfer', 'other', 1),
-- Se07
('Se07-D', '2025-12-24', 'debit', 3, 35000.00, 'Stock Exchange - Transfer', 'other', 1),
('Se07-C', '2025-12-24', 'credit', 10, 35000.00, 'Stock Exchange - Transfer', 'other', 1),
-- Se08
('Se08-D', '2025-12-24', 'debit', 5, 45000.00, 'Stock Exchange - Transfer', 'other', 1),
('Se08-C', '2025-12-24', 'credit', 2, 45000.00, 'Stock Exchange - Transfer', 'other', 1),
-- Se09
('Se09-D', '2025-12-25', 'debit', 7, 50000.00, 'Stock Exchange - Transfer', 'other', 1),
('Se09-C', '2025-12-25', 'credit', 3, 50000.00, 'Stock Exchange - Transfer', 'other', 1),
-- Se10
('Se10-D', '2025-12-25', 'debit', 8, 70000.00, 'Stock Exchange - Transfer', 'other', 1),
('Se10-C', '2025-12-25', 'credit', 5, 70000.00, 'Stock Exchange - Transfer', 'other', 1);

-- ============================================
-- SUMMARY
-- ============================================
-- Accounts: 10 records (Acc01 - Acc10)
-- Items: 10 records (Itm01 - Itm10)
-- Purchases: 10 records (Pur01 - Pur10) with multiple items each
-- Sales: 10 records (Sal01 - Sal10) with multiple items each
-- Cash Debit: 10 transactions (Dbt01 - Dbt10)
-- Cash Credit: 10 transactions (Crd01 - Crd10)
-- Journal: 10 vouchers (Jv01 - Jv10) = 20 transactions
-- Stock Exchange: 10 transactions (Se01 - Se10) = 20 transactions
-- Total Transactions: 10 + 10 + 20 + 20 = 60 transactions
-- ============================================

