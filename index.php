<?php
session_start();
include("config.php");

/*
|--------------------------------------------------------------------------
| DB — Categories
|--------------------------------------------------------------------------
*/
$cat_result = mysqli_query($connection, "SELECT * FROM category ORDER BY id ASC");
$categories = [];
if($cat_result) while($r = mysqli_fetch_assoc($cat_result)) $categories[] = $r;

/*
|--------------------------------------------------------------------------
| DB — Bestsellers (top 8 by order_items quantity sold)
|--------------------------------------------------------------------------
*/
$best_result = mysqli_query($connection, "
    SELECT product.*,
           category.category_name,
           COALESCE(SUM(order_items.quantity),0) AS total_sold
    FROM product
    LEFT JOIN order_items ON product.id = order_items.product_id
    LEFT JOIN category    ON product.category_id = category.id
    GROUP BY product.id
    ORDER BY total_sold DESC, product.id ASC
    LIMIT 8
");
$bestsellers = [];
if($best_result) while($r = mysqli_fetch_assoc($best_result)) $bestsellers[] = $r;

/*
|--------------------------------------------------------------------------
| DB — Stats
|--------------------------------------------------------------------------
*/
$total_products = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as c FROM product"))['c'] ?? 0;
$total_orders   = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as c FROM orders"))['c'] ?? 0;
$total_users    = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as c FROM uesr"))['c'] ?? 0;

// Average rating placeholder — adapt if you have a reviews table
$avg_rating = "4.9";

/*
|--------------------------------------------------------------------------
| Auth state
|--------------------------------------------------------------------------
*/
$is_logged_in = isset($_SESSION['user']);
$order_link   = $is_logged_in ? "user/orders.php"  : "login.php";
$account_link = $is_logged_in ? "user/my_orders.php": "user.php";
$account_label= $is_logged_in ? "user/My Orders"    : "Create Account";

// Category icon map (emoji fallback if no image)
$cat_icons = ['🍚','🍖','🫕','🍞','🍮','🥛','🥩','🍲','🍢','🎂'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rana's Table — Fine Pakistani Cuisine, Delivered</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --gold:#C9A84C; --gold2:#E8C96A; --gold-dim:rgba(201,168,76,0.14);
  --deep:#0C0400;  --char:#1A0800;  --warm:#2E1500;
  --cream:#F5EDD6; --cream2:rgba(245,237,214,0.70); --cream3:rgba(245,237,214,0.38);
  --nav-h:70px;
}
html{scroll-behavior:smooth}
body{font-family:'Cormorant Garamond',serif;background:var(--deep);color:var(--cream);overflow-x:hidden}

/* SCROLLBAR */
::-webkit-scrollbar{width:4px}
::-webkit-scrollbar-track{background:var(--char)}
::-webkit-scrollbar-thumb{background:rgba(201,168,76,0.4);border-radius:2px}

/* EMBERS */
.ember{position:fixed;border-radius:50%;pointer-events:none;opacity:0;animation:floatUp linear infinite;z-index:0}
@keyframes floatUp{0%{transform:translateY(0) translateX(0);opacity:0}8%{opacity:.6}88%{opacity:.07}100%{transform:translateY(-100vh) translateX(var(--dx));opacity:0}}

/* ─── NAV ─── */
nav{
  position:fixed;top:0;left:0;right:0;z-index:200;
  height:var(--nav-h);display:flex;align-items:center;justify-content:space-between;
  padding:0 48px;
  transition:background .5s,border-color .5s,backdrop-filter .5s;
  border-bottom:0.5px solid transparent;
}
nav.scrolled{background:rgba(8,2,0,0.94);border-bottom-color:rgba(201,168,76,0.12);backdrop-filter:blur(18px)}
.nav-logo{display:flex;align-items:center;gap:12px;text-decoration:none}
.nav-logo-ring{width:38px;height:38px;border:0.5px solid rgba(201,168,76,0.5);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.nav-logo-r{font-family:'Playfair Display',serif;font-size:18px;font-weight:700;background:linear-gradient(135deg,#C9A84C,#F0D08A);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.nav-brand{font-family:'Playfair Display',serif;font-size:18px;color:var(--cream)}
.nav-brand em{color:var(--gold);font-style:italic}
.nav-links{display:flex;align-items:center;gap:32px}
.nav-links a{font-size:12px;font-weight:300;letter-spacing:2.5px;text-transform:uppercase;color:var(--cream3);text-decoration:none;transition:color .2s;position:relative}
.nav-links a::after{content:'';position:absolute;bottom:-3px;left:0;right:0;height:0.5px;background:var(--gold);transform:scaleX(0);transition:transform .3s}
.nav-links a:hover{color:var(--cream)}
.nav-links a:hover::after{transform:scaleX(1)}
.nav-right{display:flex;align-items:center;gap:14px}
.nav-user-hi{font-size:12px;font-weight:300;color:var(--cream3);letter-spacing:0.5px}
.nav-user-hi strong{color:var(--gold);font-weight:400}
.nav-cta{padding:9px 22px;border:0.5px solid rgba(201,168,76,0.45);color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:12px;letter-spacing:3px;text-transform:uppercase;text-decoration:none;transition:all .25s}
.nav-cta:hover{background:var(--gold);color:var(--deep)}
.nav-cta.solid{background:var(--gold);color:var(--deep)}
.nav-cta.solid:hover{background:var(--gold2)}
.nav-mob-btn{display:none;background:none;border:none;color:var(--cream);font-size:22px;cursor:pointer}
.mob-menu{display:none;position:fixed;inset:0;background:rgba(8,2,0,0.98);z-index:300;flex-direction:column;align-items:center;justify-content:center;gap:28px}
.mob-menu.open{display:flex}
.mob-menu a{font-family:'Playfair Display',serif;font-size:24px;color:var(--cream);text-decoration:none;transition:color .2s}
.mob-menu a:hover{color:var(--gold)}
.mob-close{position:absolute;top:24px;right:28px;background:none;border:none;color:var(--cream3);font-size:26px;cursor:pointer}

/* ─── HERO ─── */
.hero{position:relative;height:100vh;min-height:640px;display:flex;align-items:center;justify-content:center;text-align:center;overflow:hidden}
.hero-bg{position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 50% 30%,rgba(80,28,0,.9) 0%,rgba(12,4,0,.97) 70%),repeating-linear-gradient(0deg,transparent,transparent 80px,rgba(201,168,76,.018) 80px,rgba(201,168,76,.018) 81px),repeating-linear-gradient(90deg,transparent,transparent 80px,rgba(201,168,76,.018) 80px,rgba(201,168,76,.018) 81px)}
.hero-ring{position:absolute;width:560px;height:560px;border-radius:50%;border:0.5px solid rgba(201,168,76,.1);top:50%;left:50%;transform:translate(-50%,-50%);animation:ringPulse 4s ease-in-out infinite}
.hero-ring2{position:absolute;width:760px;height:760px;border-radius:50%;border:0.5px solid rgba(201,168,76,.05);top:50%;left:50%;transform:translate(-50%,-50%);animation:ringPulse 4s ease-in-out 1s infinite}
@keyframes ringPulse{0%,100%{opacity:.5;transform:translate(-50%,-50%) scale(1)}50%{opacity:1;transform:translate(-50%,-50%) scale(1.02)}}
.hero-content{position:relative;z-index:1;padding:0 20px;max-width:800px}
.hero-eyebrow{font-size:10px;letter-spacing:6px;text-transform:uppercase;color:var(--gold);margin-bottom:24px;display:flex;align-items:center;justify-content:center;gap:16px;animation:fadeUp .8s .2s both}
.hero-eyebrow::before,.hero-eyebrow::after{content:'';flex:80px 0 0;height:0.5px;background:linear-gradient(to right,transparent,rgba(201,168,76,.5))}
.hero-eyebrow::after{transform:rotate(180deg)}
.hero-h1{font-family:'Playfair Display',serif;font-size:clamp(52px,9vw,96px);line-height:1;font-weight:400;color:var(--cream);margin-bottom:6px;animation:fadeUp .8s .35s both}
.hero-h1 em{color:var(--gold);font-style:italic}
.hero-sub{font-size:clamp(18px,2.5vw,22px);font-weight:300;color:var(--cream2);letter-spacing:1px;margin-bottom:40px;animation:fadeUp .8s .5s both}
.hero-btns{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;animation:fadeUp .8s .65s both}
.btn-primary{padding:15px 38px;background:var(--gold);color:var(--deep);font-family:'Cormorant Garamond',serif;font-size:13px;font-weight:500;letter-spacing:4px;text-transform:uppercase;text-decoration:none;position:relative;overflow:hidden;transition:all .35s;display:inline-block}
.btn-primary::before{content:'';position:absolute;inset:0;background:linear-gradient(120deg,transparent 30%,rgba(255,255,255,.25) 50%,transparent 70%);transform:translateX(-100%);transition:transform .55s}
.btn-primary:hover::before{transform:translateX(100%)}
.btn-primary:hover{background:var(--gold2);transform:translateY(-2px);box-shadow:0 12px 40px rgba(201,168,76,.28)}
.btn-outline{padding:15px 38px;border:0.5px solid rgba(201,168,76,.45);color:var(--cream);font-family:'Cormorant Garamond',serif;font-size:13px;font-weight:300;letter-spacing:4px;text-transform:uppercase;text-decoration:none;transition:all .25s;display:inline-block}
.btn-outline:hover{border-color:var(--gold);color:var(--gold)}
.hero-scroll{position:absolute;bottom:36px;left:50%;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;gap:8px;color:var(--cream3);font-size:10px;letter-spacing:3px;text-transform:uppercase;animation:fadeUp .8s .9s both}
.hero-scroll-line{width:0.5px;height:40px;background:linear-gradient(to bottom,rgba(201,168,76,.5),transparent);animation:scrollPulse 1.8s ease-in-out infinite}
@keyframes scrollPulse{0%,100%{opacity:.4;transform:scaleY(1)}50%{opacity:1;transform:scaleY(1.1)}}

/* ─── STATS STRIP ─── */
.stats-strip{border-top:0.5px solid rgba(201,168,76,.12);border-bottom:0.5px solid rgba(201,168,76,.12);display:grid;grid-template-columns:repeat(4,1fr);background:rgba(20,8,0,.7)}
.stat-item{padding:32px 20px;text-align:center;border-right:0.5px solid rgba(201,168,76,.08);transition:background .3s}
.stat-item:last-child{border-right:none}
.stat-item:hover{background:rgba(201,168,76,.04)}
.stat-n{font-family:'Playfair Display',serif;font-size:38px;font-weight:400;color:var(--gold);line-height:1}
.stat-l{font-size:10px;letter-spacing:3px;text-transform:uppercase;color:var(--cream3);margin-top:6px}

/* ─── MARQUEE ─── */
.marquee-section{padding:26px 0;border-top:0.5px solid rgba(201,168,76,.1);border-bottom:0.5px solid rgba(201,168,76,.1);background:rgba(8,3,0,.8);overflow:hidden}
.marquee-track{display:flex;gap:48px;white-space:nowrap;animation:marquee 22s linear infinite}
.marquee-track:hover{animation-play-state:paused}
.marquee-item{display:flex;align-items:center;gap:16px;font-size:12px;letter-spacing:4px;text-transform:uppercase;color:rgba(201,168,76,.45);flex-shrink:0}
.marquee-gem{width:5px;height:5px;background:var(--gold);transform:rotate(45deg);opacity:.4}
@keyframes marquee{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}

/* ─── SECTION COMMON ─── */
.section{padding:96px 48px}
.section-eyebrow{font-size:10px;letter-spacing:5px;text-transform:uppercase;color:rgba(201,168,76,.6);margin-bottom:14px}
.section-title{font-family:'Playfair Display',serif;font-size:clamp(32px,4vw,52px);font-weight:400;line-height:1.15;color:var(--cream);margin-bottom:18px}
.section-title em{color:var(--gold);font-style:italic}
.section-sub{font-size:17px;font-weight:300;color:var(--cream2);line-height:1.8;max-width:580px}
.sec-divider{display:flex;align-items:center;gap:16px;margin:16px 0}
.sec-divider-line{flex:1;height:0.5px;background:linear-gradient(to right,rgba(201,168,76,.3),transparent)}
.sec-divider-gem{width:6px;height:6px;background:var(--gold);transform:rotate(45deg);opacity:.6;flex-shrink:0}

/* ─── ABOUT ─── */
.about-section{display:grid;grid-template-columns:1fr 1fr;background:rgba(14,6,0,.6);border-top:0.5px solid rgba(201,168,76,.08);border-bottom:0.5px solid rgba(201,168,76,.08)}
.about-left{padding:96px 64px;border-right:0.5px solid rgba(201,168,76,.08)}
.about-right{padding:64px;display:grid;grid-template-columns:1fr 1fr;gap:1px;background:rgba(201,168,76,.06)}
.about-feat{background:rgba(14,6,0,.9);padding:36px 28px;transition:background .3s}
.about-feat:hover{background:rgba(28,10,0,.95)}
.about-feat-icon{font-size:28px;margin-bottom:16px;display:block}
.about-feat-title{font-family:'Playfair Display',serif;font-size:18px;color:var(--cream);margin-bottom:8px}
.about-feat-desc{font-size:14px;font-weight:300;color:var(--cream3);line-height:1.7}

/* ─── CATEGORIES ─── */
.cat-section{padding:96px 48px;background:var(--deep)}
.cat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1px;margin-top:52px;background:rgba(201,168,76,.06)}
.cat-item{background:rgba(12,4,0,.95);padding:32px 20px;text-align:center;cursor:pointer;transition:background .3s;text-decoration:none;display:block}
.cat-item:hover{background:rgba(28,10,0,.98)}
.cat-item:hover .cat-icon{transform:translateY(-4px)}
.cat-icon-img{width:64px;height:64px;border-radius:50%;object-fit:cover;display:block;margin:0 auto 14px;border:0.5px solid rgba(201,168,76,.2);transition:transform .3s}
.cat-item:hover .cat-icon-img{transform:translateY(-4px)}
.cat-icon-ph{font-size:40px;display:block;margin-bottom:14px;transition:transform .3s}
.cat-name{font-family:'Playfair Display',serif;font-size:15px;color:var(--cream);margin-bottom:4px}
.cat-count{font-size:11px;letter-spacing:2px;color:var(--cream3)}

/* ─── SLIDER ─── */
.slider-section{padding:96px 0;overflow:hidden;background:var(--char)}
.slider-header{padding:0 48px;margin-bottom:48px;display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:20px}
.slider-nav{display:flex;gap:12px}
.slider-btn{width:46px;height:46px;border:0.5px solid rgba(201,168,76,.3);background:transparent;color:rgba(201,168,76,.6);font-size:18px;cursor:pointer;transition:all .25s;display:flex;align-items:center;justify-content:center}
.slider-btn:hover{border-color:var(--gold);color:var(--gold);background:rgba(201,168,76,.06)}
.slider-track-wrap{overflow:hidden;padding:0 48px}
.slider-track{display:flex;gap:20px;transition:transform .55s cubic-bezier(0.16,1,0.3,1)}

/* ─── FOOD CARD ─── */
.food-card{flex-shrink:0;width:300px;background:rgba(22,8,0,.9);border:0.5px solid rgba(201,168,76,.12);overflow:hidden;position:relative;transition:border-color .3s,transform .3s;cursor:pointer}
.food-card:hover{border-color:rgba(201,168,76,.35);transform:translateY(-4px)}
.food-card-img{width:100%;height:210px;object-fit:cover;display:block;transition:transform .5s}
.food-card:hover .food-card-img{transform:scale(1.04)}
.food-card-img-ph{width:100%;height:210px;display:flex;align-items:center;justify-content:center;font-size:64px;background:linear-gradient(135deg,rgba(46,21,0,.8),rgba(26,8,0,.95));position:relative}
.food-card-img-ph::after{content:'';position:absolute;inset:0;background:linear-gradient(to top,rgba(12,4,0,.6) 0%,transparent 50%)}
.food-card-badge{position:absolute;top:14px;left:14px;font-size:9px;letter-spacing:2px;text-transform:uppercase;padding:4px 10px;background:var(--gold);color:var(--deep);font-weight:600;z-index:1}
.food-card-badge.hot{background:#c0392b;color:var(--cream)}
.food-card-body{padding:22px 20px 20px}
.food-card-cat{font-size:10px;letter-spacing:3px;text-transform:uppercase;color:rgba(201,168,76,.5);margin-bottom:6px}
.food-card-name{font-family:'Playfair Display',serif;font-size:20px;font-weight:400;color:var(--cream);margin-bottom:8px;line-height:1.2}
.food-card-desc{font-size:13px;font-weight:300;color:var(--cream3);line-height:1.6;margin-bottom:16px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.food-card-foot{display:flex;align-items:center;justify-content:space-between}
.food-card-price{font-family:'Playfair Display',serif;font-size:22px;color:var(--gold)}
.food-card-price span{font-size:13px;font-weight:300;color:var(--cream3);font-family:'Cormorant Garamond',serif}
.food-card-add{width:36px;height:36px;border:0.5px solid rgba(201,168,76,.35);background:transparent;color:var(--gold);font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .25s;text-decoration:none}
.food-card-add:hover{background:var(--gold);color:var(--deep)}
.slider-dots{display:flex;gap:8px;justify-content:center;margin-top:36px;padding:0 48px}
.slider-dot{width:6px;height:6px;border-radius:50%;background:rgba(201,168,76,.2);cursor:pointer;transition:all .3s}
.slider-dot.active{background:var(--gold);width:20px;border-radius:3px}

/* ─── FEATURE ROW ─── */
.feature-row{display:grid;grid-template-columns:1fr 1fr;min-height:500px;border-top:0.5px solid rgba(201,168,76,.08)}
.feature-visual{background:linear-gradient(135deg,#2E1500,#0C0400);display:flex;align-items:center;justify-content:center;font-size:120px;position:relative;overflow:hidden}
.feature-visual::after{content:'';position:absolute;inset:0;background:linear-gradient(to right,transparent 60%,rgba(12,4,0,.8))}
.feature-visual img{width:100%;height:100%;object-fit:cover;display:block}
.feature-copy{background:rgba(14,6,0,.9);display:flex;flex-direction:column;justify-content:center;padding:72px 64px}
.feature-copy.flip{order:-1}

/* ─── TESTIMONIALS ─── */
.testi-section{padding:96px 48px;background:var(--char);text-align:center}
.testi-slider{max-width:720px;margin:52px auto 0;position:relative;overflow:hidden}
.testi-track{display:flex;transition:transform .6s cubic-bezier(0.16,1,0.3,1)}
.testi-item{flex-shrink:0;width:100%;padding:0 20px}
.testi-stars{color:var(--gold);font-size:18px;letter-spacing:4px;margin-bottom:24px}
.testi-quote{font-family:'Playfair Display',serif;font-style:italic;font-size:clamp(18px,2.5vw,22px);line-height:1.7;color:var(--cream);margin-bottom:28px}
.testi-author{font-size:12px;letter-spacing:3px;text-transform:uppercase;color:rgba(201,168,76,.55)}

/* ─── CTA ─── */
.cta-section{padding:96px 48px;text-align:center;position:relative;overflow:hidden;background:linear-gradient(135deg,#1A0800,#2E1500,#1A0800);border-top:0.5px solid rgba(201,168,76,.14);border-bottom:0.5px solid rgba(201,168,76,.14)}
.cta-section::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 60% 70% at 50% 50%,rgba(201,168,76,.08),transparent)}
.cta-content{position:relative;z-index:1}
.cta-title{font-family:'Playfair Display',serif;font-size:clamp(36px,5vw,64px);font-weight:400;color:var(--cream);margin-bottom:16px;line-height:1.1}
.cta-title em{color:var(--gold);font-style:italic}
.cta-sub{font-size:18px;font-weight:300;color:var(--cream2);margin-bottom:44px;max-width:520px;margin-left:auto;margin-right:auto;line-height:1.8}

/* ─── FOOTER ─── */
footer{background:rgba(6,2,0,.98);border-top:0.5px solid rgba(201,168,76,.1);padding:72px 48px 32px}
.footer-grid{display:grid;grid-template-columns:1.6fr 1fr 1fr 1fr;gap:48px;margin-bottom:52px}
.footer-brand-name{font-family:'Playfair Display',serif;font-size:24px;color:var(--cream);margin-bottom:14px}
.footer-brand-name em{color:var(--gold);font-style:italic}
.footer-brand-desc{font-size:14px;font-weight:300;color:var(--cream3);line-height:1.8;margin-bottom:20px}
.footer-social{display:flex;gap:12px}
.footer-social a{width:36px;height:36px;border:0.5px solid rgba(201,168,76,.2);display:flex;align-items:center;justify-content:center;color:rgba(201,168,76,.4);font-size:14px;text-decoration:none;transition:all .2s}
.footer-social a:hover{border-color:var(--gold);color:var(--gold)}
.footer-col-title{font-size:10px;letter-spacing:4px;text-transform:uppercase;color:rgba(201,168,76,.5);margin-bottom:20px}
.footer-col a{display:block;font-size:14px;font-weight:300;color:var(--cream3);text-decoration:none;margin-bottom:10px;transition:color .2s}
.footer-col a:hover{color:var(--cream)}
.footer-bottom{border-top:0.5px solid rgba(201,168,76,.07);padding-top:24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;font-size:12px;font-weight:300;color:rgba(245,237,214,.2)}

/* ANIMATIONS */
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.reveal{opacity:0;transform:translateY(24px);transition:opacity .7s,transform .7s}
.reveal.visible{opacity:1;transform:translateY(0)}

/* RESPONSIVE */
@media(max-width:1024px){
  .about-section{grid-template-columns:1fr}
  .about-left{border-right:none;border-bottom:0.5px solid rgba(201,168,76,.08);padding:64px 40px}
  .about-right{padding:40px}
  .footer-grid{grid-template-columns:1fr 1fr;gap:36px}
  .feature-row{grid-template-columns:1fr}
  .feature-visual{min-height:260px}
  .feature-copy.flip{order:0}
}
@media(max-width:768px){
  nav{padding:0 20px}
  .nav-links,.nav-cta,.nav-user-hi{display:none}
  .nav-mob-btn{display:block}
  .section{padding:64px 20px}
  .slider-header{padding:0 20px}
  .slider-track-wrap{padding:0 20px}
  .hero-ring,.hero-ring2{display:none}
  .stats-strip{grid-template-columns:repeat(2,1fr)}
  .cat-grid{grid-template-columns:repeat(2,1fr)}
  .footer-grid{grid-template-columns:1fr}
  .footer-bottom{flex-direction:column;text-align:center}
  .feature-copy{padding:48px 24px}
  .cta-section{padding:64px 20px}
  .testi-section{padding:64px 20px}
}
</style>
</head>
<body>

<div id="embers"></div>

<!-- ══ NAV ══ -->
<?php include("user/navbar.php");  ?>

<!-- ══ HERO ══ -->
<section class="hero" id="home">
  <div class="hero-bg"></div>
  <div class="hero-ring"></div>
  <div class="hero-ring2"></div>
  <div class="hero-content">
    <div class="hero-eyebrow">Est. 2020 &nbsp;·&nbsp; Karachi, Pakistan</div>
    <h1 class="hero-h1">Where Every<br>Bite Tells a<br><em>Story</em></h1>
    <p class="hero-sub">Royal Pakistani cuisine, crafted with heritage spices<br>and delivered to your door</p>
    <div class="hero-btns">
      <a href="<?php echo $order_link; ?>" class="btn-primary">Explore Menu</a>
      <a href="#about" class="btn-outline">Our Story</a>
    </div>
  </div>
  <div class="hero-scroll"><div class="hero-scroll-line"></div>Scroll</div>
</section>

<!-- ══ STATS ══ -->
<div class="stats-strip reveal">
  <div class="stat-item"><div class="stat-n"><?php echo $avg_rating; ?>★</div><div class="stat-l">Avg. Rating</div></div>
  <div class="stat-item"><div class="stat-n"><?php echo number_format($total_orders); ?>+</div><div class="stat-l">Orders Served</div></div>
  <div class="stat-item"><div class="stat-n"><?php echo $total_products; ?></div><div class="stat-l">Menu Items</div></div>
  <div class="stat-item"><div class="stat-n">30<small style="font-size:20px">min</small></div><div class="stat-l">Avg. Delivery</div></div>
</div>

<!-- ══ MARQUEE ══ -->
<div class="marquee-section">
  <div class="marquee-track" id="marqueeTrack">
    <?php
    $marquee_items = ['Biryani','Nihari','Karahi','Haleem','Seekh Kabab','Paya','Chapli Kabab','Sajji','Dal Makhani','Gulab Jamun','Kheer','Lassi'];
    // Print twice for seamless loop
    foreach(array_merge($marquee_items,$marquee_items) as $item):
    ?>
    <div class="marquee-item"><div class="marquee-gem"></div> <?php echo htmlspecialchars($item); ?></div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ══ ABOUT ══ -->
<div class="about-section reveal" id="about">
  <div class="about-left">
    <div class="section-eyebrow">Our Story</div>
    <h2 class="section-title">Rooted in<br><em>Tradition</em></h2>
    <div class="sec-divider"><div class="sec-divider-line"></div><div class="sec-divider-gem"></div></div>
    <p class="section-sub">Rana's Table was born from a single belief — that great Pakistani food should never be a compromise. Every recipe carries the memory of slow-cooked mornings, clay pots, and spices ground by hand.</p>
    <p class="section-sub" style="margin-top:16px">From Lahori Karahi to silky Nihari that simmers through the night, we bring the full depth of our culinary heritage, directly to your table.</p>
    <a href="<?php echo $order_link; ?>" class="btn-primary" style="margin-top:36px;display:inline-block">View Full Menu</a>
  </div>
  <div class="about-right">
    <div class="about-feat"><span class="about-feat-icon">🌶️</span><div class="about-feat-title">Authentic Spices</div><div class="about-feat-desc">Sourced directly from artisan growers across Punjab and KPK.</div></div>
    <div class="about-feat"><span class="about-feat-icon">🔥</span><div class="about-feat-title">Slow Cooked</div><div class="about-feat-desc">Nihari and Haleem cooked for 8–12 hours for depth of flavour.</div></div>
    <div class="about-feat"><span class="about-feat-icon">🌿</span><div class="about-feat-title">Fresh Daily</div><div class="about-feat-desc">No preservatives. Ingredients prepped fresh every morning.</div></div>
    <div class="about-feat"><span class="about-feat-icon">🚴</span><div class="about-feat-title">Fast Delivery</div><div class="about-feat-desc">Hot and packed right — average delivery under 30 minutes.</div></div>
  </div>
</div>

<!-- ══ CATEGORIES FROM DB ══ -->
<section class="cat-section reveal" id="menu">
  <div style="text-align:center">
    <div class="section-eyebrow">Browse by</div>
    <h2 class="section-title">Food <em>Categories</em></h2>
  </div>
  <div class="cat-grid">
    <?php if(!empty($categories)): ?>
      <?php foreach($categories as $i => $cat): ?>
      <a href="<?php echo $is_logged_in ? 'user/orders.php#cat-sec-'.$cat['id'] : 'login.php'; ?>" class="cat-item">
        <?php if(!empty($cat['category_image'])): ?>
          <img class="cat-icon-img" src="uploads/<?php echo htmlspecialchars($cat['category_image']); ?>"
               alt="<?php echo htmlspecialchars($cat['category_name']); ?>">
        <?php else: ?>
          <span class="cat-icon-ph"><?php echo $cat_icons[$i % count($cat_icons)]; ?></span>
        <?php endif; ?>
        <div class="cat-name"><?php echo htmlspecialchars($cat['category_name']); ?></div>
        <?php if(!empty($cat['category_details'])): ?>
        <div class="cat-count"><?php echo htmlspecialchars(mb_substr($cat['category_details'],0,28)); ?>…</div>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    <?php else: ?>
      <!-- fallback static items if DB is empty -->
      <a href="<?php echo $order_link; ?>" class="cat-item"><span class="cat-icon-ph">🍚</span><div class="cat-name">Biryani</div></a>
      <a href="<?php echo $order_link; ?>" class="cat-item"><span class="cat-icon-ph">🍖</span><div class="cat-name">BBQ & Kabab</div></a>
      <a href="<?php echo $order_link; ?>" class="cat-item"><span class="cat-icon-ph">🫕</span><div class="cat-name">Karahi</div></a>
      <a href="<?php echo $order_link; ?>" class="cat-item"><span class="cat-icon-ph">🍮</span><div class="cat-name">Desserts</div></a>
      <a href="<?php echo $order_link; ?>" class="cat-item"><span class="cat-icon-ph">🍞</span><div class="cat-name">Breads</div></a>
      <a href="<?php echo $order_link; ?>" class="cat-item"><span class="cat-icon-ph">🥛</span><div class="cat-name">Drinks</div></a>
    <?php endif; ?>
  </div>
</section>

<!-- ══ BESTSELLERS FROM DB ══ -->
<section class="slider-section" id="specials">
  <div class="slider-header">
    <div>
      <div class="section-eyebrow">Most Loved</div>
      <h2 class="section-title" style="margin-bottom:0">Bestseller <em>Dishes</em></h2>
    </div>
    <div class="slider-nav">
      <button class="slider-btn" id="sliderPrev"><i class="bi bi-arrow-left"></i></button>
      <button class="slider-btn" id="sliderNext"><i class="bi bi-arrow-right"></i></button>
    </div>
  </div>

  <div class="slider-track-wrap">
    <div class="slider-track" id="sliderTrack">
      <?php if(!empty($bestsellers)): ?>
        <?php foreach($bestsellers as $i => $p): ?>
        <div class="food-card" onclick="window.location='<?php echo $order_link; ?>'">
          <?php if(!empty($p['product_image'])): ?>
            <img class="food-card-img" src="product_uploads/<?php echo htmlspecialchars($p['product_image']); ?>"
                 alt="<?php echo htmlspecialchars($p['product_name']); ?>">
          <?php else: ?>
            <div class="food-card-img-ph">🍽</div>
          <?php endif; ?>

          <?php if($p['total_sold'] > 0): ?>
          <div class="food-card-badge">Best Seller</div>
          <?php elseif($i < 2): ?>
          <div class="food-card-badge hot">New</div>
          <?php endif; ?>

          <div class="food-card-body">
            <div class="food-card-cat"><?php echo htmlspecialchars($p['category_name'] ?? 'Dish'); ?></div>
            <div class="food-card-name"><?php echo htmlspecialchars($p['product_name']); ?></div>
            <?php if(!empty($p['product_details'])): ?>
            <div class="food-card-desc"><?php echo htmlspecialchars($p['product_details']); ?></div>
            <?php endif; ?>
            <div class="food-card-foot">
              <div class="food-card-price">
                PKR <?php echo number_format($p['product_price']); ?>
                <span>/ serving</span>
              </div>
              <a href="<?php echo $order_link; ?>" class="food-card-add" title="Order">+</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="color:var(--cream3);font-size:15px;padding:60px 20px;text-align:center;width:100%">No products yet. Check back soon!</div>
      <?php endif; ?>
    </div>
  </div>
  <div class="slider-dots" id="sliderDots"></div>
</section>

<!-- ══ TESTIMONIALS ══ -->
<section class="testi-section reveal" id="reviews">
  <div class="section-eyebrow">What People Say</div>
  <h2 class="section-title">Loved by <em>Thousands</em></h2>
  <div class="testi-slider">
    <div class="testi-track" id="testiTrack">
      <div class="testi-item"><div class="testi-stars">★★★★★</div><p class="testi-quote">"The Nihari is unlike anything else in Karachi. It tastes like something a grandmother spent all night over — because someone did."</p><div class="testi-author">— Ahmed R., DHA Karachi</div></div>
      <div class="testi-item"><div class="testi-stars">★★★★★</div><p class="testi-quote">"Ordered the Karahi for a family dinner. Everyone went silent when they tasted it — the highest compliment in a Pakistani household."</p><div class="testi-author">— Sana M., Gulshan-e-Iqbal</div></div>
      <div class="testi-item"><div class="testi-stars">★★★★★</div><p class="testi-quote">"Delivery was hot, packaged beautifully, and the Biryani had that exact smoky dum aroma you can't fake. Will be ordering every week."</p><div class="testi-author">— Usman K., Clifton</div></div>
      <div class="testi-item"><div class="testi-stars">★★★★★</div><p class="testi-quote">"Finally a restaurant that doesn't compromise on the dal. The Dal Makhani had real depth — not the quick-cooked version everyone else serves."</p><div class="testi-author">— Nadia F., North Nazimabad</div></div>
    </div>
    <div class="testi-dots" id="testiDots"></div>
  </div>
</section>

<!-- ══ CTA ══ -->
<section class="cta-section reveal">
  <div class="cta-content">
    <div class="section-eyebrow">Ready to Order?</div>
    <h2 class="cta-title">Your Royal Meal<br>Awaits <em>You</em></h2>
    <?php if($is_logged_in): ?>
    <p class="cta-sub">Welcome back! Your next royal feast is just a few clicks away.</p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
      <a href="user/orders.php"    class="btn-primary">Browse Menu</a>
      <a href="user/my_orders.php" class="btn-outline">My Orders</a>
    </div>
    <?php else: ?>
    <p class="cta-sub">Sign up in seconds and have Karachi's finest food at your door in under 30 minutes.</p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
      <a href="user.php"  class="btn-primary">Create Account</a>
      <a href="login.php" class="btn-outline">Sign In</a>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ══ FOOTER ══ -->
<footer>
  <div class="footer-grid">
    <div>
      <div class="footer-brand-name">Rana's <em>Table</em></div>
      <p class="footer-brand-desc">Royal Pakistani cuisine, crafted with heritage spices and delivered to your door across Karachi.</p>
      <div class="footer-social">
        <a href="#"><i class="bi bi-instagram"></i></a>
        <a href="#"><i class="bi bi-facebook"></i></a>
        <a href="#"><i class="bi bi-whatsapp"></i></a>
        <a href="#"><i class="bi bi-tiktok"></i></a>
      </div>
    </div>
    <div class="footer-col">
      <div class="footer-col-title">Menu</div>
      <?php foreach(array_slice($categories,0,5) as $cat): ?>
      <a href="<?php echo $is_logged_in ? 'user/orders.php#cat-sec-'.$cat['id'] : 'login.php'; ?>">
        <?php echo htmlspecialchars($cat['category_name']); ?>
      </a>
      <?php endforeach; ?>
      <?php if(empty($categories)): ?>
      <a href="<?php echo $order_link; ?>">View All</a>
      <?php endif; ?>
    </div>
    <div class="footer-col">
      <div class="footer-col-title">Account</div>
      <?php if($is_logged_in): ?>
      <a href="user/orders.php">Browse Menu</a>
      <a href="user/my_orders.php">My Orders</a>
      <a href="user/profile.php">My Profile</a>
      <a href="logout.php">Sign Out</a>
      <?php else: ?>
      <a href="login.php">Sign In</a>
      <a href="user.php">Register</a>
      <a href="forgot.php">Forgot Password</a>
      <?php endif; ?>
    </div>
    <div class="footer-col">
      <div class="footer-col-title">Contact</div>
      <a href="#">+92 300 0000000</a>
      <a href="#">info@ranastable.pk</a>
      <a href="#">DHA, Karachi</a>
      <a href="#">Daily 11am – 11pm</a>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© <?php echo date('Y'); ?> Rana's Table. All rights reserved.</span>
    <span>Crafted with ♥ in Karachi, Pakistan</span>
  </div>
</footer>

<script>
/* Embers */
const ec=['#C9A84C','#FF8C42','#FFD166'];
const eb=document.getElementById('embers');
for(let i=0;i<16;i++){
  const e=document.createElement('div'),s=1+Math.random()*2.5,c=ec[i%3];
  e.className='ember';
  Object.assign(e.style,{width:s+'px',height:s+'px',background:c,left:(3+Math.random()*94)+'%',bottom:'-8px','--dx':((Math.random()-.5)*80)+'px',animationDuration:(12+Math.random()*14)+'s',animationDelay:(Math.random()*20)+'s',boxShadow:`0 0 ${s*2}px ${c}`});
  eb.appendChild(e);
}

/* Nav scroll */
const nav=document.getElementById('rnNav') || document.getElementById('mainNav');
if(nav) window.addEventListener('scroll',()=>nav.classList.toggle('scrolled',window.scrollY>60),{passive:true});

/* Reveal */
const revObs=new IntersectionObserver(entries=>{
  entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('visible');revObs.unobserve(e.target);}});
},{threshold:0.1});
document.querySelectorAll('.reveal').forEach(el=>revObs.observe(el));

/* Bestseller Slider */
(function(){
  const track=document.getElementById('sliderTrack');
  const dotsWrap=document.getElementById('sliderDots');
  if(!track||!dotsWrap) return;
  const cards=track.querySelectorAll('.food-card');
  if(!cards.length) return;
  const GAP=20, CARD_W=300;
  let cur=0;
  const visible=()=>Math.max(1,Math.floor((track.parentElement.offsetWidth+GAP)/(CARD_W+GAP)));
  const maxIdx=()=>Math.max(0,cards.length-visible());
  function buildDots(){
    dotsWrap.innerHTML='';
    for(let i=0;i<=maxIdx();i++){
      const d=document.createElement('div');
      d.className='slider-dot'+(i===cur?' active':'');
      d.addEventListener('click',()=>goTo(i));
      dotsWrap.appendChild(d);
    }
  }
  function goTo(idx){
    cur=Math.max(0,Math.min(idx,maxIdx()));
    track.style.transform=`translateX(-${cur*(CARD_W+GAP)}px)`;
    dotsWrap.querySelectorAll('.slider-dot').forEach((d,i)=>d.classList.toggle('active',i===cur));
  }
  document.getElementById('sliderPrev').addEventListener('click',()=>goTo(cur-1));
  document.getElementById('sliderNext').addEventListener('click',()=>goTo(cur+1));
  buildDots();
  window.addEventListener('resize',()=>{cur=0;buildDots();goTo(0);});
  let tx=0;
  track.addEventListener('touchstart',e=>tx=e.touches[0].clientX,{passive:true});
  track.addEventListener('touchend',e=>{if(Math.abs(tx-e.changedTouches[0].clientX)>40)goTo(tx>e.changedTouches[0].clientX?cur+1:cur-1);});
})();

/* Testimonial Slider */
(function(){
  const track=document.getElementById('testiTrack');
  const dotsWrap=document.getElementById('testiDots');
  if(!track) return;
  const items=track.querySelectorAll('.testi-item');
  let cur=0;
  items.forEach((_,i)=>{
    const d=document.createElement('div');
    d.className='slider-dot'+(i===0?' active':'');
    d.addEventListener('click',()=>goTo(i));
    dotsWrap.appendChild(d);
  });
  function goTo(i){
    cur=i;
    track.style.transform=`translateX(-${cur*100}%)`;
    dotsWrap.querySelectorAll('.slider-dot').forEach((d,j)=>d.classList.toggle('active',j===cur));
  }
  let timer=setInterval(()=>goTo((cur+1)%items.length),5000);
  track.parentElement.addEventListener('mouseenter',()=>clearInterval(timer));
  track.parentElement.addEventListener('mouseleave',()=>{timer=setInterval(()=>goTo((cur+1)%items.length),5000);});
  let tx=0;
  track.addEventListener('touchstart',e=>tx=e.touches[0].clientX,{passive:true});
  track.addEventListener('touchend',e=>{if(Math.abs(tx-e.changedTouches[0].clientX)>40){clearInterval(timer);goTo(tx>e.changedTouches[0].clientX?(cur+1)%items.length:(cur-1+items.length)%items.length);}});
})();
</script>
</body>
</html>