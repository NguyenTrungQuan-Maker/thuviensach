<?php

session_start();

include 'db.php'; 

$favoriteBooks = [];
$message = '';
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {

    $_SESSION['message'] = "<div class='alert alert-warning'>Vui lòng đăng nhập để xem sách yêu thích.</div>";
    header("location: dangnhap.php?redirect=" . urlencode($_SERVER['REQUEST_URI'])); // Chuyển hướng và lưu lại trang hiện tại
    exit;
}


$loggedInUserId = $_SESSION['user_id'];

if (!($conn instanceof mysqli)) {
    $message = "<div class='alert alert-danger'>Lỗi: Kết nối CSDL không phải là đối tượng MySQLi. Vui lòng kiểm tra file db.php</div>";
} else {
   
    $sql_favorites = "SELECT qs.MaSoSach, qs.TenSach, qs.TacGia, qs.AnhBia
                      FROM quyensach qs
                      JOIN sachyeuthich sy ON qs.MaSoSach = sy.MaSoSach
                      WHERE sy.IDNguoiDung = ?";

    if ($stmt = $conn->prepare($sql_favorites)) {
        $stmt->bind_param("s", $loggedInUserId); 
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $favoriteBooks[] = $row; 
            }
            $result->free();
        } else {
          
             if (!isset($_SESSION['message'])) {
                 $message = "<div class='alert alert-info text-center'>Bạn chưa thêm cuốn sách nào vào danh sách yêu thích.</div>";
             } else {
                
                 $message = $_SESSION['message'];
                 unset($_SESSION['message']); 
             }
        }
        $stmt->close();

    } else {
        $message = "<div class='alert alert-danger'>Lỗi khi chuẩn bị truy vấn sách yêu thích: " . $conn->error . "</div>";
    }

    
}


if (isset($_SESSION['message'])) {
    
    if (empty($message) || strpos($message, 'Lỗi') === false) {
        $message = $_SESSION['message'];
    }
    unset($_SESSION['message']); 
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
    <title>Sách yêu thích của tôi - Thư viện Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="index.css">
   
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
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
                        <a class="nav-link" href="index.php">
                             <i class="fas fa-home" style="margin-right: 5px;"></i> Trang Chủ
                        </a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="danhmucsach.php">
                             <i class="fas fa-book-open" style="margin-right: 5px;"></i> Danh mục sách
                        </a> </li>
                     <li class="nav-item">
                         <a class="nav-link active" aria-current="page" href="sachyeuthich.php"> <i class="fas fa-heart" style="margin-right: 5px;"></i> Sách được yêu thích </a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="sach_dang_muon.php">
                              <i class="fas fa-exchange-alt" style="margin-right: 5px;"></i> Mượn/Trả Sách</a> </li>
                     <li class="nav-item">
                          <a class="nav-link" href="#about-section">
                               <i class="fas fa-info-circle" style="margin-right: 5px;"></i> Giới thiệu</a> </li>
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
         <h1>Sách được yêu thích của tôi</h1>
         <p>Danh sách những cuốn sách bạn quan tâm.</p>
         <a href="danhmucsach.php" class="btn btn-light btn-lg">Tìm thêm sách</a>
     </div>


    <div class="container">
        <div class="favorites-section">
             <h3 class="section-title">Danh mục sách ưa thích</h3>
             <?php echo $message; // Hiển thị thông báo (ví dụ: chưa có sách yêu thích) ?>

            <?php if (!empty($favoriteBooks)): ?>
                <div class="row">
                    <?php
                        $base_image_path = 'assets/uploads/book_covers/';
                        $default_image = 'images/default_book.png'; 
                    ?>
                    <?php foreach ($favoriteBooks as $book): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4"> <div class="book-item-homepage">
                                <?php
                                   
                                    $imagePath = $base_image_path . htmlspecialchars($book['AnhBia'] ?? '');
                              
                                     $displayImage = !empty($book['AnhBia']) ? $imagePath : $default_image;
                                ?>
                                 <img src="<?= $displayImage ?>" alt="Bìa sách <?php echo htmlspecialchars($book['TenSach'] ?? 'Sách'); ?>" loading="lazy">
                                 <h6><?= htmlspecialchars($book['TenSach'] ?? 'N/A') ?></h6>
                                 <p>Tác giả: <?= htmlspecialchars($book['TacGia'] ?? 'N/A') ?></p>
                                 <a href="chitietsach.php?id=<?= urlencode($book['MaSoSach'] ?? '') ?>" class="btn btn-sm btn-info mt-2"><i class="fas fa-info-circle"></i> Chi tiết</a>
                             </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
