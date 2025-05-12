<?php
session_start();

// ทำการลบ session
session_destroy();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กำลังออกจากระบบ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .logout-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
        }

        h1 {
            font-size: 24px;
            color: #333;
        }

        p {
            font-size: 16px;
            color: #555;
        }

        .redirect-message {
            font-size: 14px;
            color: #888;
            margin-top: 10px;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            margin-top: 20px;
        }

        a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }

    </style>
</head>
<body>

    <div class="logout-container">
        <h1>กำลังออกจากระบบ...</h1>
        <p>ขอบคุณที่ใช้บริการ</p>
        <p class="redirect-message">ระบบจะเปลี่ยนเส้นทางไปยังหน้าเข้าสู่ระบบในไม่ช้า</p>
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <?php
    // หน่วงเวลา 3 วินาทีเพื่อให้ผู้ใช้เห็นข้อความ
    header("Refresh: 2; url=../users/user_login.php");
    ?>

</body>
</html>
