-- ============================================
-- ฐานข้อมูลเว็บไซต์ขายของ (Shop Database)
-- ใช้กับ XAMPP / phpMyAdmin
-- วิธีใช้: เปิด phpMyAdmin -> Import -> เลือกไฟล์นี้
-- ============================================

CREATE DATABASE IF NOT EXISTS shop_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shop_db;

-- ตารางผู้ใช้งาน
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,          -- เก็บด้วย password_hash() ห้ามเก็บข้อความดิบ
    full_name VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    status ENUM('active','banned') NOT NULL DEFAULT 'active',
    login_attempts INT NOT NULL DEFAULT 0,   -- กันการโจมตีแบบ brute force
    locked_until DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ตารางหมวดหมู่สินค้า
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ตารางสินค้า
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock INT NOT NULL DEFAULT 0,
    image VARCHAR(255) DEFAULT 'no-image.png',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ตารางตะกร้าสินค้า
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_user_product (user_id, product_id)
) ENGINE=InnoDB;

-- ตารางคำสั่งซื้อ
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    shipping_address TEXT,
    payment_method VARCHAR(50) DEFAULT 'โอนเงิน/เก็บเงินปลายทาง',
    status ENUM('pending','paid','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ตารางรายการสินค้าในคำสั่งซื้อ
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- ข้อมูลตัวอย่าง
-- ============================================

-- บัญชีแอดมิน (username: admin / password: Admin@123)
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@myshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'admin');
-- หมายเหตุ: แฮชด้านบนคือ password "password" (bcrypt ตัวอย่าง)
-- แนะนำให้สมัครสมาชิกใหม่ผ่านหน้าเว็บ แล้วอัปเดต role เป็น admin ในภายหลังเพื่อความปลอดภัย

INSERT INTO categories (name, slug) VALUES
('เสื้อผ้าแฟชั่น', 'fashion'),
('รองเท้า', 'shoes'),
('กระเป๋า', 'bags'),
('อุปกรณ์อิเล็กทรอนิกส์', 'electronics');

INSERT INTO products (category_id, name, description, price, stock, image) VALUES
(1, 'เสื้อยืดสีดำโลโก้แดง', 'เสื้อยืดผ้าคอตตอน 100% ทรงสวย ใส่สบาย', 299.00, 50, 'no-image.png'),
(1, 'แจ็คเก็ตหนังสีดำ', 'แจ็คเก็ตหนัง PU ทรงมอเตอร์ไซค์ สไตล์เท่', 1290.00, 20, 'no-image.png'),
(2, 'รองเท้าผ้าใบสีเทา-แดง', 'รองเท้าผ้าใบใส่สบาย เหมาะกับทุกโอกาส', 890.00, 35, 'no-image.png'),
(3, 'กระเป๋าสะพายสีดำ', 'กระเป๋าสะพายหนัง PU ทนทาน ดีไซน์ทันสมัย', 650.00, 25, 'no-image.png'),
(4, 'หูฟังไร้สาย', 'หูฟังบลูทูธเสียงดี แบตอึด ตัดเสียงรบกวน', 1590.00, 40, 'no-image.png');
