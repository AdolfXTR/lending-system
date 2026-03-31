<?php
// user/billing/print.php  — printable billing statement (no external library needed)
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../helpers.php';

$uid     = $_SESSION['user_id'];
$bill_id = (int)($_GET['id'] ?? 0);

if (!$bill_id) { header('Location: ' . APP_URL . '/user/billing/index.php'); exit; }

$stmt = $pdo->prepare("
    SELECT b.*, l.applied_amount, l.received_amount, l.term_months,
           u.first_name, u.last_name, u.email, u.address, u.account_type,
           u.bank_name, u.bank_account_number, u.card_holder_name, u.is_verified
    FROM billing b
    JOIN loans l ON l.id = b.loan_id
    JOIN users u ON u.id = b.user_id
    WHERE b.id = ? AND b.user_id = ?
    LIMIT 1
");
$stmt->execute([$bill_id, $uid]);
$b = $stmt->fetch();

if (!$b) { header('Location: ' . APP_URL . '/user/billing/index.php'); exit; }

$generated = date('F d, Y \a\t h:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Billing Statement — Month <?= $b['month_number'] ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:#fff;color:#111827;font-size:13px;line-height:1.6;}
.page{max-width:680px;margin:0 auto;padding:40px;}

/* Header */
.doc-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px;padding-bottom:20px;border-bottom:2px solid #1B3B8B;}
.logo-box{display:flex;align-items:center;gap:12px;}
.logo-icon{width:44px;height:44px;background:#1B3B8B;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#FFD84D;font-size:18px;font-weight:800;}
.logo-name{font-size:18px;font-weight:800;color:#1B3B8B;}
.logo-sub{font-size:11px;color:#6B7280;}
.doc-meta{text-align:right;}
.doc-title{font-size:20px;font-weight:800;color:#1B3B8B;margin-bottom:4px;}
.doc-ref{font-size:11px;color:#6B7280;}

/* Status banner */
.status-banner{padding:12px 16px;border-radius:10px;margin-bottom:24px;display:flex;align-items:center;gap:10px;font-weight:700;font-size:13px;}
.status-completed{background:#DCFCE7;color:#15803D;}
.status-pending  {background:#FEF3C7;color:#D97706;}
.status-overdue  {background:#FEE2E2;color:#DC2626;}

/* Sections */
.section{margin-bottom:24px;}
.section-title{font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid #F0F0F0;}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 24px;}
.info-row{display:flex;flex-direction:column;gap:1px;}
.info-label{font-size:11px;color:#9CA3AF;}
.info-value{font-size:13px;font-weight:600;color:#111827;}
.verified-tag{display:inline-flex;align-items:center;gap:4px;background:#DCFCE7;color:#15803D;font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px;margin-left:6px;}

/* Breakdown table */
.breakdown{width:100%;border-collapse:collapse;}
.breakdown th{padding:8px 12px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.04em;background:#F9FAFB;border-bottom:1px solid #F0F0F0;}
.breakdown td{padding:10px 12px;border-bottom:1px solid #F9F9F9;font-size:13px;}
.breakdown tr:last-child td{border:none;}
.breakdown .total-row td{font-weight:800;font-size:15px;border-top:2px solid #1B3B8B;padding-top:14px;}
.breakdown .total-row td:last-child{color:#1B3B8B;}
.text-red{color:#DC2626;}
.text-muted{color:#9CA3AF;}

/* Total highlight */
.total-box{background:#1B3B8B;border-radius:10px;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;margin-top:16px;}
.total-box-label{color:rgba(255,255,255,.7);font-size:13px;}
.total-box-amount{color:#FFD84D;font-size:24px;font-weight:800;}

/* Footer */
.doc-footer{margin-top:32px;padding-top:16px;border-top:1px solid #F0F0F0;display:flex;justify-content:space-between;align-items:center;}
.footer-note{font-size:11px;color:#9CA3AF;max-width:380px;line-height:1.5;}
.footer-generated{font-size:11px;color:#9CA3AF;text-align:right;}

/* Print button */
.print-bar{background:#F5F6FA;border-bottom:1px solid #E5E7EB;padding:12px 40px;display:flex;justify-content:space-between;align-items:center;gap:10px;}
.print-bar a{font-size:13px;color:#1B3B8B;text-decoration:none;font-weight:600;}
.btn-group{display:flex;gap:8px;}
.btn-print, .btn-download{background:#1B3B8B;color:#FFD84D;border:none;border-radius:8px;padding:9px 20px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;}
.btn-print:hover, .btn-download:hover{background:#0f2557;}

@media print {
    .print-bar{display:none!important;}
    .page{padding:20px;}
}
</style>
</head>
<body>

<!-- Print bar (hidden when printing) -->
<div class="print-bar">
    <a href="<?= APP_URL ?>/user/billing/index.php">← Back to Billing</a>
    <div class="btn-group">
        <button class="btn-download" onclick="downloadPDF()">📥 Download PDF</button>
        <button class="btn-print" onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>
</div>

<div class="page">

    <!-- Header -->
    <div class="doc-header">
        <div class="logo-box">
            <div class="logo-icon">LS</div>
            <div>
                <div class="logo-name">LendingSystem</div>
                <div class="logo-sub">Official Billing Statement</div>
            </div>
        </div>
        <div class="doc-meta">
            <div class="doc-title">Billing Statement</div>
            <div class="doc-ref">Month <?= $b['month_number'] ?> of <?= $b['term_months'] ?></div>
            <div class="doc-ref">Due: <?= date('F d, Y', strtotime($b['due_date'])) ?></div>
        </div>
    </div>

    <!-- Status -->
    <?php
    $sc = match($b['status']) {
        'Completed' => 'status-completed',
        'Overdue'   => 'status-overdue',
        default     => 'status-pending',
    };
    $si = match($b['status']) {
        'Completed' => '✅',
        'Overdue'   => '⚠️',
        default     => '🕐',
    };
    ?>
    <div class="status-banner <?= $sc ?>">
        <?= $si ?> Payment Status: <?= $b['status'] ?>
        <?php if ($b['status'] === 'Completed' && $b['paid_at']): ?>
            — Paid on <?= date('F d, Y', strtotime($b['paid_at'])) ?>
        <?php endif; ?>
    </div>

    <!-- Borrower Info -->
    <div class="section">
        <div class="section-title">Borrower Details</div>
        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Full Name</span>
                <span class="info-value">
                    <?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?>
                    <?php if ($b['is_verified']): ?>
                        <span class="verified-tag">✓ Verified</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Email</span>
                <span class="info-value"><?= htmlspecialchars($b['email']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Account Type</span>
                <span class="info-value"><?= $b['account_type'] ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Address</span>
                <span class="info-value"><?= htmlspecialchars($b['address']) ?></span>
            </div>
        </div>
    </div>

    <!-- Loan Info -->
    <div class="section">
        <div class="section-title">Loan Information</div>
        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Date Generated</span>
                <span class="info-value"><?= date('F d, Y', strtotime($b['created_at'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Due Date</span>
                <span class="info-value"><?= date('F d, Y', strtotime($b['due_date'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Loaned Amount</span>
                <span class="info-value"><?= formatMoney($b['applied_amount']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Received Amount</span>
                <span class="info-value"><?= formatMoney($b['received_amount']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Payment Term</span>
                <span class="info-value"><?= $b['term_months'] ?> months</span>
            </div>
            <div class="info-row">
                <span class="info-label">Month #</span>
                <span class="info-value">Month <?= $b['month_number'] ?> of <?= $b['term_months'] ?></span>
            </div>
        </div>
    </div>

    <!-- Billing Breakdown -->
    <div class="section">
        <div class="section-title">Billing Breakdown</div>
        <table class="breakdown">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align:right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Amount to pay this month (without interest)</td>
                    <td style="text-align:right;font-weight:600;"><?= formatMoney($b['amount_due']) ?></td>
                </tr>
                <tr>
                    <td>
                        Interest (3% on full loan amount)
                        <?php if ($b['month_number'] > 1): ?>
                            <span class="text-muted" style="font-size:11px;"> — charged on Month 1 only</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;" class="<?= $b['interest'] > 0 ? 'text-red' : 'text-muted' ?>">
                        <?= $b['interest'] > 0 ? '+ ' . formatMoney($b['interest']) : '—' ?>
                    </td>
                </tr>
                <tr>
                    <td>Penalty (2% — late payment charge)</td>
                    <td style="text-align:right;" class="<?= $b['penalty'] > 0 ? 'text-red' : 'text-muted' ?>">
                        <?= $b['penalty'] > 0 ? '+ ' . formatMoney($b['penalty']) : '—' ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="total-box">
            <span class="total-box-label">Total Amount Due</span>
            <span class="total-box-amount"><?= formatMoney($b['total_due']) ?></span>
        </div>
    </div>

    <!-- Bank Details -->
    <div class="section">
        <div class="section-title">Payment Information</div>
        <div style="background:#F9FAFB;border-radius:10px;padding:14px 16px;">
            <div style="font-size:12px;color:#6B7280;margin-bottom:8px;font-weight:600;">Send payment to borrower's bank account on file:</div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Bank Name</span>
                    <span class="info-value"><?= htmlspecialchars($b['bank_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Account Number</span>
                    <span class="info-value"><?= htmlspecialchars($b['bank_account_number']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Account Name</span>
                    <span class="info-value"><?= htmlspecialchars($b['card_holder_name']) ?></span>
                </div>
            </div>
            <div style="margin-top:10px;font-size:11px;color:#9CA3AF;">Please coordinate with your loan officer to process payment via bank transfer. Keep your receipt as proof of payment.</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="doc-footer">
        <div class="footer-note">This is an official billing statement from LendingSystem. For questions or concerns, please contact your loan officer.</div>
        <div class="footer-generated">Generated on<br><?= $generated ?></div>
    </div>

</div>
</body>
<script>
function downloadPDF() {
    // Generate PDF name with month and date
    const month = <?= $b['month_number'] ?>;
    const dueDate = '<?= date('Y-m-d', strtotime($b['due_date'])) ?>';
    const filename = `Billing-Statement-Month-${month}-${dueDate}.pdf`;
    
    // Trigger print dialog with PDF save option
    // Note: User will click "Save as PDF" in the print dialog
    const printWindow = window.open('', '', 'width=900,height=700');
    printWindow.document.write(document.documentElement.innerHTML);
    printWindow.document.title = filename;
    printWindow.focus();
    
    // Auto-trigger print dialog
    setTimeout(() => {
        printWindow.print();
        // Note: After printing, user can save as PDF from browser's print dialog
    }, 500);
}

// Enable Ctrl+P for print
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        downloadPDF();
    }
});
</script>
</html>