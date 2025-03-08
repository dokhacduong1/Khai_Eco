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
    /* Shadcn UI inspired styles */
    :root {
        --primary: #0f172a;
        --primary-hover: #1e293b;
        --background: #ffffff;
        --foreground: #0f172a;
        --muted: #f1f5f9;
        --muted-foreground: #64748b;
        --border: #e2e8f0;
        --ring: #94a3b8;
        --radius: 0.5rem;
    }

    .shadcn-card {
        background-color: var(--background);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        position: relative;
        transition: all 0.2s ease;
    }

    .shadcn-card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .shadcn-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius);
        font-weight: 500;
        font-size: 0.875rem;
        height: 2.5rem;
        padding-left: 1rem;
        padding-right: 1rem;
        transition: all 0.2s ease;
    }

    .shadcn-btn-sm {
        height: 2rem;
        padding-left: 0.75rem;
        padding-right: 0.75rem;
        font-size: 0.75rem;
    }

    .shadcn-btn-primary {
        background-color: var(--primary);
        color: white;
        border: none;
    }

    .shadcn-btn-primary:hover {
        background-color: var(--primary-hover);
    }

    .shadcn-btn-outline {
        background-color: transparent;
        border: 1px solid var(--border);
        color: var(--foreground);
    }

    .shadcn-btn-outline:hover {
        background-color: var(--muted);
        border-color: var(--muted-foreground);
    }

    .shadcn-btn-destructive {
        background-color: #ef4444;
        color: white;
        border: none;
    }

    .shadcn-btn-destructive:hover {
        background-color: #dc2626;
    }

    .shadcn-input {
        height: 2.5rem;
        width: 100%;
        border-radius: var(--radius);
        border: 1px solid var(--border);
        padding: 0 0.75rem;
        font-size: 0.875rem;
        background-color: var(--background);
        color: var(--foreground);
    }

    .shadcn-input:focus {
        outline: none;
        border-color: var(--ring);
        box-shadow: 0 0 0 2px rgba(148, 163, 184, 0.2);
    }

    .delete-button {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 10;
        background-color: #ef4444;
        color: white;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .delete-button:hover {
        background-color: #dc2626;
        transform: scale(1.05);
    }

    .quantity-input-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .quantity-input {
        max-width: 5rem;
    }
</style>

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
                <div class="shadcn-card mb-3 position-relative">
                    <!-- Delete button placed at top right corner -->
                    <a href="remove_from_cart.php?id=<?= $item['ID'] ?>" 
                       class="delete-button"
                       onclick="return confirm('Bạn chắc chắn muốn xóa?')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </a>
                    
                    <div class="row g-0">
                        <div class="col-md-3">
                            <img src="<?= $item['ImageURL'] ? 'uploads/products/'.basename($item['ImageURL']) : 'assets/no-image.jpg' ?>" 
                                 class="img-fluid rounded-start" 
                                 alt="<?= htmlspecialchars($item['Title']) ?>"
                                 style="height: 100%; object-fit: cover;">
                        </div>
                        <div class="col-md-9">
                            <div class="card-body p-4">
                                <h5 class="card-title mb-3"><?= htmlspecialchars($item['Title']) ?></h5>
                                
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <p class="h5 text-danger mb-1">
                                            <?= number_format($price,0,'',',') ?> VNĐ
                                        </p>
                                        <?php if ($item['DiscountPercent'] > 0): ?>
                                            <del class="text-muted small"><?= number_format($item['Price'], 0,'',',') ?> VNĐ</del>
                                        <?php endif ?>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <form action="update_cart.php" method="POST" class="quantity-input-group">
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
                                    
                                    <div class="col-md-4 text-end">
                                        <p class="h5"><?= number_format($subtotal, 0,'',',') ?> VNĐ</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
            
            <div class="col-lg-4">
                <div class="shadcn-card">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">Tổng cộng</h5>
                        <dl class="row">
                            <dt class="col-6">Tạm tính:</dt>
                            <dd class="col-6 text-end"><?= number_format($total, 0,'',',') ?> VNĐ</dd>
                            
                            <dt class="col-6">Phí vận chuyển:</dt>
                            <dd class="col-6 text-end">0</dd>
                            
                            <dt class="col-6 border-top mt-3 pt-3">Tổng tiền:</dt>
                            <dd class="col-6 border-top mt-3 pt-3 text-end h4 text-danger">
                                <?= number_format($total, 0,'',',') ?> VNĐ
                            </dd>
                        </dl>
                        <a href="checkout.php" class="shadcn-btn shadcn-btn-primary w-100 mt-3">
                            Thanh toán
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>
</div>

<?php include 'includes/footer.php'; ?>