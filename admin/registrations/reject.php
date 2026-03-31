<?php
// ============================================================
//  admin/registrations/reject.php
// ============================================================
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers.php';

// Check if admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    die("Not authorized");
}

// Get data from POST or GET
$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$reason = trim($_POST['reason'] ?? $_GET['reason'] ?? '');

// Validate
if (!$id) {
    $_SESSION['flash_error'] = "Missing ID";
    header("Location: " . APP_URL . "/admin/registrations/index.php");
    exit;
}

if (!$reason) {
    $reason = "Rejected by admin";
}

// Update user status to Rejected
try {
    $stmt = $pdo->prepare("UPDATE users SET status = 'Rejected' WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['flash_success'] = "Application rejected successfully";
} catch (Exception $e) {
    $_SESSION['flash_error'] = "Error: " . $e->getMessage();
}

header("Location: " . APP_URL . "/admin/registrations/index.php");
exit;
?>