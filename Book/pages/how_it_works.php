<?php
// Thiết lập biến
$is_detail_page = true;

// Include header
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold mb-4">Cách Thức Hoạt Động của BookSwap</h1>
        <p class="lead text-muted">Khám phá cách thức trao đổi sách dễ dàng và thú vị</p>
    </div>

    <!-- Quy Trình Hoạt Động -->
    <section class="process-section mb-5">
        <div class="row">
            <div class="col-md-3 mb-4 mb-md-0">
                <div class="card h-100 border-0 text-center">
                    <div class="card-body">
                        <div class="process-icon mb-3">
                            <i class="fas fa-user-plus fa-3x text-primary"></i>
                        </div>
                        <h4 class="card-title">Đăng Ký</h4>
                        <p class="card-text text-muted">
                            Tạo tài khoản miễn phí để bắt đầu trao đổi sách với cộng đồng BookSwap.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4 mb-md-0">
                <div class="card h-100 border-0 text-center">
                    <div class="card-body">
                        <div class="process-icon mb-3">
                            <i class="fas fa-book-medical fa-3x text-success"></i>
                        </div>
                        <h4 class="card-title">Đăng Sách</h4>
                        <p class="card-text text-muted">
                            Chia sẻ những cuốn sách bạn muốn trao đổi. Cung cấp thông tin chi tiết và hình ảnh rõ ràng.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4 mb-md-0">
                <div class="card h-100 border-0 text-center">
                    <div class="card-body">
                        <div class="process-icon mb-3">
                            <i class="fas fa-search fa-3x text-info"></i>
                        </div>
                        <h4 class="card-title">Tìm Kiếm</h4>
                        <p class="card-text text-muted">
                            Khám phá kho sách phong phú. Lọc theo danh mục, tình trạng, hoặc hình thức trao đổi.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card h-100 border-0 text-center">
                    <div class="card-body">
                        <div class="process-icon mb-3">
                            <i class="fas fa-exchange-alt fa-3x text-warning"></i>
                        </div>
                        <h4 class="card-title">Trao Đổi</h4>
                        <p class="card-text text-muted">
                            Gửi yêu cầu trao đổi, thương lượng và hoàn tất giao dịch một cách an toàn.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Chi Tiết Quy Trình -->
    <section class="details-section bg-light py-5 mb-5">
        <div class="container">
            <h2 class="text-center mb-4 fw-bold">Chi Tiết Quy Trình</h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h4 class="card-title text-primary mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i> Đăng Ký & Đăng Nhập
                            </h4>
                            <p class="card-text">
                                - Tạo tài khoản hoàn toàn miễn phí
                                - Xác thực email để bảo mật tài khoản
                                - Tùy chỉnh hồ sơ cá nhân
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h4 class="card-title text-success mb-3">
                                <i class="fas fa-upload me-2"></i> Đăng Sách
                            </h4>
                            <p class="card-text">
                                - Chụp ảnh sách rõ nét
                                - Nhập thông tin chi tiết (tiêu đề, tác giả, mô tả)
                                - Chọn hình thức: Trao đổi hoặc Bán
                                - Đánh giá tình trạng sách
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h4 class="card-title text-info mb-3">
                                <i class="fas fa-hand-pointer me-2"></i> Tìm Kiếm & Lựa Chọn
                            </h4>
                            <p class="card-text">
                                - Tìm kiếm sách theo từ khóa, danh mục
                                - Lọc theo tình trạng, giá, hình thức
                                - Xem chi tiết thông tin sách
                                - Thêm vào danh sách yêu thích
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h4 class="card-title text-warning mb-3">
                                <i class="fas fa-paper-plane me-2"></i> Gửi Yêu Cầu
                            </h4>
                            <p class="card-text">
                                - Chọn sách muốn trao đổi
                                - Nhập lời nhắn chi tiết
                                - Tùy chọn trao đổi kèm tiền
                                - Chờ chủ sở hữu phản hồi
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h4 class="card-title text-danger mb-3">
                                <i class="fas fa-handshake me-2"></i> Thỏa Thuận
                            </h4>
                            <p class="card-text">
                                - Trao đổi thông tin chi tiết
                                - Thống nhất phương thức giao dịch
                                - Xác nhận địa điểm, thời gian
                                - Đảm bảo an toàn cho cả hai bên
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h4 class="card-title text-primary mb-3">
                                <i class="fas fa-star me-2"></i> Đánh Giá
                            </h4>
                            <p class="card-text">
                                - Hoàn tất giao dịch
                                - Đánh giá người dùng
                                - Chia sẻ trải nghiệm
                                - Xây dựng uy tín cộng đồng
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Lợi Ích -->
    <section class="benefits-section text-center py-5 bg-primary text-white">
        <div class="container">
            <h2 class="mb-4 fw-bold">Tại Sao Chọn BookSwap?</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <i class="fas fa-piggy-bank fa-3x mb-3"></i>
                    <h4>Tiết Kiệm Chi Phí</h4>
                    <p>Trao đổi sách mà không tốn nhiều tiền, mở rộng tủ sách của bạn một cách thông minh.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <i class="fas fa-recycle fa-3x mb-3"></i>
                    <h4>Bảo Vệ Môi Trường</h4>
                    <p>Tái sử dụng sách, giảm thiểu rác thải và bảo vệ tài nguyên rừng.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <h4>Kết Nối Cộng Đồng</h4>
                    <p>Gặp gỡ những người bạn đồng hành yêu sách, chia sẻ kiến thức và trải nghiệm.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Câu Hỏi Thường Gặp -->
    <section class="faq-section py-5">
        <div class="container">
            <h2 class="text-center mb-4 fw-bold">Câu Hỏi Thường Gặp</h2>
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                            BookSwap là gì?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            BookSwap là nền tảng trực tuyến kết nối những người yêu sách, cho phép họ trao đổi, mua bán sách một cách dễ dàng và an toàn.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                            Làm thế nào để bắt đầu?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Bạn chỉ cần đăng ký tài khoản miễn phí, đăng những cuốn sách bạn muốn trao đổi và bắt đầu khám phá kho sách của cộng đồng.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                            Việc trao đổi sách có an toàn không?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Chúng tôi cung cấp hệ thống đánh giá người dùng, hỗ trợ giao tiếp an toàn và giúp bạn kiểm tra uy tín của đối tác trao đổi.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section bg-light text-center py-5">
        <div class="container">
            <h2 class="mb-4 fw-bold">Bắt Đầu Trải Nghiệm Ngay Hôm Nay</h2>
            <p class="lead mb-4">Hàng nghìn cuốn sách đang chờ đợi bạn khám phá!</p>
            <div>
                <a href="../register.php" class="btn btn-primary btn-lg me-3">Đăng Ký Miễn Phí</a>
                <a href="../pages/search.php" class="btn btn-outline-primary btn-lg">Khám Phá Sách</a>
            </div>
        </div>
    </section>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>

<style>
.process-icon {
    margin-bottom: 1rem;
    color: #007bff;
}

.benefits-section {
    background-color: #007bff;
    color: white;
}

.benefits-section i {
    color: white;
    margin-bottom: 1rem;
}

.faq-section .accordion-button {
    font-weight: bold;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mở accordion đầu tiên mặc định
    const firstAccordionButton = document.querySelector('.accordion-button');
    if (firstAccordionButton) {
        firstAccordionButton.setAttribute('aria-expanded', 'true');
    }

    const firstAccordionCollapse = document.querySelector('.accordion-collapse');
    if (firstAccordionCollapse) {
        firstAccordionCollapse.classList.add('show');
    }
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>