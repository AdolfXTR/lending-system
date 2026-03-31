<?php
// includes/header.php
if (!isset($pdo)) require_once __DIR__ . '/../config.php';
$flash       = getFlash();
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
$initials    = '';
if (isset($_SESSION['first_name'], $_SESSION['last_name'])) {
    $initials = strtoupper(substr($_SESSION['first_name'],0,1) . substr($_SESSION['last_name'],0,1));
}
$fullName    = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
$accountType = $_SESSION['account_type'] ?? 'Basic';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lending System</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Mono&display=swap">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<style>
/* Self-contained navbar — works even if style.css fails to load */
* { box-sizing: border-box; }
body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: #f0f2f7; margin: 0; }

.ls-nav {
    background: #0f2557;
    height: 58px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 28px;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 12px rgba(15,37,87,0.3);
}
.ls-nav-brand {
    display: flex; align-items: center; gap: 10px;
    color: #fff; text-decoration: none; font-size: 16px; font-weight: 700;
    flex-shrink: 0;
}
.ls-nav-brand .brand-box {
    width: 32px; height: 32px; background: #f5c842;
    border-radius: 8px; display: flex; align-items: center; justify-content: center;
    font-size: 16px;
}
.ls-nav-links {
    display: flex; align-items: center; gap: 2px;
}
.ls-nav-links a {
    color: rgba(255,255,255,0.65); text-decoration: none;
    font-size: 13px; font-weight: 600;
    padding: 7px 14px; border-radius: 7px;
    transition: all 0.15s;
    display: flex; align-items: center; gap: 6px;
    white-space: nowrap;
}
.ls-nav-links a:hover { background: rgba(255,255,255,0.1); color: #fff; }
.ls-nav-links a.active { background: rgba(255,255,255,0.15); color: #fff; }
.ls-nav-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.ls-avatar {
    width: 33px; height: 33px; background: #f5c842;
    color: #0f2557; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; flex-shrink: 0;
}
.ls-dropdown-btn {
    background: none; border: none; cursor: pointer;
    color: rgba(255,255,255,0.85); font-size: 13px; font-weight: 600;
    font-family: inherit; display: flex; align-items: center; gap: 6px;
    padding: 5px 8px; border-radius: 6px; transition: background 0.15s;
}
.ls-dropdown-btn:hover { background: rgba(255,255,255,0.1); }

.ls-page { padding: 28px; max-width: 1240px; margin: 0 auto; }

.ls-alert {
    border-radius: 10px; padding: 12px 16px; font-size: 13px;
    margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
    border: 1px solid transparent; font-family: inherit;
}
.ls-alert.success { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
.ls-alert.danger  { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
.ls-alert.warning { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.ls-alert.info    { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }

/* Dark mode test - inline fallback */
body.dark-mode {
    background: #0d1021 !important;
    color: #e2e8f0 !important;
}

body.dark-mode .ls-nav {
    background: #0f1117 !important;
}

body.dark-mode .ls-page {
    background: #0d1021 !important;
}

body.dark-mode .card-lending,
body.dark-mode .stat-card-lending,
body.dark-mode .card,
body.dark-mode .admin-card,
body.dark-mode .billing-card,
body.dark-mode .loan-card,
body.dark-mode .progress-card,
body.dark-mode .statement-card {
    background: #161b2e !important;
    border-color: rgba(255,255,255,0.08) !important;
    box-shadow: 0 2px 12px rgba(0,0,0,0.4) !important;
}

/* Colored top borders for stat cards in dark mode */
body.dark-mode .stat-card-lending:nth-child(1) {
    border-top: 3px solid #2563eb !important;
}

body.dark-mode .stat-card-lending:nth-child(2) {
    border-top: 3px solid #f59e0b !important;
}

body.dark-mode .stat-card-lending:nth-child(3) {
    border-top: 3px solid #10b981 !important;
}

body.dark-mode .stat-card-lending:nth-child(4) {
    border-top: 3px solid #2563eb !important;
}

body.dark-mode .card-lending *,
body.dark-mode .stat-card-lending * {
    color: #e2e8f0 !important;
}

body.dark-mode .stat-label,
body.dark-mode .text-muted,
body.dark-mode .page-subtitle {
    color: #94a3b8 !important;
}

body.dark-mode .page-title,
body.dark-mode .welcome-text {
    color: #f1f5f9 !important;
}

body.dark-mode .btn-lending-outline {
    background: #1e2538 !important;
    border-color: rgba(255,255,255,0.2) !important;
    color: #e2e8f0 !important;
}

body.dark-mode .btn-lending-primary {
    background: #1e293b !important;
    color: #60a5fa !important;
}

body.dark-mode .btn-lending-gold {
    background: #1e293b !important;
    color: #fbbf24 !important;
}

body.dark-mode .form-control-lending,
body.dark-mode .form-select-lending {
    background: #1e2538 !important;
    border-color: rgba(255,255,255,0.15) !important;
    color: #e2e8f0 !important;
}

body.dark-mode .table-lending th {
    background: #1e293b !important;
    color: #94a3b8 !important;
}

body.dark-mode .table-lending td {
    color: #e2e8f0 !important;
}

body.dark-mode .table-lending tbody tr:hover {
    background: rgba(255,255,255,0.05) !important;
}

body.dark-mode .dropdown-menu {
    background: #161b2e !important;
    border-color: rgba(255,255,255,0.1) !important;
}

body.dark-mode .dropdown-item {
    color: #e2e8f0 !important;
}

body.dark-mode .dropdown-item:hover {
    background: #1e2538 !important;
}

/* Comprehensive dark mode for all cards and containers */
body.dark-mode div[class*="card"],
body.dark-mode div[class*="Card"],
body.dark-mode .card,
body.dark-mode .card-body,
body.dark-mode .card-header,
body.dark-mode .panel,
body.dark-mode .well,
body.dark-mode .jumbotron,
body.dark-mode .alert,
body.dark-mode .modal-content,
body.dark-mode .popover,
body.dark-mode .tooltip-inner {
    background: #161b2e !important;
    color: #e2e8f0 !important;
    border-color: rgba(255,255,255,0.08) !important;
}

/* Force all text in cards to be light */
body.dark-mode .card *,
body.dark-mode .card-lending *,
body.dark-mode .stat-card-lending *,
body.dark-mode div[class*="card"] *,
body.dark-mode .card-body *,
body.dark-mode .card-header * {
    color: #e2e8f0 !important;
}

/* Headers and titles in cards */
body.dark-mode .card-title,
body.dark-mode .card-header,
body.dark-mode h1, body.dark-mode h2, body.dark-mode h3, 
body.dark-mode h4, body.dark-mode h5, body.dark-mode h6 {
    color: #f1f5f9 !important;
}

/* Bootstrap override */
body.dark-mode .bg-white {
    background: #161b2e !important;
}

body.dark-mode .text-dark {
    color: #e2e8f0 !important;
}

/* Table improvements for dark mode */
body.dark-mode .table,
body.dark-mode .table-lending {
    background: #1a1f2e !important;
}

body.dark-mode .table th,
body.dark-mode .table-lending th,
body.dark-mode .table td,
body.dark-mode .table-lending td {
    background: transparent !important;
    color: #e2e8f0 !important;
    border-color: rgba(255,255,255,0.1) !important;
}

body.dark-mode .table tbody tr,
body.dark-mode .table-lending tbody tr {
    background: #1a1f2e !important;
}

body.dark-mode .table tbody tr:nth-child(even),
body.dark-mode .table-lending tbody tr:nth-child(even) {
    background: #1e2538 !important;
}

body.dark-mode .table tbody tr:hover,
body.dark-mode .table-lending tbody tr:hover {
    background: #252d3a !important;
}

/* Remove opacity from table text in dark mode */
body.dark-mode .table .text-muted,
body.dark-mode .table-lending .text-muted,
body.dark-mode .table .txn-id-mono,
body.dark-mode .table-lending .txn-id-mono {
    color: #e2e8f0 !important;
    opacity: 1 !important;
}

/* Billing page specific fixes */
body.dark-mode .billing-summary-card,
body.dark-mode .billing-card,
body.dark-mode .current-bill-section,
body.dark-mode .billing-breakdown {
    background: #1a1f2e !important;
}

body.dark-mode .billing-summary-card *,
body.dark-mode .billing-card *,
body.dark-mode .current-bill-section *,
body.dark-mode .billing-breakdown * {
    color: #e2e8f0 !important;
}

body.dark-mode .section-title,
body.dark-mode .billing-label {
    color: #f1f5f9 !important;
}

/* Info notices in dark mode */
body.dark-mode .alert-info,
body.dark-mode .info-notice,
body.dark-mode .billing-notice {
    background: #1e293b !important;
    color: #e2e8f0 !important;
    border-color: #2563eb !important;
}

body.dark-mode .alert-info *,
body.dark-mode .info-notice *,
body.dark-mode .billing-notice * {
    color: #e2e8f0 !important;
}

/* Specific billing page stat cards */
body.dark-mode .billing-stats .stat-card-lending,
body.dark-mode .billing-summary .card,
body.dark-mode .billing-overview .card-lending,
body.dark-mode div[class*="billing"] .card,
body.dark-mode div[class*="bill"] .stat-card {
    background: #1a1f2e !important;
}

body.dark-mode .billing-stats .stat-card-lending *,
body.dark-mode .billing-summary .card *,
body.dark-mode .billing-overview .card-lending *,
body.dark-mode div[class*="billing"] .card *,
body.dark-mode div[class*="bill"] .stat-card * {
    color: #e2e8f0 !important;
}

/* Section labels for billing and dashboard */
body.dark-mode .section-label,
body.dark-mode .section-title,
body.dark-mode .billing-section-title,
body.dark-mode .current-bill-label,
body.dark-mode .billing-history-label,
body.dark-mode h2.section-heading,
body.dark-mode h3.billing-heading,
body.dark-mode .dashboard-section-title {
    color: #f1f5f9 !important;
}

/* Dashboard specific headings */
body.dark-mode .billing-statement-title,
body.dark-mode .loan-progress-title,
body.dark-mode .payment-schedule-title,
body.dark-mode .current-billing-statement,
body.dark-mode .loan-progress-heading,
body.dark-mode .monthly-payment-schedule {
    color: #e2e8f0 !important;
}

/* Force all h2, h3 in main content to be light */
body.dark-mode .ls-page h2,
body.dark-mode .ls-page h3,
body.dark-mode .page-content h2,
body.dark-mode .page-content h3 {
    color: #f1f5f9 !important;
}

/* Blue info notice specific fix */
body.dark-mode .alert-info,
body.dark-mode .info-box,
body.dark-mode .notice-box,
body.dark-style .info-notice {
    background: #1e293b !important;
    color: #e2e8f0 !important;
    border-color: #2563eb !important;
}

body.dark-mode .alert-info i,
body.dark-mode .info-box i,
body.dark-mode .notice-box i {
    color: #60a5fa !important;
}

/* AGGRESSIVE OVERRIDES FOR BILLING COMPONENTS */

/* Billing stat cards - force dark backgrounds */
body.dark-mode .stat-card-lending.billing-card,
body.dark-mode .card.billing-stat,
body.dark-mode div[class*="billing"] .stat-card-lending,
body.dark-mode div[class*="bill"] .card,
body.dark-mode .billing-overview .stat-card,
body.dark-mode .billing-summary .card,
body.dark-mode .ls-page .stat-grid .stat-card-lending {
    background: #1a1f2e !important;
}

body.dark-mode .stat-card-lending.billing-card *,
body.dark-mode .card.billing-stat *,
body.dark-mode div[class*="billing"] .stat-card-lending *,
body.dark-mode div[class*="bill"] .card *,
body.dark-mode .billing-overview .stat-card *,
body.dark-mode .billing-summary .card *,
body.dark-mode .ls-page .stat-grid .stat-card-lending * {
    color: #e2e8f0 !important;
}

/* Billing History Accordion - force dark */
body.dark-mode .accordion,
body.dark-mode .accordion-item,
body.dark-mode .accordion-header,
body.dark-mode .accordion-body,
body.dark-mode .billing-history,
body.dark-mode .history-accordion,
body.dark-mode div[class*="accordion"] {
    background: #1a1f2e !important;
    border-color: rgba(255,255,255,0.1) !important;
}

body.dark-mode .accordion-button,
body.dark-mode .accordion-button:not(.collapsed),
body.dark-mode .billing-history .accordion-button {
    background: #1a1f2e !important;
    color: #e2e8f0 !important;
    border-color: rgba(255,255,255,0.1) !important;
}

body.dark-mode .accordion-button:focus,
body.dark-mode .accordion-button:not(.collapsed):focus {
    background: #252d3a !important;
    color: #f1f5f9 !important;
    box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.2) !important;
}

/* Expanded accordion rows */
body.dark-mode .accordion-body,
body.dark-mode .accordion-content,
body.dark-mode .billing-details,
body.dark-mode .expanded-bill-row {
    background: #151929 !important;
    color: #e2e8f0 !important;
}

/* All text in accordion */
body.dark-mode .accordion *,
body.dark-mode .accordion-item *,
body.dark-mode .accordion-header *,
body.dark-mode .accordion-body *,
body.dark-mode .billing-history * {
    color: #e2e8f0 !important;
}

/* Accordion headers and month names */
body.dark-mode .accordion-button,
body.dark-mode .month-name,
body.dark-mode .year-header,
body.dark-mode .billing-period {
    color: #f1f5f9 !important;
    font-weight: 600 !important;
}

/* Pending badge in dark mode */
body.dark-mode .badge.pending,
body.dark-mode .badge-warning,
body.dark-mode .status-pending {
    background: #d97706 !important;
    color: #fef3c7 !important;
    border-color: #b45309 !important;
}

/* Force override any Bootstrap bg-white in billing */
body.dark-mode .billing-page .bg-white,
body.dark-mode .billing-section .bg-white,
body.dark-mode div[class*="billing"] .bg-white,
body.dark-mode .accordion .bg-white {
    background: #1a1f2e !important;
}

/* Blue info notice specific styling */
body.dark-mode .alert-info,
body.dark-mode .info-notice,
body.dark-mode .billing-info,
body.dark-mode .notice-info,
body.dark-mode .alert.alert-info {
    background: #1e3a5f !important;
    color: #93c5fd !important;
    border-color: #2563eb !important;
}

body.dark-mode .alert-info *,
body.dark-mode .info-notice *,
body.dark-mode .billing-info *,
body.dark-mode .notice-info *,
body.dark-mode .alert.alert-info * {
    color: #93c5fd !important;
}

body.dark-mode .alert-info i,
body.dark-mode .info-notice i,
body.dark-mode .billing-info i,
body.dark-mode .notice-info i,
body.dark-mode .alert.alert-info i {
    color: #93c5fd !important;
}

/* Force table headers and content in billing history */
body.dark-mode .billing-history .table th,
body.dark-mode .billing-history .table td,
body.dark-mode .accordion .table th,
body.dark-mode .accordion .table td {
    background: transparent !important;
    color: #e2e8f0 !important;
    border-color: rgba(255,255,255,0.1) !important;
}

body.dark-mode .billing-history .table th,
body.dark-mode .accordion .table th {
    color: #f1f5f9 !important;
    font-weight: 600 !important;
}

/* Notification Bell Styling */
.notification-bell {
    position: relative;
    padding: 8px 12px !important;
}

.notification-badge {
    position: absolute;
    top: 4px;
    right: 4px;
    background: #dc2626;
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 5px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    line-height: 1;
}

body.dark-mode .notification-badge {
    background: #ef4444;
    color: white;
}

.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    width: 320px;
    max-height: 400px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}

body.dark-mode .notification-dropdown {
    background: #1e293b;
    border-color: rgba(255,255,255,0.1);
}

.notification-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f3f4f6;
    cursor: pointer;
    transition: background 0.15s;
}

body.dark-mode .notification-item {
    border-color: rgba(255,255,255,0.1);
}

.notification-item:hover {
    background: #f9fafb;
}

body.dark-mode .notification-item:hover {
    background: #334155;
}

.notification-item.unread {
    background: #eff6ff;
}

body.dark-mode .notification-item.unread {
    background: #1e3a8a;
}

.notification-title {
    font-weight: 600;
    font-size: 13px;
    color: #1f2937;
    margin-bottom: 4px;
}

body.dark-mode .notification-title {
    color: #f1f5f9;
}

.notification-message {
    font-size: 12px;
    color: #6b7280;
    line-height: 1.4;
}

body.dark-mode .notification-message {
    color: #94a3b8;
}

.notification-time {
    font-size: 11px;
    color: #9ca3af;
    margin-top: 4px;
}

body.dark-mode .notification-time {
    color: #64748b;
}

.notification-empty {
    padding: 20px;
    text-align: center;
    color: #6b7280;
    font-size: 13px;
}

body.dark-mode .notification-empty {
    color: #94a3b8;
}

</style>

</head>
<body>

<nav class="ls-nav">
    <a href="<?= APP_URL ?>/user/dashboard.php" class="ls-nav-brand">
        <div class="brand-box">🏦</div>
        LendingSystem
    </a>

    <div class="ls-nav-links">
        <a href="<?= APP_URL ?>/user/dashboard.php"
           class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="<?= APP_URL ?>/user/loan/index.php"
           class="<?= $currentDir === 'loan' ? 'active' : '' ?>">
            <i class="bi bi-credit-card"></i> Loan
        </a>
        <a href="<?= APP_URL ?>/user/billing/index.php"
           class="<?= $currentDir === 'billing' ? 'active' : '' ?>">
            <i class="bi bi-receipt"></i> Billing
        </a>
        <?php if ($accountType === 'Premium'): ?>
        <a href="<?= APP_URL ?>/user/savings/index.php"
           class="<?= $currentDir === 'savings' ? 'active' : '' ?>">
            <i class="bi bi-piggy-bank"></i> Savings
        </a>
        <?php endif; ?>
    </div>

    <div class="ls-nav-right">
        <!-- Notifications Bell - HIDDEN (not implemented) -->
        <!--
        <button class="ls-dropdown-btn notification-bell" 
                title="Notifications"
                onclick="toggleNotifications()">
            <span style="font-size: 18px;">🔔</span>
            <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
        </button>

        <div class="notification-dropdown" id="notificationDropdown">
            <div class="notification-empty">
                <div style="font-size: 24px; margin-bottom: 8px;">🔔</div>
                <div>No notifications yet</div>
            </div>
        </div>
        -->

        <!-- Dark Mode Toggle -->
        <button class="ls-dropdown-btn" 
                id="dark-mode-toggle"
                onclick="window.darkModeManager && window.darkModeManager.toggle(); return false;"
                style="color: rgba(255,255,255,0.85);"
                title="Toggle dark mode">
            <span style="font-size: 16px;">🌙</span>
            <span class="dark-mode-label">Dark</span>
        </button>

        <div class="ls-avatar"><?= htmlspecialchars($initials) ?></div>
        <div class="dropdown">
            <button class="ls-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown">
                <?= htmlspecialchars($fullName) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow" style="font-size:13px;min-width:190px;border-radius:10px;">
                <li>
                    <div class="px-3 py-2">
                        <div style="font-weight:700;color:#1a1f2e;"><?= htmlspecialchars($fullName) ?></div>
                        <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;background:<?= $accountType==='Premium'?'#f5c842':'#e2e8f0' ?>;color:<?= $accountType==='Premium'?'#0f2557':'#475569' ?>;">
                            <?= $accountType ?>
                        </span>
                    </div>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li><a class="dropdown-item" href="<?= APP_URL ?>/user/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                <li><hr class="dropdown-divider my-1"></li>
                <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="ls-page">

<?php if ($flash): ?>
<?php
$alertType = match($flash['type']) {
    'success' => 'success',
    'danger'  => 'danger',
    'warning' => 'warning',
    default   => 'info',
};
$alertIcon = match($flash['type']) {
    'success' => 'check-circle-fill',
    'danger'  => 'exclamation-circle-fill',
    'warning' => 'exclamation-triangle-fill',
    default   => 'info-circle-fill',
};
?>
<div class="ls-alert <?= $alertType ?>">
    <i class="bi bi-<?= $alertIcon ?>"></i>
    <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<script>
// Notification Bell Functionality
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    const isVisible = dropdown.style.display === 'block';
    dropdown.style.display = isVisible ? 'none' : 'block';
    
    // Close dropdown when clicking outside
    if (!isVisible) {
        setTimeout(() => {
            document.addEventListener('click', closeNotificationsOutside);
        }, 100);
    }
}

function closeNotificationsOutside(event) {
    const dropdown = document.getElementById('notificationDropdown');
    const bell = document.querySelector('.notification-bell');
    
    if (!dropdown.contains(event.target) && !bell.contains(event.target)) {
        dropdown.style.display = 'none';
        document.removeEventListener('click', closeNotificationsOutside);
    }
}

// Initialize notification count (placeholder for future implementation)
document.addEventListener('DOMContentLoaded', function() {
    // This would be populated from a notifications API/database
    updateNotificationCount(0);
});

function updateNotificationCount(count) {
    const badge = document.getElementById('notificationCount');
    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = 'block';
    } else {
        badge.style.display = 'none';
    }
}
</script>