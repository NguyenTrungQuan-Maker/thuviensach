<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: dangnhap.php?error=not_logged_in");
    exit();
}

require_once __DIR__ . '/../config/db.php'; 

$user_id = $_SESSION['user_id'];
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
$room_id = $_GET['id']; // Lấy ID phòng trọ từ URL
} else {
header("Location: quanly_tin_dang.php?error=" . urlencode("ID tin đăng không hợp lệ."));
 exit();
}

$sqlRoom = "SELECT ma_phong, tieu_de, gia_thue, dien_tich, dia_chi_chi_tiet, quan_huyen, thanh_pho, so_phong_ngu, so_phong_ve_sinh, mo_ta, ma_nguoi_dung, trang_thai FROM rooms WHERE ma_phong = ? AND ma_nguoi_dung = ?";

$stmtRoom = $conn->prepare($sqlRoom);
if ($stmtRoom === false) {
    die("Lỗi chuẩn bị truy vấn lấy thông tin phòng trọ để sửa: " . $conn->error);
}
$stmtRoom->bind_param('ii', $room_id, $user_id); 
$stmtRoom->execute();
$resultRoom = $stmtRoom->get_result();

if ($resultRoom->num_rows === 1) {
    $room_data = $resultRoom->fetch_assoc(); 
    $stmtRoom->close(); 

    $sqlImages = "SELECT ma_hinh_anh, duong_dan_anh FROM images WHERE ma_phong = ?";
    $stmtImages = $conn->prepare($sqlImages);
    if ($stmtImages === false) {
        die("Lỗi chuẩn bị truy vấn lấy hình ảnh để sửa: " . $conn->error);
    }
    $stmtImages->bind_param('i', $room_id);
    $stmtImages->execute();
    $images = $stmtImages->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtImages->close();

    $sqlAmenities = "SELECT ma_tien_ich FROM room_amenities WHERE ma_phong = ?";
    $stmtAmenities = $conn->prepare($sqlAmenities);
     if ($stmtAmenities === false) {
        die("Lỗi chuẩn bị truy vấn lấy tiện ích để sửa: " . $conn->error);
    }
    $stmtAmenities->bind_param('i', $room_id);
    $stmtAmenities->execute();
    $room_amenity_ids = $stmtAmenities->get_result()->fetch_all(MYSQLI_ASSOC); 
    $stmtAmenities->close();

    $room_amenity_ids_flat = [];
    foreach ($room_amenity_ids as $amenity_row) {
        $room_amenity_ids_flat[] = $amenity_row['ma_tien_ich'];
    }


} else {

    header("Location: quanly_tin_dang.php?error=" . urlencode("Không tìm thấy tin đăng hoặc bạn không có quyền sửa."));
    exit();
}

$sqlAllAmenities = "SELECT ma_tien_ich, ten_tien_ich FROM amenities ORDER BY ten_tien_ich ASC";
$resultAllAmenities = $conn->query($sqlAllAmenities);
$all_amenities = [];
if ($resultAllAmenities) {
    while ($row = $resultAllAmenities->fetch_assoc()) {
        $all_amenities[] = $row;
    }
    $resultAllAmenities->free();
} else {
     error_log("Lỗi truy vấn danh sách tiện ích: " . $conn->error); // Ghi log lỗi
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Sửa tin đăng: <?php echo htmlspecialchars($room_data['tieu_de']); ?></title>
    <link rel="stylesheet" href="css/suatin.css">

</head>
<body>
    <h2>Sửa tin đăng</h2>

    <?php
     if (isset($_GET['status'])) {
        if ($_GET['status'] == 'edit_success') {
            echo "<p style='color: green;'>Tin đăng đã được cập nhật thành công.</p>";
        }

    }
     if (isset($_GET['error'])) {
        echo "<p style='color: red;'>Lỗi: " . htmlspecialchars($_GET['error']) . "</p>";
    }
    ?>

    <form action="xuly_sua_tin.php" method="POST" enctype="multipart/form-data">

        <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">

        <div>
            <label for="tieu_de">Tiêu đề:</label><br>
            <input type="text" id="tieu_de" name="tieu_de" value="<?php echo htmlspecialchars($room_data['tieu_de']); ?>" required>
        </div>
        <br>
        <div>
            <label for="gia_thue">Giá thuê (VNĐ/tháng):</label><br>
            <input type="number" id="gia_thue" name="gia_thue" value="<?php echo htmlspecialchars($room_data['gia_thue']); ?>" required min="0">
        </div>
        <br>
        <div>
            <label for="dien_tich">Diện tích (m²):</label><br>
            <input type="number" id="dien_tich" name="dien_tich" value="<?php echo htmlspecialchars($room_data['dien_tich']); ?>" required min="0" step="0.1">
        </div>
        <br>
        <div>
            <label for="thanh_pho">Tỉnh/Thành phố:</label><br>
            <input type="text" id="thanh_pho" name="thanh_pho" value="<?php echo htmlspecialchars($room_data['thanh_pho']); ?>" required>
        </div>
        <br>
        <div>
            <label for="quan_huyen">Quận/Huyện:</label><br>
            <input type="text" id="quan_huyen" name="quan_huyen" value="<?php echo htmlspecialchars($room_data['quan_huyen']); ?>" required>
        </div>
        <br>
        <div>
            <label for="dia_chi_chi_tiet">Địa chỉ chi tiết:</label><br>
            <input type="text" id="dia_chi_chi_tiet" name="dia_chi_chi_tiet" value="<?php echo htmlspecialchars($room_data['dia_chi_chi_tiet']); ?>" required>
        </div>
        <br>
        <div>
            <label for="so_phong_ngu">Số phòng ngủ:</label><br>
            <input type="number" id="so_phong_ngu" name="so_phong_ngu" value="<?php echo htmlspecialchars($room_data['so_phong_ngu']); ?>" required min="0">
        </div>
        <br>
        <div>
            <label for="so_phong_ve_sinh">Số phòng vệ sinh:</label><br>
            <input type="number" id="so_phong_ve_sinh" name="so_phong_ve_sinh" value="<?php echo htmlspecialchars($room_data['so_phong_ve_sinh']); ?>" required min="0">
        </div>
        <br>
        <div>
            <label for="mo_ta">Mô tả chi tiết:</label><br>
            <textarea id="mo_ta" name="mo_ta" rows="8" cols="50" required><?php echo htmlspecialchars($room_data['mo_ta']); ?></textarea>
             </div>
        <br>

        <div>
            <label>Hình ảnh hiện tại:</label><br>
            <?php if (!empty($images)): ?>
                <?php foreach ($images as $image): ?>
                    <div class="image-preview" id="image-<?php echo $image['ma_hinh_anh']; ?>">
                        <img src="<?php echo htmlspecialchars($image['duong_dan_anh']); ?>" alt="Ảnh phòng trọ">
                        <button type="button" class="remove-image" data-image-id="<?php echo $image['ma_hinh_anh']; ?>">X</button>
                        </div>
                <?php endforeach; ?>
                <input type="hidden" name="images_to_remove" id="images_to_remove" value="">
            <?php else: ?>
                <p>Tin đăng này chưa có hình ảnh.</p>
            <?php endif; ?>

            <br>
            <label for="new_room_images">Thêm ảnh mới:</label><br>
            <input type="file" id="new_room_images" name="new_room_images[]" multiple accept="image/*">
             </div>
        <br>

        <div>
            <label>Tiện ích:</label><br>
            <?php if (!empty($all_amenities)): ?>
                <?php foreach ($all_amenities as $amenity): ?>
                    <input type="checkbox" name="amenities[]" value="<?php echo $amenity['ma_tien_ich']; ?>"
                           <?php if (in_array($amenity['ma_tien_ich'], $room_amenity_ids_flat)): echo 'checked'; endif; // Giữ trạng thái checkbox đã chọn ?>>
                    <label><?php echo htmlspecialchars($amenity['ten_tien_ich']); ?></label>
                    <span style="margin-right: 15px;"></span> <?php endforeach; ?>
            <?php else: ?>
                <p>Chưa có tiện ích nào trong hệ thống.</p>
            <?php endif; ?>
        </div>
        <br>

        <div>
            <button type="submit">Cập nhật tin đăng</button>
        </div>
    </form>

    <p><a href="quanly_tin_dang.php">Quay lại quản lý tin đăng</a></p>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const removeButtons = document.querySelectorAll('.remove-image');
        const imagesToRemoveInput = document.getElementById('images_to_remove');
        let imagesToRemove = [];

        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const imageId = this.dataset.imageId;
                const imageDiv = document.getElementById('image-' + imageId);

                if (!imagesToRemove.includes(imageId)) {
                    imagesToRemove.push(imageId);
                }

                imagesToRemoveInput.value = imagesToRemove.join(',');

                if (imageDiv) {
                    imageDiv.style.display = 'none';
                }
            });
        });
    });
    </script>

</body>
</html>