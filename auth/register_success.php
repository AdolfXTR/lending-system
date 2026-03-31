<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

// Only accessible right after registration
if (empty($_SESSION['reg_success_name'])) {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

$firstName   = $_SESSION['reg_success_name'];
$accountType = $_SESSION['reg_success_type'] ?? 'Basic';

// Clear session data
unset($_SESSION['reg_success_name'], $_SESSION['reg_success_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Application Submitted | <?= APP_NAME ?></title>
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
}
.success-card {
    background: #fff;
    border-radius: 20px;
    padding: 48px 40px;
    max-width: 520px; width: 100%;
    text-align: center;
    box-shadow: 0 24px 60px rgba(0,0,0,0.3);
}
.success-icon {
    width: 80px; height: 80px;
    background: #dcfce7;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 24px;
    font-size: 36px;
}
.success-title {
    font-size: 26px; font-weight: 700;
    color: #0f2557; margin: 0 0 8px;
}
.success-sub {
    font-size: 15px; color: #6b7280;
    margin: 0 0 28px; line-height: 1.6;
}
.account-type-badge {
    display: inline-block;
    background: #f5c842; color: #0f2557;
    font-size: 12px; font-weight: 700;
    padding: 4px 14px; border-radius: 20px;
    margin-bottom: 28px;
}
.steps {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px 24px;
    text-align: left;
    margin-bottom: 28px;
}
.steps h6 {
    font-size: 12px; font-weight: 700;
    color: #9ca3af; text-transform: uppercase;
    letter-spacing: 0.05em; margin: 0 0 14px;
}
.step-item {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    font-size: 13px; color: #374151;
}
.step-item:last-child { border-bottom: none; }
.step-num {
    width: 22px; height: 22px;
    background: #0f2557; color: #f5c842;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700;
    flex-shrink: 0; margin-top: 1px;
}
.btn-go-login {
    display: block; width: 100%;
    background: #0f2557; color: #f5c842;
    border: none; border-radius: 10px;
    padding: 14px; font-size: 15px; font-weight: 700;
    font-family: inherit; cursor: pointer;
    text-decoration: none;
    transition: background 0.15s;
}
.btn-go-login:hover { background: #1a3a7a; color: #f5c842; }
.footer-note {
    font-size: 12px; color: #9ca3af; margin-top: 16px;
}
</style>
</head>
<body>

<div class="success-card">
    <div class="success-icon">✅</div>

    <h1 class="success-title">Application Submitted!</h1>
    <p class="success-sub">
        Thank you, <strong><?= htmlspecialchars($firstName) ?>!</strong><br>
        Your membership application has been received and is now under review.
    </p>

    <div class="account-type-badge">
        <?= $accountType === 'Premium' ? '⭐ Premium Account' : '📋 Basic Account' ?>
    </div>

    <div class="steps">
        <h6>What happens next?</h6>
        <div class="step-item">
            <div class="step-num">1</div>
            <div>Admin will review your submitted documents and application details.</div>
        </div>
        <div class="step-item">
            <div class="step-num">2</div>
            <div>Your TIN and employment details will be verified with BIR and your company.</div>
        </div>
        <div class="step-item">
            <div class="step-num">3</div>
            <div>You will receive an email notification once your account is <strong>approved</strong> or <strong>rejected</strong>.</div>
        </div>
        <div class="step-item">
            <div class="step-num">4</div>
            <div>Once approved, you can log in and start applying for a loan.</div>
        </div>
    </div>

    <a href="<?= APP_URL ?>/auth/login.php" class="btn-go-login">
        <i class="bi bi-box-arrow-in-right"></i> Go to Login
    </a>

    <p class="footer-note">
        Have questions? Contact us for assistance.
    </p>
</div>

</body>
</html>