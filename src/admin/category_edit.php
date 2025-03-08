<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$category = ['ID' => '', 'Name' => '', 'Slug' => '', 'ParentID' => ''];
$error = '';
$allCategories = [];

try {
    // Lấy danh sách danh mục để chọn parent
    $allCategories = $pdo->query("SELECT * FROM Categories WHERE ID != " . ($_GET['id'] ?? 0))->fetchAll();

    // Chế độ chỉnh sửa
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM Categories WHERE ID = ?");
        $stmt->execute([$_GET['id']]);
        $category = $stmt->fetch();

        if (!$category) {
            header('Location: categories.php');
            exit;
        }
    }

    // Xử lý submit form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']);
        $parentID = $_POST['parent_id'] ?: null;

        if (empty($name)) {
            $error = 'Category name is required';
        } else {
            // Tạo slug
            $slug = strtolower(str_replace(' ', '-', $name));
            $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);

            // Kiểm tra slug trùng
            $slugCheck = $pdo->prepare("SELECT ID FROM Categories WHERE Slug = ? AND ID != ?");
            $slugCheck->execute([$slug, $category['ID']]);
            
            if ($slugCheck->fetch()) {
                $slug .= '-' . time();
            }

            try {
                if ($category['ID']) {
                    // Update
                    $stmt = $pdo->prepare("
                        UPDATE Categories SET
                            Name = ?,
                            Slug = ?,
                            ParentID = ?
                        WHERE ID = ?
                    ");
                    $stmt->execute([$name, $slug, $parentID, $category['ID']]);
                    $_SESSION['success_message'] = 'Category updated successfully';
                } else {
                    // Insert
                    $stmt = $pdo->prepare("
                        INSERT INTO Categories (Name, Slug, ParentID)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$name, $slug, $parentID]);
                    $_SESSION['success_message'] = 'Category added successfully';
                }
                
                header('Location: categories.php');
                exit;

            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }

} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $category['ID'] ? 'Edit' : 'Add' ?> Category</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h3><?= $category['ID'] ? 'Edit Category' : 'Add New Category' ?></h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif ?>

        <form method="POST">
            <div class="mb-3">
                <label>Category Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" 
                    value="<?= htmlspecialchars($category['Name']) ?>" required>
            </div>

            <div class="mb-3">
                <label>Parent Category</label>
                <select name="parent_id" class="form-select">
                    <option value="">-- No Parent --</option>
                    <?php foreach ($allCategories as $cat): ?>
                        <option value="<?= $cat['ID'] ?>"
                            <?= $cat['ID'] == $category['ParentID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['Name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Category</button>
                <a href="categories.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>