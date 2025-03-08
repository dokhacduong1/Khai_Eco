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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto my-8 px-4">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Phần hình ảnh -->
            <div class="lg:w-1/2">
                <div class="bg-white p-4 rounded-lg shadow-md">
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
            <div class="lg:w-1/2">
                <div class="bg-white p-4 rounded-lg shadow-md">
                    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($product['Title']) ?></h1>

                    <div class="flex items-center gap-4 mb-4">
                        <?php if($product['DiscountPercent'] > 0): ?>
                            <span class="text-2xl font-semibold text-red-500">
                                <?= number_format($discountedPrice, 0,'',',') ?> VNĐ
                            </span>
                            <del class="text-gray-500">
                                <?= number_format($product['Price'], 0,'',',') ?> VNĐ
                            </del>
                            <span class="bg-red-500 text-white text-sm font-semibold py-1 px-2 rounded">
                                -<?= $product['DiscountPercent'] ?>%
                            </span>
                        <?php else: ?>
                            <span class="text-2xl font-semibold text-gray-900">
                                <?= number_format($product['Price'], 0,'',',') ?> VNĐ
                            </span>
                        <?php endif ?>
                    </div>

                    <div class="mb-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold <?= $product['Stock'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                            <i class="fas fa-<?= $product['Stock'] > 0 ? 'check' : 'times' ?>-circle mr-2"></i>
                            <?= $product['Stock'] > 0 ? 'Còn hàng' : 'Hết hàng' ?>
                        </span>
                    </div>

                    <div class="mb-4">
                        <h5 class="text-lg font-semibold">Mô tả sản phẩm</h5>
                        <p class="text-gray-700"><?= nl2br(htmlspecialchars($product['Description'])) ?></p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Số lượng</label>
                            <input type="number" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                   value="1" 
                                   min="1" 
                                   id="quantityInput"
                                   max="<?= $product['Stock'] ?>"
                                   <?= $product['Stock'] < 1 ? 'disabled' : '' ?>>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Danh mục</label>
                            <div class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-50 sm:text-sm">
                                <?= htmlspecialchars($product['CategoryName'] ?? 'Không phân loại') ?>
                            </div>
                        </div>
                    </div>

                    <form action="add_to_cart.php" method="POST" class="flex gap-4">
                        <input type="hidden" name="product_id" value="<?= $product['ID'] ?>">
                        <input type="hidden" name="quantity" id="quantityHidden" value="1">
                        
                        <button class="bg-blue-500 text-white py-2 px-4 rounded-lg shadow hover:bg-blue-600 disabled:bg-gray-400" 
                            <?= $product['Stock'] < 1 ? 'disabled' : '' ?> 
                            type="submit">
                            <i class="fas fa-cart-plus mr-2"></i>Thêm vào giỏ
                        </button>
                        
                        <button class="bg-gray-200 text-gray-700 py-2 px-4 rounded-lg shadow hover:bg-gray-300" type="button">
                            <i class="fas fa-heart mr-2"></i>Yêu thích
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sản phẩm liên quan -->
        <?php if(!empty($relatedProducts)): ?>
        <section class="mt-8">
            <h3 class="text-xl font-bold mb-4">Sản phẩm liên quan</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach($relatedProducts as $product): ?>
                <div class="bg-white p-4 rounded-lg shadow-md">
                    <img src="<?= $product['ImageURL'] ? './uploads/products/' . basename($product['ImageURL']) : '/assets/no-image.jpg' ?>" 
                         class="w-full h-48 object-contain mb-4" 
                         alt="<?= htmlspecialchars($product['Title']) ?>">
                    <h5 class="text-lg font-semibold mb-2"><?= htmlspecialchars($product['Title']) ?></h5>
                    <div class="flex justify-between items-center mb-4">
                        <?php if($product['DiscountPercent'] > 0): ?>
                            <span class="text-red-500 font-bold">
                                <?= number_format($product['Price'] * (1 - $product['DiscountPercent'] / 100), 0, '', ',') ?> VNĐ
                            </span>
                            <del class="text-gray-500"><?= number_format($product['Price'], 0, '', ',') ?> VNĐ</del>
                        <?php else: ?>
                            <span class="text-gray-900 font-bold"><?= number_format($product['Price'], 0,'', ',') ?> VNĐ</span>
                        <?php endif ?>
                    </div>
                    <a href="product.php?id=<?= $product['ID'] ?>" class="block bg-blue-500 text-white text-center py-2 rounded-lg hover:bg-blue-600">Xem chi tiết</a>
                </div>
                <?php endforeach ?>
            </div>
        </section>
        <?php endif ?>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Cập nhật số lượng vào input ẩn
        document.getElementById('quantityInput').addEventListener('input', function() {
            document.getElementById('quantityHidden').value = this.value;
        });

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