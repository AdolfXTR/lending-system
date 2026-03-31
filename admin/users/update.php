<?php
// ============================================================
//  admin/users/update.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(APP_URL . '/admin/users/index.php');

$id           = (int)($_POST['id'] ?? 0);
$accountType  = $_POST['account_type'] ?? '';
$status       = $_POST['status'] ?? '';

$allowedTypes    = ['Basic', 'Premium'];
$allowedStatuses = ['Active', 'Disabled', 'Pending'];

if (!$id || !in_array($accountType, $allowedTypes) || !in_array($status, $allowedStatuses)) {
    setFlash('error', 'Invalid data submitted.');
    redirect(APP_URL . '/admin/users/index.php');
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'User not found.');
    redirect(APP_URL . '/admin/users/index.php');
}

// Check Premium slot limit if upgrading to Premium
if ($accountType === 'Premium' && $user['account_type'] === 'Basic') {
    $premiumCount = $pdo->query("SELECT COUNT(*) FROM users WHERE account_type = 'Premium' AND status = 'Active'")->fetchColumn();
    if ($premiumCount >= PREMIUM_MAX_SLOTS) {
        setFlash('error', 'Premium slots are full (' . PREMIUM_MAX_SLOTS . ' max). Cannot upgrade this user.');
        redirect(APP_URL . '/admin/users/view.php?id=' . $id);
    }
}

// Update user
$pdo->prepare("UPDATE users SET account_type = ?, status = ? WHERE id = ?")
    ->execute([$accountType, $status, $id]);

// If upgraded to Premium, create savings record if not exists
if ($accountType === 'Premium') {
    $exists = $pdo->prepare("SELECT id FROM savings WHERE user_id = ? LIMIT 1");
    $exists->execute([$id]);
    if (!$exists->fetch()) {
        $pdo->prepare("INSERT INTO savings (user_id, balance) VALUES (?, 0.00)")->execute([$id]);
    }
}

setFlash('success', 'Account updated successfully.');
redirect(APP_URL . '/admin/users/view.php?id=' . $id);