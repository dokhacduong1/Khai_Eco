<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập và tham số
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: login.php');
    exit;
}

$order_id = (int)$_GET['id'];

try {
    // Lấy thông tin đơn hàng
    $orderStmt = $pdo->prepare("
        SELECT o.*, u.*, p.* 
        FROM Orders o
        JOIN Users u ON o.UserID = u.ID
        LEFT JOIN Payments p ON o.ID = p.OrderID
        WHERE o.ID = ? AND o.UserID = ?
    ");
    $orderStmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $orderStmt->fetch();

    if (!$order) {
        header("HTTP/1.0 404 Not Found");
        include '404.php';
        exit;
    }

    // Lấy chi tiết đơn hàng
    $itemsStmt = $pdo->prepare("
        SELECT oi.*, p.Title, p.Price, p.DiscountPercent 
        FROM OrderItems oi
        JOIN Products p ON oi.ProductID = p.ID
        WHERE oi.OrderID = ?
    ");
    $itemsStmt->execute([$order_id]);
    $items = $itemsStmt->fetchAll();

    // Tính tổng tiền
    $total = 0;
    foreach ($items as $item) {
        $price = $item['Price'] * (1 - $item['DiscountPercent']/100);
        $total += $price * $item['Quantity'];
    }

} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Chi tiết đơn hàng #<?= $order['ID'] ?></h2>
        <a href="orders.php" class="btn btn-secondary">Quay lại</a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Thông tin khách hàng</h5>
                    <dl class="row">
                        <dt class="col-sm-4">Họ tên:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($order['FullName']) ?></dd>

                        <dt class="col-sm-4">Email:</dt>
                        <dd class="col-sm-8"><?= $order['Email'] ?></dd>

                        <dt class="col-sm-4">Điện thoại:</dt>
                        <dd class="col-sm-8"><?= $order['Phone'] ?? 'N/A' ?></dd>

                        <dt class="col-sm-4">Địa chỉ:</dt>
                        <dd class="col-sm-8"><?= nl2br(htmlspecialchars($order['Address'])) ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Thông tin thanh toán</h5>
                    <dl class="row">
                        <dt class="col-sm-5">Phương thức:</dt>
                        <dd class="col-sm-7"><?= ucfirst($order['PaymentMethod']) ?></dd>

                        <dt class="col-sm-5">Trạng thái:</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-<?= 
                                $order['Status'] == 'completed' ? 'success' : 
                                ($order['Status'] == 'failed' ? 'danger' : 'secondary') ?>">
                                <?= ucfirst($order['Status']) ?>
                            </span>
                        </dd>

                        <dt class="col-sm-5">Mã giao dịch:</dt>
                        <dd class="col-sm-7"><?= $order['TransactionID'] ?? 'N/A' ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Sản phẩm đã đặt</h5>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Đơn giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): 
                            $price = $item['Price'] * (1 - $item['DiscountPercent']/100);
                            $subtotal = $price * $item['Quantity'];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($item['Title']) ?></td>
                            <td><?= number_format($price, 0,'',',') ?> VNĐ</td>
                            <td><?= $item['Quantity'] ?></td>
                            <td><?= number_format($subtotal, 0,'',',') ?> VNĐ</td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Tổng cộng:</th>
                            <th><?= number_format($total, 0,'',',') ?> VNĐ</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Lịch sử trạng thái</h5>
            <ul class="list-group">
                <?php
                $trackingStmt = $pdo->prepare("
                    SELECT * FROM OrderTracking 
                    WHERE OrderID = ? 
                    ORDER BY UpdatedAt DESC
                ");
                $trackingStmt->execute([$order_id]);
                $tracking = $trackingStmt->fetchAll();
                ?>
                
                <?php foreach ($tracking as $log): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><?= ucfirst($log['Status']) ?></span>
                    <small class="text-muted">
                        <?= date('d/m/Y H:i', strtotime($log['UpdatedAt'])) ?>
                    </small>
                </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>