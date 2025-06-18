<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {

    $_SESSION['message'] = "<div class='alert alert-danger'>Bạn không có quyền truy cập trang này. Vui lòng đăng nhập với tài khoản quản trị viên.</div>";
  
    header("Location: dangnhap_admin.php"); 
    exit(); 
}

include '../db.php'; 

$message = ''; 
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); 
}
$sql_yeu_cau = "SELECT
                    ctpm.SoPhieuMuon,
                    ctpm.MaSoSach,
                    ctpm.HanTra,
                    ctpm.TrangThai,
                    ms.MaSoDG,
                    ms.NgayMuon,
                    nd.HoTen AS TenDocGia,
                    qs.TenSach AS TenSachMuon
                FROM
                    chitietphieumuon ctpm
                JOIN
                    muonsach ms ON ctpm.SoPhieuMuon = ms.SoPhieuMuon
                JOIN
                    nguoidung nd ON ms.MaSoDG = nd.IDNguoiDung
                JOIN
                    quyensach qs ON ctpm.MaSoSach = qs.MaSoSach
                WHERE
                    ctpm.TrangThai = 'Chờ duyệt mượn'
                ORDER BY
                    ms.NgayMuon ASC"; // Sắp xếp theo ngày yêu cầu mượn

$result_yeu_cau = $conn->query($sql_yeu_cau);

$yeu_cau_muon_list = [];
if ($result_yeu_cau && $result_yeu_cau->num_rows > 0) {
    while ($row = $result_yeu_cau->fetch_assoc()) {
        $yeu_cau_muon_list[] = $row;
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
    <title>Quản lý Yêu cầu Mượn Sách - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../admin/css/admin.css">
    <style>
        
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'index.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Quản lý Yêu cầu Mượn Sách</h1>
                </div>

                <?php
                // Hiển thị thông báo
                if (!empty($message)) {
                    echo $message;
                }
                ?>

                <div class="card mt-3">
                    <div class="card-header">
                        Danh sách Yêu cầu Mượn đang chờ duyệt
                    </div>
                    <div class="card-body">
                        <?php if (!empty($yeu_cau_muon_list)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>Số Phiếu Mượn</th>
                                            <th>Độc giả</th>
                                            <th>Mã Độc giả</th>
                                            <th>Ngày Yêu cầu</th>
                                            <th>Mã Sách</th>
                                            <th>Tên Sách</th>
                                            <th>Hạn trả dự kiến</th>
                                            <th>Trạng thái</th>
                                            <th>Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $stt = 1; ?>
                                        <?php foreach ($yeu_cau_muon_list as $yeu_cau): ?>
                                            <tr>
                                                <td><?= $stt++ ?></td>
                                                <td><?= htmlspecialchars($yeu_cau['SoPhieuMuon']) ?></td>
                                                <td><?= htmlspecialchars($yeu_cau['TenDocGia']) ?></td>
                                                <td><?= htmlspecialchars($yeu_cau['MaSoDG']) ?></td>
                                                <td><?= htmlspecialchars($yeu_cau['NgayMuon']) ?></td>
                                                <td><?= htmlspecialchars($yeu_cau['MaSoSach']) ?></td>
                                                <td><?= htmlspecialchars($yeu_cau['TenSachMuon']) ?></td>
                                                <td><?= htmlspecialchars($yeu_cau['HanTra']) ?></td>
                                                <td><span class="badge bg-warning"><?= htmlspecialchars($yeu_cau['TrangThai']) ?></span></td>
                                                <td class="action-buttons">
                                                    <a href="xu_ly_duyet_muon.php?sophieumuon=<?= urlencode($yeu_cau['SoPhieuMuon']) ?>&masosach=<?= urlencode($yeu_cau['MaSoSach']) ?>&action=approve" class="btn btn-sm btn-success" onclick="return confirm('Bạn có chắc chắn muốn duyệt yêu cầu mượn sách này không?');"><i class="fas fa-check"></i> Duyệt</a>

                                                    <a href="xu_ly_duyet_muon.php?sophieumuon=<?= urlencode($yeu_cau['SoPhieuMuon']) ?>&masosach=<?= urlencode($yeu_cau['MaSoSach']) ?>&action=reject" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn từ chối yêu cầu mượn sách này không?');"><i class="fas fa-times"></i> Từ chối</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>Không có yêu cầu mượn sách nào đang chờ duyệt.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../admin/js/admin.js"></script>
</body>
</html>
