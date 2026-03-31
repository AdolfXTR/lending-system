<?php
// ============================================================
//  includes/modern_header.php
//  Reusable header/navbar component
//  Usage: <?php require_once 'includes/modern_header.php'; ?>
// ============================================================

$pageTitle = $pageTitle ?? 'Dashboard';
$appName   = APP_NAME ?? 'LendingSystem';
$userName  = htmlspecialchars(($_SESSION['first_name'] ?? 'User') . ' ' . ($_SESSION['last_name'] ?? ''));
$userInitials = strtoupper(substr($_SESSION['first_name'] ?? 'U', 0, 1) . substr($_SESSION['last_name'] ?? 'S', 0, 1));
$isAdmin   = !empty($_SESSION['admin_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/modern.css">
    <style>
        /* Page-specific styles can be added here */
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar__header">
                <div class="sidebar__logo">💰</div>
                <div>
                    <div class="sidebar__title"><?= $appName ?></div>
                    <div class="sidebar__subtitle"><?= $isAdmin ? 'Admin' : 'Dashboard' ?></div>
                </div>
            </div>

            <nav class="sidebar__menu">
                <?php if ($isAdmin): ?>
                    <!-- Admin Menu -->
                    
                    <li class="sidebar__section-title">Main</li>
                    
                    <li class="sidebar__item">
                        <a href="<?= APP_URL ?>/admin/dashboard.php" class="sidebar__link <?= ($pageTitle === 'Dashboard' ? 'active' : '') ?>">
                            <span class="sidebar__icon">📊</span>
                            Dashboard
                        </a>
                    </li>

                    <li class="sidebar__section-title">Management</li>

                    <li class="sidebar__item">
                        <a href="<?= APP_URL ?>/admin/registrations/index.php" class="sidebar__link <?= (strpos($_SERVER['REQUEST_URI'], 'registrations') !== false ? 'active' : '') ?>">
                            <span class="sidebar__icon">👥</span>
                            Registrations
                        </a>
                    </li>

                    <li class="sidebar__item">
                        <a href="<?= APP_URL ?>/admin/users/index.php" class="sidebar__link <?= (strpos($_SERVER['REQUEST_URI'], 'users') !== false ? 'active' : '') ?>">
                            <span class="sidebar__icon">👤</span>
                            Users
                        </a>
                    </li>

                    <li class="sidebar__item">
                        <a href="<?= APP_URL ?>/admin/loans/index.php" class="sidebar__link <?= (strpos($_SERVER['REQUEST_URI'], 'loans') !== false ? 'active' : '') ?>">
                            <span class="sidebar__icon">📋</span>
                            Loans
                        </a>
                    </li>

                    <li class="sidebar__item">
                        <a href="<?= APP_URL ?>/admin/billing/index.php" class="sidebar__link <?= (strpos($_SERVER['REQUEST_URI'], 'billing') !== false ? 'active' : '') ?>">
                            <span class="sidebar__icon">💳</span>
                            Billing
                        </a>
                    </li>

                    <li class="sidebar__item">
                        <a href="<?= APP_URL ?>/admin/savings/index.php" class="sidebar__link <?= (strpos($_SERVER['REQUEST_URI'], 'savings') !== false ? 'active' : '') ?>">
                            <span class="sidebar__icon">🏦</span>
                            Savings
                        </a>
                    </li>

                    <li class="sidebar__item">
                        <a href="<?= APP_URL ?>/admin/moneyback/index.php" class="sidebar__link <?= (strpos($_SERVER['REQUEST_URI'], 'moneyback') !== false ? 'active' : '') ?>">
                            <span class="sidebar__icon">💸</span>
                            Money Back
                        </a>
                    </li>

                <?php else: ?>
                    <!-- User Menu -->
                    
                    <li class="sidebar__section-title">Main</li>
                    
                    <li class="sidebar__item">
                        <a href="<?= APP_URL ?>/user/dashboard.php" class="sidebar__link <?= ($pageTitle === 'Dashboard' ? 'active' : '') ?>">
                            <span class="sidebar__icon">📊</span>
                            Dashboard
                        </a>
                    </li>

                    <li class="sidebar__section-title">Finances</li>

                    <li class="sidebar__item">
                        <a href="<?= APP_URL ?>/user/loan/index.php" class="sidebar__link <?= (strpos($_SERVER['REQUEST_URI'], '/loan/') !== false ? 'active' : '') ?>">
                            <span class="sidebar__icon">📋</span>
                            Loans
                        </a>
                    </li>

                    <li class="sidebar__item">
                        <a href="<?= APP_URL ?>/user/billing/index.php" class="sidebar__link <?= (strpos($_SERVER['REQUEST_URI'], '/billing/') !== false ? 'active' : '') ?>">
                            <span class="sidebar__icon">💳</span>
                            Billing
                        </a>
                    </li>

                    <li class="sidebar__item">
                        <a href="<?= APP_URL ?>/user/savings/index.php" class="sidebar__link <?= (strpos($_SERVER['REQUEST_URI'], '/savings/') !== false ? 'active' : '') ?>">
                            <span class="sidebar__icon">🏦</span>
                            Savings
                        </a>
                    </li>

                    <li class="sidebar__section-title">Account</li>

                    <li class="sidebar__item">
                        <a href="<?= APP_URL ?>/user/profile.php" class="sidebar__link <?= ($pageTitle === 'Profile' ? 'active' : '') ?>">
                            <span class="sidebar__icon">⚙️</span>
                            Profile
                        </a>
                    </li>

                <?php endif; ?>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <header class="navbar">
                <div class="navbar__left">
                    <h1 class="navbar__title"><?= htmlspecialchars($pageTitle) ?></h1>
                </div>

                <div class="navbar__right">
                    <!-- Dark Mode Toggle -->
                    <div class="navbar__item" 
                         id="dark-mode-toggle"
                         onclick="window.darkModeManager.toggle()" 
                         style="cursor: pointer; user-select: none; transition: background-color 0.2s;"
                         onmouseover="this.style.background='var(--bg-hover)'; this.style.borderRadius='6px'; this.style.padding='6px 12px';"
                         onmouseout="this.style.background='transparent'"
                         title="Toggle dark mode">
                        <span style="font-size: 18px;">🌙</span>
                        <span class="dark-mode-label">Dark</span>
                    </div>

                    <div class="navbar__user">
                        <div class="navbar__avatar"><?= $userInitials ?></div>
                        <span><?= $userName ?></span>
                    </div>
                    <a href="<?= APP_URL ?>/auth/logout.php" class="navbar__item" title="Logout">
                        <span>🚪</span>
                    </a>
                </div>
            </header>

            <!-- Flash Messages -->
            <?php echo showFlash(); ?>

            <!-- Page Content Container -->
            <div class="page-content">
