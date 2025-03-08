<?php
session_start();
require_once 'config/database.php';

$error = '';
$settings = [];
try {
    $stmt = $pdo->query("SELECT KeyName, KeyValue FROM Settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    die("Error loading settings: " . $e->getMessage());
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['PasswordHash'])) {
            $_SESSION['user_id'] = $user['ID'];
            $_SESSION['user_email'] = $user['Email'];
            $_SESSION['user_role'] = $user['Role'];
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Email hoặc mật khẩu không chính xác';
        }
    } catch (PDOException $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
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
    <title>Đăng nhập</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto mt-12 p-4">
        <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold mb-6 text-center">Đăng nhập</h2>
            
            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?= $error ?></div>
            <?php endif ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700">Email</label>
                    <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded mt-1" required 
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700">Mật khẩu</label>
                    <input type="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded mt-1" required>
                </div>

                <button type="submit" class="w-full bg-black text-white py-2 rounded">Đăng nhập</button>
                
                <div class="mt-4 text-center">
                    <a href="register.php" class="text-black hover:underline">Đăng ký ngay</a>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>