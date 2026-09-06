<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_admin();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $userId = (int)($_POST['user_id'] ?? 0);
    // ป้องกันแอดมินแบนหรือลดสิทธิ์ตัวเอง
    if ($userId !== (int)$_SESSION['user_id']) {
        if (($_POST['action'] ?? '') === 'toggle_status') {
            $pdo->prepare("UPDATE users SET status = IF(status='active','banned','active') WHERE id = ?")->execute([$userId]);
            set_flash('success', 'อัปเดตสถานะผู้ใช้เรียบร้อย');
        }
    } else {
        set_flash('error', 'ไม่สามารถระงับบัญชีของตนเองได้');
    }
    header('Location: users.php');
    exit;
}

$users = $pdo->query('SELECT id, username, email, full_name, role, status, created_at FROM users ORDER BY created_at DESC')->fetchAll();

$page_title = 'จัดการผู้ใช้ - Admin MyShop';
require __DIR__ . '/includes/admin_header.php';
?>

<h2 class="section-title">สมาชิกทั้งหมด</h2>

<table>
    <thead><tr><th>ชื่อผู้ใช้</th><th>อีเมล</th><th>ชื่อ-นามสกุล</th><th>สิทธิ์</th><th>สถานะ</th><th>สมัครเมื่อ</th><th>จัดการ</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= clean($u['username']) ?></td>
            <td><?= clean($u['email']) ?></td>
            <td><?= clean($u['full_name']) ?></td>
            <td><?= clean($u['role']) ?></td>
            <td><span class="badge badge-<?= $u['status'] === 'active' ? 'completed' : 'cancelled' ?>"><?= clean($u['status']) ?></span></td>
            <td><?= clean(date('d/m/Y', strtotime($u['created_at']))) ?></td>
            <td>
                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                    <form method="POST" onsubmit="return confirm('ยืนยันการเปลี่ยนสถานะผู้ใช้นี้?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <button type="submit" class="btn btn-sm <?= $u['status'] === 'active' ? 'btn-danger' : 'btn-primary' ?>">
                            <?= $u['status'] === 'active' ? 'ระงับบัญชี' : 'ปลดระงับ' ?>
                        </button>
                    </form>
                <?php else: ?>
                    <span style="color:#666;">— บัญชีของคุณ —</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
