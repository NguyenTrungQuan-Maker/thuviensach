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

    
    
    $sqlCheckOwner = "SELECT ma_nguoi_dung FROM rooms WHERE ma_phong = ?";
    $stmtCheckOwner = $conn->prepare($sqlCheckOwner);
    if ($stmtCheckOwner === false) {
        
        error_log("Lỗi chuẩn bị truy vấn kiểm tra chủ sở hữu khi xóa: " . $conn->error);
        header("Location: quanly_tin_dang.php?error=" . urlencode("Có lỗi xảy ra, vui lòng thử lại."));
        exit();
    }
    $stmtCheckOwner->bind_param('i', $room_id);
    $stmtCheckOwner->execute();
    $resultCheckOwner = $stmtCheckOwner->get_result();

    if ($resultCheckOwner->num_rows === 0) {
        
        $stmtCheckOwner->close();
        header("Location: quanly_tin_dang.php?error=" . urlencode("Không tìm thấy tin đăng."));
        exit();
    }

    $owner_row = $resultCheckOwner->fetch_assoc();
    $stmtCheckOwner->close();

    
    if ($owner_row['ma_nguoi_dung'] !== $user_id) {
        
        header("Location: quanly_tin_dang.php?error=" . urlencode("Bạn không có quyền xóa tin đăng này."));
        exit();
    }


    
    
    $conn->begin_transaction();

    $delete_success = true; 


    
    
    $sqlGetImages = "SELECT duong_dan_anh FROM images WHERE ma_phong = ?";
    $stmtGetImages = $conn->prepare($sqlGetImages);
     if ($stmtGetImages === false) {
         error_log("Lỗi chuẩn bị truy vấn lấy ảnh để xóa: " . $conn->error);
         $delete_success = false; 
     } else {
          $stmtGetImages->bind_param('i', $room_id);
          $stmtGetImages->execute();
          $resultGetImages = $stmtGetImages->get_result();
          $image_paths = $resultGetImages->fetch_all(MYSQLI_ASSOC); 
          $resultGetImages->free();
          $stmtGetImages->close();
     }


    
    
    $sqlDeleteImages = "DELETE FROM images WHERE ma_phong = ?";
    $stmtDeleteImages = $conn->prepare($sqlDeleteImages);
    if ($stmtDeleteImages === false) {
         error_log("Lỗi chuẩn bị truy vấn xóa ảnh: " . $conn->error);
         $delete_success = false;
     } else {
         $stmtDeleteImages->bind_param('i', $room_id);
         if ($stmtDeleteImages->execute() === false) {
             error_log("Lỗi thực thi truy vấn xóa ảnh cho phong ID " . $room_id . ": " . $stmtDeleteImages->error);
             $delete_success = false;
         }
         $stmtDeleteImages->close();
     }


    
    
    $sqlDeleteAmenities = "DELETE FROM room_amenities WHERE ma_phong = ?";
    $stmtDeleteAmenities = $conn->prepare($sqlDeleteAmenities);
     if ($stmtDeleteAmenities === false) {
         error_log("Lỗi chuẩn bị truy vấn xóa tiện ích: " . $conn->error);
         $delete_success = false;
     } else {
         $stmtDeleteAmenities->bind_param('i', $room_id);
         if ($stmtDeleteAmenities->execute() === false) {
             error_log("Lỗi thực thi truy vấn xóa tiện ích cho phong ID " . $room_id . ": " . $stmtDeleteAmenities->error);
             $delete_success = false;
         }
         $stmtDeleteAmenities->close();
     }


    
    
    $sqlDeleteRoom = "DELETE FROM rooms WHERE ma_phong = ? AND ma_nguoi_dung = ?"; 
    $stmtDeleteRoom = $conn->prepare($sqlDeleteRoom);
     if ($stmtDeleteRoom === false) {
         error_log("Lỗi chuẩn bị truy vấn xóa phòng: " . $conn->error);
         $delete_success = false;
     } else {
         $stmtDeleteRoom->bind_param('ii', $room_id, $user_id);
         if ($stmtDeleteRoom->execute() === false) {
             error_log("Lỗi thực thi truy vấn xóa phòng ID " . $room_id . " user ID " . $user_id . ": " . $stmtDeleteRoom->error);
             $delete_success = false;
         }
         $stmtDeleteRoom->close();
     }


    
    
    
    if ($delete_success === true && !empty($image_paths)) {
        $upload_directory_base = __DIR__ . '/'; 
        foreach ($image_paths as $image) {
            $file_path = $upload_directory_base . ltrim($image['duong_dan_anh'], '/'); 
            if (file_exists($file_path)) {
                if (!unlink($file_path)) {
                    error_log("Lỗi khi xóa file anh: " . $file_path);
                    
                    
                }
            } else {
                 error_log("File anh khong ton tai khi xoa: " . $file_path);
            }
        }
    }


    
    if ($delete_success) {
        
        $conn->commit();
        
        header("Location: quanly_tin_dang.php?status=delete_success");
        exit();
    } else {
        
        $conn->rollback();
        
        header("Location: quanly_tin_dang.php?error=" . urlencode("Có lỗi xảy ra khi xóa tin đăng."));
        exit();
    }

} else {
    
    header("Location: quanly_tin_dang.php");
    exit();
}



?>