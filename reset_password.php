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
        $token = ""; // reset so OTP form shows again
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
            $token = ""; // go back to login
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Cormorant+Garamond:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        :root {
            --gold:      #C9A84C;
            --gold-lite: #E8C96A;
            --deep:      #110600;
            --card-bg:   rgba(30,12,0,0.84);
            --cream:     #F5EDD6;
            --cream-dim: rgba(245,237,214,0.52);
            --error:     #e05c3a;
            --success:   #5ab97a;
        }

        body {
            font-family:'Cormorant Garamond',serif;
            background:var(--deep); color:var(--cream);
            min-height:100vh; display:flex; align-items:center; justify-content:center;
            padding:40px 20px; overflow-x:hidden;
        }

        .bg-fixed { position:fixed; inset:0; z-index:0; pointer-events:none; background:radial-gradient(ellipse 80% 60% at 50% 30%,#3a1600 0%,#110600 65%); }

        /* EMBERS */
        .ember { position:fixed; border-radius:50%; pointer-events:none; opacity:0; animation:floatUp linear infinite; z-index:0; }
        @keyframes floatUp { 0%{transform:translateY(0) translateX(0);opacity:0} 8%{opacity:.75} 90%{opacity:.1} 100%{transform:translateY(-100vh) translateX(var(--d));opacity:0} }

        /* CANDLES */
        .candles { position:fixed; bottom:0; width:100%; display:flex; justify-content:space-around; align-items:flex-end; padding:0 6%; z-index:0; pointer-events:none; }
        .candle   { display:flex; flex-direction:column; align-items:center; opacity:0.1; }
        .flame    { width:10px; height:24px; background:linear-gradient(to top,#ff6a00,#ffb347,#fffbe6); border-radius:50% 50% 20% 20%; filter:blur(1px); animation:flicker 1.6s ease-in-out infinite alternate; margin-bottom:-2px; }
        @keyframes flicker { 0%{transform:scaleX(1) scaleY(1) rotate(-1deg)} 50%{transform:scaleX(0.91) scaleY(1.06) rotate(1.2deg)} 100%{transform:scaleX(1.07) scaleY(0.96) rotate(-0.4deg)} }
        .c-body { width:9px; background:linear-gradient(to right,#c8955a,#f0d49a,#c8955a); border-radius:2px; }
        .c-base { width:14px; height:5px; background:linear-gradient(to right,#8B6914,#c9a84c,#8B6914); border-radius:2px; }

        /* CARD */
        .card {
            position:relative; z-index:1;
            width:100%; max-width:450px;
            background:var(--card-bg);
            border:0.5px solid rgba(201,168,76,0.22);
            padding:52px 44px 44px;
            backdrop-filter:blur(14px);
            animation:cardIn 0.9s cubic-bezier(0.16,1,0.3,1) both;
        }
        @keyframes cardIn { from{opacity:0;transform:translateY(32px) scale(0.97)} to{opacity:1;transform:translateY(0) scale(1)} }
        .card::before,.card::after { content:''; position:absolute; width:18px; height:18px; border-color:var(--gold); border-style:solid; opacity:.4; }
        .card::before { top:12px; left:12px; border-width:1px 0 0 1px; }
        .card::after  { bottom:12px; right:12px; border-width:0 1px 1px 0; }

        /* LOGO */
        .logo-wrap { text-align:center; margin-bottom:30px; animation:fadeUp 0.7s 0.15s both; }
        .logo-ring { width:64px; height:64px; border:0.5px solid rgba(201,168,76,0.42); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:14px; animation:spinIn 0.9s cubic-bezier(0.16,1,0.3,1) 0.2s both; }
        @keyframes spinIn { from{opacity:0;transform:rotate(-18deg) scale(0.6)} to{opacity:1;transform:rotate(0) scale(1)} }
        .logo-r { font-family:'Playfair Display',serif; font-size:28px; font-weight:700; background:linear-gradient(135deg,#C9A84C,#F0D08A,#8B6914); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
        .brand   { font-family:'Playfair Display',serif; font-size:24px; font-weight:400; color:var(--cream); letter-spacing:1px; margin-bottom:4px; }
        .brand em { color:var(--gold); font-style:italic; }
        .brand-tag { font-size:10px; letter-spacing:4px; text-transform:uppercase; color:rgba(201,168,76,0.52); }

        /* STEP INDICATOR */
        .steps { display:flex; align-items:center; justify-content:center; gap:0; margin-bottom:28px; animation:fadeUp 0.7s 0.25s both; }
        .step { display:flex; flex-direction:column; align-items:center; gap:5px; position:relative; }
        .step-circle { width:32px; height:32px; border-radius:50%; border:0.5px solid rgba(201,168,76,0.25); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:600; color:rgba(201,168,76,0.35); transition:all 0.4s; }
        .step.done .step-circle { border-color:var(--success); color:var(--success); background:rgba(90,185,122,0.08); }
        .step.active .step-circle { border-color:var(--gold); color:var(--gold); background:rgba(201,168,76,0.08); }
        .step-label { font-size:10px; letter-spacing:2px; text-transform:uppercase; color:rgba(245,237,214,0.3); }
        .step.active .step-label { color:rgba(201,168,76,0.65); }
        .step.done .step-label { color:rgba(90,185,122,0.6); }
        .step-line { width:60px; height:0.5px; background:rgba(201,168,76,0.15); margin:0 6px 18px; align-self:flex-start; margin-top:16px; }
        .step-line.done { background:rgba(90,185,122,0.35); }

        /* DIVIDER */
        .divider { display:flex; align-items:center; gap:12px; margin-bottom:24px; }
        .div-line { flex:1; height:.5px; background:linear-gradient(to right,transparent,rgba(201,168,76,0.28),transparent); }
        .div-gem  { width:5px; height:5px; background:var(--gold); transform:rotate(45deg); flex-shrink:0; opacity:.6; }

        @keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }

        /* BANNER */
        .banner { display:flex; align-items:flex-start; gap:10px; padding:13px 16px; margin-bottom:20px; font-size:14px; font-weight:300; letter-spacing:0.3px; line-height:1.6; }
        .banner.error   { background:rgba(224,92,58,0.1); border:0.5px solid rgba(224,92,58,0.35); color:#f0896a; animation:shake 0.4s both; }
        .banner.success { background:rgba(90,185,122,0.1); border:0.5px solid rgba(90,185,122,0.35); color:#8de8a8; animation:fadeUp 0.5s both; }
        @keyframes shake { 0%,100%{transform:translateX(0)} 20%{transform:translateX(-6px)} 40%{transform:translateX(6px)} 60%{transform:translateX(-4px)} 80%{transform:translateX(4px)} }

        /* SECTION TITLE */
        .section-title { font-family:'Playfair Display',serif; font-size:22px; font-weight:400; color:var(--cream); margin-bottom:6px; animation:fadeUp 0.6s 0.3s both; }
        .section-sub   { font-size:14px; font-weight:300; color:var(--cream-dim); margin-bottom:24px; line-height:1.7; animation:fadeUp 0.6s 0.35s both; }

        /* OTP INPUT */
        .otp-wrap { display:flex; gap:8px; justify-content:center; margin-bottom:24px; animation:fadeUp 0.6s 0.4s both; }
        .otp-digit {
            width:52px; height:60px; text-align:center;
            background:rgba(255,255,255,0.03); border:0.5px solid rgba(201,168,76,0.2);
            color:var(--cream); font-family:'Playfair Display',serif; font-size:24px; font-weight:700;
            outline:none; transition:all 0.3s; border-radius:0; -webkit-appearance:none;
            caret-color:var(--gold);
        }
        .otp-digit:focus { border-color:rgba(201,168,76,0.6); background:rgba(201,168,76,0.05); box-shadow:0 0 0 3px rgba(201,168,76,0.08); }
        .otp-digit.filled { border-color:rgba(201,168,76,0.4); }
        /* hidden real input */
        .otp-hidden { display:none; }

        /* FIELD */
        .field { margin-bottom:20px; animation:fadeUp 0.6s both; }
        .field:nth-child(1) { animation-delay:0.38s; }
        .field:nth-child(2) { animation-delay:0.46s; }
        label  { display:block; font-size:10px; letter-spacing:3.5px; text-transform:uppercase; color:rgba(201,168,76,0.62); margin-bottom:8px; }
        .input-wrap { position:relative; }
        .i-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); font-size:15px; color:rgba(201,168,76,0.3); pointer-events:none; transition:color 0.3s; }

        input[type="password"] {
            width:100%; padding:13px 48px 13px 44px;
            background:rgba(255,255,255,0.03); border:0.5px solid rgba(201,168,76,0.18);
            color:var(--cream); font-family:'Cormorant Garamond',serif; font-size:16px; font-weight:300;
            outline:none; transition:all 0.3s; border-radius:0; -webkit-appearance:none;
        }
        input[type="text"] {
            width:100%; padding:13px 16px 13px 44px;
            background:rgba(255,255,255,0.03); border:0.5px solid rgba(201,168,76,0.18);
            color:var(--cream); font-family:'Cormorant Garamond',serif; font-size:16px; font-weight:300;
            outline:none; transition:all 0.3s; border-radius:0; -webkit-appearance:none;
        }
        input:focus { border-color:rgba(201,168,76,0.55); background:rgba(201,168,76,0.04); box-shadow:0 0 0 3px rgba(201,168,76,0.07); }
        input:focus + .focus-bar { transform:scaleX(1); }
        input:focus ~ .i-icon { color:rgba(201,168,76,0.75); }
        ::placeholder { color:rgba(245,237,214,0.2); font-size:15px; }
        .focus-bar { position:absolute; bottom:0; left:0; right:0; height:1.5px; background:linear-gradient(to right,transparent,var(--gold),transparent); transform:scaleX(0); transform-origin:center; transition:transform 0.35s cubic-bezier(0.4,0,0.2,1); pointer-events:none; }

        /* show/hide pw */
        .pw-toggle { position:absolute; right:14px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:14px; color:rgba(201,168,76,0.35); background:none; border:none; transition:color 0.2s; }
        .pw-toggle:hover { color:var(--gold); }

        /* PW STRENGTH */
        .pw-strength { display:flex; gap:4px; margin-top:8px; }
        .pw-bar { flex:1; height:2px; background:rgba(201,168,76,0.1); border-radius:1px; transition:background 0.3s; }
        .pw-bar.weak   { background:#e05c3a; }
        .pw-bar.fair   { background:#e0a93a; }
        .pw-bar.good   { background:var(--gold); }
        .pw-bar.strong { background:#5ab97a; }
        .pw-label { font-size:11px; color:var(--cream-dim); margin-top:4px; }

        /* SUBMIT */
        .btn-submit {
            width:100%; padding:15px; background:var(--gold); color:var(--deep);
            font-family:'Cormorant Garamond',serif; font-size:13px; font-weight:700; letter-spacing:4px; text-transform:uppercase;
            border:none; cursor:pointer; position:relative; overflow:hidden; transition:all 0.35s;
            display:flex; align-items:center; justify-content:center; gap:8px; margin-top:8px;
        }
        .btn-submit::before { content:''; position:absolute; inset:0; background:linear-gradient(120deg,transparent 30%,rgba(255,255,255,.28) 50%,transparent 70%); transform:translateX(-100%); transition:transform .55s; }
        .btn-submit:hover::before { transform:translateX(100%); }
        .btn-submit:hover { background:var(--gold-lite); transform:translateY(-2px); box-shadow:0 10px 36px rgba(201,168,76,.22); }
        .btn-submit:active { transform:translateY(0); }
        .btn-submit.loading { pointer-events:none; opacity:.7; }
        .spinner { width:15px; height:15px; border:2px solid rgba(17,6,0,0.3); border-top-color:var(--deep); border-radius:50%; animation:spin 0.7s linear infinite; display:none; }
        .btn-submit.loading .spinner { display:block; }
        .btn-submit.loading .btn-text { display:none; }
        @keyframes spin { to{transform:rotate(360deg)} }

        /* RESEND */
        .resend-row { text-align:center; margin-top:16px; font-size:13px; font-weight:300; color:rgba(245,237,214,0.38); }
        .resend-row a { color:rgba(201,168,76,0.6); text-decoration:none; border-bottom:0.5px solid rgba(201,168,76,0.25); transition:all 0.2s; }
        .resend-row a:hover { color:var(--gold); }

        /* SUCCESS OVERLAY */
        .success-overlay {
            position:fixed; inset:0; z-index:999; background:rgba(8,3,0,0.97);
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            text-align:center; padding:40px;
            animation:fadeIn 0.5s both;
        }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }
        .success-ring { width:90px; height:90px; border:1px solid var(--success); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:38px; color:var(--success); margin-bottom:24px; animation:popIn 0.6s cubic-bezier(0.16,1,0.3,1) 0.2s both; }
        @keyframes popIn { from{transform:scale(0);opacity:0} to{transform:scale(1);opacity:1} }
        .success-title { font-family:'Playfair Display',serif; font-size:32px; color:var(--cream); margin-bottom:12px; }
        .success-msg   { font-size:16px; font-weight:300; color:var(--cream-dim); line-height:1.8; margin-bottom:32px; }
        .btn-login { padding:14px 40px; background:var(--gold); color:var(--deep); font-family:'Cormorant Garamond',serif; font-size:13px; font-weight:700; letter-spacing:4px; text-transform:uppercase; border:none; cursor:pointer; text-decoration:none; display:inline-block; transition:all 0.3s; }
        .btn-login:hover { background:var(--gold-lite); transform:translateY(-2px); }

        /* BACK LINK */
        .back-link { text-align:center; margin-top:20px; font-size:13px; font-weight:300; color:rgba(245,237,214,0.35); }
        .back-link a { color:rgba(201,168,76,0.55); text-decoration:none; border-bottom:0.5px solid rgba(201,168,76,0.25); transition:all 0.2s; }
        .back-link a:hover { color:var(--gold); }

        .card-footer-line { display:flex; align-items:center; gap:12px; margin-top:26px; opacity:.22; }
        .card-footer-line .div-line { background:linear-gradient(to right,transparent,rgba(201,168,76,.6),transparent); }
    </style>
</head>
<body>

<div class="bg-fixed"></div>
<div class="candles" id="candleStrip"></div>

<?php if($success_msg): ?>
<!-- SUCCESS OVERLAY -->
<div class="success-overlay">
    <div class="success-ring">✓</div>
    <h2 class="success-title">Password Reset!</h2>
    <p class="success-msg">Your password has been changed successfully.<br>You can now sign in with your new password.</p>
    <a href="login.php" class="btn-login">Sign In Now</a>
</div>
<?php endif; ?>

<div class="card">

    <!-- Logo -->
    <div class="logo-wrap">
        <div class="logo-ring"><span class="logo-r">R</span></div>
        <div class="brand">Rana's <em>Table</em></div>
        <div class="brand-tag">Password Reset</div>
    </div>

    <!-- Step Indicator -->
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

    <!-- BANNER -->
    <?php if($error_msg): ?>
    <div class="banner error">⚠ &nbsp;<?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <?php if(!$token): ?>
    <!-- ════════════════════════════
         STEP 1 — Enter OTP
    ════════════════════════════ -->
    <div class="section-title">Enter Your OTP</div>
    <p class="section-sub">We sent a 6-digit code to your email. Enter it below to proceed.</p>

    <form action="" method="POST" id="otpForm">
        <!-- Visual OTP boxes -->
        <div class="otp-wrap" id="otpBoxes">
            <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
        </div>
        <!-- Real hidden input that gets submitted -->
        <input type="hidden" name="otp" id="realOtp">

        <!-- Countdown Timer -->
        <div style="text-align:center;margin-bottom:20px;font-size:13px;font-weight:300;color:rgba(201,168,76,0.5);letter-spacing:1px">
            OTP expires in &nbsp;<span id="countdown" style="color:var(--gold);font-weight:600;font-family:'Playfair Display',serif">05:00</span>
        </div>

        <button type="submit" name="enter_otp" class="btn-submit" id="otpBtn">
            <span class="btn-text">Verify OTP &nbsp;→</span>
            <div class="spinner"></div>
        </button>
    </form>

    <div class="resend-row">
        Didn't receive it? <a href="forgot_password.php">Resend OTP</a>
    </div>

    <?php else: ?>
    <!-- ════════════════════════════
         STEP 2 — New Password
    ════════════════════════════ -->
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
            <div id="matchMsg" style="font-size:12px;margin-top:6px;letter-spacing:.3px"></div>
        </div>

        <button type="submit" name="reset" class="btn-submit" id="resetBtn">
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

<script>
/* Embers */
const ec=['#C9A84C','#FF8C42','#FFD166','#E87040'];
for(let i=0;i<22;i++){
    const e=document.createElement('div'); e.className='ember';
    const s=1.5+Math.random()*3, c=ec[Math.floor(Math.random()*ec.length)];
    Object.assign(e.style,{width:s+'px',height:s+'px',background:c,left:(4+Math.random()*92)+'%',bottom:'-8px','--d':((Math.random()-.5)*70)+'px',animationDuration:(8+Math.random()*10)+'s',animationDelay:(Math.random()*16)+'s',boxShadow:`0 0 ${s*2.5}px ${c}`});
    document.body.appendChild(e);
}

/* Candles */
const strip=document.getElementById('candleStrip');
[55,85,45,100,70,60,90].forEach(h=>{
    const c=document.createElement('div'); c.className='candle';
    c.style.opacity=(0.07+Math.random()*0.1).toFixed(2);
    c.innerHTML=`<div class="flame" style="animation-delay:${(Math.random()*.8).toFixed(2)}s;animation-duration:${(1.2+Math.random()*.8).toFixed(2)}s"></div><div class="c-body" style="height:${h}px"></div><div class="c-base"></div>`;
    strip.appendChild(c);
});

<?php if(!$token): ?>
/* ── OTP BOXES ── */
const digits  = document.querySelectorAll('.otp-digit');
const realOtp = document.getElementById('realOtp');

digits.forEach((inp, idx)=>{
    inp.addEventListener('input',()=>{
        inp.value = inp.value.replace(/\D/g,'').slice(-1);
        if(inp.value) inp.classList.add('filled');
        else inp.classList.remove('filled');
        // move to next
        if(inp.value && idx < digits.length-1) digits[idx+1].focus();
        // collect value
        realOtp.value = Array.from(digits).map(d=>d.value).join('');
    });
    inp.addEventListener('keydown',e=>{
        if(e.key==='Backspace'&&!inp.value&&idx>0) digits[idx-1].focus();
    });
    inp.addEventListener('paste',e=>{
        e.preventDefault();
        const paste = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'');
        paste.split('').slice(0,6).forEach((ch,i)=>{
            if(digits[i]){ digits[i].value=ch; digits[i].classList.add('filled'); }
        });
        realOtp.value = Array.from(digits).map(d=>d.value).join('');
        digits[Math.min(paste.length,5)].focus();
    });
});

/* countdown 5 min */
let secs = 5 * 60;
const cd  = document.getElementById('countdown');
const timer = setInterval(()=>{
    secs--;
    if(secs<=0){ clearInterval(timer); cd.textContent='Expired'; cd.style.color='#e05c3a'; return; }
    const m=Math.floor(secs/60).toString().padStart(2,'0');
    const s=(secs%60).toString().padStart(2,'0');
    cd.textContent=`${m}:${s}`;
    if(secs<=60) cd.style.color='#e05c3a';
},1000);

/* loading on submit */
document.getElementById('otpForm').addEventListener('submit',function(){
    realOtp.value = Array.from(digits).map(d=>d.value).join('');
    document.getElementById('otpBtn').classList.add('loading');
});

<?php else: ?>
/* ── PASSWORD STRENGTH ── */
const pw   = document.getElementById('f_pw');
const cpw  = document.getElementById('f_cpw');
const bars = [document.getElementById('b1'),document.getElementById('b2'),document.getElementById('b3'),document.getElementById('b4')];
const pwLbl= document.getElementById('pwLabel');

pw.addEventListener('input',()=>{
    const v=pw.value;
    bars.forEach(b=>b.className='pw-bar');
    if(!v){ pwLbl.textContent=''; return; }
    let score=0;
    if(v.length>=8)score++;
    if(/[A-Z]/.test(v))score++;
    if(/[0-9]/.test(v))score++;
    if(/[^A-Za-z0-9]/.test(v))score++;
    const cls =['','weak','fair','good','strong'];
    const lbl =['','Too short','Fair','Good','Strong'];
    const clr =['','#e05c3a','#e0a93a','#C9A84C','#5ab97a'];
    for(let i=0;i<score;i++) bars[i].classList.add(cls[score]);
    pwLbl.textContent=lbl[score];
    pwLbl.style.color=clr[score];
    checkMatch();
});

/* match checker */
const matchMsg=document.getElementById('matchMsg');
function checkMatch(){
    if(!cpw.value) return;
    if(pw.value===cpw.value){
        matchMsg.textContent='✓ Passwords match';
        matchMsg.style.color='#5ab97a';
    } else {
        matchMsg.textContent='✕ Passwords do not match';
        matchMsg.style.color='#e05c3a';
    }
}
cpw.addEventListener('input',checkMatch);

/* show/hide toggles */
function makeToggle(inputId, btnId){
    document.getElementById(btnId).addEventListener('click',()=>{
        const f=document.getElementById(inputId);
        const show=f.type==='password';
        f.type=show?'text':'password';
        document.getElementById(btnId).textContent=show?'🙈':'👁';
    });
}
makeToggle('f_pw','toggle1');
makeToggle('f_cpw','toggle2');

/* loading on submit */
document.getElementById('resetForm').addEventListener('submit',function(e){
    const p=document.getElementById('f_pw').value;
    const c=document.getElementById('f_cpw').value;
    if(p!==c){ e.preventDefault(); return; }
    document.getElementById('resetBtn').classList.add('loading');
});
<?php endif; ?>
</script>
</body>
</html>