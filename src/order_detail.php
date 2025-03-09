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

<div class="container mx-auto my-8 px-4">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Chi tiết đơn hàng #<?= $order['ID'] ?></h2>
        <a href="orders.php" class="bg-gray-500 text-white px-4 py-2 rounded">Quay lại</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div>
            <div class="bg-white p-6 rounded shadow mb-6">
                <h5 class="text-lg font-semibold mb-4">Thông tin khách hàng</h5>
                <dl class="divide-y divide-gray-200">
                    <div class="py-2 flex justify-between">
                        <dt class="text-gray-600">Họ tên:</dt>
                        <dd class="text-gray-900"><?= htmlspecialchars($order['FullName']) ?></dd>
                    </div>
                    <div class="py-2 flex justify-between">
                        <dt class="text-gray-600">Email:</dt>
                        <dd class="text-gray-900"><?= $order['Email'] ?></dd>
                    </div>
                    <div class="py-2 flex justify-between">
                        <dt class="text-gray-600">Điện thoại:</dt>
                        <dd class="text-gray-900"><?= $order['Phone'] ?? 'N/A' ?></dd>
                    </div>
                    <div class="py-2 flex justify-between">
                        <dt class="text-gray-600">Địa chỉ:</dt>
                        <dd class="text-gray-900"><?= nl2br(htmlspecialchars($order['Address'])) ?></dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white p-6 rounded shadow">
                <h5 class="text-lg font-semibold mb-4">Thông tin thanh toán</h5>
                <dl class="divide-y divide-gray-200">
                    <div class="py-2 flex justify-between">
                        <dt class="text-gray-600">Phương thức:</dt>
                        <dd class="text-gray-900"><?= ucfirst($order['PaymentMethod']) ?></dd>
                    </div>
                    <div class="py-2 flex justify-between">
                        <dt class="text-gray-600">Trạng thái:</dt>
                        <dd class="text-gray-900">
                            <span class="px-2 py-1 rounded text-white bg-<?= 
                                $order['Status'] == 'completed' ? 'green-500' : 
                                ($order['Status'] == 'failed' ? 'red-500' : 'yellow-500') ?>">
                                <?= ucfirst($order['Status']) ?>
                            </span>
                        </dd>
                    </div>
                    <div class="py-2 flex justify-between">
                        <dt class="text-gray-600">Mã giao dịch:</dt>
                        <dd class="text-gray-900"><?= $order['TransactionID'] ?? 'N/A' ?></dd>
                    </div>
                </dl>
            </div>
        </div>

        <div>
            <div class="bg-white p-6 rounded shadow mb-6">
                <h5 class="text-lg font-semibold mb-4">Sản phẩm đã đặt</h5>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 text-left">Sản phẩm</th>
                                <th class="py-2 px-4 text-right">Đơn giá</th>
                                <th class="py-2 px-4 text-center">Số lượng</th>
                                <th class="py-2 px-4 text-right">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): 
                                $price = $item['Price'] * (1 - $item['DiscountPercent']/100);
                                $subtotal = $price * $item['Quantity'];
                            ?>
                            <tr>
                                <td class="py-2 px-4"><?= htmlspecialchars($item['Title']) ?></td>
                                <td class="py-2 px-4 text-right"><?= number_format($price, 0, '', ',') ?> VNĐ</td>
                                <td class="py-2 px-4 text-center"><?= $item['Quantity'] ?></td>
                                <td class="py-2 px-4 text-right"><?= number_format($subtotal, 0, '', ',') ?> VNĐ</td>
                            </tr>
                            <?php endforeach ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="py-2 px-4 text-right">Tổng cộng:</th>
                                <th class="py-2 px-4 text-right"><?= number_format($total, 0, '', ',') ?> VNĐ</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="bg-white p-6 rounded shadow">
                <h5 class="text-lg font-semibold mb-4">Lịch sử trạng thái</h5>
                <ul class="divide-y divide-gray-200">
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
                    <li class="py-2 flex justify-between">
                        <span><?= ucfirst($log['Status']) ?></span>
                        <small class="text-gray-500">
                            <?= date('d/m/Y H:i', strtotime($log['UpdatedAt'])) ?>
                        </small>
                    </li>
                    <?php endforeach ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php if ($order['Status'] === 'completed'): ?>
        <div class="bg-white p-6 rounded shadow mb-6">
            <h5 class="text-lg font-semibold mb-4">Đánh giá sản phẩm</h5>
            <form action="submit_review.php" method="POST">
                <input type="hidden" name="order_id" value="<?= $order_id ?>">
                <input type="hidden" name="product_id" value="<?= $items[0]['ProductID'] ?>"> <!-- Assuming only one product per order -->
                <div class="mb-4">
                    <label for="rating" class="block text-sm font-medium text-gray-700">Đánh giá (1-5 sao)</label>
                    <input type="number" id="rating" name="rating" min="1" max="5" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div class="mb-4">
                    <label for="comment" class="block text-sm font-medium text-gray-700">Nhận xét</label>
                    <textarea id="comment" name="comment" rows="4" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Gửi đánh giá</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

<?php include 'includes/footer.php'; ?>