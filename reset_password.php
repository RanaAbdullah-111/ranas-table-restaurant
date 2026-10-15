<?php
include("config.php");

$token       = "";
$success_msg = "";
$error_msg   = "";

/*
|--------------------------------------------------------------------------
| STEP 1 — Verify OTP
|--------------------------------------------------------------------------
*/
if(isset($_POST['enter_otp'])){
    $token = trim($_POST['otp']);

    $stmt = $connection->prepare("SELECT * FROM uesr WHERE otp=? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 0){
        $error_msg = "Invalid OTP or it has expired. Please request a new one.";
        $token = "";
    }
}

/*
|--------------------------------------------------------------------------
| STEP 2 — Reset Password
|--------------------------------------------------------------------------
*/
if(isset($_POST['reset'])){
    $token        = $_POST['otp'];
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $confirm      = $_POST['confirm_password'];

    if($_POST['password'] !== $confirm){
        $error_msg = "Passwords do not match. Please try again.";
    } else {
        $stmt = $connection->prepare("UPDATE uesr SET password=?, otp=NULL, reset_expires=NULL WHERE otp=?");
        $stmt->bind_param("ss", $new_password, $token);
        if($stmt->execute() && $stmt->affected_rows > 0){
            $success_msg = "Password changed successfully!";
            $token = "";
        } else {
            $error_msg = "Session expired. Please request a new OTP.";
            $token = "";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Rana's Table</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

<div class="bg-fixed"></div>
<div class="candles" id="candleStrip"></div>

<?php if($success_msg): ?>
<div class="success-overlay">
    <div class="success-ring">✓</div>
    <h2 class="success-title">Password Reset!</h2>
    <p class="success-msg">Your password has been changed successfully.<br>You can now sign in with your new password.</p>
    <a href="login.php" class="btn-login">Sign In Now</a>
</div>
<?php endif; ?>

<div class="card">
    <div class="logo-wrap">
        <div class="logo-ring"><span class="logo-r">R</span></div>
        <div class="brand">Rana's <em>Table</em></div>
        <div class="brand-tag">Password Reset</div>
    </div>

    <div class="steps">
        <div class="step <?php echo !$token ? 'active' : 'done'; ?>">
            <div class="step-circle"><?php echo !$token ? '1' : '✓'; ?></div>
            <div class="step-label">OTP</div>
        </div>
        <div class="step-line <?php echo $token ? 'done' : ''; ?>"></div>
        <div class="step <?php echo $token ? 'active' : ''; ?>">
            <div class="step-circle">2</div>
            <div class="step-label">New Password</div>
        </div>
    </div>

    <div class="divider"><div class="div-line"></div><div class="div-gem"></div><div class="div-line"></div></div>

    <?php if($error_msg): ?>
    <div class="banner error shake">⚠ &nbsp;<?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <?php if(!$token): ?>
    <!-- STEP 1 — Enter OTP -->
    <div class="section-title">Enter Your OTP</div>
    <p class="section-sub">We sent a 6-digit code to your email. Enter it below to proceed.</p>

    <form action="" method="POST" id="otpForm">
        <div class="otp-boxes" id="otpBoxes">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
        </div>
        <input type="hidden" name="otp" id="realOtp">

        <div class="otp-timer">OTP expires in &nbsp;<span id="countdown">05:00</span></div>

        <button type="submit" name="enter_otp" class="btn-primary" id="otpBtn">
            <span class="btn-text">Verify OTP &nbsp;→</span>
            <div class="spinner"></div>
        </button>
    </form>

    <div class="resend-row">
        Didn't receive it? <a href="forgot.php">Resend OTP</a>
    </div>

    <?php else: ?>
    <!-- STEP 2 — New Password -->
    <div class="section-title">Create New Password</div>
    <p class="section-sub">Choose a strong password for your account.</p>

    <form action="" method="POST" id="resetForm">
        <input type="hidden" name="otp" value="<?php echo htmlspecialchars($token); ?>">

        <div class="field">
            <label for="f_pw">New Password</label>
            <div class="input-wrap">
                <span class="i-icon">🔒</span>
                <input type="password" id="f_pw" name="password" placeholder="Create a strong password" required>
                <button type="button" class="pw-toggle" id="toggle1">👁</button>
                <div class="focus-bar"></div>
            </div>
            <div class="pw-strength">
                <div class="pw-bar" id="b1"></div>
                <div class="pw-bar" id="b2"></div>
                <div class="pw-bar" id="b3"></div>
                <div class="pw-bar" id="b4"></div>
            </div>
            <div class="pw-label" id="pwLabel"></div>
        </div>

        <div class="field">
            <label for="f_cpw">Confirm Password</label>
            <div class="input-wrap">
                <span class="i-icon">🔒</span>
                <input type="password" id="f_cpw" name="confirm_password" placeholder="Repeat your password" required>
                <button type="button" class="pw-toggle" id="toggle2">👁</button>
                <div class="focus-bar"></div>
            </div>
            <div id="matchMsg" style="font-size:12px;margin-top:6px;min-height:16px"></div>
        </div>

        <button type="submit" name="reset" class="btn-primary" id="resetBtn">
            <span class="btn-text">Change Password &nbsp;→</span>
            <div class="spinner"></div>
        </button>
    </form>

    <?php endif; ?>

    <div class="back-link">
        Remembered it? <a href="login.php">Sign in instead</a>
    </div>

    <div class="card-footer-line">
        <div class="div-line"></div><div class="div-gem"></div><div class="div-line"></div>
    </div>
</div>

<script src="assets/js/auth.js"></script>
<?php if(!$token): ?>
<script>
const boxes = document.querySelectorAll('.otp-box');
const realOtp = document.getElementById('realOtp');
if (boxes.length) boxes[0].focus();
function syncOtp(){ realOtp.value = [...boxes].map(b => b.value).join(''); }
boxes.forEach((box, i) => {
    box.addEventListener('input', () => {
        box.value = box.value.replace(/\D/g, '').slice(-1);
        box.classList.toggle('filled', !!box.value);
        if (box.value && i < boxes.length - 1) boxes[i+1].focus();
        syncOtp();
    });
    box.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !box.value && i > 0) {
            boxes[i-1].value = '';
            boxes[i-1].classList.remove('filled');
            boxes[i-1].focus();
            syncOtp();
        }
    });
    box.addEventListener('paste', e => {
        e.preventDefault();
        const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
        digits.split('').forEach((d, j) => {
            if (boxes[j]) { boxes[j].value = d; boxes[j].classList.add('filled'); }
        });
        syncOtp();
        const next = boxes[Math.min(digits.length, 5)];
        if (next) next.focus();
    });
});
document.getElementById('otpForm').addEventListener('submit', () => syncOtp());

let secs = 5 * 60;
const cd = document.getElementById('countdown');
const timer = setInterval(() => {
    secs--;
    if (secs <= 0) {
        clearInterval(timer);
        cd.textContent = 'Expired';
        cd.classList.add('expired');
        return;
    }
    const m = String(Math.floor(secs / 60)).padStart(2, '0');
    const s = String(secs % 60).padStart(2, '0');
    cd.textContent = `${m}:${s}`;
    if (secs <= 60) cd.style.color = '#e05c3a';
}, 1000);
</script>
<?php else: ?>
<script>
const pw = document.getElementById('f_pw');
const cpw = document.getElementById('f_cpw');
const bars = ['b1', 'b2', 'b3', 'b4'].map(id => document.getElementById(id));
const pwLbl = document.getElementById('pwLabel');
const matchMsg = document.getElementById('matchMsg');

pw.addEventListener('input', () => {
    const v = pw.value;
    bars.forEach(b => b.className = 'pw-bar');
    if (!v) { pwLbl.textContent = ''; return; }
    let score = 0;
    if (v.length >= 6) score++;
    if (v.length >= 8) score++;
    if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;

    let level = 1;
    if (score >= 5) level = 4;
    else if (score >= 3) level = 3;
    else if (score >= 2) level = 2;
    else level = 1;

    const cls = ['', 'weak', 'fair', 'good', 'strong'];
    const lbl = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    const clr = ['', '#e05c3a', '#e0a93a', '#C9A84C', '#5ab97a'];
    for (let i = 0; i < level; i++) bars[i].classList.add(cls[level]);
    pwLbl.textContent = lbl[level];
    pwLbl.style.color = clr[level];
    checkMatch();
});

cpw.addEventListener('input', checkMatch);
function checkMatch(){
    if (!cpw.value) { matchMsg.textContent = ''; return; }
    if (pw.value === cpw.value) {
        matchMsg.textContent = '✓ Passwords match';
        matchMsg.style.color = '#5ab97a';
    } else {
        matchMsg.textContent = '✗ Passwords do not match';
        matchMsg.style.color = '#e05c3a';
    }
}

document.getElementById('toggle1').addEventListener('click', function () {
    pw.type = pw.type === 'password' ? 'text' : 'password';
    this.textContent = pw.type === 'password' ? '👁' : '🙈';
});
document.getElementById('toggle2').addEventListener('click', function () {
    cpw.type = cpw.type === 'password' ? 'text' : 'password';
    this.textContent = cpw.type === 'password' ? '👁' : '🙈';
});
</script>
<?php endif; ?>
</body>
</html>