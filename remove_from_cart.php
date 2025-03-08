<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Mục giỏ hàng không hợp lệ';
    header('Location: cart.php');
    exit;
}

$cart_id = (int)$_GET['id'];

try {
    // Kiểm tra quyền sở hữu
    $checkStmt = $pdo->prepare("
        SELECT * FROM Cart 
        WHERE ID = ? AND UserID = ?
    ");
    $checkStmt->execute([$cart_id, $_SESSION['user_id']]);
    
    if (!$checkStmt->fetch()) {
        $_SESSION['error'] = 'Mục giỏ hàng không tồn tại';
        header('Location: cart.php');
        exit;
    }

    // Xóa mục khỏi giỏ hàng
    $deleteStmt = $pdo->prepare("DELETE FROM Cart WHERE ID = ?");
    $deleteStmt->execute([$cart_id]);

    $_SESSION['success'] = 'Xóa sản phẩm thành công';
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Lỗi xóa sản phẩm: ' . $e->getMessage();
}

header('Location: cart.php');
exit;