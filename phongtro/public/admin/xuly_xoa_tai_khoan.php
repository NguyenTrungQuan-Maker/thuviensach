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


    $user_id_to_delete = $_POST['user_id'] ?? 0; 


    if (!filter_var($user_id_to_delete, FILTER_VALIDATE_INT) || $user_id_to_delete <= 0) {
        header("Location: quanly_tai_khoan.php?error=" . urlencode("ID tài khoản không hợp lệ."));
        $conn->close();
        exit();
    }

    if ($user_id_to_delete == $current_admin_id) {
        header("Location: quanly_tai_khoan.php?error=" . urlencode("Bạn không thể xóa chính tài khoản Admin mà bạn đang sử dụng."));
        $conn->close();
        exit();
    }

    $sqlCheckIfTargetIsAdmin = "SELECT is_admin FROM users WHERE ma_nguoi_dung = ?";
    $stmtCheckIfTargetIsAdmin = $conn->prepare($sqlCheckIfTargetIsAdmin);
    if ($stmtCheckIfTargetIsAdmin === false) {
        error_log("Admin Delete User Prepare check target is admin failed: " . $conn->error);
        header("Location: quanly_tai_khoan.php?error=" . urlencode("Có lỗi xảy ra khi kiểm tra tài khoản mục tiêu."));
        $conn->close();
        exit();
    }
    $stmtCheckIfTargetIsAdmin->bind_param('i', $user_id_to_delete);
    $stmtCheckIfTargetIsAdmin->execute();
    $resultIsAdmin = $stmtCheckIfTargetIsAdmin->get_result();
    $target_user_info = $resultIsAdmin->fetch_assoc();
    $resultIsAdmin->free();
    $stmtCheckIfTargetIsAdmin->close();

    if ($target_user_info && $target_user_info['is_admin'] == 1) {
        $sqlCountOtherAdmins = "SELECT COUNT(*) AS other_admins FROM users WHERE is_admin = 1 AND ma_nguoi_dung != ?";
        $stmtCountOtherAdmins = $conn->prepare($sqlCountOtherAdmins);
        $stmtCountOtherAdmins->bind_param('i', $user_id_to_delete);
        $stmtCountOtherAdmins->execute();
        $resultOtherAdmins = $stmtCountOtherAdmins->get_result();
        $rowOtherAdmins = $resultOtherAdmins->fetch_assoc();
        $resultOtherAdmins->free();
        $stmtCountOtherAdmins->close();

        if ($rowOtherAdmins['other_admins'] == 0) {
            header("Location: quanly_tai_khoan.php?error=" . urlencode("Không thể xóa tài khoản Admin cuối cùng. Vui lòng tạo ít nhất một Admin khác trước khi xóa."));
            $conn->close();
            exit();
        }
    }

    $sqlDelete = "DELETE FROM users WHERE ma_nguoi_dung = ?";

    $stmtDelete = $conn->prepare($sqlDelete);

    if ($stmtDelete === false) {
        error_log("Admin Delete User Prepare DELETE failed: " . $conn->error);
        header("Location: quanly_tai_khoan.php?error=" . urlencode("Có lỗi xảy ra khi chuẩn bị xóa tài khoản."));
        $conn->close();
        exit();
    }


    $bindSuccess = $stmtDelete->bind_param('i', $user_id_to_delete);
    if ($bindSuccess === false) {
        error_log("Admin Delete User Bind_param failed: " . $stmtDelete->error);
        header("Location: quanly_tai_khoan.php?error=" . urlencode("Có lỗi xảy ra khi gán tham số xóa."));
        $stmtDelete->close();
        $conn->close();
        exit();
    }

    // --- 6. Thực thi statement DELETE ---
    $executeSuccess = $stmtDelete->execute();

    if ($executeSuccess === false) {
        error_log("Admin Delete User Execute failed: " . $stmtDelete->error);
        header("Location: quanly_tai_khoan.php?error=" . urlencode("Có lỗi xảy ra khi xóa tài khoản. Vui lòng thử lại."));
        $stmtDelete->close();
        $conn->close();
        exit();
    }

    if ($stmtDelete->affected_rows > 0) {
        // Xóa thành công
        header("Location: quanly_tai_khoan.php?status=delete_success");
        exit();
    } else {
        // Không có dòng nào bị xóa (có thể ID không tồn tại hoặc lỗi khác)
        header("Location: quanly_tai_khoan.php?error=" . urlencode("Không tìm thấy tài khoản để xóa hoặc không có sự thay đổi."));
        exit();
    }

    // Đóng statement
    $stmtDelete->close();

} else {
    header("Location: quanly_tai_khoan.php?error=" . urlencode("Truy cập không hợp lệ."));
    exit();
}

// Đóng kết nối database
$conn->close();
?>