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

<div class="container my-5">
    <h2 class="mb-4">Kết quả tìm kiếm cho: <?= htmlspecialchars($keyword) ?></h2>

    <!-- Form tìm kiếm -->
    <div class="search-container mb-4">
        <form method="GET" action="search.php">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="q">Từ khóa</label>
                        <input type="text" class="form-control" id="q" name="q" value="<?= htmlspecialchars($keyword) ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="category">Danh mục</label>
                        <select class="form-control" id="category" name="category">
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
                </div>
                <div class="col-md-12 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Tìm kiếm</button>
                </div>
            </div>
        </form>
    </div>
    
    <?php if (empty($products)): ?>
        <div class="alert alert-info">Không có sản phẩm nào phù hợp với từ khóa tìm kiếm</div>
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
                            <a href="./product.php?id=<?= $product['ID'] ?>"  class="card-title2" ><?= htmlspecialchars($product['Title']) ?></a>
                            <p class="card-text truncate-text"><?= htmlspecialchars($product['Description']) ?></p>
                            <p class="card-text">
                                Giá: 
                                <?php if ($product['DiscountPercent'] > 0): ?>
                                    <span style="text-decoration: line-through;"><?= htmlspecialchars($product['Price']) ?> VND</span>
                                    <span><?= htmlspecialchars($product['Price'] * (1 - $product['DiscountPercent'] / 100)) ?> VND</span>
                                <?php else: ?>
                                    <span><?= htmlspecialchars($product['Price']) ?> VND</span>
                                <?php endif; ?>
                            </p>
                            <form action="add_to_cart.php" method="POST" class="d-flex gap-3">
                                <input type="hidden" name="product_id" value="<?= $product['ID'] ?>">
                                <input type="hidden" name="quantity" value="1" id="quantityInput">
                                
                                <button class="btn btn-primary btn-lg px-5" 
                                    <?= $product['Stock'] < 1 ? 'disabled' : '' ?> 
                                    type="submit">
                                   Thêm vào giỏ hàng
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
                        <a class="page-link" href="search.php?q=<?= htmlspecialchars($keyword) ?>&category=<?= $category_id ?>&page=<?= $i ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor ?>
            </ul>
        </nav>
    <?php endif ?>
</div>

<?php include 'includes/footer.php'; ?>