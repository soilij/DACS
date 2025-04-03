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
require_once '../classes/Book.php';
require_once '../classes/Category.php';
require_once '../classes/User.php';
require_once '../classes/Notification.php';  

// Khởi tạo đối tượng cần thiết
$book = new Book();
$category = new Category();
$user = new User();
$db = new Database();
$notify = new Notification();  

// Xử lý hành động
$message = '';
$message_type = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $book_id = $_GET['id'];
    
    switch ($action) {
        case 'approve':
            // Cập nhật trạng thái sách thành "available"
            if ($book->updateStatus($book_id, 'available')) {
                $message = 'Đã duyệt sách thành công!';
                $message_type = 'success';
                
                // Lấy thông tin sách và người dùng
                $book_info = $book->getById($book_id);
                if ($book_info) {
                    // Tạo thông báo cho người dùng
                    $notify = new Notification();
                    $data = [
                        'user_id' => $book_info['user_id'],
                        'message' => 'Sách "' . $book_info['title'] . '" của bạn đã được duyệt.',
                        'link' => 'pages/book_details.php?id=' . $book_id
                    ];
                    $notify->create($data);
                }
            } else {
                $message = 'Có lỗi xảy ra khi duyệt sách!';
                $message_type = 'danger';
            }
            break;
            
        case 'reject':
            // Cập nhật trạng thái sách thành "rejected"
            if ($book->updateStatus($book_id, 'rejected')) {
                $message = 'Đã từ chối sách thành công!';
                $message_type = 'success';
                
                // Lấy thông tin sách và người dùng
                $book_info = $book->getById($book_id);
                if ($book_info) {
                    // Tạo thông báo cho người dùng
                    $notify = new Notification();
                    $data = [
                        'user_id' => $book_info['user_id'],
                        'message' => 'Sách "' . $book_info['title'] . '" của bạn đã bị từ chối.',
                        'link' => 'pages/my_books.php'
                    ];
                    $notify->create($data);
                }
            } else {
                $message = 'Có lỗi xảy ra khi từ chối sách!';
                $message_type = 'danger';
            }
            break;
            
        case 'delete':
            // Xóa sách
            if ($book->adminDelete($book_id)) {
                $message = 'Đã xóa sách thành công!';
                $message_type = 'success';
            } else {
                $message = 'Có lỗi xảy ra khi xóa sách!';
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
$filter_category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Xây dựng câu truy vấn
$sql = '
    SELECT b.*, c.name as category_name, u.username
    FROM books b
    LEFT JOIN categories c ON b.category_id = c.id
    JOIN users u ON b.user_id = u.id
    WHERE 1=1
';

// Thêm điều kiện lọc
$params = [];

if (!empty($search)) {
    $sql .= ' AND (b.title LIKE ? OR b.author LIKE ? OR u.username LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_status)) {
    $sql .= ' AND b.status = ?';
    $params[] = $filter_status;
}

if (!empty($filter_category)) {
    $sql .= ' AND b.category_id = ?';
    $params[] = $filter_category;
}

// Thêm sắp xếp
$sql .= ' ORDER BY b.created_at DESC';

// Thực hiện truy vấn
$db->query($sql);

// Bind tham số
foreach ($params as $key => $param) {
    $db->bind($key + 1, $param);
}

// Lấy kết quả
$books = $db->resultSet();

// Lấy danh sách danh mục
$categories = $category->getAll();

// Admin header
include('includes/header.php');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Quản lý sách</h1>
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
                <div class="col-md-3">
                    <label for="search" class="form-label">Tìm kiếm</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tiêu đề, tác giả, người đăng...">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Trạng thái</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tất cả</option>
                        <option value="pending_approval" <?php echo $filter_status == 'pending_approval' ? 'selected' : ''; ?>>Chờ duyệt</option>
                        <option value="available" <?php echo $filter_status == 'available' ? 'selected' : ''; ?>>Có sẵn</option>
                        <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Đang giao dịch</option>
                        <option value="exchanged" <?php echo $filter_status == 'exchanged' ? 'selected' : ''; ?>>Đã trao đổi</option>
                        <option value="rejected" <?php echo $filter_status == 'rejected' ? 'selected' : ''; ?>>Đã từ chối</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">Danh mục</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">Tất cả</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $filter_category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo $cat['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Danh sách sách -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách sách</h6>
            <div>
                <a href="?status=pending_approval" class="btn btn-warning btn-sm">
                    <i class="fas fa-clock me-1"></i> Sách chờ duyệt
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover datatable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="20%">Tiêu đề</th>
                            <th width="15%">Tác giả</th>
                            <th width="10%">Danh mục</th>
                            <th width="10%">Người đăng</th>
                            <th width="10%">Trạng thái</th>
                            <th width="10%">Loại</th>
                            <th width="10%">Ngày đăng</th>
                            <th width="10%">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($books as $book): ?>
                        <tr>
                            <td><?php echo $book['id']; ?></td>
                            <td>
                                <a href="../pages/book_details.php?id=<?php echo $book['id']; ?>" target="_blank">
                                    <?php echo $book['title']; ?>
                                </a>
                            </td>
                            <td><?php echo $book['author']; ?></td>
                            <td><?php echo $book['category_name']; ?></td>
                            <td><?php echo $book['username']; ?></td>
                            <td>
                                <?php if($book['status'] == 'available'): ?>
                                    <span class="badge bg-success">Có sẵn</span>
                                <?php elseif($book['status'] == 'pending_approval'): ?>
                                    <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                <?php elseif($book['status'] == 'pending'): ?>
                                    <span class="badge bg-info">Đang giao dịch</span>
                                <?php elseif($book['status'] == 'exchanged'): ?>
                                    <span class="badge bg-secondary">Đã trao đổi</span>
                                <?php elseif($book['status'] == 'rejected'): ?>
                                    <span class="badge bg-danger">Đã từ chối</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($book['exchange_type'] == 'exchange_only'): ?>
                                    <span class="badge bg-success">Chỉ trao đổi</span>
                                <?php elseif($book['exchange_type'] == 'sell_only'): ?>
                                    <span class="badge bg-danger">Chỉ bán</span>
                                <?php else: ?>
                                    <span class="badge bg-primary">Trao đổi/Bán</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($book['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                <a href="book_detail.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                    
                                    <?php if($book['status'] == 'pending_approval'): ?>
                                    <a href="?action=approve&id=<?php echo $book['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Bạn có chắc chắn muốn duyệt sách này?');">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <a href="?action=reject&id=<?php echo $book['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn từ chối sách này?');">
                                        <i class="fas fa-times"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <a href="?action=delete&id=<?php echo $book['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa sách này?\nHành động này không thể hoàn tác!');">
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