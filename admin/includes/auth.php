<?php
// เรียกไฟล์นี้ในทุกหน้า admin เพื่อบังคับให้ล็อกอินก่อน
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: ../public/login.php');
    exit;
}

if ($_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    die("สิทธิ์การเข้าใช้งานไม่เพียงพอ");
}
