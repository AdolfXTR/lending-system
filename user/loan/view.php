<?php
// ============================================================
//  user/loan/view.php
// ============================================================
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../helpers.php';

$userId = $_SESSION['user_id'];

// Get loan ID from URL parameter
$loanId = $_GET['id'] ?? null;
if (!$loanId) {
    header('Location: ' . APP_URL . '/user/loan/index.php');
    exit;
}

// Get loan details (ensure it belongs to current user)
$stmt = $pdo->prepare("SELECT * FROM loans WHERE id = ? AND user_id = ?");
$stmt->execute([$loanId, $userId]);
$loan = $stmt->fetch();

if (!$loan) {
    // Loan not found or doesn't belong to user
    header('Location: ' . APP_URL . '/user/loan/index.php');
    exit;
}

// Get billing schedule for this loan
$stmt = $pdo->prepare("SELECT * FROM billing WHERE loan_id = ? ORDER BY month_number ASC");
$stmt->execute([$loanId]);
$billingSchedule = $stmt->fetchAll();

// Get loan transactions for this specific loan
$stmt = $pdo->prepare("SELECT * FROM loan_transactions WHERE loan_id = ? ORDER BY created_at DESC");
$stmt->execute([$loanId]);
$loanTransactions = $stmt->fetchAll();

// Calculate loan statistics
$totalBills = count($billingSchedule);
$completedBills = count(array_filter($billingSchedule, fn($b) => $b['status'] === 'Completed'));
$pendingBills = count(array_filter($billingSchedule, fn($b) => $b['status'] === 'Pending'));
$overdueBills = count(array_filter($billingSchedule, fn($b) => $b['status'] === 'Overdue'));

$completedPayments = array_filter($billingSchedule, fn($b) => $b['status'] === 'Completed');
$totalPaid = array_sum(array_map(fn($b) => $b['total_due'], $completedPayments));
$totalDue = array_sum(array_map(fn($b) => $b['total_due'], $billingSchedule));

// Check if any transactions have descriptions
$hasDescriptions = !empty($loanTransactions) && !empty(array_filter($loanTransactions, fn($t) => !empty($t['description'])));

$pageTitle = 'Loan Details #' . $loan['id'];
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
/* Premium Loan View Page Styles */

/* 1. PAGE HEADER */
.page-header { margin-bottom:24px; }
.page-title { font-size:22px; font-weight:700; color:#0f2557; margin:0 0 4px; display:flex; align-items:center; gap:8px; }
body.dark-mode .page-title { color:#f1f5f9 !important; }
.page-subtitle { font-size:13px; color:#9ca3af; margin:0; }
body.dark-mode .page-subtitle { color:#94a3b8 !important; }

/* 2. CARDS */
.sc { background:#fff; border-radius:14px; border:1px solid rgba(0,0,0,.08); box-shadow:0 2px 8px rgba(0,0,0,.06); overflow:hidden; margin-bottom:24px; }
body.dark-mode .sc { background:#1e293b !important; border-color:rgba(255,255,255,.1) !important; }
.sc.loan-details { border-top:3px solid #3b82f6; background:linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%); }
body.dark-mode .sc.loan-details { background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important; border-top-color:#60a5fa !important; }

.sc-head { padding:16px 24px; border-bottom:1px solid rgba(0,0,0,.06); display:flex; align-items:center; justify-content:space-between; }
body.dark-mode .sc-head { border-color:rgba(255,255,255,.1) !important; }
.sc-title { font-size:14px; font-weight:700; color:#0f2557; display:flex; align-items:center; gap:8px; margin:0; }
body.dark-mode .sc-title { color:#f1f5f9 !important; }

/* 3. LOAN META ROW */
.loan-meta-row { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; padding:24px; }
.loan-meta-item { text-align:center; }
.loan-meta-label { font-size:11px; color:#9ca3af; font-weight:600; text-transform:uppercase; letter-spacing:.05em; margin-bottom:8px; }
body.dark-mode .loan-meta-label { color:#94a3b8 !important; }
.loan-meta-value { font-size:18px; font-weight:700; color:#1a1f2e; }
body.dark-mode .loan-meta-value { color:#f1f5f9 !important; }
.loan-meta-value.amount { font-size:24px; color:#3b82f6; }
body.dark-mode .loan-meta-value.amount { color:#60a5fa !important; }

/* 4. STATUS BADGES */
.status-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 16px; border-radius:20px; font-size:12px; font-weight:700; }
.status-badge.active { background:#dcfce7; color:#16a34a; }
.status-badge.pending { background:#fef3c7; color:#d97706; }
.status-badge.approved { background:#dbeafe; color:#2563eb; }
.status-badge.completed { background:#e0e7ff; color:#4f46e5; }
.status-badge.rejected { background:#fee2e2; color:#dc2626; }
body.dark-mode .status-badge.active { background:#14532d !important; color:#4ade80 !important; }
body.dark-mode .status-badge.pending { background:#92400e !important; color:#fbbf24 !important; }
body.dark-mode .status-badge.approved { background:#1e3a8a !important; color:#60a5fa !important; }
body.dark-mode .status-badge.completed { background:#312e81 !important; color:#818cf8 !important; }
body.dark-mode .status-badge.rejected { background:#7f1d1d !important; color:#f87171 !important; }

/* 5. TABLES */
.data-table { width:100%; border-collapse:collapse; font-size:13px; }
.data-table th { padding:12px 16px; text-align:left; font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; background:#fafbfc; border-bottom:1px solid #f3f4f6; }
body.dark-mode .data-table th { background:#334155 !important; color:#94a3b8 !important; border-color:rgba(255,255,255,.1) !important; }
.data-table td { padding:12px 16px; border-bottom:1px solid #f3f4f6; color:#111827; }
body.dark-mode .data-table td { color:#e2e8f0 !important; border-color:rgba(255,255,255,.1) !important; }
.data-table tr:last-child td { border-bottom:none; }
.data-table tbody tr:hover { background:#f9fafb; }
body.dark-mode .data-table tbody tr:hover { background:#475569 !important; }

/* 6. BILLING STATUS BADGES */
.bill-status { display:inline-block; font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; }
.bill-status.completed { background:#dcfce7; color:#16a34a; }
.bill-status.pending { background:#fef3c7; color:#d97706; }
.bill-status.overdue { background:#fee2e2; color:#dc2626; }
.bill-status.approved { background:#dcfce7; color:#16a34a; }
.bill-status.rejected { background:#fee2e2; color:#dc2626; }
body.dark-mode .bill-status.completed { background:#14532d !important; color:#4ade80 !important; }
body.dark-mode .bill-status.pending { background:#92400e !important; color:#fbbf24 !important; }
body.dark-mode .bill-status.overdue { background:#7f1d1d !important; color:#f87171 !important; }
body.dark-mode .bill-status.approved { background:#14532d !important; color:#4ade80 !important; }
body.dark-mode .bill-status.rejected { background:#7f1d1d !important; color:#f87171 !important; }

/* 7. TRANSACTION TYPE BADGES */
.txn-type { display:inline-block; font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; }
.txn-type.application { background:#e0e7ff; color:#4f46e5; }
.txn-type.payment { background:#dcfce7; color:#16a34a; }
.txn-type.penalty { background:#fee2e2; color:#dc2626; }
body.dark-mode .txn-type.application { background:#312e81 !important; color:#818cf8 !important; }
body.dark-mode .txn-type.payment { background:#14532d !important; color:#4ade80 !important; }
body.dark-mode .txn-type.penalty { background:#7f1d1d !important; color:#f87171 !important; }

/* 8. EMPTY STATE */
.empty-state { padding:48px; text-align:center; color:#9ca3af; }
.empty-state-icon { font-size:48px; margin-bottom:16px; }
.empty-state-title { font-size:16px; font-weight:700; color:#374151; margin-bottom:8px; }
body.dark-mode .empty-state-title { color:#f1f5f9 !important; }
.empty-state-text { font-size:13px; }

/* 9. BACK BUTTON */
.back-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#f8fafc; color:#64748b; text-decoration:none; border-radius:8px; font-size:13px; font-weight:600; margin-bottom:16px; transition:all 0.2s ease; }
.back-btn:hover { background:#e2e8f0; color:#475569; }
body.dark-mode .back-btn { background:#374151 !important; color:#d1d5db !important; }
body.dark-mode .back-btn:hover { background:#4b5563 !important; color:#e5e7eb !important; }

/* 10. PROGRESS BAR */
.progress-bar { height:8px; background:#e5e7eb; border-radius:20px; overflow:hidden; margin:8px 0; }
body.dark-mode .progress-bar { background:#374151 !important; }
.progress-fill { height:100%; background:#3b82f6; border-radius:20px; transition:width 0.3s ease; }
body.dark-mode .progress-fill { background:#60a5fa !important; }

/* 11. DARK MODE ADDITIONS */
body.dark-mode .data-table td[style*="color:#f59e0b"] { color:#fbbf24 !important; }
body.dark-mode tr[style*="border-left: 3px solid #f59e0b"] { border-left-color: #fbbf24 !important; }
</style>

<!-- Back Button -->
<a href="<?= APP_URL ?>/user/loan/index.php" class="back-btn">
    <i class="bi bi-arrow-left"></i> Back to Loan History
</a>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="bi bi-file-text"></i> Loan Details #<?= $loan['id'] ?>
    </h1>
    <p class="page-subtitle">View complete information about this loan</p>
</div>

<!-- Loan Details Card -->
<div class="sc loan-details">
    <div class="sc-head">
        <h6 class="sc-title">
            <i class="bi bi-info-circle"></i> Loan Information
        </h6>
        <span class="status-badge <?= strtolower($loan['status']) ?>">
            <?= $loan['status'] ?>
        </span>
    </div>
    
    <div class="loan-meta-row">
        <div class="loan-meta-item">
            <div class="loan-meta-label">Loan Amount</div>
            <div class="loan-meta-value amount"><?= formatMoney($loan['applied_amount']) ?></div>
        </div>
        <div class="loan-meta-item">
            <div class="loan-meta-label">Amount Received</div>
            <div class="loan-meta-value"><?= formatMoney($loan['received_amount']) ?></div>
        </div>
        <div class="loan-meta-item">
            <div class="loan-meta-label">Term</div>
            <div class="loan-meta-value"><?= $loan['term_months'] ?> month<?= $loan['term_months'] > 1 ? 's' : '' ?></div>
        </div>
        <div class="loan-meta-item">
            <div class="loan-meta-label">Monthly Payment</div>
            <div class="loan-meta-value"><?= formatMoney($loan['applied_amount'] / $loan['term_months']) ?></div>
        </div>
        <div class="loan-meta-item">
            <div class="loan-meta-label">Application Date</div>
            <div class="loan-meta-value"><?= date('M d, Y', strtotime($loan['created_at'])) ?></div>
        </div>
        <?php if (!empty($loan['updated_at'])): ?>
        <div class="loan-meta-item">
            <div class="loan-meta-label">Status Date</div>
            <div class="loan-meta-value"><?= date('M d, Y', strtotime($loan['updated_at'])) ?></div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($loan['rejection_reason']): ?>
    <div style="padding:0 24px 24px;">
        <div style="background:#fee2e2; border-left:4px solid #dc2626; padding:12px 16px; border-radius:0 8px 8px 0;">
            <div style="font-size:12px; font-weight:700; color:#dc2626; margin-bottom:4px;">Rejection Reason</div>
            <div style="font-size:13px; color:#7f1d1d;"><?= htmlspecialchars($loan['rejection_reason']) ?></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Payment Progress -->
<?php if ($totalBills > 0): ?>
<div class="sc">
    <div class="sc-head">
        <h6 class="sc-title">
            <i class="bi bi-graph-up"></i> Payment Progress
        </h6>
        <span style="font-size:12px; color:#9ca3af;"><?= $completedBills ?>/<?= $totalBills ?> payments completed</span>
    </div>
    
    <div style="padding:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <span style="font-size:14px; font-weight:600; color:#374151;">Overall Progress</span>
            <span style="font-size:14px; font-weight:700; color:#3b82f6;"><?= round(($completedBills / $totalBills) * 100) ?>%</span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?= ($completedBills / $totalBills) * 100 ?>%;"></div>
        </div>
        
        <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; margin-top:20px;">
            <div style="text-align:center; padding:12px; background:#f0fdf4; border-radius:8px;">
                <div style="font-size:18px; color:#16a34a; margin-bottom:4px;">✓</div>
                <div style="font-size:20px; font-weight:700; color:#16a34a;"><?= $completedBills ?></div>
                <div style="font-size:12px; color:#15803d; margin-top:2px;">Completed</div>
            </div>
            <div style="text-align:center; padding:12px; background:#fef3c7; border-radius:8px;">
                <div style="font-size:18px; color:#d97706; margin-bottom:4px;">⏳</div>
                <div style="font-size:20px; font-weight:700; color:#d97706;"><?= $pendingBills ?></div>
                <div style="font-size:12px; color:#b45309; margin-top:2px;">Pending</div>
            </div>
            <div style="text-align:center; padding:12px; background:#fee2e2; border-radius:8px;">
                <div style="font-size:18px; color:#dc2626; margin-bottom:4px;">⚠</div>
                <div style="font-size:20px; font-weight:700; color:#dc2626;"><?= $overdueBills ?></div>
                <div style="font-size:12px; color:#b91c1c; margin-top:2px;">Overdue</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Payment Schedule -->
<div class="sc">
    <div class="sc-head">
        <h6 class="sc-title">
            <i class="bi bi-calendar-check"></i> Payment Schedule
        </h6>
        <span style="font-size:12px; color:#9ca3af;"><?= count($billingSchedule) ?> installments</span>
    </div>
    
    <?php if (!empty($billingSchedule)): ?>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Amount Due</th>
                        <th>Interest</th>
                        <th>Penalty</th>
                        <th>Total Due</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($billingSchedule as $bill): ?>
                    <tr <?= $bill['status'] === 'Pending' ? 'style="border-left: 3px solid #f59e0b;"' : '' ?>>
                        <td style="font-weight:600;">Month <?= $bill['month_number'] ?></td>
                        <td><?= formatMoney($bill['amount_due']) ?></td>
                        <td style="color:#f59e0b; font-weight:600;"><?= formatMoney($bill['interest']) ?></td>
                        <td><?= $bill['penalty'] > 0 ? formatMoney($bill['penalty']) : '₱0.00' ?></td>
                        <td style="font-weight:600;"><?= formatMoney($bill['total_due']) ?></td>
                        <td><?= date('M d, Y', strtotime($bill['due_date'])) ?></td>
                        <td>
                            <span class="bill-status <?= strtolower($bill['status']) ?>">
                                <?= $bill['status'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($bill['status'] === 'Completed'): ?>
                                <a href="<?= APP_URL ?>/user/billing/print.php?id=<?= $bill['id'] ?>" target="_blank" style="color:#3b82f6; text-decoration:none; font-size:13px;">
                                    <i class="bi bi-printer"></i> Receipt
                                </a>
                            <?php else: ?>
                                <span style="color:#9ca3af; font-size:13px;">No action</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📅</div>
            <div class="empty-state-title">No payment schedule</div>
            <div class="empty-state-text">Payment schedule will be available once the loan is approved.</div>
        </div>
    <?php endif; ?>
</div>

<!-- Transaction History -->
<div class="sc">
    <div class="sc-head">
        <h6 class="sc-title">
            <i class="bi bi-clock-history"></i> Transaction History
        </h6>
        <span style="font-size:12px; color:#9ca3af;"><?= count($loanTransactions) ?> transactions</span>
    </div>
    
    <?php if (!empty($loanTransactions)): ?>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <?php if ($hasDescriptions): ?>
                        <th>Description</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loanTransactions as $txn): ?>
                    <tr>
                        <td style="font-family:monospace; font-size:11px; color:#6b7280;"><?= $txn['transaction_id'] ?></td>
                        <td>
                            <?php 
                            $typeClass = match(strtolower($txn['type'])) {
                                'loan application' => 'application',
                                'payment' => 'payment',
                                'penalty' => 'penalty',
                                default => 'application'
                            };
                            ?>
                            <span class="txn-type <?= $typeClass ?>"><?= htmlspecialchars($txn['type']) ?></span>
                        </td>
                        <td style="font-weight:600;"><?= formatMoney($txn['amount']) ?></td>
                        <td>
                            <?php 
                            $statusClass = match(strtolower($txn['status'])) {
                                'approved' => 'approved',
                                'completed' => 'completed',
                                'pending' => 'pending',
                                'rejected' => 'rejected',
                                default => 'pending'
                            };
                            ?>
                            <span class="bill-status <?= $statusClass ?>">
                                <?= $txn['status'] ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y H:i', strtotime($txn['created_at'])) ?></td>
                        <?php if ($hasDescriptions): ?>
                        <td style="font-size:12px; color:#6b7280;"><?= htmlspecialchars($txn['description'] ?? '—') ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <div class="empty-state-title">No transactions</div>
            <div class="empty-state-text">Transaction history will appear here once available.</div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
