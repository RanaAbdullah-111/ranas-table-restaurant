<?php
// navbar.php — lightweight include, no extra DB queries
// Requires: session_start() + config.php already called in parent page

$_nav_user    = $_SESSION['user']    ?? null;
$_nav_uid     = $_SESSION['user_id'] ?? null;
$_nav_role    = $_SESSION['role']    ?? null;
$_nav_page    = basename($_SERVER['PHP_SELF']);
$_nav_logged  = !empty($_nav_user);

// Dynamic relative paths depending on whether navbar is included from root or /user/
$_script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');
$_in_user_dir = (strpos($_script_path, '/user/') !== false);
$_base_root   = $_in_user_dir ? '../' : '';
$_base_user   = $_in_user_dir ? '' : 'user/';

// Only fetch name if we have it in session, otherwise use email prefix
$_nav_display = '';
if(!empty($_SESSION['user_name'])){
    $_nav_display = $_SESSION['user_name'];
} elseif(!empty($_nav_user)){
    $_nav_display = explode('@', $_nav_user)[0];
}

// Cart count: query DB dynamically if user is logged in
$_nav_cart = 0;
if(!empty($_SESSION['user_id']) && isset($connection) && $connection){
    $c_uid = (int)$_SESSION['user_id'];
    $cq = mysqli_query($connection, "SELECT SUM(quantity) AS total_qty FROM `card` WHERE user_id = $c_uid");
    if($cq && $cr = mysqli_fetch_assoc($cq)){
        $_nav_cart = (int)($cr['total_qty'] ?? 0);
    }
} elseif(isset($_SESSION['cart_count'])) {
    $_nav_cart = (int)$_SESSION['cart_count'];
}
?>
<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<style>
:root{--gold:#C9A84C;--gold2:#E8C96A;--deep:#110600;--cream:#F5EDD6;--cdim:rgba(245,237,214,.48);--nav-h:64px;--dw:280px}
*{box-sizing:border-box}

.rn{position:fixed;top:0;left:0;right:0;z-index:999;height:var(--nav-h);display:flex;align-items:center;justify-content:space-between;padding:0 28px;background:rgba(8,2,0,.95);border-bottom:.5px solid rgba(201,168,76,.15);backdrop-filter:blur(14px);font-family:'Cormorant Garamond',serif}

/* brand */
.rn-brand{font-family:'Playfair Display',serif;font-size:19px;font-weight:400;color:var(--cream);text-decoration:none;letter-spacing:1px;flex-shrink:0}
.rn-brand em{color:var(--gold);font-style:italic}

/* center links */
.rn-links{display:flex;align-items:center;gap:24px}
.rn-links a{font-size:11px;letter-spacing:2.5px;text-transform:uppercase;color:var(--cdim);text-decoration:none;transition:color .2s;white-space:nowrap}
.rn-links a:hover,.rn-links a.on{color:var(--gold)}

/* right */
.rn-right{display:flex;align-items:center;gap:8px;flex-shrink:0}

/* cart */
.rn-cart{position:relative;width:36px;height:36px;border:.5px solid rgba(201,168,76,.25);display:flex;align-items:center;justify-content:center;font-size:17px;color:rgba(201,168,76,.6);text-decoration:none;transition:all .2s}
.rn-cart:hover{border-color:var(--gold);color:var(--gold);background:rgba(201,168,76,.07)}
.rn-badge{position:absolute;top:-6px;right:-6px;width:18px;height:18px;border-radius:50%;background:var(--gold);color:var(--deep);font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;border:1.5px solid rgba(8,2,0,.95)}

/* buttons */
.rn-btn{padding:7px 16px;font-family:'Cormorant Garamond',serif;font-size:11px;letter-spacing:3px;text-transform:uppercase;border:.5px solid rgba(201,168,76,.3);color:var(--cdim);background:transparent;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:all .2s;white-space:nowrap}
.rn-btn:hover{border-color:var(--gold);color:var(--gold)}
.rn-btn.g{background:var(--gold);color:var(--deep);border-color:var(--gold);font-weight:700}
.rn-btn.g:hover{background:var(--gold2)}

/* hamburger */
.rn-ham{width:34px;height:34px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;cursor:pointer;border:none;background:none;flex-shrink:0}
.rn-ham span{display:block;width:20px;height:1.5px;background:rgba(201,168,76,.65);transition:all .3s;transform-origin:center}
.rn-ham.x span:nth-child(1){transform:translateY(6.5px) rotate(45deg);background:var(--gold)}
.rn-ham.x span:nth-child(2){opacity:0;transform:scaleX(0)}
.rn-ham.x span:nth-child(3){transform:translateY(-6.5px) rotate(-45deg);background:var(--gold)}

/* overlay */
.rn-ov{display:none;position:fixed;inset:0;z-index:997;background:rgba(4,1,0,.75)}
.rn-ov.on{display:block}

/* drawer */
.rn-dr{position:fixed;top:0;left:0;bottom:0;width:var(--dw);z-index:998;background:rgba(10,3,0,.99);border-right:.5px solid rgba(201,168,76,.14);display:flex;flex-direction:column;transform:translateX(-100%);transition:transform .35s cubic-bezier(.16,1,.3,1);overflow-y:auto;overflow-x:hidden}
.rn-dr.on{transform:translateX(0)}

.rn-dr-top{height:var(--nav-h);flex-shrink:0;display:flex;align-items:center;justify-content:space-between;padding:0 22px;border-bottom:.5px solid rgba(201,168,76,.1)}
.rn-dr-brand{font-family:'Playfair Display',serif;font-size:17px;color:var(--cream)}
.rn-dr-brand em{color:var(--gold);font-style:italic}
.rn-dr-x{background:none;border:none;color:rgba(201,168,76,.45);font-size:20px;cursor:pointer;width:30px;height:30px;display:flex;align-items:center;justify-content:center;transition:color .2s}
.rn-dr-x:hover{color:var(--gold)}

/* drawer user */
.rn-dr-user{padding:18px 22px;flex-shrink:0;border-bottom:.5px solid rgba(201,168,76,.08);display:flex;align-items:center;gap:12px}
.rn-dr-av{width:44px;height:44px;border-radius:50%;border:.5px solid rgba(201,168,76,.3);display:flex;align-items:center;justify-content:center;font-size:20px;color:rgba(201,168,76,.6);background:rgba(201,168,76,.05);flex-shrink:0}
.rn-dr-un{font-family:'Playfair Display',serif;font-size:15px;color:var(--cream)}
.rn-dr-ue{font-size:12px;font-weight:300;color:var(--cdim);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:170px}

/* drawer section + links */
.rn-dr-sec{font-size:9px;letter-spacing:4px;text-transform:uppercase;color:rgba(201,168,76,.28);padding:14px 22px 5px;display:block;width:100%;flex-shrink:0;font-family:'Cormorant Garamond',serif}
.rn-dr-lk{display:flex;align-items:center;gap:13px;padding:11px 22px;font-family:'Cormorant Garamond',serif;font-size:15px;font-weight:400;color:rgba(245,237,214,.42);text-decoration:none;border-left:2px solid transparent;transition:all .2s;width:100%;flex-shrink:0;white-space:nowrap;overflow:hidden}
.rn-dr-lk i{font-size:15px;color:rgba(201,168,76,.28);width:18px;text-align:center;flex-shrink:0;transition:color .2s}
.rn-dr-lk .rn-bd{margin-left:auto;font-size:9px;font-weight:700;background:var(--gold);color:var(--deep);padding:2px 7px;letter-spacing:1px}
.rn-dr-lk:hover{background:rgba(201,168,76,.06);color:var(--cream);border-left-color:rgba(201,168,76,.3)}
.rn-dr-lk:hover i{color:var(--gold)}
.rn-dr-lk.on{background:rgba(201,168,76,.08);color:var(--gold);border-left-color:var(--gold)}
.rn-dr-lk.on i{color:var(--gold)}
.rn-dr-lk.red{color:rgba(224,92,58,.65)}
.rn-dr-lk.red i{color:rgba(224,92,58,.35)}
.rn-dr-lk.red:hover{background:rgba(224,92,58,.07);color:#f0896a;border-left-color:#e05c3a}
.rn-dr-lk.red:hover i{color:#f0896a}

.rn-div{height:.5px;width:100%;background:rgba(201,168,76,.07);margin:6px 0;flex-shrink:0}

.rn-dr-ft{margin-top:auto;padding:16px 22px;flex-shrink:0;border-top:.5px solid rgba(201,168,76,.07);font-size:11px;font-weight:300;color:rgba(201,168,76,.22);font-family:'Cormorant Garamond',serif}

/* profile dropdown */
.rn-prof{position:relative}
.rn-pc{display:flex;align-items:center;gap:7px;padding:5px 11px 5px 5px;border:.5px solid rgba(201,168,76,.2);cursor:pointer;background:transparent;transition:all .2s;font-family:'Cormorant Garamond',serif}
.rn-pc:hover{border-color:rgba(201,168,76,.45);background:rgba(201,168,76,.05)}
.rn-pc.on{border-color:var(--gold);background:rgba(201,168,76,.08)}
.rn-pav{width:26px;height:26px;border-radius:50%;border:.5px solid rgba(201,168,76,.32);display:flex;align-items:center;justify-content:center;font-size:13px;color:rgba(201,168,76,.62);background:rgba(201,168,76,.05);flex-shrink:0}
.rn-pn{font-size:13px;color:var(--cream)}
.rn-pa{font-size:10px;color:rgba(201,168,76,.38);transition:transform .2s}
.rn-pc.on .rn-pa{transform:rotate(180deg)}

.rn-dd{position:absolute;top:calc(100% + 8px);right:0;width:200px;background:rgba(12,5,0,.99);border:.5px solid rgba(201,168,76,.18);padding:5px 0;opacity:0;visibility:hidden;pointer-events:none;transform:translateY(-6px) scale(.97);transform-origin:top right;transition:all .25s cubic-bezier(.16,1,.3,1);z-index:600}
.rn-dd.on{opacity:1;visibility:visible;pointer-events:all;transform:translateY(0) scale(1)}
.rn-dd::before{content:'';position:absolute;top:-5px;right:15px;width:9px;height:9px;background:rgba(12,5,0,.99);border-left:.5px solid rgba(201,168,76,.18);border-top:.5px solid rgba(201,168,76,.18);transform:rotate(45deg)}
.rn-dh{padding:11px 15px 9px;border-bottom:.5px solid rgba(201,168,76,.07);margin-bottom:3px}
.rn-dhn{font-family:'Playfair Display',serif;font-size:14px;color:var(--cream)}
.rn-dhe{font-size:11px;font-weight:300;color:var(--cdim);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rn-di{display:flex;align-items:center;gap:9px;padding:8px 15px;font-family:'Cormorant Garamond',serif;font-size:13px;font-weight:300;color:var(--cdim);text-decoration:none;transition:all .18s}
.rn-di i{font-size:13px;color:rgba(201,168,76,.28);width:15px;text-align:center;transition:color .2s}
.rn-di:hover{background:rgba(201,168,76,.06);color:var(--cream)}
.rn-di:hover i{color:var(--gold)}
.rn-di.red{color:rgba(224,92,58,.68)}
.rn-di.red i{color:rgba(224,92,58,.35)}
.rn-di.red:hover{background:rgba(224,92,58,.07);color:#f0896a}
.rn-di.red:hover i{color:#f0896a}
.rn-ddiv{height:.5px;background:rgba(201,168,76,.07);margin:3px 0}

@media(max-width:768px){
    .rn{padding:0 14px}
    .rn-links,.rn-right .rn-btn:not(.g),.rn-prof,.rn-cart,.rn-nav-user{display:none!important}
}
@media(max-width:480px){
    .rn-right .rn-btn.g{display:none!important}
}
</style>

<!-- overlay -->
<div class="rn-ov" id="rnOv"></div>

<!-- drawer -->
<div class="rn-dr" id="rnDr">
    <div class="rn-dr-top">
        <div class="rn-dr-brand">Rana's <em>Table</em></div>
        <button class="rn-dr-x" id="rnDrX">✕</button>
    </div>

    <?php if($_nav_logged): ?>
    <div class="rn-dr-user">
        <div class="rn-dr-av"><i class="bi bi-person-fill"></i></div>
        <div>
            <div class="rn-dr-un"><?php echo htmlspecialchars($_nav_display); ?></div>
            <div class="rn-dr-ue"><?php echo htmlspecialchars($_nav_user); ?></div>
        </div>
    </div>
    <?php endif; ?>

    <span class="rn-dr-sec">Navigation</span>
    <a href="<?php echo $_base_root; ?>index.php" class="rn-dr-lk <?php echo $_nav_page==='index.php' ?'on':''; ?>"><i class="bi bi-house-fill"></i> Home</a>
    <a href="<?php echo $_base_user; ?>orders.php" class="rn-dr-lk <?php echo $_nav_page==='orders.php' ?'on':''; ?>"><i class="bi bi-grid-fill"></i> Menu</a>
    <a href="<?php echo $_base_user; ?>card.php" class="rn-dr-lk <?php echo $_nav_page==='card.php' ?'on':''; ?>">
        <i class="bi bi-cart3"></i> My Cart
        <?php if($_nav_cart > 0): ?><span class="rn-bd"><?php echo $_nav_cart; ?></span><?php endif; ?>
    </a>
    <a href="<?php echo $_base_user; ?>my_orders.php" class="rn-dr-lk <?php echo $_nav_page==='my_orders.php' ?'on':''; ?>"><i class="bi bi-bag-check-fill"></i> My Orders</a>

    <div class="rn-div"></div>
    <span class="rn-dr-sec">Account</span>
    <a href="<?php echo $_base_user; ?>profile.php" class="rn-dr-lk <?php echo $_nav_page==='profile.php' ?'on':''; ?>"><i class="bi bi-person-fill"></i> Edit Profile</a>
    <?php if($_nav_role === 'admin'): ?>
    <a href="<?php echo $_base_root; ?>admin/dashboard.php" class="rn-dr-lk"><i class="bi bi-speedometer2"></i> Admin Panel</a>
    <?php endif; ?>

    <div class="rn-div"></div>

    <?php if($_nav_logged): ?>
    <a href="<?php echo $_base_root; ?>logout.php" class="rn-dr-lk red"><i class="bi bi-box-arrow-left"></i> Sign Out</a>
    <?php else: ?>
    <a href="<?php echo $_base_root; ?>login.php" class="rn-dr-lk"><i class="bi bi-box-arrow-in-right"></i> Sign In</a>
    <a href="<?php echo $_base_root; ?>user.php" class="rn-dr-lk"><i class="bi bi-person-plus-fill"></i> Register</a>
    <?php endif; ?>

    <div class="rn-dr-ft">© <?php echo date('Y'); ?> Rana's Table</div>
</div>

<!-- TOP NAV -->
<nav class="rn" id="rnNav">

    <div style="display:flex;align-items:center;gap:14px">
        <button class="rn-ham" id="rnHam"><span></span><span></span><span></span></button>
        <a href="<?php echo $_base_root; ?>index.php" class="rn-brand">Rana's <em>Table</em></a>
    </div>

    <!-- center links -->
    <div class="rn-links">
        <a href="<?php echo $_base_root; ?>index.php" class="<?php echo $_nav_page==='index.php' ?'on':''; ?>">Home</a>
        <a href="<?php echo $_base_user; ?>orders.php" class="<?php echo $_nav_page==='orders.php' ?'on':''; ?>">Menu</a>
        <a href="<?php echo $_base_user; ?>my_orders.php" class="<?php echo $_nav_page==='my_orders.php' ?'on':''; ?>">My Orders</a>
        <a href="<?php echo $_base_user; ?>card.php" class="<?php echo $_nav_page==='card.php' ?'on':''; ?>">Cart</a>
        <a href="<?php echo $_base_user; ?>profile.php" class="<?php echo $_nav_page==='profile.php' ?'on':''; ?>">Profile</a>
        <?php if($_nav_role === 'admin'): ?>
        <a href="<?php echo $_base_root; ?>admin/dashboard.php" style="color:var(--gold)">Admin</a>
        <?php endif; ?>
    </div>

    <!-- right -->
    <div class="rn-right">
        <?php if($_nav_logged): ?>

        <a href="<?php echo $_base_user; ?>card.php" class="rn-cart" title="Cart">
            <i class="bi bi-cart3"></i>
            <?php if($_nav_cart > 0): ?><span class="rn-badge"><?php echo $_nav_cart > 99 ? '99+' : $_nav_cart; ?></span><?php endif; ?>
        </a>

        <div class="rn-prof">
            <div class="rn-pc" id="rnPc">
                <div class="rn-pav"><i class="bi bi-person-fill"></i></div>
                <span class="rn-pn"><?php echo htmlspecialchars(mb_substr($_nav_display,0,12)); ?></span>
                <span class="rn-pa">▾</span>
            </div>
            <div class="rn-dd" id="rnDd">
                <div class="rn-dh">
                    <div class="rn-dhn"><?php echo htmlspecialchars($_nav_display); ?></div>
                    <div class="rn-dhe"><?php echo htmlspecialchars($_nav_user); ?></div>
                </div>
                <a href="<?php echo $_base_root; ?>index.php" class="rn-di"><i class="bi bi-house-fill"></i> Home</a>
                <a href="<?php echo $_base_user; ?>orders.php" class="rn-di"><i class="bi bi-grid-fill"></i> Menu</a>
                <a href="<?php echo $_base_user; ?>my_orders.php" class="rn-di"><i class="bi bi-bag-check-fill"></i> My Orders</a>
                <a href="<?php echo $_base_user; ?>card.php" class="rn-di"><i class="bi bi-cart3"></i> My Cart</a>
                <a href="<?php echo $_base_user; ?>profile.php" class="rn-di"><i class="bi bi-person-fill"></i> Profile</a>
                <?php if($_nav_role === 'admin'): ?>
                <a href="<?php echo $_base_root; ?>admin/dashboard.php" class="rn-di" style="color:var(--gold)"><i class="bi bi-speedometer2"></i> Admin Panel</a>
                <?php endif; ?>
                <div class="rn-ddiv"></div>
                <a href="<?php echo $_base_root; ?>logout.php" class="rn-di red"><i class="bi bi-box-arrow-left"></i> Sign Out</a>
            </div>
        </div>

        <?php else: ?>
        <a href="<?php echo $_base_root; ?>login.php" class="rn-btn">Sign In</a>
        <a href="<?php echo $_base_root; ?>user.php" class="rn-btn g">Register</a>
        <?php endif; ?>
    </div>
</nav>

<script>
!function(){
    var ham=document.getElementById('rnHam'),
        dr =document.getElementById('rnDr'),
        ov =document.getElementById('rnOv'),
        drx=document.getElementById('rnDrX'),
        pc =document.getElementById('rnPc'),
        dd =document.getElementById('rnDd');

    function open(){dr.classList.add('on');ov.classList.add('on');ham.classList.add('x');document.body.style.overflow='hidden'}
    function close(){dr.classList.remove('on');ov.classList.remove('on');ham.classList.remove('x');document.body.style.overflow=''}

    ham.onclick=open;
    if(drx) drx.onclick=close;
    ov.onclick=close;

    if(pc&&dd){
        pc.onclick=function(e){e.stopPropagation();var o=dd.classList.toggle('on');pc.classList.toggle('on',o)};
        document.addEventListener('click',function(){dd.classList.remove('on');pc.classList.remove('on')});
    }

    document.addEventListener('keydown',function(e){if(e.key==='Escape')close()});
}();
</script>