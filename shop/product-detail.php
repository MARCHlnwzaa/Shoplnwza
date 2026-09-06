<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.is_active = 1');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit;
}

$page_title = clean($product['name']) . ' - MyShop';
require __DIR__ . '/includes/header.php';
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:36px;margin:30px 0;">
    <div>
        <img src="assets/img/<?= clean($product['image']) ?>" onerror="this.src='assets/img/no-image.png'"
             style="width:100%;border-radius:12px;border:1px solid #2b2b2b;">
    </div>
    <div>
        <p style="color:#e63946;font-weight:600;"><?= clean($product['category_name'] ?? 'ไม่ระบุหมวดหมู่') ?></p>
        <h1 style="color:#fff;margin:10px 0;"><?= clean($product['name']) ?></h1>
        <p class="price" style="font-size:1.8rem;"><?= format_price($product['price']) ?></p>
        <p style="color:#999;margin:16px 0;"><?= nl2br(clean($product['description'])) ?></p>
        <p class="stock-tag"><?= $product['stock'] > 0 ? 'คงเหลือ ' . (int)$product['stock'] . ' ชิ้น' : 'สินค้าหมด' ?></p>

        <?php if (is_logged_in()): ?>
            <form class="add-to-cart-form" method="POST" action="cart-action.php" style="max-width:260px;margin-top:20px;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                <div class="form-group">
                    <label>จำนวน</label>
                    <input type="number" name="quantity" value="1" min="1" max="<?= (int)$product['stock'] ?>" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary btn-block" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                    เพิ่มลงตะกร้า
                </button>
            </form>
        <?php else: ?>
            <a href="login.php" class="btn btn-outline" style="margin-top:20px;">เข้าสู่ระบบเพื่อสั่งซื้อ</a>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
