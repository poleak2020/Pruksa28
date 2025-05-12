<?php
session_start();
include '../users/db_connection.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: ../users/user_login.php");
    exit();
}

// ตรวจสอบว่ามีการส่งค่า id มาหรือไม่
if (isset($_GET['id'])) {
    $problem_id = $_GET['id'];

    // สร้างคำสั่ง SQL สำหรับลบข้อมูลจากฐานข้อมูล
    $sql = "DELETE FROM problems WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("การเตรียมคำสั่ง SQL ล้มเหลว: " . $conn->error);
    }

    $stmt->bind_param("i", $problem_id); // ผูกค่า id เข้ากับคำสั่ง SQL
    
    // ตรวจสอบการดำเนินการ
    if ($stmt->execute()) {
        // ลบข้อมูลสำเร็จ
        header("Location: report_issue.php?status=pending"); // กลับไปยังหน้ารายการแจ้งปัญหา
        exit();
    } else {
        echo "เกิดข้อผิดพลาดในการลบข้อมูล: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "ไม่พบข้อมูล id สำหรับลบ";
}

$conn->close();
?>
