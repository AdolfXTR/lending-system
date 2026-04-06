<?php
// ============================================================
//  user/savings/deposit_process.php
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

// Validate amount
if ($amount < 100 || $amount > 1000) {
    setFlash('danger', 'Deposit amount must be between ₱100 and ₱1,000.');
    header('Location: ' . APP_URL . '/user/savings/index.php'); exit;
}

// Get current balance
$stmt = $pdo->prepare("SELECT * FROM savings WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$savings = $stmt->fetch();
$balance = $savings ? (float)$savings['balance'] : 0;

// Check savings cap
if ($balance + $amount > SAVINGS_MAX) {
    $allowed = SAVINGS_MAX - $balance;
    setFlash('danger', 'Deposit exceeds savings limit of ₱100,000. You can only deposit up to ' . formatMoney($allowed) . ' more.');
    header('Location: ' . APP_URL . '/user/savings/index.php'); exit;
}

// Check for duplicate transaction today
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM savings_transactions 
    WHERE user_id = ? AND category = 'Deposit' AND DATE(requested_at) = CURDATE()
");
$stmt->execute([$userId]);
$alreadyExists = $stmt->fetchColumn() > 0;

if (!$alreadyExists) {
    // Generate transaction
    $txnId = generateTransactionId('SAV');
    $no    = getNextNo($pdo, 'savings_transactions');

    // Insert transaction record
    $stmt = $pdo->prepare("
        INSERT INTO savings_transactions (no, transaction_id, user_id, category, amount, status, requested_at, processed_at)
        VALUES (?, ?, ?, 'Deposit', ?, 'Completed', NOW(), NOW())
    ");
    $stmt->execute([$no, $txnId, $userId, $amount]);
}

// Update or create savings balance
if ($savings) {
    $stmt = $pdo->prepare("UPDATE savings SET balance = balance + ? WHERE user_id = ?");
    $stmt->execute([$amount, $userId]);
} else {
    $stmt = $pdo->prepare("INSERT INTO savings (user_id, balance) VALUES (?, ?)");
    $stmt->execute([$userId, $amount]);
}

setFlash('success', 'Successfully deposited ' . formatMoney($amount) . ' to your savings!');
header('Location: ' . APP_URL . '/user/savings/index.php');
exit;