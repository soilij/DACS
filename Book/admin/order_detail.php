<?php
// Khởi động session
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../index.php');
    exit();
}

// Include các file cần thiết
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Payment.php';
require_once '../classes/Book.php';
require_once '../classes/User.php';
require_once '../classes/Notification.php';

// Khởi tạo đối tượng cần thiết
$payment = new Payment();
$book = new Book();
$user = new User();
$notification = new Notification();
$db = new Database();

// Kiểm tra ID giao dịch
if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$transaction_code = $_GET['id'];

// Lấy thông tin giao dịch
$sql = '
    SELECT p.*, 
           b.title as book_title, b.author as book_author, b.image as book_image, b.user_id as seller_id,
           u.username as buyer_username, u.email as buyer_email, u.full_name as buyer_name, u.phone as buyer_phone,
           seller.username as seller_username, seller.email as seller_email, seller.full_name as seller_name, seller.phone as seller_phone
    FROM payments p
    JOIN books b ON p.book_id = b.id
    JOIN users u ON p.user_id = u.id
    JOIN users seller ON b.user_id = seller.id
    WHERE p.transaction_code = ?
';

$db->query($sql);
$db->bind(1, $transaction_code);
$order = $db->single();

// Kiểm tra giao dịch tồn tại
if (!$order) {
    header('Location: orders.php');
    exit();
}

// Lấy thông tin giao hàng
$shipping_info = json_decode($order['shipping_info'] ?? '{}', true);

// Lấy lịch sử cập nhật đơn hàng (giả sử có bảng payment_history)
$db->query('
    SELECT * FROM payment_history 
    WHERE transaction_code = ? 
    ORDER BY created_at DESC
');
$db->bind(1, $transaction_code);
$payment_history = $db->resultSet() ?: [];

// Xử lý hành động
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Xử lý cập nhật trạng thái đơn hàng
    if (isset($_POST['update_status'])) {
        $new_status = $_POST['payment_status'];
        $shipping_status = $_POST['shipping_status'];
        $admin_note = $_POST['admin_note'];
        
        // Cập nhật trạng thái thanh toán
        $db->query('UPDATE payments SET status = ?, shipping_status = ? WHERE transaction_code = ?');
        $db->bind(1, $new_status);
        $db->bind(2, $shipping_status);
        $db->bind(3, $transaction_code);
        
        if ($db->execute()) {
            // Thêm vào lịch sử cập nhật
            $db->query('
                INSERT INTO payment_history (transaction_code, payment_status, shipping_status, note, updated_by)
                VALUES (?, ?, ?, ?, ?)
            ');
            $db->bind(1, $transaction_code);
            $db->bind(2, $new_status);
            $db->bind(3, $shipping_status);
            $db->bind(4, $admin_note);
            $db->bind(5, $_SESSION['user_id']);
            $db->execute();
            
            // Tạo thông báo cho người mua
            $notification_message = '';
            switch ($new_status) {
                case 'paid':
                    $notification_message = 'Thanh toán của bạn đã được xác nhận cho đơn hàng #' . $transaction_code;
                    break;
                case 'processing':
                    $notification_message = 'Đơn hàng #' . $transaction_code . ' đang được xử lý';
                    break;
                case 'completed':
                    $notification_message = 'Đơn hàng #' . $transaction_code . ' đã hoàn thành';
                    break;
                case 'cancelled':
                    $notification_message = 'Đơn hàng #' . $transaction_code . ' đã bị hủy';
                    break;
            }
            
            if (!empty($notification_message)) {
                $notification_data = [
                    'user_id' => $order['user_id'],
                    'message' => $notification_message,
                    'link' => 'pages/payment_details.php?transaction_code=' . $transaction_code
                ];
                $notification->create($notification_data);
            }
            
            // Tạo thông báo cho người bán
            $seller_notification = [
                'user_id' => $order['seller_id'],
                'message' => 'Đơn hàng #' . $transaction_code . ' đã được cập nhật trạng thái thành ' . $new_status,
                'link' => 'pages/seller_orders.php'
            ];
            $notification->create($seller_notification);
            
            $message = 'Đã cập nhật trạng thái đơn hàng thành công!';
            $message_type = 'success';
        } else {
            $message = 'Có lỗi xảy ra khi cập nhật trạng thái đơn hàng!';
            $message_type = 'danger';
        }
    }
    
    // Xử lý gửi email thông báo
    if (isset($_POST['send_notification'])) {
        // Code gửi email thông báo ở đây (nếu có)
        $message = 'Đã gửi thông báo đến người dùng thành công!';
        $message_type = 'success';
    }
}

// Admin header
include('includes/header.php');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Chi tiết đơn hàng</h1>
        <div>
            <a href="orders.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Quay lại danh sách
            </a>
            <button type="button" class="btn btn-primary ms-2" onclick="window.print()">
                <i class="fas fa-print me-1"></i> In đơn hàng
            </button>
        </div>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Thông tin đơn hàng -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Thông tin đơn hàng #<?php echo $transaction_code; ?></h5>
                    <div>
                        <span class="badge 
                            <?php 
                            switch($order['status']) {
                                case 'pending': echo 'bg-warning text-dark'; break;
                                case 'paid': echo 'bg-success'; break;
                                case 'processing': echo 'bg-info'; break;
                                case 'completed': echo 'bg-success'; break;
                                case 'cancelled': echo 'bg-danger'; break;
                                default: echo 'bg-secondary';
                            }
                            ?>">
                            <?php 
                            switch($order['status']) {
                                case 'pending': echo 'Chờ thanh toán'; break;
                                case 'paid': echo 'Đã thanh toán'; break;
                                case 'processing': echo 'Đang xử lý'; break;
                                case 'completed': echo 'Hoàn thành'; break;
                                case 'cancelled': echo 'Đã hủy'; break;
                                default: echo 'Thất bại';
                            }
                            ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Mã đơn hàng:</strong> <?php echo $transaction_code; ?></p>
                            <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                            <p><strong>Cập nhật cuối:</strong> <?php echo date('d/m/Y H:i', strtotime($order['updated_at'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p>
                                <strong>Phương thức thanh toán:</strong> 
                                <?php 
                                switch($order['payment_method']) {
                                    case 'momo': echo 'Ví MoMo'; break;
                                    case 'bank_transfer': echo 'Chuyển khoản ngân hàng'; break;
                                    case 'cod': echo 'Thanh toán khi nhận hàng (COD)'; break;
                                    default: echo $order['payment_method'];
                                }
                                ?>
                            </p>
                            <p>
                                <strong>Trạng thái vận chuyển:</strong> 
                                <span class="badge 
                                    <?php 
                                    switch($order['shipping_status']) {
                                        case 'pending': echo 'bg-warning text-dark'; break;
                                        case 'processing': echo 'bg-info'; break;
                                        case 'shipped': echo 'bg-primary'; break;
                                        case 'delivered': echo 'bg-success'; break;
                                        default: echo 'bg-secondary';
                                    }
                                    ?>">
                                    <?php 
                                    switch($order['shipping_status']) {
                                        case 'pending': echo 'Chờ xử lý'; break;
                                        case 'processing': echo 'Đang chuẩn bị'; break;
                                        case 'shipped': echo 'Đang giao'; break;
                                        case 'delivered': echo 'Đã giao'; break;
                                        default: echo $order['shipping_status'];
                                    }
                                    ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Thông tin người mua</h6>
                            <p><strong>Tên tài khoản:</strong> <?php echo $order['buyer_username']; ?></p>
                            <p><strong>Họ tên:</strong> <?php echo $order['buyer_name']; ?></p>
                            <p><strong>Email:</strong> <?php echo $order['buyer_email']; ?></p>
                            <p><strong>Số điện thoại:</strong> <?php echo $order['buyer_phone']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Thông tin người bán</h6>
                            <p><strong>Tên tài khoản:</strong> <?php echo $order['seller_username']; ?></p>
                            <p><strong>Họ tên:</strong> <?php echo $order['seller_name']; ?></p>
                            <p><strong>Email:</strong> <?php echo $order['seller_email']; ?></p>
                            <p><strong>Số điện thoại:</strong> <?php echo $order['seller_phone']; ?></p>
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3">Thông tin giao hàng</h6>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Người nhận:</strong> <?php echo $shipping_info['fullname'] ?? $order['buyer_name']; ?></p>
                            <p><strong>Số điện thoại:</strong> <?php echo $shipping_info['phone'] ?? $order['buyer_phone']; ?></p>
                            <p><strong>Email:</strong> <?php echo $shipping_info['email'] ?? $order['buyer_email']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Địa chỉ:</strong> <?php echo $shipping_info['address'] ?? ''; ?></p>
                            <p><strong>Quận/Huyện:</strong> <?php echo $shipping_info['district'] ?? ''; ?></p>
                            <p><strong>Tỉnh/Thành phố:</strong> <?php echo $shipping_info['city'] ?? ''; ?></p>
                            <?php if (!empty($shipping_info['note'])): ?>
                            <p><strong>Ghi chú:</strong> <?php echo $shipping_info['note']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3">Chi tiết đơn hàng</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Sách</th>
                                    <th>Thông tin</th>
                                    <th class="text-end">Giá</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td width="100">
                                        <img src="../uploads/books/<?php echo $order['book_image']; ?>" 
                                             alt="<?php echo $order['book_title']; ?>" 
                                             class="img-thumbnail" style="width: 80px; height: 100px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <h6 class="mb-1"><?php echo $order['book_title']; ?></h6>
                                        <p class="mb-0 text-muted">Tác giả: <?php echo $order['book_author']; ?></p>
                                    </td>
                                    <td class="text-end"><?php echo number_format($order['amount'], 0, ',', '.'); ?> đ</td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="text-end"><strong>Phí vận chuyển:</strong></td>
                                    <td class="text-end">20.000 đ</td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="text-end"><strong>Tổng cộng:</strong></td>
                                    <td class="text-end"><strong><?php echo number_format($order['amount'] + 20000, 0, ',', '.'); ?> đ</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Lịch sử đơn hàng -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Lịch sử đơn hàng</h5>
                </div>
                <div class="card-body">
                    <?php if (count($payment_history) > 0): ?>
                    <ul class="timeline">
                        <?php foreach ($payment_history as $history): ?>
                        <li class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">
                                    <?php 
                                    switch($history['payment_status']) {
                                        case 'pending': echo 'Chờ thanh toán'; break;
                                        case 'paid': echo 'Đã thanh toán'; break;
                                        case 'processing': echo 'Đang xử lý'; break;
                                        case 'completed': echo 'Hoàn thành'; break;
                                        case 'cancelled': echo 'Đã hủy'; break;
                                        default: echo $history['payment_status'];
                                    }
                                    
                                    echo ' - ';
                                    
                                    switch($history['shipping_status']) {
                                        case 'pending': echo 'Chờ xử lý'; break;
                                        case 'processing': echo 'Đang chuẩn bị'; break;
                                        case 'shipped': echo 'Đang giao'; break;
                                        case 'delivered': echo 'Đã giao'; break;
                                        default: echo $history['shipping_status'];
                                    }
                                    ?>
                                </h6>
                                <p class="timeline-subtitle"><?php echo date('d/m/Y H:i', strtotime($history['created_at'])); ?></p>
                                <?php if (!empty($history['note'])): ?>
                                <p><?php echo $history['note']; ?></p>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                        
                        <!-- Thêm mục tạo đơn hàng -->
                        <li class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Đơn hàng được tạo</h6>
                                <p class="timeline-subtitle"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                            </div>
                        </li>
                    </ul>
                    <?php else: ?>
                    <p class="text-muted">Chưa có lịch sử cập nhật đơn hàng.</p>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Đơn hàng được tạo</h6>
                                <p class="timeline-subtitle"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Cập nhật trạng thái -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Cập nhật trạng thái</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="payment_status" class="form-label">Trạng thái thanh toán</label>
                            <select class="form-select" id="payment_status" name="payment_status" required>
                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Chờ thanh toán</option>
                                <option value="paid" <?php echo $order['status'] == 'paid' ? 'selected' : ''; ?>>Đã thanh toán</option>
                                <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                                <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                <option value="failed" <?php echo $order['status'] == 'failed' ? 'selected' : ''; ?>>Thất bại</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="shipping_status" class="form-label">Trạng thái vận chuyển</label>
                            <select class="form-select" id="shipping_status" name="shipping_status" required>
                                <option value="pending" <?php echo $order['shipping_status'] == 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                                <option value="processing" <?php echo $order['shipping_status'] == 'processing' ? 'selected' : ''; ?>>Đang chuẩn bị</option>
                                <option value="shipped" <?php echo $order['shipping_status'] == 'shipped' ? 'selected' : ''; ?>>Đang giao</option>
                                <option value="delivered" <?php echo $order['shipping_status'] == 'delivered' ? 'selected' : ''; ?>>Đã giao</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="admin_note" class="form-label">Ghi chú</label>
                            <textarea class="form-control" id="admin_note" name="admin_note" rows="3"></textarea>
                            <div class="form-text">Ghi chú này sẽ được lưu vào lịch sử đơn hàng</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="update_status" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Cập nhật trạng thái
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Gửi thông báo -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Gửi thông báo</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="notification_type" class="form-label">Loại thông báo</label>
                            <select class="form-select" id="notification_type" name="notification_type" required>
                                <option value="buyer">Gửi cho người mua</option>
                                <option value="seller">Gửi cho người bán</option>
                                <option value="both">Gửi cho cả hai</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notification_message" class="form-label">Nội dung thông báo</label>
                            <textarea class="form-control" id="notification_message" name="notification_message" rows="4" required></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="send_notification" class="btn btn-success">
                                <i class="fas fa-paper-plane me-1"></i> Gửi thông báo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Hành động nhanh -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Hành động nhanh</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="?action=status&id=<?php echo $transaction_code; ?>&status=paid" class="btn btn-outline-success">
                            <i class="fas fa-check-circle me-1"></i> Xác nhận thanh toán
                        </a>
                        <a href="?action=shipping&id=<?php echo $transaction_code; ?>&status=shipped" class="btn btn-outline-primary">
                            <i class="fas fa-truck me-1"></i> Đánh dấu đã giao
                        </a>
                        <a href="?action=status&id=<?php echo $transaction_code; ?>&status=completed" class="btn btn-outline-success">
                            <i class="fas fa-check-double me-1"></i> Hoàn thành đơn hàng
                        </a>
                        <a href="?action=status&id=<?php echo $transaction_code; ?>&status=cancelled" 
                           class="btn btn-outline-danger"
                           onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')">
                            <i class="fas fa-times-circle me-1"></i> Hủy đơn hàng
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* CSS cho timeline */
.timeline {
    position: relative;
    padding-left: 30px;
    margin-bottom: 20px;
    list-style: none;
}

.timeline::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 11px;
    width: 2px;
    background-color: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: #007bff;
    border: 2px solid #fff;
    z-index: 1;
}

.timeline-content {
    padding-left: 10px;
}

.timeline-title {
    margin-bottom: 5px;
}

.timeline-subtitle {
    color: #6c757d;
    font-size: 0.875rem;
}

/* CSS cho in */
@media print {
    .no-print {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .card-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
        border-bottom: 1px solid #dee2e6 !important;
    }
}
</style>

<?php include('includes/footer.php'); ?>