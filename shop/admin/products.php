<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_admin();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete') {
        $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        set_flash('success', 'ลบสินค้าเรียบร้อย');
    } elseif (($_POST['action'] ?? '') === 'toggle') {
        $pdo->prepare('UPDATE products SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
        set_flash('success', 'อัปเดตสถานะสินค้าเรียบร้อย');
    }
    header('Location: products.php');
    exit;
}

$products = $pdo->query('
    SELECT p.*, c.name AS category_name FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
')->fetchAll();

$page_title = 'จัดการสินค้า - Admin MyShop';
require __DIR__ . '/includes/admin_header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;">
    <h2 class="section-title">จัดการสินค้า</h2>
    <a href="product-form.php" class="btn btn-primary">+ เพิ่มสินค้าใหม่</a>
</div>

<table>
    <thead><tr><th>รูป</th><th>ชื่อสินค้า</th><th>หมวดหมู่</th><th>ราคา</th><th>คงเหลือ</th><th>สถานะ</th><th>จัดการ</th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
        <tr>
            <td><img src="../assets/img/<?= clean($p['image']) ?>" onerror="this.src='../assets/img/no-image.png'" style="width:50px;height:50px;object-fit:cover;border-radius:6px;"></td>
            <td><?= clean($p['name']) ?></td>
            <td><?= clean($p['category_name'] ?? '-') ?></td>
            <td><?= format_price($p['price']) ?></td>
            <td><?= (int)$p['stock'] ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="badge badge-<?= $p['is_active'] ? 'completed' : 'cancelled' ?>" style="border:none;cursor:pointer;">
                        <?= $p['is_active'] ? 'เปิดขาย' : 'ปิดขาย' ?>
                    </button>
                </form>
            </td>
            <td>
                <a href="product-form.php?id=<?= (int)$p['id'] ?>" class="btn btn-gray btn-sm">แก้ไข</a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('ยืนยันการลบสินค้านี้?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">ลบ</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
