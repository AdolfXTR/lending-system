<?php
// ============================================================
//  user/loan/index.php
// ============================================================
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../helpers.php';

$userId = $_SESSION['user_id'];

// Get active/pending loan
$stmt = $pdo->prepare("SELECT * FROM loans WHERE user_id = ? AND status IN ('Pending','Approved','Active') ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$userId]);
$activeLoan = $stmt->fetch();

// Get all loans history
$stmt = $pdo->prepare("SELECT * FROM loans WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$allLoans = $stmt->fetchAll();

// Get loan transactions
$stmt = $pdo->prepare("SELECT * FROM loan_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$userId]);
$loanTxns = $stmt->fetchAll();

$pageTitle = 'My Loan';
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
/* Premium Loan Page Styles - Consistent with Billing Page */

/* 1. PAGE HEADER */
.page-header { margin-bottom:24px; }
.page-title { font-size:22px; font-weight:700; color:#0f2557; margin:0 0 4px; display:flex; align-items:center; gap:8px; }
body.dark-mode .page-title { color:#f1f5f9 !important; }
.page-subtitle { font-size:13px; color:#9ca3af; margin:0; }
body.dark-mode .page-subtitle { color:#94a3b8 !important; }

/* 2. CARDS */
.sc { background:#fff; border-radius:14px; border:1px solid rgba(0,0,0,.08); box-shadow:0 2px 8px rgba(0,0,0,.06); overflow:hidden; margin-bottom:24px; }
body.dark-mode .sc { background:#1e293b !important; border-color:rgba(255,255,255,.1) !important; }
.sc.current-loan { border-top:3px solid #3b82f6; background:linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%); }
body.dark-mode .sc.current-loan { background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important; border-top-color:#60a5fa !important; }

.sc-head { padding:16px 24px; border-bottom:1px solid rgba(0,0,0,.06); display:flex; align-items:center; justify-content:space-between; }
body.dark-mode .sc-head { border-color:rgba(255,255,255,.1) !important; }
.sc-title { font-size:14px; font-weight:700; color:#0f2557; display:flex; align-items:center; gap:8px; margin:0; }
body.dark-mode .sc-title { color:#f1f5f9 !important; }

/* 3. LOAN META ROW */
.loan-meta-row { display:grid; grid-template-columns:repeat(4,1fr); padding:24px; border-bottom:1px solid rgba(0,0,0,.05); }
body.dark-mode .loan-meta-row { border-color:rgba(255,255,255,.1) !important; }
.loan-meta-item { text-align:center; padding:0 16px; position:relative; }
.loan-meta-item + .loan-meta-item::before { content:''; position:absolute; left:0; top:20%; bottom:20%; width:1px; background:rgba(0,0,0,.08); }
body.dark-mode .loan-meta-item + .loan-meta-item::before { background:rgba(255,255,255,.1); }
.lml { font-size:11px; color:#9ca3af; font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin-bottom:8px; }
body.dark-mode .lml { color:#94a3b8 !important; }
.lmv { font-size:20px; font-weight:800; color:#1a1f2e; }
body.dark-mode .lmv { color:#f1f5f9 !important; }
.lmv.blue{color:#3b82f6;} .lmv.green{color:#10b981;}
body.dark-mode .lmv.blue{color:#60a5fa !important;} body.dark-mode .lmv.green{color:#4ade80 !important;}
.lmv-subtitle { font-size:11px; color:#9ca3af; margin-top:4px; }
body.dark-mode .lmv-subtitle { color:#94a3b8 !important; }

/* 4. STATUS BADGES */
.status-badge { display:inline-flex; align-items:center; gap:4px; padding:4px 12px; border-radius:16px; font-size:11px; font-weight:700; }
.status-badge.active { background:#dcfce7; color:#16a34a; }
.status-badge.pending { background:#fef3c7; color:#d97706; }
.status-badge.approved { background:#dbeafe; color:#2563eb; }
body.dark-mode .status-badge.active { background:#14532d !important; color:#4ade80 !important; }
body.dark-mode .status-badge.pending { background:#92400e !important; color:#fef3c7 !important; }
body.dark-mode .status-badge.approved { background:#1e3a8a !important; color:#93c5fd !important; }

/* Pulsing dot for active status */
.pulsing-dot { width:6px; height:6px; background:#16a34a; border-radius:50%; display:inline-block; animation:pulse 2s infinite; }
body.dark-mode .pulsing-dot { background:#4ade80; }
@keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.5; } }

/* 5. APPROVAL NOTICE BANNER */
.approval-notice { background:#f0fdf4; border-left:4px solid #10b981; padding:16px 20px; font-size:13px; color:#166534; display:flex; align-items:center; gap:12px; margin:16px; border-radius:0 8px 8px 0; }
body.dark-mode .approval-notice { background:#14532d !important; border-color:#4ade80 !important; color:#4ade80 !important; }
.approval-notice i { font-size:16px; color:#10b981; flex-shrink:0; }
body.dark-mode .approval-notice i { color:#4ade80 !important; }

.pending-notice { background:#fef3c7; border-left:4px solid #f59e0b; padding:16px 20px; font-size:13px; color:#92400e; display:flex; align-items:center; gap:12px; margin:16px; border-radius:0 8px 8px 0; }
body.dark-mode .pending-notice { background:#92400e !important; border-color:#fbbf24 !important; color:#fef3c7 !important; }
.pending-notice i { font-size:16px; color:#f59e0b; flex-shrink:0; }
body.dark-mode .pending-notice i { color:#fbbf24 !important; }

/* 6. TABLES */
.loan-tbl { width:100%; border-collapse:collapse; font-size:13px; }
.loan-tbl-wrap { overflow-x:auto; margin:0 -24px; padding:0 24px; }
body.dark-mode .loan-tbl-wrap { background:#1e293b !important; }
.loan-tbl th { padding:10px 12px; text-align:left; font-size:11px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.04em; background:#fafbfc; border-bottom:1px solid rgba(0,0,0,.05); white-space:nowrap; }
body.dark-mode .loan-tbl th { background:#334155 !important; color:#94a3b8 !important; border-color:rgba(255,255,255,.1) !important; }
.loan-tbl td { padding:12px; border-bottom:1px solid rgba(0,0,0,.04); color:#111827; font-weight:500; }
body.dark-mode .loan-tbl td { color:#e2e8f0 !important; border-color:rgba(255,255,255,.1) !important; }
.loan-tbl tr:last-child td { border-bottom:none; }

/* Alternating rows */
.loan-tbl tbody tr:nth-child(even) { background:#fafbfc; }
body.dark-mode .loan-tbl tbody tr:nth-child(even) { background:#0d1526 !important; }
.loan-tbl tbody tr:hover { background:#f1f5f9; }
body.dark-mode .loan-tbl tbody tr:hover { background:#475569 !important; }

/* Transaction ID styling */
.txn-id { font-family:'Courier New', monospace; font-size:12px; color:#6b7280; }
body.dark-mode .txn-id { color:#9ca3af !important; }

/* Type badges */
.type-badge { display:inline-block; font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; }
.type-badge.loan-app{background:#dbeafe;color:#2563eb;} .type-badge.approval{background:#dcfce7;color:#16a34a;} .type-badge.limit-inc{background:#e9d5ff;color:#9333ea;} .type-badge.rejected{background:#fee2e2;color:#dc2626;}
body.dark-mode .type-badge.loan-app{background:#1e3a8a !important; color:#93c5fd !important;} body.dark-mode .type-badge.approval{background:#14532d !important; color:#4ade80 !important;} body.dark-mode .type-badge.limit-inc{background:#581c87 !important; color:#d8b4fe !important;} body.dark-mode .type-badge.rejected{background:#7f1d1d !important; color:#fca5a5 !important;}

/* Status badges for tables */
.table-status-badge { display:inline-block; font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; }
.table-status-badge.approved{background:#dcfce7;color:#16a34a;} .table-status-badge.active{background:#dcfce7;color:#16a34a;} .table-status-badge.completed{background:#f3f4f6;color:#374151;} .table-status-badge.pending{background:#fef3c7;color:#d97706;} .table-status-badge.rejected{background:#fee2e2;color:#dc2626;}
body.dark-mode .table-status-badge.approved{background:#14532d !important; color:#4ade80 !important;} body.dark-mode .table-status-badge.active{background:#14532d !important; color:#4ade80 !important;} body.dark-mode .table-status-badge.completed{background:#374151 !important; color:#d1d5db !important;} body.dark-mode .table-status-badge.pending{background:#92400e !important; color:#fef3c7 !important;} body.dark-mode .table-status-badge.rejected{background:#7f1d1d !important; color:#fca5a5 !important;}

/* Amount styling */
.amount { font-weight:700; color:#111827; }
body.dark-mode .amount { color:#e2e8f0 !important; }

/* View button */
.view-btn { background:#3b82f6; color:#fff; border:none; padding:6px 12px; font-size:11px; font-weight:600; border-radius:6px; cursor:pointer; transition:all 0.2s ease; text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
.view-btn:hover { background:#2563eb; transform:translateY(-1px); box-shadow:0 2px 8px rgba(59,130,246,0.3); color:#fff; }

/* No loan state */
.no-loan { padding:48px; text-align:center; }
.no-loan-icon { font-size:64px; margin-bottom:16px; }
.no-loan-title { font-size:20px; font-weight:700; color:#374151; margin-bottom:8px; }
body.dark-mode .no-loan-title { color:#f1f5f9 !important; }
.no-loan-text { font-size:14px; color:#6b7280; line-height:1.5; margin-bottom:20px; }
body.dark-mode .no-loan-text { color:#94a3b8 !important; }
.apply-btn { background:#3b82f6; color:#fff; border:none; padding:12px 24px; font-size:14px; font-weight:600; border-radius:8px; cursor:pointer; transition:all 0.2s ease; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
.apply-btn:hover { background:#2563eb; transform:translateY(-1px); box-shadow:0 4px 12px rgba(59,130,246,0.3); color:#fff; }
</style>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="bi bi-cash-coin" style="color:#3b82f6;"></i> My Loan
    </h1>
    <p class="page-subtitle">Manage your active loan and view transaction history</p>
</div>

<?= showFlash() ?>

<!-- Current Loan Card -->
<?php if ($activeLoan): ?>
<div class="sc current-loan">
    <div class="sc-head">
        <h6 class="sc-title"><span class="dot" style="background:#3b82f6;"></span> Current Loan</h6>
    </div>

    <div class="loan-meta-row">
        <div class="loan-meta-item">
            <div class="lml">Loan Amount</div>
            <div class="lmv blue"><?= formatMoney($activeLoan['applied_amount']) ?></div>
        </div>
        <div class="loan-meta-item">
            <div class="lml">Amount Received</div>
            <div class="lmv green"><?= $activeLoan['received_amount'] ? formatMoney($activeLoan['received_amount']) : '—' ?></div>
            <?php if ($activeLoan['received_amount']): ?>
                <div class="lmv-subtitle">After 3% interest deduction</div>
            <?php endif; ?>
        </div>
        <div class="loan-meta-item">
            <div class="lml">Term</div>
            <div class="lmv"><?= $activeLoan['term_months'] ?> month<?= $activeLoan['term_months'] > 1 ? 's' : '' ?></div>
        </div>
        <div class="loan-meta-item">
            <div class="lml">Status</div>
            <div class="lmv">
                <?php if ($activeLoan['status'] === 'Active'): ?>
                    <span class="status-badge active">
                        <span class="pulsing-dot"></span> <?= $activeLoan['status'] ?>
                    </span>
                <?php elseif ($activeLoan['status'] === 'Pending'): ?>
                    <span class="status-badge pending">
                        <i class="bi bi-clock-fill"></i> <?= $activeLoan['status'] ?>
                    </span>
                <?php elseif ($activeLoan['status'] === 'Approved'): ?>
                    <span class="status-badge approved">
                        <i class="bi bi-check-circle-fill"></i> <?= $activeLoan['status'] ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($activeLoan['status'] === 'Pending'): ?>
    <div class="pending-notice">
        <i class="bi bi-hourglass-split"></i>
        <span>Your loan application is being reviewed by admin. You will be notified once it's approved.</span>
    </div>
    <?php elseif ($activeLoan['status'] === 'Approved' || $activeLoan['status'] === 'Active'): ?>
    <div class="approval-notice">
        <i class="bi bi-check-circle-fill"></i>
        <span>Your loan has been approved! Check your billing for payment schedule.</span>
    </div>
    <?php endif; ?>

    <?php if (!empty($activeLoan['rejection_reason'])): ?>
    <div class="pending-notice">
        <i class="bi bi-x-circle-fill"></i>
        <span><strong>Rejection Reason:</strong> <?= clean($activeLoan['rejection_reason']) ?></span>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="sc">
    <div class="no-loan">
        <div class="no-loan-icon">💳</div>
        <div class="no-loan-title">No active loan</div>
        <div class="no-loan-text">You don't have any active loans at the moment.<br>Apply for a loan to get started with your financial needs!</div>
        <a href="apply.php" class="apply-btn">
            <i class="bi bi-plus-circle"></i> Apply for Loan
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Loan Transactions -->
<?php if (!empty($loanTxns)): ?>
<div class="sc">
    <div class="sc-head">
        <h6 class="sc-title"><span class="dot" style="background:#3b82f6;"></span> Loan Transactions</h6>
    </div>
    <div class="loan-tbl-wrap">
        <table class="loan-tbl">
            <thead>
                <tr><th>#</th><th>Transaction ID</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php foreach ($loanTxns as $i => $t): ?>
                <?php
                    // Check if this is a loan application and the loan has been approved
                    $displayStatus = $t['status'];
                    if (strtolower($t['type']) === 'loan application' && $activeLoan && $activeLoan['status'] === 'Active') {
                        $displayStatus = 'Approved';
                    }
                    
                    // Type badge class
                    $typeClass = match(strtolower($t['type'])) {
                        'loan application' => 'loan-app',
                        'approval' => 'approval',
                        'limit increase' => 'limit-inc',
                        'application' => 'rejected',
                        default => 'loan-app'
                    };
                    
                    // Status badge class
                    $statusClass = match(strtolower($displayStatus)) {
                        'approved' => 'approved',
                        'active' => 'active',
                        'completed' => 'completed',
                        'pending' => 'pending',
                        'rejected' => 'rejected',
                        default => 'pending'
                    };
                ?>
                <tr>
                    <td><?= $t['no'] ?: $i+1 ?></td>
                    <td class="txn-id"><?= clean($t['transaction_id']) ?></td>
                    <td><span class="type-badge <?= $typeClass ?>"><?= clean($t['type']) ?></span></td>
                    <td class="amount"><?= formatMoney($t['amount']) ?></td>
                    <td><span class="table-status-badge <?= $statusClass ?>"><?= $displayStatus ?></span></td>
                    <td class="small" style="color:#6b7280;"><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Loan History -->
<?php if (count($allLoans) > 1): ?>
<div class="sc">
    <div class="sc-head">
        <h6 class="sc-title"><span class="dot" style="background:#9ca3af;"></span> Loan History</h6>
    </div>
    <div class="loan-tbl-wrap">
        <table class="loan-tbl">
            <thead>
                <tr><th>#</th><th>Amount</th><th>Term</th><th>Status</th><th>Date Applied</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($allLoans as $i => $l): ?>
                <?php
                    $statusClass = match(strtolower($l['status'])) {
                        'active' => 'active',
                        'completed' => 'completed',
                        'pending' => 'pending',
                        'approved' => 'approved',
                        'rejected' => 'rejected',
                        default => 'pending'
                    };
                ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td class="amount"><?= formatMoney($l['applied_amount']) ?></td>
                    <td><?= $l['term_months'] ?> mo</td>
                    <td>
                        <span class="table-status-badge <?= $statusClass ?>">
                            <?php if ($l['status'] === 'Active'): ?>
                                <span class="pulsing-dot"></span> 
                            <?php endif; ?>
                            <?= $l['status'] ?>
                        </span>
                    </td>
                    <td class="small" style="color:#6b7280;"><?= date('M d, Y', strtotime($l['created_at'])) ?></td>
                    <td>
                        <a href="view.php?id=<?= $l['id'] ?>" class="view-btn">
                            <i class="bi bi-eye"></i> View
                        </a>
                        <?php if ($l['status'] === 'Rejected' && !empty($l['rejection_reason'])): ?>
                            <button class="btn btn-sm btn-link p-0 ms-2" data-bs-toggle="popover" data-bs-content="<?= htmlspecialchars($l['rejection_reason']) ?>" title="Rejection Reason" onclick="new bootstrap.Popover(this)" style="color:#6b7280;">
                                <i class="bi bi-info-circle"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>