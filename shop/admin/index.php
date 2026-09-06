<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_admin();
$pdo = getDB();

$totalProducts = $pdo->query('SELECT COUNT(*) c FROM products')->fetch()['c'];
$totalUsers    = $pdo->query('SELECT COUNT(*) c FROM users WHERE role = "customer"')->fetch()['c'];
$totalOrders   = $pdo->query('SELECT COUNT(*) c FROM orders')->fetch()['c'];
$totalRevenue  = $pdo->query('SELECT COALESCE(SUM(total),0) s FROM orders WHERE status != "cancelled"')->fetch()['s'];

$recentOrders = $pdo->query('
    SELECT o.id, o.total, o.status, o.created_at, u.username
    FROM orders o JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC LIMIT 8
')->fetchAll();

$page_title = 'แดชบอร์ด - Admin MyShop';
require __DIR__ . '/includes/admin_header.php';
?>

<h2 class="section-title">ภาพรวมระบบ</h2>

<div class="stat-grid">
    <div class="stat-card"><div class="num"><?= (int)$totalProducts ?></div><div class="label">สินค้าทั้งหมด</div></div>
    <div class="stat-card"><div class="num"><?= (int)$totalUsers ?></div><div class="label">สมาชิกทั้งหมด</div></div>
    <div class="stat-card"><div class="num"><?= (int)$totalOrders ?></div><div class="label">คำสั่งซื้อทั้งหมด</div></div>
    <div class="stat-card"><div class="num"><?= format_price($totalRevenue) ?></div><div class="label">ยอดขายรวม</div></div>
</div>

<h2 class="section-title">คำสั่งซื้อล่าสุด</h2>
<table>
    <thead><tr><th>หมายเลข</th><th>ลูกค้า</th><th>ยอดรวม</th><th>สถานะ</th><th>วันที่</th></tr></thead>
    <tbody>
    <?php foreach ($recentOrders as $o): ?>
        <tr>
            <td>#<?= (int)$o['id'] ?></td>
            <td><?= clean($o['username']) ?></td>
            <td><?= format_price($o['total']) ?></td>
            <td><span class="badge badge-<?= clean($o['status']) ?>"><?= clean($o['status']) ?></span></td>
            <td><?= clean(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
