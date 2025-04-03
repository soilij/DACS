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
require_once '../classes/User.php';
require_once '../classes/Book.php';
require_once '../classes/Notification.php';

// Khởi tạo đối tượng cần thiết
$user = new User();
$book = new Book();
$notification = new Notification();
$db = new Database();

// Xử lý hành động
$message = '';
$message_type = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $user_id = $_GET['id'];
    
    // Không cho phép admin xóa chính mình
    if ($user_id == $_SESSION['user_id'] && ($action == 'delete' || $action == 'block')) {
        $message = 'Bạn không thể xóa hoặc khóa tài khoản của chính mình!';
        $message_type = 'danger';
    } else {
        switch ($action) {
            case 'make_admin':
                // Cấp quyền admin
                $db->query('UPDATE users SET is_admin = 1 WHERE id = :id');
                $db->bind(':id', $user_id);
                
                if ($db->execute()) {
                    $message = 'Đã cấp quyền admin thành công!';
                    $message_type = 'success';
                    
                    // Gửi thông báo cho người dùng
                    $notification_data = [
                        'user_id' => $user_id,
                        'message' => 'Bạn đã được cấp quyền quản trị viên.',
                        'link' => 'profile.php'
                    ];
                    $notification->create($notification_data);
                } else {
                    $message = 'Có lỗi xảy ra khi cấp quyền admin!';
                    $message_type = 'danger';
                }
                break;
                
            case 'remove_admin':
                // Thu hồi quyền admin
                $db->query('UPDATE users SET is_admin = 0 WHERE id = :id');
                $db->bind(':id', $user_id);
                
                if ($db->execute()) {
                    $message = 'Đã thu hồi quyền admin thành công!';
                    $message_type = 'success';
                    
                    // Gửi thông báo cho người dùng
                    $notification_data = [
                        'user_id' => $user_id,
                        'message' => 'Quyền quản trị viên của bạn đã bị thu hồi.',
                        'link' => 'profile.php'
                    ];
                    $notification->create($notification_data);
                } else {
                    $message = 'Có lỗi xảy ra khi thu hồi quyền admin!';
                    $message_type = 'danger';
                }
                break;
                
            case 'block':
                // Khóa tài khoản
                $db->query('UPDATE users SET is_blocked = 1 WHERE id = :id');
                $db->bind(':id', $user_id);
                
                if ($db->execute()) {
                    $message = 'Đã khóa tài khoản thành công!';
                    $message_type = 'success';
                    
                    // Gửi thông báo cho người dùng
                    $notification_data = [
                        'user_id' => $user_id,
                        'message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ admin để biết thêm chi tiết.',
                        'link' => 'profile.php'
                    ];
                    $notification->create($notification_data);
                } else {
                    $message = 'Có lỗi xảy ra khi khóa tài khoản!';
                    $message_type = 'danger';
                }
                break;
                
            case 'unblock':
                // Mở khóa tài khoản
                $db->query('UPDATE users SET is_blocked = 0 WHERE id = :id');
                $db->bind(':id', $user_id);
                
                if ($db->execute()) {
                    $message = 'Đã mở khóa tài khoản thành công!';
                    $message_type = 'success';
                    
                    // Gửi thông báo cho người dùng
                    $notification_data = [
                        'user_id' => $user_id,
                        'message' => 'Tài khoản của bạn đã được mở khóa.',
                        'link' => 'profile.php'
                    ];
                    $notification->create($notification_data);
                } else {
                    $message = 'Có lỗi xảy ra khi mở khóa tài khoản!';
                    $message_type = 'danger';
                }
                break;
                
            case 'delete':
                // Xóa tài khoản
                // Lưu ý: Cần cẩn thận khi xóa tài khoản vì sẽ ảnh hưởng đến dữ liệu liên quan
                $db->query('DELETE FROM users WHERE id = :id');
                $db->bind(':id', $user_id);
                
                if ($db->execute()) {
                    $message = 'Đã xóa tài khoản thành công!';
                    $message_type = 'success';
                } else {
                    $message = 'Có lỗi xảy ra khi xóa tài khoản!';
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
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Xây dựng câu truy vấn
$sql = 'SELECT * FROM users WHERE 1=1';

// Thêm điều kiện lọc
$params = [];

if (!empty($search)) {
    $sql .= ' AND (username LIKE ? OR email LIKE ? OR full_name LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_role === 'admin') {
    $sql .= ' AND is_admin = 1';
} elseif ($filter_role === 'user') {
    $sql .= ' AND is_admin = 0';
}

if ($filter_status === 'blocked') {
    $sql .= ' AND is_blocked = 1';
} elseif ($filter_status === 'active') {
    $sql .= ' AND is_blocked = 0';
}

// Thêm sắp xếp
$sql .= ' ORDER BY created_at DESC';

// Thực hiện truy vấn
$db->query($sql);

// Bind tham số
foreach ($params as $key => $param) {
    $db->bind($key + 1, $param);
}

// Lấy kết quả
$users = $db->resultSet();

// Admin header
include('includes/header.php');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Quản lý người dùng</h1>
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
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tên đăng nhập, email, họ tên...">
                </div>
                <div class="col-md-3">
                    <label for="role" class="form-label">Vai trò</label>
                    <select class="form-select" id="role" name="role">
                        <option value="">Tất cả</option>
                        <option value="admin" <?php echo $filter_role == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="user" <?php echo $filter_role == 'user' ? 'selected' : ''; ?>>Người dùng</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Trạng thái</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tất cả</option>
                        <option value="active" <?php echo $filter_status == 'active' ? 'selected' : ''; ?>>Đang hoạt động</option>
                        <option value="blocked" <?php echo $filter_status == 'blocked' ? 'selected' : ''; ?>>Đã khóa</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Danh sách người dùng -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách người dùng</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover datatable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên đăng nhập</th>
                            <th>Email</th>
                            <th>Họ tên</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th>Ngày đăng ký</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo $user['username']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo $user['full_name']; ?></td>
                            <td>
                                <?php if($user['is_admin'] == 1): ?>
                                    <span class="badge bg-primary">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Người dùng</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($user['is_blocked'] == 1): ?>
                                    <span class="badge bg-danger">Đã khóa</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Đang hoạt động</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="../pages/profile.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if($user['id'] != $_SESSION['user_id']): ?>
                                        <?php if($user['is_admin'] == 0): ?>
                                            <a href="?action=make_admin&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary" onclick="return confirm('Bạn có chắc chắn muốn cấp quyền admin cho người dùng này?');">
                                                <i class="fas fa-user-shield"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=remove_admin&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Bạn có chắc chắn muốn thu hồi quyền admin của người dùng này?');">
                                                <i class="fas fa-user-minus"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if($user['is_blocked'] == 0): ?>
                                            <a href="?action=block&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn khóa tài khoản này?');">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=unblock&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Bạn có chắc chắn muốn mở khóa tài khoản này?');">
                                                <i class="fas fa-unlock"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản này?\nHành động này không thể hoàn tác và sẽ xóa tất cả dữ liệu liên quan!');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
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