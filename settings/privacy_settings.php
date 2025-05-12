<?php
session_start();

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: ../users/user_login.php");
    exit();
}

include '../users/db_connection.php'; // เชื่อมต่อฐานข้อมูล

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าการตั้งค่าจากฟอร์ม
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;

    // อัปเดตการตั้งค่าลงในฐานข้อมูล
    $sql = "UPDATE users SET email_notifications = ?, sms_notifications = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("iii", $email_notifications, $sms_notifications, $user_id);
        if ($stmt->execute()) {
            $success_message = "บันทึกการตั้งค่าเรียบร้อยแล้ว!";
        } else {
            $error_message = "เกิดข้อผิดพลาดในการบันทึกการตั้งค่า.";
        }
        $stmt->close();
    } else {
        $error_message = "ไม่สามารถเตรียมคำสั่ง SQL ได้.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าความเป็นส่วนตัว</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            height: 100vh;
        }

        .header {
            background-color: #ffffff;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
            position: relative;
        }

        .header a {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #000;
            text-decoration: none;
            font-size: 20px;
        }

        .form-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: 40px auto;
        }

        .form-label {
            font-size: 16px;
            color: #555;
            font-weight: 600;
        }

        .form-check-input {
            margin-right: 10px;
        }

        .submit-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
            font-size: 16px;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background-color: #0056b3;
        }

        .success-message,
        .error-message {
            font-size: 14px;
            text-align: center;
            margin-bottom: 20px;
        }

        .success-message {
            color: green;
        }

        .error-message {
            color: red;
        }

        .back-button {
            color: #000;
            text-decoration: none;
            font-size: 24px;
            position: absolute;
            top: 50%;
            left: 20px;
            transform: translateY(-50%);
        }
    </style>
</head>

<body>

    <div class="header">
        <a href="../main/settings.php" class="back-button"><i class="bi bi-arrow-left"></i></a>
        <h4 class="mb-0">ตั้งค่าความเป็นส่วนตัว</h4>
    </div>

    <div class="form-container">
        <h4 class="text-center">ปรับแต่งการแจ้งเตือน</h4>

        <!-- Success Message -->
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="privacy_settings.php">
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="email_notifications" name="email_notifications">
                <label class="form-label" for="email_notifications">เปิดใช้งานการแจ้งเตือนทางอีเมล</label>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="sms_notifications" name="sms_notifications">
                <label class="form-label" for="sms_notifications">เปิดใช้งานการแจ้งเตือนทาง SMS</label>
            </div>

            <button type="submit" class="submit-btn">บันทึกการเปลี่ยนแปลง</button>
        </form>
    </div>

</body>
</html>
