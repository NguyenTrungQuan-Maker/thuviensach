<?php


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';
$message = ''; 

$redirect_url = 'index.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['ma_so_dg']) || empty($_SESSION['ma_so_dg'])) {
    
    $_SESSION['message'] = "<div class='alert alert-warning'>Vui lòng đăng nhập bằng tài khoản độc giả để đăng ký mượn sách.</div>";
    if (isset($_GET['sach_id'])) {
         $redirect_url = 'dangnhap.php?redirect=' . urlencode('chitietsach.php?id=' . urlencode($_GET['sach_id']));
    } else {
         $redirect_url = 'dangnhap.php';
    }
    header("location: " . $redirect_url);
    exit();
}


$loggedInUserId = $_SESSION['ma_so_dg'];


if (!isset($_GET['sach_id']) || empty($_GET['sach_id'])) {

    $_SESSION['message'] = "<div class='alert alert-warning'>Không có mã sách được chỉ định để đăng ký mượn.</div>";
    header("Location: danhmucsach.php"); 
    exit();
}

$maSoSach = trim($_GET['sach_id']); 

$redirect_url = 'chitietsach.php?id=' . urlencode($maSoSach);


try {
    if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
         throw new Exception("Lỗi kết nối CSDL: " . ($conn->connect_error ?? 'Biến kết nối $conn không tồn tại hoặc không phải đối tượng MySQLi.'));
    }


    $conn->begin_transaction();
    $sql_check_book = "SELECT MaSoSach, SoLuong FROM quyensach WHERE MaSoSach = ? LIMIT 1";
    $stmt_check = $conn->prepare($sql_check_book);
    if ($stmt_check === false) {
         throw new Exception("Lỗi chuẩn bị truy vấn kiểm tra sách: " . $conn->error);
    }
    $stmt_check->bind_param("s", $maSoSach);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows === 0) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Sách bạn yêu cầu không tồn tại.</div>";
        $conn->rollback(); 
        header("Location: " . $redirect_url);
        exit();
    }

    $book_info = $result_check->fetch_assoc();
    $stmt_check->close();

    if ($book_info['SoLuong'] <= 0) {
        $_SESSION['message'] = "<div class='alert alert-warning'>Sách này hiện đã hết và không thể đăng ký mượn.</div>";
        $conn->rollback(); // Hoàn tác transaction
        header("Location: " . $redirect_url);
        exit();
    }

    $sql_check_existing = "SELECT COUNT(c.SoPhieuMuon) AS count
                           FROM chitietphieumuon c
                           JOIN muonsach m ON c.SoPhieuMuon = m.SoPhieuMuon
                           LEFT JOIN trasach t ON c.SoPhieuMuon = t.SoPhieuMuon AND c.MaSoSach = t.MaSoSach -- Kiểm tra bản ghi trả sách
                           WHERE m.MaSoDG = ? AND c.MaSoSach = ?
                           AND (
                               c.TrangThai = 'Chờ duyệt mượn' 
                               OR (c.TrangThai = 'Đang mượn' AND t.SoPhieuMuon IS NULL) 
                           )";

    $stmt_check_existing = $conn->prepare($sql_check_existing);
    if ($stmt_check_existing === false) {
        throw new Exception("Lỗi chuẩn bị truy vấn kiểm tra yêu cầu/sách đã mượn: " . $conn->error);
    }

    $stmt_check_existing->bind_param("is", $loggedInUserId, $maSoSach);
    $stmt_check_existing->execute();
    $result_check_existing = $stmt_check_existing->get_result();
    $existing_count = $result_check_existing->fetch_assoc()['count'];
    $stmt_check_existing->close();

    if ($existing_count > 0) {
         $_SESSION['message'] = "<div class='alert alert-info'>Bạn đã có yêu cầu đăng ký mượn sách này đang chờ duyệt hoặc đã mượn và chưa trả.</div>";
         $conn->rollback();
         header("Location: " . $redirect_url);
         exit();
    }

    $shortUserId = substr((string)$loggedInUserId, 0, 10); 

    $datePart = date('Ymd');

    $randomPart = mt_rand(1000, 9999);

    $soPhieuMuon = 'PM_' . $shortUserId . '_' . $datePart . '_' . $randomPart;

    $soPhieuMuon = substr($soPhieuMuon, 0, 50);

    $ngayMuon = date('Y-m-d'); 
    $sql_insert_muonsach = "INSERT INTO muonsach (SoPhieuMuon, MaSoDG, NgayMuon) VALUES (?, ?, ?)";
    $stmt_muonsach = $conn->prepare($sql_insert_muonsach);
     if ($stmt_muonsach === false) {
         throw new Exception("Lỗi chuẩn bị truy vấn thêm muonsach: " . $conn->error);
     }
    $stmt_muonsach->bind_param("sis", $soPhieuMuon, $loggedInUserId, $ngayMuon);

    if (!$stmt_muonsach->execute()) {
         $error_msg = "Lỗi khi tạo phiếu mượn tạm thời: " . $stmt_muonsach->error;
         if ($conn->errno == 1062) { 
     
             $error_msg = "Lỗi hệ thống khi tạo phiếu mượn (ID trùng lặp). Vui lòng thử lại.";
         } elseif ($conn->errno == 1452) { 
              $error_msg = "Lỗi hệ thống (Mã độc giả không hợp lệ). Vui lòng kiểm tra lại thông tin đăng nhập hoặc liên hệ quản trị viên.";
         }
         $_SESSION['message'] = "<div class='alert alert-danger'>" . $error_msg . "</div>";
         $stmt_muonsach->close();
         $conn->rollback(); 
         header("Location: " . $redirect_url);
         exit();
    }
    $stmt_muonsach->close();

    $hanTra = date('Y-m-d', strtotime($ngayMuon . ' + 14 days')); 
    $trangThai = 'Chờ duyệt mượn';

    $sql_insert_chitiet = "INSERT INTO chitietphieumuon (SoPhieuMuon, MaSoSach, HanTra, TrangThai) VALUES (?, ?, ?, ?)";
    $stmt_chitiet = $conn->prepare($sql_insert_chitiet);
    if ($stmt_chitiet === false) {
         throw new Exception("Lỗi chuẩn bị truy vấn thêm chi tiết phiếu mượn: " . $conn->error);
    }
    $stmt_chitiet->bind_param("ssss", $soPhieuMuon, $maSoSach, $hanTra, $trangThai);

    if (!$stmt_chitiet->execute()) {
         $error_msg = "Lỗi khi gửi yêu cầu chi tiết mượn: " . $stmt_chitiet->error;
         if ($conn->errno == 1062) { 
              $error_msg = "Yêu cầu đăng ký mượn sách này đã tồn tại.";
         } elseif ($conn->errno == 1452) { 
              $error_msg = "Lỗi hệ thống (Phiếu mượn tạm thời hoặc mã sách không hợp lệ).";
         }
         $_SESSION['message'] = "<div class='alert alert-danger'>" . $error_msg . "</div>";
         $stmt_chitiet->close();
         $conn->rollback(); 
         header("Location: " . $redirect_url);
         exit();
    }
    $stmt_chitiet->close();

    $conn->commit();
    $_SESSION['message'] = "<div class='alert alert-success'>Yêu cầu đăng ký mượn sách của bạn đã được gửi thành công và đang chờ admin duyệt.</div>";


} catch (Exception $e) {
    if (isset($conn) && $conn instanceof mysqli) {
         $conn->rollback(); 
    }
    if (!isset($_SESSION['message'])) {
         $_SESSION['message'] = "<div class='alert alert-danger'>Đã xảy ra lỗi trong quá trình gửi yêu cầu đăng ký mượn: " . $e->getMessage() . "</div>";
    }

} finally {
    if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
         $conn->close();
    }
}

header("Location: " . $redirect_url);
exit(); 

?>
