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

<div class="container my-5">
    <h2 class="mb-4">Thanh toán</h2>
    
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

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Thông tin giao hàng</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label>Họ và tên</label>
                            <input type="text" name="name" class="form-control" required
                                value="<?= htmlspecialchars($user['FullName'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label>Số điện thoại</label>
                            <input type="tel" name="phone" class="form-control" required
                                value="<?= htmlspecialchars($user['Phone'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label>Địa chỉ</label>
                            <textarea name="address" class="form-control" rows="3" required><?= 
                                htmlspecialchars($user['Address'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label>Phương thức thanh toán</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cod">Thanh toán khi nhận hàng</option>
                                <option value="bank_transfer">Chuyển khoản ngân hàng</option>
                                <option value="credit_card">Thẻ tín dụng</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg">
                            Đặt hàng
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Đơn hàng của bạn</h5>
                    
                    <?php if (empty($cartItems)): ?>
                        <div class="alert alert-info">Không có sản phẩm nào</div>
                    <?php else: ?>
                        <ul class="list-group mb-3">
                            <?php foreach ($cartItems as $item): 
                                $price = $item['Price'] * (1 - $item['DiscountPercent']/100);
                                $subtotal = $price * $item['Quantity'];
                            ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <div>
                                    <h6><?= htmlspecialchars($item['Title']) ?></h6>
                                    <small class="text-muted">
                                        <?= $item['Quantity'] ?> x $<?= number_format($price, 2) ?>
                                    </small>
                                </div>
                                <span>$<?= number_format($subtotal, 2) ?></span>
                            </li>
                            <?php endforeach ?>
                        </ul>

                        <dl class="row">
                            <dt class="col-6">Tạm tính:</dt>
                            <dd class="col-6 text-end">$<?= number_format($total, 2) ?></dd>
                            
                            <dt class="col-6">Phí vận chuyển:</dt>
                            <dd class="col-6 text-end">$0.00</dd>
                            
                            <dt class="col-6 border-top mt-2 pt-2">Tổng tiền:</dt>
                            <dd class="col-6 border-top mt-2 pt-2 text-end h4 text-danger">
                                $<?= number_format($total, 2) ?>
                            </dd>
                        </dl>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>