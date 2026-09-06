<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();
if (is_logged_in()) { header('Location: index.php'); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');

        // ----- ตรวจสอบข้อมูลฝั่งเซิร์ฟเวอร์ (สำคัญที่สุด) -----
        if ($username === '' || !preg_match('/^[a-zA-Z0-9_]{4,30}$/', $username)) {
            $errors[] = 'ชื่อผู้ใช้ต้องเป็นตัวอักษร/ตัวเลข 4-30 ตัวอักษร';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'อีเมลไม่ถูกต้อง';
        }
        if (strlen($password) < 8) {
            $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
        }
        if ($password !== $confirm) {
            $errors[] = 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน';
        }

        if (empty($errors)) {
            $pdo = getDB();
            // ตรวจสอบว่ามี username/email ซ้ำหรือไม่ (ใช้ prepared statement ป้องกัน SQL Injection)
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = 'ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้งานแล้ว';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare(
                    'INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, "customer")'
                );
                $stmt->execute([$username, $email, $hash, $full_name]);
                set_flash('success', 'สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ');
                header('Location: login.php');
                exit;
            }
        }
    }
}

$page_title = 'สมัครสมาชิก - MyShop';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
    <h2>สมัครสมาชิก</h2>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= clean($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" id="register-form" novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
            <label>ชื่อ-นามสกุล</label>
            <input type="text" name="full_name" class="form-control" value="<?= clean($_POST['full_name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>ชื่อผู้ใช้ (username)</label>
            <input type="text" name="username" class="form-control" required value="<?= clean($_POST['username'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>อีเมล</label>
            <input type="email" name="email" class="form-control" required value="<?= clean($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)</label>
            <input type="password" name="password" class="form-control" required minlength="8">
        </div>
        <div class="form-group">
            <label>ยืนยันรหัสผ่าน</label>
            <input type="password" name="confirm_password" class="form-control" required minlength="8">
        </div>
        <button type="submit" class="btn btn-primary btn-block">สมัครสมาชิก</button>
    </form>

    <div class="auth-switch">มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a></div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
