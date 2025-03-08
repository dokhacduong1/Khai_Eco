<?php
session_start();
require_once '../config/database.php';
require_once 'auth_check.php';

// Thống kê cơ bản
$stats = [
    'total_products' => $pdo->query("SELECT COUNT(*) FROM Products")->fetchColumn(),
    'total_orders' => $pdo->query("SELECT COUNT(*) FROM Orders")->fetchColumn(),
    'total_customers' => $pdo->query("SELECT COUNT(*) FROM Users WHERE Role = 'customer'")->fetchColumn(),
    'revenue' => $pdo->query("SELECT SUM(TotalPrice) FROM Orders WHERE Status = 'delivered'")->fetchColumn()
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h3>Dashboard Overview</h3>
        
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><?= $stats['total_products'] ?></h5>
                        <p class="card-text">Total Products</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><?= $stats['total_orders'] ?></h5>
                        <p class="card-text">Total Orders</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><?= $stats['total_customers'] ?></h5>
                        <p class="card-text">Total Customers</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-danger mb-3">
                    <div class="card-body">
                        <h5 class="card-title">$<?= number_format($stats['revenue'], 2) ?></h5>
                        <p class="card-text">Total Revenue</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>