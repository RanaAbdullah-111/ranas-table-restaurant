<?php
    include("../config.php");
    require("../auth.php");

    $user_id = (int)$_SESSION['user_id'];
    $current_page = basename($_SERVER['PHP_SELF']);

    if(isset($_POST['add_quantity'])){
        $quantity = (int)$_POST['quantity'];
        $id       = (int)$_POST['id'];
        $stmt = $connection->prepare("UPDATE `card` SET `quantity` = `quantity` + ? WHERE `user_id` = ? AND `id` = ?");
        $stmt->bind_param("iii", $quantity, $user_id, $id);
        if($stmt->execute()){
            $cq = mysqli_query($connection, "SELECT SUM(quantity) AS total_qty FROM `card` WHERE user_id = $user_id");
            if($cq && $cr = mysqli_fetch_assoc($cq)) $_SESSION['cart_count'] = (int)($cr['total_qty'] ?? 0);
            header("location: $current_page"); exit();
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
            header("location: $current_page"); exit();
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
        .hero-divider .line {
            flex:1; height:0.5px;
            background:linear-gradient(to right,transparent,rgba(201,168,76,0.4),transparent);
        }
        .hero-divider .gem {
            width:5px; height:5px; background:var(--gold);
            transform:rotate(45deg); opacity:.7;
        }

        /* CART CONTAINER */
        .cart-layout {
            display:grid;
            grid-template-columns:1fr 340px;
            gap:36px;
            align-items:start;
        }

        /* ITEMS LIST */
        .items-list {
            display:flex; flex-direction:column; gap:16px;
        }

        /* CART ITEM */
        .cart-item {
            display:grid;
            grid-template-columns:90px 1fr auto auto;
            gap:20px;
            align-items:center;
            background:var(--card-bg);
            border:0.5px solid rgba(201,168,76,0.18);
            padding:18px 22px;
            backdrop-filter:blur(8px);
            position:relative;
            transition:all .3s ease;
            opacity:0; transform:translateY(20px);
        }
        .cart-item.visible {
            opacity:1; transform:translateY(0);
        }
        .cart-item::before {
            content:''; position:absolute; top:8px; left:8px;
            width:10px; height:10px;
            border-top:1px solid rgba(201,168,76,0.3);
            border-left:1px solid rgba(201,168,76,0.3);
            opacity:0; transition:opacity .3s;
        }
        .cart-item:hover {
            border-color:rgba(201,168,76,0.38);
            box-shadow:0 8px 32px rgba(0,0,0,0.4);
        }
        .cart-item:hover::before { opacity:1; }

        .item-img {
            width:90px; height:74px;
            border-radius:2px;
            object-fit:cover;
            border:0.5px solid rgba(201,168,76,0.2);
            background:rgba(201,168,76,0.05);
        }

        .item-details { display:flex; flex-direction:column; gap:4px; }
        .item-name {
            font-family:'Playfair Display',serif;
            font-size:18px; font-weight:400; color:var(--cream);
            letter-spacing:.3px;
        }
        .item-unit-price {
            font-size:13px; font-weight:300; color:var(--cream-dim);
        }
        .item-subtotal {
            font-family:'Playfair Display',serif;
            font-size:16px; font-weight:600; color:var(--gold);
            margin-top:2px;
        }

        /* QTY CONTROLS */
        .qty-controls {
            display:flex; align-items:center; gap:0;
            border:0.5px solid rgba(201,168,76,0.25);
            background:rgba(255,255,255,0.02);
        }
        .qty-btn {
            width:34px; height:34px;
            background:transparent; border:none;
            color:var(--gold); font-size:16px;
            cursor:pointer; display:flex; align-items:center; justify-content:center;
            transition:all .2s;
        }
        .qty-btn:hover {
            background:rgba(201,168,76,0.12);
            color:var(--gold-lite);
        }
        .qty-btn:active { background:rgba(201,168,76,0.22); }
        .qty-num {
            min-width:32px; text-align:center;
            font-family:'Playfair Display',serif;
            font-size:15px; font-weight:600; color:var(--cream);
            padding:0 4px;
        }

        /* ORDER SUMMARY CARD */
        .summary-card {
            background:var(--card-bg);
            border:0.5px solid rgba(201,168,76,0.22);
            padding:32px 28px;
            backdrop-filter:blur(14px);
            position:sticky; top:24px;
            animation:fadeUp 0.7s 0.3s both;
        }
        .summary-card::before, .summary-card::after {
            content:''; position:absolute;
            width:16px; height:16px;
            border-color:var(--gold); border-style:solid; opacity:.4;
        }
        .summary-card::before { top:10px; left:10px; border-width:1px 0 0 1px; }
        .summary-card::after  { bottom:10px; right:10px; border-width:0 1px 1px 0; }

        .summary-title {
            font-family:'Playfair Display',serif;
            font-size:22px; font-weight:400; color:var(--cream);
            margin-bottom:6px;
        }
        .summary-title em { color:var(--gold); font-style:italic; }
        .summary-divider {
            height:0.5px;
            background:linear-gradient(to right,transparent,rgba(201,168,76,0.3),transparent);
            margin:18px 0;
        }
        .summary-row {
            display:flex; justify-content:space-between; align-items:center;
            font-size:14px; font-weight:300; color:var(--cream-dim);
            margin-bottom:12px;
        }
        .summary-row .val { color:var(--cream); font-family:'Playfair Display',serif; font-size:15px; }

        .summary-total {
            display:flex; justify-content:space-between; align-items:baseline;
            margin-top:18px; padding-top:16px;
            border-top:0.5px solid rgba(201,168,76,0.18);
        }
        .summary-total .tl {
            font-size:12px; letter-spacing:3px; text-transform:uppercase;
            color:rgba(201,168,76,0.65);
        }
        .summary-total .tv {
            font-family:'Playfair Display',serif;
            font-size:26px; font-weight:700; color:var(--gold);
        }

        .confirm-form { margin-top:24px; }
        .btn-confirm {
            width:100%; padding:14px 20px;
            background:var(--gold); color:var(--deep);
            font-family:'Cormorant Garamond',serif;
            font-size:12px; font-weight:600; letter-spacing:3.5px; text-transform:uppercase;
            border:none; cursor:pointer; position:relative; overflow:hidden;
            transition:all .35s; display:block; text-align:center; text-decoration:none;
        }
        .btn-confirm::before {
            content:''; position:absolute; inset:0;
            background:linear-gradient(120deg,transparent 30%,rgba(255,255,255,0.28) 50%,transparent 70%);
            transform:translateX(-100%); transition:transform .55s;
        }
        .btn-confirm:hover::before { transform:translateX(100%); }
        .btn-confirm:hover {
            background:var(--gold-lite);
            transform:translateY(-2px);
            box-shadow:0 8px 28px rgba(201,168,76,0.25);
        }
        .btn-confirm:active { transform:translateY(0); }

        .back-link {
            display:block; text-align:center; margin-top:16px;
            font-size:12px; font-weight:300; letter-spacing:1px;
            color:rgba(201,168,76,0.45); text-decoration:none;
            transition:all .2s;
        }
        .back-link:hover { color:var(--gold); }

        /* EMPTY STATE */
        .empty {
            grid-column:1 / -1;
            text-align:center; padding:80px 20px;
            background:var(--card-bg);
            border:0.5px solid rgba(201,168,76,0.18);
        }
        .empty-icon { font-size:52px; margin-bottom:16px; opacity:.6; }
        .empty h3 {
            font-family:'Playfair Display',serif;
            font-size:28px; font-weight:400; color:var(--cream);
            margin-bottom:8px;
        }
        .empty p {
            font-size:15px; font-weight:300; color:var(--cream-dim);
            margin-bottom:28px; line-height:1.7;
        }
        .btn-menu {
            display:inline-block; padding:12px 32px;
            background:var(--gold); color:var(--deep);
            font-family:'Cormorant Garamond',serif;
            font-size:11px; font-weight:600; letter-spacing:3.5px; text-transform:uppercase;
            text-decoration:none; transition:all .3s;
        }
        .btn-menu:hover { background:var(--gold-lite); transform:translateY(-2px); }

        /* FOOTER */
        footer {
            position:relative; z-index:1;
            border-top:0.5px solid rgba(201,168,76,0.12);
            padding:32px 0; text-align:center; margin-top:60px;
        }
        .f-brand {
            font-family:'Playfair Display',serif;
            font-size:16px; color:var(--cream); opacity:.7; margin-bottom:6px;
        }
        .f-note { font-size:12px; font-weight:300; color:rgba(245,237,214,0.25); }

        @keyframes fadeUp {
            from { opacity:0; transform:translateY(18px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* RESPONSIVE */
        @media(max-width:768px) {
            .cart-layout { grid-template-columns:1fr; }
            .cart-item { grid-template-columns:70px 1fr auto; }
            .item-img { width:70px; height:60px; }
            .page { padding:0 16px 60px; }
        }
    </style>
</head>
<body>

<div class="bg-fixed"></div>

<?php include("navbar.php"); ?>

<div class="page">

    <div class="hero">
        <div class="hero-tag">Rana's Table</div>
        <h1 class="hero-title">Your Cart</h1>
        <p class="hero-sub">Review your selections before confirming your order</p>
        <div class="hero-divider">
            <div class="line"></div>
            <div class="gem"></div>
            <div class="line"></div>
        </div>
    </div>

    <div class="cart-layout">

        <?php if($has_items): ?>

        <div class="items-list">
            <?php foreach($items as $item): ?>
            <div class="cart-item">
                <img class="item-img"
                     src="../product_uploads/<?php echo htmlspecialchars($item['product_image']); ?>"
                     alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                     onerror="this.style.display='none'">

                <div class="item-details">
                    <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                    <span class="item-unit-price">PKR <?php echo number_format($item['product_price']); ?> each</span>
                    <span class="item-subtotal">PKR <?php echo number_format($item['subtotal']); ?></span>
                </div>

                <div class="qty-controls">
                    <form action="" method="POST" style="margin:0;">
                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button class="qty-btn" type="submit" name="remove_quantity" title="Decrease">">−</button>
                    </form>
                    <span class="qty-num"><?php echo $item['quantity']; ?></span>
                    <form action="" method="POST" style="margin:0;">
                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button class="qty-btn" type="submit" name="add_quantity" title="Increase">+</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="summary-card">
            <h2 class="summary-title">Order <em>Summary</em></h2>
            <div class="summary-divider"></div>

            <div class="summary-row">
                <span class="label">Total Items</span>
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

    const obs = new IntersectionObserver(entries=>{
        entries.forEach(e=>{ if(e.isIntersecting) e.target.classList.add('visible'); });
    },{threshold:0.08});
    document.querySelectorAll('.cart-item').forEach(c=>obs.observe(c));
</script>

</body>
</html>
