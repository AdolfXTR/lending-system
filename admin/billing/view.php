<?php
// ============================================================
//  admin/billing/view.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../helpers.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/admin/billing/index.php');

$stmt = $pdo->prepare("
    SELECT b.*, u.first_name, u.last_name, u.email,
           u.bank_name, u.bank_account_number, u.card_holder_name,
           l.applied_amount, l.term_months, l.received_amount
    FROM billing b
    JOIN users u ON u.id = b.user_id
    JOIN loans l ON l.id = b.loan_id
    WHERE b.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$bill = $stmt->fetch();

if (!$bill) redirect(APP_URL . '/admin/billing/index.php');

$pageTitle = 'Billing — Month ' . $bill['month_number'];

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<style>
/* Custom Payment Confirmation Modal Styles */
.text-amber { color: #f59e0b !important; }

.payment-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1050;
    backdrop-filter: blur(4px);
}

.payment-modal-content {
    background: white;
    border-radius: 12px;
    padding: 0;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
    animation: paymentModalSlideIn 0.3s ease-out;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

@keyframes paymentModalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.payment-modal-header {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    padding: 24px 24px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.payment-modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.payment-modal-body {
    padding: 24px;
    background: white;
    color: #374151;
}

.payment-summary {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #10b981;
}

.payment-summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid rgba(16, 185, 129, 0.2);
    font-size: 14px;
}

.payment-summary-row:last-child {
    border-bottom: none;
}

.payment-summary-label {
    color: #065f46;
    font-weight: 500;
}

.payment-summary-value {
    font-weight: 600;
    color: #111827;
}

.payment-summary-value.amount {
    font-size: 18px;
    font-weight: 700;
    color: #059669;
}

.payment-warning {
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.3);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.payment-warning i {
    color: #f59e0b;
    font-size: 18px;
    margin-top: 2px;
}

.payment-warning-text {
    color: #92400e;
    font-size: 14px;
    line-height: 1.5;
}

.payment-modal-footer {
    padding: 20px 24px 24px;
    background: white;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    border-top: 1px solid #e5e7eb;
}

.payment-modal-btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: inherit;
    min-width: 120px;
}

.payment-modal-btn-cancel {
    background: transparent;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

.payment-modal-btn-cancel:hover {
    background: #f9fafb;
    color: #374151;
}

.payment-modal-btn-confirm {
    background: #10b981;
    color: #ffffff;
    font-weight: 700;
}

.payment-modal-btn-confirm:hover {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
</style>

<!-- Enhanced Header Section -->
<div class="mb-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/admin/dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Billing</a></li>
            <li class="breadcrumb-item active fw-semibold" aria-current="page">Month <?= $bill['month_number'] ?></li>
        </ol>
    </nav>
    
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-receipt fs-4 text-primary"></i>
                <h4 class="fw-bold mb-0">Billing — <?= clean($bill['first_name'] . ' ' . $bill['last_name']) ?></h4>
            </div>
            <?php if ($bill['status'] === 'Completed'): ?>
            <span class="badge fs-6 bg-success d-flex align-items-center gap-2">
                <span class="pulse-dot"></span>
                <?= $bill['status'] ?>
            </span>
            <?php elseif ($bill['status'] !== 'Pending'): ?>
                <span class="badge fs-6 bg-<?= $bill['status']==='Overdue'?'danger':'secondary' ?>">
                    <?= $bill['status'] ?>
                </span>
            <?php endif; ?>
        </div>
        <?php if ($bill['status'] === 'Pending' || $bill['status'] === 'Overdue'): ?>
            <button class="btn btn-success" onclick="showPaymentConfirmation()">
                <i class="bi bi-check-circle"></i> Mark as Paid
            </button>
        <?php endif; ?>
    </div>
</div>

<?= showFlash() ?>

<!-- Enhanced Amount Summary Banner -->
<div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #0f2557, #1a3a7a); border-radius: 16px;">
    <div class="card-body py-4">
        <div class="row g-0 text-center text-white">
            <div class="col-md-4 border-end border-white border-opacity-25 py-3 px-4">
                <i class="bi bi-calendar-check fs-4 mb-2 opacity-75"></i>
                <div class="small opacity-75 mb-2">Due Date</div>
                <div class="fs-5 fw-bold <?= $bill['status']==='Overdue'?'text-danger':'' ?>">
                    <?= date('M d, Y', strtotime($bill['due_date'])) ?>
                </div>
                <div class="small opacity-75 mt-1">Month <?= $bill['month_number'] ?> of <?= $bill['term_months'] ?? '—' ?></div>
            </div>
            <div class="col-md-4 border-end border-white border-opacity-25 py-3 px-4">
                <i class="bi bi-cash-stack fs-4 mb-2 opacity-75"></i>
                <div class="small opacity-75 mb-2">Monthly Payment</div>
                <div class="fs-5 fw-bold"><?= formatMoney($bill['amount_due']) ?></div>
                <div class="small opacity-75 mt-1">Base amount</div>
            </div>
            <div class="col-md-4 py-3 px-4">
                <i class="bi bi-wallet2 fs-4 mb-2 opacity-75"></i>
                <div class="small opacity-75 mb-2">Total Due</div>
                <div class="fs-3 fw-bold" style="color:#f5c842;"><?= formatMoney($bill['total_due']) ?></div>
                <div class="small opacity-75 mt-1">
                    <?php if ($bill['interest'] > 0 || $bill['penalty'] > 0): ?>
                        Includes interest & penalties
                    <?php else: ?>
                        No additional charges
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Payment Confirmation Modal -->
<div id="paymentConfirmationModal" class="payment-modal-overlay">
    <div class="payment-modal-content">
        <div class="payment-modal-header">
            <h3 class="payment-modal-title">
                <i class="bi bi-check-circle-fill"></i>
                Confirm Payment
            </h3>
        </div>
        <div class="payment-modal-body">
            <div class="payment-summary">
                <div class="payment-summary-row">
                    <span class="payment-summary-label">Borrower Name</span>
                    <span class="payment-summary-value"><?= clean($bill['first_name'] . ' ' . $bill['last_name']) ?></span>
                </div>
                <div class="payment-summary-row">
                    <span class="payment-summary-label">Month</span>
                    <span class="payment-summary-value">Month <?= $bill['month_number'] ?> of <?= $bill['term_months'] ?? '—' ?></span>
                </div>
                <div class="payment-summary-row">
                    <span class="payment-summary-label">Total Amount Due</span>
                    <span class="payment-summary-value amount"><?= formatMoney($bill['total_due']) ?></span>
                </div>
            </div>
            
            <div class="payment-warning">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div class="payment-warning-text">
                    Make sure you have verified that the borrower has already transferred the payment before marking as paid. This action cannot be undone.
                </div>
            </div>
        </div>
        <div class="payment-modal-footer">
            <button type="button" class="payment-modal-btn payment-modal-btn-cancel" onclick="hidePaymentConfirmation()">
                Cancel
            </button>
            <form method="POST" action="mark_paid.php" style="display: inline;">
                <input type="hidden" name="id" value="<?= $bill['id'] ?>">
                <button type="submit" class="payment-modal-btn payment-modal-btn-confirm">
                    Yes, Mark as Paid
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function showPaymentConfirmation() {
    document.getElementById('paymentConfirmationModal').style.display = 'flex';
}

function hidePaymentConfirmation() {
    document.getElementById('paymentConfirmationModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('paymentConfirmationModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hidePaymentConfirmation();
    }
});

function copyAccountNumber() {
    navigator.clipboard.writeText(document.getElementById('accountNumber').textContent);
    alert('Account number copied to clipboard!');
}
</script>

<div class="row g-3">

    <!-- Billing Statement -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
            <div class="card-header bg-white fw-bold border-0 pt-3">
                <i class="bi bi-file-earmark-text text-primary"></i> Billing Statement
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="text-muted" width="50%">
                            <i class="bi bi-calendar-plus me-1"></i> Date Generated
                        </th>
                        <td><?= date('M d, Y', strtotime($bill['created_at'])) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-calendar-event me-1"></i> Due Date
                        </th>
                        <td class="<?= $bill['status']==='Overdue'?'text-danger fw-bold':'' ?>">
                            <?= date('M d, Y', strtotime($bill['due_date'])) ?>
                            <?php if ($bill['status']==='Overdue'): ?>
                                <span class="badge bg-danger ms-2 small">OVERDUE</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-calendar-range me-1"></i> Month
                        </th>
                        <td>Month <?= $bill['month_number'] ?> of <?= $bill['term_months'] ?? '—' ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-cash-stack me-1"></i> Loan Amount
                        </th>
                        <td><?= formatMoney($bill['applied_amount']) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-arrow-down-circle me-1"></i> Received Amount
                        </th>
                        <td><?= $bill['received_amount'] ? formatMoney($bill['received_amount']) : '—' ?></td>
                    </tr>
                    <tr><td colspan="2"><hr class="my-2"></td></tr>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-receipt me-1"></i> Amount Due (this month)
                        </th>
                        <td><?= formatMoney($bill['amount_due']) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-percent me-1"></i> Interest (3%)
                        </th>
                        <td class="text-amber"><?= $bill['interest'] > 0 ? formatMoney($bill['interest']) : formatMoney(0) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-exclamation-triangle me-1"></i> Penalty<?= $bill['penalty'] > 0 ? ' (2%)' : '' ?>
                        </th>
                        <td class="text-amber"><?= $bill['penalty'] > 0 ? formatMoney($bill['penalty']) : formatMoney(0) ?></td>
                    </tr>
                    <tr style="background: linear-gradient(90deg, rgba(34, 197, 94, 0.1) 0%, transparent 100%); border-left: 4px solid #22c55e;">
                        <th class="text-success fw-semibold">
                            <i class="bi bi-wallet2 me-1"></i> Total Due
                        </th>
                        <td class="fw-bold fs-5 text-success"><?= formatMoney($bill['total_due']) ?></td>
                    </tr>
                    <tr><td colspan="2"><hr class="my-2"></td></tr>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-tag me-1"></i> Status
                        </th>
                        <td>
                            <?php
                            $badge = match($bill['status']) {
                                'Pending'   => 'warning',
                                'Completed' => 'success',
                                'Overdue'   => 'danger',
                                default     => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $badge ?> <?= $bill['status']==='Pending'?'text-dark':'' ?>">
                                <?= $bill['status'] ?>
                                <?php if ($bill['status'] === 'Completed'): ?>
                                    <i class="bi bi-check-circle-fill ms-1"></i>
                                <?php endif; ?>
                            </span>
                        </td>
                    </tr>
                    <?php if ($bill['paid_at']): ?>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-calendar-check me-1"></i> Paid On
                        </th>
                        <td class="text-success">
                            <?= date('M d, Y', strtotime($bill['paid_at'])) ?>
                            <span class="text-muted small"> · <?= date('g:i A', strtotime($bill['paid_at'])) ?></span>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Borrower Details -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
            <div class="card-header bg-white fw-bold border-0 pt-3">
                <i class="bi bi-person text-success"></i> Borrower Details
            </div>
            <div class="card-body">
                <!-- Borrower Avatar and Name Section -->
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 24px; font-weight: bold;">
                        <?= strtoupper(substr($bill['first_name'], 0, 1) . substr($bill['last_name'], 0, 1)) ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-semibold mb-0"><?= clean($bill['first_name'] . ' ' . $bill['last_name']) ?></h5>
                            <a href="<?= APP_URL ?>/admin/users/view.php?id=<?= $bill['user_id'] ?>" class="text-decoration-none small text-primary">
                                View Profile →
                            </a>
                        </div>
                        <div class="text-muted small"><?= clean($bill['email']) ?></div>
                    </div>
                </div>

                <table class="table table-sm table-borderless mb-3">
                    <tr>
                        <th class="text-muted" width="40%">
                            <i class="bi bi-calendar-check me-1"></i> Billing Status
                        </th>
                        <td>
                            <?php
                            $badge = match($bill['status']) {
                                'Pending'   => 'warning',
                                'Completed' => 'success',
                                'Overdue'   => 'danger',
                                default     => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $badge ?> <?= $bill['status']==='Pending'?'text-dark':'' ?>">
                                <?= $bill['status'] ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted">
                            <i class="bi bi-calendar-event me-1"></i> Payment Due
                        </th>
                        <td class="<?= $bill['status']==='Overdue'?'text-danger fw-bold':'' ?>">
                            <?= date('M d, Y', strtotime($bill['due_date'])) ?>
                        </td>
                    </tr>
                </table>

                <p class="fw-semibold text-muted small mb-3">
                    <i class="bi bi-bank me-1"></i> BANK DETAILS (for payment verification)
                </p>
                <div class="rounded-3 p-4" style="background:#f8fafc;border:1.5px solid #e2e8f0;">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="text-muted small mb-1">Bank Name</div>
                            <div class="fw-semibold d-flex align-items-center gap-2">
                                <i class="bi bi-bank2 text-primary"></i>
                                <?= clean($bill['bank_name'] ?? 'Not specified') ?>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="text-muted small mb-1">Account Number</div>
                            <div class="fw-semibold d-flex align-items-center gap-2">
                                <span id="accountNumber"><?= clean($bill['bank_account_number'] ?? 'Not specified') ?></span>
                                <?php if (!empty($bill['bank_account_number'])): ?>
                                <button class="btn btn-sm btn-outline-secondary" onclick="copyAccountNumber()" title="Copy account number">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="text-muted small mb-1">Card Holder's Name</div>
                            <div class="fw-semibold"><?= clean($bill['card_holder_name'] ?? 'Not specified') ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Instructions -->
                <?php if ($bill['status'] === 'Pending' || $bill['status'] === 'Overdue'): ?>
                <div class="alert d-flex gap-3 align-items-start mb-0 mt-3" style="background: linear-gradient(135deg, #e0f2fe, #bae6fd); border: none; border-radius: 12px;">
                    <i class="bi bi-info-circle-fill fs-4 text-info flex-shrink-0"></i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold text-dark mb-1">Payment Verification</div>
                        <div class="small text-dark">
                            Verify that <strong><?= formatMoney($bill['total_due']) ?></strong> has been transferred to the bank account above before marking as paid.
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>