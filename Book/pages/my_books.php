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
require_once '../classes/Book.php';
require_once '../classes/Category.php';
require_once '../classes/User.php';

// Khởi tạo đối tượng cần thiết
$book = new Book();
$category = new Category();
$user = new User();

// Thiết lập biến
$is_detail_page = true;
$msg = '';
$msg_type = '';

// Xử lý xóa sách
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $book_id = $_GET['id'];
    
    if ($book->delete($book_id, $_SESSION['user_id'])) {
        $msg = 'Sách đã được xóa thành công!';
        $msg_type = 'success';
    } else {
        $msg = 'Có lỗi xảy ra khi xóa sách!';
        $msg_type = 'danger';
    }
}

// Lấy tất cả sách của người dùng hiện tại
$user_books = $book->getByUser($_SESSION['user_id']);

// Lọc sách theo trạng thái nếu có
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

if (!empty($filter_status)) {
    $filtered_books = [];
    foreach ($user_books as $b) {
        if ($b['status'] == $filter_status) {
            $filtered_books[] = $b;
        }
    }
    $user_books = $filtered_books;
}

// Lấy danh sách danh mục để hiển thị tên
$categories = $category->getAll();
$category_names = [];
foreach ($categories as $cat) {
    $category_names[$cat['id']] = $cat['name'];
}

// Include header
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Sách của tôi</h1>
        <a href="add_book.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Đăng sách mới
        </a>
    </div>
    
    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <!-- Tabs để lọc -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo empty($filter_status) ? 'active' : ''; ?>" href="my_books.php">Tất cả</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter_status == 'available' ? 'active' : ''; ?>" href="?status=available">Có sẵn</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter_status == 'pending_approval' ? 'active' : ''; ?>" href="?status=pending_approval">Chờ duyệt</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter_status == 'pending' ? 'active' : ''; ?>" href="?status=pending">Đang giao dịch</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter_status == 'exchanged' ? 'active' : ''; ?>" href="?status=exchanged">Đã trao đổi</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter_status == 'rejected' ? 'active' : ''; ?>" href="?status=rejected">Bị từ chối</a>
        </li>
    </ul>
    
    <?php if (count($user_books) > 0): ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        <?php foreach ($user_books as $b): ?>
        <div class="col">
            <div class="card h-100 shadow-sm book-card">
                <div class="position-relative">
                    <img src="../uploads/books/<?php echo $b['image']; ?>" class="card-img-top" alt="<?php echo $b['title']; ?>" style="height: 200px; object-fit: cover;">
                    <?php if($b['status'] == 'available'): ?>
                        <?php if($b['exchange_type'] == 'both'): ?>
                        <span class="position-absolute top-0 start-0 badge bg-primary m-2">Trao đổi/Mua</span>
                        <?php elseif($b['exchange_type'] == 'exchange_only'): ?>
                        <span class="position-absolute top-0 start-0 badge bg-success m-2">Chỉ trao đổi</span>
                        <?php else: ?>
                        <span class="position-absolute top-0 start-0 badge bg-warning text-dark m-2">Chỉ bán</span>
                        <?php endif; ?>
                    <?php elseif($b['status'] == 'pending_approval'): ?>
                        <span class="position-absolute top-0 start-0 badge bg-warning text-dark m-2">Chờ duyệt</span>
                    <?php elseif($b['status'] == 'pending'): ?>
                        <span class="position-absolute top-0 start-0 badge bg-info m-2">Đang giao dịch</span>
                    <?php elseif($b['status'] == 'rejected'): ?>
                        <span class="position-absolute top-0 start-0 badge bg-danger m-2">Bị từ chối</span>
                    <?php else: ?>
                        <span class="position-absolute top-0 start-0 badge bg-secondary m-2">Đã trao đổi</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $b['title']; ?></h5>
                    <p class="card-text text-muted"><?php echo $b['author']; ?></p>
                    
                    <div class="mb-2">
                        <span class="badge bg-info text-dark">
                            <?php echo isset($category_names[$b['category_id']]) ? $category_names[$b['category_id']] : 'Không rõ'; ?>
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <?php if($b['exchange_type'] != 'exchange_only'): ?>
                        <span class="fw-bold"><?php echo number_format($b['price'], 0, ',', '.'); ?> đ</span>
                        <?php else: ?>
                        <span class="badge bg-success">Trao đổi</span>
                        <?php endif; ?>
                        
                        <div>
                            <?php for($i = 1; $i <= 5; $i++): ?>
                            <?php if($i <= $b['condition_rating']): ?>
                            <i class="fas fa-star text-warning small"></i>
                            <?php else: ?>
                            <i class="far fa-star text-warning small"></i>
                            <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white border-top-0">
                    <div class="d-flex justify-content-between">
                        <a href="book_details.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye me-1"></i> Xem
                        </a>
                        
                        <div class="btn-group">
                            <?php if($b['status'] == 'available' || $b['status'] == 'rejected'): ?>
                            <a href="edit_book.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit me-1"></i> Sửa
                            </a>
                            <a href="?action=delete&id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa sách này?');">
                                <i class="fas fa-trash me-1"></i> Xóa
                            </a>
                            <?php elseif($b['status'] == 'pending_approval'): ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled>
                                <i class="fas fa-hourglass-half me-1"></i> Đang chờ duyệt
                            </button>
                            <?php elseif($b['status'] == 'pending'): ?>
                            <a href="exchange_requests.php" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-exchange-alt me-1"></i> Xem giao dịch
                            </a>
                            <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled>
                                <i class="fas fa-check me-1"></i> Đã trao đổi
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-info" role="alert">
        <i class="fas fa-info-circle me-2"></i> 
        <?php if (empty($filter_status)): ?>
            Bạn chưa có sách nào. <a href="add_book.php" class="alert-link">Đăng sách ngay</a>!
        <?php else: ?>
            Không tìm thấy sách nào với trạng thái đã chọn.
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>