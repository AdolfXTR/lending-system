<?php
// ============================================================
//  admin/users/index.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../helpers.php';

$pageTitle = 'Users Management';

$filter  = $_GET['filter'] ?? 'All';
$allowed = ['All', 'Active', 'Disabled'];
if (!in_array($filter, $allowed)) $filter = 'All';

$search = trim($_GET['search'] ?? '');

$conditions = ['status != :exclude1', 'status != :exclude2'];
$params     = [':exclude1' => 'Pending', ':exclude2' => 'Rejected'];

if ($filter !== 'All') {
    $conditions[] = 'status = :status';
    $params[':status'] = $filter;
}
if ($search) {
    $conditions[] = '(first_name LIKE :s OR last_name LIKE :s OR email LIKE :s OR username LIKE :s)';
    $params[':s'] = '%' . $search . '%';
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$users = $pdo->prepare("
    SELECT id, username, first_name, last_name, email, account_type, status, created_at
    FROM users
    $where
    ORDER BY created_at DESC
");
$users->execute($params);
$users = $users->fetchAll();

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-people"></i> Users Management</h4>
</div>

<!-- Filter Tabs -->
<ul class="nav nav-tabs mb-3">
    <?php foreach (['All','Active','Disabled'] as $tab): ?>
    <li class="nav-item">
        <a class="nav-link <?= $filter === $tab ? 'active' : '' ?>"
           href="?filter=<?= $tab ?>&search=<?= urlencode($search) ?>">
            <?= $tab ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<!-- Search -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="filter" value="<?= $filter ?>">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="Search by name, email or username..."
                    value="<?= clean($search) ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100">Search</button>
            </div>
            <?php if ($search): ?>
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
                    <th>#</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No users found</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $i => $u): ?>
                    <?php
                        $badge = match($u['status']) {
                            'Active'   => 'success',
                            'Disabled' => 'danger',
                            default    => 'secondary'
                        };
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold"><?= clean($u['first_name'] . ' ' . $u['last_name']) ?></div>
                            <div class="text-muted small"><?= clean($u['email']) ?></div>
                        </td>
                        <td class="small"><?= clean($u['username']) ?></td>
                        <td>
                            <span class="badge bg-<?= $u['account_type']==='Premium'?'success':'secondary' ?>">
                                <?= $u['account_type'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $badge ?>">
                                <?= $u['status'] ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <a href="view.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View
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