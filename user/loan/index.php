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

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-cash-coin text-primary"></i> My Loan</h4>
    <?php if (!$activeLoan): ?>
        <a href="apply.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Apply for Loan
        </a>
    <?php endif; ?>
</div>

<?= showFlash() ?>

<!-- Active Loan Card -->
<?php if ($activeLoan): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-bold border-0 pt-3">
        <i class="bi bi-credit-card text-primary"></i> Current Loan
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3 text-center">
                <div class="text-muted small">Loan Amount</div>
                <div class="fs-4 fw-bold text-primary"><?= formatMoney($activeLoan['applied_amount']) ?></div>
            </div>
            <div class="col-md-3 text-center">
                <div class="text-muted small">Amount Received</div>
                <div class="fs-4 fw-bold text-success"><?= $activeLoan['received_amount'] ? formatMoney($activeLoan['received_amount']) : '—' ?></div>
                <?php if ($activeLoan['received_amount']): ?>
                    <div class="text-muted" style="font-size:0.75rem;">After 3% interest deduction</div>
                <?php endif; ?>
            </div>
            <div class="col-md-3 text-center">
                <div class="text-muted small">Term</div>
                <div class="fs-4 fw-bold"><?= $activeLoan['term_months'] ?> month<?= $activeLoan['term_months'] > 1 ? 's' : '' ?></div>
            </div>
            <div class="col-md-3 text-center">
                <div class="text-muted small">Status</div>
                <?php
                $sb = match($activeLoan['status']) {
                    'Pending'  => 'warning',
                    'Approved' => 'info',
                    'Active'   => 'success',
                    default    => 'secondary'
                };
                ?>
                <span class="badge bg-<?= $sb ?> fs-6 <?= $activeLoan['status']==='Pending'?'text-dark':'' ?>">
                    <?= $activeLoan['status'] ?>
                </span>
            </div>
        </div>

        <?php if ($activeLoan['status'] === 'Pending'): ?>
        <div class="alert alert-warning mt-3 mb-0 small">
            <i class="bi bi-hourglass-split"></i> Your loan application is being reviewed by admin. You will be notified once it's approved.
        </div>
        <?php elseif ($activeLoan['status'] === 'Approved' || $activeLoan['status'] === 'Active'): ?>
        <div class="alert alert-success mt-3 mb-0 small">
            <i class="bi bi-check-circle"></i> Your loan has been approved! Check your billing for payment schedule.
        </div>
        <?php endif; ?>

        <?php if (!empty($activeLoan['rejection_reason'])): ?>
        <div class="alert alert-danger mt-3 mb-0 small">
            <i class="bi bi-x-circle"></i> <strong>Rejection Reason:</strong> <?= clean($activeLoan['rejection_reason']) ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body text-center py-5">
        <div style="font-size:64px;margin-bottom:16px;">💳</div>
        <h5 style="color:#374151;font-weight:700;margin-bottom:8px;">No active loan</h5>
        <p style="color:#6b7280;font-size:14px;line-height:1.5;margin-bottom:20px;">You don't have any active loans at the moment.<br>Apply for a loan to get started with your financial needs!</p>
        <a href="apply.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Apply for Loan
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Loan Transactions -->
<?php if (!empty($loanTxns)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-bold border-0 pt-3">
        <i class="bi bi-arrow-up-circle text-success"></i> Loan Transactions
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Transaction ID</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php foreach ($loanTxns as $i => $t): ?>
                <?php
                    $tb = match($t['status']) {
                        'Pending'  => 'warning',
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default    => 'secondary'
                    };
                ?>
                <tr>
                    <td><?= $t['no'] ?: $i+1 ?></td>
                    <td class="font-monospace small"><?= clean($t['transaction_id']) ?></td>
                    <td><?= clean($t['type']) ?></td>
                    <td><?= formatMoney($t['amount']) ?></td>
                    <td><span class="badge bg-<?= $tb ?> <?= $t['status']==='Pending'?'text-dark':'' ?>"><?= $t['status'] ?></span></td>
                    <td class="small text-muted"><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Loan History -->
<?php if (count($allLoans) > 1): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold border-0 pt-3">
        <i class="bi bi-clock-history text-muted"></i> Loan History
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Amount</th><th>Term</th><th>Status</th><th>Date Applied</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($allLoans as $i => $l): ?>
                <?php
                    $lb = match($l['status']) {
                        'Pending'   => 'warning',
                        'Approved'  => 'info',
                        'Active'    => 'success',
                        'Rejected'  => 'danger',
                        'Completed' => 'secondary',
                        default     => 'secondary'
                    };
                ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= formatMoney($l['applied_amount']) ?></td>
                    <td><?= $l['term_months'] ?> mo</td>
                    <td><span class="badge bg-<?= $lb ?> <?= $l['status']==='Pending'?'text-dark':'' ?>"><?= $l['status'] ?></span></td>
                    <td class="small text-muted"><?= date('M d, Y', strtotime($l['created_at'])) ?></td>
                    <td>
                        <?php if ($l['status'] === 'Rejected' && !empty($l['rejection_reason'])): ?>
                            <button class="btn btn-sm btn-link p-0" data-bs-toggle="popover" data-bs-content="<?= htmlspecialchars($l['rejection_reason']) ?>" title="Rejection Reason" onclick="new bootstrap.Popover(this)">
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