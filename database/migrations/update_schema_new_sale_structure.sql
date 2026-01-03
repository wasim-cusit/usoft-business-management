-- Migration: Update schema to reflect new sale structure
-- Date: 2026-01-03
-- Description: Updates the sale_items table structure to make new fields NOT NULL with defaults
--              This ensures the new structure is enforced at database level

-- Make new calculation fields NOT NULL with defaults
ALTER TABLE `sale_items` 
MODIFY COLUMN `qty` decimal(15,2) NOT NULL DEFAULT 0.00,
MODIFY COLUMN `narch` decimal(15,2) NOT NULL DEFAULT 0.00,
MODIFY COLUMN `bag` decimal(15,2) NOT NULL DEFAULT 0.00,
MODIFY COLUMN `wt` decimal(15,2) NOT NULL DEFAULT 0.00,
MODIFY COLUMN `kate` decimal(15,2) NOT NULL DEFAULT 0.00,
MODIFY COLUMN `wt2` decimal(15,2) NOT NULL DEFAULT 0.00;

-- Ensure quantity field matches wt2 (for any remaining compatibility)
-- Note: We keep quantity field but it should always equal wt2
ALTER TABLE `sale_items` 
MODIFY COLUMN `quantity` decimal(15,2) NOT NULL DEFAULT 0.00;

-- Update sales table - make new fields NOT NULL with defaults
ALTER TABLE `sales` 
MODIFY COLUMN `location` varchar(255) DEFAULT '',
MODIFY COLUMN `details` text DEFAULT NULL,
MODIFY COLUMN `phone` varchar(50) DEFAULT '',
MODIFY COLUMN `bilti` varchar(50) DEFAULT '',
MODIFY COLUMN `bardana` decimal(15,2) NOT NULL DEFAULT 0.00,
MODIFY COLUMN `netcash` decimal(15,2) NOT NULL DEFAULT 0.00;

