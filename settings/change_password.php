<?php
session_start();

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: ../users/user_login.php");
    exit();
}

require_once '../users/db_connection.php'; // เชื่อมต่อฐานข้อมูล

// ฟังก์ชันสำหรับสร้าง CSRF token
function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// ฟังก์ชันสำหรับตรวจสอบความแข็งแกร่งของรหัสผ่าน
function is_strong_password($password)
{
    // รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร
    return strlen($password) >= 4;
}

// ตรวจสอบและสร้างโฟลเดอร์ logs ถ้ายังไม่มี
$log_dir = __DIR__ . '/../logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// กำหนดเส้นทางไฟล์ log
$log_file = $log_dir . '/error.log';

generate_csrf_token();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบ CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "การร้องขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง";
        error_log("CSRF token ไม่ถูกต้องสำหรับ user_id: " . $_SESSION['user_id'], 3, $log_file);
    } else {
        // รับและทำความสะอาดข้อมูลที่ผู้ใช้ส่งมา
        $current_password = trim($_POST['current_password'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        // ตรวจสอบความครบถ้วนของข้อมูล
        if (empty($current_password)) {
            $errors[] = "กรุณากรอกรหัสผ่านปัจจุบัน";
            error_log("ผู้ใช้ " . $_SESSION['user_id'] . " ไม่ได้กรอกรหัสผ่านปัจจุบัน", 3, $log_file);
        }

        if (empty($new_password)) {
            $errors[] = "กรุณากรอกรหัสผ่านใหม่";
            error_log("ผู้ใช้ " . $_SESSION['user_id'] . " ไม่ได้กรอกรหัสผ่านใหม่", 3, $log_file);
        }

        if (empty($confirm_password)) {
            $errors[] = "กรุณายืนยันรหัสผ่านใหม่";
            error_log("ผู้ใช้ " . $_SESSION['user_id'] . " ไม่ได้ยืนยันรหัสผ่านใหม่", 3, $log_file);
        }

        // ตรวจสอบว่ารหัสผ่านใหม่และยืนยันรหัสผ่านตรงกัน
        if ($new_password !== $confirm_password) {
            $errors[] = "รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน";
            error_log("ผู้ใช้ " . $_SESSION['user_id'] . " รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน", 3, $log_file);
        }

        // ตรวจสอบความแข็งแกร่งของรหัสผ่านใหม่
        if (!is_strong_password($new_password)) {
            $errors[] = "รหัสผ่านใหม่ต้องมีอย่างน้อย 4 ตัวอักษร";
            error_log("ผู้ใช้ " . $_SESSION['user_id'] . " รหัสผ่านใหม่ไม่แข็งแกร่ง: " . $new_password, 3, $log_file);
        }

        if (empty($errors)) {
            // ดึงข้อมูลผู้ใช้จากฐานข้อมูล
            $user_id = $_SESSION['user_id'];

            $stmt = $conn->prepare("SELECT password, owner_name FROM users WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("s", $user_id);
                if (!$stmt->execute()) {
                    $errors[] = "เกิดข้อผิดพลาดในการดำเนินการ กรุณาลองใหม่อีกครั้ง";
                    error_log("การดำเนินการ SQL ล้มเหลว: " . $stmt->error, 3, $log_file);
                } else {
                    $stmt->store_result();

                    if ($stmt->num_rows === 1) {
                        $stmt->bind_result($hashed_password, $owner_name);
                        $stmt->fetch();

                        // ตรวจสอบรหัสผ่านปัจจุบัน
                        if (password_verify($current_password, $hashed_password)) {
                            // แฮชรหัสผ่านใหม่
                            $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                            // อัปเดตรหัสผ่านในฐานข้อมูล
                            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");

                            if ($update_stmt) {
                                $update_stmt->bind_param("ss", $new_hashed_password, $user_id);

                                if ($update_stmt->execute()) {
                                    $success = "รหัสผ่านของคุณถูกเปลี่ยนแปลงเรียบร้อยแล้ว";

                                    // รีเซ็ต CSRF token เพื่อป้องกันการใช้ซ้ำ
                                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                                } else {
                                    $errors[] = "เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน กรุณาลองใหม่อีกครั้ง";
                                    error_log("เกิดข้อผิดพลาดในการอัปเดตรหัสผ่านสำหรับ user_id: " . $user_id . " Error: " . $update_stmt->error, 3, $log_file);
                                }

                                $update_stmt->close();
                            } else {
                                $errors[] = "ไม่สามารถเตรียมคำสั่ง SQL สำหรับการอัปเดตรหัสผ่านได้";
                                error_log("ไม่สามารถเตรียมคำสั่ง SQL สำหรับการอัปเดตรหัสผ่านได้สำหรับ user_id: " . $user_id . " Error: " . $conn->error, 3, $log_file);
                            }
                        } else {
                            $errors[] = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
                            error_log("ผู้ใช้ " . $user_id . " รหัสผ่านปัจจุบันไม่ถูกต้อง", 3, $log_file);
                        }
                    } else {
                        $errors[] = "ไม่พบผู้ใช้ที่กำหนด";
                        error_log("ไม่พบผู้ใช้ที่กำหนดสำหรับ user_id: " . $user_id, 3, $log_file);
                    }
                }

                $stmt->close();
            } else {
                $errors[] = "ไม่สามารถเตรียมคำสั่ง SQL สำหรับการตรวจสอบรหัสผ่านได้";
                error_log("ไม่สามารถเตรียมคำสั่ง SQL สำหรับการตรวจสอบรหัสผ่านได้สำหรับ user_id: " . $user_id . " Error: " . $conn->error, 3, $log_file);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เปลี่ยนรหัสผ่าน</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- โหลด SweetAlert2 -->
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
            max-width: 600px;
            margin: 0 auto;
        }

        .form-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
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

        .alert {
            margin-top: 20px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($errors)): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    html: <?= json_encode(implode('<br>', $errors)) ?>
                });
            <?php endif; ?>

            <?php if ($success): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: <?= json_encode($success) ?>
                });
            <?php endif; ?>

            // การยืนยันการเปลี่ยนรหัสผ่าน
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const newPassword = document.getElementById('new_password').value.trim();
                const confirmPassword = document.getElementById('confirm_password').value.trim();
                const passwordRegex = /^.{4,}$/; // ตรวจสอบว่ามีอย่างน้อย 4 ตัวอักษร

                const errors = [];

                // ตรวจสอบความครบถ้วนของข้อมูล
                if (newPassword.length < 4) {
                    errors.push('รหัสผ่านใหม่ต้องมีอย่างน้อย 4 ตัวอักษร');
                }

                if (newPassword !== confirmPassword) {
                    errors.push('รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน');
                }

                if (errors.length > 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'พบข้อผิดพลาด',
                        html: errors.join('<br>')
                    });
                } else {
                    e.preventDefault(); // ป้องกันการส่งฟอร์มทันที
                    Swal.fire({
                        title: 'ยืนยันการเปลี่ยนรหัสผ่าน?',
                        text: "คุณต้องการเปลี่ยนรหัสผ่านหรือไม่?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'ใช่, เปลี่ยนรหัสผ่าน',
                        cancelButtonText: 'ยกเลิก'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit(); // ส่งฟอร์มถ้าผู้ใช้ยืนยัน
                        }
                    });
                }
            });
        });
    </script>
</head>
<body>

    <!-- ส่วนหัว -->
    <header class="header">
        <a href="../main/settings.php" class="back-button" aria-label="กลับ"><i class="bi bi-arrow-left"></i></a>
        <h4 class="mb-0">เปลี่ยนรหัสผ่าน</h4>
    </header>

    <!-- เนื้อหา -->
    <main class="container">
        <div class="form-container">
            <form action="change_password.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="mb-3">
                    <label for="current_password" class="form-label">รหัสผ่านปัจจุบัน</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                    <div id="passwordHelp" class="form-text">
                        รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร
                    </div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary">เปลี่ยนรหัสผ่าน</button>
            </form>
        </div>
    </main>

    <!-- Footer Menu -->
    <?php
    // กำหนดเส้นทางแบบสัมพัทธ์อย่างปลอดภัย
    $footerMenuPath = realpath(__DIR__ . '/../includes/footer_menu.php');
    $includesPath = realpath(__DIR__ . '/../includes');
    if ($footerMenuPath && strpos($footerMenuPath, $includesPath) === 0) {
        require_once $footerMenuPath;
    } else {
        // บันทึก error ไปยังไฟล์ log และแสดงข้อความแจ้งเตือนบนหน้า
        error_log("ไม่สามารถโหลด Footer Menu ได้: $footerMenuPath", 3, $log_file);
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถโหลด Footer Menu ได้'
                });
              </script>";
    }
    ?>
</body>
</html>
