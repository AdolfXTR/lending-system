<?php
// ============================================================
//  config.php — place at root: lending_system/config.php
// ============================================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Database ─────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'lending_system');
define('DB_USER', 'root');       // default XAMPP username
define('DB_PASS', '');           // default XAMPP password (empty)
define('DB_CHARSET', 'utf8mb4');

// ── App Settings ─────────────────────────────────────────────
define('APP_NAME',    'Lending System');
define('APP_URL',     'http://localhost/lending_system'); // change if needed
define('APP_VERSION', '1.0.0');

// ── Loan Rules ───────────────────────────────────────────────
define('LOAN_MIN',              5000);
define('LOAN_MAX',              10000);
define('LOAN_INCREMENT',        5000);   // increases by 5k on good standing
define('LOAN_ABSOLUTE_MAX',     50000);  // can never exceed this
define('LOAN_INTEREST_RATE',    0.03);   // 3%
define('LOAN_PENALTY_RATE',     0.02);   // 2% for missed payment
define('LOAN_DUE_DAYS',         28);     // due date = release date + 28 days

// ── Payable Months Options ───────────────────────────────────
define('LOAN_TERM_OPTIONS',     serialize([1, 3, 6, 12]));
define('LOAN_TERM_INCREMENT',   3);      // increases by 3 months on good standing
define('LOAN_TERM_MAX',         32);     // max payable months

// ── Savings Rules ────────────────────────────────────────────
define('SAVINGS_MAX',               100000);
define('SAVINGS_DEPOSIT_MIN',       100);
define('SAVINGS_DEPOSIT_MAX',       1000);
define('SAVINGS_WITHDRAW_MIN',      500);
define('SAVINGS_WITHDRAW_MAX',      5000);
define('SAVINGS_WITHDRAW_DAILY',    5);      // max 5 withdrawals per day
define('SAVINGS_MAX_WITHDRAW_DAY',  5000);   // max withdrawal amount per day
define('SAVINGS_ZERO_MONTHS',       3);      // downgrade after 3 months at 0

// ── Premium Slots ────────────────────────────────────────────
define('PREMIUM_MAX_SLOTS', 50);

// ── Money Back ───────────────────────────────────────────────
define('MONEYBACK_RATE', 0.02);  // 2% of company income

// ── Upload Settings ──────────────────────────────────────────
define('UPLOAD_PATH',    __DIR__ . '/uploads/');
define('UPLOAD_MAX_MB',  5);
define('UPLOAD_ALLOWED', serialize(['image/jpeg', 'image/png', 'application/pdf']));

// ── Email (PHPMailer) ────────────────────────────────────────
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_USERNAME', 'your_email@gmail.com');   // change this
define('MAIL_PASSWORD', 'your_app_password');       // use Gmail App Password
define('MAIL_FROM',     'your_email@gmail.com');
define('MAIL_FROM_NAME', APP_NAME);

// ── Timezone ─────────────────────────────────────────────────
date_default_timezone_set('Asia/Manila');

// ── Load DB connection ────────────────────────────────────────
require_once __DIR__ . '/db.php';