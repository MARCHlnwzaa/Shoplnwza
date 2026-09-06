<?php
/**
 * includes/header.php
 * ต้อง require_once functions.php และเรียก start_secure_session() ก่อน include ไฟล์นี้
 */
$page_title = $page_title ?? 'MyShop';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= clean($page_title) ?></title>
<link rel="stylesheet" href="<?= isset($asset_prefix) ? $asset_prefix : '' ?>assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="navbar-inner">
        <a href="<?= isset($asset_prefix) ? $asset_prefix : '' ?>index.php" class="brand">MY<span>SHOP</span></a>
        <button class="nav-toggle">&#9776;</button>
        <ul class="nav-links">
            <li><a href="<?= isset($asset_prefix) ? $asset_prefix : '' ?>index.php">หน้าแรก</a></li>
            <?php if (is_logged_in()): ?>
                <li><a href="<?= isset($asset_prefix) ? $asset_prefix : '' ?>cart.php">ตะกร้า <span class="cart-badge"><?= cart_count() ?></span></a></li>
                <li><a href="<?= isset($asset_prefix) ? $asset_prefix : '' ?>profile.php">บัญชีของฉัน</a></li>
                <?php if (is_admin()): ?>
                    <li><a href="<?= isset($asset_prefix) ? $asset_prefix : '' ?>admin/index.php">แผงควบคุม</a></li>
                <?php endif; ?>
                <li><a href="<?= isset($asset_prefix) ? $asset_prefix : '' ?>logout.php">ออกจากระบบ (<?= clean($_SESSION['username'] ?? '') ?>)</a></li>
            <?php else: ?>
                <li><a href="<?= isset($asset_prefix) ? $asset_prefix : '' ?>login.php">เข้าสู่ระบบ</a></li>
                <li><a href="<?= isset($asset_prefix) ? $asset_prefix : '' ?>register.php">สมัครสมาชิก</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<div class="container">
    <?php if ($flash): ?>
        <div class="alert alert-<?= clean($flash['type']) ?>"><?= clean($flash['message']) ?></div>
    <?php endif; ?>
