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

// Get summary counts for stat cards
$totalCount = $pdo->query("SELECT COUNT(*) FROM billing")->fetchColumn();
$pendingCount = $pdo->query("SELECT COUNT(*) FROM billing WHERE status = 'Pending'")->fetchColumn();
$completedCount = $pdo->query("SELECT COUNT(*) FROM billing WHERE status = 'Completed'")->fetchColumn();

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<style>
/* Stat Cards */
.stat-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media (max-width: 768px) {
    .stat-grid { grid-template-columns: repeat(2, 1fr); }
}

.stat-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,0.07);
    padding: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    transition: transform 0.15s, box-shadow 0.15s;
    position: relative;
    overflow: hidden;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.09);
}
.stat-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
}
.stat-card.total::after { background: #6366f1; }
.stat-card.pending::after { background: #f59e0b; }
.stat-card.completed::after { background: #10b981; }
.stat-card.overdue::after { background: #dc2626; }

.stat-label {
    font-size: 11px;
    color: #9ca3af;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}
.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
}
.stat-card.total .stat-value { color: #6366f1; }
.stat-card.pending .stat-value { color: #f59e0b; }
.stat-card.completed .stat-value { color: #10b981; }
.stat-card.overdue .stat-value { color: #dc2626; }

/* Amber text for Interest */
.text-amber { color: #f59e0b !important; }

/* Avatar */
.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 600;
    margin-right: 10px;
    flex-shrink: 0;
}

/* Row hover highlight */
.data-table tbody tr {
    transition: background-color 0.15s;
}
.data-table tbody tr:hover {
    background-color: #f8fafc;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-receipt"></i> Billing Management</h4>
    <?php if ($overdueCount > 0): ?>
    <span class="badge bg-danger fs-6">
        <?= $overdueCount ?> overdue bill<?= $overdueCount > 1 ? 's' : '' ?>
    </span>
    <?php endif; ?>
</div>

<!-- Summary Stats Row -->
<div class="stat-grid">
    <div class="stat-card total">
        <div class="stat-label">Total Bills</div>
        <div class="stat-value"><?= $totalCount ?></div>
    </div>
    <div class="stat-card pending">
        <div class="stat-label">Pending</div>
        <div class="stat-value"><?= $pendingCount ?></div>
    </div>
    <div class="stat-card completed">
        <div class="stat-label">Completed</div>
        <div class="stat-value"><?= $completedCount ?></div>
    </div>
    <div class="stat-card overdue">
        <div class="stat-label">Overdue</div>
        <div class="stat-value"><?= $overdueCount ?></div>
    </div>
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
        <table class="table table-hover mb-0 data-table">
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
                            <div class="d-flex align-items-center">
                                <div class="user-avatar">
                                    <?= strtoupper(substr($b['first_name'],0,1).substr($b['last_name'],0,1)) ?>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?= clean($b['first_name'] . ' ' . $b['last_name']) ?></div>
                                    <div class="text-muted small"><?= clean($b['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="small"><?= formatMoney($b['applied_amount']) ?></td>
                        <td class="small">Month <?= $b['month_number'] ?></td>
                        <td><?= formatMoney($b['amount_due']) ?></td>
                        <td class="text-amber small"><?= $b['interest'] > 0 ? formatMoney($b['interest']) : formatMoney(0) ?></td>
                        <td class="text-amber small"><?= $b['penalty'] > 0 ? formatMoney($b['penalty']) : formatMoney(0) ?></td>
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