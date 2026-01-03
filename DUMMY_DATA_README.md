# Dummy Data Seeder

This script populates your database with realistic sample data to help you understand and test the Business Management System.

## What Data Will Be Added?

The seeder will add:

1. **User Types** (5 types)
   - Customer, Supplier, Both, Retailer, Wholesaler

2. **Users** (3 additional users)
   - manager / manager123
   - staff1 / staff123
   - staff2 / staff123

3. **Accounts** (9 accounts)
   - 5 Customers
   - 3 Suppliers
   - 1 Both (Customer & Supplier)

4. **Items** (15 products)
   - Food items (Rice, Sugar, Tea, Oil, etc.)
   - Personal care items (Soap, Shampoo, Toothpaste)
   - Cleaning products
   - All with Urdu names

5. **Purchases** (7 purchase transactions)
   - Various dates over the last 30 days
   - Multiple items per purchase
   - Different payment statuses

6. **Sales** (8 sale transactions)
   - Various dates over the last 30 days
   - Multiple items per sale
   - Different payment statuses

7. **Transactions** (Cash book entries)
   - Debit transactions (payments)
   - Credit transactions (receipts)

8. **Stock Movements**
   - Automatically created for all purchases and sales
   - Tracks stock in/out and balance

## How to Run

### Method 1: Via Browser
1. Open your browser
2. Navigate to: `http://localhost/usoft/seed-dummy-data.php`
3. The script will run and display progress

### Method 2: Via Command Line
```bash
cd C:\xampp\htdocs\usoft
php seed-dummy-data.php
```

## Important Notes

- **Safe to Run Multiple Times**: The script uses `INSERT IGNORE` for most data, so running it multiple times won't create duplicates
- **Transaction Safe**: All data is inserted in a transaction, so if any error occurs, all changes will be rolled back
- **Existing Data**: The script will not delete existing data, it only adds new data

## After Seeding

You can login with:
- **Username**: `adil`
- **Password**: `sana25`

Or use the new users:
- **Username**: `manager` / **Password**: `manager123`
- **Username**: `staff1` / **Password**: `staff123`
- **Username**: `staff2` / **Password**: `staff123`

## Exploring the Data

After seeding, you can:

1. **View Accounts**: Go to Accounts > Customer List
2. **View Items**: Go to Items > All Items List
3. **View Purchases**: Go to Purchases > All Purchases List
4. **View Sales**: Go to Sales > All Sales List
5. **View Transactions**: Go to Transactions > All Transactions List
6. **View Reports**: Check various reports like:
   - Party Ledger
   - Stock Ledger
   - Cash Book
   - Balance Sheet
   - Daily Book

## Sample Data Details

### Accounts Include:
- Customers with opening balances (debit/credit)
- Suppliers with contact information
- Both English and Urdu names

### Items Include:
- Various categories (Food, Personal Care, Cleaning)
- Different units (kg, liter, pcs, dozen)
- Purchase and sale rates
- Opening stock and minimum stock levels

### Purchases & Sales:
- Realistic quantities and amounts
- Partial payments (some paid, some balance)
- Discounts applied
- Linked to accounts and items

### Transactions:
- Cash debit (payments to suppliers)
- Cash credit (receipts from customers)
- Proper narration and references

## Troubleshooting

If you encounter errors:

1. **Database Connection Error**: Check `config/database.php` settings
2. **Foreign Key Errors**: Make sure you've run the schema.sql first
3. **Duplicate Key Errors**: Some data might already exist, which is fine

## Resetting Data

To start fresh:

1. Drop and recreate the database
2. Run `database/schema.sql` to create tables
3. Run `seed-dummy-data.php` to add sample data

---

**Note**: This is sample data for testing purposes only. Use appropriate data for production environments.

