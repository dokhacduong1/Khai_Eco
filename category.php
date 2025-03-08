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
    
    // Lấy tổng số sản phẩm
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM Products 
        WHERE CategoryID IN ($placeholders)
    ");
    $countStmt->execute($categoryIds);
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
        ORDER BY p.CreatedAt DESC
        LIMIT ? OFFSET ?
    ");

    // Gộp tham số đúng cách
    $params = array_merge($categoryIds, [$perPage, $offset]);
    
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
    
    <?php if (empty($products)): ?>
        <div class="alert alert-info">Không có sản phẩm nào trong danh mục này</div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <div class="card">
                        <img  src="<?= $product['ImageURL'] ? 'uploads/products/'.basename($product['ImageURL']) : 'assets/no-image.jpg' ?>" 
                          
                             alt="<?= htmlspecialchars($product['Title']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['Title']) ?></h5>
                            <p class="card-text">
                                <?= number_format($product['Price']) ?> VNĐ
                                <?php if ($product['DiscountPercent'] > 0): ?>
                                    <span class="ml-3 badge bg-danger"><?= $product['DiscountPercent'] ?>%</span>
                                <?php endif ?>
                            </p>
                            <a href="product.php?id=<?= $product['ID'] ?>" class="btn btn-primary">Xem chi tiết</a>
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
                        <a class="page-link" href="category.php?id=<?= $category_id ?>&page=<?= $i ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor ?>
            </ul>
        </nav>
    <?php endif ?>
</div>

<?php include 'includes/footer.php'; ?>