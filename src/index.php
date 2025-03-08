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
        LIMIT 8
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
        LIMIT 8
    ")->fetchAll();
}

// Thêm sản phẩm mới nhất
$newest_products = $pdo->query("
    SELECT p.*, c.Name as CategoryName, pi.ImageURL 
    FROM Products p
    LEFT JOIN Categories c ON p.CategoryID = c.ID
    LEFT JOIN (
        SELECT ProductID, MIN(ImageURL) as ImageURL 
        FROM ProductImages 
        GROUP BY ProductID
    ) pi ON p.ID = pi.ProductID
    ORDER BY p.CreatedAt DESC 
    LIMIT 4
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - <?= htmlspecialchars($settings['site_title'] ?? 'Ecommerce') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .carousel-inner > .carousel-item {
            transition: transform 0.5s ease-in-out;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Container -->
    <div class="container mx-auto mt-4 px-4">
        <!-- Hero Banner Carousel -->
        <?php if (!empty($settings['banner_1']) || !empty($settings['banner_2'])): ?>
        <div class="mb-5">
            <div id="hero-carousel" class="relative w-full" data-carousel="static">
                <div class="relative h-56 overflow-hidden rounded-lg md:h-96">
                    <?php if (!empty($settings['banner_1'])): ?>
                        <div class=" duration-700 ease-in-out" data-carousel-item>
                            <img src="<?= $settings['banner_1'] ?>" class="block w-full h-full object-cover" alt="Banner 1">
                           
                        </div>
                    <?php endif ?>
                    <?php if (!empty($settings['banner_2'])): ?>
                        <div class="hidden duration-700 ease-in-out" data-carousel-item>
                            <img src="<?= $settings['banner_2'] ?>" class="block w-full h-full object-cover" alt="Banner 2">
                            
                        </div>
                    <?php endif ?>
                </div>
                <button type="button" class="absolute top-0 left-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none" data-carousel-prev>
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-black bg-opacity-50 group-hover:bg-opacity-75 focus:ring-4 focus:ring-white focus:outline-none">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        <span class="sr-only">Previous</span>
                    </span>
                </button>
                <button type="button" class="absolute top-0 right-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none" data-carousel-next>
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-black bg-opacity-50 group-hover:bg-opacity-75 focus:ring-4 focus:ring-white focus:outline-none">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <span class="sr-only">Next</span>
                    </span>
                </button>
            </div>
        </div>
        <?php endif ?>

        <!-- Category Highlight -->
        <section class="mb-5">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold">Danh mục nổi bật</h2>
                <a href="categories.php" class="text-primary-500 hover:text-primary-600 font-semibold">Xem tất cả <i class="fas fa-arrow-right ml-2"></i></a>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach (array_slice($categories, 0, 4) as $category): ?>
                    <a href="category.php?id=<?= $category['ID'] ?>" class="block text-center bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200">
                        <div class="p-4">
                            <div class="mb-3 text-primary-500">
                                <i class="fas fa-<?= $category['ID'] % 8 == 0 ? 'tshirt' : ($category['ID'] % 8 == 1 ? 'mobile-alt' : ($category['ID'] % 8 == 2 ? 'laptop' : ($category['ID'] % 8 == 3 ? 'headphones' : ($category['ID'] % 8 == 4 ? 'couch' : ($category['ID'] % 8 == 5 ? 'utensils' : ($category['ID'] % 8 == 6 ? 'baby-carriage' : 'book')))))) ?> fa-3x"></i>
                            </div>
                            <h5 class="font-semibold text-lg"><?= htmlspecialchars($category['Name']) ?></h5>
                        </div>
                    </a>
                <?php endforeach ?>
            </div>
        </section>

        <!-- Featured Products Section -->
        <section class="mb-5">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold">Sản phẩm nổi bật</h2>
                <a href="products.php?featured=1" class="text-primary-500 hover:text-primary-600 font-semibold">Xem tất cả <i class="fas fa-arrow-right ml-2"></i></a>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($featured_products as $product): ?>
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200 overflow-hidden">
                        <?php if($product['DiscountPercent'] > 0): ?>
                            <span class="absolute top-2 right-2 bg-red-500 text-white text-xs font-semibold px-2 py-1 rounded">-<?= $product['DiscountPercent'] ?>%</span>
                        <?php endif ?>
                        <img src="<?= $product['ImageURL'] ? './uploads/products/' . basename($product['ImageURL']) : '/assets/no-image.jpg' ?>" class="w-full h-48 object-cover" alt="<?= htmlspecialchars($product['Title']) ?>">
                        <div class="p-4">
                            <div class="text-sm text-gray-500 mb-1"><?= htmlspecialchars($product['CategoryName']) ?></div>
                            <h5 class="font-semibold text-lg truncate mb-1"><?= htmlspecialchars($product['Title']) ?></h5>
                            <div class="flex justify-between items-center mb-2">
                                <?php if($product['DiscountPercent'] > 0): ?>
                                    <div>
                                        <span class="text-red-500 font-bold"><?= number_format($product['Price'] * (1 - $product['DiscountPercent'] / 100), 0, '', ',') ?> VNĐ</span>
                                        <span class="line-through text-gray-500 text-sm ml-2"><?= number_format($product['Price'], 0, '', ',') ?> VNĐ</span>
                                    </div>
                                <?php else: ?>
                                    <div class="text-gray-900 font-bold"><?= number_format($product['Price'], 0,'', ',') ?> VNĐ</div>
                                <?php endif ?>
                            </div>
                            <a href="add_to_cart.php?id=<?= $product['ID'] ?>" class="block text-center bg-black hover:bg-primary-600 text-white font-semibold py-2 rounded">Thêm vào giỏ</a>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </section>

        <!-- Promotions Banner -->
        <section class="mb-5">
            <div class="relative bg-primary-500 text-white rounded-lg p-5 overflow-hidden">
                <div class="relative z-10">
                    <h2 class="text-2xl md:text-4xl font-bold mb-2"><?= htmlspecialchars($settings['promotion_title'] ?? 'Giảm giá lên đến 50%') ?></h2>
                    <p class="mb-4"><?= htmlspecialchars($settings['promotion_description'] ?? 'Khuyến mãi đặc biệt trong tháng này') ?></p>
                    <a href="products.php?discount=1" class="bg-white text-primary-500 font-bold py-2 px-4 rounded">Mua ngay</a>
                </div>
                <div class="absolute inset-0 bg-black bg-opacity-25"></div>
            </div>
        </section>

        <!-- New Arrivals -->
        <section class="mb-5">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold">Sản phẩm mới</h2>
                <a href="products.php?sort=newest" class="text-primary-500 hover:text-primary-600 font-semibold">Xem tất cả <i class="fas fa-arrow-right ml-2"></i></a>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($newest_products as $product): ?>
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200 overflow-hidden">
                        <?php if($product['DiscountPercent'] > 0): ?>
                            <span class="absolute top-2 right-2 bg-red-500 text-white text-xs font-semibold px-2 py-1 rounded">-<?= $product['DiscountPercent'] ?>%</span>
                        <?php else: ?>
                            <span class="absolute top-2 right-2 bg-green-500 text-white text-xs font-semibold px-2 py-1 rounded">Mới</span>
                        <?php endif ?>
                        <img src="<?= $product['ImageURL'] ? './uploads/products/' . basename($product['ImageURL']) : '/assets/no-image.jpg' ?>" class="w-full h-48 object-cover" alt="<?= htmlspecialchars($product['Title']) ?>">
                        <div class="p-4">
                            <div class="text-sm text-gray-500 mb-1"><?= htmlspecialchars($product['CategoryName']) ?></div>
                            <h5 class="font-semibold truncate text-lg mb-1"><?= htmlspecialchars($product['Title']) ?></h5>
                            <div class="flex justify-between items-center mb-2">
                                <?php if($product['DiscountPercent'] > 0): ?>
                                    <div>
                                        <span class="text-red-500 font-bold"><?= number_format($product['Price'] * (1 - $product['DiscountPercent'] / 100), 0, '', ',') ?> VNĐ</span>
                                        <span class="line-through text-gray-500 text-sm ml-2"><?= number_format($product['Price'], 0, '', ',') ?> VNĐ</span>
                                    </div>
                                <?php else: ?>
                                    <div class="text-gray-900 font-bold"><?= number_format($product['Price'], 0,'', ',') ?> VNĐ</div>
                                <?php endif ?>
                            </div>
                            <a href="product.php?id=<?= $product['ID'] ?>" class="block text-center bg-black hover:bg-primary-600 text-white font-semibold py-2 rounded">Xem chi tiết</a>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        // JavaScript for Carousel
        const prevButton = document.querySelector('[data-carousel-prev]');
        const nextButton = document.querySelector('[data-carousel-next]');
        const carouselItems = document.querySelectorAll('[data-carousel-item]');
        
        let currentIndex = 0;

        function showItem(index) {
            carouselItems.forEach((item, i) => {
                item.classList.add('hidden');
                item.classList.remove('flex');
                if (i === index) {
                    item.classList.remove('hidden');
                    item.classList.add('flex');
                }
            });
        }

        function showNextItem() {
            currentIndex = (currentIndex + 1) % carouselItems.length;
            showItem(currentIndex);
        }

        function showPrevItem() {
            currentIndex = (currentIndex - 1 + carouselItems.length) % carouselItems.length;
            showItem(currentIndex);
        }

        prevButton.addEventListener('click', showPrevItem);
        nextButton.addEventListener('click', showNextItem);

        // Initialize
        showItem(currentIndex);
    </script>
</body>
</html>