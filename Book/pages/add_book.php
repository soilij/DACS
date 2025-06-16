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

// Lấy danh sách danh mục
$categories = $category->getAll();

$current_user = $user->getById($_SESSION['user_id']);
if ($current_user['can_sell'] == 0) {
    $msg = 'Tài khoản của bạn đã bị hạn chế quyền đăng bán/trao đổi sách. Vui lòng liên hệ quản trị viên để biết thêm chi tiết.';
    $msg_type = 'danger';
}

// Xử lý thêm sách
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra các trường bắt buộc
    if (empty($_POST['title']) || empty($_POST['author']) || empty($_POST['description']) || empty($_POST['category_id']) || empty($_POST['condition_rating']) || empty($_POST['exchange_type'])) {
        $msg = 'Vui lòng điền đầy đủ thông tin!';
        $msg_type = 'danger';
    } elseif ($_FILES['image']['error'] != 0) {
        $msg = 'Vui lòng chọn ảnh bìa sách!';
        $msg_type = 'danger';
    } elseif ($_POST['exchange_type'] !== 'exchange_only' && (empty($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] <= 0)) {
        $msg = 'Vui lòng nhập giá hợp lệ!';
        $msg_type = 'danger';
    } elseif ($current_user['can_sell'] == 0) {
        // Không cho phép thêm sách
        echo "Tài khoản của bạn đã bị hạn chế quyền đăng bán/trao đổi sách.";
        exit();
    } else {
        // Xử lý upload ảnh
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['image']['name'];
        $file_size = $_FILES['image']['size'];
        $file_tmp = $_FILES['image']['tmp_name'];
        
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_ext)) {
            if ($file_size <= 5242880) { // 5MB
                $new_file_name = uniqid('book_') . '.' . $file_ext;
                $upload_path = '../uploads/books/' . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // Chuẩn bị dữ liệu sách
                    $data = [
                        'title' => $_POST['title'],
                        'author' => $_POST['author'],
                        'description' => $_POST['description'],
                        'condition_rating' => $_POST['condition_rating'],
                        'isbn' => $_POST['isbn'] ?? '',
                        'image' => $new_file_name,
                        'category_id' => $_POST['category_id'],
                        'user_id' => $_SESSION['user_id'],
                        'exchange_type' => $_POST['exchange_type'],
                        'price' => $_POST['exchange_type'] === 'exchange_only' ? 0 : $_POST['price'],
                        'status' => 'pending_approval' // Chờ admin duyệt
                    ];
                    
                    // Thêm sách vào database
                    $book_id = $book->add($data);
                    
                    if ($book_id) {
                        // Thêm thành công
                        $msg = 'Sách đã được đăng thành công và đang chờ duyệt!';
                        $msg_type = 'success';
                        
                        // Xử lý ảnh bổ sung (nếu có)
                        if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
                            for ($i = 0; $i < count($_FILES['additional_images']['name']); $i++) {
                                if ($_FILES['additional_images']['error'][$i] == 0) {
                                    $add_file_name = $_FILES['additional_images']['name'][$i];
                                    $add_file_size = $_FILES['additional_images']['size'][$i];
                                    $add_file_tmp = $_FILES['additional_images']['tmp_name'][$i];
                                    $add_file_ext = strtolower(pathinfo($add_file_name, PATHINFO_EXTENSION));
                                    
                                    if (in_array($add_file_ext, $allowed_ext) && $add_file_size <= 5242880) {
                                        $add_new_file_name = uniqid('book_add_') . '.' . $add_file_ext;
                                        $add_upload_path = '../uploads/books/' . $add_new_file_name;
                                        
                                        if (move_uploaded_file($add_file_tmp, $add_upload_path)) {
                                            // Thêm vào bảng book_images
                                            $book->addImage($book_id, $add_new_file_name, false);
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Chuyển hướng sau 3 giây
                        header('refresh:3;url=my_books.php');
                    } else {
                        $msg = 'Có lỗi xảy ra khi đăng sách!';
                        $msg_type = 'danger';
                    }
                } else {
                    $msg = 'Không thể tải ảnh lên!';
                    $msg_type = 'danger';
                }
            } else {
                $msg = 'Kích thước file quá lớn! Tối đa 5MB.';
                $msg_type = 'danger';
            }
        } else {
            $msg = 'Định dạng file không được hỗ trợ! Chỉ chấp nhận JPG, PNG và GIF.';
            $msg_type = 'danger';
        }
    }
}

// Include header
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Đăng sách mới</h4>
                </div>
                <div class="card-body">
                    <?php if ($msg): ?>
                    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($current_user['can_sell'] != 0): ?>
                    <form method="post" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="title" class="form-label">Tiêu đề sách <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="col-md-6">
                                <label for="author" class="form-label">Tác giả <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="author" name="author" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                            <div class="form-text">Hãy mô tả chi tiết về nội dung, tình trạng và các đặc điểm nổi bật của sách.</div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Danh mục <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Chọn danh mục</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="isbn" class="form-label">Mã ISBN</label>
                                <input type="text" class="form-control" id="isbn" name="isbn">
                                <div class="form-text">Mã ISBN thường được in ở mặt sau sách (nếu có).</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="condition_rating" class="form-label">Tình trạng sách <span class="text-danger">*</span></label>
                                <select class="form-select condition-rating" id="condition_rating" name="condition_rating" required>
                                    <option value="">Chọn tình trạng</option>
                                    <option value="5">Như mới (5 sao)</option>
                                    <option value="4">Rất tốt (4 sao)</option>
                                    <option value="3">Tốt (3 sao)</option>
                                    <option value="2">Khá (2 sao)</option>
                                    <option value="1">Đã qua sử dụng nhiều (1 sao)</option>
                                </select>
                                <div class="stars-container mt-2">
                                    <i class="far fa-star" data-rating="1"></i>
                                    <i class="far fa-star" data-rating="2"></i>
                                    <i class="far fa-star" data-rating="3"></i>
                                    <i class="far fa-star" data-rating="4"></i>
                                    <i class="far fa-star" data-rating="5"></i>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="exchange_type" class="form-label">Hình thức <span class="text-danger">*</span></label>
                                <select class="form-select" id="exchange_type" name="exchange_type" required>
                                    <option value="">Chọn hình thức</option>
                                    <option value="exchange_only">Chỉ trao đổi</option>
                                    <option value="sell_only">Chỉ bán</option>
                                    <option value="both">Trao đổi hoặc bán</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="price-container" style="display: none;">
                            <label for="price" class="form-label">Giá bán <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="price" name="price" min="1000" step="1000">
                                <span class="input-group-text">VNĐ</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Ảnh bìa sách <span class="text-danger">*</span></label>
                            <input class="form-control" type="file" id="image" name="image" accept="image/*" required onchange="previewImage(this, 'mainImagePreview')">
                            <div class="form-text">Chọn ảnh bìa sách. Kích thước tối đa 5MB.</div>
                            <div id="mainImagePreview" class="mt-2" style="display: none;">
                                <img src="" alt="Preview" class="img-thumbnail" style="max-height: 200px;">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="additional_images" class="form-label">Ảnh bổ sung (tối đa 3 ảnh)</label>
                            <input class="form-control" type="file" id="additional_images" name="additional_images[]" accept="image/*" multiple onchange="previewAdditionalImages(this)">
                            <div class="form-text">Chọn các ảnh khác của sách nếu có (các trang quan trọng, chi tiết đặc biệt, v.v.). Kích thước tối đa 5MB mỗi ảnh.</div>
                            <div id="additionalImagePreview" class="mt-2 d-flex flex-wrap gap-2">
                                <!-- Hiển thị các ảnh xem trước -->
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Đăng sách</button>
                            <a href="../index.php" class="btn btn-outline-secondary">Hủy bỏ</a>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Hiển thị/ẩn trường giá
    document.getElementById('exchange_type').addEventListener('change', function() {
        const priceContainer = document.getElementById('price-container');
        const priceInput = document.getElementById('price');
        
        if (this.value === 'exchange_only') {
            priceContainer.style.display = 'none';
            priceInput.required = false;
        } else {
            priceContainer.style.display = 'block';
            priceInput.required = true;
        }
    });
    
    // Xem trước ảnh chính
    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        const previewImg = preview.querySelector('img');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            }
            
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.style.display = 'none';
        }
    }
    
    // Xem trước các ảnh bổ sung
    function previewAdditionalImages(input) {
        const preview = document.getElementById('additionalImagePreview');
        preview.innerHTML = '';
        
        if (input.files) {
            const maxFiles = Math.min(input.files.length, 3); // Tối đa 3 ảnh
            
            for (let i = 0; i < maxFiles; i++) {
                const reader = new FileReader();
                const file = input.files[i];
                
                reader.onload = function(e) {
                    const imgContainer = document.createElement('div');
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail';
                    img.style.height = '150px';
                    imgContainer.appendChild(img);
                    preview.appendChild(imgContainer);
                }
                
                reader.readAsDataURL(file);
            }
        }
    }
    
    // Xử lý đánh giá bằng sao
    document.querySelector('.condition-rating').addEventListener('change', function() {
        const value = this.value;
        const stars = document.querySelectorAll('.stars-container i');
        
        stars.forEach(star => {
            star.className = parseInt(star.dataset.rating) <= value ? 'fas fa-star text-warning' : 'far fa-star text-warning';
        });
    });
    
    document.querySelectorAll('.stars-container i').forEach(star => {
        star.addEventListener('click', function() {
            const value = this.dataset.rating;
            document.querySelector('.condition-rating').value = value;
            
            const stars = document.querySelectorAll('.stars-container i');
            stars.forEach(s => {
                s.className = parseInt(s.dataset.rating) <= value ? 'fas fa-star text-warning' : 'far fa-star text-warning';
            });
        });
    });
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>