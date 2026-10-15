<?php
session_start();
date_default_timezone_set('Asia/Karachi');

// Clear pending session if requested
if (isset($_GET['clear'])) {
    unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['pending_email'], $_SESSION['pending_user_id'], $_SESSION['pending_name']);
    header("Location: register.php");
    exit();
}

include("config.php");
require_once __DIR__ . '/includes/mail.php';

// Google OAuth
require 'google_config.php';
$login_url = $client->createAuthUrl();

$error         = '';
$show_otp_form = false;

/*
|--------------------------------------------------------------------------
| Helper — Send OTP Email
|--------------------------------------------------------------------------
*/
function sendOtpEmail(string $email, string $name, string $otp): bool {
    $subject = "Your Verification Code — Rana's Table";
    $body = '
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

    $altBody = "Hello $name,\n\nYour OTP is: $otp\n\nExpires in 10 minutes.\n\n© " . date('Y') . " Rana's Table";
    return sendAppMail($email, $name, $subject, $body, $altBody);
}

/*
|--------------------------------------------------------------------------
| AUTO-SHOW OTP if user came from login page (session already set)
|--------------------------------------------------------------------------
*/
if (!isset($_POST['submit']) && !isset($_POST['verify_otp']) && !isset($_POST['resend_otp'])) {
    if (!empty($_SESSION['pending_email']) && !empty($_SESSION['pending_user_id'])) {
        if (empty($_SESSION['otp']) || time() > ($_SESSION['otp_expiry'] ?? 0)) {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['otp']        = $otp;
            $_SESSION['otp_expiry'] = time() + 600;

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
        else $error = '';
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
    <link rel="stylesheet" href="assets/css/auth.css">
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

    <form action="" method="POST" style="margin-top:10px" data-no-spinner="true">
        <input type="hidden" name="resend_otp" value="1">
        <button type="submit" class="btn-ghost">↩ Resend Code to Email</button>
    </form>

    <div class="link-row" style="margin-top:16px">
        Wrong email? <a href="register.php?clear=1">Start over</a>
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
                <button type="button" class="pw-toggle" id="pwToggle" aria-label="Toggle password view">👁</button>
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

        <div style="margin-top:24px">
            <button type="submit" name="submit" class="btn-primary" id="signupBtn">
                <span class="btn-text">Create Account &nbsp;→</span>
                <div class="spinner"></div>
            </button>
        </div>

        <div class="or-divider"><span>or continue with</span></div>

        <a href="<?= htmlspecialchars($login_url) ?>" class="btn-google">
            <svg viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            Continue with Google
        </a>

        <div class="link-row">Already have an account? <a href="login.php">Sign in</a></div>
        <div class="card-footer-line"><div class="div-line"></div><div class="div-gem"></div><div class="div-line"></div></div>
    </form>

    <?php endif; ?>
</div>

<script src="assets/js/auth.js"></script>
<?php if($show_otp_form): ?>
<script>
const boxes = document.querySelectorAll('.otp-box');
const hidden = document.getElementById('otpHidden');
if (boxes.length) boxes[0].focus();
function syncHidden(){ hidden.value = [...boxes].map(b => b.value).join(''); }
boxes.forEach((box, i) => {
    box.addEventListener('input', () => {
        box.value = box.value.replace(/\D/g, '').slice(-1);
        box.classList.toggle('filled', !!box.value);
        if (box.value && i < boxes.length - 1) boxes[i+1].focus();
        syncHidden();
    });
    box.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !box.value && i > 0) {
            boxes[i-1].value = '';
            boxes[i-1].classList.remove('filled');
            boxes[i-1].focus();
            syncHidden();
        }
    });
    box.addEventListener('paste', e => {
        e.preventDefault();
        const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
        digits.split('').forEach((d, j) => {
            if (boxes[j]) { boxes[j].value = d; boxes[j].classList.add('filled'); }
        });
        syncHidden();
        const next = boxes[Math.min(digits.length, 5)];
        if (next) next.focus();
    });
});
document.getElementById('otpForm').addEventListener('submit', () => syncHidden());
<?php if($error && $show_otp_form && !str_contains($error, 'new OTP')): ?>
boxes.forEach(b => b.classList.add('error'));
setTimeout(() => boxes.forEach(b => b.classList.remove('error')), 1200);
<?php endif; ?>
const expiry = <?php echo $_SESSION['otp_expiry'] ?? (time() + 600); ?>;
const cdEl = document.getElementById('countdown');
function tick(){
    const left = Math.max(0, expiry - Math.floor(Date.now() / 1000));
    const m = String(Math.floor(left / 60)).padStart(2, '0');
    const s = String(left % 60).padStart(2, '0');
    cdEl.textContent = m + ':' + s;
    if (left <= 0) {
        cdEl.textContent = 'Expired';
        cdEl.classList.add('expired');
        return;
    }
    if (left <= 60) cdEl.style.color = '#e05c3a';
    setTimeout(tick, 1000);
}
tick();
</script>
<?php endif; ?>
</body>
</html>
