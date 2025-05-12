<?php
session_start();
include '../users/db_connection.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่าผู้ใช้ล็อกอินและมี house_number ใน session หรือไม่
if (!isset($_SESSION['user_id']) || !isset($_SESSION['house_number'])) {
    header("Location: ../users/user_login.php");
    exit();
}

$house_number = filter_var($_SESSION['house_number'], FILTER_SANITIZE_STRING); // ตรวจสอบและกรองข้อมูล

// ฟังก์ชันแปลงสถานะภาษาอังกฤษเป็นภาษาไทย
function translateStatusToThai($status)
{
    switch ($status) {
        case 'pending':
            return 'รอดำเนินการ';
        case 'received':
            return 'ได้รับแล้ว';
        case 'resolved':
            return 'แก้ไขแล้ว';
        case 'unmodifiable':
            return 'แก้ไขไม่ได้';
        case 'deleted':
            return 'ถูกลบแล้ว'; // เพิ่มสถานะ deleted
        default:
            return 'ไม่ทราบสถานะ';
    }
}


// ฟังก์ชันเพื่อคืนค่าไอคอนสถานะ
function getStatusIcon($status)
{
    switch ($status) {
        case 'pending':
            return 'bi-exclamation-circle text-warning';
        case 'received':
            return 'bi-envelope-open text-primary';
        case 'resolved':
            return 'bi-check-circle text-success';
        case 'unmodifiable':
            return 'bi-lock-fill text-secondary';
        case 'deleted':
            return 'bi-trash-fill text-danger'; // เพิ่มไอคอนสำหรับสถานะ deleted
        default:
            return 'bi-question-circle text-muted';
    }
}


// เช็กว่ามีการเรียกใช้งาน AJAX หรือไม่
if (isset($_POST['action']) && $_POST['action'] == 'load_messages') {
    // คำสั่ง SQL ดึงข้อมูลแจ้งปัญหาและการดำเนินการจากฐานข้อมูล
    $sql = "SELECT p.description, p.status, p.updated_at, a.action, a.performed_at 
            FROM problems p
            LEFT JOIN actions a ON p.id = a.problem_id
            WHERE p.house_number = ?
            ORDER BY p.status = 'pending' DESC, p.status = 'received' DESC, a.performed_at DESC
            LIMIT 0, 25";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $house_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
?>
            <div class="message-item">
                <div class="d-flex align-items-center">
                    <i class="bi <?php echo getStatusIcon($row['status']); ?> status-icon"></i>
                    <div>
                        <div class="description"><?php echo htmlspecialchars($row['description']); ?></div>
                        <div class="status">สถานะ: <?php echo htmlspecialchars(translateStatusToThai($row['status'])); ?></div>
                        <div class="performed-at">
                            อัปเดตล่าสุด: <?php echo !empty($row['updated_at']) ? date("d/m/Y H:i", strtotime($row['updated_at'])) : 'ไม่มีข้อมูล'; ?>
                        </div>
                    </div>
                </div>
                <div class="action mt-2">
                    <p><?php echo !empty($row['action']) ? htmlspecialchars($row['action']) : 'กำลังดำเนินการ'; ?></p>
                </div>
            </div>
<?php
        }
    } else {
        echo '<p class="text-center" style="color: #888; font-size: 16px;">ไม่มีการแจ้งเตือน</p>';
    }
    exit(); // หยุดการประมวลผลเพื่อไม่ให้ส่งข้อมูลส่วนอื่น
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กล่องข้อความ</title>
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
            padding: 20px;
        }

        .message-item {
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .message-item:hover {
            box-shadow: 0px 8px 15px rgba(0, 0, 0, 0.2);
        }

        .status-icon {
            color: #ffc107;
            font-size: 20px;
            margin-right: 10px;
        }

        .description {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .status {
            font-size: 14px;
            color: #888;
            margin-bottom: 5px;
        }

        .performed-at {
            font-size: 12px;
            color: #aaa;
        }

        /* Footer Menu */
        .footer-menu {
            position: fixed;
            bottom: 0;
            width: 100%;
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
    </style>

    <!-- เพิ่ม SweetAlert และ jQuery สำหรับการแสดงแจ้งเตือน -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        function loadMessages() {
            $.ajax({
                url: '', // เนื่องจากเป็นไฟล์เดียวกันจึงเว้นไว้ได้
                type: 'POST',
                data: {
                    action: 'load_messages'
                }, // ส่งข้อมูลเพื่อบอกว่าเป็นการโหลดข้อมูล
                success: function(response) {
                    $('#message-container').html(response); // อัปเดตข้อมูลใน container ที่ใช้แสดงข้อความ
                },
                error: function() {
                    console.error('เกิดข้อผิดพลาดในการดึงข้อมูล');
                }
            });
        }

        // ฟังก์ชันสำหรับแสดงแจ้งเตือนเมื่อทำการลบ
        function showDeleteAlert() {
            Swal.fire({
                icon: 'success',
                title: 'ลบสำเร็จ!',
                text: 'ข้อมูลถูกลบเรียบร้อยแล้ว.',
                confirmButtonText: 'ตกลง'
            });
        }

        // ฟังก์ชันสำหรับเรียกเมื่อทำการลบข้อมูล
        function deleteItem(id) {
            $.ajax({
                url: 'delete_item.php', // ตัวอย่างไฟล์สำหรับการลบข้อมูล
                type: 'POST',
                data: {
                    id: id
                },
                success: function(response) {
                    showDeleteAlert(); // แสดงแจ้งเตือนเมื่อการลบสำเร็จ
                    loadMessages(); // โหลดข้อมูลใหม่
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'ลบไม่สำเร็จ!',
                        text: 'เกิดข้อผิดพลาดในการลบข้อมูล.',
                        confirmButtonText: 'ตกลง'
                    });
                }
            });
        }

        // ฟังก์ชันสำหรับเปลี่ยนสถานะ
        function changeStatus(id, status) {
            $.ajax({
                url: 'change_status.php', // ไฟล์สำหรับเปลี่ยนสถานะ
                type: 'POST',
                data: {
                    id: id,
                    status: status
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'อัปเดตสถานะสำเร็จ!',
                        text: 'สถานะได้ถูกเปลี่ยนเป็น "' + status + '" เรียบร้อยแล้ว.',
                        confirmButtonText: 'ตกลง'
                    });
                    loadMessages(); // โหลดข้อมูลใหม่
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'ไม่สำเร็จ!',
                        text: 'เกิดข้อผิดพลาดในการเปลี่ยนสถานะ.',
                        confirmButtonText: 'ตกลง'
                    });
                }
            });
        }

        // ฟังก์ชันแสดงแจ้งเตือนเมื่อเปลี่ยนเป็นสถานะ "แก้ไขไม่ได้"
        function confirmUnmodifiable(id) {
            Swal.fire({
                title: 'ยืนยันการเปลี่ยนสถานะเป็น "แก้ไขไม่ได้"?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'ใช่, เปลี่ยนสถานะ',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    changeStatus(id, 'unmodifiable'); // เปลี่ยนสถานะเป็น unmodifiable
                }
            });
        }

        // ดึงข้อมูลใหม่ทุกๆ 30 วินาที
        setInterval(loadMessages, 300);

        // ดึงข้อมูลเมื่อหน้าเว็บโหลดครั้งแรก
        $(document).ready(function() {
            loadMessages();
        });
    </script>
</head>

<body>

    <!-- ส่วนหัว -->
    <div class="header">
        <a href="../main/index.php" class="back-button"><i class="bi bi-arrow-left"></i></a>
        <h4 class="mb-0">กล่องข้อความ</h4>
    </div>

    <div class="container" id="message-container">
        <!-- ส่วนนี้จะถูกอัปเดตด้วยข้อมูลใหม่จาก AJAX -->
    </div>

    <!-- Footer Menu -->
    <?php
    $footerMenuPath = realpath('../includes/footer_menu.php');
    if ($footerMenuPath && strpos($footerMenuPath, realpath('../includes')) === 0) {
        require_once $footerMenuPath;
    } else {
        error_log("ไม่สามารถโหลด Footer Menu ได้: $footerMenuPath", 3, "/path/to/your/log/file.log");
        echo "<div class='alert alert-danger'>ไม่สามารถโหลด Footer Menu ได้</div>";
    }
    ?>
</body>

</html>