<!DOCTYPE html>
<html>
<head>
    <title>Đăng nhập tài khoản</title>
    <link rel="stylesheet" href="css/dangnhap.css">
    </head>
<body>
    <h2>Đăng nhập tài khoản</h2>
    <form action="xuly_dangnhap.php" method="POST">
        <div>
            <label for="username_email">Tên đăng nhập hoặc Email:</label><br>
            <input type="text" id="username_email" name="username_email" required>
        </div>
        <div>
            <label for="password">Mật khẩu:</label><br>
            <input type="password" id="password" name="password" required>
        </div>
        <br>
        <button type="submit">Đăng nhập</button>
    </form>

    <p>Chưa có tài khoản? <a href="dangky.php">Đăng ký ngay</a>.</p>

  <?php
if (isset($_GET['registration']) && $_GET['registration'] == 'success') {
    echo "<p class='message success'>Đăng ký thành công! Vui lòng đăng nhập.</p>";
}
if (isset($_GET['error'])) {
    $error_message = '';
    if ($_GET['error'] == 'invalid_credentials') {
        $error_message = 'Tên đăng nhập/Email hoặc mật khẩu không đúng.';
    } elseif ($_GET['error'] == 'database_error') {
         $error_message = 'Có lỗi xảy ra trong quá trình xử lý. Vui lòng thử lại.';
    }
    echo "<p class='message error'>Lỗi: " . htmlspecialchars($error_message) . "</p>";
}
?>

</body>
</html>