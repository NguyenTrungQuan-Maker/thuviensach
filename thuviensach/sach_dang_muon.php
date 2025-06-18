<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


include 'db.php'; 
$message = '';
$borrowedBooks = []; 
$error_db_connection = false;

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}


if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {

    $_SESSION['message'] = "<div class='alert alert-warning'>Vui lòng đăng nhập để xem sách đang mượn.</div>";

    $redirect_url = 'dangnhap.php?redirect=' . urlencode($_SERVER['REQUEST_URI']);
    header("location: " . $redirect_url);
    exit;
}


$loggedInUserId = $_SESSION['user_id'];


if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $message .= "<div class='alert alert-danger'>Lỗi kết nối CSDL: " . ($conn->connect_error ?? 'Biến kết nối $conn không tồn tại hoặc không phải đối tượng MySQLi.') . "</div>";
    $error_db_connection = true;
} else {
   
    $sql = "SELECT
                ctpm.SoPhieuMuon,
                ctpm.MaSoSach,
                qs.TenSach,
                qs.TacGia,
                qs.AnhBia, 
                ctpm.HanTra,
                ms.NgayMuon, 
                ctpm.TrangThai
            FROM
                chitietphieumuon ctpm
            JOIN
                muonsach ms ON ctpm.SoPhieuMuon = ms.SoPhieuMuon
            JOIN
                quyensach qs ON ctpm.MaSoSach = qs.MaSoSach
            WHERE
                ms.MaSoDG = ? AND TRIM(ctpm.TrangThai) COLLATE utf8mb4_unicode_ci = 'Đang mượn' COLLATE utf8mb4_unicode_ci"; 
            

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        $message .= "<div class='alert alert-danger'>Lỗi chuẩn bị truy vấn CSDL: " . $conn->error . "</div>";
       
        error_log("Lỗi prepare truy vấn sach_dang_muon: " . $conn->error);
    } else {
        
        $stmt->bind_param("i", $loggedInUserId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            if ($result->num_rows > 0) {
               
                while ($row = $result->fetch_assoc()) {
                    $borrowedBooks[] = $row;
                }
            }
         
            $result->free();
        } else {
            $message .= "<div class='alert alert-danger'>Lỗi khi thực thi truy vấn CSDL: " . $stmt->error . "</div>";
     
            error_log("Lỗi execute/get_result truy vấn sach_dang_muon: " . $stmt->error);
        }
    
        $stmt->close();
    }

}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thư viện Online - Trang Chủ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="index.css">
    <style>
      
.table-striped thead tr th {
    background-color: #3a3a4f !important;
    color: #f39c12 !important;
    font-weight: bold;
    border-bottom: 2px solid #555;
}

/* Mỗi ô trong tbody */
.table-striped tbody tr td,
.table-striped tbody tr th {
    background-color: #3a3a4f !important;
    color: white !important;
    vertical-align: middle;
}


.table-striped tbody tr th {
    color: #f39c12 !important;
    font-weight: bold;
}

.table-striped tbody tr td .btn-success {
    background-color: #218838;
    border: none;
}

.table-striped tbody tr td .btn-success:hover {
    background-color: #28a745
}

    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                 <i class="fas fa-book" style="margin-right: 5px;"></i> Thư viện Online
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php">
                             <i class="fas fa-home" style="margin-right: 5px;"></i> Trang Chủ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="danhmucsach.php">
                             <i class="fas fa-book-open" style="margin-right: 5px;"></i> Danh mục sách
                        </a>
                    </li>
                    <li class="nav-item">
                         <a class="nav-link" href="sachyeuthich.php"> <i class="fas fa-heart" style="margin-right: 5px;"></i> Sách được yêu thích </a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="sach_dang_muon.php">
                              <i class="fas fa-exchange-alt" style="margin-right: 5px;"></i> Mượn/Trả Sách</a>
                     </li>
                      <li class="nav-item">
                          <a class="nav-link" href="#about-section">
                               <i class="fas fa-info-circle" style="margin-right: 5px;"></i> Giới thiệu</a>
                      </li>
                    <li class="nav-item">
                        <?php
                       
                        if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                          
                            echo '<a class="nav-link btn btn-outline-secondary ms-2" href="dangxuat.php">';
                            echo '<i class="fas fa-sign-out-alt" style="margin-right: 5px;"></i> Đăng xuất';
                            echo '</a>';
          
                        } else {
                           
                            echo '<a class="nav-link btn btn-outline-primary ms-2" href="dangnhap.php">';
                            echo '<i class="fas fa-sign-in-alt" style="margin-right: 5px;"></i> Đăng nhập / Đăng ký';
                            echo '</a>';
                        }
                        ?>
                    </li>
                     </ul>
            </div>
        </div>
    </nav>

    <div class="banner">
        <h1>Chào mừng đến với Thư viện Online</h1>
        <p>Khám phá thế giới tri thức rộng lớn ngay trong tầm tay bạn.</p>
        <a href="danhmucsach.php" class="btn btn-light btn-lg">Tìm sách ngay!</a>
    </div>
    <div class="container mt-5">
        <h2 class="text-center mb-4 section-title">Sách Bạn Đang Mượn</h2>

        <?php
        // Hiển thị thông báo (nếu có)
        if (!empty($message)) {
            echo $message;
        }
        ?>

        <?php if ($error_db_connection): ?>
            <p class="alert alert-danger">Không thể tải danh sách sách đang mượn do lỗi kết nối CSDL.</p>
        <?php elseif (empty($borrowedBooks)): ?>
            <p class="alert alert-info text-center">Bạn hiện không mượn cuốn sách nào.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th scope="col">STT</th>
                            <th scope="col">Ảnh bìa</th>
                            <th scope="col">Tên sách</th>
                            <th scope="col">Tác giả</th>
                            <th scope="col">Ngày mượn</th>
                            <th scope="col">Hạn trả</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php foreach ($borrowedBooks as $book): ?>
                            <tr>
                                <th scope="row"><?= $counter++ ?></th>
                                <td>
                                     <?php
                                       
                                        $imagePath = 'assets/uploads/book_covers/' . urlencode($book['AnhBia'] ?? '');
                                        $default_image = 'assets/images/placeholder_image.png'; // Ảnh mặc định

                             
                                        $displayImage = !empty($book['AnhBia']) ? $imagePath : $default_image;
                                    ?>
                                    <img src="<?= $displayImage ?>" alt="Bìa sách" style="width: 60px; height: auto; object-fit: cover; border-radius: 3px;">
                                </td>
                                <td><?= htmlspecialchars($book['TenSach'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($book['TacGia'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars((new DateTime($book['NgayMuon'] ?? 'now'))->format('d/m/Y')) ?? 'N/A' ?></td>
                                <td>
                                     <?php
                                       
                                        $hanTraString = $book['HanTra'] ?? null;
                                        $hanTraFormatted = 'N/A';
                                        $classColor = '';

                                        if ($hanTraString) {
                                            try {
                                                 $hanTra = new DateTime($hanTraString);
                                                 $ngayHienTai = new DateTime();
                                                 $hanTraFormatted = htmlspecialchars($hanTra->format('d/m/Y'));

            
                                                  if ($hanTra < $ngayHienTai) {
                                                      $classColor = 'text-danger fw-bold'; 
                                                  } elseif ($hanTra >= $ngayHienTai && $hanTra->diff($ngayHienTai)->days <= 3) {
                                                       $classColor = 'text-warning'; 
                                                  }
                                            } catch (Exception $e) {
                                               
                                                $hanTraFormatted = 'Ngày không hợp lệ';
                                                $classColor = 'text-danger';
                                                 error_log("Lỗi định dạng ngày HanTra cho SoPhieuMuon=" . ($book['SoPhieuMuon'] ?? 'N/A') . ": " . ($book['HanTra'] ?? 'NULL') . " - " + $e->getMessage());
                                            }
                                        }
                                     ?>
                                     <span class="<?= $classColor ?>"><?= $hanTraFormatted ?></span>
                                </td>
                                <td><?= htmlspecialchars($book['TrangThai'] ?? 'N/A') ?></td>
                                <td>
                                     <a href="yeu_cau_tra.php?sophieumuon=<?= urlencode($book['SoPhieuMuon'] ?? '') ?>&masosach=<?= urlencode($book['MaSoSach'] ?? '') ?>"
                                        class="btn btn-sm btn-success"
                                        onclick="return confirm('Bạn có chắc chắn muốn yêu cầu trả sách này không? Yêu cầu sẽ được gửi đến admin.');">
                                         <i class="fas fa-undo"></i> Yêu cầu trả
                                     </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
              <div class="about-section mb-5" id="about-section">
            <h3 class="section-title">Về Thư viện Online</h3>
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <p class="text-center">
                        Chào mừng bạn đến với Thư viện Online - nơi mở ra cánh cửa tri thức vô tận. Chúng tôi tự hào mang đến một bộ sưu tập sách đa dạng và phong phú, từ văn học kinh điển đến các tài liệu khoa học chuyên sâu, phục vụ mọi nhu cầu đọc và nghiên cứu của bạn. Với giao diện thân thiện, dễ sử dụng, việc tìm kiếm và khám phá sách chưa bao giờ dễ dàng đến thế. Hãy cùng chúng tôi xây dựng một cộng đồng yêu sách và kiến tạo tương lai từ trang sách hôm nay!
                    </p>
                    <div class="text-center mt-3">
                         <a href="#" class="btn btn-outline-secondary">Tìm hiểu thêm</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php
    // Đóng kết nối CSDL nếu nó vẫn mở
    if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
       $conn->close();
    }
    ?>
</body>
</html>
