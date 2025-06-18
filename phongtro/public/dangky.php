<!DOCTYPE html>
<html>
<head>
    <title>Đăng ký tài khoản</title>
     <link rel="stylesheet" href="css/dangky.css">
    </head>
<body>
    <h2>Đăng ký tài khoản mới</h2>
    <form action="xuly_dangky.php" method="POST">
        <div>
            <label for="username">Tên đăng nhập:</label><br>
            <input type="text" id="username" name="username" required>
        </div>
        <div>
            <label for="email">Email:</label><br>
            <input type="email" id="email" name="email" required>
        </div>
        <div>
            <label for="password">Mật khẩu:</label><br>
            <input type="password" id="password" name="password" required>
        </div>
         <div>
            <label for="full_name">Tên đầy đủ:</label><br>
            <input type="text" id="full_name" name="full_name">
        </div>
         <div>
            <label for="phone">Số điện thoại:</label><br>
            <input type="text" id="phone" name="phone">
        </div>
         <div>
            <label for="address">Địa chỉ:</label><br>
            <input type="text" id="address" name="address">
        </div>
        <br>
        <button type="submit">Đăng ký</button>
    </form>
</body>
</html>