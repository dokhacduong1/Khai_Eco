<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// Lấy danh sách đơn hàng
$orders = $pdo->query("
    SELECT o.*, u.FullName as CustomerName, p.Status as PaymentStatus
    FROM Orders o
    LEFT JOIN Users u ON o.UserID = u.ID
    LEFT JOIN Payments p ON o.ID = p.OrderID
    ORDER BY o.CreatedAt DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h3>Order Management</h3>

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
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Order Status</th>
                    <th>Payment</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?= $order['ID'] ?></td>
                    <td><?= htmlspecialchars($order['CustomerName']) ?></td>
                    <td>$<?= number_format($order['TotalPrice'], 2) ?></td>
                    <td>
                        <span class="badge bg-<?= 
                            $order['Status'] == 'delivered' ? 'success' : 
                            ($order['Status'] == 'cancelled' ? 'danger' : 'warning') ?>">
                            <?= ucfirst($order['Status']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= 
                            $order['PaymentStatus'] == 'completed' ? 'success' : 
                            ($order['PaymentStatus'] == 'failed' ? 'danger' : 'secondary') ?>">
                            <?= ucfirst($order['PaymentStatus'] ?? 'pending') ?>
                        </span>
                    </td>
                    <td><?= date('M d, Y H:i', strtotime($order['CreatedAt'])) ?></td>
                    <td>
                        <a href="order_detail.php?id=<?= $order['ID'] ?>" 
                           class="btn btn-sm btn-info">View</a>
                        <a href="order_update.php?id=<?= $order['ID'] ?>" 
                           class="btn btn-sm btn-warning">Update</a>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</body>
</html>