<?php
// ============================================================
//  admin/registrations/index.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../helpers.php';

$pageTitle = 'Registration Applications';

// Filter
$filter = $_GET['filter'] ?? 'Pending';
$allowed = ['Pending', 'Active', 'Disabled', 'Rejected', 'All'];
if (!in_array($filter, $allowed)) $filter = 'Pending';

$where = $filter === 'All' ? '' : "WHERE u.status = :status";

$sql = "
    SELECT id, first_name, last_name, email, account_type, status, created_at
    FROM users u
    $where
    ORDER BY created_at DESC
";

$stmt = $pdo->prepare($sql);
if ($filter !== 'All') $stmt->bindValue(':status', $filter);
$stmt->execute();
$users = $stmt->fetchAll();

// Get summary counts
$totalCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status != 'Rejected'")->fetchColumn();
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Pending'")->fetchColumn();
$activeCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Active'")->fetchColumn();
$rejectedCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Rejected'")->fetchColumn();

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<style>
/* Enhanced Registration List Styles */
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
.stat-card.total::after { background: #3b82f6; }
.stat-card.pending::after { background: #f59e0b; }
.stat-card.active::after { background: #10b981; }
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
.stat-card.total .stat-value { color: #3b82f6; }
.stat-card.pending .stat-value { color: #f59e0b; }
.stat-card.active .stat-value { color: #10b981; }
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

/* Status Badges */
.badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-active { background: #d1fae5; color: #065f46; }
.badge-rejected { background: #fee2e2; color: #991b1b; }
.badge-disabled { background: #f3f4f6; color: #4b5563; }

/* Table Enhancements */
.data-table tbody tr {
    transition: background-color 0.15s;
}
.data-table tbody tr:hover {
    background-color: #f8fafc;
}

/* Empty State */
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
.empty-state-subtext {
    font-size: 13px;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-person-plus"></i> Registration Applications</h4>
</div>

<!-- Summary Stats Row -->
<div class="stat-grid">
    <div class="stat-card total">
        <div class="stat-label">Total</div>
        <div class="stat-value"><?= $totalCount ?></div>
    </div>
    <div class="stat-card pending">
        <div class="stat-label">Pending</div>
        <div class="stat-value"><?= $pendingCount ?></div>
    </div>
    <div class="stat-card active">
        <div class="stat-label">Active</div>
        <div class="stat-value"><?= $activeCount ?></div>
    </div>
    <div class="stat-card rejected">
        <div class="stat-label">Rejected</div>
        <div class="stat-value"><?= $rejectedCount ?></div>
    </div>
</div>

<!-- Filter Tabs -->
<ul class="nav nav-tabs mb-3">
    <?php foreach (['Pending','Active','Disabled','Rejected','All'] as $tab): ?>
    <li class="nav-item">
        <a class="nav-link <?= $filter === $tab ? 'active' : '' ?>"
           href="?filter=<?= $tab ?>">
            <?= $tab ?>
            <?php if ($tab === 'Pending'): ?>
                <span class="badge bg-warning text-dark ms-1">
                    <?= $pdo->query("SELECT COUNT(*) FROM users WHERE status='Pending'")->fetchColumn() ?>
                </span>
            <?php endif; ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<?= showFlash() ?>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['flash_success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['flash_error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 data-table">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Applied</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            <div class="empty-state-icon">📭</div>
                            <div class="empty-state-text">No records found</div>
                            <div class="empty-state-subtext">Try selecting a different filter tab</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar">
                                    <?= strtoupper(substr($u['first_name'],0,1).substr($u['last_name'],0,1)) ?>
                                </div>
                                <span class="fw-semibold"><?= clean($u['first_name'] . ' ' . $u['last_name']) ?></span>
                            </div>
                        </td>
                        <td class="small"><?= clean($u['email']) ?></td>
                        <td>
                            <span class="badge bg-<?= $u['account_type'] === 'Premium' ? 'success' : 'secondary' ?>">
                                <?= $u['account_type'] ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $badgeClass = match($u['status']) {
                                'Pending'  => 'badge-pending',
                                'Active'   => 'badge-active',
                                'Disabled' => 'badge-disabled',
                                'Rejected' => 'badge-rejected',
                                default    => 'badge-secondary'
                            };
                            $badgeIcon = match($u['status']) {
                                'Pending'  => '⏳',
                                'Active'   => '✓',
                                'Disabled' => '⊘',
                                'Rejected' => '✕',
                                default    => ''
                            };
                            ?>
                            <span class="badge-pill <?= $badgeClass ?>">
                                <?= $badgeIcon ?> <?= $u['status'] ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <a href="view.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary">
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