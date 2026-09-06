<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();
$pdo = getDB();

// รับค่าหมวดหมู่ที่เลือก (กรองด้วย intval ป้องกัน SQL injection)
$cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$search = trim($_GET['q'] ?? '');

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$sql = 'SELECT * FROM products WHERE is_active = 1';
$params = [];
if ($cat > 0) {
    $sql .= ' AND category_id = ?';
    $params[] = $cat;
}
if ($search !== '') {
    $sql .= ' AND name LIKE ?';
    $params[] = '%' . $search . '%';
}
$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$page_title = '9อั๋นแฟมิลี่';
require __DIR__ . '/includes/header.php';
?>

<div class="hero">
    <h1>ช้อปสไตล์คุณ ที่ <span>MYSHOP</span></h1>
    <p>สินค้าคุณภาพ ดีไซน์ทันสมัย จัดส่งรวดเร็ว ปลอดภัยทุกการสั่งซื้อ</p>
    <form method="GET" style="max-width:420px;margin:0 auto;display:flex;gap:8px;">
        <input type="text" name="q" class="form-control" placeholder="ค้นหาสินค้า..." value="<?= clean($search) ?>">
        <button class="btn btn-primary">ค้นหา</button>
    </form>
</div>

<h2 class="section-title">หมวดหมู่สินค้า</h2>
<div class="chip-row">
    <a href="index.php" class="chip <?= $cat === 0 ? 'active' : '' ?>">ทั้งหมด</a>
    <?php foreach ($categories as $c): ?>
        <a href="index.php?cat=<?= (int)$c['id'] ?>" class="chip <?= $cat === (int)$c['id'] ? 'active' : '' ?>">
            <?= clean($c['name']) ?>
        </a>
    <?php endforeach; ?>
</div>

<h2 class="section-title">สินค้าแนะนำ</h2>
<div class="product-grid">
    <?php if (empty($products)): ?>
        <p style="color:#888;">ไม่พบสินค้าที่ค้นหา</p>
    <?php endif; ?>
    <?php foreach ($products as $p): ?>
        <div class="product-card">
            <a href="product-detail.php?id=<?= (int)$p['id'] ?>">
                <img src="assets/img/<?= clean($p['image']) ?>" onerror="this.src='assets/img/no-image.png'" class="product-img" alt="<?= clean($p['name']) ?>">
            </a>
            <div class="product-info">
                <a href="product-detail.php?id=<?= (int)$p['id'] ?>"><h3><?= clean($p['name']) ?></h3></a>
                <div class="price"><?= format_price($p['price']) ?></div>
                <div class="stock-tag"><?= $p['stock'] > 0 ? 'คงเหลือ ' . (int)$p['stock'] . ' ชิ้น' : 'สินค้าหมด' ?></div>

                <?php if (is_logged_in()): ?>
                    <form class="add-to-cart-form" method="POST" action="cart-action.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-sm btn-block" <?= $p['stock'] <= 0 ? 'disabled' : '' ?>>
                            <?= $p['stock'] <= 0 ? 'สินค้าหมด' : 'เพิ่มลงตะกร้า' ?>
                        </button>
                    </form>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline btn-sm btn-block">เข้าสู่ระบบเพื่อสั่งซื้อ</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
