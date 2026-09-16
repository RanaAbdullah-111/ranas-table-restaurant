<?php
session_start();
date_default_timezone_set('Asia/Karachi');
include("config.php");
// goole 
require 'google_config.php';

$login_url = $client->createAuthUrl();
// google  end
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error         = '';
$show_otp_form = false;

/*
|--------------------------------------------------------------------------
| Helper — Send OTP Email via PHPMailer
|--------------------------------------------------------------------------
*/
function sendOtpEmail(string $email, string $name, string $otp): bool {
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
        $mail->addAddress($email, $name);
        $mail->Subject = 'Your Verification Code — Rana\'s Table';
        $mail->isHTML(true);

        $mail->Body = '
        <div style="font-family:Georgia,serif;background:#110600;padding:48px 20px;min-height:100vh">
          <div style="max-width:480px;margin:0 auto;background:rgba(30,12,0,0.98);border:0.5px solid rgba(201,168,76,0.28);padding:44px 40px">
            <div style="text-align:center;margin-bottom:30px">
              <div style="width:60px;height:60px;border:0.5px solid rgba(201,168,76,0.45);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px">
                <span style="font-size:26px;font-weight:700;color:#C9A84C">R</span>
              </div>
              <h2 style="font-family:Georgia,serif;font-size:22px;font-weight:400;color:#F5EDD6;margin:0">Rana\'s <em style="color:#C9A84C">Table</em></h2>
              <p style="font-size:10px;letter-spacing:4px;text-transform:uppercase;color:rgba(201,168,76,0.45);margin:6px 0 0">Account Verification</p>
            </div>
            <div style="height:0.5px;background:linear-gradient(to right,transparent,rgba(201,168,76,0.3),transparent);margin-bottom:28px"></div>
            <p style="font-size:16px;font-weight:300;color:rgba(245,237,214,0.75);line-height:1.85;margin-bottom:8px">Hello <strong style="color:#F5EDD6;font-weight:400">' . htmlspecialchars($name) . '</strong>,</p>
            <p style="font-size:15px;font-weight:300;color:rgba(245,237,214,0.6);line-height:1.85;margin-bottom:28px">Use the code below to verify your email address and activate your account.</p>
            <div style="background:rgba(201,168,76,0.07);border:0.5px solid rgba(201,168,76,0.25);padding:28px 20px;text-align:center;margin-bottom:28px">
              <p style="font-size:10px;letter-spacing:4px;text-transform:uppercase;color:rgba(201,168,76,0.5);margin:0 0 12px">Your Verification Code</p>
              <div style="font-family:Georgia,serif;font-size:48px;font-weight:700;letter-spacing:14px;color:#C9A84C;line-height:1">' . $otp . '</div>
            </div>
            <p style="font-size:13px;font-weight:300;color:rgba(245,237,214,0.38);line-height:1.8;text-align:center;margin-bottom:20px">
              This code expires in <strong style="color:rgba(201,168,76,0.6);font-weight:400">10 minutes</strong>.<br>Never share this code with anyone.
            </p>
            <div style="height:0.5px;background:linear-gradient(to right,transparent,rgba(201,168,76,0.18),transparent);margin-bottom:20px"></div>
            <p style="font-size:12px;font-weight:300;color:rgba(245,237,214,0.25);text-align:center;margin:0">
              If you did not create an account, ignore this email.<br>© ' . date('Y') . ' Rana\'s Table.
            </p>
          </div>
        </div>';

        $mail->AltBody = "Hello $name,\n\nYour OTP is: $otp\n\nExpires in 10 minutes.\n\n© " . date('Y') . " Rana's Table";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/*
|--------------------------------------------------------------------------
| AUTO-SHOW OTP if user came from login page (session already set)
|--------------------------------------------------------------------------
*/
if (!isset($_POST['submit']) && !isset($_POST['verify_otp']) && !isset($_POST['resend_otp'])) {
    if (!empty($_SESSION['pending_email']) && !empty($_SESSION['pending_user_id'])) {
        // User redirected from login — show OTP form directly
        // Generate a fresh OTP and send it if none exists or expired
        if (empty($_SESSION['otp']) || time() > ($_SESSION['otp_expiry'] ?? 0)) {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['otp']        = $otp;
            $_SESSION['otp_expiry'] = time() + 600;
            // Fetch name if not in session
            if (empty($_SESSION['pending_name'])) {
                $s = $connection->prepare("SELECT name FROM `uesr` WHERE id=?");
                $s->bind_param("i", $_SESSION['pending_user_id']);
                $s->execute();
                $row = $s->get_result()->fetch_assoc();
                $_SESSION['pending_name'] = $row['name'] ?? 'User';
            }
            $sent = sendOtpEmail($_SESSION['pending_email'], $_SESSION['pending_name'], $otp);
            if (!$sent) $error = "Could not send OTP email. Use Resend below.";
        }
        $show_otp_form = true;
    }
}

/*
|--------------------------------------------------------------------------
| STEP 2 — Verify OTP
|--------------------------------------------------------------------------
*/
if (isset($_POST['verify_otp'])) {
    $entered = trim($_POST['otp_code']);
    $stored  = $_SESSION['otp']             ?? '';
    $expires = $_SESSION['otp_expiry']      ?? 0;
    $email   = $_SESSION['pending_email']   ?? '';

    if (!$stored || !$email) {
        $error = "Session expired. Please sign up again.";
    } elseif (time() > $expires) {
        unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['pending_email'], $_SESSION['pending_user_id'], $_SESSION['pending_name']);
        $error = "OTP expired. Please sign up again.";
    } elseif ($entered !== $stored) {
        $show_otp_form = true;
        $error = "Incorrect OTP. Please try again.";
    } else {
        $user_id = $_SESSION['pending_user_id'];
        $stmt = $connection->prepare("UPDATE `uesr` SET `is_verified`=1, `verify_token`=NULL WHERE `id`=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['pending_email'], $_SESSION['pending_user_id'], $_SESSION['pending_name']);
        header("Location: login.php?verified=1");
        exit();
    }
}

/*
|--------------------------------------------------------------------------
| RESEND OTP
|--------------------------------------------------------------------------
*/
elseif (isset($_POST['resend_otp'])) {
    if (!empty($_SESSION['pending_email'])) {
        // Fetch name if missing
        if (empty($_SESSION['pending_name']) && !empty($_SESSION['pending_user_id'])) {
            $s = $connection->prepare("SELECT name FROM `uesr` WHERE id=?");
            $s->bind_param("i", $_SESSION['pending_user_id']);
            $s->execute();
            $row = $s->get_result()->fetch_assoc();
            $_SESSION['pending_name'] = $row['name'] ?? 'User';
        }
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['otp']        = $otp;
        $_SESSION['otp_expiry'] = time() + 600;
        $sent = sendOtpEmail($_SESSION['pending_email'], $_SESSION['pending_name'] ?? 'User', $otp);
        $show_otp_form = true;
        if (!$sent) $error = "Failed to send email. Check your internet connection.";
        else $error = ''; // clear any old error
    } else {
        $error = "Session expired. Please sign up again.";
    }
}

/*
|--------------------------------------------------------------------------
| STEP 1 — Register & Send OTP
|--------------------------------------------------------------------------
*/
elseif (isset($_POST['submit'])) {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $address  = trim($_POST['address']);
    $phone    = trim($_POST['phone']);

    $stmt = $connection->prepare("SELECT id, is_verified FROM `uesr` WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $existing = $res->fetch_assoc();
        if ($existing['is_verified']) {
            $error = "An account with this email already exists.";
        } else {
            // Already registered but not verified — resend OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['otp']             = $otp;
            $_SESSION['otp_expiry']      = time() + 600;
            $_SESSION['pending_email']   = $email;
            $_SESSION['pending_name']    = $name;
            $_SESSION['pending_user_id'] = $existing['id'];
            $sent = sendOtpEmail($email, $name, $otp);
            $show_otp_form = true;
            $error = $sent
                ? "This email is already registered but not verified. A new OTP has been sent to your email."
                : "Could not send OTP. Please try again.";
        }
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));

        $stmt = $connection->prepare("INSERT INTO `uesr`(`name`,`email`,`password`,`address`,`phone`,`is_verified`,`verify_token`) VALUES (?,?,?,?,?,0,?)");
        $stmt->bind_param("ssssss", $name, $email, $hashedPassword, $address, $phone, $token);

        if ($stmt->execute()) {
            $user_id = $connection->insert_id;
            $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $_SESSION['otp']             = $otp;
            $_SESSION['otp_expiry']      = time() + 600;
            $_SESSION['pending_email']   = $email;
            $_SESSION['pending_name']    = $name;
            $_SESSION['pending_user_id'] = $user_id;

            $sent = sendOtpEmail($email, $name, $otp);
            $show_otp_form = true;
            if (!$sent) $error = "Account created but OTP email failed. Use Resend below.";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $show_otp_form ? 'Verify Account' : 'Sign Up'; ?> — Rana's Table</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Cormorant+Garamond:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    :root {
        --gold:#C9A84C; --gold-lite:#E8C96A; --deep:#110600;
        --card-bg:rgba(30,12,0,0.82); --cream:#F5EDD6;
        --cream-dim:rgba(245,237,214,0.52);
        --warning:#e0a93a; --error:#e05c3a; --success:#5ab97a;
    }
    body { font-family:'Cormorant Garamond',serif; background:var(--deep); color:var(--cream); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:40px 20px; overflow-x:hidden; position:relative; }
    .bg-fixed { position:fixed; inset:0; z-index:0; pointer-events:none; background:radial-gradient(ellipse 80% 60% at 50% 30%,#3a1600 0%,#110600 65%); }
    .ember { position:fixed; border-radius:50%; pointer-events:none; opacity:0; animation:floatUp linear infinite; z-index:0; }
    @keyframes floatUp { 0%{transform:translateY(0) translateX(0);opacity:0} 8%{opacity:.75} 90%{opacity:.1} 100%{transform:translateY(-100vh) translateX(var(--d));opacity:0} }
    .candles { position:fixed; bottom:0; width:100%; display:flex; justify-content:space-around; align-items:flex-end; padding:0 6%; z-index:0; pointer-events:none; }
    .candle { display:flex; flex-direction:column; align-items:center; opacity:0.1; }
    .flame { width:10px; height:24px; background:linear-gradient(to top,#ff6a00,#ffb347,#fffbe6); border-radius:50% 50% 20% 20%; filter:blur(1px); animation:flicker 1.6s ease-in-out infinite alternate; margin-bottom:-2px; }
    @keyframes flicker { 0%{transform:scaleX(1) scaleY(1) rotate(-1deg)} 50%{transform:scaleX(0.91) scaleY(1.06) rotate(1.2deg)} 100%{transform:scaleX(1.07) scaleY(0.96) rotate(-0.4deg)} }
    .c-body { width:9px; background:linear-gradient(to right,#c8955a,#f0d49a,#c8955a); border-radius:2px; }
    .c-base { width:14px; height:5px; background:linear-gradient(to right,#8B6914,#c9a84c,#8B6914); border-radius:2px; }

    .card { position:relative; z-index:1; width:100%; max-width:480px; background:var(--card-bg); border:0.5px solid rgba(201,168,76,0.22); padding:48px 44px; backdrop-filter:blur(14px); animation:cardIn 0.9s cubic-bezier(0.16,1,0.3,1) both; }
    @keyframes cardIn { from{opacity:0;transform:translateY(32px) scale(0.97)} to{opacity:1;transform:translateY(0) scale(1)} }
    .card::before,.card::after { content:''; position:absolute; width:18px; height:18px; border-color:var(--gold); border-style:solid; opacity:.38; }
    .card::before { top:12px; left:12px; border-width:1px 0 0 1px; }
    .card::after  { bottom:12px; right:12px; border-width:0 1px 1px 0; }

    .logo-wrap { text-align:center; margin-bottom:28px; animation:fadeUp .7s .1s both; }
    .logo-ring { width:64px; height:64px; border:0.5px solid rgba(201,168,76,0.42); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:14px; animation:spinIn .9s cubic-bezier(0.16,1,0.3,1) .2s both; }
    @keyframes spinIn { from{opacity:0;transform:rotate(-15deg) scale(0.6)} to{opacity:1;transform:rotate(0) scale(1)} }
    .logo-r { font-family:'Playfair Display',serif; font-size:28px; font-weight:700; background:linear-gradient(135deg,#C9A84C,#F0D08A,#8B6914); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
    .brand { font-family:'Playfair Display',serif; font-size:24px; font-weight:400; color:var(--cream); letter-spacing:1px; margin-bottom:4px; }
    .brand em { color:var(--gold); font-style:italic; }
    .brand-tag { font-size:10px; letter-spacing:4px; text-transform:uppercase; color:rgba(201,168,76,0.52); }

    .steps { display:flex; align-items:center; justify-content:center; margin-bottom:24px; animation:fadeUp .6s .2s both; }
    .step { display:flex; flex-direction:column; align-items:center; gap:5px; }
    .step-circle { width:30px; height:30px; border-radius:50%; border:0.5px solid rgba(201,168,76,0.25); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:600; color:rgba(201,168,76,0.35); transition:all .4s; }
    .step.done   .step-circle { border-color:var(--success); color:var(--success); background:rgba(90,185,122,0.08); }
    .step.active .step-circle { border-color:var(--gold); color:var(--gold); background:rgba(201,168,76,0.08); }
    .step-label { font-size:9px; letter-spacing:2px; text-transform:uppercase; color:rgba(245,237,214,0.28); }
    .step.active .step-label { color:rgba(201,168,76,0.6); }
    .step.done   .step-label { color:rgba(90,185,122,0.55); }
    .step-line { width:50px; height:0.5px; background:rgba(201,168,76,0.14); margin:0 6px 18px; align-self:flex-start; margin-top:15px; }
    .step-line.done { background:rgba(90,185,122,0.32); }

    .divider { display:flex; align-items:center; gap:12px; margin-bottom:24px; animation:fadeUp .6s .25s both; }
    .div-line { flex:1; height:.5px; background:linear-gradient(to right,transparent,rgba(201,168,76,0.28),transparent); }
    .div-gem  { width:5px; height:5px; background:var(--gold); transform:rotate(45deg); flex-shrink:0; opacity:.6; }

    @keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }

    .banner { display:flex; align-items:flex-start; gap:10px; padding:13px 16px; margin-bottom:20px; font-size:13px; font-weight:300; line-height:1.65; border:0.5px solid; animation:fadeUp .4s both; }
    .banner.error   { background:rgba(224,92,58,0.08);  border-color:rgba(224,92,58,0.35);  color:#f0896a; }
    .banner.warning { background:rgba(224,169,58,0.08); border-color:rgba(224,169,58,0.3);  color:var(--warning); }
    @keyframes shake { 0%,100%{transform:translateX(0)} 20%{transform:translateX(-6px)} 40%{transform:translateX(6px)} 60%{transform:translateX(-4px)} 80%{transform:translateX(4px)} }
    .banner.shake { animation:shake .4s both; }

    .email-sent-notice { display:flex; align-items:center; gap:12px; background:rgba(90,185,122,0.07); border:0.5px solid rgba(90,185,122,0.28); padding:14px 16px; margin-bottom:22px; animation:fadeUp .5s both; }
    .esn-icon { font-size:22px; flex-shrink:0; }
    .esn-text { font-size:14px; font-weight:300; color:rgba(245,237,214,0.7); line-height:1.65; }
    .esn-text strong { color:#8de8a8; font-weight:400; }

    .otp-title { font-family:'Playfair Display',serif; font-size:22px; color:var(--cream); margin-bottom:6px; }
    .otp-title em { color:var(--gold); font-style:italic; }
    .otp-sub { font-size:14px; font-weight:300; color:var(--cream-dim); margin-bottom:26px; line-height:1.75; }

    .otp-boxes { display:flex; gap:10px; justify-content:center; margin-bottom:20px; }
    .otp-box { width:50px; height:60px; background:rgba(255,255,255,0.03); border:0.5px solid rgba(201,168,76,0.2); color:var(--cream); font-family:'Playfair Display',serif; font-size:26px; text-align:center; outline:none; transition:all .25s; border-radius:0; -webkit-appearance:none; caret-color:var(--gold); }
    .otp-box:focus { border-color:rgba(201,168,76,0.65); background:rgba(201,168,76,0.06); box-shadow:0 0 0 3px rgba(201,168,76,0.1); }
    .otp-box.filled { border-color:rgba(201,168,76,0.45); }
    .otp-box.error  { border-color:rgba(224,92,58,0.55) !important; animation:shake .4s both; }

    .otp-timer { text-align:center; font-size:13px; font-weight:300; color:rgba(245,237,214,0.35); margin-bottom:22px; }
    .otp-timer span { color:var(--warning); font-weight:400; font-family:'Playfair Display',serif; }
    .otp-timer span.expired { color:var(--error); }

    .field { margin-bottom:20px; animation:fadeUp .6s both; }
    .field:nth-child(1){animation-delay:.3s}.field:nth-child(2){animation-delay:.38s}.field:nth-child(3){animation-delay:.46s}.field:nth-child(4){animation-delay:.54s}.field:nth-child(5){animation-delay:.62s}
    label { display:block; font-size:10px; letter-spacing:3.5px; text-transform:uppercase; color:rgba(201,168,76,0.62); margin-bottom:8px; }
    .input-wrap { position:relative; }
    .i-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); font-size:15px; color:rgba(201,168,76,0.3); pointer-events:none; transition:color .3s; }
    input[type="text"], input[type="email"], input[type="password"] { width:100%; padding:13px 16px 13px 42px; background:rgba(255,255,255,0.03); border:0.5px solid rgba(201,168,76,0.18); color:var(--cream); font-family:'Cormorant Garamond',serif; font-size:16px; font-weight:300; outline:none; transition:all .3s; border-radius:0; -webkit-appearance:none; }
    input:focus { border-color:rgba(201,168,76,0.55); background:rgba(201,168,76,0.04); box-shadow:0 0 0 3px rgba(201,168,76,0.07); }
    input:focus + .focus-bar { transform:scaleX(1); }
    input:focus ~ .i-icon { color:rgba(201,168,76,0.75); }
    ::placeholder { color:rgba(245,237,214,0.2); font-size:15px; }
    .focus-bar { position:absolute; bottom:0; left:0; right:0; height:1.5px; background:linear-gradient(to right,transparent,var(--gold),transparent); transform:scaleX(0); transform-origin:center; transition:transform .35s cubic-bezier(0.4,0,0.2,1); pointer-events:none; }

    .pw-strength { display:flex; gap:4px; margin-top:7px; }
    .pw-bar { flex:1; height:2px; background:rgba(201,168,76,0.1); border-radius:1px; transition:background .3s; }
    .pw-bar.weak{background:#e05c3a} .pw-bar.fair{background:#e0a93a} .pw-bar.good{background:var(--gold)} .pw-bar.strong{background:#5ab97a}
    .pw-label { font-size:11px; color:var(--cream-dim); margin-top:4px; }
    .pw-toggle { position:absolute; right:14px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:14px; color:rgba(201,168,76,0.35); background:none; border:none; transition:color .2s; }
    .pw-toggle:hover { color:var(--gold); }

    .btn-primary { width:100%; padding:15px; background:var(--gold); color:var(--deep); font-family:'Cormorant Garamond',serif; font-size:13px; font-weight:700; letter-spacing:4px; text-transform:uppercase; border:none; cursor:pointer; position:relative; overflow:hidden; transition:all .35s; display:flex; align-items:center; justify-content:center; gap:8px; }
    .btn-primary::before { content:''; position:absolute; inset:0; background:linear-gradient(120deg,transparent 30%,rgba(255,255,255,.25) 50%,transparent 70%); transform:translateX(-100%); transition:transform .55s; }
    .btn-primary:hover::before { transform:translateX(100%); }
    .btn-primary:hover { background:var(--gold-lite); transform:translateY(-2px); box-shadow:0 10px 36px rgba(201,168,76,.22); }
    .btn-primary:active { transform:translateY(0); }
    .btn-primary.loading { pointer-events:none; opacity:.7; }
    .spinner { width:15px; height:15px; border:2px solid rgba(17,6,0,0.3); border-top-color:var(--deep); border-radius:50%; animation:spin .7s linear infinite; display:none; }
    .btn-primary.loading .spinner { display:block; }
    .btn-primary.loading .btn-text { display:none; }
    .btn-primary a{
        text-decoration: none;
        color: var(--deep);
    }
    @keyframes spin { to{transform:rotate(360deg)} }

    .btn-ghost { width:100%; padding:13px; background:transparent; border:0.5px solid rgba(201,168,76,0.22); color:rgba(245,237,214,0.45); font-family:'Cormorant Garamond',serif; font-size:12px; letter-spacing:3px; text-transform:uppercase; cursor:pointer; transition:all .25s; margin-top:10px; display:flex; align-items:center; justify-content:center; gap:6px; }
    .btn-ghost:hover { border-color:rgba(201,168,76,0.45); color:var(--cream); }

    .link-row { text-align:center; margin-top:20px; font-size:13px; font-weight:300; color:rgba(245,237,214,0.38); }
    .link-row a { color:var(--gold); text-decoration:none; border-bottom:0.5px solid rgba(201,168,76,0.3); transition:all .2s; }
    .link-row a:hover { color:var(--gold-lite); }
    .card-footer-line { display:flex; align-items:center; gap:12px; margin-top:26px; opacity:.2; }
    .card-footer-line .div-line { background:linear-gradient(to right,transparent,rgba(201,168,76,.6),transparent); }

    @media(max-width:520px){ .card{padding:36px 20px} .otp-box{width:40px;height:54px;font-size:22px} }
</style>
</head>
<body>

<div class="bg-fixed"></div>
<div class="candles" id="candleStrip"></div>

<div class="card">

    <div class="logo-wrap">
        <div class="logo-ring"><span class="logo-r">R</span></div>
        <div class="brand">Rana's <em>Table</em></div>
        <div class="brand-tag"><?php echo $show_otp_form ? 'Verify your account' : 'Create your account'; ?></div>
    </div>

    <div class="steps">
        <div class="step <?php echo !$show_otp_form ? 'active' : 'done'; ?>">
            <div class="step-circle"><?php echo !$show_otp_form ? '1' : '✓'; ?></div>
            <div class="step-label">Details</div>
        </div>
        <div class="step-line <?php echo $show_otp_form ? 'done' : ''; ?>"></div>
        <div class="step <?php echo $show_otp_form ? 'active' : ''; ?>">
            <div class="step-circle">2</div>
            <div class="step-label">Verify</div>
        </div>
    </div>

    <div class="divider"><div class="div-line"></div><div class="div-gem"></div><div class="div-line"></div></div>

    <?php if($error): ?>
    <div class="banner <?php echo str_contains($error,'OTP has been sent') || str_contains($error,'new OTP') ? 'warning' : 'error shake'; ?>">
        <?php echo str_contains($error,'OTP has been sent') || str_contains($error,'new OTP') ? '📧' : '⚠'; ?>
        &nbsp;<?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <?php if($show_otp_form): ?>

    <?php if(empty($error) || str_contains($error ?? '', 'new OTP') || str_contains($error ?? '', 'OTP has been sent')): ?>
    <div class="email-sent-notice">
        <div class="esn-icon">📧</div>
        <div class="esn-text">
            We've sent a 6-digit code to<br>
            <strong><?php echo htmlspecialchars($_SESSION['pending_email'] ?? ''); ?></strong><br>
            Check your inbox and spam folder.
        </div>
    </div>
    <?php endif; ?>

    <div class="otp-title">Enter the <em>Code</em></div>
    <p class="otp-sub">Enter the 6-digit verification code sent to your email address.</p>

    <form action="" method="POST" id="otpForm">
        <input type="hidden" name="verify_otp" value="1">
        <input type="hidden" name="otp_code" id="otpHidden">
        <div class="otp-boxes" id="otpBoxes">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
        </div>
        <div class="otp-timer">Code expires in &nbsp;<span id="countdown">10:00</span></div>
        <button type="submit" class="btn-primary" id="verifyBtn">
            <span class="btn-text">Verify Account</span>
            <div class="spinner"></div>
        </button>
    </form>

    <form action="" method="POST" style="margin-top:10px">
        <input type="hidden" name="resend_otp" value="1">
        <button type="submit" class="btn-ghost">↩ Resend Code to Email</button>
    </form>

    <div class="link-row" style="margin-top:16px">
        Wrong email? <a href="user.php?clear=1">Start over</a>
    </div>

    <?php else: ?>

    <form action="" method="POST" id="signupForm">
        <div class="field">
            <label>Full Name</label>
            <div class="input-wrap">
                <span class="i-icon">👤</span>
                <input type="text" name="name" placeholder="e.g. Rana Ahmed" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                <div class="focus-bar"></div>
            </div>
        </div>
        <div class="field">
            <label>Email Address</label>
            <div class="input-wrap">
                <span class="i-icon">✉</span>
                <input type="email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                <div class="focus-bar"></div>
            </div>
        </div>
        <div class="field">
            <label>Password</label>
            <div class="input-wrap">
                <span class="i-icon">🔒</span>
                <input type="password" id="pwField" name="password" placeholder="Create a strong password" required>
                <button type="button" class="pw-toggle" id="pwToggle">👁</button>
                <div class="focus-bar"></div>
            </div>
            <div class="pw-strength"><div class="pw-bar" id="pb1"></div><div class="pw-bar" id="pb2"></div><div class="pw-bar" id="pb3"></div><div class="pw-bar" id="pb4"></div></div>
            <div class="pw-label" id="pwLabel"></div>
        </div>
        <div class="field">
            <label>Delivery Address</label>
            <div class="input-wrap">
                <span class="i-icon">📍</span>
                <input type="text" name="address" placeholder="Street, Area, City" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" required>
                <div class="focus-bar"></div>
            </div>
        </div>
        <div class="field">
            <label>Phone Number</label>
            <div class="input-wrap">
                <span class="i-icon">📞</span>
                <input type="text" name="phone" placeholder="+92 300 0000000" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                <div class="focus-bar"></div>
            </div>
        </div>
        <div style="margin-top:28px">
            <button type="submit" name="submit" class="btn-primary" id="signupBtn">
                <span class="btn-text">Create Account &nbsp;→</span>
                <div class="spinner"></div>
            </button>
        </div>
        <div style="margin-top:28px">
            <button type="submit" name="submit" class="btn-primary" id="signupBtn">
                <span class="btn-text">
                    <a href="<?= $login_url ?>">
                        Continue With Google &nbsp;→
                    </a>
                </span>
                <div class="spinner"></div>
            </button>
        </div>
        <div class="link-row">Already have an account? <a href="login.php">Sign in</a></div>
        <div class="card-footer-line"><div class="div-line"></div><div class="div-gem"></div><div class="div-line"></div></div>
    </form>

    <?php endif; ?>
</div>

<script>
const ec=['#C9A84C','#FF8C42','#FFD166','#E87040'];
for(let i=0;i<22;i++){
    const e=document.createElement('div'); e.className='ember';
    const s=1.5+Math.random()*3,c=ec[Math.floor(Math.random()*ec.length)];
    Object.assign(e.style,{width:s+'px',height:s+'px',background:c,left:(4+Math.random()*92)+'%',bottom:'-8px','--d':((Math.random()-.5)*70)+'px',animationDuration:(8+Math.random()*10)+'s',animationDelay:(Math.random()*16)+'s',boxShadow:`0 0 ${s*2.5}px ${c}`});
    document.body.appendChild(e);
}
const strip=document.getElementById('candleStrip');
[55,85,45,100,70,60,90].forEach(h=>{
    const c=document.createElement('div'); c.className='candle';
    c.style.opacity=(0.07+Math.random()*.1).toFixed(2);
    c.innerHTML=`<div class="flame" style="animation-delay:${(Math.random()*.8).toFixed(2)}s;animation-duration:${(1.2+Math.random()*.8).toFixed(2)}s"></div><div class="c-body" style="height:${h}px"></div><div class="c-base"></div>`;
    strip.appendChild(c);
});

// Clear session if user clicks "Start over"
<?php if(isset($_GET['clear'])): ?>
<?php
session_unset();
session_destroy();
header("Location: user.php");
exit();
?>
<?php endif; ?>

<?php if($show_otp_form): ?>
const boxes=document.querySelectorAll('.otp-box');
const hidden=document.getElementById('otpHidden');
const verifyBtn=document.getElementById('verifyBtn');
if(boxes.length) boxes[0].focus();
function syncHidden(){ hidden.value=[...boxes].map(b=>b.value).join(''); }
boxes.forEach((box,i)=>{
    box.addEventListener('input',()=>{
        box.value=box.value.replace(/\D/g,'').slice(-1);
        box.classList.toggle('filled',!!box.value);
        if(box.value && i<boxes.length-1) boxes[i+1].focus();
        syncHidden();
    });
    box.addEventListener('keydown',e=>{
        if(e.key==='Backspace'&&!box.value&&i>0){ boxes[i-1].value=''; boxes[i-1].classList.remove('filled'); boxes[i-1].focus(); syncHidden(); }
    });
    box.addEventListener('paste',e=>{
        e.preventDefault();
        const digits=(e.clipboardData.getData('text')||'').replace(/\D/g,'').slice(0,6);
        digits.split('').forEach((d,j)=>{ if(boxes[j]){boxes[j].value=d;boxes[j].classList.add('filled');} });
        syncHidden();
        const next=boxes[Math.min(digits.length,5)]; if(next) next.focus();
    });
});
document.getElementById('otpForm').addEventListener('submit',function(){ syncHidden(); verifyBtn.classList.add('loading'); });
<?php if($error && $show_otp_form && !str_contains($error,'new OTP')): ?>
boxes.forEach(b=>b.classList.add('error'));
setTimeout(()=>boxes.forEach(b=>b.classList.remove('error')),1200);
<?php endif; ?>
const expiry=<?php echo $_SESSION['otp_expiry'] ?? (time()+600); ?>;
const cdEl=document.getElementById('countdown');
function tick(){
    const left=Math.max(0,expiry-Math.floor(Date.now()/1000));
    const m=String(Math.floor(left/60)).padStart(2,'0'), s=String(left%60).padStart(2,'0');
    cdEl.textContent=m+':'+s;
    if(left<=0){ cdEl.textContent='Expired'; cdEl.classList.add('expired'); return; }
    if(left<=60) cdEl.style.color='#e05c3a';
    setTimeout(tick,1000);
}
tick();
<?php else: ?>
const pw=document.getElementById('pwField');
const bars=['pb1','pb2','pb3','pb4'].map(id=>document.getElementById(id));
const pwLbl=document.getElementById('pwLabel');
pw.addEventListener('input',()=>{
    const v=pw.value; bars.forEach(b=>b.className='pw-bar');
    if(!v){ pwLbl.textContent=''; return; }
    let score=0;
    if(v.length>=8)score++; if(/[A-Z]/.test(v))score++; if(/[0-9]/.test(v))score++; if(/[^A-Za-z0-9]/.test(v))score++;
    const cls=['','weak','fair','good','strong'];
    const lbl=['','Too short','Fair','Good','Strong'];
    const clr=['','#e05c3a','#e0a93a','#C9A84C','#5ab97a'];
    for(let i=0;i<score;i++) bars[i].classList.add(cls[score]);
    pwLbl.textContent=lbl[score]; pwLbl.style.color=clr[score];
});
document.getElementById('pwToggle').addEventListener('click',function(){
    const show=pw.type==='password'; pw.type=show?'text':'password'; this.textContent=show?'🙈':'👁';
});
document.getElementById('signupForm').addEventListener('submit',()=>{
    document.getElementById('signupBtn').classList.add('loading');
});
<?php endif; ?>
</script>
</body>
</html>



// devsinc.com

// contour.com

// systemsltd.com