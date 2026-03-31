<?php
// ============================================================
//  cron/check_overdue.php
//  Run daily — marks overdue bills and disables accounts
//  that failed to pay after the interim period
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

$today = date('Y-m-d');
$log   = [];

// ── Step 1: Mark overdue bills ────────────────────────────────
$stmt = $pdo->prepare("
    UPDATE billing 
    SET status = 'Overdue',
        penalty = ROUND(total_due * 0.02, 2),
        total_due = total_due + ROUND(total_due * 0.02, 2)
    WHERE status = 'Pending'
    AND due_date < ?
");
$stmt->execute([$today]);
$overdueBills = $stmt->rowCount();
$log[] = "Marked $overdueBills bills as Overdue.";

// ── Step 2: Disable accounts with overdue loans ───────────────
// Find users who have loans with overdue bills older than 28 days
$stmt = $pdo->query("
    SELECT DISTINCT l.user_id
    FROM billing b
    JOIN loans l ON l.id = b.loan_id
    WHERE b.status = 'Overdue'
    AND b.due_date < DATE_SUB(CURDATE(), INTERVAL 28 DAY)
    AND l.status IN ('Active', 'Approved')
");
$usersToDisable = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($usersToDisable as $uid) {
    $pdo->prepare("UPDATE users SET status = 'Disabled' WHERE id = ? AND status = 'Active'")
        ->execute([$uid]);
    $log[] = "Disabled user ID: $uid due to overdue loan.";
}

// ── Step 3: Mark loans as overdue if all bills are overdue ────
$pdo->query("
    UPDATE loans l
    SET l.status = 'Overdue'
    WHERE l.status IN ('Active','Approved')
    AND NOT EXISTS (
        SELECT 1 FROM billing b 
        WHERE b.loan_id = l.id AND b.status != 'Overdue'
    )
    AND EXISTS (
        SELECT 1 FROM billing b 
        WHERE b.loan_id = l.id AND b.status = 'Overdue'
    )
");

$log[] = "Overdue check complete.";

// Output log
echo "<pre>";
echo "=== CRON: check_overdue.php ===\n";
echo "Run at: " . date('Y-m-d H:i:s') . "\n\n";
foreach ($log as $entry) {
    echo "• $entry\n";
}
echo "\nDone.</pre>";