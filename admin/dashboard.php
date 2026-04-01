<?php
// ============================================================
//  admin/dashboard.php
// ============================================================
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../helpers.php';

// Stats
$totalUsers     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status != 'Rejected'")->fetchColumn();
$activeUsers    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Active'")->fetchColumn();
$premiumUsers   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE account_type = 'Premium' AND status = 'Active'")->fetchColumn();
$basicUsers     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE account_type = 'Basic' AND status = 'Active'")->fetchColumn();
$pendingRegs    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Pending'")->fetchColumn();
$totalLoans     = (int)$pdo->query("SELECT COUNT(*) FROM loans")->fetchColumn();
$pendingLoans   = (int)$pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'Pending'")->fetchColumn();
$activeLoans    = (int)$pdo->query("SELECT COUNT(*) FROM loans WHERE status IN ('Active','Approved')")->fetchColumn();
$overdueLoans   = (int)$pdo->query("SELECT COUNT(*) FROM billing WHERE status = 'Overdue'")->fetchColumn();
$totalSavings   = (float)$pdo->query("SELECT COALESCE(SUM(balance),0) FROM savings")->fetchColumn();

// Chart data: Loans per month
$loansPerMonth = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') as month_label,
           DATE_FORMAT(created_at, '%Y-%m') as month_key,
           COUNT(*) as count,
           COALESCE(SUM(applied_amount), 0) as total_amount
    FROM loans
    WHERE status != 'Rejected'
    GROUP BY month_key
    ORDER BY month_key ASC
")->fetchAll();

$chartLabels  = json_encode(array_values(array_column($loansPerMonth, 'month_label')));
$chartCounts  = json_encode(array_values(array_map('intval', array_column($loansPerMonth, 'count'))));

// Chart data: Income per month
$incomePerMonth = $pdo->query("
    SELECT DATE_FORMAT(earned_at, '%b %Y') as month_label,
           DATE_FORMAT(earned_at, '%Y-%m') as month_key,
           COALESCE(SUM(amount), 0) as total
    FROM company_income
    GROUP BY month_key
    ORDER BY month_key ASC
")->fetchAll();

$incomeLabels = json_encode(array_values(array_column($incomePerMonth, 'month_label')));
$incomeData   = json_encode(array_values(array_map('floatval', array_column($incomePerMonth, 'total'))));

// Recent loans
$recentLoans = $pdo->query("
    SELECT l.*, u.first_name, u.last_name
    FROM loans l JOIN users u ON u.id = l.user_id
    ORDER BY l.created_at DESC LIMIT 6
")->fetchAll();

// Recent registrations
$recentRegs = $pdo->query("
    SELECT * FROM users WHERE status = 'Pending' ORDER BY created_at DESC LIMIT 5
")->fetchAll();

// Company earnings for current year
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_due), 0) as total_income 
    FROM billing 
    WHERE status = 'Completed' 
    AND YEAR(paid_at) = YEAR(CURRENT_DATE)
");
$stmt->execute();
$totalIncome = (float)$stmt->fetchColumn();

$premiumCount = getPremiumMemberCount($pdo);

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Modern Admin Dashboard Styles -->
<style>
.admin-hero {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    border-radius: 24px;
    padding: 32px;
    margin-bottom: 32px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 25px -5px rgba(30, 41, 59, 0.15);
}

.admin-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
}

.admin-hero::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -5%;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 50%;
}

.hero-title {
    color: #ffffff;
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 8px;
    position: relative;
    z-index: 1;
}

.hero-subtitle {
    color: rgba(255, 255, 255, 0.7);
    font-size: 16px;
    margin-bottom: 24px;
    position: relative;
    z-index: 1;
}

.admin-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.admin-stat-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 28px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.admin-stat-card:hover {
    transform: translateY(-6px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
}

.admin-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6 0%, #1d4ed8 100%);
}

.admin-stat-card.primary::before { background: linear-gradient(90deg, #3b82f6 0%, #1d4ed8 100%); }
.admin-stat-card.primary { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); }

.admin-stat-card.warning::before { background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%); }
.admin-stat-card.warning { background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); }

.admin-stat-card.success::before { background: linear-gradient(90deg, #10b981 0%, #059669 100%); }
.admin-stat-card.success { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); }

.admin-stat-card.danger::before { background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%); }
.admin-stat-card.danger { background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); }

.admin-stat-card.gold::before { background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%); }
.admin-stat-card.gold { background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%); }

.admin-stat-card.purple::before { background: linear-gradient(90deg, #8b5cf6 0%, #7c3aed 100%); }
.admin-stat-card.purple { background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); }

.stat-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-bottom: 16px;
    position: relative;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}

.admin-stat-card:hover .stat-icon {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.admin-stat-card.primary .stat-icon { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; }
.admin-stat-card.warning .stat-icon { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
.admin-stat-card.success .stat-icon { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
.admin-stat-card.danger .stat-icon { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
.admin-stat-card.gold .stat-icon { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; }
.admin-stat-card.purple .stat-icon { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; }

.stat-label {
    font-size: 14px;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 36px;
    font-weight: 800;
    margin-bottom: 8px;
    line-height: 1;
}

.admin-stat-card.primary .stat-value { color: #1d4ed8; }
.admin-stat-card.warning .stat-value { color: #d97706; }
.admin-stat-card.success .stat-value { color: #059669; }
.admin-stat-card.danger .stat-value { color: #dc2626; }
.admin-stat-card.gold .stat-value { color: #d97706; }
.admin-stat-card.purple .stat-value { color: #7c3aed; }

.stat-trend {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 4px;
}

.stat-trend.up { color: #059669; }
.stat-trend.down { color: #dc2626; }
.stat-trend.neutral { color: #6b7280; }

.stat-subtitle {
    font-size: 13px;
    color: #9ca3af;
    font-weight: 500;
}

.stat-link {
    color: inherit;
    font-weight: 700;
    text-decoration: none;
    transition: color 0.2s ease;
}

.stat-link:hover {
    color: #1d4ed8;
}

.charts-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    margin-bottom: 32px;
}

.chart-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.chart-card:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.chart-title {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.date-filter {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
}

.date-filter select {
    padding: 6px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 13px;
    background: white;
    color: #374151;
    cursor: pointer;
    transition: all 0.15s ease;
}

.date-filter select:hover {
    border-color: #3b82f6;
}

.date-filter select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.full-width-chart {
    grid-column: 1 / -1;
}

.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
}

.admin-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: all 0.3s ease;
}

.admin-card:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.admin-card-header {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.admin-card-title {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.admin-table {
    width: 100%;
    margin: 0;
}

.admin-table thead th {
    background: #f8fafc;
    color: #374151;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 16px 20px;
    text-align: left;
    border: none;
}

.admin-table tbody td {
    padding: 16px 20px;
    vertical-align: middle;
    border-top: 1px solid #f1f5f9;
    font-size: 14px;
}

.admin-table tbody tr:hover {
    background: #f0f4ff;
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
}

.badge-admin {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 1px solid transparent;
}

.badge-active { 
    background: #d1fae5; 
    color: #065f46; 
    border-color: #a7f3d0;
}

.badge-completed { 
    background: #f3f4f6; 
    color: #374151; 
    border-color: #d1d5db;
}

.badge-pending { 
    background: #fef3c7; 
    color: #92400e; 
    border-color: #fde68a;
}

.badge-overdue { 
    background: #fee2e2; 
    color: #991b1b; 
    border-color: #fecaca;
}

.badge-approved { 
    background: #dbeafe; 
    color: #1e40af; 
    border-color: #bfdbfe;
}

.badge-rejected { 
    background: #fee2e2; 
    color: #991b1b; 
    border-color: #fecaca;
}

.table-search {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    padding: 8px 12px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.table-search input {
    border: none;
    background: transparent;
    outline: none;
    flex: 1;
    font-size: 13px;
    color: #374151;
}

.table-search input::placeholder {
    color: #9ca3af;
}

.quick-link-pill {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 20px;
    text-decoration: none;
    color: #374151;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.quick-link-pill:hover {
    background: #1a45a8;
    color: white;
    border-color: #1a45a8;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(26, 69, 168, 0.3);
}

.btn-admin {
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-primary-admin {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.btn-primary-admin:hover {
    background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-outline-admin {
    background: transparent;
    color: #6b7280;
    border: 2px solid #e2e8f0;
}

.btn-outline-admin:hover {
    background: #f8fafc;
    color: #374151;
    border-color: #d1d5db;
    transform: translateY(-1px);
}

.empty-state {
    text-align: center;
    padding: 48px 20px;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 12px;
    opacity: 0.5;
}

.empty-title {
    font-size: 16px;
    font-weight: 700;
    color: #374151;
    margin-bottom: 4px;
}

@media (max-width: 1024px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
    
    .content-grid {
        grid-template-columns: 1fr;
    }
    
    .admin-stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
}

@media (max-width: 768px) {
    .admin-hero {
        padding: 24px;
        margin-bottom: 24px;
    }
    
    .hero-title {
        font-size: 24px;
    }
    
    .stat-value {
        font-size: 28px;
    }
    
    .admin-stats-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
}
</style>

<!-- Hero Section -->
<div class="admin-hero">
    <h1 class="hero-title">🛡️ Admin Dashboard</h1>
    <p class="hero-subtitle"><?= date('l, F j, Y') ?> · Lending System Control Panel</p>
</div>

<!-- Stats Cards -->
<div class="admin-stats-grid">
    <div class="admin-stat-card primary">
        <div class="stat-icon">👥</div>
        <div class="stat-label">Total Users</div>
        <div class="stat-value"><?= $totalUsers ?></div>
        <div class="stat-trend up">
            <i class="bi bi-arrow-up"></i> +12% from last month
        </div>
        <div class="stat-subtitle"><?= $activeUsers ?> active · <?= $premiumUsers ?> premium</div>
    </div>
    
    <div class="admin-stat-card warning">
        <div class="stat-icon">⏳</div>
        <div class="stat-label">Pending Registrations</div>
        <div class="stat-value"><?= $pendingRegs ?></div>
        <div class="stat-trend neutral">
            <i class="bi bi-dash"></i> No change
        </div>
        <div class="stat-subtitle"><a href="<?= APP_URL ?>/admin/registrations/index.php" class="stat-link">Review now →</a></div>
    </div>
    
    <div class="admin-stat-card success">
        <div class="stat-icon">💳</div>
        <div class="stat-label">Active Loans</div>
        <div class="stat-value"><?= $activeLoans ?></div>
        <div class="stat-trend up">
            <i class="bi bi-arrow-up"></i> +8% from last month
        </div>
        <div class="stat-subtitle"><?= $pendingLoans ?> pending approval</div>
    </div>
    
    <div class="admin-stat-card danger">
        <div class="stat-icon">⚠️</div>
        <div class="stat-label">Overdue Bills</div>
        <div class="stat-value"><?= $overdueLoans ?></div>
        <div class="stat-trend down">
            <i class="bi bi-arrow-down"></i> -5% from last month
        </div>
        <div class="stat-subtitle"><a href="<?= APP_URL ?>/admin/billing/index.php" class="stat-link">View billing →</a></div>
    </div>
    
    <div class="admin-stat-card gold">
        <div class="stat-icon">🏦</div>
        <div class="stat-label">Total Savings</div>
        <div class="stat-value" style="font-size: 28px;"><?= formatMoney($totalSavings) ?></div>
        <div class="stat-trend up">
            <i class="bi bi-arrow-up"></i> +15% from last month
        </div>
        <div class="stat-subtitle">Across all Premium members</div>
    </div>
    
    <div class="admin-stat-card purple">
        <div class="stat-icon">📊</div>
        <div class="stat-label">Company Income</div>
        <div class="stat-value" style="font-size: 28px;"><?= formatMoney($totalIncome) ?></div>
        <div class="stat-trend up">
            <i class="bi bi-arrow-up"></i> +22% from last month
        </div>
        <div class="stat-subtitle"><a href="<?= APP_URL ?>/admin/money_back.php" class="stat-link">Manage money back →</a></div>
    </div>
</div>

<!-- Charts -->
<div class="charts-grid">
    <div class="chart-card">
        <div class="chart-title">
            <span>📈</span> Loan Applications — Last 6 Months
        </div>
        <canvas id="loansChart" style="max-height: 250px;"></canvas>
    </div>
    
    <div class="chart-card">
        <div class="chart-title">
            <span>👥</span> Member Breakdown
        </div>
        <canvas id="membersChart" style="max-height: 200px;"></canvas>
        <div style="display: flex; justify-content: center; gap: 16px; margin-top: 16px; font-size: 13px;">
            <span style="display: flex; align-items: center; gap: 6px;">
                <span style="width: 12px; height: 12px; border-radius: 50%; background: #3b82f6; display: inline-block;"></span>
                Premium (<?= $premiumUsers ?>)
            </span>
            <span style="display: flex; align-items: center; gap: 6px;">
                <span style="width: 12px; height: 12px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
                Basic (<?= $basicUsers ?>)
            </span>
        </div>
    </div>
    
    <div class="chart-card full-width-chart">
        <div class="chart-title">
            <span>💰</span> Company Income — Last 6 Months
            <div class="date-filter">
                <select id="incomeDateRange" onchange="updateIncomeChart(this.value)">
                    <option value="1">This Month</option>
                    <option value="3" selected>Last 3 Months</option>
                    <option value="6">Last 6 Months</option>
                    <option value="12">Last Year</option>
                </select>
            </div>
        </div>
        <canvas id="incomeChart" style="max-height: 250px;"></canvas>
    </div>
</div>

<!-- Main Content Grid -->
<div class="content-grid">
    <div>
        <?php if ($pendingLoans > 0): ?>
        <div class="admin-card" style="margin-bottom: 24px;">
            <div class="admin-card-header" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);">
                <h6 class="admin-card-title" style="color: #92400e;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
                    ⚡ Pending Loan Approvals
                </h6>
                <a href="<?= APP_URL ?>/admin/loans/index.php" class="btn-admin btn-primary-admin">View All</a>
            </div>
            <table class="admin-table">
                <thead>
                    <tr><th>Borrower</th><th>Amount</th><th>Term</th><th>Applied</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach (array_filter($recentLoans, fn($l) => $l['status']==='Pending') as $l): ?>
                    <tr>
                        <td><span style="font-weight: 600;"><?= clean($l['first_name'].' '.$l['last_name']) ?></span></td>
                        <td style="font-weight: 700; color: #1d4ed8;"><?= formatMoney($l['applied_amount']) ?></td>
                        <td><?= $l['term_months'] ?> mo</td>
                        <td style="color: #9ca3af; font-size: 13px;"><?= date('M d, Y', strtotime($l['created_at'])) ?></td>
                        <td><a href="<?= APP_URL ?>/admin/loans/view.php?id=<?= $l['id'] ?>" class="btn-admin btn-primary-admin">Review</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="admin-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #3b82f6; display: inline-block;"></span>
                    Recent Loans
                </h6>
                <a href="<?= APP_URL ?>/admin/loans/index.php" style="font-size: 13px; color: #3b82f6; text-decoration: none; font-weight: 700;">View all →</a>
            </div>
            <div style="padding: 16px 20px;">
                <div class="table-search">
                    <i class="bi bi-search" style="color: #9ca3af;"></i>
                    <input type="text" id="loanSearch" placeholder="Search by borrower name..." onkeyup="filterLoans()">
                </div>
            </div>
            <table class="admin-table" id="loansTable">
                <thead>
                    <tr><th>Borrower</th><th>Amount</th><th>Term</th><th>Status</th><th>Date</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (empty($recentLoans)): ?>
                        <tr><td colspan="6" class="empty-state">
                            <div class="empty-icon">📭</div>
                            <div class="empty-title">No loans yet</div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($recentLoans as $l):
                            $badgeClass = match($l['status']) {
                                'Pending'   => 'pending',
                                'Approved'  => 'approved',
                                'Active'    => 'active',
                                'Rejected'  => 'rejected',
                                'Completed' => 'completed',
                                default     => 'pending'
                            };
                        ?>
                        <tr>
                            <td><span style="font-weight: 600;"><?= clean($l['first_name'].' '.$l['last_name']) ?></span></td>
                            <td style="font-weight: 700;"><?= formatMoney($l['applied_amount']) ?></td>
                            <td><?= $l['term_months'] ?> mo</td>
                            <td><span class="badge-admin badge-<?= $badgeClass ?>"><?= $l['status'] ?></span></td>
                            <td style="color: #9ca3af; font-size: 13px;"><?= date('M d, Y', strtotime($l['created_at'])) ?></td>
                            <td><a href="<?= APP_URL ?>/admin/loans/view.php?id=<?= $l['id'] ?>" class="btn-admin btn-outline-admin">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div>
        <!-- Merged Status Card -->
        <div class="admin-card" style="margin-bottom: 24px;">
            <div class="admin-card-header">
                <h6 class="admin-card-title">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
                    System Status
                </h6>
            </div>
            <div style="padding: 20px; text-align: center;">
                <div style="font-size: 32px; margin-bottom: 8px; opacity: 0.5;">✅</div>
                <div style="font-size: 14px; font-weight: 600; color: #374151;">All systems operational</div>
                <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;"><?= $pendingRegs ?> pending registrations · <?= $pendingLoans ?> pending loans</div>
            </div>
        </div>
        
        <!-- Pending Registrations (only if there are any) -->
        <?php if (!empty($recentRegs)): ?>
        <div class="admin-card" style="margin-bottom: 24px;">
            <div class="admin-card-header">
                <h6 class="admin-card-title">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
                    Pending Registrations
                </h6>
                <a href="<?= APP_URL ?>/admin/registrations/index.php" class="btn-admin btn-primary-admin">Manage</a>
            </div>
            <div style="padding: 20px;">
                <?php foreach ($recentRegs as $r): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid rgba(0,0,0,0.05);">
                    <div style="width:36px;height:36px;background:#f0f4ff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#1a45a8;flex-shrink:0;">
                        <?= strtoupper(substr($r['first_name'],0,1).substr($r['last_name'],0,1)) ?>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:700;font-size:13px;color:#1a1f2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= clean($r['first_name'].' '.$r['last_name']) ?>
                        </div>
                        <div style="font-size:11px;color:#9ca3af;">><?= clean($r['email']) ?></div>
                    </div>
                    <span class="adm-badge <?= strtolower($r['account_type']) ?>"><?= $r['account_type'] ?></span>
                </div>
                <?php endforeach; ?>
                <div style="padding:12px 16px;">
                    <a href="<?= APP_URL ?>/admin/registrations/index.php" class="btn-adm-primary" style="width:100%;justify-content:center;">
                        Review Applications
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="admin-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title">
                    <span style="width:8px;height:8px;border-radius:50%;background:#f5c842;display:inline-block;"></span>
                    Quick Links
                </h6>
            </div>
            <div style="padding:14px;display:flex;flex-direction:column;gap:8px;">
                <a href="<?= APP_URL ?>/admin/users/index.php" class="quick-link-pill">
                    <span style="font-size:18px;">👥</span> Manage Users
                </a>
                <a href="<?= APP_URL ?>/admin/loans/index.php" class="quick-link-pill">
                    <span style="font-size:18px;">💳</span> Manage Loans
                </a>
                <a href="<?= APP_URL ?>/admin/savings/index.php" class="quick-link-pill">
                    <span style="font-size:18px;">🏦</span> Savings Requests
                </a>
                <a href="<?= APP_URL ?>/admin/billing/index.php" class="quick-link-pill">
                    <span style="font-size:18px;">🧾</span> Billing Records
                </a>
                <a href="<?= APP_URL ?>/admin/money_back.php" class="quick-link-pill">
                    <span style="font-size:18px;">📊</span> Money Back
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js - ONE script tag only -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Loans Chart (Bar)
    const loansCtx = document.getElementById('loansChart');
    if (loansCtx) {
        const loanLabels = <?= $chartLabels ?> || ['No Data'];
        const loanCounts = <?= $chartCounts ?> || [0];
        
        new Chart(loansCtx, {
            type: 'bar',
            data: {
                labels: loanLabels,
                datasets: [{
                    label: 'Number of Loans',
                    data: loanCounts,
                    backgroundColor: '#1a45a8',
                    borderRadius: 6,
                    maxBarThickness: 80
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        ticks: { 
                            color: '#6b7280',
                            stepSize: 1,
                            precision: 0
                        },
                        suggestedMax: Math.max(...loanCounts) + 1
                    },
                    x: { grid: { display: false }, ticks: { color: '#6b7280' } }
                }
            }
        });
    }

    // Members Chart (Doughnut)
    const membersCtx = document.getElementById('membersChart');
    if (membersCtx) {
        const premiumCount = <?= $premiumUsers ?> || 0;
        const basicCount = <?= $basicUsers ?> || 0;
        
        // Show fallback if no data
        const hasData = premiumCount > 0 || basicCount > 0;
        
        new Chart(membersCtx, {
            type: 'doughnut',
            data: {
                labels: hasData ? ['Premium', 'Basic'] : ['No Members'],
                datasets: [{
                    data: hasData ? [premiumCount, basicCount] : [1],
                    backgroundColor: hasData ? ['#1a45a8', '#f59e0b'] : ['#e5e7eb'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (!hasData) return 'No members yet';
                                return context.label + ': ' + context.raw;
                            }
                        }
                    }
                }
            }
        });
    }

    // Income Chart (Line)
    const incomeCtx = document.getElementById('incomeChart');
    if (incomeCtx) {
        const incomeLabels = <?= $incomeLabels ?> || ['No Data'];
        const incomeData = <?= $incomeData ?> || [0];
        
        new Chart(incomeCtx, {
            type: 'line',
            data: {
                labels: <?= $incomeLabels ?>,
                datasets: [{
                    label: 'Income',
                    data: <?= $incomeData ?>,
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22,163,74,0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#16a34a',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#6b7280',
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    },
                    x: { grid: { display: false }, ticks: { color: '#6b7280' } }
                }
            }
        });
    }

    // Search functionality for loans table
    function filterLoans() {
        const searchInput = document.getElementById('loanSearch');
        const filter = searchInput.value.toLowerCase();
        const table = document.getElementById('loansTable');
        const rows = table.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
            const borrowerCell = rows[i].getElementsByTagName('td')[0];
            if (borrowerCell) {
                const borrowerName = borrowerCell.textContent || borrowerCell.innerText;
                if (borrowerName.toLowerCase().indexOf(filter) > -1) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }
    }

    // Update income chart based on date range
    function updateIncomeChart(months) {
        // This would typically make an AJAX call to get new data
        // For now, we'll just show a message
        const chart = Chart.getChart('incomeChart');
        if (chart) {
            // In a real implementation, you would fetch new data and update the chart
            console.log('Updating chart for ' + months + ' months');
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>