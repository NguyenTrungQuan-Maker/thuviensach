<?php

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=" . urlencode("Bạn cần đăng nhập với quyền Admin để truy cập trang này."));
    exit();
}

$current_admin_id = $_SESSION['admin_user_id'] ?? null;

require_once __DIR__ . '/../../config/db.php'; 

$user_info = null;
$user_id = $_GET['id'] ?? 0; 

if (!filter_var($user_id, FILTER_VALIDATE_INT) || $user_id <= 0) {
    header("Location: quanly_tai_khoan.php?error=" . urlencode("ID tài khoản không hợp lệ."));
    $conn->close();
    exit();
}


$sql = "SELECT ma_nguoi_dung, email, ten_dang_nhap, ten_day_du, so_dien_thoai, is_admin FROM users WHERE ma_nguoi_dung = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    error_log("Admin Edit User Prepare SELECT failed: " . $conn->error);
    header("Location: quanly_tai_khoan.php?error=" . urlencode("Có lỗi xảy ra khi chuẩn bị lấy thông tin tài khoản."));
    $conn->close();
    exit();
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user_info = $result->fetch_assoc();
} else {

    header("Location: quanly_tai_khoan.php?error=" . urlencode("Không tìm thấy tài khoản người dùng."));
    $stmt->close();
    $conn->close();
    exit();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Sửa tài khoản</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        h2 { text-align: center; }
        div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="tel"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .error-message { color: red; text-align: center; margin-bottom: 10px; }
        .success-message { color: green; text-align: center; margin-bottom: 10px; }
        hr { border: 0; border-top: 1px solid #eee; margin: 20px 0; }
        h3 { margin-top: 25px; margin-bottom: 15px; color: #555;}
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
        <h2>Sửa tài khoản người dùng</h2>

        <?php
        if (isset($_GET['error'])) {
            echo '<p class="error-message">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>

        <?php if ($user_info): ?>
            <form action="xuly_sua_tai_khoan.php" method="POST">
                <input type="hidden" name="ma_nguoi_dung" value="<?php echo htmlspecialchars($user_info['ma_nguoi_dung']); ?>">

                <div>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                </div>
                <div>
                    <label for="ten_dang_nhap">Tên đăng nhập:</label>
                    <input type="text" id="ten_dang_nhap" name="ten_dang_nhap" value="<?php echo htmlspecialchars($user_info['ten_dang_nhap']); ?>" required>
                </div>
                <div>
                    <label for="ten_day_du">Tên đầy đủ:</label>
                    <input type="text" id="ten_day_du" name="ten_day_du" value="<?php echo htmlspecialchars($user_info['ten_day_du'] ?? ''); ?>">
                </div>
                <div>
                    <label for="so_dien_thoai">Số điện thoại:</label>
                    <input type="tel" id="so_dien_thoai" name="so_dien_thoai" value="<?php echo htmlspecialchars($user_info['so_dien_thoai'] ?? ''); ?>">
                </div>
                <div>
                    <label for="is_admin">Quyền Admin:</label>
                    <input type="checkbox" id="is_admin" name="is_admin" value="1" <?php echo ($user_info['is_admin'] == 1) ? 'checked' : ''; ?>>
                    <label for="is_admin" style="font-weight: normal;">Cấp quyền quản trị viên</label>
                    <?php if ($user_info['ma_nguoi_dung'] == $current_admin_id): ?>
                        <br><small style="color: gray;">(Bạn không thể tự sửa quyền Admin của mình tại đây)</small>
                    <?php endif; ?>
                </div>

                <hr>
                <h3>Thay đổi mật khẩu (chỉ nhập nếu muốn thay đổi)</h3>
                <div>
                    <label for="new_password">Mật khẩu mới:</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Để trống nếu không muốn thay đổi mật khẩu">
                </div>
                <div>
                    <label for="confirm_new_password">Xác nhận mật khẩu mới:</label>
                    <input type="password" id="confirm_new_password" name="confirm_new_password" placeholder="Nhập lại mật khẩu mới">
                </div>
                <hr>
                <br>
                <div>
                    <button type="submit">Cập nhật tài khoản</button>
                </div>
            </form>
        <?php endif; ?>

        <p style="margin-top: 20px;"><a href="quanly_tai_khoan.php">Quay lại danh sách tài khoản</a></p>
        <p><a href="index.php">Quay lại Dashboard</a></p>

    </div>
</body>
</html>