<?php

session_start();


if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=" . urlencode("Bạn cần đăng nhập với quyền Admin để thực hiện chức năng này."));
    exit();
}


require_once __DIR__ . '/../../config/db.php'; 


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $ten_day_du = $_POST['ten_day_du'] ?? null;
    $so_dien_thoai = $_POST['so_dien_thoai'] ?? null;
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    $ten_dang_nhap = $email;

    if (empty($email) || empty($password) || empty($confirm_password)) {
        header("Location: them_tai_khoan.php?error=" . urlencode("Vui lòng điền đầy đủ Email, Mật khẩu và Xác nhận mật khẩu."));
        $conn->close();
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: them_tai_khoan.php?error=" . urlencode("Định dạng Email không hợp lệ."));
        $conn->close();
        exit();
    }

    if ($password !== $confirm_password) {
        header("Location: them_tai_khoan.php?error=" . urlencode("Mật khẩu và Xác nhận mật khẩu không khớp."));
        $conn->close();
        exit();
    }

    $sqlCheckEmail = "SELECT ma_nguoi_dung FROM users WHERE email = ?";
    $stmtCheckEmail = $conn->prepare($sqlCheckEmail);
    if ($stmtCheckEmail === false) {
        error_log("Admin Add User Prepare Check Email failed: " . $conn->error);
        header("Location: them_tai_khoan.php?error=" . urlencode("Có lỗi xảy ra khi kiểm tra Email."));
        $conn->close();
        exit();
    }
    $stmtCheckEmail->bind_param('s', $email);
    $stmtCheckEmail->execute();
    $resultCheckEmail = $stmtCheckEmail->get_result();

    if ($resultCheckEmail->num_rows > 0) {
        $resultCheckEmail->free();
        $stmtCheckEmail->close();
        header("Location: them_tai_khoan.php?error=" . urlencode("Email này đã được sử dụng. Vui lòng chọn Email khác."));
        $conn->close();
        exit();
    }
    $resultCheckEmail->free();
    $stmtCheckEmail->close();
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    if ($hashed_password === false) {
         error_log("Admin Add User Password Hashing failed.");
         header("Location: them_tai_khoan.php?error=" . urlencode("Có lỗi xảy ra khi xử lý mật khẩu. Vui lòng thử lại."));
         $conn->close();
         exit();
    }

    $sqlInsert = "INSERT INTO users (email, mat_khau, ten_day_du, so_dien_thoai, is_admin, thoi_gian_tao, ten_dang_nhap) VALUES (?, ?, ?, ?, ?, NOW(), ?)";

    // --- 6. Sử dụng Prepared Statement ---
    $stmtInsert = $conn->prepare($sqlInsert);

    if ($stmtInsert === false) {
        error_log("Admin Add User Prepare INSERT failed: " . $conn->error);
        header("Location: them_tai_khoan.php?error=" . urlencode("Có lỗi xảy ra khi thêm tài khoản. Vui lòng thử lại."));
        $conn->close();
        exit();
    }

    $bindInsertSuccess = $stmtInsert->bind_param('ssssis',
        $email,
        $hashed_password,
        $ten_day_du,
        $so_dien_thoai,
        $is_admin,
        $ten_dang_nhap 
    );

     if ($bindInsertSuccess === false) {
         error_log("Admin Add User Bind_param failed: " . $stmtInsert->error);
         header("Location: them_tai_khoan.php?error=" . urlencode("Có lỗi xảy ra khi thêm tài khoản. Vui lòng thử lại."));
         $stmtInsert->close();
         $conn->close();
         exit();
     }


    // --- 7. Thực thi statement INSERT ---
    $executeInsertSuccess = $stmtInsert->execute();

     if ($executeInsertSuccess === false) {
         error_log("Admin Add User Execute failed: " . $stmtInsert->error);
         header("Location: them_tai_khoan.php?error=" . urlencode("Có lỗi xảy ra khi thêm tài khoản. Vui lòng thử lại."));
         $stmtInsert->close();
         $conn->close();
         exit();
     }

    if ($stmtInsert->affected_rows > 0) {
        // Thêm tài khoản thành công
        header("Location: quanly_tai_khoan.php?status=add_success");
        exit();
    } else {
        // Không có dòng nào được thêm
         error_log("Admin Add User INSERT affected_rows is 0 for email: " . $email);
         header("Location: them_tai_khoan.php?error=" . urlencode("Thêm tài khoản không thành công, không có dữ liệu được ghi."));
         exit();
    }

    $stmtInsert->close();

} else {
    header("Location: them_tai_khoan.php");
    exit();
}

// Đóng kết nối database
$conn->close();
?>