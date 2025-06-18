<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: dangnhap.php?error=not_logged_in");
    exit();
}

require_once __DIR__ . '/../config/db.php'; 

$sqlAmenities = "SELECT ma_tien_ich, ten_tien_ich FROM amenities ORDER BY ten_tien_ich ASC"; 

$resultAmenities = $conn->query($sqlAmenities);

if ($resultAmenities === false) {
    die("Lỗi truy vấn tiện ích: " . $conn->error);
}

$amenities = [];
if ($resultAmenities->num_rows > 0) {
    while ($row = $resultAmenities->fetch_assoc()) {
        $amenities[] = $row;
    }
}

$resultAmenities->free();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Đăng tin phòng trọ mới</title>
     <link rel="stylesheet" href="css/themtin.css">
    </head>
<body>
    <h2>Đăng tin phòng trọ mới</h2>

    <?php
if (isset($_GET['status']) && $_GET['status'] == 'success_add_room') {
    echo "<p class='success-message'>Đăng tin thành công!</p>";
}
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
    echo "<p class='error-message'>Lỗi: " . $error_message . "</p>";
}
?>


    <form action="xuly_them_tin.php" method="POST" enctype="multipart/form-data">

        <div>
            <label for="tieu_de">Tiêu đề tin đăng:</label><br>
            <input type="text" id="tieu_de" name="tieu_de" required>
        </div>
        <div>
            <label for="gia_thue">Giá thuê (VNĐ/tháng):</label><br>
            <input type="number" id="gia_thue" name="gia_thue" required min="0">
        </div>
        <div>
            <label for="dien_tich">Diện tích (m²):</label><br>
            <input type="number" id="dien_tich" name="dien_tich" required min="0">
        </div>

        <div>
            <label for="thanh_pho">Tỉnh/Thành phố:</label><br>
            <input type="text" id="thanh_pho" name="thanh_pho" required>
        </div>
        <div>
            <label for="quan_huyen">Quận/Huyện:</label><br>
            <input type="text" id="quan_huyen" name="quan_huyen" required>
        </div>
        <div>
            <label for="dia_chi_chi_tiet">Địa chỉ chi tiết (Số nhà, đường/ngõ):</label><br>
            <input type="text" id="dia_chi_chi_tiet" name="dia_chi_chi_tiet" required>
        </div>

        <div>
            <label for="so_phong_ngu">Số phòng ngủ:</label><br>
            <input type="number" id="so_phong_ngu" name="so_phong_ngu" min="0">
        </div>
        <div>
            <label for="so_phong_ve_sinh">Số phòng vệ sinh:</label><br>
            <input type="number" id="so_phong_ve_sinh" name="so_phong_ve_sinh" min="0">
        </div>
        <div>
            <label for="mo_ta">Mô tả chi tiết:</label><br>
            <textarea id="mo_ta" name="mo_ta" rows="5" required></textarea>
        </div>

        <div>
            <label>Tiện ích kèm theo:</label><br>
            <?php if (!empty($amenities)): ?>
                <?php foreach ($amenities as $amenity): ?>
                    <input type="checkbox" name="tien_ich[]" value="<?php echo $amenity['ma_tien_ich']; ?>">
                    <label><?php echo htmlspecialchars($amenity['ten_tien_ich']); ?></label><br>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Chưa có tiện ích nào trong hệ thống.</p>
            <?php endif; ?>
        </div>

        <div>
            <label for="room_images">Chọn hình ảnh phòng trọ:</label><br>
            <input type="file" id="room_images" name="room_images[]" accept="image/*" multiple required> </div>


        <br>
        <button type="submit">Đăng tin</button>
    </form>
    <p><a href="index.php">Trang chủ</a></p>
</html>