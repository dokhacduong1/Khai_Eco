<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['order_id']) || !isset($_POST['product_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = (int)$_POST['order_id'];
$product_id = (int)$_POST['product_id'];
$rating = (int)$_POST['rating'];
$comment = trim($_POST['comment']);

try {
    $stmt = $pdo->prepare("
        INSERT INTO Reviews (UserID, ProductID, Rating, Comment, CreatedAt)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $product_id, $rating, $comment]);

    $_SESSION['success_message'] = 'Đánh giá của bạn đã được gửi';
    header('Location: order_detail.php?id=' . $order_id);
    exit;

} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}
?>