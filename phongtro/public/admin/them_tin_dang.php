<?php

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=" . urlencode("Bạn cần đăng nhập với quyền Admin để truy cập trang này."));
    exit();
}

$admin_user_id = $_SESSION['admin_user_id'] ?? null;

if ($admin_user_id === null) {
    header("Location: login.php?error=" . urlencode("Không tìm thấy ID Admin trong phiên làm việc. Vui lòng đăng nhập lại."));
    exit();
}

require_once __DIR__ . '/../../config/db.php'; 

$amenities = [];

try {
    $sqlAmenities = "SELECT ma_tien_ich, ten_tien_ich FROM amenities ORDER BY ten_tien_ich ASC";
    $stmtAmenities = $conn->prepare($sqlAmenities);
    $stmtAmenities->execute();
    $resultAmenities = $stmtAmenities->get_result();
    while ($row = $resultAmenities->fetch_assoc()) {
        $amenities[] = $row;
    }
    $resultAmenities->free();
    $stmtAmenities->close();

} catch (Exception $e) {
    error_log("Lỗi khi tải dữ liệu tiện ích cho form thêm tin đăng: " . $e->getMessage());
    $error_message = "Đã xảy ra lỗi khi tải dữ liệu tiện ích. Vui lòng thử lại.";
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

$error_message_display = '';
if (isset($_GET['error'])) {
    $error_message_display = '<p style="color: red;">' . htmlspecialchars($_GET['error']) . '</p>';
}
if (isset($error_message)) {
    $error_message_display = '<p style="color: red;">' . htmlspecialchars($error_message) . '</p>';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Thêm Tin đăng mới</title>
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
        .amenities-group {
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        .amenities-group label { display: inline-block; margin-right: 15px; font-weight: normal; }
        button {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        button:hover { background-color: #218838; }
        .back-link { display: block; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Thêm Tin đăng phòng trọ mới</h2>

        <?php echo $error_message_display; ?>

        <form action="xuly_them_tin_dang.php" method="POST" enctype="multipart/form-data">
            <div>
                <label for="tieu_de">Tiêu đề:</label>
                <input type="text" id="tieu_de" name="tieu_de" required>
            </div>

            <div>
                <label for="mo_ta">Mô tả:</label>
                <textarea id="mo_ta" name="mo_ta" required></textarea>
            </div>

            <div>
                <label for="gia_thue">Giá thuê (VNĐ):</label>
                <input type="number" id="gia_thue" name="gia_thue" min="0" required>
            </div>

            <div>
                <label for="dien_tich">Diện tích (m²):</label>
                <input type="number" id="dien_tich" name="dien_tich" step="0.01" min="0" required>
            </div>

            <div>
                <label for="dia_chi_chi_tiet">Địa chỉ chi tiết:</label>
                <input type="text" id="dia_chi_chi_tiet" name="dia_chi_chi_tiet" required>
            </div>

            <div>
                <label for="thanh_pho">Thành phố:</label>
                <input type="text" id="thanh_pho" name="thanh_pho" required>
            </div>

            <div>
                <label for="quan_huyen">Quận/Huyện:</label>
                <input type="text" id="quan_huyen" name="quan_huyen" required>
            </div>

            <div>
                <label for="phuong_xa">Phường/Xã:</label>
                <input type="text" id="phuong_xa" name="phuong_xa" required>
            </div>


            <div>
                <label for="so_phong_ngu">Số phòng ngủ:</label>
                <input type="number" id="so_phong_ngu" name="so_phong_ngu" min="0" value="0">
            </div>

            <div>
                <label for="so_phong_ve_sinh">Số phòng vệ sinh:</label>
                <input type="number" id="so_phong_ve_sinh" name="so_phong_ve_sinh" min="0" value="0">
            </div>

            <div>
                <label for="trang_thai">Trạng thái:</label>
                <select id="trang_thai" name="trang_thai" required>
                    <option value="available">Khả dụng (Duyệt)</option>
                    <option value="hidden">Ẩn</option>
                    <option value="rented">Đã cho thuê</option>
                </select>
            </div>

            <div>
                <label for="images">Hình ảnh (có thể chọn nhiều):</label>
                <input type="file" id="images" name="images[]" multiple accept="image/*">
                <small>Chọn ít nhất 1 hình ảnh.</small>
            </div>

            <div class="amenities-group">
                <label>Tiện ích:</label>
                <?php if (!empty($amenities)): ?>
                    <?php foreach ($amenities as $amenity): ?>
                        <label>
                            <input type="checkbox" name="amenities[]" value="<?php echo htmlspecialchars($amenity['ma_tien_ich']); ?>">
                            <?php echo htmlspecialchars($amenity['ten_tien_ich']); ?>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Chưa có tiện ích nào được định nghĩa. Vui lòng thêm tiện ích vào CSDL.</p>
                <?php endif; ?>
            </div>

            <button type="submit">Thêm Tin đăng</button>
        </form>

        <p class="back-link"><a href="quanly_tin_dang.php">Quay lại danh sách Tin đăng</a></p>
    </div>
</body>
</html>