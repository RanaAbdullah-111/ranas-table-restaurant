<?php
include_once("../config.php");
require_once("../auth.php");
require_admin();

$success_msg = "";
$error_msg   = "";

/*
|--------------------------------------------------------------------------
| DELETE CATEGORY
|--------------------------------------------------------------------------
*/
if(isset($_GET['delete'])){
    $del_id = (int)$_GET['delete'];
    $del = $connection->prepare("DELETE FROM category WHERE id=?");
    $del->bind_param("i", $del_id);
    if($del->execute()){
        $success_msg = "Category deleted successfully.";
    } else {
        $error_msg = "Could not delete category.";
    }
}

/*
|--------------------------------------------------------------------------
| UPDATE CATEGORY
|--------------------------------------------------------------------------
*/
if(isset($_POST['update'])){
    $upd_id      = (int)$_POST['upd_id'];
    $upd_name    = trim($_POST['upd_name']);
    $upd_details = trim($_POST['upd_details']);

    // If new image uploaded
    if(isset($_FILES['upd_image']) && $_FILES['upd_image']['error'] == 0){
        $imageName    = basename($_FILES['upd_image']['name']);
        $tempName     = $_FILES['upd_image']['tmp_name'];
        $ext          = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
        $allowed      = ['jpg','jpeg','png','webp','gif','jfif'];

        if(in_array($ext, $allowed)){
            $newImageName = time() . "_" . $imageName;
            if(move_uploaded_file($tempName, "../uploads/" . $newImageName)){
                $upd = $connection->prepare("UPDATE category SET category_name=?, category_details=?, category_image=? WHERE id=?");
                $upd->bind_param("sssi", $upd_name, $upd_details, $newImageName, $upd_id);
            } else {
                $error_msg = "Image upload failed.";
            }
        } else {
            $error_msg = "Invalid file type.";
        }
    } else {
        // No new image — keep existing
        $upd = $connection->prepare("UPDATE category SET category_name=?, category_details=? WHERE id=?");
        $upd->bind_param("ssi", $upd_name, $upd_details, $upd_id);
    }

    if(empty($error_msg) && isset($upd)){
        if($upd->execute()){
            $success_msg = "Category \"" . htmlspecialchars($upd_name) . "\" updated successfully!";
        } else {
            $error_msg = "Database error. Please try again.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| ADD CATEGORY
|--------------------------------------------------------------------------
*/
if(isset($_POST['submit'])){
    $category_name    = trim($_POST['category_name']);
    $category_details = trim($_POST['category_details']);

    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){
        $imageName = basename($_FILES['image']['name']);
        $tempName  = $_FILES['image']['tmp_name'];
        $ext       = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
        $allowed   = ['jpg','jpeg','png','webp','gif','jfif'];

        if(in_array($ext, $allowed)){
            $newImageName = time() . "_" . $imageName;
            if(move_uploaded_file($tempName, "../uploads/" . $newImageName)){
                $stmt = $connection->prepare("INSERT INTO `category`(`category_name`,`category_details`,`category_image`) VALUES (?,?,?)");
                $stmt->bind_param("sss", $category_name, $category_details, $newImageName);
                if($stmt->execute()){
                    $success_msg = "Category \"" . htmlspecialchars($category_name) . "\" added successfully!";
                } else { $error_msg = "Database error. Please try again."; }
            } else { $error_msg = "Failed to upload image. Check folder permissions."; }
        } else { $error_msg = "Invalid file type. Allowed: JPG, PNG, WEBP, GIF, JFIF."; }
    } else { $error_msg = "Please select an image for the category."; }
}

/*
|--------------------------------------------------------------------------
| FETCH CATEGORIES
|--------------------------------------------------------------------------
*/
$cats_result = mysqli_query($connection, "SELECT * FROM category ORDER BY id DESC");
$categories  = [];
if($cats_result) while($r = mysqli_fetch_assoc($cats_result)) $categories[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories — Rana's Table</title>
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
        nav { position:sticky; top:0; z-index:200; background:rgba(12,5,0,0.96); backdrop-filter:blur(16px); border-bottom:0.5px solid rgba(201,168,76,0.18); padding:0 40px; display:flex; align-items:center; justify-content:space-between; height:64px; }
        .nav-brand { font-family:'Playfair Display',serif; font-size:20px; font-weight:400; letter-spacing:1px; color:var(--cream); text-decoration:none; }
        .nav-brand em { color:var(--gold); font-style:italic; }
        .nav-right { display:flex; align-items:center; gap:12px; }
        .nav-btn { padding:7px 18px; font-family:'Cormorant Garamond',serif; font-size:11px; letter-spacing:3px; text-transform:uppercase; border:0.5px solid rgba(201,168,76,0.3); color:var(--cream-dim); background:transparent; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:all 0.3s; white-space:nowrap; }
        .nav-btn:hover { border-color:var(--gold); color:var(--gold); }
        .nav-btn.gold { background:var(--gold); color:var(--deep); border-color:var(--gold); font-weight:700; }
        .admin-badge { font-size:9px; letter-spacing:2px; text-transform:uppercase; background:rgba(201,168,76,0.12); border:0.5px solid rgba(201,168,76,0.28); color:var(--gold); padding:3px 10px; }

        /* TOAST */
        .toast { position:fixed; top:80px; left:50%; transform:translateX(-50%) translateY(-20px); padding:13px 26px; font-size:14px; z-index:999; pointer-events:none; opacity:0; transition:all 0.4s cubic-bezier(0.16,1,0.3,1); white-space:nowrap; display:flex; align-items:center; gap:10px; }
        .toast.show { opacity:1; transform:translateX(-50%) translateY(0); }
        .toast.success { background:rgba(25,55,35,0.97); border:0.5px solid rgba(90,185,122,0.4); color:#8de8a8; }
        .toast.error   { background:rgba(55,15,10,0.97); border:0.5px solid rgba(224,92,58,0.4); color:#f0896a; }

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
        .page { position:relative; z-index:1; max-width:1060px; margin:0 auto; padding:0 32px 80px; }
        .main-layout { display:grid; grid-template-columns:420px 1fr; gap:28px; align-items:start; }

        /* FORM CARD */
        .form-card { background:var(--card-bg); border:0.5px solid rgba(201,168,76,0.16); padding:36px 32px; position:sticky; top:88px; position:relative; animation:fadeUp 0.6s 0.3s both; }
        .form-card::before, .form-card::after { content:''; position:absolute; width:14px; height:14px; border-color:var(--gold); border-style:solid; opacity:.35; }
        .form-card::before { top:10px; left:10px; border-width:1px 0 0 1px; }
        .form-card::after  { bottom:10px; right:10px; border-width:0 1px 1px 0; }
        .card-title { font-family:'Playfair Display',serif; font-size:24px; font-weight:400; color:var(--cream); margin-bottom:6px; }
        .card-sub   { font-size:13px; font-weight:300; color:var(--cream-dim); margin-bottom:28px; }

        /* FIELDS */
        .field { margin-bottom:20px; }
        label  { display:block; font-size:10px; letter-spacing:3.5px; text-transform:uppercase; color:rgba(201,168,76,0.6); margin-bottom:8px; }
        .input-wrap { position:relative; }
        .i-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); font-size:14px; color:rgba(201,168,76,0.3); pointer-events:none; transition:color 0.3s; }
        .i-icon.top { top:14px; transform:none; }

        input[type="text"], textarea {
            width:100%; padding:12px 16px 12px 42px;
            background:rgba(255,255,255,0.03); border:0.5px solid rgba(201,168,76,0.18);
            color:var(--cream); font-family:'Cormorant Garamond',serif; font-size:16px; font-weight:300;
            outline:none; transition:all 0.3s; border-radius:0; -webkit-appearance:none;
        }
        textarea { padding-top:12px; resize:vertical; min-height:80px; }
        input:focus, textarea:focus { border-color:rgba(201,168,76,0.52); background:rgba(201,168,76,0.04); box-shadow:0 0 0 3px rgba(201,168,76,0.07); }
        input:focus ~ .i-icon, textarea:focus ~ .i-icon { color:rgba(201,168,76,0.75); }
        ::placeholder { color:rgba(245,237,214,0.2); font-size:15px; }
        .focus-bar { position:absolute; bottom:0; left:0; right:0; height:1.5px; background:linear-gradient(to right,transparent,var(--gold),transparent); transform:scaleX(0); transform-origin:center; transition:transform 0.35s cubic-bezier(0.4,0,0.2,1); pointer-events:none; }
        input:focus + .focus-bar, textarea:focus + .focus-bar { transform:scaleX(1); }

        /* UPLOAD ZONE */
        .upload-zone { border:0.5px dashed rgba(201,168,76,0.3); padding:22px 16px; text-align:center; cursor:pointer; transition:all 0.3s; position:relative; overflow:hidden; background:rgba(201,168,76,0.02); }
        .upload-zone:hover, .upload-zone.dragging { border-color:var(--gold); background:rgba(201,168,76,0.06); }
        .upload-zone input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
        .upload-icon { font-size:28px; color:rgba(201,168,76,0.35); margin-bottom:8px; display:block; }
        .upload-text { font-size:14px; font-weight:300; color:var(--cream-dim); margin-bottom:3px; }
        .upload-hint { font-size:11px; letter-spacing:1px; color:rgba(201,168,76,0.35); }
        .img-preview-wrap { display:none; position:relative; margin-top:10px; }
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

        /* CATEGORIES LIST */
        .list-card { animation:fadeUp 0.6s 0.4s both; }
        .list-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
        .list-title { font-family:'Playfair Display',serif; font-size:26px; font-weight:400; color:var(--cream); }
        .list-count { font-size:11px; letter-spacing:3px; text-transform:uppercase; color:rgba(201,168,76,0.4); }
        .cat-list { display:flex; flex-direction:column; gap:2px; }

        .cat-item {
            background:var(--card-bg); border:0.5px solid rgba(201,168,76,0.1);
            display:flex; align-items:center; overflow:hidden;
            opacity:0; transform:translateX(20px);
            transition:opacity 0.5s ease, transform 0.5s ease, border-color 0.3s;
        }
        .cat-item.visible { opacity:1; transform:translateX(0); }
        .cat-item:hover { border-color:rgba(201,168,76,0.28); }

        .cat-img { width:92px; height:92px; flex-shrink:0; overflow:hidden; background:rgba(45,21,0,0.5); }
        .cat-img img { width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.5s; }
        .cat-item:hover .cat-img img { transform:scale(1.08); }
        .cat-img-ph { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:28px; opacity:.2; }

        .cat-info { flex:1; padding:16px 20px; min-width:0; }
        .cat-name { font-family:'Playfair Display',serif; font-size:19px; font-weight:400; color:var(--cream); margin-bottom:4px; }
        .cat-detail { font-size:13px; font-weight:300; color:var(--cream-dim); line-height:1.6; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

        /* ACTION BUTTONS */
        .cat-actions { display:flex; flex-direction:column; gap:0; flex-shrink:0; height:92px; }
        .cat-act {
            flex:1; width:48px; display:flex; align-items:center; justify-content:center;
            font-size:15px; border:none; cursor:pointer; transition:all 0.25s;
            border-left:0.5px solid rgba(201,168,76,0.08); text-decoration:none;
        }
        .cat-act-edit   { background:rgba(201,168,76,0.06); color:rgba(201,168,76,0.55); border-bottom:0.5px solid rgba(201,168,76,0.08); }
        .cat-act-edit:hover   { background:rgba(201,168,76,0.15); color:var(--gold); }
        .cat-act-delete { background:rgba(224,92,58,0.06); color:rgba(224,92,58,0.55); }
        .cat-act-delete:hover { background:rgba(224,92,58,0.15); color:#e05c3a; }

        /* EMPTY */
        .list-empty { text-align:center; padding:60px 20px; background:var(--card-bg); border:0.5px solid rgba(201,168,76,0.1); }
        .list-empty-icon { font-size:44px; opacity:.18; margin-bottom:14px; }
        .list-empty p { font-size:15px; font-weight:300; color:var(--cream-dim); }

        /* ── EDIT MODAL ── */
        .modal-overlay { position:fixed; inset:0; background:rgba(4,1,0,0.88); z-index:500; display:none; align-items:center; justify-content:center; padding:20px; backdrop-filter:blur(4px); }
        .modal-overlay.open { display:flex; animation:fadeIn 0.3s both; }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }

        .modal { background:rgba(22,8,0,0.99); border:0.5px solid rgba(201,168,76,0.24); padding:0; max-width:520px; width:100%; position:relative; animation:popUp 0.35s cubic-bezier(0.16,1,0.3,1) both; overflow:hidden; }
        @keyframes popUp { from{opacity:0;transform:scale(0.94) translateY(18px)} to{opacity:1;transform:scale(1) translateY(0)} }
        .modal::before, .modal::after { content:''; position:absolute; width:14px; height:14px; border-color:var(--gold); border-style:solid; opacity:.4; z-index:1; }
        .modal::before { top:10px; left:10px; border-width:1px 0 0 1px; }
        .modal::after  { bottom:10px; right:10px; border-width:0 1px 1px 0; }

        .modal-head { padding:24px 28px 18px; border-bottom:0.5px solid rgba(201,168,76,0.1); display:flex; align-items:center; justify-content:space-between; }
        .modal-title { font-family:'Playfair Display',serif; font-size:22px; color:var(--cream); }
        .modal-close { width:32px; height:32px; display:flex; align-items:center; justify-content:center; font-size:18px; color:rgba(201,168,76,0.4); cursor:pointer; border:none; background:none; transition:color 0.2s; }
        .modal-close:hover { color:var(--gold); }

        .modal-body { padding:24px 28px 28px; }

        /* current image preview in modal */
        .modal-cur-img { width:100%; height:120px; object-fit:cover; border:0.5px solid rgba(201,168,76,0.15); display:block; margin-bottom:16px; }
        .modal-cur-ph  { width:100%; height:120px; background:rgba(45,21,0,0.5); display:flex; align-items:center; justify-content:center; font-size:36px; opacity:.2; margin-bottom:16px; }

        .modal-field { margin-bottom:18px; }

        .btn-update { width:100%; padding:14px; background:var(--gold); color:var(--deep); font-family:'Cormorant Garamond',serif; font-size:13px; font-weight:700; letter-spacing:4px; text-transform:uppercase; border:none; cursor:pointer; transition:all 0.3s; margin-top:6px; display:flex; align-items:center; justify-content:center; gap:8px; }
        .btn-update:hover { background:var(--gold-lite); transform:translateY(-1px); }

        /* DELETE CONFIRM MODAL */
        .del-modal { max-width:400px; }
        .del-modal .modal-body { text-align:center; padding:32px 28px; }
        .del-icon { font-size:40px; margin-bottom:14px; }
        .del-title { font-family:'Playfair Display',serif; font-size:22px; color:var(--cream); margin-bottom:8px; }
        .del-msg { font-size:15px; font-weight:300; color:var(--cream-dim); margin-bottom:26px; line-height:1.7; }
        .del-btns { display:flex; gap:10px; }
        .btn-del-confirm { flex:1; padding:13px; background:#c0392b; color:#fff; font-family:'Cormorant Garamond',serif; font-size:12px; font-weight:700; letter-spacing:3px; text-transform:uppercase; border:none; cursor:pointer; transition:all 0.3s; text-decoration:none; display:flex; align-items:center; justify-content:center; }
        .btn-del-confirm:hover { background:#e05c3a; transform:translateY(-1px); }
        .btn-cancel { flex:1; padding:13px; background:transparent; color:var(--cream-dim); font-family:'Cormorant Garamond',serif; font-size:12px; font-weight:500; letter-spacing:3px; text-transform:uppercase; border:0.5px solid rgba(201,168,76,0.2); cursor:pointer; transition:all 0.3s; }
        .btn-cancel:hover { border-color:var(--gold); color:var(--gold); }

        footer { position:relative; z-index:1; border-top:0.5px solid rgba(201,168,76,0.1); padding:28px 40px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; max-width:1060px; margin:0 auto; }
        footer .f-brand { font-family:'Playfair Display',serif; font-size:17px; color:var(--gold); }
        footer .f-note  { font-size:12px; font-weight:300; color:rgba(245,237,214,.28); }

        @media(max-width:760px){
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

<!-- ══ EDIT MODAL ══ -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-head">
            <div class="modal-title">Edit Category</div>
            <button class="modal-close" onclick="closeEditModal()"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body">
            <form action="" method="POST" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="upd_id" id="upd_id">

                <!-- Current image -->
                <div id="modalImgWrap"></div>

                <div class="modal-field">
                    <label for="upd_name">Category Name</label>
                    <div class="input-wrap">
                        <i class="bi bi-tag-fill i-icon"></i>
                        <input type="text" id="upd_name" name="upd_name" placeholder="Category name" required>
                        <div class="focus-bar"></div>
                    </div>
                </div>

                <div class="modal-field">
                    <label for="upd_details">Description</label>
                    <div class="input-wrap">
                        <i class="bi bi-card-text i-icon top"></i>
                        <textarea id="upd_details" name="upd_details" placeholder="Description (optional)…"></textarea>
                        <div class="focus-bar"></div>
                    </div>
                </div>

                <div class="modal-field">
                    <label>New Image <span style="color:rgba(201,168,76,0.38);font-size:9px;letter-spacing:1px">(leave blank to keep current)</span></label>
                    <div class="upload-zone" id="editUploadZone" style="padding:14px">
                        <input type="file" name="upd_image" id="editImageInput" accept="image/*">
                        <i class="bi bi-cloud-arrow-up upload-icon" style="font-size:22px"></i>
                        <div class="upload-text" style="font-size:13px">Click or drag & drop</div>
                        <div class="upload-hint">JPG · PNG · WEBP · GIF</div>
                    </div>
                    <div class="img-preview-wrap" id="editPreviewWrap" style="position:relative">
                        <img src="" alt="" class="img-preview" id="editImgPreview">
                        <button type="button" class="img-preview-remove" id="editRemoveImg"><i class="bi bi-x"></i></button>
                    </div>
                </div>

                <button type="submit" name="update" class="btn-update">
                    <i class="bi bi-check2-circle"></i> Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ══ DELETE CONFIRM MODAL ══ -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal del-modal">
        <div class="modal-body">
            <div class="del-icon">🗑️</div>
            <div class="del-title">Delete Category?</div>
            <div class="del-msg" id="delMsg">This will permanently delete this category and cannot be undone.</div>
            <div class="del-btns">
                <button class="btn-cancel" onclick="closeDeleteModal()">Go Back</button>
                <a href="#" class="btn-del-confirm" id="delConfirmLink">
                    <i class="bi bi-trash3-fill"></i> &nbsp;Delete
                </a>
            </div>
        </div>
    </div>
</div>

<!-- NAV -->
<?php require("admin_navbar.php"); ?>

<!-- HERO -->
<div class="hero">
    <div class="hero-tag">Admin Panel &nbsp;·&nbsp; Rana's Table</div>
    <h1 class="hero-title">Manage Categories</h1>
    <p class="hero-sub">Add, edit and delete your menu categories.</p>
    <div class="hero-divider"><div class="hdl"></div><div class="hdg"></div><div class="hdl"></div></div>
</div>

<div class="page">
<div class="main-layout">

    <!-- ADD FORM -->
    <div class="form-card">
        <div class="card-title">Add New Category</div>
        <div class="card-sub">Fill in the details and upload a cover image.</div>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="field">
                <label for="cat_name">Category Name</label>
                <div class="input-wrap">
                    <i class="bi bi-tag-fill i-icon"></i>
                    <input type="text" id="cat_name" name="category_name" placeholder="e.g. Starters, Biryani, Desserts" required>
                    <div class="focus-bar"></div>
                </div>
            </div>
            <div class="field">
                <label for="cat_detail">Category Description</label>
                <div class="input-wrap">
                    <i class="bi bi-card-text i-icon top"></i>
                    <textarea id="cat_detail" name="category_details" placeholder="Brief description of this category…"></textarea>
                    <div class="focus-bar"></div>
                </div>
            </div>
            <div class="field">
                <label>Category Image</label>
                <div class="upload-zone" id="uploadZone">
                    <input type="file" name="image" id="imageInput" accept="image/*" required>
                    <i class="bi bi-cloud-arrow-up upload-icon"></i>
                    <div class="upload-text">Click or drag & drop an image</div>
                    <div class="upload-hint">JPG · PNG · WEBP · GIF · JFIF</div>
                </div>
                <div class="img-preview-wrap" id="previewWrap" style="position:relative">
                    <img src="" alt="Preview" class="img-preview" id="imgPreview">
                    <button type="button" class="img-preview-remove" id="removeImg"><i class="bi bi-x"></i></button>
                    <div class="img-name" id="imgName"></div>
                </div>
            </div>
            <button type="submit" name="submit" class="btn-submit">
                <i class="bi bi-plus-circle-fill"></i> Add Category
            </button>
        </form>
    </div>

    <!-- CATEGORY LIST -->
    <div class="list-card">
        <div class="list-header">
            <div class="list-title">All Categories</div>
            <div class="list-count"><?php echo count($categories); ?> total</div>
        </div>

        <?php if(!empty($categories)): ?>
        <div class="cat-list">
            <?php foreach($categories as $i => $cat): ?>
            <div class="cat-item" style="transition-delay:<?php echo $i*0.07; ?>s">

                <div class="cat-img">
                    <?php if(!empty($cat['category_image'])): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($cat['category_image']); ?>"
                             alt="<?php echo htmlspecialchars($cat['category_name']); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="cat-img-ph">🏷</div>
                    <?php endif; ?>
                </div>

                <div class="cat-info">
                    <div class="cat-name"><?php echo htmlspecialchars($cat['category_name']); ?></div>
                    <div class="cat-detail"><?php echo !empty($cat['category_details']) ? htmlspecialchars($cat['category_details']) : '<em style="opacity:.35">No description</em>'; ?></div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="cat-actions">
                    <!-- Edit -->
                    <button class="cat-act cat-act-edit" title="Edit"
                        onclick="openEditModal(
                            <?php echo $cat['id']; ?>,
                            '<?php echo addslashes(htmlspecialchars($cat['category_name'])); ?>',
                            '<?php echo addslashes(htmlspecialchars($cat['category_details'] ?? '')); ?>',
                            '<?php echo !empty($cat['category_image']) ? '../uploads/'.htmlspecialchars($cat['category_image']) : ''; ?>'
                        )">
                        <i class="bi bi-pencil-fill"></i>
                    </button>
                    <!-- Delete -->
                    <button class="cat-act cat-act-delete" title="Delete"
                        onclick="openDeleteModal(<?php echo $cat['id']; ?>, '<?php echo addslashes(htmlspecialchars($cat['category_name'])); ?>')">
                        <i class="bi bi-trash3-fill"></i>
                    </button>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="list-empty">
            <div class="list-empty-icon">🏷</div>
            <p>No categories yet. Add your first one!</p>
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
if(toast){ setTimeout(()=>toast.classList.add('show'),120); setTimeout(()=>toast.classList.remove('show'),4200); }

/* Scroll reveal */
const obs = new IntersectionObserver(entries=>{
    entries.forEach(e=>{ if(e.isIntersecting) e.target.classList.add('visible'); });
},{threshold:0.06});
document.querySelectorAll('.cat-item').forEach(c=>obs.observe(c));

/* ── ADD FORM image preview ── */
function makePreview(fileInput, previewWrap, imgPreview, uploadZone, imgName){
    fileInput.addEventListener('change', ()=>{
        const file = fileInput.files[0];
        if(!file) return;
        const r = new FileReader();
        r.onload = e => {
            imgPreview.src = e.target.result;
            if(imgName) imgName.textContent = file.name;
            previewWrap.classList.add('show');
            uploadZone.style.display = 'none';
        };
        r.readAsDataURL(file);
    });
}
makePreview(
    document.getElementById('imageInput'),
    document.getElementById('previewWrap'),
    document.getElementById('imgPreview'),
    document.getElementById('uploadZone'),
    document.getElementById('imgName')
);
document.getElementById('removeImg').addEventListener('click',()=>{
    document.getElementById('imageInput').value='';
    document.getElementById('previewWrap').classList.remove('show');
    document.getElementById('uploadZone').style.display='';
    document.getElementById('imgPreview').src='';
});

/* Drag & drop (add form) */
const uz = document.getElementById('uploadZone');
['dragenter','dragover'].forEach(ev=>uz.addEventListener(ev,e=>{e.preventDefault();uz.classList.add('dragging');}));
['dragleave','drop'].forEach(ev=>uz.addEventListener(ev,e=>{e.preventDefault();uz.classList.remove('dragging');}));
uz.addEventListener('drop',e=>{
    const file=e.dataTransfer.files[0];
    if(file&&file.type.startsWith('image/')){
        const dt=new DataTransfer(); dt.items.add(file);
        document.getElementById('imageInput').files=dt.files;
        document.getElementById('imageInput').dispatchEvent(new Event('change'));
    }
});

/* ── EDIT MODAL ── */
function openEditModal(id, name, details, imgSrc){
    document.getElementById('upd_id').value     = id;
    document.getElementById('upd_name').value   = name;
    document.getElementById('upd_details').value= details;

    // Current image
    const wrap = document.getElementById('modalImgWrap');
    if(imgSrc){
        wrap.innerHTML = `<img src="${imgSrc}" class="modal-cur-img" alt="${name}">`;
    } else {
        wrap.innerHTML = `<div class="modal-cur-ph">🏷</div>`;
    }

    // Reset edit upload preview
    document.getElementById('editImageInput').value = '';
    document.getElementById('editPreviewWrap').classList.remove('show');
    document.getElementById('editUploadZone').style.display = '';
    document.getElementById('editImgPreview').src = '';

    document.getElementById('editModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeEditModal(){
    document.getElementById('editModal').classList.remove('open');
    document.body.style.overflow = '';
}

// Edit image preview
makePreview(
    document.getElementById('editImageInput'),
    document.getElementById('editPreviewWrap'),
    document.getElementById('editImgPreview'),
    document.getElementById('editUploadZone'),
    null
);
document.getElementById('editRemoveImg').addEventListener('click',()=>{
    document.getElementById('editImageInput').value='';
    document.getElementById('editPreviewWrap').classList.remove('show');
    document.getElementById('editUploadZone').style.display='';
    document.getElementById('editImgPreview').src='';
});

// Click outside to close edit
document.getElementById('editModal').addEventListener('click',function(e){
    if(e.target===this) closeEditModal();
});

/* ── DELETE MODAL ── */
function openDeleteModal(id, name){
    document.getElementById('delMsg').textContent = `Delete "${name}"? This cannot be undone.`;
    document.getElementById('delConfirmLink').href = `?delete=${id}`;
    document.getElementById('deleteModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeDeleteModal(){
    document.getElementById('deleteModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.getElementById('deleteModal').addEventListener('click',function(e){
    if(e.target===this) closeDeleteModal();
});

/* Escape key */
document.addEventListener('keydown',e=>{
    if(e.key==='Escape'){ closeEditModal(); closeDeleteModal(); }
});
</script>
</body>
</html>