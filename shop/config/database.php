<?php
/**
 * config/database.php
 * ไฟล์เชื่อมต่อฐานข้อมูล MySQL ด้วย PDO (สำหรับ XAMPP)
 * แก้ไขค่าตั้งต้นด้านล่างให้ตรงกับเครื่องของคุณ
 */

// ตั้งค่าการเชื่อมต่อฐานข้อมูล
define('DB_HOST', 'localhost');
define('DB_NAME', 'shop_db');
define('DB_USER', 'root');   // ค่าเริ่มต้นของ XAMPP
define('DB_PASS', '');       // ค่าเริ่มต้นของ XAMPP คือค่าว่าง

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // ใช้ prepared statement จริง ป้องกัน SQL Injection
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // ไม่แสดง error ดิบให้ผู้ใช้เห็น (ป้องกันข้อมูลรั่วไหล)
            error_log('DB Connection Error: ' . $e->getMessage());
            die('ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาตรวจสอบการตั้งค่าใน config/database.php');
        }
    }
    return $pdo;
}
