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
require_once '../classes/Exchange.php';

// Khởi tạo đối tượng cần thiết
$user = new User();
$book = new Book();
$exchange = new Exchange();
$db = new Database();

// Lấy thống kê tổng quan
// Tổng số người dùng
$db->query('SELECT COUNT(*) as total_users FROM users');
$total_users = $db->single()['total_users'];

// Tổng số sách
$db->query('SELECT COUNT(*) as total_books FROM books');
$total_books = $db->single()['total_books'];

// Số sách chờ duyệt
$db->query('SELECT COUNT(*) as pending_books FROM books WHERE status = "pending_approval"');
$pending_books = $db->single()['pending_books'];

// Tổng số giao dịch trao đổi
$db->query('SELECT COUNT(*) as total_exchanges FROM exchange_requests');
$total_exchanges = $db->single()['total_exchanges'];

// Giao dịch đang chờ
$db->query('SELECT COUNT(*) as pending_exchanges FROM exchange_requests WHERE status = "pending"');
$pending_exchanges = $db->single()['pending_exchanges'];

// Giao dịch thành công
$db->query('SELECT COUNT(*) as completed_exchanges FROM exchange_requests WHERE status = "completed"');
$completed_exchanges = $db->single()['completed_exchanges'];

// Người dùng mới nhất
$db->query('SELECT * FROM users ORDER BY created_at DESC LIMIT 5');
$newest_users = $db->resultSet();

// Sách mới nhất
$db->query('
    SELECT b.*, u.username 
    FROM books b
    JOIN users u ON b.user_id = u.id
    ORDER BY b.created_at DESC 
    LIMIT 5
');
$newest_books = $db->resultSet();

// Giao dịch gần đây
$db->query('
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
    ORDER BY er.created_at DESC
    LIMIT 5
');
$recent_exchanges = $db->resultSet();

// Admin header
include('includes/header.php');
?>

<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Bảng điều khiển</h1>
    
    <!-- Thẻ thống kê -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tổng người dùng</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Tổng sách</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_books; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Sách chờ duyệt</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_books; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Tổng giao dịch</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_exchanges; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sách mới đăng -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Sách mới đăng</h6>
                    <a href="books.php" class="btn btn-sm btn-primary">Xem tất cả</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Tiêu đề</th>
                                    <th>Người đăng</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày đăng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($newest_books as $book): ?>
                                <tr>
                                    <td><?php echo $book['title']; ?></td>
                                    <td><?php echo $book['username']; ?></td>
                                    <td>
                                        <?php if($book['status'] == 'available'): ?>
                                            <span class="badge bg-success">Có sẵn</span>
                                        <?php elseif($book['status'] == 'pending_approval'): ?>
                                            <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                        <?php elseif($book['status'] == 'pending'): ?>
                                            <span class="badge bg-info">Đang giao dịch</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Đã trao đổi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($book['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Người dùng mới nhất -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Người dùng mới nhất</h6>
                    <a href="users.php" class="btn btn-sm btn-primary">Xem tất cả</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Tên đăng nhập</th>
                                    <th>Email</th>
                                    <th>Họ tên</th>
                                    <th>Ngày đăng ký</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($newest_users as $user): ?>
                                <tr>
                                    <td><?php echo $user['username']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo $user['full_name']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Giao dịch gần đây -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Giao dịch gần đây</h6>
            <a href="exchanges.php" class="btn btn-sm btn-primary">Xem tất cả</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Người yêu cầu</th>
                            <th>Chủ sở hữu</th>
                            <th>Sách</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_exchanges as $exchange): ?>
                        <tr>
                            <td><?php echo $exchange['id']; ?></td>
                            <td><?php echo $exchange['requester_username']; ?></td>
                            <td><?php echo $exchange['owner_username']; ?></td>
                            <td>
                                <?php echo $exchange['owner_book_title']; ?>
                                <?php if($exchange['requester_book_title']): ?>
                                    <i class="fas fa-exchange-alt mx-2"></i>
                                    <?php echo $exchange['requester_book_title']; ?>
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
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include('includes/footer.php'); ?>