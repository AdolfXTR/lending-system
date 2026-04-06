<?php
// user/dashboard.php
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../helpers.php';

$user_id     = $_SESSION['user_id'];
$accountType = $_SESSION['account_type'] ?? 'Basic';
$firstName   = $_SESSION['first_name'] ?? '';
$lastName    = $_SESSION['last_name'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM loans WHERE user_id = ? AND status = 'Active' LIMIT 1");
$stmt->execute([$user_id]);
$loan = $stmt->fetch();

$pendingLoan = null;
if (!$loan) {
    $stmt = $pdo->prepare("SELECT * FROM loans WHERE user_id = ? AND status = 'Pending' LIMIT 1");
    $stmt->execute([$user_id]);
    $pendingLoan = $stmt->fetch();
}

$currentBill = null;
if ($loan) {
    $stmt = $pdo->prepare("SELECT * FROM billing WHERE user_id = ? AND status IN ('Pending','Overdue') ORDER BY due_date ASC LIMIT 1");
    $stmt->execute([$user_id]);
    $currentBill = $stmt->fetch();
}

$savings = null;
if ($accountType === 'Premium') {
    $stmt = $pdo->prepare("SELECT * FROM savings WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $savings = $stmt->fetch();
}

$monthsPaid = 0; $totalMonths = 0; $paidAmount = 0;
if ($loan) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM billing WHERE loan_id = ? AND status = 'Completed'");
    $stmt->execute([$loan['id']]);
    $monthsPaid  = (int)$stmt->fetchColumn();
    $totalMonths = (int)$loan['term_months'];
    $monthlyDue  = $totalMonths > 0 ? $loan['applied_amount'] / $totalMonths : 0;
    $paidAmount  = round($monthsPaid * $monthlyDue, 2);
}

$stmt = $pdo->prepare("SELECT * FROM loan_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recentTxns = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT month_number, total_due, status FROM billing WHERE user_id = ? ORDER BY month_number ASC LIMIT 6");
$stmt->execute([$user_id]);
$billingHistory = $stmt->fetchAll();
$chartLabels = array_map(fn($b) => 'Mo. '.$b['month_number'], $billingHistory);
$chartData   = array_map(fn($b) => (float)$b['total_due'], $billingHistory);
$chartColors = array_map(fn($b) => $b['status'] === 'Completed' ? 'rgba(34,197,94,0.8)' : 'rgba(59,130,246,0.8)', $billingHistory);

$daysUntilDue = null; $dueSeverity = 'normal';
if ($currentBill) {
    $daysUntilDue = (int)ceil((strtotime($currentBill['due_date']) - time()) / 86400);
    if ($daysUntilDue < 0)       $dueSeverity = 'overdue';
    elseif ($daysUntilDue <= 5)  $dueSeverity = 'urgent';
    elseif ($daysUntilDue <= 10) $dueSeverity = 'warning';
}

require_once __DIR__ . '/../includes/header.php';
?>
<style>
.dash-welcome h1{font-size:24px;font-weight:700;color:#0f2557;margin:0 0 4px;}
.dash-welcome h1 span{color:#1a45a8;}
.dash-welcome p{font-size:13px;color:#6b7280;margin:0 0 24px;}
.stat-grid-4{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:22px;}
@media(max-width:860px){.stat-grid-4{grid-template-columns:repeat(2,1fr);}}
.stat-card{background:#fff;border-radius:14px;border:1px solid rgba(0,0,0,.07);padding:18px 18px 16px;box-shadow:0 1px 4px rgba(0,0,0,.05);transition:transform .15s,box-shadow .15s;position:relative;overflow:hidden;}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 6px 18px rgba(0,0,0,.09);}
.stat-card::after{content:'';position:absolute;top:0;left:0;right:0;height:3px;}
.stat-card.c-blue::after{background:#1a45a8;}.stat-card.c-orange::after{background:#ea7c0a;}.stat-card.c-green::after{background:#16a34a;}.stat-card.c-gray::after{background:#94a3b8;}
.s-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:17px;margin-bottom:10px;}
.c-blue .s-icon{background:#eff4ff;} .c-orange .s-icon{background:#fff7ed;} .c-green .s-icon{background:#f0fdf4;} .c-gray .s-icon{background:#f8fafc;}
.s-label{font-size:11px;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;}
.s-amount{font-size:26px;font-weight:700;line-height:1;letter-spacing:-.5px;margin-bottom:3px;}
.c-blue .s-amount{color:#1a45a8;}.c-orange .s-amount{color:#ea7c0a;}.c-green .s-amount{color:#16a34a;}.c-gray .s-amount{color:#94a3b8;}
.s-sub{font-size:11px;color:#9ca3af;}.s-link{font-size:12px;color:#1a45a8;text-decoration:none;font-weight:600;margin-top:5px;display:inline-block;}
.quick-actions{display:flex;flex-wrap:wrap;gap:9px;margin-bottom:22px;}
.qa-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:999px;font-size:13px;font-weight:600;text-decoration:none;border:none;cursor:pointer;font-family:inherit;transition:all .15s;}
.qa-primary{background:#1e3a5f;color:#f5c842;}.qa-primary:hover{background:#2a4a7f;color:#f5c842;}
.qa-outline{background:#f8fafc;color:#1e3a5f;border:1.5px solid #e2e8f0;}.qa-outline:hover{background:#1e3a5f;color:#f5c842;border-color:#1e3a5f;}
.qa-soft{background:#f8fafc;color:#1e3a5f;border:1.5px solid #e2e8f0;}.qa-soft:hover{background:#1e3a5f;color:#f5c842;border-color:#1e3a5f;}
.main-grid{display:grid;grid-template-columns:1fr 330px;gap:18px;}
@media(max-width:900px){.main-grid{grid-template-columns:1fr;}}
.sc{background:#fff;border-radius:14px;border:1px solid rgba(0,0,0,.07);box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;margin-bottom:18px;}
.sc:last-child{margin-bottom:0;}
.sc-head{padding:13px 18px;border-bottom:1px solid rgba(0,0,0,.06);display:flex;align-items:center;justify-content:space-between;}
.sc-title{font-size:13px;font-weight:700;color:#1a1f2e;display:flex;align-items:center;gap:7px;margin:0;}
.dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.bill-row{display:flex;justify-content:space-between;align-items:center;padding:9px 18px;border-bottom:1px solid rgba(0,0,0,.04);font-size:13px;}
.bill-row:last-child{border-bottom:none;}.bl{color:#6b7280;}.bv{font-weight:600;color:#1a1f2e;}.bv.danger{color:#dc2626;}.bv.warning{color:#f59e0b;}
.bill-total{background:#0f2557;padding:13px 18px;display:flex;justify-content:space-between;align-items:center;}
.tl{color:rgba(255,255,255,.6);font-size:13px;}.tv{color:#f5c842;font-size:22px;font-weight:700;}
.due-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.due-overdue{background:#fee2e2;color:#dc2626;}.due-urgent{background:#ffedd5;color:#c2410c;}.due-warning{background:#fef3c7;color:#d97706;}.due-normal{background:#e0f2fe;color:#0369a1;}
.prog-bar{height:10px;background:#e8ecf5;border-radius:20px;overflow:hidden;}
.prog-fill{height:100%;border-radius:20px;background:#1a45a8;transition:width .4s;}
.txn-tbl{width:100%;border-collapse:collapse;font-size:13px;}
.txn-tbl th{padding:9px 14px;text-align:left;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid #f3f4f6;background:#fafbfc;}
.txn-tbl td{padding:11px 14px;border-bottom:1px solid #f3f4f6;color:#111827;}
.txn-tbl tr:last-child td{border-bottom:none;}.txn-tbl tbody tr:hover{background:#f9fafb;}
.txn-mono{font-family:monospace;font-size:11px;color:#6b7280;}
.badge{font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;display:inline-block;}
.b-approved{background:#dcfce7;color:#16a34a;}.b-pending{background:#fef3c7;color:#d97706;}.b-rejected{background:#fee2e2;color:#dc2626;}
.lp-amount{font-size:30px;font-weight:700;color:#0f2557;letter-spacing:-1px;}
.lp-row{display:flex;justify-content:space-between;font-size:13px;padding:7px 0;border-bottom:1px solid rgba(0,0,0,.05);}
.lp-row:last-child{border-bottom:none;}.lp-l{color:#6b7280;}.lp-v{font-weight:700;color:#1a1f2e;}.lp-v.blue{color:#1a45a8;}

/* Dark mode styles */
body.dark-mode .dash-welcome h1{color:#f1f5f9;}
body.dark-mode .dash-welcome h1 span{color:#60a5fa;}
body.dark-mode .dash-welcome p{color:#94a3b8;}

body.dark-mode .stat-card{background:#1e293b;border-color:rgba(255,255,255,.1);}
body.dark-mode .stat-card:hover{box-shadow:0 6px 18px rgba(0,0,0,.4);}
body.dark-mode .c-blue .s-icon{background:#1e3a8a;} body.dark-mode .c-orange .s-icon{background:#92400e;} body.dark-mode .c-green .s-icon{background:#14532d;} body.dark-mode .c-gray .s-icon{background:#374151;}
body.dark-mode .s-label{color:#94a3b8;}
body.dark-mode .c-blue .s-amount{color:#60a5fa;} body.dark-mode .c-orange .s-amount{color:#fb923c;} body.dark-mode .c-green .s-amount{color:#4ade80;} body.dark-mode .c-gray .s-amount{color:#94a3b8;}
body.dark-mode .s-sub{color:#94a3b8;} body.dark-mode .s-link{color:#60a5fa;}

body.dark-mode .qa-primary{background:#1e293b;color:#fbbf24;} body.dark-mode .qa-primary:hover{background:#334155;color:#fbbf24;}
body.dark-mode .qa-outline{background:#1e293b;color:#f1f5f9;border-color:rgba(255,255,255,.2);} body.dark-mode .qa-outline:hover{background:#334155;color:#f1f5f9;}
body.dark-mode .qa-soft{background:#1e3a8a;color:#93c5fd;} body.dark-mode .qa-soft:hover{background:#2563eb;}

body.dark-mode .sc{background:#1e293b;border-color:rgba(255,255,255,.1);}
body.dark-mode .sc-head{border-color:rgba(255,255,255,.1);}
body.dark-mode .sc-title{color:#f1f5f9;}

body.dark-mode .bill-row{border-color:rgba(255,255,255,.1);}
body.dark-mode .bl{color:#94a3b8;} body.dark-mode .bv{color:#f1f5f9;} body.dark-mode .bv.danger{color:#f87171;} body.dark-mode .bv.warning{color:#fbbf24;}
body.dark-mode .bill-total{background:#0f2557 !important;}
body.dark-mode .tl{color:#94a3b8;} body.dark-mode .tv{color:#fbbf24;}

body.dark-mode .due-overdue{background:#7f1d1d;color:#fca5a5;} body.dark-mode .due-urgent{background:#92400e;color:#fed7aa;} body.dark-mode .due-warning{background:#92400e;color:#fef3c7;} body.dark-mode .due-normal{background:#1e3a8a;color:#93c5fd;}

body.dark-mode .prog-bar{background:#374151;}
body.dark-mode .prog-fill{background:#60a5fa;}

body.dark-mode .txn-tbl th{background:#334155;color:#94a3b8;border-color:rgba(255,255,255,.1);}
body.dark-mode .txn-tbl td{color:#e2e8f0;border-color:rgba(255,255,255,.1);}
body.dark-mode .txn-tbl tbody tr:hover{background:#475569;}
body.dark-mode .txn-mono{color:#94a3b8;}

body.dark-mode .b-approved{background:#14532d;color:#4ade80;} body.dark-mode .b-pending{background:#92400e;color:#fbbf24;} body.dark-mode .b-rejected{background:#7f1d1d;color:#f87171;}

body.dark-mode .lp-amount{color:#f1f5f9;}
body.dark-mode .lp-row{border-color:rgba(255,255,255,.1);}
body.dark-mode .lp-l{color:#94a3b8;} body.dark-mode .lp-v{color:#f1f5f9;} body.dark-mode .lp-v.blue{color:#60a5fa;}

body.dark-mode .lp-amount{color:#f1f5f9 !important;}
body.dark-mode div[style*="color:#9ca3af"]{color:#94a3b8 !important;}
body.dark-mode div[style*="color:#16a34a"]{color:#4ade80 !important;}
body.dark-mode div[style*="background:#f0f4ff"]{background:#1e3a8a !important;}
body.dark-mode a[style*="background:#0f2557"]{background:#1e293b !important;color:#fbbf24 !important;}
body.dark-mode a[style*="background:#f0fdf4"]{background:#14532d !important;color:#4ade80 !important;}
body.dark-mode a[style*="background:#f8fafc"]{background:#374151 !important;color:#e2e8f0 !important;border-color:rgba(255,255,255,.2) !important;}
body.dark-mode div[style*="background:#fef2f2"]{background:#7f1d1d !important;color:#fca5a5 !important;}
</style>

<div class="dash-welcome">
    <h1>Welcome back, <span><?= htmlspecialchars($firstName) ?></span>! 👋</h1>
    <p>
        <span style="background:#f5c842;color:#0f2557;font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px;margin-right:6px;"><?= $accountType ?></span>
        <?= date('l, F j, Y') ?>
    </p>
</div>

<!-- Stat Cards -->
<div class="stat-grid-4">
    <div class="stat-card c-blue">
        <div class="s-icon">💳</div>
        <div class="s-label">Active Loan</div>
        <?php if ($loan): ?>
            <div class="s-amount"><?= formatMoney($loan['applied_amount']) ?></div>
            <div class="s-sub">Received <?= formatMoney($loan['received_amount']) ?></div>
            <a href="<?= APP_URL ?>/user/loan/index.php" class="s-link">Details →</a>
        <?php elseif ($pendingLoan): ?>
            <div class="s-amount" style="font-size:18px;color:#d97706;">Pending</div>
            <div class="s-sub"><?= formatMoney($pendingLoan['applied_amount']) ?></div>
        <?php else: ?>
            <div class="s-amount" style="font-size:18px;color:#9ca3af;">None</div>
            <a href="<?= APP_URL ?>/user/loan/apply.php" class="s-link">Apply now →</a>
        <?php endif; ?>
    </div>

    <div class="stat-card c-orange">
        <div class="s-icon">🧾</div>
        <div class="s-label">Current Bill</div>
        <?php if ($currentBill): ?>
            <div class="s-amount"><?= formatMoney($currentBill['total_due']) ?></div>
            <div class="s-sub">Due <?= date('M d, Y', strtotime($currentBill['due_date'])) ?></div>
            <span class="due-badge due-<?= $dueSeverity ?> mt-1">
                <?= $dueSeverity==='overdue'?'⚠ Overdue':($dueSeverity==='urgent'?"⚡ {$daysUntilDue}d left":($dueSeverity==='warning'?"⏰ {$daysUntilDue}d left":"✓ {$daysUntilDue}d left")) ?>
            </span>
        <?php else: ?>
            <div class="s-amount" style="font-size:18px;color:#16a34a;">Clear ✓</div>
            <div class="s-sub">No outstanding bills</div>
        <?php endif; ?>
    </div>

    <?php if ($accountType === 'Premium'): ?>
    <div class="stat-card c-green">
        <div class="s-icon">🏦</div>
        <div class="s-label">Savings</div>
        <div class="s-amount"><?= formatMoney($savings['balance'] ?? 0) ?></div>
        <div class="s-sub">Max ₱100,000</div>
        <?php $sp = SAVINGS_MAX > 0 ? round((($savings['balance']??0)/SAVINGS_MAX)*100) : 0; ?>
        <div class="prog-bar mt-2" style="height:5px;"><div class="prog-fill" style="width:<?=$sp?>%;background:#16a34a;"></div></div>
    </div>
    <?php else: ?>
    <div class="stat-card c-gray">
        <div class="s-icon">🔒</div>
        <div class="s-label">Savings</div>
        <div class="s-amount">—</div>
        <div class="s-sub">Premium Only</div>
        <div class="prog-bar mt-2" style="height:5px;"><div class="prog-fill" style="width:0%;background:#9ca3af;"></div></div>
    </div>
    <?php endif; ?>

    <div class="stat-card <?= $loan ? 'c-blue' : 'c-gray' ?>">
        <div class="s-icon">📅</div>
        <div class="s-label">Months Paid</div>
        <?php if ($loan && $totalMonths): ?>
            <div class="s-amount"><?= $monthsPaid ?><span style="font-size:16px;color:#9ca3af;font-weight:500;">/<?= $totalMonths ?></span></div>
            <div class="s-sub"><?= round(($monthsPaid/$totalMonths)*100) ?>% complete</div>
            <div class="prog-bar mt-2" style="height:5px;"><div class="prog-fill" style="width:<?= round(($monthsPaid/$totalMonths)*100) ?>%;"></div></div>
        <?php else: ?>
            <div class="s-amount" style="font-size:20px;color:#9ca3af;">—</div>
            <div class="s-sub">No active loan</div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <?php if (!$loan && !$pendingLoan): ?><a href="<?= APP_URL ?>/user/loan/apply.php" class="qa-btn qa-primary">💳 Apply for Loan</a><?php endif; ?>
    <?php if ($accountType==='Premium'): ?>
        <a href="<?= APP_URL ?>/user/savings/index.php" class="qa-btn qa-outline">🏦 Manage Savings</a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/user/billing/index.php" class="qa-btn qa-soft">🧾 View Billing</a>
    <a href="<?= APP_URL ?>/user/loan/index.php" class="qa-btn qa-soft">📋 Loan History</a>
    <a href="<?= APP_URL ?>/user/profile.php" class="qa-btn qa-soft">👤 Profile</a>
</div>

<!-- Main Grid -->
<div class="main-grid">
<div>

    <!-- Billing Statement -->
    <div class="sc">
        <div class="sc-head">
            <h6 class="sc-title"><span class="dot" style="background:#ea7c0a;"></span> Current Billing Statement</h6>
            <?php if ($currentBill): ?><span class="due-badge due-<?= $dueSeverity ?>"><?= $dueSeverity==='overdue'?'⚠ Overdue':($dueSeverity==='urgent'?'⚡ Urgent':($dueSeverity==='warning'?'⏰ Upcoming':'✓ On Track')) ?></span><?php endif; ?>
        </div>
        <?php if ($currentBill): ?>
            <?php if ($dueSeverity==='overdue'): ?>
            <div style="background:#fef2f2;border-left:4px solid #dc2626;padding:10px 18px;font-size:13px;color:#b91c1c;display:flex;gap:8px;align-items:center;">⚠️ <strong>Overdue!</strong> A 2% penalty has been applied.</div>
            <?php endif; ?>
            <div class="bill-row"><span class="bl">Month</span><span class="bv">Month <?= $currentBill['month_number'] ?></span></div>
            <div class="bill-row"><span class="bl">Principal</span><span class="bv"><?= formatMoney($currentBill['amount_due']) ?></span></div>
            <div class="bill-row"><span class="bl">Interest (3%)</span><span class="bv <?= $currentBill['interest']>0?'warning':'' ?>"><?= $currentBill['interest']>0?formatMoney($currentBill['interest']):'—' ?></span></div>
            <div class="bill-row"><span class="bl">Penalty (2%)</span><span class="bv <?= $currentBill['penalty']>0?'danger':'' ?>"><?= $currentBill['penalty']>0?formatMoney($currentBill['penalty']):'—' ?></span></div>
            <div class="bill-total"><span class="tl">Due <?= date('F d, Y', strtotime($currentBill['due_date'])) ?></span><span class="tv"><?= formatMoney($currentBill['total_due']) ?></span></div>
        <?php else: ?>
            <div style="padding:36px;text-align:center;"><div style="font-size:36px;">✅</div><div style="font-weight:700;font-size:15px;color:#16a34a;margin:8px 0 4px;">No bills to pay</div><div style="font-size:13px;color:#9ca3af;">You're all caught up!</div></div>
        <?php endif; ?>
    </div>

    <!-- Chart -->
    <?php if (!empty($chartData)): ?>
    <div class="sc">
        <div class="sc-head">
            <h6 class="sc-title"><span class="dot" style="background:#1a45a8;"></span> Monthly Payment Schedule</h6>
            <span style="font-size:11px;color:#9ca3af;">Green = paid</span>
        </div>
        <div style="padding:16px 18px;"><canvas id="billingChart" height="110"></canvas></div>
    </div>
    <?php endif; ?>

    <!-- Transactions -->
    <div class="sc">
        <div class="sc-head">
            <h6 class="sc-title"><span class="dot" style="background:#f5c842;"></span> Recent Loan Transactions</h6>
            <a href="<?= APP_URL ?>/user/loan/index.php" style="font-size:12px;color:#1a45a8;text-decoration:none;font-weight:600;">View all →</a>
        </div>
        <?php if ($recentTxns): ?>
        <table class="txn-tbl">
            <thead><tr><th>Transaction ID</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($recentTxns as $t):
                // Check if this is a loan application and the loan has been approved
                $displayStatus = $t['status'];
                if (strtolower($t['type']) === 'loan application' && $loan && $loan['status'] === 'Active') {
                    $displayStatus = 'Approved';
                }
                $sc = match(strtolower($displayStatus)) { 'approved'=>'b-approved','pending'=>'b-pending','rejected'=>'b-rejected', default=>'b-pending' };
            ?>
                <tr>
                    <td><span class="txn-mono"><?= htmlspecialchars($t['transaction_id']) ?></span></td>
                    <td><?= htmlspecialchars($t['type']) ?></td>
                    <td style="font-weight:700;"><?= formatMoney($t['amount']) ?></td>
                    <td><span class="badge <?= $sc ?>"><?= $displayStatus ?></span></td>
                    <td style="color:#9ca3af;"><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="padding:36px;text-align:center;color:#9ca3af;"><div style="font-size:28px;">📭</div><div style="margin-top:8px;font-size:13px;">No transactions yet.</div></div>
        <?php endif; ?>
    </div>

</div>

<!-- Sidebar -->
<div>
    <div class="sc">
        <div class="sc-head">
            <h6 class="sc-title"><span class="dot" style="background:#0f2557;"></span> Loan Progress</h6>
            <?php if ($loan): ?><span class="badge b-approved">Active</span><?php elseif ($pendingLoan): ?><span class="badge b-pending">Pending</span><?php endif; ?>
        </div>
        <div style="padding:18px;">
        <?php if ($loan): ?>
            <?php $pct = $totalMonths>0?round(($monthsPaid/$totalMonths)*100):0; ?>
            <div style="font-size:12px;color:#9ca3af;margin-bottom:3px;">Total Loaned</div>
            <div class="lp-amount"><?= formatMoney($loan['applied_amount']) ?></div>
            <div style="font-size:12px;color:#9ca3af;margin-bottom:12px;"><?= $monthsPaid ?> of <?= $totalMonths ?> months paid</div>
            <div class="prog-bar" style="height:12px;"><div class="prog-fill" style="width:<?= $pct ?>%;"></div></div>
            <div style="display:flex;justify-content:space-between;font-size:11px;color:#9ca3af;margin:5px 0 16px;">
                <span>₱<?= number_format($paidAmount,0) ?> paid</span><span><?= $pct ?>%</span><span>₱<?= number_format($loan['applied_amount']-$paidAmount,0) ?> left</span>
            </div>
            <div style="background:#f0f4ff;border-radius:10px;padding:12px;margin-bottom:14px;">
                <div class="lp-row"><span class="lp-l">Received</span><span class="lp-v"><?= formatMoney($loan['received_amount']) ?></span></div>
                <div class="lp-row"><span class="lp-l">Term</span><span class="lp-v"><?= $totalMonths ?> month<?= $totalMonths > 1 ? 's' : '' ?></span></div>
                <div class="lp-row"><span class="lp-l">Monthly</span><span class="lp-v"><?= formatMoney($loan['applied_amount']/$totalMonths) ?></span></div>
                <?php if ($currentBill): ?>
                <div class="lp-row"><span class="lp-l">Next Due</span><span class="lp-v blue"><?= date('M d, Y', strtotime($currentBill['due_date'])) ?></span></div>
                <?php endif; ?>
            </div>
            <a href="<?= APP_URL ?>/user/billing/index.php" style="display:block;text-align:center;background:#0f2557;color:#f5c842;padding:11px;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;">View Billing Schedule →</a>
        <?php elseif ($pendingLoan): ?>
            <div style="text-align:center;padding:16px 0;">
                <div style="font-size:36px;">⏳</div>
                <div style="font-weight:700;font-size:15px;margin:8px 0 4px;">Under Review</div>
                <div style="font-size:13px;color:#9ca3af;"><?= formatMoney($pendingLoan['applied_amount']) ?> awaiting admin approval</div>
            </div>
        <?php else: ?>
            <div style="text-align:center;padding:16px 0;">
                <div style="font-size:36px;">💳</div>
                <div style="font-weight:700;font-size:15px;margin:8px 0 4px;">No Active Loan</div>
                <div style="font-size:13px;color:#9ca3af;margin-bottom:14px;">Apply for up to ₱10,000</div>
                <a href="<?= APP_URL ?>/user/loan/apply.php" style="display:block;text-align:center;background:#0f2557;color:#f5c842;padding:11px;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;">Apply for a Loan →</a>
            </div>
        <?php endif; ?>
        </div>
    </div>

    <?php if ($accountType === 'Premium'): ?>
    <div class="sc">
        <div class="sc-head">
            <h6 class="sc-title"><span class="dot" style="background:#16a34a;"></span> My Savings</h6>
        </div>
        <div style="padding:18px;">
            <?php $sb=$savings['balance']??0; $sp2=SAVINGS_MAX>0?round(($sb/SAVINGS_MAX)*100,1):0; ?>
            <div style="font-size:28px;font-weight:700;color:#16a34a;letter-spacing:-1px;"><?= formatMoney($sb) ?></div>
            <div style="font-size:12px;color:#9ca3af;margin-bottom:8px;"><?= $sp2 ?>% of ₱100,000 max</div>
            <div class="prog-bar" style="height:8px;margin-bottom:14px;"><div class="prog-fill" style="width:<?= $sp2 ?>%;background:#16a34a;"></div></div>
            <div style="display:flex;gap:8px;">
                <a href="<?= APP_URL ?>/user/savings/index.php" style="flex:1;text-align:center;background:#f0fdf4;color:#16a34a;padding:9px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;">+ Deposit</a>
                <a href="<?= APP_URL ?>/user/savings/index.php" style="flex:1;text-align:center;background:#f8fafc;color:#374151;padding:9px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;border:1px solid rgba(0,0,0,.08);">Withdraw</a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="sc">
        <div class="sc-head">
            <h6 class="sc-title"><span class="dot" style="background:#94a3b8;"></span> My Savings</h6>
        </div>
        <div style="padding:18px;">
            <div style="text-align:center;padding:16px 0;">
                <div style="font-size:36px;">🔒</div>
                <div style="font-weight:700;font-size:15px;margin:8px 0 4px;">Premium Feature</div>
                <div style="font-size:13px;color:#9ca3af;margin-bottom:14px;">Upgrade to unlock Savings & Money Back features</div>
                <a href="#" class="qa-btn qa-outline" style="width:100%;justify-content:center;background:#fef3c7;color:#92400e;border-color:#f59e0b;" title="Upgrade to Premium to unlock Savings features">🔒 Upgrade to Premium</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
</div>

<?php if (!empty($chartData)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('billingChart').getContext('2d'),{
    type:'bar',
    data:{
        labels:<?= json_encode($chartLabels) ?>,
        datasets:[{
            label:'Amount Due (₱)',
            data:<?= json_encode($chartData) ?>,
            backgroundColor:<?= json_encode($chartColors) ?>,
            borderRadius:6,borderSkipped:false
        }]
    },
    options:{responsive:true,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>'₱ '+c.parsed.y.toLocaleString('en-PH',{minimumFractionDigits:2})}}},scales:{y:{beginAtZero:true,grid:{color:'rgba(0,0,0,.04)'},ticks:{font:{size:11},callback:v=>'₱'+Number(v).toLocaleString()}},x:{grid:{display:false},ticks:{font:{size:11}}}}}
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>