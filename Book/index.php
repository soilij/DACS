<?php
// Include header
require_once 'includes/header.php';
require_once 'classes/Category.php';

// Khởi tạo Book class
$book = new Book();

// Lấy các sách mới nhất
$latest_books = $book->getLatest(8);

// Lấy sách phổ biến
$popular_books = $book->getPopular(5);

// Lấy danh mục nổi bật
$category = new Category();
$featured_categories = $category->getFeatured();
?>
<!-- Include Chatbot -->
<?php require_once 'includes/chatbot_include.php'; ?>

<!-- Hero Section / Slider -->
<section class="hero-section position-relative bg-light mb-5">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="pe-lg-5">
                    <h6 class="text-primary">BOOKSWAP - NỀN TẢNG TRAO ĐỔI SÁCH</h6>
                    <h1 class="display-5 fw-bold mb-4">Trao đổi sách, Kết nối tri thức</h1>
                    <p class="lead mb-4">Khám phá vô vàn cuốn sách hấp dẫn, trao đổi dễ dàng với cộng đồng yêu sách. Mở rộng tủ sách của bạn mà không tốn nhiều chi phí.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="pages/search.php" class="btn btn-primary btn-lg">Tìm sách ngay</a>
                        <a href="pages/add_book.php" class="btn btn-outline-dark btn-lg">Đăng sách của bạn</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="uploads/books/background.jpg" alt="Bookswap Hero" class="img-fluid rounded-3 shadow">
            </div>
        </div>
    </div>
</section>

<!-- Cách thức hoạt động -->
<section class="how-it-works mb-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Cách thức hoạt động</h2>
            <p class="lead">Chỉ với 4 bước đơn giản, trao đổi sách chưa bao giờ dễ dàng đến thế</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card h-100 border-0 text-center">
                    <div class="card-body">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                            <i class="fas fa-upload fa-2x"></i>
                        </div>
                        <h5 class="card-title">Đăng sách</h5>
                        <p class="card-text">Đăng tải những cuốn sách bạn muốn trao đổi lên hệ thống.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card h-100 border-0 text-center">
                    <div class="card-body">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                            <i class="fas fa-search fa-2x"></i>
                        </div>
                        <h5 class="card-title">Tìm sách</h5>
                        <p class="card-text">Khám phá những cuốn sách bạn muốn từ cộng đồng người dùng.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card h-100 border-0 text-center">
                    <div class="card-body">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                            <i class="fas fa-handshake fa-2x"></i>
                        </div>
                        <h5 class="card-title">Đề xuất trao đổi</h5>
                        <p class="card-text">Gửi yêu cầu và trao đổi thông tin với chủ sở hữu cuốn sách.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card h-100 border-0 text-center">
                    <div class="card-body">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                            <i class="fas fa-book-reader fa-2x"></i>
                        </div>
                        <h5 class="card-title">Nhận sách mới</h5>
                        <p class="card-text">Nhận sách mới và trải nghiệm niềm vui đọc sách.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Danh mục sách -->
<section class="featured-categories py-5 mb-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0 fw-bold">Danh mục sách</h2>
                <p class="text-muted mt-2">Khám phá sách theo sở thích của bạn</p>
            </div>
            <!-- <a href="pages/categories.php" class="btn btn-outline-primary rounded-pill">Xem tất cả <i class="fas fa-arrow-right ms-1"></i></a> -->
        </div>
        
        <div class="position-relative categories-wrapper">
            <!-- Nút điều khiển cuộn -->
            <button class="position-absolute top-50 start-0 translate-middle-y btn btn-white rounded-circle shadow scroll-control prev-btn" 
                    style="z-index: 10; width: 45px; height: 45px; transform: translateX(-10px);">
                <i class="fas fa-chevron-left text-primary"></i>
            </button>
            <button class="position-absolute top-50 end-0 translate-middle-y btn btn-white rounded-circle shadow scroll-control next-btn" 
                    style="z-index: 10; width: 45px; height: 45px; transform: translateX(10px);">
                <i class="fas fa-chevron-right text-primary"></i>
            </button>
            
            <!-- Container có thể cuộn -->
            <div class="categories-scroll-container" 
                 style="overflow-x: auto; scroll-behavior: smooth; -webkit-overflow-scrolling: touch; scrollbar-width: none; -ms-overflow-style: none; padding: 10px 0 20px 0;">
                <div class="d-flex flex-nowrap">
                    <?php
                    // Truy vấn tất cả danh mục từ cơ sở dữ liệu
                    $all_categories = $category->getAll();
                    foreach($all_categories as $cat): 
                    ?>
                    <div class="category-item mx-2" style="min-width: 200px; flex: 0 0 auto;">
                        <a href="pages/search.php?category=<?php echo $cat['id']; ?>" class="text-decoration-none">
                            <div class="card h-100 border-0 rounded-4 overflow-hidden category-card">
                                <div class="category-img-container position-relative" style="height: 140px; overflow: hidden;">
                                    <img src="assets/images/categories/<?php echo $cat['image']; ?>" 
                                         alt="<?php echo $cat['name']; ?>" 
                                         class="w-100 h-100 object-fit-cover">
                                    <div class="category-overlay"></div>
                                </div>
                                <div class="card-body text-center py-3">
                                    <h5 class="card-title mb-0 fw-bold"><?php echo $cat['name']; ?></h5>
                                    <?php
                                    // Đếm số sách trong danh mục
                                    $book_count = $category->countBooks($cat['id']);
                                    ?>
                                    <p class="text-muted small mb-0 mt-1"><?php echo $book_count; ?> cuốn sách</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Sách mới -->
<section class="latest-books mb-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0 fw-bold">Sách mới</h2>
            <a href="pages/search.php?sort=newest" class="btn btn-outline-primary rounded-pill">Xem tất cả <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
        
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach($latest_books as $book): ?>
            <div class="col">
                <div class="card h-100 border-0 book-card">
                    <div class="position-relative">
                        <a href="pages/book_details.php?id=<?php echo $book['id']; ?>">
                            <img src="uploads/books/<?php echo $book['image']; ?>" class="card-img-top" alt="<?php echo $book['title']; ?>" style="height: 300px; object-fit: cover;">
                        </a>
                        <?php if($book['exchange_type'] == 'both'): ?>
                        <span class="position-absolute top-0 start-0 badge bg-primary m-2">Trao đổi/Mua</span>
                        <?php elseif($book['exchange_type'] == 'exchange_only'): ?>
                        <span class="position-absolute top-0 start-0 badge bg-success m-2">Chỉ trao đổi</span>
                        <?php else: ?>
                        <span class="position-absolute top-0 start-0 badge bg-warning text-dark m-2">Chỉ bán</span>
                        <?php endif; ?>
                        
                        <button class="btn btn-sm position-absolute top-0 end-0 m-2 btn-outline-danger rounded-circle p-2 toggle-wishlist" data-book-id="<?php echo $book['id']; ?>">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title mb-1"><a href="pages/book_details.php?id=<?php echo $book['id']; ?>" class="text-decoration-none text-dark"><?php echo $book['title']; ?></a></h5>
                        <p class="card-text text-muted small mb-2"><?php echo $book['author']; ?></p>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <?php if($book['exchange_type'] != 'exchange_only'): ?>
                            <span class="fw-bold"><?php echo number_format($book['price'], 0, ',', '.'); ?> đ</span>
                            <?php else: ?>
                            <span class="badge bg-success">Trao đổi</span>
                            <?php endif; ?>
                            
                            <div>
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                <?php if($i <= $book['condition_rating']): ?>
                                <i class="fas fa-star text-warning"></i>
                                <?php else: ?>
                                <i class="far fa-star text-warning"></i>
                                <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Sách phổ biến -->
<section class="popular-books mb-5 bg-light py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0 fw-bold">Lựa chọn phổ biến</h2>
            <a href="pages/search.php?sort=popular" class="btn btn-outline-primary rounded-pill">Xem tất cả <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
        
        <div class="row">
            <?php foreach($popular_books as $book): ?>
            <div class="col-md-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="row g-0">
                        <div class="col-md-3">
                            <a href="pages/book_details.php?id=<?php echo $book['id']; ?>">
                               <img src="uploads/books/<?php echo $book['image']; ?>" class="img-fluid rounded" alt="<?php echo $book['title']; ?>"  style="width: 100%; height: 250px; object-fit: cover; border-radius: 10px;">
                            </a>
                        </div>
                        <div class="col-md-9">
                            <div class="card-body h-100 d-flex flex-column">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3 class="card-title"><a href="pages/book_details.php?id=<?php echo $book['id']; ?>" class="text-decoration-none text-dark"><?php echo $book['title']; ?></a></h3>
                                        <h6 class="card-subtitle mb-2 text-muted"><?php echo $book['author']; ?></h6>
                                    </div>
                                    <div>
                                        <?php if($book['exchange_type'] != 'exchange_only'): ?>
                                        <span class="fs-4 fw-bold text-primary"><?php echo number_format($book['price'], 0, ',', '.'); ?> đ</span>
                                        <?php else: ?>
                                        <span class="badge bg-success fs-6">Chỉ trao đổi</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <p class="card-text mt-3"><?php echo substr($book['description'], 0, 200); ?>...</p>
                                
                                <div class="mt-auto d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="me-3">
                                            <i class="fas fa-user-circle"></i> 
                                            <a href="pages/profile.php?id=<?php echo $book['user_id']; ?>" class="text-decoration-none">
                                                <?php echo $book['username']; ?>
                                            </a>
                                        </span>
                                        <span>
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <?php echo $book['city']; ?>
                                        </span>
                                    </div>
                                    <a href="pages/book_details.php?id=<?php echo $book['id']; ?>" class="btn btn-primary">Xem chi tiết</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Giới thiệu về nền tảng -->
<section class="about-platform mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="uploads/books/background1.jpg" alt="About BookSwap" class="img-fluid rounded-3 shadow">
            </div>
            <div class="col-lg-6">
                <div class="ps-lg-5">
                    <h2 class="fw-bold mb-4">Tại sao chọn BookSwap?</h2>
                    <p class="mb-4">BookSwap là nền tảng kết nối những người yêu sách, cho phép họ trao đổi và chia sẻ những cuốn sách đã đọc với nhau. Chúng tôi tin rằng mỗi cuốn sách đều xứng đáng có nhiều độc giả, và mỗi độc giả đều xứng đáng tiếp cận nhiều cuốn sách hơn.</p>
                    
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="me-3 text-primary"><i class="fas fa-check-circle fa-2x"></i></div>
                            <div>
                                <h5 class="mb-0">Tiết kiệm chi phí</h5>
                                <p class="mb-0 text-muted">Đọc nhiều sách hơn mà không tốn quá nhiều tiền</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="me-3 text-primary"><i class="fas fa-check-circle fa-2x"></i></div>
                            <div>
                                <h5 class="mb-0">Bảo vệ môi trường</h5>
                                <p class="mb-0 text-muted">Giảm thiểu rác thải và bảo vệ tài nguyên thiên nhiên</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="me-3 text-primary"><i class="fas fa-check-circle fa-2x"></i></div>
                            <div>
                                <h5 class="mb-0">Kết nối cộng đồng</h5>
                                <p class="mb-0 text-muted">Gặp gỡ và giao lưu với những người có cùng sở thích</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <div class="me-3 text-primary"><i class="fas fa-check-circle fa-2x"></i></div>
                            <div>
                                <h5 class="mb-0">Khám phá sách mới</h5>
                                <p class="mb-0 text-muted">Mở rộng sở thích và khám phá những cuốn sách mới mẻ</p>
                            </div>
                        </div>
                    </div>
                    
                    <a href="pages/about.php" class="btn btn-primary">Tìm hiểu thêm</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5 bg-primary text-white mb-5">
    <div class="container text-center">
        <h2 class="fw-bold mb-3">Bắt đầu trao đổi sách ngay hôm nay</h2>
        <p class="lead mb-4">Hàng nghìn cuốn sách đang chờ đợi bạn trong cộng đồng BookSwap</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="register.php" class="btn btn-light btn-lg">Đăng ký miễn phí</a>
            <a href="pages/how-it-works.php" class="btn btn-outline-light btn-lg">Tìm hiểu thêm</a>
        </div>
    </div>
</section>

<!-- CSS cho danh mục -->
<style>
.featured-categories {
    background-color: #f8f9fa;
}

.category-card {
    transition: all 0.3s ease;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
}

.category-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.category-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(0deg, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0) 50%);
    transition: all 0.3s ease;
}

.category-card:hover .category-overlay {
    background: linear-gradient(0deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.1) 70%);
}

.category-img-container {
    position: relative;
    border-bottom: 3px solid var(--bs-primary);
}

.btn-white {
    background-color: white;
    border: none;
    color: #0d6efd;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-white:hover {
    background-color: #0d6efd;
    color: white;
}

.btn-white:hover i {
    color: white !important;
}

.categories-scroll-container::-webkit-scrollbar {
    display: none;
}

.categories-wrapper {
    margin: 0 20px;
}

@media (max-width: 576px) {
    .category-item {
        min-width: 160px;
    }
    
    .category-img-container {
        height: 120px;
    }
}
</style>

<!-- Thêm JavaScript cho chức năng cuộn ngang -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.categories-scroll-container');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    
    // Ẩn nút trước ban đầu
    prevBtn.style.opacity = '0.5';
    
    // Kiểm tra nếu cần hiện/ẩn nút dựa trên kích thước container
    function checkScrollability() {
        if (container.scrollWidth <= container.clientWidth) {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
        } else {
            prevBtn.style.display = 'flex';
            nextBtn.style.display = 'flex';
            
            // Kiểm tra vị trí cuộn
            checkScrollPosition();
        }
    }
    
    // Hiện/ẩn nút dựa trên vị trí cuộn
    function checkScrollPosition() {
        const isAtStart = container.scrollLeft < 10;
        const isAtEnd = container.scrollLeft + container.clientWidth >= container.scrollWidth - 20;
        
        prevBtn.style.opacity = isAtStart ? '0.5' : '1';
        prevBtn.style.cursor = isAtStart ? 'default' : 'pointer';
        
        nextBtn.style.opacity = isAtEnd ? '0.5' : '1';
        nextBtn.style.cursor = isAtEnd ? 'default' : 'pointer';
    }
    
    // Kiểm tra trạng thái ban đầu
    checkScrollability();
    
    // Cập nhật khi kích thước thay đổi
    window.addEventListener('resize', checkScrollability);
    
    // Cập nhật khi cuộn
    container.addEventListener('scroll', checkScrollPosition);
    
    // Cuộn sang trái
    prevBtn.addEventListener('click', function() {
        const scrollAmount = Math.min(600, container.clientWidth * 0.8);
        container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    });
    
    // Cuộn sang phải
    nextBtn.addEventListener('click', function() {
        const scrollAmount = Math.min(600, container.clientWidth * 0.8);
        container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    });
    
    // Hỗ trợ cuộn bằng chuột
    container.addEventListener('wheel', function(e) {
        if (e.deltaY !== 0) {
            e.preventDefault();
            container.scrollBy({
                left: e.deltaY > 0 ? 100 : -100,
                behavior: 'smooth'
            });
        }
    }, { passive: false });
});
</script>
<?php
// Include footer
require_once 'includes/footer.php';
?>