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

// All billing grouped by loan, then year, then month
$stmt = $pdo->prepare("SELECT b.*, l.applied_amount as loan_amount, l.status as loan_status FROM billing b LEFT JOIN loans l ON b.loan_id = l.id WHERE b.user_id = ? ORDER BY l.id DESC, b.due_date DESC");
$stmt->execute([$userId]);
$allBilling = $stmt->fetchAll();

$grouped = [];
foreach ($allBilling as $b) {
    $loanId = $b['loan_id'];
    $year   = date('Y', strtotime($b['due_date']));
    $month  = date('F', strtotime($b['due_date']));
    $grouped[$loanId][$year][$month][] = $b;
}

$totalBills     = count($allBilling);
$completedBills = count(array_filter($allBilling, fn($b) => $b['status'] === 'Completed'));
$overdueBills   = count(array_filter($allBilling, fn($b) => $b['status'] === 'Overdue'));

$pageTitle = 'My Billing';
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
/* Dashboard-style Billing Page */
.dash-welcome h1{font-size:24px;font-weight:700;color:#0f2557;margin:0 0 4px;}
.dash-welcome h1 span{color:#1a45a8;}
.dash-welcome p{font-size:13px;color:#6b7280;margin:0 0 24px;}
.stat-grid-4{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:22px;}
@media(max-width:860px){.stat-grid-4{grid-template-columns:repeat(2,1fr);}}
.stat-card{background:#fff;border-radius:14px;border:1px solid rgba(0,0,0,.07);padding:18px 18px 16px;box-shadow:0 1px 4px rgba(0,0,0,.05);transition:transform .15s,box-shadow .15s;position:relative;overflow:hidden;}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 6px 18px rgba(0,0,0,.09);}
.stat-card::after{content:'';position:absolute;top:0;left:0;right:0;height:3px;}
.stat-card.c-blue::after{background:#1a45a8;}.stat-card.c-orange::after{background:#ea7c0a;}.stat-card.c-green::after{background:#16a34a;}.stat-card.c-red::after{background:#dc2626;}
.s-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:17px;margin-bottom:10px;}
.c-blue .s-icon{background:#eff4ff;} .c-orange .s-icon{background:#fff7ed;} .c-green .s-icon{background:#f0fdf4;} .c-red .s-icon{background:#fef2f2;}
.s-label{font-size:11px;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;}
.s-amount{font-size:26px;font-weight:700;line-height:1;letter-spacing:-.5px;margin-bottom:3px;}
.c-blue .s-amount{color:#1a45a8;}.c-orange .s-amount{color:#ea7c0a;}.c-green .s-amount{color:#16a34a;}.c-red .s-amount{color:#dc2626;}
.s-sub{font-size:11px;color:#9ca3af;}
.quick-actions{display:flex;flex-wrap:wrap;gap:9px;margin-bottom:22px;}
.qa-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:999px;font-size:13px;font-weight:600;text-decoration:none;border:none;cursor:pointer;font-family:inherit;transition:all .15s;}
.qa-primary{background:#1e3a5f;color:#f5c842;}.qa-primary:hover{background:#2a4a7f;color:#f5c842;}
.qa-outline{background:#f8fafc;color:#1e3a5f;border:1.5px solid #e2e8f0;}.qa-outline:hover{background:#1e3a5f;color:#f5c842;border-color:#1e3a5f;}
.sc{background:#fff;border-radius:14px;border:1px solid rgba(0,0,0,.07);box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;margin-bottom:18px;}
.sc-head{padding:13px 18px;border-bottom:1px solid rgba(0,0,0,.06);display:flex;align-items:center;justify-content:space-between;}
.sc-title{font-size:13px;font-weight:700;color:#1a1f2e;display:flex;align-items:center;gap:7px;margin:0;}
.dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.bill-row{display:flex;justify-content:space-between;align-items:center;padding:9px 18px;border-bottom:1px solid rgba(0,0,0,.04);font-size:13px;}
.bill-row:last-child{border-bottom:none;}.bl{color:#6b7280;}.bv{font-weight:600;color:#1a1f2e;}.bv.danger{color:#dc2626;}.bv.warning{color:#f59e0b;}
.bill-total{background:#0f2557;padding:13px 18px;display:flex;justify-content:space-between;align-items:center;}
.tl{color:rgba(255,255,255,.6);font-size:13px;}.tv{color:#f5c842;font-size:22px;font-weight:700;}
.due-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.due-overdue{background:#fee2e2;color:#dc2626;}.due-urgent{background:#ffedd5;color:#c2410c;}.due-warning{background:#fef3c7;color:#d97706;}.due-normal{background:#e0f2fe;color:#0369a1;}

/* Billing History Accordion */
.yr-btn{width:100%;background:#fff;border-radius:14px;border:1px solid rgba(0,0,0,.07);padding:13px 18px;margin:8px 0;text-align:left;font-size:13px;font-weight:700;color:#1a1f2e;cursor:pointer;display:flex;align-items:center;justify-content:space-between;font-family:inherit;transition:transform .15s,box-shadow .15s;box-shadow:0 1px 4px rgba(0,0,0,.05);}
.yr-btn:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(0,0,0,.09);}
.yr-btn.active-loan{border-left:4px solid #1a45a8;}
.yr-btn.completed-loan{border-left:4px solid #16a34a;}
.yr-btn i{transition:transform .2s;font-size:12px;color:#9ca3af;}
.yr-btn.open i{transform:rotate(180deg);}
.mo-btn{width:100%;background:#f8fafc;border:none;padding:11px 18px 11px 32px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;display:flex;align-items:center;justify-content:space-between;font-family:inherit;border-top:1px solid rgba(0,0,0,.04);}
.mo-btn:hover{background:#f1f5f9;}
.mo-btn i{font-size:11px;color:#9ca3af;transition:transform .2s;}
.mo-btn.open i{transform:rotate(180deg);}
.mo-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:#e8ecf5;color:#374151;margin-left:8px;}
.mo-badge.active{background:#dcfce7;color:#16a34a;}
.mo-badge.completed{background:#f1f5f9;color:#64748b;}
.mo-badge.pending{background:#fef3c7;color:#d97706;}
.mo-badge.red{background:#fee2e2;color:#dc2626;}
.bill-tbl{width:100%;border-collapse:collapse;font-size:13px;}
.bill-tbl-wrap{overflow-x:auto;margin:0 -18px;padding:0 18px;}
.bill-tbl th{padding:9px 14px;text-align:left;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid #f3f4f6;background:#fafbfc;}
.bill-tbl td{padding:11px 14px;border-bottom:1px solid #f3f4f6;color:#111827;}
.bill-tbl tr:last-child td{border-bottom:none;}
.bill-tbl tbody tr:hover{background:#f9fafb;}
.bill-tbl tbody tr.pending{border-left:3px solid #f59e0b;}
.bill-tbl tbody tr.completed{border-left:3px solid #16a34a;}
.bill-tbl tbody tr.overdue{border-left:3px solid #dc2626;}
.b-badge{font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;display:inline-block;}
.b-badge.completed{background:#dcfce7;color:#16a34a;}
.b-badge.pending{background:#fef3c7;color:#d97706;}
.b-badge.overdue{background:#fee2e2;color:#dc2626;}

/* Dark mode */
body.dark-mode .dash-welcome h1{color:#f1f5f9;}
body.dark-mode .dash-welcome h1 span{color:#60a5fa;}
body.dark-mode .dash-welcome p{color:#94a3b8;}
body.dark-mode .stat-card{background:#1e293b;border-color:rgba(255,255,255,.1);}
body.dark-mode .stat-card:hover{box-shadow:0 6px 18px rgba(0,0,0,.4);}
body.dark-mode .c-blue .s-icon{background:#1e3a8a;} body.dark-mode .c-orange .s-icon{background:#92400e;} body.dark-mode .c-green .s-icon{background:#14532d;} body.dark-mode .c-red .s-icon{background:#7f1d1d;}
body.dark-mode .s-label{color:#94a3b8;}
body.dark-mode .c-blue .s-amount{color:#60a5fa;} body.dark-mode .c-orange .s-amount{color:#fb923c;} body.dark-mode .c-green .s-amount{color:#4ade80;} body.dark-mode .c-red .s-amount{color:#f87171;}
body.dark-mode .s-sub{color:#94a3b8;}
body.dark-mode .qa-primary{background:#1e293b;color:#fbbf24;} body.dark-mode .qa-primary:hover{background:#334155;}
body.dark-mode .qa-outline{background:#1e293b;color:#f1f5f9;border-color:rgba(255,255,255,.2);} body.dark-mode .qa-outline:hover{background:#334155;color:#f1f5f9;}
body.dark-mode .sc{background:#1e293b;border-color:rgba(255,255,255,.1);}
body.dark-mode .sc-head{border-color:rgba(255,255,255,.1);}
body.dark-mode .sc-title{color:#f1f5f9;}
body.dark-mode .bill-row{border-color:rgba(255,255,255,.1);}
body.dark-mode .bl{color:#94a3b8;} body.dark-mode .bv{color:#f1f5f9;} body.dark-mode .bv.danger{color:#f87171;} body.dark-mode .bv.warning{color:#fbbf24;}
body.dark-mode .bill-total{background:#0f2557;}
body.dark-mode .tl{color:#94a3b8;} body.dark-mode .tv{color:#fbbf24;}
body.dark-mode .due-overdue{background:#7f1d1d;color:#fca5a5;} body.dark-mode .due-urgent{background:#92400e;color:#fed7aa;} body.dark-mode .due-warning{background:#92400e;color:#fef3c7;} body.dark-mode .due-normal{background:#1e3a8a;color:#93c5fd;}
body.dark-mode .yr-btn{background:#1e293b;border-color:rgba(255,255,255,.1);color:#f1f5f9;}
body.dark-mode .yr-btn:hover{background:#334155;}
body.dark-mode .mo-btn{background:#0f172a;color:#94a3b8;}
body.dark-mode .mo-btn:hover{background:#1e293b;}
body.dark-mode .mo-badge{background:#374151;color:#e2e8f0;}
body.dark-mode .mo-badge.active{background:#14532d;color:#4ade80;}
body.dark-mode .mo-badge.completed{background:#334155;color:#94a3b8;}
body.dark-mode .mo-badge.pending{background:#92400e;color:#fbbf24;}
body.dark-mode .mo-badge.red{background:#7f1d1d;color:#fca5a5;}
body.dark-mode .bill-tbl th{background:#334155;color:#94a3b8;border-color:rgba(255,255,255,.1);}
body.dark-mode .bill-tbl td{color:#e2e8f0;border-color:rgba(255,255,255,.1);}
body.dark-mode .bill-tbl tbody tr:hover{background:#475569;}
body.dark-mode .b-badge.completed{background:#14532d;color:#4ade80;}
body.dark-mode .b-badge.pending{background:#92400e;color:#fbbf24;}
body.dark-mode .b-badge.overdue{background:#7f1d1d;color:#fca5a5;}
</style>

<!-- Dashboard-style Welcome Banner -->
<div class="dash-welcome">
    <h1>My <span>Billing</span></h1>
    <p>
        <?php if ($currentBill): ?>
            <span style="background:#f5c842;color:#0f2557;font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px;margin-right:6px;">Current Due</span>
            Current Due: <?= formatMoney($currentBill['total_due']) ?> · Due <?= date('M d, Y', strtotime($currentBill['due_date'])) ?> · 
            <?php if ($daysUntilDue < 0): ?>
                <span style="color:#dc2626;font-weight:600;">Overdue</span>
            <?php else: ?>
                <span style="color:#16a34a;font-weight:600;"><?= $daysUntilDue ?> days left</span>
            <?php endif; ?>
        <?php else: ?>
            <span style="background:#16a34a;color:white;font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px;margin-right:6px;">Clear</span>
            No outstanding bills - You're all caught up!
        <?php endif; ?>
    </p>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <?php if ($loan): ?>
        <a href="<?= APP_URL ?>/user/savings/index.php" class="qa-btn qa-outline">🏦 Manage Savings</a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/user/billing/index.php" class="qa-btn qa-primary">🧾 View Billing</a>
    <a href="<?= APP_URL ?>/user/loan/index.php" class="qa-btn qa-outline">📋 Loan History</a>
    <a href="<?= APP_URL ?>/user/profile.php" class="qa-btn qa-outline">👤 Profile</a>
</div>

<!-- Stat Cards -->
<div class="stat-grid-4">
    <div class="stat-card c-blue">
        <div class="s-icon">📋</div>
        <div class="s-label">Total Bills</div>
        <div class="s-amount"><?= $totalBills ?></div>
        <div class="s-sub">All time</div>
    </div>
    <div class="stat-card c-green">
        <div class="s-icon">✓</div>
        <div class="s-label">Paid</div>
        <div class="s-amount"><?= $completedBills ?></div>
        <div class="s-sub">Completed payments</div>
    </div>
    <div class="stat-card c-red">
        <div class="s-icon">⚠</div>
        <div class="s-label">Overdue</div>
        <div class="s-amount"><?= $overdueBills ?></div>
        <div class="s-sub">Need attention</div>
    </div>
</div>

<?php if ($currentBill): ?>
<!-- Current Bill Card - Dashboard Style -->
<div class="sc">
    <div class="sc-head">
        <h6 class="sc-title"><span class="dot" style="background:#ea7c0a;"></span> Current Billing Statement</h6>
        <span class="due-badge due-<?= $dueSeverity ?>">
            <?= $dueSeverity==='overdue'?'⚠ Overdue':($dueSeverity==='urgent'?'⚡ Urgent':($dueSeverity==='warning'?'⏰ Upcoming':'✓ On Track')) ?>
        </span>
    </div>
    <?php if ($dueSeverity==='overdue'): ?>
    <div style="background:#fef2f2;border-left:4px solid #dc2626;padding:10px 18px;font-size:13px;color:#b91c1c;display:flex;gap:8px;align-items:center;">
        ⚠️ <strong>Overdue!</strong> A 2% penalty has been applied.
    </div>
    <?php endif; ?>
    <div class="bill-row"><span class="bl">Month</span><span class="bv">Month <?= $currentBill['month_number'] ?></span></div>
    <div class="bill-row"><span class="bl">Principal</span><span class="bv"><?= formatMoney($currentBill['amount_due']) ?></span></div>
    <div class="bill-row"><span class="bl">Interest (3%)</span><span class="bv <?= $currentBill['interest']>0?'warning':'' ?>"><?= $currentBill['interest']>0?formatMoney($currentBill['interest']):'—' ?></span></div>
    <div class="bill-row"><span class="bl">Penalty (2%)</span><span class="bv <?= $currentBill['penalty']>0?'danger':'' ?>"><?= $currentBill['penalty']>0?formatMoney($currentBill['penalty']):'—' ?></span></div>
    <div class="bill-total"><span class="tl">Due <?= date('F d, Y', strtotime($currentBill['due_date'])) ?></span><span class="tv"><?= formatMoney($currentBill['total_due']) ?></span></div>
</div>
<?php endif; ?>

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
        <?php foreach ($grouped as $loanId => $years): ?>
            <?php 
                // Get loan info from first bill
                $yearKeys = array_keys($years);
                $firstYearKey = end($yearKeys);
                $monthKeys = array_keys($years[$firstYearKey]);
                $firstMonthKey = end($monthKeys);
                $firstBill = $years[$firstYearKey][$firstMonthKey][0];
                $loanAmount = $firstBill['loan_amount'];
                $loanStatus = $firstBill['loan_status'];
                
                // Count totals for this loan
                $loanTotalBills = 0;
                $loanCompletedBills = 0;
                foreach ($years as $months) {
                    foreach ($months as $bills) {
                        $loanTotalBills += count($bills);
                        $loanCompletedBills += count(array_filter($bills, fn($b) => $b['status']==='Completed'));
                    }
                }
            ?>
            <div>
                <button class="yr-btn open" onclick="toggleBlock(this,'loan<?= $loanId ?>')">
                    <span>
                        💰 Loan #<?= $loanId ?> — <?= formatMoney((float)($loanAmount ?? 0)) ?> 
                        <span class="mo-badge <?= $loanStatus === 'Active' ? '' : ($loanStatus === 'Completed' ? 'completed' : 'pending') ?>">
                            <?= $loanStatus ?>
                        </span>
                        <span style="font-size:11px;font-weight:600;color:#9ca3af;margin-left:8px;"><?= $loanCompletedBills ?>/<?= $loanTotalBills ?> paid</span>
                    </span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div id="loan<?= $loanId ?>">
                    <?php foreach ($years as $year => $months): ?>
                    <?php
                        $yearCompleted = array_sum(array_map(fn($ms) => count(array_filter($ms, fn($b) => $b['status']==='Completed')), $months));
                        $yearCount     = array_sum(array_map('count', $months));
                    ?>
                    <div style="border-top:1px solid rgba(0,0,0,.04);">
                        <button class="mo-btn" onclick="toggleBlock(this,'yr<?= $loanId ?>_<?= $year ?>')">
                            <span>
                                📅 <?= $year ?>
                                <span class="mo-badge"><?= $yearCount ?> bill<?= $yearCount>1?'s':'' ?></span>
                                <?php 
                                    $yearOverdue = array_sum(array_map(fn($ms) => count(array_filter($ms, fn($b) => $b['status']==='Overdue')), $months));
                                    if ($yearOverdue > 0): 
                                ?>
                                    <span class="mo-badge red"><?= $yearOverdue ?> overdue</span>
                                <?php endif; ?>
                            </span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div id="yr<?= $loanId ?>_<?= $year ?>" style="display:none;">
                            <?php foreach ($months as $month => $bills): ?>
                            <?php
                                $moOverdue = count(array_filter($bills, fn($b) => $b['status']==='Overdue'));
                            ?>
                            <div style="border-top:1px solid rgba(0,0,0,.04);">
                                <button class="mo-btn" onclick="toggleBlock(this,'mo<?= $loanId ?>_<?= $year ?>_<?= preg_replace('/\s/','',$month) ?>')">
                                    <span>
                                        <?= $month ?>
                                        <span class="mo-badge"><?= count($bills) ?> bill<?= count($bills)>1?'s':'' ?></span>
                                        <?php if ($moOverdue > 0): ?>
                                            <span class="mo-badge red"><?= $moOverdue ?> overdue</span>
                                        <?php endif; ?>
                                    </span>
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                                <div id="mo<?= $loanId ?>_<?= $year ?>_<?= preg_replace('/\s/','',$month) ?>" style="display:none;">
                                    <div class="bill-tbl-wrap">
                                        <table class="bill-tbl">
                                            <thead>
                                                <tr><th>Month #</th><th>Amount Due</th><th>Interest</th><th>Penalty</th><th>Total</th><th>Due Date</th><th>Status</th><th>Action</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($bills as $b): ?>
                                                <tr class="<?= $b['status'] === 'Completed' ? 'completed' : ($b['status'] === 'Pending' ? 'pending' : 'overdue') ?>">
                                                    <td style="font-weight:700;">Month <?= $b['month_number'] ?></td>
                                                    <td><?= formatMoney($b['amount_due']) ?></td>
                                                    <td style="color:#374151;">+ <?= formatMoney($b['interest']) ?></td>
                                                    <td style="color:#374151;"><?= $b['penalty']>0?'+ '.formatMoney($b['penalty']):'₱0.00' ?></td>
                                                    <td style="font-weight:700;"><?= formatMoney($b['total_due']) ?></td>
                                                    <td style="color:#6b7280;font-size:12px;"><?= date('M d, Y', strtotime($b['due_date'])) ?></td>
                                                    <td>
                                                        <?php $bc = match($b['status']){'Completed'=>'completed','Overdue'=>'overdue',default=>'pending'}; ?>
                                                        <span class="b-badge <?= $bc ?>"><?= $b['status'] ?></span>
                                                    </td>
                                                    <td class="bill-tbl-action"><a href="<?= APP_URL ?>/user/billing/print.php?id=<?= $b['id'] ?>" target="_blank" class="print-btn">🖨️</a></td>
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