<?php
include("config.php");

$status = 'invalid'; // default

if (!empty($_GET['token'])) {
    $token = $_GET['token'];

    // Find user with this token who is not yet verified
    $stmt = $connection->prepare("SELECT id, name, email FROM `uesr` WHERE `verify_token` = ? AND `is_verified` = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Mark as verified and clear the token
        $upd = $connection->prepare("UPDATE `uesr` SET `is_verified` = 1, `verify_token` = NULL WHERE `id` = ?");
        $upd->bind_param("i", $user['id']);

        if ($upd->execute()) {
            $status     = 'success';
            $user_name  = $user['name'];
            $user_email = $user['email'];
        } else {
            $status = 'error';
        }
    } else {
        // Check if already verified (used link twice)
        $stmt2 = $connection->prepare("SELECT id FROM `uesr` WHERE `verify_token` = ? AND `is_verified` = 1");
        $stmt2->bind_param("s", $token);
        $stmt2->execute();
        if ($stmt2->get_result()->num_rows > 0) {
            $status = 'already';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Account — Rana's Table</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Cormorant+Garamond:wght@300;400;600&display=swap');
  *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
  :root { --gold:#C9A84C; --deep:#1A0A00; --cream:#F5EDD6; }
  body { font-family:'Cormorant Garamond',serif; background:var(--deep); color:var(--cream); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:40px 20px; }

  .ember { position:fixed; border-radius:50%; pointer-events:none; opacity:0; animation:rise linear infinite; z-index:0; }
  @keyframes rise { 0%{transform:translateY(0) translateX(0);opacity:0} 10%{opacity:.7} 90%{opacity:.2} 100%{transform:translateY(-100vh) translateX(var(--drift));opacity:0} }

  .card { position:relative; z-index:1; width:100%; max-width:440px; background:rgba(45,21,0,0.8); border:0.5px solid rgba(201,168,76,0.25); padding:52px 44px; backdrop-filter:blur(12px); text-align:center; animation:cardIn .8s cubic-bezier(0.16,1,0.3,1) both; }
  @keyframes cardIn { from{opacity:0;transform:translateY(28px)} to{opacity:1;transform:translateY(0)} }

  .logo-circle { width:72px; height:72px; border:0.5px solid rgba(201,168,76,0.4); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; }
  .logo-r { font-family:'Playfair Display',serif; font-size:30px; font-weight:700; background:linear-gradient(135deg,#C9A84C,#F0D08A); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }

  .status-icon { width:72px; height:72px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:30px; margin:0 auto 20px; animation:popIn .5s cubic-bezier(0.16,1,0.3,1) .2s both; }
  @keyframes popIn { from{transform:scale(0)} to{transform:scale(1)} }

  .icon-success { border:1.5px solid var(--gold); color:var(--gold); }
  .icon-error   { border:1.5px solid #e05c3a; color:#e05c3a; }
  .icon-already { border:1.5px solid #6AB4E8; color:#6AB4E8; }

  h1 { font-family:'Playfair Display',serif; font-size:26px; font-weight:400; margin-bottom:12px; }
  h1 em { color:var(--gold); font-style:italic; }
  p  { font-size:15px; font-weight:300; color:rgba(245,237,214,0.6); line-height:1.9; margin-bottom:8px; }
  .highlight { color:var(--gold); font-style:italic; }

  .divider { display:flex; align-items:center; gap:12px; margin:24px 0; }
  .divider-line { flex:1; height:0.5px; background:linear-gradient(to right,transparent,rgba(201,168,76,0.3),transparent); }
  .divider-dot  { width:4px; height:4px; background:var(--gold); transform:rotate(45deg); opacity:.6; }

  .btn {
    display:inline-block; padding:14px 36px; margin-top:8px;
    font-family:'Cormorant Garamond',serif; font-size:13px; font-weight:600;
    letter-spacing:4px; text-transform:uppercase; text-decoration:none;
    transition:all .25s; cursor:pointer; border:none;
  }
  .btn-gold { background:var(--gold); color:var(--deep); }
  .btn-gold:hover { background:#E8C96A; transform:translateY(-2px); box-shadow:0 10px 32px rgba(201,168,76,0.25); }
  .btn-outline { background:transparent; border:0.5px solid rgba(201,168,76,0.3); color:rgba(245,237,214,0.6); }
  .btn-outline:hover { border-color:var(--gold); color:var(--gold); }
</style>
</head>
<body>

<div class="card">

  <?php if ($status === 'success'): ?>
    <div class="status-icon icon-success">✓</div>
    <h1>Account <em>Verified</em></h1>
    <p>Welcome, <span class="highlight"><?php echo htmlspecialchars($user_name); ?></span>!</p>
    <p>Your email has been confirmed.<br>You can now sign in to Rana's Table.</p>
    <div class="divider"><div class="divider-line"></div><div class="divider-dot"></div><div class="divider-line"></div></div>
    <a href="login.php" class="btn btn-gold">Sign In Now</a>

  <?php elseif ($status === 'already'): ?>
    <div class="logo-circle"><span class="logo-r">R</span></div>
    <div class="status-icon icon-already" style="margin-top:0">ℹ</div>
    <h1>Already <em>Verified</em></h1>
    <p>This account has already been verified.</p>
    <div class="divider"><div class="divider-line"></div><div class="divider-dot"></div><div class="divider-line"></div></div>
    <a href="login.php" class="btn btn-gold">Sign In</a>

  <?php else: ?>
    <div class="status-icon icon-error">✕</div>
    <h1>Link <em>Invalid</em></h1>
    <p>This verification link is invalid or has expired.</p>
    <p style="font-size:13px;color:rgba(245,237,214,0.35);">Links expire after 24 hours.</p>
    <div class="divider"><div class="divider-line"></div><div class="divider-dot"></div><div class="divider-line"></div></div>
    <a href="user.php" class="btn btn-gold">Sign Up Again</a>
    &nbsp;&nbsp;
    <a href="login.php" class="btn btn-outline">Sign In</a>

  <?php endif; ?>

</div>

<script>
const c = ['#C9A84C','#FF8C42','#FFD166'];
for (let i = 0; i < 16; i++) {
    const e = document.createElement('div'), s = 2 + Math.random() * 3;
    e.className = 'ember';
    Object.assign(e.style, { width:s+'px', height:s+'px', background:c[i%3], left:(5+Math.random()*90)+'%', bottom:'-10px', '--drift':((Math.random()-.5)*60)+'px', animationDuration:(9+Math.random()*10)+'s', animationDelay:(Math.random()*12)+'s', boxShadow:`0 0 ${s*2}px ${c[i%3]}` });
    document.body.appendChild(e);
}
<?php if ($status === 'success'): ?>
setTimeout(() => window.location = 'login.php', 4000);
<?php endif; ?>
</script>
</body>
</html>