<?php
// ============================================================
//  includes/admin_check.php
//  Add at the top of every ADMIN protected page
//  Usage: require_once __DIR__ . '/../../includes/admin_check.php';
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

if (!isAdmin()) {
    setFlash('danger', 'Admin access only.');
    redirect('auth/login.php');
}