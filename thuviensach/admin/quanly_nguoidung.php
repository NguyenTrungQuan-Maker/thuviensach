<?php

session_start(); 

include '../admin/db.php'; 

$message = ''; 
$editMode = false; 
$currentUser = []; 

$loaiTaiKhoanOptions = ['user', 'admin']; 

if (!($conn instanceof mysqli)) {
     $message = "<div class='alert alert-danger'>Lỗi: Kết nối CSDL . Vui lòng kiểm tra file db.php</div>";
    
     goto display_page; 
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
 
    $idNguoiDungToDelete = (int)trim($_GET['id']); 

    if ($idNguoiDungToDelete > 0) { 
        $conn->begin_transaction();

        try {
            $phieuMuonList = [];
           
            $sql_get_phieumuon = "SELECT SoPhieuMuon FROM muonsach WHERE MaSoDG = ?";
            if ($stmt_get_phieuMuon = $conn->prepare($sql_get_phieumuon)) {
        
                $stmt_get_phieuMuon->bind_param("i", $idNguoiDungToDelete);
                $stmt_get_phieuMuon->execute();
                $result_phieuMuon = $stmt_get_phieuMuon->get_result();
                while ($row = $result_phieuMuon->fetch_assoc()) {
                    $phieuMuonList[] = $row['SoPhieuMuon'];
                }
                $stmt_get_phieuMuon->close();
            } else {
                 throw new Exception("Lỗi khi chuẩn bị truy vấn lấy phiếu mượn: " . $conn->error);
            }
            if (!empty($phieuMuonList)) {
               
                $placeholders = implode(',', array_fill(0, count($phieuMuonList), '?'));
                $sql_delete_chitiet = "DELETE FROM chitietphieumuon WHERE SoPhieuMuon IN ($placeholders)";
                 if ($stmt_delete_chitiet = $conn->prepare($sql_delete_chitiet)) {
               
                    $types = str_repeat('s', count($phieuMuonList)); // Tất cả là string
                   
                    call_user_func_array([$stmt_delete_chitiet, 'bind_param'], array_merge([$types], $phieuMuonList));
                    $stmt_delete_chitiet->execute();
                    $stmt_delete_chitiet->close();
                 } else {
                     throw new Exception("Lỗi khi chuẩn bị truy vấn xóa chi tiết phiếu mượn: " . $conn->error);
                 }


                 $sql_delete_trasach = "DELETE FROM trasach WHERE SoPhieuMuon IN ($placeholders)";
                 if ($stmt_delete_trasach = $conn->prepare($sql_delete_trasach)) {
                    $types = str_repeat('s', count($phieuMuonList));
                     call_user_func_array([$stmt_delete_trasach, 'bind_param'], array_merge([$types], $phieuMuonList));
                    $stmt_delete_trasach->execute();
                    $stmt_delete_trasach->close();
                 } else {
                     throw new Exception("Lỗi khi chuẩn bị truy vấn xóa trả sách: " . $conn->error);
                 }
            }

            $sql_delete_muonsach = "DELETE FROM muonsach WHERE MaSoDG = ?";
            if ($stmt_delete_muonsach = $conn->prepare($sql_delete_muonsach)) {
                // Bind param: i (MaSoDG là INT)
                $stmt_delete_muonsach->bind_param("i", $idNguoiDungToDelete);
                $stmt_delete_muonsach->execute();
                $stmt_delete_muonsach->close();
            } else {
                 throw new Exception("Lỗi khi chuẩn bị truy vấn xóa phiếu mượn: " . $conn->error);
            }
            $sql_delete_nguoidung = "DELETE FROM nguoidung WHERE IDNguoiDung = ?";
            $stmt_delete_nguoidung = $conn->prepare($sql_delete_nguoidung);
            if ($stmt_delete_nguoidung === false) {
                 throw new Exception("Lỗi chuẩn bị truy vấn xóa người dùng chính: " . $conn->error);
            }
          
            $stmt_delete_nguoidung->bind_param("i", $idNguoiDungToDelete);

            if ($stmt_delete_nguoidung->execute()) {
                
                $conn->commit(); 
                $message = "<div class='alert alert-success'>Đã xóa người dùng thành công.</div>";
                
                header("Location: quanly_nguoidung.php?msg=" . urlencode(strip_tags($message)));
                exit(); 
            } else {
                
                throw new Exception("Lỗi khi xóa người dùng: " . $stmt_delete_nguoidung->error);
            }
            $stmt_delete_nguoidung->close();


        } catch (Exception $e) {
          
            $conn->rollback();
            $message = "<div class='alert alert-danger'>Có lỗi xảy ra khi xóa người dùng: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>Mã người dùng cần xóa không hợp lệ.</div>";
    }
}



if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
   
    $idNguoiDungToEdit = (int)trim($_GET['id']);

    if ($idNguoiDungToEdit > 0) { 
        try {
           
            $sql = "SELECT IDNguoiDung, TenDangNhap, MatKhau, LoaiTaiKhoan, HoTen, DiaChi, NgaySinh, Email, GioiTinh FROM nguoidung WHERE IDNguoiDung = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Lỗi prepare SQL lấy thông tin người dùng: " . $conn->error);
            }
            // Bind param: i (IDNguoiDung là INT)
            $stmt->bind_param("i", $idNguoiDungToEdit);
            $stmt->execute();
            $result = $stmt->get_result(); // Lấy kết quả từ prepared statement

            if ($result->num_rows === 1) {
                $currentUser = $result->fetch_assoc(); // Lấy dữ liệu dòng kết quả
                $editMode = true; // Bật chế độ sửa
            } else {
                $message = "<div class='alert alert-warning'>Không tìm thấy người dùng có Mã '" . htmlspecialchars($idNguoiDungToEdit) . "'.</div>";
            }

            $stmt->close();

        } catch (Exception $e) {
            $message = "<div class='alert alert-danger'>Có lỗi xảy ra khi lấy thông tin người dùng: " . $e->getMessage() . "</div>";
        }
    } else {
         $message = "<div class='alert alert-warning'>Không có Mã người dùng được chỉ định để sửa.</div>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    
    if (isset($_POST['add_nguoidung'])) {
       
        $tenDangNhap = trim($_POST['ten_dang_nhap'] ?? '');
        $matKhau = trim($_POST['mat_khau'] ?? ''); 
        $loaiTaiKhoan = trim($_POST['loai_tai_khoan'] ?? 'user');
        $hoTen = trim($_POST['ho_ten'] ?? '');
        $diaChi = trim($_POST['dia_chi'] ?? '');
        $ngaySinh = trim($_POST['ngay_sinh'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $gioiTinh = trim($_POST['gioi_tinh'] ?? '');

        if (empty($tenDangNhap) || empty($matKhau) || empty($hoTen) || empty($diaChi) || empty($ngaySinh) || empty($email) || empty($gioiTinh)) {
             $message = "<div class='alert alert-warning'>Vui lòng điền đầy đủ thông tin bắt buộc!</div>";
        } else {
            try {
            
                $plain_matKhau = $matKhau; 

                $sql = "INSERT INTO nguoidung (TenDangNhap, MatKhau, LoaiTaiKhoan, HoTen, DiaChi, NgaySinh, Email, GioiTinh) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

               
                $stmt = $conn->prepare($sql);
                 if ($stmt === false) {
                     throw new Exception("Lỗi prepare SQL thêm người dùng: " . $conn->error);
                }

                $stmt->bind_param("ssssssss", $tenDangNhap, $plain_matKhau, $loaiTaiKhoan, $hoTen, $diaChi, $ngaySinh, $email, $gioiTinh);

               
                if ($stmt->execute()) {
                   
                    $newUserId = $conn->insert_id; 
                    $message = "<div class='alert alert-success'>Thêm người dùng mới thành công! ID: " . $newUserId . "</div>";
                   
                     header("Location: quanly_nguoidung.php?msg=" . urlencode(strip_tags($message)));
                     exit();

                } else {
                 
                     if ($conn->errno == 1062) { 
                        
                          $message = "<div class='alert alert-danger'>Lỗi: Tên đăng nhập hoặc Email đã tồn tại.</div>";
                     } else {
                         $message = "<div class='alert alert-danger'>Lỗi khi thêm người dùng: " . $stmt->error . "</div>";
                     }
                }

                // Đóng prepared statement
                $stmt->close();

            } catch (Exception $e) {
                // Bắt các ngoại lệ khác có thể xảy ra
                $message = "<div class='alert alert-danger'>Có lỗi xảy ra: " . $e->getMessage() . "</div>";
            }
        }
    }

    if (isset($_POST['update_nguoidung'])) {
    
        $idNguoiDung = (int)trim($_POST['id_nguoi_dung'] ?? '');
        $tenDangNhap = trim($_POST['ten_dang_nhap'] ?? '');
       
        $matKhauMoi = trim($_POST['mat_khau_moi'] ?? ''); 
        $loaiTaiKhoan = trim($_POST['loai_tai_khoan'] ?? 'user');
        $hoTen = trim($_POST['ho_ten'] ?? '');
        $diaChi = trim($_POST['dia_chi'] ?? '');
        $ngaySinh = trim($_POST['ngay_sinh'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $gioiTinh = trim($_POST['gioi_tinh'] ?? '');

        
        if ($idNguoiDung <= 0 || empty($tenDangNhap) || empty($hoTen) || empty($diaChi) || empty($ngaySinh) || empty($email) || empty($gioiTinh)) {
             $message = "<div class='alert alert-warning'>Vui lòng điền đầy đủ thông tin bắt buộc và đảm bảo có Mã người dùng hợp lệ!</div>";
            
             if ($idNguoiDung > 0 && ($conn instanceof mysqli)) {
                 $sql_fetch_current = "SELECT IDNguoiDung, TenDangNhap, MatKhau, LoaiTaiKhoan, HoTen, DiaChi, NgaySinh, Email, GioiTinh FROM nguoidung WHERE IDNguoiDung = ? LIMIT 1";
                 if ($stmt_fetch = $conn->prepare($sql_fetch_current)) {
                   
                     $stmt_fetch->bind_param("i", $idNguoiDung);
                     $stmt_fetch->execute();
                     $result_fetch = $stmt_fetch->get_result();
                     if ($result_fetch->num_rows === 1) {
                         $currentUser = $result_fetch->fetch_assoc();
                         $editMode = true; // Vẫn ở chế độ sửa
                     }
                     $stmt_fetch->close();
                 }
             }

        } else {
            try {
            
                $conn->begin_transaction();

                $sql = "UPDATE nguoidung SET TenDangNhap = ?, LoaiTaiKhoan = ?, HoTen = ?, DiaChi = ?, NgaySinh = ?, Email = ?, GioiTinh = ?";
                $params = [$tenDangNhap, $loaiTaiKhoan, $hoTen, $diaChi, $ngaySinh, $email, $gioiTinh];
                $types = "sssssss"; 

                if (!empty($matKhauMoi)) {
                   
                    $plain_matKhau_moi = $matKhauMoi; 

                    $sql .= ", MatKhau = ?"; 
                    $params[] = $plain_matKhau_moi; 
                    $types .= "s"; 
                }

                $sql .= " WHERE IDNguoiDung = ?"; 
                $params[] = $idNguoiDung; 
                $types .= "i";

                // Chuẩn bị câu lệnh
                $stmt = $conn->prepare($sql);
                 if ($stmt === false) {
                     throw new Exception("Lỗi prepare SQL cập nhật người dùng: " . $conn->error);
                }

                call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));

                // Thực thi câu lệnh
                if ($stmt->execute()) {
                   
                    if ($stmt->affected_rows > 0) {
                        $message = "<div class='alert alert-success'>Cập nhật thông tin người dùng có Mã '" . htmlspecialchars($idNguoiDung) . "' thành công!</div>";
                    } else {
                        //
                        $message = "<div class='alert alert-info'>Không có thay đổi nào được lưu cho người dùng có Mã '" . htmlspecialchars($idNguoiDung) . "'. (Có thể không tìm thấy mã hoặc dữ liệu không đổi)</div>";
                    }
                    $conn->commit(); 
                   
                    header("Location: quanly_nguoidung.php?msg=" . urlencode(strip_tags($message)));
                    exit();

                } else {
                    // Xử lý lỗi khi thực thi
                     if ($conn->errno == 1062) { 
                        
                          $message = "<div class='alert alert-danger'>Lỗi: Tên đăng nhập hoặc Email đã tồn tại.</div>";
                     } else {
                         $message = "<div class='alert alert-danger'>Lỗi khi cập nhật người dùng: " . $stmt->error . "</div>";
                     }
                     $conn->rollback(); 
                      if ($idNguoiDung > 0 && ($conn instanceof mysqli)) {
                         $sql_fetch_current = "SELECT IDNguoiDung, TenDangNhap, MatKhau, LoaiTaiKhoan, HoTen, DiaChi, NgaySinh, Email, GioiTinh FROM nguoidung WHERE IDNguoiDung = ? LIMIT 1";
                         if ($stmt_fetch = $conn->prepare($sql_fetch_current)) {
                             
                             $stmt_fetch->bind_param("i", $idNguoiDung);
                             $stmt_fetch->execute();
                             $result_fetch = $stmt_fetch->get_result();
                             if ($result_fetch->num_rows === 1) {
                                 $currentUser = $result_fetch->fetch_assoc();
                                 $editMode = true; 
                             }
                             $stmt_fetch->close();
                         }
                     }
                }

                $stmt->close();

            } catch (Exception $e) {
             
                $conn->rollback(); 
                $message = "<div class='alert alert-danger'>Có lỗi xảy ra: " . $e->getMessage() . "</div>";
             
                 if ($idNguoiDung > 0 && ($conn instanceof mysqli)) {
                     $sql_fetch_current = "SELECT IDNguoiDung, TenDangNhap, MatKhau, LoaiTaiKhoan, HoTen, DiaChi, NgaySinh, Email, GioiTinh FROM nguoidung WHERE IDNguoiDung = ? LIMIT 1";
                     if ($stmt_fetch = $conn->prepare($sql_fetch_current)) {
                       
                         $stmt_fetch->bind_param("i", $idNguoiDung);
                         $stmt_fetch->execute();
                         $result_fetch = $stmt_fetch->get_result();
                         if ($result_fetch->num_rows === 1) {
                             $currentUser = $result_fetch->fetch_assoc();
                             $editMode = true; // Vẫn ở chế độ sửa
                         }
                         $stmt_fetch->close();
                     }
                 }
            }
        }
    }
}


// --- Lấy danh sách Người dùng để hiển thị ---
$nguoiDungList = []; 
if ((!$editMode || !empty($message)) && ($conn instanceof mysqli)) {
    $sql = "SELECT IDNguoiDung, TenDangNhap, MatKhau, LoaiTaiKhoan, HoTen, Email, GioiTinh FROM nguoidung ORDER BY IDNguoiDung DESC"; // Sắp xếp theo ID mới nhất lên đầu

    // Thực thi truy vấn
    $result = $conn->query($sql);

    if ($result) { 
        if ($result->num_rows > 0) {
            
            while($row = $result->fetch_assoc()) {
                $nguoiDungList[] = $row;
            }
        }
        $result->free(); // Giải phóng bộ nhớ kết quả
    } else {
         if (empty($message)) {
             $message = "<div class='alert alert-danger'>Lỗi khi lấy danh sách Người dùng: " . $conn->error . "</div>";
         }
    }
}

if (isset($_GET['msg'])) {
    if (empty($message) || (strpos($message, 'Lỗi: Kết nối CSDL') === false && strpos(strval($message), 'Lỗi khi lấy danh sách') === false && strpos(strval($message), 'Lỗi khi thêm người dùng') === false && strpos(strval($message), 'Lỗi khi cập nhật người dùng') === false && strpos(strval($message), 'Có lỗi xảy ra khi xóa người dùng') === false && strpos(strval($message), 'Vui lòng điền đầy đủ thông tin') === false)) {
         $message = "<div class='alert alert-info'>" . htmlspecialchars($_GET['msg']) . "</div>";
    }
}


display_page:
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Người dùng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../admin/css/admin.css">
     <style>
         
         body {
             padding-top: 0; 
             overflow-x: hidden;
             background-color: #f4f7f6; 
         }
         main {
             margin-left: 250px; 
             padding-top: 20px; 
         }
         @media (max-width: 768px) {
             main {
                 margin-left: 0;
             }
         }
          .card-header {
             font-weight: bold;
         }
        
         .table td:last-child a {
             margin-right: 5px;
         }
      
         .form-control[readonly] {
             background-color: #e9ecef;
             opacity: 1;
         }
     </style>

</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php
        include 'index.php'; 
        ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $editMode ? 'Sửa thông tin Người dùng' : 'Quản lý Người dùng' ?></h1>
            </div>

            <?php echo $message; // Hiển thị thông báo ?>

            <div class="card mb-4">
                <div class="card-header">
                    <?= $editMode ? 'Cập nhật thông tin Người dùng' : 'Thêm mới Người dùng' ?>
                </div>
                <div class="card-body">
                    <form action="quanly_nguoidung.php" method="post">
                        <?php if ($editMode): // Nếu ở chế độ sửa, thêm trường ẩn cho ID ?>
                            <input type="hidden" name="id_nguoi_dung" value="<?= htmlspecialchars($currentUser['IDNguoiDung'] ?? '') ?>">
                            <input type="hidden" name="update_nguoidung" value="1">
                            <div class="mb-3">
                                <label for="display_id_nguoi_dung" class="form-label">ID Người dùng</label>
                                <input type="text" class="form-control" id="display_id_nguoi_dung" value="<?= htmlspecialchars($currentUser['IDNguoiDung'] ?? '') ?>" readonly>
                            </div>
                        <?php else: // Nếu ở chế độ thêm mới, thêm trường ẩn cho Add ?>
                            <input type="hidden" name="add_nguoidung" value="1">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="ten_dang_nhap" class="form-label">Tên đăng nhập (*)</label>
                            <input type="text" class="form-control" id="ten_dang_nhap" name="ten_dang_nhap" value="<?= htmlspecialchars($currentUser['TenDangNhap'] ?? '') ?>" maxlength="100" required <?= $editMode ? 'readonly' : '' ?>>
                             <?php if ($editMode): ?>
                                <small class="form-text text-muted">Không thể thay đổi tên đăng nhập khi sửa.</small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="mat_khau<?= $editMode ? '_moi' : '' ?>" class="form-label">Mật khẩu <?= $editMode ? 'mới' : '(*)' ?></label>
                            <input type="password" class="form-control" id="mat_khau<?= $editMode ? '_moi' : '' ?>" name="mat_khau<?= $editMode ? '_moi' : '' ?>" <?= $editMode ? '' : 'required' ?>>
                            <?php if ($editMode): ?>
                                <small class="form-text text-muted">Để trống nếu không muốn thay đổi mật khẩu.</small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="loai_tai_khoan" class="form-label">Loại tài khoản (*)</label>
                            <select class="form-select" id="loai_tai_khoan" name="loai_tai_khoan" required>
                                <option value="">-- Chọn loại tài khoản --</option>
                                <?php
                                // Chỉ hiển thị tùy chọn user và admin
                                $allowedLoaiTaiKhoan = ['user', 'admin'];
                                foreach ($allowedLoaiTaiKhoan as $option):
                                ?>
                                    <option value="<?= htmlspecialchars($option) ?>" <?= (($currentUser['LoaiTaiKhoan'] ?? '') == $option) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(ucfirst($option)) ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php
                                    // Nếu loại tài khoản hiện tại không phải user hoặc admin, hiển thị nó là tùy chọn đã chọn (chỉ khi sửa)
                                    $currentLoaiTaiKhoan = $currentUser['LoaiTaiKhoan'] ?? '';
                                    if ($editMode && !empty($currentLoaiTaiKhoan) && !in_array($currentLoaiTaiKhoan, $allowedLoaiTaiKhoan)):
                                ?>
                                     <option value="<?= htmlspecialchars($currentLoaiTaiKhoan) ?>" selected>
                                         <?= htmlspecialchars(ucfirst($currentLoaiTaiKhoan)) ?> (Giá trị hiện tại)
                                     </option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="ho_ten" class="form-label">Họ và tên (*)</label>
                            <input type="text" class="form-control" id="ho_ten" name="ho_ten" value="<?= htmlspecialchars($currentUser['HoTen'] ?? '') ?>" maxlength="100" required>
                        </div>

                         <div class="mb-3">
                            <label for="dia_chi" class="form-label">Địa chỉ (*)</label>
                            <input type="text" class="form-control" id="dia_chi" name="dia_chi" value="<?= htmlspecialchars($currentUser['DiaChi'] ?? '') ?>" maxlength="100" required>
                        </div>

                         <div class="mb-3">
                            <label for="ngay_sinh" class="form-label">Ngày sinh (*)</label>
                            <input type="date" class="form-control" id="ngay_sinh" name="ngay_sinh" value="<?= htmlspecialchars($currentUser['NgaySinh'] ?? '') ?>" required>
                        </div>

                         <div class="mb-3">
                            <label for="email" class="form-label">Email (*)</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($currentUser['Email'] ?? '') ?>" maxlength="100" required>
                        </div>

                         <div class="mb-3">
                            <label for="gioi_tinh" class="form-label">Giới tính (*)</label>
                             <select class="form-select" id="gioi_tinh" name="gioi_tinh" required>
                                 <option value="">-- Chọn giới tính --</option>
                                 <?php
                                 // Chỉ hiển thị tùy chọn Nam và Nữ
                                 $allowedGioiTinh = ['Nam', 'Nữ'];
                                 foreach ($allowedGioiTinh as $option):
                                 ?>
                                     <option value="<?= htmlspecialchars($option) ?>" <?= (($currentUser['GioiTinh'] ?? '') == $option) ? 'selected' : '' ?>>
                                         <?= htmlspecialchars($option) ?>
                                     </option>
                                 <?php endforeach; ?>
                                 <?php
                                     // Hiển thị tùy chọn hiện tại nếu giá trị từ DB không phải là Nam hoặc Nữ và không rỗng (chỉ khi sửa)
                                     $currentGioiTinh = $currentUser['GioiTinh'] ?? '';
                                     if ($editMode && !empty($currentGioiTinh) && !in_array($currentGioiTinh, $allowedGioiTinh)):
                                 ?>
                                     <option value="<?= htmlspecialchars($currentGioiTinh) ?>" selected>
                                         <?= htmlspecialchars($currentGioiTinh) ?> (Giá trị hiện tại)
                                     </option>
                                 <?php endif; ?>
                             </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas <?= $editMode ? 'fa-save' : 'fa-plus' ?>"></i>
                            <?= $editMode ? 'Cập nhật' : 'Thêm mới' ?>
                        </button>
                         <?php if ($editMode): ?>
                             <a href="quanly_nguoidung.php" class="btn btn-secondary"><i class="fas fa-times"></i> Hủy</a>
                         <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Danh sách Người dùng
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <?php if (!empty($nguoiDungList)): ?>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID Người dùng</th>
                                        <th>Tên đăng nhập</th>
                                        <th>Loại tài khoản</th>
                                        <th>Họ và tên</th>
                                        <th>Email</th>
                                        <th>Giới tính</th>
                                        <th>Mật khẩu</th> <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($nguoiDungList as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['IDNguoiDung'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($user['TenDangNhap'] ?? '') ?></td>
                                            <td><?= htmlspecialchars(ucfirst($user['LoaiTaiKhoan'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars($user['HoTen'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($user['Email'] ?? '') ?></td>
                                            <td>
                                                <?php
                                                // Hiển thị Giới tính, sửa giá trị '0' hoặc rỗng thành 'Chưa xác định'
                                                $gioiTinh = $user['GioiTinh'] ?? '';
                                                if ($gioiTinh === '0' || empty($gioiTinh)) {
                                                    echo 'Chưa xác định';
                                                } else {
                                                    echo htmlspecialchars($gioiTinh);
                                                }
                                                ?>
                                            </td>
                                            <td><?= htmlspecialchars($user['MatKhau'] ?? '') ?></td> <td>
                                                <a href="quanly_nguoidung.php?action=edit&id=<?= urlencode($user['IDNguoiDung'] ?? '') ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Sửa</a>
                                                <a href="quanly_nguoidung.php?action=delete&id=<?= urlencode($user['IDNguoiDung'] ?? '') ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng \'<?= htmlspecialchars($user['HoTen'] ?? '') ?>\' (ID: <?= htmlspecialchars($user['IDNguoiDung'] ?? '') ?>) không? Thao tác này sẽ xóa tất cả các bản ghi mượn, trả, yêu thích, và yêu cầu mượn đang chờ duyệt liên quan.');"><i class="fas fa-trash-alt"></i> Xóa</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>Chưa có người dùng nào trong cơ sở dữ liệu.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../admin/js/admin.js"></script>
</body>
</html>

<?php
// Đóng kết nối CSDL nếu nó vẫn mở sau khi hiển thị trang
if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
    $conn->close();
}
?>
