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

<!-- Custom CSS for Shadcn UI style -->
<style>
   
</style>

<div class="container mx-auto my-8 px-4">
    <h2 class="text-2xl font-bold mb-6">Giỏ hàng của bạn</h2>
    
    <?php if (empty($cartItems)): ?>
        <div class="alert alert-info">Giỏ hàng trống</div>
    <?php else: ?>
        <div class="flex flex-col lg:flex-row gap-8">
            <div class="lg:w-2/3">
                <?php foreach ($cartItems as $item): 
                    $price = $item['Price'] * (1 - $item['DiscountPercent']/100);
                    $subtotal = $price * $item['Quantity'];
                    $total += $subtotal;
                ?>
                <div class="shadcn-card mb-3 relative">
                    <!-- Delete button placed at top right corner -->
                    <a href="remove_from_cart.php?id=<?= $item['ID'] ?>" 
                       class="delete-button"
                       onclick="return confirm('Bạn chắc chắn muốn xóa?')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </a>
                    
                    <div class="flex flex-col md:flex-row">
                        <div class="md:w-1/3">
                            <img src="<?= $item['ImageURL'] ? 'uploads/products/'.basename($item['ImageURL']) : 'assets/no-image.jpg' ?>" 
                                 class="w-full h-full object-cover rounded-l-md" 
                                 alt="<?= htmlspecialchars($item['Title']) ?>">
                        </div>
                        <div class="md:w-2/3 p-4">
                            <h5 class="text-lg font-semibold mb-3"><?= htmlspecialchars($item['Title']) ?></h5>
                            
                            <div class="flex flex-col md:flex-row md:items-center justify-between">
                                <div class="mb-3 md:mb-0">
                                    <p class="text-red-500 font-bold mb-1">
                                        <?= number_format($price,0,'',',') ?> VNĐ
                                    </p>
                                    <?php if ($item['DiscountPercent'] > 0): ?>
                                        <del class="text-gray-500 text-sm"><?= number_format($item['Price'], 0,'',',') ?> VNĐ</del>
                                    <?php endif ?>
                                </div>
                                
                                <div class="quantity-input-group">
                                    <form action="update_cart.php" method="POST">
                                        <input type="hidden" name="cart_id" value="<?= $item['ID'] ?>">
                                        <input type="number" 
                                               name="quantity" 
                                               value="<?= $item['Quantity'] ?>" 
                                               min="1" 
                                               max="<?= $item['Stock'] ?>" 
                                               class="shadcn-input quantity-input">
                                        <button type="submit" class="shadcn-btn shadcn-btn-outline shadcn-btn-sm">
                                            Cập nhật
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="text-end">
                                    <p class="text-lg font-bold"><?= number_format($subtotal, 0,'',',') ?> VNĐ</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
            
            <div class="lg:w-1/3">
                <div class="shadcn-card">
                    <div class="p-4">
                        <h5 class="text-xl font-semibold mb-4">Tổng cộng</h5>
                        <dl class="grid grid-cols-2 gap-y-2">
                            <dt class="text-gray-700">Tạm tính:</dt>
                            <dd class="text-end text-gray-900"><?= number_format($total, 0,'',',') ?> VNĐ</dd>
                            
                            <dt class="text-gray-700">Phí vận chuyển:</dt>
                            <dd class="text-end text-gray-900">0</dd>
                            
                            <dt class="col-span-2 border-t mt-3 pt-3 text-lg font-bold text-red-500">Tổng tiền:</dt>
                            <dd class="col-span-2 text-end text-lg font-bold text-red-500">
                                <?= number_format($total, 0,'',',') ?> VNĐ
                            </dd>
                        </dl>
                        <a href="checkout.php" class="bg-black text-white p-3 rounded-sm w-full mt-4">
                            Thanh toán
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>
</div>

<?php include 'includes/footer.php'; ?>