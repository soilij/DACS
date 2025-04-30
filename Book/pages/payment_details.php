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
$user = new User();
$db = new Database();

// Kiểm tra mã giao dịch
if (!isset($_GET['id'])) {
    header('Location: seller_orders.php');
    exit();
}

$transaction_code = $_GET['id'];

// Lấy thông tin đơn hàng
$sql = '
    SELECT p.*, 
           b.title as book_title, b.author as book_author, b.image as book_image, b.condition_rating,
           u.username as buyer_username, u.email as buyer_email, u.full_name as buyer_name,
           s.username as seller_username, s.email as seller_email, s.full_name as seller_name
    FROM payments p
    JOIN books b ON p.book_id = b.id
    JOIN users u ON p.user_id = u.id
    JOIN users s ON b.user_id = s.id
    WHERE p.transaction_code = ?
';

$db->query($sql);
$db->bind(1, $transaction_code);
$order = $db->single();

// Kiểm tra đơn hàng tồn tại
if (!$order) {
    $_SESSION['error'] = 'Không tìm thấy thông tin đơn hàng!';
    header('Location: seller_orders.php');
    exit();
}

// Kiểm tra quyền truy cập (chỉ người mua hoặc người bán mới được xem)
if ($_SESSION['user_id'] != $order['user_id'] && $_SESSION['user_id'] != $order['seller_id']) {
    $_SESSION['error'] = 'Bạn không có quyền xem đơn hàng này!';
    header('Location: seller_orders.php');
    exit();
}

// Lấy thông tin giao hàng
$shipping_info = json_decode($order['shipping_info'] ?? '{}', true);

// Đặt biến $is_detail_page để header biết đang ở trang chi tiết
$is_detail_page = true;

// Include header
require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="seller_orders.php">Đơn hàng của tôi</a></li>
                    <li class="breadcrumb-item active">Chi tiết đơn hàng</li>
                </ol>
            </nav>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Thông tin đơn hàng -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Chi tiết đơn hàng #<?php echo $transaction_code; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Trạng thái đơn hàng -->
                    <div class="text-center mb-4">
                        <?php if($order['status'] == 'pending'): ?>
                            <div class="h1 text-warning mb-2"><i class="fas fa-clock"></i></div>
                            <h4 class="text-warning">Chờ xác nhận</h4>
                        <?php elseif($order['status'] == 'paid'): ?>
                            <div class="h1 text-success mb-2"><i class="fas fa-check-circle"></i></div>
                            <h4 class="text-success">Đã thanh toán</h4>
                        <?php elseif($order['status'] == 'processing'): ?>
                            <div class="h1 text-info mb-2"><i class="fas fa-cog fa-spin"></i></div>
                            <h4 class="text-info">Đang xử lý</h4>
                        <?php elseif($order['status'] == 'completed'): ?>
                            <div class="h1 text-success mb-2"><i class="fas fa-check-double"></i></div>
                            <h4 class="text-success">Hoàn thành</h4>
                        <?php elseif($order['status'] == 'cancelled'): ?>
                            <div class="h1 text-danger mb-2"><i class="fas fa-times-circle"></i></div>
                            <h4 class="text-danger">Đã hủy</h4>
                        <?php endif; ?>
                    </div>

                    <!-- Thông tin sách -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Thông tin sách</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex">
                                <img src="../uploads/books/<?php echo $order['book_image']; ?>" 
                                     alt="<?php echo $order['book_title']; ?>" 
                                     class="img-thumbnail me-3" 
                                     style="width: 100px; height: 150px; object-fit: cover;">
                                <div>
                                    <h5 class="mb-1"><?php echo $order['book_title']; ?></h5>
                                    <p class="text-muted mb-1">Tác giả: <?php echo $order['book_author']; ?></p>
                                    <div class="mb-2">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <?php if($i <= $order['condition_rating']): ?>
                                                <i class="fas fa-star text-warning"></i>
                                            <?php else: ?>
                                                <i class="far fa-star text-warning"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <span class="ms-1 text-muted">Tình trạng sách</span>
                                    </div>
                                    <p class="h5 mb-0 text-danger"><?php echo number_format($order['amount'], 0, ',', '.'); ?> đ</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Thông tin thanh toán -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Thông tin thanh toán</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <p class="mb-0"><strong>Phương thức:</strong></p>
                                </div>
                                <div class="col-md-8">
                                    <?php if($order['payment_method'] == 'momo'): ?>
                                        <span class="badge bg-danger">MoMo</span>
                                    <?php elseif($order['payment_method'] == 'bank_transfer'): ?>
                                        <span class="badge bg-primary">Chuyển khoản</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">COD</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <p class="mb-0"><strong>Trạng thái:</strong></p>
                                </div>
                                <div class="col-md-8">
                                    <?php if($order['status'] == 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Chờ xác nhận</span>
                                    <?php elseif($order['status'] == 'paid'): ?>
                                        <span class="badge bg-success">Đã thanh toán</span>
                                    <?php elseif($order['status'] == 'processing'): ?>
                                        <span class="badge bg-info">Đang xử lý</span>
                                    <?php elseif($order['status'] == 'completed'): ?>
                                        <span class="badge bg-success">Hoàn thành</span>
                                    <?php elseif($order['status'] == 'cancelled'): ?>
                                        <span class="badge bg-danger">Đã hủy</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <p class="mb-0"><strong>Ngày đặt hàng:</strong></p>
                                </div>
                                <div class="col-md-8">
                                    <p class="mb-0"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                                </div>
                            </div>
                            <?php if($order['payment_method'] != 'cod'): ?>
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-0"><strong>Ngày thanh toán:</strong></p>
                                </div>
                                <div class="col-md-8">
                                    <p class="mb-0"><?php echo $order['paid_at'] ? date('d/m/Y H:i', strtotime($order['paid_at'])) : 'Chưa thanh toán'; ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Thông tin giao hàng -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Thông tin giao hàng</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <p class="mb-0"><strong>Người nhận:</strong></p>
                                </div>
                                <div class="col-md-8">
                                    <p class="mb-0"><?php echo $shipping_info['fullname'] ?? ''; ?></p>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <p class="mb-0"><strong>Số điện thoại:</strong></p>
                                </div>
                                <div class="col-md-8">
                                    <p class="mb-0"><?php echo $shipping_info['phone'] ?? ''; ?></p>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <p class="mb-0"><strong>Email:</strong></p>
                                </div>
                                <div class="col-md-8">
                                    <p class="mb-0"><?php echo $shipping_info['email'] ?? ''; ?></p>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <p class="mb-0"><strong>Địa chỉ:</strong></p>
                                </div>
                                <div class="col-md-8">
                                    <p class="mb-0">
                                        <?php echo ($shipping_info['address'] ?? '') . ', ' . 
                                                  ($shipping_info['district'] ?? '') . ', ' . 
                                                  ($shipping_info['city'] ?? ''); ?>
                                    </p>
                                </div>
                            </div>
                            <?php if (!empty($shipping_info['note'])): ?>
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-0"><strong>Ghi chú:</strong></p>
                                </div>
                                <div class="col-md-8">
                                    <p class="mb-0"><?php echo $shipping_info['note']; ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tổng tiền -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Giá sách:</span>
                                <span class="fw-bold"><?php echo number_format($order['amount'], 0, ',', '.'); ?> đ</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span>Phí vận chuyển:</span>
                                <span class="fw-bold">20.000 đ</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Tổng cộng:</span>
                                <span class="h4 mb-0 text-danger"><?php echo number_format($order['amount'] + 20000, 0, ',', '.'); ?> đ</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nút thao tác -->
            <div class="d-flex justify-content-between">
                <a href="seller_orders.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Quay lại
                </a>
                <div>
                    <?php if($order['status'] == 'pending'): ?>
                        <a href="update_order.php?id=<?php echo $transaction_code; ?>&action=confirm" class="btn btn-success me-2">
                            <i class="fas fa-check me-2"></i> Xác nhận đơn hàng
                        </a>
                        <a href="update_order.php?id=<?php echo $transaction_code; ?>&action=cancel" class="btn btn-danger"
                           onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?');">
                            <i class="fas fa-times me-2"></i> Hủy đơn hàng
                        </a>
                    <?php endif; ?>
                    <?php if($order['status'] == 'paid' || $order['status'] == 'processing'): ?>
                        <a href="update_order.php?id=<?php echo $transaction_code; ?>&action=ship" class="btn btn-primary">
                            <i class="fas fa-shipping-fast me-2"></i> Giao hàng
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?> 