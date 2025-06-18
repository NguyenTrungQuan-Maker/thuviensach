<?php

session_start();

require_once __DIR__ . '/../../config/db.php'; 

$conn->set_charset("utf8mb4");


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $input_username = $_POST['username'] ?? ''; 
    $input_password = $_POST['password'] ?? ''; 

    if (empty($input_username) || empty($input_password)) {
         header("Location: login.php?error=" . urlencode("Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu."));
         $conn->close(); // Đóng kết nối
         exit();
    }
    $sql = "SELECT ma_nguoi_dung, mat_khau, is_admin FROM users WHERE email = ?";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        error_log("Admin Login Prepare failed: " . $conn->error);
        header("Location: login.php?error=" . urlencode("Có lỗi xảy ra trong quá trình xác thực. Vui lòng thử lại."));
        $conn->close(); // Đóng kết nối
        exit();
    }
    $bindSuccess = $stmt->bind_param('s', $input_username);

     // Kiểm tra lỗi bind_param
     if ($bindSuccess === false) {
         error_log("Admin Login Bind_param failed: " . $stmt->error);
         header("Location: login.php?error=" . urlencode("Có lỗi xảy ra trong quá trình xác thực. Vui lòng thử lại."));
         $stmt->close();
         $conn->close(); 
         exit();
     }
    $executeSuccess = $stmt->execute();

     if ($executeSuccess === false) {
         error_log("Admin Login Execute failed: " . $stmt->error);
         header("Location: login.php?error=" . urlencode("Có lỗi xảy ra trong quá trình xác thực. Vui lòng thử lại."));
         $stmt->close(); // Đóng statement
         $conn->close(); // Đóng kết nối
         exit();
     }

    // Lấy kết quả
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc(); 

        if (password_verify($input_password, $user['mat_khau'])) {

            // --- 6. Kiểm tra quyền Admin ---
            if ($user['is_admin'] === 1) { 
                $_SESSION['admin_logged_in'] = true; 
                $_SESSION['admin_user_id'] = $user['ma_nguoi_dung']; 

                header("Location: index.php"); 
                exit();

            } else {
            
                 header("Location: login.php?error=" . urlencode("Bạn không có quyền truy cập Admin."));
            }

        } else {
            // Mật khẩu không khớp
            header("Location: login.php?error=" . urlencode("Sai tên đăng nhập hoặc mật khẩu."));
        }

    } else {
        // Không tìm thấy người dùng nào với email đã nhập
        header("Location: login.php?error=" . urlencode("Sai tên đăng nhập hoặc mật khẩu."));
    }

    $result->free();
    $stmt->close();

} else {

    header("Location: login.php");
    exit();
}

// Đóng kết nối database
$conn->close();
?>