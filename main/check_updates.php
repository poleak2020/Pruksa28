<?php
session_start();
include '../users/db_connection.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่าผู้ใช้ล็อกอินและมี house_number ใน session หรือไม่
if (!isset($_SESSION['user_id']) || !isset($_SESSION['house_number'])) {
    echo json_encode(['hasUpdate' => false]);
    exit();
}

$house_number = filter_var($_SESSION['house_number'], FILTER_SANITIZE_STRING);

// ดึงข้อมูลล่าสุดจากฐานข้อมูล
$sql = "SELECT MAX(performed_at) as last_update FROM actions a 
        JOIN problems p ON a.problem_id = p.id 
        WHERE p.house_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $house_number);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// ตรวจสอบว่ามีการอัปเดตใหม่หรือไม่
$last_update = isset($row['last_update']) ? $row['last_update'] : null;
$hasUpdate = false;

if ($last_update) {
    $current_time = new DateTime();
    $last_update_time = new DateTime($last_update);

    // เช็กว่าเวลาการอัปเดตล่าสุดห่างจากปัจจุบันหรือไม่ (ตัวอย่างเช็กว่าภายใน 1 นาทีที่ผ่านมา)
    if ($current_time->diff($last_update_time)->i < 1) {
        $hasUpdate = true;
    }
}

echo json_encode(['hasUpdate' => $hasUpdate]);
?>
