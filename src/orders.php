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

<div class="container mx-auto my-8 px-4">
    <h2 class="text-2xl font-bold mb-6">Lịch sử đơn hàng</h2>
    
    <?php if (empty($orders)): ?>
        <div class="bg-blue-100 text-blue-700 p-4 rounded mb-6">Bạn chưa có đơn hàng nào</div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white divide-y divide-gray-200">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="py-3 px-4 text-left">Mã đơn</th>
                        <th class="py-3 px-4 text-left">Ngày đặt</th>
                        <th class="py-3 px-4 text-left">Tổng tiền</th>
                        <th class="py-3 px-4 text-left">Trạng thái</th>
                        <th class="py-3 px-4 text-left">Thanh toán</th>
                        <th class="py-3 px-4 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td class="py-3 px-4">#<?= $order['ID'] ?></td>
                        <td class="py-3 px-4"><?= date('d/m/Y H:i', strtotime($order['CreatedAt'])) ?></td>
                        <td class="py-3 px-4"><?= number_format($order['TotalPrice'], 0, '', ',') ?> VNĐ</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 rounded text-white bg-<?= 
                                $order['Status'] == 'delivered' ? 'green-500' : 
                                ($order['Status'] == 'cancelled' ? 'red-500' : 'yellow-500') ?>">
                                <?= ucfirst($order['Status']) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 rounded text-white bg-<?= 
                                $order['PaymentStatus'] == 'completed' ? 'green-500' : 
                                ($order['PaymentStatus'] == 'failed' ? 'red-500' : 'gray-500') ?>">
                                <?= ucfirst($order['PaymentStatus']) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <a href="order_detail.php?id=<?= $order['ID'] ?>" 
                               class="text-blue-500 hover:underline">
                                Xem chi tiết
                            </a>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        <nav class="mt-6">
            <ul class="flex justify-center space-x-2">
                <?php for ($i = 1; $i <= ceil($totalOrders / $limit); $i++): ?>
                    <li class="page-item <?= $i === $page ? 'bg-black text-white' : 'bg-gray-200' ?>">
                        <a class="px-3 py-1 rounded <?= $i === $page ? 'bg-black text-white' : 'bg-gray-200 text-gray-700' ?>" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor ?>
            </ul>
        </nav>
    <?php endif ?>
</div>

<?php include 'includes/footer.php'; ?>