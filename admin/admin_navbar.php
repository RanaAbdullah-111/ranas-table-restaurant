<?php
// Get current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* ══════════════════════════════════════
       ADMIN NAVBAR & LAYOUT — Rana's Table
    ══════════════════════════════════════ */

        :root {
            --gold:        #C9A84C;
            --gold-lite:   #E8C96A;
            --gold-dim:    rgba(201,168,76,0.15);
            --deep:        #110600;
            --sidebar-bg:  rgba(6,2,0,0.98);
            --topbar-bg:   rgba(10,4,0,0.96);
            --cream:       #F5EDD6;
            --cream-dim:   rgba(245,237,214,0.48);
            --sidebar-w:   250px;
            --topbar-h:    64px;
            --danger:      #e05c3a;
            --success:     #5ab97a;
            --processing:  #6ab4e8;
        }

        /* Reset only for nav elements */
        .adm-nav *, .adm-sidebar *, .adm-overlay {
            margin:0; padding:0; box-sizing:border-box;
        }

        /* ── SIDEBAR ── */
        .adm-sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w);
            z-index: 300;
            background: var(--sidebar-bg);
            border-right: 0.5px solid rgba(201,168,76,0.14);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transform: translateX(0);
            transition: transform 0.4s cubic-bezier(0.16,1,0.3,1);
            font-family: 'Cormorant Garamond', serif;
        }
        .adm-sidebar.collapsed { transform: translateX(-100%); }

        /* Brand */
        .sb-brand {
            padding: 22px 16px 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            border-bottom: 0.5px solid rgba(201,168,76,0.1);
            flex-shrink: 0;
            text-align: center;
        }
        .sb-logo {
            width: 52px; height: 52px;
            border: 0.5px solid rgba(201,168,76,0.4);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            background: rgba(201,168,76,0.04);
        }
        .sb-logo-r {
            font-family: 'Playfair Display', serif;
            font-size: 24px; font-weight: 700;
            background: linear-gradient(135deg,#C9A84C,#F0D08A,#8B6914);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .sb-brand-text { text-align: center; }
        .sb-name { font-family:'Playfair Display',serif; font-size:17px; font-weight:400; color:var(--cream); white-space:nowrap; }
        .sb-name em { color:var(--gold); font-style:italic; }
        .sb-role { font-size:9px; letter-spacing:3px; text-transform:uppercase; color:rgba(201,168,76,0.38); margin-top:2px; }

        /* Admin info block */
        .sb-admin-block {
            padding: 16px 22px 18px;
            border-bottom: 0.5px solid rgba(201,168,76,0.08);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
            text-align: center;
        }
        .sb-admin-avatar {
            width: 48px; height: 48px; border-radius: 50%;
            border: 0.5px solid rgba(201,168,76,0.32);
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; color: rgba(201,168,76,0.6);
            background: rgba(201,168,76,0.05); flex-shrink: 0;
        }
        .sb-admin-name { font-size: 15px; font-weight: 400; color: var(--cream); }
        .sb-admin-badge {
            font-size: 8px; letter-spacing: 2px; text-transform: uppercase;
            background: rgba(201,168,76,0.12); border: 0.5px solid rgba(201,168,76,0.28);
            color: var(--gold); padding: 2px 7px; display: inline-block;
        }

        /* Nav area */
        .sb-nav {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 12px 0;
            scrollbar-width: thin;
            scrollbar-color: rgba(201,168,76,0.15) transparent;
            width: 100%;
        }
        .sb-nav::-webkit-scrollbar { width: 3px; }
        .sb-nav::-webkit-scrollbar-thumb { background: rgba(201,168,76,0.15); border-radius: 2px; }

        /* Section labels */
        .sb-section {
            font-size: 9px; letter-spacing: 4px; text-transform: uppercase;
            color: rgba(201,168,76,0.28); padding: 14px 22px 5px;
            font-family: 'Cormorant Garamond', serif;
            width: 100%; display: block;
        }

        /* Nav links */
        .sb-link {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 13px;
            padding: 11px 22px;
            width: 100%;
            font-size: 14px; font-weight: 300; letter-spacing: 0.3px;
            color: rgba(245,237,214,0.45);
            text-decoration: none;
            border-left: 2px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
            overflow: hidden;
            flex-shrink: 0;
            font-family: 'Cormorant Garamond', serif;
        }
        .sb-link i {
            font-size: 16px; width: 20px; text-align: center;
            color: rgba(201,168,76,0.28); transition: color 0.2s; flex-shrink: 0;
        }
        .sb-link .sb-link-badge {
            margin-left: auto; font-size: 9px; font-weight: 700;
            background: var(--gold); color: var(--deep); padding: 2px 7px;
            letter-spacing: 1px; flex-shrink: 0;
        }
        .sb-link .sb-link-badge.red { background: var(--danger); color: #fff; }

        .sb-link:hover { background: rgba(201,168,76,0.05); color: var(--cream); border-left-color: rgba(201,168,76,0.3); }
        .sb-link:hover i { color: rgba(201,168,76,0.65); }

        .sb-link.active { background: rgba(201,168,76,0.09); color: var(--gold); border-left-color: var(--gold); }
        .sb-link.active i { color: var(--gold); }

        .sb-link.danger { color: rgba(224,92,58,0.65); }
        .sb-link.danger i { color: rgba(224,92,58,0.38); }
        .sb-link.danger:hover { background: rgba(224,92,58,0.07); color: #f0896a; border-left-color: var(--danger); }
        .sb-link.danger:hover i { color: #f0896a; }

        /* Divider */
        .sb-divider { height: 0.5px; width: 100%; background: rgba(201,168,76,0.07); margin: 6px 0; flex-shrink: 0; }

        /* Sidebar footer */
        .sb-footer {
            padding: 16px 22px;
            border-top: 0.5px solid rgba(201,168,76,0.08);
            font-size: 11px; font-weight: 300;
            color: rgba(201,168,76,0.22); letter-spacing: 0.5px;
            flex-shrink: 0;
            font-family: 'Cormorant Garamond', serif;
        }
        .sb-version { color: rgba(201,168,76,0.18); font-size:10px; margin-top:2px; }

        /* ── TOPBAR ── */
        .adm-nav {
            position: sticky; top: 0; z-index: 200;
            height: var(--topbar-h);
            background: var(--topbar-bg);
            backdrop-filter: blur(18px);
            border-bottom: 0.5px solid rgba(201,168,76,0.16);
            padding: 0 28px;
            display: flex; align-items: center; justify-content: space-between;
            font-family: 'Cormorant Garamond', serif;
            margin-left: var(--sidebar-w);
            transition: margin-left 0.4s cubic-bezier(0.16,1,0.3,1);
        }
        .adm-nav.full { margin-left: 0; }

        /* Left */
        .adm-nav-left { display: flex; align-items: center; gap: 18px; }

        .adm-hamburger {
            width: 38px; height: 38px;
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px;
            cursor: pointer; border-radius: 3px; border: none; background: none;
            transition: background 0.2s; flex-shrink: 0;
        }
        .adm-hamburger:hover { background: rgba(201,168,76,0.08); }
        .adm-hamburger span {
            display: block; width: 20px; height: 1.5px;
            background: rgba(201,168,76,0.6);
            transition: all 0.35s cubic-bezier(0.4,0,0.2,1); transform-origin: center;
        }
        .adm-hamburger.active span:nth-child(1) { transform: translateY(6.5px) rotate(45deg); background: var(--gold); }
        .adm-hamburger.active span:nth-child(2) { opacity: 0; transform: scaleX(0); }
        .adm-hamburger.active span:nth-child(3) { transform: translateY(-6.5px) rotate(-45deg); background: var(--gold); }

        /* Breadcrumb */
        .adm-breadcrumb {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; font-weight: 300; color: var(--cream-dim);
        }
        .adm-breadcrumb .bc-home { color: rgba(201,168,76,0.5); text-decoration: none; font-size: 12px; transition: color 0.2s; }
        .adm-breadcrumb .bc-home:hover { color: var(--gold); }
        .adm-breadcrumb .bc-sep { color: rgba(201,168,76,0.2); font-size: 11px; }
        .adm-breadcrumb .bc-current { color: var(--cream); font-size: 14px; }

        /* Right */
        .adm-nav-right { display: flex; align-items: center; gap: 10px; }

        /* Live clock */
        .adm-clock { font-size: 13px; font-weight: 300; color: var(--cream-dim); letter-spacing: 0.5px; white-space: nowrap; }

        /* Quick action btn */
        .adm-nav-btn {
            padding: 7px 16px;
            font-family: 'Cormorant Garamond', serif;
            font-size: 11px; letter-spacing: 2.5px; text-transform: uppercase;
            border: 0.5px solid rgba(201,168,76,0.25);
            color: var(--cream-dim); background: transparent;
            cursor: pointer; text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.3s; white-space: nowrap;
        }
        .adm-nav-btn:hover { border-color: var(--gold); color: var(--gold); }
        .adm-nav-btn.gold { background: var(--gold); color: var(--deep); border-color: var(--gold); font-weight: 700; }
        .adm-nav-btn.gold:hover { background: var(--gold-lite); }

        /* Notification bell */
        .adm-notif-wrap { position: relative; }
        .adm-notif-btn {
            width: 36px; height: 36px;
            border: 0.5px solid rgba(201,168,76,0.22);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; color: rgba(201,168,76,0.5);
            cursor: pointer; background: transparent;
            transition: all 0.25s;
        }
        .adm-notif-btn:hover { border-color: var(--gold); color: var(--gold); background: rgba(201,168,76,0.06); }
        .adm-notif-dot {
            position: absolute; top: 3px; right: 3px;
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--danger); border: 1.5px solid var(--topbar-bg);
        }

        /* Admin profile chip */
        .adm-profile-wrap { position: relative; }
        .adm-profile-chip {
            display: flex; align-items: center; gap: 8px;
            padding: 5px 12px 5px 6px;
            border: 0.5px solid rgba(201,168,76,0.2);
            cursor: pointer; transition: all 0.25s; background: transparent;
            font-family: 'Cormorant Garamond', serif;
        }
        .adm-profile-chip:hover { border-color: rgba(201,168,76,0.45); background: rgba(201,168,76,0.05); }
        .adm-profile-chip.open { border-color: var(--gold); background: rgba(201,168,76,0.08); }
        .adm-chip-avatar {
            width: 28px; height: 28px; border-radius: 50%;
            border: 0.5px solid rgba(201,168,76,0.35);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; color: rgba(201,168,76,0.65);
            background: rgba(201,168,76,0.06); flex-shrink: 0;
        }
        .adm-chip-name { font-size: 13px; font-weight: 400; color: var(--cream); }
        .adm-chip-arrow { font-size: 10px; color: rgba(201,168,76,0.4); transition: transform 0.25s; }
        .adm-profile-chip.open .adm-chip-arrow { transform: rotate(180deg); }

        /* Profile dropdown */
        .adm-profile-dd {
            position: absolute; top: calc(100% + 10px); right: 0;
            width: 210px;
            background: rgba(12,5,0,0.99);
            border: 0.5px solid rgba(201,168,76,0.2);
            backdrop-filter: blur(16px);
            padding: 6px 0;
            opacity: 0; visibility: hidden; pointer-events: none;
            transform: translateY(-8px) scale(0.97);
            transform-origin: top right;
            transition: all 0.28s cubic-bezier(0.16,1,0.3,1);
            z-index: 500;
            font-family: 'Cormorant Garamond', serif;
        }
        .adm-profile-dd::before {
            content: ''; position: absolute; top: -5px; right: 18px;
            width: 10px; height: 10px;
            background: rgba(12,5,0,0.99);
            border-left: 0.5px solid rgba(201,168,76,0.2);
            border-top: 0.5px solid rgba(201,168,76,0.2);
            transform: rotate(45deg);
        }
        .adm-profile-dd.open { opacity: 1; visibility: visible; pointer-events: all; transform: translateY(0) scale(1); }

        .dd-head {
            padding: 12px 16px 10px;
            border-bottom: 0.5px solid rgba(201,168,76,0.08);
            margin-bottom: 4px;
        }
        .dd-head-name  { font-size: 15px; color: var(--cream); font-family: 'Playfair Display', serif; }
        .dd-head-email { font-size: 12px; font-weight: 300; color: var(--cream-dim); margin-top: 1px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .dd-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 16px;
            font-size: 14px; font-weight: 300; color: var(--cream-dim);
            text-decoration: none; transition: all 0.18s;
            cursor: pointer; border: none; background: none; width: 100%; text-align: left;
        }
        .dd-item i { font-size: 14px; color: rgba(201,168,76,0.3); width: 16px; text-align: center; transition: color 0.2s; }
        .dd-item:hover { background: rgba(201,168,76,0.06); color: var(--cream); }
        .dd-item:hover i { color: var(--gold); }
        .dd-item.danger { color: rgba(224,92,58,0.7); }
        .dd-item.danger i { color: rgba(224,92,58,0.38); }
        .dd-item.danger:hover { background: rgba(224,92,58,0.07); color: #f0896a; }
        .dd-item.danger:hover i { color: #f0896a; }

        .dd-divider { height: 0.5px; background: rgba(201,168,76,0.08); margin: 4px 0; }

        /* ── MOBILE OVERLAY ── */
        .adm-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.65); z-index: 290;
            backdrop-filter: blur(3px);
        }
        .adm-overlay.show { display: block; animation: fadeIn 0.3s both; }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }

        /* ── RESPONSIVE ── */
        @media(max-width:900px){
            .adm-sidebar { transform: translateX(-100%); width: 260px; }
            .adm-sidebar.mobile-open { transform: translateX(0); }
            .adm-nav { margin-left: 0 !important; padding: 0 16px; }
            .adm-clock { display: none; }
            .adm-chip-name { display: none; }
        }
        @media(max-width:560px){
            .adm-nav-btn:not(.gold) { display: none; }
        }

        /* ── GLOBAL ADMIN LAYOUT WRAPPER ── */
        .adm-main, .dash-main, .page-wrap {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.4s cubic-bezier(0.16,1,0.3,1);
            position: relative;
            z-index: 1;
        }
        body.sidebar-collapsed .adm-main,
        body.sidebar-collapsed .dash-main,
        body.sidebar-collapsed .page-wrap {
            margin-left: 0 !important;
        }
        @media(max-width:900px){
            .adm-main, .dash-main, .page-wrap {
                margin-left: 0 !important;
            }
        }
    </style>

<!-- ══════════════════════════════
     MOBILE OVERLAY
══════════════════════════════ -->
<div class="adm-overlay" id="admOverlay" onclick="admCloseSidebar()"></div>

<!-- ══════════════════════════════
     SIDEBAR
══════════════════════════════ -->
<aside class="adm-sidebar" id="admSidebar">

    <!-- Brand -->
    <div class="sb-brand">
        <div class="sb-logo"><span class="sb-logo-r">R</span></div>
        <div class="sb-brand-text">
            <div class="sb-name">Rana's <em>Table</em></div>
            <div class="sb-role">Admin Panel</div>
        </div>
    </div>

    <!-- Admin block -->
    <div class="sb-admin-block">
        <div class="sb-admin-avatar"><i class="bi bi-shield-fill"></i></div>
        <div>
            <div class="sb-admin-name">Administrator</div>
            <div class="sb-admin-badge">Super Admin</div>
        </div>
    </div>

    <!-- Nav links -->
    <nav class="sb-nav">

        <div class="sb-section">Overview</div>
        <a href="dashboard.php"    class="sb-link <?php echo $current_page==='dashboard.php'?'active':''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <div class="sb-divider"></div>
        <div class="sb-section">Orders</div>
        <a href="orders_admin.php" class="sb-link <?php echo $current_page==='orders_admin.php'?'active':''; ?>">
            <i class="bi bi-bag-check-fill"></i> All Orders
            <?php
            // Show pending count badge
            $pending_count = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as c FROM orders WHERE order_status='pending' OR order_status='' OR order_status IS NULL"))['c'] ?? 0;
            if($pending_count > 0):
            ?>
            <span class="sb-link-badge red"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>

        <div class="sb-divider"></div>
        <div class="sb-section">Menu</div>
        <a href="product.php"   class="sb-link <?php echo $current_page==='product.php'?'active':''; ?>">
            <i class="bi bi-box-seam-fill"></i> Products
        </a>
        <a href="category.php"  class="sb-link <?php echo $current_page==='category.php'?'active':''; ?>">
            <i class="bi bi-tag-fill"></i> Categories
        </a>

        <div class="sb-divider"></div>
        <div class="sb-section">Users</div>
        <a href="users_admin.php" class="sb-link <?php echo $current_page==='users_admin.php'?'active':''; ?>">
            <i class="bi bi-people-fill"></i> Customers
        </a>

        <div class="sb-divider"></div>
        <div class="sb-section">Site</div>
        <a href="../user/orders.php" class="sb-link">
            <i class="bi bi-grid-fill"></i> View Live Menu
            <span class="sb-link-badge">Live</span>
        </a>
        <a href="../user/profile.php" class="sb-link <?php echo $current_page==='profile.php'?'active':''; ?>">
            <i class="bi bi-person-fill"></i> My Profile
        </a>

        <div class="sb-divider"></div>
        <a href="../logout.php" class="sb-link danger">
            <i class="bi bi-box-arrow-left"></i> Sign Out
        </a>

    </nav>

    <div class="sb-footer">
        © <?php echo date('Y'); ?> Rana's Table
        <div class="sb-version">Admin v1.0</div>
    </div>
</aside>

<!-- ══════════════════════════════
     TOP NAV BAR
══════════════════════════════ -->
<nav class="adm-nav" id="admTopNav">

    <!-- Left -->
    <div class="adm-nav-left">
        <button class="adm-hamburger" id="admHamburger" onclick="admToggleSidebar()">
            <span></span><span></span><span></span>
        </button>

        <!-- Breadcrumb -->
        <div class="adm-breadcrumb">
            <a href="dashboard.php" class="bc-home"><i class="bi bi-house-fill"></i></a>
            <span class="bc-sep">›</span>
            <span class="bc-current"><?php
                $page_titles = [
                    'dashboard.php'    => 'Dashboard',
                    'orders_admin.php' => 'Manage Orders',
                    'product.php'      => 'Products',
                    'category.php'     => 'Categories',
                    'users_admin.php'  => 'Customers',
                    'profile.php'      => 'My Profile',
                ];
                echo $page_titles[$current_page] ?? ucfirst(str_replace(['.php','_'], ['', ' '], $current_page));
            ?></span>
        </div>
    </div>

    <!-- Right -->
    <div class="adm-nav-right">

        <!-- Live clock -->
        <div class="adm-clock" id="admClock"></div>

        <!-- View live site -->
        <a href="../user/orders.php" class="adm-nav-btn" target="_blank">
            <i class="bi bi-eye-fill"></i> Live Site
        </a>

        <!-- Notification bell -->
        <div class="adm-notif-wrap">
            <button class="adm-notif-btn" title="Pending orders">
                <i class="bi bi-bell-fill"></i>
            </button>
            <?php if($pending_count > 0): ?>
            <div class="adm-notif-dot"></div>
            <?php endif; ?>
        </div>

        <!-- Admin profile chip -->
        <div class="adm-profile-wrap">
            <div class="adm-profile-chip" id="admProfileChip" onclick="admToggleProfile()">
                <div class="adm-chip-avatar"><i class="bi bi-shield-fill"></i></div>
                <span class="adm-chip-name"><?php echo htmlspecialchars(!empty($_SESSION['user_name']) ? explode(' ', trim($_SESSION['user_name']))[0] : 'Admin'); ?></span>
                <span class="adm-chip-arrow"><i class="bi bi-chevron-down"></i></span>
            </div>
            <div class="adm-profile-dd" id="admProfileDd">
                <div class="dd-head">
                    <div class="dd-head-name"><?php echo htmlspecialchars(!empty($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Administrator'); ?></div>
                    <div class="dd-head-email"><?php echo htmlspecialchars($_SESSION['user'] ?? 'admin@ranastable.pk'); ?></div>
                </div>
                <a href="dashboard.php"   class="dd-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="../user/profile.php"  class="dd-item"><i class="bi bi-person-fill"></i> My Profile</a>
                <div class="dd-divider"></div>
                <a href="../user/orders.php"   class="dd-item"><i class="bi bi-grid-fill"></i> View Menu</a>
                <div class="dd-divider"></div>
                <a href="../logout.php"   class="dd-item danger"><i class="bi bi-box-arrow-left"></i> Sign Out</a>
            </div>
        </div>

    </div>
</nav>

<script>
/* ── Sidebar ── */
function admToggleSidebar(){
    const sb  = document.getElementById('admSidebar');
    const nav = document.getElementById('admTopNav');
    const hbg = document.getElementById('admHamburger');
    const ovl = document.getElementById('admOverlay');
    const mobile = window.innerWidth <= 900;

    if(mobile){
        const open = sb.classList.toggle('mobile-open');
        ovl.classList.toggle('show', open);
        hbg.classList.toggle('active', open);
    } else {
        const collapsed = sb.classList.toggle('collapsed');
        nav.classList.toggle('full', collapsed);
        hbg.classList.toggle('active', collapsed);
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        localStorage.setItem('admSidebarCollapsed', collapsed ? '1' : '0');
    }
}
function admCloseSidebar(){
    document.getElementById('admSidebar').classList.remove('mobile-open');
    document.getElementById('admOverlay').classList.remove('show');
    document.getElementById('admHamburger').classList.remove('active');
}

/* Restore sidebar state on desktop */
window.addEventListener('DOMContentLoaded',()=>{
    if(window.innerWidth > 900 && localStorage.getItem('admSidebarCollapsed') === '1'){
        document.getElementById('admSidebar').classList.add('collapsed');
        document.getElementById('admTopNav').classList.add('full');
        document.body.classList.add('sidebar-collapsed');
        const hbg = document.getElementById('admHamburger');
        if(hbg) hbg.classList.add('active');
    }
});

/* ── Profile dropdown ── */
function admToggleProfile(){
    const dd  = document.getElementById('admProfileDd');
    const chip= document.getElementById('admProfileChip');
    const open= dd.classList.toggle('open');
    chip.classList.toggle('open', open);
}
document.addEventListener('click', e=>{
    const wrap = document.querySelector('.adm-profile-wrap');
    if(wrap && !wrap.contains(e.target)){
        document.getElementById('admProfileDd').classList.remove('open');
        document.getElementById('admProfileChip').classList.remove('open');
    }
});

/* ── Live Clock ── */
function admUpdateClock(){
    const now = new Date();
    document.getElementById('admClock').textContent =
        now.toLocaleTimeString('en-PK',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
admUpdateClock(); setInterval(admUpdateClock, 1000);

/* ── Escape key ── */
document.addEventListener('keydown', e=>{
    if(e.key==='Escape'){
        admCloseSidebar();
        document.getElementById('admProfileDd').classList.remove('open');
        document.getElementById('admProfileChip').classList.remove('open');
    }
});
</script>