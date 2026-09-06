<?php
/**
 * includes/functions.php
 * ฟังก์ชันช่วยเหลือ + ฟังก์ชันด้านความปลอดภัย
 */

require_once __DIR__ . '/../config/database.php';

// เริ่ม session อย่างปลอดภัย (เรียกครั้งเดียวในทุกหน้า)
function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,   // ป้องกัน JS อ่าน cookie (ลดความเสี่ยง XSS ขโมย session)
            'samesite' => 'Lax',  // ป้องกัน CSRF บางส่วน
        ]);
        session_start();
    }
}

// ทำความสะอาด input ก่อนแสดงผล ป้องกัน XSS
function clean($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

// สร้าง / ตรวจสอบ CSRF token ป้องกันการปลอมแปลงคำขอข้ามเว็บไซต์
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

// ตรวจสอบว่าล็อกอินอยู่หรือไม่
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// ตรวจสอบสิทธิ์แอดมิน
function is_admin() {
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

// บังคับให้ล็อกอินก่อนเข้าหน้านี้
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

// บังคับสิทธิ์แอดมิน
function require_admin() {
    if (!is_admin()) {
        header('Location: ../login.php');
        exit;
    }
}

// แจ้งเตือนแบบ flash message (แสดงครั้งเดียวแล้วหาย)
function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// จัดรูปแบบราคาเป็นสกุลเงินบาท
function format_price($price) {
    return number_format((float)$price, 2) . ' บาท';
}

// นับจำนวนสินค้าในตะกร้าของผู้ใช้ปัจจุบัน
function cart_count() {
    if (!is_logged_in()) return 0;
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) AS total FROM cart WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetch()['total'];
}
