<?php
// ============================================================
//  includes/security.php — Security & Validation Functions
// ============================================================

/**
 * Load environment variables from .env file
 */
function loadEnv(string $path = __DIR__ . '/../.env'): void {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Remove quotes if present
        if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
            $value = substr($value, 1, -1);
        } elseif (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1) {
            $value = substr($value, 1, -1);
        }
        
        if (!isset($_ENV[$key]) && !getenv($key)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

/**
 * Get environment variable with fallback
 */
function env(string $key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? null;
    }
    return $value !== null ? $value : $default;
}

/**
 * Validate and sanitize email address
 */
function validateEmail(string $email): array {
    $email = trim(strtolower($email));
    $errors = [];
    
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    } elseif (strlen($email) > 255) {
        $errors[] = 'Email must not exceed 255 characters.';
    }
    
    return ['valid' => empty($errors), 'value' => $email, 'errors' => $errors];
}

/**
 * Validate username
 */
function validateUsername(string $username): array {
    $username = trim($username);
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 6) {
        $errors[] = 'Username must be at least 6 characters.';
    } elseif (strlen($username) > 32) {
        $errors[] = 'Username must not exceed 32 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores.';
    }
    
    return ['valid' => empty($errors), 'value' => $username, 'errors' => $errors];
}

/**
 * Validate password strength
 */
function validatePassword(string $password, bool $confirmMatch = true, string $confirm = ''): array {
    $errors = [];
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } else {
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (strlen($password) > 128) {
            $errors[] = 'Password must not exceed 128 characters.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        if ($confirmMatch && $password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }
    }
    
    return ['valid' => empty($errors), 'errors' => $errors];
}

/**
 * Validate Philippine mobile number
 */
function validatePhoneNumber(string $phone): array {
    $phone = trim($phone);
    $errors = [];
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    } elseif (!preg_match('/^(09|\+639)\d{9}$/', $phone)) {
        $errors[] = 'Invalid Philippine mobile number format (e.g., 09XXXXXXXXX or +639XXXXXXXXX).';
    }
    
    return ['valid' => empty($errors), 'value' => $phone, 'errors' => $errors];
}

/**
 * Validate name fields
 */
function validateName(string $name, string $fieldName = 'Name'): array {
    $name = trim($name);
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "$fieldName is required.";
    } elseif (strlen($name) < 2) {
        $errors[] = "$fieldName must be at least 2 characters.";
    } elseif (strlen($name) > 100) {
        $errors[] = "$fieldName must not exceed 100 characters.";
    } elseif (!preg_match('/^[a-zA-Z\s\-\.\']+$/', $name)) {
        $errors[] = "$fieldName contains invalid characters.";
    }
    
    return ['valid' => empty($errors), 'value' => $name, 'errors' => $errors];
}

/**
 * Validate numeric amount
 */
function validateAmount($amount, float $min = 0, float $max = PHP_FLOAT_MAX): array {
    $errors = [];
    
    if (!is_numeric($amount)) {
        $errors[] = 'Amount must be a valid number.';
    } else {
        $amount = (float)$amount;
        if ($amount < $min) {
            $errors[] = "Amount must be at least " . formatMoney($min) . ".";
        }
        if ($amount > $max) {
            $errors[] = "Amount must not exceed " . formatMoney($max) . ".";
        }
    }
    
    return ['valid' => empty($errors), 'value' => $amount ?? 0, 'errors' => $errors];
}

/**
 * Validate date
 */
function validateDate(string $date, string $format = 'Y-m-d'): array {
    $errors = [];
    
    if (empty($date)) {
        $errors[] = 'Date is required.';
    } else {
        $d = DateTime::createFromFormat($format, $date);
        if (!$d || $d->format($format) !== $date) {
            $errors[] = 'Invalid date format.';
        }
    }
    
    return ['valid' => empty($errors), 'value' => $date, 'errors' => $errors];
}

/**
 * Validate age
 */
function validateAge(int $age, int $min = 18, int $max = 120): array {
    $errors = [];
    
    if ($age < $min) {
        $errors[] = "You must be at least {$min} years old.";
    } elseif ($age > $max) {
        $errors[] = "Invalid age.";
    }
    
    return ['valid' => empty($errors), 'errors' => $errors];
}

/**
 * Validate text field with max length
 */
function validateText(string $text, string $fieldName = 'Field', int $maxLength = 255, bool $required = true): array {
    $text = trim($text);
    $errors = [];
    
    if ($required && empty($text)) {
        $errors[] = "$fieldName is required.";
    } elseif (strlen($text) > $maxLength) {
        $errors[] = "$fieldName must not exceed {$maxLength} characters.";
    }
    
    return ['valid' => empty($errors), 'value' => $text, 'errors' => $errors];
}

/**
 * Sanitize file name
 */
function sanitizeFileName(string $filename): string {
    // Remove path components and dangerous characters
    $filename = basename($filename);
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    
    // Prevent double extensions
    $filename = preg_replace('/\.(?!(jpg|jpeg|png|gif|pdf)$)[^.]*$/i', '', $filename);
    
    return strtolower($filename);
}

/**
 * Validate file upload
 */
function validateFileUpload(array $file, array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'], int $maxSize = 5242880): array {
    $errors = [];
    
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        $errors[] = 'No file uploaded.';
        return ['valid' => false, 'errors' => $errors];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload failed with error code: ' . $file['error'];
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $errors[] = 'File size exceeds maximum allowed (' . ($maxSize / 1024 / 1024) . 'MB).';
    }
    
    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes);
    }
    
    // Check for PHP code in file
    $content = file_get_contents($file['tmp_name']);
    if (preg_match('/<\?php|<\?=|<\?/i', $content)) {
        $errors[] = 'File contains potentially dangerous content.';
    }
    
    return ['valid' => empty($errors), 'mime_type' => $mimeType, 'errors' => $errors];
}

/**
 * Rate limiting check
 */
function checkRateLimit(string $key, int $maxRequests = 60, int $windowSeconds = 60): bool {
    $sessionKey = 'rate_limit_' . $key;
    $now = time();
    
    if (!isset($_SESSION[$sessionKey])) {
        $_SESSION[$sessionKey] = ['count' => 1, 'start' => $now];
        return true;
    }
    
    $data = $_SESSION[$sessionKey];
    
    // Reset if window has passed
    if ($now - $data['start'] > $windowSeconds) {
        $_SESSION[$sessionKey] = ['count' => 1, 'start' => $now];
        return true;
    }
    
    // Check limit
    if ($data['count'] >= $maxRequests) {
        return false;
    }
    
    $_SESSION[$sessionKey]['count']++;
    return true;
}

/**
 * Generate and validate CSRF token
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Enhanced XSS protection - stricter than clean()
 */
function escapeHtml(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Strip all HTML tags
 */
function stripAllTags(string $text): string {
    return strip_tags($text);
}

/**
 * Check for suspicious patterns in input
 */
function containsMaliciousContent(string $input): bool {
    $patterns = [
        '/<script/i',
        '/javascript:/i',
        '/on\w+\s*=/i',
        '/<iframe/i',
        '/<object/i',
        '/<embed/i',
        '/eval\s*\(/i',
        '/expression\s*\(/i',
        '/url\s*\(/i',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Comprehensive input sanitization
 */
function sanitizeInput($input, string $type = 'string') {
    if (is_array($input)) {
        return array_map(function($item) use ($type) {
            return sanitizeInput($item, $type);
        }, $input);
    }
    
    switch ($type) {
        case 'email':
            return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT) !== false ? (int)$input : 0;
        case 'float':
            return filter_var($input, FILTER_VALIDATE_FLOAT) !== false ? (float)$input : 0.0;
        case 'bool':
            return filter_var($input, FILTER_VALIDATE_BOOLEAN);
        case 'url':
            return filter_var(trim($input), FILTER_SANITIZE_URL);
        case 'html':
            // Allow specific safe HTML tags
            return strip_tags(trim($input), '<p><br><strong><em><ul><ol><li>');
        default:
            return trim($input);
    }
}
