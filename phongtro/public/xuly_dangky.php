<?php

require_once __DIR__ . '/../config/db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';

    $full_name = $_POST['full_name'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'] ?? null;

    $hashed_password = null;
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    } else {
        
        echo "Lỗi: Mật khẩu không được để trống.";
        exit();
    }

    $sqlCheckExist = "SELECT COUNT(*) FROM users WHERE ten_dang_nhap = ? OR email = ?";

    $stmtCheckExist = $conn->prepare($sqlCheckExist);

    if ($stmtCheckExist === false) {
   
        die("Lỗi chuẩn bị truy vấn kiểm tra tồn tại: " . $conn->error);
    }

    $stmtCheckExist->bind_param('ss', $username, $email);

    $executeCheckSuccess = $stmtCheckExist->execute();

     if ($executeCheckSuccess === false) {
         die("Lỗi thực thi truy vấn kiểm tra tồn tại: " . $stmtCheckExist->error);
     }

    $resultCheckExist = $stmtCheckExist->get_result();
    $countExist = $resultCheckExist->fetch_row()[0]; 

    $stmtCheckExist->close();


    if ($countExist > 0) {
 
        echo "Lỗi: Tên đăng nhập hoặc Email đã tồn tại. Vui lòng chọn tên khác.";

    } else {
        
        $sqlInsert = "INSERT INTO users (ten_dang_nhap, mat_khau, email, so_dien_thoai, ten_day_du, dia_chi, vai_tro)
                      VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmtInsert = $conn->prepare($sqlInsert);

        if ($stmtInsert === false) {
             die("Lỗi chuẩn bị truy vấn INSERT: " . $conn->error);
        }

        $role = 'user'; 
        $stmtInsert->bind_param('sssssss',
            $username,
            $hashed_password, 
            $email,
            $phone,
            $full_name,
            $address,
            $role 
        );

        $executeInsertSuccess = $stmtInsert->execute();

        if ($executeInsertSuccess) {
            header("Location: dangnhap.php?registration=success"); // Giả định trang đăng nhập là dangnhap.php
            exit(); 

        } else {

            echo "Có lỗi xảy ra khi lưu dữ liệu: " . $stmtInsert->error;

        }

        $stmtInsert->close();
    }

} else {

    header("Location: dangky.php");
    exit();
}

?>