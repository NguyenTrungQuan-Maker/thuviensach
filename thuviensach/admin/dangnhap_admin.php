<?php
session_start();
include '../db.php';
$message = ""; 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ten_dang_nhap = $_POST['ten_dang_nhap'];
    $matkhau_nhap = $_POST['matkhau'];

    if (!($conn instanceof mysqli)) {
        $message = "<div class='alert alert-danger'>Lỗi kết nối CSDL. Vui lòng thử lại sau.</div>";
    } else {
        $sql = "SELECT IDNguoiDung, TenDangNhap, MatKhau, LoaiTaiKhoan FROM nguoidung WHERE TenDangNhap = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            $message = "<div class='alert alert-danger'>Lỗi chuẩn bị truy vấn. Vui lòng thử lại sau.</div>";
        } else {
            $stmt->bind_param("s", $ten_dang_nhap);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if ($matkhau_nhap === $user['MatKhau']) {
                    if ($user['LoaiTaiKhoan'] === 'admin') {
                        $_SESSION['loggedin'] = true;
                        $_SESSION['user_id'] = $user['IDNguoiDung'];
                        $_SESSION['username'] = $user['TenDangNhap'];
                        $_SESSION['user_type'] = $user['LoaiTaiKhoan'];

                        header("Location: index.php"); 
                        exit();
                    } else {
                        $message = "<div class='alert alert-warning'>Tài khoản này không có quyền truy cập Admin.</div>";
                    }
                } else {
                    $message = "<div class='alert alert-danger'>Tên đăng nhập hoặc mật khẩu không đúng.</div>";
                }
            } else {
                $message = "<div class='alert alert-danger'>Tên đăng nhập hoặc mật khẩu không đúng.</div>";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="login-container">
            <h2>Đăng nhập Admin</h2>

            <?php
            if (isset($_SESSION['message'])) {
                echo $_SESSION['message'];
                unset($_SESSION['message']); 
            }
            echo $message; 
            ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label for="ten_dang_nhap" class="form-label">Tên đăng nhập</label>
                    <input type="text" class="form-control" id="ten_dang_nhap" name="ten_dang_nhap" required>
                </div>
                <div class="mb-3">
                    <label for="matkhau" class="form-label">Mật khẩu</label>
                    <input type="password" class="form-control" id="matkhau" name="matkhau" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>