<?php

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=" . urlencode("Bạn cần đăng nhập với quyền Admin để truy cập trang này."));
    exit();
}

require_once __DIR__ . '/../../config/db.php'; 

$room_id = $_GET['id'] ?? 0;
if (!filter_var($room_id, FILTER_VALIDATE_INT) || $room_id <= 0) {
    header("Location: quanly_tin_dang.php?error=" . urlencode("ID tin đăng không hợp lệ."));
    exit();
}

$room_info = null;
$users = [];
$all_amenities = [];
$room_selected_amenities = [];
$room_images = [];

try {

    $sqlRoom = "SELECT r.*, u.email as chu_so_huu_email, u.ten_day_du as chu_so_huu_ten_day_du
                FROM rooms r
                JOIN users u ON r.ma_nguoi_dung = u.ma_nguoi_dung
                WHERE r.ma_phong = ?";
    $stmtRoom = $conn->prepare($sqlRoom);
    if ($stmtRoom === false) {
        throw new Exception("Lỗi prepare SQL Room: " . $conn->error);
    }
    $stmtRoom->bind_param("i", $room_id);
    $stmtRoom->execute();
    $resultRoom = $stmtRoom->get_result();
    $room_info = $resultRoom->fetch_assoc();
    $resultRoom->free();
    $stmtRoom->close();

    if (!$room_info) {
        throw new Exception("Không tìm thấy tin đăng với ID: " . $room_id);
    }

    // Lấy danh sách tất cả người dùng (để chọn chủ trọ khác nếu cần)
    $sqlUsers = "SELECT ma_nguoi_dung, email, ten_day_du FROM users ORDER BY email ASC";
    $stmtUsers = $conn->prepare($sqlUsers);
    $stmtUsers->execute();
    $resultUsers = $stmtUsers->get_result();
    while ($row = $resultUsers->fetch_assoc()) {
        $users[] = $row;
    }
    $resultUsers->free();
    $stmtUsers->close();

    // Lấy danh sách tất cả tiện ích
    $sqlAllAmenities = "SELECT ma_tien_ich, ten_tien_ich FROM amenities ORDER BY ten_tien_ich ASC";
    $stmtAllAmenities = $conn->prepare($sqlAllAmenities);
    $stmtAllAmenities->execute();
    $resultAllAmenities = $stmtAllAmenities->get_result();
    while ($row = $resultAllAmenities->fetch_assoc()) {
        $all_amenities[] = $row;
    }
    $resultAllAmenities->free();
    $stmtAllAmenities->close();

    // Lấy các tiện ích đã chọn của tin đăng này
    $sqlRoomAmenities = "SELECT ma_tien_ich FROM room_amenities WHERE ma_phong = ?";
    $stmtRoomAmenities = $conn->prepare($sqlRoomAmenities);
    if ($stmtRoomAmenities === false) {
        throw new Exception("Lỗi prepare SQL Room Amenities: " . $conn->error);
    }
    $stmtRoomAmenities->bind_param("i", $room_id);
    $stmtRoomAmenities->execute();
    $resultRoomAmenities = $stmtRoomAmenities->get_result();
    while ($row = $resultRoomAmenities->fetch_assoc()) {
        $room_selected_amenities[] = $row['ma_tien_ich'];
    }
    $resultRoomAmenities->free();
    $stmtRoomAmenities->close();

    // Lấy danh sách hình ảnh của tin đăng
    $sqlImages = "SELECT ma_hinh_anh, duong_dan_anh FROM images WHERE ma_phong = ?";
    $stmtImages = $conn->prepare($sqlImages);
    if ($stmtImages === false) {
        throw new Exception("Lỗi prepare SQL Images: " . $conn->error);
    }
    $stmtImages->bind_param("i", $room_id);
    $stmtImages->execute();
    $resultImages = $stmtImages->get_result();
    while ($row = $resultImages->fetch_assoc()) {
        $room_images[] = $row;
    }
    $resultImages->free();
    $stmtImages->close();

} catch (Exception $e) {
    error_log("Lỗi khi tải dữ liệu sửa tin đăng: " . $e->getMessage());
    header("Location: quanly_tin_dang.php?error=" . urlencode("Không thể tải thông tin tin đăng: " . $e->getMessage()));
    exit();
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

$error_message_display = '';
if (isset($_GET['error'])) {
    $error_message_display = '<p style="color: red;">' . htmlspecialchars($_GET['error']) . '</p>';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Sửa Tin đăng: <?php echo htmlspecialchars($room_info['tieu_de']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"], input[type="email"], textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        textarea { resize: vertical; min-height: 100px; }
        input[type="file"] { border: none; padding: 0; }
        .amenities-group, .images-group {
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
            background-color: #f9f9f9;
            margin-bottom: 20px;
        }
        .amenities-group label { display: inline-block; margin-right: 15px; font-weight: normal; }
        .current-images { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
        .current-image-item {
            border: 1px solid #ccc;
            padding: 5px;
            border-radius: 4px;
            text-align: center;
        }
        .current-image-item img {
            max-width: 150px;
            height: auto;
            display: block;
            margin-bottom: 5px;
        }
        .current-image-item button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }
        .current-image-item button:hover { background-color: #c82333; }

        button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        button:hover { background-color: #0056b3; }
        .back-link { display: block; margin-top: 20px; text-align: center; }
        
    </style>
</head>
<body>
    <div class="container">
        <h2>Sửa Tin đăng phòng trọ</h2>

        <?php echo $error_message_display; ?>

        <form action="xuly_sua_tin_dang.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="ma_phong" value="<?php echo htmlspecialchars($room_info['ma_phong']); ?>">

            <div>
                <label for="ma_nguoi_dung">Chủ sở hữu (Người đăng tin):</label>
                <select id="ma_nguoi_dung" name="ma_nguoi_dung" required>
                    <option value="">Chọn chủ sở hữu</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo htmlspecialchars($user['ma_nguoi_dung']); ?>"
                            <?php echo ($user['ma_nguoi_dung'] == $room_info['ma_nguoi_dung']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['email']) . ' (' . htmlspecialchars($user['ten_day_du']) . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="tieu_de">Tiêu đề:</label>
                <input type="text" id="tieu_de" name="tieu_de" value="<?php echo htmlspecialchars($room_info['tieu_de']); ?>" required>
            </div>

            <div>
                <label for="mo_ta">Mô tả:</label>
                <textarea id="mo_ta" name="mo_ta" required><?php echo htmlspecialchars($room_info['mo_ta']); ?></textarea>
            </div>

            <div>
                <label for="gia_thue">Giá thuê (VNĐ):</label>
                <input type="number" id="gia_thue" name="gia_thue" min="0" value="<?php echo htmlspecialchars($room_info['gia_thue']); ?>" required>
            </div>

            <div>
                <label for="dien_tich">Diện tích (m²):</label>
                <input type="number" id="dien_tich" name="dien_tich" step="0.01" min="0" value="<?php echo htmlspecialchars($room_info['dien_tich']); ?>" required>
            </div>

            <div>
                <label for="dia_chi_chi_tiet">Địa chỉ chi tiết:</label>
                <input type="text" id="dia_chi_chi_tiet" name="dia_chi_chi_tiet" value="<?php echo htmlspecialchars($room_info['dia_chi_chi_tiet']); ?>" required>
            </div>

            <div>
                <label for="thanh_pho">Thành phố:</label>
                <input type="text" id="thanh_pho" name="thanh_pho" value="<?php echo htmlspecialchars($room_info['thanh_pho']); ?>" required>
            </div>

            <div>
                <label for="quan_huyen">Quận/Huyện:</label>
                <input type="text" id="quan_huyen" name="quan_huyen" value="<?php echo htmlspecialchars($room_info['quan_huyen']); ?>" required>
            </div>

            <div>
                <label for="so_phong_ngu">Số phòng ngủ:</label>
                <input type="number" id="so_phong_ngu" name="so_phong_ngu" min="0" value="<?php echo htmlspecialchars($room_info['so_phong_ngu']); ?>">
            </div>

            <div>
                <label for="so_phong_ve_sinh">Số phòng vệ sinh:</label>
                <input type="number" id="so_phong_ve_sinh" name="so_phong_ve_sinh" min="0" value="<?php echo htmlspecialchars($room_info['so_phong_ve_sinh']); ?>">
            </div>

            <div>
                <label for="trang_thai">Trạng thái:</label>
                <select id="trang_thai" name="trang_thai" required>
                    <option value="available" <?php echo ($room_info['trang_thai'] == 'available') ? 'selected' : ''; ?>>Khả dụng (Duyệt)</option>
                    <option value="hidden" <?php echo ($room_info['trang_thai'] == 'hidden') ? 'selected' : ''; ?>>Ẩn</option>
                    <option value="rented" <?php echo ($room_info['trang_thai'] == 'rented') ? 'selected' : ''; ?>>Đã cho thuê</option>
                </select>
            </div>

            <div class="images-group">
                <label>Hình ảnh hiện tại:</label>
                <?php if (!empty($room_images)): ?>
                    <div class="current-images">
                        <?php foreach ($room_images as $image): ?>
                            <div class="current-image-item">
                                <img src="<?php echo htmlspecialchars('../../' . $image['duong_dan_anh']); ?>" alt="Ảnh phòng trọ">
                                <button type="button" onclick="deleteImage(<?php echo htmlspecialchars($image['ma_hinh_anh']); ?>, this)">Xóa ảnh này</button>
                                <input type="hidden" name="existing_images[]" value="<?php echo htmlspecialchars($image['ma_hinh_anh']); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small>Chọn "Xóa ảnh này" để loại bỏ ảnh. Ảnh sẽ bị xóa khi bạn lưu cập nhật.</small>
                <?php else: ?>
                    <p>Chưa có hình ảnh nào cho tin đăng này.</p>
                <?php endif; ?>

                <label for="new_images" style="margin-top: 15px;">Thêm hình ảnh mới:</label>
                <input type="file" id="new_images" name="new_images[]" multiple accept="image/*">
                <small>Chọn thêm hình ảnh nếu muốn.</small>
            </div>


            <div class="amenities-group">
                <label>Tiện ích:</label>
                <?php if (!empty($all_amenities)): ?>
                    <?php foreach ($all_amenities as $amenity): ?>
                        <label>
                            <input type="checkbox" name="amenities[]" value="<?php echo htmlspecialchars($amenity['ma_tien_ich']); ?>"
                                <?php echo in_array($amenity['ma_tien_ich'], $room_selected_amenities) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($amenity['ten_tien_ich']); ?>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Chưa có tiện ích nào được định nghĩa. Vui lòng thêm tiện ích vào CSDL.</p>
                <?php endif; ?>
            </div>

            <button type="submit">Cập nhật Tin đăng</button>
        </form>

        <p class="back-link"><a href="quanly_tin_dang.php">Quay lại danh sách Tin đăng</a></p>
    </div>

    <script>
        // JavaScript để xử lý xóa ảnh tạm thời trên giao diện
        function deleteImage(imageId, buttonElement) {
            if (confirm('Bạn có chắc chắn muốn xóa hình ảnh này? Nó sẽ bị xóa khi bạn lưu cập nhật.')) {
                // Tạo một input ẩn để báo cho server biết ảnh nào cần xóa
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'images_to_delete[]';
                hiddenInput.value = imageId;
                buttonElement.closest('form').appendChild(hiddenInput); // Thêm vào form

                // Xóa phần tử ảnh khỏi DOM để hiển thị ngay lập tức
                buttonElement.closest('.current-image-item').remove();
            }
        }
    </script>
</body>
</html>