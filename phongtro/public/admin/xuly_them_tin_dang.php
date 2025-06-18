<?php

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=" . urlencode("Bạn cần đăng nhập với quyền Admin để thực hiện chức năng này."));
    exit();
}


$admin_user_id = $_SESSION['admin_user_id'] ?? null;

if ($admin_user_id === null) {
    header("Location: quanly_tin_dang.php?error=" . urlencode("Lỗi: Không tìm thấy ID Admin trong phiên làm việc. Vui lòng đăng nhập lại."));
    exit();
}

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $ma_nguoi_dung = $admin_user_id;

    $tieu_de = $_POST['tieu_de'] ?? '';
    $mo_ta = $_POST['mo_ta'] ?? '';
    $gia_thue = $_POST['gia_thue'] ?? '';
    $dien_tich = $_POST['dien_tich'] ?? '';
    $dia_chi_chi_tiet = $_POST['dia_chi_chi_tiet'] ?? '';
    $thanh_pho = $_POST['thanh_pho'] ?? '';
    $quan_huyen = $_POST['quan_huyen'] ?? '';
    $phuong_xa = $_POST['phuong_xa'] ?? '';
    $vi_do = $_POST['vi_do'] ?? null;
    $kinh_do = $_POST['kinh_do'] ?? null;
    $so_phong_ngu = $_POST['so_phong_ngu'] ?? 0;
    $so_phong_ve_sinh = $_POST['so_phong_ve_sinh'] ?? 0;
    $trang_thai = $_POST['trang_thai'] ?? 'hidden';
    $selected_amenities = $_POST['amenities'] ?? []; 

    $errors = [];

    if (empty($tieu_de)) { $errors[] = "Tiêu đề không được để trống."; }
    if (empty($mo_ta)) { $errors[] = "Mô tả không được để trống."; }
    if (!filter_var($gia_thue, FILTER_VALIDATE_FLOAT) || $gia_thue < 0) {
        $errors[] = "Giá thuê không hợp lệ.";
    }
    if (!filter_var($dien_tich, FILTER_VALIDATE_FLOAT) || $dien_tich < 0) {
        $errors[] = "Diện tích không hợp lệ.";
    }
    if (empty($dia_chi_chi_tiet)) { $errors[] = "Địa chỉ chi tiết không được để trống."; }
    if (empty($thanh_pho)) { $errors[] = "Thành phố không được để trống."; }
    if (empty($quan_huyen)) { $errors[] = "Quận/Huyện không được để trống."; }
    if (empty($phuong_xa)) { $errors[] = "Phường/Xã không được để trống."; }

    // Kiểm tra hình ảnh
    if (empty($_FILES['images']['name'][0])) {
        $errors[] = "Vui lòng tải lên ít nhất một hình ảnh.";
    }

    if (!empty($errors)) {
        $error_string = implode("\\n", $errors); // Dùng \n để xuống dòng trong alert
        header("Location: them_tin_dang.php?error=" . urlencode($error_string));
        exit();
    }

    $conn->begin_transaction();

    try {

        $sqlInsertRoom = "INSERT INTO rooms (ma_nguoi_dung, tieu_de, mo_ta, gia_thue, dien_tich, dia_chi_chi_tiet, thanh_pho, quan_huyen, phuong_xa, vi_do, kinh_do, so_phong_ngu, so_phong_ve_sinh, trang_thai, thoi_gian_cong_khai)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"; // Tự động set thoi_gian_tao và thoi_gian_cap_nhat

        $stmtInsertRoom = $conn->prepare($sqlInsertRoom);
        if ($stmtInsertRoom === false) {
            throw new Exception("Lỗi prepare SQL INSERT Room: " . $conn->error);
        }

        $stmtInsertRoom->bind_param("issssdssssssis", $ma_nguoi_dung, $tieu_de, $mo_ta, $gia_thue, $dien_tich, $dia_chi_chi_tiet, $thanh_pho, $quan_huyen, $phuong_xa, $vi_do, $kinh_do, $so_phong_ngu, $so_phong_ve_sinh, $trang_thai);

        if (!$stmtInsertRoom->execute()) {
            throw new Exception("Lỗi thực thi INSERT Room: " . $stmtInsertRoom->error);
        }

        $ma_phong_moi = $conn->insert_id; // Lấy ID của tin đăng vừa thêm
        $stmtInsertRoom->close();

        if (!empty($_FILES['images']['name'][0])) {
            $target_dir = __DIR__ . "/../../uploads/rooms/"; 
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true); 
            }

            $sqlInsertImage = "INSERT INTO images (ma_phong, duong_dan_anh) VALUES (?, ?)";
            $stmtInsertImage = $conn->prepare($sqlInsertImage);
            if ($stmtInsertImage === false) {
                throw new Exception("Lỗi prepare SQL INSERT Image: " . $conn->error);
            }

            foreach ($_FILES['images']['name'] as $key => $image_name) {
                $tmp_name = $_FILES['images']['tmp_name'][$key];
                $file_extension = pathinfo($image_name, PATHINFO_EXTENSION);
                $new_file_name = uniqid('room_') . '.' . $file_extension;
                $target_file = $target_dir . $new_file_name;
                $db_path = 'uploads/rooms/' . $new_file_name; 

                if (move_uploaded_file($tmp_name, $target_file)) {
                    $stmtInsertImage->bind_param("is", $ma_phong_moi, $db_path);
                    if (!$stmtInsertImage->execute()) {
                        error_log("Lỗi INSERT Image: " . $stmtInsertImage->error);

                    }
                } else {
                    error_log("Lỗi di chuyển file upload: " . $_FILES['images']['error'][$key]);
                }
            }
            $stmtInsertImage->close();
        }
        if (!empty($selected_amenities)) {
            $sqlInsertRoomAmenity = "INSERT INTO room_amenities (ma_phong, ma_tien_ich) VALUES (?, ?)";
            $stmtInsertRoomAmenity = $conn->prepare($sqlInsertRoomAmenity);
            if ($stmtInsertRoomAmenity === false) {
                throw new Exception("Lỗi prepare SQL INSERT Room Amenity: " . $conn->error);
            }

            foreach ($selected_amenities as $amenity_id) {
                $stmtInsertRoomAmenity->bind_param("ii", $ma_phong_moi, $amenity_id);
                if (!$stmtInsertRoomAmenity->execute()) {
                    error_log("Lỗi INSERT Room Amenity: " . $stmtInsertRoomAmenity->error);
                }
            }
            $stmtInsertRoomAmenity->close();
        }

        $conn->commit(); // Cam kết giao dịch
        header("Location: quanly_tin_dang.php?status=add_success");
        exit();

    } catch (Exception $e) {
        $conn->rollback(); // Hoàn tác giao dịch nếu có lỗi
        error_log("Lỗi khi thêm tin đăng: " . $e->getMessage());
        header("Location: them_tin_dang.php?error=" . urlencode("Lỗi khi thêm tin đăng: " . $e->getMessage()));
        exit();
    } finally {
        if (isset($conn) && $conn->ping()) {
            $conn->close();
        }
    }

} else {
    // Nếu không phải POST request, chuyển hướng về trang thêm tin đăng
    header("Location: them_tin_dang.php");
    exit();
}
?>