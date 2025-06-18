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
    $tieu_de = $_POST['tieu_de'] ?? '';
    $gia_thue = $_POST['gia_thue'] ?? ''; 
    $dien_tich = $_POST['dien_tich'] ?? '';
    $thanh_pho = $_POST['thanh_pho'] ?? '';
    $quan_huyen = $_POST['quan_huyen'] ?? '';
    $dia_chi_chi_tiet = $_POST['dia_chi_chi_tiet'] ?? '';
    $so_phong_ngu = $_POST['so_phong_ngu'] ?? '';
    $so_phong_ve_sinh = $_POST['so_phong_ve_sinh'] ?? ''; 
    $mo_ta = $_POST['mo_ta'] ?? ''; 
    $amenities_selected = $_POST['amenities'] ?? []; 

    $images_to_remove_str = $_POST['images_to_remove'] ?? '';
    $images_to_remove_ids = array_filter(explode(',', $images_to_remove_str)); 

     if ($room_id === null || !filter_var($room_id, FILTER_VALIDATE_INT)) {
        header("Location: quanly_tin_dang.php?error=" . urlencode("ID tin đăng không hợp lệ."));
        exit();
    }

    $sqlCheckOwner = "SELECT ma_nguoi_dung FROM rooms WHERE ma_phong = ?";
    $stmtCheckOwner = $conn->prepare($sqlCheckOwner);
    if ($stmtCheckOwner === false) {
        error_log("Lỗi chuẩn bị truy vấn kiểm tra chủ sở hữu khi sửa: " . $conn->error);
        header("Location: quanly_tin_dang.php?error=" . urlencode("Có lỗi xảy ra khi xác minh quyền sở hữu, vui lòng thử lại."));
        exit();
    }
    $stmtCheckOwner->bind_param('i', $room_id);
    $stmtCheckOwner->execute();
    $resultCheckOwner = $stmtCheckOwner->get_result();

    if ($resultCheckOwner->num_rows === 0) {
        $stmtCheckOwner->close();
        header("Location: quanly_tin_dang.php?error=" . urlencode("Không tìm thấy tin đăng để sửa."));
        exit();
    }

    $owner_row = $resultCheckOwner->fetch_assoc();
    $stmtCheckOwner->close();

    if ($owner_row['ma_nguoi_dung'] !== $user_id) {
        header("Location: quanly_tin_dang.php?error=" . urlencode("Bạn không có quyền sửa tin đăng này."));
        exit();
    }

    $conn->begin_transaction();

    $update_success = true; 


    
    $sqlRoom = "UPDATE rooms SET tieu_de = ?, gia_thue = ?, dien_tich = ?, dia_chi_chi_tiet = ?, quan_huyen = ?, thanh_pho = ?, so_phong_ngu = ?, so_phong_ve_sinh = ?, mo_ta = ? WHERE ma_phong = ?";

    $stmtRoom = $conn->prepare($sqlRoom);
    if ($stmtRoom === false) {
        error_log("Lỗi chuẩn bị truy vấn UPDATE rooms: " . $conn->error);
        $update_success = false; 
    } else {
        $bindRoomSuccess = $stmtRoom->bind_param('sssssssssi', 
            $tieu_de, $gia_thue, $dien_tich, $dia_chi_chi_tiet, $quan_huyen, $thanh_pho,
            $so_phong_ngu, $so_phong_ve_sinh, $mo_ta, $room_id
        );

        if ($bindRoomSuccess === false || $stmtRoom->execute() === false) {
            error_log("Lỗi thực thi truy vấn UPDATE rooms cho phong ID " . $room_id . ": " . $stmtRoom->error);
            $update_success = false;
        }
        $stmtRoom->close();
    }

    $upload_directory_base = __DIR__ . '/'; 

    if ($update_success && !empty($images_to_remove_ids)) {
        
        $in_placeholders = implode(',', array_fill(0, count($images_to_remove_ids), '?')); 
        $sqlGetImagePaths = "SELECT duong_dan_anh FROM images WHERE ma_hinh_anh IN (" . $in_placeholders . ")";
        $stmtGetImagePaths = $conn->prepare($sqlGetImagePaths);
        if ($stmtGetImagePaths === false) {
             error_log("Lỗi chuẩn bị truy vấn lấy đường dẫn ảnh để xóa: " . $conn->error);
             $update_success = false;
        } else {
             
             $types_get_paths = str_repeat('i', count($images_to_remove_ids)); 
             $bindGetPathsSuccess = call_user_func_array([$stmtGetImagePaths, 'bind_param'], array_merge([$types_get_paths], $images_to_remove_ids));

             if ($bindGetPathsSuccess === false || $stmtGetImagePaths->execute() === false) {
                  error_log("Lỗi thực thi truy vấn lấy đường dẫn ảnh để xóa: " . $stmtGetImagePaths->error);
                  $update_success = false;
             } else {
                  $paths_to_delete = $stmtGetImagePaths->get_result()->fetch_all(MYSQLI_ASSOC);
                  $stmtGetImagePaths->close();

                  
                  $sqlDeleteImages = "DELETE FROM images WHERE ma_hinh_anh IN (" . $in_placeholders . ")"; 
                  $stmtDeleteImages = $conn->prepare($sqlDeleteImages);
                   if ($stmtDeleteImages === false) {
                        error_log("Lỗi chuẩn bị truy vấn xóa bản ghi ảnh: " . $conn->error);
                        $update_success = false;
                    } else {
                         
                         $bindDeleteImagesSuccess = call_user_func_array([$stmtDeleteImages, 'bind_param'], array_merge([$types_get_paths], $images_to_remove_ids));

                         if ($bindDeleteImagesSuccess === false || $stmtDeleteImages->execute() === false) {
                              error_log("Lỗi thực thi truy vấn xóa bản ghi ảnh: " . $stmtDeleteImages->error);
                              $update_success = false;
                         }
                         $stmtDeleteImages->close();

                         
                         if ($update_success) {
                             foreach ($paths_to_delete as $image) {
                                 $file_path = $upload_directory_base . ltrim($image['duong_dan_anh'], '/');
                                 if (file_exists($file_path)) {
                                     if (!unlink($file_path)) {
                                         error_log("Lỗi khi xóa file anh tren server: " . $file_path);
                                         
                                     }
                                 } else {
                                      error_log("File anh khong ton tai khi xoa tren server: " . $file_path);
                                 }
                             }
                         }
                     }
                }
            }
        }


    
     if ($update_success && isset($_FILES['new_room_images']) && is_array($_FILES['new_room_images']['name'])) {

        $file_count = count($_FILES['new_room_images']['name']);
        $image_upload_success = true; 

        
        if ($file_count > 0 && ($_FILES['new_room_images']['error'][0] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {

             $sqlInsertImage = "INSERT INTO images (ma_phong, duong_dan_anh, thoi_gian_tai_len) VALUES (?, ?, NOW())";
             $stmtInsertImage = $conn->prepare($sqlInsertImage);
             if ($stmtInsertImage === false) {
                  error_log("Lỗi chuẩn bị truy vấn INSERT ảnh mới: " . $conn->error);
                  $image_upload_success = false;
                  $update_success = false; 
             } else {

                 for ($i = 0; $i < $file_count; $i++) {
                     
                     if (isset($_FILES['new_room_images']['error'][$i]) && $_FILES['new_room_images']['error'][$i] === UPLOAD_ERR_OK) {

                         $file_tmp = $_FILES['new_room_images']['tmp_name'][$i];
                         $file_name = $_FILES['new_room_images']['name'][$i];
                         $file_size = $_FILES['new_room_images']['size'][$i];
                         

                         $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                         $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

                         
                         $max_file_size_per_image = 10 * 1024 * 1024; 
                         if ($file_size > $max_file_size_per_image || !in_array($file_ext, $allowed_extensions) || getimagesize($file_tmp) === false) {
                              error_log("Skipping invalid or too large new image: " . $file_name);
                              
                              continue; 
                         }

                         
                         $new_file_name = uniqid('room_img_', true) . '.' . $file_ext;
                         $target_file_path = $upload_directory_base . 'uploads/rooms/' . $new_file_name; 

                         
                         if (move_uploaded_file($file_tmp, $target_file_path)) {
                             
                             $image_path_to_save = '/uploads/rooms/' . $new_file_name; 

                          
                             $bindInsertSuccess = $stmtInsertImage->bind_param('is', $room_id, $image_path_to_save);

                             if ($bindInsertSuccess === false || $stmtInsertImage->execute() === false) {
                                 error_log("Lỗi thực thi truy vấn INSERT ảnh mới cho file " . $file_name . ": " . $stmtInsertImage->error);
                                 $image_upload_success = false;
                          
                             }

                         } else {
                             
                             error_log("Lỗi khi di chuyển file ảnh mới " . $file_name);
                             $image_upload_success = false;
                             
                         }
                     } else {
                          if (isset($_FILES['new_room_images']['error'][$i]) && $_FILES['new_room_images']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                              error_log("Lỗi tải lên file ảnh mới " . ($_FILES['new_room_images']['name'][$i] ?? 'Unknown file') . ": Code " . $_FILES['new_room_images']['error'][$i]);
                              $image_upload_success = false;
                             
                          }
                     }
                 } 

                 $stmtInsertImage->close(); 

                 if ($image_upload_success === false) {
                      error_log("Có lỗi xảy ra khi xử lý ảnh mới cho tin đăng ID: " . $room_id);
              
                 }
             }
         }
     } 

    if ($update_success) { 

         
         $sqlDeleteAmenities = "DELETE FROM room_amenities WHERE ma_phong = ?";
         $stmtDeleteAmenities = $conn->prepare($sqlDeleteAmenities);
         if ($stmtDeleteAmenities === false) {
             error_log("Lỗi chuẩn bị truy vấn xóa tiện ích cũ: " . $conn->error);
             $update_success = false;
         } else {
             $stmtDeleteAmenities->bind_param('i', $room_id);
             if ($stmtDeleteAmenities->execute() === false) {
                 error_log("Lỗi thực thi truy vấn xóa tiện ích cũ cho phong ID " . $room_id . ": " . $stmtDeleteAmenities->error);
                 $update_success = false;
             }
             $stmtDeleteAmenities->close();
         }
        if ($update_success && !empty($amenities_selected)) {
             $sqlInsertAmenity = "INSERT INTO room_amenities (ma_phong, ma_tien_ich) VALUES (?, ?)";
             $stmtInsertAmenity = $conn->prepare($sqlInsertAmenity);
             if ($stmtInsertAmenity === false) {
                 error_log("Lỗi chuẩn bị truy vấn INSERT tiện ích mới: " . $conn->error);
                 $update_success = false;
             } else {
                 foreach ($amenities_selected as $amenity_id) {
                     $amenity_id = (int) $amenity_id; 

                    $bindInsertAmenitySuccess = $stmtInsertAmenity->bind_param('ii', $room_id, $amenity_id);
                     if ($bindInsertAmenitySuccess === false || $stmtInsertAmenity->execute() === false) {
                          error_log("Lỗi thực thi truy vấn INSERT tiện ích mới cho phong ID " . $room_id . " tien ich ID " . $amenity_id . ": " . $stmtInsertAmenity->error);
                          $update_success = false;
                        
                     }
                 }
                 $stmtInsertAmenity->close();
             }
         }
     } 

    if ($update_success) {
      
        $conn->commit();
     
        header("Location: quanly_tin_dang.php?status=edit_success");
        exit();
    } else {
   
        $conn->rollback();
      
        header("Location: quanly_tin_dang.php?error=" . urlencode("Có lỗi xảy ra khi cập nhật tin đăng."));
        exit();
    }

} else {
   
    header("Location: quanly_tin_dang.php");
    exit();
}

?>