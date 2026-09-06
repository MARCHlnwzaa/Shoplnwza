<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_admin();
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
$product = ['id' => 0, 'name' => '', 'description' => '', 'price' => '', 'stock' => '', 'category_id' => '', 'image' => 'no-image.png'];
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) $product = $found;
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'คำขอไม่ถูกต้อง กรุณาลองใหม่';
    } else {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $category_id = (int)($_POST['category_id'] ?? 0) ?: null;
        $imageName = $product['image'];

        if ($name === '') $errors[] = 'กรุณากรอกชื่อสินค้า';
        if ($price < 0) $errors[] = 'ราคาต้องไม่ติดลบ';

        // ----- อัปโหลดรูปภาพอย่างปลอดภัย -----
        if (!empty($_FILES['image']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $errors[] = 'รองรับเฉพาะไฟล์ภาพ jpg, jpeg, png, webp เท่านั้น';
            } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
                $errors[] = 'ขนาดไฟล์ต้องไม่เกิน 3MB';
            } else {
                // สุ่มชื่อไฟล์ใหม่ ป้องกันการเขียนทับ / ป้องกันการอัปโหลดไฟล์อันตราย
                $imageName = bin2hex(random_bytes(8)) . '.' . $ext;
                $destDir = __DIR__ . '/../assets/img/';
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $destDir . $imageName)) {
                    $errors[] = 'อัปโหลดรูปภาพไม่สำเร็จ';
                }
            }
        }

        if (empty($errors)) {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE products SET name=?, description=?, price=?, stock=?, category_id=?, image=? WHERE id=?');
                $stmt->execute([$name, $description, $price, $stock, $category_id, $imageName, $id]);
                set_flash('success', 'แก้ไขสินค้าเรียบร้อย');
            } else {
                $stmt = $pdo->prepare('INSERT INTO products (name, description, price, stock, category_id, image) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $description, $price, $stock, $category_id, $imageName]);
                set_flash('success', 'เพิ่มสินค้าใหม่เรียบร้อย');
            }
            header('Location: products.php');
            exit;
        }
    }
}

$page_title = ($id > 0 ? 'แก้ไขสินค้า' : 'เพิ่มสินค้า') . ' - Admin MyShop';
require __DIR__ . '/includes/admin_header.php';
?>

<h2 class="section-title"><?= $id > 0 ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่' ?></h2>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= clean($err) ?></div>
<?php endforeach; ?>

<form method="POST" enctype="multipart/form-data" style="max-width:520px;">
    <?= csrf_field() ?>
    <div class="form-group">
        <label>ชื่อสินค้า</label>
        <input type="text" name="name" class="form-control" required value="<?= clean($product['name']) ?>">
    </div>
    <div class="form-group">
        <label>รายละเอียด</label>
        <textarea name="description" class="form-control" rows="4"><?= clean($product['description']) ?></textarea>
    </div>
    <div class="form-group">
        <label>หมวดหมู่</label>
        <select name="category_id" class="form-control">
            <option value="">-- เลือกหมวดหมู่ --</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)($product['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                    <?= clean($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>ราคา (บาท)</label>
        <input type="number" step="0.01" min="0" name="price" class="form-control" required value="<?= clean($product['price']) ?>">
    </div>
    <div class="form-group">
        <label>จำนวนคงเหลือ</label>
        <input type="number" min="0" name="stock" class="form-control" required value="<?= clean($product['stock']) ?>">
    </div>
    <div class="form-group">
        <label>รูปภาพสินค้า (jpg, png, webp — ไม่เกิน 3MB)</label>
        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
    </div>
    <button type="submit" class="btn btn-primary btn-block"><?= $id > 0 ? 'บันทึกการแก้ไข' : 'เพิ่มสินค้า' ?></button>
</form>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
