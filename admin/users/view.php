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

// Calculate summary stats
$totalBorrowed = array_sum(array_column(array_filter($loans, fn($l) => $l['status'] !== 'Rejected'), 'applied_amount'));
$activeLoans = count(array_filter($loans, fn($l) => $l['status'] === 'Active'));
$completedLoans = count(array_filter($loans, fn($l) => $l['status'] === 'Completed'));

$totalDeposits = array_sum(array_column(array_filter($savingsTxns, fn($t) => $t['category'] === 'Deposit'), 'amount'));
$totalWithdrawals = array_sum(array_column(array_filter($savingsTxns, fn($t) => $t['category'] === 'Withdrawal'), 'amount'));
$netBalance = $totalDeposits - $totalWithdrawals;

$totalDue = array_sum(array_column($billing, 'amount_due'));
$totalPaid = array_sum(array_column(array_filter($billing, fn($b) => $b['status'] === 'Completed'), 'total_due'));
$remainingBalance = $totalDue - $totalPaid;

$pageTitle = 'User — ' . $user['first_name'] . ' ' . $user['last_name'];

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<style>
/* Enhanced User View Styles */
.user-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 32px;
    position: relative;
    overflow: hidden;
}

.user-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.03"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
    opacity: 0.1;
}

.user-header-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.user-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #14b8a6 0%, #0891b2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 700;
    margin-right: 24px;
    box-shadow: 0 8px 24px rgba(20, 184, 166, 0.3);
    border: 3px solid rgba(255, 255, 255, 0.2);
}

.user-info {
    flex: 1;
}

.user-name {
    font-size: 28px;
    font-weight: 800;
    color: white;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.user-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-premium {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.badge-active {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.active-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: white;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.user-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 15px;
    margin: 0;
}

.btn-edit-account {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-edit-account:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
    color: white;
}

/* Enhanced Tabs */
.user-tabs {
    border: none;
    background: white;
    border-radius: 16px;
    padding: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    margin-bottom: 24px;
}

.user-tabs .nav-link {
    border: none;
    border-radius: 12px;
    padding: 12px 20px;
    font-weight: 600;
    color: #64748b;
    transition: all 0.3s ease;
    position: relative;
    margin: 0 4px;
}

.user-tabs .nav-link:hover {
    background: #f8fafc;
    color: #334155;
}

.user-tabs .nav-link.active {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.user-tabs .badge {
    background: rgba(255, 255, 255, 0.2);
    color: inherit;
    font-weight: 600;
}

.user-tabs .nav-link.active .badge {
    background: rgba(255, 255, 255, 0.3);
    color: white;
}

/* Enhanced Cards */
.info-card {
    background: white;
    border-radius: 16px;
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    margin-bottom: 24px;
}

.info-card:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.info-card-header {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 20px 24px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    display: flex;
    align-items: center;
    gap: 12px;
}

.info-card-title {
    font-size: 16px;
    font-weight: 700;
    color: #374151;
    margin: 0;
}

.info-card-body {
    padding: 24px;
}

/* 2-Column Grid Layout */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.info-grid-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    border-radius: 12px;
    transition: all 0.2s ease;
}

.info-grid-item:hover {
    background: #f8fafc;
}

.info-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: white;
    flex-shrink: 0;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.info-icon.person { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
.info-icon.email { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
.info-icon.phone { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.info-icon.address { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.info-icon.calendar { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
.info-icon.money { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.info-icon.bank { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.info-icon.work { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }

.info-label {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.info-value {
    font-size: 15px;
    color: #1e293b;
    font-weight: 500;
    word-break: break-word;
}

/* Summary Cards */
.summary-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 16px;
    padding: 24px;
    border: 1px solid rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.summary-title {
    font-size: 14px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.summary-value {
    font-size: 28px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

/* Enhanced Tables */
.data-table {
    background: white;
    border-radius: 16px;
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.data-table table {
    margin: 0;
}

.data-table thead {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.data-table th {
    font-weight: 700;
    color: #374151;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
    padding: 16px;
    border: none;
}

.data-table td {
    padding: 16px;
    vertical-align: middle;
    border-top: 1px solid #f1f5f9;
}

.data-table tbody tr:hover {
    background: #f8fafc;
}

.data-table tbody tr.overdue {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
}

.data-table tbody tr.overdue:hover {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
}

.btn-view {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-view:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    color: white;
}

/* Savings Balance Card */
.balance-card {
    background: linear-gradient(135deg, #14b8a6 0%, #059669 100%);
    border-radius: 16px;
    padding: 32px;
    color: white;
    text-align: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(20, 184, 166, 0.3);
}

.balance-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

.balance-icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.9;
}

.balance-label {
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.9;
    margin-bottom: 8px;
}

.balance-amount {
    font-size: 36px;
    font-weight: 800;
    margin: 0;
    position: relative;
    z-index: 1;
}

/* Transaction Badges */
.badge-deposit {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    color: white;
}

.badge-withdrawal {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    color: white;
}

.amount-positive {
    color: #059669;
    font-weight: 600;
}

.amount-negative {
    color: #dc2626;
    font-weight: 600;
}

/* Status Badges */
.badge-completed {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.badge-pending {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

.badge-overdue {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

.btn-mark-paid {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-mark-paid:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .user-header-content {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .user-avatar {
        margin-right: 0;
        margin-bottom: 16px;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .summary-grid {
        grid-template-columns: 1fr;
    }
    
    .user-tabs .nav-link {
        padding: 10px 16px;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        border-bottom: 2px solid transparent;
        transition: all 0.2s ease;
    }

    .user-tabs .nav-link.active {
        color: #059669;
        border-bottom-color: #059669;
    }

    .user-tabs .nav-link:hover {
        color: #059669;
    }

    .user-tabs .nav-link i {
        margin-right: 8px;
    }
</style>

<!-- Enhanced User Header -->
<div class="user-header">
    <div class="user-header-content">
        <div style="display: flex; align-items: center;">
            <div class="user-avatar">
                <?= strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1)) ?>
            </div>
            <div class="user-info">
                <a href="index.php" class="text-decoration-none text-white small mb-2 d-inline-block">
                    <i class="bi bi-arrow-left"></i> Back to Users
                </a>
                <h1 class="user-name">
                    <?= clean($user['first_name'] . ' ' . $user['last_name']) ?>
                    <div class="user-badges">
                        <?php if ($user['account_type'] === 'Premium'): ?>
                            <span class="user-badge badge-premium">
                                <i class="bi bi-star-fill"></i> Premium
                            </span>
                        <?php endif; ?>
                        <?php if ($user['status'] === 'Active'): ?>
                            <span class="user-badge badge-active">
                                <span class="active-dot"></span> Active
                            </span>
                        <?php endif; ?>
                    </div>
                </h1>
                <p class="user-subtitle">User ID #<?= $user['id'] ?> • Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
            </div>
        </div>
        <button class="btn-edit-account" data-bs-toggle="modal" data-bs-target="#editModal">
            <i class="bi bi-pencil"></i> Edit Account
        </button>
    </div>
</div>

<?= showFlash() ?>

<!-- Enhanced Nav Tabs -->
<ul class="nav nav-tabs user-tabs mb-4" id="userTab">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#profile">
            <i class="bi bi-person"></i> Profile
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#loans">
            <i class="bi bi-cash-stack"></i> Loans 
            <span class="badge ms-1"><?= count($loans) ?></span>
        </a>
    </li>
    <?php if ($user['account_type'] === 'Premium'): ?>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#savings">
            <i class="bi bi-piggy-bank"></i> Savings
        </a>
    </li>
    <?php endif; ?>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#billing">
            <i class="bi bi-receipt"></i> Billing 
            <span class="badge ms-1"><?= count($billing) ?></span>
        </a>
    </li>
</ul>

<div class="tab-content">
    <!-- ... existing content ... -->
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
                            <?php
                            // Calculate age with validation
                            $ageDisplay = '—';
                            if (!empty($user['birthday']) && $user['birthday'] !== '0000-00-00') {
                                $birthYear = (int)date('Y', strtotime($user['birthday']));
                                $currentYear = (int)date('Y');
                                $age = $currentYear - $birthYear;
                                // Adjust if birthday hasn't occurred this year
                                if (date('md') < date('md', strtotime($user['birthday']))) {
                                    $age--;
                                }
                                // Validate: age must be between 0 and 120
                                if ($age >= 0 && $age <= 120) {
                                    $ageDisplay = $age . ' years old';
                                } else {
                                    $ageDisplay = 'N/A';
                                }
                            }
                            ?>
                            <tr><th class="text-muted">Age</th><td><?= $ageDisplay ?></td></tr>
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
                    <div class="card-body">
                        <?php
                        // Check if any bank details exist
                        $hasBankDetails = !empty($user['bank_name']) || !empty($user['bank_account_number']) || !empty($user['card_holder_name']);
                        ?>
                        <?php if ($hasBankDetails): ?>
                            <table class="table table-sm table-borderless mb-0">
                                <tr><th class="text-muted" width="40%">Bank</th><td><?= clean($user['bank_name'] ?? '—') ?></td></tr>
                                <tr><th class="text-muted">Account No.</th><td><?= clean($user['bank_account_number'] ?? '—') ?></td></tr>
                                <tr><th class="text-muted">Card Holder</th><td><?= clean($user['card_holder_name'] ?? '—') ?></td></tr>
                            </table>
                        <?php else: ?>
                            <div class="text-muted text-center py-3">
                                <i class="bi bi-bank fs-4 d-block mb-2"></i>
                                No bank details on file
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Section -->
        <div class="row g-3 mt-1">
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
        </div>
    </div>
    <!-- End Profile Tab -->

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