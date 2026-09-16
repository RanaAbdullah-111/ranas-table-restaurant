<?php
include("config.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['email'])) {
    echo json_encode(['message' => 'Invalid request.']);
    exit;
}

$email = trim($_POST['email']);

// Check user exists and is not verified
$stmt = $connection->prepare("SELECT id, name, is_verified FROM `uesr` WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['message' => 'Email not found.']);
    exit;
}

$user = $result->fetch_assoc();

if ($user['is_verified'] == 1) {
    echo json_encode(['message' => 'This account is already verified.']);
    exit;
}

// Generate a fresh token
$token = bin2hex(random_bytes(32));
$upd   = $connection->prepare("UPDATE `uesr` SET verify_token = ? WHERE id = ?");
$upd->bind_param("si", $token, $user['id']);
$upd->execute();

$verify_link = "http://yourdomain.com/verify.php?token=" . $token; // ← change domain
$name        = $user['name'];

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'ranaabrazzaq7@gmail.com';
    $mail->Password   = 'YOUR_APP_PASSWORD';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('ranaabrazzaq7@gmail.com', "Rana's Table");
    $mail->addAddress($email, $name);
    $mail->isHTML(true);
    $mail->Subject = "Verify your Rana's Table account";
    $mail->Body    = "
        <div style='font-family:Georgia,serif;background:#1A0A00;padding:40px;text-align:center;'>
            <div style='max-width:480px;margin:0 auto;background:rgba(45,21,0,0.9);border:0.5px solid rgba(201,168,76,0.3);padding:40px;'>
                <div style='width:60px;height:60px;border:0.5px solid rgba(201,168,76,0.5);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:20px;'>
                    <span style='font-size:26px;font-weight:700;color:#C9A84C;'>R</span>
                </div>
                <h2 style='font-family:Georgia,serif;color:#F5EDD6;font-weight:400;font-size:22px;margin-bottom:8px;'>Verify your <span style='color:#C9A84C;font-style:italic;'>Email</span></h2>
                <p style='color:rgba(245,237,214,0.55);font-size:14px;margin-bottom:28px;line-height:1.8;'>Hello {$name}, here is your new verification link.</p>
                <a href='{$verify_link}' style='display:inline-block;background:#C9A84C;color:#1A0A00;padding:14px 36px;font-family:Georgia,serif;font-size:13px;font-weight:700;letter-spacing:3px;text-transform:uppercase;text-decoration:none;'>Verify My Account</a>
                <p style='color:rgba(245,237,214,0.28);font-size:12px;margin-top:24px;'>Link expires in 24 hours.</p>
            </div>
        </div>
    ";
    $mail->AltBody = "Verify your account: $verify_link";
    $mail->send();
    echo json_encode(['message' => 'Verification email sent! Check your inbox.']);
} catch (Exception $e) {
    echo json_encode(['message' => 'Could not send email. Please try again.']);
}