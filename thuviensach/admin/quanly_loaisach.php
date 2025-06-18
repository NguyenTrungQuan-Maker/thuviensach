<?php

include '../admin/db.php';

$message = '';
$editMode = false;
$currentLoaiSach = [];

if (!($conn instanceof mysqli)) {
    $message = "<div class='alert alert-danger'>Lỗi: Kết nối CSDL. Vui lòng kiểm tra file db.php</div>";

    goto display_page;
}


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $maLoaiSachToDelete = trim($_GET['id']);

    if (!empty($maLoaiSachToDelete)) {
        try {

            $sql = "DELETE FROM loaisach WHERE MaLoaiSach = ?";
            $stmt = $conn->prepare($sql);

            $stmt->bind_param("s", $maLoaiSachToDelete);

            if ($stmt->execute()) {

                if ($stmt->affected_rows > 0) {
                    $message = "<div class='alert alert-success'>Xóa loại sách có Mã '" . htmlspecialchars($maLoaiSachToDelete) . "' thành công!</div>";
                } else {
                    $message = "<div class='alert alert-warning'>Không tìm thấy loại sách có Mã '" . htmlspecialchars($maLoaiSachToDelete) . "' để xóa.</div>";
                }

                header("Location: quanly_loaisach.php?msg=" . urlencode(strip_tags($message))); // Truyền thông báo qua URL
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
    $maLoaiSachToEdit = trim($_GET['id']);

    if (!empty($maLoaiSachToEdit)) {
        try {

            $sql = "SELECT MaLoaiSach, LoaiSach FROM loaisach WHERE MaLoaiSach = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $maLoaiSachToEdit);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $currentLoaiSach = $result->fetch_assoc();
                $editMode = true;
            } else {
                $message = "<div class='alert alert-warning'>Không tìm thấy loại sách có Mã '" . htmlspecialchars($maLoaiSachToEdit) . "'.</div>";
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
    if (isset($_POST['add_loaisach'])) {
        $maLoaiSach = trim($_POST['ma_loai_sach']);
        $loaiSach = trim($_POST['loai_sach']);

        if (empty($maLoaiSach) || empty($loaiSach)) {
            $message = "<div class='alert alert-warning'>Mã loại sách và Tên loại sách không được để trống!</div>";
        } else {
            try {
                $sql = "INSERT INTO loaisach (MaLoaiSach, LoaiSach) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $maLoaiSach, $loaiSach);

                if ($stmt->execute()) {
                    $message = "<div class='alert alert-success'>Thêm loại sách thành công!</div>";

                    header("Location: quanly_loaisach.php?msg=" . urlencode(strip_tags($message)));
                    exit();
                } else {
                    if ($conn->errno == 1062) {
                        $message = "<div class='alert alert-danger'>Lỗi: Mã loại sách '" . htmlspecialchars($maLoaiSach) . "' đã tồn tại.</div>";
                    } else {
                        $message = "<div class='alert alert-danger'>Lỗi khi thêm loại sách: " . $stmt->error . "</div>";
                    }
                }
                $stmt->close();
            } catch (Exception $e) {
                $message = "<div class='alert alert-danger'>Có lỗi xảy ra: " . $e->getMessage() . "</div>";
            }
        }
    }

    if (isset($_POST['update_loaisach'])) {
        $maLoaiSach = trim($_POST['ma_loai_sach']);
        $loaiSach = trim($_POST['loai_sach']);

        if (empty($maLoaiSach) || empty($loaiSach)) {
            $message = "<div class='alert alert-warning'>Mã loại sách và Tên loại sách không được để trống!</div>";
        } else {
            try {

                $sql = "UPDATE loaisach SET LoaiSach = ? WHERE MaLoaiSach = ?";
                $stmt = $conn->prepare($sql);

                $stmt->bind_param("ss", $loaiSach, $maLoaiSach);

                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $message = "<div class='alert alert-success'>Cập nhật loại sách có Mã '" . htmlspecialchars($maLoaiSach) . "' thành công!</div>";
                    } else {

                        $message = "<div class='alert alert-info'>Không có thay đổi nào được lưu cho loại sách có Mã '" . htmlspecialchars($maLoaiSach) . "'.</div>";
                    }

                    header("Location: quanly_loaisach.php?msg=" . urlencode(strip_tags($message)));
                    exit();
                } else {
                    $message = "<div class='alert alert-danger'>Lỗi khi cập nhật loại sách: " . $stmt->error . "</div>";
                }
                $stmt->close();
            } catch (Exception $e) {
                $message = "<div class='alert alert-danger'>Có lỗi xảy ra: " . $e->getMessage() . "</div>";
            }
        }

        if (!empty($maLoaiSach)) {
            try {
                $sql = "SELECT MaLoaiSach, LoaiSach FROM loaisach WHERE MaLoaiSach = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $maLoaiSach);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $currentLoaiSach = $result->fetch_assoc();
                    $editMode = true; 
                }
                $stmt->close();
            } catch (Exception $e) {
            }
        }
    }
}

$loaisachList = [];

if (!$editMode && ($conn instanceof mysqli)) {
    $sql = "SELECT MaLoaiSach, LoaiSach FROM loaisach ORDER BY MaLoaiSach";
    $result = $conn->query($sql);

    if ($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $loaisachList[] = $row;
            }
        }
        $result->free();
    } else {

        if (empty($message)) {
            $message = "<div class='alert alert-danger'>Lỗi khi lấy danh sách loại sách: " . $conn->error . "</div>";
        }
    }
}


if (isset($_GET['msg'])) {
    $message = "<div class='alert alert-info'>" . htmlspecialchars($_GET['msg']) . "</div>";
}

display_page:
?>
<?php
include '../admin/viewquanlysach.php';
?>
<?php

goto end_of_script;
end_of_script:
?>