<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// Lấy danh sách danh mục với tên danh mục cha
$categories = $pdo->query("
    SELECT c1.*, c2.Name as ParentName 
    FROM Categories c1 
    LEFT JOIN Categories c2 ON c1.ParentID = c2.ID
    ORDER BY c1.ParentID, c1.Name
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between mb-3">
            <h3>Category Management</h3>
            <a href="category_edit.php" class="btn btn-primary">Add New Category</a>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
            <?php unset($_SESSION['success_message']) ?>
        <?php endif ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
            <?php unset($_SESSION['error_message']) ?>
        <?php endif ?>

        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Parent Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?= $category['ID'] ?></td>
                    <td><?= htmlspecialchars($category['Name']) ?></td>
                    <td><?= $category['Slug'] ?></td>
                    <td><?= $category['ParentName'] ?? '---' ?></td>
                    <td>
                        <a href="category_edit.php?id=<?= $category['ID'] ?>" 
                           class="btn btn-sm btn-warning">Edit</a>
                        <a href="category_delete.php?id=<?= $category['ID'] ?>" 
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Are you sure? This action cannot be undone!')">Delete</a>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</body>
</html>