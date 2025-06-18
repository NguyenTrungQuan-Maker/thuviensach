<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Loại sách</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../admin/css/admin.css">
    <link rel="stylesheet" href="../admin/css/loaisach.css">

</head>
<?php
include '../admin/index.php';
?>
<body>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $editMode ? 'Sửa Loại sách' : 'Quản lý Loại sách' ?></h1>
            </div>

            <?php echo $message; ?>

            <?php if ($editMode): // Hiển thị Form Sửa khi ở chế độ sửa ?>
                <div class="card mb-4">
                    <div class="card-header">
                        Sửa thông tin Loại sách
                    </div>
                    <div class="card-body">
                         <form action="quanly_loaisach.php" method="POST">
                            <input type="hidden" name="update_loaisach" value="1">
                            <div class="mb-3">
                                <label for="ma_loai_sach_edit" class="form-label">Mã Loại sách:</label>
                                <input type="text" class="form-control" id="ma_loai_sach_edit" name="ma_loai_sach" value="<?= htmlspecialchars($currentLoaiSach['MaLoaiSach']) ?>" readonly required>
                                <input type="hidden" name="original_ma_loai_sach" value="<?= htmlspecialchars($currentLoaiSach['MaLoaiSach']) ?>"> </div>
                            <div class="mb-3">
                                <label for="loai_sach_edit" class="form-label">Tên Loại sách:</label>
                                <input type="text" class="form-control" id="loai_sach_edit" name="loai_sach" value="<?= htmlspecialchars($currentLoaiSach['LoaiSach']) ?>" maxlength="100" required>
                            </div>
                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Cập nhật</button>
                            <a href="quanly_loaisach.php" class="btn btn-secondary">Hủy</a> </form>
                    </div>
                </div>
            <?php else: // Hiển thị Form Thêm mới và Danh sách khi không ở chế độ sửa ?>
                <div class="card mb-4">
                    <div class="card-header">
                        Thêm Loại sách mới
                    </div>
                    <div class="card-body">
                         <form action="" method="POST">
                            <input type="hidden" name="add_loaisach" value="1">
                            <div class="mb-3">
                                <label for="ma_loai_sach" class="form-label">Mã Loại sách:</label>
                                <input type="text" class="form-control" id="ma_loai_sach" name="ma_loai_sach" maxlength="50" required>
                            </div>
                            <div class="mb-3">
                                <label for="loai_sach" class="form-label">Tên Loại sách:</label>
                                <input type="text" class="form-control" id="loai_sach" name="loai_sach" maxlength="100" required>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm mới</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        Danh sách Loại sách
                    </div>
                    <div class="card-body">
                        <?php if (count($loaisachList) > 0): ?>
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Mã Loại sách</th>
                                        <th>Tên Loại sách</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loaisachList as $loaisach): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($loaisach['MaLoaiSach']) ?></td>
                                            <td><?= htmlspecialchars($loaisach['LoaiSach']) ?></td>
                                            <td>
                                                <a href="?action=edit&id=<?= urlencode($loaisach['MaLoaiSach']) ?>" class="btn btn-sm btn-warning me-2"><i class="fas fa-edit"></i> Sửa</a>
                                                <a href="?action=delete&id=<?= urlencode($loaisach['MaLoaiSach']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa Loại sách \'<?= htmlspecialchars($loaisach['LoaiSach']) ?>\' (Mã: <?= htmlspecialchars($loaisach['MaLoaiSach']) ?>) không?');"><i class="fas fa-trash-alt"></i> Xóa</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>Chưa có loại sách nào trong cơ sở dữ liệu.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; // Kết thúc khối hiển thị Form Sửa hoặc Form Thêm + Danh sách ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>