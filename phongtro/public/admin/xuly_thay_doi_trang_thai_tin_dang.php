<?php

session_start();


if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=" . urlencode("Bạn cần đăng nhập với quyền Admin để thực hiện chức năng này."));
    exit();
}

require_once __DIR__ . '/../../config/db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ma_phong = $_POST['ma_phong'] ?? 0;
    $trang_thai_moi = $_POST['trang_thai_moi'] ?? '';

    if (!filter_var($ma_phong, FILTER_VALIDATE_INT) || $ma_phong <= 0) {
        header("Location: quanly_tin_dang.php?error=" . urlencode("ID tin đăng không hợp lệ."));
        exit();
    }

    $valid_statuses = ['available', 'hidden', 'rented'];
    if (!in_array($trang_thai_moi, $valid_statuses)) {
        header("Location: quanly_tin_dang.php?error=" . urlencode("Trạng thái không hợp lệ."));
        exit();
    }

    try {
        $sqlUpdateStatus = "UPDATE rooms SET trang_thai = ?, thoi_gian_cap_nhat = NOW() WHERE ma_phong = ?";
        $stmtUpdateStatus = $conn->prepare($sqlUpdateStatus);
        if ($stmtUpdateStatus === false) {
            throw new Exception("Lỗi prepare SQL Update Status: " . $conn->error);
        }
        $stmtUpdateStatus->bind_param("si", $trang_thai_moi, $ma_phong);

        if (!$stmtUpdateStatus->execute()) {
            throw new Exception("Lỗi thực thi Update Status: " . $stmtUpdateStatus->error);
        }

        if ($stmtUpdateStatus->affected_rows > 0) {
            header("Location: quanly_tin_dang.php?status=status_change_success");
        } else {
            header("Location: quanly_tin_dang.php?error=" . urlencode("Không có thay đổi trạng thái hoặc tin đăng không tồn tại."));
        }
        $stmtUpdateStatus->close();

    } catch (Exception $e) {
        error_log("Lỗi khi thay đổi trạng thái tin đăng: " . $e->getMessage());
        header("Location: quanly_tin_dang.php?error=" . urlencode("Lỗi khi thay đổi trạng thái: " . $e->getMessage()));
    } finally {
        if (isset($conn) && $conn->ping()) {
            $conn->close();
        }
    }

} else {
    header("Location: quanly_tin_dang.php");
    exit();
}
?>