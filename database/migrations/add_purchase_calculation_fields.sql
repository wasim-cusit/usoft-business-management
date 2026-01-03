-- Migration: Add purchase calculation fields to purchase_items table
-- Date: 2026-01-03
-- Description: Adds fields for detailed weight calculations (qty/nag, bag, weight, cut)

ALTER TABLE `purchase_items` 
ADD COLUMN `qty` decimal(15,2) DEFAULT 0.00 AFTER `item_id`,
ADD COLUMN `bag` decimal(15,2) DEFAULT 0.00 AFTER `qty`,
ADD COLUMN `wt` decimal(15,2) DEFAULT 0.00 AFTER `bag`,
ADD COLUMN `kate` decimal(15,2) DEFAULT 0.00 AFTER `wt`;

