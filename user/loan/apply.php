<?php
// user/loan/apply.php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../helpers.php';

// Block admins from performing user actions
if (!empty($_SESSION['admin_id'])) {
    setFlash('danger', 'Admins cannot apply for loans.');
    header('Location: ' . APP_URL . '/admin/dashboard.php');
    exit;
}

$uid = $_SESSION['user_id'];

// Get user loan limit and max term
$stmt = $pdo->prepare("SELECT loan_limit, max_term_months, account_type FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$uid]);
$user = $stmt->fetch();
$loanLimit   = (float)($user['loan_limit'] ?? 10000);
$maxTerm     = (int)($user['max_term_months'] ?? 12);

// Calculate total active/pending loans
$stmt = $pdo->prepare("SELECT COALESCE(SUM(applied_amount), 0) as total FROM loans WHERE user_id = ? AND status IN ('Active','Pending','Approved')");
$stmt->execute([$uid]);
$activeLoanTotal = (float)$stmt->fetchColumn();
$availableAmount = max(0, $loanLimit - $activeLoanTotal);

// Get any single active/pending loan
$stmt = $pdo->prepare("SELECT id FROM loans WHERE user_id = ? AND status IN ('Active','Pending') LIMIT 1");
$stmt->execute([$uid]);
$existing = $stmt->fetch();

// Available terms based on requirements (only 1, 3, 6, 12 months allowed)
$terms = [1, 3, 6, 12];
$terms = array_filter($terms, fn($t) => $t <= $maxTerm);

// Available amounts (₱5,000 to ₱10,000, multiples of 1,000 only)
$amounts = [];
$maxApply = min($availableAmount, 10000); // Max ₱10,000 as per requirements
for ($a = 5000; $a <= $maxApply; $a += 1000) $amounts[] = $a;

require_once __DIR__ . '/../../includes/header.php';
?>
<style>
.apply-grid{display:grid;grid-template-columns:1fr 320px;gap:20px;}
@media(max-width:900px){.apply-grid{grid-template-columns:1fr;}}
.amount-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:8px;margin-bottom:4px;}
.amount-opt{border:1.5px solid #E5E7EB;border-radius:10px;padding:12px 6px;text-align:center;cursor:pointer;font-size:14px;font-weight:700;color:#6B7280;transition:all .15s;background:#fff;user-select:none;}
.amount-opt:hover{border-color:#1B3B8B;color:#1B3B8B;background:#EEF2FF;}
.amount-opt.picked{border-color:#1B3B8B;background:#1B3B8B;color:#FFD84D;}
.term-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:8px;margin-bottom:4px;}
.term-opt{border:1.5px solid #E5E7EB;border-radius:10px;padding:10px 6px;text-align:center;cursor:pointer;font-size:13px;font-weight:700;color:#6B7280;transition:all .15s;background:#fff;user-select:none;}
.term-opt:hover{border-color:#1B3B8B;color:#1B3B8B;background:#EEF2FF;}
.term-opt.picked{border-color:#1B3B8B;background:#1B3B8B;color:#FFD84D;}
.preview{background:#1B3B8B;border-radius:14px;padding:20px;}
.pv-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.1);font-size:13px;}
.pv-row:last-child{border:none;}
.pv-l{color:rgba(255,255,255,.6);}
.pv-v{font-weight:700;color:#fff;}
.pv-v.gold{color:#FFD84D;}
.limit-bar{height:8px;background:rgba(255,255,255,.15);border-radius:20px;overflow:hidden;margin:10px 0 5px;}
.limit-fill{height:100%;background:#FFD84D;border-radius:20px;transition:width .4s;}

/* Dark mode styles */
body.dark-mode .amount-opt{background:#1e293b;border-color:rgba(255,255,255,.1);color:#94a3b8;}
body.dark-mode .amount-opt:hover{border-color:#60a5fa;color:#60a5fa;background:#1e3a8a;}
body.dark-mode .amount-opt.picked{border-color:#60a5fa;background:#1e3a8a;color:#fbbf24;}
body.dark-mode .term-opt{background:#1e293b;border-color:rgba(255,255,255,.1);color:#94a3b8;}
body.dark-mode .term-opt:hover{border-color:#60a5fa;color:#60a5fa;background:#1e3a8a;}
body.dark-mode .term-opt.picked{border-color:#60a5fa;background:#1e3a8a;color:#fbbf24;}
body.dark-mode .preview{background:#1e293b;}
</style>

<div style="margin-bottom:20px;">
    <a href="<?= APP_URL ?>/user/loan/index.php" style="font-size:13px;color:#6B7280;text-decoration:none;display:inline-flex;align-items:center;gap:5px;">← Back to Loans</a>
    <h2 style="font-size:22px;font-weight:800;color:#111827;margin:8px 0 4px;">Apply for a Loan</h2>
    <p style="font-size:13px;color:#6B7280;margin:0;">Select your loan amount and payment term.</p>
</div>

<?php if ($activeLoanTotal > 0): ?>
<div class="ls-alert info">
    <i class="bi bi-info-circle-fill"></i>
    You have ₱<?= number_format($activeLoanTotal, 2) ?> in active/pending loans. You can still apply for up to ₱<?= number_format($availableAmount, 2) ?> more.
</div>
<?php endif; ?>

<form method="POST" action="<?= APP_URL ?>/user/loan/apply_process.php" id="loanForm">
<input type="hidden" name="amount" id="amountInput">
<input type="hidden" name="term"   id="termInput">

<div class="apply-grid">
<div>

    <!-- Amount -->
    <div class="box" style="margin-bottom:16px;">
        <div class="box-head">
            <h6 class="box-title"><span class="dot" style="background:#1B3B8B;"></span> Loan Amount</h6>
            <span style="font-size:12px;color:#6B7280;">Min ₱5,000 — Max <?= formatMoney($loanLimit) ?></span>
        </div>
        <div class="box-body">
            <!-- Limit progress -->
            <div style="margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;font-size:12px;color:#6B7280;margin-bottom:5px;">
                    <span>Your current loan limit</span>
                    <span style="font-weight:700;color:#1B3B8B;"><?= formatMoney($loanLimit) ?></span>
                </div>
                <div style="height:6px;background:#EEF2FF;border-radius:20px;overflow:hidden;">
                    <div style="height:100%;width:<?= round(($loanLimit/50000)*100) ?>%;background:#1B3B8B;border-radius:20px;"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:11px;color:#9CA3AF;margin-top:3px;">
                    <span>₱10,000 (start)</span>
                    <span><?= round(($loanLimit/50000)*100) ?>% of max ₱50,000</span>
                    <span>₱50,000 (max)</span>
                </div>
            </div>

            <div class="amount-grid">
            <?php foreach ($amounts as $a): ?>
                <div class="amount-opt" onclick="pickAmount(<?= $a ?>, this)">
                    ₱<?= number_format($a/1000, 0) ?>k
                </div>
            <?php endforeach; ?>
            </div>
            <div style="font-size:11px;color:#9CA3AF;margin-top:6px;">Amounts are in multiples of ₱1,000 only.</div>
        </div>
    </div>

    <!-- Term -->
    <div class="box">
        <div class="box-head">
            <h6 class="box-title"><span class="dot" style="background:#D97706;"></span> Payment Term</h6>
            <span style="font-size:12px;color:#6B7280;">Max <?= $maxTerm ?> months</span>
        </div>
        <div class="box-body">
            <div class="term-grid">
            <?php foreach ($terms as $t): ?>
                <div class="term-opt" onclick="pickTerm(<?= $t ?>, this)">
                    <?= $t ?> mo.
                </div>
            <?php endforeach; ?>
            </div>
            <?php if ($maxTerm > 12): ?>
            <div style="font-size:11px;color:#059669;margin-top:6px;font-weight:600;">
                🎉 You've unlocked extended terms up to <?= $maxTerm ?> months!
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Preview Sidebar -->
<div>
    <div style="position:sticky;top:80px;">
        <div class="preview">
            <div style="font-size:12px;color:rgba(255,255,255,.6);margin-bottom:4px;">Loan Preview</div>
            <div id="previewAmount" style="font-size:30px;font-weight:800;color:#FFD84D;margin-bottom:16px;">₱0</div>

            <div class="pv-row"><span class="pv-l">Loan Amount</span><span class="pv-v" id="pvAmount">—</span></div>
            <div class="pv-row"><span class="pv-l">Interest (3%)</span><span class="pv-v" style="color:#FCA5A5;" id="pvInterest">—</span></div>
            <div class="pv-row"><span class="pv-l">You Receive</span><span class="pv-v gold" id="pvReceive">—</span></div>
            <div class="pv-row"><span class="pv-l">Term</span><span class="pv-v" id="pvTerm">—</span></div>
            <div class="pv-row"><span class="pv-l">Monthly Due</span><span class="pv-v gold" id="pvMonthly">—</span></div>
            <div class="pv-row"><span class="pv-l">First Due Date</span><span class="pv-v" id="pvDue">—</span></div>

            <button type="submit" id="submitBtn" disabled
                style="width:100%;background:#FFD84D;color:#1B3B8B;border:none;border-radius:10px;padding:13px;font-size:14px;font-weight:800;cursor:pointer;margin-top:16px;transition:opacity .15s;font-family:inherit;"
                onclick="return validateForm()">
                Submit Application →
            </button>
            <div style="font-size:11px;color:rgba(255,255,255,.5);text-align:center;margin-top:8px;">Admin will review your application</div>
        </div>

        <?php if ($loanLimit < 50000): ?>
        <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:12px;padding:14px;margin-top:14px;">
            <div style="font-size:12px;font-weight:700;color:#065F46;margin-bottom:4px;">💡 How to increase your limit</div>
            <div style="font-size:12px;color:#047857;line-height:1.5;">Complete all monthly payments on time and your loan limit will increase by ₱5,000 (up to ₱50,000).</div>
        </div>
        <?php else: ?>
        <div style="background:#EEF2FF;border:1px solid #C7D2FE;border-radius:12px;padding:14px;margin-top:14px;">
            <div style="font-size:12px;font-weight:700;color:#3730A3;">🏆 Maximum limit reached!</div>
            <div style="font-size:12px;color:#4338CA;margin-top:2px;">You've reached the maximum loan limit of ₱50,000.</div>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
</form>

<script>
let selAmount = 0, selTerm = 0;

function pickAmount(val, el) {
    document.querySelectorAll('.amount-opt').forEach(e => e.classList.remove('picked'));
    el.classList.add('picked');
    selAmount = val;
    document.getElementById('amountInput').value = val;
    updatePreview();
}

function pickTerm(val, el) {
    document.querySelectorAll('.term-opt').forEach(e => e.classList.remove('picked'));
    el.classList.add('picked');
    selTerm = val;
    document.getElementById('termInput').value = val;
    updatePreview();
}

function fmt(n) {
    return '₱ ' + n.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function updatePreview() {
    if (!selAmount || !selTerm) {
        document.getElementById('submitBtn').disabled = true;
        return;
    }

    const interest  = Math.round(selAmount * 0.03 * 100) / 100;
    const receive   = selAmount - interest;
    const monthly   = Math.round((selAmount / selTerm) * 100) / 100;
    const due       = new Date(); due.setDate(due.getDate() + 28);
    const dueStr    = due.toLocaleDateString('en-PH', {year:'numeric',month:'long',day:'numeric'});

    document.getElementById('previewAmount').textContent = fmt(selAmount);
    document.getElementById('pvAmount').textContent      = fmt(selAmount);
    document.getElementById('pvInterest').textContent    = '− ' + fmt(interest);
    document.getElementById('pvReceive').textContent     = fmt(receive);
    document.getElementById('pvTerm').textContent        = selTerm + ' month' + (selTerm > 1 ? 's' : '');
    document.getElementById('pvMonthly').textContent     = fmt(monthly);
    document.getElementById('pvDue').textContent         = dueStr;

    document.getElementById('submitBtn').disabled = false;
}

function validateForm() {
    if (!selAmount || !selTerm) {
        alert('Please select a loan amount and payment term.');
        return false;
    }
    
    // Validation for loan amount requirements
    if (selAmount < 5000) {
        alert('❌ Invalid Amount\n\nMinimum loan amount is ₱5,000.\nPlease select a higher amount.');
        return false;
    }
    
    if (selAmount > 10000) {
        alert('❌ Invalid Amount\n\nMaximum loan amount is ₱10,000.\nPlease select a lower amount.');
        return false;
    }
    
    // Check if amount is in thousands (already enforced by UI, but double-check)
    if (selAmount % 1000 !== 0) {
        alert('❌ Invalid Amount\n\nLoan amounts must be in multiples of ₱1,000 only.\nExamples: ₱5,000, ₱6,000, ₱7,000, ₱8,000, ₱9,000, ₱10,000');
        return false;
    }
    
    // Validation for term requirements
    const allowedTerms = [1, 3, 6, 12];
    if (!allowedTerms.includes(selTerm)) {
        alert('❌ Invalid Term\n\nPayment term must be 1, 3, 6, or 12 months only.');
        return false;
    }
    
    const interest  = Math.round(selAmount * 0.03 * 100) / 100;
    const receive   = selAmount - interest;
    
    return confirm(`✅ Loan Application Summary\n\nLoan Amount: ${fmt(selAmount)}\nPayment Term: ${selTerm} month(s)\nInterest (3%): ${fmt(interest)}\nYou Receive: ${fmt(receive)}\nMonthly Payment: ${fmt(Math.round((selAmount / selTerm) * 100) / 100)}\n\nProceed with application?`);
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>