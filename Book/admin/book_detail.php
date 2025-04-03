<?php
// Khởi động session
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../index.php');
    exit();
}

// Include các file cần thiết
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Book.php';
require_once '../classes/Category.php';
require_once '../classes/User.php';

// Khởi tạo đối tượng cần thiết
$book = new Book();
$category = new Category();
$user = new User();

// Kiểm tra ID sách
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: books.php');
    exit();
}

$book_id = $_GET['id'];

// Lấy thông tin sách
$book_info = $book->getById($book_id);

if (!$book_info) {
    header('Location: books.php');
    exit();
}

// Lấy thông tin người đăng
$book_owner = $user->getUserById($book_info['user_id']);

// Lấy các ảnh bổ sung của sách
$book_images = $book->getImages($book_id);

// Admin header
include('includes/header.php');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Chi tiết sách</h1>
        <a href="books.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Quay lại
        </a>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Thông tin sách</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-4 mb-md-0">
                            <img src="../uploads/books/<?php echo $book_info['image']; ?>" alt="<?php echo $book_info['title']; ?>" class="img-fluid rounded">
                            
                            <!-- Ảnh bổ sung -->
                            <?php if(count($book_images) > 0): ?>
                            <div class="mt-3 d-flex flex-wrap gap-2">
                                <?php foreach($book_images as $image): ?>
                                <img src="../uploads/books/<?php echo $image['image_path']; ?>" alt="Extra" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-8">
                            <h2 class="mb-2"><?php echo $book_info['title']; ?></h2>
                            <p class="text-muted mb-3">Tác giả: <?php echo $book_info['author']; ?></p>
                            
                            <div class="mb-3">
                                <strong>Danh mục:</strong> 
                                <span class="badge bg-info"><?php echo $book_info['category_name']; ?></span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Tình trạng sách:</strong>
                                <span>
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php if($i <= $book_info['condition_rating']): ?>
                                    <i class="fas fa-star text-warning"></i>
                                    <?php else: ?>
                                    <i class="far fa-star text-warning"></i>
                                    <?php endif; ?>
                                    <?php endfor; ?>
                                </span>
                            </div>
                            
                            <?php if($book_info['isbn']): ?>
                            <div class="mb-3">
                                <strong>ISBN:</strong> <?php echo $book_info['isbn']; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <strong>Hình thức:</strong>
                                <?php if($book_info['exchange_type'] == 'exchange_only'): ?>
                                <span class="badge bg-success">Chỉ trao đổi</span>
                                <?php elseif($book_info['exchange_type'] == 'sell_only'): ?>
                                <span class="badge bg-danger">Chỉ bán</span>
                                <?php else: ?>
                                <span class="badge bg-primary">Trao đổi hoặc bán</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if($book_info['exchange_type'] != 'exchange_only'): ?>
                            <div class="mb-3">
                                <strong>Giá:</strong> 
                                <?php echo number_format($book_info['price'], 0, ',', '.'); ?> đ
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <strong>Trạng thái:</strong>
                                <?php if($book_info['status'] == 'available'): ?>
                                <span class="badge bg-success">Có sẵn</span>
                                <?php elseif($book_info['status'] == 'pending_approval'): ?>
                                <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                <?php elseif($book_info['status'] == 'pending'): ?>
                                <span class="badge bg-info">Đang giao dịch</span>
                                <?php elseif($book_info['status'] == 'exchanged'): ?>
                                <span class="badge bg-secondary">Đã trao đổi</span>
                                <?php elseif($book_info['status'] == 'rejected'): ?>
                                <span class="badge bg-danger">Đã từ chối</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Ngày đăng:</strong> 
                                <?php echo date('d/m/Y H:i', strtotime($book_info['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <strong>Mô tả:</strong>
                        <p><?php echo nl2br($book_info['description']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Thông tin người đăng -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Thông tin người đăng</h4>
                </div>
                <div class="card-body text-center">
                    <img src="../uploads/users/<?php echo $book_owner['profile_image'] ? $book_owner['profile_image'] : 'default.jpg'; ?>" 
                         alt="<?php echo $book_owner['username']; ?>" 
                         class="img-fluid rounded-circle mb-3" 
                         style="width: 150px; height: 150px; object-fit: cover;">
                    
                    <h5 class="mb-1"><?php echo $book_owner['full_name']; ?></h5>
                    <p class="text-muted mb-2">@<?php echo $book_owner['username']; ?></p>
                    
                    <div class="mb-3">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?php if ($i <= $book_owner['rating']): ?>
                        <i class="fas fa-star text-warning"></i>
                        <?php elseif ($i - 0.5 <= $book_owner['rating']): ?>
                        <i class="fas fa-star-half-alt text-warning"></i>
                        <?php else: ?>
                        <i class="far fa-star text-warning"></i>
                        <?php endif; ?>
                        <?php endfor; ?>
                        <span class="ms-1"><?php echo number_format($book_owner['rating'], 1); ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Email:</strong> <?php echo $book_owner['email']; ?>
                    </div>
                    
                    <?php if($book_owner['address']): ?>
                    <div class="mb-3">
                        <strong>Địa chỉ:</strong> <?php echo $book_owner['address']; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Admin footer
include('includes/footer.php');
?>