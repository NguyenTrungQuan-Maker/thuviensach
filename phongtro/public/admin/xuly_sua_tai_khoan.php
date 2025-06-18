<?php

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=" . urlencode("Bạn cần đăng nhập với quyền Admin để thực hiện chức năng này."));
    exit();
}

$current_admin_id = $_SESSION['admin_user_id'] ?? null;
if ($current_admin_id === null) {
    header("Location: quanly_tai_khoan.php?error=" . urlencode("Lỗi xác thực Admin."));
    exit();
}

require_once __DIR__ . '/../../config/db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $ma_nguoi_dung = $_POST['ma_nguoi_dung'] ?? 0;
    $email = $_POST['email'] ?? '';
    $ten_dang_nhap = $_POST['ten_dang_nhap'] ?? '';
    $ten_day_du = $_POST['ten_day_du'] ?? null;
    $so_dien_thoai = $_POST['so_dien_thoai'] ?? null;
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    if (!filter_var($ma_nguoi_dung, FILTER_VALIDATE_INT) || $ma_nguoi_dung <= 0) {
        header("Location: quanly_tai_khoan.php?error=" . urlencode("ID tài khoản không hợp lệ để sửa."));
        $conn->close();
        exit();
    }

    if (empty($email) || empty($ten_dang_nhap)) {
        header("Location: sua_tai_khoan.php?id=" . $ma_nguoi_dung . "&error=" . urlencode("Email và Tên đăng nhập không được để trống."));
        $conn->close();
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: sua_tai_khoan.php?id=" . $ma_nguoi_dung . "&error=" . urlencode("Định dạng Email không hợp lệ."));
        $conn->close();
        exit();
    }

    $update_password = false;
    $hashed_password = null;

    if (!empty($new_password)) { 
        if ($new_password !== $confirm_new_password) {
            header("Location: sua_tai_khoan.php?id=" . $ma_nguoi_dung . "&error=" . urlencode("Mật khẩu mới và Xác nhận mật khẩu mới không khớp."));
            $conn->close();
            exit();
        }
        // Hash mật khẩu mới
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        if ($hashed_password === false) {
             error_log("Admin Edit User Password Hashing failed for user ID: " . $ma_nguoi_dung);
             header("Location: sua_tai_khoan.php?id=" . $ma_nguoi_dung . "&error=" . urlencode("Có lỗi xảy ra khi xử lý mật khẩu mới."));
             $conn->close();
             exit();
        }
        $update_password = true; 
    }

    $sqlCheckDuplicate = "SELECT ma_nguoi_dung FROM users WHERE (email = ? OR ten_dang_nhap = ?) AND ma_nguoi_dung != ?";
    $stmtCheckDuplicate = $conn->prepare($sqlCheckDuplicate);
    if ($stmtCheckDuplicate === false) {
        error_log("Admin Edit User Prepare check duplicate failed: " . $conn->error);
        header("Location: sua_tai_khoan.php?id=" . $ma_nguoi_dung . "&error=" . urlencode("Có lỗi xảy ra khi kiểm tra trùng lặp."));
        $conn->close();
        exit();
    }
    $stmtCheckDuplicate->bind_param('ssi', $email, $ten_dang_nhap, $ma_nguoi_dung);
    $stmtCheckDuplicate->execute();
    $resultCheckDuplicate = $stmtCheckDuplicate->get_result();

    if ($resultCheckDuplicate->num_rows > 0) {
        header("Location: sua_tai_khoan.php?id=" . $ma_nguoi_dung . "&error=" . urlencode("Email hoặc Tên đăng nhập đã được sử dụng bởi tài khoản khác."));
        $resultCheckDuplicate->free();
        $stmtCheckDuplicate->close();
        $conn->close();
        exit();
    }
    $resultCheckDuplicate->free();
    $stmtCheckDuplicate->close();

    if ($ma_nguoi_dung == $current_admin_id) {
        $sqlCheckCurrentAdminStatus = "SELECT is_admin FROM users WHERE ma_nguoi_dung = ?";
        $stmtCheckCurrentAdminStatus = $conn->prepare($sqlCheckCurrentAdminStatus);
        $stmtCheckCurrentAdminStatus->bind_param('i', $current_admin_id);
        $stmtCheckCurrentAdminStatus->execute();
        $resultCurrentAdminStatus = $stmtCheckCurrentAdminStatus->get_result();
        $current_admin_status = $resultCurrentAdminStatus->fetch_assoc();
        $resultCurrentAdminStatus->free();
        $stmtCheckCurrentAdminStatus->close();

        if ($current_admin_status && $current_admin_status['is_admin'] == 1 && $is_admin == 0) {
            header("Location: sua_tai_khoan.php?id=" . $ma_nguoi_dung . "&error=" . urlencode("Bạn không thể tự gỡ bỏ quyền quản trị viên của mình."));
            $conn->close();
            exit();
        }
    } else {

        if ($is_admin == 0) { 
             $sqlCountOtherAdmins = "SELECT COUNT(*) AS other_admins FROM users WHERE is_admin = 1 AND ma_nguoi_dung != ?";
             $stmtCountOtherAdmins = $conn->prepare($sqlCountOtherAdmins);
             $stmtCountOtherAdmins->bind_param('i', $ma_nguoi_dung);
             $stmtCountOtherAdmins->execute();
             $resultOtherAdmins = $stmtCountOtherAdmins->get_result();
             $rowOtherAdmins = $resultOtherAdmins->fetch_assoc();
             $resultOtherAdmins->free();
             $stmtCountOtherAdmins->close();

             if ($rowOtherAdmins['other_admins'] == 0) {
                 header("Location: sua_tai_khoan.php?id=" . $ma_nguoi_dung . "&error=" . urlencode("Không thể hạ cấp tài khoản Admin cuối cùng thành người dùng thường."));
                 $conn->close();
                 exit();
             }
        }
    }



    $sql_fields = "email = ?, ten_dang_nhap = ?, ten_day_du = ?, so_dien_thoai = ?, is_admin = ?";
    $bind_types = 'ssssi';
    $bind_params = [&$email, &$ten_dang_nhap, &$ten_day_du, &$so_dien_thoai, &$is_admin];

    if ($update_password) {
        $sql_fields .= ", mat_khau = ?";
        $bind_types .= 's'; 
        $bind_params[] = &$hashed_password;
    }

    $sql_fields .= " WHERE ma_nguoi_dung = ?";
    $bind_types .= 'i';
    $bind_params[] = &$ma_nguoi_dung; 

    $sqlUpdate = "UPDATE users SET " . $sql_fields;

    // --- 7. Sử dụng Prepared Statement ---
    $stmtUpdate = $conn->prepare($sqlUpdate);

    if ($stmtUpdate === false) {
        error_log("Admin Edit User Prepare UPDATE failed: " . $conn->error);
        header("Location: sua_tai_khoan.php?id=" . $ma_nguoi_dung . "&error=" . urlencode("Có lỗi xảy ra khi chuẩn bị cập nhật tài khoản."));
        $conn->close();
        exit();
    }

    $bindSuccess = $stmtUpdate->bind_param($bind_types, ...$bind_params);

    if ($bindSuccess === false) {
        error_log("Admin Edit User Bind_param failed: " . $stmtUpdate->error);
        header("Location: sua_tai_khoan.php?id=" . $ma_nguoi_dung . "&error=" . urlencode("Có lỗi xảy ra khi gán tham số cập nhật."));
        $stmtUpdate->close();
        $conn->close();
        exit();
    }


    $executeSuccess = $stmtUpdate->execute();

    if ($executeSuccess === false) {
        error_log("Admin Edit User Execute failed: " . $stmtUpdate->error);
        header("Location: sua_tai_khoan.php?id=" . $ma_nguoi_dung . "&error=" . urlencode("Có lỗi xảy ra khi cập nhật tài khoản. Vui lòng thử lại."));
        $stmtUpdate->close();
        $conn->close();
        exit();
    }


    if ($stmtUpdate->affected_rows > 0) {
        // Cập nhật thành công
        header("Location: quanly_tai_khoan.php?status=edit_success");
        exit();
    } else {

        header("Location: quanly_tai_khoan.php?status=edit_no_change"); 
        exit();
    }

    // Đóng statement
    $stmtUpdate->close();

} else {
    header("Location: quanly_tai_khoan.php?error=" . urlencode("Truy cập không hợp lệ."));
    exit();
}

// Đóng kết nối database
$conn->close();
?>