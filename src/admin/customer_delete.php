<?php
require_once 'auth_check.php';
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header('Location: customers.php');
    exit;
}

try {
    // Bắt đầu transaction
    $pdo->beginTransaction();

    // Xóa các dữ liệu liên quan (đơn hàng, giỏ hàng,...)
    // Các bảng có khóa ngoại đã được thiết lập ON DELETE CASCADE
    $stmt = $pdo->prepare("DELETE FROM Users WHERE ID = ?");
    $stmt->execute([$_GET['id']]);

    $pdo->commit();
    
    $_SESSION['success_message'] = 'Customer deleted successfully';
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = 'Error deleting customer: ' . $e->getMessage();
}

header('Location: customers.php');
exit;