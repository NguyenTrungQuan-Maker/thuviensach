<?php
session_start();

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php"); // index.php trong thư mục public/admin
    exit();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        h2 { text-align: center; }
        div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #5cb85c; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #4cae4c; }
        p { text-align: center; }
        a { color: #337ab7; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .error-message { color: red; text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Admin Login</h2>

        <?php
        // Hiển thị thông báo lỗi (nếu có từ xuly_login.php)
        if (isset($_GET['error'])) {
            echo '<p class="error-message">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>

        <form action="xuly_login.php" method="POST">
            <div>
                <label for="username">Tên đăng nhập (Email):</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div>
                <label for="password">Mật khẩu:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div>
                <button type="submit">Đăng nhập Admin</button>
            </div>
        </form>

        <p><a href="../index.php">Quay lại trang chủ</a></p> </div>

</body>
</html>