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

// Khởi tạo đối tượng cần thiết
$user = new User();
$book = new Book();

// Thiết lập biến
$is_detail_page = true;
$msg = '';
$msg_type = '';

// Xử lý xóa sách khỏi danh sách yêu thích
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['book_id'])) {
    $book_id = $_GET['book_id'];
    
    if ($user->removeFromWishlist($_SESSION['user_id'], $book_id)) {
        $msg = 'Đã xóa sách khỏi danh sách yêu thích!';
        $msg_type = 'success';
    } else {
        $msg = 'Có lỗi xảy ra khi xóa sách!';
        $msg_type = 'danger';
    }
}

// Lấy danh sách sách yêu thích
$wishlist_books = $user->getUserWishlist($_SESSION['user_id']);

// Include header
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Danh sách yêu thích</h1>
    </div>
    
    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if (count($wishlist_books) > 0): ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        <?php foreach ($wishlist_books as $book_item): ?>
        <div class="col">
            <div class="card h-100 shadow-sm book-card">
                <div class="position-relative">
                    <a href="book_details.php?id=<?php echo $book_item['id']; ?>">
                        <img src="../uploads/books/<?php echo $book_item['image']; ?>" class="card-img-top" alt="<?php echo $book_item['title']; ?>" style="height: 250px; object-fit: cover;">
                    </a>
                    
                    <?php if($book_item['exchange_type'] == 'both'): ?>
                    <span class="position-absolute top-0 start-0 badge bg-primary m-2">Trao đổi/Mua</span>
                    <?php elseif($book_item['exchange_type'] == 'exchange_only'): ?>
                    <span class="position-absolute top-0 start-0 badge bg-success m-2">Chỉ trao đổi</span>
                    <?php else: ?>
                    <span class="position-absolute top-0 start-0 badge bg-warning text-dark m-2">Chỉ bán</span>
                    <?php endif; ?>
                    
                    <a href="?action=remove&book_id=<?php echo $book_item['id']; ?>" class="btn btn-sm position-absolute top-0 end-0 m-2 btn-danger rounded-circle p-2" onclick="return confirm('Bạn có chắc chắn muốn xóa sách này khỏi danh sách yêu thích?');">
                        <i class="fas fa-heart"></i>
                    </a>
                </div>
                
                <div class="card-body">
                    <h5 class="card-title">
                        <a href="book_details.php?id=<?php echo $book_item['id']; ?>" class="text-decoration-none text-dark">
                            <?php echo $book_item['title']; ?>
                        </a>
                    </h5>
                    <p class="card-text text-muted"><?php echo $book_item['author']; ?></p>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <?php if($book_item['exchange_type'] != 'exchange_only'): ?>
                        <span class="fw-bold"><?php echo number_format($book_item['price'], 0, ',', '.'); ?> đ</span>
                        <?php else: ?>
                        <span class="badge bg-success">Trao đổi</span>
                        <?php endif; ?>
                        
                        <div>
                            <?php for($i = 1; $i <= 5; $i++): ?>
                            <?php if($i <= $book_item['condition_rating']): ?>
                            <i class="fas fa-star text-warning small"></i>
                            <?php else: ?>
                            <i class="far fa-star text-warning small"></i>
                            <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer bg-white border-top-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            Thêm vào yêu thích: <?php echo date('d/m/Y', strtotime($book_item['wishlist_date'])); ?>
                        </small>
                        <a href="book_details.php?id=<?php echo $book_item['id']; ?>" class="btn btn-sm btn-outline-primary">
                            Xem chi tiết
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-info" role="alert">
        <i class="fas fa-info-circle me-2"></i> 
        Bạn chưa có sách nào trong danh sách yêu thích. <a href="search.php" class="alert-link">Khám phá sách ngay</a>!
    </div>
    <?php endif; ?>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>