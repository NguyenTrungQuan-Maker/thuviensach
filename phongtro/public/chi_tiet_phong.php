<?php
session_start();
require_once __DIR__ . '/../config/db.php'; 

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
$room_id = $_GET['id']; 
}else{
header("Location: index.php?error=invalid_room"); 
exit();
}

$sqlRoom = "SELECT ma_phong, tieu_de, gia_thue, dien_tich, dia_chi_chi_tiet, quan_huyen, thanh_pho, so_phong_ngu, so_phong_ve_sinh, mo_ta, ma_nguoi_dung, luot_xem FROM rooms WHERE ma_phong = ?";

$stmtRoom = $conn->prepare($sqlRoom);
if ($stmtRoom === false) {
die("Lỗi chuẩn bị truy vấn phòng trọ: " . $conn->error);
}
$stmtRoom->bind_param('i', $room_id); 
$stmtRoom->execute();
$resultRoom = $stmtRoom->get_result();

if ($resultRoom->num_rows == 1) {
$room = $resultRoom->fetch_assoc(); 
$stmtRoom->close(); 

    $sqlIncreaseViews = "UPDATE rooms SET luot_xem = luot_xem + 1 WHERE ma_phong = ?";
    $stmtIncreaseViews = $conn->prepare($sqlIncreaseViews);
    if ($stmtIncreaseViews === false) {
        error_log("Lỗi chuẩn bị truy vấn UPDATE luot_xem: " . $conn->error);
    } else {
        $stmtIncreaseViews->bind_param('i', $room_id);
        $executeIncreaseViews = $stmtIncreaseViews->execute();
        if ($executeIncreaseViews === false) {
             error_log("Lỗi thực thi truy vấn UPDATE luot_xem cho ma_phong " . $room_id . ": " . $stmtIncreaseViews->error); // Ghi log lỗi
        }
        $stmtIncreaseViews->close(); 
    }
$sqlImages = "SELECT ma_hinh_anh, duong_dan_anh FROM images WHERE ma_phong = ?"; 
$stmtImages = $conn->prepare($sqlImages);
if ($stmtImages === false) {
die("Lỗi chuẩn bị truy vấn hình ảnh: " . $conn->error);
}
$stmtImages->bind_param('i', $room_id);
$stmtImages->execute();
$resultImages = $stmtImages->get_result();
$images = $resultImages->fetch_all(MYSQLI_ASSOC); 
$stmtImages->close(); // Đóng statement hình ảnh

$sqlAmenities = "SELECT a.ma_tien_ich, a.ten_tien_ich FROM amenities a JOIN room_amenities ra ON a.ma_tien_ich = ra.ma_tien_ich WHERE ra.ma_phong = ?"; // Câu lệnh SQL lấy tiện ích
$stmtAmenities = $conn->prepare($sqlAmenities);
if ($stmtAmenities === false) {
die("Lỗi chuẩn bị truy vấn tiện ích: " . $conn->error);
}
$stmtAmenities->bind_param('i', $room_id);
$stmtAmenities->execute();
$resultAmenities = $stmtAmenities->get_result();
$amenities = $resultAmenities->fetch_all(MYSQLI_ASSOC); // Lấy tất cả tiện ích
$stmtAmenities->close(); // Đóng statement tiện ích


} else {
header("Location: index.php?error=room_not_found"); // Chuyển hướng về index
exit();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($room['tieu_de']); ?> - Chi tiết phòng trọ</title>
<link rel="stylesheet" href="css/chitietphong.css">
    </head>
<body>
    <h2><?php echo htmlspecialchars($room['tieu_de']); ?></h2>

    <div>
        <p>Giá: <?php echo number_format($room['gia_thue']); ?> VNĐ/tháng</p>
        <p>Diện tích: <?php echo htmlspecialchars($room['dien_tich']); ?> m²</p>
        <p>Địa chỉ: <?php echo htmlspecialchars($room['dia_chi_chi_tiet']) . ', ' . htmlspecialchars($room['quan_huyen']) . ', ' . htmlspecialchars($room['thanh_pho']); ?></p>
        <p>Số phòng ngủ: <?php echo htmlspecialchars($room['so_phong_ngu']); ?></p>
        <p>Số phòng vệ sinh: <?php echo htmlspecialchars($room['so_phong_ve_sinh']); ?></p>
                 <p>Lượt xem: <?php echo htmlspecialchars($room['luot_xem']); ?></p>
        <p>Mô tả chi tiết:</p>
        <p><?php echo nl2br(htmlspecialchars($room['mo_ta'])); ?></p>
     </div>

    <div>
        <h3>Hình ảnh:</h3>
        <?php if (!empty($images)): ?>
            <?php foreach ($images as $image): ?>
                <img src="<?php echo htmlspecialchars($image['duong_dan_anh']); ?>" alt="Ảnh phòng trọ" style="max-width: 300px; margin-right: 10px;">
            <?php endforeach; ?>
        <?php else: ?>
            <p>Chưa có hình ảnh nào cho phòng trọ này.</p>
        <?php endif; ?>
    </div>

    <div>
        <h3>Tiện ích:</h3>
        <?php if (!empty($amenities)): ?>
            <ul>
                <?php foreach ($amenities as $amenity): ?>
                    <li><?php echo htmlspecialchars($amenity['ten_tien_ich']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Không có tiện ích nào được liệt kê.</p>
        <?php endif; ?>
    </div>

    <p><a href="index.php">Quay lại trang chủ</a></p> </body>
</html>