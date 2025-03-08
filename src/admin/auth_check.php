<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_user']['Role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>