<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Lấy tổng số đơn hàng
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM Orders 
        WHERE UserID = ?
    ");
    $countStmt->execute([$_SESSION['user_id']]);
    $totalOrders = $countStmt->fetchColumn();

    // Lấy danh sách đơn hàng
    $ordersStmt = $pdo->prepare("
        SELECT o.*, p.Status as PaymentStatus 
        FROM Orders o
        LEFT JOIN Payments p ON o.ID = p.OrderID
        WHERE o.UserID = ?
        ORDER BY o.CreatedAt DESC
        LIMIT ? OFFSET ?
    ");
    $ordersStmt->execute([$_SESSION['user_id'], $limit, $offset]);
    $orders = $ordersStmt->fetchAll();

} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="container my-5">
    <h2 class="mb-4">Lịch sử đơn hàng</h2>
    
    <?php if (empty($orders)): ?>
        <div class="alert alert-info">Bạn chưa có đơn hàng nào</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Thanh toán</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= $order['ID'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($order['CreatedAt'])) ?></td>
                        <td><?= number_format($order['TotalPrice'], 0,'',',') ?> VNĐ</td>
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
                                <?= ucfirst($order['PaymentStatus']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="order_detail.php?id=<?= $order['ID'] ?>" 
                               class="btn btn-sm btn-outline-primary">
                                Xem chi tiết
                            </a>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= ceil($totalOrders / $limit); $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor ?>
            </ul>
        </nav>
    <?php endif ?>
</div>

<?php include 'includes/footer.php'; ?>