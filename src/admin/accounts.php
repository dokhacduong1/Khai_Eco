<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$users = [];
$error = '';

// Fetch users
try {
    $usersStmt = $pdo->prepare("
        SELECT * FROM Users
        ORDER BY CreatedAt DESC
    ");
    $usersStmt->execute();
    $users = $usersStmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error fetching users: ' . $e->getMessage();
}

// Handle delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    try {
        $deleteStmt = $pdo->prepare("DELETE FROM Users WHERE ID = ?");
        $deleteStmt->execute([$_POST['delete_user_id']]);
        header('Location: accounts.php');
        exit;
    } catch (PDOException $e) {
        $error = 'Error deleting user: ' . $e->getMessage();
    }
}

// Handle add/edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $fullName = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $userId = $_POST['user_id'] ?? null;

    try {
        if ($_POST['action'] == 'add') {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $addStmt = $pdo->prepare("
                INSERT INTO Users (FullName, Email, PasswordHash, Role, CreatedAt)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $addStmt->execute([$fullName, $email, $passwordHash, $role]);
        } elseif ($_POST['action'] == 'edit' && $userId) {
            if (!empty($password)) {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                $editStmt = $pdo->prepare("
                    UPDATE Users 
                    SET FullName = ?, Email = ?, PasswordHash = ?, Role = ? 
                    WHERE ID = ?
                ");
                $editStmt->execute([$fullName, $email, $passwordHash, $role, $userId]);
            } else {
                $editStmt = $pdo->prepare("
                    UPDATE Users 
                    SET FullName = ?, Email = ?, Role = ? 
                    WHERE ID = ?
                ");
                $editStmt->execute([$fullName, $email, $role, $userId]);
            }
        }
        header('Location: accounts.php');
        exit;
    } catch (PDOException $e) {
        $error = 'Error saving user: ' . $e->getMessage();
    }
}

// Fetch user for editing
$editUser = null;
if (isset($_GET['edit_user_id'])) {
    try {
        $editStmt = $pdo->prepare("SELECT * FROM Users WHERE ID = ?");
        $editStmt->execute([$_GET['edit_user_id']]);
        $editUser = $editStmt->fetch();
    } catch (PDOException $e) {
        $error = 'Error fetching user: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Accounts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h3>Manage Accounts</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif ?>

        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['ID'] ?></td>
                    <td><?= htmlspecialchars($user['FullName']) ?></td>
                    <td><?= htmlspecialchars($user['Email']) ?></td>
                    <td><?= htmlspecialchars($user['Role']) ?></td>
                    <td><?= date('Y-m-d H:i:s', strtotime($user['CreatedAt'])) ?></td>
                    <td>
                        <a href="accounts.php?edit_user_id=<?= $user['ID'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="delete_user_id" value="<?= $user['ID'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>

        <h3 class="mt-4"><?= $editUser ? 'Edit User' : 'Add User' ?></h3>
        <form method="POST">
            <input type="hidden" name="user_id" value="<?= $editUser['ID'] ?? '' ?>">
            <div class="mb-3">
                <label for="fullname" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="fullname" name="fullname" value="<?= htmlspecialchars($editUser['FullName'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($editUser['Email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" <?= $editUser ? '' : 'required' ?>>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="customer" <?= isset($editUser['Role']) && $editUser['Role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                    <option value="admin" <?= isset($editUser['Role']) && $editUser['Role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" name="action" value="<?= $editUser ? 'edit' : 'add' ?>"><?= $editUser ? 'Save' : 'Add' ?> User</button>
        </form>
    </div>
</body>
</html>