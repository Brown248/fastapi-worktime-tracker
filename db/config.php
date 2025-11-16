<?php
// แก้ค่า DB ให้ตรงกับของ Awardspace ของคุณ
// อย่าเก็บรหัสผ่านลงใน repo จริง ควรใช้ environment variables บน host

$DB_HOST = 'fdb1034.awardspace.net';   // <-- เปลี่ยนเป็น host ของคุณ
$DB_NAME = '4705518_aie313';             // <-- เปลี่ยนเป็นชื่อฐานข้อมูลของคุณ
$DB_USER = '4705518_aie313';             // <-- เปลี่ยนเป็นชื่อผู้ใช้ฐานข้อมูลของคุณ
$DB_PASS = 'You254809';         // <-- เปลี่ยนเป็นรหัสผ่านฐานข้อมูลของคุณ

try {
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, 
        PDO::ATTR_EMULATE_PREPARES => false,            
    ];
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    error_log("[DB_CONNECT_ERR] " . $e->getMessage());
    die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล โปรดติดต่อผู้ดูแลระบบ");
}
