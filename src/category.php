<?php
require_once 'config/database.php';

// Hàm đệ quy lấy tất cả ID con
function getAllChildIds($pdo, $category_id, &$ids = []) {
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

<!-- Phần CSS tùy chỉnh -->
<style>
    .product-card {
        position: relative;
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .product-card img {
        height: 200px;
        width: 100%;
        object-fit: cover;
        border-bottom: 1px solid #ddd;
    }

    .product-card .card-body {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 15px;
    }

    .product-card .badge-discount {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: red;
        color: white;
        padding: 5px;
        border-radius: 5px;
    }

    .buy-now {
        background-color: #28a745;
        color: white;
        text-align: center;
        border-radius: 5px;
        padding: 10px;
        text-decoration: none;
        transition: background-color 0.2s;
        display: block;
    }

    .buy-now:hover {
        background-color: #218838;
    }

    .filter-container {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .filter-container .form-group {
        margin-bottom: 15px;
    }

    .filter-container label {
        font-weight: bold;
    }

    .pagination {
        margin-top: 20px;
    }
</style>

<div class="container my-5">
    <h2 class="mb-4">Danh mục: <?= htmlspecialchars($category['Name']) ?></h2>

    <!-- Form lọc sản phẩm -->
    <div class="filter-container mb-4">
        <form method="GET" action="category.php">
            <input type="hidden" name="id" value="<?= $category_id ?>">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="keyword">Từ khóa</label>
                        <input type="text" class="form-control" id="keyword" name="keyword" value="<?= htmlspecialchars($keyword) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="min_price">Giá tối thiểu</label>
                        <input type="number" class="form-control" id="min_price" name="min_price" value="<?= htmlspecialchars($minPrice) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="max_price">Giá tối đa</label>
                        <input type="number" class="form-control" id="max_price" name="max_price" value="<?= htmlspecialchars($maxPrice) ?>">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="discount_only" name="discount_only" <?= $discountOnly ? 'checked' : '' ?>>
                        <label class="form-check-label" for="discount_only">Chỉ sản phẩm giảm giá</label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="in_stock_only" name="in_stock_only" <?= $inStockOnly ? 'checked' : '' ?>>
                        <label class="form-check-label" for="in_stock_only">Chỉ sản phẩm còn hàng</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Lọc sản phẩm</button>
        </form>
    </div>
    
    <?php if (empty($products)): ?>
        <div class="alert alert-info">Không có sản phẩm nào trong danh mục này</div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <div class="card product-card">
                        <?php if ($product['DiscountPercent'] > 0): ?>
                            <span class="badge-discount">-<?= htmlspecialchars($product['DiscountPercent']) ?>%</span>
                        <?php endif; ?>
                        <img src="<?= htmlspecialchars($product['ImageURL']) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['Title']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['Title']) ?></h5>
                            <p class="card-text truncate-text"><?= htmlspecialchars($product['Description']) ?></p>
                            <p class="card-text">
                                <span>Giá:</span> 
                                <?php if ($product['DiscountPercent'] > 0): ?>
                                    <span style="text-decoration: line-through;"><?= number_format($product['Price'], 0,'', ',') ?> VNĐ</span>
                                    <span><?= number_format($product['Price'] * (1 - $product['DiscountPercent'] / 100), 0,'', ',') ?> VNĐ</span>
                                <?php else: ?>
                                    <span><?= number_format($product['Price'], 0,'', ',') ?> VNĐ</span>
                                <?php endif; ?>
                            </p>
                            <form action="add_to_cart.php" method="POST" class="d-flex gap-3">
                                <input type="hidden" name="product_id" value="<?= $product['ID'] ?>">
                                <input type="hidden" name="quantity" value="1" id="quantityInput">
                                
                                <button class="btn btn-primary btn-lg px-5" 
                                    <?= $product['Stock'] < 1 ? 'disabled' : '' ?> 
                                    type="submit">
                                    <i class="fas fa-cart-plus me-2"></i>Thêm vào giỏ
                                </button>
                    
                            </form>
                           
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>

        <!-- Phân trang -->
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= ceil($totalProducts / $perPage); $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="category.php?id=<?= $category_id ?>&page=<?= $i ?>&keyword=<?= htmlspecialchars($keyword) ?>&min_price=<?= htmlspecialchars($minPrice) ?>&max_price=<?= htmlspecialchars($maxPrice) ?>&discount_only=<?= $discountOnly ? '1' : '0' ?>&in_stock_only=<?= $inStockOnly ? '1' : '0' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor ?>
            </ul>
        </nav>
    <?php endif ?>
</div>

<?php include 'includes/footer.php'; ?>