<?php
// ============================================================
//  includes/admin_header.php
// ============================================================
if (!isset($pdo)) require_once __DIR__ . '/../config.php';
$flash       = getFlash();
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
$adminName   = $_SESSION['admin_username'] ?? 'Admin';
$initials    = strtoupper(substr($adminName, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | Admin | ' : 'Admin | ' ?><?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Mono&display=swap">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css">
<style>
* { box-sizing: border-box; }
body {
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    background: #f1f5f9;
    margin: 0;
    font-size: 14px;
    color: #1a1f2e;
}

/* ── ADMIN NAVBAR ──────────────────────────────────────── */
.ls-nav {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    height: 58px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 28px;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 4px 20px rgba(15,37,87,0.3);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.ls-nav-brand {
    display: flex; align-items: center; gap: 10px;
    color: #fff; text-decoration: none;
    font-size: 16px; font-weight: 700; flex-shrink: 0;
}
.ls-nav-brand .brand-box {
    width: 32px; height: 32px;
    background: #f5c842;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px;
}
.ls-nav-brand .admin-badge {
    background: rgba(245,200,66,0.2);
    color: #f5c842;
    font-size: 10px; font-weight: 700;
    padding: 2px 8px; border-radius: 20px;
    letter-spacing: 0.05em; text-transform: uppercase;
}
.ls-nav-links {
    display: flex; align-items: center; gap: 2px;
}
.ls-nav-links a {
    color: rgba(255,255,255,0.65);
    text-decoration: none;
    font-size: 12.5px; font-weight: 600;
    padding: 7px 11px; border-radius: 7px;
    transition: all 0.15s;
    display: flex; align-items: center; gap: 5px;
    white-space: nowrap;
}
.ls-nav-links a:hover { background: rgba(255,255,255,0.1); color: #fff; }
.ls-nav-links a.active { background: rgba(255,255,255,0.15); color: #fff; }
.ls-nav-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.ls-avatar {
    width: 33px; height: 33px;
    background: #f5c842; color: #0f2557;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; flex-shrink: 0;
}
.ls-dropdown-btn {
    background: none; border: none; cursor: pointer;
    color: rgba(255,255,255,0.85);
    font-size: 13px; font-weight: 600; font-family: inherit;
    display: flex; align-items: center; gap: 6px;
    padding: 5px 8px; border-radius: 6px;
    transition: background 0.15s;
}
.ls-dropdown-btn:hover { background: rgba(255,255,255,0.1); }

/* ── PAGE WRAPPER ──────────────────────────────────────── */
.ls-page { padding: 28px; max-width: 1300px; margin: 0 auto; }

/* ── FLASH ALERTS ──────────────────────────────────────── */
.ls-alert {
    border-radius: 10px; padding: 12px 16px; font-size: 13px;
    margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
    border: 1px solid transparent;
}
.ls-alert.success { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
.ls-alert.danger  { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
.ls-alert.warning { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.ls-alert.info    { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }

/* ── ADMIN CARDS ───────────────────────────────────────── */
.admin-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,0.07);
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    overflow: hidden;
    margin-bottom: 20px;
}
.admin-card-head {
    padding: 14px 20px;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    display: flex; align-items: center; justify-content: space-between;
}
.admin-card-title {
    font-size: 13px; font-weight: 700; color: #1a1f2e;
    display: flex; align-items: center; gap: 8px; margin: 0;
}
.admin-card-body { padding: 20px; }

/* ── ADMIN STAT CARDS ──────────────────────────────────── */
.admin-stat-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 14px; margin-bottom: 22px;
}
@media(max-width:900px){ .admin-stat-grid { grid-template-columns: repeat(2,1fr); } }
.admin-stat {
    background: #fff; border-radius: 14px;
    border: 1px solid rgba(0,0,0,0.07);
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    padding: 18px 18px 16px;
    position: relative; overflow: hidden;
    transition: transform 0.15s, box-shadow 0.15s;
    border-top: 4px solid transparent;
}
.admin-stat:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,0.09); }
.admin-stat.c-blue { border-top-color: #1a45a8; }
.admin-stat.c-green { border-top-color: #16a34a; }
.admin-stat.c-orange { border-top-color: #ea7c0a; }
.admin-stat.c-gold { border-top-color: #f5c842; }
.admin-stat.c-purple { border-top-color: #7c3aed; }
.admin-stat.c-red { border-top-color: #dc2626; }
.as-icon { font-size: 22px; margin-bottom: 10px; }
.as-label { font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
.as-value { font-size: 28px; font-weight: 700; line-height: 1; letter-spacing: -0.5px; margin-bottom: 3px; }
.c-blue .as-value   { color: #1a45a8; }
.c-green .as-value  { color: #16a34a; }
.c-orange .as-value { color: #ea7c0a; }
.c-gold .as-value   { color: #b07e00; }
.c-purple .as-value { color: #7c3aed; }
.c-red .as-value    { color: #dc2626; }
.as-sub { font-size: 11px; color: #9ca3af; }

/* ── ADMIN TABLE ───────────────────────────────────────── */
.admin-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.admin-table th {
    padding: 10px 16px; text-align: left;
    font-size: 11px; font-weight: 700; color: #6b7280;
    text-transform: uppercase; letter-spacing: 0.05em;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    background: #fafbfc;
}
.admin-table td { padding: 12px 16px; border-bottom: 1px solid rgba(0,0,0,0.04); color: #374151; }
.admin-table tr:last-child td { border-bottom: none; }
.admin-table tbody tr:hover { background: #fafbfc; }
.admin-table tbody tr:nth-child(even) td { background: #fff; }
.admin-table tbody tr:nth-child(even):hover { background: #f0f4ff; }
.txn-mono { font-family: 'DM Mono', monospace; font-size: 11px; color: #9ca3af; }

/* ── BADGES ────────────────────────────────────────────── */
.adm-badge {
    font-size: 11px; font-weight: 700;
    padding: 3px 10px; border-radius: 20px;
    display: inline-block;
}
.adm-badge.pending   { background: #fef3c7; color: #d97706; }
.adm-badge.approved  { background: #dcfce7; color: #16a34a; }
.adm-badge.active    { background: #dbeafe; color: #1a45a8; }
.adm-badge.rejected  { background: #fee2e2; color: #dc2626; }
.adm-badge.overdue   { background: #fee2e2; color: #dc2626; }
.adm-badge.completed { background: #dcfce7; color: #16a34a; }
.adm-badge.disabled  { background: #f1f5f9; color: #94a3b8; }
.adm-badge.premium   { background: #f5c842; color: #0f2557; }
.adm-badge.basic     { background: #e8ecf5; color: #334155; }

/* ── BUTTONS ───────────────────────────────────────────── */
.btn-adm-primary {
    background: #0f2557; color: #f5c842;
    border: none; border-radius: 8px;
    padding: 9px 20px; font-size: 13px; font-weight: 600;
    font-family: inherit; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    text-decoration: none; transition: background 0.15s;
}
.btn-adm-primary:hover { background: #1a3a7a; color: #f5c842; }

.btn-adm-success {
    background: #16a34a; color: #fff;
    border: none; border-radius: 8px;
    padding: 9px 20px; font-size: 13px; font-weight: 600;
    font-family: inherit; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    text-decoration: none; transition: background 0.15s;
}
.btn-adm-success:hover { background: #15803d; color: #fff; }

.btn-adm-danger {
    background: #dc2626; color: #fff;
    border: none; border-radius: 8px;
    padding: 9px 20px; font-size: 13px; font-weight: 600;
    font-family: inherit; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    text-decoration: none; transition: background 0.15s;
}
.btn-adm-danger:hover { background: #b91c1c; color: #fff; }

.btn-adm-outline {
    background: transparent; color: #0f2557;
    border: 1.5px solid #0f2557; border-radius: 8px;
    padding: 8px 18px; font-size: 13px; font-weight: 600;
    font-family: inherit; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    text-decoration: none; transition: all 0.15s;
}
.btn-adm-outline:hover { background: #0f2557; color: #f5c842; }

.btn-adm-sm {
    padding: 5px 12px !important;
    font-size: 12px !important;
    border-radius: 6px !important;
}

/* ── PAGE HEADER ───────────────────────────────────────── */
.admin-page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.admin-page-title {
    font-size: 22px; font-weight: 700; color: #0f2557; margin: 0 0 3px;
}
.admin-page-sub { font-size: 13px; color: #9ca3af; margin: 0; }
.admin-breadcrumb {
    font-size: 12px; color: #9ca3af; margin-bottom: 6px;
    display: flex; align-items: center; gap: 6px;
}
.admin-breadcrumb a { color: #1a45a8; text-decoration: none; font-weight: 600; }
.admin-breadcrumb a:hover { text-decoration: underline; }

/* ── FILTER BAR ────────────────────────────────────────── */
.filter-bar {
    display: flex; align-items: center; gap: 10px;
    flex-wrap: wrap; margin-bottom: 18px;
}
.filter-tab {
    padding: 6px 16px; border-radius: 20px;
    font-size: 12px; font-weight: 600;
    text-decoration: none; color: #6b7280;
    background: #fff; border: 1.5px solid rgba(0,0,0,0.08);
    transition: all 0.15s; cursor: pointer;
}
.filter-tab:hover { border-color: #0f2557; color: #0f2557; }
.filter-tab.active { background: #0f2557; color: #f5c842; border-color: #0f2557; }
.filter-tab .count {
    background: rgba(0,0,0,0.1); color: inherit;
    font-size: 10px; padding: 1px 6px;
    border-radius: 20px; margin-left: 4px;
}
.filter-tab.active .count { background: rgba(245,200,66,0.3); }

/* ── SEARCH INPUT ──────────────────────────────────────── */
.search-input {
    padding: 8px 14px 8px 36px;
    border: 1.5px solid rgba(0,0,0,0.1);
    border-radius: 8px; font-size: 13px;
    font-family: inherit; outline: none;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") no-repeat 10px center;
    transition: border-color 0.15s;
    min-width: 220px;
}
.search-input:focus { border-color: #1a45a8; }

/* ── EMPTY STATE ───────────────────────────────────────── */
.empty-state {
    text-align: center; padding: 52px 20px;
    color: #9ca3af;
}
.empty-state .empty-icon { font-size: 42px; margin-bottom: 12px; }
.empty-state .empty-title { font-size: 15px; font-weight: 700; color: #374151; margin-bottom: 6px; }
.empty-state .empty-sub { font-size: 13px; }

/* ── PULSE ANIMATION ─────────────────────────────────────── */
.pulse-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    background: #22c55e;
    border-radius: 50%;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(34, 197, 94, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
    }
}

.adm-badge.active::after {
    content: "";
    display: inline-block;
    width: 8px;
    height: 8px;
    background: #22c55e;
    border-radius: 50%;
    animation: pulse 2s infinite;
    margin-left: 4px;
}

</style>
</head>
<body>

<nav class="ls-nav">
    <a href="<?= APP_URL ?>/admin/dashboard.php" class="ls-nav-brand">
        <div class="brand-box">🏦</div>
        LendingSystem
        <span class="admin-badge">Admin</span>
    </a>

    <div class="ls-nav-links">
        <a href="<?= APP_URL ?>/admin/dashboard.php"
           class="<?= $currentPage==='dashboard.php' && $currentDir==='admin' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="<?= APP_URL ?>/admin/users/index.php"
           class="<?= $currentDir==='users' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Users
        </a>
        <a href="<?= APP_URL ?>/admin/registrations/index.php"
           class="<?= $currentDir==='registrations' ? 'active' : '' ?>">
            <i class="bi bi-person-plus"></i> Registrations
        </a>
        <a href="<?= APP_URL ?>/admin/loans/index.php"
           class="<?= $currentDir==='loans' ? 'active' : '' ?>">
            <i class="bi bi-cash-coin"></i> Loans
        </a>
        <a href="<?= APP_URL ?>/admin/savings/index.php"
           class="<?= $currentDir==='savings' ? 'active' : '' ?>">
            <i class="bi bi-piggy-bank"></i> Savings
        </a>
        <a href="<?= APP_URL ?>/admin/billing/index.php"
           class="<?= $currentDir==='billing' ? 'active' : '' ?>">
            <i class="bi bi-receipt"></i> Billing
        </a>
        <a href="<?= APP_URL ?>/admin/money_back.php"
           class="<?= $currentDir==='moneyback' ? 'active' : '' ?>">
            <i class="bi bi-percent"></i> Money Back
        </a>
    </div>

    <div class="ls-nav-right">
        <div class="ls-avatar"><?= htmlspecialchars($initials) ?></div>
        <div class="dropdown">
            <button class="ls-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown">
                <?= htmlspecialchars($adminName) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow"
                style="font-size:13px;min-width:180px;border-radius:10px;">
                <li>
                    <div class="px-3 py-2">
                        <div style="font-weight:700;color:#1a1f2e;"><?= htmlspecialchars($adminName) ?></div>
                        <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;background:#fee2e2;color:#b91c1c;">
                            Administrator
                        </span>
                    </div>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item text-danger" href="<?= APP_URL ?>/auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="ls-page">

<?php if ($flash):
    $alertType = match($flash['type']) {
        'success' => 'success', 'danger' => 'danger',
        'warning' => 'warning', default  => 'info',
    };
    $alertIcon = match($flash['type']) {
        'success' => 'check-circle-fill', 'danger' => 'exclamation-circle-fill',
        'warning' => 'exclamation-triangle-fill', default => 'info-circle-fill',
    };
?>
<div class="ls-alert <?= $alertType ?>">
    <i class="bi bi-<?= $alertIcon ?>"></i>
    <?= $flash['message'] ?>
</div>
<?php endif; ?>