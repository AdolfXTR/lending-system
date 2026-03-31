<?php
// ============================================================
//  admin/loans/reject.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(APP_URL . '/admin/loans/index.php');

$id     = (int)($_POST['id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if (!$id || !$reason) {
    setFlash('error', 'Missing required fields.');
    redirect(APP_URL . '/admin/loans/index.php');
}

$stmt = $pdo->prepare("
    SELECT l.*, u.email, u.first_name, u.last_name
    FROM loans l
    JOIN users u ON u.id = l.user_id
    WHERE l.id = ? AND l.status = 'Pending'
    LIMIT 1
");
$stmt->execute([$id]);
$loan = $stmt->fetch();

if (!$loan) {
    setFlash('error', 'Loan not found or already processed.');
    redirect(APP_URL . '/admin/loans/index.php');
}

// Update loan status
$pdo->prepare("
    UPDATE loans SET status = 'Rejected', rejection_reason = ? WHERE id = ?
")->execute([$reason, $id]);

// Record in loan_transactions
$txnId = generateTransactionId();
$noVal = $pdo->query("SELECT COALESCE(MAX(no),0)+1 FROM loan_transactions")->fetchColumn();
$pdo->prepare("
    INSERT INTO loan_transactions
        (no, transaction_id, loan_id, user_id, type, amount, status, note, created_at)
    VALUES (?, ?, ?, ?, 'Application', ?, 'Rejected', ?, NOW())
")->execute([$noVal, $txnId, $id, $loan['user_id'], $loan['applied_amount'], $reason]);

// Send email
sendMail(
    $loan['email'],
    'Your Loan Application Was Rejected',
    "<p>Dear {$loan['first_name']} {$loan['last_name']},</p>
    <p>We regret to inform you that your loan application of <strong>₱" . number_format($loan['applied_amount'], 2) . "</strong> has been <strong style='color:red;'>rejected</strong>.</p>
    <p><strong>Reason:</strong> " . nl2br(htmlspecialchars($reason)) . "</p>
    <p>You may reapply once the issue has been resolved. If you have questions, please contact us.</p>"
);

setFlash('success', 'Loan rejected. Borrower has been notified via email.');
redirect(APP_URL . '/admin/loans/index.php');