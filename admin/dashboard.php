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

<style>
.adm-welcome { margin-bottom: 22px; }
.adm-welcome h1 { font-size: 24px; font-weight: 700; color: #0f2557; margin: 0 0 4px; }
.adm-welcome h1 span { color: #1a45a8; }
.adm-welcome p { font-size: 13px; color: #6b7280; margin: 0; }
.main-grid-admin { display: grid; grid-template-columns: 1fr 300px; gap: 18px; }
@media(max-width:900px){ .main-grid-admin { grid-template-columns: 1fr; } }
.chart-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; margin-bottom: 18px; }
@media(max-width:900px){ .chart-grid { grid-template-columns: 1fr; } }
.chart-card { background: #fff; border-radius: 14px; padding: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); min-height: 220px; }
.chart-card canvas { min-height: 180px; max-height: 200px; }
.chart-card-title { font-size: 13px; font-weight: 700; color: #0f2557; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }

.admin-stat {
    background: #fff;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    transition: transform 0.15s, box-shadow 0.15s;
    border-top: 4px solid transparent;
}
.admin-stat:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.09);
}
.admin-stat.c-blue .as-value   { color: #1a45a8; }
.admin-stat.c-green .as-value  { color: #16a34a; }
.admin-stat.c-orange .as-value { color: #ea7c0a; }
.admin-stat.c-gold .as-value   { color: #f59e0b; }
.admin-stat.c-purple .as-value { color: #7c3aed; }
.admin-stat.c-red .as-value    { color: #dc2626; }
.admin-stat.c-blue   { border-top-color: #1a45a8; }
.admin-stat.c-orange { border-top-color: #ea7c0a; }
.admin-stat.c-green  { border-top-color: #16a34a; }
.admin-stat.c-red    { border-top-color: #dc2626; }
.admin-stat.c-gold   { border-top-color: #f59e0b; }
.admin-stat.c-purple { border-top-color: #7c3aed; }

.as-icon {
    font-size: 22px;
    margin-bottom: 10px;
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.admin-stat.c-blue .as-icon   { background: #e0e7ff; }
.admin-stat.c-orange .as-icon { background: #fff3e0; }
.admin-stat.c-green .as-icon  { background: #e8f5e9; }
.admin-stat.c-red .as-icon    { background: #fde8e8; }
.admin-stat.c-gold .as-icon   { background: #fffde7; }
.admin-stat.c-purple .as-icon { background: #f3e5f5; }

.admin-table tbody tr:hover { background: #f8fafc; transition: background 0.2s; }
</style>

<!-- Welcome -->
<div class="adm-welcome">
    <h1>Admin <span>Dashboard</span> 🛡️</h1>
    <p><?= date('l, F j, Y') ?> &nbsp;·&nbsp; Lending System Control Panel</p>
</div>

<!-- Stat Cards Row 1 -->
<div class="admin-stat-grid">
    <div class="admin-stat c-blue">
        <div class="as-icon">👥</div>
        <div class="as-label">Total Users</div>
        <div class="as-value"><?= $totalUsers ?></div>
        <div class="as-sub"><?= $activeUsers ?> active · <?= $premiumUsers ?> premium</div>
    </div>
    <div class="admin-stat c-orange">
        <div class="as-icon">⏳</div>
        <div class="as-label">Pending Registrations</div>
        <div class="as-value"><?= $pendingRegs ?></div>
        <div class="as-sub"><a href="<?= APP_URL ?>/admin/registrations/index.php" style="color:inherit;font-weight:700;">Review now →</a></div>
    </div>
    <div class="admin-stat c-green">
        <div class="as-icon">💳</div>
        <div class="as-label">Active Loans</div>
        <div class="as-value"><?= $activeLoans ?></div>
        <div class="as-sub"><?= $pendingLoans ?> pending approval</div>
    </div>
    <div class="admin-stat c-red">
        <div class="as-icon">⚠️</div>
        <div class="as-label">Overdue Bills</div>
        <div class="as-value"><?= $overdueLoans ?></div>
        <div class="as-sub"><a href="<?= APP_URL ?>/admin/billing/index.php" style="color:inherit;font-weight:700;">View billing →</a></div>
    </div>
</div>

<!-- Stat Cards Row 2 -->
<div class="admin-stat-grid" style="margin-top:-8px;">
    <div class="admin-stat c-gold">
        <div class="as-icon">🏦</div>
        <div class="as-label">Total Savings</div>
        <div class="as-value" style="font-size:20px;"><?= formatMoney($totalSavings) ?></div>
        <div class="as-sub">Across all Premium members</div>
    </div>
    <div class="admin-stat c-purple">
        <div class="as-icon">📊</div>
        <div class="as-label">Company Income</div>
        <div class="as-value" style="font-size:20px;"><?= formatMoney($totalIncome) ?></div>
        <div class="as-sub"><a href="<?= APP_URL ?>/admin/money_back.php" style="color:inherit;font-weight:700;">Manage money back →</a></div>
    </div>
    <div class="admin-stat c-blue">
        <div class="as-icon">📋</div>
        <div class="as-label">Total Loans</div>
        <div class="as-value"><?= $totalLoans ?></div>
        <div class="as-sub"><a href="<?= APP_URL ?>/admin/loans/index.php" style="color:inherit;font-weight:700;">View all →</a></div>
    </div>
    <div class="admin-stat c-green">
        <div class="as-icon">⭐</div>
        <div class="as-label">Premium Members</div>
        <div class="as-value"><?= $premiumUsers ?></div>
        <div class="as-sub">of <?= PREMIUM_MAX_SLOTS ?> max slots</div>
    </div>
</div>

<!-- Charts -->
<div class="chart-grid">
    <div class="chart-card" style="grid-column: span 2;">
        <div class="chart-card-title"><span>📈</span> Loan Applications — Last 6 Months</div>
        <canvas id="loansChart"></canvas>
    </div>
    <div class="chart-card">
        <div class="chart-card-title"><span>👥</span> Member Breakdown</div>
        <canvas id="membersChart"></canvas>
        <div style="display:flex;justify-content:center;gap:16px;margin-top:12px;font-size:12px;">
            <span style="display:flex;align-items:center;gap:5px;">
                <span style="width:10px;height:10px;border-radius:50%;background:#1a45a8;display:inline-block;"></span>
                Premium (<?= $premiumUsers ?>)
            </span>
            <span style="display:flex;align-items:center;gap:5px;">
                <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block;"></span>
                Basic (<?= $basicUsers ?>)
            </span>
        </div>
    </div>
    <div class="chart-card" style="grid-column: span 3;">
        <div class="chart-card-title"><span>💰</span> Company Income — Last 6 Months</div>
        <canvas id="incomeChart"></canvas>
    </div>
</div>

<!-- Main Grid -->
<div class="main-grid-admin">
<div>
    <?php if ($pendingLoans > 0): ?>
    <div class="admin-card" style="border-left: 4px solid #ea7c0a;">
        <div class="admin-card-head">
            <h6 class="admin-card-title">
                <span style="width:8px;height:8px;border-radius:50%;background:#ea7c0a;display:inline-block;"></span>
                ⚡ Pending Loan Approvals
            </h6>
            <a href="<?= APP_URL ?>/admin/loans/index.php" class="btn-adm-primary btn-adm-sm">View All</a>
        </div>
        <table class="admin-table">
            <thead>
                <tr><th>Borrower</th><th>Amount</th><th>Term</th><th>Applied</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach (array_filter($recentLoans, fn($l) => $l['status']==='Pending') as $l): ?>
                <tr>
                    <td><span style="font-weight:600;"><?= clean($l['first_name'].' '.$l['last_name']) ?></span></td>
                    <td style="font-weight:700;color:#1a45a8;"><?= formatMoney($l['applied_amount']) ?></td>
                    <td><?= $l['term_months'] ?> mo</td>
                    <td style="color:#9ca3af;font-size:12px;"><?= date('M d, Y', strtotime($l['created_at'])) ?></td>
                    <td><a href="<?= APP_URL ?>/admin/loans/view.php?id=<?= $l['id'] ?>" class="btn-adm-primary btn-adm-sm">Review</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="admin-card">
        <div class="admin-card-head">
            <h6 class="admin-card-title">
                <span style="width:8px;height:8px;border-radius:50%;background:#1a45a8;display:inline-block;"></span>
                Recent Loans
            </h6>
            <a href="<?= APP_URL ?>/admin/loans/index.php" style="font-size:12px;color:#1a45a8;text-decoration:none;font-weight:700;">View all →</a>
        </div>
        <table class="admin-table">
            <thead>
                <tr><th>Borrower</th><th>Amount</th><th>Term</th><th>Status</th><th>Date</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (empty($recentLoans)): ?>
                    <tr><td colspan="6" class="empty-state" style="padding:36px;"><div class="empty-icon">📭</div><div class="empty-title">No loans yet</div></td></tr>
                <?php else: ?>
                    <?php foreach ($recentLoans as $l):
                        $sc = match($l['status']) {
                            'Pending'   => 'pending',
                            'Approved'  => 'approved',
                            'Active'    => 'active',
                            'Rejected'  => 'rejected',
                            'Completed' => 'completed',
                            default     => 'pending'
                        };
                    ?>
                    <tr>
                        <td><span style="font-weight:600;"><?= clean($l['first_name'].' '.$l['last_name']) ?></span></td>
                        <td style="font-weight:700;"><?= formatMoney($l['applied_amount']) ?></td>
                        <td><?= $l['term_months'] ?> mo</td>
                        <td><span class="adm-badge <?= $sc ?>"><?= $l['status'] ?></span></td>
                        <td style="color:#9ca3af;font-size:12px;"><?= date('M d, Y', strtotime($l['created_at'])) ?></td>
                        <td><a href="<?= APP_URL ?>/admin/loans/view.php?id=<?= $l['id'] ?>" class="btn-adm-outline btn-adm-sm">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Sidebar -->
<div>
    <div class="admin-card">
        <div class="admin-card-head">
            <h6 class="admin-card-title">
                <span style="width:8px;height:8px;border-radius:50%;background:#ea7c0a;display:inline-block;"></span>
                Pending Registrations
            </h6>
            <a href="<?= APP_URL ?>/admin/registrations/index.php" style="font-size:12px;color:#1a45a8;text-decoration:none;font-weight:700;">View all →</a>
        </div>
        <div>
            <?php if (empty($recentRegs)): ?>
                <div class="empty-state" style="padding:28px;">
                    <div class="empty-icon">✅</div>
                    <div class="empty-title">All clear!</div>
                    <div class="empty-sub">No pending registrations</div>
                </div>
            <?php else: ?>
                <?php foreach ($recentRegs as $r): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid rgba(0,0,0,0.05);">
                    <div style="width:36px;height:36px;background:#f0f4ff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#1a45a8;flex-shrink:0;">
                        <?= strtoupper(substr($r['first_name'],0,1).substr($r['last_name'],0,1)) ?>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:700;font-size:13px;color:#1a1f2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= clean($r['first_name'].' '.$r['last_name']) ?>
                        </div>
                        <div style="font-size:11px;color:#9ca3af;"><?= clean($r['email']) ?></div>
                    </div>
                    <span class="adm-badge <?= strtolower($r['account_type']) ?>"><?= $r['account_type'] ?></span>
                </div>
                <?php endforeach; ?>
                <div style="padding:12px 16px;">
                    <a href="<?= APP_URL ?>/admin/registrations/index.php" class="btn-adm-primary" style="width:100%;justify-content:center;">
                        Review Applications
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-head">
            <h6 class="admin-card-title">
                <span style="width:8px;height:8px;border-radius:50%;background:#f5c842;display:inline-block;"></span>
                Quick Links
            </h6>
        </div>
        <div style="padding:14px;display:flex;flex-direction:column;gap:8px;">
            <a href="<?= APP_URL ?>/admin/users/index.php" style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#f8fafc;border-radius:9px;text-decoration:none;color:#1a1f2e;font-size:13px;font-weight:600;transition:all 0.15s;" onmouseover="this.style.background='#e0e7ff';this.style.color='#1a45a8'" onmouseout="this.style.background='#f8fafc';this.style.color='#1a1f2e'">
                <span style="font-size:18px;">👥</span> Manage Users
            </a>
            <a href="<?= APP_URL ?>/admin/loans/index.php" style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#f8fafc;border-radius:9px;text-decoration:none;color:#1a1f2e;font-size:13px;font-weight:600;transition:all 0.15s;" onmouseover="this.style.background='#e0e7ff';this.style.color='#1a45a8'" onmouseout="this.style.background='#f8fafc';this.style.color='#1a1f2e'">
                <span style="font-size:18px;">💳</span> Manage Loans
            </a>
            <a href="<?= APP_URL ?>/admin/savings/index.php" style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#f8fafc;border-radius:9px;text-decoration:none;color:#1a1f2e;font-size:13px;font-weight:600;transition:all 0.15s;" onmouseover="this.style.background='#e0e7ff';this.style.color='#1a45a8'" onmouseout="this.style.background='#f8fafc';this.style.color='#1a1f2e'">
                <span style="font-size:18px;">🏦</span> Savings Requests
            </a>
            <a href="<?= APP_URL ?>/admin/billing/index.php" style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#f8fafc;border-radius:9px;text-decoration:none;color:#1a1f2e;font-size:13px;font-weight:600;transition:all 0.15s;" onmouseover="this.style.background='#e0e7ff';this.style.color='#1a45a8'" onmouseout="this.style.background='#f8fafc';this.style.color='#1a1f2e'">
                <span style="font-size:18px;">🧾</span> Billing Records
            </a>
            <a href="<?= APP_URL ?>/admin/money_back.php" style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#f8fafc;border-radius:9px;text-decoration:none;color:#1a1f2e;font-size:13px;font-weight:600;transition:all 0.15s;" onmouseover="this.style.background='#e0e7ff';this.style.color='#1a45a8'" onmouseout="this.style.background='#f8fafc';this.style.color='#1a1f2e'">
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

});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>