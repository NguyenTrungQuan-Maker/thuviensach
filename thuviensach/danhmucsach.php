<?php

session_start();

include 'db.php';

$bookList = [];
$genreList = [];
$publisherList = [];
$message = '';

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {

    $message = "<div class='alert alert-danger text-center'>Lỗi kết nối CSDL: " . ($conn->connect_error ?? 'Biến kết nối $conn không tồn tại hoặc không phải đối tượng MySQLi.') . " Vui lòng kiểm tra file db.php</div>";
} else {

    $conn->set_charset("utf8mb4");

    $sql_all_books = "SELECT
                        qs.MaSoSach,
                        qs.TenSach,
                        qs.TacGia,
                        qs.AnhBia,
                        tl.LoaiSach,         
                        nxb.TenNXB           
                      FROM
                        quyensach qs
                      JOIN
                        loaisach tl ON qs.MaLoaiSach = tl.MaLoaiSach 
                      JOIN
                        nhaxuatban nxb ON qs.MaSoNXB = nxb.MaSoNXB 
                      ORDER BY
                        qs.TenSach ASC";

    $result_books = $conn->query($sql_all_books);

    if ($result_books) {

        if ($result_books->num_rows > 0) {

            while ($row = $result_books->fetch_assoc()) {
                $bookList[] = $row;
            }
            $result_books->free();
        } else {

            $count_sql = "SELECT COUNT(*) FROM quyensach";
            $count_result = $conn->query($count_sql);
            $total_books_in_quyensach = ($count_result && $count_result->num_rows > 0) ? $count_result->fetch_row()[0] : 0;
            if ($count_result) $count_result->free();

            if ($total_books_in_quyensach > 0) {

                $message = "<div class='alert alert-warning text-center'>Có sách trong thư viện, nhưng không hiển thị được đầy đủ thông tin (kiểm tra quan hệ giữa quyensach với loaisach/nhaxuatban trong CSDL).</div>";
            } else {

                $message = "<div class='alert alert-info text-center'>Hiện tại chưa có sách nào trong thư viện.</div>";
            }
        }
    } else {

        $message = "<div class='alert alert-danger text-center'>Lỗi khi lấy danh sách sách từ CSDL: " . $conn->error . "</div>";
    }
    $sql_genres = "SELECT DISTINCT LoaiSach FROM loaisach ORDER BY LoaiSach ASC";
    $result_genres = $conn->query($sql_genres);
    if ($result_genres) {
        while ($row = $result_genres->fetch_assoc()) {
            $genreList[] = $row['LoaiSach'];
        }
        $result_genres->free();
    } else {

        error_log("Lỗi khi lấy danh sách thể loại: " . $conn->error);
    }

    $sql_publishers = "SELECT DISTINCT TenNXB FROM nhaxuatban ORDER BY TenNXB ASC";
    $result_publishers = $conn->query($sql_publishers);
    if ($result_publishers) {
        while ($row = $result_publishers->fetch_assoc()) {
            $publisherList[] = $row['TenNXB'];
        }
        $result_publishers->free();
    } else {
        error_log("Lỗi khi lấy danh sách nhà xuất bản: " . $conn->error);
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
    <link rel="stylesheet" href="danhmucsach.css">
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
        <h1>Thế Giới Sách Kết Nối Tri Thức Việt</h1>
        <p>Khám phá tất cả sách có trong thư viện của chúng tôi</p>
        <a href="#catalog-list-section" class="btn btn-light btn-lg">Xem danh sách</a>
    </div>


    <div class="container catalog-section" id="catalog-list-section">
        <h2 class="section-title">Danh Mục Sách</h2>

        <?php
        if (!empty($message)) {
            echo $message;
        }
        ?>

        <?php if (!empty($bookList)): // Chỉ hiển thị khu vực tìm kiếm/lọc và danh sách nếu có sách 
        ?>
            <div class="search-filter-area">
                <div class="search-bar">
                    <input type="text" id="searchInput" class="form-control" placeholder="Tìm kiếm theo tên sách, tác giả...">
                    <button id="searchButton" class="btn btn-primary"><i class="fas fa-search"></i> Tìm kiếm</button>
                </div>

                <div class="filter-options">
                    <div class="filter-group">
                        <label for="genreFilter">Thể loại:</label>
                        <select id="genreFilter" class="form-select">
                            <option value="">Tất cả thể loại</option>
                            <?php

                            foreach ($genreList as $genre):
                            ?>
                                <option value="<?php echo htmlspecialchars($genre); ?>"><?php echo htmlspecialchars($genre); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="publisherFilter">Nhà xuất bản:</label>
                        <select id="publisherFilter" class="form-select">
                            <option value="">Tất cả NXB</option>
                            <?php

                            foreach ($publisherList as $publisher):
                            ?>
                                <option value="<?php echo htmlspecialchars($publisher); ?>"><?php echo htmlspecialchars($publisher); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row book-list-grid" id="bookListGrid">
                <?php

                foreach ($bookList as $book):
                ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4 book-item-col">
                        <div class="book-item-homepage"
                            data-genre="<?php echo htmlspecialchars($book['LoaiSach'] ?? ''); ?>" data-publisher="<?php echo htmlspecialchars($book['TenNXB'] ?? ''); ?>" data-title="<?php echo htmlspecialchars(strtolower($book['TenSach'] ?? '')); ?>" data-author="<?php echo htmlspecialchars(strtolower($book['TacGia'] ?? '')); ?>">
                            <?php

                            $base_image_path = 'assets/uploads/book_covers/';
                            $default_image = 'images/default_book.png';

                            $imagePath = $base_image_path . htmlspecialchars($book['AnhBia'] ?? '');
                            $displayImage = !empty($book['AnhBia']) ? $imagePath : $default_image;

                            ?>
                            <img src="<?php echo $displayImage; ?>" alt="Ảnh bìa <?php echo htmlspecialchars($book['TenSach'] ?? 'Sách'); ?>" loading="lazy">
                            <h6><?php echo htmlspecialchars($book['TenSach'] ?? 'N/A'); ?></h6>
                            <p>Tác giả: <?php echo htmlspecialchars($book['TacGia'] ?? 'N/A'); ?></p> <a href="chitietsach.php?id=<?= urlencode($book['MaSoSach'] ?? '') ?>" class="btn btn-sm btn-info mt-2"><i class="fas fa-info-circle"></i> Chi tiết</a>
                        </div>
                    </div>
                <?php endforeach; ?>
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
        <?php else: // Hiển thị thông báo nếu không có sách trong danh sách $bookList 
        ?>
            <?php

            if (empty($message)) {
                echo "<div class='alert alert-info text-center'>Không tìm thấy sách nào phù hợp.</div>";
            }
            ?>
        <?php endif; ?>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('searchInput');
                const searchButton = document.getElementById('searchButton');
                const genreFilter = document.getElementById('genreFilter');
                const publisherFilter = document.getElementById('publisherFilter');
                const bookListGrid = document.getElementById('bookListGrid');


                if (!bookListGrid) {
                    console.log("Không tìm thấy container lưới sách hoặc không có sách để hiển thị.");

                }

                const bookItemCols = bookListGrid ? bookListGrid.querySelectorAll('.book-item-col') : [];

                function filterBooks() {

                    if (bookItemCols.length === 0) {
                        console.log("Không có mục sách nào để lọc.");
                        return;
                    }

                    const searchTerm = searchInput.value.toLowerCase();

                    const genre = genreFilter.value;
                    const publisher = publisherFilter.value;

                    console.log("Lọc: Tìm kiếm='" + searchTerm + "', Thể loại='" + genre + "', NXB='" + publisher + "'"); // Log để debug

                    bookItemCols.forEach(function(bookItemCol) {

                        const bookItem = bookItemCol.querySelector('.book-item-homepage');


                        if (!bookItem) {
                            bookItemCol.style.display = "none";
                            return;
                        }

                        const itemGenre = bookItem.getAttribute('data-genre') ?? '';
                        const itemPublisher = bookItem.getAttribute('data-publisher') ?? '';
                        const itemTitle = bookItem.getAttribute('data-title') ?? '';
                        const itemAuthor = bookItem.getAttribute('data-author') ?? '';

                        const matchSearch = itemTitle.includes(searchTerm) || itemAuthor.includes(searchTerm);


                        const matchGenre = genre === "" || itemGenre === genre;

                        const matchPublisher = publisher === "" || itemPublisher === publisher;

                        if (matchSearch && matchGenre && matchPublisher) {
                            bookItemCol.style.display = "";
                        } else {
                            bookItemCol.style.display = "none";
                        }
                    });
                }
                searchInput.addEventListener('input', filterBooks);

                searchButton.addEventListener('click', filterBooks);

                searchInput.addEventListener('keypress', function(event) {
                    if (event.key === 'Enter') {

                        event.preventDefault();
                        filterBooks();
                    }
                });


                genreFilter.addEventListener('change', filterBooks);

                publisherFilter.addEventListener('change', filterBooks);

                filterBooks();
            });
        </script>
</body>

</html>