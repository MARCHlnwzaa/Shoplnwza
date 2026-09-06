<?php
// ต้อง require_admin() ก่อน include ไฟล์นี้เสมอ
$page_title = $page_title ?? 'แผงควบคุม - MyShop';
$flash = get_flash();
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= clean($page_title) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-wrap">
    <aside class="admin-sidebar">
        <a href="index.php" class="brand">MY<span style="color:#e63946;">SHOP</span> <small style="font-size:.6rem;color:#888;">ADMIN</small></a>
        <a href="index.php" class="<?= $current === 'index.php' ? 'active' : '' ?>">แดชบอร์ด</a>
        <a href="products.php" class="<?= $current === 'products.php' ? 'active' : '' ?>">จัดการสินค้า</a>
        <a href="orders.php" class="<?= $current === 'orders.php' ? 'active' : '' ?>">จัดการคำสั่งซื้อ</a>
        <a href="users.php" class="<?= $current === 'users.php' ? 'active' : '' ?>">จัดการผู้ใช้</a>
        <a href="../index.php">← กลับสู่หน้าเว็บไซต์</a>
        <a href="../logout.php">ออกจากระบบ</a>
    </aside>
    <main class="admin-content">
        <?php if ($flash): ?>
            <div class="alert alert-<?= clean($flash['type']) ?>"><?= clean($flash['message']) ?></div>
        <?php endif; ?>
