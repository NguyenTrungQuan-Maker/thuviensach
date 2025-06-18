<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang quản trị Thư viện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../admin/css/admin.css">
    </head>
<body>

<div class="container-fluid">
    <div class="row">
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse"> <div class="position-sticky pt-3">
                <div class="d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <a href="#" class="h5 text-white text-decoration-none">
                    Library List</a> <button class="btn btn-outline-light d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar" aria-expanded="false" aria-controls="sidebar">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>

                <ul class="nav flex-column mt-2"> <li class="nav-item">
                        <a class="nav-link active text-white" aria-current="page" href="#"> <i class="fas fa-home"></i>
                            Quản Lý Thư Viện Sách
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50" href="quanly_quyensach.php"> <i class="fas fa-tags"></i>
                       Quản Lý Sách
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50" href="quanly_loaisach.php"> <i class="fas fa-tags"></i>
                       Quản Lý Loại sách
                        </a>
                    </li>     
                    <li class="nav-item">
                        <a class="nav-link text-white-50" href="quanly_yeucau_muon.php"> <i class="fas fa-tags"></i>
                       Quản Lý yêu cầu mượn sách
                        </a>
                    </li> 
                    <li class="nav-item">
                        <a class="nav-link text-white-50" href="quanly_chitietphieumuon.php"> <i class="fas fa-tags"></i>
                       Quản Lý chi tiết phiếu mượn
                        </a>
                    </li> 
                    <li class="nav-item">
                        <a class="nav-link text-white-50" href="quanly_yeucau_tra.php"> <i class="fas fa-tags"></i>
                       Quản Lý yêu cầu trả sách
                        </a>
                    </li> 
                    <li class="nav-item">
                        <a class="nav-link text-white-50" href="quanly_nxb.php"> <i class="fas fa-tags"></i>
                        Quản lý NXB 
                        </a>
                    </li>
                     <li class="nav-item">
                     <a class="nav-link text-white-50" href="quanly_nguoidung.php"> <i class="fas fa-user-circle"></i>
                            Quản Lý Người Dùng
                        </a>
                    </li>
                </ul>

                 <hr class="my-3 bg-secondary"> <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link text-white-50" href="#">
                            <i class="fas fa-sign-out-alt"></i>
                            Đăng xuất
                        </a>
                    </li>
                 </ul>

            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Trang chủ Admin</h1>
            </div>

            <p>Chọn một mục từ menu bên trái để quản lý.</p>

            <div id="main-content">
                </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

</body>
</html>