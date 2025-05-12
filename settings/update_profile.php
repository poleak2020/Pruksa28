<?php
session_start(); // Start session

// Connect to the database
require_once '../users/db_connection.php'; 

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../users/user_login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Get user_id from session as string
$error_message = '';
$success_message = '';

// Fetch current user profile data
$sql = "SELECT owner_name, contact_number FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    // Set error message instead of die()
    $error_message = "เกิดข้อผิดพลาดในการเตรียมคำสั่ง: " . $conn->error;
} else {
    $stmt->bind_param('s', $user_id); // 's' for string

    if (!$stmt->execute()) {
        $error_message = "เกิดข้อผิดพลาดในการดำเนินการคำสั่ง: " . $stmt->error;
    } else {
        $result = $stmt->get_result();

        // Check if data exists
        if ($result && $result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
        } else {
            $error_message = "ไม่พบข้อมูลโปรไฟล์";
            $user_data = ['owner_name' => '', 'contact_number' => ''];
        }
    }

    $stmt->close();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $owner_name = trim($_POST['owner_name']);
    $contact_number = trim($_POST['contact_number']);

    // Validate new data
    if (empty($owner_name) || empty($contact_number)) {
        $error_message = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } else {
        // Handle case where contact_number is 'ไม่มี'
        if ($contact_number === 'ไม่มี') {
            $contact_number = ''; // Set to empty string or NULL
        }

        // Update data in the database
        $sql_update = "UPDATE users SET owner_name = ?, contact_number = ? WHERE user_id = ?";
        $stmt_update = $conn->prepare($sql_update);

        if (!$stmt_update) {
            $error_message = "เกิดข้อผิดพลาดในการเตรียมคำสั่งสำหรับการอัปเดต: " . $conn->error;
        } else {
            $stmt_update->bind_param('sss', $owner_name, $contact_number, $user_id); // 'sss' for strings

            if ($stmt_update->execute()) {
                $success_message = "อัปเดตข้อมูลโปรไฟล์เรียบร้อยแล้ว";
                
                // รีเฟรชข้อมูลจากฐานข้อมูล
                $stmt_refresh = $conn->prepare("SELECT owner_name, contact_number FROM users WHERE user_id = ?");
                if ($stmt_refresh) {
                    $stmt_refresh->bind_param('s', $user_id);
                    $stmt_refresh->execute();
                    $result_refresh = $stmt_refresh->get_result();
                    if ($result_refresh && $result_refresh->num_rows > 0) {
                        $user_data = $result_refresh->fetch_assoc();
                    }
                    $stmt_refresh->close();
                }
            } else {
                $error_message = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $stmt_update->error;
            }

            $stmt_update->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อัปเดตโปรไฟล์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet"> <!-- Connect to Bootstrap Icons -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- โหลด SweetAlert2 -->
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
            font-size: 14px;
            color: #555;
        }

        .form-control {
            padding: 12px;
            font-size: 14px;
            border-radius: 8px;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #007bff;
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            <?php if (!empty($error_message)): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: <?= json_encode($error_message) ?>
                });
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: <?= json_encode($success_message) ?>
                });
            <?php endif; ?>

            // ฟังก์ชันสำหรับการฟอร์แมตเบอร์โทร
            function formatPhoneNumber(input) {
                let cleaned = input.replace(/\D/g, '');
                let formatted = '';

                // ตรวจสอบว่าเป็นหมายเลขมือถือในประเทศไทยหรือไม่
                if (/^0[689]\d{8}$/.test(cleaned)) {
                    formatted = cleaned.replace(/(\d{2})(\d{4})(\d{4})/, '$1-$2-$3');
                } else {
                    // ถ้าไม่ใช่มือถือ ให้แสดงเฉพาะตัวเลข
                    formatted = cleaned;
                }

                return formatted;
            }

            const contactInput = document.getElementById('contact_number');

            // เมื่อมีการป้อนข้อมูลใน input
            contactInput.addEventListener('input', function (e) {
                let currentValue = contactInput.value;

                // ถ้าเป็น 'ไม่มี', อนุญาตให้พิมพ์ได้
                if (currentValue.toLowerCase() === 'ไม่มี') {
                    return;
                }

                // ฟอร์แมตเบอร์โทร
                let formattedValue = formatPhoneNumber(currentValue);
                contactInput.value = formattedValue;
            });

            // เมื่อออกจาก input field
            contactInput.addEventListener('blur', function (e) {
                let currentValue = contactInput.value.trim();

                if (currentValue === '') {
                    contactInput.value = 'ไม่มี';
                } else if (!/^0[689]\d{8}$/.test(currentValue.replace(/-/g, ''))) {
                    // ถ้าไม่ใช่หมายเลขมือถือที่ถูกต้อง ให้ตั้งค่าเป็น 'ไม่มี'
                    contactInput.value = 'ไม่มี';
                }
            });

            // การยืนยันการอัปเดตโปรไฟล์
            const form = document.querySelector('form');
            form.addEventListener('submit', function (e) {
                const ownerName = document.getElementById('owner_name').value.trim();
                const contactNumber = document.getElementById('contact_number').value.trim();

                const errors = [];

                // ตรวจสอบความครบถ้วนของข้อมูล
                if (ownerName === '' || contactNumber === '') {
                    errors.push('กรุณากรอกข้อมูลให้ครบถ้วน');
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
                        title: 'ยืนยันการอัปเดตโปรไฟล์?',
                        text: "คุณต้องการบันทึกการเปลี่ยนแปลงหรือไม่?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'ใช่, บันทึกการเปลี่ยนแปลง',
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
    <div class="header">
        <a href="../main/settings.php" class="back-button"><i class="bi bi-arrow-left"></i></a> <!-- Ensure icon is loaded -->
        <h4 class="mb-0">อัปเดตโปรไฟล์</h4>
    </div>

    <!-- Form Container -->
    <div class="form-container">
        <h4 class="text-center">กรุณาอัปเดตข้อมูลโปรไฟล์</h4>

        <form method="POST" action="update_profile.php">
            <div class="mb-3">
                <label for="owner_name" class="form-label">ชื่อเจ้าของบ้าน</label>
                <input type="text" class="form-control" id="owner_name" name="owner_name" value="<?php echo htmlspecialchars($user_data['owner_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="mb-3">
                <label for="contact_number" class="form-label">เบอร์โทรติดต่อ</label>
                <input type="tel" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($user_data['contact_number'] ?: 'ไม่มี', ENT_QUOTES, 'UTF-8'); ?>" required maxlength="13" inputmode="numeric">
            </div>
            <button type="submit" class="submit-btn">บันทึกการเปลี่ยนแปลง</button>
        </form>
    </div>

</body>

</html>
