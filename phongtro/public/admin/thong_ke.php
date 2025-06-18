<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=" . urlencode("Bạn cần đăng nhập với quyền Admin để truy cập trang này."));
    exit();
}
require_once __DIR__ . '/../../config/db.php'; 

$total_rooms = 0;
$rooms_by_status = [];
$total_users = 0;
$total_admins = 0;
$total_images = 0;
$latest_rooms = [];

try {
    // 1. Tổng số phòng trọ
    $sqlTotalRooms = "SELECT COUNT(ma_phong) AS total FROM rooms";
    $resultTotalRooms = $conn->query($sqlTotalRooms);
    if ($resultTotalRooms) {
        $total_rooms = $resultTotalRooms->fetch_assoc()['total'];
        $resultTotalRooms->free();
    } else {
        error_log("Lỗi truy vấn tổng số phòng: " . $conn->error);
    }

    // 2. Số phòng trọ theo trạng thái
    $sqlRoomsByStatus = "SELECT trang_thai, COUNT(ma_phong) AS count FROM rooms GROUP BY trang_thai";
    $resultRoomsByStatus = $conn->query($sqlRoomsByStatus);
    if ($resultRoomsByStatus) {
        while ($row = $resultRoomsByStatus->fetch_assoc()) {
            $rooms_by_status[$row['trang_thai']] = $row['count'];
        }
        $resultRoomsByStatus->free();
    } else {
        error_log("Lỗi truy vấn phòng theo trạng thái: " . $conn->error);
    }

    // 3. Tổng số người dùng và Admin
    $sqlTotalUsers = "SELECT COUNT(ma_nguoi_dung) AS total_users FROM users WHERE is_admin = 0";
    $resultTotalUsers = $conn->query($sqlTotalUsers);
    if ($resultTotalUsers) {
        $total_users = $resultTotalUsers->fetch_assoc()['total_users'];
        $resultTotalUsers->free();
    } else {
        error_log("Lỗi truy vấn tổng số người dùng: " . $conn->error);
    }

    $sqlTotalAdmins = "SELECT COUNT(ma_nguoi_dung) AS total_admins FROM users WHERE is_admin = 1";
    $resultTotalAdmins = $conn->query($sqlTotalAdmins);
    if ($resultTotalAdmins) {
        $total_admins = $resultTotalAdmins->fetch_assoc()['total_admins'];
        $resultTotalAdmins->free();
    } else {
        error_log("Lỗi truy vấn tổng số admin: " . $conn->error);
    }

    // 4. Tổng số ảnh đã tải lên
    $sqlTotalImages = "SELECT COUNT(ma_hinh_anh) AS total_images FROM images";
    $resultTotalImages = $conn->query($sqlTotalImages);
    if ($resultTotalImages) {
        $total_images = $resultTotalImages->fetch_assoc()['total_images'];
        $resultTotalImages->free();
    } else {
        error_log("Lỗi truy vấn tổng số ảnh: " . $conn->error);
    }

    // 5. 5 tin đăng mới nhất
    $sqlLatestRooms = "SELECT ma_phong, tieu_de, trang_thai, thoi_gian_cong_khai FROM rooms ORDER BY thoi_gian_tao DESC LIMIT 5";
    $resultLatestRooms = $conn->query($sqlLatestRooms);
    if ($resultLatestRooms) {
        while ($row = $resultLatestRooms->fetch_assoc()) {
            $latest_rooms[] = $row;
        }
        $resultLatestRooms->free();
    } else {
        error_log("Lỗi truy vấn tin đăng mới nhất: " . $conn->error);
    }

} catch (Exception $e) {
    error_log("Lỗi chung khi lấy dữ liệu thống kê: " . $e->getMessage());
    $error_message = "Đã xảy ra lỗi khi tải dữ liệu thống kê. Vui lòng thử lại.";
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

function getStatusDisplayName($status_key) {
    switch ($status_key) {
        case 'available': return 'Khả dụng (Đã duyệt)';
        case 'hidden': return 'Ẩn';
        case 'rented': return 'Đã cho thuê';
        default: return ucfirst($status_key); // Chuyển đổi chữ cái đầu thành hoa
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Thống kê</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            border-left: 5px solid #007bff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .stat-card h3 { margin-top: 0; color: #333; font-size: 1.1em; }
        .stat-card p { margin-bottom: 0; font-size: 2em; font-weight: bold; color: #007bff; }
        .stat-detail { margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px; }
        .stat-detail h3 { color: #333; }
        .stat-detail ul { list-style: none; padding: 0; }
        .stat-detail ul li { margin-bottom: 8px; }
        .latest-rooms table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .latest-rooms th, .latest-rooms td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .latest-rooms th { background-color: #f2f2f2; }
        .back-link { display: block; margin-top: 20px; text-align: center; }
        .error-message { color: red; text-align: center; margin-bottom: 15px; }
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
        <h2>Thống kê chung</h2>

        <?php if (isset($error_message)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Tổng số phòng trọ</h3>
                <p><?php echo number_format($total_rooms); ?></p>
            </div>
            <div class="stat-card">
                <h3>Tổng số người dùng</h3>
                <p><?php echo number_format($total_users); ?></p>
            </div>
            <div class="stat-card">
                <h3>Tổng số Admin</h3>
                <p><?php echo number_format($total_admins); ?></p>
            </div>
            <div class="stat-card">
                <h3>Tổng số ảnh</h3>
                <p><?php echo number_format($total_images); ?></p>
            </div>
        </div>

        <div class="stat-detail">
            <h3>Phòng trọ theo trạng thái:</h3>
            <ul>
                <?php if (!empty($rooms_by_status)): ?>
                    <?php foreach ($rooms_by_status as $status => $count): ?>
                        <li><?php echo getStatusDisplayName($status); ?>: <strong><?php echo number_format($count); ?></strong> tin</li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>Chưa có dữ liệu trạng thái phòng trọ.</li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="stat-detail latest-rooms">
            <h3>5 Tin đăng mới nhất:</h3>
            <?php if (!empty($latest_rooms)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tiêu đề</th>
                            <th>Trạng thái</th>
                            <th>Thời gian công khai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latest_rooms as $room): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($room['ma_phong']); ?></td>
                                <td><?php echo htmlspecialchars($room['tieu_de']); ?></td>
                                <td><?php echo getStatusDisplayName($room['trang_thai']); ?></td>
                                <td><?php echo htmlspecialchars($room['thoi_gian_cong_khai']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Chưa có tin đăng nào.</p>
            <?php endif; ?>
        </div>

        <p class="back-link"><a href="index.php">Quay lại Dashboard</a></p>
    </div>
</body>
</html>