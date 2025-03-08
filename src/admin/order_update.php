<?php
require_once 'auth_check.php';
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

// Lấy thông tin đơn hàng
$stmt = $pdo->prepare("SELECT * FROM Orders WHERE ID = ?");
$stmt->execute([$_GET['id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

$statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['status'];
    
    if (!in_array($newStatus, $statuses)) {
        $error = 'Invalid status selected';
    } else {
        try {
            $pdo->beginTransaction();

            // Cập nhật trạng thái đơn hàng
            $updateStmt = $pdo->prepare("UPDATE Orders SET Status = ? WHERE ID = ?");
            $updateStmt->execute([$newStatus, $order['ID']]);

            // Thêm vào lịch sử trạng thái
            $trackStmt = $pdo->prepare("
                INSERT INTO OrderTracking (OrderID, Status)
                VALUES (?, ?)
            ");
            $trackStmt->execute([$order['ID'], $newStatus]);

            // Nếu là hủy đơn, hoàn trả tồn kho
            if ($newStatus === 'cancelled' && $order['Status'] !== 'cancelled') {
                $itemsStmt = $pdo->prepare("
                    SELECT ProductID, Quantity 
                    FROM OrderItems 
                    WHERE OrderID = ?
                ");
                $itemsStmt->execute([$order['ID']]);
                $items = $itemsStmt->fetchAll();

                foreach ($items as $item) {
                    $updateStock = $pdo->prepare("
                        UPDATE Products 
                        SET Stock = Stock + ? 
                        WHERE ID = ?
                    ");
                    $updateStock->execute([$item['Quantity'], $item['ProductID']]);
                }
            }

            $pdo->commit();
            $_SESSION['success_message'] = 'Order status updated successfully';
            header('Location: orders.php');
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error updating order: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Order Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Update Order #<?= $order['ID'] ?></h3>
            <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif ?>

        <form method="POST">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label>Current Status</label>
                        <input type="text" class="form-control" 
                            value="<?= ucfirst($order['Status']) ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label>New Status</label>
                        <select name="status" class="form-select" required>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= $status ?>" 
                                    <?= $status === $order['Status'] ? 'selected' : '' ?>>
                                    <?= ucfirst($status) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>

                    <div class="alert alert-warning">
                        <strong>Warning:</strong> Changing order status to "cancelled" will:
                        <ul>
                            <li>Restock all items in this order</li>
                            <li>Cancel any associated payments</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </div>
        </form>
    </div>
</body>
</html>