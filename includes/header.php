<!DOCTYPE html>
<html lang="<?php echo getLang() == 'ur' ? 'ur' : 'en'; ?>" dir="<?php echo getDir(); ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo isset($pageTitle) ? t($pageTitle) . ' - ' : ''; ?><?php echo t('app_name'); ?></title>
    
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
            font-family: <?php echo getLang() == 'ur' ? "'Almarai', 'Noto Nastaliq Urdu', 'Arial'" : "'Inter', 'Arial'"; ?>, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-size: 16px;
            direction: <?php echo getDir(); ?>;
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }
        .navbar-brand {
            font-weight: 700;
            color: white !important;
            font-size: 26px;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }
        .navbar-brand:hover {
            transform: scale(1.05);
        }
        .sidebar {
            background: #ffffff;
            min-height: calc(100vh - 70px);
            box-shadow: 3px 0 15px rgba(0,0,0,0.08);
        }
        [dir="rtl"] .sidebar {
            border-left: 1px solid #e0e0e0;
            box-shadow: 3px 0 15px rgba(0,0,0,0.08);
        }
        [dir="ltr"] .sidebar {
            border-right: 1px solid #e0e0e0;
            box-shadow: -3px 0 15px rgba(0,0,0,0.08);
        }
        .sidebar .nav-link {
            color: #495057;
            padding: 14px 25px;
            border-right: 4px solid transparent;
            border-left: 4px solid transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            border-radius: 0;
            margin: 2px 0;
        }
        [dir="rtl"] .sidebar .nav-link {
            border-right: 4px solid transparent;
            border-left: none;
        }
        [dir="ltr"] .sidebar .nav-link {
            border-left: 4px solid transparent;
            border-right: none;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            color: #667eea;
        }
        [dir="rtl"] .sidebar .nav-link:hover,
        [dir="rtl"] .sidebar .nav-link.active {
            border-right-color: #667eea;
            transform: translateX(-5px);
        }
        [dir="ltr"] .sidebar .nav-link:hover,
        [dir="ltr"] .sidebar .nav-link.active {
            border-left-color: #667eea;
            transform: translateX(5px);
        }
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            background: #fff;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0;
            padding: 20px 25px;
            font-weight: 600;
            font-size: 18px;
            border: none;
            position: relative;
            overflow: hidden;
        }
        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s infinite;
        }
        @keyframes shimmer {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            letter-spacing: 0.3px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
        }
        .btn-danger {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(235, 51, 73, 0.3);
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(235, 51, 73, 0.4);
        }
        .btn-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(240, 147, 251, 0.3);
        }
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(240, 147, 251, 0.4);
        }
        .btn-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
        }
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 172, 254, 0.4);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #868f96 0%, #596164 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(134, 143, 150, 0.3);
        }
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(134, 143, 150, 0.4);
        }
        .table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border-collapse: separate;
            border-spacing: 0;
        }
        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .table thead th {
            border: none;
            padding: 16px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid rgba(255,255,255,0.2);
        }
        .table tbody td {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        .table tbody tr {
            transition: all 0.2s ease;
        }
        .table tbody tr:hover {
            background-color: #f8f9ff;
            transform: scale(1.01);
        }
        .form-control, .form-select {
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            padding: 12px 18px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: #fff;
            font-size: 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            outline: none;
            transform: translateY(-1px);
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 14px;
            display: block;
        }
        .badge {
            padding: 8px 16px;
            font-weight: 600;
            border-radius: 20px;
            font-size: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .badge.bg-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
        }
        .badge.bg-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
        }
        .badge.bg-danger {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
        }
        .main-content {
            padding: 25px;
            background: transparent;
        }
        .page-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
            padding: 25px 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        .page-header h1 {
            margin: 0;
            color: #2d3748;
            font-weight: 700;
            font-size: 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(102, 126, 234, 0.1);
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        .stat-card .icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }
        .stat-card:hover .icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 70px;
                width: 280px;
                height: calc(100vh - 70px);
                z-index: 1000;
            }
            [dir="rtl"] .sidebar {
                right: -100%;
                transition: right 0.3s ease;
            }
            [dir="rtl"] .sidebar.show {
                right: 0;
            }
            [dir="ltr"] .sidebar {
                left: -100%;
                transition: left 0.3s ease;
            }
            [dir="ltr"] .sidebar.show {
                left: 0;
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
                z-index: 1001;
                background: rgba(255,255,255,0.2);
                border: none;
                color: white;
                padding: 8px 12px;
                border-radius: 5px;
            }
            [dir="rtl"] .sidebar-toggle {
                right: 15px;
            }
            [dir="ltr"] .sidebar-toggle {
                left: 15px;
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
        
        /* RTL Submenu Indentation */
        [dir="rtl"] .sidebar .nav .nav {
            padding-right: 20px;
            padding-left: 0;
        }
        [dir="ltr"] .sidebar .nav .nav {
            padding-left: 20px;
            padding-right: 0;
        }
        
        /* RTL Icon Spacing */
        [dir="rtl"] .nav-link i {
            margin-left: 10px;
            margin-right: 0;
        }
        [dir="ltr"] .nav-link i {
            margin-right: 10px;
            margin-left: 0;
        }
        
        /* Enhanced Alerts */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 16px 20px;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-success {
            background: linear-gradient(135deg, rgba(17, 153, 142, 0.1) 0%, rgba(56, 239, 125, 0.1) 100%);
            color: #11998e;
            border-left: 4px solid #11998e;
        }
        [dir="rtl"] .alert-success {
            border-left: none;
            border-right: 4px solid #11998e;
        }
        .alert-danger {
            background: linear-gradient(135deg, rgba(235, 51, 73, 0.1) 0%, rgba(245, 92, 67, 0.1) 100%);
            color: #eb3349;
            border-left: 4px solid #eb3349;
        }
        [dir="rtl"] .alert-danger {
            border-left: none;
            border-right: 4px solid #eb3349;
        }
        .alert-warning {
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.1) 0%, rgba(245, 87, 108, 0.1) 100%);
            color: #f5576c;
            border-left: 4px solid #f5576c;
        }
        [dir="rtl"] .alert-warning {
            border-left: none;
            border-right: 4px solid #f5576c;
        }
        .alert-info {
            background: linear-gradient(135deg, rgba(79, 172, 254, 0.1) 0%, rgba(0, 242, 254, 0.1) 100%);
            color: #4facfe;
            border-left: 4px solid #4facfe;
        }
        [dir="rtl"] .alert-info {
            border-left: none;
            border-right: 4px solid #4facfe;
        }
        
        /* Enhanced Pagination */
        .pagination {
            gap: 8px;
        }
        .pagination .page-link {
            border-radius: 10px;
            border: 2px solid #e1e8ed;
            padding: 10px 16px;
            font-weight: 600;
            color: #667eea;
            transition: all 0.3s ease;
            margin: 0 2px;
        }
        .pagination .page-link:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
        }
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        /* Enhanced Dropdowns */
        .dropdown-menu {
            border-radius: 12px;
            border: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            padding: 8px;
            margin-top: 8px;
        }
        .dropdown-item {
            border-radius: 8px;
            padding: 10px 16px;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        .dropdown-item:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            transform: translateX(5px);
        }
        [dir="rtl"] .dropdown-item:hover {
            transform: translateX(-5px);
        }
        
        /* Enhanced Input Groups */
        .input-group-text {
            font-weight: 600;
            color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }
        
        /* Enhanced Table Footer Alignment */
        [dir="rtl"] tfoot .text-end {
            text-align: left;
        }
        [dir="ltr"] tfoot .text-end {
            text-align: right;
        }
        
        /* Enhanced Form Row Spacing */
        .row .mb-3 {
            margin-bottom: 1.5rem !important;
        }
        
        /* Enhanced Card Body Padding */
        .card-body {
            padding: 25px;
        }
        
        /* Enhanced Button Groups */
        .btn-group .btn {
            margin: 0 4px;
        }
        .btn-group .btn:first-child {
            margin-left: 0;
        }
        .btn-group .btn:last-child {
            margin-right: 0;
        }
        [dir="rtl"] .btn-group .btn:first-child {
            margin-right: 0;
            margin-left: 4px;
        }
        [dir="rtl"] .btn-group .btn:last-child {
            margin-left: 0;
            margin-right: 4px;
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
                <ul class="navbar-nav <?php echo getDir() == 'rtl' ? 'ms-auto' : 'me-auto'; ?>">
                    <!-- Language Switcher -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-language"></i> <?php echo getLang() == 'ur' ? 'اردو' : 'English'; ?>
                        </a>
                        <ul class="dropdown-menu <?php echo getDir() == 'rtl' ? 'dropdown-menu-end' : 'dropdown-menu-start'; ?>" aria-labelledby="languageDropdown">
                            <li>
                                <a class="dropdown-item <?php echo getLang() == 'ur' ? 'active' : ''; ?>" href="?lang=ur">
                                    <i class="fas fa-check" style="<?php echo getLang() == 'ur' ? '' : 'display:none;'; ?>"></i> اردو
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo getLang() == 'en' ? 'active' : ''; ?>" href="?lang=en">
                                    <i class="fas fa-check" style="<?php echo getLang() == 'en' ? '' : 'display:none;'; ?>"></i> English
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu <?php echo getDir() == 'rtl' ? 'dropdown-menu-end' : 'dropdown-menu-start'; ?>">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>users/create.php"><i class="fas fa-user-plus"></i> <?php echo t('create_user'); ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> <?php echo t('logout'); ?></a></li>
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
                                <i class="fas fa-home"></i> <span><?php echo t('home'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#accountsMenu">
                                <i class="fas fa-users"></i> <span><?php echo t('accounts'); ?></span> <i class="fas fa-chevron-<?php echo getDir() == 'rtl' ? 'left' : 'right'; ?> <?php echo getDir() == 'rtl' ? 'float-start' : 'float-end'; ?>"></i>
                            </a>
                            <div class="collapse" id="accountsMenu">
                                <ul class="nav flex-column <?php echo getDir() == 'rtl' ? 'ms-3' : 'me-3'; ?>">
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>accounts/create.php"><?php echo t('new_account'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>accounts/list.php"><?php echo t('customer_list'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>accounts/user-types.php"><?php echo t('add_user_type'); ?></a></li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#itemsMenu">
                                <i class="fas fa-box"></i> <span><?php echo t('items'); ?></span> <i class="fas fa-chevron-<?php echo getDir() == 'rtl' ? 'left' : 'right'; ?> <?php echo getDir() == 'rtl' ? 'float-start' : 'float-end'; ?>"></i>
                            </a>
                            <div class="collapse" id="itemsMenu">
                                <ul class="nav flex-column <?php echo getDir() == 'rtl' ? 'ms-3' : 'me-3'; ?>">
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>items/create.php"><?php echo t('create_item'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>items/list.php"><?php echo t('all_items'); ?></a></li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#purchaseMenu">
                                <i class="fas fa-shopping-cart"></i> <span><?php echo t('purchases'); ?></span> <i class="fas fa-chevron-<?php echo getDir() == 'rtl' ? 'left' : 'right'; ?> <?php echo getDir() == 'rtl' ? 'float-start' : 'float-end'; ?>"></i>
                            </a>
                            <div class="collapse" id="purchaseMenu">
                                <ul class="nav flex-column <?php echo getDir() == 'rtl' ? 'ms-3' : 'me-3'; ?>">
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>purchases/create.php"><?php echo t('add_purchase'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>purchases/list.php"><?php echo t('all_purchases'); ?></a></li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#saleMenu">
                                <i class="fas fa-cash-register"></i> <span><?php echo t('sales'); ?></span> <i class="fas fa-chevron-<?php echo getDir() == 'rtl' ? 'left' : 'right'; ?> <?php echo getDir() == 'rtl' ? 'float-start' : 'float-end'; ?>"></i>
                            </a>
                            <div class="collapse" id="saleMenu">
                                <ul class="nav flex-column <?php echo getDir() == 'rtl' ? 'ms-3' : 'me-3'; ?>">
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>sales/create.php"><?php echo t('add_sale'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>sales/list.php"><?php echo t('all_sales'); ?></a></li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#paymentMenu">
                                <i class="fas fa-money-bill-wave"></i> <span><?php echo t('transactions'); ?></span> <i class="fas fa-chevron-<?php echo getDir() == 'rtl' ? 'left' : 'right'; ?> <?php echo getDir() == 'rtl' ? 'float-start' : 'float-end'; ?>"></i>
                            </a>
                            <div class="collapse" id="paymentMenu">
                                <ul class="nav flex-column <?php echo getDir() == 'rtl' ? 'ms-3' : 'me-3'; ?>">
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>transactions/debit.php"><?php echo t('debit'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>transactions/credit.php"><?php echo t('credit'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>transactions/journal.php"><?php echo t('journal'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>transactions/list.php"><?php echo t('all_transactions'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>transactions/stock-exchange.php"><?php echo t('stock_exchange'); ?></a></li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#reportsMenu">
                                <i class="fas fa-chart-bar"></i> <span><?php echo t('reports'); ?></span> <i class="fas fa-chevron-<?php echo getDir() == 'rtl' ? 'left' : 'right'; ?> <?php echo getDir() == 'rtl' ? 'float-start' : 'float-end'; ?>"></i>
                            </a>
                            <div class="collapse" id="reportsMenu">
                                <ul class="nav flex-column <?php echo getDir() == 'rtl' ? 'ms-3' : 'me-3'; ?>">
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/party-ledger.php"><?php echo t('party_ledger'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/stock-detail.php"><?php echo t('stock_detail'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/stock-ledger.php"><?php echo t('stock_ledger'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/all-bills.php"><?php echo t('all_bills'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/stock-check.php"><?php echo t('stock_check'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/balance-sheet.php"><?php echo t('balance_sheet'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/cash-book.php"><?php echo t('cash_book'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/daily-book.php"><?php echo t('daily_book'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/loan-slip.php"><?php echo t('loan_slip'); ?></a></li>
                                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>reports/rate-list.php"><?php echo t('rate_list'); ?></a></li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 <?php echo getDir() == 'rtl' ? 'ms-sm-auto' : 'me-sm-auto'; ?> col-lg-10 main-content">
