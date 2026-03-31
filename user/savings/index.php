<?php
// ============================================================
//  user/savings/index.php
// ============================================================
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../helpers.php';

// Block admins from performing user actions
if (!empty($_SESSION['admin_id'])) {
    setFlash('danger', 'Admins cannot access savings.');
    header('Location: ' . APP_URL . '/admin/dashboard.php');
    exit;
}

// Block Basic accounts
if (($_SESSION['account_type'] ?? null) !== 'Premium') {
    setFlash('warning', 'Savings is only available for Premium members.');
    redirect(APP_URL . '/user/dashboard.php');
}

$userId = $_SESSION['user_id'];

// Get savings balance
$stmt = $pdo->prepare("SELECT * FROM savings WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$savings = $stmt->fetch();
$balance = $savings ? (float)$savings['balance'] : 0;

// Count withdrawals today
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM savings_transactions 
    WHERE user_id = ? AND category = 'Withdrawal' 
    AND DATE(requested_at) = CURDATE()
    AND status IN ('Pending','Completed')
");
$stmt->execute([$userId]);
$withdrawalsToday = (int)$stmt->fetchColumn();

// Total withdrawn today
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount),0) FROM savings_transactions 
    WHERE user_id = ? AND category = 'Withdrawal' 
    AND DATE(requested_at) = CURDATE()
    AND status IN ('Pending','Completed')
");
$stmt->execute([$userId]);
$withdrawnToday = (float)$stmt->fetchColumn();

// Search & filter transactions
$search   = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? '';

$where  = "WHERE user_id = ?";
$params = [$userId];

if ($category === 'Withdrawal' || $category === 'Deposit' || $category === 'Money Back') {
    $where   .= " AND category = ?";
    $params[] = $category;
}
if ($search !== '') {
    $where   .= " AND transaction_id LIKE ?";
    $params[] = '%' . $search . '%';
}

$stmt = $pdo->prepare("SELECT * FROM savings_transactions $where ORDER BY requested_at DESC");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Get Money Back transactions for this user
$moneyBackTransactions = getUserMoneyBackTransactions($pdo, $userId);

// Get latest Money Back distribution info
$latestMoneyBack = null;
$stmt = $pdo->prepare("SELECT * FROM money_back_distributions ORDER BY distribution_date DESC LIMIT 1");
$stmt->execute();
$latestMoneyBack = $stmt->fetch();

$pageTitle = 'My Savings';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Modern Page Styles -->
<style>
.savings-hero {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 24px;
    padding: 32px;
    margin-bottom: 32px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 25px -5px rgba(16, 185, 129, 0.15);
}

.savings-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.savings-hero::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -5%;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
}

.hero-title {
    color: #ffffff;
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 8px;
    position: relative;
    z-index: 1;
}

.hero-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 16px;
    margin-bottom: 24px;
    position: relative;
    z-index: 1;
}

.hero-buttons {
    display: flex;
    gap: 12px;
    position: relative;
    z-index: 1;
}

.btn-hero {
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    font-size: 14px;
}

.btn-deposit-hero {
    background: rgba(255, 255, 255, 0.95);
    color: #059669;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
}

.btn-deposit-hero:hover {
    background: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.btn-withdraw-hero {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.btn-withdraw-hero:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
}

.stats-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 24px;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

.stats-card.warning::before {
    background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
}

.stats-card.danger::before {
    background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
}

.stats-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.stats-label {
    color: #6b7280;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stats-value {
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 4px;
}

.stats-value.success { color: #10b981; }
.stats-value.primary { color: #3b82f6; }
.stats-value.warning { color: #f59e0b; }
.stats-value.danger { color: #ef4444; }

.stats-subtitle {
    color: #9ca3af;
    font-size: 12px;
    font-weight: 500;
}

.modern-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    overflow: hidden;
}

.modern-card:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.card-header-modern {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 20px 28px;
    border-bottom: 1px solid #e2e8f0;
}

.card-title-modern {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-subtitle-modern {
    color: #6b7280;
    font-size: 13px;
    margin-top: 4px;
}

.table-modern {
    margin: 0;
}

.table-modern thead th {
    background: #f8fafc;
    color: #374151;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 16px 20px;
    border: none;
}

.table-modern tbody td {
    padding: 16px 20px;
    vertical-align: middle;
    border-top: 1px solid #f1f5f9;
    font-size: 14px;
}

.table-modern tbody tr:hover {
    background: #f8fafc;
}

.badge-modern {
    padding: 6px 12px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state-title {
    font-size: 18px;
    font-weight: 700;
    color: #374151;
    margin-bottom: 8px;
}

.empty-state-subtitle {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 24px;
}

.search-form-modern {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.form-control-modern {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 16px;
    font-size: 14px;
    transition: all 0.2s ease;
    background: #ffffff;
}

.form-control-modern:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.form-select-modern {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 16px;
    font-size: 14px;
    transition: all 0.2s ease;
    background: #ffffff;
}

.form-select-modern:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.btn-modern {
    padding: 10px 20px;
    border-radius: 12px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: inherit;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-primary-modern {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
}

.btn-primary-modern:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.btn-secondary-modern {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    color: #6b7280;
    border: 2px solid #d1d5db;
}

.btn-secondary-modern:hover {
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
    color: #374151;
    transform: translateY(-1px);
}

.money-back-section {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #bbf7d0;
    border-radius: 20px;
    overflow: hidden;
}

.money-back-header {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    padding: 16px 24px;
}

.money-back-title {
    font-size: 16px;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.money-back-subtitle {
    font-size: 12px;
    opacity: 0.9;
    margin-top: 2px;
}

@media (max-width: 768px) {
    .savings-hero {
        padding: 24px;
        margin-bottom: 24px;
    }
    
    .hero-title {
        font-size: 24px;
    }
    
    .stats-value {
        font-size: 24px;
    }
    
    .hero-buttons {
        flex-direction: column;
    }
    
    .btn-hero {
        justify-content: center;
    }
}
</style>

<!-- Hero Section -->
<div class="savings-hero">
    <h1 class="hero-title">
        <i class="bi bi-piggy-bank-fill"></i> My Savings
    </h1>
    <p class="hero-subtitle">Grow your wealth with secure savings and money back rewards</p>
    <div class="hero-buttons">
        <button type="button" class="btn-hero btn-deposit-hero" onclick="document.getElementById('depositModal').style.display = 'flex'">
            <i class="bi bi-plus-circle-fill"></i> Deposit
        </button>
        <button type="button" class="btn-hero btn-withdraw-hero" onclick="document.getElementById('withdrawModal').style.display = 'flex'">
            <i class="bi bi-arrow-up-circle-fill"></i> Request Withdrawal
        </button>
    </div>
</div>

<?= showFlash() ?>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stats-card">
            <div class="stats-label">Current Balance</div>
            <div class="stats-value success"><?= formatMoney($balance) ?></div>
            <div class="stats-subtitle">Max: <?= formatMoney(SAVINGS_MAX) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card <?= $withdrawalsToday >= 5 ? 'danger' : 'warning' ?>">
            <div class="stats-label">Withdrawals Today</div>
            <div class="stats-value <?= $withdrawalsToday >= 5 ? 'danger' : 'primary' ?>">
                <?= $withdrawalsToday ?> / 5
            </div>
            <div class="stats-subtitle">Max 5 withdrawals per day</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card <?= $withdrawnToday >= 5000 ? 'danger' : 'warning' ?>">
            <div class="stats-label">Withdrawn Today</div>
            <div class="stats-value <?= $withdrawnToday >= 5000 ? 'danger' : 'warning' ?>">
                <?= formatMoney($withdrawnToday) ?>
            </div>
            <div class="stats-subtitle">Max <?= formatMoney(SAVINGS_MAX_WITHDRAW_DAY) ?> per day</div>
        </div>
    </div>
</div>

<!-- Money Back Section -->
<?php if (!empty($moneyBackTransactions)): ?>
<div class="money-back-section mb-4">
    <div class="money-back-header">
        <div class="money-back-title">
            <i class="bi bi-cash-stack-fill"></i> Money Back Earnings
        </div>
        <div class="money-back-subtitle">2% of company income distribution</div>
    </div>
    <div class="p-0">
        <table class="table table-modern mb-0">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Transaction ID</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($moneyBackTransactions as $i => $t): ?>
                <tr>
                    <td class="text-muted small"><?= $i + 1 ?></td>
                    <td class="font-monospace small"><?= clean($t['transaction_id']) ?></td>
                    <td class="text-success fw-bold">+<?= formatMoney($t['amount']) ?></td>
                    <td class="small text-muted"><?= date('M d, Y h:i A', strtotime($t['requested_at'])) ?></td>
                    <td class="small text-muted"><?= clean($t['note'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Transactions Section -->
<div class="modern-card">
    <div class="card-header-modern">
        <div>
            <div class="card-title-modern">
                <i class="bi bi-list-ul"></i> Transactions
            </div>
            <div class="card-subtitle-modern">View your savings transaction history</div>
        </div>
        <form method="GET" class="search-form-modern">
            <input type="text" name="search" class="form-control-modern" style="width: 220px;"
                placeholder="Search Transaction ID" value="<?= clean($search) ?>">
            <select name="category" class="form-select-modern" style="width: 140px;">
                <option value="">All</option>
                <option value="Deposit" <?= $category==='Deposit'?'selected':'' ?>>Deposit Only</option>
                <option value="Withdrawal" <?= $category==='Withdrawal'?'selected':'' ?>>Withdrawal Only</option>
                <option value="Money Back" <?= $category==='Money Back'?'selected':'' ?>>Money Back Only</option>
            </select>
            <button class="btn-modern btn-primary-modern">
                <i class="bi bi-funnel"></i> Filter
            </button>
            <?php if ($search || $category): ?>
                <a href="index.php" class="btn-modern btn-secondary-modern">
                    <i class="bi bi-x"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
    <div class="p-0">
        <table class="table table-modern mb-0">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Transaction ID</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-state-icon">📭</div>
                                <div class="empty-state-title">No transactions yet</div>
                                <div class="empty-state-subtitle">Start building your savings by making your first deposit!</div>
                                <button type="button" class="btn-hero btn-deposit-hero" onclick="document.getElementById('depositModal').style.display = 'flex'">
                                    <i class="bi bi-plus-circle-fill"></i> Make First Deposit
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $i => $t): ?>
                    <?php
                        $sb = match($t['status']) {
                            'Completed' => 'success',
                            'Pending'   => 'warning',
                            'Failed'    => 'danger',
                            'Rejected'  => 'danger',
                            default     => 'secondary'
                        };
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $t['no'] ?: $i+1 ?></td>
                        <td class="font-monospace small"><?= clean($t['transaction_id']) ?></td>
                        <td>
                            <?php
                            $cat = trim($t['category'] ?? '');
                            $badgeClass = match($cat) {
                                'Deposit' => 'info',
                                'Withdrawal' => 'warning text-dark',
                                'Money Back' => 'success',
                                default => 'secondary'
                            };
                            $isPositive = ($cat === 'Deposit' || $cat === 'Money Back');
                            ?>
                            <span class="badge badge-modern bg-<?= $badgeClass ?>">
                                <?= $t['category'] ?>
                            </span>
                        </td>
                        <td class="<?= (stripos($t['category'] ?? '', 'Money') !== false || ($t['category'] ?? '') === 'Deposit') ? 'text-success' : 'text-danger' ?> fw-semibold">
                            <?= (stripos($t['category'] ?? '', 'Money') !== false || ($t['category'] ?? '') === 'Deposit') ? '+' : '−' ?><?= formatMoney(abs((float)$t['amount'])) ?>
                        </td>
                        <td>
                            <span class="badge badge-modern bg-<?= $sb ?> <?= $t['status']==='Pending'?'text-dark':'' ?>">
                                <?= $t['status'] ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= date('M d, Y h:i A', strtotime($t['requested_at'])) ?></td>
                        <td class="small text-muted"><?= clean($t['note'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Custom Modal Styles -->
<style>
.modal-overlay {
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
    backdrop-filter: blur(8px);
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content-custom {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 20px;
    padding: 0;
    max-width: 480px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.1);
    animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header-custom {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    padding: 24px 28px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
}

.modal-header-custom.withdraw {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.modal-title-custom {
    font-size: 20px;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.modal-title-custom i {
    font-size: 24px;
}

.modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 18px;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.modal-body-custom {
    padding: 28px;
    background: #ffffff;
}

.modal-footer-custom {
    padding: 20px 28px 28px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.info-box {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border: 1px solid #93c5fd;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
}

.info-box.withdraw {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 1px solid #fbbf24;
}

.info-box.danger {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border: 1px solid #f87171;
}

.info-box i {
    color: #1e40af;
    margin-right: 8px;
}

.info-box.withdraw i {
    color: #b45309;
}

.info-box.danger i {
    color: #b91c1c;
}

.info-box strong {
    color: #1e293b;
    font-weight: 600;
}

.form-label-custom {
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    display: block;
    font-size: 14px;
}

.input-group-custom {
    position: relative;
    display: flex;
    align-items: stretch;
}

.input-group-text-custom {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border: 2px solid #d1d5db;
    border-right: none;
    border-radius: 12px 0 0 12px;
    padding: 12px 16px;
    color: #6b7280;
    font-weight: 600;
    display: flex;
    align-items: center;
    font-size: 16px;
}

.form-control-custom {
    border: 2px solid #d1d5db;
    border-left: none;
    border-radius: 0 12px 12px 0;
    padding: 12px 16px;
    font-size: 15px;
    transition: all 0.2s ease;
    background: #ffffff;
}

.form-control-custom:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.input-group-custom:focus-within .input-group-text-custom {
    border-color: #10b981;
}

.form-text-custom {
    font-size: 13px;
    color: #6b7280;
    margin-top: 6px;
    display: block;
}

.btn-custom {
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: inherit;
    min-width: 100px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-cancel {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    color: #6b7280;
    border: 2px solid #d1d5db;
}

.btn-cancel:hover {
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
    color: #374151;
    transform: translateY(-1px);
}

.btn-deposit {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
}

.btn-deposit:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.btn-withdraw {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(245, 158, 11, 0.3);
}

.btn-withdraw:hover {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
}

.form-control-custom.textarea {
    resize: vertical;
    min-height: 80px;
    border-radius: 12px;
    border: 2px solid #d1d5db;
}

.form-control-custom.textarea:focus {
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
}
</style>

<!-- Deposit Modal -->
<div id="depositModal" class="modal-overlay">
    <div class="modal-content-custom">
        <div class="modal-header-custom">
            <h3 class="modal-title-custom">
                <i class="bi bi-plus-circle-fill"></i>
                Deposit to Savings
            </h3>
            <button type="button" class="modal-close" onclick="closeModal('depositModal')">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <form method="POST" action="deposit_process.php">
            <div class="modal-body-custom">
                <div class="info-box">
                    <i class="bi bi-info-circle-fill"></i>
                    <strong>Deposit Limits:</strong> Min ₱100 • Max ₱1,000 per transaction • Savings cap ₱100,000
                </div>
                
                <div class="mb-3">
                    <label class="form-label-custom">Amount <span style="color: #ef4444;">*</span></label>
                    <div class="input-group-custom">
                        <span class="input-group-text-custom">₱</span>
                        <input type="number" name="amount" class="form-control-custom"
                            min="100" max="1000" step="1" placeholder="100 – 1,000" required>
                    </div>
                    <div class="form-text-custom">Current balance: <?= formatMoney($balance) ?></div>
                </div>
            </div>
            <div class="modal-footer-custom">
                <button type="button" class="btn-custom btn-cancel" onclick="closeModal('depositModal')">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button type="submit" class="btn-custom btn-deposit">
                    <i class="bi bi-plus-circle"></i> Deposit
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Withdrawal Request Modal -->
<div id="withdrawModal" class="modal-overlay">
    <div class="modal-content-custom">
        <div class="modal-header-custom withdraw">
            <h3 class="modal-title-custom">
                <i class="bi bi-arrow-up-circle-fill"></i>
                Request Withdrawal
            </h3>
            <button type="button" class="modal-close" onclick="closeModal('withdrawModal')">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <form method="POST" action="withdraw_process.php">
            <div class="modal-body-custom">
                <div class="info-box withdraw">
                    <i class="bi bi-info-circle-fill"></i>
                    <strong>Withdrawal Limits:</strong> Min ₱500 • Max ₱5,000 per day • Max 5 withdrawals daily
                    <br><small style="color: #92400e;">Admin will review and process your request</small>
                </div>
                
                <?php if ($withdrawalsToday >= 5): ?>
                    <div class="info-box danger">
                        <i class="bi bi-x-circle-fill"></i>
                        <strong>Daily Limit Reached</strong><br>
                        You have reached the maximum of 5 withdrawals today.
                    </div>
                <?php elseif ($withdrawnToday >= 5000): ?>
                    <div class="info-box danger">
                        <i class="bi bi-x-circle-fill"></i>
                        <strong>Amount Limit Reached</strong><br>
                        You have reached the maximum withdrawal amount of ₱5,000 today.
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label-custom">Amount <span style="color: #ef4444;">*</span></label>
                        <div class="input-group-custom">
                            <span class="input-group-text-custom">₱</span>
                            <input type="number" name="amount" class="form-control-custom"
                                min="500" max="<?= min(5000, $balance, 5000 - $withdrawnToday) ?>"
                                step="1" placeholder="500 – 5,000" required>
                        </div>
                        <div class="form-text-custom">
                            Available balance: <?= formatMoney($balance) ?> • 
                            Remaining daily limit: <?= formatMoney(max(0, 5000 - $withdrawnToday)) ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label-custom">Note <span style="color: #9ca3af; font-size: 12px;">(optional)</span></label>
                        <textarea name="note" class="form-control-custom textarea" rows="2"
                            placeholder="Reason for withdrawal..."></textarea>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer-custom">
                <button type="button" class="btn-custom btn-cancel" onclick="closeModal('withdrawModal')">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <?php if ($withdrawalsToday < 5 && $withdrawnToday < 5000): ?>
                    <button type="submit" class="btn-custom btn-withdraw">
                        <i class="bi bi-arrow-up-circle"></i> Submit Request
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Update button click handlers to use custom modals
document.addEventListener('DOMContentLoaded', function() {
    // Remove Bootstrap modal triggers and add custom ones
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(function(trigger) {
        trigger.removeAttribute('data-bs-toggle');
        trigger.removeAttribute('data-bs-target');
        
        if (trigger.textContent.includes('Deposit')) {
            trigger.setAttribute('onclick', "document.getElementById('depositModal').style.display = 'flex'");
        } else if (trigger.textContent.includes('Withdrawal')) {
            trigger.setAttribute('onclick', "document.getElementById('withdrawModal').style.display = 'flex'");
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>