<?php
require_once 'config/database.php';

// Hàm đệ quy lấy tất cả ID con
function getAllChildIds($pdo, $category_id, &$ids = [])
{
    // Thêm ID hiện tại vào mảng
    if (!in_array($category_id, $ids)) {
        $ids[] = $category_id;
    }

    $stmt = $pdo->prepare("SELECT ID FROM Categories WHERE ParentID = ?");
    $stmt->execute([$category_id]);
    $children = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($children as $child_id) {
        getAllChildIds($pdo, $child_id, $ids);
    }
    return $ids;
}

// Xử lý category
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$discountOnly = isset($_GET['discount_only']) ? (bool)$_GET['discount_only'] : false;
$inStockOnly = isset($_GET['in_stock_only']) ? (bool)$_GET['in_stock_only'] : false;

try {
    // Kiểm tra category tồn tại
    $categoryStmt = $pdo->prepare("SELECT * FROM Categories WHERE ID = ?");
    $categoryStmt->execute([$category_id]);
    $category = $categoryStmt->fetch();

    if (!$category) {
        header("HTTP/1.0 404 Not Found");
        include '404.php';
        exit;
    }

    // Lấy tất cả ID liên quan (bao gồm cả chính nó và các con)
    $categoryIds = [$category_id];

    getAllChildIds($pdo, $category_id, $categoryIds);

    // Phân trang
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;

    // Xây dựng chuỗi placeholder
    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));

    // Lọc theo từ khóa
    $keywordCondition = $keyword ? "AND (p.Title LIKE ? OR p.Description LIKE ?)" : '';
    $keywordParams = $keyword ? ["%$keyword%", "%$keyword%"] : [];

    // Lọc theo giá
    $priceCondition = ($minPrice || $maxPrice) ? "AND (p.Price BETWEEN ? AND ?)" : '';
    $priceParams = ($minPrice || $maxPrice) ? [$minPrice, $maxPrice] : [];

    // Lọc theo giảm giá
    $discountCondition = $discountOnly ? "AND p.DiscountPercent > 0" : '';

    // Lọc theo sản phẩm còn hàng
    $stockCondition = $inStockOnly ? "AND p.Stock > 0" : '';

    // Lấy tổng số sản phẩm
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM Products p
        WHERE p.CategoryID IN ($placeholders)
        $keywordCondition
        $priceCondition
        $discountCondition
        $stockCondition
    ");
    $countParams = array_merge($categoryIds, $keywordParams, $priceParams);
    $countStmt->execute($countParams);
    $totalProducts = $countStmt->fetchColumn();

    // Lấy sản phẩm
    $productsStmt = $pdo->prepare("
        SELECT p.*, pi.ImageURL
        FROM Products p
        LEFT JOIN (
            SELECT ProductID, MIN(ImageURL) as ImageURL 
            FROM ProductImages 
            GROUP BY ProductID
        ) pi ON p.ID = pi.ProductID
        WHERE p.CategoryID IN ($placeholders)
        $keywordCondition
        $priceCondition
        $discountCondition
        $stockCondition
        GROUP BY p.ID, pi.ImageURL
        ORDER BY p.CreatedAt DESC
        LIMIT ? OFFSET ?
    ");

    // Gộp tham số đúng cách
    $params = array_merge($categoryIds, $keywordParams, $priceParams, [$perPage, $offset]);

    $productsStmt->execute($params);
    $products = $productsStmt->fetchAll();
} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="container mx-auto my-8 px-4">
    <h2 class="text-2xl font-bold mb-6">Danh mục: <?= htmlspecialchars($category['Name']) ?></h2>

    <!-- Form lọc sản phẩm -->
    <div class="bg-white  p-6 rounded-lg shadow mb-6">
        <form method="GET" action="category.php">
            <input type="hidden" name="id" value="<?= $category_id ?>">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="form-group">
                    <label for="keyword" class="font-semibold">Từ khóa</label>
                    <input type="text" class="form-control border border-gray-300 rounded-md shadow-sm mt-1 block w-full p-3" id="keyword" name="keyword" value="<?= htmlspecialchars($keyword) ?>">
                </div>
                <div class="form-group">
                    <label for="min_price" class="font-semibold">Giá tối thiểu</label>
                    <input type="number" class="form-control border border-gray-300 rounded-md shadow-sm mt-1 block w-full p-3" id="min_price" name="min_price" value="<?= htmlspecialchars($minPrice) ?>">
                </div>
                <div class="form-group">
                    <label for="max_price" class="font-semibold">Giá tối đa</label>
                    <input type="number" class="form-control border border-gray-300 rounded-md shadow-sm mt-1 block w-full p-3" id="max_price" name="max_price" value="<?= htmlspecialchars($maxPrice) ?>">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input border border-gray-300 rounded-md shadow-sm" id="discount_only" name="discount_only" <?= $discountOnly ? 'checked' : '' ?>>
                    <label class="form-check-label font-semibold" for="discount_only">Chỉ sản phẩm giảm giá</label>
                </div>
                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input border border-gray-300 rounded-md shadow-sm" id="in_stock_only" name="in_stock_only" <?= $inStockOnly ? 'checked' : '' ?>>
                    <label class="form-check-label font-semibold" for="in_stock_only">Chỉ sản phẩm còn hàng</label>
                </div>
            </div>
            <button type="submit" class="mt-4 bg-black cursor-pointer hover:bg-black text-white font-bold py-2 px-4 rounded">Lọc sản phẩm</button>
        </form>
    </div>

    <?php if (empty($products)): ?>
        <div class="alert alert-info">Không có sản phẩm nào trong danh mục này</div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($products as $product): ?>
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200 overflow-hidden">
                    <?php if ($product['DiscountPercent'] > 0): ?>
                        <span class="absolute top-2 right-2 bg-red-500 text-white text-xs font-semibold px-2 py-1 rounded">-<?= htmlspecialchars($product['DiscountPercent']) ?>%</span>
                    <?php endif; ?>
                    <img src="<?= htmlspecialchars($product['ImageURL']) ?>" class="w-full h-48 object-cover " alt="<?= htmlspecialchars($product['Title']) ?>">
                    <div class="p-4">
                        <h5 class="font-semibold text-lg truncate" title="<?= htmlspecialchars($product['Title']) ?>"><?= htmlspecialchars($product['Title']) ?></h5>
                        <p class="text-gray-600 truncate"><?= htmlspecialchars($product['Description']) ?></p>
                        <p class="mt-2">
                            <span class="font-semibold">Giá:</span>
                            <?php if ($product['DiscountPercent'] > 0): ?>
                                <span class="line-through text-gray-500"><?= number_format($product['Price'], 0, '', ',') ?> VNĐ</span>
                                <span class="text-red-500 font-bold"><?= number_format($product['Price'] * (1 - $product['DiscountPercent'] / 100), 0, '', ',') ?> VNĐ</span>
                            <?php else: ?>
                                <span class="font-bold"><?= number_format($product['Price'], 0, '', ',') ?> VNĐ</span>
                            <?php endif; ?>
                        </p>
                        <form action="add_to_cart.php" method="POST" class="mt-4">
                            <input type="hidden" name="product_id" value="<?= $product['ID'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button class="w-full bg-black hover:bg-black  text-white font-bold py-2 px-4 rounded <?= $product['Stock'] < 1 ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= $product['Stock'] < 1 ? 'disabled' : '' ?>>
                                <i class="fas fa-cart-plus mr-2"></i>Thêm vào giỏ
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach ?>
        </div>

        <!-- Phân trang -->
        <nav class="mt-8">
            <ul class="flex justify-center space-x-2">
                <?php for ($i = 1; $i <= ceil($totalProducts / $perPage); $i++): ?>
                    <li>
                        <a class="px-3 py-1   rounded-md <?= $i == $page ? 'bg-black text-white' : 'bg-white text-gray-700 hover:bg-gray-200' ?>"
                            href="category.php?id=<?= $category_id ?>&page=<?= $i ?>&keyword=<?= htmlspecialchars($keyword) ?>&min_price=<?= htmlspecialchars($minPrice) ?>&max_price=<?= htmlspecialchars($maxPrice) ?>&discount_only=<?= htmlspecialchars($discountOnly) ?>&in_stock_only=<?= htmlspecialchars($inStockOnly) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor ?>
            </ul>
        </nav>
    <?php endif ?>
</div>

<?php include 'includes/footer.php'; ?>