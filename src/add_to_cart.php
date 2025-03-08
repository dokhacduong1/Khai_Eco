<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng';
    header('Location: login.php');
    exit;
}

// Validate input
if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    $_SESSION['error'] = 'Sản phẩm không hợp lệ';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

try {
    // Kiểm tra tồn kho
    $productStmt = $pdo->prepare("SELECT Stock FROM Products WHERE ID = ?");
    $productStmt->execute([$product_id]);
    $product = $productStmt->fetch();

    if (!$product) {
        $_SESSION['error'] = 'Sản phẩm không tồn tại';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    if ($product['Stock'] < 1) {
        $_SESSION['error'] = 'Sản phẩm đã hết hàng';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    if ($quantity < 1 || $quantity > 10) {
        $_SESSION['error'] = 'Số lượng phải từ 1 đến 10';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Kiểm tra sản phẩm đã có trong giỏ chưa
    $cartStmt = $pdo->prepare("
        SELECT * FROM Cart 
        WHERE UserID = ? AND ProductID = ?
    ");
    $cartStmt->execute([$_SESSION['user_id'], $product_id]);
    $existingItem = $cartStmt->fetch();

    if ($existingItem) {
        // Cập nhật số lượng
        $newQuantity = $existingItem['Quantity'] + $quantity;
        if ($newQuantity > $product['Stock']) {
            $newQuantity = $product['Stock'];
        }

        $updateStmt = $pdo->prepare("
            UPDATE Cart SET Quantity = ? 
            WHERE ID = ?
        ");
        $updateStmt->execute([$newQuantity, $existingItem['ID']]);
    } else {
        // Thêm mới vào giỏ hàng
        $insertStmt = $pdo->prepare("
            INSERT INTO Cart (UserID, ProductID, Quantity)
            VALUES (?, ?, ?)
        ");
        $insertStmt->execute([
            $_SESSION['user_id'],
            $product_id,
            min($quantity, $product['Stock'])
        ]);
    }

    $_SESSION['success'] = 'Thêm vào giỏ hàng thành công';
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Lỗi hệ thống: ' . $e->getMessage();
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;