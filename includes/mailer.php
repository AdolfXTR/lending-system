    <?php
// ============================================================
//  includes/mailer.php
// ============================================================

/**
 * Send an email using PHP's built-in mail() function.
 * For production, swap this out with PHPMailer + SMTP.
 *
 * @param string $to      Recipient email
 * @param string $subject Email subject
 * @param string $body    HTML email body
 * @return bool
 */
function sendMail(string $to, string $subject, string $body): bool
{
    // If mail is disabled in config, just return true silently (dev mode)
    if (defined('MAIL_ENABLED') && !MAIL_ENABLED) {
        return true;
    }

    $from    = defined('MAIL_FROM')      ? MAIL_FROM      : 'no-reply@lendingsystem.com';
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Lending System';

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Wrap body in a simple HTML shell if it isn't already
    if (stripos($body, '<html') === false) {
        $body = "<!DOCTYPE html><html><body>{$body}</body></html>";
    }

    // On XAMPP / local dev, mail() usually won't actually send —
    // that's fine. It returns false but won't break the app.
    try {
        mail($to, $subject, $body, $headers);
    } catch (Throwable $e) {
        // Silently swallow mail errors so the rest of the app continues
        return false;
    }

    return true;
}