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
    <form method="POST" action="mark_paid.php" onsubmit="return confirm('Mark this bill as paid?')">
        <input type="hidden" name="id" value="<?= $bill['id'] ?>">
        <button class="btn btn-success"><i class="bi bi-check-circle"></i> Mark as Paid</button>
    </form>
    <?php else: ?>
        <span class="badge fs-6 bg-<?= $bill['status']==='Completed'?'success':'secondary' ?>">
            <?= $bill['status'] ?>
        </span>
    <?php endif; ?>
</div>

<?= showFlash() ?>

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