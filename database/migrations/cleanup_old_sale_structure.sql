-- Migration: Cleanup old sale structure
-- Date: 2026-01-03
-- Description: Ensures all sales use new structure and quantity field matches wt2
--              This should be run after migrate_sales_to_new_structure.sql

-- Step 1: Ensure all sale_items have quantity = wt2 (for consistency)
UPDATE `sale_items` 
SET `quantity` = `wt2` 
WHERE `wt2` > 0 AND (`quantity` != `wt2` OR `quantity` IS NULL);

-- Step 2: For any items with NULL or 0 in new fields, calculate from quantity if available
UPDATE `sale_items` 
SET 
    `qty` = CASE WHEN (`qty` = 0 OR `qty` IS NULL) AND `quantity` > 0 THEN `quantity` ELSE `qty` END,
    `wt` = CASE WHEN (`wt` = 0 OR `wt` IS NULL) AND `quantity` > 0 THEN `quantity` ELSE `wt` END,
    `wt2` = CASE WHEN (`wt2` = 0 OR `wt2` IS NULL) AND `quantity` > 0 THEN `quantity` ELSE `wt2` END
WHERE (`qty` = 0 OR `qty` IS NULL OR `wt` = 0 OR `wt` IS NULL OR `wt2` = 0 OR `wt2` IS NULL) AND `quantity` > 0;

-- Step 3: Ensure all new fields have default values (no NULLs)
UPDATE `sale_items` 
SET 
    `qty` = COALESCE(`qty`, 0),
    `narch` = COALESCE(`narch`, 0),
    `bag` = COALESCE(`bag`, 0),
    `wt` = COALESCE(`wt`, COALESCE(`qty`, 0) + COALESCE(`narch`, 0) + COALESCE(`bag`, 0)),
    `kate` = COALESCE(`kate`, 0),
    `wt2` = COALESCE(`wt2`, COALESCE(`wt`, COALESCE(`qty`, 0) + COALESCE(`narch`, 0) + COALESCE(`bag`, 0)) - COALESCE(`kate`, 0)),
    `quantity` = COALESCE(`quantity`, COALESCE(`wt2`, 0))
WHERE `qty` IS NULL OR `narch` IS NULL OR `bag` IS NULL OR `wt` IS NULL OR `kate` IS NULL OR `wt2` IS NULL OR `quantity` IS NULL;

-- Step 4: Update sales table - ensure new fields have defaults
UPDATE `sales` 
SET 
    `location` = COALESCE(`location`, ''),
    `details` = COALESCE(`details`, ''),
    `phone` = COALESCE(`phone`, ''),
    `bilti` = COALESCE(`bilti`, ''),
    `bardana` = COALESCE(`bardana`, 0),
    `netcash` = COALESCE(`netcash`, 0)
WHERE `location` IS NULL OR `details` IS NULL OR `phone` IS NULL OR `bilti` IS NULL OR `bardana` IS NULL OR `netcash` IS NULL;

