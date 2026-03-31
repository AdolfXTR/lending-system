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
    background: #1a1a1a;
    border-radius: 12px;
    padding: 0;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
    animation: paymentModalSlideIn 0.3s ease-out;
    border: 1px solid rgba(255, 255, 255, 0.1);
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
    background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
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
    background: #1a1a1a;
    color: #ffffff;
}

.payment-summary {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.payment-summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    font-size: 14px;
}

.payment-summary-row:last-child {
    border-bottom: none;
}

.payment-summary-label {
    color: rgba(255, 255, 255, 0.7);
    font-weight: 500;
}

.payment-summary-value {
    font-weight: 600;
    color: #ffffff;
}

.payment-summary-value.amount {
    font-size: 18px;
    font-weight: 700;
    color: #10b981;
}

.payment-warning {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.payment-warning i {
    color: #ef4444;
    font-size: 18px;
    margin-top: 2px;
}

.payment-warning-text {
    color: #fca5a5;
    font-size: 14px;
    line-height: 1.5;
}

.payment-modal-footer {
    padding: 20px 24px 24px;
    background: #1a1a1a;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
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
    color: rgba(255, 255, 255, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.payment-modal-btn-cancel:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
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

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="index.php" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left"></i> Back to Billing
        </a>
        <h4 class="fw-bold mb-0 mt-1">
            <i class="bi bi-receipt"></i>
            Billing — <?= clean($bill['first_name'] . ' ' . $bill['last_name']) ?> (Month <?= $bill['month_number'] ?>)
        </h4>
    </div>

    <?php if ($bill['status'] === 'Pending' || $bill['status'] === 'Overdue'): ?>
    <button class="btn btn-success" onclick="showPaymentConfirmation()">
        <i class="bi bi-check-circle"></i> Mark as Paid
    </button>
    <?php else: ?>
        <span class="badge fs-6 bg-<?= $bill['status']==='Completed'?'success':'secondary' ?>">
            <?= $bill['status'] ?>
        </span>
    <?php endif; ?>
</div>

<?= showFlash() ?>

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
</script>

<div class="row g-3">

    <!-- Billing Statement -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold border-0 pt-3">
                <i class="bi bi-file-earmark-text text-primary"></i> Billing Statement
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted" width="50%">Date Generated</th>
                        <td><?= date('F d, Y', strtotime($bill['created_at'])) ?></td></tr>
                    <tr><th class="text-muted">Due Date</th>
                        <td class="<?= $bill['status']==='Overdue'?'text-danger fw-bold':'' ?>">
                            <?= date('F d, Y', strtotime($bill['due_date'])) ?>
                        </td></tr>
                    <tr><th class="text-muted">Month</th>
                        <td>Month <?= $bill['month_number'] ?> of <?= $bill['term_months'] ?? '—' ?></td></tr>
                    <tr><th class="text-muted">Loan Amount</th>
                        <td><?= formatMoney($bill['applied_amount']) ?></td></tr>
                    <tr><th class="text-muted">Received Amount</th>
                        <td><?= $bill['received_amount'] ? formatMoney($bill['received_amount']) : '—' ?></td></tr>
                    <tr><td colspan="2"><hr class="my-2"></td></tr>
                    <tr><th class="text-muted">Amount Due (this month)</th>
                        <td><?= formatMoney($bill['amount_due']) ?></td></tr>
                    <tr><th class="text-muted">Interest (3%)</th>
                        <td class="text-danger"><?= $bill['interest'] > 0 ? formatMoney($bill['interest']) : '—' ?></td></tr>
                    <tr><th class="text-muted">Penalty (2%)</th>
                        <td class="text-danger"><?= $bill['penalty'] > 0 ? formatMoney($bill['penalty']) : '—' ?></td></tr>
                    <tr class="table-<?= $bill['status']==='Overdue'?'danger':'success' ?>">
                        <th>Total Due</th>
                        <td class="fw-bold fs-5"><?= formatMoney($bill['total_due']) ?></td>
                    </tr>
                    <tr><td colspan="2"><hr class="my-2"></td></tr>
                    <tr><th class="text-muted">Status</th>
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
                </table>
            </div>
        </div>
    </div>

    <!-- Borrower Details -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold border-0 pt-3">
                <i class="bi bi-person text-success"></i> Borrower Details
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-3">
                    <tr><th class="text-muted" width="40%">Name</th>
                        <td class="fw-semibold"><?= clean($bill['first_name'] . ' ' . $bill['last_name']) ?></td></tr>
                    <tr><th class="text-muted">Email</th>
                        <td><?= clean($bill['email']) ?></td></tr>
                </table>

                <p class="fw-semibold text-muted small mb-2">ACCOUNT TYPE</p>
                <div class="bg-light rounded p-3">
                    <div class="text-muted small">Bank Name</div>
                    <div class="fw-semibold mb-2"><?= clean($bill['bank_name'] ?? '—') ?></div>
                    <div class="text-muted small">Account Number</div>
                    <div class="fw-semibold mb-2"><?= clean($bill['bank_account_number'] ?? '—') ?></div>
                    <div class="text-muted small">Card Holder</div>
                    <div class="fw-semibold"><?= clean($bill['card_holder_name'] ?? '—') ?></div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>