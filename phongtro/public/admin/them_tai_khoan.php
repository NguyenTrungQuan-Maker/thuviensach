<?php

session_start();


if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=" . urlencode("Bạn cần đăng nhập với quyền Admin để truy cập trang này."));
    exit();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Thêm tài khoản mới</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        h2 { text-align: center; }
        div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"], input[type="tel"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #5cb85c; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #4cae4c; }
         .error-message { color: red; text-align: center; margin-bottom: 10px; }
          .success-message { color: green; text-align: center; margin-bottom: 10px; }
          .dev-mode-note { color: orange; text-align: center; margin-bottom: 10px; }
          /* Định dạng chung cho các liên kết quay lại nằm trong thẻ <p> */
p a[href*="quanly_tai_khoan"], 
p a[href*="index.php"] {
    display: inline-block;
    padding: 10px 20px;
    background-color: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    transition: background-color 0.3s ease;
    margin-top: 10px;
}

/* Hiệu ứng hover cho các liên kết */
p a[href*="quanly_tai_khoan"]:hover,
p a[href*="index.php"]:hover {
    background-color: #2980b9;
}

    </style>
</head>
<body>
    <div class="container">
        <h2>Thêm tài khoản người dùng mới</h2>

         <?php
        // Hiển thị thông báo (ví dụ: sau khi submit form lỗi/thành công)
         if (isset($_GET['status'])) {
            if ($_GET['status'] == 'add_success') {
                echo '<p class="success-message">Thêm tài khoản thành công.</p>';
            }
        }
         if (isset($_GET['error'])) {
            echo '<p class="error-message">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>

        <form action="xuly_them_tai_khoan.php" method="POST">

            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="password">Mật khẩu:</label>
                <input type="password" id="password" name="password" required>
            </div>
             <div>
                <label for="confirm_password">Xác nhận mật khẩu:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
             <div>
                <label for="ten_day_du">Tên đầy đủ:</label>
                <input type="text" id="ten_day_du" name="ten_day_du">
            </div>
            <div>
                <label for="so_dien_thoai">Số điện thoại:</label>
                <input type="tel" id="so_dien_thoai" name="so_dien_thoai">
            </div>
             <div>
                 <label for="is_admin">Quyền Admin:</label>
                 <input type="checkbox" id="is_admin" name="is_admin" value="1"> <label for="is_admin" style="font-weight: normal;">Cấp quyền quản trị viên</label>
             </div>
            <br>
            <div>
                <button type="submit">Thêm tài khoản</button>
            </div>
        </form>

        <p style="margin-top: 20px;"><a href="quanly_tai_khoan.php">Quay lại danh sách tài khoản</a></p>
         <p><a href="index.php">Quay lại Dashboard</a></p>

    </div>
</body>
</html>