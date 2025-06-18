<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sách</title>
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
        
        .table th, .table td {
            
        }
         .book-cover-img {
             max-width: 80px; 
             height: auto;
             display: block; 
             margin: 0 auto;
         }
    </style>

</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include '../admin/index.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $editMode ? 'Sửa Quyển sách' : 'Quản lý Quyển sách' ?></h1>
            </div>

            <?php echo $message; ?>

            <?php if ($editMode): // Hiển thị Form Sửa khi ở chế độ sửa ?>
                <div class="card mb-4">
                    <div class="card-header">
                        Sửa thông tin Quyển sách
                    </div>
                    <div class="card-body">
                         <form action="quanly_quyensach.php" method="POST" enctype="multipart/form-data"> <input type="hidden" name="update_sach" value="1">
                            <input type="hidden" name="ma_so_sach" value="<?= htmlspecialchars($currentSach['MaSoSach'] ?? '') ?>">
                             <input type="hidden" name="old_anh_bia" value="<?= htmlspecialchars($currentSach['AnhBia'] ?? '') ?>">

                            <div class="mb-3">
                                <label for="ma_so_sach_edit" class="form-label">Mã số Sách:</label>
                                <input type="text" class="form-control" id="ma_so_sach_edit" value="<?= htmlspecialchars($currentSach['MaSoSach'] ?? '') ?>" readonly required>
                            </div>
                            <div class="mb-3">
                                <label for="ten_sach_edit" class="form-label">Tên Sách:</label>
                                <input type="text" class="form-control" id="ten_sach_edit" name="ten_sach" value="<?= htmlspecialchars($currentSach['TenSach'] ?? '') ?>" maxlength="100" required>
                            </div>
                            <div class="mb-3">
                                <label for="tac_gia_edit" class="form-label">Tác giả:</label>
                                <input type="text" class="form-control" id="tac_gia_edit" name="tac_gia" value="<?= htmlspecialchars($currentSach['TacGia'] ?? '') ?>" maxlength="100" required>
                            </div>
                            <div class="mb-3">
                                <label for="ma_so_nxb_edit" class="form-label">Nhà xuất bản:</label>
                                <select class="form-control" id="ma_so_nxb_edit" name="ma_so_nxb" required>
                                    <option value="">-- Chọn Nhà xuất bản --</option>
                                    <?php foreach ($nxbList as $nxb): ?>
                                        <option value="<?= htmlspecialchars($nxb['MaSoNXB']) ?>" <?= (isset($currentSach['MaSoNXB']) && $currentSach['MaSoNXB'] === $nxb['MaSoNXB']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($nxb['TenNXB']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                             <div class="mb-3">
                                <label for="ma_loai_sach_edit" class="form-label">Loại sách:</label>
                                <select class="form-control" id="ma_loai_sach_edit" name="ma_loai_sach" required>
                                     <option value="">-- Chọn Loại sách --</option>
                                    <?php foreach ($loaisachList as $loaisach): ?>
                                        <option value="<?= htmlspecialchars($loaisach['MaLoaiSach']) ?>" <?= (isset($currentSach['MaLoaiSach']) && $currentSach['MaLoaiSach'] === $loaisach['MaLoaiSach']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($loaisach['LoaiSach']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="nam_xb_edit" class="form-label">Năm XB:</label>
                                <input type="number" class="form-control" id="nam_xb_edit" name="nam_xb" value="<?= htmlspecialchars($currentSach['NamXB'] ?? '') ?>" required min="0">
                            </div>
                             <div class="mb-3">
                                <label for="lan_xb_edit" class="form-label">Lần XB:</label>
                                <input type="number" class="form-control" id="lan_xb_edit" name="lan_xb" value="<?= htmlspecialchars($currentSach['LanXB'] ?? '') ?>" required min="0">
                            </div>
                             <div class="mb-3">
                                <label for="so_luong_edit" class="form-label">Số lượng:</label>
                                <input type="number" class="form-control" id="so_luong_edit" name="so_luong" value="<?= htmlspecialchars($currentSach['SoLuong'] ?? '') ?>" required min="0">
                            </div>
                             <div class="mb-3">
                                <label for="noi_dung_tom_luoc_edit" class="form-label">Nội dung tóm lược:</label>
                                <textarea class="form-control" id="noi_dung_tom_luoc_edit" name="noi_dung_tom_luoc" maxlength="200" required><?= htmlspecialchars($currentSach['NoiDungTomLuoc'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="anh_bia_edit" class="form-label">Ảnh bìa:</label>
                                <input type="file" class="form-control" id="anh_bia_edit" name="anh_bia" accept="image/*"> <?php if (!empty($currentSach['AnhBia'])): ?>
                                    <div class="mt-2">
                                         Ảnh hiện tại: <br>
                                        <img src="<?= htmlspecialchars('../assets/uploads/book_covers/' . $currentSach['AnhBia']) ?>" alt="Ảnh bìa" class="book-cover-img"> <div class="form-check mt-1">
                                             <input class="form-check-input" type="checkbox" name="remove_anh_bia" id="remove_anh_bia" value="1"> <label class="form-check-label" for="remove_anh_bia">Xóa ảnh bìa hiện tại</label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Cập nhật</button>
                            <a href="quanly_quyensach.php" class="btn btn-secondary">Hủy</a> </form>
                    </div>
                </div>
            <?php else: // Hiển thị Form Thêm mới và Danh sách khi không ở chế độ sửa ?>
                <div class="card mb-4">
                    <div class="card-header">
                        Thêm Quyển sách mới
                    </div>
                    <div class="card-body">
                         <form action="" method="POST" enctype="multipart/form-data"> <input type="hidden" name="add_sach" value="1">
                              <div class="mb-3">
                                <label for="ma_so_sach" class="form-label">Mã số Sách:</label>
                                <input type="text" class="form-control" id="ma_so_sach" name="ma_so_sach" maxlength="50" required>
                            </div>
                            <div class="mb-3">
                                <label for="ten_sach" class="form-label">Tên Sách:</label>
                                <input type="text" class="form-control" id="ten_sach" name="ten_sach" maxlength="100" required>
                            </div>
                            <div class="mb-3">
                                <label for="tac_gia" class="form-label">Tác giả:</label>
                                <input type="text" class="form-control" id="tac_gia" name="tac_gia" maxlength="100" required>
                            </div>
                            <div class="mb-3">
                                <label for="ma_so_nxb" class="form-label">Nhà xuất bản:</label>
                                <select class="form-control" id="ma_so_nxb" name="ma_so_nxb" required>
                                    <option value="">-- Chọn Nhà xuất bản --</option>
                                    <?php foreach ($nxbList as $nxb): ?>
                                        <option value="<?= htmlspecialchars($nxb['MaSoNXB']) ?>">
                                            <?= htmlspecialchars($nxb['TenNXB']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                             <div class="mb-3">
                                <label for="ma_loai_sach" class="form-label">Loại sách:</label>
                                <select class="form-control" id="ma_loai_sach" name="ma_loai_sach" required>
                                     <option value="">-- Chọn Loại sách --</option>
                                    <?php foreach ($loaisachList as $loaisach): ?>
                                        <option value="<?= htmlspecialchars($loaisach['MaLoaiSach']) ?>">
                                            <?= htmlspecialchars($loaisach['LoaiSach']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="nam_xb" class="form-label">Năm XB:</label>
                                <input type="number" class="form-control" id="nam_xb" name="nam_xb" required min="0">
                            </div>
                             <div class="mb-3">
                                <label for="lan_xb" class="form-label">Lần XB:</label>
                                <input type="number" class="form-control" id="lan_xb" name="lan_xb" required min="0">
                            </div>
                             <div class="mb-3">
                                <label for="so_luong" class="form-label">Số lượng:</label>
                                <input type="number" class="form-control" id="so_luong" name="so_luong" required min="0">
                            </div>
                             <div class="mb-3">
                                <label for="noi_dung_tom_luoc" class="form-label">Nội dung tóm lược:</label>
                                <textarea class="form-control" id="noi_dung_tom_luoc" name="noi_dung_tom_luoc" maxlength="200" required></textarea>
                            </div>
                             <div class="mb-3">
                                <label for="anh_bia" class="form-label">Ảnh bìa:</label>
                                <input type="file" class="form-control" id="anh_bia" name="anh_bia" accept="image/*"> </div>

                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm mới</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        Danh sách Quyển sách
                    </div>
                    <div class="card-body">
                        <?php if (count($sachList) > 0): ?>
                             <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                             <th>Ảnh bìa</th> <th>Mã Sách</th>
                                            <th>Tên Sách</th>
                                            <th>Tác giả</th>
                                            <th>NXB</th> <th>Loại sách</th> <th>Năm XB</th>
                                            <th>Lần XB</th>
                                            <th>Số lượng</th>
                                            <th>Nội dung tóm lược</th>
                                            <th>Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sachList as $sach): ?>
                                            <tr>
                                                 <td>
                                                    <?php if (!empty($sach['AnhBia'])): ?>
                                                        <img src="<?= htmlspecialchars('../assets/uploads/book_covers/' . $sach['AnhBia']) ?>" alt="Ảnh bìa" class="book-cover-img"> <?php else: ?>
                                                        Không có ảnh
                                                    <?php endif; ?>
                                                 </td>
                                                <td><?= htmlspecialchars($sach['MaSoSach']) ?></td>
                                                <td><?= htmlspecialchars($sach['TenSach']) ?></td>
                                                <td><?= htmlspecialchars($sach['TacGia']) ?></td>
                                                <td><?= htmlspecialchars($sach['TenNXB']) ?></td> <td><?= htmlspecialchars($sach['LoaiSach']) ?></td> <td><?= htmlspecialchars($sach['NamXB']) ?></td>
                                                <td><?= htmlspecialchars($sach['LanXB']) ?></td>
                                                <td><?= htmlspecialchars($sach['SoLuong']) ?></td>
                                                <td><?= htmlspecialchars($sach['NoiDungTomLuoc']) ?></td>
                                                <td>
                                                    <a href="?action=edit&id=<?= urlencode($sach['MaSoSach']) ?>" class="btn btn-sm btn-warning me-2"><i class="fas fa-edit"></i> Sửa</a>
                                                    <a href="?action=delete&id=<?= urlencode($sach['MaSoSach']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa Quyển sách \'<?= htmlspecialchars($sach['TenSach']) ?>\' (Mã: <?= htmlspecialchars($sach['MaSoSach']) ?>) không?');"><i class="fas fa-trash-alt"></i> Xóa</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div> <?php else: ?>
                            <p>Chưa có quyển sách nào trong cơ sở dữ liệu.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?> </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>