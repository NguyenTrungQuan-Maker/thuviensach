<?php
session_start();
include 'db.php';

$book = null;
$recommendedBooks = [];
$message = '';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $maSoSach = trim($_GET['id']);

    if (!($conn instanceof mysqli)) {
        $message = "<div class='alert alert-danger'>Lỗi: Kết nối CSDL không phải là đối tượng MySQLi. Vui lòng kiểm tra file db.php</div>";
    } else {

        $sql_book_detail = "SELECT qs.MaSoSach, qs.TenSach, qs.AnhBia, qs.NoiDungTomLuoc, qs.MaLoaiSach
                            FROM quyensach qs
                            WHERE qs.MaSoSach = ? LIMIT 1";

        if ($stmt = $conn->prepare($sql_book_detail)) {
            $stmt->bind_param("s", $maSoSach);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $book = $result->fetch_assoc();
            } else {
                $message = "<div class='alert alert-warning'>Không tìm thấy sách với Mã số: " . htmlspecialchars($maSoSach) . "</div>";
            }
            $stmt->close();
        } else {
            $message .= "<div class='alert alert-danger'>Lỗi khi chuẩn bị truy vấn chi tiết sách: " . $conn->error . "</div>";
        }

        if ($book && isset($book['MaLoaiSach'])) {
            $maLoaiSach = $book['MaLoaiSach'];
            $currentBookId = $book['MaSoSach'];

            $sql_recommended = "SELECT qs.MaSoSach, qs.TenSach, qs.TacGia, qs.AnhBia
                                FROM quyensach qs
                                WHERE qs.MaLoaiSach = ? AND qs.MaSoSach != ?
                                ORDER BY RAND() LIMIT 5";

            if ($stmt_rec = $conn->prepare($sql_recommended)) {
                $stmt_rec->bind_param("ss", $maLoaiSach, $currentBookId);
                $stmt_rec->execute();
                $result_rec = $stmt_rec->get_result();

                if ($result_rec->num_rows > 0) {
                    while ($row_rec = $result_rec->fetch_assoc()) {
                        $recommendedBooks[] = $row_rec;
                    }
                    $result_rec->free();
                }
                $stmt_rec->close();
            } else {
                error_log("Lỗi khi chuẩn bị truy vấn sách liên quan: " . $conn->error);
            }
        }
    }
} else {
    $message = "<div class='alert alert-warning'>Vui lòng cung cấp mã sách để xem nội dung đọc thử.</div>";
}

// Thiết lập tiêu đề trang mặc định nếu không tìm thấy sách
$pageTitle = $book ? htmlspecialchars($book['TenSach']) . ' - Đọc thử' : 'Đọc thử Sách';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="index.css">
    <style>
        body {
            background-color: #1e1e2f;
            color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            padding-top: 70px;
            /* Để phù hợp với navbar nếu có */
        }

        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        .read-preview-container {
            background-color: #2b2b3d;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.4);
        }

        /* Sử dụng flexbox cho nội dung chính */
        .main-content-flex {
            display: flex;
            flex-wrap: wrap;
            /* Cho phép các mục xuống dòng trên màn hình nhỏ */
            gap: 20px;
            /* Khoảng cách giữa các cột */
        }

        .book-info-and-summary {
            flex: 2;
            /* Chiếm 2 phần trong 3 phần của tổng số */
            min-width: 300px;
            /* Đảm bảo không quá nhỏ trên màn hình hẹp */
        }

        .recommended-books-col {
            flex: 1;
            /* Chiếm 1 phần */
            min-width: 250px;
            /* Đảm bảo không quá nhỏ */
            background-color: #252536;
            /* Nền cho cột đề xuất */
            padding: 20px;
            border-radius: 8px;
        }

        /* Ảnh bìa */
        .book-cover-preview {
            width: 150px;
            /* Kích thước ảnh bìa nhỏ hơn để hiển thị ngang */
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            margin-right: 20px;
            /* Khoảng cách với nội dung tóm lược */
            float: left;
            /* Đẩy ảnh sang trái, văn bản chảy quanh */
        }

        /* Tiêu đề sách trong phần đọc thử */
        .book-title-read {
            color: #00bfff;
            margin-bottom: 15px;
            text-align: left;
            /* Căn trái tiêu đề */
        }

        /* Nội dung tóm lược (phần đọc thử) */
        .book-summary-content {
            background-color: #252536;
            /* Nền riêng cho phần tóm lược nếu muốn */
            padding: 20px;
            border-left: 5px solid #007bff;
            border-radius: 6px;
            text-align: left;
            font-size: 1.05rem;
            line-height: 1.8;
            color: #ccc;
            overflow: hidden;
            /* Clear float */
        }

        /* Nút quay lại */
        .btn-back-to-detail {
            margin-top: 30px;
            background-color: #6c757d;
            color: white;
            padding: 10px 22px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .btn-back-to-detail:hover {
            background-color: #5a6268;
            color: white;
        }

        /* Phần sách cùng thể loại */
        .recommended-books h4 {
            color: #ffffff;
            margin-bottom: 20px;
            border-bottom: 1px solid #444;
            padding-bottom: 10px;
        }

        .recommended-book-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #555;
        }

        .recommended-book-item:last-child {
            border-bottom: none;
        }

        .recommended-book-item img {
            width: 50px;
            /* Ảnh nhỏ hơn trong danh sách đề xuất */
            height: 70px;
            margin-right: 15px;
            border-radius: 4px;
            object-fit: cover;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .recommended-book-item h6 {
            font-size: 0.95rem;
            color: #fff;
            margin-bottom: 4px;
        }

        .recommended-book-item p {
            font-size: 0.8rem;
            color: #ccc;
        }

        /* Điều chỉnh cho màn hình nhỏ hơn */
        @media (max-width: 768px) {
            .main-content-flex {
                flex-direction: column;
                /* Xếp chồng các cột trên màn hình nhỏ */
            }

            .book-info-and-summary,
            .recommended-books-col {
                flex: none;
                width: 100%;
            }

            .book-cover-preview {
                float: none;
                /* Bỏ float trên màn hình nhỏ */
                margin: 0 auto 20px auto;
                /* Căn giữa lại */
            }
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
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home" style="margin-right: 5px;"></i> Trang Chủ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="danhmucsach.php">
                            <i class="fas fa-book-open" style="margin-right: 5px;"></i> Danh mục sách
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sachyeuthich.php">
                            <i class="fas fa-heart" style="margin-right: 5px;"></i> Sách được yêu thích</a>
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
                        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                            <a class="nav-link btn btn-outline-secondary ms-2" href="dangxuat.php">
                                <i class="fas fa-sign-out-alt" style="margin-right: 5px;"></i> Đăng xuất
                            </a>
                        <?php else: ?>
                            <a class="nav-link btn btn-outline-primary ms-2" href="dangnhap.php">
                                <i class="fas fa-sign-in-alt" style="margin-right: 5px;"></i> Đăng nhập / Đăng ký
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>


    <div class="container">
        <div class="read-preview-container">
            <?php echo $message; // Hiển thị thông báo 
            ?>

            <?php if ($book): // Chỉ hiển thị nếu tìm thấy sách 
            ?>
                <div class="main-content-flex">
                    <div class="book-info-and-summary">
                        <h2 class="book-title-read"><?= htmlspecialchars($book['TenSach']) ?></h2>
                        <div class="clearfix"> <img src="<?= !empty($book['AnhBia']) ? 'assets/uploads/book_covers/' . htmlspecialchars($book['AnhBia']) : 'assets/uploads/book_covers/placeholder_image.png' ?>"
                                alt="Bìa sách" class="img-fluid book-cover-preview">
                            <div class="book-summary-content">
                                <h4>Nội dung đọc thử (Tóm lược):</h4>
                                <p><?= nl2br(htmlspecialchars($book['NoiDungTomLuoc'] ?? 'Chưa có nội dung tóm lược cho cuốn sách này.')) ?></p>
                            </div>
                        </div>
                        <a href="javascript:history.back()" class="btn btn-back-to-detail">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>

                    <div class="recommended-books-col">
                        <div class="recommended-books">
                            <h4>Sách cùng Thể loại</h4>
                            <?php if (!empty($recommendedBooks)): ?>
                                <?php foreach ($recommendedBooks as $recBook): ?>
                                    <a href="chitietsach.php?id=<?= urlencode($recBook['MaSoSach']) ?>" class="text-decoration-none text-dark">
                                        <div class="recommended-book-item">
                                            <?php
                                            $recImagePath = !empty($recBook['AnhBia']) ? 'assets/uploads/book_covers/' . htmlspecialchars($recBook['AnhBia']) : 'assets/uploads/book_covers/placeholder_image.png';
                                            ?>
                                            <img src="<?= $recImagePath ?>" alt="Bìa sách" loading="lazy">
                                            <div>
                                                <h6><?= htmlspecialchars($recBook['TenSach']) ?></h6>
                                                <p>Tác giả: <?= htmlspecialchars($recBook['TacGia']) ?></p>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p><small>Không tìm thấy sách cùng thể loại.</small></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>