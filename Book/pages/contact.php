<?php
// Thiết lập biến
$is_detail_page = true;

// Include file Mailer để sử dụng chức năng gửi mail
require_once '../classes/Mailer.php';

// Xử lý form liên hệ
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra và xử lý dữ liệu form
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validate dữ liệu
    $errors = [];

    if (empty($name)) {
        $errors[] = 'Vui lòng nhập họ và tên';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Vui lòng nhập email hợp lệ';
    }

    if (empty($subject)) {
        $errors[] = 'Vui lòng nhập chủ đề';
    }

    if (empty($message)) {
        $errors[] = 'Vui lòng nhập nội dung tin nhắn';
    }

    // Nếu không có lỗi
    if (empty($errors)) {
        // Sử dụng phương thức sendContactForm để gửi email
        try {
            if (Mailer::sendContactForm($name, $email, $phone, $subject, $message)) {
                $msg = 'Cảm ơn bạn đã liên hệ. Chúng tôi sẽ phản hồi trong thời gian sớm nhất!';
                $msg_type = 'success';
                
                // Reset form
                $name = $email = $phone = $subject = $message = '';
            } else {
                $msg = 'Có lỗi xảy ra khi gửi tin nhắn. Vui lòng thử lại sau hoặc liên hệ qua số điện thoại.';
                $msg_type = 'danger';
            }
        } catch (Exception $e) {
            $msg = 'Có lỗi xảy ra: ' . $e->getMessage();
            $msg_type = 'danger';
        }
    } else {
        // Có lỗi validate
        $msg = implode('<br>', $errors);
        $msg_type = 'danger';
    }
}

// Include header
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0 text-center">Liên Hệ BookSwap</h2>
                </div>
                <div class="card-body p-5">
                    <?php if ($msg): ?>
                    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Số điện thoại (tùy chọn)</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">Chủ đề <span class="text-danger">*</span></label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="">Chọn chủ đề</option>
                                <option value="Hỗ trợ kỹ thuật" <?php echo ($subject ?? '') == 'Hỗ trợ kỹ thuật' ? 'selected' : ''; ?>>Hỗ trợ kỹ thuật</option>
                                <option value="Góp ý và báo lỗi" <?php echo ($subject ?? '') == 'Góp ý và báo lỗi' ? 'selected' : ''; ?>>Góp ý và báo lỗi</option>
                                <option value="Câu hỏi về trao đổi sách" <?php echo ($subject ?? '') == 'Câu hỏi về trao đổi sách' ? 'selected' : ''; ?>>Câu hỏi về trao đổi sách</option>
                                <option value="Hợp tác" <?php echo ($subject ?? '') == 'Hợp tác' ? 'selected' : ''; ?>>Hợp tác</option>
                                <option value="Khác" <?php echo ($subject ?? '') == 'Khác' ? 'selected' : ''; ?>>Khác</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">Nội dung tin nhắn <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i> Gửi tin nhắn
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Thông tin liên hệ bổ sung -->
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="d-flex flex-column align-items-center">
                                <i class="fas fa-map-marker-alt fa-2x text-primary mb-2"></i>
                                <h5>Địa chỉ</h5>
                                <p class="text-muted">Tầng 5, 123 Sách, Quận 1, TP. Hồ Chí Minh</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="d-flex flex-column align-items-center">
                                <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                                <h5>Email</h5>
                                <p class="text-muted">contact@bookswap.vn</p>
                                <p class="text-muted">support@bookswap.vn</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex flex-column align-items-center">
                                <i class="fas fa-phone fa-2x text-primary mb-2"></i>
                                <h5>Điện thoại</h5>
                                <p class="text-muted">028 1234 5678</p>
                                <p class="text-muted">Hotline: 1900 6868</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bản đồ (sử dụng iframe thay vì JavaScript API) -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0 text-center">Vị Trí Của Chúng Tôi</h5>
                </div>
                <div class="card-body p-0">
                    <div class="ratio ratio-16x9">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.4241667024246!2d106.68525841471867!3d10.775157992323746!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f3a9d8d1bb3%3A0x35d2d39f9a24c18e!2zUXXhuq1uIDEsIFRow6BuaCBwaOG7kSBI4buTIENow60gTWluaCwgVmnhu4d0IE5hbQ!5e0!3m2!1svi!2s!4v1711603786175!5m2!1svi!2s" 
                            width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="mb-0 text-muted"><i class="fas fa-map-marker-alt text-primary me-2"></i>Tầng 5, 123 Sách, Quận 1, TP. Hồ Chí Minh</p>
                            <a href="https://maps.google.com/maps?ll=10.775153,106.687836&z=15&t=m&hl=vi&gl=VN&mapclient=embed&q=Quận+1+Thành+phố+Hồ+Chí+Minh" 
                               class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="fas fa-directions me-2"></i>Chỉ đường
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>

<script>
// Script chỉ cho validate số điện thoại
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            // Chỉ cho phép nhập số
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
});
</script>

<style>
.form-label span.text-danger {
    font-size: 0.8em;
}

#distance-info {
    background-color: #f8f9fa;
    border-radius: 5px;
    padding: 10px;
    min-height: 100px;
}

#map {
    border-radius: 0 0 5px 0;
}
</style>