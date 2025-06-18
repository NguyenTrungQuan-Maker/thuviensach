<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Nhà xuất bản</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../admin/css/admin.css">
     <style>
        
         body {
            padding-top: 0; 
            overflow-x: hidden;
            background-color: #f4f7f6; 
        }
         main {
            margin-left: 250px;
            padding-top: 20px; 
         }
         @media (max-width: 768px) {
            main {
                margin-left: 0;
            }
         }
          .card-header {
            font-weight: bold;
        }
         
        .table td:last-child a {
            margin-right: 5px;
        }
    </style>

</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include '../admin/index.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $editMode ? 'Sửa Nhà xuất bản' : 'Quản lý Nhà xuất bản' ?></h1>
            </div>

            <?php echo $message; ?>

            <?php if ($editMode): // Hiển thị Form Sửa khi ở chế độ sửa ?>
                <div class="card mb-4">
                    <div class="card-header">
                        Sửa thông tin Nhà xuất bản
                    </div>
                    <div class="card-body">
                         <form action="quanly_nxb.php" method="POST">
                            <input type="hidden" name="update_nhaxuatban" value="1">
                            <div class="mb-3">
                                <label for="ma_so_nxb_edit" class="form-label">Mã số NXB:</label>
                                <input type="text" class="form-control" id="ma_so_nxb_edit" name="ma_so_nxb" value="<?= htmlspecialchars($currentNXB['MaSoNXB'] ?? '') ?>" readonly required>
                            </div>
                            <div class="mb-3">
                                <label for="ten_nxb_edit" class="form-label">Tên NXB:</label>
                                <input type="text" class="form-control" id="ten_nxb_edit" name="ten_nxb" value="<?= htmlspecialchars($currentNXB['TenNXB'] ?? '') ?>" maxlength="100" required>
                            </div>
                             <div class="mb-3">
                                <label for="dia_chi_nxb_edit" class="form-label">Địa chỉ NXB:</label>
                                <input type="text" class="form-control" id="dia_chi_nxb_edit" name="dia_chi_nxb" value="<?= htmlspecialchars($currentNXB['DiaChiNXB'] ?? '') ?>" maxlength="150" required>
                            </div>
                             <div class="mb-3">
                                <label for="website_nxb_edit" class="form-label">Website NXB:</label>
                                <input type="text" class="form-control" id="website_nxb_edit" name="website_nxb" value="<?= htmlspecialchars($currentNXB['WebsiteNXB'] ?? '') ?>" maxlength="100" required>
                            </div>
                             <div class="mb-3">
                                <label for="thong_tin_khac_nxb_edit" class="form-label">Thông tin khác:</label>
                                <textarea class="form-control" id="thong_tin_khac_nxb_edit" name="thong_tin_khac_nxb" maxlength="200" required><?= htmlspecialchars($currentNXB['ThongTinKhacNXB'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Cập nhật</button>
                            <a href="quanly_nxb.php" class="btn btn-secondary">Hủy</a> </form>
                    </div>
                </div>
            <?php else: // Hiển thị Form Thêm mới và Danh sách khi không ở chế độ sửa ?>
                <div class="card mb-4">
                    <div class="card-header">
                        Thêm Nhà xuất bản mới
                    </div>
                    <div class="card-body">
                         <form action="" method="POST">
                            <input type="hidden" name="add_nhaxuatban" value="1">
                            <div class="mb-3">
                                <label for="ma_so_nxb" class="form-label">Mã số NXB:</label>
                                <input type="text" class="form-control" id="ma_so_nxb" name="ma_so_nxb" maxlength="50" required>
                            </div>
                            <div class="mb-3">
                                <label for="ten_nxb" class="form-label">Tên NXB:</label>
                                <input type="text" class="form-control" id="ten_nxb" name="ten_nxb" maxlength="100" required>
                            </div>
                             <div class="mb-3">
                                <label for="dia_chi_nxb" class="form-label">Địa chỉ NXB:</label>
                                <input type="text" class="form-control" id="dia_chi_nxb" name="dia_chi_nxb" maxlength="150" required>
                            </div>
                             <div class="mb-3">
                                <label for="website_nxb" class="form-label">Website NXB:</label>
                                <input type="text" class="form-control" id="website_nxb" name="website_nxb" maxlength="100" required>
                            </div>
                             <div class="mb-3">
                                <label for="thong_tin_khac_nxb" class="form-label">Thông tin khác:</label>
                                <textarea class="form-control" id="thong_tin_khac_nxb" name="thong_tin_khac_nxb" maxlength="200" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm mới</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        Danh sách Nhà xuất bản
                    </div>
                    <div class="card-body">
                        <?php if (count($nhaxuatbanList) > 0): ?>
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Mã số NXB</th>
                                        <th>Tên NXB</th>
                                        <th>Địa chỉ NXB</th>
                                        <th>Website</th>
                                        <th>Thông tin khác</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($nhaxuatbanList as $nxb): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($nxb['MaSoNXB']) ?></td>
                                            <td><?= htmlspecialchars($nxb['TenNXB']) ?></td>
                                            <td><?= htmlspecialchars($nxb['DiaChiNXB']) ?></td>
                                            <td><?= htmlspecialchars($nxb['WebsiteNXB']) ?></td>
                                            <td><?= htmlspecialchars($nxb['ThongTinKhacNXB']) ?></td>
                                            <td>
                                                <a href="?action=edit&id=<?= urlencode($nxb['MaSoNXB']) ?>" class="btn btn-sm btn-warning me-2"><i class="fas fa-edit"></i> Sửa</a>
                                                <a href="?action=delete&id=<?= urlencode($nxb['MaSoNXB']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa Nhà xuất bản \'<?= htmlspecialchars($nxb['TenNXB']) ?>\' (Mã: <?= htmlspecialchars($nxb['MaSoNXB']) ?>) không?');"><i class="fas fa-trash-alt"></i> Xóa</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>Chưa có nhà xuất bản nào trong cơ sở dữ liệu.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php

if ($conn instanceof mysqli) {

}