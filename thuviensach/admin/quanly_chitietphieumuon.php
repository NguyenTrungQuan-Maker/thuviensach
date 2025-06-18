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
$chitietMuonList = [];

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['sophieumuon']) && isset($_GET['masosach'])) {
    $soPhieuMuonToDelete = trim($_GET['sophieumuon']);
    $maSoSachToDelete = trim($_GET['masosach']);

    if (!empty($soPhieuMuonToDelete) && !empty($maSoSachToDelete)) {

        $conn->begin_transaction();

        try {

            $sql_delete = "DELETE FROM chitietphieumuon WHERE SoPhieuMuon = ? AND MaSoSach = ?";
            $stmt_delete = $conn->prepare($sql_delete);

            if ($stmt_delete === false) {
                throw new Exception("Lỗi prepare statement xóa chi tiết: " . $conn->error);
            }


            $stmt_delete->bind_param("ss", $soPhieuMuonToDelete, $maSoSachToDelete);

            if ($stmt_delete->execute()) {

                if ($stmt_delete->affected_rows > 0) {
                    $conn->commit();
                    $_SESSION['message'] = "<div class='alert alert-success'>Đã xóa chi tiết phiếu mượn thành công.</div>";
                } else {

                    $conn->rollback();
                    $_SESSION['message'] = "<div class='alert alert-warning'>Không tìm thấy chi tiết phiếu mượn để xóa hoặc chi tiết này không tồn tại.</div>";
                }
            } else {
                throw new Exception("Lỗi execute statement xóa chi tiết: " . $stmt_delete->error);
            }

            $stmt_delete->close();
        } catch (Exception $e) {

            if (isset($conn) && $conn instanceof mysqli) {
                $conn->rollback();
            }

            $_SESSION['message'] = "<div class='alert alert-danger'>Lỗi khi xóa chi tiết phiếu mượn: " . htmlspecialchars($e->getMessage()) . "</div>";

            error_log("Lỗi xóa chi tiết phiếu mượn (Admin): SoPhieu=" . ($soPhieuMuonToDelete ?? 'N/A') . ", Maso=" . ($maSoSachToDelete ?? 'N/A') . " - Lỗi: " . $e->getMessage());
        }
    } else {
        $_SESSION['message'] = "<div class='alert alert-warning'>Thiếu thông tin chi tiết phiếu mượn để xóa.</div>";
    }

    header("Location: quanly_chitietphieumuon.php");
    exit();
}

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $sql = "SELECT
                ctpm.SoPhieuMuon,
                ctpm.MaSoSach,
                qs.TenSach,         
                ms.MaSoDG,
                nd.HoTen AS TenDocGia, 
                ms.NgayMuon,
                ctpm.HanTra,
                ctpm.TrangThai
            FROM
                chitietphieumuon ctpm
            JOIN
                quyensach qs ON ctpm.MaSoSach = qs.MaSoSach
            JOIN
                muonsach ms ON ctpm.SoPhieuMuon = ms.SoPhieuMuon
            JOIN
                nguoidung nd ON ms.MaSoDG = nd.IDNguoiDung
            ORDER BY
                ctpm.SoPhieuMuon DESC, ctpm.MaSoSach ASC"; // Sắp xếp để dễ theo dõi

    $result = $conn->query($sql);

    if ($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $chitietMuonList[] = $row;
            }
        }
        $result->free(); 
    } else {

        if (empty($message) || strpos($message, 'Lỗi kết nối CSDL') !== false) {
            $message = "<div class='alert alert-danger'>Lỗi khi lấy danh sách Chi tiết phiếu mượn: " . $conn->error . "</div>";
        }
    }
} else {

    if (empty($message)) {
        $message = "<div class='alert alert-danger'>Lỗi: Kết nối CSDL không thành công hoặc không phải đối tượng MySQLi. Vui lòng kiểm tra file db.php</div>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Chi tiết Phiếu mượn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../admin/css/admin.css">
    <style>
        .action-buttons a {
            margin-right: 5px;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: bold;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
        }

        .badge-info {
            background-color: #17a2b8;
            color: white;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <?php include 'index.php'; // Giả định bạn có file sidebar riêng 
            ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Quản lý Chi tiết Phiếu mượn</h1>
                </div>

                <?php echo $message; ?>

                <div class="card">
                    <div class="card-header">
                        Danh sách Chi tiết Phiếu mượn
                    </div>
                    <div class="card-body">
                        <?php if (empty($message) || strpos($message, 'Lỗi kết nối CSDL') === false): // Chỉ hiển thị bảng nếu không có lỗi kết nối 
                        ?>
                            <?php if (!empty($chitietMuonList)): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Số Phiếu Mượn</th>
                                                <th>Mã Số Sách</th>
                                                <th>Tên Sách</th>
                                                <th>Mã Số Độc giả</th>
                                                <th>Tên Độc giả</th>
                                                <th>Ngày Mượn</th>
                                                <th>Hạn Trả</th>
                                                <th>Trạng Thái</th>
                                                <th>Hành động</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($chitietMuonList as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['SoPhieuMuon'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($item['MaSoSach'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($item['TenSach'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($item['MaSoDG'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($item['TenDocGia'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($item['NgayMuon'] ?? 'N/A') ?></td>
                                                    <td>
                                                        <?php
                                                        $hanTraFormatted = htmlspecialchars($item['HanTra'] ?? 'N/A');
                                                        $classColor = '';

                                                        $currentDate = new DateTime();
                                                        try {
                                                            $hanTraDate = new DateTime($item['HanTra']);
                                                            if ($hanTraDate < $currentDate && $item['TrangThai'] === 'Đang mượn') {
                                                                $classColor = 'badge-danger'; // Quá hạn
                                                            } elseif ($hanTraDate >= $currentDate && $item['TrangThai'] === 'Đang mượn') {
                                                                $classColor = 'badge-success'; // Còn hạn
                                                            } elseif ($item['TrangThai'] === 'Đã trả') {
                                                                $classColor = 'badge-info'; // Đã trả
                                                            } elseif ($item['TrangThai'] === 'Chờ duyệt mượn') {
                                                                $classColor = 'badge-warning'; // Chờ duyệt mượn
                                                            }
                                                        } catch (Exception $e) {

                                                            $hanTraFormatted = 'Ngày không hợp lệ';
                                                            $classColor = 'badge-secondary';
                                                            error_log("Lỗi định dạng ngày HanTra: " . ($item['HanTra'] ?? 'NULL') . " - " . $e->getMessage());
                                                        }
                                                        ?>
                                                        <span class="badge <?= $classColor ?>"><?= $hanTraFormatted ?></span>
                                                    </td>
                                                    <td><span class="badge
                                                     <?php
                                                        switch ($item['TrangThai']) {
                                                            case 'Đang mượn':
                                                                echo 'badge-success';
                                                                break;
                                                            case 'Đã trả':
                                                                echo 'badge-info';
                                                                break;
                                                            case 'Chờ duyệt mượn':
                                                                echo 'badge-warning';
                                                                break;
                                                            case 'Chờ duyệt trả':
                                                                echo 'badge-warning';
                                                                break;
                                                            default:
                                                                echo 'badge-secondary';
                                                        }
                                                        ?>">
                                                            <?= htmlspecialchars($item['TrangThai'] ?? 'N/A') ?>
                                                        </span>
                                                    </td>
                                                    <td class="action-buttons">
                                                        <a href="quanly_chitietphieumuon.php?action=delete&sophieumuon=<?= urlencode($item['SoPhieuMuon'] ?? '') ?>&masosach=<?= urlencode($item['MaSoSach'] ?? '') ?>"
                                                            class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Bạn có chắc chắn muốn xóa chi tiết phiếu mượn này không? (Số phiếu: <?= htmlspecialchars($item['SoPhieuMuon'] ?? '') ?>, Mã sách: <?= htmlspecialchars($item['MaSoSach'] ?? '') ?>)');">
                                                            <i class="fas fa-trash-alt"></i> Xóa
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>Chưa có chi tiết phiếu mượn nào trong cơ sở dữ liệu.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../admin/js/admin.js"></script>
    <?php
    if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
        $conn->close();
    }
    ?>
</body>

</html>