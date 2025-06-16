<?php
// Khởi động session
session_start();
// Thiết lập biến
$is_detail_page = true;
// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Include các file cần thiết
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Payment.php';
require_once '../classes/Book.php';
require_once '../classes/User.php';

// Khởi tạo đối tượng
$db = new Database();
$payment = new Payment();
$book = new Book();
$user = new User();

// Kiểm tra mã giao dịch
if (!isset($_GET['transaction_code'])) {
    header('Location: order_history.php');
    exit();
}

$transaction_code = $_GET['transaction_code'];

// Lấy thông tin chi tiết đơn hàng
$db->query("
    SELECT p.*, b.title, b.image, b.author, b.condition_rating,
           u.username as seller_name, u.full_name as seller_full_name,
           u.phone as seller_phone, u.email as seller_email
    FROM payments p
    JOIN books b ON p.book_id = b.id
    JOIN users u ON b.user_id = u.id
    WHERE p.transaction_code = :transaction_code AND p.user_id = :user_id
");
$db->bind(':transaction_code', $transaction_code);
$db->bind(':user_id', $_SESSION['user_id']);
$order = $db->single();

// Kiểm tra đơn hàng tồn tại và thuộc về người dùng hiện tại
if (!$order) {
    header('Location: order_history.php');
    exit();
}

// Lấy thông tin giao hàng
$shipping_info = json_decode($order['shipping_info'], true);

// Include header
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Chi Tiết Đơn Hàng</h1>
                <a href="order_history.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
            </div>

            <!-- Thông tin đơn hàng -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Thông tin đơn hàng #<?php echo $order['transaction_code']; ?></h5>
                </div>
                <div class="card-body">
                    <?php
                        $status_class = '';
                        $status_text = '';
                        switch ($order['status']) {
                            case 'pending':
                                $status_class = 'warning';
                                $status_text = 'Chờ xác nhận';
                                break;
                            case 'processing':
                                $status_class = 'info';
                                $status_text = 'Đang xử lý';
                                break;
                            case 'shipping':
                                $status_class = 'primary';
                                $status_text = 'Đang giao hàng';
                                break;
                            case 'completed':
                                $status_class = 'success';
                                $status_text = 'Hoàn thành';
                                break;
                            case 'cancelled':
                                $status_class = 'danger';
                                $status_text = 'Đã hủy';
                                break;
                            case 'paid':
                                $status_class = 'info';
                                $status_text = 'Đã thanh toán';
                                break;
                        }
                    ?>
                    <div class="alert alert-<?php echo $status_class; ?> mb-4">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-info-circle fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Trạng thái đơn hàng</h6>
                                <div class="fw-bold"><?php echo $status_text; ?></div>
                                <small class="text-muted">Cập nhật: <?php echo date('d/m/Y H:i', strtotime($order['updated_at'])); ?></small>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="mb-3">Thông tin thanh toán</h6>
                            <p class="mb-1">
                                <strong>Phương thức:</strong> 
                                <?php 
                                    switch($order['payment_method']) {
                                        case 'momo':
                                            echo 'Ví MoMo';
                                            break;
                                        case 'bank_transfer':
                                            echo 'Chuyển khoản ngân hàng';
                                            break;
                                        case 'cod':
                                            echo 'Thanh toán khi nhận hàng';
                                            break;
                                    }
                                ?>
                            </p>
                            <p class="mb-1"><strong>Tổng tiền:</strong> <?php echo number_format($order['amount'] - 20000, 0, ',', '.'); ?> đ</p>
                            <p class="mb-1"><strong>Phí vận chuyển:</strong> 20.000 đ</p>
                            <p class="mb-0">
                                <strong>Tổng cộng:</strong> 
                                <span class="text-danger fw-bold">
                                    <?php echo number_format($order['amount'], 0, ',', '.'); ?> đ
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3">Thông tin người bán</h6>
                            <p class="mb-1"><strong>Tên:</strong> <?php echo $order['seller_full_name']; ?></p>
                            <p class="mb-1"><strong>Username:</strong> @<?php echo $order['seller_name']; ?></p>
                            <?php if ($order['status'] != 'pending' && $order['status'] != 'cancelled'): ?>
                            <p class="mb-1"><strong>Số điện thoại:</strong> <?php echo $order['seller_phone']; ?></p>
                            <p class="mb-0"><strong>Email:</strong> <?php echo $order['seller_email']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Thông tin giao hàng</h6>
                            <p class="mb-1"><strong>Người nhận:</strong> <?php echo $shipping_info['fullname']; ?></p>
                            <p class="mb-1"><strong>Số điện thoại:</strong> <?php echo $shipping_info['phone']; ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo $shipping_info['email']; ?></p>
                            <p class="mb-1">
                                <strong>Địa chỉ:</strong> 
                                <?php echo $shipping_info['address'] . ', ' . 
                                         $shipping_info['district'] . ', ' . 
                                         $shipping_info['city']; ?>
                            </p>
                            <?php if (!empty($shipping_info['note'])): ?>
                            <p class="mb-0"><strong>Ghi chú:</strong> <?php echo $shipping_info['note']; ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3">Thông tin sách</h6>
                            <div class="d-flex">
                                <img src="../uploads/books/<?php echo $order['image']; ?>" 
                                     alt="<?php echo $order['title']; ?>"
                                     class="img-thumbnail me-3"
                                     style="width: 100px; height: 150px; object-fit: cover;">
                                <div>
                                    <h5 class="mb-1"><?php echo $order['title']; ?></h5>
                                    <p class="text-muted mb-2"><?php echo $order['author']; ?></p>
                                    <div class="mb-2">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <?php if($i <= $order['condition_rating']): ?>
                                                <i class="fas fa-star text-warning"></i>
                                            <?php else: ?>
                                                <i class="far fa-star text-warning"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <small class="text-muted ms-1">Tình trạng sách</small>
                                    </div>
                                    <p class="fw-bold mb-0"><?php echo number_format($order['amount'] - 20000, 0, ',', '.'); ?> đ</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nút thao tác -->
            <div class="d-flex justify-content-between">
                <?php if ($order['status'] == 'pending'): ?>
                <form method="post" action="cancel_order.php">
                    <input type="hidden" name="transaction_code" value="<?php echo $order['transaction_code']; ?>">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')">
                        <i class="fas fa-times-circle me-2"></i>Hủy đơn hàng
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($order['status'] == 'completed'): ?>
                <a href="review.php?transaction_code=<?php echo $order['transaction_code']; ?>" class="btn btn-primary">
                    <i class="fas fa-star me-2"></i>Đánh giá
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?> 