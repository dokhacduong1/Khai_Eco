<?php
session_start();
require_once 'config/database.php';

// Kiểm tra nếu người dùng đã đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Kiểm tra nếu form đã được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userID = $_SESSION['user_id'];
    $productID = $_POST['product_id'];
    $orderID = $_POST['order_id'];
    $commentText = trim($_POST['comment_text']);
    $rating = (int)$_POST['rating'];

    // Validate dữ liệu
    if (empty($commentText) || $rating < 1 || $rating > 5) {
        $error = 'Vui lòng nhập bình luận và xếp hạng hợp lệ.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO Comments (UserID, ProductID, OrderID, CommentText, Rating)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userID, $productID, $orderID, $commentText, $rating]);
            $success = 'Bình luận của bạn đã được thêm thành công.';
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm bình luận</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800">
    <div class="container mx-auto my-8 px-4">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-4">Thêm bình luận</h2>
            <?php if (isset($error)): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?= $error ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded mb-4"><?= $success ?></div>
            <?php endif; ?>
            <form action="add_comment.php" method="POST">
                <input type="hidden" name="product_id" value="<?= htmlspecialchars($_GET['product_id']) ?>">
                <input type="hidden" name="order_id" value="<?= htmlspecialchars($_GET['order_id']) ?>">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Bình luận</label>
                    <textarea name="comment_text" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Xếp hạng</label>
                    <select name="rating" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="5">5 sao</option>
                        <option value="4">4 sao</option>
                        <option value="3">3 sao</option>
                        <option value="2">2 sao</option>
                        <option value="1">1 sao</option>
                    </select>
                </div>
                <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded-lg shadow hover:bg-blue-600">Gửi bình luận</button>
            </form>
        </div>
    </div>
</body>
</html>