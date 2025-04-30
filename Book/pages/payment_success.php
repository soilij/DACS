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

// Khởi tạo đối tượng
$payment = new Payment();
$book = new Book();

// Kiểm tra mã giao dịch
if (!isset($_GET['transaction_code'])) {
    header('Location: search.php');
    exit();
}

$transaction_code = $_GET['transaction_code'];
$payment_info = $payment->getPaymentByTransactionCode($transaction_code);

// Kiểm tra thông tin thanh toán
if (!$payment_info) {
    $_SESSION['error'] = 'Không tìm thấy thông tin giao dịch!';
    header('Location: search.php');
    exit();
}

// Lấy thông tin sách
$book_info = $book->getById($payment_info['book_id']);

// Cập nhật trạng thái thanh toán nếu là COD
if ($payment_info['payment_method'] == 'cod' && $payment_info['status'] == 'pending') {
    $payment->updatePaymentStatus($transaction_code, 'processing');
}

// Lấy thông tin giao hàng
$shipping_info = json_decode($payment_info['shipping_info'] ?? '{}', true);

// Đặt biến $is_detail_page để header biết đang ở trang chi tiết
$is_detail_page = true;

// Include header
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    
                    <h2 class="mb-4">Cảm ơn bạn đã đặt hàng!</h2>
                    
                    <p class="mb-2">Đơn hàng của bạn đã được tiếp nhận và đang được xử lý.</p>
                    <p class="mb-4">Mã đơn hàng: <strong><?php echo $transaction_code; ?></strong></p>
                    
                    <?php if ($payment_info['status'] == 'paid'): ?>
                    <div class="alert alert-success d-inline-block mx-auto mb-4">
                        <p class="mb-0">Đơn hàng của bạn đã được thanh toán thành công!</p>
                    </div>
                    <?php elseif ($payment_info['payment_method'] == 'cod'): ?>
                    <div class="alert alert-info d-inline-block mx-auto mb-4">
                        <p class="mb-0">Đơn hàng của bạn đang chờ xác nhận. Bạn sẽ thanh toán khi nhận được sách.</p>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info d-inline-block mx-auto mb-4">
                        <p class="mb-0">Đơn hàng của bạn đang chờ xác nhận thanh toán từ admin.</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <h5>Thông tin đơn hàng</h5>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex">
                                    <img src="../uploads/books/<?php echo $book_info['image']; ?>" alt="<?php echo $book_info['title']; ?>" 
                                        class="img-thumbnail me-3" style="width: 80px; height: 120px; object-fit: cover;">
                                    <div class="text-start">
                                        <h5 class="mb-1"><?php echo $book_info['title']; ?></h5>
                                        <p class="text-muted mb-1">Tác giả: <?php echo $book_info['author']; ?></p>
                                        <p class="fw-bold mb-0"><?php echo number_format($book_info['price'], 0, ',', '.'); ?> đ</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body text-start">
                                        <h6 class="card-title">Địa chỉ giao hàng</h6>
                                        <p class="mb-1"><strong><?php echo $shipping_info['fullname'] ?? ''; ?></strong></p>
                                        <p class="mb-1"><?php echo ($shipping_info['address'] ?? '') . ', ' . 
                                            ($shipping_info['district'] ?? '') . ', ' . 
                                            ($shipping_info['city'] ?? ''); ?></p>
                                        <p class="mb-0">Điện thoại: <?php echo $shipping_info['phone'] ?? ''; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body text-start">
                                        <h6 class="card-title">Phương thức thanh toán</h6>
                                        <?php if ($payment_info['payment_method'] == 'momo'): ?>
                                            <p class="mb-0"><i class="fas fa-wallet text-danger me-2"></i> Ví điện tử MoMo</p>
                                        <?php elseif ($payment_info['payment_method'] == 'bank_transfer'): ?>
                                            <p class="mb-0"><i class="fas fa-university me-2"></i> Chuyển khoản ngân hàng</p>
                                        <?php else: ?>
                                            <p class="mb-0"><i class="fas fa-money-bill-wave me-2"></i> Thanh toán khi nhận hàng</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning mb-4">
                        <p class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Chúng tôi sẽ liên hệ với bạn trong thời gian sớm nhất để xác nhận đơn hàng.
                            Nếu có bất kỳ thắc mắc nào, vui lòng liên hệ với chúng tôi qua email <strong>support@bookswap.vn</strong> 
                            hoặc số điện thoại <strong>028 1234 5678</strong>.
                        </p>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="../index.php" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i> Trang chủ
                        </a>
                        <a href="search.php" class="btn btn-outline-primary">
                            <i class="fas fa-search me-2"></i> Tiếp tục mua sắm
                        </a>
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