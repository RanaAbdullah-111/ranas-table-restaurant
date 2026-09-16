<?php
include("../config.php");
require("../auth.php");

$user_id = $_SESSION['user_id'];
$action_msg  = "";
$action_type = ""; // success | error

/*
|--------------------------------------------------------------------------
| HANDLE ORDER ACTIONS (Cancel / Reorder)
|--------------------------------------------------------------------------
*/
if(isset($_POST['action']) && isset($_POST['order_id'])){
    $action   = $_POST['action'];
    $order_id = (int)$_POST['order_id'];

    // Verify this order belongs to the logged-in user
    $verify = $connection->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $verify->bind_param("ii", $order_id, $user_id);
    $verify->execute();
    $order_row = $verify->get_result()->fetch_assoc();

    if($order_row){
        $current_status = strtolower($order_row['order_status'] ?? 'pending');

        if($action === 'cancel'){
            // Only allow cancel if pending
            if(in_array($current_status, ['pending', ''])){
                $upd = $connection->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ? AND user_id = ?");
                $upd->bind_param("ii", $order_id, $user_id);
                if($upd->execute()){
                    $action_msg  = "Order #" . str_pad($order_id,4,'0',STR_PAD_LEFT) . " has been cancelled.";
                    $action_type = "success";
                } else {
                    $action_msg  = "Could not cancel the order. Please try again.";
                    $action_type = "error";
                }
            } else {
                $action_msg  = "This order cannot be cancelled as it is already " . ucfirst($current_status) . ".";
                $action_type = "error";
            }
        }

        elseif($action === 'reorder'){
            // Add all items from this order back into the cart
            $items_q = $connection->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $items_q->bind_param("i", $order_id);
            $items_q->execute();
            $items_r = $items_q->get_result();

            $added = 0;
            while($item = $items_r->fetch_assoc()){
                $ins = $connection->prepare("INSERT INTO card (user_id, product_id, quantity, price) VALUES (?,?,?,?)");
                $ins->bind_param("iiii", $user_id, $item['product_id'], $item['quantity'], $item['price']);
                if($ins->execute()) $added++;
            }
            if($added > 0){
                $action_msg  = "Order #" . str_pad($order_id,4,'0',STR_PAD_LEFT) . " items added to your cart!";
                $action_type = "success";
            } else {
                $action_msg  = "Could not reorder. Please try again.";
                $action_type = "error";
            }
        }

        elseif($action === 'request_refund'){
            if($current_status === 'delivered'){
                $upd = $connection->prepare("UPDATE orders SET order_status = 'refund_requested' WHERE id = ? AND user_id = ?");
                $upd->bind_param("ii", $order_id, $user_id);
                if($upd->execute()){
                    $action_msg  = "Refund request submitted for Order #" . str_pad($order_id,4,'0',STR_PAD_LEFT) . ". We'll contact you shortly.";
                    $action_type = "success";
                } else {
                    $action_msg  = "Could not submit refund request. Please try again.";
                    $action_type = "error";
                }
            } else {
                $action_msg  = "Refunds can only be requested for delivered orders.";
                $action_type = "error";
            }
        }
    } else {
        $action_msg  = "Order not found.";
        $action_type = "error";
    }
}

/*
|--------------------------------------------------------------------------
| FETCH ALL ORDERS
|--------------------------------------------------------------------------
*/
$orders_query = "
    SELECT orders.*,
           COUNT(order_items.id) AS item_count
    FROM orders
    LEFT JOIN order_items ON orders.id = order_items.order_id
    WHERE orders.user_id = ?
    GROUP BY orders.id
    ORDER BY orders.id DESC
";
$stmt = $connection->prepare($orders_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$orders = [];
while($row = mysqli_fetch_assoc($orders_result)) $orders[] = $row;

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
    $order_items_map[$oid] = [];
    while($ir = mysqli_fetch_assoc($i_res)) $order_items_map[$oid][] = $ir;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders — Rana's Table</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Cormorant+Garamond:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        :root {
            --gold:      #C9A84C;
            --gold-lite: #E8C96A;
            --deep:      #110600;
            --card-bg:   rgba(30,12,0,0.84);
            --cream:     #F5EDD6;
            --cream-dim: rgba(245,237,214,0.52);
            --pending:   #e0a93a;
            --delivered: #5ab97a;
            --cancelled: #e05c3a;
            --processing:#6ab4e8;
            --refund:    #b97ae8;
        }

        html { scroll-behavior:smooth; }
        body { font-family:'Cormorant Garamond',serif; background:var(--deep); color:var(--cream); min-height:100vh; }

        .bg-fixed { position:fixed; inset:0; z-index:0; pointer-events:none; background:radial-gradient(ellipse 80% 50% at 50% 0%,#3a1600 0%,#110600 65%); }

        .ember { position:fixed; border-radius:50%; pointer-events:none; opacity:0; animation:floatUp linear infinite; z-index:0; }
        @keyframes floatUp { 0%{transform:translateY(0) translateX(0);opacity:0} 8%{opacity:.7} 90%{opacity:.1} 100%{transform:translateY(-100vh) translateX(var(--d));opacity:0} }

        

        /* TOAST */
        .toast {
            position:fixed; top:80px; left:50%;
            transform:translateX(-50%) translateY(-20px);
            padding:14px 28px; font-size:14px; letter-spacing:0.5px;
            z-index:999; pointer-events:none; opacity:0;
            transition:all 0.45s cubic-bezier(0.16,1,0.3,1);
            white-space:nowrap; display:flex; align-items:center; gap:10px;
        }
        .toast.show { opacity:1; transform:translateX(-50%) translateY(0); }
        .toast.success { background:rgba(25,55,35,0.97); border:0.5px solid rgba(90,185,122,0.4); color:#8de8a8; }
        .toast.error   { background:rgba(55,15,10,0.97); border:0.5px solid rgba(224,92,58,0.4);  color:#f0896a; }

        /* HERO */
        .hero { position:relative; z-index:1; text-align:center; padding:52px 40px 32px; }
        .hero-tag { font-size:10px; letter-spacing:5px; text-transform:uppercase; color:rgba(201,168,76,0.55); margin-bottom:12px; animation:fadeUp 0.6s 0.1s both; }
        .hero-title { font-family:'Playfair Display',serif; font-size:clamp(36px,6vw,64px); font-weight:700; line-height:1; background:linear-gradient(135deg,#C9A84C 0%,#F0D08A 45%,#8B6914 100%); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; animation:fadeUp 0.6s 0.2s both; }
        .hero-sub { font-size:16px; font-weight:300; color:var(--cream-dim); margin-top:10px; animation:fadeUp 0.6s 0.3s both; }
        .hero-divider { display:flex; align-items:center; gap:12px; width:220px; margin:18px auto 0; animation:fadeUp 0.6s 0.4s both; }
        .hdl { flex:1; height:.5px; background:linear-gradient(to right,transparent,rgba(201,168,76,.38),transparent); }
        .hdg { width:5px; height:5px; background:var(--gold); transform:rotate(45deg); flex-shrink:0; opacity:.65; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

        /* STATS */
        .stats-strip { position:relative; z-index:1; max-width:1060px; margin:0 auto 28px; padding:0 32px; display:grid; grid-template-columns:repeat(4,1fr); gap:2px; animation:fadeUp 0.6s 0.45s both; }
        .stat-box { background:var(--card-bg); border:0.5px solid rgba(201,168,76,0.12); padding:20px 24px; text-align:center; }
        .stat-num { font-family:'Playfair Display',serif; font-size:30px; color:var(--gold); display:block; line-height:1; margin-bottom:6px; }
        .stat-lbl { font-size:10px; letter-spacing:3px; text-transform:uppercase; color:var(--cream-dim); }

        /* PAGE */
        .page { position:relative; z-index:1; max-width:1060px; margin:0 auto; padding:0 32px 80px; }

        /* ORDER CARD */
        .order-card { background:var(--card-bg); border:0.5px solid rgba(201,168,76,0.12); margin-bottom:14px; opacity:0; transform:translateY(20px); transition:opacity 0.5s ease, transform 0.5s ease, border-color 0.3s; }
        .order-card.visible { opacity:1; transform:translateY(0); }
        .order-card:hover { border-color:rgba(201,168,76,0.28); }

        /* CARD HEADER */
        .oc-head { padding:20px 28px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; border-bottom:0.5px solid transparent; cursor:pointer; user-select:none; transition:background 0.2s; }
        .oc-head:hover { background:rgba(201,168,76,0.03); }
        .order-card.open .oc-head { border-bottom-color:rgba(201,168,76,0.08); }

        .oc-head-left { display:flex; align-items:center; gap:20px; flex-wrap:wrap; }
        .oc-id { font-family:'Playfair Display',serif; font-size:20px; font-weight:400; color:var(--cream); }
        .oc-id span { font-size:12px; font-weight:300; color:rgba(201,168,76,0.45); letter-spacing:1px; font-family:'Cormorant Garamond',serif; }

        .oc-meta { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
        .oc-meta-item { font-size:13px; font-weight:300; color:var(--cream-dim); display:flex; align-items:center; gap:6px; }
        .sep { width:3px; height:3px; background:rgba(201,168,76,0.28); border-radius:50%; }

        .oc-head-right { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }

        /* STATUS */
        .status-badge { font-size:10px; font-weight:700; letter-spacing:2px; text-transform:uppercase; padding:5px 12px; border:0.5px solid; }
        .s-pending          { color:var(--pending);    border-color:rgba(224,169,58,0.35);  background:rgba(224,169,58,0.08); }
        .s-processing       { color:var(--processing); border-color:rgba(106,180,232,0.35); background:rgba(106,180,232,0.08); }
        .s-delivered        { color:var(--delivered);  border-color:rgba(90,185,122,0.35);  background:rgba(90,185,122,0.08); }
        .s-cancelled        { color:var(--cancelled);  border-color:rgba(224,92,58,0.35);   background:rgba(224,92,58,0.08); }
        .s-refund_requested { color:var(--refund);     border-color:rgba(185,122,232,0.35); background:rgba(185,122,232,0.08); }

        .oc-total { font-family:'Playfair Display',serif; font-size:22px; color:var(--gold); white-space:nowrap; }
        .oc-toggle { font-size:16px; color:rgba(201,168,76,0.4); transition:transform 0.35s cubic-bezier(0.4,0,0.2,1); flex-shrink:0; }
        .order-card.open .oc-toggle { transform:rotate(180deg); }

        /* BODY */
        .oc-body { max-height:0; overflow:hidden; transition:max-height 0.5s cubic-bezier(0.4,0,0.2,1); }
        .order-card.open .oc-body { max-height:1200px; }
        .oc-body-inner { padding:24px 28px; display:flex; flex-direction:column; gap:24px; }

        /* ITEMS TABLE */
        .items-table { width:100%; border-collapse:collapse; }
        .items-table th { font-size:10px; letter-spacing:3px; text-transform:uppercase; color:rgba(201,168,76,0.45); font-weight:500; padding:8px 12px; border-bottom:0.5px solid rgba(201,168,76,0.1); text-align:left; }
        .items-table th:last-child { text-align:right; }
        .items-table td { padding:13px 12px; border-bottom:0.5px solid rgba(201,168,76,0.05); font-size:15px; font-weight:300; color:var(--cream-dim); vertical-align:middle; }
        .items-table td:last-child { text-align:right; color:var(--gold); font-size:16px; }
        .items-table tr:last-child td { border-bottom:none; }

        .item-with-img { display:flex; align-items:center; gap:14px; }
        .item-thumb { width:52px; height:52px; flex-shrink:0; overflow:hidden; background:rgba(45,21,0,0.5); border:0.5px solid rgba(201,168,76,0.1); }
        .item-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
        .item-thumb-ph { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:20px; opacity:.2; }
        .item-name { font-size:16px; font-weight:400; color:var(--cream); font-family:'Playfair Display',serif; }
        .item-unit { font-size:12px; color:rgba(201,168,76,0.4); margin-top:2px; }

        /* DELIVERY INFO */
        .delivery-info { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; padding:20px 0; border-top:0.5px solid rgba(201,168,76,0.08); border-bottom:0.5px solid rgba(201,168,76,0.08); }
        .di-label { font-size:10px; letter-spacing:3px; text-transform:uppercase; color:rgba(201,168,76,0.42); margin-bottom:6px; }
        .di-value { font-size:15px; font-weight:300; color:var(--cream); line-height:1.7; }

        /* ACTION BUTTONS */
        .action-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding-top:4px; }
        .action-label { font-size:11px; letter-spacing:3px; text-transform:uppercase; color:rgba(201,168,76,0.4); margin-right:4px; }

        .act-btn {
            padding:9px 22px; font-family:'Cormorant Garamond',serif;
            font-size:12px; font-weight:600; letter-spacing:2.5px; text-transform:uppercase;
            border:0.5px solid; cursor:pointer; background:transparent;
            transition:all 0.3s; display:inline-flex; align-items:center; gap:7px;
        }
        .act-btn:hover { transform:translateY(-1px); }

        .btn-reorder  { color:var(--gold);      border-color:rgba(201,168,76,0.35); }
        .btn-reorder:hover  { background:rgba(201,168,76,0.1); border-color:var(--gold); }

        .btn-cancel   { color:var(--cancelled); border-color:rgba(224,92,58,0.35); }
        .btn-cancel:hover   { background:rgba(224,92,58,0.1); border-color:var(--cancelled); }

        .btn-refund   { color:var(--refund);    border-color:rgba(185,122,232,0.35); }
        .btn-refund:hover   { background:rgba(185,122,232,0.08); border-color:var(--refund); }

        .act-btn:disabled, .act-btn.disabled { opacity:0.3; cursor:not-allowed; transform:none; pointer-events:none; }

        /* CONFIRM MODAL */
        .modal-overlay { position:fixed; inset:0; background:rgba(5,2,0,0.88); z-index:500; display:none; align-items:center; justify-content:center; padding:20px; }
        .modal-overlay.open { display:flex; animation:fadeIn 0.3s both; }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }

        .modal {
            background:rgba(28,11,0,0.98); border:0.5px solid rgba(201,168,76,0.25);
            padding:40px 36px; max-width:420px; width:100%;
            position:relative; animation:popUp 0.35s cubic-bezier(0.16,1,0.3,1) both;
        }
        @keyframes popUp { from{opacity:0;transform:scale(0.94) translateY(16px)} to{opacity:1;transform:scale(1) translateY(0)} }

        .modal::before, .modal::after { content:''; position:absolute; width:14px; height:14px; border-color:var(--gold); border-style:solid; opacity:.4; }
        .modal::before { top:10px; left:10px; border-width:1px 0 0 1px; }
        .modal::after  { bottom:10px; right:10px; border-width:0 1px 1px 0; }

        .modal-icon { font-size:36px; margin-bottom:16px; text-align:center; }
        .modal-title { font-family:'Playfair Display',serif; font-size:24px; font-weight:400; color:var(--cream); margin-bottom:10px; text-align:center; }
        .modal-msg { font-size:15px; font-weight:300; color:var(--cream-dim); line-height:1.8; text-align:center; margin-bottom:28px; }

        .modal-btns { display:flex; gap:10px; }
        .modal-confirm {
            flex:1; padding:13px; background:var(--cancelled); color:#fff;
            font-family:'Cormorant Garamond',serif; font-size:12px; font-weight:700;
            letter-spacing:3px; text-transform:uppercase; border:none; cursor:pointer;
            transition:all 0.3s; position:relative; overflow:hidden;
        }
        .modal-confirm.gold-confirm { background:var(--gold); color:var(--deep); }
        .modal-confirm.purple-confirm { background:#7c4dbc; color:#fff; }
        .modal-confirm:hover { opacity:0.88; transform:translateY(-1px); }

        .modal-cancel-btn {
            flex:1; padding:13px; background:transparent; color:var(--cream-dim);
            font-family:'Cormorant Garamond',serif; font-size:12px; font-weight:500;
            letter-spacing:3px; text-transform:uppercase;
            border:0.5px solid rgba(201,168,76,0.2); cursor:pointer; transition:all 0.3s;
        }
        .modal-cancel-btn:hover { border-color:var(--gold); color:var(--gold); }

        /* EMPTY */
        .empty { text-align:center; padding:90px 40px; position:relative; z-index:1; }
        .empty-icon { font-size:60px; opacity:.18; margin-bottom:22px; }
        .empty h3 { font-family:'Playfair Display',serif; font-size:30px; font-weight:400; margin-bottom:10px; }
        .empty p  { font-size:15px; font-weight:300; color:var(--cream-dim); margin-bottom:30px; }
        .btn-go { padding:13px 36px; background:var(--gold); color:var(--deep); font-family:'Cormorant Garamond',serif; font-size:12px; font-weight:700; letter-spacing:3px; text-transform:uppercase; border:none; cursor:pointer; text-decoration:none; display:inline-block; transition:all 0.3s; }
        .btn-go:hover { background:var(--gold-lite); transform:translateY(-2px); }

        footer { position:relative; z-index:1; border-top:0.5px solid rgba(201,168,76,0.1); padding:28px 40px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; max-width:1060px; margin:0 auto; }
        footer .f-brand { font-family:'Playfair Display',serif; font-size:17px; color:var(--gold); }
        footer .f-note  { font-size:12px; font-weight:300; color:rgba(245,237,214,.28); }

        @media(max-width:700px){
            nav { padding:0 16px; }
            .hero { padding:40px 16px 28px; }
            .stats-strip { grid-template-columns:1fr 1fr; padding:0 16px; }
            .page { padding:0 14px 60px; }
            .oc-head { padding:14px 16px; }
            .oc-body-inner { padding:16px; }
            .delivery-info { grid-template-columns:1fr; }
            .items-table th:nth-child(2), .items-table td:nth-child(2) { display:none; }
            footer { padding:20px 16px; flex-direction:column; text-align:center; }
        }
    </style>
</head>
<body>

<div class="bg-fixed"></div>

<!-- TOAST -->
<?php if($action_msg): ?>
<div class="toast <?php echo $action_type; ?>" id="actionToast">
    <?php echo $action_type === 'success' ? '✓' : '⚠'; ?>
    <?php echo htmlspecialchars($action_msg); ?>
</div>
<?php endif; ?>

<!-- CONFIRM MODAL -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <div class="modal-icon" id="modalIcon">⚠️</div>
        <div class="modal-title" id="modalTitle">Are you sure?</div>
        <div class="modal-msg"   id="modalMsg">This action cannot be undone.</div>
        <div class="modal-btns">
            <button class="modal-cancel-btn" onclick="closeModal()">Go Back</button>
            <form id="modalForm" action="" method="POST" style="flex:1">
                <input type="hidden" name="order_id" id="modalOrderId">
                <input type="hidden" name="action"   id="modalAction">
                <button type="submit" class="modal-confirm" id="modalConfirmBtn" style="width:100%">Confirm</button>
            </form>
        </div>
    </div>
</div>

<!-- NAV -->
<?php
    include("navbar.php");
?>
<!-- HERO -->
<div class="hero">
    <div class="hero-tag">Rana's Table &nbsp;·&nbsp; Order History</div>
    <h1 class="hero-title">My Orders</h1>
    <p class="hero-sub">Track, manage, and reorder your past meals.</p>
    <div class="hero-divider"><div class="hdl"></div><div class="hdg"></div><div class="hdl"></div></div>
</div>

<?php if(!empty($orders)):
    $total_spent   = array_sum(array_column($orders,'total_amount'));
    $total_items   = array_sum(array_column($orders,'item_count'));
    $delivered_cnt = count(array_filter($orders, fn($o)=> strtolower($o['order_status']??'') === 'delivered'));
    $pending_cnt   = count(array_filter($orders, fn($o)=> in_array(strtolower($o['order_status']??''),['pending',''])));
?>

<!-- STATS -->
<div class="stats-strip">
    <div class="stat-box"><span class="stat-num"><?php echo count($orders); ?></span><span class="stat-lbl">Total Orders</span></div>
    <div class="stat-box"><span class="stat-num"><?php echo $total_items; ?></span><span class="stat-lbl">Items Ordered</span></div>
    <div class="stat-box"><span class="stat-num"><?php echo $delivered_cnt; ?></span><span class="stat-lbl">Delivered</span></div>
    <div class="stat-box"><span class="stat-num">PKR <?php echo number_format($total_spent); ?></span><span class="stat-lbl">Total Spent</span></div>
</div>

<!-- ORDERS -->
<div class="page">
    <?php foreach($orders as $idx => $order):
        $oid    = $order['id'];
        $status = strtolower($order['order_status'] ?? 'pending');

        $status_class = match($status){
            'delivered'        => 's-delivered',
            'processing'       => 's-processing',
            'cancelled'        => 's-cancelled',
            'refund_requested' => 's-refund_requested',
            default            => 's-pending'
        };
        $status_label = match($status){
            'delivered'        => '✓ Delivered',
            'processing'       => '⟳ Processing',
            'cancelled'        => '✕ Cancelled',
            'refund_requested' => '↩ Refund Requested',
            default            => '⏳ Pending'
        };
        $pay_icon = $order['payment_method'] === 'Bank Transfer' ? '🏦' : '💵';
        $items_in_order = $order_items_map[$oid] ?? [];

        // What actions are available?
        $can_cancel  = in_array($status, ['pending', '']);
        $can_reorder = true; // always
        $can_refund  = ($status === 'delivered');
    ?>
    <div class="order-card" style="transition-delay:<?php echo $idx*0.07; ?>s" id="order-<?php echo $oid; ?>">

        <!-- HEADER -->
        <div class="oc-head" onclick="toggleOrder(<?php echo $oid; ?>)">
            <div class="oc-head-left">
                <div class="oc-id"><span>Order #</span><?php echo str_pad($oid,4,'0',STR_PAD_LEFT); ?></div>
                <div class="oc-meta">
                    <div class="oc-meta-item">📦 <?php echo $order['item_count']; ?> item<?php echo $order['item_count']!=1?'s':''; ?></div>
                    <?php if(!empty($order['created_at'])): ?>
                    <div class="oc-meta-item"><div class="sep"></div>🕐 <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></div>
                    <?php endif; ?>
                    <div class="oc-meta-item"><div class="sep"></div><?php echo $pay_icon; ?> <?php echo htmlspecialchars($order['payment_method']); ?></div>
                </div>
            </div>
            <div class="oc-head-right">
                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                <span class="oc-total">PKR <?php echo number_format($order['total_amount']); ?></span>
                <span class="oc-toggle">▼</span>
            </div>
        </div>

        <!-- BODY -->
        <div class="oc-body" id="body-<?php echo $oid; ?>">
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
                            <div class="item-with-img">
                                <div class="item-thumb">
                                    <?php if(!empty($item['product_image'])): ?>
                                        <img src="product_uploads/<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
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
                        <td>PKR <?php echo number_format($item['price'] * $item['quantity']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>

                <!-- Delivery info -->
                <div class="delivery-info">
                    <div><div class="di-label">Customer</div><div class="di-value"><?php echo htmlspecialchars($order['customer_name']); ?></div></div>
                    <div><div class="di-label">Phone</div><div class="di-value"><?php echo htmlspecialchars($order['customer_phone']); ?></div></div>
                    <div><div class="di-label">Delivery Address</div><div class="di-value"><?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></div></div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="action-row">
                    <span class="action-label">Actions:</span>

                    <!-- Reorder — always available -->
                    <button class="act-btn btn-reorder"
                            onclick="openModal(<?php echo $oid; ?>, 'reorder', '🔄', 'Reorder this meal?', 'All items from Order #<?php echo str_pad($oid,4,"0",STR_PAD_LEFT); ?> will be added to your cart.', 'gold-confirm', 'Add to Cart')">
                        🔄 Reorder
                    </button>

                    <!-- Cancel — only if pending -->
                    <button class="act-btn btn-cancel <?php echo !$can_cancel ? 'disabled' : ''; ?>"
                            <?php echo !$can_cancel ? 'disabled title="Cannot cancel — order is '.ucfirst($status).'"' : ''; ?>
                            onclick="openModal(<?php echo $oid; ?>, 'cancel', '⚠️', 'Cancel this order?', 'Order #<?php echo str_pad($oid,4,"0",STR_PAD_LEFT); ?> will be cancelled. This cannot be undone.', '', 'Yes, Cancel Order')">
                        ✕ Cancel Order
                    </button>

                    <!-- Refund — only if delivered -->
                    <button class="act-btn btn-refund <?php echo !$can_refund ? 'disabled' : ''; ?>"
                            <?php echo !$can_refund ? 'disabled title="Refunds only available for delivered orders"' : ''; ?>
                            onclick="openModal(<?php echo $oid; ?>, 'request_refund', '↩️', 'Request a refund?', 'A refund request will be submitted for Order #<?php echo str_pad($oid,4,"0",STR_PAD_LEFT); ?>. Our team will contact you within 24 hours.', 'purple-confirm', 'Request Refund')">
                        ↩ Request Refund
                    </button>
                </div>

            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php else: ?>
<div class="empty">
    <div class="empty-icon">🧾</div>
    <h3>No orders yet</h3>
    <p>You haven't placed any orders yet.<br>Head to the menu and start your royal feast.</p>
    <a href="orders.php" class="btn-go">Browse the Menu</a>
</div>
<?php endif; ?>

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

/* Scroll reveal */
const obs = new IntersectionObserver(entries=>{
    entries.forEach(e=>{ if(e.isIntersecting) e.target.classList.add('visible'); });
},{threshold:0.05});
document.querySelectorAll('.order-card').forEach(c=>obs.observe(c));

/* Accordion */
function toggleOrder(id){
    document.getElementById('order-'+id).classList.toggle('open');
}

/* Auto-open first */
window.addEventListener('DOMContentLoaded',()=>{
    const first = document.querySelector('.order-card');
    if(first) setTimeout(()=>first.classList.add('open'), 500);
});

/* Toast */
const toast = document.getElementById('actionToast');
if(toast){
    setTimeout(()=>toast.classList.add('show'), 100);
    setTimeout(()=>toast.classList.remove('show'), 4000);
}

/* Modal */
function openModal(orderId, action, icon, title, msg, confirmClass, confirmLabel){
    document.getElementById('modalIcon').textContent      = icon;
    document.getElementById('modalTitle').textContent     = title;
    document.getElementById('modalMsg').textContent       = msg;
    document.getElementById('modalOrderId').value         = orderId;
    document.getElementById('modalAction').value          = action;
    const btn = document.getElementById('modalConfirmBtn');
    btn.className = 'modal-confirm ' + (confirmClass || '');
    btn.textContent = confirmLabel;
    document.getElementById('modalOverlay').classList.add('open');
}
function closeModal(){
    document.getElementById('modalOverlay').classList.remove('open');
}
// Close on backdrop click
document.getElementById('modalOverlay').addEventListener('click', function(e){
    if(e.target === this) closeModal();
});
// Close on Escape
document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeModal(); });
</script>
</body>
</html>