<?php
    include("../config.php");
    require("../auth.php");

    $success_msg = "";

    if(isset($_POST['submit'])){
        $product_id    = (int)$_POST['product_id'];
        $quantity      = max(1, (int)($_POST['quantity'] ?? 1));
        $product_price = (int)$_POST['product_price'];
        $user_id       = (int)($_SESSION['user_id'] ?? 0);

        if($user_id > 0 && $product_id > 0){
            // Check if product is already in the cart for this user
            $check = $connection->prepare("SELECT id, quantity FROM `card` WHERE `user_id` = ? AND `product_id` = ?");
            $check->bind_param("ii", $user_id, $product_id);
            $check->execute();
            $existing = $check->get_result()->fetch_assoc();

            if($existing){
                $upd = $connection->prepare("UPDATE `card` SET `quantity` = `quantity` + ? WHERE `id` = ?");
                $upd->bind_param("ii", $quantity, $existing['id']);
                $upd->execute();
            } else {
                $stmt = $connection->prepare("INSERT INTO `card`(`user_id`, `product_id`, `quantity`, `price`) VALUES (?,?,?,?)");
                $stmt->bind_param("iiii", $user_id, $product_id, $quantity, $product_price);
                $stmt->execute();
            }

            // Sync cart count
            $cq = mysqli_query($connection, "SELECT SUM(quantity) AS total_qty FROM `card` WHERE user_id = $user_id");
            if($cq && $cr = mysqli_fetch_assoc($cq)){
                $_SESSION['cart_count'] = (int)($cr['total_qty'] ?? 0);
            }

            $success_msg = "Item added to your cart!";
        } else {
            $success_msg = "Please log in to add items to your cart.";
        }
    }

    $cat_result = mysqli_query($connection, "SELECT * FROM category ORDER BY id ASC");
    $categories = [];
    if($cat_result && mysqli_num_rows($cat_result) > 0){
        while($c = mysqli_fetch_assoc($cat_result)) $categories[] = $c;
    }

    $prod_sql = "SELECT product.*, category.category_name
                 FROM product
                 LEFT JOIN category ON product.category_id = category.id
                 ORDER BY product.category_id ASC, product.id ASC";
    $prod_result = mysqli_query($connection, $prod_sql);

    $products_by_cat = [];
    $all_products    = [];
    if($prod_result && mysqli_num_rows($prod_result) > 0){
        while($row = mysqli_fetch_assoc($prod_result)){
            $cid = $row['category_id'] ?? 0;
            $products_by_cat[$cid][] = $row;
            $all_products[] = $row;
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu — Rana's Table</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Cormorant+Garamond:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        :root {
            --gold:      #C9A84C;
            --gold-lite: #E8C96A;
            --deep:      #110600;
            --card-bg:   rgba(35,15,2,0.85);
            --cream:     #F5EDD6;
            --cream-dim: rgba(245,237,214,0.52);
        }

        /* ── CRITICAL: html scrolls, body does NOT clip ── */
        html {
            scroll-behavior: smooth;
        }
        body {
            font-family: 'Cormorant Garamond', serif;
            background: var(--deep);
            color: var(--cream);
            min-height: 100vh;
            padding-top: 64px;
            /* NO overflow-x:hidden here — it breaks sticky + scroll */
        }

        .bg-fixed {
            position:fixed; inset:0; z-index:0; pointer-events:none;
            background: radial-gradient(ellipse 80% 50% at 50% 0%, #3a1600 0%, #110600 65%);
        }

        .ember {
            position:fixed; border-radius:50%; pointer-events:none;
            opacity:0; animation:floatUp linear infinite; z-index:0;
        }
        @keyframes floatUp {
            0%   { transform:translateY(0) translateX(0); opacity:0; }
            8%   { opacity:.75; }
            90%  { opacity:.12; }
            100% { transform:translateY(-100vh) translateX(var(--d)); opacity:0; }
        }

        /* ── TOP NAV ── */

        /* ── CATEGORY BAR ──
           Key rules:
           - position:sticky works only when NO ancestor has overflow hidden/auto on the scroll axis
           - The bar itself scrolls horizontally
           - inner row is a single nowrap flex line — NEVER wraps
        ── */
        .cat-bar {
            position: sticky;
            top: 64px;
            z-index: 190;
            background: rgba(20,8,0,0.96);
            backdrop-filter: blur(14px);
            border-bottom: 0.5px solid rgba(201,168,76,0.14);

            /* horizontal scroll */
            overflow-x: auto;
            overflow-y: hidden;

            /* visible gold scrollbar */
            scrollbar-width: thin;
            scrollbar-color: #C9A84C rgba(45,21,0,0.6);
            -ms-overflow-style: none;       /* IE/Edge */
            -webkit-overflow-scrolling: touch;
        }
        .cat-bar::-webkit-scrollbar { height: 4px; }
        .cat-bar::-webkit-scrollbar-track { background: rgba(45,21,0,0.6); }
        .cat-bar::-webkit-scrollbar-thumb { background: #C9A84C; border-radius: 2px; }
        .cat-bar::-webkit-scrollbar-thumb:hover { background: #E8C96A; }

        .cat-bar-inner {
            /* single unwrapping row */
            display: flex;
            flex-wrap: nowrap;
            align-items: stretch;

            /* wide enough for all buttons */
            width: max-content;
            min-width: 100%;

            padding: 0 24px;
        }

        .cat-btn {
            flex: 0 0 auto;          /* never grow, never shrink, never wrap */
            white-space: nowrap;

            position: relative;
            padding: 0 24px;
            height: 50px;

            font-family: 'Cormorant Garamond', serif;
            font-size: 12px; font-weight: 500;
            letter-spacing: 3px; text-transform: uppercase;
            color: rgba(245,237,214,0.42);

            background: transparent; border: none;
            cursor: pointer;
            transition: color 0.3s;

            display: flex; align-items: center;
        }
        /* gold underline indicator */
        .cat-btn::after {
            content: '';
            position: absolute; bottom: 0;
            left: 50%; right: 50%;
            height: 2px;
            background: var(--gold);
            transition: left 0.32s cubic-bezier(0.4,0,0.2,1),
                        right 0.32s cubic-bezier(0.4,0,0.2,1);
        }
        .cat-btn:hover { color: rgba(201,168,76,0.7); }
        .cat-btn.active { color: var(--gold); }
        .cat-btn.active::after { left: 0; right: 0; }

        .cat-sep { flex:0 0 1px; width:1px; background:rgba(201,168,76,0.1); align-self:center; height:18px; }

        /* ── HERO ── */
        .hero { position:relative; z-index:1; text-align:center; padding:56px 40px 40px; }
        .hero-tag { font-size:10px; letter-spacing:5px; text-transform:uppercase; color:rgba(201,168,76,0.55); margin-bottom:12px; animation:fadeUp 0.7s 0.1s both; }
        .hero-title {
            font-family:'Playfair Display',serif; font-size:clamp(40px,7vw,74px); font-weight:700; line-height:1;
            background:linear-gradient(135deg,#C9A84C 0%,#F0D08A 45%,#8B6914 100%);
            -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
            animation:fadeUp 0.7s 0.2s both;
        }
        .hero-sub { font-size:16px; font-weight:300; color:var(--cream-dim); margin-top:10px; animation:fadeUp 0.7s 0.3s both; }
        .hero-divider { display:flex; align-items:center; gap:12px; width:220px; margin:20px auto 0; animation:fadeUp 0.7s 0.4s both; }
        .hdl { flex:1; height:.5px; background:linear-gradient(to right,transparent,rgba(201,168,76,.38),transparent); }
        .hdg { width:5px; height:5px; background:var(--gold); transform:rotate(45deg); flex-shrink:0; opacity:.65; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }

        /* ── TOAST ── */
        .toast {
            position:fixed; bottom:32px; left:50%;
            transform:translateX(-50%) translateY(80px);
            background:rgba(25,55,35,0.97); border:0.5px solid rgba(90,185,122,0.4);
            color:#8de8a8; padding:14px 28px; font-size:14px; letter-spacing:1px;
            z-index:999; transition:transform 0.5s cubic-bezier(0.16,1,0.3,1);
            white-space:nowrap; pointer-events:none;
        }
        .toast.show { transform:translateX(-50%) translateY(0); }

        /* ── MENU WRAPPER ── */
        .menu-wrap { position:relative; z-index:1; max-width:1120px; margin:0 auto; padding:0 32px 80px; }

        /* ── CATEGORY SECTION ── */
        .cat-section { margin-bottom:52px; }
        .cat-section-header { display:flex; align-items:center; gap:18px; margin-bottom:22px; padding-top:8px; }
        .cat-section-title { font-family:'Playfair Display',serif; font-size:30px; font-weight:400; color:var(--cream); white-space:nowrap; }
        .cat-section-line { flex:1; height:.5px; background:linear-gradient(to right,rgba(201,168,76,0.28),transparent); }
        .cat-section-count { font-size:10px; letter-spacing:3px; text-transform:uppercase; color:rgba(201,168,76,0.38); white-space:nowrap; }

        /* ── PRODUCT GRID ── */
        .menu-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(285px,1fr)); gap:2px; }

        /* ── PRODUCT CARD ── */
        .p-card {
            background:var(--card-bg); border:0.5px solid rgba(201,168,76,0.11);
            display:flex; flex-direction:column;
            opacity:0; transform:translateY(22px);
            transition:opacity 0.5s ease, transform 0.5s ease, border-color 0.3s, box-shadow 0.3s;
            position:relative; overflow:hidden;
        }
        .p-card.visible { opacity:1; transform:translateY(0); }
        .p-card:hover { border-color:rgba(201,168,76,0.35); box-shadow:0 12px 48px rgba(0,0,0,0.5); z-index:2; }
        .p-card::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(201,168,76,0.05),transparent); opacity:0; transition:opacity 0.4s; pointer-events:none; }
        .p-card:hover::before { opacity:1; }

        .p-img-wrap { width:100%; height:196px; overflow:hidden; background:rgba(45,21,0,0.6); position:relative; }
        .p-img-wrap img { width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.6s cubic-bezier(0.4,0,0.2,1); }
        .p-card:hover .p-img-wrap img { transform:scale(1.06); }
        .p-img-placeholder { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:48px; opacity:.2; }

        .p-cat-label {
            position:absolute; top:12px; left:12px;
            background:rgba(17,6,0,0.82); border:0.5px solid rgba(201,168,76,0.35);
            color:var(--gold); font-size:9px; font-weight:600; letter-spacing:2.5px; text-transform:uppercase;
            padding:4px 10px; backdrop-filter:blur(6px);
        }

        .p-body { padding:22px 22px 20px; flex:1; display:flex; flex-direction:column; gap:9px; }
        .p-number { font-size:10px; letter-spacing:3px; color:rgba(201,168,76,0.45); }
        .p-name { font-family:'Playfair Display',serif; font-size:22px; font-weight:400; color:var(--cream); line-height:1.15; }
        .p-price { font-size:20px; color:var(--gold); font-weight:500; }
        .p-price span { font-size:12px; font-weight:300; color:var(--cream-dim); letter-spacing:1px; }

        .p-form { display:flex; gap:8px; align-items:center; margin-top:auto; padding-top:14px; }
        .qty-wrap { position:relative; flex:1; }
        .qty-input {
            width:100%; padding:11px 14px;
            background:rgba(255,255,255,0.04); border:0.5px solid rgba(201,168,76,0.18);
            color:var(--cream); font-family:'Cormorant Garamond',serif; font-size:16px; font-weight:300;
            outline:none; transition:all 0.3s; -webkit-appearance:none; border-radius:0;
        }
        .qty-input:focus { border-color:rgba(201,168,76,0.5); background:rgba(201,168,76,0.05); box-shadow:0 0 0 3px rgba(201,168,76,0.07); }
        .qty-input::placeholder { color:rgba(245,237,214,0.22); font-size:14px; }

        .p-add-btn {
            padding:11px 20px; background:var(--gold); color:var(--deep);
            font-family:'Cormorant Garamond',serif; font-size:12px; font-weight:700; letter-spacing:2px; text-transform:uppercase;
            border:none; cursor:pointer; position:relative; overflow:hidden;
            transition:all 0.3s; white-space:nowrap; flex-shrink:0;
        }
        .p-add-btn::before {
            content:''; position:absolute; inset:0;
            background:linear-gradient(120deg,transparent 30%,rgba(255,255,255,.25) 50%,transparent 70%);
            transform:translateX(-100%); transition:transform .5s;
        }
        .p-add-btn:hover::before { transform:translateX(100%); }
        .p-add-btn:hover { background:var(--gold-lite); transform:translateY(-1px); }
        .p-add-btn:active { transform:translateY(0); }

        .empty { text-align:center; padding:100px 40px; position:relative; z-index:1; }
        .empty-icon { font-size:56px; opacity:.22; margin-bottom:20px; }
        .empty h3 { font-family:'Playfair Display',serif; font-size:28px; font-weight:400; margin-bottom:10px; }
        .empty p  { font-size:15px; font-weight:300; color:var(--cream-dim); }

        footer {
            position:relative; z-index:1; border-top:0.5px solid rgba(201,168,76,0.1);
            padding:28px 40px; display:flex; justify-content:space-between; align-items:center;
            flex-wrap:wrap; gap:12px; max-width:1120px; margin:0 auto;
        }
        footer .f-brand { font-family:'Playfair Display',serif; font-size:17px; color:var(--gold); }
        footer .f-note  { font-size:12px; font-weight:300; color:rgba(245,237,214,.28); }

        @media(max-width:640px){
            .hero { padding:40px 16px 28px; }
            .menu-wrap { padding:0 14px 60px; }
            .menu-grid { grid-template-columns:1fr; }
            footer { padding:20px 16px; flex-direction:column; text-align:center; }
        }
    </style>
</head>
<body>

<div class="bg-fixed"></div>

<!-- TOP NAV -->
<?php include("navbar.php");  ?>

<!-- CATEGORY BAR -->
<div class="cat-bar" id="catBar">
    <div class="cat-bar-inner" id="catBarInner">
        <button class="cat-btn active" data-cat="all">All</button>
        <?php foreach($categories as $cat): ?>
            <div class="cat-sep"></div>
            <button class="cat-btn" data-cat="<?php echo $cat['id']; ?>">
                <?php echo htmlspecialchars($cat['category_name']); ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>

<!-- HERO -->
<div class="hero">
    <div class="hero-tag">Rana's Table &nbsp;·&nbsp; Today's Selection</div>
    <h1 class="hero-title">The Royal Menu</h1>
    <p class="hero-sub">Scroll or tap a category to explore.</p>
    <div class="hero-divider"><div class="hdl"></div><div class="hdg"></div><div class="hdl"></div></div>
</div>

<?php if($success_msg): ?>
<div class="toast" id="toast">✓ &nbsp;<?php echo htmlspecialchars($success_msg); ?></div>
<?php endif; ?>

<!-- MENU SECTIONS -->
<div class="menu-wrap">
<?php if(!empty($all_products)): ?>

    <?php foreach($categories as $cat):
        $cid   = $cat['id'];
        $prods = $products_by_cat[$cid] ?? [];
        if(empty($prods)) continue;
    ?>
    <div class="cat-section" id="cat-sec-<?php echo $cid; ?>" data-section="<?php echo $cid; ?>">
        <div class="cat-section-header">
            <h2 class="cat-section-title"><?php echo htmlspecialchars($cat['category_name']); ?></h2>
            <div class="cat-section-line"></div>
            <span class="cat-section-count"><?php echo count($prods); ?> dish<?php echo count($prods)!==1?'es':''; ?></span>
        </div>
        <div class="menu-grid">
            <?php foreach($prods as $j => $row): ?>
            <div class="p-card" style="transition-delay:<?php echo $j*0.07; ?>s">
                <div class="p-img-wrap">
                    <?php if(!empty($row['product_image'])): ?>
                        <img src="../product_uploads/<?php echo htmlspecialchars($row['product_image']); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="p-img-placeholder">🍽</div>
                    <?php endif; ?>
                    <div class="p-cat-label"><?php echo htmlspecialchars($cat['category_name']); ?></div>
                </div>
                <div class="p-body">
                    <div class="p-number"><?php printf('%02d',$j+1); ?></div>
                    <div class="p-name"><?php echo htmlspecialchars($row['product_name']); ?></div>
                    <div class="p-price">PKR <?php echo number_format($row['product_price']); ?> <span>/ serving</span></div>
                    <form class="p-form" action="" method="POST">
                        <input type="hidden" name="product_id"    value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="product_price" value="<?php echo $row['product_price']; ?>">
                        <div class="qty-wrap">
                            <input class="qty-input" type="number" name="quantity" value="1" placeholder="Qty" min="1" max="99" required>
                        </div>
                        <button class="p-add-btn" type="submit" name="submit">Add</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <?php
    $uncategorised = $products_by_cat[0] ?? [];
    if(!empty($uncategorised)):
    ?>
    <div class="cat-section" id="cat-sec-0" data-section="0">
        <div class="cat-section-header">
            <h2 class="cat-section-title">Other Dishes</h2>
            <div class="cat-section-line"></div>
            <span class="cat-section-count"><?php echo count($uncategorised); ?> dishes</span>
        </div>
        <div class="menu-grid">
            <?php foreach($uncategorised as $j => $row): ?>
            <div class="p-card" style="transition-delay:<?php echo $j*0.07; ?>s">
                <div class="p-img-wrap">
                    <?php if(!empty($row['product_image'])): ?>
                        <img src="../product_uploads/<?php echo htmlspecialchars($row['product_image']); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="p-img-placeholder">🍽</div>
                    <?php endif; ?>
                </div>
                <div class="p-body">
                    <div class="p-number"><?php printf('%02d',$j+1); ?></div>
                    <div class="p-name"><?php echo htmlspecialchars($row['product_name']); ?></div>
                    <div class="p-price">PKR <?php echo number_format($row['product_price']); ?> <span>/ serving</span></div>
                    <form class="p-form" action="" method="POST">
                        <input type="hidden" name="product_id"    value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="product_price" value="<?php echo $row['product_price']; ?>">
                        <div class="qty-wrap">
                            <input class="qty-input" type="number" name="quantity" value="1" placeholder="Qty" min="1" max="99" required>
                        </div>
                        <button class="p-add-btn" type="submit" name="submit">Add</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

<?php else: ?>
    <div class="empty">
        <div class="empty-icon">🍽</div>
        <h3>No dishes available right now</h3>
        <p>Our chef is preparing something special. Please check back soon.</p>
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
for(let i=0;i<22;i++){
    const e=document.createElement('div'); e.className='ember';
    const s=1.5+Math.random()*3, c=ec[Math.floor(Math.random()*ec.length)];
    Object.assign(e.style,{width:s+'px',height:s+'px',background:c,left:(4+Math.random()*92)+'%',bottom:'-8px','--d':((Math.random()-.5)*80)+'px',animationDuration:(9+Math.random()*10)+'s',animationDelay:(Math.random()*18)+'s',boxShadow:`0 0 ${s*2.5}px ${c}`});
    document.body.appendChild(e);
}

/* Scroll-reveal */
const cardObs = new IntersectionObserver(entries=>{
    entries.forEach(e=>{ if(e.isIntersecting) e.target.classList.add('visible'); });
},{threshold:0.08});
document.querySelectorAll('.p-card').forEach(c=>cardObs.observe(c));

/* Toast */
<?php if($success_msg): ?>
window.addEventListener('DOMContentLoaded',()=>{
    const t=document.getElementById('toast');
    if(t){ setTimeout(()=>t.classList.add('show'),120); setTimeout(()=>t.classList.remove('show'),3600); }
});
<?php endif; ?>

/* ── Category scroll-to-section ── */
const catBtns     = document.querySelectorAll('.cat-btn');
const catSections = document.querySelectorAll('.cat-section');
const OFFSET      = 64 + 50 + 16; // top-nav + cat-bar + breathing room

catBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        catBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Scroll active button into view inside the cat-bar
        btn.scrollIntoView({ block:'nearest', inline:'center' });

        const chosen = btn.dataset.cat;
        if(chosen === 'all'){
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }
        const target = document.getElementById('cat-sec-' + chosen);
        if(target){
            const top = target.getBoundingClientRect().top + window.scrollY - OFFSET;
            window.scrollTo({ top, behavior: 'smooth' });
        }
    });
});

/* Highlight active cat-btn while user scrolls */
const sectionList = Array.from(catSections);
window.addEventListener('scroll', () => {
    let currentId = null;
    sectionList.forEach(sec => {
        if(sec.getBoundingClientRect().top <= OFFSET + 60) currentId = sec.dataset.section;
    });
    catBtns.forEach(b => {
        const match = currentId === null
            ? b.dataset.cat === 'all'
            : b.dataset.cat === currentId;
        b.classList.toggle('active', match);
        // keep the active button visible inside the scrollable bar
        if(match) b.scrollIntoView({ block:'nearest', inline:'nearest' });
    });
}, { passive: true });
</script>
</body>
</html>