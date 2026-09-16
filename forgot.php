<?php
    date_default_timezone_set('Asia/Karachi');
    include("config.php");

    require 'vendor/autoload.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

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
                    $mail->addAddress($email);
                    $mail->Subject = 'Password Reset OTP — Rana\'s Table';
                    $mail->isHTML(true);
                    $mail->Body = '
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
                    $mail->AltBody = 'Your OTP is: ' . $otp . '. Expires in 5 minutes.';
                    $mail->send();
                    header("location: reset_password.php");
                    exit();
                } catch (Exception $e) {
                    $error_msg = "Mail error: " . $mail->ErrorInfo;
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Cormorant+Garamond:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        :root {
            --gold:      #C9A84C;
            --gold-lite: #E8C96A;
            --deep:      #110600;
            --card-bg:   rgba(30,12,0,0.82);
            --cream:     #F5EDD6;
            --cream-dim: rgba(245,237,214,0.52);
            --error:     #e05c3a;
            --success:   #5ab97a;
        }

        body {
            font-family:'Cormorant Garamond',serif;
            background:var(--deep); color:var(--cream);
            min-height:100vh; display:flex; align-items:center; justify-content:center;
            padding:40px 20px; overflow-x:hidden; position:relative;
        }

        /* BG */
        .bg-fixed { position:fixed; inset:0; z-index:0; pointer-events:none; background:radial-gradient(ellipse 80% 60% at 50% 30%,#3a1600 0%,#110600 65%); }

        /* EMBERS */
        .ember { position:fixed; border-radius:50%; pointer-events:none; opacity:0; animation:floatUp linear infinite; z-index:0; }
        @keyframes floatUp { 0%{transform:translateY(0) translateX(0);opacity:0} 8%{opacity:.75} 90%{opacity:.12} 100%{transform:translateY(-100vh) translateX(var(--d));opacity:0} }

        /* CANDLES */
        .candles { position:fixed; bottom:0; width:100%; display:flex; justify-content:space-around; align-items:flex-end; padding:0 6%; z-index:0; pointer-events:none; }
        .candle   { display:flex; flex-direction:column; align-items:center; opacity:0.1; }
        .flame    { width:10px; height:24px; background:linear-gradient(to top,#ff6a00,#ffb347,#fffbe6); border-radius:50% 50% 20% 20%; filter:blur(1px); animation:flicker 1.6s ease-in-out infinite alternate; margin-bottom:-2px; }
        @keyframes flicker { 0%{transform:scaleX(1) scaleY(1) rotate(-1deg)} 33%{transform:scaleX(0.91) scaleY(1.06) rotate(1.2deg)} 66%{transform:scaleX(1.07) scaleY(0.96) rotate(-0.4deg)} 100%{transform:scaleX(0.94) scaleY(1.04) rotate(1.6deg)} }
        .c-body { width:9px; background:linear-gradient(to right,#c8955a,#f0d49a,#c8955a); border-radius:2px; }
        .c-base { width:14px; height:5px; background:linear-gradient(to right,#8B6914,#c9a84c,#8B6914); border-radius:2px; }

        /* CARD */
        .card {
            position:relative; z-index:1;
            width:100%; max-width:440px;
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
        .logo-wrap { text-align:center; margin-bottom:32px; animation:fadeUp 0.7s 0.15s both; }
        .logo-ring { width:64px; height:64px; border:0.5px solid rgba(201,168,76,0.42); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:14px; animation:spinIn 0.9s cubic-bezier(0.16,1,0.3,1) 0.2s both; }
        @keyframes spinIn { from{opacity:0;transform:rotate(-18deg) scale(0.6)} to{opacity:1;transform:rotate(0) scale(1)} }
        .logo-r { font-family:'Playfair Display',serif; font-size:28px; font-weight:700; background:linear-gradient(135deg,#C9A84C,#F0D08A,#8B6914); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
        .brand   { font-family:'Playfair Display',serif; font-size:24px; font-weight:400; color:var(--cream); letter-spacing:1px; margin-bottom:4px; }
        .brand em { color:var(--gold); font-style:italic; }
        .brand-tag { font-size:10px; letter-spacing:4px; text-transform:uppercase; color:rgba(201,168,76,0.52); }

        /* DIVIDER */
        .divider { display:flex; align-items:center; gap:12px; margin-bottom:28px; animation:fadeUp 0.7s 0.25s both; }
        .div-line { flex:1; height:.5px; background:linear-gradient(to right,transparent,rgba(201,168,76,0.3),transparent); }
        .div-gem  { width:5px; height:5px; background:var(--gold); transform:rotate(45deg); flex-shrink:0; opacity:.6; }

        @keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }

        /* DESCRIPTION */
        .card-desc { font-size:15px; font-weight:300; color:var(--cream-dim); line-height:1.85; margin-bottom:26px; text-align:center; animation:fadeUp 0.7s 0.3s both; }

        /* ERROR / SUCCESS BANNER */
        .banner { display:flex; align-items:center; gap:10px; padding:12px 16px; margin-bottom:20px; font-size:14px; font-weight:300; letter-spacing:0.3px; animation:shake 0.4s cubic-bezier(0.36,0.07,0.19,0.97) both; }
        .banner.error   { background:rgba(224,92,58,0.1); border:0.5px solid rgba(224,92,58,0.35); color:#f0896a; }
        .banner.success { background:rgba(90,185,122,0.1); border:0.5px solid rgba(90,185,122,0.35); color:#8de8a8; }
        @keyframes shake { 0%,100%{transform:translateX(0)} 20%{transform:translateX(-6px)} 40%{transform:translateX(6px)} 60%{transform:translateX(-4px)} 80%{transform:translateX(4px)} }

        /* FIELD */
        .field { margin-bottom:22px; animation:fadeUp 0.6s 0.35s both; }
        label  { display:block; font-size:10px; letter-spacing:3.5px; text-transform:uppercase; color:rgba(201,168,76,0.62); margin-bottom:8px; }

        .input-wrap { position:relative; }
        .i-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); font-size:16px; color:rgba(201,168,76,0.32); pointer-events:none; transition:color 0.3s; }

        input[type="email"] {
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

        /* SUBMIT BTN */
        .btn-send {
            width:100%; padding:15px; background:var(--gold); color:var(--deep);
            font-family:'Cormorant Garamond',serif; font-size:13px; font-weight:700; letter-spacing:4px; text-transform:uppercase;
            border:none; cursor:pointer; position:relative; overflow:hidden; transition:all 0.35s;
            display:flex; align-items:center; justify-content:center; gap:8px;
            animation:fadeUp 0.6s 0.45s both;
        }
        .btn-send::before { content:''; position:absolute; inset:0; background:linear-gradient(120deg,transparent 30%,rgba(255,255,255,.28) 50%,transparent 70%); transform:translateX(-100%); transition:transform .55s; }
        .btn-send:hover::before { transform:translateX(100%); }
        .btn-send:hover { background:var(--gold-lite); transform:translateY(-2px); box-shadow:0 10px 36px rgba(201,168,76,.22); }
        .btn-send:active { transform:translateY(0); }

        /* LOADING STATE */
        .btn-send.loading { pointer-events:none; opacity:0.75; }
        .spinner { width:16px; height:16px; border:2px solid rgba(17,6,0,0.3); border-top-color:var(--deep); border-radius:50%; animation:spin 0.7s linear infinite; display:none; }
        .btn-send.loading .spinner { display:block; }
        .btn-send.loading .btn-text { display:none; }
        @keyframes spin { to{transform:rotate(360deg)} }

        /* OTP INFO */
        .otp-info { display:flex; align-items:flex-start; gap:10px; margin-top:18px; padding:14px 16px; background:rgba(201,168,76,0.05); border:0.5px solid rgba(201,168,76,0.12); animation:fadeUp 0.6s 0.55s both; }
        .otp-info-icon { font-size:16px; flex-shrink:0; margin-top:1px; }
        .otp-info-text { font-size:13px; font-weight:300; color:rgba(201,168,76,0.55); line-height:1.7; }

        /* BACK LINK */
        .back-link { text-align:center; margin-top:22px; font-size:13px; font-weight:300; color:rgba(245,237,214,0.38); animation:fadeUp 0.6s 0.6s both; }
        .back-link a { color:var(--gold); text-decoration:none; border-bottom:0.5px solid rgba(201,168,76,0.3); transition:all 0.2s; }
        .back-link a:hover { color:var(--gold-lite); }

        /* FOOTER LINE */
        .card-footer-line { display:flex; align-items:center; gap:12px; margin-top:28px; opacity:.25; }
        .card-footer-line .div-line { background:linear-gradient(to right,transparent,rgba(201,168,76,.6),transparent); }
    </style>
</head>
<body>

<div class="bg-fixed"></div>

<!-- Candles -->
<div class="candles" id="candleStrip"></div>

<!-- CARD -->
<div class="card">

    <!-- Logo -->
    <div class="logo-wrap">
        <div class="logo-ring"><span class="logo-r">R</span></div>
        <div class="brand">Rana's <em>Table</em></div>
        <div class="brand-tag">Password Recovery</div>
    </div>

    <!-- Divider -->
    <div class="divider"><div class="div-line"></div><div class="div-gem"></div><div class="div-line"></div></div>

    <!-- Description -->
    <p class="card-desc">Enter your email address and we'll send you a 6-digit OTP to reset your password.</p>

    <!-- Error / Success Banner -->
    <?php if($error_msg): ?>
    <div class="banner error">⚠ <?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <!-- FORM -->
    <form action="" method="POST" id="forgotForm">

        <div class="field">
            <label for="f_email">Email Address</label>
            <div class="input-wrap">
                <!-- mail icon (inline SVG so no external dependency needed) -->
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

    <!-- OTP Info -->
    <div class="otp-info">
        <span class="otp-info-icon">🔒</span>
        <span class="otp-info-text">A 6-digit OTP will be sent to your email. It will expire in <strong style="color:rgba(201,168,76,0.75)">5 minutes</strong>. Check your spam folder if you don't see it.</span>
    </div>

    <!-- Back to login -->
    <div class="back-link">
        Remembered your password? <a href="login.php">Sign in</a>
    </div>

    <!-- Decorative line -->
    <div class="card-footer-line">
        <div class="div-line"></div>
        <div class="div-gem"></div>
        <div class="div-line"></div>
    </div>

</div>

<script>
    /* Embers */
    const ec=['#C9A84C','#FF8C42','#FFD166','#E87040'];
    for(let i=0;i<22;i++){
        const e=document.createElement('div'); e.className='ember';
        const s=1.5+Math.random()*3, c=ec[Math.floor(Math.random()*ec.length)];
        Object.assign(e.style,{width:s+'px',height:s+'px',background:c,left:(4+Math.random()*92)+'%',bottom:'-8px','--d':((Math.random()-.5)*70)+'px',animationDuration:(8+Math.random()*10)+'s',animationDelay:(Math.random()*15)+'s',boxShadow:`0 0 ${s*2.5}px ${c}`});
        document.body.appendChild(e);
    }

    /* Candles */
    const strip = document.getElementById('candleStrip');
    [55,85,45,100,70,60,90].forEach(h=>{
        const c=document.createElement('div'); c.className='candle';
        c.style.opacity=(0.07+Math.random()*0.1).toFixed(2);
        c.innerHTML=`<div class="flame" style="animation-delay:${(Math.random()*0.8).toFixed(2)}s;animation-duration:${(1.2+Math.random()*0.8).toFixed(2)}s"></div><div class="c-body" style="height:${h}px"></div><div class="c-base"></div>`;
        strip.appendChild(c);
    });

    /* Loading state on submit */
    document.getElementById('forgotForm').addEventListener('submit',()=>{
        const btn = document.getElementById('sendBtn');
        btn.classList.add('loading');
    });
</script>
</body>
</html>