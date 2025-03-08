<?php
require_once 'config/database.php';


// Lấy dữ liệu cho trang chủ
$featured_products = $pdo->query("
    SELECT p.*, c.Name as CategoryName, pi.ImageURL 
    FROM Products p
    LEFT JOIN Categories c ON p.CategoryID = c.ID
    LEFT JOIN (
        SELECT ProductID, MIN(ImageURL) as ImageURL 
        FROM ProductImages 
        GROUP BY ProductID
    ) pi ON p.ID = pi.ProductID
    WHERE p.DiscountPercent > 0
    ORDER BY p.CreatedAt DESC 
    LIMIT 8
")->fetchAll();

$categories = $pdo->query("SELECT * FROM Categories WHERE ParentID IS NULL")->fetchAll();

// Lấy sản phẩm gợi ý (đơn giản)
if (isset($_SESSION['user_id'])) {
    $recommended = $pdo->prepare("
        SELECT p.*, pi.ImageURL 
        FROM Products p
        INNER JOIN OrderItems oi ON p.ID = oi.ProductID
        INNER JOIN Orders o ON oi.OrderID = o.ID
        LEFT JOIN (
            SELECT ProductID, MIN(ImageURL) as ImageURL 
            FROM ProductImages 
            GROUP BY ProductID
        ) pi ON p.ID = pi.ProductID
        WHERE o.UserID = ?
        GROUP BY p.ID
        ORDER BY COUNT(p.ID) DESC
        LIMIT 4
    ");
    $recommended->execute([$_SESSION['user_id']]);
    $recommended_products = $recommended->fetchAll();
} else {
    $recommended_products = $pdo->query("
        SELECT p.*, pi.ImageURL 
        FROM Products p
        LEFT JOIN (
            SELECT ProductID, MIN(ImageURL) as ImageURL 
            FROM ProductImages 
            GROUP BY ProductID
        ) pi ON p.ID = pi.ProductID
        ORDER BY RAND() 
        LIMIT 4
    ")->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - <?= htmlspecialchars($settings['site_title'] ?? 'Ecommerce') ?></title>

    <style>
        .product-card {
            transition: transform 0.3s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .discount-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }
        .banner-carousel {
            height: 400px;
            overflow: hidden;
        }
        .banner-carousel img {
            object-fit: cover;
            height: 100%;
            width: 100%;
        }
        .banner-carousel {
            height: 500px;
            overflow: hidden;
            position: relative;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .carousel-item {
            height: 500px;
            background: #f8f9fa;
        }

        .carousel-item img {
            height: 100%;
            width: 100%;
            object-fit: cover;
            object-position: center;
        }

        .carousel-control-prev,
        .carousel-control-next {
            width: 5%;
            background: rgba(0,0,0,0.3);
            opacity: 1;
            transition: all 0.3s ease;
        }

        .carousel-control-prev:hover,
        .carousel-control-next:hover {
            background: rgba(0,0,0,0.5);
        }

        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            width: 2.5rem;
            height: 2.5rem;
        }

        .carousel-indicators [data-bs-target] {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin: 0 8px;
            border: 2px solid #fff;
            background: transparent;
            opacity: 1;
        }

        .carousel-indicators .active {
            background: #fff;
        }

        /* Điều chỉnh product card */
        .product-card {
            transition: all 0.3s ease;
            border: 1px solid #eee;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .product-card img {
            transition: transform 0.3s ease;
        }

        .product-card:hover img {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Banner Carousel -->
    <?php if (!empty($settings['banner_1']) || !empty($settings['banner_2'])): ?>
    <div class="banner-carousel carousel slide" data-bs-ride="carousel">
        <!-- Indicators -->
        <div class="carousel-indicators">
            <?php if (!empty($settings['banner_1'])): ?>
                <button type="button" data-bs-target=".banner-carousel" data-bs-slide-to="0" class="active"></button>
            <?php endif ?>
            <?php if (!empty($settings['banner_2'])): ?>
                <button type="button" data-bs-target=".banner-carousel" data-bs-slide-to="1"></button>
            <?php endif ?>
        </div>

        <div class="carousel-inner">
            <?php if (!empty($settings['banner_1'])): ?>
                <div class="carousel-item active">
                    <img src="<?= $settings['banner_1'] ?>" class="d-block w-100" alt="Banner 1">
                </div>
            <?php endif ?>
            
            <?php if (!empty($settings['banner_2'])): ?>
                <div class="carousel-item">
                    <img src="<?= $settings['banner_2'] ?>" class="d-block w-100" alt="Banner 2">
                </div>
            <?php endif ?>
        </div>

        <!-- Controls -->
        <button class="carousel-control-prev" type="button" data-bs-target=".banner-carousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target=".banner-carousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
    <?php endif ?>

    <!-- Featured Products -->
    <section class="container my-5">
        <h3 class="mb-4">Sản phẩm nổi bật</h3>
        <div class="row g-4">
            <?php foreach ($featured_products as $product): ?>
                <div class="col-md-3">
                    <div class="card product-card h-100">
                        <?php if($product['DiscountPercent'] > 0): ?>
                            <span class="discount-badge badge bg-danger">-<?= $product['DiscountPercent'] ?>%</span>
                        <?php endif ?>
                        <img src="<?= $product['ImageURL'] ? './uploads/products/' . basename($product['ImageURL']) : '/assets/no-image.jpg' ?>" 
                            class="card-img-top" 
                            style="height: 200px; object-fit: cover" 
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

    <!-- Promotions -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="p-5 mb-4 bg-dark text-white rounded-3">
                <div class="container-fluid py-5">
                    <h1 class="display-5 fw-bold"><?= htmlspecialchars($settings['promotion_title'] ?? 'Giảm giá lên đến 50%') ?></h1>
                    <p class="col-md-8 fs-4"><?= htmlspecialchars($settings['promotion_description'] ?? 'Khuyến mãi đặc biệt trong tháng này') ?></p>
                    <a class="btn btn-primary btn-lg" href="products.php">Mua ngay</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Recommended Products -->
    <section class="container my-5">
        <h3 class="mb-4">Gợi ý cho bạn</h3>
        <div class="row g-4">
            <?php foreach ($recommended_products as $product): ?>
                <div class="col-md-3">
                    <div class="card product-card h-100">
                    <?php if($product['DiscountPercent'] > 0): ?>
                            <span class="discount-badge badge bg-danger">-<?= $product['DiscountPercent'] ?>%</span>
                        <?php endif ?>
                    <img src="<?= $product['ImageURL'] ? './uploads/products/' . basename($product['ImageURL']) : '/assets/no-image.jpg' ?>" 
                        class="card-img-top" 
                        style="height: 200px; object-fit: cover" 
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

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
  

    </script>
</body>
</html>