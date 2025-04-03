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

// Khởi tạo đối tượng
$payment = new Payment();

// Kiểm tra mã giao dịch
if (!isset($_GET['transaction_code'])) {
    header('Location: search.php');
    exit();
}

$transaction_code = $_GET['transaction_code'];
$payment_info = $payment->getPaymentByTransactionCode($transaction_code);

// Kiểm tra thông tin thanh toán
if ($payment_info['payment_method'] == 'momo') {
    // Thông tin thanh toán MoMo
    $momo_payment_info = [
        'partner_code' => 'YOUR_PARTNER_CODE',  // Thay bằng mã đối tác MoMo của bạn
        'amount' => $payment_info['amount'],
        'order_id' => $transaction_code,
        'order_info' => 'Thanh toán sách: ' . $payment_info['book_title'],
        'return_url' => 'https://yourdomain.com/pages/payment_success.php',
        'notify_url' => 'https://yourdomain.com/api/momo_callback.php',
    ];
    
    // Tạo QR code URL (đây là URL giả, bạn cần tích hợp với API thực của MoMo)
    $qr_image_url = 'assets/images/momo_qr_example.png'; // Thay bằng URL thực từ MoMo API
    
    // Bạn cũng có thể tạo QR code động bằng các thư viện như phpqrcode
    // $qr_content = json_encode($momo_payment_info);
    // Tạo QR code từ $qr_content
}

// Include header
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i> Xác Nhận Thanh Toán
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Thông tin sách và giá tiền giữ nguyên -->
                    
                    <?php if ($payment_info['payment_method'] == 'momo'): ?>
                    <div class="text-center mb-4">
                        <h5 class="mb-3">Quét mã QR để thanh toán qua MoMo</h5>
                        <img src="<?php echo $qr_image_url; ?>" alt="MoMo QR Code" class="img-fluid mb-3" style="max-width: 250px;">
                        <div class="alert alert-info">
                            <p class="mb-1">Số tiền: <strong><?php echo number_format($payment_info['amount'], 0, ',', '.'); ?> đ</strong></p>
                            <p class="mb-1">Nội dung chuyển khoản: <strong><?php echo $transaction_code; ?></strong></p>
                            <p class="mb-0 small">Sau khi quét mã và thanh toán thành công, hãy nhấn "Tôi đã thanh toán"</p>
                        </div>
                        <div class="mt-3">
                            <a href="payment_success.php?transaction_code=<?php echo $transaction_code; ?>" class="btn btn-primary">Tôi đã thanh toán</a>
                        </div>
                    </div>
                    <?php elseif ($payment_info['payment_method'] == 'bank_transfer'): ?>
                    <!-- Hiển thị thông tin chuyển khoản ngân hàng -->
                    <?php else: ?>
                    <!-- Thanh toán khi nhận (COD) -->
                    <?php endif; ?>
                    
                    <!-- Các nút tiếp tục mua sắm, v.v. giữ nguyên -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>