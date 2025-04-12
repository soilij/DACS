<?php
// Khởi động session
session_start();

// Include các file cần thiết
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Book.php';
require_once '../classes/User.php';
require_once '../classes/Exchange.php';
require_once '../classes/Payment.php'; // Thêm file Payment

// Khởi tạo đối tượng cần thiết
$book = new Book();
$user = new User();
$exchange = new Exchange();
$payment = new Payment(); // Khởi tạo đối tượng Payment

// Thiết lập biến
$is_detail_page = true;
$msg = '';
$msg_type = '';

// Kiểm tra ID sách
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../index.php');
    exit();
}

$book_id = $_GET['id'];

// Lấy thông tin sách
$book_info = $book->getById($book_id);

if (!$book_info) {
    header('Location: ../index.php');
    exit();
}

// Lấy thông tin người đăng sách
$book_owner = $user->getUserById($book_info['user_id']);

// Lấy các ảnh bổ sung của sách
$book_images = $book->getImages($book_id);

// Lấy sách đề xuất
$category_id = $book_info['category_id'];
$recommended_books = $book->getRecommended($book_id, $category_id, 4);

// Kiểm tra xem sách có trong danh sách yêu thích không
$in_wishlist = false;
if (isset($_SESSION['user_id'])) {
    $in_wishlist = $user->isInWishlist($_SESSION['user_id'], $book_id);
}

// Xử lý thêm/xóa danh sách yêu thích
if (isset($_POST['toggle_wishlist']) && isset($_SESSION['user_id'])) {
    if ($in_wishlist) {
        $user->removeFromWishlist($_SESSION['user_id'], $book_id);
        $in_wishlist = false;
        $msg = 'Đã xóa sách khỏi danh sách yêu thích!';
        $msg_type = 'success';
    } else {
        $user->addToWishlist($_SESSION['user_id'], $book_id);
        $in_wishlist = true;
        $msg = 'Đã thêm sách vào danh sách yêu thích!';
        $msg_type = 'success';
    }
}

// Xử lý yêu cầu trao đổi
if (isset($_POST['send_exchange_request']) && isset($_SESSION['user_id'])) {
    // Kiểm tra không phải chính chủ sách
    if ($_SESSION['user_id'] != $book_info['user_id']) {
        // Lấy thông tin từ form
        $requester_id = $_SESSION['user_id'];
        $owner_id = $book_info['user_id'];
        $owner_book_id = $book_id;
        
        // Kiểm tra sách trao đổi
        $requester_book_id = !empty($_POST['requester_book_id']) ? $_POST['requester_book_id'] : null;
        
        // Kiểm tra xem người dùng đã chọn sách trao đổi chưa
        if ($requester_book_id === null) {
            $msg = 'Vui lòng chọn sách để trao đổi!';
            $msg_type = 'danger';
        } else {
            // Kiểm tra có kèm tiền không
            $is_money_involved = isset($_POST['is_money_involved']) && $_POST['is_money_involved'] == 1;
            $amount = $is_money_involved ? $_POST['amount'] : 0;
            
            // Kiểm tra số tiền (nếu có)
            if ($is_money_involved && (!is_numeric($amount) || $amount <= 0)) {
                $msg = 'Số tiền trao đổi không hợp lệ!';
                $msg_type = 'danger';
            } else {
                // Lấy lời nhắn
                $message = $_POST['message'];
                
                // Kiểm tra lời nhắn
                if (empty(trim($message))) {
                    $msg = 'Vui lòng nhập lời nhắn cho chủ sách!';
                    $msg_type = 'danger';
                } else {
                    // Tạo yêu cầu trao đổi
                    $exchange_data = [
                        'requester_id' => $requester_id,
                        'owner_id' => $owner_id,
                        'requester_book_id' => $requester_book_id,
                        'owner_book_id' => $owner_book_id,
                        'message' => $message,
                        'is_money_involved' => $is_money_involved,
                        'amount' => $amount
                    ];
                    
                    // Thử tạo yêu cầu trao đổi
                    try {
                        $exchange_id = $exchange->createRequest($exchange_data);
                        
                        if ($exchange_id) {
                            $msg = 'Yêu cầu trao đổi đã được gửi thành công!';
                            $msg_type = 'success';
                            
                            // Chuyển hướng sau 3 giây
                            header('refresh:3;url=exchange_requests.php');
                            exit();
                        } else {
                            $msg = 'Có lỗi xảy ra khi gửi yêu cầu trao đổi!';
                            $msg_type = 'danger';
                        }
                    } catch (Exception $e) {
                        $msg = 'Lỗi: ' . $e->getMessage();
                        $msg_type = 'danger';
                    }
                }
            }
        }
    } else {
        $msg = 'Bạn không thể trao đổi sách của chính mình!';
        $msg_type = 'danger';
    }
}

// Xử lý thanh toán
if (isset($_POST['process_payment']) && isset($_SESSION['user_id'])) {
    // Kiểm tra không phải chính chủ sách
    if ($_SESSION['user_id'] != $book_info['user_id']) {
        // Lấy phương thức thanh toán
        $payment_method = $_POST['payment_method'];
        
        // Danh sách phương thức thanh toán hợp lệ
        $valid_methods = ['momo', 'bank_transfer', 'cod'];
        
        if (!in_array($payment_method, $valid_methods)) {
            $msg = 'Phương thức thanh toán không hợp lệ!';
            $msg_type = 'danger';
        } else {
            // Tạo yêu cầu thanh toán
            $payment_data = [
                'user_id' => $_SESSION['user_id'],
                'book_id' => $book_id,
                'amount' => $book_info['price'],
                'payment_method' => $payment_method
            ];
            
            $transaction_code = $payment->createPaymentRequest($payment_data);
            
            if ($transaction_code) {
                // Chuyển hướng đến trang xác nhận thanh toán
                header("Location: payment_confirm.php?transaction_code={$transaction_code}");
                exit();
            } else {
                $msg = 'Có lỗi xảy ra khi tạo yêu cầu thanh toán!';
                $msg_type = 'danger';
            }
        }
    } else {
        $msg = 'Bạn không thể mua sách của chính mình!';
        $msg_type = 'danger';
    }
}

// Lấy sách của người dùng hiện tại (để chọn khi trao đổi)
$user_books = [];
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $book_info['user_id']) {
    $user_books = $book->getAvailableBooksByUser($_SESSION['user_id']);
}

// Include header
require_once '../includes/header.php';
?>

<div class="container py-5">
    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-9">
            <!-- Chi tiết sách -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-4 mb-md-0">
                            <div class="position-relative">
                                <img src="../uploads/books/<?php echo $book_info['image']; ?>" alt="<?php echo $book_info['title']; ?>" class="img-fluid rounded book-main-image">
                                
                                <?php if($book_info['status'] == 'available'): ?>
                                    <?php if($book_info['exchange_type'] == 'both'): ?>
                                    <span class="position-absolute top-0 start-0 badge bg-primary m-2">Trao đổi/Mua</span>
                                    <?php elseif($book_info['exchange_type'] == 'exchange_only'): ?>
                                    <span class="position-absolute top-0 start-0 badge bg-success m-2">Chỉ trao đổi</span>
                                    <?php else: ?>
                                    <span class="position-absolute top-0 start-0 badge bg-warning text-dark m-2">Chỉ bán</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="position-absolute top-0 start-0 badge bg-secondary m-2">Đã trao đổi</span>
                                <?php endif; ?>
                              
                                <!-- Thêm vào wishlist -->
                                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $book_info['user_id']): ?>
                                <form method="post" action="" class="position-absolute top-0 end-0 m-2">
                                    <button type="submit" name="toggle_wishlist" class="btn btn-sm btn-outline-danger rounded-circle p-2 <?php echo $in_wishlist ? 'active' : ''; ?>">
                                        <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Ảnh bổ sung -->
                            <?php if(count($book_images) > 0): ?>
                            <div class="mt-3 d-flex flex-wrap gap-2">
                                <img src="../uploads/books/<?php echo $book_info['image']; ?>" alt="Main" class="img-thumbnail book-thumbnail active" onclick="changeMainImage(this, '../uploads/books/<?php echo $book_info['image']; ?>')">
                                
                                <?php foreach($book_images as $image): ?>
                                <img src="../uploads/books/<?php echo $image['image_path']; ?>" alt="Extra" class="img-thumbnail book-thumbnail" onclick="changeMainImage(this, '../uploads/books/<?php echo $image['image_path']; ?>')">
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-8">
                            <h2 class="mb-2"><?php echo $book_info['title']; ?></h2>
                            <p class="text-muted mb-3">Tác giả: <?php echo $book_info['author']; ?></p>
                            
                            <div class="mb-3">
                                <span class="badge bg-info text-dark"><?php echo $book_info['category_name']; ?></span>
                                <span class="ms-2">Tình trạng sách:</span>
                                <span class="ms-1">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php if($i <= $book_info['condition_rating']): ?>
                                    <i class="fas fa-star text-warning"></i>
                                    <?php else: ?>
                                    <i class="far fa-star text-warning"></i>
                                    <?php endif; ?>
                                    <?php endfor; ?>
                                </span>
                            </div>
                            
                            <?php if($book_info['isbn']): ?>
                            <p class="mb-3">
                                <strong>ISBN:</strong> <?php echo $book_info['isbn']; ?>
                            </p>
                            <?php endif; ?>
                            
                            <div class="mb-4">
                                <p><?php echo nl2br($book_info['description']); ?></p>
                            </div>
                            
                            <div class="d-flex align-items-center mb-4">
                                <?php if($book_info['exchange_type'] != 'exchange_only'): ?>
                                <div class="me-3">
                                    <span class="h3 text-primary mb-0"><?php echo number_format($book_info['price'], 0, ',', '.'); ?> đ</span>
                                </div>
                                <?php endif; ?>
                                
                                <div>
                                    <p class="text-muted mb-0">Đăng ngày: <?php echo date('d/m/Y', strtotime($book_info['created_at'])); ?></p>
                                </div>
                            </div>
                            
                            <?php if($book_info['status'] == 'available' && isset($_SESSION['user_id']) && $_SESSION['user_id'] != $book_info['user_id']): ?>
                            <div>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exchangeModal">
                                    <i class="fas fa-exchange-alt me-1"></i> Yêu cầu trao đổi
                                </button>
                                
                                <?php if($book_info['exchange_type'] != 'exchange_only'): ?>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#buyModal">
                                    <i class="fas fa-shopping-cart me-1"></i> Mua ngay
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php elseif($book_info['status'] != 'available'): ?>
                            <div class="alert alert-secondary" role="alert">
                                Sách này đã được trao đổi/bán.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Thêm nút báo cáo ở dưới thông tin sách -->
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $book_info['user_id']): ?>
                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#reportBookModal">
                            <i class="fas fa-flag me-1"></i> Báo cáo sách này
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Thông tin chủ sở hữu -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Thông tin người đăng</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="../uploads/users/<?php echo $book_owner['profile_image'] ? $book_owner['profile_image'] : 'default.jpg'; ?>" alt="<?php echo $book_owner['username']; ?>" class="owner-image me-3">
                        <div>
                            <h5 class="mb-1"><?php echo $book_owner['full_name']; ?></h5>
                            <p class="text-muted mb-1">@<?php echo $book_owner['username']; ?></p>
                            <div class="rating-stars mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $book_owner['rating']): ?>
                                <i class="fas fa-star text-warning"></i>
                                <?php elseif ($i - 0.5 <= $book_owner['rating']): ?>
                                <i class="fas fa-star-half-alt text-warning"></i>
                                <?php else: ?>
                                <i class="far fa-star text-warning"></i>
                                <?php endif; ?>
                                <?php endfor; ?>
                                <span class="ms-1"><?php echo number_format($book_owner['rating'], 1); ?></span>
                            </div>
                            <a href="profile.php?id=<?php echo $book_owner['id']; ?>" class="btn btn-sm btn-outline-primary">Xem hồ sơ</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3">
            <!-- Sách đề xuất -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Có thể bạn quan tâm</h5>
                </div>
                <div class="card-body p-3">
                    <?php if(count($recommended_books) > 0): ?>
                        <?php foreach($recommended_books as $rec_book): ?>
                        <div class="d-flex mb-3">
                            <a href="book_details.php?id=<?php echo $rec_book['id']; ?>" class="me-2">
                                <img src="../uploads/books/<?php echo $rec_book['image']; ?>" alt="<?php echo $rec_book['title']; ?>" class="img-fluid" style="width: 60px; height: 90px; object-fit: cover;">
                            </a>
                            <div>
                                <h6 class="mb-1"><a href="book_details.php?id=<?php echo $rec_book['id']; ?>" class="text-decoration-none text-dark"><?php echo $rec_book['title']; ?></a></h6>
                                <p class="text-muted small mb-1"><?php echo $rec_book['author']; ?></p>
                                <?php if($rec_book['exchange_type'] != 'exchange_only'): ?>
                                <span class="fw-bold small"><?php echo number_format($rec_book['price'], 0, ',', '.'); ?> đ</span>
                                <?php else: ?>
                                <span class="badge bg-success">Chỉ trao đổi</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-muted my-3">Không có sách đề xuất.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal trao đổi -->
<?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $book_info['user_id']): ?>
<div class="modal fade" id="exchangeModal" tabindex="-1" aria-labelledby="exchangeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="exchangeModalLabel">Yêu cầu trao đổi sách</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Sách bạn muốn</h6>
                            <div class="d-flex align-items-center">
                                <img src="../uploads/books/<?php echo $book_info['image']; ?>" alt="<?php echo $book_info['title']; ?>" class="img-thumbnail me-3" style="width: 80px;">
                                <div>
                                    <p class="mb-1 fw-bold"><?php echo $book_info['title']; ?></p>
                                    <p class="mb-0 small text-muted"><?php echo $book_info['author']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Chủ sở hữu</h6>
                            <div class="d-flex align-items-center">
                                <img src="../uploads/users/<?php echo $book_owner['profile_image'] ? $book_owner['profile_image'] : 'default.jpg'; ?>" alt="<?php echo $book_owner['username']; ?>" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                <div>
                                    <p class="mb-1 fw-bold"><?php echo $book_owner['full_name']; ?></p>
                                    <p class="mb-0 small text-muted">@<?php echo $book_owner['username']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="requester_book_id" class="form-label">Sách bạn muốn trao đổi (nếu có)</label>
                        <select class="form-select" id="requester_book_id" name="requester_book_id">
                            <option value="">-- Chọn sách --</option>
                            <?php foreach($user_books as $user_book): ?>
                            <option value="<?php echo $user_book['id']; ?>"><?php echo $user_book['title']; ?> (<?php echo $user_book['author']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(count($user_books) == 0): ?>
                        <div class="form-text text-danger">Bạn chưa có sách nào khả dụng để trao đổi. <a href="add_book.php" target="_blank">Đăng sách ngay</a>.</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_money_involved" name="is_money_involved" value="1">
                            <label class="form-check-label" for="is_money_involved">
                                Trao đổi kèm tiền
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="money_amount_container" style="display: none;">
                        <label for="amount" class="form-label">Số tiền kèm theo</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="amount" name="amount" min="1000" step="1000" value="0">
                            <span class="input-group-text">VNĐ</span>
                        </div>
                        <div class="form-text">Nhập số tiền bạn sẵn sàng kèm theo để trao đổi sách này.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Lời nhắn</label>
                        <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                        <div class="form-text">Nhập lời nhắn cho chủ sở hữu sách, bao gồm thông tin liên hệ và thời gian thuận tiện để trao đổi.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="send_exchange_request" class="btn btn-primary">Gửi yêu cầu</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal mua sách -->
<?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $book_info['user_id'] && $book_info['exchange_type'] != 'exchange_only'): ?>
<div class="modal fade" id="buyModal" tabindex="-1" aria-labelledby="buyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="buyModalLabel">Xác Nhận Mua Sách</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <img src="../uploads/books/<?php echo $book_info['image']; ?>" alt="<?php echo $book_info['title']; ?>" class="img-thumbnail mb-3" style="max-height: 150px;">
                    <h5><?php echo $book_info['title']; ?></h5>
                    <p class="text-muted"><?php echo $book_info['author']; ?></p>
                    <h4 class="text-primary"><?php echo number_format($book_info['price'], 0, ',', '.'); ?> đ</h4>
                </div>
                
                <div class="alert alert-info">
                    <p class="mb-0">
                        <i class="fas fa-info-circle me-2"></i> Bạn muốn tiếp tục mua sách này?
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <a href="payment.php?book_id=<?php echo $book_info['id']; ?>" class="btn btn-success">
                    <i class="fas fa-shopping-cart me-1"></i> Tiếp tục
                </a>
            </div>
        </div>
    </div>
</div>


<!-- Modal Báo cáo sách -->
<div class="modal fade" id="reportBookModal" tabindex="-1" aria-labelledby="reportBookModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="../pages/report_user.php">
                <input type="hidden" name="reported_user_id" value="<?php echo $book_info['user_id']; ?>">
                <input type="hidden" name="book_id" value="<?php echo $book_info['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportBookModalLabel">Báo cáo sách</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="report_type" class="form-label">Loại báo cáo</label>
                        <select class="form-select" id="report_type" name="report_type" required>
                            <option value="">-- Chọn loại báo cáo --</option>
                            <option value="inappropriate_content">Nội dung không phù hợp</option>
                            <option value="fake_book">Sách giả/Sách không đúng mô tả</option>
                            <option value="spam">Spam/Quảng cáo</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>
                    <div class="mb-3">
                    <label for="report_reason" class="form-label">Lý do báo cáo</label>
                    <textarea class="form-control" id="report_reason" name="report_reason" rows="4" required placeholder="Mô tả chi tiết lý do báo cáo..."></textarea>
                    </div>
                    </div>
                    <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="report_user" class="btn btn-danger">Gửi báo cáo</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    // Hiển thị/ẩn trường số tiền
    document.addEventListener('DOMContentLoaded', function() {
        // Kiểm tra phương thức thanh toán
        const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
        const processPaymentBtn = document.getElementById('process_payment');
        
        if (paymentRadios.length > 0 && processPaymentBtn) {
            paymentRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        processPaymentBtn.removeAttribute('disabled');
                    }
                });
            });
        }
        
        // Hiện khi check vào trao đổi kèm tiền
        const isMoneyInvolved = document.getElementById('is_money_involved');
        const moneyAmountContainer = document.getElementById('money_amount_container');
        
        if (isMoneyInvolved && moneyAmountContainer) {
            isMoneyInvolved.addEventListener('change', function() {
                if (this.checked) {
                    moneyAmountContainer.style.display = 'block';
                } else {
                    moneyAmountContainer.style.display = 'none';
                }
            });
        }
    });
    
    // Thay đổi ảnh chính
    function changeMainImage(thumbnail, newSrc) {
        // Thay đổi ảnh chính
        document.querySelector('.book-main-image').src = newSrc;
        
        // Cập nhật thumbnail đang active
        document.querySelectorAll('.book-thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
        });
        thumbnail.classList.add('active');
    }
</script>

<style>
    .book-thumbnail {
        width: 60px;
        height: 60px;
        object-fit: cover;
        cursor: pointer;
        border: 2px solid #dee2e6;
    }
    
    .book-thumbnail.active {
        border-color: #0d6efd;
    }
    
    .book-main-image {
        width: 100%;
        height: auto;
        max-height: 400px;
        object-fit: contain;
    }

    .owner-image {
        width: 100px;     /* Điều chỉnh chiều rộng */
        height: 100px;    /* Điều chỉnh chiều cao */
        object-fit: cover;  /* Đảm bảo ảnh luôn fill đúng kích thước */
        border-radius: 50%;  /* Tạo hình tròn (nếu muốn) */
        border: 2px solid #dee2e6;  /* Thêm viền nhẹ (tùy chọn) */
    }
    
    .payment-method label {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 10px;
        height: 120px;
    }

    .payment-method label i,
    .payment-method label img {
        margin-bottom: 10px;
    }

    .payment-method input:checked + label {
        border-color: #007bff;
        background-color: rgba(0, 123, 255, 0.1);
    }
</style>

<?php
// Include footer
require_once '../includes/footer.php';
?>