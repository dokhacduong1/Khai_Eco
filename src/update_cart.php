<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_POST['cart_id']) || !is_numeric($_POST['cart_id'])) {
    $_SESSION['error'] = 'Mục giỏ hàng không hợp lệ';
    header('Location: cart.php');
    exit;
}

$cart_id = (int)$_POST['cart_id'];
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

try {
    // Kiểm tra quyền sở hữu
    $checkStmt = $pdo->prepare("
        SELECT c.*, p.Stock 
        FROM Cart c
        JOIN Products p ON c.ProductID = p.ID
        WHERE c.ID = ? AND c.UserID = ?
    ");
    $checkStmt->execute([$cart_id, $_SESSION['user_id']]);
    $item = $checkStmt->fetch();

    if (!$item) {
        $_SESSION['error'] = 'Mục giỏ hàng không tồn tại';
        header('Location: cart.php');
        exit;
    }

    if ($quantity < 1 || $quantity > $item['Stock']) {
        $_SESSION['error'] = 'Số lượng phải từ 1 đến ' . $item['Stock'];
        header('Location: cart.php');
        exit;
    }

    // Cập nhật số lượng
    $updateStmt = $pdo->prepare("
        UPDATE Cart SET Quantity = ? 
        WHERE ID = ?
    ");
    $updateStmt->execute([$quantity, $cart_id]);

    $_SESSION['success'] = 'Cập nhật giỏ hàng thành công';
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Lỗi cập nhật giỏ hàng: ' . $e->getMessage();
}

header('Location: cart.php');
exit;