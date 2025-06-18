<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['message'] = "<div class='alert alert-danger'>Bạn không có quyền thực hiện hành động này. Vui lòng đăng nhập với tài khoản quản trị viên.</div>";
    header("Location: dangnhap_admin.php"); 
    exit();
}

include '../db.php'; 

$message = '';
$redirect_url = 'quanly_yeucau_tra.php'; 


if (isset($_GET['sophieumuon']) && !empty($_GET['sophieumuon']) &&
    isset($_GET['masosach']) && !empty($_GET['masosach'])) {

    $soPhieuMuon = trim($_GET['sophieumuon']);
    $maSoSach = trim($_GET['masosach']);

    if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Lỗi kết nối CSDL: " . ($conn->connect_error ?? 'Biến kết nối $conn không tồn tại hoặc không phải đối tượng MySQLi.') . "</div>";
        header("Location: " . $redirect_url);
        exit();
    }

    $conn->begin_transaction();

    try {

        $sql_update_chitiet = "UPDATE chitietphieumuon SET TrangThai = 'Đã trả' WHERE SoPhieuMuon = ? AND MaSoSach = ? AND TrangThai = 'Chờ duyệt trả'";
        $stmt_update_chitiet = $conn->prepare($sql_update_chitiet);

        if ($stmt_update_chitiet === false) {
            throw new Exception('Lỗi chuẩn bị truy vấn cập nhật chi tiết phiếu mượn khi ghi nhận trả: ' . $conn->error);
        }

        $stmt_update_chitiet->bind_param("ss", $soPhieuMuon, $maSoSach);
        $stmt_update_chitiet->execute();
        if ($stmt_update_chitiet->affected_rows > 0) {
            $sql_update_quyensach = "UPDATE quyensach SET SoLuong = SoLuong + 1 WHERE MaSoSach = ?";
            $stmt_update_quyensach = $conn->prepare($sql_update_quyensach);

            if ($stmt_update_quyensach === false) {
                throw new Exception('Lỗi chuẩn bị truy vấn cập nhật số lượng sách khi ghi nhận trả: ' . $conn->error);
            }

            $stmt_update_quyensach->bind_param("s", $maSoSach);
            $stmt_update_quyensach->execute();

            if ($stmt_update_quyensach->affected_rows > 0) {
                 
                 $conn->commit(); 
                 $_SESSION['message'] = "<div class='alert alert-success'>Đã ghi nhận trả sách có Số Phiếu: " . htmlspecialchars($soPhieuMuon) . ", Mã Sách: " . htmlspecialchars($maSoSach) . ". Số lượng sách đã được cập nhật.</div>";
            } else {
                
                 $conn->rollback();
                 $_SESSION['message'] = "<div class='alert alert-warning'>Đã cập nhật trạng thái trả sách, nhưng không thể cập nhật số lượng sách (Mã: " . htmlspecialchars($maSoSach) . "). Vui lòng kiểm tra thủ công.</div>"; // Sửa lại thông báo
                
                 error_log("Admin ghi nhan tra sach nhung khong tang duoc SoLuong sach: SoPhieuMuon=" . $soPhieuMuon . ", MaSoSach=" . $maSoSach);
            }
            $stmt_update_quyensach->close();

        } elseif ($stmt_update_chitiet->affected_rows === 0) {
            $conn->rollback(); 
            $_SESSION['message'] = "<div class='alert alert-warning'>Yêu cầu trả sách có Số Phiếu: " . htmlspecialchars($soPhieuMuon) . ", Mã Sách: " . htmlspecialchars($maSoSach) . " không tìm thấy hoặc trạng thái không phải 'Chờ duyệt trả'.</div>";
        }
      
        $stmt_update_chitiet->close();


    } catch (Exception $e) {
     
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->rollback();
        }

        $_SESSION['message'] = "<div class='alert alert-danger'>Lỗi xử lý ghi nhận trả sách: " . htmlspecialchars($e->getMessage()) . "</div>";

      
        error_log("Lỗi xử lý ghi nhan tra sach (Admin): SoPhieu=" . ($soPhieuMuon ?? 'N/A') . ", Maso=" . ($maSoSach ?? 'N/A') . " - Lỗi: " . $e->getMessage());


    } finally {
     
        if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
            $conn->close();
        }
    }

} else {

    $_SESSION['message'] = "<div class='alert alert-warning'>Thiếu thông tin yêu cầu trả sách để xử lý.</div>";
}

header("Location: " . $redirect_url);
exit(); 
?>
