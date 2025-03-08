<?php
session_start();
require_once 'config/database.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);

    // Validate
    if (empty($name)) $errors[] = 'Vui lòng nhập họ tên';
    if (empty($email)) $errors[] = 'Vui lòng nhập email';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ';
    if (strlen($password) < 6) $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';
    if ($password !== $confirm_password) $errors[] = 'Mật khẩu không khớp';

    // Check email exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT ID FROM Users WHERE Email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email đã được đăng ký';
            }
        } catch (PDOException $e) {
            $errors[] = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }

    // Create user
    if (empty($errors)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO Users (FullName, Email, PasswordHash, Phone, Role) 
                VALUES (?, ?, ?, ?, 'customer')
            ");
            $stmt->execute([$name, $email, $passwordHash, $phone]);

            $success = 'Đăng ký thành công! Vui lòng đăng nhập';
            $_POST = []; // Clear form

        } catch (PDOException $e) {
            $errors[] = 'Lỗi đăng ký: ' . $e->getMessage();
        }
    }
}

// Nếu đã đăng nhập thì chuyển hướng
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto mt-12 p-4">
        <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold mb-6 text-center">Đăng ký tài khoản</h2>
            
            <?php if ($errors): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                    <?php foreach ($errors as $error): ?>
                        <div><?= $error ?></div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>

            <?php if ($success): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded mb-4"><?= $success ?></div>
            <?php endif ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700">Họ và tên</label>
                    <input type="text" name="name" class="w-full px-3 py-2 border border-gray-300 rounded mt-1" required
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700">Email</label>
                    <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded mt-1" required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700">Số điện thoại</label>
                    <input type="tel" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded mt-1"
                        value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700">Mật khẩu</label>
                    <input type="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded mt-1" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700">Nhập lại mật khẩu</label>
                    <input type="password" name="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded mt-1" required>
                </div>

                <button type="submit" class="w-full bg-black text-white py-2 rounded">Đăng ký</button>
                
                <div class="mt-4 text-center">
                    <a href="login.php" class="text-black hover:underline">Đăng nhập ngay</a>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>