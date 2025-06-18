<?php

session_start();

include 'db.php';

$book = null;
$recommendedBooks = [];
$message = '';

$isFavorited = false;
$loggedInUserId = null;

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['user_id'])) {
    $loggedInUserId = $_SESSION['user_id'];
}


if (isset($_GET['id']) && !empty($_GET['id'])) {
    $maSoSach = trim($_GET['id']);


    if (!($conn instanceof mysqli)) {
        $message = "<div class='alert alert-danger'>Lỗi: Kết nối CSDL . Vui lòng kiểm tra file db.php</div>";
    } else {

        $sql_book_detail = "SELECT qs.MaSoSach, qs.TenSach, qs.TacGia, qs.NamXB, qs.LanXB, qs.SoLuong, qs.NoiDungTomLuoc, qs.AnhBia,
                            ls.LoaiSach, nxb.TenNXB, qs.MaLoaiSach
                   FROM quyensach qs
                   JOIN loaisach ls ON qs.MaLoaiSach = ls.MaLoaiSach
                   JOIN nhaxuatban nxb ON qs.MaSoNXB = nxb.MaSoNXB
                   WHERE qs.MaSoSach = ? LIMIT 1";

        if ($stmt = $conn->prepare($sql_book_detail)) {
            $stmt->bind_param("s", $maSoSach);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $book = $result->fetch_assoc();


                if ($loggedInUserId) {

                    $sql_check_favorite = "SELECT COUNT(*) FROM sachyeuthich WHERE IDNguoiDung = ? AND MaSoSach = ?";

                    if ($stmt_fav = $conn->prepare($sql_check_favorite)) {

                        $stmt_fav->bind_param("ss", $loggedInUserId, $maSoSach);
                        $stmt_fav->execute();
                        $stmt_fav->bind_result($count);
                        $stmt_fav->fetch();
                        if ($count > 0) {
                            $isFavorited = true;
                        }
                        $stmt_fav->close();
                    } else {

                        error_log("Lỗi khi chuẩn bị truy vấn kiểm tra trạng thái yêu thích: " . $conn->error);
                    }
                }
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

    $message = "<div class='alert alert-warning'>Vui lòng chọn một cuốn sách để xem chi tiết.</div>";
}

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $book ? htmlspecialchars($book['TenSach']) : 'Chi tiết Sách' ?> - Thư viện Online</title>
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
        }

        /* Book Detail Container */
        .book-detail-container {
            background-color: #2b2b3d;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.4);
        }

        /* Book Cover */
        .book-detail-cover {
            width: 100%;
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        /* Info Box */
        .book-detail-info-box {
            background-color: #252536;
            padding: 20px;
            border-left: 5px solid #007bff;
            border-radius: 6px;
            margin-top: 10px;
        }

        /* Title & Info */
        .book-detail-info h2 {
            color: #00bfff;
            margin-bottom: 20px;
        }

        .book-detail-info p {
            margin-bottom: 10px;
            font-size: 1.05rem;
        }

        .book-detail-info p strong {
            color: #f39c12;
        }

        /* Description */
        .book-detail-description {
            margin-top: 30px;
            border-top: 1px solid #444;
            padding-top: 20px;
        }

        .book-detail-description h4 {
            color: #ffcc00;
            margin-bottom: 15px;
        }

        /* Recommended Books */
        .recommended-books {
            margin-top: 40px;
            border-top: 1px solid #444;
            padding-top: 20px;
        }

        .recommended-books h4 {
            color: #ffffff;
            margin-bottom: 20px;
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
            width: 60px;
            height: 80px;
            margin-right: 15px;
            border-radius: 4px;
            object-fit: cover;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .recommended-book-item h6 {
            font-size: 1rem;
            color: #fff;
            margin-bottom: 4px;
        }

        .recommended-book-item p {
            font-size: 0.85rem;
            color: #ccc;
        }

        /* Buttons */
        .btn-actions {
            margin-top: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn-borrow,
        .btn-favorite,
        .btn-back {
            font-size: 1rem;
            padding: 10px 22px;
            border-radius: 25px;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-read {
            background-color: #007bff;
            color: white;
            font-size: 1rem;
            padding: 10px 22px;
            border-radius: 25px;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-read:hover {
            background-color: #0069d9;
            color: white;
        }

        .btn-borrow {
            background-color: #28a745;
            color: white;
        }

        .btn-borrow:hover {
            background-color: #218838;
        }

        .btn-favorite {
            background-color: #dc3545;
            color: white;
        }

        .btn-favorite:hover {
            background-color: #c82333;
        }

        .btn-back {
            background-color: #6c757d;
            color: white;
        }

        .btn-back:hover {
            background-color: #5a6268;
        }

        /* Navbar Fixed */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
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
                        <a class="nav-link active" aria-current="page" href="danhmucsach.php">
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
        <div class="book-detail-container">

            <?php echo $message;  ?>

            <?php if ($book): ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-5 text-center mb-4">
                                <?php

                                $imagePath = !empty($book['AnhBia']) ? 'assets/uploads/book_covers/' . htmlspecialchars($book['AnhBia']) : 'assets/uploads/book_covers/placeholder_image.png'; // <-- Cập nhật đường dẫn ảnh mặc định
                                ?>
                                <img src="<?= $imagePath ?>" alt="Bìa sách" class="img-fluid book-detail-cover">
                            </div>
                            <div class="col-md-7 book-detail-info">
                                <div class="book-detail-info-box">
                                    <h2><?= htmlspecialchars($book['TenSach']) ?></h2>
                                    <p><strong>Mã số sách:</strong> <?= htmlspecialchars($book['MaSoSach']) ?></p>
                                    <p><strong>Tác giả:</strong> <?= htmlspecialchars($book['TacGia']) ?></p>
                                    <p><strong>Nhà xuất bản:</strong> <?= htmlspecialchars($book['TenNXB']) ?></p>
                                    <p><strong>Thể loại:</strong> <?= htmlspecialchars($book['LoaiSach']) ?></p>
                                    <p><strong>Năm xuất bản:</strong> <?= htmlspecialchars($book['NamXB']) ?></p>
                                    <p><strong>Lần xuất bản:</strong> <?= htmlspecialchars($book['LanXB']) ?></p>
                                    <p><strong>Số lượng:</strong> <?= htmlspecialchars($book['SoLuong']) ?></p>
                                    <p><strong>Tình trạng:</strong> Đang cập nhật (Tổng: <?= htmlspecialchars($book['SoLuong']) ?>)</p>

                                    <div class="btn-actions mt-4">
                                        <?php if ($book['SoLuong'] > 0): ?>
                                            <?php if ($loggedInUserId): ?>
                                                <a href="yeu_cau_muon.php?sach_id=<?= urlencode($book['MaSoSach']) ?>" class="btn btn-success btn-borrow" onclick="return confirm('Bạn có chắc chắn muốn yêu cầu mượn sách \'<?= htmlspecialchars($book['TenSach']) ?>\' không?');"><i class="fas fa-book"></i> Yêu cầu mượn</a>
                                            <?php else: ?>
                                                <button class="btn btn-outline-secondary btn-borrow" disabled title="Đăng nhập để yêu cầu mượn"><i class="fas fa-book"></i> Yêu cầu mượn</button>
                                            <?php endif; ?>
                                            <a href="doc_thu.php?id=<?= urlencode($book['MaSoSach']) ?>" class="btn btn-primary btn-read">
                                                <i class="fas fa-book-reader"></i> Đọc thử
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-borrow" disabled><i class="fas fa-times-circle"></i> Hết sách</button>
                                        <?php endif; ?>

                                        <?php if ($loggedInUserId): ?>
                                            <a href="xulynutyeuthich.php?id=<?= urlencode($book['MaSoSach']) ?>&action=<?= $isFavorited ? 'remove' : 'add' ?>" class="btn btn-outline-danger btn-favorite">
                                                <?php if ($isFavorited): ?>
                                                    <i class="fas fa-heart"></i> Bỏ yêu thích
                                                <?php else: ?>
                                                    <i class="far fa-heart"></i> Yêu thích
                                                <?php endif; ?>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary btn-favorite" disabled title="Đăng nhập để yêu thích">
                                                <i class="far fa-heart"></i> Yêu thích
                                            </button>
                                        <?php endif; ?>

                                        <a href="index.php" class="btn btn-secondary btn-back"><i class="fas fa-arrow-left"></i> Quay lại</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="book-detail-description">
                            <h4>Nội dung tóm lược:</h4>
                            <p><?= nl2br(htmlspecialchars($book['NoiDungTomLuoc'] ?? 'Chưa có nội dung tóm lược.')) ?></p>
                        </div>
                    </div>

                    <div class="col-md-4">
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