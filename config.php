<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Database ─────────────────────────────────────────────────
define('DB_HOST',     getenv('MYSQLHOST')     ?: 'localhost');
define('DB_USER',     getenv('MYSQLUSER')     ?: 'root');
define('DB_PASS',     getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME',     getenv('MYSQLDATABASE') ?: 'lending_system');
define('DB_PORT',     getenv('MYSQLPORT')     ?: '3306');
define('DB_CHARSET',  'utf8mb4');

// ── App Settings ─────────────────────────────────────────────
define('APP_NAME',    'Lending System');
define('APP_URL',     getenv('APP_URL')       ?: 'http://localhost/lending_system');
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
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Ensure uploads directory exists
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
?>