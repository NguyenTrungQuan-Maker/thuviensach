<?php

session_start();

include 'db.php'; 

$message = '';
$latestBookList = []; 
$mostBorrowedList = []; 
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); 
}


if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $message .= "<div class='alert alert-danger'>Lỗi kết nối CSDL: " . ($conn->connect_error ?? 'Biến kết nối $conn không tồn tại hoặc không phải đối tượng MySQLi.') . "</div>";
    $error_db_connection = true;
} else {
    $error_db_connection = false;

    $sql_latest_books = "SELECT qs.MaSoSach, qs.TenSach, qs.TacGia, qs.AnhBia
                         FROM quyensach qs
                         ORDER BY qs.MaSoSach DESC LIMIT 8";

    $result_latest_books = $conn->query($sql_latest_books);

    if ($result_latest_books) {
        if ($result_latest_books->num_rows > 0) {
            while($row = $result_latest_books->fetch_assoc()) {
                $latestBookList[] = $row;
            }
           
        }
    } else {
        $message .= "<div class='alert alert-danger'>Lỗi khi lấy sách mới cập nhật: " . $conn->error . "</div>";
    }

    $sql_most_borrowed = "SELECT ctp.MaSoSach, COUNT(ctp.MaSoSach) AS LuotMuon, qs.TenSach, qs.TacGia, qs.AnhBia
                          FROM chitietphieumuon ctp
                          JOIN quyensach qs ON ctp.MaSoSach = qs.MaSoSach
                          WHERE ctp.TrangThai = 'Đang mượn' OR ctp.TrangThai = 'Đã trả' 
                          GROUP BY ctp.MaSoSach, qs.TenSach, qs.TacGia, qs.AnhBia
                          ORDER BY LuotMuon DESC LIMIT 8";

    $result_most_borrowed = $conn->query($sql_most_borrowed);

    if ($result_most_borrowed) {
        if ($result_most_borrowed->num_rows > 0) {
            while($row = $result_most_borrowed->fetch_assoc()) {
                $mostBorrowedList[] = $row;
            }
            
        }
    } else {
         $message .= "<div class='alert alert-danger'>Lỗi khi lấy sách được mượn nhiều nhất: " . $conn->error . "</div>";
    }

}

if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
    $conn->close();
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

    <div class="container">
        <?php

        if (!empty($message)) {
            echo $message;
        }
        ?>

        <div class="book-list-section latest-books mb-5">
             <h3 class="section-title">Sách mới cập nhật</h3>
             <div class="row">
                 <?php if (!empty($latestBookList)): ?>
                     <?php foreach ($latestBookList as $book): ?>
                         <div class="col-lg-3 col-md-4 col-sm-6">
                             <div class="book-item-homepage">
                                 <?php

                                 $cover_image = !empty($book['AnhBia']) ? 'assets/uploads/book_covers/' . htmlspecialchars($book['AnhBia']) : 'assets/images/placeholder_image.png';
                                 ?>
                                  <img src="<?= $cover_image ?>" alt="Bìa sách" loading="lazy" onerror="this.onerror=null; this.src='assets/images/placeholder_image.png';">
                                 <h6><?= htmlspecialchars($book['TenSach']) ?></h6>
                                 <p>Tác giả: <?= htmlspecialchars($book['TacGia']) ?></p>
                                 <a href="chitietsach.php?id=<?= urlencode($book['MaSoSach']) ?>" class="btn btn-sm btn-info mt-2"><i class="fas fa-info-circle"></i> Chi tiết</a>
                             </div>
                         </div>
                     <?php endforeach; ?>
                 <?php else: ?>
                     <div class="col-md-12">
                         <p class="text-center">Hiện chưa có sách mới cập nhật.</p>
                     </div>
                 <?php endif; ?>
             </div>
         </div>

         <div class="book-list-section most-borrowed-books mb-5">
              <h3 class="section-title">Sách được mượn nhiều nhất</h3>
              <div class="row">
                  <?php if (!empty($mostBorrowedList)): ?>
                      <?php foreach ($mostBorrowedList as $book): ?>
                          <div class="col-lg-3 col-md-4 col-sm-6">
                              <div class="book-item-homepage">
                                  <?php
                            
                                  $cover_image = !empty($book['AnhBia']) ? 'assets/uploads/book_covers/' . htmlspecialchars($book['AnhBia']) : 'assets/images/placeholder_image.png';
                                  ?>
                                   <img src="<?= $cover_image ?>" alt="Bìa sách" loading="lazy" onerror="this.onerror=null; this.src='assets/images/placeholder_image.png';">
                                  <h6><?= htmlspecialchars($book['TenSach']) ?></h6>
                                  <p>Tác giả: <?= htmlspecialchars($book['TacGia']) ?></p>
                                  <p><small>(Lượt mượn: <?= htmlspecialchars($book['LuotMuon']) ?>)</small></p>
                                  <a href="chitietsach.php?id=<?= urlencode($book['MaSoSach']) ?>" class="btn btn-sm btn-info mt-2"><i class="fas fa-info-circle"></i> Chi tiết</a>
                              </div>
                          </div>
                      <?php endforeach; ?>
                  <?php else: ?>
                      <div class="col-md-12">
                          <p class="text-center">Chưa có dữ liệu sách được mượn nhiều nhất.</p>
                      </div>
                  <?php endif; ?>
              </div>
          </div>

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
</body>
</html>
