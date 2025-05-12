<?php
// main/index.php

// เริ่ม session ถ้ายังไม่ได้เริ่ม
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../users/db_connection.php'; // เชื่อมต่อฐานข้อมูล

// ฟังก์ชันเพื่อป้องกัน XSS
function sanitize_output($data)
{
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// กำหนดค่าคงที่สำหรับไดเรกทอรี includes และไฟล์ล็อก
define('INCLUDES_DIR', realpath(__DIR__ . '/../includes'));
define('LOG_FILE', __DIR__ . '/../logs/error.log'); // ปรับเส้นทางให้ถูกต้องตามเซิร์ฟเวอร์ของคุณ

/**
 * ฟังก์ชันสำหรับรวมไฟล์อย่างปลอดภัย
 *
 * @param string $fileName ชื่อไฟล์ที่ต้องการรวม
 * @return void
 */
function safe_include($fileName)
{
    $filePath = realpath(INCLUDES_DIR . '/' . $fileName);
    if ($filePath && strpos($filePath, INCLUDES_DIR) === 0 && file_exists($filePath)) {
        require_once $filePath;
    } else {
        error_log("ไม่สามารถโหลดไฟล์ $fileName ได้: $filePath", 3, LOG_FILE);
        echo "<div class='alert alert-danger'>ไม่สามารถโหลด $fileName ได้</div>";
    }
}

// กำหนดจำนวนประกาศต่อหน้า
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1; // ป้องกันหน้าไม่ถูกต้อง
$offset = ($page - 1) * $limit;

// ดึงประกาศตามหน้า
$sql_announcements = "SELECT id, title, content, image_url, created_at FROM announcements WHERE status = 'เผยแพร่' ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result_announcements = $conn->query($sql_announcements);

// ดึงจำนวนประกาศทั้งหมดเพื่อคำนวณจำนวนหน้า
$sql_total = "SELECT COUNT(*) as total FROM announcements WHERE status = 'เผยแพร่'";
$result_total = $conn->query($sql_total);
$total = 0;
if ($result_total) {
    $total = $result_total->fetch_assoc()['total'];
} else {
    error_log("ไม่สามารถดึงจำนวนประกาศได้: " . $conn->error, 3, LOG_FILE);
}
$total_pages = ceil($total / $limit);

// เพิ่มข้อความ Debugging สำหรับจำนวนหน้า
echo "<!-- Total announcements: $total, Total pages: $total_pages -->";

// ดึงประกาศที่มีสถานะ 'เผยแพร่' จากฐานข้อมูล
$announcements = [];
if ($result_announcements) {
    while ($row = $result_announcements->fetch_assoc()) {
        $announcements[] = $row;
    }
} else {
    // บันทึกข้อผิดพลาดและเตรียมข้อความแจ้งเตือน
    error_log("ไม่สามารถดึงข้อมูลประกาศได้: " . $conn->error, 3, LOG_FILE);
    $announcements_error = "ไม่สามารถดึงข้อมูลประกาศได้ในขณะนี้";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สำนักงานนิติบุคคลหมู่บ้านพฤกษา 28/1</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- External CSS -->
    <link href="styles.css" rel="stylesheet">
    <style>
        /* ถ้ายังไม่ได้ย้าย CSS ไปยังไฟล์ styles.css */
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin-bottom: 60px;
            /* เพิ่มระยะห่างด้านล่างสำหรับ Footer */
        }

        .header {
            background-color: #7BC59D;
            padding: 15px;
            text-align: center;
            font-size: 18px;
            color: white;
            border-bottom: 2px solid #5ca387;
        }

        .announcement {
            margin-top: 30px;
        }

        .announcement .card {
            margin-bottom: 20px;
        }

        .announcement .card-body {
            text-align: left;
        }

        /* สไตล์สำหรับปุ่มและ Modal */
        .btn-primary {
            margin-top: 10px;
        }

        /* สไตล์เพิ่มเติมสำหรับ Modal */
        .modal-body img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <div class="header">
        สำนักงานนิติบุคคลหมู่บ้านพฤกษา 28/1
    </div>

    <!-- Search Bar -->
    <?php safe_include('search_bar.php'); ?>

    <!-- Category Section -->
    <?php safe_include('category_section.php'); ?>

    <!-- Recent Activity -->
    <div class="category-section bg-light p-3 rounded shadow-sm">
        <h5 class="text-center mb-3">กิจกรรมล่าสุด</h5>
        <div class="text-center">
            <i class="bi bi-calendar3 text-muted" style="font-size: 2rem;"></i>
            <p class="text-muted mt-2">(ยังไม่มีข้อมูลกิจกรรม)</p>
        </div>
    </div>

    <!-- Announcements Section -->
    <div class="announcement container">
        <h5 class="text-center mb-4">ประกาศล่าสุด</h5>
        <?php if (isset($announcements_error)): ?>
            <div class="alert alert-danger text-center"><?php echo sanitize_output($announcements_error); ?></div>
        <?php elseif (!empty($announcements)): ?>
            <?php foreach ($announcements as $announcement): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <?php if (!empty($announcement['image_url'])): ?>
                            <?php
                            // ใช้ basename เพื่อป้องกัน Path Traversal
                            $image_name = basename($announcement['image_url']);
                            // กำหนด Web Path สำหรับลิงก์
                            $image_path = '/ฝึกงาน/uploads/' . $image_name;
                            // กำหนด File System Path สำหรับการตรวจสอบ
                            $file_system_path = realpath('../../ฝึกงาน/uploads/' . $image_name);

                            // เพิ่มข้อความ Debugging เพื่อดูเส้นทางที่กำลังตรวจสอบ
                            echo "<!-- Checking file path: $file_system_path -->";

                            if ($file_system_path && file_exists($file_system_path)): // ตรวจสอบว่าไฟล์มีอยู่จริง
                            ?>
                                <!-- แสดงรูปภาพแทนการเปิดลิงก์ในแท็บใหม่ -->
                                <img src="<?php echo sanitize_output($image_path); ?>" alt="รูปประกาศ" class="img-fluid mt-3">
                            <?php else: ?>
                                <p class="text-danger">ไม่พบรูปภาพ: <?php echo sanitize_output($image_path); ?></p>
                            <?php endif; ?>
                        <?php endif; ?>
                        <h5 class="card-title"><?php echo sanitize_output($announcement['title']); ?></h5>
                        <p class="card-text"><?php echo nl2br(sanitize_output($announcement['content'])); ?></p>
                        <p class="card-text"><small class="text-muted">วันที่ประกาศ: <?php echo date("d/m/Y H:i", strtotime($announcement['created_at'])); ?></small></p>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation example">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info text-center">ยังไม่มีประกาศใหม่</div>
        <?php endif; ?>
    </div>

    <!-- Footer Menu -->
    <?php safe_include('footer_menu.php'); ?>

    <!-- Bootstrap JS (รวมกับ Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>