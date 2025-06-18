<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: dangnhap.php?error=not_logged_in");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Đổi mật khẩu</title>
    <link rel="stylesheet" href="css/doi_matkhau.css">

    </head>
<body>
    <h2>Đổi mật khẩu</h2>

    <?php
    if (isset($_GET['status']) && $_GET['status'] == 'success') {
        echo "<p style='color: green;'>Đổi mật khẩu thành công!</p>";
    }
     if (isset($_GET['error'])) {
        $error_message = '';
        if ($_GET['error'] == 'invalid_old_password') {
            $error_message = 'Mật khẩu cũ không đúng.';
        } elseif ($_GET['error'] == 'new_password_mismatch') {
            $error_message = 'Mật khẩu mới và xác nhận mật khẩu không khớp.';
        } elseif ($_GET['error'] == 'password_too_short') {
            $error_message = 'Mật khẩu mới quá ngắn.';
        } elseif ($_GET['error'] == 'database_error') {
             $error_message = 'Có lỗi xảy ra khi cập nhật mật khẩu.';
        }
        echo "<p style='color: red;'>Lỗi: " . htmlspecialchars($error_message) . "</p>";
    }
    ?>

    <form action="xuly_doi_matkhau.php" method="POST">
        <div>
            <label for="current_password">Mật khẩu cũ:</label><br>
            <input type="password" id="current_password" name="current_password" required>
        </div>
        <div>
            <label for="new_password">Mật khẩu mới:</label><br>
            <input type="password" id="new_password" name="new_password" required>
        </div>
        <div>
            <label for="confirm_password">Nhập lại mật khẩu mới:</label><br>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <br>
        <button type="submit">Đổi mật khẩu</button>
    </form>

    <p><a href="chinhsua_thongtin.php">Quay lại trang chỉnh sửa thông tin</a></p>
    <p><a href="index.php">Trang chủ</a></p>

</body>
</html>