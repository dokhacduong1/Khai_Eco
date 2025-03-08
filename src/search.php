<?php
require_once 'config/database.php';

$keyword = isset($_GET['q']) ? $_GET['q'] : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$perPage = 10; // Số lượng sản phẩm mỗi trang
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

try {
    // Lọc theo từ khóa
    $keywordCondition = $keyword ? "AND (p.Title LIKE ? OR p.Description LIKE ?)" : '';
    $keywordParams = $keyword ? ["%$keyword%", "%$keyword%"] : [];

    // Lọc theo danh mục
    $categoryCondition = $category_id ? "AND p.CategoryID = ?" : '';
    $categoryParams = $category_id ? [$category_id] : [];

    // Lấy tổng số sản phẩm
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM Products p
        WHERE 1=1
        $keywordCondition
        $categoryCondition
    ");
    $countParams = array_merge($keywordParams, $categoryParams);
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
        WHERE 1=1
        $keywordCondition
        $categoryCondition
        GROUP BY p.ID, pi.ImageURL
        ORDER BY p.CreatedAt DESC
        LIMIT ? OFFSET ?
    ");
    $params = array_merge($keywordParams, $categoryParams, [$perPage, $offset]);
    $productsStmt->execute($params);
    $products = $productsStmt->fetchAll();

} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="container mx-auto my-8 px-4">
    <h2 class="text-2xl font-bold mb-6">Kết quả tìm kiếm cho: <?= htmlspecialchars($keyword) ?></h2>

    <!-- Form tìm kiếm -->
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <form method="GET" action="search.php">
            <div class="grid grid-cols-1 md:grid-cols-2 ">
                <div class="form-group">
                    <label for="q" class="font-semibold">Từ khóa</label>
                    <input type="text" class="form-control border border-gray-300 rounded-md shadow-sm mt-1 block w-full p-3" id="q" name="q" value="<?= htmlspecialchars($keyword) ?>">
                </div>
                <div class="form-group">
                    <label for="category" class="font-semibold">Danh mục</label>
                    <select class="form-control border border-gray-300 rounded-md shadow-sm mt-1 block w-full p-3" id="category" name="category">
                        <option value="0">Tất cả danh mục</option>
                        <?php
                        // Lấy danh mục
                        $categoriesStmt = $pdo->prepare("SELECT * FROM Categories");
                        $categoriesStmt->execute();
                        $categories = $categoriesStmt->fetchAll();
                        foreach ($categories as $cat) {
                            $selected = $cat['ID'] == $category_id ? 'selected' : '';
                            echo "<option value='{$cat['ID']}' $selected>{$cat['Name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-span-12 md:col-span-12  w-full">
                    <button type="submit" class="mt-4 bg-black hover:bg-black cursor-pointer text-white font-bold py-2 px-4 rounded w-full md:w-auto">Tìm kiếm</button>
                </div>
            </div>
        </form>
    </div>
    
    <?php if (empty($products)): ?>
        <div class="alert alert-info">Không có sản phẩm nào phù hợp với từ khóa tìm kiếm</div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($products as $product): ?>
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200 overflow-hidden">
                    <?php if ($product['DiscountPercent'] > 0): ?>
                        <span class="absolute top-2 right-2 bg-red-500 text-white text-xs font-semibold px-2 py-1 rounded">-<?= htmlspecialchars($product['DiscountPercent']) ?>%</span>
                    <?php endif; ?>
                    <img src="<?= htmlspecialchars($product['ImageURL']) ?>" class="w-full h-48 object-cover" alt="<?= htmlspecialchars($product['Title']) ?>">
                    <div class="p-4">
                        <h5 class="font-semibold truncate text-lg"><?= htmlspecialchars($product['Title']) ?></h5>
                        <p class="text-gray-600 truncate"><?= htmlspecialchars($product['Description']) ?></p>
                        <p class="mt-2">
                            <span class="font-semibold">Giá:</span> 
                            <?php if ($product['DiscountPercent'] > 0): ?>
                                <span class="line-through text-gray-500"><?= number_format($product['Price'], 0,'', ',') ?> VND</span>
                                <span class="text-red-500 font-bold"><?= number_format($product['Price'] * (1 - $product['DiscountPercent'] / 100), 0,'', ',') ?> VND</span>
                            <?php else: ?>
                                <span class="font-bold"><?= number_format($product['Price'], 0,'', ',') ?> VND</span>
                            <?php endif; ?>
                        </p>
                        <form action="add_to_cart.php" method="POST" class="mt-4">
                            <input type="hidden" name="product_id" value="<?= $product['ID'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button class="w-full bg-black hover:bg-black cursor-pointer text-white font-bold py-2 px-4 rounded <?= $product['Stock'] < 1 ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= $product['Stock'] < 1 ? 'disabled' : '' ?>>
                                Thêm vào giỏ
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
                        <a class="px-3 py-1 border border-gray-300 rounded-md <?= $i == $page ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-200' ?>" 
                           href="search.php?q=<?= htmlspecialchars($keyword) ?>&category=<?= $category_id ?>&page=<?= $i ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor ?>
            </ul>
        </nav>
    <?php endif ?>
</div>

<?php include 'includes/footer.php'; ?>