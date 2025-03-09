-- Tạo database
CREATE DATABASE ecommerce;
USE ecommerce;

-- Bảng người dùng
CREATE TABLE Users (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    FullName VARCHAR(255) NOT NULL,
    Email VARCHAR(255) UNIQUE NOT NULL,
    PasswordHash VARCHAR(255) NOT NULL,
    Phone VARCHAR(20),
    Address TEXT,
    Role ENUM('customer', 'admin') DEFAULT 'customer',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng danh mục sản phẩm
CREATE TABLE Categories (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(255) NOT NULL,
    Slug VARCHAR(255) UNIQUE NOT NULL,
    ParentID INT DEFAULT NULL,
    FOREIGN KEY (ParentID) REFERENCES Categories(ID) ON DELETE SET NULL
);

-- Bảng sản phẩm
CREATE TABLE Products (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Title VARCHAR(255) NOT NULL,
    Slug VARCHAR(255) UNIQUE NOT NULL,
    Description TEXT,
    Price DECIMAL(10,2) NOT NULL,
    DiscountPercent INT DEFAULT 0,
    Stock INT NOT NULL,
    CategoryID INT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (CategoryID) REFERENCES Categories(ID) ON DELETE SET NULL
);
CREATE TABLE Comments (
    CommentID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    ProductID INT NOT NULL,
    OrderID INT NOT NULL,
    CommentText TEXT NOT NULL,
    Rating INT NOT NULL CHECK (Rating >= 1 AND Rating <= 5),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(ID),
    FOREIGN KEY (ProductID) REFERENCES Products(ID),
    FOREIGN KEY (OrderID) REFERENCES Orders(ID)
);
-- Bảng hình ảnh sản phẩm
CREATE TABLE ProductImages (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ProductID INT,
    ImageURL VARCHAR(255) NOT NULL,
    FOREIGN KEY (ProductID) REFERENCES Products(ID) ON DELETE CASCADE
);

-- Bảng giỏ hàng
CREATE TABLE Cart (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    ProductID INT,
    Quantity INT NOT NULL,
    FOREIGN KEY (UserID) REFERENCES Users(ID) ON DELETE CASCADE,
    FOREIGN KEY (ProductID) REFERENCES Products(ID) ON DELETE CASCADE
);

-- Bảng đơn hàng
CREATE TABLE Orders (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    TotalPrice DECIMAL(10,2) NOT NULL,
    Status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(ID) ON DELETE CASCADE
);

-- Bảng chi tiết đơn hàng
CREATE TABLE OrderItems (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    OrderID INT,
    ProductID INT,
    Quantity INT NOT NULL,
    Price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (OrderID) REFERENCES Orders(ID) ON DELETE CASCADE,
    FOREIGN KEY (ProductID) REFERENCES Products(ID) ON DELETE CASCADE
);

-- Bảng thanh toán
CREATE TABLE Payments (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    OrderID INT,
    PaymentMethod ENUM('cod', 'credit_card', 'paypal') NOT NULL,
    Status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    TransactionID VARCHAR(255),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (OrderID) REFERENCES Orders(ID) ON DELETE CASCADE
);

-- Bảng danh sách yêu thích
CREATE TABLE Wishlist (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    ProductID INT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(ID) ON DELETE CASCADE,
    FOREIGN KEY (ProductID) REFERENCES Products(ID) ON DELETE CASCADE
);

-- Bảng đánh giá sản phẩm
CREATE TABLE Reviews (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    ProductID INT,
    Rating INT CHECK (Rating BETWEEN 1 AND 5),
    Comment TEXT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(ID) ON DELETE CASCADE,
    FOREIGN KEY (ProductID) REFERENCES Products(ID) ON DELETE CASCADE
);

-- Bảng theo dõi đơn hàng
CREATE TABLE OrderTracking (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    OrderID INT,
    Status ENUM('processing', 'shipped', 'delivered', 'cancelled') NOT NULL,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (OrderID) REFERENCES Orders(ID) ON DELETE CASCADE
);

-- Bảng quản trị viên
CREATE TABLE Admins (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    Role ENUM('manager', 'staff') DEFAULT 'staff',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(ID) ON DELETE CASCADE
);

CREATE TABLE Settings (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    KeyName VARCHAR(255) UNIQUE NOT NULL,
    KeyValue TEXT,
    KeyType ENUM('text', 'image', 'textarea', 'number') DEFAULT 'text',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT INTO Settings (KeyName, KeyValue, KeyType) VALUES
('site_title', 'My Ecommerce', 'text'),
('site_description', 'Best online shopping platform', 'textarea'),
('logo_url', 'assets/logo.png', 'image'),
('banner_1', 'assets/banner1.jpg', 'image'),
('banner_2', 'assets/banner2.jpg', 'image'),
('contact_email', 'info@ecommerce.com', 'text'),
('phone_number', '0123456789', 'text'),
('facebook_url', 'https://facebook.com', 'text'),
('instagram_url', 'https://instagram.com', 'text'),
('currency', 'USD', 'text');

INSERT INTO Settings (KeyName, KeyValue, KeyType) VALUES
('featured_title', 'Sản phẩm nổi bật', 'text'),
('promotion_text', 'Giảm giá lên đến 50%', 'text'),
('promotion_button', 'Mua ngay', 'text'),
('footer_about_title', 'Về chúng tôi', 'text'),
('footer_links_title', 'Liên kết nhanh', 'text'),
('footer_social_title', 'Kết nối với chúng tôi', 'text');