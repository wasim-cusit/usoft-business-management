# Sales Structure Migration Guide

## Overview
This migration converts the sales system from the old structure (using single `quantity` field) to the new structure (using detailed calculation fields: `qty`, `narch`, `bag`, `wt`, `kate`, `wt2`).

## Migration Order

Run these migrations in the following order:

### 1. Add New Fields (if not already done)
```sql
-- Run: add_sale_calculation_fields.sql
-- Adds: qty, narch, bag, wt, kate, wt2 to sale_items table
```

### 2. Add Additional Sales Fields (if not already done)
```sql
-- Run: add_sale_additional_fields.sql
-- Adds: location, details, phone, bilti, bardana, netcash to sales table
```

### 3. Migrate Existing Data
```sql
-- Run: migrate_sales_to_new_structure.sql
-- Migrates existing sales data from old quantity field to new calculation fields
```

### 4. Cleanup Old Structure
```sql
-- Run: cleanup_old_sale_structure.sql
-- Ensures all data uses new structure and removes NULL values
```

### 5. Update Schema Constraints (Optional but Recommended)
```sql
-- Run: update_schema_new_sale_structure.sql
-- Makes new fields NOT NULL with defaults to enforce new structure at database level
```

## What Changed

### Old Structure
- Single `quantity` field in `sale_items`
- Simple quantity-based calculations
- No detailed weight breakdown

### New Structure
- **qty** (نگ): Base quantity
- **narch** (توڈا/توڈی): Additional weight component
- **bag** (بھرتی): Bag/filling weight
- **wt** (وزن): Total weight = qty + narch + bag
- **kate** (کاٹ): Cut/deduction
- **wt2** (صافی): Net weight = wt - kate
- **quantity**: Now always equals wt2 (kept for compatibility)

### Additional Sales Fields
- **location** (لوکیشن اڈا): Location/address
- **details** (تفصیل): Additional details
- **phone** (Phn#): Phone number
- **bilti** (Bilti#): Bilti number
- **bardana**: Container/packaging cost
- **netcash**: Net cash amount

## Code Changes

All sales-related PHP files have been updated to:
1. Use only new calculation fields (qty, narch, bag, wt, kate, wt2)
2. Remove backward compatibility code
3. Set quantity = wt2 for consistency
4. Use wt2 for all stock calculations

## Files Updated
- `sales/create.php`
- `sales/edit.php`
- `sales/view.php`
- `sales/print.php`
- `sales/list.php`
- `sales/create-ajax.php`
- `sales/delete-ajax.php`

## Important Notes

1. **Quantity Field**: The `quantity` field is kept in the database but is now always set equal to `wt2`. This ensures compatibility with any remaining code that might reference it.

2. **Stock Calculations**: All stock calculations now use `wt2` (net weight) instead of the old `quantity` field.

3. **Data Migration**: Existing sales will be automatically migrated when you run the migration scripts. Old sales will have their `quantity` value copied to the new fields.

4. **Backward Compatibility**: All backward compatibility code has been removed. The system now requires the new structure.

## Verification

After running migrations, verify:
1. All `sale_items` have non-NULL values in qty, narch, bag, wt, kate, wt2
2. All `quantity` values equal `wt2` values
3. All `sales` have the new fields (location, details, phone, bilti, bardana, netcash)
4. Stock calculations work correctly with wt2

