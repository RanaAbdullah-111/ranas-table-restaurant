<?php
session_start();
include("config.php");

// Google OAuth
require_once 'google_config.php';
$login_url = $client->createAuthUrl();

$error_msg        = "";
$wrong_pass       = false;
$not_found        = false;
$not_verified     = false;
$unverified_email = "";

if(isset($_POST['submit'])){
    $email    = trim($_POST['email']);
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
            $_SESSION['user']      = $email;
            $_SESSION['user_id']   = $row['id'];
            $_SESSION['role']      = $row['role'];
            $_SESSION['user_name'] = $row['name'] ?? '';
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
    <link rel="stylesheet" href="assets/css/auth.css">
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
            <a href="register.php" class="vb-otp-btn">Enter OTP →</a>
        </div>
    </div>

    <?php elseif($error_msg): ?>
    <div class="banner error shake">
        <span>⚠</span> &nbsp;<?php echo htmlspecialchars($error_msg); ?>
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

        <div class="register-link">
            New to Rana's Table? <a href="register.php">Create an account</a>
        </div>

        <div class="card-footer-line">
            <div class="div-line"></div><div class="div-gem"></div><div class="div-line"></div>
        </div>
    </form>
</div>

<script src="assets/js/auth.js"></script>
<script>
const emailF = document.getElementById('email');
const passF  = document.getElementById('password');
if (emailF && passF) (emailF.value ? passF : emailF).focus();
</script>
</body>
</html>