<?php
require_once 'auth_check.php';
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.* 
    FROM Payments p
    WHERE OrderID = ?
");
$stmt->execute([$_GET['id']]);
$payment = $stmt->fetch();

if (!$payment) {
    header('Location: orders.php');
    exit;
}

$statuses = ['pending', 'completed', 'failed'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['status'];
    $transactionId = trim($_POST['transaction_id']);

    if (!in_array($newStatus, $statuses)) {
        $error = 'Invalid payment status';
    } else {
        try {
            $updateStmt = $pdo->prepare("
                UPDATE Payments 
                SET Status = ?, TransactionID = ?
                WHERE OrderID = ?
            ");
            $updateStmt->execute([
                $newStatus, 
                $transactionId ?: null,
                $_GET['id']
            ]);

            $_SESSION['success_message'] = 'Payment updated successfully';
            header('Location: order_detail.php?id=' . $_GET['id']);
            exit;

        } catch (PDOException $e) {
            $error = 'Error updating payment: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Update Payment for Order #<?= $_GET['id'] ?></h3>
            <a href="order_detail.php?id=<?= $_GET['id'] ?>" class="btn btn-secondary">Back to Order</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif ?>

        <form method="POST">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label>Current Status</label>
                        <input type="text" class="form-control" 
                            value="<?= ucfirst($payment['Status']) ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label>New Status</label>
                        <select name="status" class="form-select" required>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= $status ?>" 
                                    <?= $status === $payment['Status'] ? 'selected' : '' ?>>
                                    <?= ucfirst($status) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Transaction ID</label>
                        <input type="text" name="transaction_id" class="form-control" 
                            value="<?= htmlspecialchars($payment['TransactionID'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">Update Payment</button>
                </div>
            </div>
        </form>
    </div>
</body>
</html>