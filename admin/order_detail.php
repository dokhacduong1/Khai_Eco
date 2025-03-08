<?php
require_once 'auth_check.php';
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

try {
    // Lấy thông tin đơn hàng
    $orderStmt = $pdo->prepare("
        SELECT o.*, u.*, p.* 
        FROM Orders o
        LEFT JOIN Users u ON o.UserID = u.ID
        LEFT JOIN Payments p ON o.ID = p.OrderID
        WHERE o.ID = ?
    ");
    $orderStmt->execute([$_GET['id']]);
    $order = $orderStmt->fetch();

    // Lấy các sản phẩm trong đơn hàng
    $itemsStmt = $pdo->prepare("
        SELECT oi.*, p.Title, p.Price 
        FROM OrderItems oi
        LEFT JOIN Products p ON oi.ProductID = p.ID
        WHERE oi.OrderID = ?
    ");
    $itemsStmt->execute([$_GET['id']]);
    $items = $itemsStmt->fetchAll();

    // Lấy lịch sử trạng thái
    $trackingStmt = $pdo->prepare("SELECT * FROM OrderTracking WHERE OrderID = ? ORDER BY UpdatedAt DESC");
    $trackingStmt->execute([$_GET['id']]);
    $tracking = $trackingStmt->fetchAll();

} catch (PDOException $e) {
    die("Error loading order: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Order #<?= $order['ID'] ?></h3>
            <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> <?= htmlspecialchars($order['FullName']) ?></p>
                        <p><strong>Email:</strong> <?= $order['Email'] ?></p>
                        <p><strong>Phone:</strong> <?= $order['Phone'] ?? 'N/A' ?></p>
                        <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($order['Address'])) ?></p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Method:</strong> <?= ucfirst($order['PaymentMethod']) ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?= 
                                $order['Status'] == 'completed' ? 'success' : 
                                ($order['Status'] == 'failed' ? 'danger' : 'warning') ?>">
                                <?= ucfirst($order['Status']) ?>
                            </span>
                        </p>
                        <?php if ($order['TransactionID']): ?>
                            <p><strong>Transaction ID:</strong> <?= $order['TransactionID'] ?></p>
                        <?php endif ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Order Details</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['Title']) ?></td>
                                    <td>$<?= number_format($item['Price'], 2) ?></td>
                                    <td><?= $item['Quantity'] ?></td>
                                    <td>$<?= number_format($item['Price'] * $item['Quantity'], 2) ?></td>
                                </tr>
                                <?php endforeach ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3">Total</th>
                                    <th>$<?= number_format($order['TotalPrice'], 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Order History</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php foreach ($tracking as $track): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><?= ucfirst($track['Status']) ?></span>
                                <small><?= date('M d, Y H:i', strtotime($track['UpdatedAt'])) ?></small>
                            </li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>