<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_admin();
$pdo = getDB();

$validStatus = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (in_array($status, $validStatus, true)) {
        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $orderId]);
        set_flash('success', 'อัปเดตสถานะคำสั่งซื้อ #' . $orderId . ' เรียบร้อย');
    }
    header('Location: orders.php');
    exit;
}

$orders = $pdo->query('
    SELECT o.*, u.username, u.email
    FROM orders o JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
')->fetchAll();

$page_title = 'จัดการคำสั่งซื้อ - Admin MyShop';
require __DIR__ . '/includes/admin_header.php';
?>

<h2 class="section-title">คำสั่งซื้อทั้งหมด</h2>

<table>
    <thead><tr><th>หมายเลข</th><th>ลูกค้า</th><th>ยอดรวม</th><th>วันที่</th><th>สถานะ</th><th>อัปเดต</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
        <tr>
            <td>#<?= (int)$o['id'] ?></td>
            <td><?= clean($o['username']) ?><br><small style="color:#888;"><?= clean($o['email']) ?></small></td>
            <td><?= format_price($o['total']) ?></td>
            <td><?= clean(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
            <td><span class="badge badge-<?= clean($o['status']) ?>"><?= clean($o['status']) ?></span></td>
            <td>
                <form method="POST" style="display:flex;gap:6px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                    <select name="status" class="form-control" style="padding:6px;">
                        <?php foreach ($validStatus as $s): ?>
                            <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">บันทึก</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
