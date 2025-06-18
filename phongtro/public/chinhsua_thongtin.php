<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: dangnhap.php?error=not_logged_in");
    exit();
}

require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user_id'];

$sql = "SELECT ten_dang_nhap, email, so_dien_thoai, ten_day_du, dia_chi, anh_dai_dien FROM users WHERE ma_nguoi_dung = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Lỗi chuẩn bị truy vấn lấy thông tin người dùng: " . $conn->error);
}

$stmt->bind_param('i', $user_id);

$executeSuccess = $stmt->execute();

if ($executeSuccess === false) {
 
     die("Lỗi thực thi truy vấn lấy thông tin người dùng: " . $stmt->error);
}

// Lấy kết quả
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user_data = $result->fetch_assoc();
} else {
    session_unset(); // Xóa tất cả biến session
    session_destroy(); // Hủy session
    header("Location: dangnhap.php?error=user_not_found");
    exit();
}

$stmt->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Chỉnh sửa thông tin tài khoản</title>
    <link rel="stylesheet" href="css/chinh_sua_thong_tin.css">

    </head>
<body>
    <h2>Chỉnh sửa thông tin tài khoản</h2>

    <?php
    if (isset($_GET['status'])) {
        if ($_GET['status'] == 'success_update_profile') {
            echo "<p style='color: green;'>Cập nhật thông tin tài khoản thành công!</p>";
        } elseif ($_GET['status'] == 'no_change_profile') {
             echo "<p style='color: orange;'>Không có thông tin nào thay đổi.</p>";
        }
    }
     if (isset($_GET['error'])) {
        $error_message = htmlspecialchars($_GET['error']); 
        echo "<p style='color: red;'>Lỗi: " . $error_message . "</p>";
    }
    ?>


    <?php
    // Hiển thị ảnh đại diện hiện tại (nếu có)
    if (!empty($user_data['anh_dai_dien'])) {
        $avatar_path = '/phongtro/public' . htmlspecialchars($user_data['anh_dai_dien']);
             echo '<img src="' . $avatar_path . '" alt="Ảnh đại diện" style="max-width: 100px; height: auto; margin-bottom: 10px;">';

    } else {

         echo '<p>Chưa có ảnh đại diện</p>';
    }
    ?>


    <form action="xuly_chinhsua_thongtin.php" method="POST" enctype="multipart/form-data">

        <div>
            <label for="username">Tên đăng nhập:</label><br>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_data['ten_dang_nhap']); ?>" disabled>
        </div>
         <div>
            <label for="email">Email:</label><br>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
        </div>

        <div>
            <label for="full_name">Tên đầy đủ:</label><br>
            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['ten_day_du']); ?>">
        </div>
         <div>
            <label for="phone">Số điện thoại:</label><br>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['so_dien_thoai']); ?>">
        </div>
        <div>
          <label for="profile_picture">Chọn ảnh đại diện mới:</label><br>
            <input type="file" id="profile_picture" name="profile_picture" accept="image/*"> </div>

        <br>
        <button type="submit">Cập nhật thông tin</button>
    </form> <p><a href="doi_matkhau.php">Đổi mật khẩu</a></p> <p><a href="index.php">Trang chủ</a></p> </body>
</html>