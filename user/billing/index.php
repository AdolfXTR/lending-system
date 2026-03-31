<?php
// ============================================================
//  user/billing/index.php
// ============================================================
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../helpers.php';

$userId = $_SESSION['user_id'];

// Get active loan
$stmt = $pdo->prepare("SELECT * FROM loans WHERE user_id = ? AND status IN ('Approved','Active') ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$userId]);
$loan = $stmt->fetch();

// Current unpaid bill
$currentBill = null;
if ($loan) {
    $stmt = $pdo->prepare("
        SELECT * FROM billing 
        WHERE user_id = ? AND loan_id = ? AND status IN ('Pending','Overdue') 
        ORDER BY due_date ASC LIMIT 1
    ");
    $stmt->execute([$userId, $loan['id']]);
    $currentBill = $stmt->fetch();
}


// Days until due
$daysUntilDue = null;
$dueSeverity  = 'normal';
if ($currentBill) {
    $daysUntilDue = (int)ceil((strtotime($currentBill['due_date']) - time()) / 86400);
    if ($daysUntilDue < 0)       $dueSeverity = 'overdue';
    elseif ($daysUntilDue <= 5)  $dueSeverity = 'urgent';
    elseif ($daysUntilDue <= 10) $dueSeverity = 'warning';
}

// All billing grouped by year then month
$stmt = $pdo->prepare("SELECT * FROM billing WHERE user_id = ? ORDER BY due_date DESC");
$stmt->execute([$userId]);
$allBilling = $stmt->fetchAll();

$grouped = [];
foreach ($allBilling as $b) {
    $year  = date('Y', strtotime($b['due_date']));
    $month = date('F', strtotime($b['due_date']));
    $grouped[$year][$month][] = $b;
}

$totalBills     = count($allBilling);
$completedBills = count(array_filter($allBilling, fn($b) => $b['status'] === 'Completed'));
$overdueBills   = count(array_filter($allBilling, fn($b) => $b['status'] === 'Overdue'));

$pageTitle = 'My Billing';
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.bill-stat-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:22px; }
.bill-stat { background:#fff; border-radius:14px; border:1px solid rgba(0,0,0,.07); box-shadow:0 1px 4px rgba(0,0,0,.05); padding:16px 20px; display:flex; align-items:center; gap:14px; }
body.dark-mode .bill-stat { background:#1e293b !important; border-color:rgba(255,255,255,.1) !important; }
.bill-stat-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.bill-stat-icon.blue{background:#eff4ff;} .bill-stat-icon.green{background:#f0fdf4;} .bill-stat-icon.red{background:#fef2f2;}
body.dark-mode .bill-stat-icon.blue{background:#1e3a8a !important;} body.dark-mode .bill-stat-icon.green{background:#14532d !important;} body.dark-mode .bill-stat-icon.red{background:#7f1d1d !important;}
.bill-stat-label { font-size:11px; color:#9ca3af; font-weight:600; text-transform:uppercase; letter-spacing:.05em; margin-bottom:3px; }
body.dark-mode .bill-stat-label { color:#94a3b8 !important; }
.bill-stat-value { font-size:22px; font-weight:700; line-height:1; }
.bill-stat-value.blue{color:#1a45a8;} .bill-stat-value.green{color:#16a34a;} .bill-stat-value.red{color:#dc2626;}
body.dark-mode .bill-stat-value.blue{color:#60a5fa !important;} body.dark-mode .bill-stat-value.green{color:#4ade80 !important;} body.dark-mode .bill-stat-value.red{color:#f87171 !important;}

.sc { background:#fff; border-radius:14px; border:1px solid rgba(0,0,0,.07); box-shadow:0 1px 4px rgba(0,0,0,.05); overflow:hidden; margin-bottom:20px; }
body.dark-mode .sc { background:#1e293b !important; border-color:rgba(255,255,255,.1) !important; }
.sc-head { padding:14px 20px; border-bottom:1px solid rgba(0,0,0,.06); display:flex; align-items:center; justify-content:space-between; }
body.dark-mode .sc-head { border-color:rgba(255,255,255,.1) !important; }
.sc-title { font-size:13px; font-weight:700; color:#0f2557; display:flex; align-items:center; gap:8px; margin:0; }
body.dark-mode .sc-title { color:#f1f5f9 !important; }
.dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }

.due-chip { display:inline-flex; align-items:center; gap:4px; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; }
.due-chip.overdue{background:#fee2e2;color:#dc2626;} .due-chip.urgent{background:#ffedd5;color:#c2410c;} .due-chip.warning{background:#fef3c7;color:#d97706;} .due-chip.normal{background:#e0f2fe;color:#0369a1;}
body.dark-mode .due-chip.overdue{background:#7f1d1d !important; color:#fca5a5 !important;} body.dark-mode .due-chip.urgent{background:#92400e !important; color:#fed7aa !important;} body.dark-mode .due-chip.warning{background:#92400e !important; color:#fef3c7 !important;} body.dark-mode .due-chip.normal{background:#1e3a8a !important; color:#93c5fd !important;}

.bill-meta-row { display:grid; grid-template-columns:repeat(4,1fr); padding:20px; border-bottom:1px solid rgba(0,0,0,.05); }
body.dark-mode .bill-meta-row { border-color:rgba(255,255,255,.1) !important; }
.bill-meta-item { text-align:center; padding:0 10px; }
.bill-meta-item + .bill-meta-item { border-left:1px solid rgba(0,0,0,.06); }
body.dark-mode .bill-meta-item + .bill-meta-item { border-color:rgba(255,255,255,.1) !important; }
.bml { font-size:11px; color:#9ca3af; font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin-bottom:6px; }
body.dark-mode .bml { color:#94a3b8 !important; }
.bmv { font-size:18px; font-weight:700; color:#1a1f2e; }
body.dark-mode .bmv { color:#f1f5f9 !important; }
.bmv.danger{color:#dc2626;} .bmv.primary{color:#1a45a8;} .bmv.gold{color:#b07e00;}
body.dark-mode .bmv.danger{color:#f87171 !important;} body.dark-mode .bmv.primary{color:#60a5fa !important;} body.dark-mode .bmv.gold{color:#fbbf24 !important;}

.bd-wrap { padding:20px; }
.bd-title { font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em; margin-bottom:12px; }
body.dark-mode .bd-title { color:#94a3b8 !important; }
.bd-row { display:flex; justify-content:space-between; align-items:center; padding:9px 0; border-bottom:1px solid rgba(0,0,0,.05); font-size:13px; }
body.dark-mode .bd-row { border-color:rgba(255,255,255,.1) !important; }
.bd-row:last-child { border-bottom:none; }
.bd-label { color:#6b7280; }
body.dark-mode .bd-label { color:#e2e8f0 !important; }
.bd-value { font-weight:600; }
body.dark-mode .bd-value { color:#e2e8f0 !important; }
.bd-value.red { color:#dc2626; }
body.dark-mode .bd-value.red { color:#f87171 !important; }
.bill-total-bar { background:#0f2557; margin:0 -20px -20px; padding:14px 20px; display:flex; justify-content:space-between; align-items:center; border-radius:0 0 14px 14px; }
body.dark-mode .bill-total-bar { background:#1e293b !important; }
.bill-total-bar .tl{font-size:13px;color:rgba(255,255,255,.6);} .bill-total-bar .tv{font-size:22px;font-weight:700;color:#f5c842;}
body.dark-mode .bill-total-bar .tl{color:#94a3b8 !important;} body.dark-mode .bill-total-bar .tv{color:#fbbf24 !important;}

.overdue-banner { background:#fef2f2; border-left:4px solid #dc2626; padding:12px 20px; font-size:13px; color:#b91c1c; display:flex; align-items:center; gap:10px; }
body.dark-mode .overdue-banner { background:#7f1d1d !important; border-color:#dc2626 !important; color:#fca5a5 !important; }
.bill-info-note { background:#e0f2fe; padding:12px 20px; font-size:12px; color:#0369a1; display:flex; align-items:center; gap:8px; border-top:1px solid rgba(0,0,0,.05); }
body.dark-mode .bill-info-note { background:#1e3a8a !important; color:#93c5fd !important; border-color:rgba(255,255,255,.1) !important; }
.no-bill { padding:52px 20px; text-align:center; }

.yr-btn { width:100%; background:#f8fafc; border:none; padding:13px 20px; text-align:left; font-size:14px; font-weight:700; color:#0f2557; cursor:pointer; display:flex; align-items:center; justify-content:space-between; font-family:inherit; border-top:1px solid rgba(0,0,0,.05); transition:background .15s; }
body.dark-mode .yr-btn { background:#1e293b !important; color:#f1f5f9 !important; border-color:rgba(255,255,255,.1) !important; }
.yr-btn:hover { background:#f0f4ff; }
body.dark-mode .yr-btn:hover { background:#334155 !important; }
.yr-btn i { transition:transform .2s; font-size:12px; color:#9ca3af; }
body.dark-mode .yr-btn i { color:#94a3b8 !important; }
.yr-btn.open i { transform:rotate(180deg); }
.mo-btn { width:100%; background:#fff; border:none; padding:11px 20px 11px 36px; text-align:left; font-size:13px; font-weight:600; color:#374151; cursor:pointer; display:flex; align-items:center; justify-content:space-between; font-family:inherit; border-top:1px solid rgba(0,0,0,.04); transition:background .15s; }
body.dark-mode .mo-btn { background:#334155 !important; color:#e2e8f0 !important; border-color:rgba(255,255,255,.1) !important; }
.mo-btn:hover { background:#fafbfc; }
body.dark-mode .mo-btn:hover { background:#475569 !important; }
.mo-btn i { font-size:11px; color:#9ca3af; transition:transform .2s; }
body.dark-mode .mo-btn i { color:#94a3b8 !important; }
.mo-btn.open i { transform:rotate(180deg); }
.mo-badge { font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:#e8ecf5; color:#374151; margin-left:8px; }
body.dark-mode .mo-badge { background:#475569 !important; color:#e2e8f0 !important; }
.mo-badge.red { background:#fee2e2; color:#dc2626; }
body.dark-mode .mo-badge.red { background:#7f1d1d !important; color:#fca5a5 !important; }

.bill-tbl { width:100%; border-collapse:collapse; font-size:13px; }
.bill-tbl-wrap { overflow-x:auto; margin:0 -20px; padding:0 20px; }
body.dark-mode .bill-tbl-wrap { background:#1e293b !important; }
.bill-tbl { width:100%; border-collapse:collapse; font-size:13px; }
body.dark-mode .bill-tbl { background:#1e293b !important; }
.bill-tbl th { padding:9px 12px; text-align:left; font-size:11px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.04em; background:#fafbfc; border-bottom:1px solid rgba(0,0,0,.05); white-space:nowrap; }
body.dark-mode .bill-tbl th { background:#334155 !important; color:#94a3b8 !important; border-color:rgba(255,255,255,.1) !important; }
.bill-tbl td { padding:12px; border-bottom:1px solid rgba(0,0,0,.04); color:#111827; font-weight:500; }
body.dark-mode .bill-tbl td { color:#e2e8f0 !important; border-color:rgba(255,255,255,.1) !important; }
.bill-tbl tr:last-child td { border-bottom:none; }
.bill-tbl tbody tr:hover { background:#f8fafc; }
body.dark-mode .bill-tbl tbody tr:hover { background:#475569 !important; }
.bill-tbl-action { text-align:center; font-size:14px; padding:12px 8px; }
.b-badge { display:inline-block; font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; }
.b-badge.completed{background:#dcfce7;color:#16a34a;} .b-badge.overdue{background:#fee2e2;color:#dc2626;} .b-badge.pending{background:#fef3c7;color:#d97706;}
body.dark-mode .b-badge.completed{background:#14532d !important; color:#4ade80 !important;} body.dark-mode .b-badge.overdue{background:#7f1d1d !important; color:#f87171 !important;} body.dark-mode .b-badge.pending{background:#92400e !important; color:#fbbf24 !important;}
</style>

<!-- Page Header -->
<div style="margin-bottom:22px;">
    <h4 style="font-size:22px;font-weight:700;color:#0f2557;margin:0 0 4px;">
        <i class="bi bi-receipt" style="color:#1a45a8;"></i> My Billing
    </h4>
    <p style="font-size:13px;color:#9ca3af;margin:0;">Track your monthly loan payments and billing history.</p>
</div>

<?= showFlash() ?>

<!-- Stats -->
<div class="bill-stat-grid">
    <div class="bill-stat">
        <div class="bill-stat-icon blue">📋</div>
        <div><div class="bill-stat-label">Total Bills</div><div class="bill-stat-value blue"><?= $totalBills ?></div></div>
    </div>
    <div class="bill-stat">
        <div class="bill-stat-icon green">✅</div>
        <div><div class="bill-stat-label">Paid</div><div class="bill-stat-value green"><?= $completedBills ?></div></div>
    </div>
    <div class="bill-stat">
        <div class="bill-stat-icon red">⚠️</div>
        <div><div class="bill-stat-label">Overdue</div><div class="bill-stat-value red"><?= $overdueBills ?></div></div>
    </div>
</div>

<!-- Current Bill -->
<div class="sc">
    <div class="sc-head">
        <h6 class="sc-title"><span class="dot" style="background:#1a45a8;"></span> Current Bill</h6>
        <?php if ($currentBill): ?>
            <span class="due-chip <?= $dueSeverity ?>">
                <?php if ($dueSeverity==='overdue'): ?>⚠ Overdue
                <?php elseif ($dueSeverity==='urgent'): ?>⚡ <?= $daysUntilDue ?>d left
                <?php elseif ($dueSeverity==='warning'): ?>⏰ <?= $daysUntilDue ?>d left
                <?php else: ?>✓ <?= $daysUntilDue ?>d left<?php endif; ?>
            </span>
        <?php endif; ?>
    </div>

    <?php if (!$currentBill): ?>
        <div class="no-bill">
            <div style="font-size:64px;margin-bottom:16px;">🎉</div>
            <div style="font-size:20px;font-weight:700;color:#16a34a;margin-bottom:8px;">No bills to pay!</div>
            <div style="font-size:14px;color:#6b7280;line-height:1.5;">You're all caught up! Great job keeping up with your payments.<br>Keep up the excellent work! 🌟</div>
            <div style="margin-top:20px;">
                <a href="<?= APP_URL ?>/user/loan/index.php" style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#1a45a8;color:#fff;text-decoration:none;border-radius:8px;font-size:13px;font-weight:600;">
                    <i class="bi bi-plus-circle"></i> Apply for New Loan
                </a>
            </div>
        </div>
    <?php else: ?>
        <?php if ($dueSeverity==='overdue'): ?>
        <div class="overdue-banner">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span><strong>Your payment is overdue!</strong> A 2% penalty has been added to your total due.</span>
        </div>
        <?php endif; ?>

        <div class="bill-meta-row">
            <div class="bill-meta-item">
                <div class="bml">Month</div>
                <div class="bmv">Month <?= $currentBill['month_number'] ?></div>
            </div>
            <div class="bill-meta-item">
                <div class="bml">Due Date</div>
                <div class="bmv <?= $dueSeverity==='overdue'?'danger':'' ?>"><?= date('M d, Y', strtotime($currentBill['due_date'])) ?></div>
            </div>
            <div class="bill-meta-item">
                <div class="bml">Status</div>
                <div class="bmv <?= $dueSeverity==='overdue'?'danger':'gold' ?>"><?= $currentBill['status'] ?></div>
            </div>
            <div class="bill-meta-item">
                <div class="bml">Total Due</div>
                <div class="bmv <?= $dueSeverity==='overdue'?'danger':'primary' ?>" style="font-size:22px;"><?= formatMoney($currentBill['total_due']) ?></div>
            </div>
        </div>

        <div class="bd-wrap">
            <div class="bd-title">Billing Breakdown</div>
            <div class="bd-row">
                <span class="bd-label">Principal <span style="font-size:11px;">(without interest)</span></span>
                <span class="bd-value"><?= formatMoney($currentBill['amount_due']) ?></span>
            </div>
            <div class="bd-row">
                <span class="bd-label">Interest <span style="font-size:11px;">(3% on loan amount)</span></span>
                <span class="bd-value red">+ <?= formatMoney($currentBill['interest']) ?></span>
            </div>
            <?php if ($currentBill['penalty'] > 0): ?>
            <div class="bd-row">
                <span class="bd-label">Penalty <span style="font-size:11px;">(2% for late payment)</span></span>
                <span class="bd-value red">+ <?= formatMoney($currentBill['penalty']) ?></span>
            </div>
            <?php endif; ?>
            <div class="bill-total-bar">
                <span class="tl">Total Due — <?= date('M d, Y', strtotime($currentBill['due_date'])) ?></span>
                <span class="tv"><?= formatMoney($currentBill['total_due']) ?></span>
            </div>
        </div>

        <div style="padding:16px 20px; border-top:1px solid rgba(0,0,0,.05); display:flex; gap:10px;">
            <a href="<?= APP_URL ?>/user/billing/print.php?id=<?= $currentBill['id'] ?>" target="_blank" class="btn-lending-outline">🖨️ Print Statement</a>
        </div>

        <div class="bill-info-note">
            <i class="bi bi-info-circle-fill"></i>
            Please coordinate with your loan officer to process payment via bank transfer. Your bank details on file will be used for verification.
        </div>
    <?php endif; ?>
</div>

<!-- Billing History -->
<div class="sc">
    <div class="sc-head">
        <h6 class="sc-title"><span class="dot" style="background:#9ca3af;"></span> Billing History</h6>
    </div>

    <?php if (empty($grouped)): ?>
        <div style="padding:48px;text-align:center;color:#9ca3af;">
            <div style="font-size:36px;margin-bottom:10px;">📭</div>
            <div style="font-weight:700;color:#374151;margin-bottom:4px;">No billing records yet</div>
            <div style="font-size:13px;">Your billing history will appear here once a loan is approved.</div>
        </div>
    <?php else: ?>
        <?php foreach ($grouped as $year => $months): ?>
        <?php
            $yearCompleted = array_sum(array_map(fn($ms) => count(array_filter($ms, fn($b) => $b['status']==='Completed')), $months));
            $yearCount     = array_sum(array_map('count', $months));
        ?>
        <div>
            <button class="yr-btn open" onclick="toggleBlock(this,'yr<?= $year ?>')">
                <span>📅 <?= $year ?> <span style="font-size:11px;font-weight:600;color:#9ca3af;margin-left:8px;"><?= $yearCompleted ?>/<?= $yearCount ?> paid</span></span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div id="yr<?= $year ?>">
                <?php foreach ($months as $month => $bills): ?>
                <?php
                    $moOverdue = count(array_filter($bills, fn($b) => $b['status']==='Overdue'));
                ?>
                <div style="border-top:1px solid rgba(0,0,0,.04);">
                    <button class="mo-btn" onclick="toggleBlock(this,'mo<?= $year.preg_replace('/\s/','',$month) ?>')">
                        <span>
                            <?= $month ?>
                            <span class="mo-badge"><?= count($bills) ?> bill<?= count($bills)>1?'s':'' ?></span>
                            <?php if ($moOverdue > 0): ?>
                                <span class="mo-badge red"><?= $moOverdue ?> overdue</span>
                            <?php endif; ?>
                        </span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div id="mo<?= $year.preg_replace('/\s/','',$month) ?>" style="display:none;">
                        <div class="bill-tbl-wrap">
                            <table class="bill-tbl">
                                <thead>
                                    <tr><th>Month #</th><th>Amount Due</th><th>Interest</th><th>Penalty</th><th>Total</th><th>Due Date</th><th>Status</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bills as $b): ?>
                                    <tr style="<?= $b['status']==='Overdue'?'background:#fef2f2;':'' ?>">
                                        <td style="font-weight:700;">Month <?= $b['month_number'] ?></td>
                                        <td><?= formatMoney($b['amount_due']) ?></td>
                                        <td style="color:#dc2626;">+ <?= formatMoney($b['interest']) ?></td>
                                        <td style="color:#dc2626;"><?= $b['penalty']>0?'+ '.formatMoney($b['penalty']):'—' ?></td>
                                        <td style="font-weight:700;"><?= formatMoney($b['total_due']) ?></td>
                                        <td style="color:#6b7280;font-size:12px;"><?= date('M d, Y', strtotime($b['due_date'])) ?></td>
                                        <td>
                                            <?php $bc = match($b['status']){'Completed'=>'completed','Overdue'=>'overdue',default=>'pending'}; ?>
                                            <span class="b-badge <?= $bc ?>"><?= $b['status'] ?></span>
                                        </td>
                                        <td class="bill-tbl-action"><a href="<?= APP_URL ?>/user/billing/print.php?id=<?= $b['id'] ?>" target="_blank" style="color:#1a45a8;text-decoration:none;">🖨️</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function toggleBlock(btn, id) {
    const el   = document.getElementById(id);
    const open = el.style.display !== 'none';
    el.style.display = open ? 'none' : 'block';
    btn.classList.toggle('open', !open);
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>