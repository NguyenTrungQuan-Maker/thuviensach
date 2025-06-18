<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=" . urlencode("Bạn cần đăng nhập với quyền Admin để thực hiện chức năng này."));
    exit();
}

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ma_phong = $_POST['ma_phong'] ?? 0;

    if (!filter_var($ma_phong, FILTER_VALIDATE_INT) || $ma_phong <= 0) {
        header("Location: quanly_tin_dang.php?error=" . urlencode("ID tin đăng không hợp lệ để xóa."));
        exit();
    }

    $conn->begin_transaction();

    try {
        $sqlSelectImages = "SELECT duong_dan_anh FROM images WHERE ma_phong = ?";
        $stmtSelectImages = $conn->prepare($sqlSelectImages);
        if ($stmtSelectImages === false) {
            throw new Exception("Lỗi prepare SQL SELECT Images for Deletion: " . $conn->error);
        }
        $stmtSelectImages->bind_param("i", $ma_phong);
        $stmtSelectImages->execute();
        $resultImages = $stmtSelectImages->get_result();

        $image_paths = [];
        while ($row = $resultImages->fetch_assoc()) {
            $image_paths[] = __DIR__ . '/../../' . $row['duong_dan_anh'];
        }
        $resultImages->free();
        $stmtSelectImages->close();

        $sqlDeleteRoom = "DELETE FROM rooms WHERE ma_phong = ?";
        $stmtDeleteRoom = $conn->prepare($sqlDeleteRoom);
        if ($stmtDeleteRoom === false) {
            throw new Exception("Lỗi prepare SQL DELETE Room: " . $conn->error);
        }
        $stmtDeleteRoom->bind_param("i", $ma_phong);

        if (!$stmtDeleteRoom->execute()) {
            throw new Exception("Lỗi thực thi DELETE Room: " . $stmtDeleteRoom->error);
        }

        if ($stmtDeleteRoom->affected_rows > 0) {
            foreach ($image_paths as $file_path) {
                if (file_exists($file_path)) {
                    unlink($file_path); // Xóa file
                }
            }
            $conn->commit();
            header("Location: quanly_tin_dang.php?status=delete_success");
            exit();
        } else {
            $conn->rollback(); 
            header("Location: quanly_tin_dang.php?error=" . urlencode("Không tìm thấy tin đăng để xóa hoặc đã có lỗi."));
            exit();
        }

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Lỗi khi xóa tin đăng: " . $e->getMessage());
        header("Location: quanly_tin_dang.php?error=" . urlencode("Lỗi khi xóa tin đăng: " . $e->getMessage()));
        exit();
    } finally {
        if (isset($conn) && $conn->ping()) {
            $conn->close();
        }
    }

} else {
    header("Location: quanly_tin_dang.php");
    exit();
}
?>