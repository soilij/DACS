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
require_once '../classes/Report.php';
require_once '../classes/User.php';
require_once '../classes/Book.php';
require_once '../classes/Notification.php';

// Khởi tạo đối tượng cần thiết
$report = new Report();
$user = new User();
$book = new Book();

// Kiểm tra ID báo cáo
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: reports_list.php');
    exit();
}

$report_id = $_GET['id'];

// Lấy thông tin báo cáo
$report_info = $report->getReportById($report_id);

if (!$report_info) {
    header('Location: reports_list.php');
    exit();
}

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $admin_note = isset($_POST['admin_note']) ? trim($_POST['admin_note']) : '';

    // Gỡ hạn chế mua
    if ($action == 'unrestrict_buy') {
        $db = new Database();
        $db->query('UPDATE users SET can_buy = 1 WHERE id = :id');
        $db->bind(':id', $report_info['reported_user_id']);
        if ($db->execute()) {
            // Tạo thông báo cho người dùng
            $notification = new Notification();
            $notification->create([
                'user_id' => $report_info['reported_user_id'],
                'message' => 'Tài khoản của bạn đã được mở lại quyền mua sách.',
                'link' => 'pages/profile.php'
            ]);
            $message = 'Đã mở lại quyền mua sách cho người dùng!';
            $message_type = 'success';
            header('Location: report_detail.php?id=' . $report_id . '&message=' . urlencode($message) . '&type=' . $message_type);
            exit();
        }
    }

    // Gỡ hạn chế bán
    if ($action == 'unrestrict_sell') {
        $db = new Database();
        $db->query('UPDATE users SET can_sell = 1 WHERE id = :id');
        $db->bind(':id', $report_info['reported_user_id']);
        if ($db->execute()) {
            $notification = new Notification();
            $notification->create([
                'user_id' => $report_info['reported_user_id'],
                'message' => 'Tài khoản của bạn đã được mở lại quyền bán/trao đổi sách.',
                'link' => 'pages/profile.php'
            ]);
            $message = 'Đã mở lại quyền bán/trao đổi sách cho người dùng!';
            $message_type = 'success';
            header('Location: report_detail.php?id=' . $report_id . '&message=' . urlencode($message) . '&type=' . $message_type);
            exit();
        }
    }

    // Gỡ khóa vĩnh viễn
    if ($action == 'unban') {
        $db = new Database();
        $db->query('UPDATE users SET is_blocked = 0 WHERE id = :id');
        $db->bind(':id', $report_info['reported_user_id']);
        if ($db->execute()) {
            $notification = new Notification();
            $notification->create([
                'user_id' => $report_info['reported_user_id'],
                'message' => 'Tài khoản của bạn đã được mở khóa vĩnh viễn.',
                'link' => 'pages/profile.php'
            ]);
            $message = 'Đã mở khóa vĩnh viễn cho người dùng!';
            $message_type = 'success';
            header('Location: report_detail.php?id=' . $report_id . '&message=' . urlencode($message) . '&type=' . $message_type);
            exit();
        }
    }

    if (empty($admin_note)) {
        $message = 'Vui lòng nhập ghi chú của quản trị viên!';
        $message_type = 'danger';
    } else {
        if ($report->applyAction($report_id, $action, $_SESSION['user_id'], $admin_note)) {
            $message = 'Đã áp dụng hành động thành công!';
            $message_type = 'success';
            
            // Refresh để hiển thị thông tin mới
            header('Location: report_detail.php?id=' . $report_id . '&message=' . urlencode($message) . '&type=' . $message_type);
            exit();
        } else {
            $message = 'Có lỗi xảy ra khi áp dụng hành động!';
            $message_type = 'danger';
        }
    }
}

// Đánh dấu báo cáo là đã xem xét nếu đang ở trạng thái chờ xử lý
if ($report_info['status'] == 'pending') {
    $report->updateReportStatus($report_id, 'reviewed', $_SESSION['user_id']);
    // Refresh để hiển thị trạng thái mới
    header('Location: report_detail.php?id=' . $report_id);
    exit();
}

// Hiển thị thông báo từ redirect
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = $_GET['message'];
    $message_type = $_GET['type'];
}

// Đảm bảo biến luôn tồn tại
if (!isset($message)) $message = '';
if (!isset($message_type)) $message_type = '';

// Lấy lại thông tin báo cáo mới nhất
$report_info = $report->getReportById($report_id);

// Tính báo cáo người dùng có nhiều nhất là bao nhiêu
$user_reports = $report->getReportsByUserId($report_info['reported_user_id']);
$report_count = count($user_reports);

// Admin header
include('includes/header.php');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Chi tiết báo cáo</h1>
        <a href="reports_list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Quay lại danh sách
        </a>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Thông tin báo cáo -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Thông tin báo cáo #<?php echo $report_info['id']; ?></h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Thông tin người bị báo cáo</h5>
                            <p><strong>Tên đăng nhập:</strong> <a href="../pages/profile.php?id=<?php echo $report_info['reported_user_id']; ?>" target="_blank"><?php echo $report_info['reported_username']; ?></a></p>
                            <p><strong>Họ tên:</strong> <?php echo $report_info['reported_fullname']; ?></p>
                            <p><strong>Email:</strong> <?php echo $report_info['reported_email']; ?></p>
                            <p><strong>Cấp độ cảnh báo:</strong> 
                                <span class="badge bg-<?php 
                                    if ($report_info['warning_level'] <= 1) echo 'success';
                                    elseif ($report_info['warning_level'] <= 3) echo 'warning';
                                    else echo 'danger';
                                ?>">
                                    <?php echo $report_info['warning_level']; ?>
                                </span>
                            </p>
                            <p><strong>Trạng thái tài khoản:</strong>
                                <?php if ($report_info['is_blocked']): ?>
                                    <span class="badge bg-danger">Đã khóa</span>
                                    <form method="post" class="d-inline">
                                        <button type="submit" name="action" value="unban" class="btn btn-sm btn-success ms-2" onclick="return confirm('Bạn có chắc chắn muốn mở khóa vĩnh viễn tài khoản này?');">
                                            <i class="fas fa-unlock"></i> Gỡ khóa
                                        </button>
                                    </form>
                                <?php elseif ($report_info['suspended_until'] && strtotime($report_info['suspended_until']) > time()): ?>
                                    <span class="badge bg-warning">Tạm khóa đến <?php echo date('d/m/Y H:i', strtotime($report_info['suspended_until'])); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success">Đang hoạt động</span>
                                <?php endif; ?>
                            </p>
                            <p><strong>Quyền mua bán:</strong>
                                <?php if (!$report_info['can_buy']): ?>
                                    <span class="badge bg-warning">Không thể mua</span>
                                    <form method="post" class="d-inline">
                                        <button type="submit" name="action" value="unrestrict_buy" class="btn btn-sm btn-success ms-2">
                                            <i class="fas fa-unlock"></i> Mở lại quyền mua
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if (!$report_info['can_sell']): ?>
                                    <span class="badge bg-warning">Không thể bán</span>
                                    <form method="post" class="d-inline">
                                        <button type="submit" name="action" value="unrestrict_sell" class="btn btn-sm btn-success ms-2">
                                            <i class="fas fa-unlock"></i> Mở lại quyền bán
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($report_info['can_buy'] && $report_info['can_sell']): ?>
                                    <span class="badge bg-success">Đầy đủ quyền</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h5>Thông tin báo cáo</h5>
                            <p><strong>Người báo cáo:</strong> <?php echo $report_info['reporter_username']; ?></p>
                            <p><strong>Ngày báo cáo:</strong> <?php echo date('d/m/Y H:i', strtotime($report_info['created_at'])); ?></p>
                            <p><strong>Loại báo cáo:</strong> 
                                <?php 
                                $report_types = [
                                    'spam' => 'Spam',
                                    'inappropriate_content' => 'Nội dung không phù hợp',
                                    'fake_book' => 'Sách giả',
                                    'scam' => 'Lừa đảo',
                                    'harassment' => 'Quấy rối',
                                    'other' => 'Khác'
                                ];
                                echo isset($report_types[$report_info['report_type']]) ? $report_types[$report_info['report_type']] : $report_info['report_type'];
                                ?>
                            </p>
                            <p><strong>Trạng thái:</strong> 
                                <?php if($report_info['status'] == 'pending'): ?>
                                    <span class="badge bg-warning text-dark">Chờ xử lý</span>
                                <?php elseif($report_info['status'] == 'reviewed'): ?>
                                    <span class="badge bg-info">Đang xem xét</span>
                                <?php elseif($report_info['status'] == 'resolved'): ?>
                                    <span class="badge bg-success">Đã giải quyết</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Đã từ chối</span>
                                <?php endif; ?>
                            </p>
                            <?php if($report_info['book_id']): ?>
                            <p><strong>Sách liên quan:</strong> 
                                <a href="../pages/book_details.php?id=<?php echo $report_info['book_id']; ?>" target="_blank">
                                    <?php echo $report_info['book_title']; ?>
                                </a>
                                bởi <?php echo $report_info['book_author']; ?>
                            </p>
                            <?php endif; ?>
                            <?php if($report_info['exchange_id']): ?>
                            <p><strong>Giao dịch liên quan:</strong> 
                                <a href="../pages/exchange_request.php?id=<?php echo $report_info['exchange_id']; ?>" target="_blank">
                                    Giao dịch #<?php echo $report_info['exchange_id']; ?>
                                </a>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Lý do báo cáo</h5>
                        <div class="p-3 bg-light rounded">
                            <?php echo nl2br(htmlspecialchars($report_info['report_reason'])); ?>
                        </div>
                    </div>
                    
                    <?php if($report_info['admin_note']): ?>
                    <div class="mb-4">
                        <h5>Ghi chú của quản trị viên</h5>
                        <div class="p-3 bg-light rounded">
                            <p><strong>Quản trị viên:</strong> <?php echo $report_info['admin_username']; ?></p>
                            <p><?php echo nl2br(htmlspecialchars($report_info['admin_note'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($report_info['action_taken'] && $report_info['action_taken'] != 'none'): ?>
                    <div class="mb-4">
                        <h5>Hành động đã thực hiện</h5>
                        <div class="p-3 bg-light rounded">
                            <?php 
                            $action_texts = [
                                'warning' => 'Đã cảnh báo người dùng',
                                'restrict_buy' => 'Đã hạn chế quyền mua sách',
                                'restrict_sell' => 'Đã hạn chế quyền bán/trao đổi sách',
                                'suspend' => 'Đã tạm khóa tài khoản 30 ngày',
                                'ban' => 'Đã khóa vĩnh viễn tài khoản'
                            ];
                            echo isset($action_texts[$report_info['action_taken']]) ? 
                                $action_texts[$report_info['action_taken']] : 
                                $report_info['action_taken']; 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($report_info['status'] == 'reviewed'): ?>
                    <!-- Form xử lý báo cáo -->
                    <h5 class="mt-4">Thực hiện hành động</h5>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="admin_note" class="form-label">Ghi chú của quản trị viên</label>
                            <textarea class="form-control" id="admin_note" name="admin_note" rows="3" required></textarea>
                        </div>
                        
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" name="action" value="warning" class="btn btn-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i> Cảnh báo
                            </button>
                            <button type="submit" name="action" value="restrict_buy" class="btn btn-info">
                                <i class="fas fa-ban me-1"></i> Hạn chế mua sách
                            </button>
                            <button type="submit" name="action" value="restrict_sell" class="btn btn-info">
                                <i class="fas fa-ban me-1"></i> Hạn chế bán sách
                            </button>
                            <button type="submit" name="action" value="suspend" class="btn btn-danger">
                                <i class="fas fa-clock me-1"></i> Tạm khóa 30 ngày
                            </button>
                            <button type="submit" name="action" value="ban" class="btn btn-dark" onclick="return confirm('Bạn có chắc chắn muốn khóa vĩnh viễn tài khoản này?');">
                                <i class="fas fa-lock me-1"></i> Khóa vĩnh viễn
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Thống kê báo cáo -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">Thống kê báo cáo</h4>
                </div>
                <div class="card-body">
                    <p><strong>Tổng số báo cáo:</strong> <?php echo $report_count; ?></p>
                    <div class="mb-3">
                        <h5>Lịch sử báo cáo</h5>
                        <?php if(count($user_reports) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Loại</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($user_reports as $r): ?>
                                    <tr <?php echo ($r['id'] == $report_id) ? 'class="table-primary"' : ''; ?>>
                                        <td>
                                            <a href="report_detail.php?id=<?php echo $r['id']; ?>">
                                                #<?php echo $r['id']; ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php 
                                            $type_short = [
                                                'spam' => 'Spam',
                                                'inappropriate_content' => 'Không PH',
                                                'fake_book' => 'Sách giả',
                                                'scam' => 'Lừa đảo',
                                                'harassment' => 'Quấy rối',
                                                'other' => 'Khác'
                                            ];
                                            echo isset($type_short[$r['report_type']]) ? $type_short[$r['report_type']] : $r['report_type'];
                                            ?>
                                        </td>
                                        <td>
                                            <?php if($r['status'] == 'pending'): ?>
                                                <span class="badge bg-warning text-dark">Chờ</span>
                                            <?php elseif($r['status'] == 'reviewed'): ?>
                                                <span class="badge bg-info">Xem xét</span>
                                            <?php elseif($r['status'] == 'resolved'): ?>
                                                <span class="badge bg-success">Giải quyết</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Từ chối</span>
                                            <?php endif; ?>
                                            <?php if($report_info['suspended_until'] && strtotime($report_info['suspended_until']) > time()): ?>
                                            <div class="alert alert-warning">
                                                Tài khoản đang bị tạm khóa đến <?php echo date('d/m/Y H:i', strtotime($report_info['suspended_until'])); ?>
                                                <form method="post" action="" class="mt-2">
                                                    <button type="submit" name="action" value="unsuspend" class="btn btn-success btn-sm"
                                                        onclick="return confirm('Bạn có chắc chắn muốn mở khóa tài khoản này sớm hơn?');">
                                                        <i class="fas fa-unlock me-1"></i> Mở khóa sớm
                                                    </button>
                                                </form>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($r['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">Không có báo cáo nào.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Gợi ý hành động -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Gợi ý hành động</h4>
                </div>
                <div class="card-body">
                    <?php
                    $warning_level = $report_info['warning_level'];
                    $report_count = count($user_reports);
                    
                    if ($report_info['is_blocked']) {
                        echo '<div class="alert alert-danger">Tài khoản này đã bị khóa vĩnh viễn.</div>';
                    } elseif ($report_info['suspended_until'] && strtotime($report_info['suspended_until']) > time()) {
                        echo '<div class="alert alert-warning">Tài khoản đang bị tạm khóa đến ' . date('d/m/Y H:i', strtotime($report_info['suspended_until'])) . '.</div>';
                    } elseif ($warning_level >= 5 || $report_count >= 10) {
                        echo '<div class="alert alert-danger">Người dùng có nhiều vi phạm nghiêm trọng. Nên xem xét khóa tài khoản.</div>';
                    } elseif ($warning_level >= 3 || $report_count >= 5) {
                        echo '<div class="alert alert-warning">Người dùng có nhiều vi phạm. Nên xem xét tạm khóa tài khoản hoặc hạn chế quyền.</div>';
                    } elseif ($warning_level >= 1 || $report_count >= 2) {
                        echo '<div class="alert alert-info">Người dùng có một số vi phạm. Nên xem xét cảnh báo hoặc hạn chế một số quyền.</div>';
                    } else {
                        echo '<div class="alert alert-success">Đây có thể là vi phạm đầu tiên của người dùng. Xem xét cảnh báo nếu vi phạm là thật.</div>';
                    }
                    ?>
                    
                    <h5 class="mt-3">Hướng dẫn xử lý</h5>
                    <ul>
                        <li><strong>Cảnh báo:</strong> Sử dụng cho vi phạm nhỏ hoặc lần vi phạm đầu tiên.</li>
                        <li><strong>Hạn chế mua/bán:</strong> Sử dụng khi người dùng có dấu hiệu lừa đảo.</li>
                        <li><strong>Tạm khóa:</strong> Sử dụng cho vi phạm nghiêm trọng hoặc tái phạm.</li>
                        <li><strong>Khóa vĩnh viễn:</strong> Chỉ sử dụng cho các trường hợp đặc biệt nghiêm trọng.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>