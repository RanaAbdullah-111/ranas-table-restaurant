<?php
include_once("../config.php");
require_once("../auth.php");
require_admin();

/*
|--------------------------------------------------------------------------
| FETCH STATS
|--------------------------------------------------------------------------
*/
$total_orders     = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as c FROM orders"))['c'] ?? 0;
$orders_pending   = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as c FROM orders WHERE order_status='pending' OR order_status='' OR order_status IS NULL"))['c'] ?? 0;
$orders_processing= mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as c FROM orders WHERE order_status='processing'"))['c'] ?? 0;
$orders_delivered = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as c FROM orders WHERE order_status='delivered'"))['c'] ?? 0;
$orders_cancelled = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as c FROM orders WHERE order_status='cancelled'"))['c'] ?? 0;

$revenue_row      = mysqli_fetch_assoc(mysqli_query($connection, "SELECT SUM(total_amount) as total FROM orders WHERE order_status != 'cancelled'"));
$total_revenue    = $revenue_row['total'] ?? 0;
$today_revenue_row= mysqli_fetch_assoc(mysqli_query($connection, "SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_at)=CURDATE() AND order_status!='cancelled'"));
$today_revenue    = $today_revenue_row['total'] ?? 0;

$total_users      = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as c FROM uesr"))['c'] ?? 0;
$total_products   = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as c FROM product"))['c'] ?? 0;
$total_cats       = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as c FROM category"))['c'] ?? 0;
$today_orders     = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as c FROM orders WHERE DATE(created_at)=CURDATE()"))['c'] ?? 0;

$top_products_result = mysqli_query($connection, "
    SELECT product.product_name, product.product_image,
           SUM(order_items.quantity) as total_sold,
           SUM(order_items.price * order_items.quantity) as revenue
    FROM order_items
    JOIN product ON order_items.product_id = product.id
    GROUP BY order_items.product_id
    ORDER BY total_sold DESC LIMIT 5
");
$top_products = [];
if($top_products_result) while($r = mysqli_fetch_assoc($top_products_result)) $top_products[] = $r;

$recent_orders_result = mysqli_query($connection, "
    SELECT orders.*, COUNT(order_items.id) as item_count
    FROM orders
    LEFT JOIN order_items ON orders.id = order_items.order_id
    GROUP BY orders.id
    ORDER BY orders.id DESC LIMIT 8
");
$recent_orders = [];
if($recent_orders_result) while($r = mysqli_fetch_assoc($recent_orders_result)) $recent_orders[] = $r;

$chart_data = [];
for($d = 6; $d >= 0; $d--){
    $date  = date('Y-m-d', strtotime("-$d days"));
    $label = date('D', strtotime("-$d days"));
    $rev   = mysqli_fetch_assoc(mysqli_query($connection, "SELECT SUM(total_amount) as t FROM orders WHERE DATE(created_at)='$date' AND order_status!='cancelled'"))['t'] ?? 0;
    $chart_data[] = ['label'=>$label, 'value'=>(float)$rev];
}
$chart_max = max(array_column($chart_data,'value') ?: [1]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Rana's Table Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Cormorant+Garamond:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        :root {
            --gold:        #C9A84C;
            --gold-lite:   #E8C96A;
            --deep:        #110600;
            --sidebar-w:   250px;  /* must match admin_navbar.php */
            --topbar-h:    64px;
            --card-bg:     rgba(28,11,0,0.85);
            --card-border: rgba(201,168,76,0.14);
            --cream:       #F5EDD6;
            --cream-dim:   rgba(245,237,214,0.50);
            --pending:     #e0a93a;
            --delivered:   #5ab97a;
            --cancelled:   #e05c3a;
            --processing:  #6ab4e8;
        }

        html { scroll-behavior:smooth; }

        body {
            font-family:'Cormorant Garamond',serif;
            background:var(--deep); color:var(--cream);
            min-height:100vh;
            /* sidebar is fixed — body just scrolls the right side */
        }

        .bg-fixed { position:fixed; inset:0; z-index:0; pointer-events:none; background:radial-gradient(ellipse 90% 60% at 50% -10%,#2e1000 0%,#110600 60%); }

        .ember { position:fixed; border-radius:50%; pointer-events:none; opacity:0; animation:floatUp linear infinite; z-index:0; }
        @keyframes floatUp { 0%{transform:translateY(0) translateX(0);opacity:0} 8%{opacity:.5} 90%{opacity:.08} 100%{transform:translateY(-100vh) translateX(var(--d));opacity:0} }

        /* ── MAIN WRAPPER ──
           Sits to the RIGHT of the fixed sidebar.
           admin_navbar.php renders its own sticky topbar inside this margin.
        ── */
        .dash-main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.4s cubic-bezier(0.16,1,0.3,1);
            position: relative;
            z-index: 1;
        }
        /* when sidebar is collapsed by admin_navbar.js */
        body.sidebar-collapsed .dash-main { margin-left: 0; }

        /* ── CONTENT ── */
        .content { padding:32px; flex:1; }

        @keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }

        /* WELCOME */
        .welcome-strip { margin-bottom:28px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; animation:fadeUp 0.6s 0.1s both; }
        .welcome-text h1 { font-family:'Playfair Display',serif; font-size:28px; font-weight:400; color:var(--cream); }
        .welcome-text h1 em { color:var(--gold); font-style:italic; }
        .welcome-text p { font-size:14px; font-weight:300; color:var(--cream-dim); margin-top:4px; }
        .welcome-date { font-size:13px; font-weight:300; color:rgba(201,168,76,0.5); letter-spacing:0.5px; }

        /* STAT CARDS */
        .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:2px; margin-bottom:24px; animation:fadeUp 0.6s 0.15s both; }
        .stat-card { background:var(--card-bg); border:0.5px solid var(--card-border); padding:24px 24px 20px; position:relative; overflow:hidden; transition:border-color 0.3s,transform 0.3s; }
        .stat-card:hover { border-color:rgba(201,168,76,0.3); transform:translateY(-2px); }
        .stat-card::after { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(201,168,76,0.04),transparent); opacity:0; transition:opacity 0.3s; }
        .stat-card:hover::after { opacity:1; }
        .sc-icon  { font-size:22px; margin-bottom:14px; display:block; }
        .sc-value { font-family:'Playfair Display',serif; font-size:32px; line-height:1; color:var(--cream); margin-bottom:6px; }
        .sc-value.gold { color:var(--gold); }
        .sc-label { font-size:11px; letter-spacing:3px; text-transform:uppercase; color:var(--cream-dim); }
        .sc-sub   { font-size:12px; font-weight:300; margin-top:8px; color:rgba(201,168,76,0.45); }
        .sc-accent { position:absolute; bottom:0; left:0; right:0; height:2px; }

        /* STATUS ROW */
        .status-row { display:grid; grid-template-columns:repeat(4,1fr); gap:2px; margin-bottom:24px; animation:fadeUp 0.6s 0.2s both; }
        .status-mini { background:var(--card-bg); border:0.5px solid var(--card-border); padding:16px 20px; display:flex; align-items:center; gap:14px; transition:border-color 0.3s; }
        .status-mini:hover { border-color:rgba(201,168,76,0.25); }
        .sm-dot  { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
        .sm-info { flex:1; }
        .sm-label{ font-size:10px; letter-spacing:3px; text-transform:uppercase; color:var(--cream-dim); margin-bottom:3px; }
        .sm-val  { font-family:'Playfair Display',serif; font-size:22px; color:var(--cream); }

        /* QUICK ACTIONS */
        .quick-actions { display:grid; grid-template-columns:repeat(4,1fr); gap:2px; margin-bottom:24px; animation:fadeUp 0.6s 0.25s both; }
        .qa-card { background:var(--card-bg); border:0.5px solid var(--card-border); padding:22px 20px; text-align:center; text-decoration:none; transition:all 0.3s; display:flex; flex-direction:column; align-items:center; gap:10px; }
        .qa-card:hover { border-color:rgba(201,168,76,0.35); background:rgba(201,168,76,0.06); transform:translateY(-2px); }
        .qa-icon { font-size:28px; color:rgba(201,168,76,0.55); transition:color 0.3s; }
        .qa-card:hover .qa-icon { color:var(--gold); }
        .qa-label{ font-size:11px; letter-spacing:3px; text-transform:uppercase; color:var(--cream-dim); }

        /* TWO COL */
        .two-col { display:grid; grid-template-columns:1fr 340px; gap:20px; margin-bottom:24px; animation:fadeUp 0.6s 0.3s both; }

        /* PANEL */
        .panel { background:var(--card-bg); border:0.5px solid var(--card-border); }
        .panel-head { padding:18px 24px; border-bottom:0.5px solid rgba(201,168,76,0.08); display:flex; align-items:center; justify-content:space-between; }
        .panel-title { font-family:'Playfair Display',serif; font-size:20px; font-weight:400; color:var(--cream); }
        .panel-link  { font-size:12px; letter-spacing:2px; text-transform:uppercase; color:rgba(201,168,76,0.5); text-decoration:none; transition:color 0.2s; }
        .panel-link:hover { color:var(--gold); }

        /* ORDERS TABLE */
        .orders-table { width:100%; border-collapse:collapse; }
        .orders-table th { font-size:10px; letter-spacing:3px; text-transform:uppercase; color:rgba(201,168,76,0.4); font-weight:500; padding:10px 20px; border-bottom:0.5px solid rgba(201,168,76,0.07); text-align:left; white-space:nowrap; }
        .orders-table td { padding:13px 20px; border-bottom:0.5px solid rgba(201,168,76,0.05); font-size:14px; font-weight:300; color:var(--cream-dim); vertical-align:middle; }
        .orders-table tr:last-child td { border-bottom:none; }
        .orders-table tr:hover td { background:rgba(201,168,76,0.03); }
        .order-id       { font-family:'Playfair Display',serif; font-size:15px; color:var(--cream); }
        .order-customer { font-size:14px; color:var(--cream); }
        .order-amt      { font-size:15px; color:var(--gold); font-weight:500; }
        .s-pill { font-size:9px; font-weight:700; letter-spacing:2px; text-transform:uppercase; padding:4px 10px; border:0.5px solid; }
        .s-pending    { color:var(--pending);    border-color:rgba(224,169,58,0.35);  background:rgba(224,169,58,0.07); }
        .s-processing { color:var(--processing); border-color:rgba(106,180,232,0.35); background:rgba(106,180,232,0.07); }
        .s-delivered  { color:var(--delivered);  border-color:rgba(90,185,122,0.35);  background:rgba(90,185,122,0.07); }
        .s-cancelled  { color:var(--cancelled);  border-color:rgba(224,92,58,0.35);   background:rgba(224,92,58,0.07); }

        /* CHART */
        .chart-wrap  { padding:20px 24px 16px; }
        .chart-bars  { display:flex; align-items:flex-end; gap:8px; height:120px; margin-bottom:8px; }
        .chart-col   { flex:1; display:flex; flex-direction:column; align-items:center; gap:5px; height:100%; justify-content:flex-end; }
        .chart-bar-wrap { width:100%; flex:1; display:flex; align-items:flex-end; }
        .chart-bar   { width:100%; background:rgba(201,168,76,0.18); border-top:1.5px solid rgba(201,168,76,0.5); transition:height 0.8s cubic-bezier(0.16,1,0.3,1); position:relative; cursor:pointer; }
        .chart-bar:hover { background:rgba(201,168,76,0.32); border-color:var(--gold); }
        .chart-bar .bar-tip { position:absolute; bottom:calc(100% + 6px); left:50%; transform:translateX(-50%); background:rgba(10,4,0,0.9); border:0.5px solid rgba(201,168,76,0.3); padding:4px 8px; font-size:11px; color:var(--gold); white-space:nowrap; opacity:0; pointer-events:none; transition:opacity 0.2s; }
        .chart-bar:hover .bar-tip { opacity:1; }
        .chart-day   { font-size:10px; letter-spacing:1px; text-transform:uppercase; color:rgba(201,168,76,0.35); }

        /* TOP PRODUCTS */
        .top-prod-item { display:flex; align-items:center; gap:14px; padding:13px 24px; border-bottom:0.5px solid rgba(201,168,76,0.05); transition:background 0.2s; }
        .top-prod-item:last-child { border-bottom:none; }
        .top-prod-item:hover { background:rgba(201,168,76,0.03); }
        .tp-rank { width:22px; font-size:12px; color:rgba(201,168,76,0.35); text-align:center; flex-shrink:0; }
        .tp-rank.gold-rank { color:var(--gold); font-weight:700; font-size:14px; }
        .tp-img  { width:44px; height:44px; flex-shrink:0; overflow:hidden; background:rgba(45,21,0,0.5); border:0.5px solid rgba(201,168,76,0.12); }
        .tp-img img { width:100%; height:100%; object-fit:cover; display:block; }
        .tp-img-ph { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:18px; opacity:.2; }
        .tp-info { flex:1; }
        .tp-name { font-size:15px; color:var(--cream); font-weight:400; }
        .tp-sold { font-size:12px; color:var(--cream-dim); margin-top:2px; }
        .tp-rev  { font-size:14px; color:var(--gold); white-space:nowrap; font-weight:500; }

        /* FOOTER */
        footer { position:relative; z-index:1; border-top:0.5px solid rgba(201,168,76,0.08); padding:20px 32px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
        footer .f-brand { font-family:'Playfair Display',serif; font-size:16px; color:var(--gold); }
        footer .f-note  { font-size:12px; font-weight:300; color:rgba(245,237,214,.22); }

        /* RESPONSIVE */
        @media(max-width:900px){
            .dash-main { margin-left:0 !important; }
            .stats-grid    { grid-template-columns:1fr 1fr; }
            .status-row    { grid-template-columns:1fr 1fr; }
            .quick-actions { grid-template-columns:1fr 1fr; }
            .two-col       { grid-template-columns:1fr; }
            .content       { padding:20px 16px; }
        }
        @media(max-width:560px){
            .stats-grid { grid-template-columns:1fr; }
            .status-row { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>

<div class="bg-fixed"></div>

<?php
/*
 * admin_navbar.php renders:
 *   1. The fixed sidebar (position:fixed, left:0)
 *   2. The sticky topbar (position:sticky, top:0) — INSIDE .dash-main below
 * So we include it BEFORE .dash-main but its topbar uses margin-left via CSS.
 */
require("admin_navbar.php");
?>

<!-- Everything to the right of the sidebar -->
<div class="dash-main" id="dashMain">

    <!-- The sticky topbar is already rendered by admin_navbar.php
         and its margin-left matches --sidebar-w automatically. -->

    <div class="content">

        <!-- WELCOME -->
        <div class="welcome-strip">
            <div class="welcome-text">
                <h1>Good <?php echo (date('H')<12)?'Morning':((date('H')<17)?'Afternoon':'Evening'); ?>, <em>Admin</em> 👋</h1>
                <p>Here's what's happening at Rana's Table today.</p>
            </div>
            <div class="welcome-date"><?php echo date('l, d F Y'); ?></div>
        </div>

        <!-- STAT CARDS -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="sc-icon">💰</span>
                <div class="sc-value gold">PKR <?php echo number_format($total_revenue); ?></div>
                <div class="sc-label">Total Revenue</div>
                <div class="sc-sub">Today: PKR <?php echo number_format($today_revenue); ?></div>
                <div class="sc-accent" style="background:linear-gradient(to right,var(--gold),transparent)"></div>
            </div>
            <div class="stat-card">
                <span class="sc-icon">📦</span>
                <div class="sc-value"><?php echo $total_orders; ?></div>
                <div class="sc-label">Total Orders</div>
                <div class="sc-sub">Today: <?php echo $today_orders; ?> new</div>
                <div class="sc-accent" style="background:linear-gradient(to right,var(--processing),transparent)"></div>
            </div>
            <div class="stat-card">
                <span class="sc-icon">👥</span>
                <div class="sc-value"><?php echo $total_users; ?></div>
                <div class="sc-label">Customers</div>
                <div class="sc-sub">&nbsp;</div>
                <div class="sc-accent" style="background:linear-gradient(to right,var(--delivered),transparent)"></div>
            </div>
            <div class="stat-card">
                <span class="sc-icon">🍽</span>
                <div class="sc-value"><?php echo $total_products; ?></div>
                <div class="sc-label">Menu Items</div>
                <div class="sc-sub"><?php echo $total_cats; ?> categories</div>
                <div class="sc-accent" style="background:linear-gradient(to right,var(--pending),transparent)"></div>
            </div>
        </div>

        <!-- STATUS ROW -->
        <div class="status-row">
            <div class="status-mini">
                <div class="sm-dot" style="background:var(--pending)"></div>
                <div class="sm-info"><div class="sm-label">Pending</div><div class="sm-val"><?php echo $orders_pending; ?></div></div>
            </div>
            <div class="status-mini">
                <div class="sm-dot" style="background:var(--processing)"></div>
                <div class="sm-info"><div class="sm-label">Processing</div><div class="sm-val"><?php echo $orders_processing; ?></div></div>
            </div>
            <div class="status-mini">
                <div class="sm-dot" style="background:var(--delivered)"></div>
                <div class="sm-info"><div class="sm-label">Delivered</div><div class="sm-val"><?php echo $orders_delivered; ?></div></div>
            </div>
            <div class="status-mini">
                <div class="sm-dot" style="background:var(--cancelled)"></div>
                <div class="sm-info"><div class="sm-label">Cancelled</div><div class="sm-val"><?php echo $orders_cancelled; ?></div></div>
            </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="quick-actions">
            <a href="product.php"      class="qa-card"><i class="bi bi-plus-circle-fill qa-icon"></i><span class="qa-label">Add Product</span></a>
            <a href="category.php"     class="qa-card"><i class="bi bi-tag-fill qa-icon"></i><span class="qa-label">Add Category</span></a>
            <a href="orders_admin.php" class="qa-card"><i class="bi bi-bag-check-fill qa-icon"></i><span class="qa-label">View Orders</span></a>
            <a href="users_admin.php"  class="qa-card"><i class="bi bi-people-fill qa-icon"></i><span class="qa-label">Customers</span></a>
        </div>

        <!-- RECENT ORDERS + CHART -->
        <div class="two-col">

            <!-- Recent Orders -->
            <div class="panel">
                <div class="panel-head">
                    <div class="panel-title">Recent Orders</div>
                    <a href="orders_admin.php" class="panel-link">View All →</a>
                </div>
                <div>
                    <?php if(!empty($recent_orders)): ?>
                    <table class="orders-table">
                        <thead><tr>
                            <th>Order</th><th>Customer</th><th>Items</th><th>Amount</th><th>Status</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach($recent_orders as $o):
                            $st = strtolower($o['order_status'] ?? 'pending');
                            $sc = match($st){ 'delivered'=>'s-delivered','processing'=>'s-processing','cancelled'=>'s-cancelled',default=>'s-pending' };
                            $sl = match($st){ 'delivered'=>'Delivered','processing'=>'Processing','cancelled'=>'Cancelled',default=>'Pending' };
                        ?>
                        <tr>
                            <td><span class="order-id">#<?php echo str_pad($o['id'],4,'0',STR_PAD_LEFT); ?></span></td>
                            <td><span class="order-customer"><?php echo htmlspecialchars($o['customer_name']); ?></span></td>
                            <td><?php echo $o['item_count']; ?></td>
                            <td><span class="order-amt">PKR <?php echo number_format($o['total_amount']); ?></span></td>
                            <td><span class="s-pill <?php echo $sc; ?>"><?php echo $sl; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align:center;padding:40px;color:var(--cream-dim);font-size:15px;font-weight:300;">No orders yet.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right column -->
            <div style="display:flex;flex-direction:column;gap:20px;">

                <!-- 7-day chart -->
                <div class="panel">
                    <div class="panel-head"><div class="panel-title">Revenue — 7 Days</div></div>
                    <div class="chart-wrap">
                        <div class="chart-bars">
                            <?php foreach($chart_data as $cd):
                                $pct = $chart_max > 0 ? ($cd['value'] / $chart_max * 100) : 0;
                            ?>
                            <div class="chart-col">
                                <div class="chart-bar-wrap">
                                    <div class="chart-bar" data-pct="<?php echo $pct; ?>" style="height:0%">
                                        <div class="bar-tip">PKR <?php echo number_format($cd['value']); ?></div>
                                    </div>
                                </div>
                                <div class="chart-day"><?php echo $cd['label']; ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Top products -->
                <div class="panel">
                    <div class="panel-head">
                        <div class="panel-title">Top Dishes</div>
                        <a href="product.php" class="panel-link">Manage →</a>
                    </div>
                    <?php if(!empty($top_products)): ?>
                        <?php foreach($top_products as $ti => $tp): ?>
                        <div class="top-prod-item">
                            <div class="tp-rank <?php echo $ti===0?'gold-rank':''; ?>"><?php echo $ti===0?'🥇':($ti+1); ?></div>
                            <div class="tp-img">
                                <?php if(!empty($tp['product_image'])): ?>
                                    <img src="../product_uploads/<?php echo htmlspecialchars($tp['product_image']); ?>" alt="">
                                <?php else: ?><div class="tp-img-ph">🍽</div><?php endif; ?>
                            </div>
                            <div class="tp-info">
                                <div class="tp-name"><?php echo htmlspecialchars($tp['product_name']); ?></div>
                                <div class="tp-sold"><?php echo $tp['total_sold']; ?> sold</div>
                            </div>
                            <div class="tp-rev">PKR <?php echo number_format($tp['revenue']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding:28px;text-align:center;color:var(--cream-dim);font-size:14px;font-weight:300;">No sales data yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /content -->

    <footer>
        <div class="f-brand">Rana's Table</div>
        <div class="f-note">© <?php echo date('Y'); ?> Rana's Table &nbsp;·&nbsp; Admin Dashboard</div>
    </footer>

</div><!-- /dash-main -->

<script>
/* Embers */
const ec=['#C9A84C','#FF8C42','#FFD166'];
for(let i=0;i<12;i++){
    const e=document.createElement('div'); e.className='ember';
    const s=1+Math.random()*2.5, c=ec[Math.floor(Math.random()*ec.length)];
    Object.assign(e.style,{width:s+'px',height:s+'px',background:c,left:(4+Math.random()*92)+'%',bottom:'-8px','--d':((Math.random()-.5)*70)+'px',animationDuration:(10+Math.random()*12)+'s',animationDelay:(Math.random()*20)+'s',boxShadow:`0 0 ${s*2}px ${c}`});
    document.body.appendChild(e);
}

/* Sync sidebar collapse with dash-main margin */
window.addEventListener('DOMContentLoaded', ()=>{
    // admin_navbar.php manages the sidebar toggle.
    // We just listen to the sidebar state and adjust dash-main.
    const dashMain = document.getElementById('dashMain');
    const sidebar  = document.getElementById('admSidebar');

    if(!sidebar || !dashMain) return;

    function syncMargin(){
        const isMobile = window.innerWidth <= 900;
        if(isMobile){
            dashMain.style.marginLeft = '0';
        } else {
            const collapsed = sidebar.classList.contains('collapsed');
            dashMain.style.marginLeft = collapsed ? '0' : 'var(--sidebar-w)';
        }
    }

    // Observe sidebar class changes
    new MutationObserver(syncMargin).observe(sidebar, { attributes:true, attributeFilter:['class'] });
    window.addEventListener('resize', syncMargin);
    syncMargin();
});

/* Animate chart bars */
window.addEventListener('DOMContentLoaded', ()=>{
    setTimeout(()=>{
        document.querySelectorAll('.chart-bar').forEach(bar=>{
            bar.style.height = bar.dataset.pct + '%';
        });
    }, 400);
});
</script>
</body>
</html>