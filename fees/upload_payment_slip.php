<?php
session_start();
include '../users/db_connection.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ตรวจสอบว่ามี session house_number หรือไม่
if (!isset($_SESSION['house_number'])) {
    // ถ้าไม่มี session ให้เปลี่ยนเส้นทางไปหน้า user_login.php
    header("Location: ../users/user_login.php");
    exit(); // เพื่อหยุดการทำงานของสคริปต์หลังจากเปลี่ยนเส้นทาง
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $house_number = $_SESSION['house_number'];
    $months = intval($_POST['months']);
    $totalAmount = $months * 250; // สมมติว่าค่าส่วนกลางเดือนละ 250 บาท
    $paymentDate = date('Y-m-d'); // วันที่ปัจจุบัน
    $paymentMethod = 'transfer'; // วิธีชำระเงินเป็น "transfer"

    // ดึงเดือนที่ชำระล่าสุด
    $query = "SELECT MAX(end_month) AS lastPaidMonth, MAX(payment_year) AS lastPaidYear FROM payments WHERE house_number = ? AND status = 'approved'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $house_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $lastPaidMonth = intval($row['lastPaidMonth']);
    $lastPaidYear = intval($row['lastPaidYear']);

    // หากไม่เคยชำระเงิน กำหนดเดือนเริ่มต้นเป็นเดือนปัจจุบัน
    if ($lastPaidMonth == 0 || $lastPaidYear == 0) {
        $startMonth = date('n'); // ใช้เดือนปัจจุบัน
        $startYear = date('Y'); // ใช้ปีปัจจุบัน
    } else {
        // คำนวณเดือนถัดไปจากเดือนที่ชำระล่าสุด
        if ($lastPaidMonth >= 12) {
            $startMonth = 1;
            $startYear = $lastPaidYear + 1;
        } else {
            $startMonth = $lastPaidMonth + 1;
            $startYear = $lastPaidYear;
        }
    }

    // คำนวณเดือนสิ้นสุดตามจำนวนเดือนที่เลือก
    $endMonth = ($startMonth + $months - 1) % 12;
    $endYear = $startYear + intval(($startMonth + $months - 1) / 12);

    if ($endMonth == 0) {
        $endMonth = 12;
        $endYear--;
    }

    // ตรวจสอบและอัปโหลดสลิปการชำระเงิน
    if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] == 0) {
        $upload_dir = '../uploads/'; // โฟลเดอร์เก็บสลิป
        $extension = pathinfo($_FILES['payment_slip']['name'], PATHINFO_EXTENSION); // ดึงนามสกุลไฟล์

        // สร้างชื่อไฟล์ใหม่ที่สั้นลงด้วย uniqid()
        $file_name = uniqid() . '.' . $extension;
        $target_file = $upload_dir . $file_name;

        // ตรวจสอบขนาดของไฟล์ (จำกัดไม่เกิน 5MB)
        if ($_FILES['payment_slip']['size'] > 5 * 1024 * 1024) {
            die("ไฟล์มีขนาดใหญ่เกินไป (ไม่เกิน 5MB)");
        }

        // ตรวจสอบว่าเป็นไฟล์รูปภาพที่อนุญาต (jpg, png, gif)
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['payment_slip']['tmp_name']); // ดึงประเภทของไฟล์
        if (!in_array($file_type, $allowed_types)) {
            die("อนุญาตเฉพาะไฟล์ JPG, PNG, หรือ GIF เท่านั้น");
        }

        if (move_uploaded_file($_FILES['payment_slip']['tmp_name'], $target_file)) {
            // บันทึกไฟล์รูปภาพและประเภทไฟล์ลงในตาราง images
            $stmt = $conn->prepare("INSERT INTO images (file_name, image_type) VALUES (?, ?)");

            if (!$stmt) {
                die("Error preparing statement for inserting image: " . $conn->error);
            }

            $stmt->bind_param("ss", $file_name, $file_type); // เพิ่มประเภทไฟล์ลงไป
            if ($stmt->execute()) {
                // รับค่า id ของภาพที่เพิ่มล่าสุด
                $payment_proof_image_id = $stmt->insert_id;

                // บันทึกข้อมูลการชำระเงินลงในฐานข้อมูลในสถานะ 'pending'
                $stmt = $conn->prepare("INSERT INTO payments (house_number, amount, payment_date, payment_proof_image_id, start_month, end_month, payment_year, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

                if (!$stmt) {
                    die("Error preparing statement for inserting payment: " . $conn->error);
                }

                // เปลี่ยนการเก็บค่า startYear และ endYear ให้เป็นปี ค.ศ.
                $stmt->bind_param("sisiiiss", $house_number, $totalAmount, $paymentDate, $payment_proof_image_id, $startMonth, $endMonth, $endYear, $paymentMethod);

                if ($stmt->execute()) {
                    // ใช้ SweetAlert2 แจ้งเตือนและเปลี่ยนเส้นทางไปหน้า common_fees.php หลังจากชำระเงินเสร็จ
                    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'success',
                                title: 'การชำระเงินสำเร็จ',
                                text: 'การชำระเงินของคุณถูกบันทึกแล้ว และกำลังรอตรวจสอบจากเจ้าหน้าที่.',
                                showConfirmButton: false,
                                timer: 3000
                            }).then(() => {
                                window.location.href = '../fees/common_fees.php';
                            });
                        });
                    </script>";
                } else {
                    echo "เกิดข้อผิดพลาดในการบันทึกข้อมูลการชำระเงิน";
                }
                $stmt->close();
            } else {
                echo "เกิดข้อผิดพลาดในการบันทึกภาพสลิป";
            }
        } else {
            echo "ไม่สามารถอัปโหลดไฟล์ได้";
        }
    } else {
        echo "ไม่พบไฟล์ที่อัปโหลด";
    }

    $conn->close();
}
