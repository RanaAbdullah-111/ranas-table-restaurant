<?php
session_start();
include("config.php");

$error_msg        = "";
$wrong_pass       = false;
$not_found        = false;
$not_verified     = false;
$unverified_email = "";

if(isset($_POST['submit'])){
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $connection->prepare("SELECT * FROM uesr WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        if($row['is_verified'] == 0){
            $not_verified     = true;
            $unverified_email = $email;
            // Carry the pending session so signup OTP page knows who to verify
            $_SESSION['pending_email']   = $email;
            $_SESSION['pending_user_id'] = $row['id'];
        } elseif(password_verify($password, $row['password'])){
            $_SESSION['user']    = $email;
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role']    = $row['role'];
            header($row['role'] == 'admin' ? "Location: admin/dashboard.php" : "Location: index.php");
            exit();
        } else {
            $wrong_pass = true;
            $error_msg  = "Wrong password. Please try again.";
        }
    } else {
        $not_found = true;
        $error_msg = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Rana's Table</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Cormorant+Garamond:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        :root {
            --gold:#C9A84C; --gold-light:#E8C96A;
            --deep:#110600; --cream:#F5EDD6;
            --cream-dim:rgba(245,237,214,0.55);
            --warning:#E0A93A; --error:#E05C3A;
        }
        body {
            font-family:'Cormorant Garamond',serif;
            background:var(--deep); color:var(--cream);
            min-height:100vh; display:flex; align-items:center;
            justify-content:center; position:relative;
        }
        .bg-glow { position:fixed; inset:0; background:radial-gradient(ellipse 70% 55% at 50% 60%,#3a1600 0%,#110600 65%); z-index:0; }
        .ember { position:fixed; border-radius:50%; pointer-events:none; opacity:0; animation:floatUp linear infinite; z-index:0; }
        @keyframes floatUp { 0%{transform:translateY(0) translateX(0) scale(1);opacity:0} 8%{opacity:.85} 88%{opacity:.2} 100%{transform:translateY(-100vh) translateX(var(--d)) scale(0.3);opacity:0} }
        .candles { position:fixed; bottom:0; width:100%; display:flex; justify-content:space-around; align-items:flex-end; padding:0 6%; z-index:0; pointer-events:none; }
        .candle  { display:flex; flex-direction:column; align-items:center; }
        .flame   { width:10px; height:24px; background:linear-gradient(to top,#ff6a00,#ffb347,#fffbe6); border-radius:50% 50% 20% 20%; filter:blur(1px); animation:flicker 1.6s ease-in-out infinite alternate; margin-bottom:-2px; }
        @keyframes flicker { 0%{transform:scaleX(1) scaleY(1) rotate(-1deg)} 33%{transform:scaleX(0.91) scaleY(1.06) rotate(1.2deg)} 66%{transform:scaleX(1.07) scaleY(0.96) rotate(-0.4deg)} 100%{transform:scaleX(0.94) scaleY(1.04) rotate(1.6deg)} }
        .c-body { width:9px; background:linear-gradient(to right,#c8955a,#f0d49a,#c8955a); border-radius:2px; }
        .c-base { width:14px; height:5px; background:linear-gradient(to right,#8B6914,#c9a84c,#8B6914); border-radius:2px; }

        /* CARD */
        .card {
            position:relative; z-index:1; width:100%; max-width:440px; margin:20px;
            background:rgba(30,12,0,0.78); border:0.5px solid rgba(201,168,76,0.22);
            padding:52px 44px 44px; backdrop-filter:blur(14px);
            animation:cardIn 0.9s cubic-bezier(0.16,1,0.3,1) both;
        }
        @keyframes cardIn { from{opacity:0;transform:translateY(36px) scale(0.96)} to{opacity:1;transform:translateY(0) scale(1)} }
        .card::before,.card::after { content:''; position:absolute; width:18px; height:18px; border-color:var(--gold); border-style:solid; opacity:.45; }
        .card::before { top:12px; left:12px; border-width:1px 0 0 1px; }
        .card::after  { bottom:12px; right:12px; border-width:0 1px 1px 0; }

        .logo-wrap { text-align:center; margin-bottom:32px; animation:fadeUp .7s .15s both; }
        .logo-ring { width:62px; height:62px; border:0.5px solid rgba(201,168,76,0.45); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:14px; animation:spinIn .9s cubic-bezier(0.16,1,0.3,1) .2s both; }
        @keyframes spinIn { from{opacity:0;transform:rotate(-18deg) scale(0.6)} to{opacity:1;transform:rotate(0) scale(1)} }
        .logo-r { font-family:'Playfair Display',serif; font-size:27px; font-weight:700; background:linear-gradient(135deg,#C9A84C,#F0D08A,#8B6914); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
        .brand    { font-family:'Playfair Display',serif; font-size:24px; font-weight:400; color:var(--cream); letter-spacing:1px; margin-bottom:4px; }
        .brand em { color:var(--gold); font-style:italic; }
        .brand-tag { font-size:10px; letter-spacing:4px; text-transform:uppercase; color:rgba(201,168,76,0.55); }

        .divider { display:flex; align-items:center; gap:12px; margin-bottom:30px; animation:fadeUp .7s .25s both; }
        .div-line { flex:1; height:0.5px; background:linear-gradient(to right,transparent,rgba(201,168,76,0.3),transparent); }
        .div-gem  { width:5px; height:5px; background:var(--gold); transform:rotate(45deg); flex-shrink:0; opacity:.65; }

        /* STANDARD ERROR */
        .error-banner {
            display:flex; align-items:center; gap:10px;
            background:rgba(224,92,58,0.12); border:0.5px solid rgba(224,92,58,0.35);
            padding:12px 16px; margin-bottom:22px;
            font-size:14px; color:#f0896a; font-weight:300;
            animation:shake .4s cubic-bezier(0.36,0.07,0.19,0.97) both;
        }
        @keyframes shake { 0%,100%{transform:translateX(0)} 20%{transform:translateX(-6px)} 40%{transform:translateX(6px)} 60%{transform:translateX(-4px)} 80%{transform:translateX(4px)} }

        /* ══ NOT VERIFIED BANNER ══ */
        .vb-wrap {
            margin-bottom:26px;
            border:0.5px solid rgba(224,169,58,0.35);
            background:rgba(224,169,58,0.05);
            animation:fadeUp .5s both;
        }
        .vb-header {
            display:flex; align-items:center; gap:16px;
            padding:18px 20px 16px;
        }
        .vb-icon {
            width:46px; height:46px; flex-shrink:0;
            border:0.5px solid rgba(224,169,58,0.45); border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:20px;
            animation:pulse 2.2s ease-in-out infinite;
        }
        @keyframes pulse { 0%,100%{box-shadow:0 0 0 0 rgba(224,169,58,0.3)} 50%{box-shadow:0 0 0 7px rgba(224,169,58,0)} }
        .vb-header-text h4 { font-family:'Playfair Display',serif; font-size:16px; font-weight:400; color:var(--cream); margin-bottom:3px; }
        .vb-header-text h4 em { color:var(--warning); font-style:italic; }
        .vb-header-text p  { font-size:13px; font-weight:300; color:rgba(245,237,214,0.5); line-height:1.6; }
        .vb-header-text p strong { color:rgba(224,169,58,0.9); font-weight:400; font-style:italic; }

        /* Steps */
        .vb-steps-row {
            display:flex; align-items:center;
            padding:14px 20px;
            border-top:0.5px solid rgba(224,169,58,0.12);
            border-bottom:0.5px solid rgba(224,169,58,0.12);
        }
        .vb-step { flex:1; display:flex; flex-direction:column; align-items:center; gap:6px; position:relative; }
        .vb-step:not(:last-child)::after { content:''; position:absolute; top:11px; left:calc(50% + 13px); right:calc(-50% + 13px); height:0.5px; background:rgba(224,169,58,0.18); }
        .vb-step-circle { width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:600; flex-shrink:0; border:0.5px solid rgba(224,169,58,0.2); color:rgba(224,169,58,0.3); }
        .vb-step.done .vb-step-circle  { border-color:rgba(224,169,58,0.5); color:var(--warning); background:rgba(224,169,58,0.1); }
        .vb-step.active .vb-step-circle { border-color:var(--warning); color:var(--warning); background:rgba(224,169,58,0.15); box-shadow:0 0 8px rgba(224,169,58,0.2); }
        .vb-step-label { font-size:11px; font-weight:300; color:rgba(245,237,214,0.28); text-align:center; white-space:nowrap; }
        .vb-step.done .vb-step-label  { color:rgba(224,169,58,0.55); }
        .vb-step.active .vb-step-label { color:rgba(224,169,58,0.85); }

        /* OTP action row */
        .vb-actions {
            display:flex; align-items:center; justify-content:space-between;
            padding:12px 20px; gap:12px; flex-wrap:wrap;
        }
        .vb-hint { font-size:12px; font-weight:300; color:rgba(245,237,214,0.3); font-style:italic; }
        .vb-otp-btn {
            font-size:10px; letter-spacing:3px; text-transform:uppercase;
            color:var(--warning); border:0.5px solid rgba(224,169,58,0.35);
            padding:8px 18px; background:transparent; cursor:pointer;
            font-family:'Cormorant Garamond',serif; transition:all .25s;
            white-space:nowrap; text-decoration:none; display:inline-block;
        }
        .vb-otp-btn:hover { background:rgba(224,169,58,0.1); border-color:var(--warning); color:var(--warning); }

        @keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }

        /* FORM FIELDS */
        .field { margin-bottom:20px; animation:fadeUp .6s both; }
        .field:nth-child(1){animation-delay:.35s} .field:nth-child(2){animation-delay:.47s}
        label { display:block; font-size:10px; letter-spacing:3.5px; text-transform:uppercase; color:rgba(201,168,76,0.65); margin-bottom:8px; }
        .input-wrap { position:relative; }
        .i-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); font-size:16px; color:rgba(201,168,76,0.35); pointer-events:none; transition:color .3s; line-height:1; }
        input[type="email"], input[type="password"] { width:100%; padding:13px 16px 13px 44px; background:rgba(255,255,255,0.03); border:0.5px solid rgba(201,168,76,0.18); color:var(--cream); font-family:'Cormorant Garamond',serif; font-size:16px; font-weight:300; outline:none; transition:all .3s; border-radius:0; -webkit-appearance:none; }
        input::placeholder { color:rgba(245,237,214,0.22); font-size:15px; }
        input:focus { border-color:rgba(201,168,76,0.55); background:rgba(201,168,76,0.04); box-shadow:0 0 0 3px rgba(201,168,76,0.07); }
        input.error-field { border-color:rgba(224,92,58,0.45) !important; }
        input:focus ~ .i-icon { color:rgba(201,168,76,0.75); }
        .focus-bar { position:absolute; bottom:0; left:0; right:0; height:1.5px; background:linear-gradient(to right,transparent,var(--gold),transparent); transform:scaleX(0); transform-origin:center; transition:transform .35s cubic-bezier(0.4,0,0.2,1); pointer-events:none; }
        input:focus + .focus-bar { transform:scaleX(1); }
        .pw-toggle { position:absolute; right:14px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:15px; color:rgba(201,168,76,0.35); transition:color .2s; user-select:none; line-height:1; }
        .pw-toggle:hover { color:var(--gold); }

        .forgot { text-align:right; margin-top:-10px; margin-bottom:28px; animation:fadeUp .6s .58s both; }
        .forgot a { font-size:12px; color:rgba(201,168,76,0.5); text-decoration:none; border-bottom:0.5px solid transparent; transition:all .2s; }
        .forgot a:hover { color:var(--gold); border-bottom-color:rgba(201,168,76,0.4); }

        .btn-submit { width:100%; padding:15px; background:var(--gold); color:var(--deep); font-family:'Cormorant Garamond',serif; font-size:13px; font-weight:600; letter-spacing:4px; text-transform:uppercase; border:none; cursor:pointer; position:relative; overflow:hidden; transition:all .35s; animation:fadeUp .6s .65s both; }
        .btn-submit::before { content:''; position:absolute; inset:0; background:linear-gradient(120deg,transparent 30%,rgba(255,255,255,0.28) 50%,transparent 70%); transform:translateX(-100%); transition:transform .55s; }
        .btn-submit:hover::before { transform:translateX(100%); }
        .btn-submit:hover { background:var(--gold-light); transform:translateY(-2px); box-shadow:0 10px 36px rgba(201,168,76,0.22); }
        .btn-submit:active { transform:translateY(0); }

        .register-link { text-align:center; margin-top:22px; font-size:13px; font-weight:300; color:rgba(245,237,214,0.4); animation:fadeUp .6s .75s both; }
        .register-link a { color:var(--gold); text-decoration:none; border-bottom:0.5px solid rgba(201,168,76,0.3); transition:all .2s; }
        .register-link a:hover { color:var(--gold-light); }
        .card-footer-line { display:flex; align-items:center; gap:12px; margin-top:30px; opacity:.3; }

        @media(max-width:480px){ .card{ padding:40px 24px 36px; } .vb-header{ padding:16px; } .vb-actions{ padding:10px 16px; } .vb-steps-row{ padding:12px 16px; } }
    </style>
</head>
<body>

<div class="bg-glow"></div>
<div class="candles" id="candleStrip"></div>

<div class="card">

    <div class="logo-wrap">
        <div class="logo-ring"><span class="logo-r">R</span></div>
        <div class="brand">Rana's <em>Table</em></div>
        <div class="brand-tag">Welcome back</div>
    </div>

    <div class="divider">
        <div class="div-line"></div><div class="div-gem"></div><div class="div-line"></div>
    </div>

    <?php if($not_verified): ?>
    <!-- ══ NOT VERIFIED BANNER — OTP version ══ -->
    <div class="vb-wrap">

        <div class="vb-header">
            <div class="vb-icon">🔐</div>
            <div class="vb-header-text">
                <h4>Account Not <em>Verified</em></h4>
                <p>You need to verify <strong><?php echo htmlspecialchars($unverified_email); ?></strong> before signing in.</p>
            </div>
        </div>

        <div class="vb-steps-row">
            <div class="vb-step done">
                <div class="vb-step-circle">✓</div>
                <div class="vb-step-label">Signed Up</div>
            </div>
            <div class="vb-step active">
                <div class="vb-step-circle">2</div>
                <div class="vb-step-label">Enter OTP</div>
            </div>
            <div class="vb-step">
                <div class="vb-step-circle">3</div>
                <div class="vb-step-label">Sign In</div>
            </div>
        </div>

        <div class="vb-actions">
            <span class="vb-hint">A 6-digit code was sent during signup.</span>
            <a href="user.php" class="vb-otp-btn">Enter OTP →</a>
        </div>

    </div>

    <?php elseif($error_msg): ?>
    <div class="error-banner">
        <span>⚠</span> <?php echo htmlspecialchars($error_msg); ?>
    </div>
    <?php endif; ?>

    <form action="" method="POST">

        <div class="field">
            <label for="email">Email Address</label>
            <div class="input-wrap">
                <span class="i-icon">✉</span>
                <input type="email" id="email" name="email" placeholder="you@example.com"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    class="<?php echo $not_found ? 'error-field' : ''; ?>"
                    required autocomplete="email">
                <div class="focus-bar"></div>
            </div>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
                <span class="i-icon">🔒</span>
                <input type="password" id="password" name="password" placeholder="Enter your password"
                    class="<?php echo $wrong_pass ? 'error-field' : ''; ?>"
                    required autocomplete="current-password">
                <div class="focus-bar"></div>
                <span class="pw-toggle" id="pwToggle" title="Show/Hide">👁</span>
            </div>
        </div>

        <div class="forgot"><a href="forgot.php">Forgot password?</a></div>

        <button type="submit" name="submit" class="btn-submit">Sign In</button>

        <div class="register-link">
            New to Rana's Table? <a href="user.php">Create an account</a>
        </div>

        <div class="card-footer-line">
            <div class="div-line"></div><div class="div-gem"></div><div class="div-line"></div>
        </div>

    </form>
</div>

<script>
const colors = ['#C9A84C','#FF8C42','#FFD166','#E87040'];
for (let i = 0; i < 24; i++) {
    const el = document.createElement('div'), s = 1.5 + Math.random() * 3, c = colors[i % colors.length];
    el.className = 'ember';
    Object.assign(el.style, { width:s+'px', height:s+'px', background:c, left:(4+Math.random()*92)+'%', bottom:'-8px', '--d':((Math.random()-.5)*70)+'px', animationDuration:(8+Math.random()*10)+'s', animationDelay:(Math.random()*15)+'s', boxShadow:`0 0 ${s*2.5}px ${c}` });
    document.body.appendChild(el);
}

const strip = document.getElementById('candleStrip');
[55,85,45,100,70,60,90,50,80].forEach(h => {
    const c = document.createElement('div');
    c.className = 'candle';
    c.style.opacity = (0.07 + Math.random() * 0.1).toFixed(2);
    c.innerHTML = `<div class="flame" style="animation-delay:${(Math.random()*.8).toFixed(2)}s;animation-duration:${(1.2+Math.random()*.8).toFixed(2)}s"></div><div class="c-body" style="height:${h}px"></div><div class="c-base"></div>`;
    strip.appendChild(c);
});

const pwField = document.getElementById('password');
const pwToggle = document.getElementById('pwToggle');
if (pwToggle) pwToggle.addEventListener('click', () => {
    const show = pwField.type === 'password';
    pwField.type = show ? 'text' : 'password';
    pwToggle.textContent = show ? '🙈' : '👁';
});

const emailF = document.getElementById('email');
const passF  = document.getElementById('password');
if (emailF && passF) (emailF.value ? passF : emailF).focus();
</script>
</body>
</html>