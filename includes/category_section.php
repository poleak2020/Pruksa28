<div class="category-section">
    <div class="category-item" onclick="window.location.href='../fees/common_fees.php'">
        <i class="bi bi-cash-stack"></i> <!-- ไอคอนสำหรับค่าส่วนกลาง -->
        <p>ค่าส่วนกลาง</p>
    </div>
    <div class="category-item" onclick="window.location.href='../report/report_issue.php'">
        <i class="bi bi-exclamation-circle"></i> <!-- ไอคอนสำหรับแจ้งปัญหา -->
        <p>แจ้งปัญหา</p>
    </div>
</div>
<style>
    .category-section {
        text-align: center;
        margin-top: 20px;
    }
    .category-item {
        display: inline-block;
        width: 45%;
        padding: 20px;
        margin: 10px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        text-align: center;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .category-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    .category-item i {
        font-size: 50px;
        color: #7BC59D;
        margin-bottom: 10px;
    }
</style>
