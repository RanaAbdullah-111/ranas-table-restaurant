<?php
include_once("../config.php");
require_once("../auth.php");
require_admin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error_msg   = "";
$success_msg = "";

if ($id <= 0) {
    header("Location: product.php");
    exit();
}

// Handle Update
if(isset($_POST['submit'])){
    $category_id     = (int)$_POST['category_id'];
    $product_name    = trim($_POST['product_name']);
    $product_details = trim($_POST['product_details']);
    $product_price   = trim($_POST['product_price']);

    // Check if new image was provided
    if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK){
        $imageName = basename($_FILES['image']['name']);
        $tempName  = $_FILES['image']['tmp_name'];
        $ext       = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
        $allowed   = ['jpg','jpeg','png','webp','gif','jfif'];

        if(in_array($ext, $allowed)){
            $newImageName = time() . "_" . $imageName;
            $folder       = "../product_uploads/" . $newImageName;

            // Fetch old image filename before update
            $old_stmt = $connection->prepare("SELECT `product_image` FROM `product` WHERE id = ?");
            $old_stmt->bind_param("i", $id);
            $old_stmt->execute();
            $old_res = $old_stmt->get_result();
            $old_row = $old_res->fetch_assoc();
            $old_image = $old_row['product_image'] ?? '';
            $old_stmt->close();

            if(move_uploaded_file($tempName, $folder)){
                // Delete previous image file from product_uploads
                if(!empty($old_image)){
                    $old_path = __DIR__ . "/../product_uploads/" . $old_image;
                    if(file_exists($old_path) && is_file($old_path)){
                        @unlink($old_path);
                    }
                }

                $stmt = $connection->prepare("UPDATE `product` SET `category_id`=?, `product_name`=?, `product_details`=?, `product_price`=?, `product_image`=? WHERE id=?");
                $stmt->bind_param("issssi", $category_id, $product_name, $product_details, $product_price, $newImageName, $id);
                if($stmt->execute()){
                    header("Location: product.php?msg=updated");
                    exit();
                } else {
                    $error_msg = "Database error while updating product.";
                }
                $stmt->close();
            } else {
                $error_msg = "Failed to upload image. Check folder permissions.";
            }
        } else {
            $error_msg = "Invalid image file type. Allowed formats: JPG, PNG, WEBP, GIF, JFIF.";
        }
    } else {
        // Update without changing image
        $stmt = $connection->prepare("UPDATE `product` SET `category_id`=?, `product_name`=?, `product_details`=?, `product_price`=? WHERE id=?");
        $stmt->bind_param("isssi", $category_id, $product_name, $product_details, $product_price, $id);
        if($stmt->execute()){
            header("Location: product.php?msg=updated");
            exit();
        } else {
            $error_msg = "Database error while updating product.";
        }
        $stmt->close();
    }
}

// Fetch current product
$stmt = $connection->prepare("SELECT * FROM `product` WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product_result = $stmt->get_result();

if($product_result->num_rows === 0){
    header("Location: product.php");
    exit();
}
$product = $product_result->fetch_assoc();

// Fetch categories for dropdown
$result_categories = mysqli_query($connection, "SELECT * FROM `category` ORDER BY category_name ASC");
$categories = [];
if($result_categories) while($c = mysqli_fetch_assoc($result_categories)) $categories[] = $c;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product — Rana's Table Admin</title>
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

        /* TOAST */
        .toast { position:fixed; top:80px; left:50%; transform:translateX(-50%) translateY(-20px); padding:13px 26px; font-size:14px; letter-spacing:0.5px; z-index:999; pointer-events:none; opacity:0; transition:all 0.4s cubic-bezier(0.16,1,0.3,1); white-space:nowrap; display:flex; align-items:center; gap:10px; }
        .toast.show { opacity:1; transform:translateX(-50%) translateY(0); }
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
        .page { position:relative; z-index:1; max-width:760px; margin:0 auto; padding:0 32px 80px; }

        /* ── FORM CARD ── */
        .form-card { background:var(--card-bg); border:0.5px solid rgba(201,168,76,0.16); padding:40px 36px; position:relative; animation:fadeUp 0.6s 0.3s both; }
        .form-card::before,.form-card::after { content:''; position:absolute; width:14px; height:14px; border-color:var(--gold); border-style:solid; opacity:.35; }
        .form-card::before { top:10px; left:10px; border-width:1px 0 0 1px; }
        .form-card::after  { bottom:10px; right:10px; border-width:0 1px 1px 0; }

        .card-header-flex { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; border-bottom:0.5px solid rgba(201,168,76,0.12); padding-bottom:16px; }
        .card-title { font-family:'Playfair Display',serif; font-size:24px; font-weight:400; color:var(--cream); }
        .btn-back { font-size:12px; letter-spacing:2px; text-transform:uppercase; color:var(--gold); text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:color 0.2s; }
        .btn-back:hover { color:var(--gold-lite); }

        /* FIELDS */
        .field { margin-bottom:20px; }
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
        textarea { padding-top:12px; resize:vertical; min-height:90px; }
        input:focus, textarea:focus, select:focus { border-color:rgba(201,168,76,0.52); background:rgba(201,168,76,0.04); box-shadow:0 0 0 3px rgba(201,168,76,0.07); }
        input:focus ~ .i-icon, textarea:focus ~ .i-icon, select:focus ~ .i-icon { color:rgba(201,168,76,0.75); }
        ::placeholder { color:rgba(245,237,214,0.2); font-size:15px; }
        .focus-bar { position:absolute; bottom:0; left:0; right:0; height:1.5px; background:linear-gradient(to right,transparent,var(--gold),transparent); transform:scaleX(0); transform-origin:center; transition:transform 0.35s cubic-bezier(0.4,0,0.2,1); pointer-events:none; }
        input:focus + .focus-bar, textarea:focus + .focus-bar, select:focus + .focus-bar { transform:scaleX(1); }

        /* PRICE ROW */
        .price-wrap { position:relative; }
        .price-prefix { position:absolute; left:42px; top:50%; transform:translateY(-50%); font-size:14px; color:rgba(201,168,76,0.5); pointer-events:none; z-index:1; }
        .price-wrap input { padding-left:80px; }

        /* IMAGE DISPLAY & UPLOAD */
        .img-grid { display:grid; grid-template-columns:140px 1fr; gap:20px; align-items:start; }
        .current-img-wrap { border:0.5px solid rgba(201,168,76,0.2); background:rgba(201,168,76,0.03); padding:8px; text-align:center; }
        .current-img { width:100%; height:110px; object-fit:cover; display:block; }
        .current-img-ph { width:100%; height:110px; display:flex; align-items:center; justify-content:center; font-size:32px; color:rgba(201,168,76,0.3); }
        .current-img-lbl { font-size:10px; letter-spacing:2px; text-transform:uppercase; color:rgba(201,168,76,0.5); margin-top:6px; }

        .upload-zone { border:0.5px dashed rgba(201,168,76,0.3); padding:20px 16px; text-align:center; cursor:pointer; transition:all 0.3s; position:relative; overflow:hidden; background:rgba(201,168,76,0.02); }
        .upload-zone:hover { border-color:var(--gold); background:rgba(201,168,76,0.05); }
        .upload-zone.dragging { border-color:var(--gold); background:rgba(201,168,76,0.08); }
        .upload-zone input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
        .upload-icon { font-size:24px; color:rgba(201,168,76,0.35); margin-bottom:6px; display:block; }
        .upload-text { font-size:13px; font-weight:300; color:var(--cream-dim); margin-bottom:2px; }
        .upload-hint { font-size:11px; letter-spacing:1px; color:rgba(201,168,76,0.35); }

        .new-preview-wrap { display:none; margin-top:10px; position:relative; }
        .new-preview-wrap.show { display:block; }
        .new-preview { width:100%; height:120px; object-fit:cover; border:0.5px solid rgba(201,168,76,0.3); display:block; }
        .new-preview-remove { position:absolute; top:6px; right:6px; width:24px; height:24px; background:rgba(10,4,0,0.85); border:0.5px solid rgba(224,92,58,0.4); color:#e05c3a; font-size:12px; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s; }
        .new-preview-remove:hover { background:#e05c3a; color:#fff; }

        /* BUTTONS */
        .btn-group { display:flex; gap:14px; margin-top:12px; }
        .btn-submit { flex:1; padding:15px; background:var(--gold); color:var(--deep); font-family:'Cormorant Garamond',serif; font-size:13px; font-weight:700; letter-spacing:4px; text-transform:uppercase; border:none; cursor:pointer; position:relative; overflow:hidden; transition:all 0.3s; display:flex; align-items:center; justify-content:center; gap:8px; }
        .btn-submit::before { content:''; position:absolute; inset:0; background:linear-gradient(120deg,transparent 30%,rgba(255,255,255,.25) 50%,transparent 70%); transform:translateX(-100%); transition:transform .55s; }
        .btn-submit:hover::before { transform:translateX(100%); }
        .btn-submit:hover { background:var(--gold-lite); transform:translateY(-2px); box-shadow:0 10px 32px rgba(201,168,76,.2); }
        .btn-cancel { padding:15px 28px; background:transparent; border:0.5px solid rgba(201,168,76,0.3); color:var(--cream-dim); font-family:'Cormorant Garamond',serif; font-size:13px; font-weight:600; letter-spacing:3px; text-transform:uppercase; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; transition:all 0.3s; }
        .btn-cancel:hover { border-color:var(--gold); color:var(--cream); }

        footer { position:relative; z-index:1; border-top:0.5px solid rgba(201,168,76,0.1); padding:28px 40px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; max-width:760px; margin:0 auto; }
        footer .f-brand { font-family:'Playfair Display',serif; font-size:17px; color:var(--gold); }
        footer .f-note  { font-size:12px; font-weight:300; color:rgba(245,237,214,.28); }

        @media(max-width:640px){
            .hero { padding:40px 16px 28px; }
            .page { padding:0 16px 60px; }
            .img-grid { grid-template-columns:1fr; }
            .btn-group { flex-direction:column; }
            footer { padding:20px 16px; flex-direction:column; text-align:center; }
        }
    </style>
</head>
<body>

<div class="bg-fixed"></div>

<!-- TOAST -->
<?php if($error_msg): ?>
<div class="toast error show" id="toast">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <?php echo htmlspecialchars($error_msg); ?>
</div>
<?php endif; ?>

<!-- NAV -->
<?php require("admin_navbar.php"); ?>

<div class="adm-main">

<!-- HERO -->
<div class="hero">
    <div class="hero-tag">Admin Panel &nbsp;·&nbsp; Rana's Table</div>
    <h1 class="hero-title">Edit Product</h1>
    <p class="hero-sub">Update dish information, pricing, or change image.</p>
    <div class="hero-divider"><div class="hdl"></div><div class="hdg"></div><div class="hdl"></div></div>
</div>

<div class="page">
    <div class="form-card">
        <div class="card-header-flex">
            <div class="card-title">Product Details</div>
            <a href="product.php" class="btn-back"><i class="bi bi-arrow-left"></i> Back to Products</a>
        </div>

        <form action="" method="POST" enctype="multipart/form-data">

            <div class="field">
                <label for="f_cat">Category</label>
                <div class="input-wrap">
                    <i class="bi bi-tag-fill i-icon"></i>
                    <select name="category_id" id="f_cat" required>
                        <option value="" disabled>Select a category…</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $product['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="focus-bar"></div>
                </div>
            </div>

            <div class="field">
                <label for="f_name">Product Name</label>
                <div class="input-wrap">
                    <i class="bi bi-bag-fill i-icon"></i>
                    <input type="text" id="f_name" name="product_name"
                           value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                    <div class="focus-bar"></div>
                </div>
            </div>

            <div class="field">
                <label for="f_detail">Description</label>
                <div class="input-wrap">
                    <i class="bi bi-card-text i-icon top"></i>
                    <textarea id="f_detail" name="product_details" placeholder="Brief description of the dish…"><?php echo htmlspecialchars($product['product_details']); ?></textarea>
                    <div class="focus-bar"></div>
                </div>
            </div>

            <div class="field">
                <label for="f_price">Price (PKR)</label>
                <div class="input-wrap price-wrap">
                    <i class="bi bi-currency-exchange i-icon"></i>
                    <span class="price-prefix">PKR</span>
                    <input type="number" id="f_price" name="product_price" min="0" step="1"
                           value="<?php echo htmlspecialchars($product['product_price']); ?>" required>
                    <div class="focus-bar"></div>
                </div>
            </div>

            <div class="field">
                <label>Product Image</label>
                <div class="img-grid">
                    <div class="current-img-wrap">
                        <?php if(!empty($product['product_image'])): ?>
                            <img class="current-img" src="../product_uploads/<?php echo htmlspecialchars($product['product_image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        <?php else: ?>
                            <div class="current-img-ph">🍽</div>
                        <?php endif; ?>
                        <div class="current-img-lbl">Current Photo</div>
                    </div>

                    <div>
                        <div class="upload-zone" id="uploadZone">
                            <input type="file" name="image" id="imageInput" accept="image/*">
                            <i class="bi bi-cloud-arrow-up upload-icon"></i>
                            <div class="upload-text">Upload new photo</div>
                            <div class="upload-hint">Leave blank to keep current · JPG, PNG, WEBP</div>
                        </div>
                        <div class="new-preview-wrap" id="previewWrap">
                            <img src="" alt="New Preview" class="new-preview" id="imgPreview">
                            <button type="button" class="new-preview-remove" id="removeImg"><i class="bi bi-x"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="btn-group">
                <button type="submit" name="submit" class="btn-submit">
                    <i class="bi bi-check2-circle"></i> Save Changes
                </button>
                <a href="product.php" class="btn-cancel">Cancel</a>
            </div>

        </form>
    </div>
</div>

<footer>
    <div class="f-brand">Rana's Table</div>
    <div class="f-note">© <?php echo date('Y'); ?> Rana's Table &nbsp;·&nbsp; Admin Panel</div>
</footer>

</div><!-- /.adm-main -->

<script>
/* Embers */
const ec=['#C9A84C','#FF8C42','#FFD166','#E87040'];
for(let i=0;i<14;i++){
    const e=document.createElement('div'); e.className='ember';
    const s=1.5+Math.random()*3, c=ec[Math.floor(Math.random()*ec.length)];
    Object.assign(e.style,{width:s+'px',height:s+'px',background:c,left:(4+Math.random()*92)+'%',bottom:'-8px','--d':((Math.random()-.5)*80)+'px',animationDuration:(9+Math.random()*10)+'s',animationDelay:(Math.random()*16)+'s',boxShadow:`0 0 ${s*2.5}px ${c}`});
    document.body.appendChild(e);
}

/* Image preview */
const fileInput   = document.getElementById('imageInput');
const previewWrap = document.getElementById('previewWrap');
const imgPreview  = document.getElementById('imgPreview');
const uploadZone  = document.getElementById('uploadZone');

if(fileInput){
    fileInput.addEventListener('change', handleFile);
    function handleFile(){
        const file = fileInput.files[0];
        if(!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            imgPreview.src = e.target.result;
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
}
</script>
</body>
</html>