<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?= htmlspecialchars($data['title'] ?? 'Daily Statement App') ?></title>
    
    <!-- Prevent caching of dynamic content -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= Response::url('favicon.svg') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= Response::url('icon-192.png') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= Response::url('icon-512.png') ?>">
    <link rel="apple-touch-icon" href="<?= Response::url('icon-192.png') ?>">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= Response::url('manifest.json') ?>">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Daily Statement">
    <meta name="msapplication-TileColor" content="#667eea">
    <meta name="msapplication-config" content="<?= Response::url('browserconfig.xml') ?>">
    
    <!-- CSRF Token -->
    <?= CSRF::meta() ?>
    
    <!-- CoreUI CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@coreui/coreui@4.2.6/dist/css/coreui.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@coreui/icons@3.0.1/css/all.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <style>
        .sidebar {
            --cui-sidebar-width: 256px;
        }
        
        /* Fix for content being hidden behind sidebar */
        body {
            padding-left: 0;
        }
        
        @media (min-width: 992px) {
            body {
                padding-left: 256px;
            }
            
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: 256px;
                z-index: 1030;
            }
            
            .wrapper {
                margin-left: 0;
                width: 100%;
            }
        }
        
        @media (max-width: 991.98px) {
            body {
                padding-left: 0;
            }
            
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: 256px;
                z-index: 1040;
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                background: var(--cui-sidebar-bg, #212529);
            }
            
            .sidebar.show {
                transform: translateX(0);
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            }
            
            /* Mobile overlay */
            .sidebar.show::before {
                content: '';
                position: fixed;
                top: 0;
                left: 256px;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: -1;
            }
            
            /* Ensure header toggle button is visible */
            .header-toggler {
                display: block !important;
                border: none;
                background: transparent;
                font-size: 1.25rem;
                color: var(--cui-body-color);
                padding: 0.5rem;
            }
            
            .header-toggler:hover {
                background: rgba(0, 0, 0, 0.1);
                border-radius: 0.25rem;
            }
        }
        
        .money-input {
            text-align: right;
        }
        
        .table-money {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .preview-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .preview-card .card-body {
            padding: 1.5rem;
        }
        
        .preview-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .preview-item:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .locked-badge {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .alert-dismissible .btn-close {
            position: absolute;
            top: 0;
            right: 0;
            z-index: 2;
            padding: 1.25rem 1rem;
        }
        
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 10;
            background: white;
        }
        
        /* Enhanced Mobile Responsiveness */
        @media (max-width: 768px) {
            .table-responsive .table td {
                white-space: nowrap;
                font-size: 0.875rem;
            }
            
            .mobile-stack {
                display: block !important;
            }
            
            .mobile-stack .col-md-6 {
                width: 100% !important;
                margin-bottom: 1rem;
            }
            
            /* Mobile-friendly headers */
            h1 { font-size: 1.75rem !important; }
            h2 { font-size: 1.5rem !important; }
            h3 { font-size: 1.25rem !important; }
            
            /* Mobile button groups */
            .btn-group {
                flex-wrap: wrap;
            }
            
            .btn-group .btn {
                margin-bottom: 0.25rem;
            }
            
            /* Mobile cards */
            .card {
                margin-bottom: 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            /* Mobile forms */
            .form-control, .form-select {
                font-size: 16px; /* Prevents zoom on iOS */
            }
            
            /* Mobile tables */
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .table th, .table td {
                padding: 0.5rem 0.25rem;
                vertical-align: middle;
            }
            
            /* Mobile navigation improvements */
            .header-nav .nav-item {
                margin: 0.25rem 0;
            }
            
            /* Mobile header layout */
            .header .container-fluid {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            /* Add Transaction button styling */
            .btn-add-transaction {
                white-space: nowrap;
            }
            
            /* Mobile spacing */
            .mb-4 { margin-bottom: 1.5rem !important; }
            .mb-3 { margin-bottom: 1rem !important; }
            
            /* Mobile text sizing */
            .fs-5 { font-size: 1rem !important; }
            .fs-4 { font-size: 1.1rem !important; }
            
            /* Mobile container padding */
            .container-lg {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            /* Extra small devices */
            body {
                font-size: 0.875rem;
            }
            
            h1 { font-size: 1.5rem !important; }
            h2 { font-size: 1.25rem !important; }
            
            .btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
            }
            
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
            
            /* Stack columns on very small screens */
            .col-md-3, .col-md-4, .col-md-6 {
                margin-bottom: 1rem;
            }
            
            /* Mobile-friendly dropdowns */
            .dropdown-menu {
                font-size: 0.875rem;
            }
            
            /* Compact table for mobile */
            .table-sm th, .table-sm td {
                padding: 0.25rem;
                font-size: 0.75rem;
            }
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
    </style>
</head>

<body>
    <div class="sidebar sidebar-dark sidebar-fixed" id="sidebar">
        <div class="sidebar-brand d-md-flex">
            <div class="sidebar-brand-full">
                <i class="bi bi-calculator"></i>
                Daily Statement
            </div>
            <div class="sidebar-brand-minimized">
                <i class="bi bi-calculator"></i>
            </div>
        </div>
        
        <?php include __DIR__ . '/../partials/nav.php'; ?>
        
        <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
    </div>

    <div class="wrapper d-flex flex-column min-vh-100 bg-light">
        <header class="header header-sticky mb-4">
            <div class="container-fluid">
                <button class="header-toggler px-md-0 me-md-3 d-md-none" type="button" id="mobile-menu-toggle">
                    <i class="bi bi-list"></i>
                </button>
                
                <!-- Mobile Add Transaction Button -->
                <div class="d-md-none">
                    <a href="<?= Response::url('daily/create') ?>" class="btn btn-primary btn-sm btn-add-transaction">
                        <i class="bi bi-plus-circle"></i>
                    </a>
                </div>
                
                <div class="header-nav d-none d-md-flex">
                    <div class="nav-item">
                        <span class="nav-link">
                            <i class="bi bi-calendar-date"></i>
                            <?= date('F j, Y') ?>
                        </span>
                    </div>
                </div>
                
                <div class="header-nav ms-auto">
                    <div class="nav-item">
                        <a href="<?= Response::url('daily/create') ?>" class="btn btn-primary btn-sm me-3 btn-add-transaction">
                            <i class="bi bi-plus-circle me-1"></i>
                            Add Transaction
                        </a>
                    </div>
                    <div class="nav-item dropdown">
                        <a class="nav-link py-0" data-coreui-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                            <div class="avatar avatar-md">
                                <i class="bi bi-person-circle fs-4"></i>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end pt-0 pr-5 w-auto">
                            <div class="dropdown-header bg-light py-2">
                                <div class="fw-semibold"><?= htmlspecialchars(Auth::user()['name'] ?? 'User') ?></div>
                                <div class="text-medium-emphasis small">
                                    <?= htmlspecialchars(Auth::user()['email'] ?? '') ?>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="<?= Response::url('logout') ?>" class="d-inline" id="logoutForm">
                                <?= CSRF::field() ?>
                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    Logout
                                </button>
                            </form>
                            <a href="<?= Response::url('logout') ?>" class="dropdown-item d-none" id="logoutFallback">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Logout (Fallback)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="body flex-grow-1 px-3">
            <div class="container-lg">
                <?php include __DIR__ . '/../partials/flash.php'; ?>
