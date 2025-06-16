<?php
// Khởi động session
session_start();
$is_detail_page = true;
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Payment.php';
require_once '../classes/Book.php';
require_once '../classes/User.php';

$status = isset($_GET['status']) ? $_GET['status'] : 'all';

$payment = new Payment();
$orders = $payment->getUserPaymentHistory($_SESSION['user_id']);
if ($status != 'all') {
    $orders = array_filter($orders, function($order) use ($status) {
        return $order['status'] == $status;
    });
}
require_once '../includes/header.php';
?>

<div class="container py-5">
    <h1 class="text-center mb-4 fw-bold" style="font-size:2.5rem;">Lịch Sử Đơn Hàng</h1>
    <ul class="nav nav-pills justify-content-center mb-4 gap-2" id="orderStatusTabs">
        <li class="nav-item"><a class="nav-link px-4 <?php echo $status == 'all' ? 'active' : ''; ?>" href="?status=all">Tất cả</a></li>
        <li class="nav-item"><a class="nav-link px-4 <?php echo $status == 'pending' ? 'active' : ''; ?>" href="?status=pending">Chờ xác nhận</a></li>
        <li class="nav-item"><a class="nav-link px-4 <?php echo $status == 'processing' ? 'active' : ''; ?>" href="?status=processing">Đang xử lý</a></li>
        <li class="nav-item"><a class="nav-link px-4 <?php echo $status == 'shipping' ? 'active' : ''; ?>" href="?status=shipping">Đang giao hàng</a></li>
        <li class="nav-item"><a class="nav-link px-4 <?php echo $status == 'completed' ? 'active' : ''; ?>" href="?status=completed">Hoàn tất</a></li>
        <li class="nav-item"><a class="nav-link px-4 <?php echo $status == 'cancelled' ? 'active' : ''; ?>" href="?status=cancelled">Đã huỷ</a></li>
    </ul>

    <?php if (empty($orders)): ?>
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle me-2"></i>
        Bạn chưa có đơn hàng nào.
        <br>
        <a href="search.php" class="btn btn-primary mt-3">
            <i class="fas fa-search me-2"></i>Tìm sách ngay
        </a>
    </div>
    <?php else: ?>
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table align-middle table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">Mã đơn hàng</th>
                                    <th>Sách</th>
                                    <th>Người bán</th>
                                    <th class="text-center">Ngày đặt</th>
                                    <th class="text-end">Tổng tiền</th>
                                    <th class="text-center">Trạng thái</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
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
                                <tr>
                                    <td class="text-center">
                                        <span class="fw-bold"><?php echo $order['transaction_code']; ?></span>
                                        <br>
                                        <small class="text-muted"><?php echo ucfirst($order['payment_method']); ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="../uploads/books/<?php echo $order['image'] ?? $order['book_image']; ?>" 
                                                 alt="<?php echo $order['title'] ?? $order['book_title']; ?>"
                                                 class="img-thumbnail me-2"
                                                 style="width: 50px; height: 70px; object-fit: cover;">
                                            <div>
                                                <div class="fw-bold"><?php echo $order['title'] ?? $order['book_title']; ?></div>
                                                <small class="text-muted"><?php echo $order['author'] ?? $order['book_author']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo $order['seller_full_name'] ?? $order['seller_fullname'] ?? ''; ?>
                                        <br>
                                        <small class="text-muted">@<?php echo $order['seller_name'] ?? $order['seller_username'] ?? ''; ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                                        <br>
                                        <span class="text-muted" style="font-size:0.9em;">
                                            <?php echo date('H:i', strtotime($order['created_at'])); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-danger">
                                            <?php echo number_format($order['amount'], 0, ',', '.'); ?> đ
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $status_class; ?> px-3 py-2" style="font-size:1em;">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="order_detail.php?transaction_code=<?php echo $order['transaction_code']; ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i> Chi tiết
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.nav-pills .nav-link.active {
    background: #1976d2 !important;
    color: #fff !important;
    font-weight: bold;
    border-radius: 8px;
}
.table thead th {
    vertical-align: middle;
    text-align: center;
}
</style>

<?php require_once '../includes/footer.php'; ?> 