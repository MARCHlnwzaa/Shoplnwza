<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อน']);
    exit;
}
if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'คำขอไม่ถูกต้อง (CSRF)']);
    exit;
}

$pdo = getDB();
$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$productId = (int)($_POST['product_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

try {
    if ($action === 'add') {
        $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = ? AND is_active = 1');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบสินค้า']);
            exit;
        }
        if ($quantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'จำนวนสินค้าคงเหลือไม่เพียงพอ']);
            exit;
        }
        // ถ้ามีอยู่แล้วในตะกร้า ให้บวกจำนวนเพิ่ม
        $stmt = $pdo->prepare('INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)
                                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)');
        $stmt->execute([$userId, $productId, $quantity]);

    } elseif ($action === 'update') {
        $stmt = $pdo->prepare('UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$quantity, $userId, $productId]);

    } elseif ($action === 'remove') {
        $stmt = $pdo->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$userId, $productId]);

    } else {
        echo json_encode(['success' => false, 'message' => 'คำสั่งไม่ถูกต้อง']);
        exit;
    }

    echo json_encode(['success' => true, 'cart_count' => cart_count()]);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดของระบบ']);
}
