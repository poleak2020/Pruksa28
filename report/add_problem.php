<?php
session_start(); // เริ่มต้น session
include '../users/db_connection.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: ../users/user_login.php");
    exit();
}

$error_message = ''; // เก็บข้อความข้อผิดพลาด
$success_message = ''; // เก็บข้อความสำเร็จ

// ตรวจสอบว่ามีการส่งฟอร์มหรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $house_number = $_SESSION['house_number']; // ใช้ house_number จาก session
    $description = htmlspecialchars(trim($_POST['description']));
    $status = 'pending'; // ค่าเริ่มต้นสถานะคือ 'รอดำเนินการ'
    $image_url = null; // เก็บ path ของรูปภาพ

    // ตรวจสอบว่ามีการอัปโหลดรูปภาพหรือไม่
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/";

        // ตรวจสอบว่าโฟลเดอร์อัปโหลดมีอยู่และสามารถเขียนไฟล์ได้หรือไม่
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) {
                $error_message = "ไม่สามารถสร้างโฟลเดอร์สำหรับอัปโหลดได้";
            }
        } elseif (!is_writable($target_dir)) {
            $error_message = "ไม่สามารถบันทึกไฟล์ได้ ตรวจสอบสิทธิ์ของโฟลเดอร์อัปโหลด";
        } else {
            $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION)); // นามสกุลไฟล์
            $file_name = uniqid() . '.' . $extension; // สร้างชื่อไฟล์ใหม่ด้วย uniqid()
            $target_file = $target_dir . $file_name; // กำหนดที่อยู่ปลายทางของไฟล์อัปโหลด

            // ตรวจสอบชนิดไฟล์และขนาดไฟล์
            $valid_extensions = array('jpg', 'jpeg', 'png', 'gif');
            if (in_array($extension, $valid_extensions)) {
                if ($_FILES['image']['size'] <= 2097152) { // ขนาดไฟล์ไม่เกิน 2MB
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                        $image_url = "uploads/" . $file_name; // เก็บเพียงเส้นทางสัมพันธ์ที่สั้นลงสำหรับ URL
                    } else {
                        $error_message = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
                    }
                } else {
                    $error_message = "ขนาดไฟล์รูปภาพใหญ่เกินไป (ต้องไม่เกิน 2MB)";
                }
            } else {
                $error_message = "รูปแบบไฟล์ไม่รองรับ (เฉพาะ JPG, JPEG, PNG, GIF)";
            }
        }
    }

    // ตรวจสอบว่ามีข้อผิดพลาดหรือไม่
    if (empty($error_message) && !empty($description)) {
        // เพิ่มข้อมูลแจ้งเหตุลงในฐานข้อมูล
        $sql = "INSERT INTO problems (house_number, description, status, image_url) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            $error_message = "การเตรียมคำสั่ง SQL ล้มเหลว: " . $conn->error;
        } else {
            $stmt->bind_param('ssss', $house_number, $description, $status, $image_url);

            if ($stmt->execute()) {
                // เพิ่มข้อมูลสำเร็จ กลับไปยังหน้ารายการแจ้งปัญหา
                $success_message = "บันทึกการแจ้งเหตุสำเร็จ!";
                header("Location: report_issue.php?status=pending");
                exit();
            } else {
                $error_message = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error;
            }

            $stmt->close();
        }
    } elseif (empty($description)) {
        $error_message = "กรุณากรอกรายละเอียดการแจ้งเหตุ";
    }
}
?>


<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มการแจ้งเหตุ</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- เพิ่ม SweetAlert2 -->
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
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            position: relative;
            font-size: 20px;
            font-weight: bold;
            color: #000;
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

        .form-container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 30px auto;
            transition: all 0.3s ease-in-out;
        }

        .form-container:hover {
            box-shadow: 0px 8px 25px rgba(0, 0, 0, 0.2);
        }

        h2 {
            font-size: 26px;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group label {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }

        .form-group textarea,
        .form-group input[type="file"] {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ced4da;
            font-size: 16px;
            background-color: #f8f9fa;
            box-sizing: border-box;
            transition: all 0.3s;
        }

        .form-group textarea:focus,
        .form-group input[type="file"]:focus {
            border-color: #007BFF;
            outline: none;
            background-color: #eef7ff;
        }

        .submit-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 30px;
            width: 100%;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background-color: #218838;
        }

        img#imagePreview {
            display: block;
            margin: 10px auto;
            max-width: 100%;
            max-height: 300px;
        }
    </style>
</head>

<body>

    <!-- ส่วนหัว -->
    <div class="header">
        <a href="javascript:history.back()" class="back-button"><i class="bi bi-arrow-left"></i></a>
        แจ้งปัญหา
    </div>

    <!-- Container ฟอร์ม -->
    <div class="form-container">
        <h2>กรุณากรอกรายละเอียดการแจ้งปัญหา</h2>

        <form method="POST" action="add_problem.php" enctype="multipart/form-data" id="problemForm">
            <div class="form-group">
                <label for="description">รายละเอียดการแจ้งปัญหา</label>
                <textarea id="description" name="description" rows="5" placeholder="กรอกรายละเอียด..." required></textarea>
            </div>

            <div class="form-group">
                <label for="image">อัปโหลดรูปภาพ</label>
                <input type="file" id="image" name="image" accept="image/*" class="form-control" onchange="previewImage(event)">
                <img id="imagePreview" src="" alt="Image Preview" style="display:none;">
            </div>

            <button type="button" class="submit-btn" onclick="confirmSubmit()">บันทึกการแจ้งปัญหา</button>
        </form>
    </div>

    <script>
        function previewImage(event) {
            const image = document.getElementById('imagePreview');
            image.src = URL.createObjectURL(event.target.files[0]);
            image.style.display = 'block';
        }

        function confirmSubmit() {
            const description = document.getElementById('description').value.trim();
            
            if (description === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณากรอกรายละเอียดการแจ้งปัญหา',
                    confirmButtonText: 'ตกลง'
                });
                return;
            }

            // ยืนยันการกรอกข้อมูล
            Swal.fire({
                title: 'ยืนยันการบันทึก',
                html: `รายละเอียดปัญหาที่คุณกรอก: <br><strong>${description}</strong><br><br>คุณต้องการบันทึกหรือไม่?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('problemForm').submit();
                }
            });
        }

        // แสดงข้อความ SweetAlert2 เมื่อมีข้อผิดพลาดหรือสำเร็จ
        <?php if (!empty($error_message)): ?>
            Swal.fire({
                icon: 'error',
                title: 'ข้อผิดพลาด',
                text: '<?php echo htmlspecialchars($error_message); ?>',
                confirmButtonText: 'ตกลง'
            });
        <?php elseif (!empty($success_message)): ?>
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: '<?php echo htmlspecialchars($success_message); ?>',
                confirmButtonText: 'ตกลง'
            });
        <?php endif; ?>
    </script>
</body>

</html>
