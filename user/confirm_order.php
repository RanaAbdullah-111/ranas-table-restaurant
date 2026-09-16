<?php
include("../config.php");
require("../auth.php");

require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user'];
$total   = 0;
$order_placed = false;
$order_error  = false;

/*
|--------------------------------------------------------------------------
| PLACE ORDER
|--------------------------------------------------------------------------
*/
if(isset($_POST['place_order'])){
    $customer_name    = trim($_POST['customer_name'] ?? '');
    $customer_phone   = trim($_POST['customer_phone'] ?? '');
    $customer_address = trim($_POST['customer_address'] ?? '');
    $payment_method   = trim($_POST['payment_method'] ?? 'Cash On Delivery');

    // Re-calculate total from DB (never trust client)
    $t_stmt = $connection->prepare("SELECT card.*, product.product_name, product.product_price FROM card JOIN product ON card.product_id = product.id WHERE card.user_id = ?");
    $t_stmt->bind_param("i", $user_id);
    $t_stmt->execute();
    $t_res = $t_stmt->get_result();

    $cart_items  = [];
    $order_total = 0;
    while($tr = $t_res->fetch_assoc()){
        $order_total += $tr['product_price'] * $tr['quantity'];
        $cart_items[] = $tr;
    }

    if(!empty($cart_items) && $order_total > 0){
        $insert_order = $connection->prepare("INSERT INTO orders (user_id, customer_name, customer_phone, customer_address, payment_method, total_amount) VALUES (?,?,?,?,?,?)");
        $insert_order->bind_param("issssi", $user_id, $customer_name, $customer_phone, $customer_address, $payment_method, $order_total);

        if($insert_order->execute()){
            $order_id = $connection->insert_id;

            // Insert order items
            $insert_item = $connection->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
            foreach($cart_items as $cart){
                $item_price = !empty($cart['price']) ? $cart['price'] : $cart['product_price'];
                $insert_item->bind_param("iiii", $order_id, $cart['product_id'], $cart['quantity'], $item_price);
                $insert_item->execute();
            }

            // Clear user's cart
            $delete = $connection->prepare("DELETE FROM card WHERE user_id = ?");
            $delete->bind_param("i", $user_id);
            $delete->execute();

            if(isset($_SESSION['cart_count'])) {
                $_SESSION['cart_count'] = 0;
            }

            $order_placed = true;

            /*
            |--------------------------------------------------------------------------
            | EMAIL DELIVERY (Triggered only after successful database insertion)
            |--------------------------------------------------------------------------
            */

            // 1. Customer Confirmation Email
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'ranaabrazzaq7@gmail.com';
                $mail->Password   = 'bfvp fqdc pjbz wzhe';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('ranaabrazzaq7@gmail.com', "Rana's Table");
                $mail->addAddress($user_email);

                $mail->Subject = 'Order Confirmation #' . $order_id . " — Rana's Table";
                $mail->isHTML(true);
                $mail->Body    = 'Thank you for your order, ' . htmlspecialchars($customer_name) . '!<br><br>' .
                                 'Your order <strong>#' . $order_id . '</strong> has been received successfully.<br>' .
                                 '<strong>Total Amount:</strong> PKR ' . number_format($order_total) . '<br>' .
                                 '<strong>Delivery Address:</strong> ' . htmlspecialchars($customer_address) . '<br><br>' .
                                 'We are preparing your meal with care.';

                $mail->send();
            } catch (Exception $e) {
                error_log("Customer order email error: " . $mail->ErrorInfo);
            }

            // 2. Admin Notification Email (Sent to admin inbox, not customer)
            try {
                $admin_email = 'ranaabrazzaq7@gmail.com';
                $admin_mail  = new PHPMailer(true);
                $admin_mail->isSMTP();
                $admin_mail->Host       = 'smtp.gmail.com';
                $admin_mail->SMTPAuth   = true;
                $admin_mail->Username   = 'ranaabrazzaq7@gmail.com';
                $admin_mail->Password   = 'bfvp fqdc pjbz wzhe';
                $admin_mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $admin_mail->Port       = 587;

                $admin_mail->setFrom('ranaabrazzaq7@gmail.com', "Rana's Table");
                $admin_mail->addAddress($admin_email);

                $admin_mail->Subject = 'New Order Received #' . $order_id . " — Rana's Table";
                $admin_mail->isHTML(true);
                $admin_mail->Body    = '<h3>New Order Received</h3>' .
                    '<strong>Order ID:</strong> #' . $order_id . '<br>' .
                    '<strong>Customer Name:</strong> ' . htmlspecialchars($customer_name) . '<br>' .
                    '<strong>Customer Email:</strong> ' . htmlspecialchars($user_email) . '<br>' .
                    '<strong>Customer Phone:</strong> ' . htmlspecialchars($customer_phone) . '<br>' .
                    '<strong>Customer Address:</strong> ' . htmlspecialchars($customer_address) . '<br>' .
                    '<strong>Payment Method:</strong> ' . htmlspecialchars($payment_method) . '<br>' .
                    '<strong>Total Amount:</strong> PKR ' . number_format($order_total) . '<br>';

                $admin_mail->send();
            } catch (Exception $e) {
                error_log("Admin order email error: " . $admin_mail->ErrorInfo);
            }

        } else {
            $order_error = true;
        }
    } else {
        $order_error = true;
    }
}

/*
|--------------------------------------------------------------------------
| FETCH CART ITEMS
|--------------------------------------------------------------------------
*/
$query = "SELECT card.*, product.product_name, product.product_price, product.product_image
          FROM card JOIN product ON card.product_id = product.id
          WHERE card.user_id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while($row = mysqli_fetch_assoc($result)){
    $row['subtotal'] = $row['product_price'] * $row['quantity'];
    $total += $row['subtotal'];
    $items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Order — Rana's Table</title>
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
            --success:   #5ab97a;
            --error:     #e05c3a;
        }

        html { scroll-behavior:smooth; }
        body {
            font-family:'Cormorant Garamond',serif;
            background:var(--deep); color:var(--cream);
            min-height:100vh; overflow-x:hidden;
        }

        .bg-fixed {
            position:fixed; inset:0; z-index:0; pointer-events:none;
            background:radial-gradient(ellipse 80% 50% at 50% 0%,#3a1600 0%,#110600 65%);
        }

        /* EMBERS */
        .ember { position:fixed; border-radius:50%; pointer-events:none; opacity:0; animation:floatUp linear infinite; z-index:0; }
        @keyframes floatUp {
            0%  { transform:translateY(0) translateX(0); opacity:0; }
            8%  { opacity:.7; }
            90% { opacity:.1; }
            100%{ transform:translateY(-100vh) translateX(var(--d)); opacity:0; }
        }

        
        /* HERO */
        .hero { position:relative; z-index:1; text-align:center; padding:52px 40px 36px; }
        .hero-tag { font-size:10px; letter-spacing:5px; text-transform:uppercase; color:rgba(201,168,76,0.55); margin-bottom:12px; animation:fadeUp 0.6s 0.1s both; }
        .hero-title {
            font-family:'Playfair Display',serif; font-size:clamp(36px,6vw,64px); font-weight:700; line-height:1;
            background:linear-gradient(135deg,#C9A84C 0%,#F0D08A 45%,#8B6914 100%);
            -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
            animation:fadeUp 0.6s 0.2s both;
        }
        .hero-sub { font-size:16px; font-weight:300; color:var(--cream-dim); margin-top:10px; animation:fadeUp 0.6s 0.3s both; }
        .hero-divider { display:flex; align-items:center; gap:12px; width:220px; margin:20px auto 0; animation:fadeUp 0.6s 0.4s both; }
        .hdl { flex:1; height:.5px; background:linear-gradient(to right,transparent,rgba(201,168,76,.38),transparent); }
        .hdg { width:5px; height:5px; background:var(--gold); transform:rotate(45deg); flex-shrink:0; opacity:.65; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

        /* PAGE LAYOUT */
        .page { position:relative; z-index:1; max-width:1060px; margin:0 auto; padding:0 32px 80px; }

        .two-col { display:grid; grid-template-columns:1fr 360px; gap:24px; align-items:start; }

        /* ORDER ITEMS TABLE */
        .items-panel {
            background:var(--card-bg); border:0.5px solid rgba(201,168,76,0.14);
            animation:fadeUp 0.6s 0.3s both;
        }
        .panel-head {
            padding:22px 28px; border-bottom:0.5px solid rgba(201,168,76,0.12);
            display:flex; align-items:center; justify-content:space-between;
        }
        .panel-title { font-family:'Playfair Display',serif; font-size:22px; font-weight:400; color:var(--cream); }
        .panel-count { font-size:11px; letter-spacing:3px; text-transform:uppercase; color:rgba(201,168,76,0.45); }

        /* Item rows */
        .order-item {
            display:flex; align-items:center; gap:0;
            border-bottom:0.5px solid rgba(201,168,76,0.07);
            transition:background 0.2s;
            opacity:0; transform:translateX(-16px);
            animation:slideIn 0.5s ease forwards;
        }
        .order-item:last-child { border-bottom:none; }
        .order-item:hover { background:rgba(201,168,76,0.03); }
        @keyframes slideIn { to{opacity:1;transform:translateX(0)} }

        .oi-img { width:80px; height:80px; flex-shrink:0; overflow:hidden; background:rgba(45,21,0,0.5); }
        .oi-img img { width:100%; height:100%; object-fit:cover; display:block; }
        .oi-img-ph { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:26px; opacity:.2; }

        .oi-info { flex:1; padding:16px 20px; }
        .oi-name { font-family:'Playfair Display',serif; font-size:18px; font-weight:400; color:var(--cream); margin-bottom:4px; }
        .oi-unit { font-size:13px; font-weight:300; color:rgba(201,168,76,0.55); }

        .oi-qty {
            width:48px; text-align:center; flex-shrink:0;
            font-size:13px; letter-spacing:2px; color:var(--cream-dim);
            padding:0 4px;
        }
        .oi-qty span { display:block; font-size:10px; letter-spacing:2px; text-transform:uppercase; color:rgba(201,168,76,0.4); margin-bottom:2px; }

        .oi-sub {
            width:110px; text-align:right; padding:0 22px; flex-shrink:0;
            font-size:17px; color:var(--gold); font-weight:500;
        }

        /* Total row */
        .total-row {
            padding:18px 28px; border-top:0.5px solid rgba(201,168,76,0.18);
            display:flex; justify-content:space-between; align-items:center;
            background:rgba(201,168,76,0.04);
        }
        .total-label { font-size:11px; letter-spacing:4px; text-transform:uppercase; color:rgba(201,168,76,0.55); }
        .total-value { font-family:'Playfair Display',serif; font-size:28px; color:var(--gold); }

        /* CHECKOUT FORM PANEL */
        .form-panel {
            background:var(--card-bg); border:0.5px solid rgba(201,168,76,0.14);
            padding:32px 28px;
            position:sticky; top:88px;
            animation:fadeUp 0.6s 0.45s both;
        }
        .form-panel::before, .form-panel::after {
            content:''; position:absolute;
            width:16px; height:16px; border-color:var(--gold); border-style:solid; opacity:.4;
        }
        .form-panel::before { top:10px; left:10px; border-width:1px 0 0 1px; }
        .form-panel::after  { bottom:10px; right:10px; border-width:0 1px 1px 0; }

        .form-title { font-family:'Playfair Display',serif; font-size:22px; font-weight:400; color:var(--cream); margin-bottom:28px; }

        .field { margin-bottom:20px; }
        label  { display:block; font-size:10px; letter-spacing:3.5px; text-transform:uppercase; color:rgba(201,168,76,0.62); margin-bottom:8px; }

        .input-wrap { position:relative; }
        .i-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); font-size:14px; color:rgba(201,168,76,0.3); pointer-events:none; transition:color 0.3s; }

        input[type="text"], textarea, select {
            width:100%; padding:12px 16px 12px 42px;
            background:rgba(255,255,255,0.03);
            border:0.5px solid rgba(201,168,76,0.18);
            color:var(--cream); font-family:'Cormorant Garamond',serif;
            font-size:16px; font-weight:300; outline:none;
            transition:all 0.3s; border-radius:0; -webkit-appearance:none;
        }
        textarea { padding-top:12px; padding-left:42px; resize:vertical; min-height:90px; }
        select   { cursor:pointer; }
        select option { background:#1e0c00; color:var(--cream); }

        input[type="text"]:focus, textarea:focus, select:focus {
            border-color:rgba(201,168,76,0.52);
            background:rgba(201,168,76,0.04);
            box-shadow:0 0 0 3px rgba(201,168,76,0.07);
        }
        input:focus ~ .i-icon, textarea:focus ~ .i-icon, select:focus ~ .i-icon { color:rgba(201,168,76,0.75); }
        ::placeholder { color:rgba(245,237,214,0.2); font-size:15px; }

        .focus-bar {
            position:absolute; bottom:0; left:0; right:0; height:1.5px;
            background:linear-gradient(to right,transparent,var(--gold),transparent);
            transform:scaleX(0); transform-origin:center;
            transition:transform 0.35s cubic-bezier(0.4,0,0.2,1); pointer-events:none;
        }
        input:focus + .focus-bar, textarea:focus + .focus-bar { transform:scaleX(1); }

        /* Payment options */
        .pay-options { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
        .pay-opt { position:relative; }
        .pay-opt input[type="radio"] { display:none; }
        .pay-opt label {
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            gap:6px; padding:14px 10px;
            background:rgba(255,255,255,0.03); border:0.5px solid rgba(201,168,76,0.18);
            cursor:pointer; transition:all 0.3s; text-align:center;
            font-size:11px; letter-spacing:2px; text-transform:uppercase;
            color:rgba(245,237,214,0.5); font-family:'Cormorant Garamond',serif;
        }
        .pay-opt label .pay-icon { font-size:22px; display:block; }
        .pay-opt input[type="radio"]:checked + label {
            border-color:var(--gold); color:var(--gold);
            background:rgba(201,168,76,0.06);
            box-shadow:0 0 0 1px rgba(201,168,76,0.25);
        }
        .pay-opt label:hover { border-color:rgba(201,168,76,0.35); color:rgba(201,168,76,0.7); }

        /* Divider */
        .form-divider { height:.5px; background:linear-gradient(to right,transparent,rgba(201,168,76,0.25),transparent); margin:22px 0; }

        /* Submit */
        .btn-place {
            width:100%; padding:16px;
            background:var(--gold); color:var(--deep);
            font-family:'Cormorant Garamond',serif; font-size:13px; font-weight:700;
            letter-spacing:4px; text-transform:uppercase; border:none; cursor:pointer;
            position:relative; overflow:hidden; transition:all 0.3s; margin-top:4px;
        }
        .btn-place::before {
            content:''; position:absolute; inset:0;
            background:linear-gradient(120deg,transparent 30%,rgba(255,255,255,.25) 50%,transparent 70%);
            transform:translateX(-100%); transition:transform .55s;
        }
        .btn-place:hover::before { transform:translateX(100%); }
        .btn-place:hover { background:var(--gold-lite); transform:translateY(-2px); box-shadow:0 10px 32px rgba(201,168,76,.22); }
        .btn-place:active { transform:translateY(0); }

        /* SUCCESS OVERLAY */
        .success-overlay {
            position:fixed; inset:0; z-index:999;
            background:rgba(10,4,0,0.96);
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            text-align:center; padding:40px;
            animation:fadeIn 0.5s ease both;
        }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }

        .success-ring {
            width:90px; height:90px;
            border:1px solid var(--gold); border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:36px; color:var(--gold); margin-bottom:28px;
            animation:popIn 0.6s cubic-bezier(0.16,1,0.3,1) 0.2s both;
        }
        @keyframes popIn { from{transform:scale(0);opacity:0} to{transform:scale(1);opacity:1} }

        .success-title { font-family:'Playfair Display',serif; font-size:36px; font-weight:400; color:var(--cream); margin-bottom:12px; }
        .success-msg   { font-size:16px; font-weight:300; color:var(--cream-dim); line-height:1.9; margin-bottom:36px; }
        .success-divider { display:flex; align-items:center; gap:12px; width:200px; margin:0 auto 36px; }

        .btn-back {
            padding:14px 40px; background:var(--gold); color:var(--deep);
            font-family:'Cormorant Garamond',serif; font-size:13px; font-weight:700;
            letter-spacing:4px; text-transform:uppercase; border:none; cursor:pointer;
            text-decoration:none; display:inline-block; transition:all 0.3s;
        }
        .btn-back:hover { background:var(--gold-lite); transform:translateY(-2px); }

        /* EMPTY */
        .empty { text-align:center; padding:80px 40px; position:relative; z-index:1; }
        .empty-icon { font-size:56px; opacity:.2; margin-bottom:20px; }
        .empty h3 { font-family:'Playfair Display',serif; font-size:28px; font-weight:400; margin-bottom:10px; }
        .empty p  { font-size:15px; font-weight:300; color:var(--cream-dim); margin-bottom:28px; }
        .btn-go { padding:13px 32px; background:var(--gold); color:var(--deep); font-family:'Cormorant Garamond',serif; font-size:12px; font-weight:700; letter-spacing:3px; text-transform:uppercase; border:none; cursor:pointer; text-decoration:none; display:inline-block; transition:all 0.3s; }
        .btn-go:hover { background:var(--gold-lite); transform:translateY(-2px); }

        footer { position:relative; z-index:1; border-top:0.5px solid rgba(201,168,76,0.1); padding:28px 40px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; max-width:1060px; margin:0 auto; }
        footer .f-brand { font-family:'Playfair Display',serif; font-size:17px; color:var(--gold); }
        footer .f-note  { font-size:12px; font-weight:300; color:rgba(245,237,214,.28); }

        @media(max-width:720px){
            nav { padding:0 16px; }
            .hero { padding:40px 16px 28px; }
            .page { padding:0 14px 60px; }
            .two-col { grid-template-columns:1fr; }
            .form-panel { position:static; }
            .oi-sub { width:80px; padding:0 14px; }
            footer { padding:20px 16px; flex-direction:column; text-align:center; }
        }
    </style>
</head>
<body>

<div class="bg-fixed"></div>

<?php if($order_placed): ?>
<!-- SUCCESS OVERLAY -->
<div class="success-overlay">
    <div class="success-ring">✓</div>
    <h2 class="success-title">Order Placed!</h2>
    <p class="success-msg">
        Thank you, <strong style="color:var(--gold)"><?php echo htmlspecialchars($_POST['customer_name']); ?></strong>.<br>
        Your royal feast is on its way. We'll be in touch shortly.
    </p>
    <div class="success-divider">
        <div class="hdl"></div><div class="hdg" style="width:5px;height:5px;background:var(--gold);transform:rotate(45deg);flex-shrink:0"></div><div class="hdl"></div>
    </div>
    <a href="orders.php" class="btn-back">Back to Menu</a>
</div>
<?php endif; ?>

<!-- NAV -->
<?php
    include("navbar.php");
?>

<!-- HERO -->
<div class="hero">
    <div class="hero-tag">Rana's Table &nbsp;·&nbsp; Final Step</div>
    <h1 class="hero-title">Confirm Your Order</h1>
    <p class="hero-sub">Review your items and fill in your delivery details.</p>
    <div class="hero-divider"><div class="hdl"></div><div class="hdg"></div><div class="hdl"></div></div>
</div>

<div class="page">
<?php if(!empty($items)): ?>
<div class="two-col">

    <!-- LEFT: Order Items -->
    <div class="items-panel">
        <div class="panel-head">
            <span class="panel-title">Your Order</span>
            <span class="panel-count"><?php echo count($items); ?> item<?php echo count($items)!==1?'s':''; ?></span>
        </div>

        <?php foreach($items as $i => $row): ?>
        <div class="order-item" style="animation-delay:<?php echo $i*0.08; ?>s">
            <div class="oi-img">
                <?php if(!empty($row['product_image'])): ?>
                    <img src="../product_uploads/<?php echo htmlspecialchars($row['product_image']); ?>"
                         alt="<?php echo htmlspecialchars($row['product_name']); ?>">
                <?php else: ?>
                    <div class="oi-img-ph">🍽</div>
                <?php endif; ?>
            </div>
            <div class="oi-info">
                <div class="oi-name"><?php echo htmlspecialchars($row['product_name']); ?></div>
                <div class="oi-unit">PKR <?php echo number_format($row['product_price']); ?> / serving</div>
            </div>
            <div class="oi-qty">
                <span>Qty</span>
                <?php echo $row['quantity']; ?>
            </div>
            <div class="oi-sub">PKR <?php echo number_format($row['subtotal']); ?></div>
        </div>
        <?php endforeach; ?>

        <div class="total-row">
            <span class="total-label">Grand Total</span>
            <span class="total-value">PKR <?php echo number_format($total); ?></span>
        </div>
    </div>

    <!-- RIGHT: Checkout Form -->
    <div class="form-panel">
        <div class="form-title">Delivery Details</div>

        <form action="" method="POST">

            <div class="field">
                <label for="c_name">Full Name</label>
                <div class="input-wrap">
                    <span class="i-icon">👤</span>
                    <input type="text" id="c_name" name="customer_name"
                           placeholder="e.g. Rana Ahmed" required
                           value="<?php echo isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : ''; ?>">
                    <div class="focus-bar"></div>
                </div>
            </div>

            <div class="field">
                <label for="c_phone">Phone Number</label>
                <div class="input-wrap">
                    <span class="i-icon">📞</span>
                    <input type="text" id="c_phone" name="customer_phone"
                           placeholder="+92 300 0000000" required
                           value="<?php echo isset($_POST['customer_phone']) ? htmlspecialchars($_POST['customer_phone']) : ''; ?>">
                    <div class="focus-bar"></div>
                </div>
            </div>

            <div class="field">
                <label for="c_addr">Delivery Address</label>
                <div class="input-wrap">
                    <span class="i-icon">📍</span>
                    <textarea id="c_addr" name="customer_address"
                              placeholder="Street, Area, City" required><?php echo isset($_POST['customer_address']) ? htmlspecialchars($_POST['customer_address']) : ''; ?></textarea>
                    <div class="focus-bar"></div>
                </div>
            </div>

            <div class="field">
                <label>Payment Method</label>
                <div class="pay-options">
                    <div class="pay-opt">
                        <input type="radio" id="cod" name="payment_method" value="Cash On Delivery" checked>
                        <label for="cod">
                            <span class="pay-icon">💵</span>
                            Cash on Delivery
                        </label>
                    </div>
                    <div class="pay-opt">
                        <input type="radio" id="bank" name="payment_method" value="Bank Transfer">
                        <label for="bank">
                            <span class="pay-icon">🏦</span>
                            Bank Transfer
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-divider"></div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <span style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:rgba(201,168,76,0.5);">Total</span>
                <span style="font-family:'Playfair Display',serif;font-size:24px;color:var(--gold);">PKR <?php echo number_format($total); ?></span>
            </div>

            <button class="btn-place" type="submit" name="place_order">Place Order</button>

        </form>
    </div>

</div><!-- /two-col -->

<?php else: ?>
<div class="empty">
    <div class="empty-icon">🛒</div>
    <h3>Your cart is empty</h3>
    <p>Add some dishes before confirming your order.</p>
    <a href="orders.php" class="btn-go">Browse Menu</a>
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
for(let i=0;i<20;i++){
    const e=document.createElement('div'); e.className='ember';
    const s=1.5+Math.random()*3, c=ec[Math.floor(Math.random()*ec.length)];
    Object.assign(e.style,{width:s+'px',height:s+'px',background:c,left:(4+Math.random()*92)+'%',bottom:'-8px','--d':((Math.random()-.5)*80)+'px',animationDuration:(9+Math.random()*10)+'s',animationDelay:(Math.random()*16)+'s',boxShadow:`0 0 ${s*2.5}px ${c}`});
    document.body.appendChild(e);
}
</script>
</body>
</html>