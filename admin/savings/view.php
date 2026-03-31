<?php
// ============================================================
//  admin/savings/view.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../helpers.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/admin/savings/index.php');

$stmt = $pdo->prepare("
    SELECT st.*, u.first_name, u.last_name, u.email,
           u.bank_name, u.bank_account_number, u.card_holder_name,
           s.balance
    FROM savings_transactions st
    JOIN users u ON u.id = st.user_id
    JOIN savings s ON s.user_id = st.user_id
    WHERE st.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$txn = $stmt->fetch();

if (!$txn) redirect(APP_URL . '/admin/savings/index.php');

$pageTitle = 'Withdrawal Request #' . $txn['id'];

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="index.php" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left"></i> Back to Savings
        </a>
        <h4 class="fw-bold mb-0 mt-1">
            <i class="bi bi-cash"></i> Withdrawal Request #<?= $txn['id'] ?>
        </h4>
    </div>

    <?php if ($txn['status'] === 'Pending'): ?>
    <div class="d-flex gap-2">
        <form method="POST" action="approve.php" onsubmit="return confirm('Approve this withdrawal request?')">
            <input type="hidden" name="id" value="<?= $txn['id'] ?>">
            <button class="btn btn-success"><i class="bi bi-check-circle"></i> Approve</button>
        </form>
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
            <i class="bi bi-x-circle"></i> Reject
        </button>
    </div>
    <?php else: ?>
        <?php
        $badge = match($txn['status']) {
            'Completed' => 'success',
            'Rejected'  => 'danger',
            'Failed'    => 'warning',
            default     => 'secondary'
        };
        ?>
        <span class="badge fs-6 bg-<?= $badge ?>"><?= $txn['status'] ?></span>
    <?php endif; ?>
</div>

<?= showFlash() ?>

<div class="row g-3">

    <!-- Transaction Details -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold border-0 pt-3">
                <i class="bi bi-receipt text-primary"></i> Transaction Details
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted" width="45%">Transaction ID</th>
                        <td class="font-monospace fw-semibold"><?= clean($txn['transaction_id']) ?></td></tr>
                    <tr><th class="text-muted">Category</th>
                        <td><span class="badge bg-warning text-dark">Withdrawal</span></td></tr>
                    <tr><th class="text-muted">Amount Requested</th>
                        <td class="fw-bold fs-5 text-danger">−<?= formatMoney($txn['amount']) ?></td></tr>
                    <tr><th class="text-muted">Current Balance</th>
                        <td class="fw-semibold text-success"><?= formatMoney($txn['balance']) ?></td></tr>
                    <tr><th class="text-muted">Balance After</th>
                        <td class="<?= ($txn['balance'] - $txn['amount']) < 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                            <?= formatMoney($txn['balance'] - $txn['amount']) ?>
                            <?php if (($txn['balance'] - $txn['amount']) < 0): ?>
                                <span class="badge bg-danger ms-1">Insufficient</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr><th class="text-muted">Status</th>
                        <td><span class="badge bg-warning text-dark"><?= $txn['status'] ?></span></td></tr>
                    <tr><th class="text-muted">Requested On</th>
                        <td><?= date('M d, Y h:i A', strtotime($txn['requested_at'])) ?></td></tr>
                    <?php if ($txn['processed_at']): ?>
                    <tr><th class="text-muted">Processed On</th>
                        <td><?= date('M d, Y h:i A', strtotime($txn['processed_at'])) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($txn['note']): ?>
                    <tr><th class="text-muted">Note</th>
                        <td class="text-danger"><?= clean($txn['note']) ?></td></tr>
                    <?php endif; ?>
                </table>

                <?php if (($txn['balance'] - $txn['amount']) < 0): ?>
                <div class="alert alert-danger mt-3 small mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    Insufficient balance. This request should be rejected.
                </div>
                <?php else: ?>
                <div class="alert alert-info mt-3 small mb-0">
                    <i class="bi bi-info-circle"></i>
                    After approving, manually send <strong><?= formatMoney($txn['amount']) ?></strong>
                    to the member's bank account below.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Member & Bank Details -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold border-0 pt-3">
                <i class="bi bi-person text-success"></i> Member & Bank Details
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-3">
                    <tr><th class="text-muted" width="40%">Name</th>
                        <td class="fw-semibold"><?= clean($txn['first_name'] . ' ' . $txn['last_name']) ?></td></tr>
                    <tr><th class="text-muted">Email</th>
                        <td><?= clean($txn['email']) ?></td></tr>
                </table>

                <p class="fw-semibold text-muted small mb-2">BANK DETAILS (send money here)</p>
                <div class="bg-light rounded p-3">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="text-muted small">Bank Name</div>
                            <div class="fw-semibold"><?= clean($txn['bank_name'] ?? '—') ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Account Number</div>
                            <div class="fw-semibold"><?= clean($txn['bank_account_number'] ?? '—') ?></div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">Card Holder's Name</div>
                            <div class="fw-semibold"><?= clean($txn['card_holder_name'] ?? '—') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="reject.php">
                <input type="hidden" name="id" value="<?= $txn['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-x-circle"></i> Reject Withdrawal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">The member will be notified via email.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3"
                            placeholder="e.g. Insufficient balance..."
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>