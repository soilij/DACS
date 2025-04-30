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

// Xử lý hành động
$message = '';
$message_type = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $transaction_code = $_GET['id'];
    
    switch ($action) {
        case 'status':
            if (isset($_GET['status']) && in_array($_GET['status'], ['pending', 'paid', 'processing', 'completed', 'cancelled', 'failed'])) {
                $new_status = $_GET['status'];
                
                if ($payment->updatePaymentStatus($transaction_code, $new_status)) {
                    $message = 'Đã cập nhật trạng thái đơn hàng thành công!';
                    $message_type = 'success';
                    
                    // Lấy thông tin giao dịch
                    $payment_info = $payment->getPaymentByTransactionCode($transaction_code);
                    
                    // Tạo thông báo cho người dùng
                    if ($payment_info) {
                        $notification_message = '';
                        
                        switch ($new_status) {
                            case 'paid':
                                $notification_message = 'Đơn hàng #' . $transaction_code . ' đã được xác nhận thanh toán.';
                                break;
                            case 'processing':
                                $notification_message = 'Đơn hàng #' . $transaction_code . ' đang được xử lý.';
                                break;
                            case 'completed':
                                $notification_message = 'Đơn hàng #' . $transaction_code . ' đã hoàn thành.';
                                break;
                            case 'cancelled':
                                $notification_message = 'Đơn hàng #' . $transaction_code . ' đã bị hủy.';
                                break;
                            case 'failed':
                                $notification_message = 'Đơn hàng #' . $transaction_code . ' đã bị thất bại.';
                                break;
                        }
                        
                        if (!empty($notification_message)) {
                            $notification_data = [
                                'user_id' => $payment_info['user_id'],
                                'message' => $notification_message,
                                'link' => 'pages/payment_details.php?transaction_code=' . $transaction_code
                            ];
                            $notification->create($notification_data);
                        }
                    }
                } else {
                    $message = 'Có lỗi xảy ra khi cập nhật trạng thái!';
                    $message_type = 'danger';
                }
            }
            break;
            
        case 'shipping':
            if (isset($_GET['status']) && in_array($_GET['status'], ['pending', 'processing', 'shipped', 'delivered'])) {
                $new_status = $_GET['status'];
                
                if ($payment->updateShippingStatus($transaction_code, $new_status)) {
                    $message = 'Đã cập nhật trạng thái vận chuyển thành công!';
                    $message_type = 'success';
                    
                    // Lấy thông tin giao dịch
                    $payment_info = $payment->getPaymentByTransactionCode($transaction_code);
                    
                    // Tạo thông báo cho người dùng
                    if ($payment_info) {
                        $notification_message = '';
                        
                        switch ($new_status) {
                            case 'processing':
                                $notification_message = 'Đơn hàng #' . $transaction_code . ' đang được chuẩn bị giao hàng.';
                                break;
                            case 'shipped':
                                $notification_message = 'Đơn hàng #' . $transaction_code . ' đã được giao cho đơn vị vận chuyển.';
                                break;
                            case 'delivered':
                                $notification_message = 'Đơn hàng #' . $transaction_code . ' đã được giao thành công.';
                                break;
                        }
                        
                        if (!empty($notification_message)) {
                            $notification_data = [
                                'user_id' => $payment_info['user_id'],
                                'message' => $notification_message,
                                'link' => 'pages/payment_details.php?transaction_code=' . $transaction_code
                            ];
                            $notification->create($notification_data);
                        }
                    }
                } else {
                    $message = 'Có lỗi xảy ra khi cập nhật trạng thái vận chuyển!';
                    $message_type = 'danger';
                }
            }
            break;
            
        case 'delete':
            // Xóa đơn hàng
            $db->query('DELETE FROM payments WHERE transaction_code = :transaction_code');
            $db->bind(':transaction_code', $transaction_code);
            
            if ($db->execute()) {
                $message = 'Đã xóa đơn hàng thành công!';
                $message_type = 'success';
            } else {
                $message = 'Có lỗi xảy ra khi xóa đơn hàng!';
                $message_type = 'danger';
            }
            break;
            
        default:
            // Không làm gì
            break;
    }
}

// Lấy tham số lọc
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_payment = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$filter_shipping = isset($_GET['shipping_status']) ? $_GET['shipping_status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Xây dựng câu truy vấn
$sql = '
    SELECT p.*, 
           b.title as book_title, b.author as book_author, b.image as book_image, 
           u.username as buyer_username, u.email as buyer_email,
           seller.username as seller_username 
    FROM payments p
    JOIN books b ON p.book_id = b.id
    JOIN users u ON p.user_id = u.id
    JOIN users seller ON b.user_id = seller.id
    WHERE 1=1
';

// Thêm điều kiện lọc
$params = [];

if (!empty($search)) {
    $sql .= ' AND (p.transaction_code LIKE ? OR b.title LIKE ? OR u.username LIKE ? OR seller.username LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_status)) {
    $sql .= ' AND p.status = ?';
    $params[] = $filter_status;
}

if (!empty($filter_payment)) {
    $sql .= ' AND p.payment_method = ?';
    $params[] = $filter_payment;
}

if (!empty($filter_shipping)) {
    $sql .= ' AND p.shipping_status = ?';
    $params[] = $filter_shipping;
}

if (!empty($date_from)) {
    $sql .= ' AND DATE(p.created_at) >= ?';
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $sql .= ' AND DATE(p.created_at) <= ?';
    $params[] = $date_to;
}

// Thêm sắp xếp
$sql .= ' ORDER BY p.created_at DESC';

// Thực hiện truy vấn
$db->query($sql);

// Bind tham số
foreach ($params as $key => $param) {
    $db->bind($key + 1, $param);
}

// Lấy kết quả
$orders = $db->resultSet();

// Admin header
include('includes/header.php');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Quản lý đơn hàng</h1>
        <div>
            <button class="btn btn-success" onclick="exportToCSV()">
                <i class="fas fa-file-csv me-1"></i> Xuất CSV
            </button>
        </div>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <!-- Bộ lọc -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Bộ lọc đơn hàng</h6>
        </div>
        <div class="card-body">
            <form action="" method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Tìm kiếm</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Mã đơn hàng, tên sách, người mua...">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Trạng thái thanh toán</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tất cả</option>
                        <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                        <option value="paid" <?php echo $filter_status == 'paid' ? 'selected' : ''; ?>>Đã thanh toán</option>
                        <option value="processing" <?php echo $filter_status == 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                        <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                        <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="payment_method" class="form-label">Phương thức thanh toán</label>
                    <select class="form-select" id="payment_method" name="payment_method">
                        <option value="">Tất cả</option>
                        <option value="momo" <?php echo $filter_payment == 'momo' ? 'selected' : ''; ?>>Ví MoMo</option>
                        <option value="bank_transfer" <?php echo $filter_payment == 'bank_transfer' ? 'selected' : ''; ?>>Chuyển khoản</option>
                        <option value="cod" <?php echo $filter_payment == 'cod' ? 'selected' : ''; ?>>Tiền mặt (COD)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="shipping_status" class="form-label">Trạng thái vận chuyển</label>
                    <select class="form-select" id="shipping_status" name="shipping_status">
                        <option value="">Tất cả</option>
                        <option value="pending" <?php echo $filter_shipping == 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                        <option value="processing" <?php echo $filter_shipping == 'processing' ? 'selected' : ''; ?>>Đang chuẩn bị</option>
                        <option value="shipped" <?php echo $filter_shipping == 'shipped' ? 'selected' : ''; ?>>Đang giao</option>
                        <option value="delivered" <?php echo $filter_shipping == 'delivered' ? 'selected' : ''; ?>>Đã giao</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_range" class="form-label">Khoảng thời gian</label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                        <span class="input-group-text">đến</span>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                </div>
                <div class="col-md-12 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i> Lọc
                    </button>
                    <a href="orders.php" class="btn btn-secondary ms-2">
                        <i class="fas fa-sync-alt me-1"></i> Đặt lại
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Thống kê nhanh -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tổng đơn hàng</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $db->query('SELECT COUNT(*) as count FROM payments');
                                echo $db->single()['count'];
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Doanh thu</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $db->query('SELECT SUM(amount) as total FROM payments WHERE status IN ("paid", "completed")');
                                $total = $db->single()['total'] ?: 0;
                                echo number_format($total, 0, ',', '.') . ' đ';
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Đang xử lý</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $db->query('SELECT COUNT(*) as count FROM payments WHERE status = "processing"');
                                echo $db->single()['count'];
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Chờ thanh toán</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $db->query('SELECT COUNT(*) as count FROM payments WHERE status = "pending"');
                                echo $db->single()['count'];
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Danh sách đơn hàng -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách đơn hàng</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover datatable" id="orders-table" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Mã đơn hàng</th>
                            <th>Sách</th>
                            <th>Người mua</th>
                            <th>Người bán</th>
                            <th>Tổng tiền</th>
                            <th>PT Thanh toán</th>
                            <th>Trạng thái</th>
                            <th>TT Vận chuyển</th>
                            <th>Ngày tạo</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $order): ?>
                        <tr>
                            <td><span class="badge bg-primary"><?php echo $order['transaction_code']; ?></span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="../uploads/books/<?php echo $order['book_image']; ?>" alt="<?php echo $order['book_title']; ?>" 
                                         class="img-thumbnail me-2" style="width: 50px; height: 70px; object-fit: cover;">
                                    <div>
                                        <span class="d-block"><?php echo $order['book_title']; ?></span>
                                        <small class="text-muted"><?php echo $order['book_author']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $order['buyer_username'] . '<br><small class="text-muted">' . $order['buyer_email'] . '</small>'; ?></td>
                            <td><?php echo $order['seller_username']; ?></td>
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
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="order_detail.php?id=<?php echo $order['transaction_code']; ?>">Chi tiết</a></li>
                                        <li><h6 class="dropdown-header">Trạng thái thanh toán</h6></li>
                                        <li><a class="dropdown-item" href="?action=status&id=<?php echo $order['transaction_code']; ?>&status=pending">Chờ xác nhận</a></li>
                                        <li><a class="dropdown-item" href="?action=status&id=<?php echo $order['transaction_code']; ?>&status=paid">Đã thanh toán</a></li>
                                        <li><a class="dropdown-item" href="?action=status&id=<?php echo $order['transaction_code']; ?>&status=processing">Đang xử lý</a></li>
                                        <li><a class="dropdown-item" href="?action=status&id=<?php echo $order['transaction_code']; ?>&status=completed">Hoàn thành</a></li>
                                        <li><a class="dropdown-item" href="?action=status&id=<?php echo $order['transaction_code']; ?>&status=cancelled">Hủy đơn hàng</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><h6 class="dropdown-header">Trạng thái vận chuyển</h6></li>
                                        <li><a class="dropdown-item" href="?action=shipping&id=<?php echo $order['transaction_code']; ?>&status=pending">Chờ xử lý</a></li>
                                        <li><a class="dropdown-item" href="?action=shipping&id=<?php echo $order['transaction_code']; ?>&status=processing">Đang chuẩn bị</a></li>
                                        <li><a class="dropdown-item" href="?action=shipping&id=<?php echo $order['transaction_code']; ?>&status=shipped">Đang giao</a></li>
                                        <li><a class="dropdown-item" href="?action=shipping&id=<?php echo $order['transaction_code']; ?>&status=delivered">Đã giao</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="?action=delete&id=<?php echo $order['transaction_code']; ?>" 
                                               onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này?')">Xóa đơn hàng</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Hàm xuất dữ liệu sang CSV
function exportToCSV() {
    // Lấy bảng
    const table = document.getElementById('orders-table');
    
    // Tạo mảng chứa dữ liệu
    let csv = [];
    
    // Lấy tiêu đề
    let headers = [];
    for (let i = 0; i < table.tHead.rows[0].cells.length - 1; i++) { // Bỏ cột Hành động
        headers.push(table.tHead.rows[0].cells[i].textContent);
    }
    csv.push(headers.join(','));
    
    // Lấy dữ liệu
    for (let i = 0; i < table.tBodies[0].rows.length; i++) {
        let row = [];
        for (let j = 0; j < table.tBodies[0].rows[i].cells.length - 1; j++) { // Bỏ cột Hành động
            // Loại bỏ dấu phẩy và thay thế bằng dấu chấm phẩy để tránh xung đột với định dạng CSV
            let cellText = table.tBodies[0].rows[i].cells[j].textContent.trim().replace(/,/g, ';');
            row.push('"' + cellText + '"');
        }
        csv.push(row.join(','));
    }
    
    // Tạo link tải xuống
    const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', 'orders_' + new Date().toLocaleDateString() + '.csv');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php include('includes/footer.php'); ?>