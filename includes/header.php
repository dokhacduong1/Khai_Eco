<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
$settings = $pdo->query("SELECT KeyName, KeyValue FROM Settings")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['site_title'] ?? 'Ecommerce') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/webdungai/assets/css/style.css">

</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/webdungai">GNOUD</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 col-1">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span>Sản phẩm</span>
                            <i class="fas fa-chevron-down" style="font-size:12px"></i>
                     
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <?php
                            function displayCategories($categories, $parentId = null, $level = 0) {
                                $html = '';
                                foreach ($categories as $category) {
                                    if ($category['ParentID'] == $parentId) {
                                        $hasChildren = false;
                                        foreach ($categories as $child) {
                                            if ($child['ParentID'] == $category['ID']) {
                                                $hasChildren = true;
                                                break;
                                            }
                                        }
                                        
                                        $html .= '<li class="dropdown-submenu">';
                                        $html .= '<a class="dropdown-item d-flex justify-content-between align-items-center" href="category.php?id=' . $category['ID'] . '">';
                                        $html .= htmlspecialchars($category['Name']);
                                        if ($hasChildren) {
                                            $html .= '<i class="fas fa-chevron-right"></i>';
                                        }
                                        $html .= '</a>';
                                        
                                        if ($hasChildren) {
                                            $html .= '<ul class="dropdown-menu">';
                                            $html .= displayCategories($categories, $category['ID'], $level + 1);
                                            $html .= '</ul>';
                                        }
                                        
                                        $html .= '</li>';
                                    }
                                }
                                return $html;
                            }
                            $categories = $pdo->query("SELECT * FROM Categories ORDER BY ParentID, Name")->fetchAll();
                            echo displayCategories($categories);
                            ?>
                        </ul>
                    </li>
                </ul>

                <!-- Search Form -->
                <form class="d-flex mx-auto col-9" action="search.php" method="GET">
                    <div class="input-group">
                        <select class="form-select" name="category" style="max-width: 200px;">
                            <option value="">Tất cả danh mục</option>
                            <?php 
                            $categories = $pdo->query("SELECT * FROM Categories WHERE ParentID IS NULL")->fetchAll();
                            foreach ($categories as $cat): ?>
                                <option value="<?= $cat['ID'] ?>"><?= htmlspecialchars($cat['Name']) ?></option>
                            <?php endforeach ?>
                        </select>
                        <input type="text" class="form-control" name="q" placeholder="Tìm kiếm sản phẩm...">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>

                <!-- User Menu -->
                <div class="d-flex align-items-center col-1">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="cart.php" class="btn btn-light me-2"><i class="fas fa-shopping-cart"></i></a>
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                                <li><a class="dropdown-item" href="profile.php">Tài khoản</a></li>
                                <li><a class="dropdown-item" href="orders.php">Đơn hàng</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Đăng xuất</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-light">Đăng nhập</a>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Khởi tạo carousel
        const carousel = new bootstrap.Carousel('.banner-carousel', {
            interval: 5000,
            wrap: true
        });

        // Xử lý hover product card
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mouseover', () => {
                card.style.cursor = 'pointer';
            });
        });
    </script>
</body>
</html>