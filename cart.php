<?php
session_start();
require_once 'config/database.php';
include 'includes/header.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    // Lấy giỏ hàng
    $cartStmt = $pdo->prepare("
        SELECT c.*, p.Title, p.Price, p.DiscountPercent, p.Stock, pi.ImageURL 
        FROM Cart c
        JOIN Products p ON c.ProductID = p.ID
        LEFT JOIN (
            SELECT ProductID, MIN(ImageURL) as ImageURL 
            FROM ProductImages 
            GROUP BY ProductID
        ) pi ON p.ID = pi.ProductID
        WHERE c.UserID = ?
    ");
    $cartStmt->execute([$_SESSION['user_id']]);
    $cartItems = $cartStmt->fetchAll();

    // Tính tổng tiền
    $total = 0;

} catch (PDOException $e) {
    die("Lỗi khi tải giỏ hàng: " . $e->getMessage());
}
?>

<div class="container my-5">
    <h2 class="mb-4">Giỏ hàng của bạn</h2>
    
    <?php if (empty($cartItems)): ?>
        <div class="alert alert-info">Giỏ hàng trống</div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <?php foreach ($cartItems as $item): 
                    $price = $item['Price'] * (1 - $item['DiscountPercent']/100);
                    $subtotal = $price * $item['Quantity'];
                    $total += $subtotal;
                ?>
                <div class="card mb-3 shadow-sm">
                    <div class="row g-0">
                        <div class="col-md-3">
                            <img src="<?= $item['ImageURL'] ? 'uploads/products/'.basename($item['ImageURL']) : 'assets/no-image.jpg' ?>" 
                                 class="img-fluid rounded-start" 
                                 alt="<?= htmlspecialchars($item['Title']) ?>">
                        </div>
                        <div class="col-md-9">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($item['Title']) ?></h5>
                                
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <p class="h5 text-danger">
                                            <?= number_format($price,0,'',',') ?> VNĐ
                                        </p>
                                        <?php if ($item['DiscountPercent'] > 0): ?>
                                            <del class="text-muted"><?= number_format($item['Price'], 0,'',',') ?> VNĐ</del>
                                        <?php endif ?>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <form action="update_cart.php" method="POST" class="input-group">
                                            <input type="hidden" name="cart_id" value="<?= $item['ID'] ?>">
                                            <input type="number" 
                                                   name="quantity" 
                                                   value="<?= $item['Quantity'] ?>" 
                                                   min="1" 
                                                   max="<?= $item['Stock'] ?>" 
                                                   class="form-control">
                                            <button type="submit" class="btn btn-outline-primary">
                                                Cập nhật
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <div class="col-md-4 text-end">
                                        <p class="h5"><?= number_format($subtotal, 0,'',',') ?> VNĐ</p>
                                        <a href="remove_from_cart.php?id=<?= $item['ID'] ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Bạn chắc chắn muốn xóa?')">
                                            Xóa
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Tổng cộng</h5>
                        <dl class="row">
                            <dt class="col-6">Tạm tính:</dt>
                            <dd class="col-6 text-end"><?= number_format($total, 0,'',',') ?> VNĐ</dd>
                            
                            <dt class="col-6">Phí vận chuyển:</dt>
                            <dd class="col-6 text-end">0</dd>
                            
                            <dt class="col-6 border-top mt-2 pt-2">Tổng tiền:</dt>
                            <dd class="col-6 border-top mt-2 pt-2 text-end h4 text-danger">
                                <?= number_format($total, 0,'',',') ?> VNĐ
                            </dd>
                        </dl>
                        <a href="checkout.php" class="btn btn-primary w-100 btn-lg">
                            Thanh toán
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>
</div>

<?php include 'includes/footer.php'; ?>