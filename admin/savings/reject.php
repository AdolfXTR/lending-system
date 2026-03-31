<?php
// ============================================================
//  admin/savings/reject.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(APP_URL . '/admin/savings/index.php');

$id     = (int)($_POST['id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if (!$id || !$reason) {
    setFlash('error', 'Missing required fields.');
    redirect(APP_URL . '/admin/savings/index.php');
}

$stmt = $pdo->prepare("
    SELECT st.*, u.email, u.first_name, u.last_name
    FROM savings_transactions st
    JOIN users u ON u.id = st.user_id
    WHERE st.id = ? AND st.status = 'Pending' AND st.category = 'Withdrawal'
    LIMIT 1
");
$stmt->execute([$id]);
$txn = $stmt->fetch();

if (!$txn) {
    setFlash('error', 'Transaction not found or already processed.');
    redirect(APP_URL . '/admin/savings/index.php');
}

$now = date('Y-m-d H:i:s');

// Mark as Rejected with note
$pdo->prepare("
    UPDATE savings_transactions
    SET status = 'Rejected', note = ?, processed_at = ?
    WHERE id = ?
")->execute([$reason, $now, $id]);

// Send email
sendMail(
    $txn['email'],
    $txn['first_name'] . ' ' . $txn['last_name'],
    'Withdrawal Request Rejected',
    "
    <p>Dear {$txn['first_name']},</p>
    <p>Your withdrawal request of <strong>₱" . number_format($txn['amount'], 2) . "</strong> has been <strong style='color:red;'>rejected</strong>.</p>
    <p><strong>Reason:</strong> " . nl2br(htmlspecialchars($reason)) . "</p>
    <p>If you have questions, please contact us.</p>
    "
);

setFlash('success', 'Withdrawal rejected. Member has been notified.');
redirect(APP_URL . '/admin/savings/index.php');