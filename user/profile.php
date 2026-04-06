<?php
// user/profile.php
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../helpers.php';

$userId = $_SESSION['user_id'];
$stmt   = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>
<style>
.profile-section { margin-bottom: 20px; }
.profile-card {
    background: #fff; border-radius: 14px;
    border: 1px solid rgba(0,0,0,.07);
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
    overflow: hidden;
}
.profile-card-head {
    padding: 14px 20px; border-bottom: 1px solid rgba(0,0,0,.06);
    display: flex; align-items: center; gap: 10px;
}
.profile-card-head .head-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center; font-size: 15px;
}
.profile-card-head .head-title { font-size: 14px; font-weight: 700; color: #1a1f2e; margin: 0; }
.profile-card-head .head-sub   { font-size: 12px; color: #9ca3af; margin: 0; }
.profile-card-body { padding: 20px; }

.form-row { display: grid; gap: 14px; margin-bottom: 14px; }
.form-row.cols-2 { grid-template-columns: 1fr 1fr; }
.form-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
.form-row.cols-4 { grid-template-columns: repeat(4, 1fr); }
@media(max-width:640px){ .form-row.cols-2,.form-row.cols-3,.form-row.cols-4{ grid-template-columns:1fr; } }

.form-group { display: flex; flex-direction: column; gap: 5px; }
.form-lbl {
    font-size: 11px; font-weight: 700; color: #374151;
    text-transform: uppercase; letter-spacing: .04em;
}
.form-lbl .req { color: #dc2626; margin-left: 2px; }
.form-inp {
    padding: 9px 12px; border: 1px solid rgba(0,0,0,.12);
    border-radius: 8px; font-size: 13px; font-family: inherit;
    color: #1a1f2e; background: #fff; outline: none;
    transition: border-color .15s, box-shadow .15s; width: 100%;
}
.readonly-styled {
    background: #f8fafc !important; 
    color: #6b7280 !important; 
    border-color: #d1d5db !important;
    position: relative;
    padding-left: 36px !important;
}
.readonly-styled::before {
    content: '🔒';
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #9ca3af;
}
.form-inp:focus { border-color: #1a45a8; box-shadow: 0 0 0 3px rgba(26,69,168,.1); }
.form-inp:disabled, .form-inp[readonly] { background: #f8fafc; color: #9ca3af; cursor: not-allowed; }
.form-hint { font-size: 11px; color: #9ca3af; display: flex; align-items: flex-start; gap: 4px; }
.form-hint.warn { color: #d97706; }

.section-divider { height: 1px; background: rgba(0,0,0,.06); margin: 0 20px; }

.save-bar {
    background: #fff; border-radius: 14px;
    border: 1px solid rgba(0,0,0,.07);
    padding: 16px 20px;
    display: flex; align-items: center; justify-content: space-between;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.save-bar-text { font-size: 13px; color: #6b7280; }
.btn-save {
    background: #0f2557; color: #f5c842;
    border: none; border-radius: 9px;
    padding: 10px 28px; font-size: 14px; font-weight: 700;
    font-family: inherit; cursor: pointer; transition: background .15s;
    display: inline-flex; align-items: center; gap: 8px;
}
.btn-save:hover { background: #1a3a7a; }

.profile-hero {
    background: #0f2557; border-radius: 14px;
    padding: 22px 24px; margin-bottom: 20px;
    display: flex; align-items: center; gap: 18px;
    box-shadow: 0 2px 10px rgba(15,37,87,.2);
}
.hero-avatar {
    width: 60px; height: 60px; background: #f5c842;
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 22px; font-weight: 700; color: #0f2557; flex-shrink: 0;
}
.hero-name  { font-size: 18px; font-weight: 700; color: #fff; margin: 0 0 4px; }
.hero-meta  { font-size: 13px; color: rgba(255,255,255,.6); margin: 0; display: flex; align-items: center; gap: 10px; }
.hero-badge {
    font-size: 11px; font-weight: 700; padding: 2px 10px; border-radius: 20px;
    background: #f5c842; color: #0f2557;
}
.hero-badge.basic { background: rgba(255,255,255,.15); color: rgba(255,255,255,.8); }

/* Dark mode styles */
body.dark-mode .profile-card {
    background: #1a1f2e !important;
    border-color: rgba(255,255,255,.1) !important;
    box-shadow: 0 1px 4px rgba(0,0,0,.4) !important;
}

body.dark-mode .profile-card-head {
    border-color: rgba(255,255,255,.1) !important;
}

body.dark-mode .profile-card-head .head-title {
    color: #f1f5f9 !important;
}

body.dark-mode .profile-card-head .head-sub {
    color: #94a3b8 !important;
}

body.dark-mode .form-lbl {
    color: #e2e8f0 !important;
}

body.dark-mode .form-lbl .req {
    color: #f87171 !important;
}

body.dark-mode .form-inp {
    background: #1e293b !important;
    border-color: rgba(255,255,255,.1) !important;
    color: #f1f5f9 !important;
}

body.dark-mode .form-inp::placeholder {
    color: #64748b !important;
}

body.dark-mode .form-inp:focus {
    border-color: #60a5fa !important;
    box-shadow: 0 0 0 3px rgba(96,165,250,.1) !important;
}

body.dark-mode .form-inp:disabled,
body.dark-mode .form-inp[readonly] {
    background: #374151 !important;
    color: #94a3b8 !important;
}

body.dark-mode .form-inp.readonly-styled {
    background: #1e3a8a !important;
    color: #93c5fd !important;
    border-color: rgba(255,255,255,.05) !important;
}

body.dark-mode .form-hint {
    color: #94a3b8 !important;
}

body.dark-mode .form-hint.warn {
    color: #fbbf24 !important;
}

body.dark-mode .section-divider {
    background: rgba(255,255,255,.1) !important;
}

body.dark-mode .save-bar {
    background: #1a1f2e !important;
    border-color: rgba(255,255,255,.1) !important;
    box-shadow: 0 1px 4px rgba(0,0,0,.4) !important;
}

body.dark-mode .save-bar-text {
    color: #94a3b8 !important;
}

body.dark-mode .btn-save {
    background: #1e293b !important;
    color: #fbbf24 !important;
}

body.dark-mode .btn-save:hover {
    background: #334155 !important;
}

body.dark-mode .profile-hero {
    background: #1e293b !important;
    box-shadow: 0 2px 10px rgba(30,41,59,.4) !important;
}

body.dark-mode .hero-avatar {
    background: #fbbf24 !important;
    color: #1e293b !important;
}

body.dark-mode .hero-name {
    color: #f1f5f9 !important;
}

body.dark-mode .hero-meta {
    color: rgba(255,255,255,.6) !important;
}

body.dark-mode .hero-badge {
    background: #fbbf24 !important;
    color: #1e293b !important;
}

body.dark-mode .hero-badge.verified {
    background: #4ade80 !important;
    color: #064e3b !important;
}

body.dark-mode .hero-badge.pending {
    background: #fbbf24 !important;
    color: #78350f !important;
}

body.dark-mode .hero-badge.basic {
    background: rgba(255,255,255,.15) !important;
    color: rgba(255,255,255,.8) !important;
}

/* Light mode verification badges */
.hero-badge.verified {
    background: #dcfce7 !important;
    color: #15803d !important;
}

.hero-badge.pending {
    background: #fef3c7 !important;
    color: #92400e !important;
}

.hero-badge.basic {
    background: #f3f4f6 !important;
    color: #6b7280 !important;
}

/* Select dropdown styling */
body.dark-mode select.form-inp {
    background: #1e293b !important;
    border-color: rgba(255,255,255,.1) !important;
    color: #f1f5f9 !important;
}

body.dark-mode select.form-inp option {
    background: #1e293b !important;
    color: #f1f5f9 !important;
}
</style>

<!-- Hero -->
<div class="profile-hero">
    <div class="hero-avatar">
        <?= strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1)) ?>
    </div>
    <div>
        <div class="hero-name">
            <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
            <?php if ($user['is_verified']): ?>
                <span class="hero-badge verified">✓ Verified</span>
            <?php else: ?>
                <span class="hero-badge pending">⏳ Pending Verification</span>
            <?php endif; ?>
        </div>
        <div class="hero-meta">
            <span class="hero-badge <?= $user['account_type']==='Basic'?'basic':'' ?>"><?= $user['account_type'] ?></span>
            @<?= htmlspecialchars($user['username']) ?>
            &bull; Member since <?= date('M Y', strtotime($user['created_at'])) ?>
        </div>
    </div>
</div>

<form method="POST" action="<?= APP_URL ?>/user/profile_update.php">
<div class="row g-4">

    <!-- Left column -->
    <div class="col-lg-6">

        <!-- Personal Info -->
        <div class="profile-card profile-section">
            <div class="profile-card-head">
                <div class="head-icon" style="background:#eff4ff;">👤</div>
                <div>
                    <div class="head-title">Personal Information</div>
                </div>
            </div>
            <div class="profile-card-body">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-lbl">First Name <span class="req">*</span></label>
                        <input type="text" name="first_name" class="form-inp" value="<?= clean($user['first_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-lbl">Last Name <span class="req">*</span></label>
                        <input type="text" name="last_name" class="form-inp" value="<?= clean($user['last_name']) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-lbl">Address <span class="req">*</span></label>
                        <input type="text" name="address" class="form-inp" value="<?= clean($user['address'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-lbl">Gender</label>
                        <select name="gender" class="form-inp">
                            <option value="">-- Select --</option>
                            <option value="Male"   <?= ($user['gender']??'') === 'Male'   ? 'selected':'' ?>>Male</option>
                            <option value="Female" <?= ($user['gender']??'') === 'Female' ? 'selected':'' ?>>Female</option>
                            <option value="Other"  <?= ($user['gender']??'') === 'Other'  ? 'selected':'' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-lbl">Birthday <span class="req">*</span></label>
                        <input type="date" name="birthday" class="form-inp" value="<?= $user['birthday'] ?>" required onchange="calcAge(this.value)">
                    </div>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-lbl">Age (auto)</label>
                        <input type="number" name="age" id="ageField" class="form-inp readonly-styled" value="<?= $user['age'] ?? '' ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-lbl">TIN Number <span class="req">*</span></label>
                        <input type="text" name="tin_number" class="form-inp" value="<?= clean($user['tin_number'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-lbl">Email <span class="req">*</span></label>
                        <input type="email" name="email" class="form-inp" value="<?= clean($user['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-lbl">Contact Number <span class="req">*</span></label>
                        <input type="text" name="contact_number" class="form-inp" value="<?= clean($user['contact_number'] ?? '') ?>" placeholder="09XXXXXXXXX" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Info (read-only) -->
        <div class="profile-card profile-section">
            <div class="profile-card-head">
                <div class="head-icon" style="background:#f0fdf4;">🔒</div>
                <div>
                    <div class="head-title">Account Info</div>
                    <div class="head-sub">These fields cannot be changed</div>
                </div>
            </div>
            <div class="profile-card-body">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-lbl">Username</label>
                        <input class="form-inp readonly-styled" value="<?= clean($user['username']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-lbl">Account Type</label>
                        <input class="form-inp readonly-styled" value="<?= $user['account_type'] ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-lbl">Status</label>
                        <input class="form-inp readonly-styled" value="<?= $user['status'] ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-lbl">Member Since</label>
                        <input class="form-inp readonly-styled" value="<?= date('M d, Y', strtotime($user['created_at'])) ?>" disabled>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Right column -->
    <div class="col-lg-6">

        <!-- Bank Details -->
        <div class="profile-card profile-section">
            <div class="profile-card-head">
                <div class="head-icon" style="background:#fef9c3;">🏦</div>
                <div><div class="head-title">Bank Details</div></div>
            </div>
            <div class="profile-card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-lbl">Bank Name <span class="req">*</span></label>
                        <input type="text" name="bank_name" class="form-inp" value="<?= clean($user['bank_name'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-lbl">Bank Account Number <span class="req">*</span></label>
                        <input type="text" name="bank_account_number" class="form-inp" value="<?= clean($user['bank_account_number'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-lbl">Card Holder's Name <span class="req">*</span></label>
                        <input type="text" name="card_holder_name" class="form-inp" value="<?= clean($user['card_holder_name'] ?? '') ?>" required>
                        <div class="form-hint warn">⚠️ Make sure the name is correct to avoid transaction interruptions.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employment -->
        <div class="profile-card profile-section">
            <div class="profile-card-head">
                <div class="head-icon" style="background:#f0fdf4;">💼</div>
                <div><div class="head-title">Employment Details</div></div>
            </div>
            <div class="profile-card-body">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-lbl">Company Name <span class="req">*</span></label>
                        <input type="text" name="company_name" class="form-inp" value="<?= clean($user['company_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-lbl">Position <span class="req">*</span></label>
                        <input type="text" name="position" class="form-inp" value="<?= clean($user['position'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-lbl">Company Address <span class="req">*</span></label>
                        <input type="text" name="company_address" class="form-inp" value="<?= clean($user['company_address'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-lbl">Company Phone <span class="req">*</span></label>
                        <input type="text" name="company_phone" class="form-inp" value="<?= clean($user['company_phone'] ?? '') ?>" required>
                        <div class="form-hint">ℹ️ Provide HR number to confirm employment.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-lbl">Monthly Earnings <span class="req">*</span></label>
                        <div style="display:flex;align-items:center;gap:0;">
                            <span style="padding:9px 11px;background:#f0f4ff;border:1px solid rgba(0,0,0,.12);border-right:none;border-radius:8px 0 0 8px;font-size:13px;color:#1a45a8;font-weight:700;">₱</span>
                            <input type="number" name="monthly_earnings" class="form-inp" style="border-radius:0 8px 8px 0;" value="<?= $user['monthly_earnings'] ?? '' ?>" min="0" step="0.01" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="profile-card profile-section">
            <div class="profile-card-head">
                <div class="head-icon" style="background:#fee2e2;">🔑</div>
                <div>
                    <div class="head-title">Change Password</div>
                    <div class="head-sub">Leave blank to keep current password</div>
                </div>
            </div>
            <div class="profile-card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-lbl">Current Password</label>
                        <input type="password" name="current_password" class="form-inp" placeholder="Enter current password">
                    </div>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-lbl">New Password</label>
                        <input type="password" name="new_password" class="form-inp" placeholder="Min 8 chars, upper, lower, number">
                    </div>
                    <div class="form-group">
                        <label class="form-lbl">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-inp" placeholder="Repeat new password">
                    </div>
                </div>
                <div class="form-hint">ℹ️ Password must be at least 8 characters with uppercase, lowercase, and a number.</div>
            </div>
        </div>

    </div>

    <!-- Save Bar -->
    <div class="col-12">
        <div class="save-bar">
            <div class="save-bar-text">Make sure all required fields are filled before saving.</div>
            <button type="submit" class="btn-save">💾 Save Changes</button>
        </div>
    </div>

</div>
</form>

<script>
function calcAge(birthday) {
    if (!birthday) return;
    const dob = new Date(birthday), today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
    document.getElementById('ageField').value = age;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>