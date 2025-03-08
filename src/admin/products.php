<?php
session_start();
require_once '../config/database.php';
require_once 'auth_check.php';

// Xử lý thêm/xóa/sửa sản phẩm
// ...

// Lấy danh sách sản phẩm
$products = $pdo->query("
    SELECT p.*, c.Name as CategoryName 
    FROM Products p 
    LEFT JOIN Categories c ON p.CategoryID = c.ID
    ORDER BY p.CreatedAt DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
        <?php unset($_SESSION['success_message']) ?>
    <?php endif ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
        <?php unset($_SESSION['error_message']) ?>
    <?php endif ?>
    <div class="container mt-4">
        <div class="d-flex justify-content-between mb-3">
            <h3>Product Management</h3>
            <a href="product_edit.php" class="btn btn-primary">Add New Product</a>
        </div>
        
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= $product['ID'] ?></td>
                    <td><?= $product['Title'] ?></td>
                    <td>$<?= number_format($product['Price'], 2) ?></td>
                    <td><?= $product['Stock'] ?></td>
                    <td><?= $product['CategoryName'] ?? 'N/A' ?></td>
                    <td>
                        <a href="product_edit.php?id=<?= $product['ID'] ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="product_delete.php?id=<?= $product['ID'] ?>" class="btn btn-sm btn-danger">Delete</a>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</body>
</html>