<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['product_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = (int)$_POST['product_id'];

try {
    $stmt = $pdo->prepare("
        INSERT INTO Wishlist (UserID, ProductID, CreatedAt)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$user_id, $product_id]);

    $_SESSION['success_message'] = 'Sản phẩm đã được thêm vào danh sách yêu thích';
    header('Location: wishlist.php');
    exit;

} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}
?>