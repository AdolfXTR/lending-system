<?php
// ============================================================
//  cron/check_savings_zero.php
//  Run monthly — downgrades Premium users whose savings
//  balance has been 0 for 3 consecutive months
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

$log = [];

// Find Premium users where savings balance = 0
// and zero_since date is set and is >= 3 months ago
$stmt = $pdo->query("
    SELECT u.id, u.first_name, u.last_name, u.email, s.zero_since, s.balance
    FROM users u
    JOIN savings s ON s.user_id = u.id
    WHERE u.account_type = 'Premium'
    AND u.status = 'Active'
    AND s.balance = 0
    AND s.zero_since IS NOT NULL
    AND s.zero_since <= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
");
$users = $stmt->fetchAll();

foreach ($users as $user) {
    // Downgrade to Basic
    $pdo->prepare("UPDATE users SET account_type = 'Basic' WHERE id = ?")
        ->execute([$user['id']]);

    $log[] = "Downgraded user ID {$user['id']} ({$user['first_name']} {$user['last_name']}) to Basic — savings at 0 since {$user['zero_since']}.";
}

// ── Also: set zero_since for users whose balance just hit 0 ──
$pdo->query("
    UPDATE savings 
    SET zero_since = CURDATE()
    WHERE balance = 0 AND zero_since IS NULL
");

// ── Also: clear zero_since for users who deposited again ──────
$pdo->query("
    UPDATE savings 
    SET zero_since = NULL
    WHERE balance > 0 AND zero_since IS NOT NULL
");

$log[] = "Savings zero check complete. " . count($users) . " account(s) downgraded.";

// Output log
echo "<pre>";
echo "=== CRON: check_savings_zero.php ===\n";
echo "Run at: " . date('Y-m-d H:i:s') . "\n\n";
if (empty($users)) {
    echo "• No accounts to downgrade.\n";
} else {
    foreach ($log as $entry) {
        echo "• $entry\n";
    }
}
echo "\nDone.</pre>";