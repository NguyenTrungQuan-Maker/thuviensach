<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: dangnhap.php?error=not_logged_in");
    exit();
}

require_once __DIR__ . '/../config/db.php'; 

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $avatar_path_to_save = null; 

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {

        $file = $_FILES['profile_picture'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name']; 
        $file_size = $file['size'];
        $file_type = $file['type'];
        $file_error = $file['error'];

        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        $upload_directory = __DIR__ . '/phongtro/public/uploads/avatars/';
        if (!is_dir($upload_directory)) {
             mkdir($upload_directory, 0777, true); 
         }

        if ($file_error !== UPLOAD_ERR_OK) {
             $error_message = 'Lỗi tải file lên.';
             if ($file_error == UPLOAD_ERR_INI_SIZE || $file_error == UPLOAD_ERR_FORM_SIZE) {
                 $error_message = 'Kích thước file quá lớn.';
             }
             header("Location: chinhsua_thongtin.php?error=" . urlencode($error_message));
             exit();
        }
        $max_file_size = 5 * 1024 * 1024; // 5 MB
        if ($file_size > $max_file_size) {
            header("Location: chinhsua_thongtin.php?error=" . urlencode("Kích thước file ảnh quá lớn (tối đa 5MB)."));
            exit();
        }

        if (!in_array($file_ext, $allowed_extensions)) {
            header("Location: chinhsua_thongtin.php?error=" . urlencode("Chỉ cho phép tải file ảnh JPG, JPEG, PNG, GIF."));
            exit();
        }

        $image_info = getimagesize($file_tmp);
        if ($image_info === false) {
             header("Location: chinhsua_thongtin.php?error=" . urlencode("File tải lên không phải là ảnh hợp lệ."));
             exit();
        }
        $new_file_name = uniqid('avatar_', true) . '.' . $file_ext; 
        $target_file_path = $upload_directory . $new_file_name; 

        if (move_uploaded_file($file_tmp, $target_file_path)) {
            $avatar_path_to_save = '/uploads/avatars/' . $new_file_name;

        } else {
            // Lỗi khi di chuyển file
            header("Location: chinhsua_thongtin.php?error=" . urlencode("Lỗi khi lưu file ảnh."));
            exit();
        }
    }

    $email = $_POST['email'] ?? '';
    $full_name = $_POST['full_name'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'] ?? null;


    $sql = "UPDATE users SET email = ?, so_dien_thoai = ?, ten_day_du = ?, dia_chi = ?";
    $bind_params = 'ssss'; 
    $bind_values = [$email, $phone, $full_name, $address]; 

    if ($avatar_path_to_save !== null) {
        $sql .= ", anh_dai_dien = ?";
        $bind_params .= 's'; 
        $bind_values[] = $avatar_path_to_save;
    }

    $sql .= " WHERE ma_nguoi_dung = ?";
    $bind_params .= 'i';
    $bind_values[] = $user_id; 


    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        // Log lỗi prepare
        die("Lỗi chuẩn bị truy vấn cập nhật thông tin và ảnh đại diện: " . $conn->error);
    }

    $bind_params_and_values = array_merge([$bind_params], $bind_values);
    call_user_func_array([$stmt, 'bind_param'], $bind_params_and_values);

    // Thực thi statement
    $executeSuccess = $stmt->execute();

     // Kiểm tra lỗi execute
     if ($executeSuccess === false) {
         // Log lỗi execute
          die("Lỗi thực thi truy vấn cập nhật thông tin và ảnh đại diện: " . $stmt->error);
     }


    // 6. Kiểm tra kết quả cập nhật
    if ($stmt->affected_rows > 0 || ($avatar_path_to_save !== null && $stmt->affected_rows === 0)) {
         if ($avatar_path_to_save !== null) {
              $_SESSION['profile_picture_path'] = $avatar_path_to_save; // Lưu đường dẫn ảnh mới vào session
         }

        header("Location: chinhsua_thongtin.php?status=success_update_profile");
        exit();

    } else {
   
        header("Location: chinhsua_thongtin.php?status=no_change_profile");
        exit();
    }

    // Đóng statement
    $stmt->close();

} else {
    
    header("Location: chinhsua_thongtin.php");
    exit();
}

?>