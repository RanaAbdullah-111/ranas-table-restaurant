<?php
include_once("../config.php");
require_once("../auth.php");
require_admin();

$success_msg = "";
$error_msg   = "";

/*
|--------------------------------------------------------------------------
| UPDATE ORDER STATUS
|--------------------------------------------------------------------------
*/
if(isset($_POST['update_status'])){
    $order_id  = (int)$_POST['order_id'];
    $new_status = $_POST['new_status'];

    $allowed = ['pending','processing','delivered','cancelled'];
    if(in_array($new_status, $allowed)){
        $upd = $connection->prepare("UPDATE orders SET order_status=? WHERE id=?");
        $upd->bind_param("si", $new_status, $order_id);
        if($upd->execute()){
            $success_msg = "Order #" . str_pad($order_id,4,'0',STR_PAD_LEFT) . " marked as " . ucfirst($new_status) . ".";
        } else {
            $error_msg = "Could not update order status.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/
$filter = $_GET['filter'] ?? 'all';
$allowed_filters = ['all','pending','processing','delivered','cancelled'];
if(!in_array($filter, $allowed_filters)) $filter = 'all';

$where = $filter !== 'all' ? "WHERE orders.order_status = '$filter'" : "";

/*
|--------------------------------------------------------------------------
| FETCH ORDERS
|--------------------------------------------------------------------------
*/
$orders_sql = "
    SELECT orders.*, COUNT(order_items.id) AS item_count
    FROM orders
    LEFT JOIN order_items ON orders.id = order_items.order_id
    $where
    GROUP BY orders.id
    ORDER BY orders.id DESC
";
$orders_result = mysqli_query($connection, $orders_sql);
$orders = [];
if($orders_result) while($r = mysqli_fetch_assoc($orders_result)) $orders[] = $r;

/*
|--------------------------------------------------------------------------
| FETCH ITEMS PER ORDER
|--------------------------------------------------------------------------
*/
$order_items_map = [];
foreach($orders as $order){
    $oid = $order['id'];
    $i_stmt = $connection->prepare("
        SELECT order_items.*, product.product_name, product.product_image, product.product_price
        FROM order_items
        JOIN product ON order_items.product_id = product.id
        WHERE order_items.order_id = ?
    ");
    $i_stmt->bind_param("i", $oid);
    $i_stmt->execute();
    $i_res = $i_stmt->get_result();
    while($ir = mysqli_fetch_assoc($i_res)) $order_items_map[$oid][] = $ir;
}

/*
|--------------------------------------------------------------------------
| COUNT PER STATUS (for filter tabs)
|--------------------------------------------------------------------------
*/
$counts = ['all'=>0,'pending'=>0,'processing'=>0,'delivered'=>0,'cancelled'=>0];
$cnt_res = mysqli_query($connection,"SELECT order_status, COUNT(*) as c FROM orders GROUP BY order_status");
if($cnt_res) while($cr=mysqli_fetch_assoc($cnt_res)){
    $s = strtolower($cr['order_status'] ?? 'pending');
    $counts[$s] = (int)$cr['c'];
    $counts['all'] += (int)$cr['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders — Rana's Table</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Cormorant+Garamond:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        :root {
            --gold:       #C9A84C;
            --gold-lite:  #E8C96A;
            --deep:       #110600;
            --card-bg:    rgba(28,11,0,0.86);
            --cream:      #F5EDD6;
            --cream-dim:  rgba(245,237,214,0.50);
            --pending:    #e0a93a;
            --processing: #6ab4e8;
            --delivered:  #5ab97a;
            --cancelled:  #e05c3a;
        }

        html { scroll-behavior:smooth; }
        body { font-family:'Cormorant Garamond',serif; background:var(--deep); color:var(--cream); min-height:100vh; overflow-x:hidden; }

        .bg-fixed { position:fixed; inset:0; z-index:0; pointer-events:none; background:radial-gradient(ellipse 80% 50% at 50% 0%,#3a1600 0%,#110600 65%); }
        .ember { position:fixed; border-radius:50%; pointer-events:none; opacity:0; animation:floatUp linear infinite; z-index:0; }
        @keyframes floatUp { 0%{transform:translateY(0) translateX(0);opacity:0} 8%{opacity:.6} 90%{opacity:.08} 100%{transform:translateY(-100vh) translateX(var(--d));opacity:0} }

        /* ── NAV ── */

        /* ── TOAST ── */
        .toast { position:fixed; top:80px; left:50%; transform:translateX(-50%) translateY(-20px); padding:13px 28px; font-size:14px; z-index:999; pointer-events:none; opacity:0; transition:all 0.4s cubic-bezier(0.16,1,0.3,1); white-space:nowrap; display:flex; align-items:center; gap:10px; }
        .toast.show { opacity:1; transform:translateX(-50%) translateY(0); }
        .toast.success { background:rgba(25,55,35,0.97); border:0.5px solid rgba(90,185,122,0.4); color:#8de8a8; }
        .toast.error   { background:rgba(55,15,10,0.97); border:0.5px solid rgba(224,92,58,0.4);  color:#f0896a; }

        /* ── HERO ── */
        .hero { position:relative; z-index:1; text-align:center; padding:48px 40px 28px; }
        .hero-tag { font-size:10px; letter-spacing:5px; text-transform:uppercase; color:rgba(201,168,76,0.55); margin-bottom:12px; animation:fadeUp 0.6s 0.1s both; }
        .hero-title { font-family:'Playfair Display',serif; font-size:clamp(34px,5vw,58px); font-weight:700; line-height:1; background:linear-gradient(135deg,#C9A84C 0%,#F0D08A 45%,#8B6914 100%); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; animation:fadeUp 0.6s 0.2s both; }
        .hero-sub { font-size:15px; font-weight:300; color:var(--cream-dim); margin-top:8px; animation:fadeUp 0.6s 0.3s both; }
        .hero-divider { display:flex; align-items:center; gap:12px; width:200px; margin:16px auto 0; animation:fadeUp 0.6s 0.4s both; }
        .hdl { flex:1; height:.5px; background:linear-gradient(to right,transparent,rgba(201,168,76,.38),transparent); }
        .hdg { width:5px; height:5px; background:var(--gold); transform:rotate(45deg); flex-shrink:0; opacity:.65; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }

        /* ── PAGE ── */
        .page { position:relative; z-index:1; max-width:1100px; margin:0 auto; padding:0 32px 80px; }

        /* ── FILTER TABS ── */
        .filter-bar {
            display:flex; align-items:center; gap:0;
            border:0.5px solid rgba(201,168,76,0.14);
            background:var(--card-bg);
            margin-bottom:20px; overflow-x:auto;
            scrollbar-width:none; -webkit-overflow-scrolling:touch;
            animation:fadeUp 0.6s 0.3s both;
        }
        .filter-bar::-webkit-scrollbar { display:none; }

        .f-tab {
            flex:0 0 auto; white-space:nowrap;
            padding:14px 24px; font-family:'Cormorant Garamond',serif;
            font-size:13px; font-weight:500; letter-spacing:2px; text-transform:uppercase;
            color:var(--cream-dim); text-decoration:none;
            border-right:0.5px solid rgba(201,168,76,0.1);
            transition:all 0.25s; position:relative; display:flex; align-items:center; gap:8px;
        }
        .f-tab:last-child { border-right:none; }
        .f-tab:hover { background:rgba(201,168,76,0.05); color:var(--cream); }
        .f-tab.active { background:rgba(201,168,76,0.09); color:var(--gold); }
        .f-tab.active::after { content:''; position:absolute; bottom:0; left:0; right:0; height:2px; background:var(--gold); }

        .f-count { font-size:11px; background:rgba(201,168,76,0.15); border:0.5px solid rgba(201,168,76,0.25); color:var(--gold); padding:2px 8px; border-radius:0; font-weight:700; }
        .f-count.red { background:rgba(224,92,58,0.12); border-color:rgba(224,92,58,0.3); color:var(--cancelled); }

        /* ── STATS STRIP ── */
        .stats-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:2px; margin-bottom:20px; animation:fadeUp 0.6s 0.2s both; }
        .stat-box { background:var(--card-bg); border:0.5px solid rgba(201,168,76,0.1); padding:18px 20px; }
        .sb-num { font-family:'Playfair Display',serif; font-size:28px; display:block; line-height:1; margin-bottom:4px; }
        .sb-lbl { font-size:10px; letter-spacing:3px; text-transform:uppercase; color:var(--cream-dim); }

        /* ── ORDER CARDS ── */
        .orders-list { display:flex; flex-direction:column; gap:2px; }

        .order-card {
            background:var(--card-bg); border:0.5px solid rgba(201,168,76,0.1);
            opacity:0; transform:translateY(16px);
            transition:opacity 0.5s ease, transform 0.5s ease, border-color 0.3s;
        }
        .order-card.visible { opacity:1; transform:translateY(0); }
        .order-card:hover { border-color:rgba(201,168,76,0.26); }

        /* CARD HEAD */
        .oc-head { padding:18px 24px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; border-bottom:0.5px solid transparent; cursor:pointer; transition:background 0.2s; user-select:none; }
        .oc-head:hover { background:rgba(201,168,76,0.03); }
        .order-card.open .oc-head { border-bottom-color:rgba(201,168,76,0.08); }

        .oc-left { display:flex; align-items:center; gap:20px; flex-wrap:wrap; flex:1; }
        .oc-id { font-family:'Playfair Display',serif; font-size:20px; color:var(--cream); }
        .oc-id span { font-size:12px; color:rgba(201,168,76,0.4); font-family:'Cormorant Garamond',serif; }

        .oc-meta { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
        .oc-meta-item { font-size:13px; font-weight:300; color:var(--cream-dim); display:flex; align-items:center; gap:5px; }
        .dot { width:3px; height:3px; background:rgba(201,168,76,0.25); border-radius:50%; }

        .oc-right { display:flex; align-items:center; gap:14px; flex-wrap:wrap; flex-shrink:0; }
        .oc-total { font-family:'Playfair Display',serif; font-size:20px; color:var(--gold); }
        .oc-toggle { font-size:14px; color:rgba(201,168,76,0.35); transition:transform 0.35s; flex-shrink:0; }
        .order-card.open .oc-toggle { transform:rotate(180deg); }

        /* STATUS BADGE */
        .s-badge { font-size:10px; font-weight:700; letter-spacing:2px; text-transform:uppercase; padding:5px 12px; border:0.5px solid; flex-shrink:0; }
        .s-pending    { color:var(--pending);    border-color:rgba(224,169,58,0.35);  background:rgba(224,169,58,0.08); }
        .s-processing { color:var(--processing); border-color:rgba(106,180,232,0.35); background:rgba(106,180,232,0.08); }
        .s-delivered  { color:var(--delivered);  border-color:rgba(90,185,122,0.35);  background:rgba(90,185,122,0.08); }
        .s-cancelled  { color:var(--cancelled);  border-color:rgba(224,92,58,0.35);   background:rgba(224,92,58,0.08); }

        /* CARD BODY */
        .oc-body { max-height:0; overflow:hidden; transition:max-height 0.5s cubic-bezier(0.4,0,0.2,1); }
        .order-card.open .oc-body { max-height:1400px; }
        .oc-body-inner { padding:24px; display:flex; flex-direction:column; gap:20px; }

        /* items table */
        .items-table { width:100%; border-collapse:collapse; }
        .items-table th { font-size:10px; letter-spacing:3px; text-transform:uppercase; color:rgba(201,168,76,0.42); font-weight:500; padding:8px 12px; border-bottom:0.5px solid rgba(201,168,76,0.1); text-align:left; }
        .items-table th:last-child { text-align:right; }
        .items-table td { padding:12px 12px; border-bottom:0.5px solid rgba(201,168,76,0.05); font-size:14px; font-weight:300; color:var(--cream-dim); vertical-align:middle; }
        .items-table td:last-child { text-align:right; color:var(--gold); font-size:15px; }
        .items-table tr:last-child td { border-bottom:none; }

        .item-row { display:flex; align-items:center; gap:12px; }
        .item-thumb { width:46px; height:46px; flex-shrink:0; overflow:hidden; background:rgba(45,21,0,0.5); border:0.5px solid rgba(201,168,76,0.1); }
        .item-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
        .item-thumb-ph { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:18px; opacity:.2; }
        .item-name { font-size:15px; color:var(--cream); font-family:'Playfair Display',serif; }
        .item-unit { font-size:12px; color:rgba(201,168,76,0.4); margin-top:2px; }

        /* delivery info */
        .delivery-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; padding:16px 0; border-top:0.5px solid rgba(201,168,76,0.08); border-bottom:0.5px solid rgba(201,168,76,0.08); }
        .dg-label { font-size:9px; letter-spacing:3px; text-transform:uppercase; color:rgba(201,168,76,0.38); margin-bottom:5px; }
        .dg-value { font-size:14px; font-weight:300; color:var(--cream); line-height:1.6; }

        /* ── STATUS CONTROL PANEL ── */
        .status-panel {
            background:rgba(201,168,76,0.04);
            border:0.5px solid rgba(201,168,76,0.14);
            padding:20px 22px;
        }
        .sp-label { font-size:10px; letter-spacing:4px; text-transform:uppercase; color:rgba(201,168,76,0.5); margin-bottom:14px; display:flex; align-items:center; gap:10px; }
        .sp-label::after { content:''; flex:1; height:.5px; background:linear-gradient(to right,rgba(201,168,76,0.2),transparent); }

        .sp-buttons { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }

        /* quick status buttons */
        .sp-btn {
            padding:10px 22px; font-family:'Cormorant Garamond',serif;
            font-size:12px; font-weight:600; letter-spacing:2.5px; text-transform:uppercase;
            border:0.5px solid; cursor:pointer; background:transparent;
            transition:all 0.28s; display:inline-flex; align-items:center; gap:8px;
        }
        .sp-btn:hover { transform:translateY(-1px); }
        .sp-btn:active { transform:translateY(0); }

        .sp-delivered { color:var(--delivered); border-color:rgba(90,185,122,0.35); }
        .sp-delivered:hover { background:rgba(90,185,122,0.12); border-color:var(--delivered); box-shadow:0 4px 20px rgba(90,185,122,0.15); }

        .sp-cancelled { color:var(--cancelled); border-color:rgba(224,92,58,0.35); }
        .sp-cancelled:hover { background:rgba(224,92,58,0.12); border-color:var(--cancelled); box-shadow:0 4px 20px rgba(224,92,58,0.15); }

        .sp-processing { color:var(--processing); border-color:rgba(106,180,232,0.35); }
        .sp-processing:hover { background:rgba(106,180,232,0.1); border-color:var(--processing); }

        .sp-pending { color:var(--pending); border-color:rgba(224,169,58,0.35); }
        .sp-pending:hover { background:rgba(224,169,58,0.1); border-color:var(--pending); }

        /* current status pill inside panel */
        .sp-current { margin-left:auto; font-size:11px; font-weight:300; color:var(--cream-dim); display:flex; align-items:center; gap:8px; flex-shrink:0; }
        .sp-current strong { color:var(--cream); }

        /* disabled (already that status) */
        .sp-btn.is-current { opacity:0.3; cursor:not-allowed; transform:none !important; pointer-events:none; }

        /* ── CONFIRM MODAL ── */
        .modal-overlay { position:fixed; inset:0; background:rgba(4,1,0,0.88); z-index:500; display:none; align-items:center; justify-content:center; padding:20px; backdrop-filter:blur(4px); }
        .modal-overlay.open { display:flex; animation:fadeIn 0.3s both; }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }

        .modal { background:rgba(20,7,0,0.99); border:0.5px solid rgba(201,168,76,0.22); padding:0; max-width:440px; width:100%; position:relative; animation:popUp 0.35s cubic-bezier(0.16,1,0.3,1) both; overflow:hidden; }
        @keyframes popUp { from{opacity:0;transform:scale(0.93) translateY(16px)} to{opacity:1;transform:scale(1) translateY(0)} }
        .modal::before,.modal::after { content:''; position:absolute; width:13px; height:13px; border-color:var(--gold); border-style:solid; opacity:.38; }
        .modal::before { top:10px; left:10px; border-width:1px 0 0 1px; }
        .modal::after  { bottom:10px; right:10px; border-width:0 1px 1px 0; }

        .modal-body { padding:36px 32px; text-align:center; }
        .modal-icon  { font-size:44px; margin-bottom:16px; display:block; }
        .modal-title { font-family:'Playfair Display',serif; font-size:24px; color:var(--cream); margin-bottom:10px; }
        .modal-msg   { font-size:15px; font-weight:300; color:var(--cream-dim); line-height:1.8; margin-bottom:28px; }

        /* status preview badge in modal */
        .modal-status-preview { display:inline-flex; align-items:center; gap:8px; padding:8px 18px; border:0.5px solid; margin-bottom:24px; font-size:12px; font-weight:700; letter-spacing:2px; text-transform:uppercase; }

        .modal-btns { display:flex; gap:10px; }
        .btn-modal-confirm { flex:1; padding:14px; font-family:'Cormorant Garamond',serif; font-size:12px; font-weight:700; letter-spacing:3px; text-transform:uppercase; border:none; cursor:pointer; transition:all 0.3s; display:flex; align-items:center; justify-content:center; gap:7px; }
        .btn-modal-confirm:hover { transform:translateY(-1px); opacity:.9; }
        .btn-modal-cancel { flex:1; padding:14px; background:transparent; color:var(--cream-dim); font-family:'Cormorant Garamond',serif; font-size:12px; font-weight:500; letter-spacing:3px; text-transform:uppercase; border:0.5px solid rgba(201,168,76,0.18); cursor:pointer; transition:all 0.3s; }
        .btn-modal-cancel:hover { border-color:var(--gold); color:var(--gold); }

        /* hidden form inside modal */
        #modalForm { display:none; }

        /* EMPTY */
        .empty { text-align:center; padding:80px 40px; }
        .empty-icon { font-size:52px; opacity:.18; margin-bottom:18px; }
        .empty h3 { font-family:'Playfair Display',serif; font-size:26px; font-weight:400; margin-bottom:8px; }
        .empty p  { font-size:14px; font-weight:300; color:var(--cream-dim); }

        footer { position:relative; z-index:1; border-top:0.5px solid rgba(201,168,76,0.1); padding:24px 40px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; max-width:1100px; margin:0 auto; }
        footer .f-brand { font-family:'Playfair Display',serif; font-size:16px; color:var(--gold); }
        footer .f-note  { font-size:12px; font-weight:300; color:rgba(245,237,214,.25); }

        @media(max-width:700px){
            nav { padding:0 16px; }
            .hero { padding:36px 16px 24px; }
            .page { padding:0 14px 60px; }
            .stats-strip { grid-template-columns:1fr 1fr; }
            .delivery-grid { grid-template-columns:1fr; }
            .sp-buttons { flex-direction:column; align-items:flex-start; }
            .sp-current { margin-left:0; }
            footer { padding:18px 16px; flex-direction:column; text-align:center; }
        }
    </style>
</head>
<body>

<div class="bg-fixed"></div>

<!-- TOAST -->
<?php if($success_msg || $error_msg): ?>
<div class="toast <?php echo $success_msg ? 'success':'error'; ?>" id="toast">
    <i class="bi bi-<?php echo $success_msg ? 'check-circle-fill':'exclamation-triangle-fill'; ?>"></i>
    <?php echo htmlspecialchars($success_msg ?: $error_msg); ?>
</div>
<?php endif; ?>

<!-- CONFIRM MODAL -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal">
        <div class="modal-body">
            <span class="modal-icon" id="modalIcon">📦</span>
            <div class="modal-title" id="modalTitle">Update Order Status?</div>
            <div id="modalStatusPreview"></div>
            <div class="modal-msg" id="modalMsg">This will update the order status. The customer will see the change.</div>
            <div class="modal-btns">
                <button class="btn-modal-cancel" onclick="closeModal()">Go Back</button>
                <button class="btn-modal-confirm" id="modalConfirmBtn" onclick="submitStatusUpdate()">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for status update -->
<form id="modalForm" action="" method="POST">
    <input type="hidden" name="order_id"   id="formOrderId">
    <input type="hidden" name="new_status" id="formNewStatus">
    <input type="hidden" name="update_status" value="1">
</form>

<!-- NAV -->
<?php require("admin_navbar.php"); ?>

<div class="adm-main">
<!-- HERO -->
<div class="hero">
    <div class="hero-tag">Admin Panel &nbsp;·&nbsp; Rana's Table</div>
    <h1 class="hero-title">Manage Orders</h1>
    <p class="hero-sub">Review, update status, and track every order.</p>
    <div class="hero-divider"><div class="hdl"></div><div class="hdg"></div><div class="hdl"></div></div>
</div>

<div class="page">

    <!-- STATS -->
    <div class="stats-strip">
        <div class="stat-box"><span class="sb-num" style="color:var(--gold)"><?php echo $counts['all']; ?></span><span class="sb-lbl">Total Orders</span></div>
        <div class="stat-box"><span class="sb-num" style="color:var(--pending)"><?php echo $counts['pending']; ?></span><span class="sb-lbl">Pending</span></div>
        <div class="stat-box"><span class="sb-num" style="color:var(--delivered)"><?php echo $counts['delivered']; ?></span><span class="sb-lbl">Delivered</span></div>
        <div class="stat-box"><span class="sb-num" style="color:var(--cancelled)"><?php echo $counts['cancelled']; ?></span><span class="sb-lbl">Cancelled</span></div>
    </div>

    <!-- FILTER TABS -->
    <div class="filter-bar">
        <?php
        $tabs = [
            'all'        => ['All Orders',  '', $counts['all']],
            'pending'    => ['Pending',     '⏳', $counts['pending']],
            'processing' => ['Processing',  '⟳', $counts['processing']],
            'delivered'  => ['Delivered',   '✓', $counts['delivered']],
            'cancelled'  => ['Cancelled',   '✕', $counts['cancelled']],
        ];
        foreach($tabs as $key => [$label, $icon, $cnt]):
        ?>
        <a href="?filter=<?php echo $key; ?>"
           class="f-tab <?php echo $filter===$key?'active':''; ?>">
            <?php if($icon) echo $icon.' '; ?><?php echo $label; ?>
            <span class="f-count <?php echo $key==='cancelled'?'red':''; ?>"><?php echo $cnt; ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ORDERS LIST -->
    <?php if(!empty($orders)): ?>
    <div class="orders-list">
        <?php foreach($orders as $idx => $order):
            $oid    = $order['id'];
            $status = strtolower($order['order_status'] ?? 'pending');
            $sc = match($status){ 'delivered'=>'s-delivered','processing'=>'s-processing','cancelled'=>'s-cancelled',default=>'s-pending' };
            $sl = match($status){ 'delivered'=>'✓ Delivered','processing'=>'⟳ Processing','cancelled'=>'✕ Cancelled',default=>'⏳ Pending' };
            $pay_icon = $order['payment_method']==='Bank Transfer' ? '🏦' : '💵';
            $items_in_order = $order_items_map[$oid] ?? [];
        ?>
        <div class="order-card" style="transition-delay:<?php echo $idx*0.05; ?>s" id="oc-<?php echo $oid; ?>">

            <!-- HEADER -->
            <div class="oc-head" onclick="toggleOrder(<?php echo $oid; ?>)">
                <div class="oc-left">
                    <div class="oc-id"><span>Order #</span><?php echo str_pad($oid,4,'0',STR_PAD_LEFT); ?></div>
                    <div class="oc-meta">
                        <div class="oc-meta-item"><i class="bi bi-person-fill" style="color:rgba(201,168,76,0.4)"></i> <?php echo htmlspecialchars($order['customer_name']); ?></div>
                        <div class="oc-meta-item"><div class="dot"></div>📦 <?php echo $order['item_count']; ?> item<?php echo $order['item_count']!=1?'s':''; ?></div>
                        <?php if(!empty($order['created_at'])): ?>
                        <div class="oc-meta-item"><div class="dot"></div>🕐 <?php echo date('d M Y, h:i A',strtotime($order['created_at'])); ?></div>
                        <?php endif; ?>
                        <div class="oc-meta-item"><div class="dot"></div><?php echo $pay_icon; ?> <?php echo htmlspecialchars($order['payment_method']); ?></div>
                    </div>
                </div>
                <div class="oc-right">
                    <span class="s-badge <?php echo $sc; ?>"><?php echo $sl; ?></span>
                    <span class="oc-total">PKR <?php echo number_format($order['total_amount']); ?></span>
                    <span class="oc-toggle">▼</span>
                </div>
            </div>

            <!-- BODY -->
            <div class="oc-body" id="ob-<?php echo $oid; ?>">
                <div class="oc-body-inner">

                    <!-- Items -->
                    <?php if(!empty($items_in_order)): ?>
                    <table class="items-table">
                        <tr>
                            <th>Product</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                        <?php foreach($items_in_order as $item): ?>
                        <tr>
                            <td>
                                <div class="item-row">
                                    <div class="item-thumb">
                                        <?php if(!empty($item['product_image'])): ?>
                                            <img src="../product_uploads/<?php echo htmlspecialchars($item['product_image']); ?>" alt="">
                                        <?php else: ?>
                                            <div class="item-thumb-ph">🍽</div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <div class="item-unit">PKR <?php echo number_format($item['product_price']); ?> each</div>
                                    </div>
                                </div>
                            </td>
                            <td>PKR <?php echo number_format($item['price']); ?></td>
                            <td>× <?php echo $item['quantity']; ?></td>
                            <td>PKR <?php echo number_format($item['price']*$item['quantity']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php endif; ?>

                    <!-- Delivery Info -->
                    <div class="delivery-grid">
                        <div><div class="dg-label">Customer Name</div><div class="dg-value"><?php echo htmlspecialchars($order['customer_name']); ?></div></div>
                        <div><div class="dg-label">Phone</div><div class="dg-value"><?php echo htmlspecialchars($order['customer_phone']); ?></div></div>
                        <div><div class="dg-label">Delivery Address</div><div class="dg-value"><?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></div></div>
                    </div>

                    <!-- ══ STATUS CONTROL PANEL ══ -->
                    <div class="status-panel">
                        <div class="sp-label">Update Order Status</div>
                        <div class="sp-buttons">

                            <!-- DELIVERED -->
                            <button class="sp-btn sp-delivered <?php echo $status==='delivered'?'is-current':''; ?>"
                                onclick="openModal(<?php echo $oid; ?>, 'delivered',
                                    '✅', 'Mark as Delivered?',
                                    'Order #<?php echo str_pad($oid,4,"0",STR_PAD_LEFT); ?> will be marked as Delivered.',
                                    '#5ab97a', '#8de8a8', 'delivered', '✓ Confirm Delivery')">
                                <i class="bi bi-check-circle-fill"></i>
                                Mark Delivered
                            </button>

                            <!-- CANCELLED -->
                            <button class="sp-btn sp-cancelled <?php echo $status==='cancelled'?'is-current':''; ?>"
                                onclick="openModal(<?php echo $oid; ?>, 'cancelled',
                                    '❌', 'Cancel this Order?',
                                    'Order #<?php echo str_pad($oid,4,"0",STR_PAD_LEFT); ?> will be marked as Cancelled.',
                                    '#c0392b', '#f0896a', 'cancelled', '✕ Confirm Cancel')">
                                <i class="bi bi-x-circle-fill"></i>
                                Cancel Order
                            </button>

                            <!-- PROCESSING -->
                            <button class="sp-btn sp-processing <?php echo $status==='processing'?'is-current':''; ?>"
                                onclick="openModal(<?php echo $oid; ?>, 'processing',
                                    '⟳', 'Set to Processing?',
                                    'Order #<?php echo str_pad($oid,4,"0",STR_PAD_LEFT); ?> will be set to Processing.',
                                    '#2980b9', '#6ab4e8', 'processing', '⟳ Set Processing')">
                                <i class="bi bi-arrow-repeat"></i>
                                Processing
                            </button>

                            <!-- PENDING -->
                            <button class="sp-btn sp-pending <?php echo $status==='pending'?'is-current':''; ?>"
                                onclick="openModal(<?php echo $oid; ?>, 'pending',
                                    '⏳', 'Reset to Pending?',
                                    'Order #<?php echo str_pad($oid,4,"0",STR_PAD_LEFT); ?> will be reset to Pending.',
                                    '#8B6914', '#e0a93a', 'pending', '⏳ Set Pending')">
                                <i class="bi bi-clock-fill"></i>
                                Pending
                            </button>

                            <!-- Current status indicator -->
                            <div class="sp-current">
                                Current: <span class="s-badge <?php echo $sc; ?>"><?php echo $sl; ?></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="empty">
        <div class="empty-icon">📋</div>
        <h3>No orders found</h3>
        <p><?php echo $filter !== 'all' ? 'No '.ucfirst($filter).' orders at the moment.' : 'No orders have been placed yet.'; ?></p>
    </div>
    <?php endif; ?>

</div>

<footer>
    <div class="f-brand">Rana's Table</div>
    <div class="f-note">© <?php echo date('Y'); ?> Rana's Table &nbsp;·&nbsp; Admin Panel</div>
</footer>
</div><!-- .adm-main -->

<script>
/* Embers */
const ec=['#C9A84C','#FF8C42','#FFD166','#E87040'];
for(let i=0;i<16;i++){
    const e=document.createElement('div'); e.className='ember';
    const s=1.2+Math.random()*2.5, c=ec[Math.floor(Math.random()*ec.length)];
    Object.assign(e.style,{width:s+'px',height:s+'px',background:c,left:(4+Math.random()*92)+'%',bottom:'-8px','--d':((Math.random()-.5)*70)+'px',animationDuration:(10+Math.random()*10)+'s',animationDelay:(Math.random()*18)+'s',boxShadow:`0 0 ${s*2}px ${c}`});
    document.body.appendChild(e);
}

/* Toast */
const toast = document.getElementById('toast');
if(toast){ setTimeout(()=>toast.classList.add('show'),120); setTimeout(()=>toast.classList.remove('show'),4500); }

/* Scroll reveal */
const obs = new IntersectionObserver(entries=>{
    entries.forEach(e=>{ if(e.isIntersecting) e.target.classList.add('visible'); });
},{threshold:0.04});
document.querySelectorAll('.order-card').forEach(c=>obs.observe(c));

/* Accordion */
function toggleOrder(id){
    document.getElementById('oc-'+id).classList.toggle('open');
}

/* Auto-open first if only a few results */
window.addEventListener('DOMContentLoaded',()=>{
    const cards = document.querySelectorAll('.order-card');
    if(cards.length === 1) setTimeout(()=>cards[0].classList.add('open'),400);
});

/* ── STATUS UPDATE MODAL ── */
let pendingOrderId = null;
let pendingStatus  = null;

function openModal(orderId, status, icon, title, msg, btnBg, btnColor, statusKey, btnLabel){
    pendingOrderId = orderId;
    pendingStatus  = status;

    document.getElementById('modalIcon').textContent  = icon;
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalMsg').textContent   = msg;

    // Status preview badge
    const statusStyles = {
        delivered:  { color:'#5ab97a', border:'rgba(90,185,122,0.4)',  bg:'rgba(90,185,122,0.1)',  label:'✓ Delivered' },
        cancelled:  { color:'#e05c3a', border:'rgba(224,92,58,0.4)',   bg:'rgba(224,92,58,0.1)',   label:'✕ Cancelled' },
        processing: { color:'#6ab4e8', border:'rgba(106,180,232,0.4)', bg:'rgba(106,180,232,0.1)', label:'⟳ Processing' },
        pending:    { color:'#e0a93a', border:'rgba(224,169,58,0.4)',  bg:'rgba(224,169,58,0.1)',  label:'⏳ Pending' },
    };
    const st = statusStyles[statusKey] || statusStyles.pending;
    document.getElementById('modalStatusPreview').innerHTML =
        `<div style="text-align:center;margin-bottom:20px">
           <span style="display:inline-flex;align-items:center;gap:8px;padding:7px 18px;border:0.5px solid ${st.border};background:${st.bg};color:${st.color};font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase">
             ${st.label}
           </span>
         </div>`;

    const btn = document.getElementById('modalConfirmBtn');
    btn.textContent    = btnLabel;
    btn.style.background = btnBg;
    btn.style.color      = btnColor;

    document.getElementById('confirmModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal(){
    document.getElementById('confirmModal').classList.remove('open');
    document.body.style.overflow = '';
    pendingOrderId = null; pendingStatus = null;
}

function submitStatusUpdate(){
    if(!pendingOrderId || !pendingStatus) return;
    document.getElementById('formOrderId').value  = pendingOrderId;
    document.getElementById('formNewStatus').value = pendingStatus;
    document.getElementById('modalForm').submit();
}

document.getElementById('confirmModal').addEventListener('click',function(e){
    if(e.target===this) closeModal();
});
document.addEventListener('keydown',e=>{ if(e.key==='Escape') closeModal(); });
</script>
</body>
</html>