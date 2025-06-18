<?php

session_start();

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {

    if (isset($_SESSION['loaitaikhoan']) && $_SESSION['loaitaikhoan'] === 'admin') {
        header("Location: admin/index.php");
    } else {

        header("Location: index.php");
    }
    exit();
}

include 'db.php';

$username = $password = "";
$username_err = $password_err = $login_err = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty(trim($_POST["username"]))) {
        $username_err = "Vui lòng nhập tên đăng nhập.";
    } else {
        $username = trim($_POST["username"]);
    }


    if (empty(trim($_POST["password"]))) {
        $password_err = "Vui lòng nhập mật khẩu.";
    } else {
        $password = trim($_POST["password"]);
    }

    if (empty($username_err) && empty($password_err)) {


        if (!($conn instanceof mysqli)) {
            $login_err = "Lỗi kết nối CSDL. Vui lòng thử lại sau.";
        } else {

            $sql = "SELECT IDNguoiDung, TenDangNhap, MatKhau, LoaiTaiKhoan, HoTen FROM nguoidung WHERE TenDangNhap = ?";

            if ($stmt = $conn->prepare($sql)) {

                $stmt->bind_param("s", $param_username);


                $param_username = $username;

                if ($stmt->execute()) {

                    $stmt->store_result();


                    if ($stmt->num_rows == 1) {
                        
                        $stmt->bind_result($id_nguoi_dung, $ten_dang_nhap_db, $password_from_db, $loai_tai_khoan, $ho_ten);

                        if ($stmt->fetch()) {

                            if ($password === $password_from_db) {

                                
                                session_regenerate_id(true); 

                                
                                $_SESSION["loggedin"] = true;
                                $_SESSION["user_id"] = $id_nguoi_dung;
                                $_SESSION["username"] = $ten_dang_nhap_db;
                                $_SESSION["loaitaikhoan"] = $loai_tai_khoan;
                                $_SESSION["user_name_display"] = $ho_ten;


                                $_SESSION["ma_so_dg"] = $id_nguoi_dung; 

                                if (isset($_SESSION['redirect_after_login'])) {
                                    $redirect_page = $_SESSION['redirect_after_login'];
                                    unset($_SESSION['redirect_after_login']);
                                    header("location: " . $redirect_page);
                                } else {

                                    if ($loai_tai_khoan === 'admin') {
                                        header("Location: admin/index.php");
                                    } else {
                                        header("Location: index.php");
                                    }
                                }
                                exit();
                            } else {

                                $login_err = "Tên đăng nhập hoặc mật khẩu không hợp lệ.";
                            }
                        }
                    } else {

                        $login_err = "Tên đăng nhập hoặc mật khẩu không hợp lệ.";
                    }
                } else {

                    $login_err = "Đã xảy ra lỗi. Vui lòng thử lại sau.";
                    error_log("Lỗi thực thi truy vấn đăng nhập (nguoidung): " . $stmt->error); // Ghi log lỗi chi tiết
                }

                $stmt->close();
            } else {
            
                $login_err = "Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.";
                error_log("Lỗi chuẩn bị truy vấn đăng nhập (nguoidung): " . $conn->error);
            }
        }
    }


    if (!empty($login_err)) {
        $message = "<div class='alert alert-danger text-center'>{$login_err}</div>";
    }
}

if (isset($_SESSION['message'])) {

    if (empty($message) || strpos($message, 'Lỗi') === false) {
        $message = $_SESSION['message'];
    }
    unset($_SESSION['message']);
}

if (isset($_GET['redirect'])) {

    $_SESSION['redirect_after_login'] = htmlspecialchars($_GET['redirect']);
}


if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
    $conn->close();
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Thư viện Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #74ebd5, #9face6);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 20px;
        }

        .login-container {
            max-width: 420px;
            background-color: #ffffff;
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
            width: 100%;
            animation: fadeIn 0.6s ease-in-out;
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 25px;
            font-weight: bold;
            color: #333;
        }

        .form-label {
            font-weight: 600;
            color: #555;
        }

        .form-control {
            height: 48px;
            border-radius: 8px;
            border: 1px solid #ced4da;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
        }

        .invalid-feedback {
            font-size: 0.875rem;
            margin-top: 4px;
        }

        .btn-primary {
            background-color: #4a90e2;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #3a78c2;
        }

        .text-center a {
            color: #4a90e2;
            font-weight: 500;
            text-decoration: none;
        }

        .text-center a:hover {
            text-decoration: underline;
        }

        .alert {
            font-size: 0.95rem;
            padding: 12px;
            border-radius: 8px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

</head>

<body>

    <div class="login-container">
        <h2>Đăng nhập</h2>

        <?php echo $message; 
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-3">
                <label for="username" class="form-label">Tên đăng nhập:</label>
                <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required>
                <div class="invalid-feedback"><?php echo $username_err; ?></div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu:</label>
                <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required>
                <div class="invalid-feedback"><?php echo $password_err; ?></div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">Đăng nhập</button>
            </div>

            <p class="mt-3 text-center">Chưa có tài khoản? <a href="dangky.php">Đăng ký ngay</a>.</p>
        </form>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>