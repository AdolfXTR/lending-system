<?php
// ============================================================
//  includes/session_check.php
//  Add at the top of every USER protected page
//  Usage: require_once __DIR__ . '/../../includes/session_check.php';
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

if (!isLoggedIn()) {
    setFlash('danger', 'Please login to continue.');
    redirect('auth/login.php');
}

// Always re-fetch user's current account_type and status from database
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT account_type, status FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Update session with fresh data from database
        $_SESSION['account_type'] = $user['account_type'];
        $_SESSION['status'] = $user['status'];
        
        // Check status with fresh data
        if ($user['status'] === 'Disabled') {
            session_destroy();
            redirect('auth/login.php?err=disabled');
        }
        
        if ($user['status'] === 'Pending') {
            session_destroy();
            redirect('auth/login.php?err=pending');
        }
        
        if ($user['status'] === 'Rejected') {
            session_destroy();
            redirect('auth/login.php?err=rejected');
        }
    } else {
        // User not found in database, destroy session
        session_destroy();
        redirect('auth/login.php');
    }
}