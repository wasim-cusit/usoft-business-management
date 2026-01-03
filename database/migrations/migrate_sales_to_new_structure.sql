-- Migration: Migrate existing sales to new structure
-- Date: 2026-01-03
-- Description: Migrates old sales data to use new calculation fields (qty, narch, bag, wt, kate, wt2)
--              and removes dependency on old quantity field

-- Step 1: Migrate existing sale_items data
-- For items that have quantity but missing new fields, populate new fields from quantity
UPDATE `sale_items` 
SET 
    `qty` = CASE WHEN `qty` = 0 OR `qty` IS NULL THEN `quantity` ELSE `qty` END,
    `narch` = CASE WHEN `narch` IS NULL THEN 0 ELSE `narch` END,
    `bag` = CASE WHEN `bag` IS NULL THEN 0 ELSE `bag` END,
    `wt` = CASE 
        WHEN `wt` = 0 OR `wt` IS NULL THEN 
            (CASE WHEN `qty` = 0 OR `qty` IS NULL THEN `quantity` ELSE `qty` END) + 
            COALESCE(`narch`, 0) + 
            COALESCE(`bag`, 0)
        ELSE `wt` 
    END,
    `kate` = CASE WHEN `kate` IS NULL THEN 0 ELSE `kate` END,
    `wt2` = CASE 
        WHEN `wt2` = 0 OR `wt2` IS NULL THEN 
            (CASE WHEN `wt` = 0 OR `wt` IS NULL THEN 
                (CASE WHEN `qty` = 0 OR `qty` IS NULL THEN `quantity` ELSE `qty` END) + 
                COALESCE(`narch`, 0) + 
                COALESCE(`bag`, 0)
            ELSE `wt` END) - 
            COALESCE(`kate`, 0)
        ELSE `wt2` 
    END
WHERE `quantity` > 0 AND (`qty` = 0 OR `qty` IS NULL OR `wt2` = 0 OR `wt2` IS NULL);

-- Step 2: Ensure quantity field matches wt2 for consistency (keep quantity = wt2 for backward compatibility)
UPDATE `sale_items` 
SET `quantity` = `wt2` 
WHERE `wt2` > 0 AND (`quantity` != `wt2` OR `quantity` IS NULL);

-- Step 3: Update sales table - migrate old sales to have new fields populated
-- For sales without location/details/phone/bilti, set defaults or leave NULL
-- (These are optional fields, so no migration needed)

-- Step 4: Ensure all sale_items have proper new structure
-- Set defaults for any NULL values in new fields
UPDATE `sale_items` 
SET 
    `qty` = COALESCE(`qty`, 0),
    `narch` = COALESCE(`narch`, 0),
    `bag` = COALESCE(`bag`, 0),
    `wt` = COALESCE(`wt`, COALESCE(`qty`, 0) + COALESCE(`narch`, 0) + COALESCE(`bag`, 0)),
    `kate` = COALESCE(`kate`, 0),
    `wt2` = COALESCE(`wt2`, COALESCE(`wt`, COALESCE(`qty`, 0) + COALESCE(`narch`, 0) + COALESCE(`bag`, 0)) - COALESCE(`kate`, 0))
WHERE `qty` IS NULL OR `narch` IS NULL OR `bag` IS NULL OR `wt` IS NULL OR `kate` IS NULL OR `wt2` IS NULL;

