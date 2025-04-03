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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra phương thức thanh toán
    $payment_method = $_POST['payment_method'] ?? '';
    
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
            'payment_method' => $payment_method
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
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Thanh Toán Sách</h4>
                </div>
                <div class="card-body">
                    <?php if ($msg): ?>
                    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex mb-4">
                        <img src="../uploads/books/<?php echo $book_info['image']; ?>" 
                             alt="<?php echo $book_info['title']; ?>" 
                             class="img-thumbnail me-3" 
                             style="width: 100px; height: 150px; object-fit: cover;">
                        <div>
                            <h5 class="mb-2"><?php echo $book_info['title']; ?></h5>
                            <p class="text-muted mb-1">Tác giả: <?php echo $book_info['author']; ?></p>
                            <p class="h4 text-primary"><?php echo number_format($book_info['price'], 0, ',', '.'); ?> đ</p>
                        </div>
                    </div>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label class="form-label">Chọn phương thức thanh toán</label>
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="payment_method" id="momo" value="momo" required>
                                    <label class="btn btn-outline-primary w-100" for="momo">
                                        <img src="../assets/images/momo-logo.png" alt="MoMo" class="img-fluid mb-2" style="max-height: 50px;">
                                        Ví MoMo
                                    </label>
                                </div>
                                
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="payment_method" id="bank_transfer" value="bank_transfer">
                                    <label class="btn btn-outline-primary w-100" for="bank_transfer">
                                        <img src="../assets/images/bank-transfer.png" alt="Chuyển khoản" class="img-fluid mb-2" style="max-height: 50px;">
                                        Chuyển khoản
                                    </label>
                                </div>
                                
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="payment_method" id="cod" value="cod">
                                    <label class="btn btn-outline-primary w-100" for="cod">
                                        <i class="fas fa-money-bill-wave fa-3x mb-2"></i>
                                        Thanh toán khi nhận
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i> 
                            Sau khi thanh toán, chúng tôi sẽ liên hệ để xác nhận và giao sách.
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-credit-card me-2"></i> Xác Nhận Thanh Toán
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>