<?php
// ============================================================
//  admin/savings/index.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../helpers.php';

$pageTitle = 'Savings Management';

$filter  = $_GET['filter'] ?? 'Pending';
$allowed = ['Pending', 'Completed', 'Failed', 'Rejected', 'All'];
if (!in_array($filter, $allowed)) $filter = 'Pending';

$category = $_GET['category'] ?? 'All';
$catAllowed = ['All', 'Deposit', 'Withdrawal'];
if (!in_array($category, $catAllowed)) $category = 'All';

$search = trim($_GET['search'] ?? '');

$conditions = [];
$params     = [];

if ($filter !== 'All') {
    $conditions[] = 'st.status = :status';
    $params[':status'] = $filter;
}
if ($category !== 'All') {
    $conditions[] = 'st.category = :category';
    $params[':category'] = $category;
}
if ($search) {
    $conditions[] = '(st.transaction_id LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$sql = "
    SELECT st.*, u.first_name, u.last_name, u.email, s.balance
    FROM savings_transactions st
    JOIN users u ON u.id = st.user_id
    JOIN savings s ON s.user_id = st.user_id
    $where
    ORDER BY st.requested_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Pending withdrawals count
$pendingCount = $pdo->query("SELECT COUNT(*) FROM savings_transactions WHERE category='Withdrawal' AND status='Pending'")->fetchColumn();

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-piggy-bank"></i> Savings Management</h4>
    <?php if ($pendingCount > 0): ?>
    <span class="badge bg-warning text-dark fs-6">
        <?= $pendingCount ?> pending withdrawal<?= $pendingCount > 1 ? 's' : '' ?>
    </span>
    <?php endif; ?>
</div>

<!-- Filter Tabs -->
<ul class="nav nav-tabs mb-3">
    <?php foreach (['Pending','Completed','Failed','Rejected','All'] as $tab): ?>
    <li class="nav-item">
        <a class="nav-link <?= $filter === $tab ? 'active' : '' ?>"
           href="?filter=<?= $tab ?>&category=<?= $category ?>&search=<?= urlencode($search) ?>">
            <?= $tab ?>
            <?php if ($tab === 'Pending'): ?>
                <span class="badge bg-warning text-dark ms-1"><?= $pendingCount ?></span>
            <?php endif; ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<!-- Search & Category Filter -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="filter" value="<?= $filter ?>">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="Search by transaction ID or name..."
                    value="<?= clean($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select form-select-sm">
                    <option value="All" <?= $category==='All'?'selected':'' ?>>All Categories</option>
                    <option value="Withdrawal" <?= $category==='Withdrawal'?'selected':'' ?>>Withdrawal Only</option>
                    <option value="Deposit" <?= $category==='Deposit'?'selected':'' ?>>Deposit Only</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
            <?php if ($search || $category !== 'All'): ?>
            <div class="col-md-2">
                <a href="?filter=<?= $filter ?>" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?= showFlash() ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>No.</th>
                    <th>Transaction ID</th>
                    <th>Member</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Current Balance</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No records found</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): ?>
                    <?php
                        $badge = match($t['status']) {
                            'Pending'   => 'warning',
                            'Completed' => 'success',
                            'Failed'    => 'danger',
                            'Rejected'  => 'secondary',
                            default     => 'secondary'
                        };
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $t['no'] ?></td>
                        <td class="small fw-semibold font-monospace"><?= clean($t['transaction_id']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= clean($t['first_name'] . ' ' . $t['last_name']) ?></div>
                            <div class="text-muted small"><?= clean($t['email']) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-<?= $t['category']==='Deposit'?'info':'warning text-dark' ?>">
                                <?= $t['category'] ?>
                            </span>
                        </td>
                        <td class="fw-semibold <?= $t['category']==='Deposit'?'text-success':'text-danger' ?>">
                            <?= $t['category']==='Deposit' ? '+' : '−' ?><?= formatMoney($t['amount']) ?>
                        </td>
                        <td><?= formatMoney($t['balance']) ?></td>
                        <td>
                            <span class="badge bg-<?= $badge ?> <?= $t['status']==='Pending'?'text-dark':'' ?>">
                                <?= $t['status'] ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= date('M d, Y', strtotime($t['requested_at'])) ?></td>
                        <td>
                            <?php if ($t['status'] === 'Pending' && $t['category'] === 'Withdrawal'): ?>
                            <a href="view.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Review
                            </a>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>