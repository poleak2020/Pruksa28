<?php
session_start();
include 'db_connection.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่าฟอร์มถูกส่งมาหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับค่าจากฟอร์ม
    $house_number = htmlspecialchars(trim($_POST['house_number']));
    $user_id = htmlspecialchars(trim($_POST['user_id']));
    $password = htmlspecialchars(trim($_POST['password']));
    $confirm_password = htmlspecialchars(trim($_POST['confirm_password']));
    $owner_name = htmlspecialchars(trim($_POST['owner_name']));
    $contact_number = htmlspecialchars(trim($_POST['contact_number']));

    // ตรวจสอบว่าทุกฟิลด์ถูกกรอกหรือไม่
    $errors = [];

    if (empty($house_number)) {
        $errors['house_number'] = "กรุณากรอกเลขที่บ้าน";
    } else {
        // ตรวจสอบว่าเลขที่บ้านซ้ำหรือไม่
        $stmt = $conn->prepare("SELECT * FROM users WHERE house_number = ?");
        $stmt->bind_param("s", $house_number);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors['house_number'] = "เลขที่บ้านนี้มีอยู่ในระบบแล้ว";
        }
        $stmt->close();
    }

    if (empty($user_id)) {
        $errors['user_id'] = "กรุณากรอกชื่อผู้ใช้";
    } else {
        // ตรวจสอบว่ารหัสผู้ใช้มีอยู่แล้วหรือไม่
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors['user_id'] = "รหัสผู้ใช้นี้มีอยู่ในระบบแล้ว";
        }
        $stmt->close();
    }

    if (empty($password)) {
        $errors['password'] = "กรุณากรอกรหัสผ่าน";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
        $errors['password'] = "รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัว และต้องประกอบด้วยตัวพิมพ์ใหญ่ พิมพ์เล็ก ตัวเลข และอักขระพิเศษ";
    }

    if (empty($confirm_password)) {
        $errors['confirm_password'] = "กรุณายืนยันรหัสผ่าน";
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = "รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน";
    }

    if (empty($owner_name)) {
        $errors['owner_name'] = "กรุณากรอกชื่อเจ้าของบ้าน";
    }

    if (empty($contact_number)) {
        $errors['contact_number'] = "กรุณากรอกเบอร์โทรศัพท์";
    } else {
        $clean_contact_number = str_replace("-", "", $contact_number);
        if (!preg_match("/^[0-9]{10}$/", $clean_contact_number)) {
            $errors['contact_number'] = "หมายเลขโทรศัพท์ต้องเป็นตัวเลข 10 หลัก";
        }
    }

    // ตรวจสอบว่ามีข้อผิดพลาดหรือไม่
    if (empty($errors)) {
        // เข้ารหัสรหัสผ่าน
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // บันทึกข้อมูลผู้ใช้ใหม่ลงฐานข้อมูล
        $stmt = $conn->prepare("INSERT INTO users (house_number, user_id, password, owner_name, contact_number) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $house_number, $user_id, $hashed_password, $owner_name, $contact_number);

        if ($stmt->execute()) {
            session_regenerate_id(true); // ป้องกัน Session Fixation
            echo "<script>
                    window.onload = function() {
                        // แสดงข้อความสมัครสำเร็จแบบ popup
                        let modal = document.createElement('div');
                        modal.style.position = 'fixed';
                        modal.style.top = '50%';
                        modal.style.left = '50%';
                        modal.style.transform = 'translate(-50%, -50%)';
                        modal.style.backgroundColor = '#fff';
                        modal.style.padding = '20px';
                        modal.style.borderRadius = '10px';
                        modal.style.boxShadow = '0px 4px 15px rgba(0, 0, 0, 0.1)';
                        modal.style.textAlign = 'center';
                        modal.innerHTML = '<h3>สมัครสำเร็จ!</h3><p>กำลังไปยังหน้าถัดไป...</p>';
                        document.body.appendChild(modal);
        
                        setTimeout(function() {
                            window.location.href = 'user_login.php';
                        }, 1000); // ดีเลย์ 1 วินาที
                    };
                  </script>";
        } else {
            echo "<script>alert('สมัครไม่สำเร็จ: เกิดข้อผิดพลาดในระบบ');</script>";
        }
    } else {
        // แสดงข้อผิดพลาดหากการสมัครไม่สำเร็จ
        echo "<script>alert('สมัครไม่สำเร็จ: " . implode(", ", $errors) . "');</script>";
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก</title>
    <style>
        /* CSS Styles */
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }

        .signup-container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }

        input[type="text"],
        input[type="password"],
        input[type="tel"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 30px;
            border: 1px solid #ccc;
            background-color: #eaf4fb;
            font-size: 16px;
            box-sizing: border-box;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="tel"]:focus {
            border: 1px solid #007BFF;
            outline: none;
        }

        .signup-btn {
            background-color: #006400;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 30px;
            width: 100%;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s ease;
        }

        .signup-btn:hover {
            background-color: #004f00;
        }

        .back-btn {
            background-color: #b0b0b0;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 30px;
            width: 100%;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s ease;
        }

        .back-btn:hover {
            background-color: #8c8c8c;
        }

        .error-message {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }

        form {
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="signup-container">
        <h2>สมัครสมาชิก</h2>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php echo implode('<br>', $errors); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="signup.php">
            <input type="text" name="house_number" placeholder="เลขที่บ้าน" required>
            <input type="text" name="user_id" placeholder="ชื่อผู้ใช้" required>
            <input type="password" name="password" placeholder="รหัสผ่าน" required>
            <input type="password" name="confirm_password" placeholder="ยืนยันรหัสผ่าน" required>
            <input type="text" name="owner_name" placeholder="ชื่อเจ้าของบ้าน" required>
            <input type="tel" name="contact_number" placeholder="เบอร์โทรศัพท์" required oninput="formatPhoneNumber(this)">
            <button type="submit" class="signup-btn">สมัครสมาชิก</button>
        </form>

        <button class="back-btn" onclick="window.location.href='user_login.php'">ย้อนกลับ</button>
    </div>

    <script>
        // ฟังก์ชันจัดรูปแบบเบอร์โทรศัพท์
        function formatPhoneNumber(input) {
            const value = input.value.replace(/\D/g, ''); // ลบตัวอักษรที่ไม่ใช่ตัวเลขทั้งหมดออก
            const length = value.length;

            if (length > 3 && length <= 6) {
                input.value = value.slice(0, 3) + '-' + value.slice(3);
            } else if (length > 6) {
                input.value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 10);
            } else {
                input.value = value;
            }
        }
    </script>
</body>

</html>