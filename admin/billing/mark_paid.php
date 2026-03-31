<?php
// admin/billing/mark_paid.php
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/admin/billing/index.php'); exit;
}

$bill_id = (int)($_POST['id'] ?? 0);
if (!$bill_id) { setFlash('danger','Invalid billing record.'); header('Location: ' . APP_URL . '/admin/billing/index.php'); exit; }

// Fetch bill
$stmt = $pdo->prepare("SELECT b.*, u.first_name, u.last_name, u.email, u.loan_limit, u.max_term_months FROM billing b JOIN users u ON u.id = b.user_id WHERE b.id = ? AND b.status IN ('Pending','Overdue') LIMIT 1");
$stmt->execute([$bill_id]);
$bill = $stmt->fetch();

if (!$bill) { setFlash('danger','Bill not found or already paid.'); header('Location: ' . APP_URL . '/admin/billing/index.php'); exit; }

try {
    $pdo->beginTransaction();

    $now = date('Y-m-d H:i:s');

    // Mark this bill as completed
    $pdo->prepare("UPDATE billing SET status='Completed', paid_at=? WHERE id=?")->execute([$now, $bill_id]);

    // Check if ALL bills for this loan are now completed
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM billing WHERE loan_id=? AND status != 'Completed'");
    $stmt->execute([$bill['loan_id']]);
    $remaining = (int)$stmt->fetchColumn();

    $loanCompleted   = false;
    $limitIncreased  = false;
    $newLimit        = (float)$bill['loan_limit'];
    $newMaxTerm      = (int)$bill['max_term_months'];

    if ($remaining === 0) {
        // All bills paid — close the loan
        $pdo->prepare("UPDATE loans SET status='Completed' WHERE id=?")->execute([$bill['loan_id']]);
        $loanCompleted = true;

        // Check if all payments were on time (no Overdue bills)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM billing WHERE loan_id=? AND status='Overdue'");
        $stmt->execute([$bill['loan_id']]);
        $hadOverdue = (int)$stmt->fetchColumn();

        if ($hadOverdue === 0) {
            // All paid on time — increase loan limit and max term
            $currentLimit   = (float)$bill['loan_limit'];
            $currentMaxTerm = (int)$bill['max_term_months'];

            $newLimit   = min($currentLimit + 5000, 50000);
            $newMaxTerm = min($currentMaxTerm + 3, 32);

            if ($newLimit > $currentLimit || $newMaxTerm > $currentMaxTerm) {
                $limitIncreased = true;

                // Update user's loan limit and max term
                $pdo->prepare("UPDATE users SET loan_limit=?, max_term_months=? WHERE id=?")
                    ->execute([$newLimit, $newMaxTerm, $bill['user_id']]);

                // Record the increase in loan_transactions
                $txnId = generateTransactionId('LIM');
                $no    = getNextNo($pdo, 'loan_transactions');
                $pdo->prepare("
                    INSERT INTO loan_transactions (no, transaction_id, loan_id, user_id, type, amount, status, note, created_at)
                    VALUES (?, ?, ?, ?, 'Limit Increase', ?, 'Approved', ?, ?)
                ")->execute([
                    $no, $txnId, $bill['loan_id'], $bill['user_id'],
                    $newLimit,
                    "Loan limit increased to ₱" . number_format($newLimit, 2) . ". Max term increased to {$newMaxTerm} months.",
                    $now
                ]);
            }
        }
    }

    $pdo->commit();

    // Email notification
    $name = $bill['first_name'] . ' ' . $bill['last_name'];
    $msg  = "<p>Dear {$name},</p><p>Your payment for Month {$bill['month_number']} has been recorded as <strong>Completed</strong>.</p>";

    if ($loanCompleted) {
        $msg .= "<p>🎉 Congratulations! You have fully paid your loan.</p>";
        if ($limitIncreased) {
            $msg .= "<p>Your loan limit has been increased to <strong>₱" . number_format($newLimit, 2) . "</strong> and your maximum term is now <strong>{$newMaxTerm} months</strong>. Keep up the great payment record!</p>";
        }
    }

    sendMail($bill['email'], 'Payment Recorded — LendingSystem', $msg);

    $flashMsg = "Payment recorded for Month {$bill['month_number']}.";
    if ($loanCompleted) $flashMsg .= " Loan fully paid!";
    if ($limitIncreased) $flashMsg .= " Loan limit increased to ₱" . number_format($newLimit, 2) . "!";

    setFlash('success', $flashMsg);

} catch (Exception $e) {
    $pdo->rollBack();
    setFlash('danger', 'Error: ' . $e->getMessage());
}

header('Location: ' . APP_URL . '/admin/billing/index.php');
exit;