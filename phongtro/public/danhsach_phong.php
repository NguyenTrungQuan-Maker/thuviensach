<?php
require_once __DIR__ . '/../config/db.php'; 

?>
<!DOCTYPE html>
<html>
<head>
    <title>Danh sách phòng trọ</title>
    </head>
<body>
    <h2>Danh sách phòng trọ mới nhất</h2>

    <?php

    $sql = "SELECT ma_phong, tieu_de, gia_thue, dien_tich, dia_chi_chi_tiet, thanh_pho, quan_huyen FROM rooms ORDER BY thoi_gian_cong_khai DESC";
    $result = $conn->query($sql);

    // Kiểm tra lỗi truy vấn
    if ($result === false) {
        die("Lỗi thực thi truy vấn: " . $conn->error);
    }

    if ($result->num_rows > 0) {
       

        while ($row = $result->fetch_assoc()) { 
            ?>
            <div style="border: 1px solid #ccc; margin-bottom: 10px; padding: 10px;">
                <h3><?php echo htmlspecialchars($row['tieu_de']); ?></h3>
                <p>Giá: <?php echo number_format($row['gia_thue']); ?> VNĐ/tháng</p>
                <p>Diện tích: <?php echo htmlspecialchars($row['dien_tich']); ?> m²</p>
                <p>Địa chỉ: <?php echo htmlspecialchars($row['dia_chi_chi_tiet']) . ', ' . htmlspecialchars($row['quan_huyen']) . ', ' . htmlspecialchars($row['thanh_pho']); ?></p>
                <p><a href="chi_tiet_phong.php?id=<?php echo $row['ma_phong']; ?>">Xem chi tiết</a></p> </div>
            <?php
        }
    } else {
        echo "<p>Hiện chưa có phòng trọ nào được đăng.</p>";
    }

    $result->free();

    ?>

    </body>
</html>