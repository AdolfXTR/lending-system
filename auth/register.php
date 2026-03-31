<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

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

/* Progress Bar */
.progress-steps {
    display: flex; align-items: center; justify-content: center;
    gap: 8px; margin-bottom: 32px;
}
.step {
    display: flex; align-items: center; gap: 8px;
}
.step-number {
    width: 32px; height: 32px; border-radius: 50%;
    background: #e5e7eb; color: #6b7280;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 13px;
    transition: all 0.3s;
}
.step.active .step-number {
    background: #1a45a8; color: #fff;
}
.step.completed .step-number {
    background: #16a34a; color: #fff;
}
.step-label {
    font-size: 12px; font-weight: 600; color: #6b7280;
    display: none;
}
@media (min-width: 768px) {
    .step-label { display: block; }
}
.step.active .step-label {
    color: #1a45a8;
}
.step-line {
    width: 30px; height: 2px; background: #e5e7eb;
}
@media (min-width: 768px) {
    .step-line { width: 60px; }
}
.step.completed + .step-line {
    background: #16a34a;
}

/* Multi-step form */
.reg-section {
    display: none;
    animation: fadeIn 0.3s ease;
}
.reg-section.active {
    display: block;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* File upload preview */
.file-preview {
    margin-top: 10px;
    border: 2px dashed #e5e7eb;
    border-radius: 8px;
    padding: 10px;
    text-align: center;
    min-height: 100px;
    display: flex; align-items: center; justify-content: center;
    flex-direction: column;
}
.file-preview img {
    max-height: 150px;
    max-width: 100%;
    border-radius: 6px;
}
.file-preview .file-icon {
    font-size: 40px;
    color: #9ca3af;
}
.file-preview .file-name {
    font-size: 12px;
    color: #6b7280;
    margin-top: 5px;
}

/* Terms checkbox */
.terms-box {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 16px 20px;
    margin: 20px 0;
}
.terms-box .form-check-input {
    width: 20px; height: 20px;
    margin-top: 0;
}
.terms-box .form-check-label {
    font-size: 14px;
    color: #374151;
    margin-left: 8px;
}

/* Navigation buttons */
.btn-nav {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
}
.btn-nav-prev {
    background: #f3f4f6;
    color: #6b7280;
}
.btn-nav-prev:hover {
    background: #e5e7eb;
}
.btn-nav-next {
    background: #1a45a8;
    color: #fff;
}
.btn-nav-next:hover {
    background: #153a8a;
}
.nav-buttons {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-top: 24px;
}

/* Submit note */
.submit-note {
    text-align: center;
    margin-top: 12px;
    font-size: 12px;
    color: #6b7280;
}
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

    <!-- Progress Steps -->
    <div class="progress-steps">
        <div class="step active" id="step1-indicator">
            <div class="step-number">1</div>
            <span class="step-label">Account</span>
        </div>
        <div class="step-line"></div>
        <div class="step" id="step2-indicator">
            <div class="step-number">2</div>
            <span class="step-label">Personal</span>
        </div>
        <div class="step-line"></div>
        <div class="step" id="step3-indicator">
            <div class="step-number">3</div>
            <span class="step-label">Bank & Work</span>
        </div>
        <div class="step-line"></div>
        <div class="step" id="step4-indicator">
            <div class="step-number">4</div>
            <span class="step-label">Documents</span>
        </div>
    </div>

    <form method="POST" action="<?= APP_URL ?>/auth/register_process.php" enctype="multipart/form-data" id="regForm">

        <!-- Account Info -->
        <div class="reg-section active" id="step1">
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
                <div class="nav-buttons">
                    <div></div>
                    <button type="button" class="btn-nav btn-nav-next" onclick="nextStep(2)">
                        Next <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Personal Info -->
        <div class="reg-section" id="step2">
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
                <div class="nav-buttons">
                    <button type="button" class="btn-nav btn-nav-prev" onclick="prevStep(1)">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn-nav btn-nav-next" onclick="nextStep(3)">
                        Next <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 3: Bank & Employment Combined -->
        <div class="reg-section" id="step3">
            <div class="reg-section-head">
                <span class="sec-dot" style="background:#f5c842;"></span>
                <h6 class="reg-section-title">Bank & Employment Details</h6>
            </div>
            <div class="reg-section-body">
                <!-- Bank Section -->
                <p class="fw-semibold text-muted small mb-2">BANK INFORMATION</p>
                <div class="reg-note mb-3">
                    <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0;margin-top:1px;"></i>
                    <span><strong>Important:</strong> Make sure your card holder's name is <strong>exactly correct</strong> as it appears on your card to avoid transaction interruptions.</span>
                </div>
                <div class="row g-3 mb-4">
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

                <!-- Employment Section -->
                <p class="fw-semibold text-muted small mb-2">EMPLOYMENT INFORMATION</p>
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
                <div class="row g-3 mb-3">
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
                <div class="nav-buttons">
                    <button type="button" class="btn-nav btn-nav-prev" onclick="prevStep(2)">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn-nav btn-nav-next" onclick="nextStep(4)">
                        Next <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 4: Document Uploads -->
        <div class="reg-section" id="step4">
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
                        <input type="file" name="proof_of_billing" id="file-pob" class="form-control" required accept=".jpg,.jpeg,.png,.pdf" onchange="previewFile(this, 'preview-pob')">
                        <div id="preview-pob" class="file-preview">
                            <i class="bi bi-image file-icon"></i>
                            <span class="file-name">No file selected</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Valid ID (Primary) <span class="text-danger">*</span></label>
                        <input type="file" name="valid_id" id="file-id" class="form-control" required accept=".jpg,.jpeg,.png,.pdf" onchange="previewFile(this, 'preview-id')">
                        <div id="preview-id" class="file-preview">
                            <i class="bi bi-image file-icon"></i>
                            <span class="file-name">No file selected</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">COE (Certificate of Employment) <span class="text-danger">*</span></label>
                        <input type="file" name="coe" id="file-coe" class="form-control" required accept=".jpg,.jpeg,.png,.pdf" onchange="previewFile(this, 'preview-coe')">
                        <div id="preview-coe" class="file-preview">
                            <i class="bi bi-image file-icon"></i>
                            <span class="file-name">No file selected</span>
                        </div>
                    </div>
                </div>

                <!-- Terms Checkbox -->
                <div class="terms-box">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="termsCheck" required>
                        <label class="form-check-label" for="termsCheck">
                            I confirm that all information provided is accurate and complete
                        </label>
                    </div>
                </div>

                <div class="nav-buttons">
                    <button type="button" class="btn-nav btn-nav-prev" onclick="prevStep(3)">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="bi bi-send"></i> Submit Application
                    </button>
                </div>
                <p class="submit-note">
                    <i class="bi bi-clock"></i> Your application will be reviewed by an admin within 1-3 business days
                </p>
            </div>
        </div>

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

// Multi-step navigation
let currentStep = 1;
const totalSteps = 4;

function nextStep(step) {
    // Validate current step before moving
    const currentSection = document.getElementById('step' + currentStep);
    const requiredFields = currentSection.querySelectorAll('[required]');
    let valid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            valid = false;
            field.classList.add('is-invalid');
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    if (!valid) {
        alert('Please fill in all required fields before proceeding.');
        return;
    }
    
    // Hide current step
    document.getElementById('step' + currentStep).classList.remove('active');
    document.getElementById('step' + currentStep + '-indicator').classList.remove('active');
    document.getElementById('step' + currentStep + '-indicator').classList.add('completed');
    
    // Show next step
    currentStep = step;
    document.getElementById('step' + currentStep).classList.add('active');
    document.getElementById('step' + currentStep + '-indicator').classList.add('active');
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function prevStep(step) {
    // Hide current step
    document.getElementById('step' + currentStep).classList.remove('active');
    document.getElementById('step' + currentStep + '-indicator').classList.remove('active');
    
    // Show previous step
    currentStep = step;
    document.getElementById('step' + currentStep).classList.add('active');
    document.getElementById('step' + currentStep + '-indicator').classList.add('active');
    document.getElementById('step' + currentStep + '-indicator').classList.remove('completed');
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// File preview function
function previewFile(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    
    if (file) {
        const fileName = file.name;
        const fileExt = fileName.split('.').pop().toLowerCase();
        
        if (['jpg', 'jpeg', 'png'].includes(fileExt)) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            };
            reader.readAsDataURL(file);
        } else if (fileExt === 'pdf') {
            preview.innerHTML = `
                <i class="bi bi-file-earmark-pdf file-icon" style="color: #dc2626;"></i>
                <span class="file-name">${fileName}</span>
            `;
        } else {
            preview.innerHTML = `
                <i class="bi bi-file-earmark file-icon"></i>
                <span class="file-name">${fileName}</span>
            `;
        }
    }
}
</script>
</body>
</html>