<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();
require_login();
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) { header('Location: index.php'); exit; }

$page_title = 'สั่งซื้อสำเร็จ - MyShop';
require __DIR__ . '/includes/header.php';
?>

<div style="text-align:center;padding:60px 20px;">
    <h1 style="color:#e63946;font-size:2.2rem;">✓ สั่งซื้อสำเร็จ</h1>
    <p style="color:#ccc;margin:16px 0;">ขอบคุณสำหรับการสั่งซื้อ หมายเลขคำสั่งซื้อของคุณคือ <strong>#<?= (int)$order['id'] ?></strong></p>
    <p style="color:#999;">ยอดรวม: <?= format_price($order['total']) ?></p>
    <a href="profile.php" class="btn btn-primary" style="margin-top:20px;">ดูประวัติคำสั่งซื้อ</a>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
