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
$minRating = isset($_GET['min_rating']) ? (int)$_GET['min_rating'] : 0;

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

    // Lọc theo đánh giá
    $ratingCondition = $minRating ? "AND (SELECT AVG(r.Rating) FROM Reviews r WHERE r.ProductID = p.ID) >= ?" : '';
    $ratingParams = $minRating ? [$minRating] : [];

    // Lấy tổng số sản phẩm
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM Products p
        WHERE p.CategoryID IN ($placeholders)
        $keywordCondition
        $priceCondition
        $ratingCondition
    ");
    $countParams = array_merge($categoryIds, $keywordParams, $priceParams, $ratingParams);
    $countStmt->execute($countParams);
    $totalProducts = $countStmt->fetchColumn();

    // Lấy sản phẩm
    $productsStmt = $pdo->prepare("
        SELECT p.*, pi.ImageURL, IFNULL(AVG(r.Rating), 0) as AvgRating
        FROM Products p
        LEFT JOIN (
            SELECT ProductID, MIN(ImageURL) as ImageURL 
            FROM ProductImages 
            GROUP BY ProductID
        ) pi ON p.ID = pi.ProductID
        LEFT JOIN Reviews r ON p.ID = r.ProductID
        WHERE p.CategoryID IN ($placeholders)
        $keywordCondition
        $priceCondition
        $ratingCondition
        GROUP BY p.ID, pi.ImageURL
        ORDER BY p.CreatedAt DESC
        LIMIT ? OFFSET ?
    ");

    // Gộp tham số đúng cách
    $params = array_merge($categoryIds, $keywordParams, $priceParams, $ratingParams, [$perPage, $offset]);
    
    $productsStmt->execute($params);
    $products = $productsStmt->fetchAll();


} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

include 'includes/header.php';
?>

<!-- Phần HTML giữ nguyên như trước -->
<div class="container my-5">
    <h2 class="mb-4">Danh mục: <?= htmlspecialchars($category['Name']) ?></h2>

    <!-- Form lọc sản phẩm -->
    <form method="GET" action="category.php" class="mb-4">
        <input type="hidden" name="id" value="<?= $category_id ?>">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="keyword">Từ khóa</label>
                    <input type="text" class="form-control" id="keyword" name="keyword" value="<?= htmlspecialchars($keyword) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="min_price">Giá tối thiểu</label>
                    <input type="number" class="form-control" id="min_price" name="min_price" value="<?= htmlspecialchars($minPrice) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="max_price">Giá tối đa</label>
                    <input type="number" class="form-control" id="max_price" name="max_price" value="<?= htmlspecialchars($maxPrice) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="min_rating">Đánh giá tối thiểu</label>
                    <input type="number" class="form-control" id="min_rating" name="min_rating" value="<?= htmlspecialchars($minRating) ?>" min="1" max="5">
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Lọc sản phẩm</button>
    </form>
    
    <?php if (empty($products)): ?>
        <div class="alert alert-info">Không có sản phẩm nào trong danh mục này</div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <div class="card">
                        <img src="<?= htmlspecialchars($product['ImageURL']) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['Title']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['Title']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($product['Description']) ?></p>
                            <p class="card-text">Giá: <?= htmlspecialchars($product['Price']) ?> VND</p>
                            <p class="card-text">Đánh giá: <?= htmlspecialchars(number_format($product['AvgRating'], 1)) ?>/5</p>
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
                        <a class="page-link" href="category.php?id=<?= $category_id ?>&page=<?= $i ?>&keyword=<?= htmlspecialchars($keyword) ?>&min_price=<?= htmlspecialchars($minPrice) ?>&max_price=<?= htmlspecialchars($maxPrice) ?>&min_rating=<?= htmlspecialchars($minRating) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor ?>
            </ul>
        </nav>
    <?php endif ?>
</div>

<?php include 'includes/footer.php'; ?>