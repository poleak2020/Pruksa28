<?php
session_start();
include 'db_connection.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่ามีโทเค็นที่ถูกส่งมาหรือไม่
if (isset($_GET['token'])) {
    $token = htmlspecialchars($_GET['token']);

    // ตรวจสอบว่าโทเค็นถูกต้องหรือไม่
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $reset = $result->fetch_assoc();
        $user_id = $reset['user_id'];

        // ตรวจสอบว่าผู้ใช้ส่งฟอร์มรีเซ็ตรหัสผ่านหรือไม่
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $new_password = htmlspecialchars($_POST['new_password']);
            $confirm_password = htmlspecialchars($_POST['confirm_password']);

            // ตรวจสอบว่ารหัสผ่านใหม่ตรงกับการยืนยันรหัสผ่าน
            if ($new_password === $confirm_password) {
                // เข้ารหัสรหัสผ่านใหม่
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // อัปเดตรหัสผ่านในตาราง users
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->bind_param("ss", $hashed_password, $user_id);
                if ($stmt->execute()) {
                    // ลบโทเค็นออกจากตาราง password_resets หลังจากรีเซ็ตรหัสผ่านสำเร็จ
                    $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                    $stmt->bind_param("s", $token);
                    $stmt->execute();

                    $_SESSION['message'] = "รีเซ็ตรหัสผ่านสำเร็จ! คุณสามารถเข้าสู่ระบบได้แล้ว";
                    header("Location: login.php");
                    exit();
                } else {
                    $error_message = "เกิดข้อผิดพลาดในการรีเซ็ตรหัสผ่าน";
                }
            } else {
                $error_message = "รหัสผ่านไม่ตรงกัน";
            }
        }
    } else {
        $error_message = "โทเค็นไม่ถูกต้องหรือหมดอายุ";
    }
} else {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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

        .reset-container {
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

        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 30px;
            border: 1px solid #ccc;
            background-color: #eaf4fb;
            font-size: 16px;
            box-sizing: border-box;
        }

        input[type="password"]:focus {
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

        .error-message {
            color: red;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .success-message {
            color: green;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .reset-container input::placeholder {
            color: #a1a1a1;
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="reset-container">
        <h2>Reset Password</h2>

        <!-- Error or Success Message -->
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="password" name="new_password" placeholder="Enter your new password" required>
            <input type="password" name="confirm_password" placeholder="Confirm your new password" required>
            <button type="submit" class="submit-btn">Reset Password</button>
        </form>
    </div>
</body>

</html>
