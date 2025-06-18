<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: dangnhap.php?error=not_logged_in");
    exit();
}

require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $room_id = $_POST['room_id'] ?? null;

    if ($room_id === null || !filter_var($room_id, FILTER_VALIDATE_INT)) {
        header("Location: quanly_tin_dang.php?error=" . urlencode("ID tin đăng không hợp lệ."));
        exit();
    }
    $sql = "UPDATE rooms SET trang_thai = 'rented' WHERE ma_phong = ? AND ma_nguoi_dung = ?";

    // Chuẩn bị statement
    $stmt = $conn->prepare($sql);

    // Kiểm tra lỗi prepare
    if ($stmt === false) {
        die("Lỗi chuẩn bị truy vấn cập nhật trạng thái: " . $conn->error);
    }

    // Gán giá trị cho các tham số (ii = 2 integer)
    $bindSuccess = $stmt->bind_param('ii', $room_id, $user_id);

     // Kiểm tra lỗi bind_param
     if ($bindSuccess === false) {
         die("Lỗi gán tham số truy vấn cập nhật trạng thái: " . $stmt->error);
     }


    // --- 3. Thực thi statement UPDATE ---
    $executeSuccess = $stmt->execute();

     // Kiểm tra lỗi execute
     if ($executeSuccess === false) {
          die("Lỗi thực thi truy vấn cập nhật trạng thái: " . $stmt->error);
     }

    if ($stmt->affected_rows > 0) {

        header("Location: quanly_tin_dang.php?status=rented_success");
        exit();

    } else {

         header("Location: quanly_tin_dang.php?status=no_change"); 
         exit();
    }

    $stmt->close();

} else {

    header("Location: quanly_tin_dang.php");
    exit();
}

?>