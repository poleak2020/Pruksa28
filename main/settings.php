<?php
session_start();

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../users/user_login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']); // แปลงเป็น integer เพื่อตรวจสอบความถูกต้อง
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การตั้งค่า</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background-color: #ffffff;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
            position: relative;
        }

        .header h4 {
            margin: 0;
            font-size: 18px;
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

        .container {
            flex: 1;
            padding: 20px;
        }

        .settings-item {
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .settings-item:hover {
            box-shadow: 0px 8px 15px rgba(0, 0, 0, 0.2);
        }

        .settings-item i {
            font-size: 24px;
            color: #007BFF;
        }

        .settings-item .text {
            font-size: 16px;
            font-weight: 500;
            flex: 1;
        }

        .settings-item .arrow {
            color: #888;
            font-size: 20px;
        }

        .footer-menu {
            background-color: #e9ecef;
            border-top: 1px solid #ccc;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
        }

        .footer-menu a {
            text-align: center;
            color: #333;
            font-size: 14px;
            text-decoration: none;
        }

        .footer-menu a i {
            display: block;
            font-size: 20px;
        }

        .footer-menu a.active {
            color: #7BC59D;
        }

        .alert-danger {
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <!-- ส่วนหัว -->
    <header class="header">
        <a href="../main/index.php" class="back-button" aria-label="กลับ"><i class="bi bi-arrow-left"></i></a>
        <h4 class="mb-0">การตั้งค่า</h4>
    </header>

    <!-- เนื้อหา -->
    <main class="container">
        <a href="../settings/change_password.php" class="settings-item">
            <span class="text">เปลี่ยนรหัสผ่าน</span>
            <i class="bi bi-lock"></i>
            <span class="arrow"><i class="bi bi-chevron-right"></i></span>
        </a>

        <a href="../settings/update_profile.php" class="settings-item">
            <span class="text">อัปเดตข้อมูลส่วนตัว</span>
            <i class="bi bi-person"></i>
            <span class="arrow"><i class="bi bi-chevron-right"></i></span>
        </a>

        <a href="../settings/privacy_settings.php" class="settings-item">
            <span class="text">การตั้งค่าความเป็นส่วนตัว</span>
            <i class="bi bi-shield-lock"></i>
            <span class="arrow"><i class="bi bi-chevron-right"></i></span>
        </a>

        <a href="../settings/logout.php" class="settings-item">
            <span class="text">ออกจากระบบ</span>
            <i class="bi bi-box-arrow-right"></i>
            <span class="arrow"><i class="bi bi-chevron-right"></i></span>
        </a>
    </main>

    <!-- Footer Menu -->
    <?php
    // กำหนดเส้นทางแบบสัมพัทธ์อย่างปลอดภัย
    $footerMenuPath = realpath(dirname(__FILE__) . '/../includes/footer_menu.php');
    $includesPath = realpath(dirname(__FILE__) . '/../includes');
    if ($footerMenuPath && strpos($footerMenuPath, $includesPath) === 0) {
        require_once $footerMenuPath;
    } else {
        // บันทึก error ไปยังไฟล์ log และแสดงข้อความแจ้งเตือนบนหน้า
        error_log("ไม่สามารถโหลด Footer Menu ได้: $footerMenuPath", 3, dirname(__FILE__) . '/../logs/error.log');
        echo "<div class='alert alert-danger'>ไม่สามารถโหลด Footer Menu ได้</div>";
    }
    ?>

</body>

</html>
