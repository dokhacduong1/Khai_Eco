<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
$settings = $pdo->query("SELECT KeyName, KeyValue FROM Settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// Lấy số lượng sản phẩm trong giỏ hàng
$cartItemCount = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT SUM(Quantity) FROM Cart WHERE UserID = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartItemCount = (int) $stmt->fetchColumn();
}
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
    <style>
        /* Shadcn UI Style Variables */
        /* Shadcn UI Style Variables */
        :root {
            --background: 0 0% 100%;
            --foreground: 222.2 47.4% 11.2%;
            --muted: 210 40% 96.1%;
            --muted-foreground: 215.4 16.3% 46.9%;
            --popover: 0 0% 100%;
            --popover-foreground: 222.2 47.4% 11.2%;
            --border: 214.3 31.8% 91.4%;
            --input: 214.3 31.8% 91.4%;
            --card: 0 0% 100%;
            --card-foreground: 222.2 47.4% 11.2%;
            --primary: 222.2 47.4% 11.2%;
            --primary-foreground: 210 40% 98%;
            --secondary: 210 40% 96.1%;
            --secondary-foreground: 222.2 47.4% 11.2%;
            --accent: 210 40% 96.1%;
            --accent-foreground: 222.2 47.4% 11.2%;
            --destructive: 0 100% 50%;
            --destructive-foreground: 210 40% 98%;
            --ring: 215 20.2% 65.1%;
            --radius: 0.5rem;
        }

        /* Modern Header Styling */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            color: hsl(var(--foreground));
            background-color: hsl(var(--background));
        }
        
        .shadcn-header {
            padding: 0.75rem 0;
            background-color: white;
            border-bottom: 1px solid hsl(var(--border));
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .shadcn-container {
            max-width: 1200px;
            padding: 0 1rem;
            margin: 0 auto;
        }
        
        .shadcn-navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .shadcn-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: hsl(var(--primary));
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .shadcn-logo:hover {
            color: hsl(var(--primary-foreground));
        }
        
        .shadcn-nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .shadcn-nav-item {
            position: relative;
        }
        
        .shadcn-nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius);
            color: hsl(var(--foreground));
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        
        .shadcn-nav-link:hover,
        .shadcn-nav-link:focus {
            background-color: hsl(var(--accent));
            color: hsl(var(--accent-foreground));
        }
        
        /* Fix cho dropdown menu */
        .shadcn-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 220px;
            padding: 0.5rem;
            margin-top: 0.5rem;
            background-color: hsl(var(--popover));
            border-radius: var(--radius);
            box-shadow: 0 10px 38px -10px rgba(22, 23, 24, 0.35), 
                        0 10px 20px -15px rgba(22, 23, 24, 0.2);
            border: 1px solid hsl(var(--border));
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
            z-index: 20;
        }
        
        /* Thêm khoảng đệm ảo để giúp di chuột từ menu đến dropdown */
        .shadcn-nav-item::after {
            content: '';
            position: absolute;
            height: 20px;
            left: 0;
            right: 0;
            bottom: -10px;
            background: transparent;
            z-index: 10;
        }
        
        .shadcn-dropdown-submenu {
            position: relative;
        }
        
        /* Thêm khoảng đệm ảo cho submenu */
        .shadcn-dropdown-submenu::after {
            content: '';
            position: absolute;
            width: 20px;
            top: 0;
            bottom: 0;
            right: -20px;
            background: transparent;
            z-index: 10;
        }
        
        .shadcn-dropdown-submenu .shadcn-dropdown {
            top: 0;
            left: 100%;
            margin-left: 0.5rem;
            margin-top: 0;
        }
        
        /* Hiển thị dropdown khi hover */
        .shadcn-nav-item:hover > .shadcn-dropdown,
        .shadcn-dropdown-submenu:hover > .shadcn-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto;
        }
        
        .shadcn-dropdown-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0.75rem;
            color: hsl(var(--foreground));
            text-decoration: none;
            font-size: 0.875rem;
            border-radius: var(--radius);
            transition: background-color 0.2s ease;
            min-width: 180px;
            white-space: nowrap;
        }
        
        .shadcn-dropdown-link:hover {
            background-color: hsl(var(--accent));
            color: hsl(var(--accent-foreground));
        }
        
        .shadcn-search {
            flex: 1;
            max-width: 600px;
            margin: 0 2rem;
        }
        
        .shadcn-search-form {
            display: flex;
            position: relative;
        }
        
        .shadcn-search-input {
            flex: 1;
            height: 40px;
            padding: 0 1rem;
            background-color: hsl(var(--muted));
            border: 1px solid transparent;
            border-radius: var(--radius);
            font-size: 0.875rem;
            transition: border-color 0.2s ease;
            width: 100%;
        }
        
        .shadcn-search-input:focus {
            outline: none;
            border-color: hsl(var(--ring));
            box-shadow: 0 0 0 2px rgba(24, 24, 27, 0.05);
        }
        
        .shadcn-search-category {
            position: absolute;
            right: 40px;
            top: 0;
            height: 40px;
            background-color: hsl(var(--muted));
            border: none;
            border-left: 1px solid hsl(var(--border));
            font-size: 0.875rem;
            padding: 0 0.75rem;
            cursor: pointer;
            z-index: 1;
        }
        
        .shadcn-search-category:focus {
            outline: none;
        }
        
        .shadcn-search-button {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
            border: none;
            border-radius: 0 var(--radius) var(--radius) 0;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .shadcn-search-button:hover {
            background-color: hsl(var(--primary) / 0.9);
        }
        
        .shadcn-user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .shadcn-cart-button {
            position: relative;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: hsl(var(--accent));
            color: hsl(var(--accent-foreground));
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .shadcn-cart-button:hover {
            background-color: hsl(var(--accent) / 0.8);
        }
        
        .shadcn-cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: hsl(var(--destructive));
            color: hsl(var(--destructive-foreground));
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            padding: 0 0.25rem;
        }
        
        .shadcn-user-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: hsl(var(--accent));
            color: hsl(var(--accent-foreground));
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .shadcn-user-button:hover {
            background-color: hsl(var(--accent) / 0.8);
        }
        
        .shadcn-user-dropdown {
            right: 0;
            left: auto;
            width: 200px;
        }
        
        .shadcn-mobile-menu-button {
            display: none;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: transparent;
            color: hsl(var(--foreground));
            border: none;
            cursor: pointer;
        }
        
        /* Mobile Styles */
        @media (max-width: 992px) {
            .shadcn-navbar {
                flex-wrap: wrap;
            }
            
            .shadcn-mobile-menu-button {
                display: flex;
                order: 1;
            }
            
            .shadcn-logo {
                order: 2;
                margin-left: 1rem;
            }
            
            .shadcn-user-menu {
                order: 3;
                margin-left: auto;
            }
            
            .shadcn-search {
                order: 4;
                max-width: 100%;
                width: 100%;
                margin: 1rem 0;
            }
            
            .shadcn-nav {
                order: 5;
                flex-direction: column;
                width: 100%;
                gap: 0;
                display: none;
                margin-top: 1rem;
                align-items: flex-start;
            }
            
            .shadcn-nav.active {
                display: flex;
            }
            
            .shadcn-nav-item {
                width: 100%;
            }
            
            .shadcn-nav-link {
                width: 100%;
                justify-content: space-between;
            }
            
            .shadcn-dropdown {
                position: static;
                box-shadow: none;
                padding-left: 1.5rem;
                transform: none;
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
                display: none;
                margin-top: 0;
                width: 100%;
            }
            
            .shadcn-dropdown.active {
                display: block;
            }
            
            .shadcn-dropdown-submenu .shadcn-dropdown {
                margin-left: 1.5rem;
            }
            
            .shadcn-dropdown-link {
                width: 100%;
            }
            
            /* Gỡ bỏ khoảng đệm ảo trên mobile */
            .shadcn-nav-item::after,
            .shadcn-dropdown-submenu::after {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- ShadcnUI Inspired Header -->
    <header class="shadcn-header">
    <div class="shadcn-container">
        <div class="shadcn-navbar">
            <!-- Mobile Menu Button -->
            <button class="shadcn-mobile-menu-button" id="mobile-menu-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-menu">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            
            <!-- Logo -->
            <a href="/webdungai" class="shadcn-logo">GNOUD</a>
            
            <!-- Navigation Menu -->
            <nav class="shadcn-nav" id="main-nav">
                <div class="shadcn-nav-item">
                    <a href="#" class="shadcn-nav-link" id="categories-toggle">
                        Danh mục
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </a>
                    <div class="shadcn-dropdown" id="categories-dropdown">
                        <?php
                        function displayCategoriesNew($categories, $parentId = null) {
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
                                    
                                    $html .= '<div class="shadcn-dropdown-submenu">';
                                    $html .= '<a class="shadcn-dropdown-link" href="category.php?id=' . $category['ID'] . '">';
                                    $html .= htmlspecialchars($category['Name']);
                                    if ($hasChildren) {
                                        $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>';
                                    }
                                    $html .= '</a>';
                                    
                                    if ($hasChildren) {
                                        $html .= '<div class="shadcn-dropdown">';
                                        $html .= displayCategoriesNew($categories, $category['ID']);
                                        $html .= '</div>';
                                    }
                                    
                                    $html .= '</div>';
                                }
                            }
                            return $html;
                        }
                        $categories = $pdo->query("SELECT * FROM Categories ORDER BY ParentID, Name")->fetchAll();
                        echo displayCategoriesNew($categories);
                        ?>
                    </div>
                </div>
            </nav>
            
            <!-- Search Bar -->
            <div class="shadcn-search">
                <form class="shadcn-search-form" action="search.php" method="GET">
                    <input type="text" name="q" placeholder="Tìm kiếm sản phẩm..." class="shadcn-search-input">
                    <select class="shadcn-search-category" name="category">
                        <option value="">Tất cả</option>
                        <?php 
                        $categories = $pdo->query("SELECT * FROM Categories WHERE ParentID IS NULL")->fetchAll();
                        foreach ($categories as $cat): ?>
                            <option value="<?= $cat['ID'] ?>"><?= htmlspecialchars($cat['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="shadcn-search-button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </button>
                </form>
            </div>
            
            <!-- User Menu -->
            <div class="shadcn-user-menu">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="cart.php" class="shadcn-cart-button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-shopping-cart">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <?php if ($cartItemCount > 0): ?>
                            <span class="shadcn-cart-badge"><?= $cartItemCount ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="shadcn-nav-item">
                        <button class="shadcn-user-button" id="user-menu-toggle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </button>
                        <div class="shadcn-dropdown shadcn-user-dropdown" id="user-dropdown">
                            <a href="profile.php" class="shadcn-dropdown-link">
                                <span>Tài khoản</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </a>
                            <a href="orders.php" class="shadcn-dropdown-link">
                                <span>Đơn hàng</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-package">
                                    <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                </svg>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="shadcn-dropdown-link">
                                <span>Đăng xuất</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-log-out">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="shadcn-user-button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-log-in">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                            <polyline points="10 17 15 12 10 7"></polyline>
                            <line x1="15" y1="12" x2="3" y2="12"></line>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
<script>
    // Mobile menu toggle
    document.getElementById('mobile-menu-toggle').addEventListener('click', function() {
        document.getElementById('main-nav').classList.toggle('active');
    });

    // User dropdown toggle for mobile
    if (document.getElementById('user-menu-toggle')) {
        document.getElementById('user-menu-toggle').addEventListener('click', function() {
            document.getElementById('user-dropdown').classList.toggle('active');
        });
    }

    // Make dropdowns work with keyboard navigation
    document.querySelectorAll('.shadcn-nav-link, .shadcn-dropdown-link').forEach(item => {
        item.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                item.click();
            }
        });
    });

    // Handle categories dropdown for mobile
    document.getElementById('categories-toggle').addEventListener('click', function(e) {
        if (window.innerWidth < 992) {
            e.preventDefault();
            document.getElementById('categories-dropdown').classList.toggle('active');
        }
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.shadcn-nav-item') && !e.target.closest('.shadcn-mobile-menu-button')) {
            document.querySelectorAll('.shadcn-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
            
            if (window.innerWidth < 992) {
                document.getElementById('main-nav').classList.remove('active');
            }
        }
    });
</script>
</body>
</html>