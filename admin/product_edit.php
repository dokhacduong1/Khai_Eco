<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// Khởi tạo biến
$product = [
    'ID' => '',
    'Title' => '',
    'Slug' => '',
    'Description' => '',
    'Price' => '',
    'DiscountPercent' => 0,
    'Stock' => '',
    'CategoryID' => ''
];
$categories = [];
$images = [];
$error = '';

// Lấy danh sách danh mục
try {
    $categories = $pdo->query("SELECT * FROM Categories")->fetchAll();
} catch (PDOException $e) {
    $error = "Lỗi khi tải danh mục: " . $e->getMessage();
}

// Xử lý khi có ID sản phẩm (chế độ chỉnh sửa)
if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM Products WHERE ID = ?");
        $stmt->execute([$_GET['id']]);
        $product = $stmt->fetch();

        // Lấy ảnh sản phẩm
        $imgStmt = $pdo->prepare("SELECT * FROM ProductImages WHERE ProductID = ?");
        $imgStmt->execute([$_GET['id']]);
        $images = $imgStmt->fetchAll();

        if (!$product) {
            header('Location: products.php');
            exit;
        }
    } catch (PDOException $e) {
        $error = "Lỗi khi tải sản phẩm: " . $e->getMessage();
    }
}

// Xử lý submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate dữ liệu
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $discount = (int)$_POST['discount'];
    $stock = (int)$_POST['stock'];
    $category_id = (int)$_POST['category_id'];

    if (empty($title) || $price <= 0 || $stock < 0) {
        $error = "Vui lòng điền đầy đủ thông tin bắt buộc";
    } else {
        try {
            // Tạo slug từ title
            $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
            
            // Kiểm tra slug trùng lặp
            $slugCheck = $pdo->prepare("SELECT ID FROM Products WHERE Slug = ? AND ID != ?");
            $slugCheck->execute([$slug, $product['ID']]);
            
            if ($slugCheck->fetch()) {
                $slug = $slug . '-' . time();
            }

            // Lưu sản phẩm
            if ($product['ID']) {
                // Cập nhật
                $stmt = $pdo->prepare("
                    UPDATE Products SET
                        Title = ?,
                        Slug = ?,
                        Description = ?,
                        Price = ?,
                        DiscountPercent = ?,
                        Stock = ?,
                        CategoryID = ?,
                        UpdatedAt = NOW()
                    WHERE ID = ?
                ");
                $stmt->execute([
                    $title,
                    $slug,
                    $description,
                    $price,
                    $discount,
                    $stock,
                    $category_id,
                    $product['ID']
                ]);
            } else {
                // Thêm mới
                $stmt = $pdo->prepare("
                    INSERT INTO Products (
                        Title, Slug, Description, Price, 
                        DiscountPercent, Stock, CategoryID
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $title,
                    $slug,
                    $description,
                    $price,
                    $discount,
                    $stock,
                    $category_id
                ]);
                $product['ID'] = $pdo->lastInsertId();
            }

            // Xử lý upload ảnh
            if (!empty($_FILES['images']['name'][0])) {
                $uploadDir = '../uploads/products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                    $fileName = uniqid() . '-' . basename($_FILES['images']['name'][$key]);
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $imgStmt = $pdo->prepare("
                            INSERT INTO ProductImages (ProductID, ImageURL)
                            VALUES (?, ?)
                        ");
                        $imgStmt->execute([$product['ID'], 'uploads/products/' . $fileName]);
                    }
                }
            }

            header('Location: products.php');
            exit;

        } catch (PDOException $e) {
            $error = "Lỗi database: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $product['ID'] ? 'Chỉnh sửa' : 'Thêm mới' ?> Sản phẩm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .image-preview { max-width: 150px; margin: 5px; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h3><?= $product['ID'] ? 'Chỉnh sửa' : 'Thêm mới' ?> Sản phẩm</h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label>Tên sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" 
                            value="<?= htmlspecialchars($product['Title']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Mô tả</label>
                        <textarea name="description" class="form-control" rows="4"><?= 
                            htmlspecialchars($product['Description']) ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label>Giá <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-control" 
                                    value="<?= $product['Price'] ?>" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label>Giảm giá (%)</label>
                                <input type="number" name="discount" class="form-control" 
                                    value="<?= $product['DiscountPercent'] ?>" min="0" max="100">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label>Số lượng <span class="text-danger">*</span></label>
                                <input type="number" name="stock" class="form-control" 
                                    value="<?= $product['Stock'] ?>" min="0" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label>Danh mục</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['ID'] ?>" 
                                    <?= $cat['ID'] == $product['CategoryID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['Name']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Hình ảnh sản phẩm</label>
                        <input type="file" name="images[]" class="form-control" multiple 
                            accept="image/*">
                        
                        <?php if (!empty($images)): ?>
                            <div class="mt-3">
                                <label>Ảnh hiện tại:</label>
                                <div class="d-flex flex-wrap">
                                    <?php foreach ($images as $img): ?>
                                        <img src="../<?= $img['ImageURL'] ?>" 
                                            class="image-preview rounded border">
                                    <?php endforeach ?>
                                </div>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Lưu sản phẩm</button>
                <a href="products.php" class="btn btn-secondary">Hủy bỏ</a>
            </div>
        </form>
    </div>
</body>
</html>