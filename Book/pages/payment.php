<?php
// Khởi động session
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Include các file cần thiết
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Book.php';
require_once '../classes/Payment.php';
require_once '../classes/User.php';

// Khởi tạo đối tượng
$book = new Book();
$payment = new Payment();
$user = new User();

// Kiểm tra ID sách
if (!isset($_GET['book_id']) || !is_numeric($_GET['book_id'])) {
    header('Location: search.php');
    exit();
}

$book_id = $_GET['book_id'];
$book_info = $book->getById($book_id);

// Kiểm tra sách tồn tại và có thể mua
if (!$book_info || $book_info['exchange_type'] == 'exchange_only') {
    header('Location: search.php');
    exit();
}

// Xử lý thanh toán
$msg = '';
$msg_type = '';

// Lấy thông tin người dùng hiện tại
$current_user = $user->getUserById($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra các trường bắt buộc
    if (empty($_POST['fullname']) || empty($_POST['email']) || empty($_POST['phone']) || 
        empty($_POST['address']) || empty($_POST['payment_method'])) {
        $msg = 'Vui lòng điền đầy đủ thông tin bắt buộc!';
        $msg_type = 'danger';
    } else {
        // Lấy thông tin từ form
        $fullname = $_POST['fullname'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $district = $_POST['district'];
        $city = $_POST['city'];
        $note = $_POST['note'];
        $payment_method = $_POST['payment_method'];
        
        // Danh sách phương thức thanh toán hợp lệ
        $valid_methods = ['momo', 'bank_transfer', 'cod'];
        
        if (!in_array($payment_method, $valid_methods)) {
            $msg = 'Phương thức thanh toán không hợp lệ!';
            $msg_type = 'danger';
        } else {
            // Tạo yêu cầu thanh toán
            $payment_data = [
                'user_id' => $_SESSION['user_id'],
                'book_id' => $book_id,
                'amount' => $book_info['price'],
                'payment_method' => $payment_method,
                'shipping_info' => json_encode([
                    'fullname' => $fullname,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'district' => $district,
                    'city' => $city,
                    'note' => $note
                ])
            ];
            
            $transaction_code = $payment->createPaymentRequest($payment_data);
            
            if ($transaction_code) {
                // Chuyển hướng đến trang xác nhận thanh toán
                header("Location: payment_confirm.php?transaction_code={$transaction_code}");
                exit();
            } else {
                $msg = 'Có lỗi xảy ra khi tạo yêu cầu thanh toán!';
                $msg_type = 'danger';
            }
        }
    }
}

// Đặt code này sau phần kiểm tra đăng nhập và trước khi xử lý thanh toán
if (isset($_SESSION['user_id'])) {
    // Kiểm tra người dùng có bị khóa hoặc bị hạn chế không
    $user_id = $_SESSION['user_id'];
    $db = new Database();
    $db->query('SELECT can_buy, can_sell, suspended_until, is_blocked FROM users WHERE id = ?');
    $db->bind(1, $user_id);
    $user_status = $db->single();

    // Nếu tài khoản bị khóa
    if ($user_status['is_blocked']) {
        $_SESSION['error'] = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên để biết thêm chi tiết.';
        header('Location: index.php');
        exit();
    }

    // Nếu tài khoản bị tạm khóa
    if ($user_status['suspended_until'] && strtotime($user_status['suspended_until']) > time()) {
        $_SESSION['error'] = 'Tài khoản của bạn đang bị tạm khóa đến ' . date('d/m/Y H:i', strtotime($user_status['suspended_until'])) . '. Vui lòng thử lại sau.';
        header('Location: index.php');
        exit();
    }

    // Kiểm tra quyền mua sách
    if (!$user_status['can_buy']) {
        $_SESSION['error'] = 'Tài khoản của bạn đã bị hạn chế quyền mua sách. Vui lòng liên hệ quản trị viên để biết thêm chi tiết.';
        header('Location: index.php');
        exit();
    }
}

// Include header
require_once '../includes/header.php';
?>

<div class="container py-5">
    <h1 class="text-center mb-4">Thanh Toán</h1>
    
    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Thông tin sách -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Thông tin sách</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex mb-3">
                        <img src="../uploads/books/<?php echo $book_info['image']; ?>" 
                             alt="<?php echo $book_info['title']; ?>" 
                             class="img-thumbnail me-3" 
                             style="width: 100px; height: 150px; object-fit: cover;">
                        <div>
                            <h5 class="mb-2"><?php echo $book_info['title']; ?></h5>
                            <p class="text-muted mb-1">Tác giả: <?php echo $book_info['author']; ?></p>
                            <div class="mb-1">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php if($i <= $book_info['condition_rating']): ?>
                                        <i class="fas fa-star text-warning"></i>
                                    <?php else: ?>
                                        <i class="far fa-star text-warning"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Giá sách:</span>
                            <span class="fw-bold"><?php echo number_format($book_info['price'], 0, ',', '.'); ?> đ</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span>Phí vận chuyển:</span>
                            <span class="fw-bold">20.000 đ</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Tổng cộng:</span>
                            <span class="fw-bold h5 mb-0 text-danger"><?php echo number_format($book_info['price'] + 20000, 0, ',', '.'); ?> đ</span>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <a href="book_details.php?id=<?php echo $book_info['id']; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại thông tin sách
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Thông tin người bán -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Thông tin người bán</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <img src="../uploads/users/<?php echo $book_info['profile_image'] ? $book_info['profile_image'] : 'default.jpg'; ?>" 
                             alt="<?php echo $book_info['username']; ?>" 
                             class="rounded-circle me-3"
                             style="width: 50px; height: 50px; object-fit: cover;">
                        <div>
                            <h6 class="mb-0"><?php echo $book_info['username']; ?></h6>
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $book_info['rating']): ?>
                                <i class="fas fa-star text-warning small"></i>
                                <?php elseif ($i - 0.5 <= $book_info['rating']): ?>
                                <i class="fas fa-star-half-alt text-warning small"></i>
                                <?php else: ?>
                                <i class="far fa-star text-warning small"></i>
                                <?php endif; ?>
                                <?php endfor; ?>
                                <span class="small ms-1"><?php echo number_format($book_info['rating'], 1); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php if ($book_info['user_address']): ?>
                    <p class="small mb-0"><i class="fas fa-map-marker-alt me-2"></i><?php echo $book_info['user_address']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Form thanh toán -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Thông tin thanh toán và giao hàng</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <h5 class="border-bottom pb-2 mb-3">Thông tin người nhận</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="fullname" class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="fullname" name="fullname" required
                                       value="<?php echo isset($_POST['fullname']) ? $_POST['fullname'] : $current_user['full_name']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required
                                       value="<?php echo isset($_POST['email']) ? $_POST['email'] : $current_user['email']; ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" required
                                       value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : $current_user['phone']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="city" class="form-label">Tỉnh/Thành phố <span class="text-danger">*</span></label>
                                <select class="form-select" id="city" name="city" required>
                                    <option value="">Chọn Tỉnh/Thành phố</option>
                                    <option value="Hà Nội" <?php echo (isset($_POST['city']) && $_POST['city'] == 'Hà Nội') ? 'selected' : ''; ?>>Hà Nội</option>
                                    <option value="Hồ Chí Minh" <?php echo (isset($_POST['city']) && $_POST['city'] == 'Hồ Chí Minh') ? 'selected' : ''; ?>>Hồ Chí Minh</option>
                                    <option value="Đà Nẵng" <?php echo (isset($_POST['city']) && $_POST['city'] == 'Đà Nẵng') ? 'selected' : ''; ?>>Đà Nẵng</option>
                                    <option value="Cần Thơ" <?php echo (isset($_POST['city']) && $_POST['city'] == 'Cần Thơ') ? 'selected' : ''; ?>>Cần Thơ</option>
                                    <option value="Hải Phòng" <?php echo (isset($_POST['city']) && $_POST['city'] == 'Hải Phòng') ? 'selected' : ''; ?>>Hải Phòng</option>
                                    <option value="Nha Trang" <?php echo (isset($_POST['city']) && is_array($_POST['city']) && in_array('Nha Trang', $_POST['city'])) ? 'selected' : ''; ?>>Nha Trang</option>
                                    <option value="Huế" <?php echo (isset($_POST['city']) && is_array($_POST['city']) && in_array('Huế', $_POST['city'])) ? 'selected' : ''; ?>>Huế</option>
                                    <option value="Bình Dương" <?php echo (isset($_POST['city']) && is_array($_POST['city']) && in_array('Bình Dương', $_POST['city'])) ? 'selected' : ''; ?>>Bình Dương</option>
                                    <option value="Đồng Nai" <?php echo (isset($_POST['city']) && is_array($_POST['city']) && in_array('Đồng Nai', $_POST['city'])) ? 'selected' : ''; ?>>Đồng Nai</option>
                                    <option value="Khánh Hòa" <?php echo (isset($_POST['city']) && is_array($_POST['city']) && in_array('Khánh Hòa', $_POST['city'])) ? 'selected' : ''; ?>>Khánh Hòa</option>
                                    <option value="Long An" <?php echo (isset($_POST['city']) && is_array($_POST['city']) && in_array('Long An', $_POST['city'])) ? 'selected' : ''; ?>>Long An</option>
                                    <option value="Tiền Giang" <?php echo (isset($_POST['city']) && is_array($_POST['city']) && in_array('Tiền Giang', $_POST['city'])) ? 'selected' : ''; ?>>Tiền Giang</option>
                                    <option value="Bà Rịa - Vũng Tàu" <?php echo (isset($_POST['city']) && is_array($_POST['city']) && in_array('Bà Rịa - Vũng Tàu', $_POST['city'])) ? 'selected' : ''; ?>>Bà Rịa - Vũng Tàu</option>
                                    <option value="Bắc Ninh" <?php echo (isset($_POST['city']) && is_array($_POST['city']) && in_array('Bắc Ninh', $_POST['city'])) ? 'selected' : ''; ?>>Bắc Ninh</option>
                                    <option value="Nam Định" <?php echo (isset($_POST['city']) && is_array($_POST['city']) && in_array('Nam Định', $_POST['city'])) ? 'selected' : ''; ?>>Nam Định</option>
                                    <option value="Thái Nguyên" <?php echo (isset($_POST['city']) && is_array($_POST['city']) && in_array('Thái Nguyên', $_POST['city'])) ? 'selected' : ''; ?>>Thái Nguyên</option>
                                    <option value="Ninh Bình" <?php echo (isset($_POST['city']) && is_array($_POST['city']) && in_array('Ninh Bình', $_POST['city'])) ? 'selected' : ''; ?>>Ninh Bình</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="district" class="form-label">Quận/Huyện <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="district" name="district" required
                                       value="<?php echo isset($_POST['district']) ? $_POST['district'] : ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="address" class="form-label">Địa chỉ chi tiết <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="address" name="address" required
                                       value="<?php echo isset($_POST['address']) ? $_POST['address'] : $current_user['address']; ?>"
                                       placeholder="Số nhà, đường, phường/xã">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="note" class="form-label">Ghi chú</label>
                            <textarea class="form-control" id="note" name="note" rows="2" 
                                      placeholder="Ghi chú về việc giao hàng, địa điểm, thời gian..."><?php echo isset($_POST['note']) ? $_POST['note'] : ''; ?></textarea>
                        </div>
                        
                        <h5 class="border-bottom pb-2 mb-3">Phương thức thanh toán</h5>
                        <div class="mb-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="payment_method" id="momo" value="momo" required
                                           <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'momo') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3" for="momo">
                                        <i class="fas fa-wallet fa-2x text-danger mb-2"></i>
                                        <span>Ví MoMo</span>
                                        <small class="text-muted">Thanh toán qua ví điện tử MoMo</small>
                                    </label>
                                </div>
                                
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="payment_method" id="bank_transfer" value="bank_transfer"
                                           <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'bank_transfer') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3" for="bank_transfer">
                                        <i class="fas fa-university fa-2x mb-2"></i>
                                        <span>Chuyển khoản</span>
                                        <small class="text-muted">Chuyển khoản ngân hàng</small>
                                    </label>
                                </div>
                                
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="payment_method" id="cod" value="cod"
                                           <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'cod') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3" for="cod">
                                        <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                                        <span>Tiền mặt</span>
                                        <small class="text-muted">Thanh toán khi nhận hàng</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning" role="alert">
                            <div class="d-flex">
                                <i class="fas fa-exclamation-triangle me-2 mt-1"></i>
                                <div>
                                    <strong>Lưu ý:</strong> Sau khi đặt hàng, người bán sẽ liên hệ với bạn 
                                    để xác nhận đơn hàng trước khi giao sách.
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check-circle me-2"></i> Xác nhận đặt hàng
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Hiển thị label phương thức thanh toán đã chọn
    document.addEventListener('DOMContentLoaded', function() {
        // Xác thực số điện thoại
        const phoneInput = document.getElementById('phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }

        // Thêm validator cho form nếu cần
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(event) {
                // Thêm validation nếu cần
            });
        }
    });
</script>

<style>
    .btn-check:checked + .btn-outline-primary {
        background-color: #0d6efd;
        color: white;
    }
    
    .form-label span.text-danger {
        font-weight: bold;
    }
</style>

<?php
// Include footer
require_once '../includes/footer.php';
?>