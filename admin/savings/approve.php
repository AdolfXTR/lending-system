<?php
// ============================================================
//  admin/savings/approve.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(APP_URL . '/admin/savings/index.php');

$id = (int)($_POST['id'] ?? 0);
if (!$id) redirect(APP_URL . '/admin/savings/index.php');

$stmt = $pdo->prepare("
    SELECT st.*, u.email, u.first_name, u.last_name, s.balance, s.id as savings_id
    FROM savings_transactions st
    JOIN users u ON u.id = st.user_id
    JOIN savings s ON s.user_id = st.user_id
    WHERE st.id = ? AND st.status = 'Pending' AND st.category = 'Withdrawal'
    LIMIT 1
");
$stmt->execute([$id]);
$txn = $stmt->fetch();

if (!$txn) {
    setFlash('error', 'Transaction not found or already processed.');
    redirect(APP_URL . '/admin/savings/index.php');
}

// Check sufficient balance
if ($txn['balance'] < $txn['amount']) {
    setFlash('error', 'Insufficient balance. Cannot approve this withdrawal.');
    redirect(APP_URL . '/admin/savings/view.php?id=' . $id);
}

$now = date('Y-m-d H:i:s');

// Deduct from savings balance
$pdo->prepare("UPDATE savings SET balance = balance - ? WHERE id = ?")
    ->execute([$txn['amount'], $txn['savings_id']]);

// Mark transaction as Completed
$pdo->prepare("
    UPDATE savings_transactions
    SET status = 'Completed', processed_at = ?
    WHERE id = ?
")->execute([$now, $id]);

// Send email
sendMail(
    $txn['email'],
    $txn['first_name'] . ' ' . $txn['last_name'],
    'Withdrawal Request Approved',
    "
    <p>Dear {$txn['first_name']},</p>
    <p>Your withdrawal request has been <strong style='color:green;'>approved</strong>.</p>
    <table style='border-collapse:collapse; font-size:14px;'>
        <tr><td style='padding:6px; color:#666;'>Amount</td><td style='padding:6px;'><strong>₱" . number_format($txn['amount'], 2) . "</strong></td></tr>
        <tr><td style='padding:6px; color:#666;'>Transaction ID</td><td style='padding:6px;'>{$txn['transaction_id']}</td></tr>
    </table>
    <p style='margin-top:12px;'>The amount will be sent to your registered bank account within the day.</p>
    "
);

setFlash('success', 'Withdrawal approved. Member has been notified.');
redirect(APP_URL . '/admin/savings/index.php');