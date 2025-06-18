<?php

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=" . urlencode("Bạn cần đăng nhập với quyền Admin để truy cập trang này."));
    exit();
}
$admin_user_id = $_SESSION['admin_user_id'] ?? null;

require_once __DIR__ . '/../../config/db.php'; 

$sql = "SELECT ma_nguoi_dung, email, ten_day_du, so_dien_thoai, is_admin, thoi_gian_tao FROM users ORDER BY thoi_gian_tao DESC";

$result = $conn->query($sql);

$users = [];
if ($result) {
    if ($result->num_rows > 0) {
        // Lấy tất cả bản ghi vào một mảng
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    $result->free(); // Giải phóng bộ nhớ
} else {
     error_log("Admin Quan Ly Tai Khoan SELECT failed: " . $conn->error);
     echo "Lỗi truy vấn danh sách tài khoản."; // Hiển thị lỗi cho Admin
}


// Đóng kết nối database
$conn->close();

// ... (phần còn lại của code HTML)
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Quản lý tài khoản người dùng</title>
    <style>
    /* Reset */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f4f7f9;
        color: #333;
        line-height: 1.6;
    }

    .container {
        max-width: 1000px;
        margin: 50px auto;
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    h2 {
        text-align: center;
        margin-bottom: 30px;
        color: #2c3e50;
    }

    .dev-mode-note {
        color: orange;
        text-align: center;
        margin-bottom: 10px;
    }

    .add-user-link a {
        display: inline-block;
        background-color: #3498db;
        color: white;
        padding: 10px 18px;
        text-decoration: none;
        border-radius: 5px;
        transition: background-color 0.3s ease;
        font-weight: bold;
    }

    .add-user-link a:hover {
        background-color: #2980b9;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th, td {
        padding: 12px 15px;
        border-bottom: 1px solid #ddd;
        text-align: left;
    }

    th {
        background-color: #f8f9fa;
        font-weight: bold;
        color: #2c3e50;
    }

    tr:hover {
        background-color: #f1f1f1;
    }

    .actions a,
    .actions button,
    .actions span {
        font-size: 0.9rem;
        margin-right: 8px;
        text-decoration: none;
        color: #3498db;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
    }

    .actions button:hover,
    .actions a:hover {
        text-decoration: underline;
    }

    .actions button[type="submit"] {
        color: red;
    }

    .actions span {
        color: #aaa;
        cursor: not-allowed;
    }

    .error-message {
        color: #e74c3c;
        background: #fdecea;
        padding: 10px;
        text-align: center;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .success-message {
        color: #27ae60;
        background: #eafaf1;
        padding: 10px;
        text-align: center;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    a {
        color: #3498db;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }

    .footer-link {
        margin-top: 20px;
        display: block;
        text-align: center;
        color: #555;
    }
</style>

    </style>
</head>
<body>
    <div class="container">
        <h2>Quản lý tài khoản người dùng</h2>
         <?php
        // Hiển thị thông báo (ví dụ: sau khi thêm, sửa, xóa, reset mk thành công/lỗi)
         if (isset($_GET['status'])) {
            if ($_GET['status'] == 'add_success') {
                echo '<p class="success-message">Thêm tài khoản người dùng thành công.</p>';
            }
             // Thêm các thông báo khác ở đây (edit_success, delete_success, reset_password_success...)
        }
         if (isset($_GET['error'])) {
            echo '<p class="error-message">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>


        <div class="add-user-link">
            <a href="them_tai_khoan.php">Thêm tài khoản mới</a>
        </div>


        <?php if (!empty($users)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Tên đầy đủ</th>
                        <th>Số điện thoại</th>
                        <th>Admin</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th> </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['ma_nguoi_dung']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['ten_day_du']); ?></td>
                            <td><?php echo htmlspecialchars($user['so_dien_thoai']); ?></td>
                            <td><?php echo $user['is_admin'] ? 'Có' : 'Không'; ?></td> <td><?php echo htmlspecialchars($user['thoi_gian_tao']); ?></td>
                            <td class="actions">
                                <a href="sua_tai_khoan.php?id=<?php echo $user['ma_nguoi_dung']; ?>">Sửa</a> |

                                <form action="xuly_xoa_tai_khoan.php" method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tài khoản này không?');">
                                     <input type="hidden" name="user_id" value="<?php echo $user['ma_nguoi_dung']; ?>">
                                    <?php
                                    // Kiểm tra xem đây có phải tài khoản Admin đang đăng nhập KHÔNG
                                    $is_current_admin = ($admin_user_id !== null && $user['ma_nguoi_dung'] === $admin_user_id);
                                  
                                    if (!$is_current_admin): // Nếu KHÔNG phải Admin đang đăng nhập
                                        ?>
                                        <button type="submit" style="color: red; background: none; border: none; cursor: pointer; padding: 0;">Xóa</button> |
                                     <?php else: ?>
                                          <span style="color: #999;">Xóa</span> | <?php endif; ?>
                                 </form>

                                <form action="xuly_reset_mat_khau.php" method="POST" style="display:inline;" onsubmit="return confirm('Đặt lại mật khẩu sẽ tạo mật khẩu mới ngẫu nhiên. Tiếp tục?');">
                                     <input type="hidden" name="user_id" value="<?php echo $user['ma_nguoi_dung']; ?>">
                                      <?php if (!$is_current_admin): // Không cho admin tự reset mật khẩu của mình ở đây?>
                                         <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0;">Reset Mật khẩu</button>
                                       <?php else: ?>
                                         <span style="color: #999;">Reset Mật khẩu</span>
                                       <?php endif; ?>
                                 </form>


                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Chưa có tài khoản người dùng nào trong hệ thống.</p>
        <?php endif; ?>

        <p style="margin-top: 20px;"><a href="index.php">Quay lại Dashboard</a></p>

    </div>
</body>
</html>