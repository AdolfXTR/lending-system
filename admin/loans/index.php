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

// Get summary counts
$pendingCount = $pdo->query("SELECT COUNT(*) FROM loans WHERE status='Pending'")->fetchColumn();
$activeCount = $pdo->query("SELECT COUNT(*) FROM loans WHERE status='Active'")->fetchColumn();
$completedCount = $pdo->query("SELECT COUNT(*) FROM loans WHERE status='Completed'")->fetchColumn();
$rejectedCount = $pdo->query("SELECT COUNT(*) FROM loans WHERE status='Rejected'")->fetchColumn();

// Calculate total active loans amount
$totalActiveAmount = (float)$pdo->query("SELECT COALESCE(SUM(applied_amount), 0) FROM loans WHERE status='Active'")->fetchColumn();

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<style>
/* Enhanced Loans List Styles */
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
.stat-card.pending::after { background: #f59e0b; }
.stat-card.active::after { background: #10b981; }
.stat-card.completed::after { background: #6b7280; }
.stat-card.rejected::after { background: #dc2626; }

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
.stat-card.pending .stat-value { color: #f59e0b; }
.stat-card.active .stat-value { color: #10b981; }
.stat-card.completed .stat-value { color: #6b7280; }
.stat-card.rejected .stat-value { color: #dc2626; }

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

/* Summary footer */
.summary-footer {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-top: 1px solid #e2e8f0;
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.summary-footer .total-label {
    font-weight: 600;
    color: #64748b;
}
.summary-footer .total-value {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 48px 24px;
    color: #9ca3af;
}
.empty-state-icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}
.empty-state-text {
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 4px;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-cash-coin"></i> Loan Applications</h4>
</div>

<!-- Summary Stats Row -->
<div class="stat-grid">
    <div class="stat-card pending">
        <div class="stat-label">Pending</div>
        <div class="stat-value"><?= $pendingCount ?></div>
    </div>
    <div class="stat-card active">
        <div class="stat-label">Active</div>
        <div class="stat-value"><?= $activeCount ?></div>
    </div>
    <div class="stat-card completed">
        <div class="stat-label">Completed</div>
        <div class="stat-value"><?= $completedCount ?></div>
    </div>
    <div class="stat-card rejected">
        <div class="stat-label">Rejected</div>
        <div class="stat-value"><?= $rejectedCount ?></div>
    </div>
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
        <table class="table table-hover mb-0 data-table">
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
                    <tr><td colspan="7" class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <div class="empty-state-text">No records found</div>
                    </td></tr>
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
                            <div class="d-flex align-items-center">
                                <div class="user-avatar">
                                    <?= strtoupper(substr($l['first_name'],0,1).substr($l['last_name'],0,1)) ?>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?= clean($l['first_name'] . ' ' . $l['last_name']) ?></div>
                                    <div class="text-muted small"><?= clean($l['email']) ?></div>
                                </div>
                            </div>
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
                            <a href="view.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye"></i> Review
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($filter === 'Active' || $filter === 'All'): ?>
    <div class="summary-footer">
        <span class="total-label"><i class="bi bi-cash-stack me-2"></i>Total Active Loans</span>
        <span class="total-value text-success"><?= formatMoney($totalActiveAmount) ?></span>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>