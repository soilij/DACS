<?php
// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - BookSwap</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    
    <!-- Favicon -->
    <link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="wrapper d-flex">
        <!-- Sidebar -->
        <nav id="sidebar" class="bg-dark">
            <div class="sidebar-header">
                <h3 class="text-white mb-0 py-3 px-4 text-center">BookSwap Admin</h3>
            </div>

            <ul class="list-unstyled components">
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <a href="index.php" class="text-decoration-none px-4 py-3 d-block">
                        <i class="fas fa-tachometer-alt me-2"></i> Bảng điều khiển
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <a href="users.php" class="text-decoration-none px-4 py-3 d-block">
                        <i class="fas fa-users me-2"></i> Quản lý người dùng
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports_list.php' ? 'active' : ''; ?>">
                    <a href="reports_list.php" class="text-decoration-none px-4 py-3 d-block">
                        <i class="fas fa-flag me-2"></i> Quản lý báo cáo
                        <?php 
                        // Hiển thị badge nếu có báo cáo chờ xử lý
                        if (class_exists('Report')) {
                            $pendingReports = (new Report())->getPendingReportsCount();
                            if ($pendingReports > 0) {
                                echo '<span class="badge bg-danger ms-2">' . $pendingReports . '</span>';
                            }
                        }
                        ?>
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'books.php' ? 'active' : ''; ?>">
                    <a href="books.php" class="text-decoration-none px-4 py-3 d-block">
                        <i class="fas fa-book me-2"></i> Quản lý sách
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <a href="categories.php" class="text-decoration-none px-4 py-3 d-block">
                        <i class="fas fa-list me-2"></i> Danh mục sách
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                    <a href="orders.php" class="text-decoration-none px-4 py-3 d-block">
                        <i class="fas fa-exchange-alt me-2"></i> Quản lý đơn hàng
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'exchanges.php' ? 'active' : ''; ?>">
                    <a href="exchanges.php" class="text-decoration-none px-4 py-3 d-block">
                        <i class="fas fa-exchange-alt me-2"></i> Quản lý trao đổi
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <a href="reports.php" class="text-decoration-none px-4 py-3 d-block">
                        <i class="fas fa-chart-line me-2"></i> Báo cáo thống kê
                    </a>
                </li>
                <li>
                    <a href="../index.php" class="text-decoration-none px-4 py-3 d-block">
                        <i class="fas fa-home me-2"></i> Về trang chủ
                    </a>
                </li>
                <li>
                    <a href="../logout.php" class="text-decoration-none px-4 py-3 d-block">
                        <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content" class="flex-grow-1 bg-light">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="ms-auto d-flex align-items-center">
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i> <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="../profile.php"><i class="fas fa-user me-2"></i> Hồ sơ</a></li>
                                <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>