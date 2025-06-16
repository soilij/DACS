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
require_once '../classes/Exchange.php';
require_once '../classes/User.php';
require_once '../classes/Notification.php';

// Khởi tạo đối tượng cần thiết
$exchange = new Exchange();
$user = new User();
$notification = new Notification();
$db = new Database();

// Xử lý hành động
$message = '';
$message_type = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $exchange_id = $_GET['id'];
    
    // Lấy thông tin giao dịch trước khi xử lý
    $exchange_info = $exchange->getById($exchange_id);
    
    if (!$exchange_info) {
        $message = 'Không tìm thấy giao dịch!';
        $message_type = 'danger';
    } else {
        switch ($action) {
            case 'complete':
                // Đánh dấu hoàn thành giao dịch
                if ($exchange->adminCompleteExchange($exchange_id)) {
                    $message = 'Đã đánh dấu hoàn thành giao dịch thành công!';
                    $message_type = 'success';
                    
                    // Thông báo cho các bên liên quan
                    $notification_data = [
                        'user_id' => $exchange_info['requester_id'],
                        'message' => 'Giao dịch trao đổi sách đã được đánh dấu hoàn thành bởi admin.',
                        'link' => 'pages/exchange_requests.php?id=' . $exchange_id
                    ];
                    $notification->create($notification_data);
                    
                    $notification_data = [
                        'user_id' => $exchange_info['owner_id'],
                        'message' => 'Giao dịch trao đổi sách đã được đánh dấu hoàn thành bởi admin.',
                        'link' => 'pages/exchange_requests.php?id=' . $exchange_id
                    ];
                    $notification->create($notification_data);
                } else {
                    $message = 'Có lỗi xảy ra khi đánh dấu hoàn thành giao dịch!';
                    $message_type = 'danger';
                }
                break;
                
            case 'cancel':
                // Hủy giao dịch
                if ($exchange->adminCancelExchange($exchange_id)) {
                    $message = 'Đã hủy giao dịch thành công!';
                    $message_type = 'success';
                    
                    // Thông báo cho các bên liên quan
                    $notification_data = [
                        'user_id' => $exchange_info['requester_id'],
                        'message' => 'Giao dịch trao đổi sách đã bị hủy bởi admin.',
                        'link' => 'pages/exchange_requests.php?id=' . $exchange_id
                    ];
                    $notification->create($notification_data);
                    
                    $notification_data = [
                        'user_id' => $exchange_info['owner_id'],
                        'message' => 'Giao dịch trao đổi sách đã bị hủy bởi admin.',
                        'link' => 'pages/exchange_requests.php?id=' . $exchange_id
                    ];
                    $notification->create($notification_data);
                } else {
                    $message = 'Có lỗi xảy ra khi hủy giao dịch!';
                    $message_type = 'danger';
                }
                break;
                
            case 'delete':
                // Xóa giao dịch
                if ($exchange->adminDeleteExchange($exchange_id)) {
                    $message = 'Đã xóa giao dịch thành công!';
                    $message_type = 'success';
                } else {
                    $message = 'Có lỗi xảy ra khi xóa giao dịch!';
                    $message_type = 'danger';
                }
                break;
                
            default:
                // Không làm gì
                break;
        }
    }
}

// Lấy tham số lọc
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Xây dựng câu truy vấn
$sql = '
    SELECT er.*, 
           owner.username as owner_username, 
           requester.username as requester_username,
           ob.title as owner_book_title, 
           rb.title as requester_book_title
    FROM exchange_requests er
    JOIN users owner ON er.owner_id = owner.id
    JOIN users requester ON er.requester_id = requester.id
    JOIN books ob ON er.owner_book_id = ob.id
    LEFT JOIN books rb ON er.requester_book_id = rb.id
    WHERE 1=1
';

// Thêm điều kiện lọc
$params = [];

if (!empty($search)) {
    $sql .= ' AND (ob.title LIKE ? OR rb.title LIKE ? OR owner.username LIKE ? OR requester.username LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_status)) {
    $sql .= ' AND er.status = ?';
    $params[] = $filter_status;
}

// Thêm sắp xếp
$sql .= ' ORDER BY er.created_at DESC';

// Thực hiện truy vấn
$db->query($sql);

// Bind tham số
foreach ($params as $key => $param) {
    $db->bind($key + 1, $param);
}

// Lấy kết quả
$exchanges = $db->resultSet();

// Admin header
include('includes/header.php');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Quản lý giao dịch trao đổi</h1>
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
            <h6 class="m-0 font-weight-bold text-primary">Bộ lọc</h6>
        </div>
        <div class="card-body">
            <form action="" method="get" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="search" class="form-label">Tìm kiếm</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tiêu đề sách, người dùng...">
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">Trạng thái</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tất cả</option>
                        <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                        <option value="accepted" <?php echo $filter_status == 'accepted' ? 'selected' : ''; ?>>Đã chấp nhận</option>
                        <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                        <option value="rejected" <?php echo $filter_status == 'rejected' ? 'selected' : ''; ?>>Đã từ chối</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Danh sách giao dịch -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách giao dịch trao đổi</h6>
            <div>
                <a href="?status=pending" class="btn btn-warning btn-sm">
                    <i class="fas fa-clock me-1"></i> Giao dịch đang chờ
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover datatable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">Người yêu cầu</th>
                            <th width="15%">Chủ sở hữu</th>
                            <th width="20%">Sách trao đổi</th>
                            <th width="10%">Tiền kèm theo</th>
                            <th width="10%">Trạng thái</th>
                            <th width="10%">Ngày tạo</th>
                            <th width="15%">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($exchanges as $exchange): ?>
                        <tr>
                            <td><?php echo $exchange['id']; ?></td>
                            <td><?php echo $exchange['requester_username']; ?></td>
                            <td><?php echo $exchange['owner_username']; ?></td>
                            <td>
                                <?php echo $exchange['owner_book_title']; ?>
                                <?php if($exchange['requester_book_title']): ?>
                                    <br><i class="fas fa-exchange-alt"></i>
                                    <?php echo $exchange['requester_book_title']; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($exchange['is_money_involved']): ?>
                                    <?php echo number_format($exchange['amount'], 0, ',', '.'); ?> đ
                                <?php else: ?>
                                    <span class="text-muted">Không</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($exchange['status'] == 'pending'): ?>
                                    <span class="badge bg-warning text-dark">Chờ xác nhận</span>
                                <?php elseif($exchange['status'] == 'accepted'): ?>
                                    <span class="badge bg-info">Đã chấp nhận</span>
                                <?php elseif($exchange['status'] == 'completed'): ?>
                                    <span class="badge bg-success">Hoàn thành</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Đã từ chối</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($exchange['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="exchange_detail.php?id=<?php echo $exchange['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if($exchange['status'] == 'pending' || $exchange['status'] == 'accepted'): ?>
                                    <a href="?action=complete&id=<?php echo $exchange['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Bạn có chắc chắn muốn đánh dấu hoàn thành giao dịch này?');">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <a href="?action=cancel&id=<?php echo $exchange['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn hủy giao dịch này?');">
                                        <i class="fas fa-times"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <a href="?action=delete&id=<?php echo $exchange['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa giao dịch này?\nHành động này không thể hoàn tác!');">
                                        <i class="fas fa-trash"></i>
                                    </a>
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

<?php include('includes/footer.php'); ?>