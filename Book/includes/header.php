<?php
// Khởi động session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
function isLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    return false;
}

// Kiểm tra admin
function isAdmin() {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        return true;
    }
    return false;
}

// Include các file cần thiết
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Book.php';
require_once __DIR__ . '/../classes/Exchange.php';
require_once __DIR__ . '/../classes/Notification.php';

// Khởi tạo database
$database = new Database();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trao Đổi Sách - Nơi Kết Nối Tri Thức</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo isset($is_detail_page) && $is_detail_page ? '../assets/css/style.css' : 'assets/css/style.css'; ?>">
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo isset($is_detail_page) && $is_detail_page ? '../assets/images/favicon.ico' : 'assets/images/favicon.ico'; ?>" type="image/x-icon">
</head>
<body>
    <!-- Banner thông báo -->
    <div class="top-banner bg-dark text-white py-2 text-center">
        <div class="container">
            <small>Miễn phí giao hàng cho đơn trao đổi trên 3 cuốn sách</small>
        </div>
    </div>

    <!-- Header & Navigation -->
    <header class="bg-white py-3 border-bottom">
        <div class="container">
            <div class="row align-items-center">
                <!-- Logo -->
                <div class="col-auto">
                    <a href="<?php echo $is_detail_page ? '../index.php' : 'index.php'; ?>" class="text-decoration-none">
                        <h1 class="mb-0 fw-bold">BOOKSWAP</h1>
                    </a>
                </div>
                
                <!-- Navigation -->
                <div class="col">
                    <nav class="navbar navbar-expand-lg">
                        <div class="container-fluid">
                            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                                <span class="navbar-toggler-icon"></span>
                            </button>
                            <div class="collapse navbar-collapse" id="navbarMain">
                                <ul class="navbar-nav me-auto">
                                    <li class="nav-item">
                                        <a class="nav-link" href="<?php echo isset($is_detail_page) && $is_detail_page ? '../index.php' : 'index.php'; ?>">Trang chủ</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="<?php echo isset($is_detail_page) && $is_detail_page ? '../pages/search.php' : 'pages/search.php'; ?>">Tìm sách</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="<?php echo isset($is_detail_page) && $is_detail_page ? '../pages/how_it_works.php' : 'pages/how_it_works.php'; ?>">Cách thức hoạt động</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="<?php echo isset($is_detail_page) && $is_detail_page ? '../pages/contact.php' : 'pages/contact.php'; ?>">Liên hệ</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </nav>
                </div>
                
                <!-- Search, User Actions -->
                <div class="col-auto d-flex align-items-center">
                    <!-- Search Icon -->
                    <a href="<?php echo isset($is_detail_page) && $is_detail_page ? 'search.php' : 'pages/search.php'; ?>" class="me-3 text-dark">
                        <i class="fas fa-search"></i>
                    </a>
                    
                    <?php if(isLoggedIn()): ?>
                        <!-- Notifications -->
                        <div class="dropdown me-3">
                            <a class="text-dark position-relative" href="#" role="button" id="dropdownNotification" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <?php
                                // Đếm số thông báo chưa đọc
                                $notification = new Notification();
                                $count = $notification->countUnread($_SESSION['user_id']);
                                if($count > 0):
                                ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $count; ?>
                                </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownNotification">
                                <?php
                                $notifications = $notification->getRecent($_SESSION['user_id'], 5);
                                if(count($notifications) > 0):
                                    foreach($notifications as $n):
                                        // Xử lý đường dẫn thông báo
                                        $notificationLink = $n['link'];
                                        if (isset($is_detail_page) && $is_detail_page) {
                                            // Nếu đang ở trang chi tiết, thêm '../' vào đầu đường dẫn
                                            $notificationLink = '../' . $notificationLink;
                                        }
                                ?>
                                <li>
                                    <a class="dropdown-item notification-item <?php echo $n['is_read'] ? 'read' : 'unread'; ?>" 
                                       href="<?php echo $notificationLink; ?>" 
                                       data-notification-id="<?php echo $n['id']; ?>">
                                        <?php echo $n['message']; ?>
                                        <div class="small text-muted"><?php echo date('d/m/Y H:i', strtotime($n['created_at'])); ?></div>
                                    </a>
                                </li>
                                <?php
                                    endforeach;
                                else:
                                ?>
                                <li><a class="dropdown-item text-center" href="#">Không có thông báo</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="<?php echo isset($is_detail_page) && $is_detail_page ? '../pages/notifications.php' : 'pages/notifications.php'; ?>">Xem tất cả</a></li>
                            </ul>
                        </div>

                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            // Xử lý khi click vào thông báo
                            const notificationItems = document.querySelectorAll('.notification-item');
                            
                            notificationItems.forEach(item => {
                                item.addEventListener('click', function(e) {
                                    const notificationId = this.dataset.notificationId;
                                    const isUnread = this.classList.contains('unread');
                                    
                                    // Nếu là thông báo chưa đọc
                                    if (isUnread) {
                                        // Gửi AJAX để đánh dấu đã đọc
                                        fetch('api/mark_notification_read.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/x-www-form-urlencoded',
                                            },
                                            body: `notification_id=${notificationId}`
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.status === 'success') {
                                                // Cập nhật giao diện
                                                this.classList.remove('unread');
                                                this.classList.add('read');
                                                
                                                // Cập nhật số thông báo chưa đọc
                                                const badge = document.querySelector('.badge');
                                                if (badge) {
                                                    const currentCount = parseInt(badge.textContent);
                                                    if (currentCount > 1) {
                                                        badge.textContent = currentCount - 1;
                                                    } else {
                                                        badge.remove();
                                                    }
                                                }
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error:', error);
                                        });
                                    }
                                });
                            });
                        });
                        </script>

                        <style>
                        .notification-item.unread {
                            font-weight: bold;
                            background-color: #f8f9fa;
                        }

                        .notification-item.read {
                            font-weight: normal;
                        }
                        </style>
                        
                        <!-- User Dropdown -->
                        <div class="dropdown">
                            <a class="text-dark text-decoration-none dropdown-toggle" href="#" role="button" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownUser">
                                <li><a class="dropdown-item" href="<?php echo isset($is_detail_page) && $is_detail_page ? '../pages/profile.php' : 'pages/profile.php'; ?>">Hồ sơ cá nhân</a></li>
                                <li><a class="dropdown-item" href="<?php echo isset($is_detail_page) && $is_detail_page ? '../pages/my_books.php' : 'pages/my_books.php'; ?>">Sách của tôi</a></li>
                                <li><a class="dropdown-item" href="<?php echo isset($is_detail_page) && $is_detail_page ? '../pages/exchange_requests.php' : 'pages/exchange_requests.php'; ?>">Yêu cầu trao đổi</a></li>
                                <li><a class="dropdown-item" href="<?php echo isset($is_detail_page) && $is_detail_page ? '../pages/wishlist.php' : 'pages/wishlist.php'; ?>">Danh sách yêu thích</a></li>
                                <?php if(isAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo isset($is_detail_page) && $is_detail_page ? '../admin/index.php' : 'admin/index.php'; ?>">Quản trị viên</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo isset($is_detail_page) && $is_detail_page ? '../logout.php' : 'logout.php'; ?>">Đăng xuất</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Login & Register -->
                        <a href="<?php echo isset($is_detail_page) && $is_detail_page ? '../login.php' : 'login.php'; ?>" class="btn btn-outline-primary me-2">Đăng nhập</a>
                        <a href="<?php echo isset($is_detail_page) && $is_detail_page ? '../register.php' : 'register.php'; ?>" class="btn btn-primary">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content Container -->
    <main class="py-4">
        <div class="container">