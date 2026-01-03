-- Migration: Add additional fields to sales table
-- Date: 2026-01-03
-- Description: Adds location, details, phone, bilti, bardana, netcash fields to match reference design

ALTER TABLE `sales` 
ADD COLUMN `location` varchar(255) DEFAULT NULL AFTER `account_id`,
ADD COLUMN `details` text DEFAULT NULL AFTER `location`,
ADD COLUMN `phone` varchar(50) DEFAULT NULL AFTER `details`,
ADD COLUMN `bilti` varchar(50) DEFAULT NULL AFTER `phone`,
ADD COLUMN `bardana` decimal(15,2) DEFAULT 0.00 AFTER `balance_amount`,
ADD COLUMN `netcash` decimal(15,2) DEFAULT 0.00 AFTER `bardana`;

