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

<div class="container my-5">
    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="https://www.phanmemninja.com/wp-content/uploads/2023/06/avatar-facebook-nam-vo-danh.jpeg" alt="avatar"
                        class="rounded-circle img-fluid">
                    <h5 class="my-3"><?= htmlspecialchars($user['FullName']) ?></h5>
                    <p class="text-muted mb-1">Thành viên từ: <?= 
                        date('d/m/Y', strtotime($user['CreatedAt'])) ?></p>
                    <p class="text-muted mb-4"><?= htmlspecialchars($user['Email']) ?></p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-4">Thông tin cá nhân</h5>
                    
                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div><?= $error ?></div>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Họ và tên</label>
                                <input type="text" name="name" class="form-control" required
                                    value="<?= htmlspecialchars($user['FullName'] ?? '') ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Số điện thoại</label>
                                <input type="tel" name="phone" class="form-control"
                                    value="<?= htmlspecialchars($user['Phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label>Địa chỉ</label>
                            <textarea name="address" class="form-control" rows="3"><?= 
                                htmlspecialchars($user['Address'] ?? '') ?></textarea>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-4">Đổi mật khẩu</h5>

                        <div class="mb-3">
                            <label>Mật khẩu hiện tại</label>
                            <input type="password" name="current_password" class="form-control">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Mật khẩu mới</label>
                                <input type="password" name="new_password" class="form-control">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Nhập lại mật khẩu</label>
                                <input type="password" name="confirm_password" class="form-control">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Cập nhật</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="mb-4">Lịch sử đơn hàng</h5>
                    
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
                        <div class="alert alert-info">Bạn chưa có đơn hàng nào</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Ngày đặt</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['ID'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($order['CreatedAt'])) ?></td>
                                        <td><?= number_format($order['TotalPrice'], 0,'',',') ?> VNĐ</td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $order['Status'] == 'delivered' ? 'success' : 
                                                ($order['Status'] == 'cancelled' ? 'danger' : 'warning') ?>">
                                                <?= ucfirst($order['Status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="order_detail.php?id=<?= $order['ID'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                Chi tiết
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end">
                            <a href="orders.php" class="btn btn-link">Xem tất cả</a>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>