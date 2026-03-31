<?php
// ============================================================
//  admin/registrations/view.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../helpers.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/admin/registrations/index.php');

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) redirect(APP_URL . '/admin/registrations/index.php');

$pageTitle = 'Review Application — ' . $user['first_name'] . ' ' . $user['last_name'];

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="index.php" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left"></i> Back to Applications
        </a>
        <h4 class="fw-bold mb-0 mt-1">
            <i class="bi bi-person-badge"></i>
            <?= clean($user['first_name'] . ' ' . $user['last_name']) ?>
        </h4>
    </div>
    <?php if ($user['status'] === 'Pending'): ?>
    <div class="d-flex gap-2">
        <form method="POST" action="approve.php" onsubmit="return confirm('Approve this application?')">
            <input type="hidden" name="id" value="<?= $user['id'] ?>">
            <button class="btn btn-success"><i class="bi bi-check-circle"></i> Approve</button>
        </form>
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
            <i class="bi bi-x-circle"></i> Reject
        </button>
    </div>
    <?php else: ?>
        <span class="badge fs-6 bg-<?= $user['status']==='Active'?'success':($user['status']==='Disabled'?'danger':'secondary') ?>">
            <?= $user['status'] ?>
        </span>
    <?php endif; ?>
</div>

<?= showFlash() ?>

<div class="row g-3">

    <!-- Personal Info -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold border-0 pt-3">
                <i class="bi bi-person text-primary"></i> Personal Information
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted" width="40%">Full Name</th><td><?= clean($user['first_name'] . ' ' . $user['last_name']) ?></td></tr>
                    <tr><th class="text-muted">Gender</th><td><?= clean($user['gender'] ?? '—') ?></td></tr>
                    <tr><th class="text-muted">Birthday</th><td><?= $user['birthday'] ? date('F d, Y', strtotime($user['birthday'])) : '—' ?></td></tr>
                    <tr><th class="text-muted">Age</th><td><?= $user['age'] ?? '—' ?></td></tr>
                    <tr><th class="text-muted">Address</th><td><?= clean($user['address'] ?? '—') ?></td></tr>
                    <tr><th class="text-muted">Email</th><td><?= clean($user['email']) ?></td></tr>
                    <tr><th class="text-muted">Contact</th><td><?= clean($user['contact_number'] ?? '—') ?></td></tr>
                    <tr><th class="text-muted">TIN</th><td><?= clean($user['tin_number'] ?? '—') ?></td></tr>
                    <tr><th class="text-muted">Account Type</th>
                        <td>
                            <span class="badge bg-<?= $user['account_type']==='Premium'?'success':'secondary' ?>">
                                <?= $user['account_type'] ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Employment & Bank -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold border-0 pt-3">
                <i class="bi bi-briefcase text-success"></i> Employment & Bank Details
            </div>
            <div class="card-body">
                <p class="fw-semibold text-muted small mb-1">EMPLOYMENT</p>
                <table class="table table-sm table-borderless mb-3">
                    <tr><th class="text-muted" width="40%">Company</th><td><?= clean($user['company_name'] ?? '—') ?></td></tr>
                    <tr><th class="text-muted">Address</th><td><?= clean($user['company_address'] ?? '—') ?></td></tr>
                    <tr><th class="text-muted">Phone</th><td><?= clean($user['company_phone'] ?? '—') ?></td></tr>
                    <tr><th class="text-muted">Position</th><td><?= clean($user['position'] ?? '—') ?></td></tr>
                    <tr><th class="text-muted">Monthly Income</th>
                        <td><?= !empty($user['monthly_earnings']) ? formatMoney((float)$user['monthly_earnings']) : '—' ?></td>
                    </tr>
                </table>
                <p class="fw-semibold text-muted small mb-1">BANK</p>
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted" width="40%">Bank Name</th><td><?= clean($user['bank_name'] ?? '—') ?></td></tr>
                    <tr><th class="text-muted">Account No.</th><td><?= clean($user['bank_account_number'] ?? '—') ?></td></tr>
                    <tr><th class="text-muted">Card Holder</th><td><?= clean($user['card_holder_name'] ?? '—') ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Uploaded Documents -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold border-0 pt-3">
                <i class="bi bi-file-earmark-text text-warning"></i> Submitted Documents
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php
                    $docs = [
                        'proof_of_billing' => 'Proof of Billing',
                        'valid_id'         => 'Valid ID (Primary)',
                        'coe'              => 'Certificate of Employment',
                    ];
                    foreach ($docs as $field => $label):
                        $file = $user[$field] ?? null;
                    ?>
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-center h-100">
                            <div class="fw-semibold small mb-2"><?= $label ?></div>
                            <?php if ($file): ?>
                                <?php
                                // File is stored as "proof_of_billing/filename.jpg"
                                // so just append to uploads/
                                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                $url = APP_URL . '/uploads/' . $file;
                                ?>
                                <?php if (in_array($ext, ['jpg','jpeg','png','gif'])): ?>
                                    <a href="<?= $url ?>" target="_blank">
                                        <img src="<?= $url ?>" class="img-fluid rounded"
                                             style="max-height:180px; object-fit:cover;"
                                             onerror="this.src=''; this.alt='Image not found';">
                                    </a>
                                <?php else: ?>
                                    <a href="<?= $url ?>" target="_blank" class="btn btn-sm btn-outline-secondary mt-2">
                                        <i class="bi bi-file-earmark-arrow-down"></i> View File
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-muted small mt-3">
                                    <i class="bi bi-dash-circle"></i> Not uploaded
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-x-circle"></i> Reject Application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Rejecting this application will notify the applicant via email.
                        The record will be automatically deleted after 30 days.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="4"
                            placeholder="e.g. Submitted documents are invalid or incomplete..."
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