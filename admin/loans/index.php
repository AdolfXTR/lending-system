<?php
// ============================================================
//  admin/loans/index.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../helpers.php';

$pageTitle = 'Loan Applications';

$filter  = $_GET['filter'] ?? 'Pending';
$allowed = ['Pending', 'Approved', 'Active', 'Rejected', 'Completed', 'All'];
if (!in_array($filter, $allowed)) $filter = 'Pending';

$where = $filter === 'All' ? '' : 'WHERE l.status = :status';
$params = [];
if ($filter !== 'All') $params[':status'] = $filter;

$sql = "
    SELECT l.id, l.applied_amount, l.term_months, l.status, l.created_at,
           u.first_name, u.last_name, u.account_type, u.email
    FROM loans l
    JOIN users u ON u.id = l.user_id
    $where
    ORDER BY l.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$loans = $stmt->fetchAll();

$pendingCount = $pdo->query("SELECT COUNT(*) FROM loans WHERE status='Pending'")->fetchColumn();

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-cash-coin"></i> Loan Applications</h4>
</div>

<!-- Filter Tabs -->
<ul class="nav nav-tabs mb-3">
    <?php foreach (['Pending','Approved','Active','Rejected','Completed','All'] as $tab): ?>
    <li class="nav-item">
        <a class="nav-link <?= $filter === $tab ? 'active' : '' ?>" href="?filter=<?= $tab ?>">
            <?= $tab ?>
            <?php if ($tab === 'Pending' && $pendingCount > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?= $pendingCount ?></span>
            <?php endif; ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<?= showFlash() ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Borrower</th>
                    <th>Amount Applied</th>
                    <th>Term</th>
                    <th>Status</th>
                    <th>Applied On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($loans)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No records found</td></tr>
                <?php else: ?>
                    <?php foreach ($loans as $i => $l): ?>
                    <?php
                        $badge = match($l['status']) {
                            'Pending'   => 'warning',
                            'Approved'  => 'info',
                            'Active'    => 'success',
                            'Rejected'  => 'danger',
                            'Completed' => 'secondary',
                            default     => 'secondary'
                        };
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold"><?= clean($l['first_name'] . ' ' . $l['last_name']) ?></div>
                            <div class="text-muted small"><?= clean($l['email']) ?></div>
                        </td>
                        <td class="fw-semibold"><?= formatMoney($l['applied_amount']) ?></td>
                        <td><?= $l['term_months'] ?> month<?= $l['term_months'] > 1 ? 's' : '' ?></td>
                        <td>
                            <span class="badge bg-<?= $badge ?> <?= $l['status']==='Pending'?'text-dark':'' ?>">
                                <?= $l['status'] ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= date('M d, Y', strtotime($l['created_at'])) ?></td>
                        <td>
                            <a href="view.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Review
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>