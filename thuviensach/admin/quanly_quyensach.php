<?php

include '../admin/db.php'; 

$message = ''; 
$editMode = false; 
$currentSach = []; 
$sachList = []; 


$nxbList = [];
$loaisachList = [];


$uploadDir = '../assets/uploads/book_covers/'; 



if (!($conn instanceof mysqli)) {
     $message = "<div class='alert alert-danger'>Lỗi: Kết nối CSDL không phải là đối tượng MySQLi. Vui lòng kiểm tra file db.php</div>";
     
} else { 

    
    try {
        $sqlNXB = "SELECT MaSoNXB, TenNXB FROM nhaxuatban ORDER BY TenNXB";
        $resultNXB = $conn->query($sqlNXB);
        if ($resultNXB && $resultNXB->num_rows > 0) {
            while($rowNXB = $resultNXB->fetch_assoc()) {
                $nxbList[] = $rowNXB;
            }
            $resultNXB->free();
        } else {
            if (empty($message)) { $message = "<div class='alert alert-warning'>Không thể lấy danh sách Nhà xuất bản.</div>"; }
        }
    } catch (Exception $e) {
         if (empty($message)) { $message = "<div class='alert alert-danger'>Lỗi CSDL khi lấy danh sách Nhà xuất bản: " . $e->getMessage() . "</div>"; }
    }

    
    try {
        $sqlLoaiSach = "SELECT MaLoaiSach, LoaiSach FROM loaisach ORDER BY LoaiSach";
        $resultLoaiSach = $conn->query($sqlLoaiSach);
        if ($resultLoaiSach && $resultLoaiSach->num_rows > 0) {
            while($rowLoaiSach = $resultLoaiSach->fetch_assoc()) {
                $loaisachList[] = $rowLoaiSach;
            }
            $resultLoaiSach->free();
        } else {
             if (empty($message)) { $message = "<div class='alert alert-warning'>Không thể lấy danh sách Loại sách.</div>"; }
        }
    } catch (Exception $e) {
        if (empty($message)) { $message = "<div class='alert alert-danger'>Lỗi CSDL khi lấy danh sách Loại sách: " . $e->getMessage() . "</div>"; }
    }


    

    
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $maSoSachToDelete = trim($_GET['id']);

        if (!empty($maSoSachToDelete)) {
            try {
                
                $sqlFetchAnhBia = "SELECT AnhBia FROM quyensach WHERE MaSoSach = ?";
                $stmtFetch = $conn->prepare($sqlFetchAnhBia);
                $stmtFetch->bind_param("s", $maSoSachToDelete);
                $stmtFetch->execute();
                $resultFetch = $stmtFetch->get_result();
                $anhBiaPath = null;
                if ($resultFetch->num_rows === 1) {
                    $rowFetch = $resultFetch->fetch_assoc();
                    $anhBiaPath = $rowFetch['AnhBia'];
                }
                $stmtFetch->close();

                
                $sqlDelete = "DELETE FROM quyensach WHERE MaSoSach = ?";
                $stmtDelete = $conn->prepare($sqlDelete);
                $stmtDelete->bind_param("s", $maSoSachToDelete);

                if ($stmtDelete->execute()) {
                     if ($stmtDelete->affected_rows > 0) {
                         $message = "<div class='alert alert-success'>Xóa Quyển sách có Mã '" . htmlspecialchars($maSoSachToDelete) . "' thành công!</div>";
                         
                         if (!empty($anhBiaPath) && file_exists($uploadDir . $anhBiaPath)) {
                             unlink($uploadDir . $anhBiaPath); 
                         }
                     } else {
                         $message = "<div class='alert alert-warning'>Không tìm thấy Quyển sách có Mã '" . htmlspecialchars($maSoSachToDelete) . "' để xóa.</div>";
                     }
                     
                     header("Location: quanly_quyensach.php?msg=" . urlencode(strip_tags($message)));
                     exit();

                } else {
                     
                     $message = "<div class='alert alert-danger'>Lỗi khi xóa Quyển sách: " . $stmtDelete->error . "</div>";
                }
                $stmtDelete->close();

            } catch (Exception $e) {
                $message = "<div class='alert alert-danger'>Có lỗi xảy ra khi xóa: " . $e->getMessage() . "</div>";
            }
        } else {
            $message = "<div class='alert alert-warning'>Không có Mã số Sách được chỉ định để xóa.</div>";
        }
    }

    
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $maSoSachToEdit = trim($_GET['id']);

        if (!empty($maSoSachToEdit)) {
            try {
                
                $sql = "SELECT MaSoSach, TenSach, TacGia, MaSoNXB, MaLoaiSach, NamXB, LanXB, SoLuong, NoiDungTomLuoc, AnhBia FROM quyensach WHERE MaSoSach = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $maSoSachToEdit); 
                $stmt->execute();
                $result = $stmt->get_result(); 

                if ($result->num_rows === 1) {
                    $currentSach = $result->fetch_assoc(); 
                    $editMode = true; 
                } else {
                    $message = "<div class='alert alert-warning'>Không tìm thấy Quyển sách có Mã '" . htmlspecialchars($maSoSachToEdit) . "'.</div>";
                }
                $stmt->close();
            } catch (Exception $e) {
                $message = "<div class='alert alert-danger'>Có lỗi xảy ra khi lấy thông tin Quyển sách: " . $e->getMessage() . "</div>";
            }
        } else {
             $message = "<div class='alert alert-warning'>Không có Mã số Sách được chỉ định để sửa.</div>";
        }
    }

    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        if (isset($_POST['add_sach'])) {
            $maSoSach = trim($_POST['ma_so_sach']);
            $tenSach = trim($_POST['ten_sach']);
            $tacGia = trim($_POST['tac_gia']);
            $maSoNXB = trim($_POST['ma_so_nxb']); 
            $maLoaiSach = trim($_POST['ma_loai_sach']); 
            $namXB = trim($_POST['nam_xb']);
            $lanXB = trim($_POST['lan_xb']);
            $soLuong = trim($_POST['so_luong']);
            $noiDungTomLuoc = trim($_POST['noi_dung_tom_luoc']);

            
            $anhBiaPath = null; 
            $uploadOk = 1; 

            if (isset($_FILES["anh_bia"]) && $_FILES["anh_bia"]["error"] == UPLOAD_ERR_OK) {
                $target_file = $uploadDir . basename($_FILES["anh_bia"]["name"]);
                $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
                $uniqueFileName = uniqid('cover_', true) . '.' . $imageFileType; 
                $target_file_unique = $uploadDir . $uniqueFileName;

                
                if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
                    $message = "<div class='alert alert-danger'>Chỉ cho phép upload file JPG, JPEG, PNG & GIF.</div>";
                    $uploadOk = 0;
                }

                
                if ($_FILES["anh_bia"]["size"] > 5000000) {
                    $message = "<div class='alert alert-danger'>Kích thước file quá lớn (<= 5MB).</div>";
                    $uploadOk = 0;
                }

                
                if ($uploadOk == 1) {
                    if (move_uploaded_file($_FILES["anh_bia"]["tmp_name"], $target_file_unique)) {
                        $anhBiaPath = $uniqueFileName; 
                    } else {
                        $message = "<div class='alert alert-danger'>Có lỗi khi di chuyển file ảnh lên server.</div>";
                        $uploadOk = 0; 
                    }
                }
            } else if (isset($_FILES["anh_bia"]) && $_FILES["anh_bia"]["error"] != UPLOAD_ERR_NO_FILE) {
                 
                 $message = "<div class='alert alert-danger'>Lỗi upload file: " . $_FILES["anh_bia"]["error"] . "</div>";
                 $uploadOk = 0; 
            }
            

            
             if ($uploadOk == 1 && (empty($maSoSach) || empty($tenSach) || empty($tacGia) || empty($maSoNXB) || empty($maLoaiSach) || empty($namXB) || empty($lanXB) || empty($soLuong) || empty($noiDungTomLuoc))) {
                $message = "<div class='alert alert-warning'>Vui lòng điền đầy đủ thông tin bắt buộc!</div>";
             } else if ($uploadOk == 1) { 

                
                if (!is_numeric($namXB) || !is_numeric($lanXB) || !is_numeric($soLuong) || $namXB < 0 || $lanXB < 0 || $soLuong < 0) {
                     $message = "<div class='alert alert-warning'>Năm XB, Lần XB và Số lượng phải là số nguyên không âm!</div>";
                } else {

                     try {
                        
                        
                        $sql = "INSERT INTO quyensach (MaSoSach, TenSach, TacGia, MaSoNXB, MaLoaiSach, NamXB, LanXB, SoLuong, NoiDungTomLuoc, AnhBia) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                        
                        $stmt = $conn->prepare($sql);


                        $stmt->bind_param("sssssiiiss", $maSoSach, $tenSach, $tacGia, $maSoNXB, $maLoaiSach, $namXB, $lanXB, $soLuong, $noiDungTomLuoc, $anhBiaPath);


                        
                        if ($stmt->execute()) {
                            $message = "<div class='alert alert-success'>Thêm Quyển sách thành công!</div>";
                             header("Location: quanly_quyensach.php?msg=" . urlencode(strip_tags($message)));
                             exit();
                        } else {
                             if ($conn->errno == 1062) { 
                                 $message = "<div class='alert alert-danger'>Lỗi: Mã số Sách '" . htmlspecialchars($maSoSach) . "' đã tồn tại.</div>";
                             } else {
                                 $message = "<div class='alert alert-danger'>Lỗi khi thêm Quyển sách: " . $stmt->error . "</div>";
                             }
                             
                             if (!empty($anhBiaPath) && file_exists($uploadDir . $anhBiaPath)) {
                                 unlink($uploadDir . $anhBiaPath);
                             }
                        }
                        $stmt->close();
                    } catch (Exception $e) {
                        $message = "<div class='alert alert-danger'>Có lỗi xảy ra: " . $e->getMessage() . "</div>";
                         
                         if (!empty($anhBiaPath) && file_exists($uploadDir . $anhBiaPath)) {
                             unlink($uploadDir . $anhBiaPath);
                         }
                    }
                } 
             } 
        }

        
        if (isset($_POST['update_sach'])) {
            $maSoSach = trim($_POST['ma_so_sach']); 
            $tenSach = trim($_POST['ten_sach']);
            $tacGia = trim($_POST['tac_gia']);
            $maSoNXB = trim($_POST['ma_so_nxb']);
            $maLoaiSach = trim($_POST['ma_loai_sach']);
            $namXB = trim($_POST['nam_xb']);
            $lanXB = trim($_POST['lan_xb']);
            $soLuong = trim($_POST['so_luong']);
            $noiDungTomLuoc = trim($_POST['noi_dung_tom_luoc']);
            $oldAnhBiaPath = trim($_POST['old_anh_bia']); 
            $removeAnhBia = isset($_POST['remove_anh_bia']); 

            $anhBiaPathToSave = $oldAnhBiaPath; 

            
            $uploadOk = 1; 

            if (isset($_FILES["anh_bia"]) && $_FILES["anh_bia"]["error"] == UPLOAD_ERR_OK) {
                 $target_file = $uploadDir . basename($_FILES["anh_bia"]["name"]);
                 $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
                 $uniqueFileName = uniqid('cover_edit_', true) . '.' . $imageFileType;
                 $target_file_unique = $uploadDir . $uniqueFileName;

                 
                 if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
                     $message = "<div class='alert alert-danger'>Chỉ cho phép upload file JPG, JPEG, PNG & GIF.</div>";
                     $uploadOk = 0;
                 }

                 
                 if ($_FILES["anh_bia"]["size"] > 5000000) {
                     $message = "<div class='alert alert-danger'>Kích thước file quá lớn (<= 5MB).</div>";
                     $uploadOk = 0;
                 }

                 
                 if ($uploadOk == 1) {
                     if (move_uploaded_file($_FILES["anh_bia"]["tmp_name"], $target_file_unique)) {
                         $anhBiaPathToSave = $uniqueFileName; 
                         
                         if (!empty($oldAnhBiaPath) && $oldAnhBiaPath != $uniqueFileName && file_exists($uploadDir . $oldAnhBiaPath)) {
                              unlink($uploadDir . $oldAnhBiaPath);
                         }
                     } else {
                         $message = "<div class='alert alert-danger'>Có lỗi khi di chuyển file ảnh mới lên server.</div>";
                         $uploadOk = 0; 
                     }
                 }
            } else if ($removeAnhBia) {
                 
                 $anhBiaPathToSave = null; 
                 
                 if (!empty($oldAnhBiaPath) && file_exists($uploadDir . $oldAnhBiaPath)) {
                      unlink($uploadDir . $oldAnhBiaPath);
                 }
            } else if (isset($_FILES["anh_bia"]) && $_FILES["anh_bia"]["error"] != UPLOAD_ERR_NO_FILE) {
                 
                 $message = "<div class='alert alert-danger'>Lỗi upload file: " . $_FILES["anh_bia"]["error"] . "</div>";
                 $uploadOk = 0; 
            }
            


            
             if ($uploadOk == 1 && (empty($maSoSach) || empty($tenSach) || empty($tacGia) || empty($maSoNXB) || empty($maLoaiSach) || empty($namXB) || empty($lanXB) || empty($soLuong) || empty($noiDungTomLuoc))) {
                $message = "<div class='alert alert-warning'>Vui lòng điền đầy đủ thông tin bắt buộc!</div>";
             } else if ($uploadOk == 1) { 

                 
                 if (!is_numeric($namXB) || !is_numeric($lanXB) || !is_numeric($soLuong) || $namXB < 0 || $lanXB < 0 || $soLuong < 0) {
                     $message = "<div class='alert alert-warning'>Năm XB, Lần XB và Số lượng phải là số nguyên không âm!</div>";
                 } else {

                     try {
                        
                        
                        $sql = "UPDATE quyensach SET TenSach = ?, TacGia = ?, MaSoNXB = ?, MaLoaiSach = ?, NamXB = ?, LanXB = ?, SoLuong = ?, NoiDungTomLuoc = ?, AnhBia = ? WHERE MaSoSach = ?";

                        
                        $stmt = $conn->prepare($sql);

                        
                        
                        
                        $stmt->bind_param("ssssiiisss", $tenSach, $tacGia, $maSoNXB, $maLoaiSach, $namXB, $lanXB, $soLuong, $noiDungTomLuoc, $anhBiaPathToSave, $maSoSach);

                        
                        if ($stmt->execute()) {
                             if ($stmt->affected_rows > 0) {
                                $message = "<div class='alert alert-success'>Cập nhật Quyển sách có Mã '" . htmlspecialchars($maSoSach) . "' thành công!</div>";
                             } else {
                                 $message = "<div class='alert alert-info'>Không có thay đổi nào được lưu cho Quyển sách có Mã '" . htmlspecialchars($maSoSach) . "'. (Có thể không tìm thấy mã hoặc dữ liệu không đổi)</div>";
                             }
                            
                            header("Location: quanly_quyensach.php?msg=" . urlencode(strip_tags($message)));
                            exit();
                        } else {
                             
                             $message = "<div class='alert alert-danger'>Lỗi khi cập nhật Quyển sách: " . $stmt->error . "</div>";
                             
                        }
                        $stmt->close();

                     } catch (Exception $e) {
                         $message = "<div class='alert alert-danger'>Có lỗi xảy ra: " . $e->getMessage() . "</div>";
                          
                     }
                 } 
            } 

             
             
             if (!empty($maSoSach) && $uploadOk == 1) { 
                 try {
                     if ($conn instanceof mysqli) {
                         $sql = "SELECT MaSoSach, TenSach, TacGia, MaSoNXB, MaLoaiSach, NamXB, LanXB, SoLuong, NoiDungTomLuoc, AnhBia FROM quyensach WHERE MaSoSach = ?";
                         $stmt = $conn->prepare($sql);
                         $stmt->bind_param("s", $maSoSach);
                         $stmt->execute();
                         $result = $stmt->get_result();
                         if ($result->num_rows === 1) {
                             $currentSach = $result->fetch_assoc();
                             $editMode = true; 
                         }
                         $stmt->close();
                     }
                 } catch (Exception $e) {
                     
                 }
             } else if (!empty($maSoSach) && $uploadOk == 0) {
                  
                  
                  
                  
                  
             }
        }
    }


    if (!$editMode && ($conn instanceof mysqli)) {
        $sql = "SELECT q.MaSoSach, q.TenSach, q.TacGia, q.NamXB, q.LanXB, q.SoLuong, q.NoiDungTomLuoc, q.AnhBia, nxb.TenNXB, ls.LoaiSach
                FROM quyensach q
                JOIN nhaxuatban nxb ON q.MaSoNXB = nxb.MaSoNXB
                JOIN loaisach ls ON q.MaLoaiSach = ls.MaLoaiSach
                ORDER BY q.MaSoSach";
        $result = $conn->query($sql);

        if ($result) {
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sachList[] = $row;
                }
            }
            $result->free();
        } else {
             if (empty($message)) {
                 $message = "<div class='alert alert-danger'>Lỗi khi lấy danh sách Quyển sách: " . $conn->error . "</div>";
             }
        }
    }

    
    if (isset($_GET['msg'])) {
        if (empty($message) || strpos($message, 'Lỗi: Kết nối CSDL') !== false || strpos($message, 'Lỗi khi lấy danh sách') !== false) {
             $message = "<div class='alert alert-info'>" . htmlspecialchars($_GET['msg']) . "</div>";
        }
    }

} 


?>
<?php
include '../admin/viewquanlyquyensach.php';
?>
<?php

goto end_of_script;
end_of_script:





?>