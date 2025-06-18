<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'db.php'; 

$message = '';
$redirect_url = 'sach_dang_muon.php'; 


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
        $sql_update_chitiet = "UPDATE chitietphieumuon SET TrangThai = 'Chờ duyệt trả' WHERE SoPhieuMuon = ? AND MaSoSach = ? AND TrangThai = 'Đang mượn'";
        $stmt_update_chitiet = $conn->prepare($sql_update_chitiet);

        if ($stmt_update_chitiet === false) {
            throw new Exception('Lỗi chuẩn bị truy vấn cập nhật chi tiết phiếu mượn: ' . $conn->error);
        }

        $stmt_update_chitiet->bind_param("ss", $soPhieuMuon, $maSoSach);
        $stmt_update_chitiet->execute();

        if ($stmt_update_chitiet->affected_rows > 0) {
            
            $conn->commit(); 
            $_SESSION['message'] = "<div class='alert alert-success'>Yêu cầu trả sách có Số Phiếu: " . htmlspecialchars($soPhieuMuon) . ", Mã Sách: " . htmlspecialchars($maSoSach) . " đã được gửi. Vui lòng chờ Admin duyệt.</div>";
        } elseif ($stmt_update_chitiet->affected_rows === 0) {
           
            $conn->rollback(); 
            $_SESSION['message'] = "<div class='alert alert-warning'>Yêu cầu trả sách không thành công. Sách có thể không ở trạng thái 'Đang mượn' hoặc đã có lỗi xảy ra.</div>";
        }

        $stmt_update_chitiet->close();

    } catch (Exception $e) {
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->rollback(); 
        }

        $_SESSION['message'] = "<div class='alert alert-danger'>Lỗi xử lý yêu cầu trả sách: " . htmlspecialchars($e->getMessage()) . "</div>";

        error_log("Lỗi xử lý yêu cầu tra sach (Doc gia): SoPhieu=" . ($soPhieuMuon ?? 'N/A') . ", Maso=" . ($maSoSach ?? 'N/A') . " - Lỗi: " . $e->getMessage());

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
