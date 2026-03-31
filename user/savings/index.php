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

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-piggy-bank text-success"></i> My Savings</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#depositModal">
            <i class="bi bi-plus-circle"></i> Deposit
        </button>
        <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#withdrawModal">
            <i class="bi bi-arrow-up-circle"></i> Request Withdrawal
        </button>
    </div>
</div>

<?= showFlash() ?>

<!-- Balance Card -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center p-4">
            <div class="text-muted small mb-1">Current Balance</div>
            <div class="fs-2 fw-bold text-success"><?= formatMoney($balance) ?></div>
            <div class="text-muted small mt-1">Max: <?= formatMoney(SAVINGS_MAX) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center p-4">
            <div class="text-muted small mb-1">Withdrawals Today</div>
            <div class="fs-2 fw-bold <?= $withdrawalsToday >= 5 ? 'text-danger' : 'text-primary' ?>">
                <?= $withdrawalsToday ?> / 5
            </div>
            <div class="text-muted small mt-1">Max 5 withdrawals per day</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center p-4">
            <div class="text-muted small mb-1">Withdrawn Today</div>
            <div class="fs-2 fw-bold <?= $withdrawnToday >= 5000 ? 'text-danger' : 'text-warning' ?>">
                <?= formatMoney($withdrawnToday) ?>
            </div>
            <div class="text-muted small mt-1">Max <?= formatMoney(SAVINGS_MAX_WITHDRAW_DAY) ?> per day</div>
        </div>
    </div>
</div>

<!-- Money Back Section -->
<?php if (!empty($moneyBackTransactions)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <div class="d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="bi bi-cash-stack text-success"></i> Money Back Earnings</span>
            <small class="text-muted">2% of company income distribution</small>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
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

<!-- Transactions -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="fw-bold"><i class="bi bi-list-ul text-muted"></i> Transactions</span>
            <form method="GET" class="d-flex gap-2 flex-wrap">
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="Search by Transaction ID" value="<?= clean($search) ?>" style="width:220px;">
                <select name="category" class="form-select form-select-sm" style="width:140px;">
                    <option value="">All</option>
                    <option value="Deposit" <?= $category==='Deposit'?'selected':'' ?>>Deposit Only</option>
                    <option value="Withdrawal" <?= $category==='Withdrawal'?'selected':'' ?>>Withdrawal Only</option>
                    <option value="Money Back" <?= $category==='Money Back'?'selected':'' ?>>Money Back Only</option>
                </select>
                <button class="btn btn-sm btn-primary">Filter</button>
                <?php if ($search || $category): ?>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
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
                        <td colspan="7" class="text-center py-5">
                            <div style="font-size:48px;margin-bottom:12px;">📭</div>
                            <div style="font-size:16px;font-weight:600;color:#6b7280;margin-bottom:6px;">No transactions yet</div>
                            <div style="font-size:13px;color:#9ca3af;">Start building your savings by making your first deposit!</div>
                            <div style="margin-top:16px;">
                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#depositModal">
                                    <i class="bi bi-plus-circle"></i> Make First Deposit
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
                            <span class="badge bg-<?= $badgeClass ?>">
                                <?= $t['category'] ?>
                            </span>
                        </td>
                        <td class="<?= (stripos($t['category'] ?? '', 'Money') !== false || ($t['category'] ?? '') === 'Deposit') ? 'text-success' : 'text-danger' ?> fw-semibold">
                            <?php 
                            $catDebug = $t['category'] ?? 'NULL'; 
                            // Debug: show actual value
                            ?>
                            <?= (stripos($t['category'] ?? '', 'Money') !== false || ($t['category'] ?? '') === 'Deposit') ? '+' : '−' ?><?= formatMoney(abs((float)$t['amount'])) ?>
                            <!-- DEBUG: category=[<?= htmlspecialchars($catDebug) ?>] -->
                        </td>
                        <td>
                            <span class="badge bg-<?= $sb ?> <?= $t['status']==='Pending'?'text-dark':'' ?>">
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

<!-- Deposit Modal -->
<div class="modal fade" id="depositModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="deposit_process.php">
                <div class="modal-header">
                    <h5 class="modal-title text-success"><i class="bi bi-plus-circle"></i> Deposit to Savings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle"></i>
                        Min: <strong>₱100</strong> per transaction &nbsp;|&nbsp;
                        Max: <strong>₱1,000</strong> per transaction &nbsp;|&nbsp;
                        Savings cap: <strong>₱100,000</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" name="amount" class="form-control"
                                min="100" max="1000" step="1" placeholder="100 – 1,000" required>
                        </div>
                        <div class="form-text">Current balance: <?= formatMoney($balance) ?></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Deposit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Withdrawal Request Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="withdraw_process.php">
                <div class="modal-header">
                    <h5 class="modal-title text-warning"><i class="bi bi-arrow-up-circle"></i> Request Withdrawal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        <i class="bi bi-info-circle"></i>
                        Min: <strong>₱500</strong> &nbsp;|&nbsp;
                        Max: <strong>₱5,000</strong> per day &nbsp;|&nbsp;
                        Limit: <strong>5 withdrawals</strong> per day<br>
                        Admin will review and process your request.
                    </div>
                    <?php if ($withdrawalsToday >= 5): ?>
                        <div class="alert alert-danger small mb-0">
                            <i class="bi bi-x-circle"></i> You have reached the maximum of 5 withdrawals today.
                        </div>
                    <?php elseif ($withdrawnToday >= 5000): ?>
                        <div class="alert alert-danger small mb-0">
                            <i class="bi bi-x-circle"></i> You have reached the maximum withdrawal amount of ₱5,000 today.
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" name="amount" class="form-control"
                                    min="500" max="<?= min(5000, $balance, 5000 - $withdrawnToday) ?>"
                                    step="1" placeholder="500 – 5,000" required>
                            </div>
                            <div class="form-text">
                                Available balance: <?= formatMoney($balance) ?> &nbsp;|&nbsp;
                                Remaining daily limit: <?= formatMoney(max(0, 5000 - $withdrawnToday)) ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Note <span class="text-muted small">(optional)</span></label>
                            <textarea name="note" class="form-control" rows="2"
                                placeholder="Reason for withdrawal..."></textarea>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <?php if ($withdrawalsToday < 5 && $withdrawnToday < 5000): ?>
                        <button type="submit" class="btn btn-warning">Submit Request</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>