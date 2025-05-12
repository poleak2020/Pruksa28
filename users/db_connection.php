<?php
// ข้อมูลการเชื่อมต่อกับฐานข้อมูล
$servername = "localhost";  // ชื่อโฮสต์ของฐานข้อมูล (localhost ในกรณีของเครื่องที่ใช้งานเอง)
$username = "root";         // ชื่อผู้ใช้สำหรับฐานข้อมูล
$password = "";             // รหัสผ่านสำหรับฐานข้อมูล (ทิ้งว่างไว้หากไม่มีรหัสผ่าน)
$dbname = "corporation";    // ชื่อฐานข้อมูลที่ต้องการเชื่อมต่อ

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
}

// ตั้งค่าภาษาไทยเพื่อรองรับการใช้งานภาษาไทยในฐานข้อมูล
$conn->set_charset("utf8");

?>
