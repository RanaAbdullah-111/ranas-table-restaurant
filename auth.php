<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Ensure user is logged in
if (!isset($_SESSION['user'])) {
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
           || isset($_POST['toggle_role'])
           || isset($_POST['delete_user'])
           || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($isAjax) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Authentication required.']);
        exit();
    }

    $redirect = file_exists('login.php') ? 'login.php' : '../login.php';
    header("Location: " . $redirect);
    exit();
}

// 2. Auto-recover user_id, role, name if missing in session
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    global $connection;
    if (!isset($connection)) {
        if (file_exists('config.php')) include_once 'config.php';
        elseif (file_exists('../config.php')) include_once '../config.php';
    }
    if (isset($connection) && $connection) {
        $stmt = $connection->prepare("SELECT id, role, name FROM `uesr` WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $_SESSION['user']);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($u = $res->fetch_assoc()) {
                $_SESSION['user_id']   = $u['id'];
                $_SESSION['role']      = $u['role'] ?? 'user';
                $_SESSION['user_name'] = $u['name'] ?? '';
            }
        }
    }
}

// 3. Admin Authorization Helper
if (!function_exists('require_admin')) {
    function require_admin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Must be logged in
        if (!isset($_SESSION['user'])) {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                   || isset($_POST['toggle_role'])
                   || isset($_POST['delete_user'])
                   || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            if ($isAjax) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Authentication required.']);
                exit();
            }

            $redirect = file_exists('login.php') ? 'login.php' : '../login.php';
            header("Location: " . $redirect);
            exit();
        }

        // Must have 'admin' role
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                   || isset($_POST['toggle_role'])
                   || isset($_POST['delete_user'])
                   || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Access denied: Admin privileges required.']);
                exit();
            }

            $redirect = file_exists('index.php') ? 'index.php' : '../index.php';
            header("Location: " . $redirect);
            exit();
        }
    }
}

// 4. Auto-enforce admin role if required by any script in /admin/
$current_script = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['SCRIPT_NAME'] ?? '');
if (strpos($current_script, '/admin/') !== false && basename($current_script) !== 'admin_navbar.php') {
    require_admin();
}