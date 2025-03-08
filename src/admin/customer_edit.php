<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$customer = ['ID' => '', 'FullName' => '', 'Email' => '', 'Phone' => '', 'Address' => ''];
$error = '';

if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE ID = ? AND Role = 'customer'");
        $stmt->execute([$_GET['id']]);
        $customer = $stmt->fetch();

        if (!$customer) {
            header('Location: customers.php');
            exit;
        }
    } catch (PDOException $e) {
        $error = "Error loading customer: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (empty($name)) {
        $error = 'Full name is required';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE Users SET
                    FullName = ?,
                    Phone = ?,
                    Address = ?
                WHERE ID = ?
            ");
            $stmt->execute([$name, $phone, $address, $customer['ID']]);
            
            $_SESSION['success_message'] = 'Customer updated successfully';
            header('Location: customers.php');
            exit;

        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Customer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h3>Edit Customer</h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif ?>

        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" 
                            value="<?= htmlspecialchars($customer['FullName']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" class="form-control" 
                            value="<?= $customer['Email'] ?>" readonly>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="form-control" 
                            value="<?= htmlspecialchars($customer['Phone']) ?>">
                    </div>

                    <div class="mb-3">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3"><?= 
                            htmlspecialchars($customer['Address']) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="customers.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>