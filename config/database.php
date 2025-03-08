<?php
// File: config/database.php

$db_host = 'localhost';     // Database host
$db_name = 'ecommerce';     // Database name
$db_user = 'root';          // Database username
$db_pass = '270901';              // Database password

try {
    // Tạo kết nối PDO
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Hiển thị thông báo kết nối thành công (chỉ dùng cho môi trường dev)
    // echo "Connected successfully!";
    
} catch (PDOException $e) {
    // Xử lý lỗi kết nối
    error_log("Database connection failed: " . $e->getMessage());
    
    // Hiển thị thông báo lỗi thân thiện
    die("Could not connect to the database. Please check your configuration.");
}
?>