<?php
    include("../config.php");
    require("../auth.php");

    $user_id = (int)$_SESSION['user_id'];

    if(isset($_POST['add_quantity'])){
        $quantity = (int)$_POST['quantity'];
        $id       = (int)$_POST['id'];
        $stmt = $connection->prepare("UPDATE `card` SET `quantity` = `quantity` + ? WHERE `user_id` = ? AND `id` = ?");
        $stmt->bind_param("iii", $quantity, $user_id, $id);
        if($stmt->execute()){
            $cq = mysqli_query($connection, "SELECT SUM(quantity) AS total_qty FROM `card` WHERE user_id = $user_id");
            if($cq && $cr = mysqli_fetch_assoc($cq)) $_SESSION['cart_count'] = (int)($cr['total_qty'] ?? 0);
            header("location: card.php"); exit();
        }
    }

    if(isset($_POST['remove_quantity'])){
        $quantity = (int)$_POST['quantity'];
        $id       = (int)$_POST['id'];
        $check = $connection->prepare("SELECT * FROM `card` WHERE `user_id` = ? AND `id` = ?");
        $check->bind_param("ii", $user_id, $id);
        $check->execute();
        $fetch_check = $check->get_result()->fetch_assoc();

        if($fetch_check && $fetch_check['quantity'] > 1){
            $stmt = $connection->prepare("UPDATE `card` SET `quantity` = `quantity` - ? WHERE `user_id` = ? AND `id` = ?");
            $stmt->bind_param("iii", $quantity, $user_id, $id);
        } else {
            $stmt = $connection->prepare("DELETE FROM `card` WHERE `user_id` = ? AND `id` = ?");
            $stmt->bind_param("ii", $user_id, $id);
        }
        if($stmt->execute()){
            $cq = mysqli_query($connection, "SELECT SUM(quantity) AS total_qty FROM `card` WHERE user_id = $user_id");
            if($cq && $cr = mysqli_fetch_assoc($cq)) $_SESSION['cart_count'] = (int)($cr['total_qty'] ?? 0);
            header("location: card.php"); exit();
        }
    }

    // Fetch cart items with product details
    $stmt_price = $connection->prepare("
        SELECT card.*, product.product_name, product.product_price, product.product_image
        FROM card
        JOIN product ON card.product_id = product.id
        WHERE card.user_id = ?
    ");
    $stmt_price->bind_param("i", $user_id);
    $stmt_price->execute();
    $result_price = $stmt_price->get_result();

    $items = [];
    $total = 0;
    while($row = mysqli_fetch_assoc($result_price)){
        $row['subtotal'] = $row['product_price'] * $row['quantity'];
        $total += $row['subtotal'];
        $items[] = $row;
    }

    // Check if cart has items (for confirm order button)
    $has_items = count($items) > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart — Rana's Table</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Cormorant+Garamond:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        :root {
            --gold:      #C9A84C;
            --gold-lite: #E8C96A;
            --gold-dim:  rgba(201,168,76,0.14);
            --deep:      #110600;
            --warm:      #1e0c00;
            --card-bg:   rgba(30,12,0,0.82);
            --cream:     #F5EDD6;
            --cream-dim: rgba(245,237,214,0.52);
            --danger:    #c0392b;
            --danger-dim:rgba(192,57,43,0.15);
        }

        html { scroll-behavior:smooth; }

        body {
            font-family:'Cormorant Garamond', serif;
            background: var(--deep);
            color: var(--cream);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* BG */
        .bg-fixed {
            position:fixed; inset:0; z-index:0; pointer-events:none;
            background: radial-gradient(ellipse 80% 50% at 50% 0%, #3a1600 0%, #110600 65%);
        }

        /* EMBERS */
        .ember {
            position:fixed; border-radius:50%; pointer-events:none;
            opacity:0; animation:floatUp linear infinite; z-index:0;
        }
        @keyframes floatUp {
            0%   { transform:translateY(0) translateX(0); opacity:0; }
            8%   { opacity:.7; }
            90%  { opacity:.12; }
            100% { transform:translateY(-100vh) translateX(var(--d)); opacity:0; }
        }

        

        /* PAGE LAYOUT */
        .page {
            position:relative; z-index:1;
            max-width:1060px; margin:0 auto;
            padding:0 32px 80px;
        }

        /* HERO */
        .hero {
            text-align:center; padding:60px 20px 48px;
            position:relative; z-index:1;
        }
        .hero-tag {
            font-size:10px; letter-spacing:5px; text-transform:uppercase;
            color:rgba(201,168,76,0.55); margin-bottom:12px;
            animation:fadeUp 0.6s 0.1s both;
        }
        .hero-title {
            font-family:'Playfair Display',serif;
            font-size:clamp(38px,6vw,68px); font-weight:700; line-height:1;
            background:linear-gradient(135deg,#C9A84C 0%,#F0D08A 45%,#8B6914 100%);
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
            background-clip:text;
            animation:fadeUp 0.6s 0.2s both;
        }
        .hero-sub {
            font-size:16px; font-weight:300; color:var(--cream-dim);
            margin-top:10px; animation:fadeUp 0.6s 0.3s both;
        }
        .hero-divider {
            display:flex; align-items:center; gap:12px;
            width:220px; margin:22px auto 0;
            animation:fadeUp 0.6s 0.4s both;
        }
        .hdl { flex:1; height:.5px; background:linear-gradient(to right,transparent,rgba(201,168,76,.38),transparent); }
        .hdg { width:5px; height:5px; background:var(--gold); transform:rotate(45deg); flex-shrink:0; opacity:.65; }

        @keyframes fadeUp {
            from { opacity:0; transform:translateY(16px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* TWO-COLUMN LAYOUT */
        .cart-layout {
            display:grid;
            grid-template-columns:1fr 340px;
            gap:24px;
            align-items:start;
        }

        /* CART ITEMS */
        .cart-items { display:flex; flex-direction:column; gap:2px; }

        .cart-item {
            background:var(--card-bg);
            border:0.5px solid rgba(201,168,76,0.12);
            display:flex; align-items:center; gap:0;
            opacity:0; transform:translateX(-20px);
            transition:opacity 0.5s ease, transform 0.5s ease,
                        border-color 0.3s, box-shadow 0.3s;
            position:relative; overflow:hidden;
        }
        .cart-item.visible { opacity:1; transform:translateX(0); }
        .cart-item:hover {
            border-color:rgba(201,168,76,0.3);
            box-shadow:0 8px 32px rgba(0,0,0,0.4);
        }

        /* item image */
        .ci-img {
            width:110px; height:110px; flex-shrink:0;
            overflow:hidden; background:rgba(45,21,0,0.5);
        }
        .ci-img img {
            width:100%; height:100%; object-fit:cover;
            transition:transform 0.5s;
        }
        .cart-item:hover .ci-img img { transform:scale(1.08); }
        .ci-img-placeholder {
            width:100%; height:100%;
            display:flex; align-items:center; justify-content:center;
            font-size:32px; opacity:.2;
        }

        /* item info */
        .ci-info {
            flex:1; padding:18px 20px;
            display:flex; flex-direction:column; gap:6px;
        }
        .ci-name {
            font-family:'Playfair Display',serif;
            font-size:20px; font-weight:400; color:var(--cream);
        }
        .ci-unit {
            font-size:13px; font-weight:300; color:rgba(201,168,76,0.6);
            letter-spacing:0.5px;
        }
        .ci-subtotal {
            font-size:18px; color:var(--gold); font-weight:500;
            margin-top:4px;
        }

        /* qty controls */
        .ci-controls {
            padding:18px 20px;
            display:flex; flex-direction:column; align-items:center; gap:8px;
            flex-shrink:0;
        }
        .qty-display {
            font-family:'Playfair Display',serif;
            font-size:22px; color:var(--cream);
            min-width:32px; text-align:center; line-height:1;
        }
        .qty-btns { display:flex; gap:6px; }

        .qty-btn {
            width:32px; height:32px;
            background:rgba(201,168,76,0.1);
            border:0.5px solid rgba(201,168,76,0.25);
            color:var(--gold);
            font-size:18px; line-height:1;
            cursor:pointer; display:flex; align-items:center; justify-content:center;
            transition:all 0.25s; font-family:'Cormorant Garamond',serif;
        }
        .qty-btn:hover { background:var(--gold); color:var(--deep); }
        .qty-btn.minus:hover { background:var(--danger); border-color:var(--danger); color:#fff; }

        /* ORDER SUMMARY */
        .summary {
            background:var(--card-bg);
            border:0.5px solid rgba(201,168,76,0.18);
            padding:32px 28px;
            position:sticky; top:88px;
            animation:fadeUp 0.6s 0.5s both;
        }
        .summary::before, .summary::after {
            content:''; position:absolute;
            width:16px; height:16px;
            border-color:var(--gold); border-style:solid; opacity:.4;
        }
        .summary::before { top:10px; left:10px; border-width:1px 0 0 1px; }
        .summary::after  { bottom:10px; right:10px; border-width:0 1px 1px 0; }

        .summary-title {
            font-family:'Playfair Display',serif;
            font-size:24px; font-weight:400; color:var(--cream);
            margin-bottom:28px;
        }

        .summary-row {
            display:flex; justify-content:space-between; align-items:center;
            padding:12px 0;
            border-bottom:0.5px solid rgba(201,168,76,0.1);
            font-size:15px; font-weight:300; color:var(--cream-dim);
        }
        .summary-row:last-of-type { border-bottom:none; }

        .summary-row .label { letter-spacing:0.3px; }
        .summary-row .val   { color:var(--cream); font-weight:400; }

        .summary-divider {
            height:0.5px;
            background:linear-gradient(to right,transparent,rgba(201,168,76,0.3),transparent);
            margin:16px 0;
        }

        .summary-total {
            display:flex; justify-content:space-between; align-items:center;
            padding:16px 0 0;
        }
        .summary-total .tl {
            font-size:12px; letter-spacing:3px; text-transform:uppercase;
            color:rgba(201,168,76,0.6);
        }
        .summary-total .tv {
            font-family:'Playfair Display',serif;
            font-size:28px; color:var(--gold);
        }

        /* Confirm button */
        .confirm-form { margin-top:24px; }
        .btn-confirm {
            width:100%; padding:16px;
            background:var(--gold); color:var(--deep);
            font-family:'Cormorant Garamond',serif;
            font-size:13px; font-weight:700; letter-spacing:4px;
            text-transform:uppercase; border:none; cursor:pointer;
            position:relative; overflow:hidden; transition:all 0.3s;
        }
        .btn-confirm::before {
            content:''; position:absolute; inset:0;
            background:linear-gradient(120deg,transparent 30%,rgba(255,255,255,.25) 50%,transparent 70%);
            transform:translateX(-100%); transition:transform .55s;
        }
        .btn-confirm:hover::before { transform:translateX(100%); }
        .btn-confirm:hover { background:var(--gold-lite); transform:translateY(-2px); box-shadow:0 10px 32px rgba(201,168,76,.22); }
        .btn-confirm:active { transform:translateY(0); }
        .btn-confirm:disabled { opacity:.4; cursor:not-allowed; transform:none; }

        .back-link {
            display:block; text-align:center; margin-top:14px;
            font-size:12px; letter-spacing:2px; text-transform:uppercase;
            color:rgba(201,168,76,0.45); text-decoration:none;
            transition:color 0.2s;
        }
        .back-link:hover { color:var(--gold); }

        /* EMPTY STATE */
        .empty {
            grid-column:1/-1; text-align:center; padding:80px 40px;
        }
        .empty-icon { font-size:60px; opacity:.2; margin-bottom:20px; }
        .empty h3 { font-family:'Playfair Display',serif; font-size:30px; font-weight:400; margin-bottom:10px; }
        .empty p  { font-size:15px; font-weight:300; color:var(--cream-dim); margin-bottom:32px; }
        .btn-menu {
            padding:14px 36px;
            background:var(--gold); color:var(--deep);
            font-family:'Cormorant Garamond',serif;
            font-size:13px; font-weight:700; letter-spacing:3px; text-transform:uppercase;
            border:none; cursor:pointer; text-decoration:none; display:inline-block;
            transition:all 0.3s;
        }
        .btn-menu:hover { background:var(--gold-lite); transform:translateY(-2px); }

        /* FOOTER */
        footer {
            position:relative; z-index:1;
            border-top:0.5px solid rgba(201,168,76,0.1);
            padding:28px 40px;
            display:flex; justify-content:space-between; align-items:center;
            flex-wrap:wrap; gap:12px;
            max-width:1060px; margin:0 auto;
        }
        footer .f-brand { font-family:'Playfair Display',serif; font-size:17px; color:var(--gold); }
        footer .f-note  { font-size:12px; font-weight:300; color:rgba(245,237,214,.28); }

        @media(max-width:700px){
            nav { padding:0 20px; }
            .page { padding:0 16px 60px; }
            .cart-layout { grid-template-columns:1fr; }
            .summary { position:static; }
            .ci-img { width:80px; height:80px; }
            footer { padding:20px; flex-direction:column; text-align:center; }
        }
    </style>
</head>
<body>

<div class="bg-fixed"></div>

<!-- Navbar -->
<?php
    include("navbar.php");
?>

<!-- Hero -->
<div class="hero">
    <div class="hero-tag">Rana's Table &nbsp;·&nbsp; Your Selection</div>
    <h1 class="hero-title">Your Cart</h1>
    <p class="hero-sub">Review your order before confirming.</p>
    <div class="hero-divider"><div class="hdl"></div><div class="hdg"></div><div class="hdl"></div></div>
</div>

<!-- Page -->
<div class="page">
    <div class="cart-layout">

        <?php if($has_items): ?>

        <!-- Cart Items -->
        <div class="cart-items">
            <?php foreach($items as $i => $row): ?>
            <div class="cart-item" style="transition-delay:<?php echo $i * 0.07; ?>s">

                <!-- Image -->
                <div class="ci-img">
                    <?php if(!empty($row['product_image'])): ?>
                        <img src="../product_uploads/<?php echo htmlspecialchars($row['product_image']); ?>"
                             alt="<?php echo htmlspecialchars($row['product_name']); ?>"
                             loading="lazy">
                    <?php else: ?>
                        <div class="ci-img-placeholder">🍽</div>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div class="ci-info">
                    <div class="ci-name"><?php echo htmlspecialchars($row['product_name']); ?></div>
                    <div class="ci-unit">PKR <?php echo number_format($row['product_price']); ?> / serving</div>
                    <div class="ci-subtotal">PKR <?php echo number_format($row['subtotal']); ?></div>
                </div>

                <!-- Qty Controls -->
                <div class="ci-controls">
                    <div class="qty-display"><?php echo $row['quantity']; ?></div>
                    <div class="qty-btns">
                        <!-- Remove / Decrement -->
                        <form action="" method="POST">
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button class="qty-btn minus" type="submit" name="remove_quantity"
                                    title="<?php echo $row['quantity'] <= 1 ? 'Remove item' : 'Decrease'; ?>">
                                <?php echo $row['quantity'] <= 1 ? '🗑' : '−'; ?>
                            </button>
                        </form>
                        <!-- Increment -->
                        <form action="" method="POST">
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button class="qty-btn" type="submit" name="add_quantity" title="Increase">+</button>
                        </form>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

        <!-- Order Summary -->
        <div class="summary">
            <div class="summary-title">Order Summary</div>

            <?php foreach($items as $row): ?>
            <div class="summary-row">
                <span class="label"><?php echo htmlspecialchars($row['product_name']); ?> × <?php echo $row['quantity']; ?></span>
                <span class="val">PKR <?php echo number_format($row['subtotal']); ?></span>
            </div>
            <?php endforeach; ?>

            <div class="summary-divider"></div>

            <div class="summary-row">
                <span class="label">Items</span>
                <span class="val"><?php echo count($items); ?></span>
            </div>
            <div class="summary-row">
                <span class="label">Delivery</span>
                <span class="val" style="color:var(--gold);">Free</span>
            </div>

            <div class="summary-divider"></div>

            <div class="summary-total">
                <span class="tl">Total</span>
                <span class="tv">PKR <?php echo number_format($total); ?></span>
            </div>

            <div class="confirm-form">
                <form action="confirm_order.php" method="POST">
                    <button class="btn-confirm" type="submit">Confirm Order</button>
                </form>
            </div>
            <a href="orders.php" class="back-link">← Continue browsing menu</a>
        </div>

        <?php else: ?>

        <!-- Empty State -->
        <div class="empty">
            <div class="empty-icon">🛒</div>
            <h3>Your cart is empty</h3>
            <p>You haven't added any dishes yet.<br>Head back to the menu and choose your feast.</p>
            <a href="orders.php" class="btn-menu">Browse the Menu</a>
        </div>

        <?php endif; ?>

    </div>
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
        Object.assign(e.style,{
            width:s+'px',height:s+'px',background:c,
            left:(4+Math.random()*92)+'%',bottom:'-8px',
            '--d':((Math.random()-.5)*70)+'px',
            animationDuration:(9+Math.random()*10)+'s',
            animationDelay:(Math.random()*16)+'s',
            boxShadow:`0 0 ${s*2.5}px ${c}`
        });
        document.body.appendChild(e);
    }

    /* Scroll-reveal cart items */
    const obs = new IntersectionObserver(entries=>{
        entries.forEach(e=>{ if(e.isIntersecting) e.target.classList.add('visible'); });
    },{threshold:0.08});
    document.querySelectorAll('.cart-item').forEach(c=>obs.observe(c));
</script>

</body>
</html>