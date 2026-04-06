<?php
// ============================================================
//  user/loan/apply_process.php
// ============================================================
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/user/loan/apply.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Get user's current loan limit
$stmt = $pdo->prepare("SELECT loan_limit, max_term_months FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
$userLoanLimit = $user['loan_limit'] ?? 10000;
$userMaxTerm = $user['max_term_months'] ?? 12;

// Calculate total active/pending loan amount
$stmt = $pdo->prepare("SELECT COALESCE(SUM(applied_amount), 0) as total FROM loans WHERE user_id = ? AND status IN ('Pending','Approved','Active')");
$stmt->execute([$userId]);
$activeLoanTotal = (float)$stmt->fetchColumn();

$amount     = (int)($_POST['amount'] ?? 0);
$termMonths = (int)($_POST['term'] ?? $_POST['term_months'] ?? 0);
$errors     = [];

// Check if total loans would exceed user's limit
if (($activeLoanTotal + $amount) > $userLoanLimit) {
    $errors[] = "Total loan amount (including existing loans) cannot exceed your limit of ₱" . number_format($userLoanLimit) . ".";
}

// Validate amount — must be multiple of 1000, between 5000 and user's limit
if ($amount < 5000 || $amount > $userLoanLimit) {
    $errors[] = "Loan amount must be between ₱5,000 and ₱" . number_format($userLoanLimit) . ".";
}
if ($amount % 1000 !== 0) {
    $errors[] = "Loan amount must be in multiples of ₱1,000.";
}

// Validate term
$allowedTerms = [1, 3, 6, 12];
for ($t = 15; $t <= $userMaxTerm; $t += 3) $allowedTerms[] = $t;

if (!in_array($termMonths, $allowedTerms)) {
    $errors[] = "Invalid payment term selected.";
}

if (!empty($errors)) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => implode('<br>', $errors)];
    header('Location: ' . APP_URL . '/user/loan/apply.php');
    exit;
}

// Insert loan application
$txnId = generateTransactionId('LN');
$stmt = $pdo->prepare("
    INSERT INTO loans (user_id, applied_amount, term_months, status, created_at)
    VALUES (?, ?, ?, 'Pending', NOW())
");
$stmt->execute([$userId, $amount, $termMonths]);
$loanId = $pdo->lastInsertId();

// Check for duplicate transaction today with same loan_id, type, and amount
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM loan_transactions 
    WHERE loan_id = ? AND type = 'Loan Application' AND amount = ?
");
$stmt->execute([$loanId, $amount]);
$alreadyExists = $stmt->fetchColumn() > 0;

if (!$alreadyExists) {
    $txnId = generateTransactionId('LN');
    $no    = getNextNo($pdo, 'loan_transactions');
    $stmt = $pdo->prepare("
        INSERT INTO loan_transactions (no, transaction_id, loan_id, user_id, type, amount, status, created_at)
        VALUES (?, ?, ?, ?, 'Loan Application', ?, 'Pending', NOW())
    ");
    $stmt->execute([$no, $txnId, $loanId, $userId, $amount]);
}

setFlash('success', 'Your loan application has been submitted! Please wait for admin approval.');
header('Location: ' . APP_URL . '/user/loan/index.php');
exit;