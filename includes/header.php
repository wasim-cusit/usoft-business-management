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
            white-space: nowrap;
        }
        .navbar-brand:hover {
            transform: scale(1.05);
        }
        .navbar-nav {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar {
            background: #ffffff;
            min-height: calc(100vh - 70px);
            border-right: 1px solid #e5e7eb;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0;
        }
        [dir="rtl"] .sidebar {
            border-right: none;
            border-left: 1px solid #e5e7eb;
        }
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 2px;
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
        .sidebar .position-sticky {
            padding: 24px 0;
        }
        .sidebar .nav {
            padding: 0 12px;
            margin: 0;
        }
        .sidebar .nav-item {
            margin-bottom: 4px;
        }
        .sidebar .nav-link {
            color: #000000;
            padding: 10px 16px;
            border-radius: 8px;
            transition: none !important;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 500;
            position: relative;
            text-decoration: none;
            margin: 0;
            border: none;
        }
        .sidebar .nav-link > span {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }
        .sidebar .nav-link > span > i:first-child {
            width: 20px;
            text-align: center;
            font-size: 18px;
            flex-shrink: 0;
            color: #000000;
            transition: none !important;
        }
        .sidebar .nav-link .fa-chevron-right,
        .sidebar .nav-link .fa-chevron-left {
            font-size: 12px;
            transition: none !important;
            color: #000000;
            flex-shrink: 0;
        }
        [dir="rtl"] .sidebar .nav-link .fa-chevron-right,
        [dir="rtl"] .sidebar .nav-link .fa-chevron-left {
            margin-left: 0;
            margin-right: auto;
        }
        .sidebar .nav-link[aria-expanded="true"] .fa-chevron-right {
            transform: rotate(90deg);
        }
        .sidebar .nav-link[aria-expanded="true"] .fa-chevron-left {
            transform: rotate(-90deg);
        }
        .sidebar * {
            transition: none !important;
            animation: none !important;
        }
        .sidebar .nav-link,
        .sidebar .nav-link > span,
        .sidebar .nav-link > span > i,
        .sidebar .nav-link .fa-chevron-right,
        .sidebar .nav-link .fa-chevron-left {
            transition: none !important;
            animation: none !important;
        }
        .sidebar .nav-link:hover {
            background: #f3f4f6;
            color: #000000;
        }
        .sidebar .nav-link:hover > span > i:first-child {
            color: #000000;
        }
        .sidebar .nav-link.active {
            background: #f3f4f6;
            color: #000000;
            font-weight: 600;
        }
        .sidebar .nav-link.active > span > i:first-child {
            color: #000000;
        }
        .sidebar .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 20px;
            background: #000000;
            border-radius: 0 2px 2px 0;
        }
        [dir="rtl"] .sidebar .nav-link.active::before {
            left: auto;
            right: 0;
            border-radius: 2px 0 0 2px;
        }
        .sidebar .nav .nav {
            background: transparent;
            margin-top: 4px;
            margin-bottom: 4px;
            border-radius: 0;
            padding: 0;
            margin-left: 32px;
        }
        [dir="rtl"] .sidebar .nav .nav {
            margin-left: 0;
            margin-right: 32px;
        }
        .sidebar .nav .nav .nav-link {
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 400;
            color: #000000;
            border-radius: 6px;
            margin-left: 0;
        }
        [dir="rtl"] .sidebar .nav .nav .nav-link {
            margin-right: 0;
        }
        .sidebar .nav .nav .nav-link:hover {
            background: #f3f4f6;
            color: #000000;
        }
        .sidebar .nav .nav .nav-link.active {
            background: #f3f4f6;
            color: #000000;
            font-weight: 600;
        }
        .sidebar .nav .nav .nav-link.active::before {
            display: none;
        }
        <?php /*
        .sidebar .nav .nav .nav-link.active::before {
            content: '';
            position: absolute;
            left: 30px;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            background: #667eea;
            border-radius: 2px;
        }
        [dir="rtl"] .sidebar .nav .nav .nav-link.active::before {
            left: auto;
            right: 30px;
        }
        */ ?>
        .collapse {
            transition: none !important;
        }
        .collapse.show {
            display: block;
            animation: none !important;
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
            transition: none;
        }
        .table tbody tr:hover {
            background-color: #f8f9ff;
            transform: none;
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
            padding: 20px 15px;
            border-radius: 16px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(102, 126, 234, 0.1);
            position: relative;
            overflow: hidden;
            min-height: 176px;
            height: 100%;
            width: 100%;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }
        .stat-card .d-flex {
            padding: 0;
            margin: 0;
        }
        .stat-card h6 {
            font-size: 13px;
            margin-bottom: 8px;
            line-height: 1.4;
            word-wrap: break-word;
        }
        .stat-card h3 {
            font-size: 20px;
            font-weight: 700;
            line-height: 1.2;
            word-break: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
        }
        .stat-card .icon {
            flex-shrink: 0;
            margin-left: 10px;
            margin-right: 0;
        }
        [dir="rtl"] .stat-card .icon {
            margin-left: 0;
            margin-right: 10px;
        }
        .stat-card > div > div:first-child {
            flex: 1;
            min-width: 0;
            padding-right: 5px;
        }
        [dir="rtl"] .stat-card > div > div:first-child {
            padding-right: 0;
            padding-left: 5px;
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
        
        /* Ensure equal-sized stat cards */
        .row.mb-4 > .col-md-3.mb-3 {
            display: flex;
        }
        .row.mb-4 > .col-md-3.mb-3 > .stat-card {
            flex: 1;
            min-height: 176px;
        }
        .stat-card > .btn {
            margin-top: auto;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 70px;
                width: 280px;
                max-width: 85vw;
                height: calc(100vh - 70px);
                z-index: 1000;
                box-shadow: 4px 0 20px rgba(0,0,0,0.15);
            }
            [dir="rtl"] .sidebar {
                right: -100%;
                transition: right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            }
            [dir="rtl"] .sidebar.show {
                right: 0;
            }
            [dir="ltr"] .sidebar {
                left: -100%;
                transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            }
            [dir="ltr"] .sidebar.show {
                left: 0;
            }
            .sidebar .nav-link {
                padding: 14px 20px;
                font-size: 15px;
            }
            .sidebar .nav .nav .nav-link {
                padding: 12px 20px 12px 50px;
            }
            [dir="rtl"] .sidebar .nav .nav .nav-link {
                padding: 12px 50px 12px 20px;
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
        
        /* Global Notification System - Fixed Position Top Left */
        #pageNotification {
            position: fixed;
            top: 80px;
            left: 20px;
            z-index: 9999;
            max-width: 420px;
            animation: slideInNotification 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        /* RTL Support - Keep on left */
        [dir="rtl"] #pageNotification {
            left: 20px;
            right: auto;
        }
        [dir="ltr"] #pageNotification {
            left: 20px;
            right: auto;
        }
        #pageNotification .alert {
            margin-bottom: 0;
            border-radius: 12px;
            padding: 16px 20px;
            font-weight: 500;
            font-size: 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 14px;
            border: none;
            position: relative;
            overflow: hidden;
        }
        #pageNotification .alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            border-radius: 12px 0 0 12px;
        }
        [dir="rtl"] #pageNotification .alert::before {
            left: auto;
            right: 0;
            border-radius: 0 12px 12px 0;
        }
        #pageNotification .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        [dir="rtl"] #pageNotification .alert-success {
            border-left: none;
            border-right: 4px solid #10b981;
        }
        #pageNotification .alert-success::before {
            background: #10b981;
        }
        #pageNotification .alert-success i {
            color: #10b981;
        }
        #pageNotification .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        [dir="rtl"] #pageNotification .alert-danger {
            border-left: none;
            border-right: 4px solid #ef4444;
        }
        #pageNotification .alert-danger::before {
            background: #ef4444;
        }
        #pageNotification .alert-danger i {
            color: #ef4444;
        }
        #pageNotification .alert-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }
        [dir="rtl"] #pageNotification .alert-warning {
            border-left: none;
            border-right: 4px solid #f59e0b;
        }
        #pageNotification .alert-warning::before {
            background: #f59e0b;
        }
        #pageNotification .alert-warning i {
            color: #f59e0b;
        }
        #pageNotification .alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        [dir="rtl"] #pageNotification .alert-info {
            border-left: none;
            border-right: 4px solid #3b82f6;
        }
        #pageNotification .alert-info::before {
            background: #3b82f6;
        }
        #pageNotification .alert-info i {
            color: #3b82f6;
        }
        #pageNotification .alert i {
            font-size: 22px;
            flex-shrink: 0;
            width: 24px;
            text-align: center;
        }
        #pageNotification .alert span {
            flex: 1;
            line-height: 1.5;
        }
        #pageNotification .alert .btn-close {
            margin-left: auto;
            margin-right: 0;
            padding: 0.5rem;
            opacity: 0.7;
            transition: opacity 0.2s ease;
            background-size: 12px;
        }
        #pageNotification .alert .btn-close:hover {
            opacity: 1;
        }
        [dir="rtl"] #pageNotification .alert .btn-close {
            margin-left: 0;
            margin-right: auto;
        }
        @keyframes slideInNotification {
            from {
                opacity: 0;
                transform: translateX(-100px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }
        /* Mobile Responsive for Notifications */
        @media (max-width: 768px) {
            #pageNotification {
                max-width: calc(100% - 40px);
                top: 70px;
                left: 20px;
                right: 20px;
            }
            #pageNotification .alert {
                padding: 14px 18px;
                font-size: 13px;
            }
            #pageNotification .alert i {
                font-size: 20px;
            }
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
        
        /* Equal Height Cards */
        .row.equal-height {
            display: flex;
            flex-wrap: wrap;
        }
        .row.equal-height > [class*='col-'] {
            display: flex;
            flex-direction: column;
        }
        .row.equal-height > [class*='col-'] > .card {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .row.equal-height > [class*='col-'] > .card > .card-body {
            flex: 1;
        }
        .row.equal-height > [class*='col-'] > .stat-card {
            flex: 1;
            height: 100%;
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
        
        /* RTL Modal Fixes */
        [dir="rtl"] .modal-header {
            flex-direction: row-reverse;
        }
        [dir="rtl"] .modal-header .btn-close {
            margin-left: 0;
            margin-right: auto;
        }
        [dir="ltr"] .modal-header .btn-close {
            margin-left: auto;
            margin-right: 0;
        }
        [dir="rtl"] .modal-footer {
            flex-direction: row-reverse;
        }
        [dir="rtl"] .modal-footer .btn {
            margin-left: 0.5rem;
            margin-right: 0;
        }
        [dir="rtl"] .modal-footer .btn:first-child {
            margin-left: 0;
        }
        [dir="ltr"] .modal-footer .btn {
            margin-right: 0.5rem;
            margin-left: 0;
        }
        [dir="ltr"] .modal-footer .btn:first-child {
            margin-right: 0;
        }
        [dir="rtl"] .modal-title {
            margin-right: 0;
            margin-left: auto;
        }
        [dir="ltr"] .modal-title {
            margin-left: 0;
            margin-right: auto;
        }
        /* RTL Form Alignment in Modals */
        [dir="rtl"] .modal-body .form-label {
            text-align: right;
        }
        [dir="ltr"] .modal-body .form-label {
            text-align: left;
        }
        [dir="rtl"] .modal-body .form-control,
        [dir="rtl"] .modal-body .form-select {
            text-align: right;
        }
        [dir="ltr"] .modal-body .form-control,
        [dir="ltr"] .modal-body .form-select {
            text-align: left;
        }
        /* RTL Table Alignment in Modals */
        [dir="rtl"] .modal-body .table {
            text-align: right;
        }
        [dir="ltr"] .modal-body .table {
            text-align: left;
        }
        [dir="rtl"] .modal-body .text-end {
            text-align: left !important;
        }
        [dir="ltr"] .modal-body .text-end {
            text-align: right !important;
        }
        [dir="rtl"] .modal-body .text-start {
            text-align: right !important;
        }
        [dir="ltr"] .modal-body .text-start {
            text-align: left !important;
        }
        /* RTL Card Header Alignment */
        [dir="rtl"] .card-header {
            text-align: right;
        }
        [dir="ltr"] .card-header {
            text-align: left;
        }
        [dir="rtl"] .card-header .row {
            flex-direction: row-reverse;
        }
        /* RTL Modal Header and Card Header justify-content-between */
        [dir="rtl"] .modal-header.d-flex.justify-content-between,
        [dir="rtl"] .card-header .d-flex.justify-content-between,
        [dir="rtl"] .page-header .d-flex.justify-content-between {
            flex-direction: row-reverse;
        }
        /* RTL Button Groups */
        [dir="rtl"] .btn-group {
            flex-direction: row-reverse;
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
            <a class="navbar-brand <?php echo getDir() == 'rtl' ? 'order-last' : 'order-first'; ?>" href="<?php echo BASE_URL; ?>index.php">
                <i class="fas fa-store"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav <?php echo getDir() == 'rtl' ? 'me-auto' : 'ms-auto'; ?>">
                    <!-- Language Switcher -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-language"></i> <?php echo getLang() == 'ur' ? t('urdu') : t('english'); ?>
                        </a>
                        <ul class="dropdown-menu <?php echo getDir() == 'rtl' ? 'dropdown-menu-start' : 'dropdown-menu-end'; ?>" aria-labelledby="languageDropdown">
                            <li>
                                <a class="dropdown-item <?php echo getLang() == 'ur' ? 'active' : ''; ?>" href="?lang=ur">
                                    <i class="fas fa-check" style="<?php echo getLang() == 'ur' ? '' : 'display:none;'; ?>"></i> <?php echo t('urdu'); ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo getLang() == 'en' ? 'active' : ''; ?>" href="?lang=en">
                                    <i class="fas fa-check" style="<?php echo getLang() == 'en' ? '' : 'display:none;'; ?>"></i> <?php echo t('english'); ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu <?php echo getDir() == 'rtl' ? 'dropdown-menu-start' : 'dropdown-menu-end'; ?>">
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
                        <?php
                        $currentPage = basename($_SERVER['PHP_SELF']);
                        $currentPath = $_SERVER['PHP_SELF'];
                        $currentDir = dirname($currentPath);
                        $currentDir = str_replace('\\', '/', $currentDir);
                        $currentDir = trim($currentDir, '/');
                        
                        // More accurate detection using full path
                        $isAccounts = (strpos($currentPath, '/accounts/') !== false || strpos($currentPath, 'accounts/') !== false || strpos($currentPath, 'accounts\\') !== false);
                        $isItems = (strpos($currentPath, '/items/') !== false || strpos($currentPath, 'items/') !== false || strpos($currentPath, 'items\\') !== false);
                        $isPurchases = (strpos($currentPath, '/purchases/') !== false || strpos($currentPath, 'purchases/') !== false || strpos($currentPath, 'purchases\\') !== false);
                        $isSales = (strpos($currentPath, '/sales/') !== false || strpos($currentPath, 'sales/') !== false || strpos($currentPath, 'sales\\') !== false);
                        $isTransactions = (strpos($currentPath, '/transactions/') !== false || strpos($currentPath, 'transactions/') !== false || strpos($currentPath, 'transactions\\') !== false);
                        $isReports = (strpos($currentPath, '/reports/') !== false || strpos($currentPath, 'reports/') !== false || strpos($currentPath, 'reports\\') !== false);
                        
                        // Determine which menu should be open (but don't mark parent as active if child is active)
                        $accountsOpen = $isAccounts;
                        $itemsOpen = $isItems;
                        $purchasesOpen = $isPurchases;
                        $salesOpen = $isSales;
                        $transactionsOpen = $isTransactions;
                        $reportsOpen = $isReports;
                        
                        // Check if we're on a child page (not just the parent)
                        $isChildPage = ($isAccounts || $isItems || $isPurchases || $isSales || $isTransactions || $isReports) && $currentPage != 'index.php';
                        ?>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>index.php">
                                <span><i class="fas fa-home"></i> <?php echo t('home'); ?></span>
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage == 'list.php' && $isAccounts) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>accounts/list.php">
                                <span><i class="fas fa-users"></i> <?php echo t('accounts'); ?></span>
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage == 'list.php' && $isItems) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>items/list.php">
                                <span><i class="fas fa-box"></i> <?php echo t('items'); ?></span>
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage == 'list.php' && $isPurchases) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>purchases/list.php">
                                <span><i class="fas fa-shopping-cart"></i> <?php echo t('purchases'); ?></span>
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($salesOpen && !$isChildPage) ? 'active' : ''; ?>" data-bs-toggle="collapse" href="#saleMenu" aria-expanded="<?php echo $salesOpen ? 'true' : 'false'; ?>">
                                <span><i class="fas fa-cash-register"></i> <?php echo t('sales'); ?></span>
                                <i class="fas fa-chevron-<?php echo getDir() == 'rtl' ? 'left' : 'right'; ?>"></i>
                            </a>
                            <div class="collapse <?php echo $salesOpen ? 'show' : ''; ?>" id="saleMenu">
                                <ul class="nav flex-column">
                                    <!-- <li><a class="nav-link <?php echo ($currentPage == 'create.php' && $isSales) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>sales/create.php"><?php echo t('add_sale'); ?></a></li> -->
                                    <li><a class="nav-link <?php echo ($currentPage == 'list.php' && $isSales) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>sales/list.php"><?php echo t('all_sales'); ?></a></li>
                                </ul>
                            </div>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($transactionsOpen && !$isChildPage) ? 'active' : ''; ?>" data-bs-toggle="collapse" href="#paymentMenu" aria-expanded="<?php echo $transactionsOpen ? 'true' : 'false'; ?>">
                                <span><i class="fas fa-money-bill-wave"></i> <?php echo t('transactions'); ?></span>
                                <i class="fas fa-chevron-<?php echo getDir() == 'rtl' ? 'left' : 'right'; ?>"></i>
                            </a>
                            <div class="collapse <?php echo $transactionsOpen ? 'show' : ''; ?>" id="paymentMenu">
                                <ul class="nav flex-column">
                                    <li><a class="nav-link <?php echo ($currentPage == 'debit.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>transactions/debit.php"><?php echo t('debit'); ?></a></li>
                                    <li><a class="nav-link <?php echo ($currentPage == 'credit.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>transactions/credit.php"><?php echo t('credit'); ?></a></li>
                                    <?php /* Commented out Journal/Cash JV link - user requested
                                    <li><a class="nav-link <?php echo ($currentPage == 'journal.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>transactions/journal.php"><?php echo t('journal'); ?></a></li>
                                    */ ?>
                                    <li><a class="nav-link <?php echo ($currentPage == 'list.php' && $isTransactions) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>transactions/list.php"><?php echo t('all_transactions'); ?></a></li>
                                    <li><a class="nav-link <?php echo ($currentPage == 'stock-exchange.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>transactions/stock-exchange.php"><?php echo t('stock_exchange'); ?></a></li>
                                </ul>
                            </div>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($reportsOpen && !$isChildPage) ? 'active' : ''; ?>" data-bs-toggle="collapse" href="#reportsMenu" aria-expanded="<?php echo $reportsOpen ? 'true' : 'false'; ?>">
                                <span><i class="fas fa-chart-bar"></i> <?php echo t('reports'); ?></span>
                                <i class="fas fa-chevron-<?php echo getDir() == 'rtl' ? 'left' : 'right'; ?>"></i>
                            </a>
                            <div class="collapse <?php echo $reportsOpen ? 'show' : ''; ?>" id="reportsMenu">
                                <ul class="nav flex-column">
                                    <li><a class="nav-link <?php echo ($currentPage == 'party-ledger.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>reports/party-ledger.php"><?php echo t('party_ledger'); ?></a></li>
                                    <li><a class="nav-link <?php echo ($currentPage == 'stock-detail.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>reports/stock-detail.php"><?php echo t('stock_detail'); ?></a></li>
                                    <li><a class="nav-link <?php echo ($currentPage == 'stock-ledger.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>reports/stock-ledger.php"><?php echo t('stock_ledger'); ?></a></li>
                                    <li><a class="nav-link <?php echo ($currentPage == 'all-bills.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>reports/all-bills.php"><?php echo t('all_bills'); ?></a></li>
                                    <li><a class="nav-link <?php echo ($currentPage == 'stock-check.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>reports/stock-check.php"><?php echo t('stock_check'); ?></a></li>
                                    <li><a class="nav-link <?php echo ($currentPage == 'balance-sheet.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>reports/balance-sheet.php"><?php echo t('balance_sheet'); ?></a></li>
                                    <li><a class="nav-link <?php echo ($currentPage == 'cash-book.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>reports/cash-book.php"><?php echo t('cash_book'); ?></a></li>
                                    <li><a class="nav-link <?php echo ($currentPage == 'daily-book.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>reports/daily-book.php"><?php echo t('daily_book'); ?></a></li>
                                    <li><a class="nav-link <?php echo ($currentPage == 'loan-slip.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>reports/loan-slip.php"><?php echo t('loan_slip'); ?></a></li>
                                    <li><a class="nav-link <?php echo ($currentPage == 'rate-list.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>reports/rate-list.php"><?php echo t('rate_list'); ?></a></li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 <?php echo getDir() == 'rtl' ? 'ms-sm-auto' : 'me-sm-auto'; ?> col-lg-10 main-content">
                <!-- Global Notification Area - Fixed Position -->
                <div id="pageNotification" style="display: none;"></div>
