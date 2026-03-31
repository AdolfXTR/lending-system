<?php
// ============================================================
//  auth/login.php
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

if (isLoggedIn()) { header('Location: ' . APP_URL . '/user/dashboard.php'); exit; }
if (isAdmin())    { header('Location: ' . APP_URL . '/admin/dashboard.php'); exit; }

$error = '';

if (isset($_GET['err'])) {
    if ($_GET['err'] === 'disabled') $error = 'Your account has been disabled due to unpaid loan(s).';
    if ($_GET['err'] === 'pending')  $error = 'Your account is still pending admin approval. Please wait.';
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
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
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
}
body::after {
    content: '';
    position: fixed; bottom: -100px; left: -100px;
    width: 300px; height: 300px;
    background: rgba(245,200,66,0.04);
    border-radius: 50%;
    pointer-events: none;
}

.login-wrapper {
    display: flex;
    width: 100%; max-width: 900px;
    min-height: 520px;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(0,0,0,0.4);
    position: relative; z-index: 1;
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
.left-tagline h2 span { color: #f5c842; }
.left-tagline p {
    font-size: 14px; color: rgba(255,255,255,0.6);
    margin: 0 0 28px; line-height: 1.6;
}
.left-features { display: flex; flex-direction: column; gap: 10px; }
.left-feat {
    display: flex; align-items: center; gap: 10px;
    font-size: 13px; color: rgba(255,255,255,0.75);
}
.feat-icon {
    width: 28px; height: 28px;
    background: rgba(245,200,66,0.15);
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; flex-shrink: 0;
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
    outline: none; transition: border-color 0.15s, box-shadow 0.15s;
    background: #fff;
}
.login-input:focus {
    border-color: #1a45a8;
    box-shadow: 0 0 0 3px rgba(26,69,168,0.1);
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
}
.login-info {
    background: #fef3c7; color: #92400e;
    border: 1px solid #fde68a;
    border-radius: 9px; padding: 11px 14px;
    font-size: 13px; margin-bottom: 20px;
    display: flex; align-items: flex-start; gap: 8px;
}
.btn-login {
    width: 100%; background: #0f2557; color: #f5c842;
    border: none; border-radius: 9px;
    padding: 13px; font-size: 15px; font-weight: 700;
    font-family: inherit; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: background 0.15s; margin-top: 6px;
}
.btn-login:hover { background: #1a3a7a; }
.btn-login:active { transform: scale(0.99); }
.register-link {
    text-align: center; margin-top: 20px;
    font-size: 13px; color: #9ca3af;
}
.register-link a {
    color: #1a45a8; font-weight: 700; text-decoration: none;
}
.register-link a:hover { text-decoration: underline; }

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
                    Loan from ₱5,000 up to ₱10,000 initial — max ₱50,000
                </div>
                <div class="left-feat">
                    <div class="feat-icon">🏦</div>
                    Premium members: savings account up to ₱100,000
                </div>
                <div class="left-feat">
                    <div class="feat-icon">📊</div>
                    Premium members: 2% money back from company income
                </div>
                <div class="left-feat">
                    <div class="feat-icon">🔒</div>
                    Admin-verified members only — secure & trusted
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="login-right">
        <h1 class="login-title">Welcome back!</h1>
        <p class="login-sub">Sign in to your account to continue.</p>

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
                <i class="bi bi-box-arrow-in-right"></i> Sign In
            </button>
        </form>

        <div class="register-link">
            Don't have an account?
            <a href="<?= APP_URL ?>/auth/register.php">Apply for membership</a>
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
</script>
</body>
</html>