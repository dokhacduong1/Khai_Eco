<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $wishlistStmt = $pdo->prepare("
        SELECT w.*, p.Title, p.Price, p.DiscountPercent, pi.ImageURL
        FROM Wishlist w
        JOIN Products p ON w.ProductID = p.ID
        LEFT JOIN (
            SELECT ProductID, MIN(ImageURL) as ImageURL
            FROM ProductImages
            GROUP BY ProductID
        ) pi ON p.ID = pi.ProductID
        WHERE w.UserID = ?
        ORDER BY w.CreatedAt DESC
    ");
    $wishlistStmt->execute([$user_id]);
    $wishlist = $wishlistStmt->fetchAll();

} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách yêu thích - Ecommerce</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto my-8 px-4">
        <h2 class="text-2xl font-bold mb-6">Danh sách yêu thích</h2>
        <?php if (empty($wishlist)): ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p class="text-gray-700">Danh sách yêu thích của bạn đang trống.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($wishlist as $item): ?>
                    <div class="bg-white p-4 rounded-lg shadow-md">
                        <img src="<?= $item['ImageURL'] ? './uploads/products/' . basename($item['ImageURL']) : '/assets/no-image.jpg' ?>" 
                             class="w-full h-48 object-contain mb-4" 
                             alt="<?= htmlspecialchars($item['Title']) ?>">
                        <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($item['Title']) ?></h3>
                        <div class="flex justify-between items-center mb-4">
                            <?php if ($item['DiscountPercent'] > 0): ?>
                                <span class="text-red-500 font-bold">
                                    <?= number_format($item['Price'] * (1 - $item['DiscountPercent'] / 100), 0, '', ',') ?> VNĐ
                                </span>
                                <del class="text-gray-500"><?= number_format($item['Price'], 0, '', ',') ?> VNĐ</del>
                            <?php else: ?>
                                <span class="text-gray-900 font-bold"><?= number_format($item['Price'], 0, '', ',') ?> VNĐ</span>
                            <?php endif ?>
                        </div>
                        <a href="product.php?id=<?= $item['ProductID'] ?>" class="block bg-black text-white text-center py-2 rounded-lg hover:bg-back">Xem chi tiết</a>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>