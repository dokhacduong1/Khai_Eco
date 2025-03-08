<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// Lấy danh sách khách hàng
$customers = $pdo->query("
    SELECT * FROM Users 
    WHERE Role = 'customer' 
    ORDER BY CreatedAt DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Customers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h3>Customer Management</h3>

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
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Registered At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><?= $customer['ID'] ?></td>
                    <td><?= htmlspecialchars($customer['FullName']) ?></td>
                    <td><?= $customer['Email'] ?></td>
                    <td><?= $customer['Phone'] ?? 'N/A' ?></td>
                    <td><?= date('M d, Y H:i', strtotime($customer['CreatedAt'])) ?></td>
                    <td>
                        <a href="customer_edit.php?id=<?= $customer['ID'] ?>" 
                           class="btn btn-sm btn-warning">Edit</a>
                        <a href="customer_delete.php?id=<?= $customer['ID'] ?>" 
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Are you sure? This will delete all related data!')">Delete</a>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</body>
</html>