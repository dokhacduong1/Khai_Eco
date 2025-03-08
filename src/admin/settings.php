<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$error = '';
$success = '';

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        foreach ($_POST as $key => $value) {
            $stmt = $pdo->prepare("
                UPDATE Settings 
                SET KeyValue = ?
                WHERE KeyName = ?
            ");
            $stmt->execute([trim($value), $key]);
        }

        // Xử lý upload ảnh
        foreach ($_FILES as $key => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../assets/';
                $fileName = uniqid() . '-' . basename($file['name']);
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $stmt = $pdo->prepare("
                        UPDATE Settings 
                        SET KeyValue = ?
                        WHERE KeyName = ?
                    ");
                    $stmt->execute(['assets/' . $fileName, $key]);
                }
            }
        }

        $pdo->commit();
        $success = 'Settings updated successfully!';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Error updating settings: ' . $e->getMessage();
    }
}

// Lấy tất cả settings
try {
    $stmt = $pdo->query("SELECT KeyName, KeyValue FROM Settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    die("Error loading settings: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Site Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h3>Site Settings</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="card mb-4">
                <div class="card-header">General Settings</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Site Title</label>
                                <input type="text" name="site_title" class="form-control" 
                                    value="<?= htmlspecialchars($settings['site_title'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label>Site Description</label>
                                <textarea name="site_description" class="form-control" rows="3"><?= 
                                    htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label>Contact Email</label>
                                <input type="email" name="contact_email" class="form-control" 
                                    value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Logo</label>
                                <?php if (!empty($settings['logo_url'])): ?>
                                    <img src="../<?= $settings['logo_url'] ?>" 
                                        class="img-thumbnail mb-2 d-block" 
                                        style="max-width: 200px">
                                <?php endif ?>
                                <input type="file" name="logo_url" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label>Currency</label>
                                <select name="currency" class="form-select">
                                    <option value="USD" <?= ($settings['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD</option>
                                    <option value="VND" <?= ($settings['currency'] ?? '') === 'VND' ? 'selected' : '' ?>>VND</option>
                                    <option value="EUR" <?= ($settings['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Banner Settings</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Banner 1</label>
                                <?php if (!empty($settings['banner_1'])): ?>
                                    <img src="../<?= $settings['banner_1'] ?>" 
                                        class="img-thumbnail mb-2 d-block" 
                                        style="max-width: 100%">
                                <?php endif ?>
                                <input type="file" name="banner_1" class="form-control">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Banner 2</label>
                                <?php if (!empty($settings['banner_2'])): ?>
                                    <img src="../<?= $settings['banner_2'] ?>" 
                                        class="img-thumbnail mb-2 d-block" 
                                        style="max-width: 100%">
                                <?php endif ?>
                                <input type="file" name="banner_2" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Social Media</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Facebook URL</label>
                                <input type="url" name="facebook_url" class="form-control" 
                                    value="<?= htmlspecialchars($settings['facebook_url'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Instagram URL</label>
                                <input type="url" name="instagram_url" class="form-control" 
                                    value="<?= htmlspecialchars($settings['instagram_url'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Settings</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>