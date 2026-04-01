<?php
// ============================================================
//  auth/login.php - INDUSTRY LEVEL
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

if (isLoggedIn()) { header('Location: ' . APP_URL . '/user/dashboard.php'); exit; }
if (isAdmin())    { header('Location: ' . APP_URL . '/admin/dashboard.php'); exit; }

$error = '';

if (isset($_GET['err'])) {
    if ($_GET['err'] === 'disabled') $error = 'Your account has been disabled due to unpaid loan(s).';
    if ($_GET['err'] === 'pending')  $error = 'Your account is still pending admin approval. Please wait.';
    if ($_GET['err'] === 'rejected') $error = 'Your application was rejected. Please contact support.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter your username and password.';
    } else {
        // Check admin first
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']       = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: ' . APP_URL . '/admin/dashboard.php');
            exit;
        }

        // Check user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'Pending') {
                $error = 'Your account is still pending admin approval. Please wait.';
            } elseif ($user['status'] === 'Disabled') {
                $error = 'Your account has been disabled due to unpaid loan(s).';
            } elseif ($user['status'] === 'Rejected') {
                $error = 'Your application was rejected. Please contact support.';
            } else {
                $_SESSION['user_id']      = $user['id'];
                $_SESSION['first_name']   = $user['first_name'];
                $_SESSION['last_name']    = $user['last_name'];
                $_SESSION['account_type'] = $user['account_type'];
                $_SESSION['status']       = $user['status'];
                header('Location: ' . APP_URL . '/user/dashboard.php');
                exit;
            }
        } else {
            if (!$admin) $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | <?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<style>
* { box-sizing: border-box; }
body {
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    background: #0f2557;
    min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    padding: 20px; margin: 0;
    position: relative; overflow: hidden;
}
body::before {
    content: '';
    position: fixed; top: -150px; right: -150px;
    width: 400px; height: 400px;
    background: rgba(245,200,66,0.06);
    border-radius: 50%;
    pointer-events: none;
    animation: float 20s ease-in-out infinite;
}
body::after {
    content: '';
    position: fixed; bottom: -100px; left: -100px;
    width: 300px; height: 300px;
    background: rgba(245,200,66,0.04);
    border-radius: 50%;
    pointer-events: none;
    animation: float 15s ease-in-out infinite reverse;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -30px) scale(1.05); }
    66% { transform: translate(-20px, 20px) scale(0.95); }
}

.login-wrapper {
    display: flex;
    width: 100%; max-width: 900px;
    min-height: 520px;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(0,0,0,0.4);
    position: relative; z-index: 1;
    animation: fadeInUp 0.8s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Left panel */
.login-left {
    background: linear-gradient(145deg, #0a1a40, #1a3a7a);
    flex: 1; padding: 48px 40px;
    display: flex; flex-direction: column;
    justify-content: space-between;
    position: relative; overflow: hidden;
}
.login-left::before {
    content: '🏦';
    position: absolute; bottom: -20px; right: -20px;
    font-size: 160px; opacity: 0.06;
    line-height: 1;
}
.login-left::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 50%, rgba(245,200,66,0.08) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(245,200,66,0.05) 0%, transparent 40%),
        radial-gradient(circle at 40% 80%, rgba(245,200,66,0.06) 0%, transparent 45%);
    animation: shimmer 25s linear infinite;
    pointer-events: none;
}

@keyframes shimmer {
    0% { opacity: 0.3; }
    50% { opacity: 0.7; }
    100% { opacity: 0.3; }
}
.left-logo {
    display: flex; align-items: center; gap: 12px;
}
.left-logo .logo-box {
    width: 42px; height: 42px;
    background: #f5c842; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
}
.left-logo .logo-text {
    font-size: 18px; font-weight: 700; color: #fff;
}
.left-tagline { margin-top: auto; }
.left-tagline h2 {
    font-size: 28px; font-weight: 700;
    color: #fff; line-height: 1.3; margin: 0 0 12px;
}
.left-tagline p {
    font-size: 14px; color: rgba(255,255,255,0.6);
    margin: 0 0 28px; line-height: 1.6;
}
.left-features { display: flex; flex-direction: column; gap: 10px; }
.left-feat {
    display: flex; align-items: flex-start; gap: 12px;
    font-size: 13px; color: rgba(255,255,255,0.75);
    padding: 4px 0;
}
.left-feat strong {
    display: block;
    color: #fff;
    font-size: 14px;
    margin-bottom: 2px;
}
.feat-icon {
    width: 32px; height: 32px;
    background: rgba(245,200,66,0.15);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
    margin-top: 2px;
}

/* Quick Access Buttons */
.quick-access {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
}
.quick-btn {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 12px 8px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
}
.quick-btn:hover {
    background: #0f2557;
    border-color: #0f2557;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(15,37,87,0.2);
}
.quick-btn i {
    font-size: 20px;
}

/* Login Options */
.login-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 16px;
    font-size: 13px;
}
.form-check {
    display: flex;
    align-items: center;
    gap: 6px;
}
.form-check-input {
    width: 16px;
    height: 16px;
    border: 1.5px solid #d1d5db;
    border-radius: 4px;
    cursor: pointer;
}
.form-check-input:checked {
    background-color: #0f2557;
    border-color: #0f2557;
}
.form-check-label {
    color: #6b7280;
    font-weight: 500;
    cursor: pointer;
}
.forgot-link {
    color: #1a45a8;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
}
.forgot-link:hover {
    text-decoration: underline;
    color: #0f2557;
}

/* Right panel */
.login-right {
    background: #fff;
    width: 380px; flex-shrink: 0;
    padding: 48px 40px;
    display: flex; flex-direction: column;
    justify-content: center;
}
.login-title {
    font-size: 24px; font-weight: 700;
    color: #0f2557; margin: 0 0 6px;
}
.login-sub {
    font-size: 13px; color: #9ca3af;
    margin: 0 0 32px;
}
.field-label {
    font-size: 11px; font-weight: 700;
    color: #374151; text-transform: uppercase;
    letter-spacing: 0.05em; margin-bottom: 6px;
    display: block;
}
.input-wrap {
    position: relative; margin-bottom: 18px;
}
.input-icon {
    position: absolute; left: 13px; top: 50%;
    transform: translateY(-50%);
    color: #9ca3af; font-size: 16px; pointer-events: none;
}
.login-input {
    width: 100%; padding: 11px 42px;
    border: 1.5px solid rgba(0,0,0,0.1);
    border-radius: 9px; font-family: inherit;
    font-size: 14px; color: #1a1f2e;
    outline: none; transition: all 0.3s ease;
    background: #fff;
}
.login-input:focus {
    border-color: #1a45a8;
    box-shadow: 0 0 0 3px rgba(26,69,168,0.1), 0 0 15px rgba(26,69,168,0.2);
    transform: translateY(-1px);
}
.eye-btn {
    position: absolute; right: 12px; top: 50%;
    transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: #9ca3af; font-size: 16px; padding: 0;
    transition: color 0.15s;
}
.eye-btn:hover { color: #374151; }
.login-error {
    background: #fee2e2; color: #b91c1c;
    border: 1px solid #fecaca;
    border-radius: 9px; padding: 11px 14px;
    font-size: 13px; margin-bottom: 20px;
    display: flex; align-items: flex-start; gap: 8px;
    animation: slideDown 0.3s ease-out;
}
.login-info {
    background: #fef3c7; color: #92400e;
    border: 1px solid #fde68a;
    border-radius: 9px; padding: 11px 14px;
    font-size: 13px; margin-bottom: 20px;
    display: flex; align-items: flex-start; gap: 8px;
    animation: slideDown 0.3s ease-out;
}
.login-success {
    background: #dcfce7; color: #15803d;
    border: 1px solid #bbf7d0;
    border-radius: 9px; padding: 11px 14px;
    font-size: 13px; margin-bottom: 20px;
    display: flex; align-items: flex-start; gap: 8px;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
        max-height: 0;
    }
    to {
        opacity: 1;
        transform: translateY(0);
        max-height: 100px;
    }
}

.shake {
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}
.btn-login {
    width: 100%; background: #0f2557; color: #f5c842;
    border: none; border-radius: 9px;
    padding: 13px; font-size: 15px; font-weight: 700;
    font-family: inherit; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: all 0.3s ease; margin-top: 6px;
    position: relative;
    overflow: hidden;
}
.btn-login:hover { background: #1a3a7a; }
.btn-login:active { transform: scale(0.99); }
.btn-login.loading {
    pointer-events: none;
    opacity: 0.8;
    background: linear-gradient(45deg, #0f2557, #1a3a7a);
    background-size: 200% 200%;
    animation: gradientShift 2s ease infinite;
}
@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
.btn-login.loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    border: 2px solid transparent;
    border-top: 2px solid #f5c842;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 8px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.register-link {
    text-align: center; margin-top: 20px;
    font-size: 13px; color: #9ca3af;
}
.register-link a {
    color: #1a45a8; font-weight: 700; text-decoration: none;
}
.register-link a:hover { text-decoration: underline; }

.creator-credit {
    margin-top: 24px;
    text-align: center;
}

.credit-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
    margin-bottom: 16px;
}

.credit-text {
    font-size: 12px;
    color: #9ca3af;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-weight: 500;
}

.credit-text i {
    color: #6b7280;
    font-size: 14px;
}

.credit-text strong {
    color: #6b7280;
    font-weight: 600;
}

@media(max-width: 640px) {
    .login-left { display: none; }
    .login-right { width: 100%; border-radius: 20px; }
    .login-wrapper { border-radius: 20px; }
}
</style>
</head>
<body>

<div class="login-wrapper">

    <!-- Left Panel -->
    <div class="login-left">
        <div class="left-logo">
            <div class="logo-box">🏦</div>
            <div class="logo-text"><?= APP_NAME ?></div>
        </div>

        <div class="left-tagline">
            <h2>Your trusted<br><span>lending partner</span></h2>
            <p>Loan services for Basic and Premium members. Premium members enjoy savings and money back rewards.</p>
            <div class="left-features">
                <div class="left-feat">
                    <div class="feat-icon">💳</div>
                    <div>
                        <strong>Quick Loans</strong><br>
                        <span style="font-size:12px;opacity:0.8;">₱5,000 to ₱50,000 available</span>
                    </div>
                </div>
                <div class="left-feat">
                    <div class="feat-icon">🏦</div>
                    <div>
                        <strong>Premium Savings</strong><br>
                        <span style="font-size:12px;opacity:0.8;">Up to ₱100,000 savings account</span>
                    </div>
                </div>
                <div class="left-feat">
                    <div class="feat-icon">📊</div>
                    <div>
                        <strong>Money Back Rewards</strong><br>
                        <span style="font-size:12px;opacity:0.8;">2% cashback for premium members</span>
                    </div>
                </div>
                <div class="left-feat">
                    <div class="feat-icon">🔒</div>
                    <div>
                        <strong>Secure & Trusted</strong><br>
                        <span style="font-size:12px;opacity:0.8;">Admin-verified members only</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="login-right">
        <h1 class="login-title">Welcome back!</h1>
        <p class="login-sub">Sign in to access your account and continue your financial journey.</p>

        <?php if ($error): ?>
            <?php $isPending = str_contains($error, 'pending') || str_contains($error, 'disabled'); ?>
            <div class="<?= $isPending ? 'login-info' : 'login-error' ?>">
                <i class="bi bi-<?= $isPending ? 'info-circle' : 'exclamation-circle' ?>-fill" style="flex-shrink:0;margin-top:1px;"></i>
                <?= clean($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <label class="field-label">Username</label>
            <div class="input-wrap">
                <i class="bi bi-person input-icon"></i>
                <input type="text" name="username" class="login-input"
                    placeholder="Enter your username" required
                    value="<?= clean($_POST['username'] ?? '') ?>">
            </div>

            <label class="field-label">Password</label>
            <div class="input-wrap">
                <i class="bi bi-lock input-icon"></i>
                <input type="password" name="password" id="passwordInput"
                    class="login-input" placeholder="Enter your password" required>
                <button type="button" class="eye-btn" onclick="togglePwd()">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i> <span id="btnText">Sign In</span>
            </button>
        </form>

        <div class="register-link">
            Don't have an account?
            <a href="<?= APP_URL ?>/auth/register.php">Apply for membership</a>
        </div>

        <div class="creator-credit">
            <div class="credit-divider"></div>
            <div class="credit-text">
                <i class="bi bi-code-slash"></i>
                Created by <strong>France Adolf P. Borja</strong>
            </div>
        </div>
    </div>

</div>

<script>
function togglePwd() {
    const input = document.getElementById('passwordInput');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// Login form submission
document.querySelector('form').addEventListener('submit', function(e) {
    const btn = document.querySelector('.btn-login');
    const originalContent = btn.innerHTML;
    
    // Show loading state
    btn.classList.add('loading');
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Signing in...';
    
    // Check if there's an error after form submission
    <?php if ($error): ?>
        // If there's an error, shake the form and reset button
        setTimeout(() => {
            document.querySelector('.login-right').classList.add('shake');
            btn.classList.remove('loading');
            btn.innerHTML = originalContent;
            
            // Remove shake class after animation
            setTimeout(() => {
                document.querySelector('.login-right').classList.remove('shake');
            }, 500);
        }, 100);
    <?php endif; ?>
});

// Add shake animation to error messages
<?php if ($error): ?>
    setTimeout(() => {
        document.querySelector('.login-right').classList.add('shake');
        setTimeout(() => {
            document.querySelector('.login-right').classList.remove('shake');
        }, 500);
    }, 100);
<?php endif; ?>
</script>

</body>
</html>