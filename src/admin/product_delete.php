<?php
require_once 'auth_check.php';
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit;
}

try {
    // Bắt đầu transaction
    $pdo->beginTransaction();

    // Xóa ảnh sản phẩm
    $imgStmt = $pdo->prepare("SELECT ImageURL FROM ProductImages WHERE ProductID = ?");
    $imgStmt->execute([$_GET['id']]);
    $images = $imgStmt->fetchAll();

    // Xóa ảnh vật lý
    foreach ($images as $img) {
        $filePath = "../" . $img['ImageURL'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    // Xóa trong database
    $deleteStmt = $pdo->prepare("DELETE FROM Products WHERE ID = ?");
    $deleteStmt->execute([$_GET['id']]);

    $pdo->commit();
    
    $_SESSION['success_message'] = "Xóa sản phẩm thành công!";
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = "Lỗi khi xóa sản phẩm: " . $e->getMessage();
}

header('Location: products.php');
exit;