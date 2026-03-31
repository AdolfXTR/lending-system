<?php
// ============================================================
//  admin/registrations/reject.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(APP_URL . '/admin/registrations/index.php');

$id     = (int)($_POST['id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if (!$id || !$reason) {
    setFlash('error', 'Missing required fields.');
    redirect(APP_URL . '/admin/registrations/index.php');
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND status = 'Pending' LIMIT 1");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'User not found or already processed.');
    redirect(APP_URL . '/admin/registrations/index.php');
}

// Mark as Rejected with auto-delete date (30 days from now)
$deleteAt = date('Y-m-d H:i:s', strtotime('+30 days'));
$pdo->prepare("UPDATE users SET status = 'Rejected', rejection_reason = ?, delete_at = ? WHERE id = ?")
    ->execute([$reason, $deleteAt, $id]);

// Send email notification
sendMail(
    $user['email'],
    $user['first_name'] . ' ' . $user['last_name'],
    'Your Application Was Not Approved',
    "
    <p>Dear {$user['first_name']},</p>
    <p>We regret to inform you that your registration application for <strong>" . APP_NAME . "</strong> has been <strong style='color:red;'>rejected</strong>.</p>
    <p><strong>Reason:</strong> " . nl2br(htmlspecialchars($reason)) . "</p>
    <p>If you believe this is an error or would like to reapply with corrected documents, please contact us.</p>
    <p>Thank you.</p>
    "
);

setFlash('success', 'Application rejected. The applicant has been notified via email.');
redirect(APP_URL . '/admin/registrations/index.php');