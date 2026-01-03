# Laravel 11 Migration Guide
## Business Management System - Yusuf & Co (یوسف اینڈ کو)

## Overview
This document outlines the complete migration from custom PHP to Laravel 11.

## Migration Steps

### 1. Install Laravel 11
```bash
composer create-project laravel/laravel usoft-laravel "^11.0"
cd usoft-laravel
```

### 2. Database Configuration
- Update `.env` file with database credentials
- Run migrations to create database structure

### 3. Project Structure
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   ├── AccountController.php
│   │   ├── ItemController.php
│   │   ├── PurchaseController.php
│   │   ├── SaleController.php
│   │   ├── TransactionController.php
│   │   ├── ReportController.php
│   │   └── DashboardController.php
│   ├── Middleware/
│   │   └── RequireLogin.php
│   └── Requests/
├── Models/
│   ├── User.php
│   ├── UserType.php
│   ├── Account.php
│   ├── Item.php
│   ├── Purchase.php
│   ├── Sale.php
│   ├── Transaction.php
│   └── StockMovement.php
├── Services/
└── Helpers/

database/
├── migrations/
└── seeders/

resources/
├── views/
│   ├── layouts/
│   ├── auth/
│   ├── accounts/
│   ├── items/
│   ├── purchases/
│   ├── sales/
│   ├── transactions/
│   └── reports/
└── lang/
    ├── en/
    └── ur/

routes/
├── web.php
└── api.php

public/
└── assets/ (migrated from old assets folder)
```

## Features to Migrate
- ✅ Authentication System
- ✅ Accounts Management
- ✅ Items Management
- ✅ Purchases & Sales
- ✅ Transactions
- ✅ Reports
- ✅ Bilingual Support (English/Urdu)
- ✅ Stock Management
- ✅ SMS Integration

## Next Steps
Follow the migration files created in this directory.

