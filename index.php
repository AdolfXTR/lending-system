<?php
// ============================================================
//  index.php — root redirect
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

if (isAdmin()) {
    header('Location: ' . APP_URL . '/admin/dashboard.php');
    exit;
}

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/user/dashboard.php');
    exit;
}

header('Location: ' . APP_URL . '/auth/login.php');
exit;