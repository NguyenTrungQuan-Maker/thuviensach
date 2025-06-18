<?php

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=" . urlencode("Bạn cần đăng nhập với quyền Admin để thực hiện chức năng này."));
    exit();
}

require_once __DIR__ . '/../../config/db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ma_phong = $_POST['ma_phong'] ?? 0;
    $ma_nguoi_dung = $_POST['ma_nguoi_dung'] ?? '';
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
    $images_to_delete = $_POST['images_to_delete'] ?? []; 

    $errors = [];
    if (!filter_var($ma_phong, FILTER_VALIDATE_INT) || $ma_phong <= 0) {
        $errors[] = "ID tin đăng không hợp lệ.";
    }
    if (empty($ma_nguoi_dung) || !filter_var($ma_nguoi_dung, FILTER_VALIDATE_INT)) {
        $errors[] = "Chủ sở hữu không hợp lệ.";
    }
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

    if (!empty($errors)) {
        $error_string = implode("\\n", $errors);
        header("Location: sua_tin_dang.php?id=" . $ma_phong . "&error=" . urlencode($error_string));
        exit();
    }

    $conn->begin_transaction();

    try {

        $sqlUpdateRoom = "UPDATE rooms SET
                            ma_nguoi_dung = ?,
                            tieu_de = ?,
                            mo_ta = ?,
                            gia_thue = ?,
                            dien_tich = ?,
                            dia_chi_chi_tiet = ?,
                            thanh_pho = ?,
                            quan_huyen = ?,
                            phuong_xa = ?,
                            vi_do = ?,
                            kinh_do = ?,
                            so_phong_ngu = ?,
                            so_phong_ve_sinh = ?,
                            trang_thai = ?,
                            thoi_gian_cap_nhat = NOW()
                          WHERE ma_phong = ?";

        $stmtUpdateRoom = $conn->prepare($sqlUpdateRoom);
        if ($stmtUpdateRoom === false) {
            throw new Exception("Lỗi prepare SQL UPDATE Room: " . $conn->error);
        }

        $stmtUpdateRoom->bind_param("issssdssssssisi",
            $ma_nguoi_dung, $tieu_de, $mo_ta, $gia_thue, $dien_tich, $dia_chi_chi_tiet,
            $thanh_pho, $quan_huyen, $phuong_xa, $vi_do, $kinh_do,
            $so_phong_ngu, $so_phong_ve_sinh, $trang_thai, $ma_phong
        );

        if (!$stmtUpdateRoom->execute()) {
            throw new Exception("Lỗi thực thi UPDATE Room: " . $stmtUpdateRoom->error);
        }
        $stmtUpdateRoom->close();

        // --- 2. Xử lý xóa hình ảnh cũ ---
        if (!empty($images_to_delete)) {
            $placeholders = implode(',', array_fill(0, count($images_to_delete), '?'));
            $sqlSelectImagePaths = "SELECT duong_dan_anh FROM images WHERE ma_hinh_anh IN ($placeholders) AND ma_phong = ?";
            $stmtSelectImagePaths = $conn->prepare($sqlSelectImagePaths);
            if ($stmtSelectImagePaths === false) {
                throw new Exception("Lỗi prepare SQL SELECT Image Paths: " . $conn->error);
            }

            // Gắn tham số (array_merge để thêm ma_phong vào cuối)
            $bind_params = array_merge($images_to_delete, [$ma_phong]);
            $types = str_repeat('i', count($images_to_delete)) . 'i'; // Chuỗi kiểu dữ liệu (i cho integer)
            $stmtSelectImagePaths->bind_param($types, ...$bind_params);
            $stmtSelectImagePaths->execute();
            $resultImagePaths = $stmtSelectImagePaths->get_result();

            $paths_to_delete_from_server = [];
            while ($row = $resultImagePaths->fetch_assoc()) {
                $paths_to_delete_from_server[] = __DIR__ . '/../../' . $row['duong_dan_anh'];
            }
            $resultImagePaths->free();
            $stmtSelectImagePaths->close();

            // Xóa từ CSDL
            $sqlDeleteImages = "DELETE FROM images WHERE ma_hinh_anh IN ($placeholders) AND ma_phong = ?";
            $stmtDeleteImages = $conn->prepare($sqlDeleteImages);
            if ($stmtDeleteImages === false) {
                throw new Exception("Lỗi prepare SQL DELETE Images: " . $conn->error);
            }
            $stmtDeleteImages->bind_param($types, ...$bind_params);
            if (!$stmtDeleteImages->execute()) {
                throw new Exception("Lỗi thực thi DELETE Images: " . $stmtDeleteImages->error);
            }
            $stmtDeleteImages->close();

            // Xóa file trên server
            foreach ($paths_to_delete_from_server as $file_path) {
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }

        // --- 3. Xử lý tải lên hình ảnh mới vào bảng `images` ---
        if (isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0])) {
            $target_dir = __DIR__ . "'/uploads/rooms/'";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $sqlInsertImage = "INSERT INTO images (ma_phong, duong_dan_anh) VALUES (?, ?)";
            $stmtInsertImage = $conn->prepare($sqlInsertImage);
            if ($stmtInsertImage === false) {
                throw new Exception("Lỗi prepare SQL INSERT New Image: " . $conn->error);
            }

            foreach ($_FILES['new_images']['name'] as $key => $image_name) {
                $tmp_name = $_FILES['new_images']['tmp_name'][$key];
                $file_extension = pathinfo($image_name, PATHINFO_EXTENSION);
                $new_file_name = uniqid('room_') . '.' . $file_extension;
                $target_file = $target_dir . $new_file_name;
                $db_path = '/uploads/rooms/' . $new_file_name;

                if (move_uploaded_file($tmp_name, $target_file)) {
                    $stmtInsertImage->bind_param("is", $ma_phong, $db_path);
                    if (!$stmtInsertImage->execute()) {
                        error_log("Lỗi INSERT New Image: " . $stmtInsertImage->error);
                    }
                } else {
                    error_log("Lỗi di chuyển file upload mới: " . $_FILES['new_images']['error'][$key]);
                }
            }
            $stmtInsertImage->close();
        }

        $sqlDeleteAmenities = "DELETE FROM room_amenities WHERE ma_phong = ?";
        $stmtDeleteAmenities = $conn->prepare($sqlDeleteAmenities);
        if ($stmtDeleteAmenities === false) {
            throw new Exception("Lỗi prepare SQL DELETE Amenities: " . $conn->error);
        }
        $stmtDeleteAmenities->bind_param("i", $ma_phong);
        if (!$stmtDeleteAmenities->execute()) {
            throw new Exception("Lỗi thực thi DELETE Amenities: " . $stmtDeleteAmenities->error);
        }
        $stmtDeleteAmenities->close();

        // Thêm lại các tiện ích đã chọn
        if (!empty($selected_amenities)) {
            $sqlInsertRoomAmenity = "INSERT INTO room_amenities (ma_phong, ma_tien_ich) VALUES (?, ?)";
            $stmtInsertRoomAmenity = $conn->prepare($sqlInsertRoomAmenity);
            if ($stmtInsertRoomAmenity === false) {
                throw new Exception("Lỗi prepare SQL INSERT Room Amenity (Edit): " . $conn->error);
            }

            foreach ($selected_amenities as $amenity_id) {
                $stmtInsertRoomAmenity->bind_param("ii", $ma_phong, $amenity_id);
                if (!$stmtInsertRoomAmenity->execute()) {
                    error_log("Lỗi INSERT Room Amenity (Edit): " . $stmtInsertRoomAmenity->error);
                }
            }
            $stmtInsertRoomAmenity->close();
        }

        $conn->commit();
        header("Location: quanly_tin_dang.php?status=edit_success");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Lỗi khi sửa tin đăng: " . $e->getMessage());
        header("Location: sua_tin_dang.php?id=" . $ma_phong . "&error=" . urlencode("Lỗi khi sửa tin đăng: " . $e->getMessage()));
        exit();
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