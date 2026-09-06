<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();
require_login();
$pdo = getDB();
$userId = $_SESSION['user_id'];

// อัปเดตจำนวนจากฟอร์ม (ไม่ใช่ AJAX) เผื่อ JS ปิดอยู่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $productId = (int)($_POST['product_id'] ?? 0);
    if (($_POST['action'] ?? '') === 'update') {
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        $stmt = $pdo->prepare('UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$qty, $userId, $productId]);
    } elseif (($_POST['action'] ?? '') === 'remove') {
        $stmt = $pdo->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$userId, $productId]);
    }
    header('Location: cart.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT c.quantity, p.id, p.name, p.price, p.image, p.stock
    FROM cart c JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
');
$stmt->execute([$userId]);
$items = $stmt->fetchAll();

$total = 0;
foreach ($items as $it) { $total += $it['price'] * $it['quantity']; }

$page_title = 'ตะกร้าสินค้า - MyShop';
require __DIR__ . '/includes/header.php';
?>

<h2 class="section-title">ตะกร้าสินค้าของฉัน</h2>

<?php if (empty($items)): ?>
    <p style="color:#888;">ยังไม่มีสินค้าในตะกร้า — <a href="index.php" style="color:#e63946;">เลือกซื้อสินค้า</a></p>
<?php else: ?>
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">
        <div>
            <?php foreach ($items as $it): ?>
                <div class="cart-item">
                    <img src="assets/img/<?= clean($it['image']) ?>" onerror="this.src='assets/img/no-image.png'">
                    <div class="info">
                        <h3 style="color:#fff;"><?= clean($it['name']) ?></h3>
                        <p class="price"><?= format_price($it['price']) ?></p>
                    </div>
                    <form class="qty-form" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="product_id" value="<?= (int)$it['id'] ?>">
                        <button type="button" class="btn btn-gray btn-sm qty-btn" data-action="dec">-</button>
                        <input type="number" name="quantity" value="<?= (int)$it['quantity'] ?>" min="1" max="<?= (int)$it['stock'] ?>" class="form-control qty-input" style="display:inline-block;">
                        <button type="button" class="btn btn-gray btn-sm qty-btn" data-action="inc">+</button>
                    </form>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?= (int)$it['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">ลบ</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-summary">
            <h3>สรุปคำสั่งซื้อ</h3>
            <p style="margin:12px 0;">จำนวนสินค้า: <?= count($items) ?> รายการ</p>
            <p class="total">ยอดรวม: <?= format_price($total) ?></p>
            <a href="checkout.php" class="btn btn-primary btn-block" style="margin-top:16px;">ดำเนินการชำระเงิน</a>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
