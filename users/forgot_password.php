<?php
session_start();
include 'db_connection.php'; // เชื่อมต่อฐานข้อมูล

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone_number = htmlspecialchars(trim($_POST['phone_number']));

    if (empty($phone_number)) {
        $error_message = "กรุณากรอกหมายเลขโทรศัพท์";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE contact_number = ?");
        $stmt->bind_param("s", $phone_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // สร้างโทเค็นสำหรับการรีเซ็ตรหัสผ่าน
            $token = bin2hex(random_bytes(32));
            $reset_link = "https://example.com/reset_password.php?token=" . $token;

            // บันทึกโทเค็นลงในฐานข้อมูล (ตัวอย่างตาราง token)
            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("ss", $user['user_id'], $token);
            $stmt->execute();

            // ส่ง SMS หรืออีเมลไปยังหมายเลขโทรศัพท์เพื่อรีเซ็ตรหัสผ่าน
            // สำหรับตัวอย่างนี้จะใช้เป็นอีเมลเพื่อความง่าย
            $to = $user['email']; // ต้องมีฟิลด์ email ในฐานข้อมูลเพื่อให้ส่งอีเมลได้
            $subject = "Reset your password";
            $message = "Please click the following link to reset your password: " . $reset_link;
            $headers = "From: no-reply@example.com";

            if (mail($to, $subject, $message, $headers)) {
                $success_message = "อีเมลรีเซ็ตรหัสผ่านถูกส่งไปยังที่อยู่อีเมลของคุณแล้ว";
            } else {
                $error_message = "เกิดข้อผิดพลาดในการส่งอีเมล";
            }
        } else {
            $error_message = "ไม่พบหมายเลขโทรศัพท์นี้ในระบบ";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }

        .forgot-password-container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 400px;
        }

        h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 30px;
            border: 1px solid #ccc;
            background-color: #eaf4fb;
            font-size: 16px;
        }

        input[type="text"]:focus {
            border: 1px solid #007BFF;
            outline: none;
        }

        .submit-btn {
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

        .submit-btn:hover {
            background-color: #004f00;
        }

        .error-message,
        .success-message {
            font-size: 14px;
            margin-bottom: 15px;
        }

        .error-message {
            color: red;
        }

        .success-message {
            color: green;
        }

        .forgot-password-container input::placeholder {
            color: #a1a1a1;
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="forgot-password-container">
        <h2>ลืมรหัสผ่าน</h2>

        <!-- Error or Success Message -->
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php elseif (isset($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="forgot_password.php">
            <input type="text" name="phone_number" placeholder="กรุณากรอกหมายเลขโทรศัพท์" required>
            <button type="submit" class="submit-btn">รีเซ็ตรหัสผ่าน</button>
        </form>
    </div>
</body>

</html>
