<?php
include("../config.php");
require("../auth.php");

$id = $_SESSION['user_id'];
$success_msg = "";
$error_msg   = "";

/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSION
|--------------------------------------------------------------------------
*/
if(isset($_POST['submit'])){
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $address  = trim($_POST['address']);
    $phone    = trim($_POST['phone']);

    if(!empty($password)){
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $update = $connection->prepare("UPDATE `uesr` SET `name`=?, `email`=?, `password`=?, `address`=?, `phone`=? WHERE id=?");
        $update->bind_param("sssssi", $name, $email, $hashedPassword, $address, $phone, $id);
    } else {
        $update = $connection->prepare("UPDATE `uesr` SET `name`=?, `email`=?, `address`=?, `phone`=? WHERE id=?");
        $update->bind_param("ssssi", $name, $email, $address, $phone, $id);
    }

    if($update->execute()){
        $_SESSION['user'] = $email; // keep session in sync
        $success_msg = "Profile updated successfully.";
    } else {
        $error_msg = "Something went wrong. Please try again.";
    }
}

/*
|--------------------------------------------------------------------------
| FETCH USER DATA
|--------------------------------------------------------------------------
*/
$select = $connection->prepare("SELECT * FROM `uesr` WHERE id=?");
$select->bind_param("i", $id);
$select->execute();
$row = $select->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile — Rana's Table</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Cormorant+Garamond:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        :root {
            --gold:      #C9A84C;
            --gold-lite: #E8C96A;
            --deep:      #110600;
            --card-bg:   rgba(30,12,0,0.84);
            --cream:     #F5EDD6;
            --cream-dim: rgba(245,237,214,0.52);
            --success:   #5ab97a;
            --error:     #e05c3a;
        }

        html { scroll-behavior:smooth; }
        body { font-family:'Cormorant Garamond',serif; background:var(--deep); color:var(--cream); min-height:100vh; overflow-x:hidden; }

        /* BG */
        .bg-fixed { position:fixed; inset:0; z-index:0; pointer-events:none; background:radial-gradient(ellipse 80% 50% at 50% 0%,#3a1600 0%,#110600 65%); }

        /* EMBERS */
        .ember { position:fixed; border-radius:50%; pointer-events:none; opacity:0; animation:floatUp linear infinite; z-index:0; }
        @keyframes floatUp { 0%{transform:translateY(0) translateX(0);opacity:0} 8%{opacity:.7} 90%{opacity:.1} 100%{transform:translateY(-100vh) translateX(var(--d));opacity:0} }


        /* HERO */
        .hero { position:relative; z-index:1; text-align:center; padding:52px 40px 32px; }
        .hero-tag { font-size:10px; letter-spacing:5px; text-transform:uppercase; color:rgba(201,168,76,0.55); margin-bottom:12px; animation:fadeUp 0.6s 0.1s both; }
        .hero-title { font-family:'Playfair Display',serif; font-size:clamp(34px,5vw,58px); font-weight:700; line-height:1; background:linear-gradient(135deg,#C9A84C 0%,#F0D08A 45%,#8B6914 100%); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; animation:fadeUp 0.6s 0.2s both; }
        .hero-sub { font-size:16px; font-weight:300; color:var(--cream-dim); margin-top:10px; animation:fadeUp 0.6s 0.3s both; }
        .hero-divider { display:flex; align-items:center; gap:12px; width:200px; margin:18px auto 0; animation:fadeUp 0.6s 0.4s both; }
        .hdl { flex:1; height:.5px; background:linear-gradient(to right,transparent,rgba(201,168,76,.38),transparent); }
        .hdg { width:5px; height:5px; background:var(--gold); transform:rotate(45deg); flex-shrink:0; opacity:.65; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

        /* TOAST */
        .toast { position:fixed; top:80px; left:50%; transform:translateX(-50%) translateY(-20px); padding:13px 26px; font-size:14px; letter-spacing:0.5px; z-index:999; pointer-events:none; opacity:0; transition:all 0.4s cubic-bezier(0.16,1,0.3,1); white-space:nowrap; display:flex; align-items:center; gap:10px; }
        .toast.show { opacity:1; transform:translateX(-50%) translateY(0); }
        .toast.success { background:rgba(25,55,35,0.97); border:0.5px solid rgba(90,185,122,0.4); color:#8de8a8; }
        .toast.error   { background:rgba(55,15,10,0.97); border:0.5px solid rgba(224,92,58,0.4);  color:#f0896a; }

        /* PAGE */
        .page { position:relative; z-index:1; max-width:860px; margin:0 auto; padding:0 32px 80px; }

        /* PROFILE LAYOUT */
        .profile-layout { display:grid; grid-template-columns:220px 1fr; gap:24px; align-items:start; }

        /* LEFT PANEL — avatar + quick info */
        .left-panel {
            background:var(--card-bg); border:0.5px solid rgba(201,168,76,0.14);
            padding:32px 24px; text-align:center;
            position:sticky; top:88px;
            animation:fadeUp 0.6s 0.3s both;
        }
        .left-panel::before, .left-panel::after { content:''; position:absolute; width:14px; height:14px; border-color:var(--gold); border-style:solid; opacity:.35; }
        .left-panel::before { top:10px; left:10px; border-width:1px 0 0 1px; }
        .left-panel::after  { bottom:10px; right:10px; border-width:0 1px 1px 0; }

        .avatar-ring {
            width:90px; height:90px; border-radius:50%;
            border:1px solid rgba(201,168,76,0.4);
            display:inline-flex; align-items:center; justify-content:center;
            font-size:38px; color:rgba(201,168,76,0.6);
            background:rgba(201,168,76,0.06);
            margin:0 auto 18px; display:flex;
            animation:spinIn 0.8s cubic-bezier(0.16,1,0.3,1) 0.3s both;
        }
        @keyframes spinIn { from{opacity:0;transform:rotate(-12deg) scale(0.7)} to{opacity:1;transform:rotate(0) scale(1)} }

        .lp-name  { font-family:'Playfair Display',serif; font-size:20px; font-weight:400; color:var(--cream); margin-bottom:4px; word-break:break-word; }
        .lp-email { font-size:13px; font-weight:300; color:var(--cream-dim); margin-bottom:24px; word-break:break-all; }

        .lp-divider { height:.5px; background:linear-gradient(to right,transparent,rgba(201,168,76,0.25),transparent); margin:0 0 20px; }

        .lp-stat { margin-bottom:14px; }
        .lp-stat-label { font-size:9px; letter-spacing:3px; text-transform:uppercase; color:rgba(201,168,76,0.4); margin-bottom:4px; }
        .lp-stat-value { font-size:14px; font-weight:300; color:var(--cream-dim); word-break:break-word; }

        /* RIGHT PANEL — form */
        .form-panel {
            background:var(--card-bg); border:0.5px solid rgba(201,168,76,0.14);
            padding:36px 36px 32px;
            animation:fadeUp 0.6s 0.4s both;
            position:relative;
        }
        .form-panel::before, .form-panel::after { content:''; position:absolute; width:14px; height:14px; border-color:var(--gold); border-style:solid; opacity:.35; }
        .form-panel::before { top:10px; left:10px; border-width:1px 0 0 1px; }
        .form-panel::after  { bottom:10px; right:10px; border-width:0 1px 1px 0; }

        .form-title { font-family:'Playfair Display',serif; font-size:24px; font-weight:400; color:var(--cream); margin-bottom:6px; }
        .form-sub   { font-size:13px; font-weight:300; color:var(--cream-dim); margin-bottom:28px; }

        /* Section divider inside form */
        .form-section-label { font-size:9px; letter-spacing:4px; text-transform:uppercase; color:rgba(201,168,76,0.45); margin-bottom:16px; display:flex; align-items:center; gap:12px; }
        .form-section-label::after { content:''; flex:1; height:.5px; background:linear-gradient(to right,rgba(201,168,76,0.2),transparent); }

        .form-section { margin-bottom:28px; }

        /* Two-col row */
        .field-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }

        .field { margin-bottom:18px; }
        .field:last-child { margin-bottom:0; }

        label { display:block; font-size:10px; letter-spacing:3.5px; text-transform:uppercase; color:rgba(201,168,76,0.6); margin-bottom:8px; }

        .input-wrap { position:relative; }
        .i-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); font-size:14px; color:rgba(201,168,76,0.3); pointer-events:none; transition:color 0.3s; }
        textarea ~ .i-icon, .i-icon.top { top:14px; transform:none; }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        textarea {
            width:100%; padding:12px 16px 12px 42px;
            background:rgba(255,255,255,0.03); border:0.5px solid rgba(201,168,76,0.18);
            color:var(--cream); font-family:'Cormorant Garamond',serif; font-size:16px; font-weight:300;
            outline:none; transition:all 0.3s; border-radius:0; -webkit-appearance:none;
        }
        textarea { padding-top:12px; resize:vertical; min-height:80px; }
        input:focus, textarea:focus { border-color:rgba(201,168,76,0.52); background:rgba(201,168,76,0.04); box-shadow:0 0 0 3px rgba(201,168,76,0.07); }
        input:focus ~ .i-icon, textarea:focus ~ .i-icon { color:rgba(201,168,76,0.75); }
        ::placeholder { color:rgba(245,237,214,0.2); font-size:15px; }

        .focus-bar { position:absolute; bottom:0; left:0; right:0; height:1.5px; background:linear-gradient(to right,transparent,var(--gold),transparent); transform:scaleX(0); transform-origin:center; transition:transform 0.35s cubic-bezier(0.4,0,0.2,1); pointer-events:none; }
        input:focus + .focus-bar, textarea:focus + .focus-bar { transform:scaleX(1); }

        /* password note */
        .field-note { font-size:12px; font-weight:300; color:rgba(201,168,76,0.4); margin-top:6px; letter-spacing:0.3px; }

        /* password strength */
        .pw-strength { display:flex; gap:4px; margin-top:8px; }
        .pw-bar { flex:1; height:2px; background:rgba(201,168,76,0.1); border-radius:1px; transition:background 0.3s; }
        .pw-bar.weak   { background:#e05c3a; }
        .pw-bar.medium { background:var(--pending,#e0a93a); }
        .pw-bar.strong { background:var(--success,#5ab97a); }
        .pw-label { font-size:11px; color:var(--cream-dim); margin-top:4px; letter-spacing:0.5px; }

        /* show/hide password */
        .pw-toggle { position:absolute; right:14px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:14px; color:rgba(201,168,76,0.35); transition:color 0.2s; border:none; background:none; padding:0; }
        .pw-toggle:hover { color:var(--gold); }

        /* SUBMIT */
        .form-footer { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; padding-top:8px; }
        .btn-update {
            padding:14px 36px; background:var(--gold); color:var(--deep);
            font-family:'Cormorant Garamond',serif; font-size:13px; font-weight:700; letter-spacing:4px; text-transform:uppercase;
            border:none; cursor:pointer; position:relative; overflow:hidden; transition:all 0.3s;
        }
        .btn-update::before { content:''; position:absolute; inset:0; background:linear-gradient(120deg,transparent 30%,rgba(255,255,255,.25) 50%,transparent 70%); transform:translateX(-100%); transition:transform .55s; }
        .btn-update:hover::before { transform:translateX(100%); }
        .btn-update:hover { background:var(--gold-lite); transform:translateY(-2px); box-shadow:0 10px 32px rgba(201,168,76,.2); }
        .btn-update:active { transform:translateY(0); }

        .btn-back { font-size:13px; font-weight:300; color:var(--cream-dim); text-decoration:none; letter-spacing:1px; transition:color 0.2s; }
        .btn-back:hover { color:var(--gold); }

        footer { position:relative; z-index:1; border-top:0.5px solid rgba(201,168,76,0.1); padding:28px 40px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; max-width:860px; margin:0 auto; }
        footer .f-brand { font-family:'Playfair Display',serif; font-size:17px; color:var(--gold); }
        footer .f-note  { font-size:12px; font-weight:300; color:rgba(245,237,214,.28); }

        @media(max-width:700px){
            nav { padding:0 16px; }
            .hero { padding:40px 16px 28px; }
            .page { padding:0 14px 60px; }
            .profile-layout { grid-template-columns:1fr; }
            .left-panel { position:static; }
            .form-panel { padding:24px 20px; }
            .field-row { grid-template-columns:1fr; }
            footer { padding:20px 16px; flex-direction:column; text-align:center; }
        }
    </style>
</head>
<body>

<div class="bg-fixed"></div>

<!-- TOAST -->
<?php if($success_msg || $error_msg): ?>
<div class="toast <?php echo $success_msg ? 'success' : 'error'; ?>" id="toast">
    <i class="bi bi-<?php echo $success_msg ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?>"></i>
    <?php echo htmlspecialchars($success_msg ?: $error_msg); ?>
</div>
<?php endif; ?>

<!-- NAV -->
<?php

    include("navbar.php");

?>
<!-- HERO -->
<div class="hero">
    <div class="hero-tag">Rana's Table &nbsp;·&nbsp; Account</div>
    <h1 class="hero-title">Edit Profile</h1>
    <p class="hero-sub">Keep your details up to date for a seamless experience.</p>
    <div class="hero-divider"><div class="hdl"></div><div class="hdg"></div><div class="hdl"></div></div>
</div>

<div class="page">
<?php if($row): ?>
<div class="profile-layout">

    <!-- LEFT PANEL -->
    <div class="left-panel">
        <div class="avatar-ring"><i class="bi bi-person-fill"></i></div>
        <div class="lp-name"><?php echo htmlspecialchars($row['name']); ?></div>
        <div class="lp-email"><?php echo htmlspecialchars($row['email']); ?></div>

        <div class="lp-divider"></div>

        <div class="lp-stat">
            <div class="lp-stat-label">Phone</div>
            <div class="lp-stat-value"><?php echo !empty($row['phone']) ? htmlspecialchars($row['phone']) : '—'; ?></div>
        </div>
        <div class="lp-stat">
            <div class="lp-stat-label">Address</div>
            <div class="lp-stat-value"><?php echo !empty($row['address']) ? htmlspecialchars($row['address']) : '—'; ?></div>
        </div>

        <div class="lp-divider"></div>

        <a href="my_orders.php" style="display:flex;align-items:center;justify-content:center;gap:8px;font-size:13px;color:rgba(201,168,76,0.6);text-decoration:none;transition:color 0.2s;letter-spacing:1px;margin-bottom:10px;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='rgba(201,168,76,0.6)'">
            <i class="bi bi-bag-check-fill"></i> My Orders
        </a>
        <a href="logout.php" style="display:flex;align-items:center;justify-content:center;gap:8px;font-size:13px;color:rgba(224,92,58,0.6);text-decoration:none;transition:color 0.2s;letter-spacing:1px;" onmouseover="this.style.color='#f0896a'" onmouseout="this.style.color='rgba(224,92,58,0.6)'">
            <i class="bi bi-box-arrow-left"></i> Sign Out
        </a>
    </div>

    <!-- RIGHT PANEL: FORM -->
    <div class="form-panel">
        <div class="form-title">Personal Information</div>
        <div class="form-sub">Update your name, email, address, and password below.</div>

        <form action="" method="POST" id="profileForm">

            <!-- Basic Info -->
            <div class="form-section">
                <div class="form-section-label">Basic Info</div>
                <div class="field-row">
                    <div class="field">
                        <label for="f_name">Full Name</label>
                        <div class="input-wrap">
                            <i class="bi bi-person-fill i-icon"></i>
                            <input type="text" id="f_name" name="name" placeholder="Your full name" required
                                   value="<?php echo htmlspecialchars($row['name']); ?>">
                            <div class="focus-bar"></div>
                        </div>
                    </div>
                    <div class="field">
                        <label for="f_phone">Phone Number</label>
                        <div class="input-wrap">
                            <i class="bi bi-telephone-fill i-icon"></i>
                            <input type="text" id="f_phone" name="phone" placeholder="+92 300 0000000"
                                   value="<?php echo htmlspecialchars($row['phone']); ?>">
                            <div class="focus-bar"></div>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label for="f_email">Email Address</label>
                    <div class="input-wrap">
                        <i class="bi bi-envelope-fill i-icon"></i>
                        <input type="email" id="f_email" name="email" placeholder="you@example.com" required
                               value="<?php echo htmlspecialchars($row['email']); ?>">
                        <div class="focus-bar"></div>
                    </div>
                </div>

                <div class="field">
                    <label for="f_address">Delivery Address</label>
                    <div class="input-wrap">
                        <i class="bi bi-geo-alt-fill i-icon top"></i>
                        <textarea id="f_address" name="address" placeholder="Street, Area, City"><?php echo htmlspecialchars($row['address']); ?></textarea>
                        <div class="focus-bar"></div>
                    </div>
                </div>
            </div>

            <!-- Password -->
            <div class="form-section">
                <div class="form-section-label">Change Password</div>
                <div class="field">
                    <label for="f_password">New Password</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock-fill i-icon"></i>
                        <input type="password" id="f_password" name="password"
                               placeholder="Leave blank to keep current" autocomplete="new-password">
                        <button type="button" class="pw-toggle" id="pwToggle" tabindex="-1">
                            <i class="bi bi-eye-fill" id="pwToggleIcon"></i>
                        </button>
                        <div class="focus-bar"></div>
                    </div>
                    <div class="pw-strength">
                        <div class="pw-bar" id="bar1"></div>
                        <div class="pw-bar" id="bar2"></div>
                        <div class="pw-bar" id="bar3"></div>
                        <div class="pw-bar" id="bar4"></div>
                    </div>
                    <div class="pw-label" id="pwLabel"></div>
                    <div class="field-note">Leave blank to keep your current password.</div>
                </div>
            </div>

            <div class="form-footer">
                <button type="submit" name="submit" class="btn-update">
                    <i class="bi bi-check2-circle"></i> &nbsp;Save Changes
                </button>
                <a href="orders.php" class="btn-back">← Back to Menu</a>
            </div>

        </form>
    </div>

</div><!-- /profile-layout -->

<?php else: ?>
<div style="text-align:center;padding:80px 40px;position:relative;z-index:1;">
    <div style="font-size:56px;opacity:.2;margin-bottom:20px">👤</div>
    <p style="font-size:18px;color:var(--cream-dim);">User not found.</p>
</div>
<?php endif; ?>
</div>

<footer>
    <div class="f-brand">Rana's Table</div>
    <div class="f-note">© <?php echo date('Y'); ?> Rana's Table. All rights reserved.</div>
</footer>

<script>
/* Embers */
const ec=['#C9A84C','#FF8C42','#FFD166','#E87040'];
for(let i=0;i<18;i++){
    const e=document.createElement('div'); e.className='ember';
    const s=1.5+Math.random()*3, c=ec[Math.floor(Math.random()*ec.length)];
    Object.assign(e.style,{width:s+'px',height:s+'px',background:c,left:(4+Math.random()*92)+'%',bottom:'-8px','--d':((Math.random()-.5)*80)+'px',animationDuration:(9+Math.random()*10)+'s',animationDelay:(Math.random()*16)+'s',boxShadow:`0 0 ${s*2.5}px ${c}`});
    document.body.appendChild(e);
}

/* Toast */
const toast = document.getElementById('toast');
if(toast){ setTimeout(()=>toast.classList.add('show'),120); setTimeout(()=>toast.classList.remove('show'),4000); }

/* Password show/hide */
const pwField = document.getElementById('f_password');
const pwIcon  = document.getElementById('pwToggleIcon');
document.getElementById('pwToggle').addEventListener('click',()=>{
    const show = pwField.type === 'password';
    pwField.type = show ? 'text' : 'password';
    pwIcon.className = show ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
});

/* Password strength meter */
pwField.addEventListener('input', ()=>{
    const val = pwField.value;
    const bars = [document.getElementById('bar1'),document.getElementById('bar2'),document.getElementById('bar3'),document.getElementById('bar4')];
    const label = document.getElementById('pwLabel');
    bars.forEach(b=>{ b.className='pw-bar'; });

    if(!val){ label.textContent=''; return; }
    let score = 0;
    if(val.length >= 8)  score++;
    if(/[A-Z]/.test(val)) score++;
    if(/[0-9]/.test(val)) score++;
    if(/[^A-Za-z0-9]/.test(val)) score++;

    const classes = ['weak','weak','medium','strong'];
    const labels  = ['','Too short','Fair','Good','Strong'];
    const colors  = ['','#e05c3a','#e0a93a','#C9A84C','#5ab97a'];
    for(let i=0;i<score;i++) bars[i].classList.add(classes[score-1]);
    label.textContent = labels[score];
    label.style.color = colors[score];
});

/* Live update left panel */
document.getElementById('f_name').addEventListener('input', function(){
    document.querySelector('.lp-name').textContent = this.value || '—';
});
document.getElementById('f_email').addEventListener('input', function(){
    document.querySelector('.lp-email').textContent = this.value || '—';
});
document.getElementById('f_phone').addEventListener('input', function(){
    document.querySelectorAll('.lp-stat-value')[0].textContent = this.value || '—';
});
document.getElementById('f_address').addEventListener('input', function(){
    document.querySelectorAll('.lp-stat-value')[1].textContent = this.value || '—';
});
</script>
</body>
</html>