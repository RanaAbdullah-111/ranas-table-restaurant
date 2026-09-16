<?php
include_once("../config.php");
require_once("../auth.php");
require_admin();

$success_msg = "";
$error_msg   = "";

if(isset($_GET['msg']) && $_GET['msg'] === 'updated'){
    $success_msg = "Product updated successfully!";
}

/*
|--------------------------------------------------------------------------
| HANDLE ADD PRODUCT
|--------------------------------------------------------------------------
*/
if(isset($_POST['submit'])){
    $category_id     = (int)$_POST['category_id'];
    $product_name    = trim($_POST['product_name']);
    $product_details = trim($_POST['product_details']);
    $product_price   = trim($_POST['product_price']);

    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){
        $imageName    = basename($_FILES['image']['name']);
        $tempName     = $_FILES['image']['tmp_name'];
        $ext          = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
        $allowed      = ['jpg','jpeg','png','webp','gif','jfif'];

        if(in_array($ext, $allowed)){
            $newImageName = time() . "_" . $imageName;
            $folder       = "../product_uploads/" . $newImageName;

            if(move_uploaded_file($tempName, $folder)){
                $stmt = $connection->prepare("INSERT INTO `product`(`category_id`,`product_name`,`product_details`,`product_price`,`product_image`) VALUES (?,?,?,?,?)");
                $stmt->bind_param("issss", $category_id, $product_name, $product_details, $product_price, $newImageName);
                if($stmt->execute()){
                    $success_msg = "Product \"" . htmlspecialchars($product_name) . "\" added successfully!";
                } else {
                    $error_msg = "Database error. Please try again.";
                }
            } else {
                $error_msg = "Failed to upload image. Check folder permissions.";
            }
        } else {
            $error_msg = "Invalid file type. Allowed: JPG, PNG, WEBP, GIF.";
        }
    } else {
        $error_msg = "Please select a product image.";
    }
}

/*
|--------------------------------------------------------------------------
| FETCH CATEGORIES (for dropdown)
|--------------------------------------------------------------------------
*/
$cat_result = mysqli_query($connection, "SELECT * FROM category ORDER BY category_name ASC");
$categories = [];
if($cat_result) while($r = mysqli_fetch_assoc($cat_result)) $categories[] = $r;

/*
|--------------------------------------------------------------------------
| FETCH ALL PRODUCTS WITH CATEGORY NAME
|--------------------------------------------------------------------------
*/
$prod_result = mysqli_query($connection, "
    SELECT product.*, category.category_name
    FROM product
    LEFT JOIN category ON product.category_id = category.id
    ORDER BY product.id DESC
");
$products = [];
if($prod_result) while($r = mysqli_fetch_assoc($prod_result)) $products[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products — Rana's Table</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Cormorant+Garamond:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        :root {
            --gold:      #C9A84C;
            --gold-lite: #E8C96A;
            --deep:      #110600;
            --card-bg:   rgba(30,12,0,0.84);
            --cream:     #F5EDD6;
            --cream-dim: rgba(245,237,214,0.52);
        }

        html { scroll-behavior:smooth; }
        body { font-family:'Cormorant Garamond',serif; background:var(--deep); color:var(--cream); min-height:100vh; overflow-x:hidden; }

        .bg-fixed { position:fixed; inset:0; z-index:0; pointer-events:none; background:radial-gradient(ellipse 80% 50% at 50% 0%,#3a1600 0%,#110600 65%); }
        .ember { position:fixed; border-radius:50%; pointer-events:none; opacity:0; animation:floatUp linear infinite; z-index:0; }
        @keyframes floatUp { 0%{transform:translateY(0) translateX(0);opacity:0} 8%{opacity:.7} 90%{opacity:.1} 100%{transform:translateY(-100vh) translateX(var(--d));opacity:0} }

        /* NAV */

        /* TOAST */
        .toast { position:fixed; top:80px; left:50%; transform:translateX(-50%) translateY(-20px); padding:13px 26px; font-size:14px; letter-spacing:0.5px; z-index:999; pointer-events:none; opacity:0; transition:all 0.4s cubic-bezier(0.16,1,0.3,1); white-space:nowrap; display:flex; align-items:center; gap:10px; }
        .toast.show { opacity:1; transform:translateX(-50%) translateY(0); }
        .toast.success { background:rgba(25,55,35,0.97); border:0.5px solid rgba(90,185,122,0.4); color:#8de8a8; }
        .toast.error   { background:rgba(55,15,10,0.97); border:0.5px solid rgba(224,92,58,0.4);  color:#f0896a; }

        /* HERO */
        .hero { position:relative; z-index:1; text-align:center; padding:52px 40px 32px; }
        .hero-tag { font-size:10px; letter-spacing:5px; text-transform:uppercase; color:rgba(201,168,76,0.55); margin-bottom:12px; animation:fadeUp 0.6s 0.1s both; }
        .hero-title { font-family:'Playfair Display',serif; font-size:clamp(34px,5vw,58px); font-weight:700; line-height:1; background:linear-gradient(135deg,#C9A84C 0%,#F0D08A 45%,#8B6914 100%); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; animation:fadeUp 0.6s 0.2s both; }
        .hero-sub { font-size:16px; font-weight:300; color:var(--cream-dim); margin-top:10px; animation:fadeUp 0.6s 0.3s both; }
        .hero-divider { display:flex; align-items:center; gap:12px; width:200px; margin:18px auto 0; animation:fadeUp 0.6s 0.4s both; }
        .hdl { flex:1; height:.5px; background:linear-gradient(to right,transparent,rgba(201,168,76,.38),transparent); }
        .hdg { width:5px; height:5px; background:var(--gold); transform:rotate(45deg); flex-shrink:0; opacity:.65; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

        /* PAGE */
        .page { position:relative; z-index:1; max-width:1180px; margin:0 auto; padding:0 32px 80px; }

        /* LAYOUT */
        .main-layout { display:grid; grid-template-columns:400px 1fr; gap:28px; align-items:start; }

        /* ── FORM CARD ── */
        .form-card { background:var(--card-bg); border:0.5px solid rgba(201,168,76,0.16); padding:36px 32px; position:sticky; top:88px; position:relative; animation:fadeUp 0.6s 0.3s both; }
        .form-card::before,.form-card::after { content:''; position:absolute; width:14px; height:14px; border-color:var(--gold); border-style:solid; opacity:.35; }
        .form-card::before { top:10px; left:10px; border-width:1px 0 0 1px; }
        .form-card::after  { bottom:10px; right:10px; border-width:0 1px 1px 0; }

        .card-title { font-family:'Playfair Display',serif; font-size:24px; font-weight:400; color:var(--cream); margin-bottom:6px; }
        .card-sub   { font-size:13px; font-weight:300; color:var(--cream-dim); margin-bottom:28px; }

        /* FIELDS */
        .field { margin-bottom:18px; }
        label { display:block; font-size:10px; letter-spacing:3.5px; text-transform:uppercase; color:rgba(201,168,76,0.6); margin-bottom:8px; }
        .input-wrap { position:relative; }
        .i-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); font-size:14px; color:rgba(201,168,76,0.3); pointer-events:none; transition:color 0.3s; }
        .i-icon.top { top:14px; transform:none; }

        input[type="text"], input[type="number"], textarea, select {
            width:100%; padding:12px 16px 12px 42px;
            background:rgba(255,255,255,0.03); border:0.5px solid rgba(201,168,76,0.18);
            color:var(--cream); font-family:'Cormorant Garamond',serif; font-size:16px; font-weight:300;
            outline:none; transition:all 0.3s; border-radius:0; -webkit-appearance:none;
        }
        select { cursor:pointer; }
        select option { background:#1e0c00; color:var(--cream); }
        textarea { padding-top:12px; resize:vertical; min-height:80px; }
        input:focus, textarea:focus, select:focus { border-color:rgba(201,168,76,0.52); background:rgba(201,168,76,0.04); box-shadow:0 0 0 3px rgba(201,168,76,0.07); }
        input:focus ~ .i-icon, textarea:focus ~ .i-icon, select:focus ~ .i-icon { color:rgba(201,168,76,0.75); }
        ::placeholder { color:rgba(245,237,214,0.2); font-size:15px; }
        .focus-bar { position:absolute; bottom:0; left:0; right:0; height:1.5px; background:linear-gradient(to right,transparent,var(--gold),transparent); transform:scaleX(0); transform-origin:center; transition:transform 0.35s cubic-bezier(0.4,0,0.2,1); pointer-events:none; }
        input:focus + .focus-bar, textarea:focus + .focus-bar, select:focus + .focus-bar { transform:scaleX(1); }

        /* PRICE ROW */
        .price-wrap { position:relative; }
        .price-prefix { position:absolute; left:42px; top:50%; transform:translateY(-50%); font-size:14px; color:rgba(201,168,76,0.5); pointer-events:none; z-index:1; }
        .price-wrap input { padding-left:80px; }

        /* UPLOAD */
        .upload-zone { border:0.5px dashed rgba(201,168,76,0.3); padding:22px 16px; text-align:center; cursor:pointer; transition:all 0.3s; position:relative; overflow:hidden; background:rgba(201,168,76,0.02); }
        .upload-zone:hover { border-color:var(--gold); background:rgba(201,168,76,0.05); }
        .upload-zone.dragging { border-color:var(--gold); background:rgba(201,168,76,0.08); }
        .upload-zone input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
        .upload-icon { font-size:28px; color:rgba(201,168,76,0.35); margin-bottom:8px; display:block; }
        .upload-text { font-size:14px; font-weight:300; color:var(--cream-dim); margin-bottom:3px; }
        .upload-hint { font-size:11px; letter-spacing:1px; color:rgba(201,168,76,0.35); }

        .img-preview-wrap { display:none; margin-top:10px; }
        .img-preview-wrap.show { display:block; }
        .img-preview { width:100%; height:140px; object-fit:cover; border:0.5px solid rgba(201,168,76,0.2); display:block; }
        .img-preview-remove { position:absolute; top:8px; right:8px; width:26px; height:26px; background:rgba(10,4,0,0.85); border:0.5px solid rgba(224,92,58,0.4); color:#e05c3a; font-size:13px; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s; }
        .img-preview-remove:hover { background:#e05c3a; color:#fff; }
        .img-name { font-size:11px; color:rgba(201,168,76,0.45); margin-top:5px; text-align:center; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

        /* SUBMIT */
        .btn-submit { width:100%; padding:15px; background:var(--gold); color:var(--deep); font-family:'Cormorant Garamond',serif; font-size:13px; font-weight:700; letter-spacing:4px; text-transform:uppercase; border:none; cursor:pointer; position:relative; overflow:hidden; transition:all 0.3s; margin-top:8px; display:flex; align-items:center; justify-content:center; gap:8px; }
        .btn-submit::before { content:''; position:absolute; inset:0; background:linear-gradient(120deg,transparent 30%,rgba(255,255,255,.25) 50%,transparent 70%); transform:translateX(-100%); transition:transform .55s; }
        .btn-submit:hover::before { transform:translateX(100%); }
        .btn-submit:hover { background:var(--gold-lite); transform:translateY(-2px); box-shadow:0 10px 32px rgba(201,168,76,.2); }
        .btn-submit:active { transform:translateY(0); }

        /* ── PRODUCTS TABLE SECTION ── */
        .table-section { animation:fadeUp 0.6s 0.4s both; }

        .table-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:12px; }
        .table-title { font-family:'Playfair Display',serif; font-size:26px; font-weight:400; color:var(--cream); }

        /* search */
        .search-wrap { position:relative; }
        .search-wrap input { padding:9px 16px 9px 38px; font-size:13px; min-width:220px; border-color:rgba(201,168,76,0.2); }
        .search-wrap .i-icon { font-size:13px; }

        .table-meta { display:flex; align-items:center; gap:16px; }
        .table-count { font-size:11px; letter-spacing:3px; text-transform:uppercase; color:rgba(201,168,76,0.4); }

        /* TABLE */
        .prod-table-wrap { overflow-x:auto; }
        .prod-table { width:100%; border-collapse:collapse; min-width:660px; }

        .prod-table thead tr { border-bottom:0.5px solid rgba(201,168,76,0.18); }
        .prod-table th { font-size:10px; letter-spacing:3px; text-transform:uppercase; color:rgba(201,168,76,0.5); font-weight:500; padding:12px 16px; text-align:left; white-space:nowrap; }
        .prod-table th:last-child { text-align:center; }

        .prod-row {
            border-bottom:0.5px solid rgba(201,168,76,0.06);
            background:var(--card-bg);
            opacity:0; animation:rowIn 0.5s ease forwards;
            transition:background 0.2s;
        }
        .prod-row:hover { background:rgba(201,168,76,0.04); }
        @keyframes rowIn { to{opacity:1} }

        .prod-table td { padding:14px 16px; vertical-align:middle; font-size:15px; font-weight:300; color:var(--cream-dim); }
        .prod-table td:last-child { text-align:center; }

        /* thumb */
        .prod-thumb { width:54px; height:54px; object-fit:cover; border:0.5px solid rgba(201,168,76,0.15); display:block; }
        .prod-thumb-ph { width:54px; height:54px; background:rgba(45,21,0,0.5); border:0.5px solid rgba(201,168,76,0.1); display:flex; align-items:center; justify-content:center; font-size:20px; opacity:.25; }

        .prod-name { font-family:'Playfair Display',serif; font-size:17px; color:var(--cream); font-weight:400; }
        .prod-detail { font-size:12px; color:var(--cream-dim); margin-top:3px; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

        .cat-tag { display:inline-block; font-size:10px; letter-spacing:2px; text-transform:uppercase; background:rgba(201,168,76,0.1); border:0.5px solid rgba(201,168,76,0.25); color:var(--gold); padding:3px 10px; }

        .prod-price { font-size:17px; color:var(--gold); font-weight:500; white-space:nowrap; }

        /* action buttons */
        .action-btns { display:flex; align-items:center; justify-content:center; gap:6px; }
        .act { width:32px; height:32px; display:flex; align-items:center; justify-content:center; font-size:14px; border:0.5px solid; cursor:pointer; text-decoration:none; transition:all 0.25s; background:transparent; }
        .act-edit   { border-color:rgba(201,168,76,0.3); color:rgba(201,168,76,0.6); }
        .act-edit:hover   { background:rgba(201,168,76,0.12); border-color:var(--gold); color:var(--gold); }
        .act-delete { border-color:rgba(224,92,58,0.3); color:rgba(224,92,58,0.6); }
        .act-delete:hover { background:rgba(224,92,58,0.12); border-color:#e05c3a; color:#e05c3a; }

        /* empty */
        .table-empty { text-align:center; padding:60px 20px; background:var(--card-bg); border:0.5px solid rgba(201,168,76,0.1); }
        .table-empty-icon { font-size:44px; opacity:.18; margin-bottom:14px; }
        .table-empty p { font-size:15px; font-weight:300; color:var(--cream-dim); }

        footer { position:relative; z-index:1; border-top:0.5px solid rgba(201,168,76,0.1); padding:28px 40px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; max-width:1180px; margin:0 auto; }
        footer .f-brand { font-family:'Playfair Display',serif; font-size:17px; color:var(--gold); }
        footer .f-note  { font-size:12px; font-weight:300; color:rgba(245,237,214,.28); }

        @media(max-width:820px){
            nav { padding:0 16px; }
            .hero { padding:40px 16px 28px; }
            .page { padding:0 14px 60px; }
            .main-layout { grid-template-columns:1fr; }
            .form-card { position:static; }
            footer { padding:20px 16px; flex-direction:column; text-align:center; }
        }
    </style>
</head>
<body>

<div class="bg-fixed"></div>

<!-- TOAST -->
<?php if($success_msg || $error_msg): ?>
<div class="toast <?php echo $success_msg ? 'success' : 'error'; ?>" id="toast">
    <i class="bi bi-<?php echo $success_msg ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?>"></i>
    <?php echo $success_msg ?: $error_msg; ?>
</div>
<?php endif; ?>

<!-- NAV -->
<?php require("admin_navbar.php"); ?>


<!-- HERO -->
<div class="hero">
    <div class="hero-tag">Admin Panel &nbsp;·&nbsp; Rana's Table</div>
    <h1 class="hero-title">Manage Products</h1>
    <p class="hero-sub">Add new dishes and manage your full menu.</p>
    <div class="hero-divider"><div class="hdl"></div><div class="hdg"></div><div class="hdl"></div></div>
</div>

<div class="page">
<div class="main-layout">

    <!-- ── LEFT: ADD FORM ── -->
    <div class="form-card">
        <div class="card-title">Add New Product</div>
        <div class="card-sub">Fill in the dish details, select a category, and upload a photo.</div>

        <form action="" method="POST" enctype="multipart/form-data">

            <div class="field">
                <label for="f_cat">Category</label>
                <div class="input-wrap">
                    <i class="bi bi-tag-fill i-icon"></i>
                    <select name="category_id" id="f_cat" required>
                        <option value="" disabled selected>Select a category…</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="focus-bar"></div>
                </div>
            </div>

            <div class="field">
                <label for="f_name">Product Name</label>
                <div class="input-wrap">
                    <i class="bi bi-bag-fill i-icon"></i>
                    <input type="text" id="f_name" name="product_name" placeholder="e.g. Shahi Biryani" required>
                    <div class="focus-bar"></div>
                </div>
            </div>

            <div class="field">
                <label for="f_detail">Description</label>
                <div class="input-wrap">
                    <i class="bi bi-card-text i-icon top"></i>
                    <textarea id="f_detail" name="product_details" placeholder="Brief description of the dish…"></textarea>
                    <div class="focus-bar"></div>
                </div>
            </div>

            <div class="field">
                <label for="f_price">Price (PKR)</label>
                <div class="input-wrap price-wrap">
                    <i class="bi bi-currency-exchange i-icon"></i>
                    <span class="price-prefix">PKR</span>
                    <input type="text" id="f_price" name="product_price" placeholder="e.g. 1200" required>
                    <div class="focus-bar"></div>
                </div>
            </div>

            <div class="field">
                <label>Product Image</label>
                <div class="upload-zone" id="uploadZone">
                    <input type="file" name="image" id="imageInput" accept="image/*" required>
                    <i class="bi bi-cloud-arrow-up upload-icon"></i>
                    <div class="upload-text">Click or drag & drop</div>
                    <div class="upload-hint">JPG · PNG · WEBP · GIF</div>
                </div>
                <div class="img-preview-wrap" id="previewWrap" style="position:relative">
                    <img src="" alt="Preview" class="img-preview" id="imgPreview">
                    <button type="button" class="img-preview-remove" id="removeImg"><i class="bi bi-x"></i></button>
                    <div class="img-name" id="imgName"></div>
                </div>
            </div>

            <button type="submit" name="submit" class="btn-submit">
                <i class="bi bi-plus-circle-fill"></i> Add Product
            </button>
        </form>
    </div>

    <!-- ── RIGHT: PRODUCT TABLE ── -->
    <div class="table-section">
        <div class="table-header">
            <div class="table-title">All Products</div>
            <div class="table-meta">
                <div class="search-wrap">
                    <div class="input-wrap">
                        <i class="bi bi-search i-icon"></i>
                        <input type="text" id="searchInput" placeholder="Search products…" style="padding:9px 16px 9px 38px;font-size:13px;min-width:200px;">
                    </div>
                </div>
                <div class="table-count" id="prodCount"><?php echo count($products); ?> products</div>
            </div>
        </div>

        <?php if(!empty($products)): ?>
        <div class="prod-table-wrap">
            <table class="prod-table" id="prodTable">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="prodTableBody">
                    <?php foreach($products as $i => $row): ?>
                    <tr class="prod-row" style="animation-delay:<?php echo $i*0.05; ?>s" data-name="<?php echo strtolower(htmlspecialchars($row['product_name'])); ?>">
                        <td>
                            <?php if(!empty($row['product_image'])): ?>
                                <img class="prod-thumb" src="../product_uploads/<?php echo htmlspecialchars($row['product_image']); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
                            <?php else: ?>
                                <div class="prod-thumb-ph">🍽</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="prod-name"><?php echo htmlspecialchars($row['product_name']); ?></div>
                            <?php if(!empty($row['product_details'])): ?>
                            <div class="prod-detail"><?php echo htmlspecialchars($row['product_details']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="cat-tag"><?php echo htmlspecialchars($row['category_name'] ?? '—'); ?></span>
                        </td>
                        <td>
                            <span class="prod-price">PKR <?php echo number_format($row['product_price']); ?></span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="update_product.php?id=<?php echo $row['id']; ?>" class="act act-edit" title="Edit product">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a href="delete_product.php?id=<?php echo $row['id']; ?>"
                                   class="act act-delete" title="Delete product"
                                   onclick="return confirm('Delete \'<?php echo addslashes($row['product_name']); ?>\'? This cannot be undone.')">
                                    <i class="bi bi-trash3-fill"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="table-empty">
            <div class="table-empty-icon">🍽</div>
            <p>No products yet. Add your first dish!</p>
        </div>
        <?php endif; ?>
    </div>

</div>
</div>

<footer>
    <div class="f-brand">Rana's Table</div>
    <div class="f-note">© <?php echo date('Y'); ?> Rana's Table &nbsp;·&nbsp; Admin Panel</div>
</footer>

<script>
/* Embers */
const ec=['#C9A84C','#FF8C42','#FFD166','#E87040'];
for(let i=0;i<16;i++){
    const e=document.createElement('div'); e.className='ember';
    const s=1.5+Math.random()*3, c=ec[Math.floor(Math.random()*ec.length)];
    Object.assign(e.style,{width:s+'px',height:s+'px',background:c,left:(4+Math.random()*92)+'%',bottom:'-8px','--d':((Math.random()-.5)*80)+'px',animationDuration:(9+Math.random()*10)+'s',animationDelay:(Math.random()*16)+'s',boxShadow:`0 0 ${s*2.5}px ${c}`});
    document.body.appendChild(e);
}

/* Toast */
const toast = document.getElementById('toast');
if(toast){ setTimeout(()=>toast.classList.add('show'),120); setTimeout(()=>toast.classList.remove('show'),4000); }

/* Image preview */
const fileInput   = document.getElementById('imageInput');
const previewWrap = document.getElementById('previewWrap');
const imgPreview  = document.getElementById('imgPreview');
const imgName     = document.getElementById('imgName');
const uploadZone  = document.getElementById('uploadZone');

fileInput.addEventListener('change', handleFile);
function handleFile(){
    const file = fileInput.files[0];
    if(!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        imgPreview.src = e.target.result;
        imgName.textContent = file.name;
        previewWrap.classList.add('show');
        uploadZone.style.display = 'none';
    };
    reader.readAsDataURL(file);
}
document.getElementById('removeImg').addEventListener('click',()=>{
    fileInput.value = '';
    previewWrap.classList.remove('show');
    uploadZone.style.display = '';
    imgPreview.src = '';
});
['dragenter','dragover'].forEach(ev=>{ uploadZone.addEventListener(ev,e=>{e.preventDefault();uploadZone.classList.add('dragging');}); });
['dragleave','drop'].forEach(ev=>{ uploadZone.addEventListener(ev,e=>{e.preventDefault();uploadZone.classList.remove('dragging');}); });
uploadZone.addEventListener('drop', e=>{
    const file = e.dataTransfer.files[0];
    if(file && file.type.startsWith('image/')){
        const dt = new DataTransfer(); dt.items.add(file); fileInput.files = dt.files; handleFile();
    }
});

/* Live search */
const searchInput = document.getElementById('searchInput');
const prodCount   = document.getElementById('prodCount');
if(searchInput){
    searchInput.addEventListener('input',()=>{
        const q = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll('.prod-row');
        let visible = 0;
        rows.forEach(row=>{
            const name = row.dataset.name || '';
            const show = name.includes(q);
            row.style.display = show ? '' : 'none';
            if(show) visible++;
        });
        prodCount.textContent = visible + ' product' + (visible!==1?'s':'');
    });
}
</script>
</body>
</html>