<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: dangnhap.php?error=not_logged_in");
    exit();
}

require_once __DIR__ . '/../config/db.php'; 

$user_id = $_SESSION['user_id'];
$sql = "SELECT ma_phong, tieu_de, gia_thue, dien_tich, dia_chi_chi_tiet, quan_huyen, thanh_pho, luot_xem, trang_thai FROM rooms WHERE ma_nguoi_dung = ? ORDER BY thoi_gian_cong_khai DESC";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Lỗi chuẩn bị truy vấn tin đăng của người dùng: " . $conn->error);
}

$stmt->bind_param('i', $user_id);

$executeSuccess = $stmt->execute();

if ($executeSuccess === false) {
     die("Lỗi thực thi truy vấn tin đăng của người dùng: " . $stmt->error);
}

// Lấy kết quả
$result = $stmt->get_result();

// Lấy tất cả tin đăng vào một mảng
$user_listings = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $user_listings[] = $row;
    }
}

$result->free();
$stmt->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Quản lý tin đăng của tôi</title>
    <link rel="stylesheet" href="css/qldangtin.css">
    </head>
<body>
    <h2>Quản lý tin đăng của tôi</h2>

    <?php
    // Hiển thị thông báo (ví dụ: sau khi đánh dấu đã thuê, sửa, xóa)
    if (isset($_GET['status'])) {
        if ($_GET['status'] == 'rented_success') {
            echo "<p style='color: green;'>Tin đăng đã được đánh dấu là đã thuê.</p>";
        }
        // Thêm các thông báo khác (sửa, xóa...) ở đây
    }
     if (isset($_GET['error'])) {
        echo "<p style='color: red;'>Lỗi: " . htmlspecialchars($_GET['error']) . "</p>";
    }
    ?>

    <?php if (!empty($user_listings)): ?>
        <table>
            <thead>
                <tr>
                    <th>Tiêu đề</th>
                    <th>Giá</th>
                    <th>Diện tích</th>
                    <th>Địa chỉ</th>
                    <th>Lượt xem</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th> </tr>
            </thead>
            <tbody>
                <?php foreach ($user_listings as $listing): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($listing['tieu_de']); ?></td>
                        <td><?php echo number_format($listing['gia_thue']); ?></td>
                        <td><?php echo htmlspecialchars($listing['dien_tich']); ?></td>
                         <td><?php echo htmlspecialchars($listing['dia_chi_chi_tiet']) . ', ' . htmlspecialchars($listing['quan_huyen']) . ', ' . htmlspecialchars($listing['thanh_pho']); ?></td>
                        <td><?php echo htmlspecialchars($listing['luot_xem']); ?></td>
                        <td><?php echo htmlspecialchars($listing['trang_thai']); ?></td>
                        <td>
                            <a href="sua_tin.php?id=<?php echo $listing['ma_phong']; ?>">Sửa</a> |

                            <form action="xuly_xoa_tin.php" method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tin đăng này không?');">
                                 <input type="hidden" name="room_id" value="<?php echo $listing['ma_phong']; ?>">
                                 <button type="submit" style="color: red; background: none; border: none; cursor: pointer; padding: 0;">Xóa</button>
                             </form> |

                            <?php if ($listing['trang_thai'] !== 'rented'): // Chỉ hiện nút nếu chưa phải trạng thái đã thuê ?>
                                 <form action="xuly_danh_dau_da_thue.php" method="POST" style="display:inline;">
                                     <input type="hidden" name="room_id" value="<?php echo $listing['ma_phong']; ?>">
                                     <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0;">Đã thuê</button>
                                 </form>
                            <?php else: ?>
                                 Đã thuê
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Bạn chưa có tin đăng nào.</p>
    <?php endif; ?>

    <p><a href="them_tin.php">Đăng tin mới</a></p> <p><a href="index.php">Trang chủ</a></p>


</body>
</html>