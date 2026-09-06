<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();
if (is_logged_in()) { header('Location: index.php'); exit; }

$errors = [];
$MAX_ATTEMPTS = 5;
$LOCK_MINUTES = 10;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
    } else {
        $login_id = trim($_POST['login_id'] ?? ''); // username หรือ email
        $password = $_POST['password'] ?? '';

        if ($login_id === '' || $password === '') {
            $errors[] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        } else {
            $pdo = getDB();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->execute([$login_id, $login_id]);
            $user = $stmt->fetch();

            if (!$user) {
                $errors[] = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            } elseif ($user['status'] === 'banned') {
                $errors[] = 'บัญชีนี้ถูกระงับการใช้งาน';
            } elseif ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $errors[] = 'บัญชีถูกล็อกชั่วคราวเนื่องจากลองเข้าสู่ระบบผิดหลายครั้ง กรุณาลองใหม่ภายหลัง';
            } elseif (!password_verify($password, $user['password'])) {
                // ----- ป้องกัน Brute Force: นับจำนวนครั้งที่ผิด และล็อกบัญชีชั่วคราว -----
                $attempts = $user['login_attempts'] + 1;
                if ($attempts >= $MAX_ATTEMPTS) {
                    $lockUntil = date('Y-m-d H:i:s', time() + $LOCK_MINUTES * 60);
                    $upd = $pdo->prepare('UPDATE users SET login_attempts = 0, locked_until = ? WHERE id = ?');
                    $upd->execute([$lockUntil, $user['id']]);
                    $errors[] = "กรอกรหัสผ่านผิดครบ {$MAX_ATTEMPTS} ครั้ง บัญชีถูกล็อก {$LOCK_MINUTES} นาที";
                } else {
                    $upd = $pdo->prepare('UPDATE users SET login_attempts = ? WHERE id = ?');
                    $upd->execute([$attempts, $user['id']]);
                    $errors[] = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                }
            } else {
                // ----- เข้าสู่ระบบสำเร็จ -----
                $upd = $pdo->prepare('UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?');
                $upd->execute([$user['id']]);

                session_regenerate_id(true); // ป้องกัน Session Fixation
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                set_flash('success', 'เข้าสู่ระบบสำเร็จ ยินดีต้อนรับ ' . $user['username']);
                header('Location: ' . ($user['role'] === 'admin' ? 'admin/index.php' : 'index.php'));
                exit;
            }
        }
    }
}

$page_title = 'เข้าสู่ระบบ - MyShop';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
    <h2>เข้าสู่ระบบ</h2>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= clean($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
            <label>ชื่อผู้ใช้ หรือ อีเมล</label>
            <input type="text" name="login_id" class="form-control" required autofocus>
        </div>
        <div class="form-group">
            <label>รหัสผ่าน</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">เข้าสู่ระบบ</button>
    </form>

    <div class="auth-switch">ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></div>
    <p style="text-align:center;color:#666;font-size:.8rem;margin-top:14px;">
        บัญชีตัวอย่างแอดมิน: admin / password
    </p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
