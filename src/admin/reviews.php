<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$reviews = [];
$error = '';

// Fetch reviews
try {
    $reviewsStmt = $pdo->prepare("
        SELECT r.*, p.Title as ProductTitle, u.FullName as UserName 
        FROM Reviews r
        LEFT JOIN Products p ON r.ProductID = p.ID
        LEFT JOIN Users u ON r.UserID = u.ID
        ORDER BY r.CreatedAt DESC
    ");
    $reviewsStmt->execute();
    $reviews = $reviewsStmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error fetching reviews: ' . $e->getMessage();
}

// Handle delete review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review_id'])) {
    try {
        $deleteStmt = $pdo->prepare("DELETE FROM Reviews WHERE ID = ?");
        $deleteStmt->execute([$_POST['delete_review_id']]);
        header('Location: reviews.php');
        exit;
    } catch (PDOException $e) {
        $error = 'Error deleting review: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Reviews</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h3>Manage Reviews</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif ?>

        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>User</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $review): ?>
                <tr>
                    <td><?= $review['ID'] ?></td>
                    <td><?= htmlspecialchars($review['ProductTitle']) ?></td>
                    <td><?= htmlspecialchars($review['UserName']) ?></td>
                    <td><?= $review['Rating'] ?></td>
                    <td><?= htmlspecialchars($review['Comment']) ?></td>
                    <td><?= date('Y-m-d H:i:s', strtotime($review['CreatedAt'])) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="delete_review_id" value="<?= $review['ID'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</body>
</html>