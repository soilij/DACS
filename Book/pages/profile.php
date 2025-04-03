<?php
// Khởi động session
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Include các file cần thiết
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Book.php';
require_once '../classes/Exchange.php';

// Khởi tạo đối tượng cần thiết
$user = new User();
$book = new Book();
$exchange = new Exchange();

// Thiết lập biến
$is_detail_page = true;
$msg = '';
$msg_type = '';

// Xác định người dùng hiện tại hoặc đang xem
$profile_id = isset($_GET['id']) ? $_GET['id'] : $_SESSION['user_id'];
$is_own_profile = ($profile_id == $_SESSION['user_id']);

// Lấy thông tin người dùng
$profile_user = $user->getUserById($profile_id);

if (!$profile_user) {
    // Người dùng không tồn tại, chuyển hướng về trang chủ
    $_SESSION['error'] = 'Không tìm thấy thông tin người dùng!';
    header('Location: ../index.php');
    exit();
}

// Lấy sách của người dùng
$user_books = $book->getByUser($profile_id, 6);

// Lấy số lượng sách
$total_books = $book->countByUser($profile_id);

// Lấy đánh giá của người dùng
$user_reviews = $user->getUserReviews($profile_id);

// Lấy số lượng giao dịch thành công
$completed_exchanges = $exchange->countCompletedByUser($profile_id);

// Xử lý theo dõi/hủy theo dõi người dùng
if (isset($_POST['follow']) && $profile_id != $_SESSION['user_id']) {
    if ($user->isFollowing($_SESSION['user_id'], $profile_id)) {
        $user->unfollowUser($_SESSION['user_id'], $profile_id);
    } else {
        $user->followUser($_SESSION['user_id'], $profile_id);
    }
}

// Kiểm tra trạng thái theo dõi
$is_following = false;
if (isset($_SESSION['user_id']) && $profile_id != $_SESSION['user_id']) {
    $is_following = $user->isFollowing($_SESSION['user_id'], $profile_id);
}

// Đếm số người theo dõi và đang theo dõi
$followers_count = $user->countFollowers($profile_id);
$following_count = $user->countFollowing($profile_id);

// Xử lý cập nhật thông tin cá nhân
if (isset($_POST['update_profile']) && $is_own_profile) {
    $data = [
        'id' => $_SESSION['user_id'],
        'full_name' => $_POST['full_name'],
        'email' => $_POST['email'],
        'address' => $_POST['address'],
        'phone' => $_POST['phone'],
        'bio' => $_POST['bio']
    ];
    
    if ($user->updateProfile($data)) {
        $msg = 'Thông tin cá nhân đã được cập nhật!';
        $msg_type = 'success';
        $profile_user = $user->getUserById($profile_id); // Refresh data
    } else {
        $msg = 'Có lỗi xảy ra khi cập nhật thông tin!';
        $msg_type = 'danger';
    }
}


// Xử lý cập nhật mật khẩu
if (isset($_POST['update_password']) && $is_own_profile) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $password_error = '';
    $password_success = '';
    
    if ($new_password != $confirm_password) {
        $password_error = 'Mật khẩu mới và xác nhận mật khẩu không khớp!';
    } elseif (!$user->checkPassword($_SESSION['user_id'], $current_password)) {
        $password_error = 'Mật khẩu hiện tại không đúng!';
    } elseif (strlen($new_password) < 6) {
        $password_error = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
    } else {
        if ($user->updatePassword($_SESSION['user_id'], $new_password)) {
            $msg = '<div class="d-flex align-items-center"><i class="fas fa-check-circle text-success me-2 fs-4"></i> Mật khẩu đã được cập nhật thành công!</div>';
            $msg_type = 'success';
            // Reset form sau khi cập nhật thành công
            $_POST = array();
        } else {
            $password_error = 'Có lỗi xảy ra khi cập nhật mật khẩu!';
        }
    }
}

// Xử lý cập nhật ảnh đại diện
if (isset($_FILES['profile_image']) && $is_own_profile && $_FILES['profile_image']['error'] == 0) {
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    $file_name = $_FILES['profile_image']['name'];
    $file_size = $_FILES['profile_image']['size'];
    $file_tmp = $_FILES['profile_image']['tmp_name'];
    
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    if (in_array($file_ext, $allowed_ext)) {
        if ($file_size <= 2097152) { // 2MB
            $new_file_name = $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
            $upload_path = '../uploads/users/' . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                if ($user->updateProfileImage($_SESSION['user_id'], $new_file_name)) {
                    $msg = 'Ảnh đại diện đã được cập nhật!';
                    $msg_type = 'success';
                    $profile_user = $user->getUserById($profile_id); // Refresh data
                } else {
                    $msg = 'Có lỗi xảy ra khi cập nhật ảnh đại diện!';
                    $msg_type = 'danger';
                }
            } else {
                $msg = 'Không thể tải ảnh lên!';
                $msg_type = 'danger';
            }
        } else {
            $msg = 'Kích thước file quá lớn! Tối đa 2MB.';
            $msg_type = 'danger';
        }
    } else {
        $msg = 'Định dạng file không được hỗ trợ! Chỉ chấp nhận JPG, PNG và GIF.';
        $msg_type = 'danger';
    }
}

// Include header
require_once '../includes/header.php';
?>

<div class="container py-5">
    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <!-- Profile Header -->
    <div class="profile-header bg-white p-4 shadow-sm rounded mb-4">
        <div class="row align-items-center">
            <div class="col-md-3 text-center">
                <img src="../uploads/users/<?php echo $profile_user['profile_image'] ? $profile_user['profile_image'] : 'default.jpg'; ?>" alt="<?php echo $profile_user['username']; ?>" class="profile-image img-fluid rounded-circle mb-3">
                
                <?php if ($is_own_profile): ?>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#updateImageModal">
                    <i class="fas fa-camera me-1"></i> Cập nhật ảnh
                </button>
                <?php endif; ?>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="mb-0"><?php echo $profile_user['full_name']; ?></h2>
                    
                    <?php if (!$is_own_profile): ?>
                    <form method="post" action="">
                        <?php if ($is_following): ?>
                        <button type="submit" name="follow" class="btn btn-outline-primary">
                            <i class="fas fa-user-check me-1"></i> Đang theo dõi
                        </button>
                        <?php else: ?>
                        <button type="submit" name="follow" class="btn btn-primary">
                            <i class="fas fa-user-plus me-1"></i> Theo dõi
                        </button>
                        <?php endif; ?>
                        
                    </form>
                    <?php else: ?>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="fas fa-edit me-1"></i> Chỉnh sửa hồ sơ
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fas fa-key me-1"></i> Đổi mật khẩu
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <h5 class="text-muted">@<?php echo $profile_user['username']; ?></h5>
                </div>
                
                <div class="mb-3">
                    <div class="rating-stars mb-1">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?php if ($i <= $profile_user['rating']): ?>
                        <i class="fas fa-star"></i>
                        <?php elseif ($i - 0.5 <= $profile_user['rating']): ?>
                        <i class="fas fa-star-half-alt"></i>
                        <?php else: ?>
                        <i class="far fa-star"></i>
                        <?php endif; ?>
                        <?php endfor; ?>
                        <span class="ms-1"><?php echo number_format($profile_user['rating'], 1); ?></span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3 col-6 mb-2">
                        <div class="fw-bold"><?php echo $total_books; ?></div>
                        <div class="small text-muted">Sách đã đăng</div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="fw-bold"><?php echo $completed_exchanges; ?></div>
                        <div class="small text-muted">Giao dịch thành công</div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="fw-bold"><?php echo $followers_count; ?></div>
                        <div class="small text-muted">Người theo dõi</div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="fw-bold"><?php echo $following_count; ?></div>
                        <div class="small text-muted">Đang theo dõi</div>
                    </div>
                </div>
                
                <div>
                    <p><?php echo $profile_user['bio'] ? $profile_user['bio'] : 'Không có thông tin giới thiệu.'; ?></p>
                </div>
                
                <div class="mt-3">
                    <?php if ($profile_user['address']): ?>
                    <p class="mb-1">
                        <i class="fas fa-map-marker-alt me-2"></i> <?php echo $profile_user['address']; ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($profile_user['phone']): ?>
                    <p class="mb-1">
                        <i class="fas fa-phone me-2"></i> <?php echo $profile_user['phone']; ?>
                    </p>
                    <?php endif; ?>
                    
                    <p class="mb-1">
                        <i class="fas fa-envelope me-2"></i> <?php echo $profile_user['email']; ?>
                    </p>
                    
                    <p class="mb-0">
                        <i class="fas fa-clock me-2"></i> Tham gia từ <?php echo date('d/m/Y', strtotime($profile_user['created_at'])); ?>
                    </p>
                </div>
               <!-- Thêm các dòng debug này ngay trước nút báo cáo -->
               <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $profile_user['id']): ?>
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-outline-danger btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#reportUserModal" 
                            onclick="console.log('Session User ID: ', <?php echo $_SESSION['user_id']; ?>); 
                                    console.log('Profile User ID: ', <?php echo $profile_user['id']; ?>);">
                        <i class="fas fa-flag me-1"></i> Báo cáo người dùng
                    </button>
                    </div>
                <?php else: ?>
                    <!-- Debug info -->
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-4" id="profileTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="books-tab" data-bs-toggle="tab" data-bs-target="#books" type="button" role="tab" aria-controls="books" aria-selected="true">Sách đã đăng</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab" aria-controls="reviews" aria-selected="false">Đánh giá</button>
        </li>
    </ul>
    
    <!-- Tab Content -->
    <div class="tab-content" id="profileTabContent">
        <!-- Sách đã đăng -->
        <div class="tab-pane fade show active" id="books" role="tabpanel" aria-labelledby="books-tab">
            <div class="row">
                <?php if (count($user_books) > 0): ?>
                    <?php foreach ($user_books as $book): ?>
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="position-relative">
                                <img src="../uploads/books/<?php echo $book['image']; ?>" class="card-img-top" alt="<?php echo $book['title']; ?>" style="height: 200px; object-fit: cover;">
                                <?php if($book['status'] == 'available'): ?>
                                    <?php if($book['exchange_type'] == 'both'): ?>
                                    <span class="position-absolute top-0 start-0 badge bg-primary m-2">Trao đổi/Mua</span>
                                    <?php elseif($book['exchange_type'] == 'exchange_only'): ?>
                                    <span class="position-absolute top-0 start-0 badge bg-success m-2">Chỉ trao đổi</span>
                                    <?php else: ?>
                                    <span class="position-absolute top-0 start-0 badge bg-warning text-dark m-2">Chỉ bán</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="position-absolute top-0 start-0 badge bg-secondary m-2">Đã trao đổi</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><a href="book_details.php?id=<?php echo $book['id']; ?>" class="text-decoration-none text-dark"><?php echo $book['title']; ?></a></h5>
                                <p class="card-text text-muted"><?php echo $book['author']; ?></p>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <?php if($book['exchange_type'] != 'exchange_only'): ?>
                                    <span class="fw-bold"><?php echo number_format($book['price'], 0, ',', '.'); ?> đ</span>
                                    <?php else: ?>
                                    <span class="badge bg-success">Trao đổi</span>
                                    <?php endif; ?>
                                    
                                    <div>
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?php if($i <= $book['condition_rating']): ?>
                                        <i class="fas fa-star text-warning small"></i>
                                        <?php else: ?>
                                        <i class="far fa-star text-warning small"></i>
                                        <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0 text-center">
                                <a href="book_details.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline-primary">Xem chi tiết</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if ($total_books > 6): ?>
                    <div class="text-center mt-3">
                        <a href="my_books.php" class="btn btn-outline-primary">Xem tất cả sách</a>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info" role="alert">
                        <?php if ($is_own_profile): ?>
                        Bạn chưa đăng sách nào. <a href="add_book.php" class="alert-link">Đăng sách ngay</a>!
                        <?php else: ?>
                        Người dùng này chưa đăng sách nào.
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Đánh giá -->
        <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
            <?php if (count($user_reviews) > 0): ?>
                <?php foreach ($user_reviews as $review): ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex mb-3">
                            <img src="../uploads/users/<?php echo $review['profile_image'] ? $review['profile_image'] : 'default.jpg'; ?>" alt="<?php echo $review['username']; ?>" class="reviewer-image me-3">
                            <div>
                                <h5 class="mb-1"><?php echo $review['username']; ?></h5>
                                <div class="rating-stars mb-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $review['rating']): ?>
                                    <i class="fas fa-star text-warning"></i>
                                    <?php else: ?>
                                    <i class="far fa-star text-warning"></i>
                                    <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-muted small mb-0"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></p>
                            </div>
                        </div>
                        <p class="mb-0"><?php echo $review['comment']; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="alert alert-info" role="alert">
                Người dùng này chưa có đánh giá nào.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal cập nhật ảnh đại diện -->
<?php if ($is_own_profile): ?>
<div class="modal fade" id="updateImageModal" tabindex="-1" aria-labelledby="updateImageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateImageModalLabel">Cập nhật ảnh đại diện</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img id="imagePreview" src="../uploads/users/<?php echo $profile_user['profile_image'] ? $profile_user['profile_image'] : 'default.jpg'; ?>" alt="Preview" class="img-fluid rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Chọn ảnh đại diện mới</label>
                        <input class="form-control" type="file" id="profile_image" name="profile_image" accept="image/*" onchange="previewImage(this)">
                        <div class="form-text">Chấp nhận các định dạng JPG, PNG và GIF. Kích thước tối đa 2MB.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal chỉnh sửa hồ sơ -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Chỉnh sửa hồ sơ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Họ và tên</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $profile_user['full_name']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $profile_user['email']; ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="address" class="form-label">Địa chỉ</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?php echo $profile_user['address']; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Số điện thoại</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $profile_user['phone']; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="bio" class="form-label">Giới thiệu</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo $profile_user['bio']; ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="update_profile" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal đổi mật khẩu -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Đổi mật khẩu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($password_error) && !empty($password_error)): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <div><?php echo $password_error; ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                        <input type="password" class="form-control <?php echo (isset($password_error) && strpos($password_error, 'hiện tại') !== false) ? 'is-invalid' : ''; ?>" id="current_password" name="current_password" required>
                        <?php if (isset($password_error) && strpos($password_error, 'hiện tại') !== false): ?>
                        <div class="invalid-feedback">
                            Mật khẩu hiện tại không đúng
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Mật khẩu mới</label>
                        <input type="password" class="form-control <?php echo (isset($password_error) && strpos($password_error, 'ít nhất 6 ký tự') !== false) ? 'is-invalid' : ''; ?>" id="new_password" name="new_password" required>
                        <div class="form-text">Mật khẩu phải có ít nhất 6 ký tự.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                        <input type="password" class="form-control <?php echo (isset($password_error) && strpos($password_error, 'không khớp') !== false) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password" required>
                        <?php if (isset($password_error) && strpos($password_error, 'không khớp') !== false): ?>
                        <div class="invalid-feedback">
                            Mật khẩu xác nhận không khớp với mật khẩu mới
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="update_password" class="btn btn-primary">Cập nhật mật khẩu</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Báo cáo người dùng -->
<div class="modal fade" id="reportUserModal" tabindex="-1" aria-labelledby="reportUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="../pages/report_user.php">
                <input type="hidden" name="reported_user_id" value="<?php echo $profile_user['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportUserModalLabel">Báo cáo người dùng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="report_type" class="form-label">Loại báo cáo</label>
                        <select class="form-select" id="report_type" name="report_type" required>
                            <option value="">-- Chọn loại báo cáo --</option>
                            <option value="spam">Spam</option>
                            <option value="inappropriate_content">Nội dung không phù hợp</option>
                            <option value="fake_book">Sách giả</option>
                            <option value="scam">Lừa đảo</option>
                            <option value="harassment">Quấy rối</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="report_reason" class="form-label">Lý do báo cáo</label>
                        <textarea class="form-control" id="report_reason" name="report_reason" rows="4" required placeholder="Mô tả chi tiết lý do báo cáo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="report_user" class="btn btn-danger">Gửi báo cáo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        var reportModal = document.getElementById("reportUserModal");
        if (reportModal) {
            reportModal.addEventListener("show.bs.modal", function (event) {
                console.log("Report modal is shown");
            });
        } else {
            console.log("Report modal not found");
        }
    });

</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>