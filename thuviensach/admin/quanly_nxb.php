<?php

include '../admin/db.php'; 

$message = ''; 
$editMode = false; 
$currentNXB = []; 


if (!($conn instanceof mysqli)) {
     $message = "<div class='alert alert-danger'>Lỗi: Kết nối CSDL không phải là đối tượng MySQLi. Vui lòng kiểm tra file db.php</div>";
     
     goto display_page; 
}




if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $masonxbToDelete = trim($_GET['id']);

    if (!empty($masonxbToDelete)) {
        try {
            
            $sql = "DELETE FROM nhaxuatban WHERE MaSoNXB = ?";
            $stmt = $conn->prepare($sql);

            
            $stmt->bind_param("s", $masonxbToDelete);

            if ($stmt->execute()) {
                 
                 if ($stmt->affected_rows > 0) {
                     $message = "<div class='alert alert-success'>Xóa loại sách có Mã '" . htmlspecialchars($masonxbToDelete) . "' thành công!</div>";
                 } else {
                     $message = "<div class='alert alert-warning'>Không tìm thấy loại sách có Mã '" . htmlspecialchars($masonxbToDelete) . "' để xóa.</div>";
                 }
                 
                 header("Location: quanly_nxb.php?msg=" . urlencode(strip_tags($message))); 
                 exit();

            } else {
                
                $message = "<div class='alert alert-danger'>Lỗi khi xóa loại sách: " . $stmt->error . "</div>";
            }

            $stmt->close();

        } catch (Exception $e) {
            $message = "<div class='alert alert-danger'>Có lỗi xảy ra khi xóa: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>Không có Mã loại sách được chỉ định để xóa.</div>";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $masonxbToEdit = trim($_GET['id']);

    if (!empty($masonxbToEdit)) {
        try {
            
            $sql = "SELECT MaSoNXB,TenNXB,DiaChiNXB,WebsiteNXB,ThongTinKhacNXB FROM nhaxuatban WHERE MaSoNXB = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $masonxbToEdit);
            $stmt->execute();
            $result = $stmt->get_result(); 

            if ($result->num_rows === 1) {
                $currentNXB = $result->fetch_assoc(); 
                $editMode = true; 
            } else {
                $message = "<div class='alert alert-warning'>Không tìm thấy loại sách có Mã '" . htmlspecialchars($masonxbToEdit) . "'.</div>";
            }

            $stmt->close();

        } catch (Exception $e) {
            $message = "<div class='alert alert-danger'>Có lỗi xảy ra khi lấy thông tin loại sách: " . $e->getMessage() . "</div>";
        }
    } else {
         $message = "<div class='alert alert-warning'>Không có Mã loại sách được chỉ định để sửa.</div>";
    }
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

   
if (isset($_POST['add_nhaxuatban'])) {
    
    $maSoNXB = trim($_POST['ma_so_nxb']);
    $tenNXB = trim($_POST['ten_nxb']);
    $diaChiNXB = trim($_POST['dia_chi_nxb']);
    $websiteNXB = trim($_POST['website_nxb']);
    $thongTinKhacNXB = trim($_POST['thong_tin_khac_nxb']);

    
    if (empty($maSoNXB) || empty($tenNXB)) {
        $message = "<div class='alert alert-warning'>Mã số NXB và Tên NXB không được để trống!</div>";
    } else {
        try {
            
            $sql = "INSERT INTO nhaxuatban (MaSoNXB, TenNXB, DiaChiNXB, WebsiteNXB, ThongTinKhacNXB) VALUES (?, ?, ?, ?, ?)";

            
            $stmt = $conn->prepare($sql);

            
            
            $stmt->bind_param("sssss", $maSoNXB, $tenNXB, $diaChiNXB, $websiteNXB, $thongTinKhacNXB);

            
            if ($stmt->execute()) {
                
                $message = "<div class='alert alert-success'>Thêm Nhà xuất bản thành công!</div>";
                

            } else {
                
                 if ($conn->errno == 1062) { 
                     $message = "<div class='alert alert-danger'>Lỗi: Mã số NXB '" . htmlspecialchars($maSoNXB) . "' đã tồn tại.</div>";
                 } else {
                     $message = "<div class='alert alert-danger'>Lỗi khi thêm Nhà xuất bản: " . $stmt->error . "</div>";
                 }
            }

            
            $stmt->close();

        } catch (Exception $e) {
            
            $message = "<div class='alert alert-danger'>Có lỗi xảy ra: " . $e->getMessage() . "</div>";
        }
    }
}

 
 if (isset($_POST['update_nhaxuatban'])) {
    
    
    $maSoNXB = trim($_POST['ma_so_nxb']);
    $tenNXB = trim($_POST['ten_nxb']);
    $diaChiNXB = trim($_POST['dia_chi_nxb']);
    $websiteNXB = trim($_POST['website_nxb']);
    $thongTinKhacNXB = trim($_POST['thong_tin_khac_nxb']);

    
    if (empty($maSoNXB) || empty($tenNXB) || empty($diaChiNXB) || empty($websiteNXB) || empty($thongTinKhacNXB)) {
         
        $message = "<div class='alert alert-warning'>Vui lòng điền đầy đủ thông tin bắt buộc (Mã số NXB, Tên NXB, Địa chỉ, Website, Thông tin khác)!</div>";
    } else {
         try {
            
            
            
            $sql = "UPDATE nhaxuatban SET TenNXB = ?, DiaChiNXB = ?, WebsiteNXB = ?, ThongTinKhacNXB = ? WHERE MaSoNXB = ?";

            
            $stmt = $conn->prepare($sql);

            
            
            
            $stmt->bind_param("sssss", $tenNXB, $diaChiNXB, $websiteNXB, $thongTinKhacNXB, $maSoNXB);

            
            if ($stmt->execute()) {
                 
                 if ($stmt->affected_rows > 0) {
                    $message = "<div class='alert alert-success'>Cập nhật Nhà xuất bản có Mã '" . htmlspecialchars($maSoNXB) . "' thành công!</div>";
                 } else {
                    
                     $message = "<div class='alert alert-info'>Không có thay đổi nào được lưu cho Nhà xuất bản có Mã '" . htmlspecialchars($maSoNXB) . "'. (Có thể không tìm thấy mã hoặc dữ liệu không đổi)</div>";
                 }
                

                 
            } else {
                 
                 $message = "<div class='alert alert-danger'>Lỗi khi cập nhật Nhà xuất bản: " . $stmt->error . "</div>";
            }

            
            $stmt->close();

         } catch (Exception $e) {
             
             $message = "<div class='alert alert-danger'>Có lỗi xảy ra: " . $e->getMessage() . "</div>";
         }
    }


         if (!empty($maSoNXB)) {
            try {
                
                
                if ($conn instanceof mysqli) {
                    $sql = "SELECT MaSoNXB, TenNXB, DiaChiNXB, WebsiteNXB, ThongTinKhacNXB FROM nhaxuatban WHERE MaSoNXB = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $maSoNXB);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows === 1) {
                        $currentNXB = $result->fetch_assoc(); 
                        
                        
                    }
                    $stmt->close();
                }
            } catch (Exception $e) {
                
            }
         }
    }
}




$nhaxuatbanList = []; 

if (!$editMode && ($conn instanceof mysqli)) {
    
    $sql = "SELECT MaSoNXB, TenNXB, DiaChiNXB, WebsiteNXB, ThongTinKhacNXB FROM nhaxuatban ORDER BY MaSoNXB"; 

    
    $result = $conn->query($sql);

    if ($result) { 
        if ($result->num_rows > 0) {
            
            while($row = $result->fetch_assoc()) {
                $nhaxuatbanList[] = $row;
            }
        }
        $result->free(); 
    } else {
         
         if (empty($message)) {
             $message = "<div class='alert alert-danger'>Lỗi khi lấy danh sách Nhà xuất bản: " . $conn->error . "</div>";
         }
    }
}



if (isset($_GET['msg'])) {
    
    if (empty($message) || strpos($message, 'Lỗi: Kết nối CSDL') !== false) {
         $message = "<div class='alert alert-info'>" . htmlspecialchars($_GET['msg']) . "</div>";
    }
}



display_page:
?>
<?php
include '../admin/viewnhaxuatban.php';
?>
<?php

goto end_of_script;
end_of_script:




?>