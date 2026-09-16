<?php
include_once("../config.php");
require_once("../auth.php");
require_admin();

// Handle role toggle via AJAX
if(isset($_POST['toggle_role']) && isset($_POST['user_id'])){
    $uid = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'] === 'admin' ? 'admin' : 'user';
    $stmt = $connection->prepare("UPDATE `uesr` SET `role`=? WHERE `id`=?");
    $stmt->bind_param("si", $new_role, $uid);
    echo json_encode(['success' => $stmt->execute(), 'role' => $new_role]);
    exit;
}

// Handle delete
if(isset($_POST['delete_user']) && isset($_POST['user_id'])){
    $uid = (int)$_POST['user_id'];
    $stmt = $connection->prepare("DELETE FROM `uesr` WHERE `id`=? AND `role`!='admin'");
    $stmt->bind_param("i", $uid);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

// Fetch all users
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if($search){
    $stmt = $connection->prepare("SELECT * FROM `uesr` WHERE `name` LIKE ? OR `email` LIKE ? ORDER BY id DESC");
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
} else {
    $stmt = $connection->prepare("SELECT * FROM `uesr` ORDER BY id DESC");
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_users  = count($users);
$total_admins = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$total_regular = $total_users - $total_admins;

$current_page = 'users_admin.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customers — Rana's Table Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Cormorant+Garamond:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
    --gold:#C9A84C; --gold-lite:#E8C96A; --gold-dim:rgba(201,168,76,0.14);
    --deep:#0D0500; --cream:#F5EDD6; --cream-dim:rgba(245,237,214,0.5);
    --card-bg:rgba(20,7,0,0.88); --card-border:rgba(201,168,76,0.13);
    --sidebar-w:250px; --topbar-h:64px;
    --success:#5AB97A; --danger:#E05C3A; --processing:#6AB4E8; --pending:#E0A93A;
}
html{scroll-behavior:smooth}
body{
    font-family:'Cormorant Garamond',serif;
    background:var(--deep); color:var(--cream);
    min-height:100vh; overflow-x:hidden;
}
.bg-fixed{
    position:fixed; inset:0; z-index:0; pointer-events:none;
    background:radial-gradient(ellipse 80% 50% at 50% -5%,#2a0e00 0%,transparent 60%),#0D0500;
}

/* ── PAGE LAYOUT ── */
.page-wrap{
    margin-left:var(--sidebar-w);
    min-height:100vh;
    display:flex; flex-direction:column;
    transition:margin-left .4s cubic-bezier(0.16,1,0.3,1);
    position:relative; z-index:1;
}
.page-wrap.expanded{margin-left:0}

.content{padding:28px 32px;flex:1}

@keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.a1{animation:fadeUp .5s .05s both}
.a2{animation:fadeUp .5s .12s both}
.a3{animation:fadeUp .5s .19s both}

/* ── WELCOME ── */
.page-header{
    display:flex;align-items:flex-end;justify-content:space-between;
    flex-wrap:wrap;gap:14px;margin-bottom:26px;
}
.page-header h1{font-family:'Playfair Display',serif;font-size:26px;font-weight:400}
.page-header h1 em{color:var(--gold);font-style:italic}
.page-header p{font-size:14px;font-weight:300;color:var(--cream-dim);margin-top:3px}

/* ── STAT CARDS ── */
.stat-row{display:grid;grid-template-columns:repeat(3,1fr);gap:1.5px;margin-bottom:22px}
.stat-card{
    background:var(--card-bg);border:0.5px solid var(--card-border);
    padding:20px 24px;position:relative;overflow:hidden;transition:border-color .3s,transform .25s;
}
.stat-card:hover{border-color:rgba(201,168,76,0.28);transform:translateY(-2px)}
.sc-icon{font-size:18px;margin-bottom:10px;display:block}
.sc-val{font-family:'Playfair Display',serif;font-size:28px;line-height:1;color:var(--cream);margin-bottom:4px}
.sc-val.gold{color:var(--gold)}
.sc-label{font-size:10px;letter-spacing:3px;text-transform:uppercase;color:var(--cream-dim)}
.sc-accent{position:absolute;bottom:0;left:0;right:0;height:2px}

/* ── TOOLBAR ── */
.toolbar{
    display:flex;align-items:center;justify-content:space-between;
    flex-wrap:wrap;gap:14px;margin-bottom:18px;
}
.search-wrap{position:relative;flex:1;min-width:220px;max-width:360px}
.search-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:rgba(201,168,76,0.4);font-size:14px}
.search-input{
    width:100%;padding:10px 16px 10px 40px;
    background:rgba(255,255,255,0.03);border:0.5px solid rgba(201,168,76,0.2);
    color:var(--cream);font-family:'Cormorant Garamond',serif;font-size:15px;
    outline:none;transition:all .3s;
}
.search-input:focus{border-color:rgba(201,168,76,0.5);background:rgba(201,168,76,0.04)}
.search-input::placeholder{color:rgba(245,237,214,0.25)}

.filter-tabs{display:flex;gap:1px;background:rgba(201,168,76,0.07)}
.filter-tab{
    padding:9px 18px;font-family:'Cormorant Garamond',serif;
    font-size:11px;letter-spacing:2px;text-transform:uppercase;
    border:none;background:transparent;color:var(--cream-dim);
    cursor:pointer;transition:all .2s;white-space:nowrap;
}
.filter-tab.active{background:rgba(201,168,76,0.1);color:var(--gold)}
.filter-tab:hover:not(.active){background:rgba(201,168,76,0.05);color:var(--cream)}

/* ── TABLE ── */
.table-panel{background:var(--card-bg);border:0.5px solid var(--card-border);overflow:hidden}
.table-head-row{
    padding:16px 24px;border-bottom:0.5px solid rgba(201,168,76,0.08);
    display:flex;align-items:center;justify-content:space-between;
}
.table-head-title{font-family:'Playfair Display',serif;font-size:18px;color:var(--cream)}
.table-count{font-size:12px;color:var(--cream-dim)}

.users-table{width:100%;border-collapse:collapse}
.users-table th{
    font-size:9px;letter-spacing:3px;text-transform:uppercase;
    color:rgba(201,168,76,0.38);font-weight:500;
    padding:10px 20px;border-bottom:0.5px solid rgba(201,168,76,0.07);text-align:left;white-space:nowrap;
}
.users-table td{
    padding:14px 20px;border-bottom:0.5px solid rgba(201,168,76,0.05);
    font-size:14px;font-weight:300;color:var(--cream-dim);vertical-align:middle;
}
.users-table tr:last-child td{border-bottom:none}
.users-table tr:hover td{background:rgba(201,168,76,0.03)}

.user-avatar{
    width:36px;height:36px;border-radius:50%;
    border:0.5px solid rgba(201,168,76,0.25);
    display:flex;align-items:center;justify-content:center;
    font-size:16px;background:rgba(201,168,76,0.06);
    flex-shrink:0;
}
.user-info{display:flex;align-items:center;gap:12px}
.user-name{font-size:15px;color:var(--cream);font-weight:400}
.user-email{font-size:12px;color:var(--cream-dim);margin-top:1px}

/* ROLE BADGE */
.role-badge{
    font-size:9px;font-weight:700;letter-spacing:2px;text-transform:uppercase;
    padding:4px 10px;border:0.5px solid;display:inline-flex;align-items:center;gap:5px;
}
.role-admin{color:var(--gold);border-color:rgba(201,168,76,0.4);background:rgba(201,168,76,0.1)}
.role-user {color:var(--cream-dim);border-color:rgba(245,237,214,0.15);background:rgba(245,237,214,0.04)}

/* VERIFIED BADGE */
.verified-badge{
    font-size:9px;letter-spacing:1px;text-transform:uppercase;
    padding:3px 8px;border:0.5px solid;display:inline-block;
}
.v-yes{color:var(--success);border-color:rgba(90,185,122,.35);background:rgba(90,185,122,.07)}
.v-no {color:var(--pending);border-color:rgba(224,169,58,.35);background:rgba(224,169,58,.07)}

/* ACTION BUTTONS */
.action-btns{display:flex;gap:8px;align-items:center}
.act-btn{
    padding:6px 13px;font-family:'Cormorant Garamond',serif;
    font-size:10px;letter-spacing:2px;text-transform:uppercase;
    border:0.5px solid;background:transparent;cursor:pointer;
    transition:all .22s;display:inline-flex;align-items:center;gap:5px;
}
.act-btn-admin{color:var(--gold);border-color:rgba(201,168,76,0.35)}
.act-btn-admin:hover{background:rgba(201,168,76,0.12);border-color:var(--gold)}
.act-btn-user{color:var(--cream-dim);border-color:rgba(245,237,214,0.2)}
.act-btn-user:hover{background:rgba(245,237,214,0.06);border-color:rgba(245,237,214,0.4)}
.act-btn-del{color:var(--danger);border-color:rgba(224,92,58,0.3)}
.act-btn-del:hover{background:rgba(224,92,58,0.1);border-color:var(--danger)}
.act-btn:disabled{opacity:.35;cursor:not-allowed}

/* EMPTY STATE */
.empty-state{padding:56px 24px;text-align:center;color:var(--cream-dim)}
.empty-state i{font-size:40px;color:rgba(201,168,76,0.2);margin-bottom:14px;display:block}
.empty-state p{font-size:15px;font-weight:300}

/* TOAST */
.toast{
    position:fixed;bottom:28px;right:28px;z-index:999;
    background:rgba(12,4,0,0.97);border:0.5px solid rgba(201,168,76,0.3);
    padding:14px 20px;font-size:14px;font-weight:300;
    display:flex;align-items:center;gap:10px;
    opacity:0;transform:translateY(12px);
    transition:all .35s cubic-bezier(0.16,1,0.3,1);
    pointer-events:none;max-width:320px;
}
.toast.show{opacity:1;transform:translateY(0);pointer-events:all}
.toast-icon{font-size:18px;flex-shrink:0}
.toast.success{border-color:rgba(90,185,122,.4);color:var(--success)}
.toast.error  {border-color:rgba(224,92,58,.4); color:var(--danger)}

/* CONFIRM MODAL */
.modal-overlay{
    display:none;position:fixed;inset:0;
    background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);
    z-index:800;align-items:center;justify-content:center;
}
.modal-overlay.show{display:flex;animation:fadeIn .25s both}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.modal{
    background:rgba(14,5,0,0.98);border:0.5px solid rgba(201,168,76,0.25);
    padding:36px 32px;max-width:400px;width:90%;
    animation:slideUp .3s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.modal-icon{font-size:36px;margin-bottom:16px;display:block;text-align:center}
.modal-title{font-family:'Playfair Display',serif;font-size:20px;color:var(--cream);text-align:center;margin-bottom:8px}
.modal-title em{color:var(--gold);font-style:italic}
.modal-msg{font-size:14px;font-weight:300;color:var(--cream-dim);text-align:center;line-height:1.7;margin-bottom:28px}
.modal-btns{display:flex;gap:10px}
.modal-btn{flex:1;padding:12px;font-family:'Cormorant Garamond',serif;font-size:12px;letter-spacing:3px;text-transform:uppercase;border:0.5px solid;cursor:pointer;transition:all .22s}
.modal-btn-cancel{background:transparent;border-color:rgba(245,237,214,0.2);color:var(--cream-dim)}
.modal-btn-cancel:hover{border-color:rgba(245,237,214,0.4);color:var(--cream)}
.modal-btn-confirm{background:var(--gold);border-color:var(--gold);color:var(--deep);font-weight:600}
.modal-btn-confirm:hover{background:var(--gold-lite)}
.modal-btn-confirm.danger{background:var(--danger);border-color:var(--danger);color:#fff}
.modal-btn-confirm.danger:hover{background:#f0896a}

/* FOOTER */
footer{position:relative;z-index:1;border-top:0.5px solid rgba(201,168,76,0.08);padding:18px 32px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px}
.f-brand{font-family:'Playfair Display',serif;font-size:15px;color:var(--gold)}
.f-note{font-size:12px;font-weight:300;color:rgba(245,237,214,.18)}

/* RESPONSIVE */
@media(max-width:900px){
    .page-wrap{margin-left:0!important}
    .stat-row{grid-template-columns:repeat(2,1fr)}
    .content{padding:18px 16px}
    .users-table th:nth-child(4),
    .users-table td:nth-child(4){display:none}
}
@media(max-width:600px){
    .stat-row{grid-template-columns:1fr}
    .users-table th:nth-child(5),
    .users-table td:nth-child(5){display:none}
    .filter-tabs{display:none}
    .toolbar{flex-direction:column;align-items:stretch}
    .search-wrap{max-width:100%}
}
</style>
</head>
<body>
<div class="bg-fixed"></div>

<?php include("admin_navbar.php"); ?>

<div class="page-wrap" id="pageWrap">
<div class="content">

    <!-- Header -->
    <div class="page-header a1">
        <div>
            <h1>Customer <em>Management</em></h1>
            <p>View, manage roles and oversee all registered users.</p>
        </div>
        <span style="font-size:13px;font-weight:300;color:rgba(201,168,76,0.45)"><?php echo date('l, d F Y'); ?></span>
    </div>

    <!-- Stat Cards -->
    <div class="stat-row a2">
        <div class="stat-card">
            <span class="sc-icon">👥</span>
            <div class="sc-val"><?php echo $total_users; ?></div>
            <div class="sc-label">Total Users</div>
            <div class="sc-accent" style="background:linear-gradient(to right,var(--gold),transparent)"></div>
        </div>
        <div class="stat-card">
            <span class="sc-icon">🛡</span>
            <div class="sc-val gold"><?php echo $total_admins; ?></div>
            <div class="sc-label">Admins</div>
            <div class="sc-accent" style="background:linear-gradient(to right,var(--processing),transparent)"></div>
        </div>
        <div class="stat-card">
            <span class="sc-icon">🙋</span>
            <div class="sc-val"><?php echo $total_regular; ?></div>
            <div class="sc-label">Regular Users</div>
            <div class="sc-accent" style="background:linear-gradient(to right,var(--success),transparent)"></div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar a3">
        <form method="GET" style="display:flex;gap:10px;flex:1;flex-wrap:wrap;align-items:center">
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input class="search-input" type="text" name="search" placeholder="Search name or email…" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <?php if($search): ?>
            <a href="users_admin.php" style="font-size:12px;color:rgba(201,168,76,0.5);text-decoration:none;letter-spacing:1px;padding:10px 0;transition:color .2s" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='rgba(201,168,76,0.5)'">✕ Clear</a>
            <?php endif; ?>
        </form>
        <div class="filter-tabs">
            <button class="filter-tab active" onclick="filterTable('all',this)">All</button>
            <button class="filter-tab" onclick="filterTable('admin',this)">Admins</button>
            <button class="filter-tab" onclick="filterTable('user',this)">Users</button>
        </div>
    </div>

    <!-- Table -->
    <div class="table-panel a3">
        <div class="table-head-row">
            <div class="table-head-title">All Users</div>
            <div class="table-count"><?php echo $total_users; ?> total</div>
        </div>

        <?php if(!empty($users)): ?>
        <table class="users-table" id="usersTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Verified</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($users as $u):
                $is_admin = $u['role'] === 'admin';
                $is_verified = isset($u['is_verified']) ? $u['is_verified'] : 1;
            ?>
            <tr data-role="<?php echo $u['role']; ?>" data-id="<?php echo $u['id']; ?>">
                <td style="color:rgba(201,168,76,0.4);font-size:13px"><?php echo str_pad($u['id'],3,'0',STR_PAD_LEFT); ?></td>
                <td>
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo strtoupper(mb_substr($u['name'],0,1)); ?>
                        </div>
                        <div>
                            <div class="user-name"><?php echo htmlspecialchars($u['name']); ?></div>
                            <div class="user-email"><?php echo htmlspecialchars($u['email']); ?></div>
                        </div>
                    </div>
                </td>
                <td><?php echo htmlspecialchars($u['phone'] ?? '—'); ?></td>
                <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?php echo htmlspecialchars($u['address'] ?? ''); ?>">
                    <?php echo htmlspecialchars($u['address'] ?? '—'); ?>
                </td>
                <td>
                    <span class="verified-badge <?php echo $is_verified ? 'v-yes' : 'v-no'; ?>">
                        <?php echo $is_verified ? '✓ Verified' : '⏳ Pending'; ?>
                    </span>
                </td>
                <td>
                    <span class="role-badge <?php echo $is_admin ? 'role-admin' : 'role-user'; ?>" id="role-badge-<?php echo $u['id']; ?>">
                        <?php if($is_admin): ?><i class="bi bi-shield-fill"></i> Admin<?php else: ?><i class="bi bi-person-fill"></i> User<?php endif; ?>
                    </span>
                </td>
                <td>
                    <div class="action-btns">
                        <?php if($is_admin): ?>
                        <button class="act-btn act-btn-user" onclick="confirmRoleChange(<?php echo $u['id']; ?>,'user','<?php echo htmlspecialchars($u['name']); ?>')">
                            <i class="bi bi-person-fill"></i> Revoke Admin
                        </button>
                        <?php else: ?>
                        <button class="act-btn act-btn-admin" onclick="confirmRoleChange(<?php echo $u['id']; ?>,'admin','<?php echo htmlspecialchars($u['name']); ?>')">
                            <i class="bi bi-shield-fill"></i> Make Admin
                        </button>
                        <?php endif; ?>
                        <?php if(!$is_admin): ?>
                        <button class="act-btn act-btn-del" onclick="confirmDelete(<?php echo $u['id']; ?>,'<?php echo htmlspecialchars($u['name']); ?>')">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-people"></i>
            <p><?php echo $search ? "No users match \"$search\"." : "No users registered yet."; ?></p>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /content -->

<footer>
    <div class="f-brand">Rana's Table</div>
    <div class="f-note">© <?php echo date('Y'); ?> Rana's Table &nbsp;·&nbsp; Admin Panel</div>
</footer>
</div><!-- /page-wrap -->

<!-- ROLE CHANGE MODAL -->
<div class="modal-overlay" id="roleModal">
    <div class="modal">
        <span class="modal-icon" id="roleModalIcon">🛡</span>
        <div class="modal-title" id="roleModalTitle">Make <em>Admin</em>?</div>
        <div class="modal-msg" id="roleModalMsg"></div>
        <div class="modal-btns">
            <button class="modal-btn modal-btn-cancel" onclick="closeModals()">Cancel</button>
            <button class="modal-btn modal-btn-confirm" id="roleModalConfirm">Confirm</button>
        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <span class="modal-icon">⚠️</span>
        <div class="modal-title">Delete <em>User</em>?</div>
        <div class="modal-msg" id="deleteModalMsg"></div>
        <div class="modal-btns">
            <button class="modal-btn modal-btn-cancel" onclick="closeModals()">Cancel</button>
            <button class="modal-btn modal-btn-confirm danger" id="deleteModalConfirm">Delete</button>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast">
    <span class="toast-icon" id="toastIcon"></span>
    <span id="toastMsg"></span>
</div>

<script>
/* ── sync page-wrap with sidebar toggle ── */
window.addEventListener('DOMContentLoaded',()=>{
    // Override the admToggleSidebar from navbar to also toggle page-wrap
    const origToggle = window.admToggleSidebar;
    window.admToggleSidebar = function(){
        origToggle && origToggle();
        if(window.innerWidth > 900){
            const collapsed = document.getElementById('admSidebar').classList.contains('collapsed');
            document.getElementById('pageWrap').classList.toggle('expanded', collapsed);
        }
    };
    // Restore state
    if(window.innerWidth > 900 && localStorage.getItem('admSidebarCollapsed') === '1'){
        document.getElementById('pageWrap').classList.add('expanded');
    }
});

/* ── Toast ── */
function showToast(msg, type='success'){
    const t = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    document.getElementById('toastIcon').textContent = type === 'success' ? '✓' : '✕';
    t.className = 'toast show ' + type;
    clearTimeout(t._timer);
    t._timer = setTimeout(()=> t.className = 'toast', 3500);
}

/* ── Filter table ── */
function filterTable(role, btn){
    document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#usersTable tbody tr').forEach(row => {
        row.style.display = (role === 'all' || row.dataset.role === role) ? '' : 'none';
    });
}

/* ── Search submit on type ── */
const searchInput = document.querySelector('.search-input');
if(searchInput){
    let debounce;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => searchInput.closest('form').submit(), 500);
    });
}

/* ── Role Change ── */
let pendingRoleId = null, pendingRole = null;
function confirmRoleChange(id, newRole, name){
    pendingRoleId = id; pendingRole = newRole;
    const isAdmin = newRole === 'admin';
    document.getElementById('roleModalIcon').textContent   = isAdmin ? '🛡' : '👤';
    document.getElementById('roleModalTitle').innerHTML    = isAdmin ? 'Make <em>Admin</em>?' : 'Revoke <em>Admin</em>?';
    document.getElementById('roleModalMsg').textContent    = isAdmin
        ? `"${name}" will gain full admin access to the dashboard, orders, products and all settings.`
        : `"${name}" will lose all admin privileges and revert to a regular customer account.`;
    const btn = document.getElementById('roleModalConfirm');
    btn.textContent = isAdmin ? 'Grant Admin Access' : 'Revoke Access';
    btn.className   = 'modal-btn modal-btn-confirm' + (isAdmin ? '' : ' danger');
    document.getElementById('roleModal').classList.add('show');
}
document.getElementById('roleModalConfirm').addEventListener('click', ()=>{
    if(!pendingRoleId) return;
    const fd = new FormData();
    fd.append('toggle_role', '1');
    fd.append('user_id', pendingRoleId);
    fd.append('new_role', pendingRole);
    fetch('', { method:'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if(data.success){
            const row   = document.querySelector(`tr[data-id="${pendingRoleId}"]`);
            const badge = document.getElementById(`role-badge-${pendingRoleId}`);
            const isAdmin = data.role === 'admin';
            row.dataset.role = data.role;
            badge.className = `role-badge ${isAdmin ? 'role-admin' : 'role-user'}`;
            badge.innerHTML = isAdmin
                ? '<i class="bi bi-shield-fill"></i> Admin'
                : '<i class="bi bi-person-fill"></i> User';
            // swap action button
            const actDiv = row.querySelector('.action-btns');
            actDiv.innerHTML = isAdmin
                ? `<button class="act-btn act-btn-user" onclick="confirmRoleChange(${pendingRoleId},'user','${row.querySelector('.user-name').textContent}')"><i class="bi bi-person-fill"></i> Revoke Admin</button>`
                : `<button class="act-btn act-btn-admin" onclick="confirmRoleChange(${pendingRoleId},'admin','${row.querySelector('.user-name').textContent}')"><i class="bi bi-shield-fill"></i> Make Admin</button>
                   <button class="act-btn act-btn-del" onclick="confirmDelete(${pendingRoleId},'${row.querySelector('.user-name').textContent}')"><i class="bi bi-trash-fill"></i></button>`;
            showToast(isAdmin ? 'Admin access granted.' : 'Admin access revoked.', 'success');
        } else {
            showToast('Something went wrong.', 'error');
        }
        closeModals();
    }).catch(()=> showToast('Network error.','error'));
});

/* ── Delete ── */
let pendingDelId = null;
function confirmDelete(id, name){
    pendingDelId = id;
    document.getElementById('deleteModalMsg').textContent = `"${name}" will be permanently removed. This cannot be undone.`;
    document.getElementById('deleteModal').classList.add('show');
}
document.getElementById('deleteModalConfirm').addEventListener('click', ()=>{
    if(!pendingDelId) return;
    const fd = new FormData();
    fd.append('delete_user','1');
    fd.append('user_id', pendingDelId);
    fetch('', { method:'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if(data.success){
            const row = document.querySelector(`tr[data-id="${pendingDelId}"]`);
            row.style.transition = 'opacity .4s, transform .4s';
            row.style.opacity = '0'; row.style.transform = 'translateX(20px)';
            setTimeout(()=> row.remove(), 400);
            showToast('User deleted successfully.', 'success');
        } else {
            showToast('Could not delete user.', 'error');
        }
        closeModals();
    }).catch(()=> showToast('Network error.','error'));
});

/* ── Close modals ── */
function closeModals(){
    document.getElementById('roleModal').classList.remove('show');
    document.getElementById('deleteModal').classList.remove('show');
    pendingRoleId = pendingRole = pendingDelId = null;
}
document.querySelectorAll('.modal-overlay').forEach(m =>{
    m.addEventListener('click', e => { if(e.target === m) closeModals(); });
});
document.addEventListener('keydown', e => { if(e.key==='Escape') closeModals(); });
</script>
</body>
</html>