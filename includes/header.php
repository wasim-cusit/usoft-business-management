<!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-rtl@3.4.0/dist/css/bootstrap-rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Urdu Support -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;500;600;700&family=Almarai:wght@300;400;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    
    <style>
        * {
            font-family: 'Almarai', 'Noto Nastaliq Urdu', 'Arial', sans-serif;
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-size: 16px;
            direction: rtl;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        .navbar-brand {
            font-weight: 700;
            color: white !important;
            font-size: 24px;
        }
        .sidebar {
            background: #ffffff;
            min-height: calc(100vh - 70px);
            box-shadow: 3px 0 15px rgba(0,0,0,0.08);
            border-left: 1px solid #e0e0e0;
        }
        .sidebar .nav-link {
            color: #495057;
            padding: 15px 25px;
            border-right: 4px solid transparent;
            transition: all 0.3s ease;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-right-color: #667eea;
            color: #667eea;
            transform: translateX(-5px);
        }
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 18px 25px;
            font-weight: 600;
            font-size: 18px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
        }
        .btn-danger {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            border: none;
        }
        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .table thead th {
            border: none;
            padding: 15px;
            font-weight: 600;
        }
        .table tbody tr {
            transition: background 0.3s ease;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .badge {
            padding: 8px 15px;
            font-weight: 500;
            border-radius: 20px;
        }
        .main-content {
            padding: 25px;
            background: transparent;
        }
        .page-header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        .page-header h1 {
            margin: 0;
            color: #333;
            font-weight: 700;
            font-size: 28px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .stat-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 70px;
                right: -100%;
                width: 280px;
                height: calc(100vh - 70px);
                z-index: 1000;
                transition: right 0.3s ease;
            }
            .sidebar.show {
                right: 0;
            }
            .main-content {
                padding: 15px;
            }
            .page-header {
                padding: 15px;
            }
            .page-header h1 {
                font-size: 22px;
            }
            .page-header .d-flex {
                flex-direction: column;
                gap: 10px;
            }
            .stat-card {
                padding: 15px;
                margin-bottom: 15px;
            }
            .table {
                font-size: 14px;
            }
            .table thead th {
                padding: 10px 8px;
                font-size: 13px;
            }
            .table tbody td {
                padding: 10px 8px;
                font-size: 13px;
            }
            .card-header {
                padding: 15px;
                font-size: 16px;
            }
            .btn {
                padding: 8px 15px;
                font-size: 14px;
            }
            .navbar-brand {
                font-size: 18px;
            }
            .sidebar .nav-link {
                padding: 12px 20px;
                font-size: 14px;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 10px;
            }
            .page-header {
                padding: 12px;
            }
            .page-header h1 {
                font-size: 20px;
            }
            .stat-card .icon {
                width: 50px;
                height: 50px;
                font-size: 24px;
            }
            .table {
                font-size: 12px;
            }
            .table thead th,
            .table tbody td {
                padding: 8px 5px;
            }
        }
        
        /* Sidebar Toggle Button */
        .sidebar-toggle {
            display: none;
        }
        @media (max-width: 768px) {
            .sidebar-toggle {
                display: block;
                position: fixed;
                top: 15px;
                right: 15px;
                z-index: 1001;
                background: rgba(255,255,255,0.2);
                border: none;
                color: white;
                padding: 8px 12px;
                border-radius: 5px;
            }
        }
        
        /* Overlay for mobile sidebar */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 70px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .sidebar-overlay.show {
            display: block;
        }
    </style>
</head>
<body>
    <?php requireLogin(); ?>
    
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <button class="sidebar-toggle d-md-none" type="button" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>index.php">
                <i class="fas fa-store"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>users/create.php"><i class="fas fa-user-plus"></i> نیا صارف بنائیں</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> لاگ آؤٹ</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar p-0" id="sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>index.php">
                                <i class="fas fa-home"></i> <span>ہوم</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#accountsMenu">
                                <i class="fas fa-users"></i> <span>کھاتے</span> <i class="fas fa-chevron-left float-start"></i>
                            </a>
                            <div class="collapse" id="accountsMenu">
                                <ul class="nav flex-column ms-3">
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>accounts/create.php">نیا کھاتہ</a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>accounts/list.php">کسٹمر لسٹ</a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>accounts/user-types.php">یوزر ٹائپ شامل کریں</a></li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#itemsMenu">
                                <i class="fas fa-box"></i> <span>جنس</span> <i class="fas fa-chevron-left float-start"></i>
                            </a>
                            <div class="collapse" id="itemsMenu">
                                <ul class="nav flex-column ms-3">
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>items/create.php">جنس بنائیں</a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>items/list.php">تمام جنس لسٹ</a></li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#purchaseMenu">
                                <i class="fas fa-shopping-cart"></i> <span>مال آمد</span> <i class="fas fa-chevron-left float-start"></i>
                            </a>
                            <div class="collapse" id="purchaseMenu">
                                <ul class="nav flex-column ms-3">
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>purchases/create.php">مال آمد شامل کریں</a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>purchases/list.php">تمام مال آمد لسٹ</a></li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#saleMenu">
                                <i class="fas fa-cash-register"></i> <span>مال فروخت</span> <i class="fas fa-chevron-left float-start"></i>
                            </a>
                            <div class="collapse" id="saleMenu">
                                <ul class="nav flex-column ms-3">
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>sales/create.php">سیل شامل کریں</a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>sales/list.php">تمام سیل لسٹ</a></li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#paymentMenu">
                                <i class="fas fa-money-bill-wave"></i> <span>روزنامچہ</span> <i class="fas fa-chevron-left float-start"></i>
                            </a>
                            <div class="collapse" id="paymentMenu">
                                <ul class="nav flex-column ms-3">
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>transactions/debit.php">کیش بنام</a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>transactions/credit.php">کیش جمع</a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>transactions/journal.php">کیش JV</a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>transactions/list.php">تمام لین دین لسٹ</a></li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#reportsMenu">
                                <i class="fas fa-chart-bar"></i> <span>رپورٹس</span> <i class="fas fa-chevron-left float-start"></i>
                            </a>
                            <div class="collapse" id="reportsMenu">
                                <ul class="nav flex-column ms-3">
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/party-ledger.php">پارٹی لیجر</a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/stock-detail.php">سٹاک کھاتہ</a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/stock-ledger.php">سٹاک لیجر</a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/all-bills.php">تمام بل چٹھہ</a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/stock-check.php">مال چیک رپورٹ</a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/balance-sheet.php">بیلنس شیٹ</a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/cash-book.php">کیش بک</a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/daily-book.php">روزنامچہ</a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/loan-slip.php">قرضہ سلیپ & اگراھی</a></li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
