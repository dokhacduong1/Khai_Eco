<?php
require_once 'auth_check.php';
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header('Location: categories.php');
    exit;
}

try {
    // Xóa danh mục
    $stmt = $pdo->prepare("DELETE FROM Categories WHERE ID = ?");
    $stmt->execute([$_GET['id']]);
    
    $_SESSION['success_message'] = 'Category deleted successfully';
} catch (PDOException $e) {
    // Nếu có lỗi khóa ngoại, xử lý phù hợp
    if ($e->getCode() === '23000') {
        $_SESSION['error_message'] = 'Cannot delete category with existing sub-categories or products';
    } else {
        $_SESSION['error_message'] = 'Error deleting category: ' . $e->getMessage();
    }
}

header('Location: categories.php');
exit;