-- Migration: Remove user_types system completely
-- This removes the user_type_id column from accounts table and drops the user_types table
-- Since accounts can be both customer and supplier, user types are no longer needed

SET FOREIGN_KEY_CHECKS = 0;

-- Step 1: Drop the foreign key constraint on accounts.user_type_id
ALTER TABLE `accounts` 
DROP FOREIGN KEY IF EXISTS `accounts_ibfk_1`;

-- Step 2: Drop the index on user_type_id
ALTER TABLE `accounts` 
DROP INDEX IF EXISTS `user_type_id`;

-- Step 3: Remove the user_type_id column from accounts table
ALTER TABLE `accounts` 
DROP COLUMN IF EXISTS `user_type_id`;

-- Step 4: Drop the user_types table
DROP TABLE IF EXISTS `user_types`;

SET FOREIGN_KEY_CHECKS = 1;

