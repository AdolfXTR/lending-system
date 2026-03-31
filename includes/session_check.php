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

if (($_SESSION['status'] ?? null) === 'Disabled') {
    session_destroy();
    redirect('auth/login.php?err=disabled');
}

if (($_SESSION['status'] ?? null) === 'Pending') {
    session_destroy();
    redirect('auth/login.php?err=pending');
}