<?php
// ============================================================
//  user/savings/withdraw_process.php
// ============================================================
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../helpers.php';

if (($_SESSION['account_type'] ?? null) !== 'Premium') {
    header('Location: ' . APP_URL . '/user/dashboard.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/user/savings/index.php'); exit;
}

$userId = $_SESSION['user_id'];
$amount = (float)($_POST['amount'] ?? 0);
$note   = trim($_POST['note'] ?? '');

// Validate amount
if ($amount < 500 || $amount > 5000) {
    setFlash('danger', 'Withdrawal amount must be between ₱500 and ₱5,000.');
    header('Location: ' . APP_URL . '/user/savings/index.php'); exit;
}

// Check daily withdrawal count
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM savings_transactions 
    WHERE user_id = ? AND category = 'Withdrawal' 
    AND DATE(requested_at) = CURDATE()
    AND status IN ('Pending','Completed')
");
$stmt->execute([$userId]);
if ((int)$stmt->fetchColumn() >= 5) {
    setFlash('danger', 'You have reached the maximum of 5 withdrawals today.');
    header('Location: ' . APP_URL . '/user/savings/index.php'); exit;
}

// Check daily withdrawal amount
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount),0) FROM savings_transactions 
    WHERE user_id = ? AND category = 'Withdrawal' 
    AND DATE(requested_at) = CURDATE()
    AND status IN ('Pending','Completed')
");
$stmt->execute([$userId]);
$withdrawnToday = (float)$stmt->fetchColumn();

if ($withdrawnToday + $amount > 5000) {
    $remaining = 5000 - $withdrawnToday;
    setFlash('danger', 'Daily withdrawal limit reached. You can only withdraw up to ' . formatMoney($remaining) . ' more today.');
    header('Location: ' . APP_URL . '/user/savings/index.php'); exit;
}

// Check balance
$stmt = $pdo->prepare("SELECT balance FROM savings WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$savings = $stmt->fetch();
$balance = $savings ? (float)$savings['balance'] : 0;

if ($amount > $balance) {
    setFlash('danger', 'Insufficient balance. Your current balance is ' . formatMoney($balance) . '.');
    header('Location: ' . APP_URL . '/user/savings/index.php'); exit;
}

// Insert withdrawal request (Pending — admin must approve)
$txnId = generateTransactionId('WD');
$no    = getNextNo($pdo, 'savings_transactions');

$stmt = $pdo->prepare("
    INSERT INTO savings_transactions (no, transaction_id, user_id, category, amount, status, note, requested_at)
    VALUES (?, ?, ?, 'Withdrawal', ?, 'Pending', ?, NOW())
");
$stmt->execute([$no, $txnId, $userId, $amount, $note ?: null]);

setFlash('success', 'Withdrawal request of ' . formatMoney($amount) . ' submitted! Admin will process it shortly.');
header('Location: ' . APP_URL . '/user/savings/index.php');
exit;