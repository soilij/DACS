<?php
// admin/chat_history.php
ob_start();
// Include header and check admin permission
require_once '../includes/header.php';
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    header("Location: ../index.php");
    exit;
}

// Pagination setup
$items_per_page = 20;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Filter setup
$user_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Base query
$query = "SELECT ch.*, u.username 
          FROM chat_history ch 
          LEFT JOIN users u ON ch.user_id = u.id
          WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM chat_history ch 
                LEFT JOIN users u ON ch.user_id = u.id
                WHERE 1=1";
$params = [];
$types = "";

// Add filters to query
if ($user_filter) {
    $query .= " AND ch.user_id = ?";
    $count_query .= " AND ch.user_id = ?";
    $params[] = $user_filter;
    $types .= "i";
}

if ($search_term) {
    $query .= " AND (ch.user_message LIKE ? OR ch.bot_response LIKE ?)";
    $count_query .= " AND (ch.user_message LIKE ? OR ch.bot_response LIKE ?)";
    $search_param = "%" . $search_term . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($date_from) {
    $query .= " AND ch.created_at >= ?";
    $count_query .= " AND ch.created_at >= ?";
    $params[] = $date_from . " 00:00:00";
    $types .= "s";
}

if ($date_to) {
    $query .= " AND ch.created_at <= ?";
    $count_query .= " AND ch.created_at <= ?";
    $params[] = $date_to . " 23:59:59";
    $types .= "s";
}

// Add order and limit
$query .= " ORDER BY ch.created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $items_per_page;
$types .= "ii";

// Thay thế đoạn mã bind_param và execute bằng đoạn này
try {
    $count_stmt = $conn->prepare($count_query);
    if (!empty($params) && !empty($types)) {
        $count_types = substr($types, 0, -2); // Remove the 'ii' for LIMIT params
        if (!empty($count_types) && count(array_slice($params, 0, -2)) > 0) {
            $count_stmt->bind_param($count_types, ...array_slice($params, 0, -2));
        }
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_rows = $count_result->fetch_assoc()['total'];
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Lỗi truy vấn: ' . $e->getMessage() . '</div>';
    $total_rows = 0;
}
$total_pages = ceil($total_rows / $items_per_page);

// Get chat history data
$stmt = $conn->prepare($query);
if (!empty($params) && !empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Quản lý lịch sử trò chuyện chatbot</h1>
    
    <!-- Bộ lọc -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Bộ lọc</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="user_id">Người dùng</label>
                        <select class="form-control" id="user_id" name="user_id">
                            <option value="">Tất cả người dùng</option>
                            <?php
                            $user_query = "SELECT id, username FROM users ORDER BY username";
                            $user_result = $conn->query($user_query);
                            while ($user = $user_result->fetch_assoc()) {
                                $selected = ($user_filter == $user['id']) ? 'selected' : '';
                                echo "<option value='{$user['id']}' $selected>{$user['username']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="search">Tìm kiếm</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Tìm kiếm nội dung..." value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="date_from">Từ ngày</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="date_to">Đến ngày</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Lọc</button>
                <a href="chat_history.php" class="btn btn-secondary">Đặt lại</a>
            </form>
        </div>
    </div>
    
    <!-- Bảng dữ liệu -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Lịch sử trò chuyện</h6>
            <div>
                <span class="mr-2">Tổng số: <?php echo $total_rows; ?> cuộc trò chuyện</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Người dùng</th>
                            <th>Session ID</th>
                            <th>Người dùng hỏi</th>
                            <th>Bot trả lời</th>
                            <th>Thời gian</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo $row['username'] ? $row['username'] : 'Khách'; ?></td>
                                    <td><?php echo substr($row['session_id'], 0, 10) . '...'; ?></td>
                                    <td>
                                        <div class="message-content">
                                            <?php echo htmlspecialchars(substr($row['user_message'], 0, 100)); ?>
                                            <?php if (strlen($row['user_message']) > 100): ?>
                                                <span class="message-ellipsis">...</span>
                                                <div class="message-full d-none"><?php echo htmlspecialchars($row['user_message']); ?></div>
                                                <button class="btn btn-sm btn-link show-more">Xem thêm</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="message-content">
                                            <?php echo htmlspecialchars(substr($row['bot_response'], 0, 100)); ?>
                                            <?php if (strlen($row['bot_response']) > 100): ?>
                                                <span class="message-ellipsis">...</span>
                                                <div class="message-full d-none"><?php echo htmlspecialchars($row['bot_response']); ?></div>
                                                <button class="btn btn-sm btn-link show-more">Xem thêm</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary view-detail" 
                                                data-id="<?php echo $row['id']; ?>"
                                                data-user="<?php echo $row['username'] ? $row['username'] : 'Khách'; ?>"
                                                data-user-message="<?php echo htmlspecialchars($row['user_message']); ?>"
                                                data-bot-response="<?php echo htmlspecialchars($row['bot_response']); ?>"
                                                data-time="<?php echo date('d/m/Y H:i:s', strtotime($row['created_at'])); ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-chat" data-id="<?php echo $row['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">Không có dữ liệu</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Phân trang -->
            <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-center">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?php echo isset($_GET['user_id']) ? '&user_id='.$_GET['user_id'] : ''; ?><?php echo isset($_GET['search']) ? '&search='.$_GET['search'] : ''; ?><?php echo isset($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : ''; ?><?php echo isset($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : ''; ?>" aria-label="First">
                                        <span aria-hidden="true">&laquo;&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo isset($_GET['user_id']) ? '&user_id='.$_GET['user_id'] : ''; ?><?php echo isset($_GET['search']) ? '&search='.$_GET['search'] : ''; ?><?php echo isset($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : ''; ?><?php echo isset($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : ''; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['user_id']) ? '&user_id='.$_GET['user_id'] : ''; ?><?php echo isset($_GET['search']) ? '&search='.$_GET['search'] : ''; ?><?php echo isset($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : ''; ?><?php echo isset($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo isset($_GET['user_id']) ? '&user_id='.$_GET['user_id'] : ''; ?><?php echo isset($_GET['search']) ? '&search='.$_GET['search'] : ''; ?><?php echo isset($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : ''; ?><?php echo isset($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : ''; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo isset($_GET['user_id']) ? '&user_id='.$_GET['user_id'] : ''; ?><?php echo isset($_GET['search']) ? '&search='.$_GET['search'] : ''; ?><?php echo isset($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : ''; ?><?php echo isset($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : ''; ?>" aria-label="Last">
                                        <span aria-hidden="true">&raquo;&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal xem chi tiết -->
<div class="modal fade" id="viewDetailModal" tabindex="-1" role="dialog" aria-labelledby="viewDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewDetailModalLabel">Chi tiết cuộc trò chuyện</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>ID:</strong> <span id="detail-id"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Người dùng:</strong> <span id="detail-user"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Thời gian:</strong> <span id="detail-time"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">Người dùng hỏi</h6>
                            </div>
                            <div class="card-body">
                                <p id="detail-user-message"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">Bot trả lời</h6>
                            </div>
                            <div class="card-body">
                                <p id="detail-bot-response"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal xác nhận xóa -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Xác nhận xóa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa cuộc trò chuyện này?</p>
            </div>
            <div class="modal-footer">
                <form id="deleteForm" method="POST" action="chat_delete.php">
                    <input type="hidden" name="id" id="delete-id">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý nút "Xem thêm"
    document.querySelectorAll('.show-more').forEach(button => {
        button.addEventListener('click', function() {
            const messageDiv = this.closest('.message-content');
            const ellipsis = messageDiv.querySelector('.message-ellipsis');
            const fullMessage = messageDiv.querySelector('.message-full');
            
            if (fullMessage.classList.contains('d-none')) {
                fullMessage.classList.remove('d-none');
                ellipsis.classList.add('d-none');
                this.textContent = 'Thu gọn';
            } else {
                fullMessage.classList.add('d-none');
                ellipsis.classList.remove('d-none');
                this.textContent = 'Xem thêm';
            }
        });
    });
    
    // Xử lý nút xem chi tiết
    document.querySelectorAll('.view-detail').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const user = this.getAttribute('data-user');
            const userMessage = this.getAttribute('data-user-message');
            const botResponse = this.getAttribute('data-bot-response');
            const time = this.getAttribute('data-time');
            
            document.getElementById('detail-id').textContent = id;
            document.getElementById('detail-user').textContent = user;
            document.getElementById('detail-time').textContent = time;
            document.getElementById('detail-user-message').textContent = userMessage;
            document.getElementById('detail-bot-response').textContent = botResponse;
            
            $('#viewDetailModal').modal('show');
        });
    });
    
    // Xử lý nút xóa
    document.querySelectorAll('.delete-chat').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            document.getElementById('delete-id').value = id;
            $('#deleteModal').modal('show');
        });
    });

     // Xử lý nút đóng modal (nút X)
    document.querySelector('#viewDetailModal .close').addEventListener('click', function() {
        $('#viewDetailModal').modal('hide');
    });
    
    // Xử lý nút "Đóng" trong modal
    document.querySelector('#viewDetailModal .btn-secondary').addEventListener('click', function() {
        $('#viewDetailModal').modal('hide');
    });
    
    // Xử lý nút "Đóng" trong modal chi tiết
    document.querySelector('.modal .btn-secondary[data-dismiss="modal"]').addEventListener('click', function() {
        $(this).closest('.modal').modal('hide');
    });
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>