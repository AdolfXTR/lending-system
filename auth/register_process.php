<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/auth/register.php');
    exit;
}

$errors = [];
$old    = [];

$fields = [
    'account_type','username','password','first_name','last_name',
    'address','gender','birthday','age','email','contact_number',
    'bank_name','bank_account_number','card_holder_name',
    'tin_number','company_name','company_address','company_phone',
    'position','monthly_earnings'
];
foreach ($fields as $f) {
    $old[$f] = trim($_POST[$f] ?? '');
}

// Account Type
if (!in_array($old['account_type'], ['Basic', 'Premium'])) {
    $errors[] = 'Please select a valid account type.';
}
if ($old['account_type'] === 'Premium' && !premiumSlotsAvailable($pdo)) {
    $errors[] = 'Premium slots are full (max ' . PREMIUM_MAX_SLOTS . '). Please choose Basic.';
}

// Username
if (strlen($old['username']) < 6) {
    $errors[] = 'Username must be at least 6 characters.';
} else {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$old['username']]);
    if ($stmt->fetch()) $errors[] = 'Username is already taken.';
}

// Password — min 8, upper, lower, number (no special char required)
$pwd = $old['password'];
if (strlen($pwd) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
} elseif (!preg_match('/[A-Z]/', $pwd)) {
    $errors[] = 'Password must contain at least one uppercase letter.';
} elseif (!preg_match('/[a-z]/', $pwd)) {
    $errors[] = 'Password must contain at least one lowercase letter.';
} elseif (!preg_match('/[0-9]/', $pwd)) {
    $errors[] = 'Password must contain at least one number.';
}

// Required fields
$required = ['first_name','last_name','address','birthday','email',
             'contact_number','bank_name','bank_account_number',
             'card_holder_name','tin_number','company_name',
             'company_address','company_phone','position','monthly_earnings'];
foreach ($required as $f) {
    if (empty($old[$f])) {
        $errors[] = ucfirst(str_replace('_', ' ', $f)) . ' is required.';
    }
}

// Email
if (!empty($old['email'])) {
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM blocked_emails WHERE email = ?");
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) $errors[] = 'This email address is blocked from registration.';

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) $errors[] = 'Email is already registered.';
    }
}

// Contact number
if (!empty($old['contact_number'])) {
    if (!preg_match('/^(09|\+639)\d{9}$/', $old['contact_number'])) {
        $errors[] = 'Contact number must be a valid Philippine mobile number (e.g. 09XXXXXXXXX).';
    }
}

// Birthday / Age
if (!empty($old['birthday'])) {
    $age = calculateAge($old['birthday']);
    $old['age'] = $age;
    if ($age < 18) $errors[] = 'You must be at least 18 years old to register.';
}

// Uploads
$uploadFields  = ['proof_of_billing', 'valid_id', 'coe'];
$uploadedPaths = [];

foreach ($uploadFields as $field) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
    } else {
        $path = uploadFile($_FILES[$field], $field);
        if ($path === false) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ': Invalid file. Use JPG, PNG or PDF under 5MB.';
        } else {
            $uploadedPaths[$field] = $path;
        }
    }
}

// Return errors to register page
if (!empty($errors)) {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['register_old']    = $old;
    header('Location: ' . APP_URL . '/auth/register.php');
    exit;
}

// Insert user
$hashedPwd = password_hash($old['password'], PASSWORD_BCRYPT);

$stmt = $pdo->prepare("
    INSERT INTO users (
        username, password, account_type, status,
        first_name, last_name, address, gender, birthday, age,
        email, contact_number,
        bank_name, bank_account_number, card_holder_name,
        tin_number, company_name, company_address, company_phone,
        position, monthly_earnings,
        proof_of_billing, valid_id, coe
    ) VALUES (
        :username, :password, :account_type, 'Pending',
        :first_name, :last_name, :address, :gender, :birthday, :age,
        :email, :contact_number,
        :bank_name, :bank_account_number, :card_holder_name,
        :tin_number, :company_name, :company_address, :company_phone,
        :position, :monthly_earnings,
        :proof_of_billing, :valid_id, :coe
    )
");

$stmt->execute([
    ':username'            => $old['username'],
    ':password'            => $hashedPwd,
    ':account_type'        => $old['account_type'],
    ':first_name'          => $old['first_name'],
    ':last_name'           => $old['last_name'],
    ':address'             => $old['address'],
    ':gender'              => $old['gender'] ?: null,
    ':birthday'            => $old['birthday'],
    ':age'                 => $old['age'],
    ':email'               => $old['email'],
    ':contact_number'      => $old['contact_number'],
    ':bank_name'           => $old['bank_name'],
    ':bank_account_number' => $old['bank_account_number'],
    ':card_holder_name'    => $old['card_holder_name'],
    ':tin_number'          => $old['tin_number'],
    ':company_name'        => $old['company_name'],
    ':company_address'     => $old['company_address'],
    ':company_phone'       => $old['company_phone'],
    ':position'            => $old['position'],
    ':monthly_earnings'    => $old['monthly_earnings'],
    ':proof_of_billing'    => $uploadedPaths['proof_of_billing'],
    ':valid_id'            => $uploadedPaths['valid_id'],
    ':coe'                 => $uploadedPaths['coe'],
]);

// Store name for success page
$_SESSION['reg_success_name'] = $old['first_name'];
$_SESSION['reg_success_type'] = $old['account_type'];

header('Location: ' . APP_URL . '/auth/register_success.php');
exit;