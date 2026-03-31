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

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-person-plus"></i> Registration Applications</h4>
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
        <table class="table table-hover mb-0">
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
                        <td colspan="7" class="text-center text-muted py-4">No records found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= clean($u['first_name'] . ' ' . $u['last_name']) ?></td>
                        <td class="small"><?= clean($u['email']) ?></td>
                        <td>
                            <span class="badge bg-<?= $u['account_type'] === 'Premium' ? 'success' : 'secondary' ?>">
                                <?= $u['account_type'] ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $sc = match($u['status']) {
                                'Pending'  => 'warning',
                                'Active'   => 'success',
                                'Disabled' => 'danger',
                                'Rejected' => 'danger',
                                default    => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $sc ?> text-<?= $u['status']==='Pending'?'dark':'' ?>">
                                <?= $u['status'] ?>
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