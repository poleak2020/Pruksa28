<?php
session_start();
include '../users/db_connection.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['house_number'])) {
    header("Location: ../users/user_login.php");
    exit();
}

$house_number = $_SESSION['house_number'];

// ฟังก์ชันสำหรับดึงชื่อเจ้าของบ้าน
function getOwnerName($conn, $house_number)
{
    $sql = "SELECT owner_name FROM users WHERE house_number = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $house_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $owner = $result->fetch_assoc();
        $stmt->close();

        return $owner['owner_name'] ?? 'ไม่พบข้อมูลเจ้าของบ้าน';
    } else {
        die("Error in SQL prepare: " . $conn->error);
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลการชำระเงินทั้งหมด
function getAllPayments($conn, $house_number)
{
    // เพิ่มการดึงข้อมูล payment_date
    $sql = "SELECT payment_date, start_month, end_month, payment_year, status FROM payments WHERE house_number = ? ORDER BY payment_date ASC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $house_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $payments = [];

        while ($payment = $result->fetch_assoc()) {
            $payments[] = $payment;
        }

        $stmt->close();
        return $payments;
    } else {
        die("Error in SQL prepare: " . $conn->error);
    }
}

// ฟังก์ชันแปลงวันที่เป็นภาษาไทย
function formatThaiDate($date)
{
    $thaiMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];

    // แปลงวันที่จากรูปแบบ date string เป็น timestamp
    $timestamp = strtotime($date);

    // ดึงวันที่ (เลขวัน)
    $day = date('j', $timestamp);

    // ดึงเดือนและใช้ชื่อเดือนภาษาไทย
    $month = $thaiMonths[date('n', $timestamp) - 1];

    // ดึงปีและแปลงเป็น พ.ศ.
    $year = date('Y', $timestamp) + 543;

    // คืนค่าเป็นวันที่ ภาษาไทย เช่น 1 มกราคม 2567
    return "{$day} {$month} {$year}";
}

$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : 2567;

// กำหนดชื่อเดือนในภาษาไทย
$months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

// ดึงข้อมูลชื่อเจ้าของบ้าน
$owner_name = getOwnerName($conn, $house_number);

// ดึงข้อมูลการชำระเงินทั้งหมด
$allPayments = getAllPayments($conn, $house_number);

// ดึงข้อมูลเดือนและปีปัจจุบัน
$currentMonth = intval(date('n'));
$currentYear = intval(date('Y')) + 543; // แปลงเป็น พ.ศ.

// ตรวจสอบสถานะการชำระเงินและนับจำนวนเดือนที่ยังไม่ได้ชำระ
$unpaidCount = 0;

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ค่าส่วนกลาง</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f8f9fa;
            padding: 0;
        }

        .header {
            background-color: #fff;
            padding: 20px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .back-button {
            color: #000;
            text-decoration: none;
            font-size: 24px;
            position: absolute;
            left: 20px;
            transition: color 0.3s;
        }

        .back-button:hover {
            color: #0056b3;
        }

        .content {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 20px auto;
        }

        .status-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: inline-block;
            line-height: 50px;
            text-align: center;
            font-weight: bold;
            margin: 10px;
            font-size: 16px;
            color: white;
        }

        .paid {
            background-color: #28a745;
        }

        .unpaid {
            background-color: #dc3545;
        }

        .pending {
            background-color: #ffc107;
        }

        .future {
            background-color: #cccccc;
        }

        .legend {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 8px;
        }

        .legend p {
            display: flex;
            align-items: center;
            font-size: 14px;
            margin-right: 10px;
        }

        .legend .status-circle {
            margin-right: 5px;
        }

        .pay-button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }

        .pay-button:hover {
            background-color: #0056b3;
            color: white;
        }

        .mt-4 {
            margin-top: 20px;
        }

        @media (max-width: 576px) {
            .status-circle {
                width: 40px;
                height: 40px;
                line-height: 40px;
                font-size: 14px;
            }

            .legend {
                flex-direction: column;
                align-items: flex-start;
            }

            .legend p {
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>

    <!-- ส่วนหัว -->
    <div class="header">
        <a href="../main/index.php" class="back-button"><i class="bi bi-arrow-left"></i></a>
        <h4 class="mb-0 text-center">ค่าส่วนกลาง</h4>
    </div>

    <!-- เนื้อหา -->
    <div class="content">
        <p><strong>คุณ <?= $owner_name ?></strong><br>บ้านเลขที่: <?= $house_number ?></p>

        <div>
            <p>
                <i class="bi bi-flag-fill"></i> ค่าส่วนกลาง ชำระล่าสุดเดือน
                <?php
                $lastPayment = end($allPayments); // ค้นหาการชำระเงินล่าสุด
                if ($lastPayment) {
                    // ตรวจสอบว่าเก็บปีเป็นปี ค.ศ. หรือ พ.ศ.
                    $paymentYear = intval($lastPayment['payment_year']);
                    if ($paymentYear < 2500) { // ถ้าเป็น ค.ศ. ให้แปลงเป็น พ.ศ.
                        $paymentYear += 543;
                    }

                    echo $months[$lastPayment['end_month'] - 1] . ' ' . $paymentYear;
                } else {
                    echo 'ไม่มีข้อมูล';
                }
                ?>
                <br>
                <small style="color: gray;">ชำระเมื่อวันที่
                    <?= $lastPayment ? formatThaiDate($lastPayment['payment_date']) : 'ไม่พบวันที่ชำระล่าสุด' ?>
                </small>
            </p>
        </div>

        <!-- Dropdown เลือกปี -->
        <div class="mt-3">
            <label for="yearSelect">เลือกปี:</label>
            <select id="yearSelect" class="form-select" onchange="loadYearData(this.value)">
                <?php
                // ดึงปีปัจจุบัน
                $currentYear = intval(date('Y')) + 543;

                // ลูปจากปีถัดไป 1 ปี (ปีปัจจุบัน + 1) และย้อนหลังไป 5 ปี
                for ($i = 1; $i >= -5; $i--) {
                    $year = $currentYear + $i;
                    $selected = ($selectedYear == $year) ? 'selected' : ''; // ตรวจสอบว่าเป็นปีที่เลือกหรือไม่
                    echo "<option value='$year' $selected>$year</option>";
                }
                ?>
            </select>
        </div>

        <!-- สถานะการชำระเงิน -->
        <div id="paymentStatus" class="mt-4">
            <h5>ปี <?= $selectedYear ?></h5>
            <div class="d-flex flex-wrap justify-content-center">
                <?php
                $unpaidCount = 0; // นับจำนวนเดือนที่ยังไม่ได้ชำระ

                // Loop through each month
                foreach ($months as $index => $month) {
                    $monthIndex = $index + 1; // Month index starts at 1, not 0
                    $status = 'future'; // Default status is 'future'

                    foreach ($allPayments as $payment) {
                        $startMonth = intval($payment['start_month']);
                        $endMonth = intval($payment['end_month']);
                        $paymentYear = intval($payment['payment_year']);
                        $paymentStatus = $payment['status'];

                        if ($selectedYear == $paymentYear && $monthIndex >= $startMonth && $monthIndex <= $endMonth) {
                            if ($paymentStatus == 'approved') {
                                $status = 'paid';
                            } elseif ($paymentStatus == 'pending') {
                                $status = 'pending';
                            }
                        }
                    }

                    if (($selectedYear == $currentYear && $monthIndex <= $currentMonth) || ($selectedYear < $currentYear)) {
                        if ($status == 'future') {
                            $status = 'unpaid';
                        }

                        if ($status == 'unpaid') {
                            $unpaidCount++;
                        }
                    }

                    echo "<div class='status-circle {$status}'>{$month}</div>";
                }
                ?>
            </div>
        </div>

        <!-- แสดงจำนวนเดือนที่ยังไม่ได้ชำระ -->
        <div class="mt-4">
            <p><strong>จำนวนเดือนที่ยังไม่ได้ชำระ:</strong> <?= $unpaidCount ?> เดือน</p>
        </div>

        <!-- ปุ่มชำระเงิน -->
        <div class="text-center">
            <a href="../fees/central_fee_payment.php" class="pay-button"><i class="bi bi-credit-card"></i> ชำระค่าส่วนกลาง</a>
        </div>

        <!-- ตำนานการชำระ -->
        <div class="legend mt-4">
            <p><span class="status-circle paid"></span> ชำระแล้ว</p>
            <p><span class="status-circle unpaid"></span> ยังไม่ได้ชำระ</p>
            <p><span class="status-circle pending"></span> รอการตรวจสอบ</p>
            <p><span class="status-circle future"></span> เดือนในอนาคต</p>
        </div>
    </div>

    <!-- Footer Menu -->
    <?php include '../includes/footer_menu.php'; ?>

    <script>
        function loadYearData(year) {
            window.location.href = "?year=" + year;
        }
    </script>

</body>

</html>
