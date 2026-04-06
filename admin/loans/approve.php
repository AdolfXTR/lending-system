<?php
// ============================================================
//  admin/loans/approve.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/admin/loans/index.php');
    exit;
}

$loan_id = (int)($_POST['id'] ?? $_POST['loan_id'] ?? 0);
if (!$loan_id) {
    setFlash('danger', 'Invalid loan.');
    header('Location: ' . APP_URL . '/admin/loans/index.php');
    exit;
}

// Fetch loan + user
$stmt = $pdo->prepare("
    SELECT l.*, u.first_name, u.last_name, u.email, u.account_type
    FROM loans l
    JOIN users u ON u.id = l.user_id
    WHERE l.id = ? AND l.status = 'Pending'
");
$stmt->execute([$loan_id]);
$loan = $stmt->fetch();

if (!$loan) {
    setFlash('danger', 'Loan not found or already processed.');
    header('Location: ' . APP_URL . '/admin/loans/index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $now           = new DateTime();
    $approvedAt    = $now->format('Y-m-d H:i:s');
    $amount        = (float)$loan['applied_amount'];
    $interest      = round($amount * LOAN_INTEREST_RATE, 2);   // 3% upfront
    $received      = round($amount - $interest, 2);
    $term_months   = (int)$loan['term_months'];
    $monthly_due   = round($amount / $term_months, 2);

    // ── 1. Update loan status ────────────────────────────────
    $stmt = $pdo->prepare("
        UPDATE loans
        SET status          = 'Active',
            received_amount = ?,
            approved_at     = ?
        WHERE id = ?
    ");
    $stmt->execute([$received, $approvedAt, $loan_id]);

    // ── 2. Record approval in loan_transactions ──────────────
    // Check for duplicate transaction with same loan_id, type, and amount
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM loan_transactions 
        WHERE loan_id = ? AND type = 'Approval' AND amount = ?
    ");
    $stmt->execute([$loan_id, $amount]);
    $alreadyExists = $stmt->fetchColumn() > 0;
    
    if (!$alreadyExists) {
        $txnId = generateTransactionId('TXN');
        $no    = getNextNo($pdo, 'loan_transactions');
        $stmt  = $pdo->prepare("
            INSERT INTO loan_transactions
                (no, transaction_id, loan_id, user_id, type, amount, status, note, created_at)
            VALUES (?, ?, ?, ?, 'Approval', ?, 'Approved', 'Loan approved and released.', ?)
        ");
        $stmt->execute([$no, $txnId, $loan_id, $loan['user_id'], $amount, $approvedAt]);
    }

    // ── 3. Update original Loan Application transaction status ─────
    $pdo->prepare("
        UPDATE loan_transactions 
        SET status = 'Approved'
        WHERE loan_id = ? AND type = 'Loan Application'
    ")->execute([$loan_id]);

    // ── 4. Generate billing schedule (28-day intervals) ──────
    //   Month 1 due  = today + 28 days
    //   Month 2 due  = today + 56 days  (28 × 2)
    //   Month N due  = today + (28 × N) days
    for ($month = 1; $month <= $term_months; $month++) {
        $dueDate   = (clone $now)->modify('+' . (28 * $month) . ' days')->format('Y-m-d');

        // Interest (3%) only on Month 1
        $bill_interest = ($month === 1) ? $interest : 0.00;
        $total_due     = round($monthly_due + $bill_interest, 2);

        $stmt = $pdo->prepare("
            INSERT INTO billing
                (loan_id, user_id, month_number, amount_due, interest, penalty,
                 total_due, due_date, status, created_at)
            VALUES (?, ?, ?, ?, ?, 0, ?, ?, 'Pending', ?)
        ");
        $stmt->execute([
            $loan_id,
            $loan['user_id'],
            $month,
            $monthly_due,
            $bill_interest,
            $total_due,
            $dueDate,
            $approvedAt,
        ]);
    }

    $pdo->commit();

    // ── 4. Email notification ────────────────────────────────
    $name = $loan['first_name'] . ' ' . $loan['last_name'];
    sendMail(
        $loan['email'],
        'Your Loan Has Been Approved',
        "<p>Dear {$name},</p>
         <p>Your loan of <strong>&#8369; " . number_format($amount, 2) . "</strong> has been approved.</p>
         <p>You will receive <strong>&#8369; " . number_format($received, 2) . "</strong> (after 3% interest deduction).</p>
         <p>Your first payment of <strong>&#8369; " . number_format($monthly_due + $interest, 2) . "</strong>
            is due on <strong>" . (clone $now)->modify('+28 days')->format('F d, Y') . "</strong>.</p>
         <p>Please log in to your account to view your billing schedule.</p>"
    );

    setFlash('success', 'Loan approved. Billing schedule generated (' . $term_months . ' months, 28-day intervals).');

} catch (Exception $e) {
    $pdo->rollBack();
    setFlash('danger', 'Error: ' . $e->getMessage());
}

header('Location: ' . APP_URL . '/admin/loans/index.php');
exit;