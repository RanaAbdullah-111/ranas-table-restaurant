<?php
// Admin Authentication Guard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../auth.php';
require_admin();
