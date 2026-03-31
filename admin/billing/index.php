<?php
// ============================================================
//  admin/billing/index.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../helpers.php';

$pageTitle = 'Billing Management';

$filter  = $_GET['filter'] ?? 'All';
$allowed = ['All', 'Pending', 'Completed', 'Overdue'];
if (!in_array($filter, $allowed)) $filter = 'All';

$where  = $filter === 'All' ? '' : 'WHERE b.status = :status';
$params = [];
if ($filter !== 'All') $params[':status'] = $filter;

$sql = "
    SELECT b.*, u.first_name, u.last_name, u.email, l.applied_amount
    FROM billing b
    JOIN users u ON u.id = b.user_id
    JOIN loans l ON l.id = b.loan_id
    $where
    ORDER BY b.due_date ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bills = $stmt->fetchAll();

$overdueCount = $pdo->query("SELECT COUNT(*) FROM billing WHERE status = 'Overdue'")->fetchColumn();

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-receipt"></i> Billing Management</h4>
    <?php if ($overdueCount > 0): ?>
    <span class="badge bg-danger fs-6">
        <?= $overdueCount ?> overdue bill<?= $overdueCount > 1 ? 's' : '' ?>
    </span>
    <?php endif; ?>
</div>

<!-- Filter Tabs -->
<ul class="nav nav-tabs mb-3">
    <?php foreach (['All', 'Pending', 'Completed', 'Overdue'] as $tab): ?>
    <li class="nav-item">
        <a class="nav-link <?= $filter === $tab ? 'active' : '' ?>" href="?filter=<?= $tab ?>">
            <?= $tab ?>
            <?php if ($tab === 'Overdue' && $overdueCount > 0): ?>
                <span class="badge bg-danger ms-1"><?= $overdueCount ?></span>
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
                    <th>Loan Amount</th>
                    <th>Month</th>
                    <th>Amount Due</th>
                    <th>Interest</th>
                    <th>Penalty</th>
                    <th>Total Due</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bills)): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4">No records found</td></tr>
                <?php else: ?>
                    <?php foreach ($bills as $i => $b): ?>
                    <?php
                        $badge = match($b['status']) {
                            'Pending'   => 'warning',
                            'Completed' => 'success',
                            'Overdue'   => 'danger',
                            default     => 'secondary'
                        };
                        $isOverdue = $b['status'] === 'Overdue';
                    ?>
                    <tr class="<?= $isOverdue ? 'table-danger' : '' ?>">
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold"><?= clean($b['first_name'] . ' ' . $b['last_name']) ?></div>
                            <div class="text-muted small"><?= clean($b['email']) ?></div>
                        </td>
                        <td class="small"><?= formatMoney($b['applied_amount']) ?></td>
                        <td class="small">Month <?= $b['month_number'] ?></td>
                        <td><?= formatMoney($b['amount_due']) ?></td>
                        <td class="text-danger small"><?= $b['interest'] > 0 ? formatMoney($b['interest']) : '—' ?></td>
                        <td class="text-danger small"><?= $b['penalty'] > 0 ? formatMoney($b['penalty']) : '—' ?></td>
                        <td class="fw-bold"><?= formatMoney($b['total_due']) ?></td>
                        <td class="small <?= $isOverdue ? 'text-danger fw-bold' : 'text-muted' ?>">
                            <?= date('M d, Y', strtotime($b['due_date'])) ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $badge ?> <?= $b['status']==='Pending'?'text-dark':'' ?>">
                                <?= $b['status'] ?>
                            </span>
                        </td>
                        <td>
                            <a href="view.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
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