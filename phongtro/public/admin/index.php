<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=" . urlencode("Bạn cần đăng nhập với quyền Admin để truy cập trang này."));
    exit();
}

$admin_user_id = $_SESSION['admin_user_id'] ?? null; 

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
      /* Reset cơ bản */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background-color: #f4f6f8;
    color: #333;
    line-height: 1.6;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 40px 20px;
}

.container {
    background-color: #fff;
    width: 100%;
    max-width: 600px;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    padding: 30px 40px;
}

h2 {
    color: #007BFF;
    margin-bottom: 15px;
    font-weight: 700;
    text-align: center;
    letter-spacing: 1px;
}

h3 {
    margin-top: 30px;
    margin-bottom: 15px;
    border-bottom: 2px solid #007BFF;
    padding-bottom: 5px;
    color: #0056b3;
}

p {
    font-size: 1.1rem;
    margin-bottom: 15px;
    text-align: center;
}

ul {
    list-style-type: none;
    padding-left: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

ul li a {
    display: block;
    text-decoration: none;
    padding: 12px 20px;
    background-color: #007BFF;
    color: white;
    border-radius: 6px;
    font-weight: 600;
    transition: background-color 0.3s ease;
    text-align: center;
}

ul li a:hover {
    background-color: #0056b3;
}

.container > p:last-child a {
    display: inline-block;
    margin-top: 30px;
    text-decoration: none;
    color: #007BFF;
    font-weight: 600;
    border-bottom: 1.5px solid transparent;
    transition: border-color 0.3s ease;
}

.container > p:last-child a:hover {
    border-color: #007BFF;
}

    </style>
</head>
<body>
    <div class="container">
        <h2>Admin Dashboard</h2>
         <?php if (isset($admin_info['email'])): ?>
            <p>Chào mừng Admin: <?php echo htmlspecialchars($admin_info['email']); ?></p>
        <?php else: ?>
             <p>Chào mừng đến với Admin Dashboard!</p>
        <?php endif; ?>


        <h3>Các chức năng quản trị:</h3>
        <ul>
            <li><a href="quanly_tai_khoan.php">Quản lý tài khoản người dùng</a></li> <li><a href="quanly_tin_dang.php">Quản lý tin đăng</a></li> <li><a href="thong_ke.php">Thống kê</a></li> <li><a href="logout.php">Đăng xuất Admin</a></li> </ul>

        <p style="margin-top: 20px;"><a href="../index.php">Xem trang chủ</a></p>
    </div>

</body>
</html>