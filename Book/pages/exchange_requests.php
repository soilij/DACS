<?php
// Khởi động session
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Include các file cần thiết
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Exchange.php';
require_once '../classes/User.php';
require_once '../classes/Book.php';
require_once '../classes/Notification.php';

// Khởi tạo đối tượng cần thiết
$exchange = new Exchange();
$user = new User();
$book = new Book();
$notification = new Notification();

// Thiết lập biến
$is_detail_page = true;
$msg = '';
$msg_type = '';

// Xử lý các hành động
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $exchange_id = $_GET['id'];
    
    // Lấy thông tin giao dịch
    $exchange_info = $exchange->getById($exchange_id);
    
    if (!$exchange_info) {
        $msg = 'Không tìm thấy giao dịch!';
        $msg_type = 'danger';
    } else {
        // Kiểm tra quyền
        $can_action = false;
        
        if ($action == 'accept' || $action == 'reject') {
            // Chỉ chủ sở hữu mới có thể chấp nhận/từ chối
            $can_action = ($_SESSION['user_id'] == $exchange_info['owner_id'] && $exchange_info['status'] == 'pending');
        } elseif ($action == 'complete') {
            // Cả hai bên đều có thể đánh dấu hoàn thành khi đã được chấp nhận
            $can_action = (($_SESSION['user_id'] == $exchange_info['owner_id'] || $_SESSION['user_id'] == $exchange_info['requester_id']) && $exchange_info['status'] == 'accepted');
        } elseif ($action == 'cancel') {
            // Người yêu cầu có thể hủy khi chưa được chấp nhận
            $can_action = ($_SESSION['user_id'] == $exchange_info['requester_id'] && $exchange_info['status'] == 'pending');
        }
        
        if ($can_action) {
            $result = false;
            
            if ($action == 'accept') {
                $result = $exchange->handleRequest($exchange_id, 'accept', $_SESSION['user_id']);
                $msg = 'Yêu cầu trao đổi đã được chấp nhận!';
            } elseif ($action == 'reject') {
                $result = $exchange->handleRequest($exchange_id, 'reject', $_SESSION['user_id']);
                $msg = 'Yêu cầu trao đổi đã bị từ chối!';
            } elseif ($action == 'complete') {
                $result = $exchange->handleRequest($exchange_id, 'complete', $_SESSION['user_id']);
                $msg = 'Giao dịch đã được đánh dấu hoàn thành!';
            } elseif ($action == 'cancel') {
                $result = $exchange->cancelRequest($exchange_id, $_SESSION['user_id']);
                $msg = 'Yêu cầu trao đổi đã bị hủy!';
            }
            
            if ($result) {
                $msg_type = 'success';
            } else {
                $msg = 'Có lỗi xảy ra khi xử lý yêu cầu!';
                $msg_type = 'danger';
            }
        } else {
            $msg = 'Bạn không có quyền thực hiện hành động này!';
            $msg_type = 'danger';
        }
    }
}

// Đặt code này sau phần kiểm tra đăng nhập và trước khi xử lý yêu cầu trao đổi
if (isset($_SESSION['user_id'])) {
    // Kiểm tra người dùng có bị khóa hoặc bị hạn chế không
    $user_id = $_SESSION['user_id'];
    $db = new Database();
    $db->query('SELECT can_buy, can_sell, suspended_until, is_blocked FROM users WHERE id = ?');
    $db->bind(1, $user_id);
    $user_status = $db->single();

    // Nếu tài khoản bị khóa
    if ($user_status['is_blocked']) {
        $_SESSION['error'] = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên để biết thêm chi tiết.';
        header('Location: index.php');
        exit();
    }

    // Nếu tài khoản bị tạm khóa
    if ($user_status['suspended_until'] && strtotime($user_status['suspended_until']) > time()) {
        $_SESSION['error'] = 'Tài khoản của bạn đang bị tạm khóa đến ' . date('d/m/Y H:i', strtotime($user_status['suspended_until'])) . '. Vui lòng thử lại sau.';
        header('Location: index.php');
        exit();
    }

    // Kiểm tra quyền trao đổi sách
    if (!$user_status['can_sell']) {
        $_SESSION['error'] = 'Tài khoản của bạn đã bị hạn chế quyền trao đổi sách. Vui lòng liên hệ quản trị viên để biết thêm chi tiết.';
        header('Location: index.php');
        exit();
    }
}

// Xử lý đánh giá
if (isset($_POST['submit_review'])) {
    $exchange_id = $_POST['exchange_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    
    if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
        $msg = 'Vui lòng chọn đánh giá từ 1 đến 5 sao!';
        $msg_type = 'danger';
    } else {
        // Kiểm tra xem giao dịch có tồn tại và đã hoàn thành chưa
        $exchange_info = $exchange->getById($exchange_id);
        
        if (!$exchange_info || $exchange_info['status'] != 'completed') {
            $msg = 'Không thể đánh giá giao dịch chưa hoàn thành!';
            $msg_type = 'danger';
        } elseif ($exchange_info['requester_id'] != $_SESSION['user_id'] && $exchange_info['owner_id'] != $_SESSION['user_id']) {
            $msg = 'Bạn không có quyền đánh giá giao dịch này!';
            $msg_type = 'danger';
        } elseif ($exchange->hasReview($exchange_id, $_SESSION['user_id'])) {
            $msg = 'Bạn đã đánh giá giao dịch này rồi!';
            $msg_type = 'danger';
        } else {
            $review_data = [
                'exchange_id' => $exchange_id,
                'reviewer_id' => $_SESSION['user_id'],
                'rating' => $rating,
                'comment' => $comment
            ];
            
            if ($exchange->createReview($review_data)) {
                $msg = 'Đánh giá đã được gửi thành công!';
                $msg_type = 'success';
            } else {
                $msg = 'Có lỗi xảy ra khi gửi đánh giá!';
                $msg_type = 'danger';
            }
        }
    }
}

// Lấy danh sách yêu cầu
// Mặc định hiển thị tất cả
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';

if ($active_tab == 'received') {
    // Yêu cầu nhận được
    $exchange_requests = $exchange->getReceivedRequests($_SESSION['user_id']);
} elseif ($active_tab == 'sent') {
    // Yêu cầu đã gửi
    $exchange_requests = $exchange->getSentRequests($_SESSION['user_id']);
} elseif ($active_tab == 'completed') {
    // Giao dịch đã hoàn thành
    $exchange_requests = $exchange->getCompletedExchanges($_SESSION['user_id']);
} else {
    // Tất cả
    $exchange_requests = $exchange->getUserExchanges($_SESSION['user_id']);
}

// Include header
require_once '../includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Yêu cầu trao đổi</h1>
    
    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <ul class="nav nav-tabs mb-4" id="exchangeTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link <?php echo $active_tab == 'all' ? 'active' : ''; ?>" href="?tab=all">Tất cả</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?php echo $active_tab == 'received' ? 'active' : ''; ?>" href="?tab=received">Nhận được</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?php echo $active_tab == 'sent' ? 'active' : ''; ?>" href="?tab=sent">Đã gửi</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?php echo $active_tab == 'completed' ? 'active' : ''; ?>" href="?tab=completed">Đã hoàn thành</a>
        </li>
    </ul>
    
    <?php if(count($exchange_requests) > 0): ?>
        <?php foreach($exchange_requests as $er): ?>
        <div class="card mb-4 shadow-sm exchange-card">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Yêu cầu #<?php echo $er['id']; ?></strong>
                        <span class="ms-2">-</span>
                        <small class="ms-2"><?php echo date('d/m/Y H:i', strtotime($er['created_at'])); ?></small>
                    </div>
                    <div>
                        <?php if($er['status'] == 'pending'): ?>
                            <span class="badge bg-warning text-dark">Chờ xác nhận</span>
                        <?php elseif($er['status'] == 'accepted'): ?>
                            <span class="badge bg-info">Đã chấp nhận</span>
                        <?php elseif($er['status'] == 'completed'): ?>
                            <span class="badge bg-success">Hoàn thành</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Đã từ chối/Hủy</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-7">
                        <div class="row align-items-center">
                            <!-- Sách chủ sở hữu -->
                            <div class="col-md-5 mb-3 mb-md-0">
                                <div class="text-center">
                                    <h6 class="mb-3">Sách được yêu cầu</h6>
                                    <a href="book_details.php?id=<?php echo $er['owner_book_id']; ?>">
                                        <img src="../uploads/books/<?php echo $er['owner_book_image']; ?>" alt="<?php echo $er['owner_book_title']; ?>" class="img-fluid rounded" style="max-height: 150px;">
                                    </a>
                                    <h6 class="mt-2"><?php echo $er['owner_book_title']; ?></h6>
                                    <p class="text-muted small mb-0">Chủ sở hữu: <?php echo $er['owner_username']; ?></p>
                                </div>
                            </div>
                            
                            <!-- Biểu tượng trao đổi -->
                            <div class="col-md-2 text-center mb-3 mb-md-0">
                                <i class="fas fa-exchange-alt fa-2x text-primary"></i>
                            </div>
                            
                            <!-- Sách người yêu cầu (nếu có) -->
                            <div class="col-md-5 mb-3 mb-md-0">
                                <div class="text-center">
                                    <h6 class="mb-3">Đổi lấy</h6>
                                    <?php if($er['requester_book_id']): ?>
                                    <a href="book_details.php?id=<?php echo $er['requester_book_id']; ?>">
                                        <img src="../uploads/books/<?php echo $er['requester_book_image']; ?>" alt="<?php echo $er['requester_book_title']; ?>" class="img-fluid rounded" style="max-height: 150px;">
                                    </a>
                                    <h6 class="mt-2"><?php echo $er['requester_book_title']; ?></h6>
                                    <?php else: ?>
                                    <div class="border rounded p-3">
                                        <i class="fas fa-book-open fa-3x text-muted mb-2"></i>
                                        <p class="text-muted mb-0">Không có sách trao đổi</p>
                                    </div>
                                    <?php endif; ?>
                                    <p class="text-muted small mb-0">Người yêu cầu: <?php echo $er['requester_username']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-5">
                        <div class="border rounded p-3 h-100">
                            <h6>Thông tin trao đổi</h6>
                            
                            <?php if($er['is_money_involved']): ?>
                            <div class="alert alert-primary mb-3" role="alert">
                                <i class="fas fa-money-bill-wave me-1"></i> Trao đổi kèm tiền: <strong><?php echo number_format($er['amount'], 0, ',', '.'); ?> đ</strong>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <strong>Lời nhắn:</strong>
                                <p class="mb-0"><?php echo nl2br($er['message']); ?></p>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <?php if($er['status'] == 'pending'): ?>
                                    <?php if($_SESSION['user_id'] == $er['owner_id']): ?>
                                    <!-- Nút cho chủ sở hữu -->
                                    <div class="btn-group">
                                        <a href="?action=accept&id=<?php echo $er['id']; ?>&tab=<?php echo $active_tab; ?>" class="btn btn-success" onclick="return confirm('Bạn có chắc chắn muốn chấp nhận yêu cầu trao đổi này?');">
                                            <i class="fas fa-check me-1"></i> Chấp nhận
                                        </a>
                                        <a href="?action=reject&id=<?php echo $er['id']; ?>&tab=<?php echo $active_tab; ?>" class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn từ chối yêu cầu trao đổi này?');">
                                            <i class="fas fa-times me-1"></i> Từ chối
                                        </a>
                                    </div>
                                    <?php elseif($_SESSION['user_id'] == $er['requester_id']): ?>
                                    <!-- Nút cho người yêu cầu -->
                                    <a href="?action=cancel&id=<?php echo $er['id']; ?>&tab=<?php echo $active_tab; ?>" class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn hủy yêu cầu trao đổi này?');">
                                        <i class="fas fa-ban me-1"></i> Hủy yêu cầu
                                    </a>
                                    <?php endif; ?>
                                <?php elseif($er['status'] == 'accepted'): ?>
                                    <a href="?action=complete&id=<?php echo $er['id']; ?>&tab=<?php echo $active_tab; ?>" class="btn btn-success" onclick="return confirm('Xác nhận đã hoàn thành giao dịch?');">
                                        <i class="fas fa-check-circle me-1"></i> Đánh dấu hoàn thành
                                    </a>
                                <?php elseif($er['status'] == 'completed'): ?>
                                    <?php if(!$exchange->hasReview($er['id'], $_SESSION['user_id'])): ?>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reviewModal<?php echo $er['id']; ?>">
                                        <i class="fas fa-star me-1"></i> Đánh giá
                                    </button>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Đã đánh giá</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal đánh giá -->
        <?php if($er['status'] == 'completed' && !$exchange->hasReview($er['id'], $_SESSION['user_id'])): ?>
        <div class="modal fade" id="reviewModal<?php echo $er['id']; ?>" tabindex="-1" aria-labelledby="reviewModalLabel<?php echo $er['id']; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post" action="">
                        <div class="modal-header">
                            <h5 class="modal-title" id="reviewModalLabel<?php echo $er['id']; ?>">Đánh giá giao dịch</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="exchange_id" value="<?php echo $er['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Đánh giá người 
                                    <?php echo $_SESSION['user_id'] == $er['owner_id'] ? $er['requester_username'] : $er['owner_username']; ?>
                                </label>
                                <div class="star-rating mb-2">
                                    <i class="far fa-star text-warning" data-rating="1" onclick="setRating(this)"></i>
                                    <i class="far fa-star text-warning" data-rating="2" onclick="setRating(this)"></i>
                                    <i class="far fa-star text-warning" data-rating="3" onclick="setRating(this)"></i>
                                    <i class="far fa-star text-warning" data-rating="4" onclick="setRating(this)"></i>
                                    <i class="far fa-star text-warning" data-rating="5" onclick="setRating(this)"></i>
                                </div>
                                <input type="hidden" name="rating" id="ratingInput<?php echo $er['id']; ?>" value="5">
                            </div>
                            
                            <div class="mb-3">
                                <label for="comment<?php echo $er['id']; ?>" class="form-label">Nhận xét</label>
                                <textarea class="form-control" id="comment<?php echo $er['id']; ?>" name="comment" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" name="submit_review" class="btn btn-primary">Gửi đánh giá</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle me-2"></i> Không có yêu cầu trao đổi nào.
            <?php if ($active_tab == 'received'): ?>
                Bạn chưa nhận được yêu cầu trao đổi nào.
            <?php elseif ($active_tab == 'sent'): ?>
                Bạn chưa gửi yêu cầu trao đổi nào. <a href="search.php" class="alert-link">Tìm sách</a> để bắt đầu trao đổi.
            <?php elseif ($active_tab == 'completed'): ?>
                Bạn chưa có giao dịch hoàn thành nào.
            <?php else: ?>
                Chưa có yêu cầu trao đổi nào. <a href="search.php" class="alert-link">Tìm sách</a> để bắt đầu trao đổi.
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function setRating(star) {
        const stars = star.parentNode.querySelectorAll('i');
        const rating = star.getAttribute('data-rating');
        const exchangeId = star.closest('.modal').id.replace('reviewModal', '');
        document.getElementById('ratingInput' + exchangeId).value = rating;
        
        stars.forEach(s => {
            if (s.getAttribute('data-rating') <= rating) {
                s.className = 'fas fa-star text-warning';
            } else {
                s.className = 'far fa-star text-warning';
            }
        });
    }
    
    // Thiết lập mặc định là 5 sao
    document.addEventListener('DOMContentLoaded', function() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            const stars = modal.querySelectorAll('.star-rating i');
            stars.forEach(star => {
                if (star.getAttribute('data-rating') <= 5) {
                    star.className = 'fas fa-star text-warning';
                }
            });
        });
    });
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>