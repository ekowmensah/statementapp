<?php
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
$pathParts = explode('?', $currentPath);
$currentPath = $pathParts[0];

function isActive($path, $currentPath) {
    return strpos($currentPath, $path) === 0 ? 'active' : '';
}
?>

<ul class="sidebar-nav" data-coreui="navigation" data-simplebar="">
    <li class="nav-item">
        <a class="nav-link <?= isActive('/dashboard', $currentPath) ?>" href="<?= Response::url('dashboard') ?>">
            <i class="nav-icon bi bi-speedometer2"></i>
            Dashboard
        </a>
    </li>
    
    <?php if (Auth::can('view_daily')): ?>
    <li class="nav-item">
        <a class="nav-link <?= isActive('/daily', $currentPath) ?>" href="<?= Response::url('daily') ?>">
            <i class="nav-icon bi bi-calendar-plus"></i>
            Daily Transactions
        </a>
    </li>
    <?php endif; ?>
    
    
    <?php if (Auth::can('view_statement')): ?>
    <li class="nav-item">
        <a class="nav-link <?= isActive('/statement', $currentPath) ?>" href="<?= Response::url('statement') ?>">
            <i class="nav-icon bi bi-table"></i>
            Statement View
        </a>
    </li>
    <?php endif; ?>
    
    <?php if (Auth::can('view_reports')): ?>
    <li class="nav-item">
        <a class="nav-link <?= isActive('/reports', $currentPath) ?>" href="<?= Response::url('reports') ?>">
            <i class="nav-icon bi bi-graph-up"></i>
            Reports
        </a>
    </li>
    <?php endif; ?>
    
    <?php if (Auth::can('manage_locks')): ?>
    <li class="nav-item">
        <a class="nav-link <?= isActive('/locks', $currentPath) ?>" href="<?= Response::url('locks') ?>">
            <i class="nav-icon bi bi-lock"></i>
            Month Locks
        </a>
    </li>
    <?php endif; ?>
    
    <li class="nav-divider"></li>
    
    <li class="nav-title">Export</li>
    
    <?php if (Auth::can('export_csv')): ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= Response::url('export/csv') ?>?month=<?= date('n') ?>&year=<?= date('Y') ?>">
            <i class="nav-icon bi bi-filetype-csv"></i>
            Export CSV
        </a>
    </li>
    <?php endif; ?>
    
    <?php if (Auth::can('export_pdf')): ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= Response::url('export/pdf') ?>?month=<?= date('n') ?>&year=<?= date('Y') ?>">
            <i class="nav-icon bi bi-filetype-pdf"></i>
            Export PDF
        </a>
    </li>
    <?php endif; ?>
    
    <?php if (Auth::isAdmin()): ?>
    <li class="nav-divider"></li>
    
    <li class="nav-title">Administration</li>
    
    <?php if (Auth::can('view_users')): ?>
    <li class="nav-item">
        <a class="nav-link <?= isActive('/users', $currentPath) ?>" href="<?= Response::url('users') ?>">
            <i class="nav-icon bi bi-people"></i>
            User Management
        </a>
    </li>
    <?php endif; ?>
    
    <li class="nav-item">
        <a class="nav-link" href="#" onclick="alert('System settings coming soon!')">
            <i class="nav-icon bi bi-gear"></i>
            Settings
        </a>
    </li>
    <?php endif; ?>
</ul>
