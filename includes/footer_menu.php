<div class="footer-menu">
    <a href="../main/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
        <i class="bi bi-grid"></i>
        บริการ
    </a>
    <a href="../main/messages.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>">
        <i class="bi bi-envelope"></i>
        ข้อความ
    </a>
    <a href="../main/settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
        <i class="bi bi-gear"></i>
        ตั้งค่า
    </a>
</div>

<style>
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
