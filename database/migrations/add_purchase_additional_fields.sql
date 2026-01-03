-- Migration: Add additional fields to purchases table
-- Date: 2026-01-03
-- Description: Adds location, details, phone, bilti, and expense fields to match reference design

ALTER TABLE `purchases` 
ADD COLUMN `location` varchar(255) DEFAULT NULL AFTER `account_id`,
ADD COLUMN `details` text DEFAULT NULL AFTER `location`,
ADD COLUMN `phone` varchar(50) DEFAULT NULL AFTER `details`,
ADD COLUMN `bilti` varchar(50) DEFAULT NULL AFTER `phone`,
ADD COLUMN `rent` decimal(15,2) DEFAULT 0.00 AFTER `balance_amount`,
ADD COLUMN `loading` decimal(15,2) DEFAULT 0.00 AFTER `rent`,
ADD COLUMN `labor` decimal(15,2) DEFAULT 0.00 AFTER `loading`,
ADD COLUMN `brokerage` decimal(15,2) DEFAULT 0.00 AFTER `labor`,
ADD COLUMN `total_expenses` decimal(15,2) DEFAULT 0.00 AFTER `brokerage`,
ADD COLUMN `grand_total` decimal(15,2) DEFAULT 0.00 AFTER `total_expenses`;

