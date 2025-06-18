<?php

session_start();

include 'db.php';

$redirect_url = 'index.php';


if (isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['action']) && !empty($_GET['action'])) {
    $maSoSach = trim($_GET['id']);
    $action = trim($_GET['action']);

    $redirect_url = 'chitietsach.php?id=' . urlencode($maSoSach);

    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['user_id'])) {

        $loggedInUserId = $_SESSION['user_id'];

        if (!($conn instanceof mysqli)) {

            $_SESSION['message'] = "<div class='alert alert-danger'>Lỗi: Kết nối CSDL không phải là đối tượng MySQLi. Vui lòng kiểm tra file db.php</div>";

            header("location: " . $redirect_url);
            exit;
        } else {

            if ($action === 'add') {

                $sql = "INSERT INTO sachyeuthich (IDNguoiDung, MaSoSach) VALUES (?, ?)";
                if ($stmt = $conn->prepare($sql)) {

                    $stmt->bind_param("is", $loggedInUserId, $maSoSach); 
                    if ($stmt->execute()) {

                        $_SESSION['message'] = "<div class='alert alert-success'>Đã thêm sách vào danh sách yêu thích của bạn.</div>";
                    } else {

                        if ($conn->errno == 1062) {

                            $_SESSION['message'] = "<div class='alert alert-info'>Sách này đã có trong danh sách yêu thích của bạn.</div>";
                        } else {

                            $_SESSION['message'] = "<div class='alert alert-danger'>Lỗi khi thêm yêu thích: " . $conn->error . "</div>";
                            error_log("Lỗi khi thêm yêu thích: " . $conn->error); // Ghi log lỗi chi tiết
                        }
                    }
                    $stmt->close();
                } else {

                    $_SESSION['message'] = "<div class='alert alert-danger'>Lỗi hệ thống khi chuẩn bị thêm yêu thích.</div>";
                    error_log("Lỗi khi chuẩn bị truy vấn thêm yêu thích: " . $conn->error);
                }
            } elseif ($action === 'remove') {
                $sql = "DELETE FROM sachyeuthich WHERE IDNguoiDung = ? AND MaSoSach = ?";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("is", $loggedInUserId, $maSoSach);
                    if ($stmt->execute()) {

                        $_SESSION['message'] = "<div class='alert alert-success'>Đã xóa sách khỏi danh sách yêu thích của bạn.</div>";
                    } else {

                        $_SESSION['message'] = "<div class='alert alert-danger'>Lỗi khi bỏ yêu thích: " . $conn->error . "</div>";
                        error_log("Lỗi khi bỏ yêu thích: " . $conn->error);
                    }
                    $stmt->close();
                } else {

                    $_SESSION['message'] = "<div class='alert alert-danger'>Lỗi hệ thống khi chuẩn bị bỏ yêu thích.</div>";
                    error_log("Lỗi khi chuẩn bị truy vấn bỏ yêu thích: " . $conn->error);
                }
            } else {

                $_SESSION['message'] = "<div class='alert alert-warning'>Hành động yêu thích không hợp lệ.</div>";
            }
            if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
                $conn->close();
            }
        }
    } else {
        $_SESSION['message'] = "<div class='alert alert-warning'>Vui lòng đăng nhập để thêm sách vào danh sách yêu thích.</div>";

        $redirect_url = 'dangnhap.php?redirect=' . urlencode($_SERVER['REQUEST_URI']);
    }
} else {
    $_SESSION['message'] = "<div class='alert alert-warning'>Thiếu thông tin sách hoặc hành động yêu thích.</div>";
    $redirect_url = 'danhmucsach.php';
}

header("location: " . $redirect_url);
exit();
