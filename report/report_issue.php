<?php
session_start(); // เริ่มต้น session
include '../users/db_connection.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['user_id']) || !isset($_SESSION['house_number'])) {
    header("Location: ../users/user_login.php");
    exit();
}

$house_number = $_SESSION['house_number']; // ใช้ house_number จาก session
$status = isset($_GET['status']) && in_array($_GET['status'], ['pending', 'received', 'resolved']) ? $_GET['status'] : 'pending';

// ตรวจสอบว่าการเชื่อมต่อฐานข้อมูลสำเร็จหรือไม่
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// สร้างคำสั่ง SQL ตามสถานะที่เลือก และกรองด้วย house_number
$sql = "SELECT * FROM problems WHERE status = ? AND house_number = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("การเตรียมคำสั่ง SQL ล้มเหลว: " . $conn->error);
}

$stmt->bind_param('ss', $status, $house_number);

if ($stmt->execute() === false) {
    die("การดำเนินการคำสั่ง SQL ล้มเหลว: " . $stmt->error);
}

$result = $stmt->get_result();

if ($result === false) {
    die("การดึงผลลัพธ์ล้มเหลว: " . $stmt->error);
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งปัญหา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- เพิ่ม SweetAlert2 -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            position: relative;
            min-height: 100vh;
            padding-bottom: 70px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #ffffff;
            color: black;
            border-bottom: 1px solid #ccc;
            position: relative;
        }

        .header h1 {
            font-size: 24px;
            text-align: center;
        }

        .back-button {
            position: absolute;
            left: 10px;
            color: black;
            font-size: 20px;
            display: flex;
            align-items: center;
            background-color: #ffffff;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
        }

        .back-button:hover {
            background-color: #f5f5f5;
        }

        .tabs {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #ffffff;
            width: 30%;
            text-align: center;
        }

        .tab.active {
            background-color: #007BFF;
            color: white;
        }

        .problem-item {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            background-color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .problem-details {
            flex-grow: 1;
            margin-right: 10px;
        }

        .problem-item .delete-icon {
            color: #e74c3c;
            cursor: pointer;
        }

        .add-problem-btn {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 500px;
            background-color: #28a745;
            color: white;
            padding: 12px;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
        }

        .add-problem-btn:hover {
            background-color: #218838;
        }

        .alert {
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <!-- ส่วนหัวที่มีปุ่มย้อนกลับทางซ้าย -->
    <div class="header d-flex align-items-center">
        <a href="../main/index.php" class="back-button">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1>แจ้งปัญหา</h1>
    </div>

    <!-- Tabs สำหรับการกรองสถานะ -->
    <div class="tabs">
        <div id="pending-tab" class="tab <?php echo $status == 'pending' ? 'active' : ''; ?>" onclick="window.location.href='?status=pending'">รอดำเนินการ</div>
        <div id="in-progress-tab" class="tab <?php echo $status == 'received' ? 'active' : ''; ?>" onclick="window.location.href='?status=received'">กำลังรอดำเนินการ</div>
        <div id="completed-tab" class="tab <?php echo $status == 'resolved' ? 'active' : ''; ?>" onclick="window.location.href='?status=resolved'">ดำเนินการเสร็จสิ้น</div>
    </div>

    <!-- ส่วนแสดงปัญหาที่แจ้ง -->
    <div class="container">
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<div class='problem-item'>";
                echo "<div class='problem-details'>";
                echo "<p><i class='bi bi-exclamation-circle'></i> แจ้งเหตุ: " . htmlspecialchars($row['description']) . "</p>";

                // แปลงสถานะจากภาษาอังกฤษเป็นภาษาไทย
                $status_thai = "";
                switch ($row['status']) {
                    case 'pending':
                        $status_thai = 'รอดำเนินการ';
                        break;
                    case 'received':
                        $status_thai = 'กำลังดำเนินการ';
                        break;
                    case 'resolved':
                        $status_thai = 'เสร็จสิ้น';
                        break;
                    default:
                        $status_thai = 'ไม่ทราบสถานะ';
                }

                echo "<p><strong>สถานะ:</strong> " . htmlspecialchars($status_thai) . "</p>";

                // แปลงวันที่และเวลาเป็น วัน/เดือน/ปี ชั่วโมง:นาที
                $formatted_date = date("d/m/Y H:i", strtotime($row['created_at']));
                echo "<p><strong>วันที่แจ้ง:</strong> " . htmlspecialchars($formatted_date) . "</p>";

                echo "</div>";
                echo "<i class='bi bi-trash delete-icon' onclick='confirmDelete(" . $row['id'] . ")'></i>";
                echo "</div>";
            }
        } else {
            echo "<div class='alert alert-warning text-center' role='alert'>";
            echo "ไม่พบปัญหาที่แจ้ง กรุณากดปุ่มด้านล่างเพื่อเพิ่มการแจ้งเหตุ";
            echo "</div>";
        }
        ?>

    </div>

    <!-- ปุ่มเพิ่มการแจ้งเหตุ -->
    <a href="add_problem.php" class="add-problem-btn">+ เพิ่มการแจ้งปัญหา</a>

    <script>
        function confirmDelete(problemId) {
            Swal.fire({
                title: 'คุณแน่ใจหรือไม่?',
                text: "คุณต้องการลบปัญหานี้หรือไม่?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ใช่, ลบเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "../report/delete_problem.php?id=" + problemId;
                }
            });
        }
    </script>

</body>

</html>
