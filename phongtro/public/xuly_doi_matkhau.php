<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: dangnhap.php?error=not_logged_in");
    exit();
}

require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $current_password = $_POST['current_password'] ?? ''; 
    $new_password = $_POST['new_password'] ?? '';     
    $confirm_password = $_POST['confirm_password'] ?? ''; 

    if ($new_password !== $confirm_password) {
        header("Location: doi_matkhau.php?error=new_password_mismatch");
        exit();
    }

    $sqlSelectPassword = "SELECT mat_khau FROM users WHERE ma_nguoi_dung = ?";

    $stmtSelectPassword = $conn->prepare($sqlSelectPassword);
    if ($stmtSelectPassword === false) {
        die("Lỗi chuẩn bị truy vấn lấy mật khẩu: " . $conn->error);
    }



    $executeSelectSuccess = $stmtSelectPassword->execute();
    if ($executeSelectSuccess === false) {
         die("Lỗi thực thi truy vấn lấy mật khẩu: " . $stmtSelectPassword->error);
    }


    $resultPassword = $stmtSelectPassword->get_result();

    if ($resultPassword->num_rows == 1) {
        $user = $resultPassword->fetch_assoc();
        $hashed_password_in_db = $user['mat_khau']; 
    } else {

        session_unset();
        session_destroy();
        header("Location: dangnhap.php?error=user_not_found");
        exit();
    }


    $stmtSelectPassword->close();

    if (password_verify($current_password, $hashed_password_in_db)) {

        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $sqlUpdatePassword = "UPDATE users SET mat_khau = ? WHERE ma_nguoi_dung = ?";

        $stmtUpdatePassword = $conn->prepare($sqlUpdatePassword);
        if ($stmtUpdatePassword === false) {
            die("Lỗi chuẩn bị truy vấn cập nhật mật khẩu: " . $conn->error);
        }

        $bindUpdateSuccess = $stmtUpdatePassword->bind_param('si', $new_hashed_password, $user_id);
        if ($bindUpdateSuccess === false) {
             die("Lỗi gán tham số truy vấn cập nhật mật khẩu: " . $stmtUpdatePassword->error);
        }

        $executeUpdateSuccess = $stmtUpdatePassword->execute();

        if ($executeUpdateSuccess) {
            header("Location: doi_matkhau.php?status=success");
            exit();

        } else {

            echo "Có lỗi xảy ra khi cập nhật mật khẩu: " . $stmtUpdatePassword->error;
           
        }

        $stmtUpdatePassword->close();

    } else {

        header("Location: doi_matkhau.php?error=invalid_old_password");
        exit();
    }

} else {
    header("Location: doi_matkhau.php");
    exit();
}
?>