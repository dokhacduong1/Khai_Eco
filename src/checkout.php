<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = '';
$order_id = null;

try {
    // Lấy thông tin giỏ hàng
    $cartStmt = $pdo->prepare("
        SELECT c.*, p.Title, p.Price, p.DiscountPercent, p.Stock 
        FROM Cart c
        JOIN Products p ON c.ProductID = p.ID
        WHERE c.UserID = ?
    ");
    $cartStmt->execute([$_SESSION['user_id']]);
    $cartItems = $cartStmt->fetchAll();

    // Lấy thông tin người dùng
    $userStmt = $pdo->prepare("SELECT * FROM Users WHERE ID = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();

    // Tính tổng tiền
    $total = 0;
    foreach ($cartItems as $item) {
        $price = $item['Price'] * (1 - $item['DiscountPercent']/100);
        $total += $price * $item['Quantity'];
    }

} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

// Xử lý thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $payment_method = $_POST['payment_method'];

    // Validate
    if (empty($name)) $errors[] = 'Vui lòng nhập họ tên';
    if (empty($phone)) $errors[] = 'Vui lòng nhập số điện thoại';
    if (empty($address)) $errors[] = 'Vui lòng nhập địa chỉ';
    if (empty($payment_method)) $errors[] = 'Vui lòng chọn phương thức thanh toán';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Tạo đơn hàng
            $orderStmt = $pdo->prepare("
                INSERT INTO Orders (UserID, TotalPrice, Status)
                VALUES (?, ?, 'pending')
            ");
            $orderStmt->execute([$_SESSION['user_id'], $total]);
            $order_id = $pdo->lastInsertId();

            // Thêm chi tiết đơn hàng
            foreach ($cartItems as $item) {
                $price = $item['Price'] * (1 - $item['DiscountPercent']/100);
                
                $orderItemStmt = $pdo->prepare("
                    INSERT INTO OrderItems (OrderID, ProductID, Quantity, Price)
                    VALUES (?, ?, ?, ?)
                ");
                $orderItemStmt->execute([
                    $order_id,
                    $item['ProductID'],
                    $item['Quantity'],
                    $price
                ]);

                // Cập nhật tồn kho
                $updateStockStmt = $pdo->prepare("
                    UPDATE Products SET Stock = Stock - ? 
                    WHERE ID = ?
                ");
                $updateStockStmt->execute([$item['Quantity'], $item['ProductID']]);
            }

            // Thêm thông tin thanh toán
            $paymentStmt = $pdo->prepare("
                INSERT INTO Payments (OrderID, PaymentMethod, Status)
                VALUES (?, ?, 'pending')
            ");
            $paymentStmt->execute([$order_id, $payment_method]);

            // Xóa giỏ hàng
            $clearCartStmt = $pdo->prepare("DELETE FROM Cart WHERE UserID = ?");
            $clearCartStmt->execute([$_SESSION['user_id']]);

            $pdo->commit();

            $success = 'Đặt hàng thành công! Mã đơn hàng của bạn là #' . $order_id;
            $cartItems = []; // Clear cart

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Lỗi thanh toán: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="container mx-auto my-8 px-4">
    <h2 class="text-2xl font-bold mb-6">Thanh toán</h2>
    
    <?php if ($errors): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
            <?php foreach ($errors as $error): ?>
                <div><?= $error ?></div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-6"><?= $success ?></div>
    <?php endif ?>

    <div class="flex flex-col lg:flex-row gap-8">
        <div class="lg:w-2/3">
            <div class="bg-white p-6 rounded shadow mb-4">
                <h5 class="text-lg font-semibold mb-4">Thông tin giao hàng</h5>
                <form method="POST">
                    <div class="mb-4">
                        <label class="block text-gray-700">Họ và tên</label>
                        <input type="text" name="name" class="w-full px-3 py-2 border border-gray-300 rounded mt-1" required
                            value="<?= htmlspecialchars($user['FullName'] ?? '') ?>">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700">Số điện thoại</label>
                        <input type="tel" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded mt-1" required
                            value="<?= htmlspecialchars($user['Phone'] ?? '') ?>">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700">Địa chỉ</label>
                        <textarea name="address" class="w-full px-3 py-2 border border-gray-300 rounded mt-1" rows="3" required><?= 
                            htmlspecialchars($user['Address'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700">Phương thức thanh toán</label>
                        <select name="payment_method" class="w-full px-3 py-2 border border-gray-300 rounded mt-1" required>
                            <option value="cod">Thanh toán khi nhận hàng</option>
                            <option value="bank_transfer">Chuyển khoản ngân hàng</option>
                            <option value="credit_card">Thẻ tín dụng</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-black cursor-pointer text-white font-semibold py-3 rounded">Đặt hàng</button>
                </form>
            </div>
        </div>

        <div class="lg:w-1/3">
            <div class="bg-white p-6 rounded shadow">
                <h5 class="text-lg font-semibold mb-4">Đơn hàng của bạn</h5>
                
                <?php if (empty($cartItems)): ?>
                    <div class="bg-gray-100 text-gray-700 p-4 rounded">Không có sản phẩm nào</div>
                <?php else: ?>
                    <ul class="divide-y divide-gray-200 mb-4">
                        <?php foreach ($cartItems as $item): 
                            $price = $item['Price'] * (1 - $item['DiscountPercent']/100);
                            $subtotal = $price * $item['Quantity'];
                        ?>
                        <li class="py-4 flex justify-between">
                            <div>
                                <h6 class="text-gray-900"><?= htmlspecialchars($item['Title']) ?></h6>
                                <small class="text-gray-500"><?= $item['Quantity'] ?> x <?= number_format($price, 0, '', ',') ?> VNĐ</small>
                            </div>
                            <span class="text-gray-900"><?= number_format($subtotal, 0, '', ',') ?> VNĐ</span>
                        </li>
                        <?php endforeach ?>
                    </ul>

                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-gray-700">Tạm tính:</dt>
                            <dd class="text-gray-900"><?= number_format($total, 0, '', ',') ?> VNĐ</dd>
                        </div>
                        
                        <div class="flex justify-between">
                            <dt class="text-gray-700">Phí vận chuyển:</dt>
                            <dd class="text-gray-900">0 VNĐ</dd>
                        </div>
                        
                        <div class="flex justify-between border-t border-gray-200 pt-2">
                            <dt class="text-lg font-bold text-red-500">Tổng tiền:</dt>
                            <dd class="text-lg font-bold text-red-500"><?= number_format($total, 0, '', ',') ?> VNĐ</dd>
                        </div>
                    </dl>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>