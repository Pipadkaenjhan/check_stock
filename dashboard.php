<?php
session_start();

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    // ถ้าไม่ได้ล็อกอิน ให้เปลี่ยนเส้นทางไปยังหน้าล็อกอิน
    header('Location: login_old.php');
    exit;
}

// ดึงข้อมูลผู้ใช้จาก session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// เปลี่ยนเส้นทางไปยังหน้าหลัก
header('Location: index.php');
exit;
?>