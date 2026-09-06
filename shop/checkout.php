<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();
require_login();
$pdo = getDB();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare('
    SELECT c.quantity, p.id, p.name, p.price, p.stock
    FROM cart c JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
');
$stmt->execute([$userId]);
$items = $stmt->fetchAll();

if (empty($items)) {
    header('Location: cart.php');
    exit;
}

$total = 0;
foreach ($items as $it) { $total += $it['price'] * $it['quantity']; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'คำขอไม่ถูกต้อง กรุณาลองใหม่';
    } else {
        $address = trim($_POST['address'] ?? '');
        $payment = trim($_POST['payment_method'] ?? 'โอนเงิน/เก็บเงินปลายทาง');
        if ($address === '') {
            $errors[] = 'กรุณากรอกที่อยู่จัดส่ง';
        }

        if (empty($errors)) {
            try {
                // ----- ใช้ Transaction เพื่อความถูกต้องของข้อมูล (all-or-nothing) -----
                $pdo->beginTransaction();

                // ตรวจสอบสต็อกอีกครั้งแบบล็อกแถวป้องกันการสั่งซื้อพร้อมกันเกินสต็อก
                foreach ($items as $it) {
                    $check = $pdo->prepare('SELECT stock FROM products WHERE id = ? FOR UPDATE');
                    $check->execute([$it['id']]);
                    $current = $check->fetch();
                    if (!$current || $current['stock'] < $it['quantity']) {
                        throw new Exception('สินค้า "' . $it['name'] . '" มีไม่เพียงพอ');
                    }
                }

                $orderStmt = $pdo->prepare('INSERT INTO orders (user_id, total, shipping_address, payment_method, status) VALUES (?, ?, ?, ?, "pending")');
                $orderStmt->execute([$userId, $total, $address, $payment]);
                $orderId = $pdo->lastInsertId();

                $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)');
                $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');

                foreach ($items as $it) {
                    $itemStmt->execute([$orderId, $it['id'], $it['name'], $it['price'], $it['quantity']]);
                    $stockStmt->execute([$it['quantity'], $it['id']]);
                }

                $pdo->prepare('DELETE FROM cart WHERE user_id = ?')->execute([$userId]);

                $pdo->commit();

                set_flash('success', 'สั่งซื้อสำเร็จ! หมายเลขคำสั่งซื้อของคุณคือ #' . $orderId);
                header('Location: order-success.php?id=' . $orderId);
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'ไม่สามารถทำรายการได้: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'ชำระเงิน - MyShop';
require __DIR__ . '/includes/header.php';
?>

<h2 class="section-title">ยืนยันคำสั่งซื้อ</h2>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= clean($err) ?></div>
<?php endforeach; ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">
    <form method="POST">
        <?= csrf_field() ?>
        <div class="form-group">
            <label>ที่อยู่จัดส่ง</label>
            <textarea name="address" class="form-control" rows="4" required><?= clean($_POST['address'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>วิธีการชำระเงิน</label>
            <select name="payment_method" class="form-control">
                <option value="โอนเงินผ่านธนาคาร">โอนเงินผ่านธนาคาร</option>
                <option value="เก็บเงินปลายทาง">เก็บเงินปลายทาง (COD)</option>
                <option value="บัตรเครดิต/เดบิต">บัตรเครดิต/เดบิต</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-block">ยืนยันการสั่งซื้อ</button>
    </form>

    <div class="cart-summary">
        <h3>รายการสินค้า</h3>
        <?php foreach ($items as $it): ?>
            <p style="margin:8px 0;color:#ccc;"><?= clean($it['name']) ?> x<?= (int)$it['quantity'] ?> — <?= format_price($it['price'] * $it['quantity']) ?></p>
        <?php endforeach; ?>
        <hr style="border-color:#2b2b2b;margin:12px 0;">
        <p class="total">ยอดรวม: <?= format_price($total) ?></p>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
