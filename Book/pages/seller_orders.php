<?php
// Khởi động session
session_start();
// Thiết lập biến
$is_detail_page = true;
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

// Lấy danh sách đơn hàng của người bán
$sql = '
    SELECT p.*, 
           b.title as book_title, b.author as book_author, b.image as book_image,
           u.username as buyer_username, u.email as buyer_email, u.full_name as buyer_name
    FROM payments p
    JOIN books b ON p.book_id = b.id
    JOIN users u ON p.user_id = u.id
    WHERE b.user_id = ?
    ORDER BY p.created_at DESC
';

$db->query($sql);
$db->bind(1, $_SESSION['user_id']);
$orders = $db->resultSet();

// Đặt biến $is_detail_page để header biết đang ở trang chi tiết
$is_detail_page = true;

// Include header
require_once '../includes/header.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Đơn hàng của tôi (Người bán)</h1>
    
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

    <?php if (count($orders) > 0): ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Mã đơn hàng</th>
                    <th>Sách</th>
                    <th>Người mua</th>
                    <th>Tổng tiền</th>
                    <th>PT Thanh toán</th>
                    <th>Trạng thái</th>
                    <th>TT Vận chuyển</th>
                    <th>Ngày đặt</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orders as $order): ?>
                <tr>
                    <td><span class="badge bg-primary"><?php echo $order['transaction_code']; ?></span></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="../uploads/books/<?php echo $order['book_image']; ?>" 
                                 alt="<?php echo $order['book_title']; ?>" 
                                 class="img-thumbnail me-2" 
                                 style="width: 50px; height: 70px; object-fit: cover;">
                            <div>
                                <span class="d-block"><?php echo $order['book_title']; ?></span>
                                <small class="text-muted"><?php echo $order['book_author']; ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php echo $order['buyer_name']; ?>
                        <br>
                        <small class="text-muted"><?php echo $order['buyer_email']; ?></small>
                    </td>
                    <td><?php echo number_format($order['amount'], 0, ',', '.'); ?> đ</td>
                    <td>
                        <?php if($order['payment_method'] == 'momo'): ?>
                            <span class="badge bg-danger">MoMo</span>
                        <?php elseif($order['payment_method'] == 'bank_transfer'): ?>
                            <span class="badge bg-primary">Chuyển khoản</span>
                        <?php else: ?>
                            <span class="badge bg-success">COD</span>
                        <?php endif; ?>
                    </td>
                    <td>
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
                    </td>
                    <td>
                        <?php if($order['shipping_status'] == 'pending'): ?>
                            <span class="badge bg-warning text-dark">Chờ xử lý</span>
                        <?php elseif($order['shipping_status'] == 'processing'): ?>
                            <span class="badge bg-info">Đang chuẩn bị</span>
                        <?php elseif($order['shipping_status'] == 'shipped'): ?>
                            <span class="badge bg-primary">Đang giao</span>
                        <?php elseif($order['shipping_status'] == 'delivered'): ?>
                            <span class="badge bg-success">Đã giao</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-cog"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="payment_details.php?id=<?php echo $order['transaction_code']; ?>">Chi tiết</a></li>
                                <?php if($order['status'] == 'pending'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="update_order.php?id=<?php echo $order['transaction_code']; ?>&action=confirm">Xác nhận đơn hàng</a></li>
                                <li><a class="dropdown-item" href="update_order.php?id=<?php echo $order['transaction_code']; ?>&action=cancel">Hủy đơn hàng</a></li>
                                <?php endif; ?>
                                <?php if($order['status'] == 'paid' || $order['status'] == 'processing'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="update_order.php?id=<?php echo $order['transaction_code']; ?>&action=ship">Giao hàng</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> Bạn chưa có đơn hàng nào.
    </div>
    <?php endif; ?>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?> 