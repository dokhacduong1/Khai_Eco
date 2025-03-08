<?php
require_once 'config/database.php';

// Kiểm tra tham số ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}
$settings = [];
try {
    $stmt = $pdo->query("SELECT KeyName, KeyValue FROM Settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    die("Error loading settings: " . $e->getMessage());
}
try {
    // Lấy thông tin sản phẩm
    $productStmt = $pdo->prepare("
        SELECT p.*, c.Name as CategoryName 
        FROM Products p
        LEFT JOIN Categories c ON p.CategoryID = c.ID
        WHERE p.ID = ?
    ");
    $productStmt->execute([$_GET['id']]);
    $product = $productStmt->fetch();

    if (!$product) {
        header("HTTP/1.0 404 Not Found");
        include '404.php';
        exit;
    }

    // Lấy hình ảnh sản phẩm
    $imagesStmt = $pdo->prepare("SELECT * FROM ProductImages WHERE ProductID = ?");
    $imagesStmt->execute([$_GET['id']]);
    $images = $imagesStmt->fetchAll();

    // Lấy sản phẩm liên quan
    $relatedStmt = $pdo->prepare("
        SELECT p.*, pi.ImageURL 
        FROM Products p
        LEFT JOIN (
            SELECT ProductID, MIN(ImageURL) as ImageURL 
            FROM ProductImages 
            GROUP BY ProductID
        ) pi ON p.ID = pi.ProductID
        WHERE p.CategoryID = ? AND p.ID != ?
        ORDER BY RAND() 
        LIMIT 4
    ");
    $relatedStmt->execute([$product['CategoryID'], $product['ID']]);
    $relatedProducts = $relatedStmt->fetchAll();

} catch (PDOException $e) {
    die("Error loading product: " . $e->getMessage());
}

// Tính giá sau giảm
$discountedPrice = $product['Price'] * (1 - $product['DiscountPercent']/100);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['Title']) ?> - Ecommerce</title>
  
    <style>
        .product-gallery {
            position: relative;
        }
        .main-image {
            height: 500px;
            object-fit: contain;
            cursor: zoom-in;
            transition: transform 0.3s;
        }
        .thumbnails {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        .thumbnail.active {
            border-color: #0d6efd;
        }
        .product-info {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
        }
        .original-price {
            font-size: 1.2rem;
            color: #6c757d;
        }
        .discounted-price {
            font-size: 2rem;
            color: #dc3545;
            font-weight: bold;
        }
        .stock-status {
            font-size: 0.9rem;
        }
        .stock-status.in-stock {
            color: #198754;
        }
        .stock-status.out-of-stock {
            color: #dc3545;
        }
        .quantity-input {
            width: 120px;
        }
        .related-product-card {
            transition: transform 0.3s;
        }
        .related-product-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container my-5">
        <div class="row g-5">
            <!-- Phần hình ảnh -->
            <div class="col-lg-6">
                <div class="product-gallery">
                    <div id="mainCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php if(empty($images)): ?>
                                <div class="carousel-item active">
                                    <img src="./assets/no-image.jpg" class="d-block w-100 main-image" alt="No image">
                                </div>
                            <?php else: ?>
                                <?php foreach($images as $index => $image): ?>
                                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                        <img src="./uploads/products/<?= basename($image['ImageURL']) ?>" 
                                             class="d-block w-100 main-image" 
                                             alt="<?= htmlspecialchars($product['Title']) ?>">
                                    </div>
                                <?php endforeach ?>
                            <?php endif ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        </button>
                    </div>

                   
                </div>
            </div>

            <!-- Thông tin sản phẩm -->
            <div class="col-lg-6">
                <div class="product-info">
                    <h1 class="mb-3"><?= htmlspecialchars($product['Title']) ?></h1>
                    
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <?php if($product['DiscountPercent'] > 0): ?>
                            <span class="discounted-price">
                                <?= number_format($discountedPrice, 0,'',',') ?> VNĐ
                            </span>
                            <del class="original-price">
                                $<?= number_format($product['Price'], 0,'',',') ?> VNĐ
                            </del>
                            <span class="badge bg-danger">
                                -<?= $product['DiscountPercent'] ?>%
                            </span>
                        <?php else: ?>
                            <span class="discounted-price">
                                $<?= number_format($product['Price'], 2) ?>
                            </span>
                        <?php endif ?>
                    </div>

                    <div class="mb-4">
                        <span class="stock-status <?= $product['Stock'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                            <i class="fas fa-<?= $product['Stock'] > 0 ? 'check' : 'times' ?>-circle me-2"></i>
                            <?= $product['Stock'] > 0 ? 'Còn hàng' : 'Hết hàng' ?>
                        </span>
                    </div>

                    <div class="mb-4">
                        <h5>Mô tả sản phẩm</h5>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($product['Description'])) ?></p>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label>Số lượng</label>
                            <input type="number" 
                                   class="form-control quantity-input" 
                                   value="1" 
                                   min="1" 
                                   max="<?= $product['Stock'] ?>"
                                   <?= $product['Stock'] < 1 ? 'disabled' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label>Danh mục</label>
                            <div class="form-control bg-white">
                                <?= htmlspecialchars($product['CategoryName'] ?? 'Không phân loại') ?>
                            </div>
                        </div>
                    </div>

                    <form action="add_to_cart.php" method="POST" class="d-flex gap-3">
                        <input type="hidden" name="product_id" value="<?= $product['ID'] ?>">
                        <input type="hidden" name="quantity" value="1" id="quantityInput">
                        
                        <button class="btn btn-primary btn-lg px-5" 
                            <?= $product['Stock'] < 1 ? 'disabled' : '' ?> 
                            type="submit">
                            <i class="fas fa-cart-plus me-2"></i>Thêm vào giỏ
                        </button>
                        
                        <button class="btn btn-outline-secondary btn-lg px-5" type="button">
                            <i class="fas fa-heart me-2"></i>Yêu thích
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sản phẩm liên quan -->
        <?php if(!empty($relatedProducts)): ?>
        <section class="mt-5">
            <h3 class="mb-4">Sản phẩm liên quan</h3>
            <div class="row g-4">
                <?php foreach($relatedProducts as $product): ?>
                <div class="col-md-3">
                    <div class="card related-product-card h-100">
                       
                        <img src="<?= $product['ImageURL'] ? './uploads/products/' . basename($product['ImageURL']) : '/assets/no-image.jpg' ?>" 
                             class="card-img-top" 
                             style="height: 200px; object-fit: contain" 
                             alt="<?= htmlspecialchars($product['Title']) ?>">
                             <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['Title']) ?></h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <?php if($product['DiscountPercent'] > 0): ?>
                                    <span class="text-danger fs-5">
                                        <?= number_format($product['Price'] * (1 - $product['DiscountPercent'] / 100), 0, '', ',') ?> VNĐ
                                    </span>
                                    <del class="text-muted"><?= number_format($product['Price'], 0, '', ',') ?>  VNĐ</del>
                                <?php else: ?>
                                    <span class="fs-5"><?= number_format($product['Price'], 0,'', ',') ?></span>
                                <?php endif ?>
                            </div>
                            <a href="product.php?id=<?= $product['ID'] ?>" class="btn btn-primary mt-2 w-100">
                                Xem chi tiết
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
        </section>
        <?php endif ?>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Xử lý thumbnail click
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.addEventListener('click', () => {
                document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
            });
        });

        // Xử lý zoom ảnh
        document.querySelectorAll('.main-image').forEach(img => {
            img.addEventListener('click', () => {
                img.style.transform = img.style.transform === 'scale(1.5)' ? 'scale(1)' : 'scale(1.5)';
            });
        });
    </script>
</body>
</html>