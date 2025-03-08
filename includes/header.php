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
    <style>
 .dropdown-menu .dropdown-submenu {
    position: relative;
}

.dropdown-menu .dropdown-submenu .dropdown-menu {
    top: 0;
    left: 100%;
    margin-top: -1px;
    margin-left: 0.1rem;
    display: none;
}

.dropdown-menu .dropdown-submenu:hover > .dropdown-menu {
    display: block;
}

.dropdown-menu .dropdown-submenu .dropdown-toggle::after {
    content: "▸";
    float: right;
    margin-left: 0.5rem;
    vertical-align: middle;
    border: none;
}

@media (min-width: 992px) {
    .dropdown-menu .dropdown-submenu:hover > .dropdown-menu {
        display: block;
    }
    
    .dropdown-menu .dropdown-submenu .dropdown-menu {
        transform: translateY(-10px);
        opacity: 0;
        transition: all 0.3s ease;
        pointer-events: none;
    }
    
    .dropdown-menu .dropdown-submenu:hover > .dropdown-menu {
        transform: translateY(0);
        opacity: 1;
        pointer-events: auto;
    }
}

/* Hiệu ứng product card */
.product-card {
    transition: transform 0.3s, box-shadow 0.3s;
    border: 1px solid rgba(0,0,0,0.125);
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}

/* Responsive hình ảnh */
.card-img-top {
    object-fit: contain;
    padding: 15px;
}
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/webdungai">
                <?php if (!empty($settings['logo_url'])): ?>
                    <img src="<?= $settings['logo_url'] ?>" alt="Logo" style="height: 40px;">
                <?php else: ?>
                    <?= htmlspecialchars($settings['site_title'] ?? 'Ecommerce') ?>
                <?php endif ?>
            </a>
            <li class="nav-item dropdown">
    <a class="dropdown-toggle" style="color:white" href="#" role="button" data-bs-toggle="dropdown">
        Danh mục
    </a>
    <ul class="dropdown-menu">
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
                    
                    $html .= '<li class="' . ($hasChildren ? 'dropdown-submenu' : '') . '">';
                    $html .= '<a class="dropdown-item d-flex justify-content-between align-items-center" 
                                href="category.php?id=' . $category['ID'] . '">';
                    $html .= htmlspecialchars($category['Name']);
                    if ($hasChildren) {
                        $html .= '<span class="dropdown-toggle"></span>';
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

        $categories = $pdo->query("
            SELECT * FROM Categories 
            ORDER BY ParentID, Name
        ")->fetchAll();

        echo displayCategories($categories);
        ?>
    </ul>
</li>
            <!-- Search Form -->
            <form class="d-flex mx-auto col-6" action="search.php" method="GET">
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
            <div class="d-flex align-items-center">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="cart.php" class="btn btn-light me-2"><i class="fas fa-shopping-cart"></i></a>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i>
                        </button>
                        <ul class="dropdown-menu">
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
    </nav>