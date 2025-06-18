<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['message'] = "<div class='alert alert-danger'>Bạn không có quyền truy cập trang quản lý yêu cầu trả sách. Vui lòng đăng nhập với tài khoản quản trị viên.</div>";
    header("Location: dangnhap_admin.php"); // <-- Cần thay thế nếu đường dẫn khác
    exit();
}



include '../db.php'; 

$message = ''; 
$returnRequests = [];

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); 
}


if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $message .= "<div class='alert alert-danger'>Lỗi kết nối CSDL: " . ($conn->connect_error ?? 'Biến kết nối $conn không tồn tại hoặc không phải đối tượng MySQLi.') . "</div>";
    $error_db_connection = true;
} else {
     $error_db_connection = false;
    $sql = "SELECT
                ctpm.SoPhieuMuon,
                ctpm.MaSoSach,
                ctpm.HanTra,
                ctpm.TrangThai,
                ms.MaSoDG,
                ms.NgayMuon, 
                nd.HoTen AS TenDocGia, 
                qs.TenSach AS TenSachTra, 
                qs.TacGia
            FROM
                chitietphieumuon ctpm
            JOIN
                muonsach ms ON ctpm.SoPhieuMuon = ms.SoPhieuMuon
            JOIN
                nguoidung nd ON ms.MaSoDG = nd.IDNguoiDung 
            JOIN
                quyensach qs ON ctpm.MaSoSach = qs.MaSoSach
            WHERE
                ctpm.TrangThai = 'Chờ duyệt trả' 
            ORDER BY
                ms.NgayMuon ASC"; 

    $result = $conn->query($sql);

    if ($result) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $returnRequests[] = $row;
            }
        }
    } else {
        $message .= "<div class='alert alert-danger'>Lỗi khi lấy danh sách yêu cầu trả sách: " . $conn->error . "</div>";
         // Log lỗi chi tiết ở server
        error_log("Lỗi truy vấn danh sach yeu cau tra (Admin): " . $conn->error);
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
    <title>Quản lý Yêu cầu Trả Sách - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../admin/css/admin.css"> <style>
        body {
             padding-top: 0; 
             background-color: #f4f7f6;
             overflow-x: hidden;
           }
           main {
             margin-left: 250px; 
             padding-top: 20px;
             padding-right: 20px; 
             padding-bottom: 20px; 
           }
           @media (max-width: 768px) {
             main {
                 margin-left: 0;
             }
           }
         .container-fluid {
          
         }
         .table th, .table td {
             vertical-align: middle; 
         }
         .action-buttons a {
             margin-right: 5px; 
         }
         .alert {
             margin-top: 15px;
             margin-bottom: 15px; 
         }
         .card {
            margin-top: 20px;
         }
       
         .badge-warning {
             background-color: #ffc107; 
             color: #212529; 
         }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'index.php'; // <-- Include file index.php để có sidebar và navbar ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Quản lý Yêu cầu Trả Sách</h1>
                </div>

                <?php
                // Hiển thị thông báo
                if (!empty($message)) {
                    echo $message;
                }
                ?>

                <div class="card mt-3">
                    <div class="card-header">
                        Danh sách Yêu cầu Trả Sách đang chờ duyệt
                    </div>
                    <div class="card-body">
                        <?php if (!empty($returnRequests)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>Số Phiếu Mượn</th>
                                            <th>Mã Độc giả</th>
                                            <th>Tên Độc giả</th>
                                            <th>Ngày Mượn</th> <th>Mã Sách</th>
                                            <th>Tên Sách</th> <th>Hạn trả</th>
                                            <th>Trạng thái</th>
                                            <th>Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $stt = 1; ?>
                                        <?php foreach ($returnRequests as $request): ?>
                                            <tr>
                                                <td><?= $stt++ ?></td>
                                                <td><?= htmlspecialchars($request['SoPhieuMuon'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($request['MaSoDG'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($request['TenDocGia'] ?? 'N/A') ?></td> <td><?= htmlspecialchars($request['NgayMuon'] ?? 'N/A') ?></td> <td><?= htmlspecialchars($request['MaSoSach'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($request['TenSachTra'] ?? 'N/A') ?></td> <td><?= htmlspecialchars((new DateTime($request['HanTra'] ?? 'now'))->format('d/m/Y')) ?? 'N/A' ?></td>
                                                <td><span class="badge badge-warning"><?= htmlspecialchars($request['TrangThai'] ?? 'N/A') ?></span></td> <td class="action-buttons">
                                                    <a href="xu_ly_ghi_nhan_tra.php?sophieumuon=<?= urlencode($request['SoPhieuMuon'] ?? '') ?>&masosach=<?= urlencode($request['MaSoSach'] ?? '') ?>"
                                                       class="btn btn-success btn-sm"
                                                       onclick="return confirm('Bạn có chắc chắn muốn ghi nhận độc giả đã trả sách này không?');">
                                                        <i class="fas fa-check"></i> Ghi nhận trả
                                                    </a>
                                                    </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>Không có yêu cầu trả sách nào đang chờ duyệt.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../admin/js/admin.js"></script> </body>
</html>
