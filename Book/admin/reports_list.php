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
require_once '../classes/Notification.php';

// Khởi tạo đối tượng cần thiết
$report = new Report();
$user = new User();
$db = new Database();

// Xử lý hành động
$message = '';
$message_type = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $report_id = $_GET['id'];
    
    if ($action === 'dismiss') {
        // Xử lý từ chối báo cáo
        if (isset($_POST['admin_note'])) {
            $admin_note = trim($_POST['admin_note']);
            
            if ($report->dismissReport($report_id, $_SESSION['user_id'], $admin_note)) {
                $message = 'Đã từ chối báo cáo thành công!';
                $message_type = 'success';
            } else {
                $message = 'Có lỗi xảy ra khi từ chối báo cáo!';
                $message_type = 'danger';
            }
        }
    }
}

// Lấy tham số lọc
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Xây dựng bộ lọc
$filters = [
    'status' => $filter_status,
    'report_type' => $filter_type,
    'search' => $search
];

// Lấy danh sách báo cáo
$reports = $report->getReports($filters);

// Đếm báo cáo chưa xử lý
$pending_reports = $report->getPendingReportsCount();

// Admin header
include('includes/header.php');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Quản lý báo cáo người dùng</h1>
        <span class="badge bg-danger"><?php echo $pending_reports; ?> báo cáo chờ xử lý</span>
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
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tên người dùng, email, tên sách...">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Trạng thái</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tất cả</option>
                        <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                        <option value="reviewed" <?php echo $filter_status == 'reviewed' ? 'selected' : ''; ?>>Đang xem xét</option>
                        <option value="resolved" <?php echo $filter_status == 'resolved' ? 'selected' : ''; ?>>Đã giải quyết</option>
                        <option value="dismissed" <?php echo $filter_status == 'dismissed' ? 'selected' : ''; ?>>Đã từ chối</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label">Loại báo cáo</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">Tất cả</option>
                        <option value="spam" <?php echo $filter_type == 'spam' ? 'selected' : ''; ?>>Spam</option>
                        <option value="inappropriate_content" <?php echo $filter_type == 'inappropriate_content' ? 'selected' : ''; ?>>Nội dung không phù hợp</option>
                        <option value="fake_book" <?php echo $filter_type == 'fake_book' ? 'selected' : ''; ?>>Sách giả</option>
                        <option value="scam" <?php echo $filter_type == 'scam' ? 'selected' : ''; ?>>Lừa đảo</option>
                        <option value="harassment" <?php echo $filter_type == 'harassment' ? 'selected' : ''; ?>>Quấy rối</option>
                        <option value="other" <?php echo $filter_type == 'other' ? 'selected' : ''; ?>>Khác</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Danh sách báo cáo -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách báo cáo</h6>
            <div>
                <a href="?status=pending" class="btn btn-warning btn-sm">
                    <i class="fas fa-clock me-1"></i> Chờ xử lý
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover datatable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Người bị báo cáo</th>
                            <th>Người báo cáo</th>
                            <th>Loại báo cáo</th>
                            <th>Sách liên quan</th>
                            <th>Ngày báo cáo</th>
                            <th>Trạng thái</th>
                            <th>Số báo cáo</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($reports as $report_item): ?>
                        <tr>
                            <td><?php echo $report_item['id']; ?></td>
                            <td>
                                <a href="../pages/profile.php?id=<?php echo $report_item['reported_user_id']; ?>" target="_blank">
                                    <?php echo $report_item['reported_username']; ?>
                                </a>
                            </td>
                            <td><?php echo $report_item['reporter_username']; ?></td>
                            <td>
                                <?php 
                                $report_types = [
                                    'spam' => 'Spam',
                                    'inappropriate_content' => 'Nội dung không phù hợp',
                                    'fake_book' => 'Sách giả',
                                    'scam' => 'Lừa đảo',
                                    'harassment' => 'Quấy rối',
                                    'other' => 'Khác'
                                ];
                                echo isset($report_types[$report_item['report_type']]) ? $report_types[$report_item['report_type']] : $report_item['report_type'];
                                ?>
                            </td>
                            <td>
                                <?php if($report_item['book_id']): ?>
                                <a href="../pages/book_details.php?id=<?php echo $report_item['book_id']; ?>" target="_blank">
                                    <?php echo $report_item['book_title']; ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">Không có</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($report_item['created_at'])); ?></td>
                            <td>
                                <?php if($report_item['status'] == 'pending'): ?>
                                    <span class="badge bg-warning text-dark">Chờ xử lý</span>
                                <?php elseif($report_item['status'] == 'reviewed'): ?>
                                    <span class="badge bg-info">Đang xem xét</span>
                                <?php elseif($report_item['status'] == 'resolved'): ?>
                                    <span class="badge bg-success">Đã giải quyết</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Đã từ chối</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $report_item['report_count'] > 2 ? 'danger' : 'primary'; ?>">
                                    <?php echo $report_item['report_count']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="report_detail.php?id=<?php echo $report_item['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if($report_item['status'] == 'pending'): ?>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#dismissModal" data-id="<?php echo $report_item['id']; ?>">
                                        <i class="fas fa-times"></i>
                                    </button>
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

<!-- Modal Từ chối Báo cáo -->
<div class="modal fade" id="dismissModal" tabindex="-1" aria-labelledby="dismissModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="" id="dismissForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="dismissModalLabel">Từ chối báo cáo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="admin_note" class="form-label">Lý do từ chối</label>
                        <textarea class="form-control" id="admin_note" name="admin_note" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-danger">Từ chối báo cáo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Xử lý modal từ chối báo cáo
document.addEventListener('DOMContentLoaded', function() {
    const dismissModal = document.getElementById('dismissModal');
    if (dismissModal) {
        dismissModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const reportId = button.getAttribute('data-id');
            
            // Cập nhật action của form
            const dismissForm = document.getElementById('dismissForm');
            dismissForm.action = '?action=dismiss&id=' + reportId;
        });
    }
});
</script>

<?php include('includes/footer.php'); ?>