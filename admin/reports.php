<?php
session_start();
require_once '../config/database.php';
require_once 'auth_check.php';

// Thống kê doanh thu theo tháng
$revenue_data = $pdo->query("
    SELECT 
        YEAR(CreatedAt) as Year,
        MONTH(CreatedAt) as Month,
        SUM(TotalPrice) as Revenue 
    FROM Orders 
    WHERE Status = 'delivered'
    GROUP BY YEAR(CreatedAt), MONTH(CreatedAt)
    ORDER BY Year DESC, Month DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sales Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h3>Sales Report</h3>
        
        <div class="row mt-4">
            <div class="col-md-8">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($revenue_data as $row): ?>
                        <tr>
                            <td><?= date('F Y', mktime(0, 0, 0, $row['Month'], 1, $row['Year'])) ?></td>
                            <td>$<?= number_format($row['Revenue'], 2) ?></td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>