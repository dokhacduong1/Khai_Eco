<?php
session_start();
require_once '../config/database.php';

// Kiểm tra nếu người dùng đã đăng nhập và có quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

try {
    // Lấy danh sách bình luận
    $commentsStmt = $pdo->query("
        SELECT c.*, u.FullName, p.Title 
        FROM Comments c
        LEFT JOIN Users u ON c.UserID = u.ID
        LEFT JOIN Products p ON c.ProductID = p.ID
        ORDER BY c.CreatedAt DESC
    ");
    $comments = $commentsStmt->fetchAll();
} catch (PDOException $e) {
    die("Error loading comments: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý bình luận</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto my-8 px-4">
        <h1 class="text-2xl font-bold mb-6">Quản lý bình luận</h1>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Người bình luận</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bình luận</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Xếp hạng</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày bình luận</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($comments as $comment): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($comment['FullName']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($comment['Title']) ?></td