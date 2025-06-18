<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=" . urlencode("Bạn cần đăng nhập với quyền Admin để truy cập trang này."));
    exit();
}

require_once __DIR__ . '/../../config/db.php'; 

$rooms = []; 

try {
  
    $sql = "SELECT r.ma_phong, r.tieu_de, r.dia_chi_chi_tiet, r.gia_thue, r.dien_tich, r.trang_thai, r.luot_xem, r.thoi_gian_tao, u.email as chu_so_huu_email
            FROM rooms r
            JOIN users u ON r.ma_nguoi_dung = u.ma_nguoi_dung
            ORDER BY r.thoi_gian_tao DESC"; 

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Lỗi prepare SQL: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
    }
    $result->free();
    $stmt->close();

} catch (Exception $e) {
    error_log("Lỗi khi lấy danh sách tin đăng: " . $e->getMessage());
    $error_message = "Đã xảy ra lỗi khi tải dữ liệu. Vui lòng thử lại.";
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

// Xử lý thông báo trạng thái
$status_message = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'add_success':
            $status_message = '<p style="color: green;">Thêm tin đăng thành công!</p>';
            break;
        case 'edit_success':
            $status_message = '<p style="color: green;">Cập nhật tin đăng thành công!</p>';
            break;
        case 'delete_success':
            $status_message = '<p style="color: green;">Xóa tin đăng thành công!</p>';
            break;
        case 'status_change_success':
            $status_message = '<p style="color: green;">Cập nhật trạng thái tin đăng thành công!</p>';
            break;
        case 'edit_no_change':
            $status_message = '<p style="color: blue;">Không có thay đổi nào được thực hiện khi cập nhật.</p>';
            break;
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
    <title>Admin - Quản lý Tin đăng</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; color: #333; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .actions a, .actions button {
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            margin-right: 5px;
            white-space: nowrap; /* Ngăn nút xuống dòng */
        }
        .actions a { background-color: #007bff; color: white; }
        .actions a:hover { background-color: #0056b3; }
        .actions button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            color: #007bff; /* Mặc định giống màu link */
        }
        .actions button:hover { text-decoration: underline; }
        .actions .delete-btn { color: red; }
        .actions .delete-btn:hover { text-decoration: underline; }
        .actions {
    display: flex;
    gap: 8px; /* Khoảng cách giữa các nút */
    justify-content: flex-start;
    align-items: center;
    flex-wrap: nowrap;
}

        .add-button {
            display: inline-block;
            padding: 10px 15px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .add-button:hover { background-color: #218838; }
        .status-available { color: green; font-weight: bold; }
        .status-rented { color: orange; font-weight: bold; }
        .status-hidden { color: gray; font-weight: bold; }
        .back-link { display: block; margin-top: 20px; text-align: center; }
        p a[href*="index.php"] {
    display: inline-block;
    padding: 10px 20px;
    background-color: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    transition: background-color 0.3s ease;
    margin-top: 10px;
}

/* Hiệu ứng hover cho các liên kết */
p a[href*="quanly_tai_khoan"]:hover,
p a[href*="index.php"]:hover {
    background-color: #2980b9;
}
    </style>
</head>
<body>
    <div class="container">
        <h2>Quản lý Tin đăng phòng trọ</h2>

        <?php echo $status_message; ?>
        <?php echo $error_message_display; ?>

        <a href="them_tin_dang.php" class="add-button">Thêm Tin đăng mới</a>

        <?php if (!empty($rooms)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tiêu đề</th>
                        <th>Chủ sở hữu (Email)</th>
                        <th>Địa chỉ</th>
                        <th>Giá (VNĐ)</th>
                        <th>Diện tích (m²)</th>
                        <th>Trạng thái</th>
                        <th>Lượt xem</th>
                        <th>Thời gian tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($room['ma_phong']); ?></td>
                            <td><?php echo htmlspecialchars($room['tieu_de']); ?></td>
                            <td><?php echo htmlspecialchars($room['chu_so_huu_email']); ?></td>
                            <td><?php echo htmlspecialchars($room['dia_chi_chi_tiet']); ?></td>
                            <td><?php echo number_format($room['gia_thue'], 0, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($room['dien_tich']); ?></td>
                            <td>
                                <?php
                                $status_class = '';
                                switch ($room['trang_thai']) {
                                    case 'available':
                                        $status_class = 'status-available';
                                        break;
                                    case 'rented':
                                        $status_class = 'status-rented';
                                        break;
                                    case 'hidden':
                                        $status_class = 'status-hidden';
                                        break;
                                }
                                echo '<span class="' . $status_class . '">' . htmlspecialchars($room['trang_thai']) . '</span>';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($room['luot_xem']); ?></td>
                            <td><?php echo htmlspecialchars($room['thoi_gian_tao']); ?></td>
                            <td class="actions">
                                <a href="sua_tin_dang.php?id=<?php echo htmlspecialchars($room['ma_phong']); ?>">Sửa</a> |

                                <form action="xuly_xoa_tin_dang.php" method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tin đăng này? Thao tác này không thể hoàn tác và sẽ xóa tất cả hình ảnh liên quan!');">
                                    <input type="hidden" name="ma_phong" value="<?php echo htmlspecialchars($room['ma_phong']); ?>">
                                    <button type="submit" class="delete-btn">Xóa</button>
                                </form> |

                                <form action="xuly_thay_doi_trang_thai_tin_dang.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="ma_phong" value="<?php echo htmlspecialchars($room['ma_phong']); ?>">
                                    <?php if ($room['trang_thai'] == 'hidden'): ?>
                                        <input type="hidden" name="trang_thai_moi" value="available">
                                        <button type="submit" style="color: #28a745;">Duyệt/Bỏ ẩn</button>
                                    <?php else: ?>
                                        <input type="hidden" name="trang_thai_moi" value="hidden">
                                        <button type="submit" style="color: orange;">Ẩn</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Chưa có tin đăng phòng trọ nào trong hệ thống.</p>
        <?php endif; ?>

        <p class="back-link"><a href="index.php">Quay lại Dashboard</a></p>

    </div>
</body>
</html>