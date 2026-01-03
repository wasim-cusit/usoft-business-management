-- Migration: Add sale calculation fields to sale_items table
-- Date: 2024
-- Description: Adds fields for detailed weight calculations (qty/nag, toda, bharti, weight, kaat, net weight)

ALTER TABLE `sale_items` 
ADD COLUMN `qty` decimal(15,2) DEFAULT 0.00 AFTER `item_id`,
ADD COLUMN `narch` decimal(15,2) DEFAULT 0.00 AFTER `qty`,
ADD COLUMN `bag` decimal(15,2) DEFAULT 0.00 AFTER `narch`,
ADD COLUMN `wt` decimal(15,2) DEFAULT 0.00 AFTER `bag`,
ADD COLUMN `kate` decimal(15,2) DEFAULT 0.00 AFTER `wt`,
ADD COLUMN `wt2` decimal(15,2) DEFAULT 0.00 AFTER `kate`;

