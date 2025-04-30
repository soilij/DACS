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
require_once '../classes/Payment.php';
require_once '../classes/Book.php';
require_once '../classes/User.php';
require_once '../classes/Notification.php';

// Khởi tạo đối tượng
$payment = new Payment();
$book = new Book();
$user = new User();
$notification = new Notification();

// Kiểm tra mã giao dịch
if (!isset($_GET['transaction_code'])) {
    header('Location: search.php');
    exit();
}

$transaction_code = $_GET['transaction_code'];
$payment_info = $payment->getPaymentByTransactionCode($transaction_code);

// Kiểm tra thông tin thanh toán tồn tại
if (!$payment_info) {
    $_SESSION['error'] = 'Không tìm thấy thông tin giao dịch!';
    header('Location: search.php');
    exit();
}

// Lấy thông tin sách
$book_info = $book->getById($payment_info['book_id']);

// Lấy thông tin người bán
$seller = $user->getUserById($book_info['user_id']);

// Lấy thông tin giao hàng
$shipping_info = json_decode($payment_info['shipping_info'] ?? '{}', true);

// Xử lý khi nhấn nút đã thanh toán (chỉ áp dụng cho MoMo và chuyển khoản)
if (isset($_POST['confirm_paid']) && in_array($payment_info['payment_method'], ['momo', 'bank_transfer'])) {
    // Cập nhật trạng thái thanh toán thành "đã thanh toán"
    if ($payment->updatePaymentStatus($transaction_code, 'pending')) {
        // Gửi thông báo cho người bán
        $notification_data = [
            'user_id' => $book_info['user_id'],
            'message' => 'Bạn có đơn hàng mới đang chờ xác nhận thanh toán cho sách "' . $book_info['title'] . '"',
            'link' => 'pages/seller_orders.php'
        ];
        $notification->create($notification_data);
        
        // Chuyển hướng đến trang cảm ơn
        header('Location: payment_success.php?transaction_code=' . $transaction_code);
        exit();
    }
}

// Xử lý khi hủy đơn hàng
if (isset($_POST['cancel_order'])) {
    // Cập nhật trạng thái thanh toán thành "đã hủy"
    if ($payment->updatePaymentStatus($transaction_code, 'cancelled')) {
        // Chuyển hướng đến trang tìm kiếm
        $_SESSION['success'] = 'Đơn hàng đã được hủy thành công.';
        header('Location: search.php');
        exit();
    }
}

// Include header
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0 text-center">
                        <i class="fas fa-check-circle me-2"></i> Xác nhận đơn hàng
                    </h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="h1 text-success mb-3">
                            <i class="fas fa-shopping-cart fa-2x"></i>
                        </div>
                        <h4>Cảm ơn bạn đã đặt hàng!</h4>
                        <p class="text-muted">Mã đơn hàng: <strong><?php echo $transaction_code; ?></strong></p>
                    </div>
                    
                    <!-- Thông tin đơn hàng -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Thông tin đơn hàng</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex mb-3">
                                <img src="../uploads/books/<?php echo $book_info['image']; ?>" alt="<?php echo $book_info['title']; ?>" 
                                     class="img-thumbnail me-3" style="width: 80px; height: 120px; object-fit: cover;">
                                <div>
                                    <h5 class="mb-1"><?php echo $book_info['title']; ?></h5>
                                    <p class="text-muted mb-1">Tác giả: <?php echo $book_info['author']; ?></p>
                                    <div class="mb-2">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <?php if($i <= $book_info['condition_rating']): ?>
                                                <i class="fas fa-star text-warning small"></i>
                                            <?php else: ?>
                                                <i class="far fa-star text-warning small"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="fw-bold mb-0"><?php echo number_format($book_info['price'], 0, ',', '.'); ?> đ</p>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mb-0">
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
                                    <span class="fw-bold h5 mb-0 text-danger"><?php echo number_format($payment_info['amount'] + 20000, 0, ',', '.'); ?> đ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thông tin giao hàng -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Thông tin giao hàng</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Người nhận:</strong> <?php echo $shipping_info['fullname'] ?? ''; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Số điện thoại:</strong> <?php echo $shipping_info['phone'] ?? ''; ?></p>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Email:</strong> <?php echo $shipping_info['email'] ?? ''; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Địa chỉ:</strong> <?php echo ($shipping_info['address'] ?? '') . ', ' . 
                                                   ($shipping_info['district'] ?? '') . ', ' . 
                                                   ($shipping_info['city'] ?? ''); ?></p>
                                </div>
                            </div>
                            <?php if (!empty($shipping_info['note'])): ?>
                            <div class="mt-2">
                                <p class="mb-0"><strong>Ghi chú:</strong> <?php echo $shipping_info['note']; ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Thông tin thanh toán -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Phương thức thanh toán</h5>
                        </div>
                        <div class="card-body">
                            <?php if($payment_info['payment_method'] == 'momo'): ?>
                                <div class="text-center mb-4">
                                    <h5 class="mb-3">Quét mã QR để thanh toán qua MoMo</h5>
                                    <img src="../assets/images/momo_qr_example.png" alt="MoMo QR Code" class="img-fluid mb-3" style="max-width: 250px;">
                                    <div class="alert alert-info">
                                        <p class="mb-1">Số tiền: <strong><?php echo number_format($payment_info['amount'] + 20000, 0, ',', '.'); ?> đ</strong></p>
                                        <p class="mb-1">Nội dung chuyển khoản: <strong><?php echo $transaction_code; ?></strong></p>
                                        <p class="mb-0 small">Sau khi quét mã và thanh toán thành công, hãy nhấn "Tôi đã thanh toán"</p>
                                    </div>
                                </div>
                            <?php elseif($payment_info['payment_method'] == 'bank_transfer'): ?>
                                <div class="text-center mb-4">
                                    <h5 class="mb-3">Thông tin chuyển khoản ngân hàng</h5>
                                    <div class="alert alert-info">
                                        <p class="mb-1">Ngân hàng: <strong>Vietcombank</strong></p>
                                        <p class="mb-1">Số tài khoản: <strong>1234567890</strong></p>
                                        <p class="mb-1">Chủ tài khoản: <strong>CÔNG TY TNHH BOOKSWAP</strong></p>
                                        <p class="mb-1">Số tiền: <strong><?php echo number_format($payment_info['amount'] + 20000, 0, ',', '.'); ?> đ</strong></p>
                                        <p class="mb-1">Nội dung chuyển khoản: <strong><?php echo $transaction_code; ?></strong></p>
                                        <p class="mb-0 small">Sau khi chuyển khoản thành công, hãy nhấn "Tôi đã thanh toán"</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-info-circle fa-2x me-3"></i>
                                        <div>
                                            <h5 class="mb-1">Thanh toán khi nhận hàng (COD)</h5>
                                            <p class="mb-0">Bạn sẽ thanh toán số tiền <strong><?php echo number_format($payment_info['amount'] + 20000, 0, ',', '.'); ?> đ</strong> khi nhận được sách.</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Nút xác nhận -->
                    <div class="d-flex justify-content-between">
                        <form method="post" action="">
                            <button type="submit" name="cancel_order" class="btn btn-outline-danger" 
                                    onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?');">
                                <i class="fas fa-times-circle me-2"></i> Hủy đơn hàng
                            </button>
                        </form>
                        
                        <?php if(in_array($payment_info['payment_method'], ['momo', 'bank_transfer'])): ?>
                        <form method="post" action="">
                            <button type="submit" name="confirm_paid" class="btn btn-success">
                                <i class="fas fa-check-circle me-2"></i> Tôi đã thanh toán
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="post" action="">
                            <button type="submit" name="confirm_cod" class="btn btn-primary">
                                <i class="fas fa-check-circle me-2"></i> Xác nhận đặt hàng COD
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Thông tin người bán -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Thông tin người bán</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="../uploads/users/<?php echo $seller['profile_image'] ? $seller['profile_image'] : 'default.jpg'; ?>" 
                             alt="<?php echo $seller['username']; ?>" 
                             class="rounded-circle me-3"
                             style="width: 60px; height: 60px; object-fit: cover;">
                        <div>
                            <h5 class="mb-1"><?php echo $seller['full_name']; ?></h5>
                            <p class="text-muted mb-1">@<?php echo $seller['username']; ?></p>
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $seller['rating']): ?>
                                <i class="fas fa-star text-warning"></i>
                                <?php elseif ($i - 0.5 <= $seller['rating']): ?>
                                <i class="fas fa-star-half-alt text-warning"></i>
                                <?php else: ?>
                                <i class="far fa-star text-warning"></i>
                                <?php endif; ?>
                                <?php endfor; ?>
                                <span class="ms-1"><?php echo number_format($seller['rating'], 1); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>