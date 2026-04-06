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

// Fix singular/plural for months
$monthText = $b['term_months'] == 1 ? '1 month' : $b['term_months'] . ' months';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Billing Statement — Month <?= $b['month_number'] ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:#e8edf2;color:#111827;font-size:13px;line-height:1.6;}
.page{max-width:800px;margin:40px auto;background:#ffffff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1);overflow:hidden;}

/* Top colored bar */
.top-bar{background:#1e293b;height:8px;width:100%;}

/* Header */
.header{padding:32px 40px 24px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:flex-start;}
.logo-section{display:flex;align-items:center;gap:16px;}
.logo-icon{width:48px;height:48px;background:#1e293b;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fbbf24;font-size:20px;font-weight:800;}
.logo-text h1{font-size:20px;font-weight:800;color:#1e293b;margin:0;}
.logo-text p{font-size:12px;color:#64748b;margin:2px 0 0;}
.header-right{text-align:right;}
.document-title{font-size:24px;font-weight:800;color:#1e293b;margin-bottom:4px;}
.document-subtitle{font-size:12px;color:#64748b;}

/* Status Banner */
.status-banner{padding:10px 40px;display:flex;align-items:center;gap:8px;font-weight:600;font-size:14px;}
.status-completed{background:#dcfce7;color:#15803d;}
.status-pending{background:#fef3c7;color:#d97706;}
.status-overdue{background:#fee2e2;color:#dc2626;}

/* Content Sections */
.content{padding:40px;}
.section{margin-bottom:32px;}
.section-block{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:20px;}
.section-title{font-size:11px;font-weight:700;color:#3b82f6;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:16px;}

/* Two Column Grid */
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.info-row{display:flex;flex-direction:column;gap:4px;}
.info-label{font-size:12px;color:#64748b;font-weight:500;}
.info-value{font-size:14px;font-weight:600;color:#1a2332;}
.verified-tag{display:inline-flex;align-items:center;gap:4px;background:#dcfce7;color:#15803d;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:8px;}

/* Billing Breakdown Table */
.breakdown-table{width:100%;border-collapse:collapse;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;}
.breakdown-table th{padding:12px 16px;text-align:left;font-size:12px;font-weight:700;color:#ffffff;text-transform:uppercase;letter-spacing:0.04em;background:#1e293b;}
.breakdown-table td{padding:14px 16px;border-bottom:1px solid #e2e8f0;font-size:14px;}
.breakdown-table tr:nth-child(even){background:#f8fafc;}
.breakdown-table tr:last-child td{border:none;}
.breakdown-table .total-row{background:#1e293b;}
.breakdown-table .total-row td{color:#ffffff;font-weight:800;font-size:16px;padding:16px;}
.text-amber{color:#f59e0b;}
.text-muted{color:#9ca3af;}

/* Footer */
.footer{background:#f8fafc;border-top:1px solid #e2e8f0;padding:32px 40px;margin-top:40px;}
.footer-message{text-align:center;margin-bottom:24px;}
.footer-message h3{font-size:16px;font-weight:700;color:#1a2332;margin-bottom:8px;}
.footer-message p{font-size:13px;color:#64748b;}
.signature-area{display:flex;justify-content:space-between;align-items:flex-end;margin-top:32px;}
.signature-line{flex:1;border-top:1px solid #d1d9e0;padding-top:8px;text-align:center;font-size:12px;color:#64748b;}
.official-notice{text-align:center;margin-top:24px;font-size:11px;color:#9ca3af;font-style:italic;}

/* Print Controls */
.print-controls{background:#f1f5f9;border-bottom:1px solid #e2e8f0;padding:16px 40px;display:flex;justify-content:space-between;align-items:center;}
.back-link{font-size:14px;color:#3b82f6;text-decoration:none;font-weight:600;display:flex;align-items:center;gap:4px;}
.button-group{display:flex;gap:12px;}
.btn{border:none;border-radius:8px;padding:10px 20px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;transition:all 0.2s ease;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
.btn-primary{background:#3b82f6;color:#ffffff;}
.btn-primary:hover{background:#2563eb;transform:translateY(-1px);}
.btn-secondary{background:#ffffff;color:#3b82f6;border:1px solid #3b82f6;}
.btn-secondary:hover{background:#f0f4ff;}

/* Print Styles */
@media print {
    body{background:#ffffff;}
    .page{margin:0;box-shadow:none;border-radius:0;}
    .print-controls{display:none!important;}
    .content{padding:20px;}
    .header{padding:20px;}
    .footer{padding:20px;}
    .status-banner{padding:8px 20px;}
}

/* Responsive */
@media (max-width: 768px) {
    .page{margin:20px;border-radius:8px;}
    .header{flex-direction:column;gap:16px;padding:24px;}
    .header-right{text-align:left;}
    .info-grid{grid-template-columns:1fr;gap:12px;}
    .content{padding:24px;}
    .print-controls{flex-direction:column;gap:12px;padding:16px;}
}
</style>
</head>
<body>

<!-- Print Controls -->
<div class="print-controls">
    <a href="<?= APP_URL ?>/user/billing/index.php" class="back-link">← Back to Billing</a>
    <div class="button-group">
        <button class="btn btn-secondary" onclick="downloadPDF()">📥 Download PDF</button>
        <button class="btn btn-primary" onclick="window.print()">🖨️ Print Statement</button>
    </div>
</div>

<div class="page">
    <!-- Top Colored Bar -->
    <div class="top-bar"></div>
    
    <!-- Header -->
    <div class="header">
        <div class="logo-section">
            <div class="logo-icon">LS</div>
            <div class="logo-text">
                <h1>LendingSystem</h1>
                <p>Official Billing Statement</p>
            </div>
        </div>
        <div class="header-right">
            <div class="document-title">BILLING STATEMENT</div>
            <div class="document-subtitle">Month <?= $b['month_number'] ?> of <?= $monthText ?></div>
            <div class="document-subtitle">Due: <?= date('F d, Y', strtotime($b['due_date'])) ?></div>
        </div>
    </div>

    <!-- Status Banner -->
    <?php
    $statusClass = match($b['status']) {
        'Completed' => 'status-completed',
        'Overdue'   => 'status-overdue',
        default     => 'status-pending',
    };
    $statusIcon = match($b['status']) {
        'Completed' => '✅',
        'Overdue'   => '⚠️',
        default     => '🕐',
    };
    ?>
    <div class="status-banner <?= $statusClass ?>">
        <?= $statusIcon ?> Payment Status: <?= $b['status'] ?>
        <?php if ($b['status'] === 'Completed' && $b['paid_at']): ?>
            — Paid on <?= date('F d, Y', strtotime($b['paid_at'])) ?>
        <?php endif; ?>
    </div>

    <!-- Content -->
    <div class="content">
        <!-- Borrower Details -->
        <div class="section">
            <div class="section-title">BORROWER DETAILS</div>
            <div class="section-block">
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
                        <span class="info-label">Email Address</span>
                        <span class="info-value"><?= htmlspecialchars($b['email']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Account Type</span>
                        <span class="info-value"><?= $b['account_type'] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Residential Address</span>
                        <span class="info-value"><?= htmlspecialchars($b['address']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loan Information -->
        <div class="section">
            <div class="section-title">LOAN INFORMATION</div>
            <div class="section-block">
                <div class="info-grid">
                    <div class="info-row">
                        <span class="info-label">Date Generated</span>
                        <span class="info-value"><?= date('F d, Y', strtotime($b['created_at'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Due Date</span>
                        <span class="info-value"><?= date('F d, Y', strtotime($b['due_date'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Applied Loan Amount</span>
                        <span class="info-value"><?= formatMoney($b['applied_amount']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Received Amount</span>
                        <span class="info-value"><?= formatMoney($b['received_amount']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Term</span>
                        <span class="info-value"><?= $monthText ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Current Payment</span>
                        <span class="info-value">Month <?= $b['month_number'] ?> of <?= $b['term_months'] ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Billing Breakdown -->
        <div class="section">
            <div class="section-title">BILLING BREAKDOWN</div>
            <table class="breakdown-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Monthly Principal Payment</td>
                        <td style="text-align:right;font-weight:600;"><?= formatMoney($b['amount_due']) ?></td>
                    </tr>
                    <tr>
                        <td>
                            Interest (3% on full loan amount)
                            <?php if ($b['month_number'] > 1): ?>
                                <span class="text-muted" style="font-size:11px;"> — charged on Month 1 only</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;" class="<?= $b['interest'] > 0 ? 'text-amber' : 'text-muted' ?>">
                            <?= $b['interest'] > 0 ? '+ ' . formatMoney($b['interest']) : '—' ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Late Payment Penalty (2%)</td>
                        <td style="text-align:right;" class="<?= $b['penalty'] > 0 ? 'text-amber' : 'text-muted' ?>">
                            <?= $b['penalty'] > 0 ? '+ ' . formatMoney($b['penalty']) : '—' ?>
                        </td>
                    </tr>
                    <tr class="total-row">
                        <td>Total Amount Due</td>
                        <td style="text-align:right;"><?= formatMoney($b['total_due']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Payment Information -->
        <div class="section">
            <div class="section-title">PAYMENT INFORMATION</div>
            <div class="section-block">
                <div style="font-size:13px;color:#64748b;margin-bottom:16px;font-weight:600;">Please send payment to the borrower's bank account:</div>
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
                        <span class="info-label">Account Holder Name</span>
                        <span class="info-value"><?= htmlspecialchars($b['card_holder_name']) ?></span>
                    </div>
                </div>
                <div style="margin-top:16px;font-size:12px;color:#9ca3af;line-height:1.5;">
                    Please coordinate with your loan officer to process payment via bank transfer. Keep your payment receipt as proof of transaction.
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-message">
            <h3>Thank you for your payment!</h3>
            <p>We appreciate your timely payment and continued partnership with LendingSystem.</p>
        </div>
        
        <div class="signature-area">
            <div class="signature-line">Authorized Signature</div>
            <div class="signature-line">Date</div>
        </div>
        
        <div class="official-notice">
            This is an official billing statement from LendingSystem. For questions or concerns, please contact your loan officer.
        </div>
        
        <div style="text-align:center;margin-top:16px;font-size:11px;color:#9ca3af;">
            Generated on <?= $generated ?>
        </div>
    </div>
</div>
</body>
<script>
function downloadPDF() {
    // Generateormal PDF name with month and date
    const month = <?= $b['month_number'] ?>;
    const dueDate = '<?= date('Y-m-d', strtotime($b['due_date'])) ?>';
    const filename = `Billing-Statement-Month-${month}-${dueDate}.pdf`;
    
    // Trigger print dialog with PDF save option
    const printWindow = window.open('', '', 'width=900,height=700');
    printWindow.document.write(document.documentElement.innerHTML);
    printWindow.document.title = filename;
    printWindow.focus();
    
    // Auto-trigger print dialog
    setTimeout(() => {
        printWindow.print();
    }, 500);
}

// Enable Ctrl+P for print
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        window.print();
    }
});
</script>
</html>