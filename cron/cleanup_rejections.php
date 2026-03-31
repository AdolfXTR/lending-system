<?php
// ============================================================
//  cron/cleanup_rejections.php
//  Run daily — deletes rejected registrations after 30 days
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

$log = [];

// Find rejected users whose delete_at date has passed
$stmt = $pdo->query("
    SELECT id, first_name, last_name, email, delete_at
    FROM users
    WHERE status = 'Rejected'
    AND delete_at IS NOT NULL
    AND delete_at <= CURDATE()
");
$users = $stmt->fetchAll();

foreach ($users as $user) {
    // Delete uploaded files
    $stmt2 = $pdo->prepare("SELECT proof_of_billing, valid_id, coe FROM users WHERE id = ?");
    $stmt2->execute([$user['id']]);
    $files = $stmt2->fetch();

    if ($files) {
        foreach (['proof_of_billing', 'valid_id', 'coe'] as $field) {
            if (!empty($files[$field])) {
                $path = UPLOAD_PATH . $files[$field];
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }
    }

    // Delete user record
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user['id']]);
    $log[] = "Deleted rejected user ID {$user['id']} ({$user['first_name']} {$user['last_name']}) — was scheduled for deletion on {$user['delete_at']}.";
}

$log[] = "Cleanup complete. " . count($users) . " rejected registration(s) deleted.";

// Output log
echo "<pre>";
echo "=== CRON: cleanup_rejections.php ===\n";
echo "Run at: " . date('Y-m-d H:i:s') . "\n\n";
if (empty($users)) {
    echo "• No rejected registrations to clean up.\n";
} else {
    foreach ($log as $entry) {
        echo "• $entry\n";
    }
}
echo "\nDone.</pre>";