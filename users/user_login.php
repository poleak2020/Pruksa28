<?php
session_start();
include '../users/db_connection.php'; 

$error_message = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = htmlspecialchars(trim($_POST['user_id']));
    $password = htmlspecialchars(trim($_POST['password']));

    if (empty($user_id) || empty($password)) {
        $error_message = "กรุณากรอกชื่อผู้ใช้และรหัสผ่านให้ครบถ้วน";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['house_number'] = $user['house_number']; 
                header("Location: ../main/index.php");
                exit();
            } else {
                $error_message = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
            }
        } else {
            $error_message = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        }
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
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

        .login-container {
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

        input[type="text"],
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

        input[type="text"]:focus,
        input[type="password"]:focus {
            border: 1px solid #007BFF;
            outline: none;
        }

        .login-btn {
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

        .login-btn:hover {
            background-color: #004f00;
        }

        .forgot-password-link,
        .signup-link {
            display: block;
            margin: 10px 0;
            text-decoration: none;
            color: #007BFF;
            font-size: 14px;
            transition: color 0.2s ease;
        }

        .forgot-password-link:hover,
        .signup-link:hover {
            color: #0056b3;
        }

        .error-message {
            color: red;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .login-container input::placeholder {
            color: #a1a1a1;
            font-style: italic;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        form {
            width: 100%;
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 20px;
                max-width: 90%;
            }

            input[type="text"],
            input[type="password"] {
                padding: 10px;
            }

            .login-btn {
                padding: 12px;
                font-size: 14px;
            }

            h2 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h2>เข้าสู่ระบบ</h2>

        <!-- Error message -->
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="user_login.php" class="login-form">
            <input type="text" name="user_id" placeholder="ชื่อผู้ใช้" required>
            <input type="password" name="password" placeholder="รหัสผ่าน" required>
            <button type="submit" class="login-btn">เข้าสู่ระบบ</button>
        </form>

        <!-- Forgot Password and Sign Up links -->
        <a href="forgot_password.php" class="forgot-password-link">ลืมรหัสผ่าน?</a>
        <a href="signup.php" class="signup-link">สร้างบัญชีใหม่</a>
    </div>
</body>

</html>
