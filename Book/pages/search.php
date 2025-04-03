<?php
// Khởi động session
session_start();

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

// Lấy các tham số tìm kiếm
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_id = isset($_GET['category']) ? $_GET['category'] : '';
$condition = isset($_GET['condition']) ? $_GET['condition'] : '';
$exchange_type = isset($_GET['exchange_type']) ? $_GET['exchange_type'] : '';
$price_min = isset($_GET['price_min']) ? $_GET['price_min'] : '';
$price_max = isset($_GET['price_max']) ? $_GET['price_max'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Lấy danh sách danh mục
$categories = $category->getAll();

// Tìm kiếm sách
if (!empty($search_query) || !empty($category_id) || !empty($condition) || !empty($exchange_type) || !empty($price_min) || !empty($price_max)) {
    $search_results = $book->search(
        $search_query,
        $category_id,
        $condition,
        $exchange_type,
        $price_min,
        $price_max,
        $sort
    );
} else {
    // Nếu không có tham số tìm kiếm, lấy tất cả sách có sẵn
    $search_results = $book->getAllAvailable($sort);
}

// Include header
require_once '../includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Tìm kiếm sách</h1>
    
    <div class="row">
        <!-- Bộ lọc bên trái -->
        <div class="col-lg-3">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Bộ lọc</h5>
                </div>
                <div class="card-body">
                    <form action="" method="get">
                        <div class="mb-3">
                            <label for="q" class="form-label">Từ khóa</label>
                            <input type="text" class="form-control" id="q" name="q" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Tiêu đề, tác giả...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Danh mục</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Tất cả danh mục</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo $cat['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="condition" class="form-label">Tình trạng sách</label>
                            <select class="form-select" id="condition" name="condition">
                                <option value="">Tất cả</option>
                                <option value="5" <?php echo $condition == 5 ? 'selected' : ''; ?>>Như mới (5 sao)</option>
                                <option value="4" <?php echo $condition == 4 ? 'selected' : ''; ?>>Rất tốt (4 sao)</option>
                                <option value="3" <?php echo $condition == 3 ? 'selected' : ''; ?>>Tốt (3 sao)</option>
                                <option value="2" <?php echo $condition == 2 ? 'selected' : ''; ?>>Khá (2 sao)</option>
                                <option value="1" <?php echo $condition == 1 ? 'selected' : ''; ?>>Đã qua sử dụng nhiều (1 sao)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="exchange_type" class="form-label">Hình thức</label>
                            <select class="form-select" id="exchange_type" name="exchange_type">
                                <option value="">Tất cả</option>
                                <option value="exchange_only" <?php echo $exchange_type == 'exchange_only' ? 'selected' : ''; ?>>Chỉ trao đổi</option>
                                <option value="sell_only" <?php echo $exchange_type == 'sell_only' ? 'selected' : ''; ?>>Chỉ bán</option>
                                <option value="both" <?php echo $exchange_type == 'both' ? 'selected' : ''; ?>>Trao đổi hoặc bán</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Khoảng giá</label>
                            <div class="row g-2">
                                <div class="col">
                                    <input type="number" class="form-control" name="price_min" placeholder="Từ" value="<?php echo $price_min; ?>">
                                </div>
                                <div class="col">
                                    <input type="number" class="form-control" name="price_max" placeholder="Đến" value="<?php echo $price_max; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="sort" class="form-label">Sắp xếp theo</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                                <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Giá tăng dần</option>
                                <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Giá giảm dần</option>
                                <option value="condition" <?php echo $sort == 'condition' ? 'selected' : ''; ?>>Tình trạng tốt nhất</option>
                                <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Phổ biến nhất</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                            <a href="search.php" class="btn btn-outline-secondary">Xóa bộ lọc</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Kết quả tìm kiếm -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="mb-0">Kết quả tìm kiếm <?php echo !empty($search_query) ? 'cho "' . htmlspecialchars($search_query) . '"' : ''; ?></h5>
                    <p class="text-muted mb-0">Tìm thấy <?php echo count($search_results); ?> kết quả</p>
                </div>
                <div class="d-flex align-items-center">
                    <label for="sortMobile" class="me-2 d-none d-sm-block">Sắp xếp theo:</label>
                    <select class="form-select form-select-sm" id="sortMobile" onchange="updateSort(this.value)">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                        <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Giá tăng dần</option>
                        <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Giá giảm dần</option>
                        <option value="condition" <?php echo $sort == 'condition' ? 'selected' : ''; ?>>Tình trạng tốt nhất</option>
                        <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Phổ biến nhất</option>
                    </select>
                </div>
            </div>
            
            <?php if (count($search_results) > 0): ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                <?php foreach ($search_results as $book): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm book-card">
                        <div class="position-relative">
                            <a href="book_details.php?id=<?php echo $book['id']; ?>">
                                <img src="../uploads/books/<?php echo $book['image']; ?>" class="card-img-top" alt="<?php echo $book['title']; ?>" style="height: 200px; object-fit: cover;">
                            </a>
                            <?php if($book['exchange_type'] == 'both'): ?>
                            <span class="position-absolute top-0 start-0 badge bg-primary m-2">Trao đổi/Mua</span>
                            <?php elseif($book['exchange_type'] == 'exchange_only'): ?>
                            <span class="position-absolute top-0 start-0 badge bg-success m-2">Chỉ trao đổi</span>
                            <?php else: ?>
                            <span class="position-absolute top-0 start-0 badge bg-warning text-dark m-2">Chỉ bán</span>
                            <?php endif; ?>
                            
                            <?php if(isset($_SESSION['user_id'])): ?>
                            <button class="btn btn-sm position-absolute top-0 end-0 m-2 btn-outline-danger rounded-circle p-2 toggle-wishlist <?php echo $user->isInWishlist($_SESSION['user_id'], $book['id']) ? 'active' : ''; ?>" data-book-id="<?php echo $book['id']; ?>">
                                <i class="<?php echo $user->isInWishlist($_SESSION['user_id'], $book['id']) ? 'fas' : 'far'; ?> fa-heart"></i>
                            </button>
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
                        <div class="card-footer bg-white border-top-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-user-circle"></i> <?php echo $book['username']; ?>
                                </small>
                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($book['created_at'])); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i> Không tìm thấy sách phù hợp với tiêu chí tìm kiếm. Vui lòng thử lại với tiêu chí khác.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Cập nhật sắp xếp
    function updateSort(value) {
        const url = new URL(window.location);
        url.searchParams.set('sort', value);
        window.location.href = url.toString();
    }
    
    // Toggle wishlist
    document.addEventListener('DOMContentLoaded', function() {
        const wishlistButtons = document.querySelectorAll('.toggle-wishlist');
        
        wishlistButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const bookId = this.dataset.bookId;
                const button = this;
                
                // Gửi yêu cầu AJAX
                fetch('../api/toggle_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `book_id=${bookId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        if (data.action === 'added') {
                            button.classList.add('active');
                            button.innerHTML = '<i class="fas fa-heart"></i>';
                        } else {
                            button.classList.remove('active');
                            button.innerHTML = '<i class="far fa-heart"></i>';
                        }
                    } else if (data.status === 'error' && data.message === 'not_logged_in') {
                        window.location.href = '../login.php?redirect=' + encodeURIComponent(window.location.href);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
    });
    
    // Hàm hiển thị gợi ý
function displaySuggestions(suggestions) {
    suggestionsContainer.innerHTML = '';
    
    if (suggestions.length === 0) {
        suggestionsContainer.innerHTML = '<div class="no-suggestions">Không tìm thấy sách</div>';
        suggestionsContainer.style.display = 'block';
        return;
    }

    suggestions.forEach(book => {
        const suggestionItem = document.createElement('div');
        suggestionItem.classList.add('suggestion-item');
        
        suggestionItem.innerHTML = `
            <img src="../uploads/books/${book.image}" alt="${book.title}" class="suggestion-image">
            <div class="suggestion-details">
                <h6 class="suggestion-title">${book.title}</h6>
                <p class="suggestion-author">${book.author}</p>
                <div class="suggestion-meta">
                    ${book.category_name ? `<span class="badge bg-info">${book.category_name}</span>` : ''}
                    ${book.exchange_type !== 'exchange_only' 
                        ? `<span class="suggestion-price">${new Intl.NumberFormat('vi-VN').format(book.price)} đ</span>` 
                        : '<span class="badge bg-success">Trao đổi</span>'}
                </div>
            </div>
        `;

        suggestionItem.addEventListener('click', function() {
            window.location.href = `book_details.php?id=${book.id}`;
        });

        suggestionsContainer.appendChild(suggestionItem);
    });

    suggestionsContainer.style.display = 'block';
}
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>