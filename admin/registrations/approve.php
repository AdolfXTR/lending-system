<?php
// admin/registrations/approve.php
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/admin/registrations/index.php'); exit;
}

$id = (int)($_POST['user_id'] ?? 0);
if (!$id) { setFlash('danger','Invalid user.'); header('Location: ' . APP_URL . '/admin/registrations/index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND status = 'Pending' LIMIT 1");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) { setFlash('danger','User not found or already processed.'); header('Location: ' . APP_URL . '/admin/registrations/index.php'); exit; }

try {
    $pdo->beginTransaction();

    // Approve + set verified tag
    $stmt = $pdo->prepare("UPDATE users SET status = 'Active', is_verified = 1 WHERE id = ?");
    $stmt->execute([$id]);

    // Create savings record for Premium
    if ($user['account_type'] === 'Premium') {
        $check = $pdo->prepare("SELECT id FROM savings WHERE user_id = ?");
        $check->execute([$id]);
        if (!$check->fetch()) {
            $pdo->prepare("INSERT INTO savings (user_id, balance, zero_since) VALUES (?, 0.00, NULL)")->execute([$id]);
        }
    }

    $pdo->commit();

    // Email notification
    $name = $user['first_name'] . ' ' . $user['last_name'];
    sendMail(
        $user['email'],
        'Your Account Has Been Approved — LendingSystem',
        "<p>Dear {$name},</p>
         <p>Congratulations! Your registration has been <strong>approved</strong>. Your account is now active and verified.</p>
         <p>You can now log in and apply for a loan.</p>
         <p>Account Type: <strong>{$user['account_type']}</strong></p>
         <p>Thank you for choosing LendingSystem!</p>"
    );

    setFlash('success', "Account approved and verified for {$name}.");
} catch (Exception $e) {
    $pdo->rollBack();
    setFlash('danger', 'Error: ' . $e->getMessage());
}

header('Location: ' . APP_URL . '/admin/registrations/index.php');
exit;