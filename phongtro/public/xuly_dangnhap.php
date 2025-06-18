<?php

session_start();

require_once __DIR__ . '/../config/db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username_email = $_POST['username_email'] ?? ''; 
    $password = $_POST['password'] ?? '';

    $sql = "SELECT ma_nguoi_dung, ten_dang_nhap, mat_khau, email, vai_tro FROM users WHERE ten_dang_nhap = ? OR email = ?";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Lỗi chuẩn bị truy vấn đăng nhập: " . $conn->error);
    }

    $stmt->bind_param('ss', $username_email, $username_email); 

    $executeSuccess = $stmt->execute();

     if ($executeSuccess === false) {
         die("Lỗi thực thi truy vấn đăng nhập: " . $stmt->error);
     }

    // Lấy kết quả
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc(); 

        if (password_verify($password, $user['mat_khau'])) {

            $_SESSION['user_id'] = $user['ma_nguoi_dung'];
            $_SESSION['username'] = $user['ten_dang_nhap'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['vai_tro'];

            header("Location: index.php?login=success");
            exit(); 

        } else {

            header("Location: dangnhap.php?error=invalid_credentials");
            exit();
        }
    } else {
        header("Location: dangnhap.php?error=invalid_credentials");
        exit();
    }

    $stmt->close();

} else {

    header("Location: dangnhap.php");
    exit();
}

?>