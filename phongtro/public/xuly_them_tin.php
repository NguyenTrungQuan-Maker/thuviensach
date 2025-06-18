<?php

session_start();



if (!isset($_SESSION['user_id'])) {
    header("Location: dangnhap.php?error=not_logged_in");
    exit();
}


require_once __DIR__ . '/../config/db.php'; 


$user_id = $_SESSION['user_id'];


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    
    $tieu_de = $_POST['tieu_de'] ?? '';
    
    $gia_thue = (float) ($_POST['gia_thue'] ?? 0);
    $dien_tich = (float) ($_POST['dien_tich'] ?? 0);
    $thanh_pho = $_POST['thanh_pho'] ?? '';
    $quan_huyen = $_POST['quan_huyen'] ?? '';
    $dia_chi_chi_tiet = $_POST['dia_chi_chi_tiet'] ?? '';
    $so_phong_ngu = (int) ($_POST['so_phong_ngu'] ?? 0);
    $so_phong_ve_sinh = (int) ($_POST['so_phong_ve_sinh'] ?? 0);
    $mo_ta = $_POST['mo_ta'] ?? ''; 
    $tien_ich_duoc_chon = $_POST['tien_ich'] ?? []; 

    
    
    if (empty($tieu_de) || $gia_thue <= 0 || $dien_tich <= 0 || empty($thanh_pho) || empty($quan_huyen) || empty($dia_chi_chi_tiet) || empty($mo_ta)) {
         header("Location: them_tin.php?error=" . urlencode("Vui lòng điền đầy đủ và chính xác các thông tin bắt buộc."));
         exit();
    }


    
    
    $conn->begin_transaction();


    
    
    
    

    $sqlRoom = "INSERT INTO rooms (tieu_de, gia_thue, dien_tich, dia_chi_chi_tiet, quan_huyen, thanh_pho, so_phong_ngu, so_phong_ve_sinh, mo_ta, ma_nguoi_dung, thoi_gian_cong_khai, trang_thai)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)"; 

    $stmtRoom = $conn->prepare($sqlRoom);
    if ($stmtRoom === false) {
        $conn->rollback();
        die("Lỗi chuẩn bị truy vấn INSERT rooms: " . $conn->error);
    }

    
    $room_status = 'available'; 

    


    $bindRoomSuccess = $stmtRoom->bind_param('sssssssiiss', 
        $tieu_de, $gia_thue, $dien_tich, $dia_chi_chi_tiet, $quan_huyen, $thanh_pho,
        $so_phong_ngu, $so_phong_ve_sinh, $mo_ta, $user_id, $room_status
    );


     if ($bindRoomSuccess === false) {
         $conn->rollback();
         die("Lỗi gán tham số truy vấn INSERT rooms: " . $stmtRoom->error);
     }

    
    $executeRoomSuccess = $stmtRoom->execute();

    if ($executeRoomSuccess === false) {
        $conn->rollback();
        die("Lỗi thực thi truy vấn INSERT rooms: " . $stmtRoom->error);
    }

    
    $new_room_id = $conn->insert_id;

    $stmtRoom->close(); 


    
    $upload_directory = __DIR__ . '/uploads/rooms/'; 
    if (!is_dir($upload_directory)) {
         mkdir($upload_directory, 0777, true);
     }

    $image_insert_success = true; 

    if (isset($_FILES['room_images']) && is_array($_FILES['room_images']['name'])) {

        $file_count = count($_FILES['room_images']['name']);

        
        $files_uploaded_successfully = 0;
        for ($i = 0; $i < $file_count; $i++) {
            if (isset($_FILES['room_images']['error'][$i]) && $_FILES['room_images']['error'][$i] === UPLOAD_ERR_OK) {
                $files_uploaded_successfully++;
            }
        }

        
        if ($files_uploaded_successfully === 0) {
            
            $conn->rollback();
            header("Location: them_tin.php?error=" . urlencode("Vui lòng chọn ít nhất một hình ảnh hợp lệ cho phòng trọ."));
            exit();
        }


        
         $sqlImage = "INSERT INTO images (ma_phong, duong_dan_anh, thoi_gian_tai_len) VALUES (?, ?, NOW())";
         $stmtImage = $conn->prepare($sqlImage);
         if ($stmtImage === false) {
              
              $conn->rollback();
              die("Lỗi chuẩn bị truy vấn INSERT images: " . $conn->error);
          }


        for ($i = 0; $i < $file_count; $i++) {

            
            if (isset($_FILES['room_images']['error'][$i]) && $_FILES['room_images']['error'][$i] === UPLOAD_ERR_OK) {

                $file_tmp = $_FILES['room_images']['tmp_name'][$i];
                $file_name = $_FILES['room_images']['name'][$i]; 
                $file_size = $_FILES['room_images']['size'][$i];
                $file_type = $_FILES['room_images']['type'][$i];

                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

                
                $max_file_size_per_image = 10 * 1024 * 1024; 
                if ($file_size > $max_file_size_per_image || !in_array($file_ext, $allowed_extensions) || getimagesize($file_tmp) === false) {
                     error_log("Skipping invalid or too large image: " . $file_name);
                     $image_insert_success = false; 
                     continue; 
                }

                
                $new_file_name = uniqid('room_img_', true) . '.' . $file_ext;
                $target_file_path = $upload_directory . $new_file_name;

                
                if (move_uploaded_file($file_tmp, $target_file_path)) {
                    
                    $image_path_to_save = '/uploads/rooms/' . $new_file_name;

                    
                    $bindImageSuccess = $stmtImage->bind_param('is', $new_room_id, $image_path_to_save);

                     if ($bindImageSuccess === false) {
                          error_log("Lỗi gán tham số truy vấn INSERT images: " . $stmtImage->error);
                          $image_insert_success = false; 
                          
                          continue; 
                     }

                    $executeImageSuccess = $stmtImage->execute();

                    if ($executeImageSuccess === false) {
                         error_log("Lỗi thực thi INSERT images cho file " . $file_name . ": " . $stmtImage->error);
                         $image_insert_success = false; 
                         
                     }

                } else {
                    
                    error_log("Lỗi khi di chuyển file " . $file_name);
                    $image_insert_success = false; 
                    
                }
            } else {
                 
                 error_log("Lỗi tải lên file " . ($_FILES['room_images']['name'][$i] ?? 'Unknown file') . ": Code " . $_FILES['room_images']['error'][$i]);
                 $image_insert_success = false; 
                 
            }
        } 

        $stmtImage->close(); 

         
        
        

    } else {
        
        
        
        
         
         
         $conn->rollback();
         header("Location: them_tin.php?error=" . urlencode("Lỗi tải file: Kiểm tra lại form và input file."));
         exit();
    }


    
    $amenity_insert_success = true; 

    if (!empty($tien_ich_duoc_chon)) {
        $sqlAmenity = "INSERT INTO room_amenities (ma_phong, ma_tien_ich) VALUES (?, ?)";
        $stmtAmenity = $conn->prepare($sqlAmenity);
        if ($stmtAmenity === false) {
             $conn->rollback();
             die("Lỗi chuẩn bị truy vấn INSERT room_amenities: " . $conn->error);
         }

        foreach ($tien_ich_duoc_chon as $ma_tien_ich) {
             $ma_tien_ich = (int) $ma_tien_ich; 

            $bindAmenitySuccess = $stmtAmenity->bind_param('ii', $new_room_id, $ma_tien_ich); 
             if ($bindAmenitySuccess === false) {
                  error_log("Lỗi gán tham số truy vấn INSERT room_amenities: " . $stmtAmenity->error);
                  $amenity_insert_success = false;
                  continue;
             }

            $executeAmenitySuccess = $stmtAmenity->execute();
            if ($executeAmenitySuccess === false) {
                 error_log("Lỗi thực thi truy vấn INSERT room_amenities cho ma_tien_ich " . $ma_tien_ich . ": " . $stmtAmenity->error);
                 $amenity_insert_success = false;
             }
        }

        $stmtAmenity->close();
    }


    
    
    
    
    $conn->commit();

    
    header("Location: them_tin.php?status=success_add_room"); 
    exit();


} else {
    
    header("Location: them_tin.php");
    exit();
}



?>