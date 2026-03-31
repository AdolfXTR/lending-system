<?php
// ============================================================
//  admin/users/view.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../helpers.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/admin/users/index.php');

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) redirect(APP_URL . '/admin/users/index.php');

// Loans
$loans = $pdo->prepare("SELECT * FROM loans WHERE user_id = ? ORDER BY created_at DESC");
$loans->execute([$id]);
$loans = $loans->fetchAll();

// Savings
$savings = $pdo->prepare("SELECT * FROM savings WHERE user_id = ? LIMIT 1");
$savings->execute([$id]);
$savings = $savings->fetch();

$savingsTxns = $pdo->prepare("SELECT * FROM savings_transactions WHERE user_id = ? ORDER BY requested_at DESC LIMIT 10");
$savingsTxns->execute([$id]);
$savingsTxns = $savingsTxns->fetchAll();

// Billing
$billing = $pdo->prepare("SELECT * FROM billing WHERE user_id = ? ORDER BY due_date ASC");
$billing->execute([$id]);
$billing = $billing->fetchAll();

$pageTitle = 'User — ' . $user['first_name'] . ' ' . $user['last_name'];

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="index.php" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left"></i> Back to Users
        </a>
        <h4 class="fw-bold mb-0 mt-1">
            <i class="bi bi-person-badge"></i>
            <?= clean($user['first_name'] . ' ' . $user['last_name']) ?>
        </h4>
    </div>
    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal">
        <i class="bi bi-pencil"></i> Edit Account
    </button>
</div>

<?= showFlash() ?>

<!-- Nav Tabs -->
<ul class="nav nav-tabs mb-3" id="userTab">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#profile">Profile</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#loans">
            Loans <span class="badge bg-secondary ms-1"><?= count($loans) ?></span>
        </a>
    </li>
    <?php if ($user['account_type'] === 'Premium'): ?>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#savings">Savings</a>
    </li>
    <?php endif; ?>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#billing">
            Billing <span class="badge bg-secondary ms-1"><?= count($billing) ?></span>
        </a>
    </li>
</ul>

<div class="tab-content">

    <!-- Profile Tab -->
    <div class="tab-pane fade show active" id="profile">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold border-0 pt-3">
                        <i class="bi bi-person text-primary"></i> Personal Info
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><th class="text-muted" width="40%">Full Name</th><td><?= clean($user['first_name'] . ' ' . $user['last_name']) ?></td></tr>
                            <tr><th class="text-muted">Username</th><td><?= clean($user['username']) ?></td></tr>
                            <tr><th class="text-muted">Gender</th><td><?= clean($user['gender'] ?? '—') ?></td></tr>
                            <tr><th class="text-muted">Birthday</th><td><?= $user['birthday'] ? date('F d, Y', strtotime($user['birthday'])) : '—' ?></td></tr>
                            <tr><th class="text-muted">Age</th><td><?= $user['age'] ?? '—' ?></td></tr>
                            <tr><th class="text-muted">Address</th><td><?= clean($user['address'] ?? '—') ?></td></tr>
                            <tr><th class="text-muted">Email</th><td><?= clean($user['email']) ?></td></tr>
                            <tr><th class="text-muted">Contact</th><td><?= clean($user['contact_number'] ?? '—') ?></td></tr>
                            <tr><th class="text-muted">TIN</th><td><?= clean($user['tin_number'] ?? '—') ?></td></tr>
                            <tr><th class="text-muted">Account Type</th>
                                <td><span class="badge bg-<?= $user['account_type']==='Premium'?'success':'secondary' ?>"><?= $user['account_type'] ?></span></td></tr>
                            <tr><th class="text-muted">Status</th>
                                <td>
                                    <?php
                                    $sb = match($user['status']) {
                                        'Active'   => 'success',
                                        'Disabled' => 'danger',
                                        'Pending'  => 'warning',
                                        default    => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $sb ?> <?= $user['status']==='Pending'?'text-dark':'' ?>">
                                        <?= $user['status'] ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-bold border-0 pt-3">
                        <i class="bi bi-briefcase text-success"></i> Employment
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><th class="text-muted" width="40%">Company</th><td><?= clean($user['company_name'] ?? '—') ?></td></tr>
                            <tr><th class="text-muted">Address</th><td><?= clean($user['company_address'] ?? '—') ?></td></tr>
                            <tr><th class="text-muted">Phone</th><td><?= clean($user['company_phone'] ?? '—') ?></td></tr>
                            <tr><th class="text-muted">Position</th><td><?= clean($user['position'] ?? '—') ?></td></tr>
                            <tr><th class="text-muted">Monthly Income</th><td><?= $user['monthly_earnings']? formatMoney((float)($user['monthly_earnings'] ?? 0)): '—' ?></td></tr>
                        </table>
                    </div>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold border-0 pt-3">
                        <i class="bi bi-bank text-warning"></i> Bank Details
                    </div>
                    <!-- Documents -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold border-0 pt-3">
                        <i class="bi bi-file-earmark-text text-danger"></i> Uploaded Documents
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <p class="fw-semibold mb-1 small text-muted">Proof of Billing</p>
                                <?php if (!empty($user['proof_of_billing'])): ?>
                                    <?php $ext = strtolower(pathinfo($user['proof_of_billing'], PATHINFO_EXTENSION)); ?>
                                    <?php if (in_array($ext, ['jpg','jpeg','png'])): ?>
                                        <a href="<?= APP_URL ?>/uploads/<?= $user['proof_of_billing'] ?>" target="_blank">
                                            <img src="<?= APP_URL ?>/uploads/<?= $user['proof_of_billing'] ?>" class="img-fluid rounded border" style="max-height:180px;">
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= APP_URL ?>/uploads/<?= $user['proof_of_billing'] ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-file-pdf"></i> View PDF
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">Not uploaded</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <p class="fw-semibold mb-1 small text-muted">Valid ID</p>
                                <?php if (!empty($user['valid_id'])): ?>
                                    <?php $ext = strtolower(pathinfo($user['valid_id'], PATHINFO_EXTENSION)); ?>
                                    <?php if (in_array($ext, ['jpg','jpeg','png'])): ?>
                                        <a href="<?= APP_URL ?>/uploads/<?= $user['valid_id'] ?>" target="_blank">
                                            <img src="<?= APP_URL ?>/uploads/<?= $user['valid_id'] ?>" class="img-fluid rounded border" style="max-height:180px;">
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= APP_URL ?>/uploads/<?= $user['valid_id'] ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-file-pdf"></i> View PDF
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">Not uploaded</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <p class="fw-semibold mb-1 small text-muted">Certificate of Employment (COE)</p>
                                <?php if (!empty($user['coe'])): ?>
                                    <?php $ext = strtolower(pathinfo($user['coe'], PATHINFO_EXTENSION)); ?>
                                    <?php if (in_array($ext, ['jpg','jpeg','png'])): ?>
                                        <a href="<?= APP_URL ?>/uploads/<?= $user['coe'] ?>" target="_blank">
                                            <img src="<?= APP_URL ?>/uploads/<?= $user['coe'] ?>" class="img-fluid rounded border" style="max-height:180px;">
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= APP_URL ?>/uploads/<?= $user['coe'] ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-file-pdf"></i> View PDF
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">Not uploaded</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><th class="text-muted" width="40%">Bank</th><td><?= clean($user['bank_name'] ?? '—') ?></td></tr>
                            <tr><th class="text-muted">Account No.</th><td><?= clean($user['bank_account_number'] ?? '—') ?></td></tr>
                            <tr><th class="text-muted">Card Holder</th><td><?= clean($user['card_holder_name'] ?? '—') ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loans Tab -->
    <div class="tab-pane fade" id="loans">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Amount</th><th>Term</th><th>Status</th><th>Applied</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($loans)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No loans yet</td></tr>
                        <?php else: ?>
                            <?php foreach ($loans as $i => $l): ?>
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
                                <td><a href="<?= APP_URL ?>/admin/loans/view.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Savings Tab (Premium only) -->
    <?php if ($user['account_type'] === 'Premium'): ?>
    <div class="tab-pane fade" id="savings">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="text-muted small">Current Balance</div>
                    <div class="fs-3 fw-bold text-success"><?= formatMoney($savings['balance'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold border-0 pt-3">Recent Transactions</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Transaction ID</th><th>Category</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($savingsTxns)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No transactions yet</td></tr>
                        <?php else: ?>
                            <?php foreach ($savingsTxns as $t): ?>
                            <tr>
                                <td class="font-monospace small"><?= clean($t['transaction_id']) ?></td>
                                <td><span class="badge bg-<?= $t['category']==='Deposit'?'info':'warning text-dark' ?>"><?= $t['category'] ?></span></td>
                                <td class="<?= $t['category']==='Deposit'?'text-success':'text-danger' ?>">
                                    <?= $t['category']==='Deposit'?'+':'−' ?><?= formatMoney($t['amount']) ?>
                                </td>
                                <td><span class="badge bg-<?= $t['status']==='Completed'?'success':($t['status']==='Pending'?'warning text-dark':'danger') ?>"><?= $t['status'] ?></span></td>
                                <td class="small text-muted"><?= date('M d, Y', strtotime($t['requested_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Billing Tab -->
    <div class="tab-pane fade" id="billing">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Month</th><th>Amount Due</th><th>Penalty</th><th>Total</th><th>Due Date</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($billing)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No billing records yet</td></tr>
                        <?php else: ?>
                            <?php foreach ($billing as $b): ?>
                            <?php
                                $bb = match($b['status']) {
                                    'Pending'   => 'warning',
                                    'Completed' => 'success',
                                    'Overdue'   => 'danger',
                                    default     => 'secondary'
                                };
                            ?>
                            <tr class="<?= $b['status']==='Overdue'?'table-danger':'' ?>">
                                <td>Month <?= $b['month_number'] ?></td>
                                <td><?= formatMoney($b['amount_due']) ?></td>
                                <td class="text-danger"><?= $b['penalty'] > 0 ? formatMoney($b['penalty']) : '—' ?></td>
                                <td class="fw-bold"><?= formatMoney($b['total_due']) ?></td>
                                <td class="small"><?= date('M d, Y', strtotime($b['due_date'])) ?></td>
                                <td><span class="badge bg-<?= $bb ?> <?= $b['status']==='Pending'?'text-dark':'' ?>"><?= $b['status'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="update.php">
                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Account Type</label>
                        <select name="account_type" class="form-select">
                            <option value="Basic" <?= $user['account_type']==='Basic'?'selected':'' ?>>Basic</option>
                            <option value="Premium" <?= $user['account_type']==='Premium'?'selected':'' ?>>Premium</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" <?= $user['status']==='Active'?'selected':'' ?>>Active</option>
                            <option value="Disabled" <?= $user['status']==='Disabled'?'selected':'' ?>>Disabled</option>
                            <option value="Pending" <?= $user['status']==='Pending'?'selected':'' ?>>Pending</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>