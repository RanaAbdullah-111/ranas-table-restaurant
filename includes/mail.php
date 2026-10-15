<?php
/**
 * Centralized Mail Helper for Rana's Table
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $autoloadPaths = [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/vendor/autoload.php'
    ];
    foreach ($autoloadPaths as $p) {
        if (file_exists($p)) {
            require_once $p;
            break;
        }
    }
}

/**
 * Send an email using application SMTP configuration.
 *
 * @param string $toEmail Recipient email address
 * @param string $toName Recipient name
 * @param string $subject Email subject
 * @param string $htmlBody HTML content
 * @param string $altBody Plain-text fallback content
 * @return bool True if sent successfully, false otherwise
 */
function sendAppMail(string $toEmail, string $toName, string $subject, string $htmlBody, string $altBody = ''): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ranaabrazzaq7@gmail.com';
        $mail->Password   = 'bfvp fqdc pjbz wzhe';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('ranaabrazzaq7@gmail.com', "Rana's Table");
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body    = $htmlBody;
        $mail->AltBody = $altBody ?: strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer error: " . $e->getMessage());
        return false;
    }
}
