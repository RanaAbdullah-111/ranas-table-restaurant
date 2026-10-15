<?php
date_default_timezone_set('Asia/Karachi');
include("config.php");
require_once __DIR__ . '/includes/mail.php';

$success_msg = "";
$error_msg   = "";

if(isset($_POST['send'])){
    $email = trim($_POST['email']);

    $stmt = $connection->prepare("SELECT * FROM uesr WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $otp    = rand(100000, 999999);
        $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        $upd = $connection->prepare("UPDATE uesr SET otp=?, reset_expires=? WHERE email=?");
        $upd->bind_param("sss", $otp, $expiry, $email);

        if($upd->execute()){
            $subject = 'Password Reset OTP — Rana\'s Table';
            $body = '
            <div style="font-family:Georgia,serif;background:#110600;padding:40px 20px;min-height:100vh">
              <div style="max-width:480px;margin:0 auto;background:rgba(30,12,0,0.97);border:0.5px solid rgba(201,168,76,0.25);padding:40px 36px">
                <h2 style="font-family:Georgia,serif;font-size:28px;color:#F5EDD6;margin-bottom:6px">Rana\'s <em style="color:#C9A84C">Table</em></h2>
                <p style="font-size:11px;letter-spacing:4px;text-transform:uppercase;color:rgba(201,168,76,0.5);margin-bottom:28px">Password Reset</p>
                <p style="font-size:16px;font-weight:300;color:rgba(245,237,214,0.7);margin-bottom:24px;line-height:1.8">Your one-time password (OTP) for resetting your account password is:</p>
                <div style="text-align:center;margin:28px 0">
                  <span style="font-family:Georgia,serif;font-size:44px;letter-spacing:12px;color:#C9A84C;font-weight:700">' . $otp . '</span>
                </div>
                <p style="font-size:13px;font-weight:300;color:rgba(245,237,214,0.45);line-height:1.8;border-top:0.5px solid rgba(201,168,76,0.15);padding-top:20px;margin-top:8px">This OTP expires in <strong style="color:#C9A84C">5 minutes</strong>. Do not share it with anyone.</p>
              </div>
            </div>';
            $altBody = 'Your OTP is: ' . $otp . '. Expires in 5 minutes.';

            if(sendAppMail($email, "Customer", $subject, $body, $altBody)){
                header("location: reset_password.php");
                exit();
            } else {
                $error_msg = "Could not send OTP email. Please try again.";
            }
        } else {
            $error_msg = "Something went wrong. Please try again.";
        }
    } else {
        $error_msg = "No account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Rana's Table</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

<div class="bg-fixed"></div>
<div class="candles" id="candleStrip"></div>

<div class="card">
    <div class="logo-wrap">
        <div class="logo-ring"><span class="logo-r">R</span></div>
        <div class="brand">Rana's <em>Table</em></div>
        <div class="brand-tag">Password Recovery</div>
    </div>

    <div class="divider"><div class="div-line"></div><div class="div-gem"></div><div class="div-line"></div></div>

    <p class="card-desc">Enter your email address and we'll send you a 6-digit OTP to reset your password.</p>

    <?php if($error_msg): ?>
    <div class="banner error shake">⚠ &nbsp;<?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <form action="" method="POST" id="forgotForm">
        <div class="field">
            <label for="f_email">Email Address</label>
            <div class="input-wrap">
                <span class="i-icon">✉</span>
                <input type="email" id="f_email" name="email"
                       placeholder="you@example.com"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       required autocomplete="email">
                <div class="focus-bar"></div>
            </div>
        </div>

        <button type="submit" name="send" class="btn-send" id="sendBtn">
            <span class="btn-text">Send OTP &nbsp;→</span>
            <div class="spinner"></div>
        </button>
    </form>

    <div class="otp-info">
        <span class="otp-info-icon">🔒</span>
        <span class="otp-info-text">A 6-digit OTP will be sent to your email. It will expire in <strong style="color:rgba(201,168,76,0.75)">5 minutes</strong>. Check your spam folder if you don't see it.</span>
    </div>

    <div class="back-link">
        Remembered your password? <a href="login.php">Sign in</a>
    </div>

    <div class="card-footer-line">
        <div class="div-line"></div><div class="div-gem"></div><div class="div-line"></div>
    </div>
</div>

<script src="assets/js/auth.js"></script>
</body>
</html>