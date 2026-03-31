<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

session_destroy();
session_start();

if (isLoggedIn()) { header('Location: ' . APP_URL . '/user/dashboard.php'); exit; }
if (isAdmin())    { header('Location: ' . APP_URL . '/admin/dashboard.php'); exit; }

$errors = $_SESSION['register_errors'] ?? [];
$old    = $_SESSION['register_old'] ?? [];
unset($_SESSION['register_errors'], $_SESSION['register_old']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register | <?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
<style>
* { box-sizing: border-box; }
body {
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    background: #f0f2f7; margin: 0; padding: 0;
    color: #1a1f2e; font-size: 14px;
}
.reg-topbar {
    background: #0f2557;
    padding: 0 28px; height: 58px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 100;
    box-shadow: 0 2px 12px rgba(15,37,87,0.3);
}
.reg-brand {
    display: flex; align-items: center; gap: 10px;
    color: #fff; text-decoration: none; font-size: 16px; font-weight: 700;
}
.reg-brand .brand-box {
    width: 32px; height: 32px; background: #f5c842;
    border-radius: 8px; display: flex; align-items: center; justify-content: center;
    font-size: 16px;
}
.reg-topbar a.login-link {
    color: rgba(255,255,255,0.75); font-size: 13px; font-weight: 600;
    text-decoration: none; display: flex; align-items: center; gap: 6px;
}
.reg-topbar a.login-link:hover { color: #f5c842; }

.reg-wrapper { max-width: 800px; margin: 36px auto; padding: 0 20px 60px; }

.reg-hero {
    text-align: center; margin-bottom: 32px;
}
.reg-hero h1 { font-size: 26px; font-weight: 700; color: #0f2557; margin: 0 0 8px; }
.reg-hero p { font-size: 14px; color: #6b7280; margin: 0; }

/* Section cards */
.reg-section {
    background: #fff;
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,0.07);
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    margin-bottom: 18px;
    overflow: hidden;
}
.reg-section-head {
    padding: 14px 22px;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    display: flex; align-items: center; gap: 10px;
    background: #fafbfc;
}
.reg-section-head .sec-dot {
    width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.reg-section-title {
    font-size: 13px; font-weight: 700; color: #0f2557; margin: 0;
}
.reg-section-body { padding: 22px; }

/* Form controls */
.form-label {
    font-size: 12px; font-weight: 700; color: #374151;
    text-transform: uppercase; letter-spacing: 0.04em;
    margin-bottom: 5px;
}
.form-control, .form-select {
    border: 1.5px solid rgba(0,0,0,0.1);
    border-radius: 8px; font-family: inherit;
    font-size: 14px; padding: 9px 13px;
    transition: border-color 0.15s, box-shadow 0.15s;
    color: #1a1f2e;
}
.form-control:focus, .form-select:focus {
    border-color: #1a45a8;
    box-shadow: 0 0 0 3px rgba(26,69,168,0.1);
    outline: none;
}
.form-text { font-size: 11px; color: #9ca3af; margin-top: 4px; }
.input-group-text {
    background: #f8fafc; border: 1.5px solid rgba(0,0,0,0.1);
    border-right: none; font-size: 14px; color: #6b7280;
}
.input-group .form-control { border-left: none; }

/* Password strength */
.pwd-rules { margin-top: 6px; }
.pwd-rule {
    font-size: 11px; color: #9ca3af;
    display: flex; align-items: center; gap: 5px;
    margin-bottom: 2px; transition: color 0.15s;
}
.pwd-rule.met { color: #16a34a; }
.pwd-rule i { font-size: 10px; }

/* Alert */
.reg-alert {
    background: #fee2e2; color: #b91c1c;
    border: 1px solid #fecaca; border-radius: 10px;
    padding: 14px 18px; margin-bottom: 20px;
    font-size: 13px;
}
.reg-alert ul { margin: 8px 0 0; padding-left: 18px; }
.reg-alert li { margin-bottom: 3px; }

/* Warning note */
.reg-note {
    background: #fef3c7; border: 1px solid #fde68a;
    border-radius: 10px; padding: 12px 16px;
    font-size: 13px; color: #92400e;
    display: flex; align-items: flex-start; gap: 10px;
    margin-bottom: 16px;
}

/* Submit button */
.btn-submit {
    background: #0f2557; color: #f5c842;
    border: none; border-radius: 10px;
    padding: 14px; font-size: 15px; font-weight: 700;
    font-family: inherit; cursor: pointer; width: 100%;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: background 0.15s;
}
.btn-submit:hover { background: #1a3a7a; }

.login-cta {
    text-align: center; margin-top: 18px;
    font-size: 13px; color: #6b7280;
}
.login-cta a { color: #1a45a8; font-weight: 700; text-decoration: none; }
.login-cta a:hover { text-decoration: underline; }
</style>
</head>
<body>

<!-- Topbar -->
<div class="reg-topbar">
    <a href="<?= APP_URL ?>/auth/login.php" class="reg-brand">
        <div class="brand-box">🏦</div>
        <?= APP_NAME ?>
    </a>
    <a href="<?= APP_URL ?>/auth/login.php" class="login-link">
        <i class="bi bi-box-arrow-in-right"></i> Already have an account? Login
    </a>
</div>

<div class="reg-wrapper">

    <div class="reg-hero">
        <h1>Membership Application</h1>
        <p>Fill out all required fields. Your application will be reviewed by an admin before activation.</p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="reg-alert">
        <strong><i class="bi bi-exclamation-circle"></i> Please fix the following errors:</strong>
        <ul>
            <?php foreach ($errors as $e): ?>
                <li><?= clean($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= APP_URL ?>/auth/register_process.php" enctype="multipart/form-data">

        <!-- Account Info -->
        <div class="reg-section">
            <div class="reg-section-head">
                <span class="sec-dot" style="background:#1a45a8;"></span>
                <h6 class="reg-section-title">Account Information</h6>
            </div>
            <div class="reg-section-body">
                <div class="mb-3">
                    <label class="form-label">Account Type <span class="text-danger">*</span></label>
                    <select name="account_type" class="form-select" required>
                        <option value="">-- Select Account Type --</option>
                        <option value="Basic"   <?= ($old['account_type'] ?? '') === 'Basic'   ? 'selected' : '' ?>>Basic — Loans only</option>
                        <option value="Premium" <?= ($old['account_type'] ?? '') === 'Premium' ? 'selected' : '' ?>>Premium — Loans + Savings + Money Back (max 50 slots)</option>
                    </select>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required
                            placeholder="Min. 6 characters"
                            value="<?= clean($old['username'] ?? '') ?>">
                        <div class="form-text">You can use your email or any characters — min 6.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="passwordInput" class="form-control"
                            required placeholder="Create a strong password"
                            oninput="checkPassword(this.value)">
                        <div class="pwd-rules">
                            <div class="pwd-rule" id="rule-len"><i class="bi bi-circle"></i> At least 8 characters</div>
                            <div class="pwd-rule" id="rule-upper"><i class="bi bi-circle"></i> At least 1 uppercase letter</div>
                            <div class="pwd-rule" id="rule-lower"><i class="bi bi-circle"></i> At least 1 lowercase letter</div>
                            <div class="pwd-rule" id="rule-num"><i class="bi bi-circle"></i> At least 1 number</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personal Info -->
        <div class="reg-section">
            <div class="reg-section-head">
                <span class="sec-dot" style="background:#16a34a;"></span>
                <h6 class="reg-section-title">Personal Information</h6>
            </div>
            <div class="reg-section-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control" required
                            value="<?= clean($old['first_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control" required
                            value="<?= clean($old['last_name'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address <span class="text-danger">*</span></label>
                    <textarea name="address" class="form-control" rows="2" required><?= clean($old['address'] ?? '') ?></textarea>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">-- Select --</option>
                            <option value="Male"   <?= ($old['gender'] ?? '') === 'Male'   ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($old['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other"  <?= ($old['gender'] ?? '') === 'Other'  ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Birthday <span class="text-danger">*</span></label>
                        <input type="date" name="birthday" id="birthday" class="form-control" required
                            value="<?= clean($old['birthday'] ?? '') ?>"
                            onchange="calcAge(this.value)">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Age</label>
                        <input type="text" id="age_display" class="form-control" style="background:#f8fafc;" readonly
                            placeholder="Auto-calculated"
                            value="<?= !empty($old['age']) ? $old['age'].' years old' : '' ?>">
                        <input type="hidden" name="age" id="age_value" value="<?= clean($old['age'] ?? '') ?>">
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required
                            value="<?= clean($old['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" class="form-control" required
                            placeholder="09XXXXXXXXX"
                            value="<?= clean($old['contact_number'] ?? '') ?>">
                        <div class="form-text">Philippine mobile number format only.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bank Details -->
        <div class="reg-section">
            <div class="reg-section-head">
                <span class="sec-dot" style="background:#f5c842;"></span>
                <h6 class="reg-section-title">Bank Details</h6>
            </div>
            <div class="reg-section-body">
                <div class="reg-note">
                    <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0;margin-top:1px;"></i>
                    <span><strong>Important:</strong> Make sure your card holder's name is <strong>exactly correct</strong> as it appears on your card to avoid transaction interruptions.</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" class="form-control" required
                            value="<?= clean($old['bank_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Bank Account Number <span class="text-danger">*</span></label>
                        <input type="text" name="bank_account_number" class="form-control" required
                            value="<?= clean($old['bank_account_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Card Holder's Name <span class="text-danger">*</span></label>
                        <input type="text" name="card_holder_name" class="form-control" required
                            placeholder="As it appears on card"
                            value="<?= clean($old['card_holder_name'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Employment -->
        <div class="reg-section">
            <div class="reg-section-head">
                <span class="sec-dot" style="background:#7c3aed;"></span>
                <h6 class="reg-section-title">Employment Details</h6>
            </div>
            <div class="reg-section-body">
                <div class="mb-3">
                    <label class="form-label">TIN Number <span class="text-danger">*</span></label>
                    <input type="text" name="tin_number" class="form-control" required
                        placeholder="000-000-000"
                        value="<?= clean($old['tin_number'] ?? '') ?>">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" class="form-control" required
                            value="<?= clean($old['company_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Company Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="company_phone" class="form-control" required
                            value="<?= clean($old['company_phone'] ?? '') ?>">
                        <div class="form-text">Please provide the number directed to HR to confirm employment.</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Company Address <span class="text-danger">*</span></label>
                    <textarea name="company_address" class="form-control" rows="2" required><?= clean($old['company_address'] ?? '') ?></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Position <span class="text-danger">*</span></label>
                        <input type="text" name="position" class="form-control" required
                            value="<?= clean($old['position'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Monthly Earnings <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" name="monthly_earnings" class="form-control" required
                                min="0" step="0.01"
                                value="<?= clean($old['monthly_earnings'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Uploads -->
        <div class="reg-section">
            <div class="reg-section-head">
                <span class="sec-dot" style="background:#dc2626;"></span>
                <h6 class="reg-section-title">Document Uploads</h6>
            </div>
            <div class="reg-section-body">
                <p style="font-size:13px;color:#6b7280;margin:0 0 16px;">
                    <i class="bi bi-info-circle"></i>
                    Accepted formats: JPG, PNG, PDF. Maximum file size: 5MB each.
                </p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Proof of Billing <span class="text-danger">*</span></label>
                        <input type="file" name="proof_of_billing" class="form-control" required accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Valid ID (Primary) <span class="text-danger">*</span></label>
                        <input type="file" name="valid_id" class="form-control" required accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">COE (Certificate of Employment) <span class="text-danger">*</span></label>
                        <input type="file" name="coe" class="form-control" required accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-submit">
            <i class="bi bi-send"></i> Submit Application
        </button>

    </form>

    <p class="login-cta">
        Already have an account? <a href="<?= APP_URL ?>/auth/login.php">Login here</a>
    </p>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function calcAge(birthday) {
    if (!birthday) return;
    const today = new Date();
    const dob   = new Date(birthday);
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
    document.getElementById('age_display').value = age + ' years old';
    document.getElementById('age_value').value   = age;
}
const bd = document.getElementById('birthday').value;
if (bd) calcAge(bd);

function checkPassword(val) {
    const rules = {
        'rule-len':   val.length >= 8,
        'rule-upper': /[A-Z]/.test(val),
        'rule-lower': /[a-z]/.test(val),
        'rule-num':   /[0-9]/.test(val),
    };
    for (const [id, met] of Object.entries(rules)) {
        const el = document.getElementById(id);
        el.classList.toggle('met', met);
        el.querySelector('i').className = met ? 'bi bi-check-circle-fill' : 'bi bi-circle';
    }
}
</script>
</body>
</html>