<?php
session_start();

require_once __DIR__ . '/../config/db.php';

$records_per_page = 6;

$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

$current_page = max(1, $current_page);


$sort_by = $_GET['sort'] ?? 'latest'; 
$order_by_sql = "r.thoi_gian_cong_khai DESC"; 

if ($sort_by === 'views') {
    $order_by_sql = "r.luot_xem DESC";
}

$search_keyword = $_GET['keyword'] ?? '';
$min_price = $_GET['min_price'] ?? ''; 
$max_price = $_GET['max_price'] ?? ''; 
$search_province = $_GET['province'] ?? ''; 
$search_district = $_GET['district'] ?? ''; 
$min_area = $_GET['min_area'] ?? ''; 
$max_area = $_GET['max_area'] ?? ''; 


$where_clauses = []; 
$params = [];
$types = ''; 

$where_clauses[] = "r.trang_thai = ?";
$params[] = 'available';
$types .= 's'; 

if (!empty($search_keyword)) {

    $where_clauses[] = "(r.tieu_de LIKE ? OR r.mo_ta LIKE ? OR r.dia_chi_chi_tiet LIKE ?)";
    $like_keyword = '%' . $search_keyword . '%';
    $params[] = $like_keyword;
    $params[] = $like_keyword;
    $params[] = $like_keyword;
    $types .= 'sss'; 
}

if (!empty($min_price) && is_numeric($min_price)) {
    $where_clauses[] = "r.gia_thue >= ?";
    $params[] = (float)$min_price; 
    $types .= 'd'; 
}
if (!empty($max_price) && is_numeric($max_price)) {
    $where_clauses[] = "r.gia_thue <= ?";
    $params[] = (float)$max_price; 
    $types .= 'd';
}

if (!empty($search_province)) {
    $where_clauses[] = "r.thanh_pho = ?";
    $params[] = $search_province;
    $types .= 's'; 
}
if (!empty($search_district)) {
    $where_clauses[] = "r.quan_huyen = ?";
    $params[] = $search_district;
    $types .= 's'; 
}

if (!empty($min_area) && is_numeric($min_area)) {
    $where_clauses[] = "r.dien_tich >= ?";
    $params[] = (float)$min_area; 
    $types .= 'd'; 
}
if (!empty($max_area) && is_numeric($max_area)) {
    $where_clauses[] = "r.dien_tich <= ?";
    $params[] = (float)$max_area; 
    $types .= 'd'; 
}

$sqlTotal = "SELECT COUNT(DISTINCT r.ma_phong) AS total FROM rooms r"; 

if (!empty($where_clauses)) {
    $sqlTotal .= " WHERE " . implode(" AND ", $where_clauses); 
}

$stmtTotal = $conn->prepare($sqlTotal);
if ($stmtTotal === false) {
    die("Lỗi chuẩn bị truy vấn tổng số tin đăng: " . $conn->error);
}

if (!empty($params)) {
    $bind_param_values_references_total = [];
    $bind_param_values_references_total[] = $types;
    foreach ($params as &$param_total) {
        $bind_param_values_references_total[] = &$param_total;
    }
    $bindTotalSuccess = call_user_func_array([$stmtTotal, 'bind_param'], $bind_param_values_references_total);

    if ($bindTotalSuccess === false) {
        die("Lỗi gán tham số truy vấn tổng số tin đăng: " . $stmtTotal->error);
    }
}

$executeTotalSuccess = $stmtTotal->execute();
$total_records = 0;

if ($executeTotalSuccess) {
    $resultTotal = $stmtTotal->get_result();
    if ($resultTotal->num_rows > 0) {
        $rowTotal = $resultTotal->fetch_assoc();
        $total_records = $rowTotal['total'];
    }

    $resultTotal->free();

} else {
    die("Lỗi thực thi truy vấn tổng số tin đăng: " . $stmtTotal->error);
}
$stmtTotal->close();

$total_pages = ceil($total_records / $records_per_page);
$current_page = min($current_page, $total_pages > 0 ? $total_pages : 1);
$offset = ($current_page - 1) * $records_per_page;

$sql = "SELECT
            r.ma_phong,
            r.tieu_de,
            r.gia_thue,
            r.dien_tich,
            r.dia_chi_chi_tiet,
            r.quan_huyen,
            r.thanh_pho,
            r.luot_xem,
            (SELECT i.duong_dan_anh FROM images i WHERE i.ma_phong = r.ma_phong ORDER BY i.ma_hinh_anh ASC LIMIT 1) AS hinh_anh_dai_dien
        FROM
            rooms r"; 

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses); 
}

$sql .= " ORDER BY " . $order_by_sql; 
$sql .= " LIMIT ? OFFSET ?"; 

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Lỗi chuẩn bị truy vấn lấy dữ liệu trang: " . $conn->error);
}

$final_params = array_merge($params, [$records_per_page, $offset]);
$final_types = $types . 'ii'; 

$bind_param_values_references = [];
$bind_param_values_references[] = $final_types; 
foreach ($final_params as &$param) {
    $bind_param_values_references[] = &$param; 
}

$bindSuccess = call_user_func_array([$stmt, 'bind_param'], $bind_param_values_references);


if ($bindSuccess === false) {
    die("Lỗi gán tham số truy vấn lấy dữ liệu trang: " . $stmt->error);
}


$executeSuccess = $stmt->execute();

if ($executeSuccess === false) {
    die("Lỗi thực thi truy vấn lấy dữ liệu trang: " . $stmt->error);
}

// Lấy kết quả
$result = $stmt->get_result();

// Đóng statement SELECT
$stmt->close();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang chủ - Tìm kiếm phòng trọ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<header class="main-header">
    <div class="container">
        <h1>Phòng Trọ Sinh Viên</h1>
        <nav class="main-nav">
            <a href="them_tin.php">Đăng tin mới</a>
            <a href="quanly_tin_dang.php">Quản lý tin</a>
            <a href="chinhsua_thongtin.php">Tài khoản</a>
            <a href="dangky.php">Đăng ký</a>
            <a href="dangnhap.php">Đăng nhập</a>
             <a href="lienhe.php">thông tin liên hệ</a>
        </nav>
    </div>
</header>

<section class="search-section">
    <div class="container">
        <h2>Tìm kiếm phòng trọ</h2>
        <form action="index.php" method="GET" class="search-form">
            <div class="form-row">
                <input type="text" name="keyword" placeholder="Từ khóa..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                <input type="text" name="province" placeholder="Tỉnh/Thành phố" value="<?php echo htmlspecialchars($search_province); ?>">
                <input type="text" name="district" placeholder="Quận/Huyện" value="<?php echo htmlspecialchars($search_district); ?>">
            </div>

            <div class="form-row">
                <input type="number" name="min_price" placeholder="Giá từ (VNĐ)" value="<?php echo htmlspecialchars($min_price); ?>">
                <input type="number" name="max_price" placeholder="Giá đến (VNĐ)" value="<?php echo htmlspecialchars($max_price); ?>">
                <input type="number" name="min_area" placeholder="Diện tích từ (m²)" value="<?php echo htmlspecialchars($min_area); ?>">
                <input type="number" name="max_area" placeholder="Diện tích đến (m²)" value="<?php echo htmlspecialchars($max_area); ?>">
            </div>

            <div class="form-actions">
                <button type="submit">Tìm kiếm</button>
                <?php if (!empty($search_keyword) || !empty($min_price) || !empty($max_price) || !empty($search_province) || !empty($search_district) || !empty($min_area) || !empty($max_area)): // Đã xóa điều kiện liên quan đến $selected_amenities ?>
                    <a href="index.php" class="reset-link">Đặt lại</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</section>

<section class="results-section">
    <div class="container">
        <h3>
            <?php
            if (!empty($search_keyword) || !empty($min_price) || !empty($max_price) || !empty($search_province) || !empty($search_district) || !empty($min_area) || !empty($max_area)) { // Đã xóa điều kiện liên quan đến $selected_amenities
                echo "Kết quả tìm kiếm";
            } else {
                echo "Danh sách phòng trọ";
            }
            ?>
        </h3>

        <div class="sort-options">
            <?php
            $sort_base_url = "?keyword=" . urlencode($search_keyword)
                . "&min_price=" . urlencode($min_price)
                . "&max_price=" . urlencode($max_price)
                . "&province=" . urlencode($search_province)
                . "&district=" . urlencode($search_district)
                . "&min_area=" . urlencode($min_area)
                . "&max_area=" . urlencode($max_area);
            // foreach ($selected_amenities as $amenity_id) { // Đã xóa phần này
            //     $sort_base_url .= "&amenities[]=" . urlencode($amenity_id);
            // }
            ?>
            <a href="<?php echo $sort_base_url; ?>&sort=latest&page=<?php echo $current_page; ?>" class="<?php echo ($sort_by === 'latest' ? 'active' : ''); ?>">Mới nhất</a>
            <a href="<?php echo $sort_base_url; ?>&sort=views&page=<?php echo $current_page; ?>" class="<?php echo ($sort_by === 'views' ? 'active' : ''); ?>">Xem nhiều</a>
        </div>

        <div class="room-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()):
                    // Đặt đường dẫn ảnh mặc định nếu không có ảnh nào
                    $image_src = $row['hinh_anh_dai_dien'] ? htmlspecialchars($row['hinh_anh_dai_dien']) : 'assets/placeholder.jpg'; // Đảm bảo đường dẫn này đúng với ảnh mặc định của bạn
                ?>
                    <div class="room-card">
                        <div class="room-image">
                            <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($row['tieu_de']); ?>">
                        </div>
                        <div class="room-details">
                            <h4><?php echo htmlspecialchars($row['tieu_de']); ?></h4>
                            <p class="price"><strong>Giá:</strong> <?php echo number_format($row['gia_thue']); ?> VNĐ/tháng</p>
                            <p><strong>Diện tích:</strong> <?php echo htmlspecialchars($row['dien_tich']); ?> m²</p>
                            <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($row['dia_chi_chi_tiet']) . ', ' . htmlspecialchars($row['quan_huyen']) . ', ' . htmlspecialchars($row['thanh_pho']); ?></p>
                            <p><strong>Lượt xem:</strong> <?php echo htmlspecialchars($row['luot_xem'] ?? 0); ?></p>
                            <a href="chi_tiet_phong.php?id=<?php echo $row['ma_phong']; ?>">Xem chi tiết</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Không tìm thấy phòng trọ phù hợp.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="pagination-section">
    <div class="container pagination">
        <?php
        if ($total_pages > 1):
            $pagination_base_url = "?sort=" . urlencode($sort_by)
                . "&keyword=" . urlencode($search_keyword)
                . "&min_price=" . urlencode($min_price)
                . "&max_price=" . urlencode($max_price)
                . "&province=" . urlencode($search_province)
                . "&district=" . urlencode($search_district)
                . "&min_area=" . urlencode($min_area)
                . "&max_area=" . urlencode($max_area);


            for ($i = 1; $i <= $total_pages; $i++):
                if ($i == $current_page):
                    echo '<span class="current-page">' . $i . '</span>';
                else:
                    echo '<a href="' . $pagination_base_url . '&page=' . $i . '">' . $i . '</a>';
                endif;
            endfor;
        endif;
        ?>
    </div>
</section>

<footer class="main-footer">
    <div class="container">
        <p>&copy; <?php echo date('Y'); ?> Phòng Trọ 24H. All rights reserved.</p>
    </div>
</footer>

</body>
</html>