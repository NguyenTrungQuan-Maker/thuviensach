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
$redirect_url = 'quanly_yeucau_muon.php';

if (
    isset($_GET['sophieumuon']) && !empty($_GET['sophieumuon']) &&
    isset($_GET['masosach']) && !empty($_GET['masosach']) &&
    isset($_GET['action']) && !empty($_GET['action'])
) {
    $soPhieuMuon = trim($_GET['sophieumuon']);
    $maSoSach = trim($_GET['masosach']);
    $action = trim(strtolower($_GET['action']));

    if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Lỗi kết nối CSDL: " . ($conn->connect_error ?? 'Biến kết nối $conn không tồn tại hoặc không phải đối tượng MySQLi.') . "</div>";
        header("Location: " . $redirect_url);
        exit();
    }

    $conn->begin_transaction();
    try {
        if ($action === 'approve') {
            $sql_update_chitiet = "UPDATE chitietphieumuon SET TrangThai = 'Đang mượn' WHERE SoPhieuMuon = ? AND MaSoSach = ? AND TrangThai = 'Chờ duyệt mượn'";
            $stmt_update_chitiet = $conn->prepare($sql_update_chitiet);
            if ($stmt_update_chitiet === false) {
                throw new Exception('Lỗi chuẩn bị truy vấn cập nhật chi tiết phiếu mượn: ' . $conn->error);
            }
            $stmt_update_chitiet->bind_param("ss", $soPhieuMuon, $maSoSach);
            $stmt_update_chitiet->execute();

            if ($stmt_update_chitiet->affected_rows > 0) {

                $sql_update_quyensach = "UPDATE quyensach SET SoLuong = SoLuong - 1 WHERE MaSoSach = ? AND SoLuong > 0";
                $stmt_update_quyensach = $conn->prepare($sql_update_quyensach);
                if ($stmt_update_quyensach === false) {
                    throw new Exception('Lỗi chuẩn bị truy vấn cập nhật số lượng sách: ' . $conn->error);
                }
                $stmt_update_quyensach->bind_param("s", $maSoSach);
                $stmt_update_quyensach->execute();

                if ($stmt_update_quyensach->affected_rows > 0) {

                    $conn->commit();
                    $_SESSION['message'] = "<div class='alert alert-success'>Đã duyệt yêu cầu mượn sách có Số Phiếu: " . htmlspecialchars($soPhieuMuon) . ", Mã Sách: " . htmlspecialchars($maSoSach) . ".</div>";
                } else {



                    $conn->rollback();
                    $_SESSION['message'] = "<div class='alert alert-warning'>Không thể duyệt yêu cầu. Sách (Mã: " . htmlspecialchars($maSoSach) . ") hiện đã hết hoặc đã có lỗi khi cập nhật số lượng.</div>";

                    error_log("Admin duyệt yêu cầu nhung khong giam duoc SoLuong sach (het sach hoac loi update): SoPhieuMuon=" . $soPhieuMuon . ", MaSoSach=" . $maSoSach);
                }
                $stmt_update_quyensach->close();
            } elseif ($stmt_update_chitiet->affected_rows === 0) {


                $conn->rollback();
                $_SESSION['message'] = "<div class='alert alert-warning'>Yêu cầu mượn có Số Phiếu: " . htmlspecialchars($soPhieuMuon) . ", Mã Sách: " . htmlspecialchars($maSoSach) . " không tìm thấy hoặc trạng thái không phải 'Chờ duyệt mượn'.</div>";
            }

            $stmt_update_chitiet->close();
        } elseif ($action === 'reject') {
            $sql_delete_chitiet = "DELETE FROM chitietphieumuon WHERE SoPhieuMuon = ? AND MaSoSach = ? AND TrangThai = 'Chờ duyệt mượn'";
            $stmt_delete_chitiet = $conn->prepare($sql_delete_chitiet);
            if ($stmt_delete_chitiet === false) {
                throw new Exception('Lỗi chuẩn bị truy vấn xóa chi tiết phiếu mượn: ' . $conn->error);
            }
            $stmt_delete_chitiet->bind_param("ss", $soPhieuMuon, $maSoSach);
            $stmt_delete_chitiet->execute();

            if ($stmt_delete_chitiet->affected_rows > 0) {


                $sql_check_remaining = "SELECT COUNT(*) AS remaining_count FROM chitietphieumuon WHERE SoPhieuMuon = ?";
                $stmt_check_remaining = $conn->prepare($sql_check_remaining);
                if ($stmt_check_remaining === false) {

                    error_log("Lỗi chuẩn bị truy vấn kiểm tra chi tiết phiếu mượn còn lại sau khi từ chối: " . $conn->error);
                    $conn->commit();
                    $_SESSION['message'] = "<div class='alert alert-success'>Đã từ chối yêu cầu mượn sách có Số Phiếu: " . htmlspecialchars($soPhieuMuon) . ", Mã Sách: " . htmlspecialchars($maSoSach) . ". (Có lỗi khi kiểm tra các mục khác trong phiếu).</div>";
                } else {
                    $stmt_check_remaining->bind_param("s", $soPhieuMuon);
                    $stmt_check_remaining->execute();
                    $result_check = $stmt_check_remaining->get_result();
                    $row_check = $result_check->fetch_assoc();
                    $remaining_count = $row_check['remaining_count'];
                    $stmt_check_remaining->close();
                    if ($remaining_count == 0) {

                        $sql_delete_muonsach = "DELETE FROM muonsach WHERE SoPhieuMuon = ?";
                        $stmt_delete_muonsach = $conn->prepare($sql_delete_muonsach);
                        if ($stmt_delete_muonsach === false) {
                            throw new Exception('Lỗi chuẩn bị truy vấn xóa phiếu mượn: ' . $conn->error);
                        }
                        $stmt_delete_muonsach->bind_param("s", $soPhieuMuon);
                        $stmt_delete_muonsach->execute();

                        $stmt_delete_muonsach->close();
                    }

                    $conn->commit();
                    $_SESSION['message'] = "<div class='alert alert-success'>Đã từ chối yêu cầu mượn sách có Số Phiếu: " . htmlspecialchars($soPhieuMuon) . ", Mã Sách: " . htmlspecialchars($maSoSach) . ".</div>";
                }
            } elseif ($stmt_delete_chitiet->affected_rows === 0) {

                $conn->rollback();
                $_SESSION['message'] = "<div class='alert alert-warning'>Yêu cầu mượn có Số Phiếu: " . htmlspecialchars($soPhieuMuon) . ", Mã Sách: " . htmlspecialchars($maSoSach) . " không tìm thấy hoặc trạng thái không phải 'Chờ duyệt mượn'.</div>";
            }

            $stmt_delete_chitiet->close();
        } else {

            $conn->rollback();
            $_SESSION['message'] = "<div class='alert alert-warning'>Hành động không hợp lệ: " . htmlspecialchars($action) . ".</div>";
        }
    } catch (Exception $e) {

        if (isset($conn) && $conn instanceof mysqli) {
            $conn->rollback();
        }


        $_SESSION['message'] = "<div class='alert alert-danger'>Lỗi xử lý: " . htmlspecialchars($e->getMessage()) . "</div>";

        error_log("Lỗi xử lý yêu cầu mượn (Admin): SoPhieu=" . ($soPhieuMuon ?? 'N/A') . ", Maso=" . ($maSoSach ?? 'N/A') . ", Action=" . ($action ?? 'N/A') . " - Lỗi: " . $e->getMessage());
    } finally {

        if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
            $conn->close();
        }
    }
} else {

    $_SESSION['message'] = "<div class='alert alert-warning'>Thiếu thông tin yêu cầu mượn để xử lý.</div>";
}

header("Location: " . $redirect_url);
exit();
