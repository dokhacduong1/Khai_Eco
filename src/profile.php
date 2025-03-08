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

try {
    // Lấy thông tin người dùng
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE ID = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        header('Location: logout.php');
        exit;
    }

} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate
    if (empty($name)) $errors[] = 'Vui lòng nhập họ tên';
    if (strlen($phone) > 20) $errors[] = 'Số điện thoại không hợp lệ';

    // Kiểm tra mật khẩu hiện tại nếu thay đổi mật khẩu
    if (!empty($new_password)) {
        if (!password_verify($current_password, $user['PasswordHash'])) {
            $errors[] = 'Mật khẩu hiện tại không chính xác';
        }
        if (strlen($new_password) < 6) {
            $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự';
        }
        if ($new_password !== $confirm_password) {
            $errors[] = 'Mật khẩu mới không khớp';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Cập nhật thông tin cơ bản
            $updateStmt = $pdo->prepare("
                UPDATE Users SET
                    FullName = ?,
                    Phone = ?,
                    Address = ?
                WHERE ID = ?
            ");
            $updateStmt->execute([$name, $phone, $address, $_SESSION['user_id']]);

            // Cập nhật mật khẩu nếu có
            if (!empty($new_password)) {
                $passwordHash = password_hash($new_password, PASSWORD_DEFAULT);
                $passwordStmt = $pdo->prepare("
                    UPDATE Users SET PasswordHash = ? 
                    WHERE ID = ?
                ");
                $passwordStmt->execute([$passwordHash, $_SESSION['user_id']]);
            }

            $pdo->commit();
            $success = 'Cập nhật thông tin thành công!';

            // Lấy lại thông tin mới
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Lỗi cập nhật: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="container mx-auto my-8 px-4">
    <div class="flex flex-col lg:flex-row gap-8">
        <div class="lg:w-1/3">
            <div class="bg-white p-6 rounded shadow mb-4 text-center">
                <img src="https://www.phanmemninja.com/wp-content/uploads/2023/06/avatar-facebook-nam-vo-danh.jpeg" alt="avatar"
                    class="rounded-full w-32 h-32 mx-auto">
                <h5 class="text-lg font-semibold mt-4"><?= htmlspecialchars($user['FullName']) ?></h5>
                <p class="text-gray-600">Thành viên từ: <?= date('d/m/Y', strtotime($user['CreatedAt'])) ?></p>
                <p class="text-gray-600"><?= htmlspecialchars($user['Email']) ?></p>
            </div>
        </div>

        <div class="lg:w-2/3">
            <div class="bg-white p-6 rounded shadow mb-4">
                <h5 class="text-lg font-semibold mb-4">Thông tin cá nhân</h5>
                
                <?php if ($errors): ?>
                    <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                        <?php foreach ($errors as $error): ?>
                            <div><?= $error ?></div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>

                <?php if ($success): ?>
                    <div class="bg-green-100 text-green-700 p-4 rounded mb-4"><?= $success ?></div>
                <?php endif ?>

                <form method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-gray-700">Họ và tên</label>
                            <input type="text" name="name" class="w-full px-3 py-2 border border-gray-300 rounded mt-1" required
                                value="<?= htmlspecialchars($user['FullName'] ?? '') ?>">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700">Số điện thoại</label>
                            <input type="tel" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded mt-1"
                                value="<?= htmlspecialchars($user['Phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700">Địa chỉ</label>
                        <textarea name="address" class="w-full px-3 py-2 border border-gray-300 rounded mt-1" rows="3"><?= 
                            htmlspecialchars($user['Address'] ?? '') ?></textarea>
                    </div>

                    <hr class="my-4">

                    <h5 class="text-lg font-semibold mb-4">Đổi mật khẩu</h5>

                    <div class="mb-4">
                        <label class="block text-gray-700">Mật khẩu hiện tại</label>
                        <input type="password" name="current_password" class="w-full px-3 py-2 border border-gray-300 rounded mt-1">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-gray-700">Mật khẩu mới</label>
                            <input type="password" name="new_password" class="w-full px-3 py-2 border border-gray-300 rounded mt-1">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700">Nhập lại mật khẩu</label>
                            <input type="password" name="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded mt-1">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-blue-500 text-white font-semibold py-3 rounded">Cập nhật</button>
                </form>
            </div>

            <div class="bg-white p-6 rounded shadow">
                <h5 class="text-lg font-semibold mb-4">Lịch sử đơn hàng</h5>
                
                <?php
                try {
                    $ordersStmt = $pdo->prepare("
                        SELECT o.*, p.Status as PaymentStatus 
                        FROM Orders o
                        LEFT JOIN Payments p ON o.ID = p.OrderID
                        WHERE o.UserID = ?
                        ORDER BY o.CreatedAt DESC
                        LIMIT 5
                    ");
                    $ordersStmt->execute([$_SESSION['user_id']]);
                    $orders = $ordersStmt->fetchAll();

                } catch (PDOException $e) {
                    die("Lỗi hệ thống: " . $e->getMessage());
                }
                ?>

                <?php if (empty($orders)): ?>
                    <div class="bg-gray-100 text-gray-700 p-4 rounded">Bạn chưa có đơn hàng nào</div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 border-b">Mã đơn</th>
                                    <th class="py-2 px-4 border-b">Ngày đặt</th>
                                    <th class="py-2 px-4 border-b">Tổng tiền</th>
                                    <th class="py-2 px-4 border-b">Trạng thái</th>
                                    <th class="py-2 px-4 border-b"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="py-2 px-4 border-b">#<?= $order['ID'] ?></td>
                                    <td class="py-2 px-4 border-b"><?= date('d/m/Y', strtotime($order['CreatedAt'])) ?></td>
                                    <td class="py-2 px-4 border-b"><?= number_format($order['TotalPrice'], 0,'',',') ?> VNĐ</td>
                                    <td class="py-2 px-4 border-b">
                                        <span class="px-2 py-1 rounded text-white bg-<?= 
                                            $order['Status'] == 'delivered' ? 'green-500' : 
                                            ($order['Status'] == 'cancelled' ? 'red-500' : 'yellow-500') ?>">
                                            <?= ucfirst($order['Status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-2 px-4 border-b">
                                        <a href="order_detail.php?id=<?= $order['ID'] ?>" 
                                           class="text-blue-500 hover:underline">
                                            Chi tiết
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-4">
                        <a href="orders.php" class="text-blue-500 hover:underline">Xem tất cả</a>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>