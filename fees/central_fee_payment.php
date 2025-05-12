<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระค่าส่วนกลาง</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }

        .content {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 20px auto;
        }

        .pay-button {
            margin-top: 20px;
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            text-align: center;
            width: 100%;
            font-size: 18px;
            transition: background-color 0.3s ease;
        }

        .pay-button:hover {
            background-color: #0056b3;
        }

        .back-button {
            margin-top: 10px;
            background-color: #6c757d;
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            text-align: center;
            width: 100%;
            font-size: 18px;
            transition: background-color 0.3s ease;
        }

        .back-button:hover {
            background-color: #5a6268;
        }

        .qr-code {
            margin-top: 20px;
            text-align: center;
        }

        .qr-code img {
            margin-bottom: 15px;
            max-width: 100%; /* เพิ่มการแสดงผลขนาดเต็มหน้าจอสำหรับอุปกรณ์เล็ก */
            height: auto;
        }

        .img-preview {
            max-width: 100%;
            max-height: 300px; /* เพิ่มการจำกัดความสูง */
            margin-top: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        label {
            margin-top: 15px;
        }

        #totalAmount {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        h5 {
            font-weight: bold;
        }

        .form-control {
            height: 45px;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .content {
                padding: 20px;
            }

            .pay-button, .back-button {
                font-size: 16px;
            }
        }

        .save-qr-button {
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            text-align: center;
            font-size: 18px;
            text-decoration: none;
            transition: background-color 0.3s ease;
            display: block;
            width: 100%;
            margin-top: 15px;
        }

        .save-qr-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="content">
        <h5>เลือกจำนวนเดือนที่ต้องการชำระ</h5>
        <form id="monthForm" method="POST" action="upload_payment_slip.php" enctype="multipart/form-data">
            <label for="months">จำนวนเดือนที่ต้องการชำระ:</label>
            <input type="number" id="months" name="months" class="form-control" min="1" max="12" required>
            <p id="totalAmount">จำนวนเงินที่ต้องชำระ: 0 บาท</p>

            <div class="qr-code" id="qrCodeSection">
                <h5>โอนเงินผ่าน QR Code</h5>
                <img src="../QRcode/qrcode.jpg" alt="QR Code" width="200">
                <!-- ปุ่มสำหรับบันทึก QR Code -->
                <a href="../QRcode/qrcode.jpg" download="qrcode.jpg" class="save-qr-button">บันทึก QR Code</a>
            </div>

            <label for="payment_slip">อัปโหลดสลิปการโอนเงิน:</label>
            <input type="file" id="payment_slip" name="payment_slip" class="form-control" accept="image/*" required>
            <img id="imagePreview" class="img-preview" src="" alt="Image Preview" style="display: none;">

            <button type="button" class="pay-button" onclick="confirmPayment()">ยืนยันการชำระเงิน</button>
        </form>

        <!-- ปุ่มย้อนกลับ -->
        <button type="button" class="back-button" onclick="goBack()">ย้อนกลับ</button>
    </div>

    <!-- เพิ่มการตรวจสอบในฟอร์ม -->
    <script>
        function goBack() {
            window.history.back(); // คำสั่งสำหรับย้อนกลับไปยังหน้าที่แล้ว
        }

        function confirmPayment() {
            const months = parseInt(document.getElementById('months').value);
            const fileInput = document.getElementById('payment_slip');
            const file = fileInput.files[0];
            const fileReader = new FileReader();
            const allowedFileTypes = ['image/jpeg', 'image/png', 'image/gif'];

            if (isNaN(months) || months < 1 || months > 12) {
                Swal.fire({
                    icon: 'error',
                    title: 'ข้อมูลไม่ถูกต้อง',
                    text: 'กรุณากรอกจำนวนเดือนที่ถูกต้อง (1-12)'
                });
                return false;
            }

            if (!file) {
                Swal.fire({
                    icon: 'error',
                    title: 'ไฟล์ไม่ถูกต้อง',
                    text: 'กรุณาอัปโหลดสลิปการชำระเงิน'
                });
                return false;
            }

            if (!allowedFileTypes.includes(file.type)) {
                Swal.fire({
                    icon: 'error',
                    title: 'ประเภทไฟล์ไม่ถูกต้อง',
                    text: 'อนุญาตเฉพาะไฟล์ JPG, PNG, หรือ GIF เท่านั้น'
                });
                return false;
            }

            if (file.size > 5 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    title: 'ขนาดไฟล์ใหญ่เกินไป',
                    text: 'ขนาดไฟล์เกิน 5MB กรุณาอัปโหลดไฟล์ขนาดเล็กกว่า'
                });
                return false;
            }

            fileReader.onload = function(e) {
                Swal.fire({
                    title: 'ยืนยันการชำระเงิน',
                    html: `
                        <p>คุณได้เลือกชำระจำนวน ${months} เดือน</p>
                        <p>จำนวนเงินที่ต้องชำระทั้งหมด: ${months * 250} บาท</p>
                        <img src="${e.target.result}" alt="Image Preview" class="img-preview" />
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก',
                    preConfirm: () => {
                        document.getElementById('monthForm').submit(); // ส่งฟอร์มหลังจากยืนยัน
                    }
                });
            };
            
            // แสดงตัวอย่างรูปภาพ
            fileReader.readAsDataURL(file);
        }

        // แสดงจำนวนเงินตามจำนวนเดือนที่เลือก
        document.getElementById('months').addEventListener('input', function() {
            const pricePerMonth = 250;
            const months = parseInt(this.value);
            if (!isNaN(months) && months > 0) {
                const total = months * pricePerMonth;
                document.getElementById('totalAmount').innerText = "จำนวนเงินที่ต้องชำระ: " + total + " บาท";
                document.getElementById('qrCodeSection').style.display = 'block';
            } else {
                document.getElementById('totalAmount').innerText = "จำนวนเงินที่ต้องชำระ: 0 บาท";
                document.getElementById('qrCodeSection').style.display = 'none';
            }
        });

        // แสดงตัวอย่างรูปเมื่อเลือกไฟล์
        document.getElementById('payment_slip').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('imagePreview').style.display = 'none';
            }
        });
    </script>

    <!-- เพิ่ม SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
