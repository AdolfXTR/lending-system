<?php
// user/profile_update.php
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/user/profile.php'); exit;
}

$userId = $_SESSION['user_id'];
$errors = [];

$fields = [
    'first_name','last_name','address','gender','birthday',
    'email','contact_number','tin_number',
    'bank_name','bank_account_number','card_holder_name',
    'company_name','company_address','company_phone',
    'position','monthly_earnings'
];
$data = [];
foreach ($fields as $f) $data[$f] = trim($_POST[$f] ?? '');

$required = [
    'first_name','last_name','address','birthday','email',
    'contact_number','tin_number','bank_name','bank_account_number',
    'card_holder_name','company_name','company_address','company_phone',
    'position','monthly_earnings'
];
foreach ($required as $f) {
    if (empty($data[$f])) $errors[] = ucfirst(str_replace('_',' ',$f)) . ' is required.';
}

if (!empty($data['email'])) {
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$data['email'], $userId]);
        if ($stmt->fetch()) $errors[] = 'Email is already used by another account.';

        $stmt = $pdo->prepare("SELECT id FROM blocked_emails WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) $errors[] = 'This email address is blocked.';
    }
}

if (!empty($data['contact_number'])) {
    if (!preg_match('/^(09|\+639)\d{9}$/', $data['contact_number'])) {
        $errors[] = 'Contact number must be a valid Philippine mobile number (e.g. 09XXXXXXXXX).';
    }
}

$age = null;
if (!empty($data['birthday'])) {
    $age = calculateAge($data['birthday']);
    if ($age < 18) $errors[] = 'You must be at least 18 years old.';
}

// Password change — special character requirement REMOVED
$newPasswordHash = null;
$currentPassword = trim($_POST['current_password'] ?? '');
$newPassword     = trim($_POST['new_password'] ?? '');
$confirmPassword = trim($_POST['confirm_password'] ?? '');

if ($newPassword !== '') {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    if (!password_verify($currentPassword, $row['password'])) {
        $errors[] = 'Current password is incorrect.';
    } else {
        if (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $newPassword)) {
            $errors[] = 'New password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $newPassword)) {
            $errors[] = 'New password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/', $newPassword)) {
            $errors[] = 'New password must contain at least one number.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        } else {
            $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        }
    }
}

if (!empty($errors)) {
    setFlash('danger', implode('<br>', $errors));
    header('Location: ' . APP_URL . '/user/profile.php'); exit;
}

if ($newPasswordHash) {
    $stmt = $pdo->prepare("
        UPDATE users SET
            first_name=?,last_name=?,address=?,gender=?,
            birthday=?,age=?,email=?,contact_number=?,
            tin_number=?,bank_name=?,bank_account_number=?,
            card_holder_name=?,company_name=?,company_address=?,
            company_phone=?,position=?,monthly_earnings=?,password=?
        WHERE id=?
    ");
    $stmt->execute([
        $data['first_name'],$data['last_name'],$data['address'],
        $data['gender']?:null,$data['birthday'],$age,
        $data['email'],$data['contact_number'],$data['tin_number'],
        $data['bank_name'],$data['bank_account_number'],$data['card_holder_name'],
        $data['company_name'],$data['company_address'],$data['company_phone'],
        $data['position'],$data['monthly_earnings'],
        $newPasswordHash,$userId
    ]);
} else {
    $stmt = $pdo->prepare("
        UPDATE users SET
            first_name=?,last_name=?,address=?,gender=?,
            birthday=?,age=?,email=?,contact_number=?,
            tin_number=?,bank_name=?,bank_account_number=?,
            card_holder_name=?,company_name=?,company_address=?,
            company_phone=?,position=?,monthly_earnings=?
        WHERE id=?
    ");
    $stmt->execute([
        $data['first_name'],$data['last_name'],$data['address'],
        $data['gender']?:null,$data['birthday'],$age,
        $data['email'],$data['contact_number'],$data['tin_number'],
        $data['bank_name'],$data['bank_account_number'],$data['card_holder_name'],
        $data['company_name'],$data['company_address'],$data['company_phone'],
        $data['position'],$data['monthly_earnings'],$userId
    ]);
}

$_SESSION['first_name'] = $data['first_name'];
$_SESSION['last_name']  = $data['last_name'];

setFlash('success', 'Profile updated successfully!');
header('Location: ' . APP_URL . '/user/profile.php');
exit;