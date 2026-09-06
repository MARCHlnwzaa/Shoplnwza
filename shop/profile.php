<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();
require_login();
$pdo = getDB();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'update_info') {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $address   = trim($_POST['address'] ?? '');
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?');
        $stmt->execute([$full_name, $phone, $address, $userId]);
        set_flash('success', 'บันทึกข้อมูลสำเร็จ');
        header('Location: profile.php');
        exit;

    } elseif ($formType === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            $errors[] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
        } elseif (strlen($new) < 8) {
            $errors[] = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร';
        } elseif ($new !== $confirm) {
            $errors[] = 'รหัสผ่านใหม่ไม่ตรงกัน';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $userId]);
            set_flash('success', 'เปลี่ยนรหัสผ่านสำเร็จ');
            header('Location: profile.php');
            exit;
        }
    }
}

$orderStmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$orderStmt->execute([$userId]);
$orders = $orderStmt->fetchAll();

$page_title = 'บัญชีของฉัน - MyShop';
require __DIR__ . '/includes/header.php';
?>

<h2 class="section-title">บัญชีของฉัน</h2>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= clean($err) ?></div>
<?php endforeach; ?>

<!-- ระบบสลับแท็บ: ข้อมูลส่วนตัว / ประวัติคำสั่งซื้อ / เปลี่ยนรหัสผ่าน -->
<div class="tabs">
    <button class="tab-btn active" data-tab="tab-info">ข้อมูลส่วนตัว</button>
    <button class="tab-btn" data-tab="tab-orders">ประวัติคำสั่งซื้อ</button>
    <button class="tab-btn" data-tab="tab-password">เปลี่ยนรหัสผ่าน</button>
</div>

<div class="tab-panels">
    <div class="tab-panel active" id="tab-info">
        <form method="POST" style="max-width:500px;">
            <?= csrf_field() ?>
            <input type="hidden" name="form_type" value="update_info">
            <div class="form-group">
                <label>ชื่อผู้ใช้</label>
                <input type="text" class="form-control" value="<?= clean($user['username']) ?>" disabled>
            </div>
            <div class="form-group">
                <label>อีเมล</label>
                <input type="text" class="form-control" value="<?= clean($user['email']) ?>" disabled>
            </div>
            <div class="form-group">
                <label>ชื่อ-นามสกุล</label>
                <input type="text" name="full_name" class="form-control" value="<?= clean($user['full_name']) ?>">
            </div>
            <div class="form-group">
                <label>เบอร์โทรศัพท์</label>
                <input type="text" name="phone" class="form-control" value="<?= clean($user['phone']) ?>">
            </div>
            <div class="form-group">
                <label>ที่อยู่</label>
                <textarea name="address" class="form-control" rows="3"><?= clean($user['address']) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
        </form>
    </div>

    <div class="tab-panel" id="tab-orders">
        <?php if (empty($orders)): ?>
            <p style="color:#888;">ยังไม่มีประวัติคำสั่งซื้อ</p>
        <?php else: ?>
            <table>
                <thead><tr><th>หมายเลข</th><th>วันที่</th><th>ยอดรวม</th><th>สถานะ</th></tr></thead>
                <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td>#<?= (int)$o['id'] ?></td>
                        <td><?= clean(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
                        <td><?= format_price($o['total']) ?></td>
                        <td><span class="badge badge-<?= clean($o['status']) ?>"><?= clean($o['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="tab-panel" id="tab-password">
        <form method="POST" style="max-width:420px;">
            <?= csrf_field() ?>
            <input type="hidden" name="form_type" value="change_password">
            <div class="form-group">
                <label>รหัสผ่านปัจจุบัน</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>รหัสผ่านใหม่</label>
                <input type="password" name="new_password" class="form-control" required minlength="8">
            </div>
            <div class="form-group">
                <label>ยืนยันรหัสผ่านใหม่</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="8">
            </div>
            <button type="submit" class="btn btn-primary">เปลี่ยนรหัสผ่าน</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
