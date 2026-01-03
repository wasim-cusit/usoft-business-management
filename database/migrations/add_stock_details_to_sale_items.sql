-- Migration: Add stock details columns to sale_items table
-- Date: 2024

ALTER TABLE `sale_items` 
ADD COLUMN `own_stock` decimal(15,2) DEFAULT 0.00 AFTER `amount`,
ADD COLUMN `arranged_stock` decimal(15,2) DEFAULT 0.00 AFTER `own_stock`;

