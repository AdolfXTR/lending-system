<?php
// ============================================================
//  admin/loans/view.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../helpers.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/admin/loans/index.php');

$stmt = $pdo->prepare("
    SELECT l.*, u.first_name, u.last_name, u.email, u.account_type,
           u.bank_name, u.bank_account_number, u.card_holder_name,
           u.company_name, u.monthly_earnings
    FROM loans l
    JOIN users u ON u.id = l.user_id
    WHERE l.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$loan = $stmt->fetch();

if (!$loan) redirect(APP_URL . '/admin/loans/index.php');

$interest = $loan['applied_amount'] * 0.03;
$received = $loan['applied_amount'] - $interest;
$monthly  = $loan['applied_amount'] / $loan['term_months'];

$billingStmt = $pdo->prepare("SELECT * FROM billing WHERE loan_id = ? ORDER BY month_number ASC");
$billingStmt->execute([$id]);
$billings = $billingStmt->fetchAll();

$totalMonths   = count($billings);
$paidMonths    = count(array_filter($billings, fn($b) => $b['status'] === 'Completed'));
$overdueMonths = count(array_filter($billings, fn($b) => $b['status'] === 'Overdue'));
$progressPct   = $totalMonths > 0 ? round(($paidMonths / $totalMonths) * 100) : 0;
$allPaid       = $totalMonths > 0 && $paidMonths === $totalMonths;

$completedBillings = array_filter($billings, fn($b) => $b['status'] === 'Completed');
$totalCollected = !empty($completedBillings) 
    ? array_sum(array_map(fn($b) => $b['total_due'], $completedBillings))
    : 0;
$totalDueAmount = array_sum(array_map(fn($b) => $b['total_due'], $billings));

$pageTitle = 'Loan #' . $loan['id'];

$badge = match($loan['status']) {
    'Pending'   => 'warning',
    'Approved'  => 'info',
    'Active'    => 'success',
    'Rejected'  => 'danger',
    'Completed' => 'secondary',
    default     => 'warning'
};

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<!-- Header Section with Breadcrumb -->
<div class="mb-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/admin/dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Loans</a></li>
            <li class="breadcrumb-item active fw-semibold" aria-current="page">Loan #<?= $loan['id'] ?></li>
        </ol>
    </nav>
    
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-cash-coin fs-4 text-primary"></i>
                <h4 class="fw-bold mb-0">Loan Application #<?= $loan['id'] ?></h4>
            </div>
            <?php if ($loan['status'] === 'Active'): ?>
            <span class="badge fs-6 bg-success d-flex align-items-center gap-2">
                <span class="pulse-dot"></span>
                <?= $loan['status'] ?>
            </span>
            <?php elseif ($loan['status'] !== 'Pending'): ?>
                <span class="badge fs-6 bg-<?= $badge ?> <?= $loan['status']==='Pending'?'text-dark':'' ?>">
                    <?= $loan['status'] ?>
                </span>
            <?php endif; ?>
        </div>
        <?php if ($loan['status'] === 'Pending'): ?>
        <div class="d-flex gap-2">
            <form method="POST" action="approve.php" onsubmit="return confirm('Approve and release this loan?')">
                <input type="hidden" name="id" value="<?= $loan['id'] ?>">
                <button class="btn btn-success"><i class="bi bi-check-circle"></i> Approve & Release</button>
            </form>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="bi bi-x-circle"></i> Reject
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<?= showFlash() ?>

<!-- Enhanced Amount Summary Banner -->
<div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #0f2557, #1a3a7a); border-radius: 16px;">
    <div class="card-body py-4">
        <div class="row g-0 text-center text-white">
            <div class="col-md-4 border-end border-white border-opacity-25 py-3 px-4">
                <i class="bi bi-cash-stack fs-4 mb-2 opacity-75"></i>
                <div class="small opacity-75 mb-2">Applied Amount</div>
                <div class="fs-3 fw-bold"><?= formatMoney($loan['applied_amount']) ?></div>
            </div>
            <div class="col-md-4 border-end border-white border-opacity-25 py-3 px-4">
                <i class="bi bi-percent fs-4 mb-2 opacity-75"></i>
                <div class="small opacity-75 mb-2">Interest Deducted (3%)</div>
                <div class="fs-3 fw-bold text-danger">− <?= formatMoney($interest) ?></div>
            </div>
            <div class="col-md-4 py-3 px-4">
                <i class="bi bi-arrow-down-circle fs-4 mb-2 opacity-75"></i>
                <div class="small opacity-75 mb-2">Borrower Will Receive</div>
                <div class="fs-2 fw-bold" style="color:#f5c842;"><?= formatMoney($received) ?></div>
                <div class="small opacity-75 mt-2">Transfer this amount via bank</div>
            </div>
        </div>
    </div>
</div>

<?php if ($allPaid): ?>
<div class="alert alert-success d-flex align-items-center gap-3 mb-4">
    <i class="bi bi-patch-check-fill fs-3"></i>
    <div>
        <div class="fw-bold">Loan Fully Paid! 🎉</div>
        <div class="small">All <?= $totalMonths ?> months have been paid. This borrower is eligible for a loan increase.</div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
            <div class="card-header bg-white fw-bold border-0 pt-3">
                <i class="bi bi-calculator text-primary"></i> Loan Summary
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="text-muted" width="50%">
                            <i class="bi bi-cash-stack me-1"></i> Applied Amount
                        </th>
                        <td class="fw-bold fs-5"><?= formatMoney($loan['applied_amount']) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-percent me-1"></i> Interest (3%)
                        </th>
                        <td class="text-danger">− <?= formatMoney($interest) ?></td>
                    </tr>
                    <tr style="background: linear-gradient(90deg, rgba(34, 197, 94, 0.1) 0%, transparent 100%); border-left: 4px solid #22c55e;">
                        <th class="text-success fw-semibold">
                            <i class="bi bi-arrow-down-circle me-1"></i> Amount to Receive
                        </th>
                        <td class="fw-bold text-success"><?= formatMoney($received) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-calendar-range me-1"></i> Term
                        </th>
                        <td><?= $loan['term_months'] ?> month<?= $loan['term_months'] > 1 ? 's' : '' ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-calendar-month me-1"></i> Monthly Payment
                        </th>
                        <td><?= formatMoney($monthly) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-tag me-1"></i> Status
                        </th>
                        <td><span class="badge bg-<?= $badge ?> <?= $loan['status']==='Pending'?'text-dark':'' ?>"><?= $loan['status'] ?></span></td>
                    </tr>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-calendar-plus me-1"></i> Applied On
                        </th>
                        <td>
                            <?= date('M d, Y', strtotime($loan['created_at'])) ?>
                            <span class="text-muted small"> · <?= date('g:i A', strtotime($loan['created_at'])) ?></span>
                        </td>
                    </tr>
                    <?php if ($loan['approved_at']): ?>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-calendar-check me-1"></i> Approved On
                        </th>
                        <td>
                            <?= date('M d, Y', strtotime($loan['approved_at'])) ?>
                            <span class="text-muted small"> · <?= date('g:i A', strtotime($loan['approved_at'])) ?></span>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($loan['rejection_reason']): ?>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-exclamation-triangle me-1"></i> Rejection Reason
                        </th>
                        <td class="text-danger"><?= clean($loan['rejection_reason']) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php if ($loan['status'] === 'Pending'): ?>
                <div class="alert alert-warning mt-3 small mb-0">
                    <i class="bi bi-info-circle"></i>
                    Approving will auto-generate billing. Transfer <strong><?= formatMoney($received) ?></strong> after approval.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
            <div class="card-header bg-white fw-bold border-0 pt-3">
                <i class="bi bi-person text-success"></i> Borrower Details
            </div>
            <div class="card-body">
                <!-- Borrower Avatar and Name Section -->
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 24px; font-weight: bold;">
                        <?= strtoupper(substr($loan['first_name'], 0, 1) . substr($loan['last_name'], 0, 1)) ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-semibold mb-0"><?= clean($loan['first_name'] . ' ' . $loan['last_name']) ?></h5>
                            <a href="<?= APP_URL ?>/admin/users/view.php?id=<?= $loan['user_id'] ?>" class="text-decoration-none small text-primary">
                                View Profile →
                            </a>
                        </div>
                        <div class="text-muted small"><?= clean($loan['email']) ?></div>
                    </div>
                </div>

                <table class="table table-sm table-borderless mb-3">
                    <tr>
                        <th class="text-muted" width="40%">
                            <i class="bi bi-tag me-1"></i> Account Type
                        </th>
                        <td><span class="badge bg-<?= $loan['account_type']==='Premium'?'success':'secondary' ?>"><?= $loan['account_type'] ?></span></td>
                    </tr>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-building me-1"></i> Company
                        </th>
                        <td><?= clean($loan['company_name'] ?? 'None') ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-wallet2 me-1"></i> Monthly Income
                        </th>
                        <td><?= !empty($loan['monthly_earnings']) ? formatMoney((float)$loan['monthly_earnings']) : 'Not specified' ?></td>
                    </tr>
                </table>

                <p class="fw-semibold text-muted small mb-3">
                        <i class="bi bi-bank me-1"></i> BANK DETAILS (transfer money here)
                    </p>
                <div class="rounded-3 p-4" style="background:#f8fafc;border:1.5px solid #e2e8f0;">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="text-muted small mb-1">Bank Name</div>
                            <div class="fw-semibold d-flex align-items-center gap-2">
                                <i class="bi bi-bank2 text-primary"></i>
                                <?= clean($loan['bank_name'] ?? 'Not specified') ?>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="text-muted small mb-1">Account Number</div>
                            <div class="fw-semibold d-flex align-items-center gap-2">
                                <span id="accountNumber"><?= clean($loan['bank_account_number'] ?? 'Not specified') ?></span>
                                <?php if (!empty($loan['bank_account_number'])): ?>
                                <button class="btn btn-sm btn-outline-secondary" onclick="copyAccountNumber()" title="Copy account number">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="text-muted small mb-1">Card Holder's Name</div>
                            <div class="fw-semibold"><?= clean($loan['card_holder_name'] ?? 'Not specified') ?></div>
                        </div>
                    </div>
                </div>
                <!-- Enhanced Transfer Instruction Banner -->
                <div class="alert d-flex gap-3 align-items-start mb-0" style="background: linear-gradient(135deg, #e0f2fe, #bae6fd); border: none; border-radius: 12px;">
                    <i class="bi bi-info-circle-fill fs-4 text-info flex-shrink-0"></i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold text-dark mb-1">Transfer Instruction</div>
                        <div class="small text-dark">
                            After approving, manually transfer <strong class="text-success"><?= formatMoney($received) ?></strong> to the bank account above.
                        </div>
                        <div class="mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="transferDone" onchange="toggleTransferStatus()">
                                <label class="form-check-label small text-dark" for="transferDone">
                                    <strong>Transfer Done</strong> - Check this box when you've completed the bank transfer
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Billing Progress -->
<?php if (!empty($billings)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3">
        <div class="d-flex justify-content-between align-items-center">
            <div class="fw-bold">
                <i class="bi bi-list-check text-primary"></i> Payment Progress
            </div>
            <div class="small text-muted">
                <span class="text-success fw-bold"><?= $paidMonths ?></span> of
                <span class="fw-bold"><?= $totalMonths ?></span> months paid
                <?php if ($overdueMonths > 0): ?>
                    &bull; <span class="text-danger fw-bold"><?= $overdueMonths ?> overdue</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="progress mt-2" style="height:10px;border-radius:99px;">
            <div class="progress-bar bg-success" style="width:<?= $progressPct ?>%;border-radius:99px;"></div>
        </div>
        <div class="small text-muted mt-1"><?= $progressPct ?>% complete</div>
    </div>

    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Month</th><th>Due Date</th><th>Amount Due</th>
                    <th>Interest</th><th>Penalty</th><th>Total</th>
                    <th>Paid On</th><th>Status</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($billings as $b): ?>
                <?php
                    $bb = match($b['status']) {
                        'Completed' => 'success',
                        'Overdue'   => 'danger',
                        'Pending'   => 'warning',
                        default     => 'secondary'
                    };
                ?>
                <tr class="<?= $b['status']==='Overdue' ? 'table-danger' : '' ?>">
                    <td class="fw-semibold">Month <?= $b['month_number'] ?></td>
                    <td class="small"><?= date('M d, Y', strtotime($b['due_date'])) ?></td>
                    <td><?= formatMoney($b['amount_due']) ?></td>
                    <td class="text-danger small"><?= $b['interest'] > 0 ? formatMoney($b['interest']) : '—' ?></td>
                    <td class="text-danger small"><?= $b['penalty'] > 0 ? formatMoney($b['penalty']) : '—' ?></td>
                    <td class="fw-bold"><?= formatMoney($b['total_due']) ?></td>
                    <td class="small text-muted"><?= $b['paid_at'] ? date('M d, Y', strtotime($b['paid_at'])) : '—' ?></td>
                    <td>
                        <span class="badge bg-<?= $bb ?> <?= $b['status']==='Pending'?'text-dark':'' ?>">
                            <?= $b['status'] ?>
                            <?php if ($b['status'] === 'Completed'): ?>
                                <i class="bi bi-check-circle-fill ms-1"></i>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($b['status'] === 'Pending' || $b['status'] === 'Overdue'): ?>
                        <button type="button" class="btn btn-sm btn-success"
                            onclick="openMarkPaidModal(
                                <?= $b['id'] ?>,
                                <?= $b['month_number'] ?>,
                                '<?= date('M d, Y', strtotime($b['due_date'])) ?>',
                                '<?= addslashes(formatMoney($b['total_due'])) ?>',
                                '<?= addslashes(clean($loan['first_name'] . ' ' . $loan['last_name'])) ?>'
                            )">
                            <i class="bi bi-check"></i> Mark Paid
                        </button>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Mark Paid Confirmation Modal -->
<div class="modal fade" id="markPaidModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= APP_URL ?>/admin/billing/mark_paid.php">
                <input type="hidden" name="id" id="markPaidBillingId">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold" style="color:#16a34a;">
                            <i class="bi bi-check-circle-fill"></i> Confirm Payment
                        </h5>
                        <p class="text-muted small mb-0">Review the details below before confirming.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-3">
                    <!-- Summary Box -->
                    <div class="rounded-3 p-3 mb-3" style="background:#f0fdf4;border:1.5px solid #bbf7d0;">
                        <div class="row g-2 text-center mb-2">
                            <div class="col-4">
                                <div class="text-muted" style="font-size:11px;">Borrower</div>
                                <div class="fw-bold small" id="mpBorrower">—</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted" style="font-size:11px;">Month</div>
                                <div class="fw-bold" id="mpMonth">—</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted" style="font-size:11px;">Total Due</div>
                                <div class="fw-bold text-success" id="mpAmount">—</div>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="text-center">
                            <div class="text-muted" style="font-size:11px;">Due Date</div>
                            <div class="fw-semibold" id="mpDueDate">—</div>
                        </div>
                    </div>

                    <!-- Warning -->
                    <div class="alert alert-warning small mb-0 d-flex gap-2 align-items-start">
                        <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
                        <span>
                            <strong>This action cannot be undone.</strong><br>
                            Make sure you have verified that the borrower has transferred the payment before proceeding.
                        </span>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check-circle"></i> Yes, Mark as Paid
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="reject.php">
                <input type="hidden" name="id" value="<?= $loan['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-x-circle"></i> Reject Loan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">The borrower will be notified via email with your reason.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="4"
                            placeholder="e.g. Insufficient income based on submitted documents..."
                            required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openMarkPaidModal(billingId, monthNum, dueDate, amount, borrower) {
    document.getElementById('markPaidBillingId').value = billingId;
    document.getElementById('mpBorrower').textContent  = borrower;
    document.getElementById('mpMonth').textContent     = 'Month ' + monthNum;
    document.getElementById('mpDueDate').textContent   = dueDate;
    document.getElementById('mpAmount').textContent    = amount;
    new bootstrap.Modal(document.getElementById('markPaidModal')).show();
}

function copyAccountNumber() {
    const accountNumber = document.getElementById('accountNumber').textContent;
    if (accountNumber && accountNumber !== 'Not specified') {
        navigator.clipboard.writeText(accountNumber).then(() => {
            // Show success feedback
            const button = event.target.closest('button');
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="bi bi-check"></i>';
            button.classList.add('btn-success');
            button.classList.remove('btn-outline-secondary');
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 2000);
        });
    }
}

function toggleTransferStatus() {
    const checkbox = document.getElementById('transferDone');
    const alertDiv = checkbox.closest('.alert');
    
    if (checkbox.checked) {
        alertDiv.style.background = 'linear-gradient(135deg, #dcfce7, #bbf7d0)';
        alertDiv.querySelector('.text-info').classList.remove('text-info');
        alertDiv.querySelector('.text-info').classList.add('text-success');
    } else {
        alertDiv.style.background = 'linear-gradient(135deg, #e0f2fe, #bae6fd)';
        alertDiv.querySelector('.text-success').classList.remove('text-success');
        alertDiv.querySelector('.text-success').classList.add('text-info');
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>