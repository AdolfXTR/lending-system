<?php
// ============================================================
//  admin/registrations/block_email.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../helpers.php';

$pageTitle = 'Blocked Emails';

// Handle block
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['block'])) {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $note  = trim($_POST['note'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Invalid email address.');
    } else {
        $check = $pdo->prepare("SELECT id FROM blocked_emails WHERE email = ? LIMIT 1");
        $check->execute([$email]);
        if ($check->fetch()) {
            setFlash('error', 'Email is already blocked.');
        } else {
            $pdo->prepare("INSERT INTO blocked_emails (email, note, blocked_at) VALUES (?, ?, NOW())")
                ->execute([$email, $note]);
            setFlash('success', "Email <strong>{$email}</strong> has been blocked.");
        }
    }
    redirect(APP_URL . '/admin/registrations/block_email.php');
}

// Handle unblock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unblock'])) {
    $bid = (int)($_POST['bid'] ?? 0);
    if ($bid) {
        $pdo->prepare("DELETE FROM blocked_emails WHERE id = ?")->execute([$bid]);
        setFlash('success', 'Email has been unblocked.');
    }
    redirect(APP_URL . '/admin/registrations/block_email.php');
}

$blocked = $pdo->query("SELECT * FROM blocked_emails ORDER BY blocked_at DESC")->fetchAll();

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-slash-circle"></i> Blocked Emails</h4>
    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#blockModal">
        <i class="bi bi-plus"></i> Block an Email
    </button>
</div>

<?= showFlash() ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Email</th>
                    <th>Note</th>
                    <th>Blocked On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($blocked)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No blocked emails</td></tr>
                <?php else: ?>
                    <?php foreach ($blocked as $i => $b): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= clean($b['email']) ?></td>
                        <td class="text-muted small"><?= clean($b['note'] ?? '—') ?></td>
                        <td class="small text-muted"><?= date('M d, Y', strtotime($b['blocked_at'])) ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Unblock this email?')">
                                <input type="hidden" name="bid" value="<?= $b['id'] ?>">
                                <button name="unblock" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-unlock"></i> Unblock
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Block Modal -->
<div class="modal fade" id="blockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-slash-circle"></i> Block Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="e.g. person@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Note (optional)</label>
                        <input type="text" name="note" class="form-control" placeholder="Reason for blocking...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="block" class="btn btn-danger">Block Email</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>