<?php

session_start();

include 'db.php';

$tenDangNhap = $matKhau = $confirm_matKhau = $hoTen = $diaChi = $email = $ngaySinh = $gioiTinh = "";
$tenDangNhap_err = $matKhau_err = $confirm_matKhau_err = $hoTen_err = $diaChi_err = $email_err = $ngaySinh_err = $gioiTinh_err = "";
$registration_success = false;
$message = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty(trim($_POST["username"]))) {
        $tenDangNhap_err = "Vui lòng nhập tên đăng nhập.";
    } else {
        $tenDangNhap = trim($_POST["username"]);
    }

    if (empty(trim($_POST["password"]))) {
        $matKhau_err = "Vui lòng nhập mật khẩu.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $matKhau_err = "Mật khẩu phải có ít nhất 6 ký tự.";
    } else {
        $matKhau = trim($_POST["password"]); 
    }

    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_matKhau_err = "Vui lòng xác nhận mật khẩu.";
    } else {
        $confirm_matKhau = trim($_POST["confirm_password"]);
        if (empty($matKhau_err) && ($matKhau != $confirm_matKhau)) {
            $confirm_matKhau_err = "Mật khẩu xác nhận không khớp.";
        }
    }


    if (empty(trim($_POST["ten_docgia"]))) {
        $hoTen_err = "Vui lòng nhập họ và tên.";
    } else {
        $hoTen = trim($_POST["ten_docgia"]);
    }

    if (empty(trim($_POST["dia_chi"]))) {
        $diaChi_err = "Vui lòng nhập địa chỉ.";
    } else {
        $diaChi = trim($_POST["dia_chi"]);
    }

 
    if (empty(trim($_POST["email"]))) {
        $email_err = "Vui lòng nhập email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
         $email_err = "Định dạng email không hợp lệ.";
    } else {
        $email = trim($_POST["email"]);
    }

   
    if (empty(trim($_POST["ngay_sinh"]))) {
        $ngaySinh_err = "Vui lòng nhập ngày sinh.";
    } else {
        $ngaySinh = trim($_POST["ngay_sinh"]);
        
    }


    if (empty(trim($_POST["gioi_tinh"]))) {
        $gioiTinh_err = "Vui lòng chọn giới tính.";
    } else {
        $gioiTinh = trim($_POST["gioi_tinh"]);
    }

    if (empty($tenDangNhap_err) && empty($matKhau_err) && empty($confirm_matKhau_err) && empty($hoTen_err) && empty($diaChi_err) && empty($email_err) && empty($ngaySinh_err) && empty($gioiTinh_err)) {

       
        if (!($conn instanceof mysqli)) {
             $message = "<div class='alert alert-danger'>Lỗi kết nối CSDL. Vui lòng thử lại sau.</div>";
        } else {
           
            $conn->begin_transaction();
            $registration_success = false; 

            try {
                $sql_check_existing = "SELECT IDNguoiDung FROM nguoidung WHERE TenDangNhap = ? OR Email = ?";
                if ($stmt_check_existing = $conn->prepare($sql_check_existing)) {
                    $stmt_check_existing->bind_param("ss", $param_tenDangNhap, $param_email);
                    $param_tenDangNhap = $tenDangNhap;
                    $param_email = $email;
                    $stmt_check_existing->execute();
                    $stmt_check_existing->store_result();

                    if ($stmt_check_existing->num_rows > 0) {
                        
                        $sql_check_username_only = "SELECT IDNguoiDung FROM nguoidung WHERE TenDangNhap = ?";
                         if ($stmt_check_username_only = $conn->prepare($sql_check_username_only)) {
                             $stmt_check_username_only->bind_param("s", $param_tenDangNhap);
                             $stmt_check_username_only->execute();
                             $stmt_check_username_only->store_result();
                             if ($stmt_check_username_only->num_rows > 0) {
                                 $tenDangNhap_err = "Tên đăng nhập này đã tồn tại.";
                             }
                             $stmt_check_username_only->close();
                         }

                         $sql_check_email_only = "SELECT IDNguoiDung FROM nguoidung WHERE Email = ?";
                          if ($stmt_check_email_only = $conn->prepare($sql_check_email_only)) {
                             $stmt_check_email_only->bind_param("s", $param_email);
                             $stmt_check_email_only->execute();
                             $stmt_check_email_only->store_result();
                              if ($stmt_check_email_only->num_rows > 0) {
                                 $email_err = "Email này đã được sử dụng.";
                             }
                             $stmt_check_email_only->close();
                         }

                        $message = "<div class='alert alert-danger text-center'>Đăng ký thất bại: Tên đăng nhập hoặc Email đã tồn tại.</div>";
                        $conn->rollback();

                    } else {
                       
                        $plain_matKhau = $matKhau;

                        $loai_tai_khoan = 'user';

                        $sql_insert_nguoidung = "INSERT INTO nguoidung (TenDangNhap, MatKhau, LoaiTaiKhoan, HoTen, DiaChi, NgaySinh, Email, GioiTinh) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                        if ($stmt_insert_nguoidung = $conn->prepare($sql_insert_nguoidung)) {
                            
                            $stmt_insert_nguoidung->bind_param("ssssssss", $tenDangNhap, $plain_matKhau, $loai_tai_khoan, $hoTen, $diaChi, $ngaySinh, $email, $gioiTinh);

                            if ($stmt_insert_nguoidung->execute()) {
                               
                                $conn->commit(); 
                                $registration_success = true;
                               
                                $newUserId = $conn->insert_id;
                                $message = "<div class='alert alert-success text-center'>Đăng ký tài khoản thành công! ID người dùng của bạn là: " . $newUserId . ". Bạn có thể <a href='dangnhap.php'>Đăng nhập</a> ngay bây giờ.</div>";

                             
                                $tenDangNhap = $matKhau = $confirm_matKhau = $hoTen = $diaChi = $email = $ngaySinh = $gioiTinh = "";

                            } else {
                               
                                $message = "<div class='alert alert-danger text-center'>Lỗi khi thêm người dùng vào CSDL: " . $stmt_insert_nguoidung->error . "</div>";
                                $conn->rollback();
                            }
                            $stmt_insert_nguoidung->close();
                        } else {
                        
                            $message = "<div class='alert alert-danger text-center'>Lỗi hệ thống khi chuẩn bị truy vấn thêm người dùng: " . $conn->error . "</div>";
                             $conn->rollback(); 
                        }
                    }
                    $stmt_check_existing->close();
                } else {
                    
                    $message = "<div class='alert alert-danger text-center'>Lỗi hệ thống khi chuẩn bị kiểm tra tài khoản/email: " . $conn->error . "</div>";
                }

            } catch (Exception $e) {
                
                $conn->rollback(); 
                $message = "<div class='alert alert-danger text-center'>Đã xảy ra lỗi: " . $e->getMessage() . "</div>";
            }

            if ($conn instanceof mysqli && $conn->ping()) {
                $conn->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký Tài khoản - Thư viện Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
       body {
    background: linear-gradient(to right, #4facfe, #00f2fe);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    padding: 20px;
}

.registration-container {
    background-color: #ffffff;
    border-radius: 16px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    padding: 40px;
    max-width: 480px;
    width: 100%;
    animation: fadeIn 0.5s ease;
}

.registration-container h2 {
    text-align: center;
    color: #007bff;
    margin-bottom: 25px;
}

.form-label {
    font-weight: 600;
    color: #333;
}

.form-control,
.form-select {
    border-radius: 12px;
    padding: 10px 14px;
    border: 1px solid #ced4da;
}

.form-control:focus,
.form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

button[type="submit"] {
    background-color: #007bff;
    color: #fff;
    font-weight: bold;
    padding: 10px 16px;
    border: none;
    border-radius: 12px;
    width: 100%;
    transition: background-color 0.3s ease;
}

button[type="submit"]:hover {
    background-color: #0056b3;
}

.alert {
    border-radius: 12px;
    padding: 12px 18px;
    font-size: 15px;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

    </style>
</head>
<body>

<div class="registration-container">
    <h2>Đăng ký Tài khoản Độc giả</h2>

    <?php
        // Hiển thị thông báo thành công hoặc lỗi
        if (!empty($message)) {
            echo $message;
        }
    ?>

    <?php if (!$registration_success): // Chỉ hiển thị form nếu chưa đăng ký thành công ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <h4>Thông tin Tài khoản</h4>
            <div class="mb-3">
                <label for="username" class="form-label">Tên tài khoản (*)</label>
                <input type="text" name="username" id="username" class="form-control <?php echo (!empty($tenDangNhap_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($tenDangNhap); ?>" required>
                <div class="invalid-feedback"><?php echo $tenDangNhap_err; ?></div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu (*)</label>
                <input type="password" name="password" id="password" class="form-control <?php echo (!empty($matKhau_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($matKhau); ?>" required>
                <div class="invalid-feedback"><?php echo $matKhau_err; ?></div>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Xác nhận mật khẩu (*)</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_matKhau_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($confirm_matKhau); ?>" required>
                <div class="invalid-feedback"><?php echo $confirm_matKhau_err; ?></div>
            </div>

            <h4 class="mt-4">Thông tin Độc giả</h4>
             <div class="mb-3">
                <label for="ten_docgia" class="form-label">Họ và tên Độc giả (*)</label>
                <input type="text" name="ten_docgia" id="ten_docgia" class="form-control <?php echo (!empty($hoTen_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($hoTen); ?>" required>
                <div class="invalid-feedback"><?php echo $hoTen_err; ?></div>
            </div>

            <div class="mb-3">
                <label for="dia_chi" class="form-label">Địa chỉ (*)</label>
                <input type="text" name="dia_chi" id="dia_chi" class="form-control <?php echo (!empty($diaChi_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($diaChi); ?>" required>
                <div class="invalid-feedback"><?php echo $diaChi_err; ?></div>
            </div>

             <div class="mb-3">
                <label for="email" class="form-label">Email (*)</label>
                <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>" required>
                <div class="invalid-feedback"><?php echo $email_err; ?></div>
            </div>

             <div class="mb-3">
                <label for="ngay_sinh" class="form-label">Ngày sinh (*)</label>
                <input type="date" name="ngay_sinh" id="ngay_sinh" class="form-control <?php echo (!empty($ngaySinh_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($ngaySinh); ?>" required>
                <div class="invalid-feedback"><?php echo $ngaySinh_err; ?></div>
            </div>

             <div class="mb-3">
                <label for="gioi_tinh" class="form-label">Giới tính (*)</label>
                 <select name="gioi_tinh" id="gioi_tinh" class="form-select <?php echo (!empty($gioiTinh_err)) ? 'is-invalid' : ''; ?>" required>
                     <option value="">-- Chọn giới tính --</option>
                     <option value="Nam" <?php echo ($gioiTinh == 'Nam') ? 'selected' : ''; ?>>Nam</option>
                     <option value="Nữ" <?php echo ($gioiTinh == 'Nữ') ? 'selected' : ''; ?>>Nữ</option>
                     <option value="Khác" <?php echo ($gioiTinh == 'Khác') ? 'selected' : ''; ?>>Khác</option>
                 </select>
                <div class="invalid-feedback"><?php echo $gioiTinh_err; ?></div>
            </div>

            <?php
                // Trường HanSuDung đã bị loại bỏ khỏi form và xử lý PHP vì không có trong bảng nguoidung hiện tại.
            ?>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg">Đăng ký</button>
            </div>

            <p class="mt-3 text-center">Đã có tài khoản? <a href="dangnhap.php">Đăng nhập ngay</a>.</p>
        </form>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
