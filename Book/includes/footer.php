</div>
    </main>
    
    <!-- Newsletter Section -->
    <section class="py-5 bg-dark text-white">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-md-8">
                    <h2 class="mb-4">Đăng ký nhận bản tin</h2>
                    <p class="mb-4">Nhận thông báo về sách mới và cơ hội trao đổi hấp dẫn. Giảm 10% cho lần trao đổi đầu tiên!</p>
                    <form class="d-flex justify-content-center">
                        <div class="input-group mb-3" style="max-width: 500px;">
                            <input type="email" class="form-control" placeholder="Email của bạn" required>
                            <button class="btn btn-primary" type="submit">Đăng ký</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <!-- Thông tin liên hệ -->
                <div class="col-lg-3 mb-4">
                    <h5 class="mb-3">Kết nối với chúng tôi</h5>
                    <address class="mb-0">
                        <p class="mb-2">123 Đường Sách, Quận 1</p>
                        <p class="mb-2">TP. Hồ Chí Minh, Việt Nam</p>
                        <p class="mb-2">Email: contact@bookswap.vn</p>
                        <p class="mb-2">Điện thoại: 028 1234 5678</p>
                    </address>
                    
                    <!-- Social Media Icons -->
                    <div class="mt-3">
                        <a href="#" class="text-dark me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-dark me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-dark me-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-dark"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <!-- Danh mục -->
                <div class="col-lg-3 mb-4">
                    <h5 class="mb-3">Danh mục sách</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark">Văn học</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark">Kinh tế</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark">Tâm lý - Kỹ năng sống</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark">Nuôi dạy con</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark">Sách thiếu nhi</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark">Tiểu sử - Hồi ký</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark">Sách ngoại ngữ</a></li>
                    </ul>
                </div>
                
                <!-- Khám phá -->
                <div class="col-lg-3 mb-4">
                    <h5 class="mb-3">Khám phá</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark">Về chúng tôi</a></li>
                        <li class="mb-2"> <a class="nav-link" href="<?php echo isset($is_detail_page) && $is_detail_page ? 'how_it_works.php' : 'pages/how_it_works.php'; ?>">Cách thức hoạt động</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark">Câu hỏi thường gặp</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark">Điều khoản sử dụng</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark">Chính sách bảo mật</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark">Hỗ trợ</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark">Liên hệ</a></li>
                    </ul>
                </div>
                
                <!-- Tài khoản -->
                <div class="col-lg-3 mb-4">
                    <h5 class="mb-3">Tài khoản của tôi</h5>
                    <ul class="list-unstyled">
                        <li><a class="dropdown-item" href="<?php echo isset($is_detail_page) && $is_detail_page ? '../pages/profile.php' : 'pages/profile.php'; ?>">Hồ sơ cá nhân</a></li>
                        <li><a class="dropdown-item" href="<?php echo isset($is_detail_page) && $is_detail_page ? '../pages/my_books.php' : 'pages/my_books.php'; ?>">Sách của tôi</a></li>
                        <li><a class="dropdown-item" href="<?php echo isset($is_detail_page) && $is_detail_page ? '../pages/exchange_requests.php' : 'pages/exchange_requests.php'; ?>">Yêu cầu trao đổi</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark">Yêu cầu trao đổi</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark">Lịch sử trao đổi</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark">Đánh giá</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark">Thẻ quà tặng</a></li>
                    </ul>
                </div>
            </div>
            
            <hr>
            
            <!-- Copyright và Payment Methods -->
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="small mb-0">&copy; <?php echo date('Y'); ?> BookSwap. Tất cả quyền được bảo lưu.</p>
                </div>
                <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                    <img src="assets/images/payment-visa.png" alt="Visa" height="30" class="me-2">
                    <img src="assets/images/payment-mastercard.png" alt="Mastercard" height="30" class="me-2">
                    <img src="assets/images/payment-paypal.png" alt="PayPal" height="30" class="me-2">
                    <img src="assets/images/payment-momo.png" alt="MoMo" height="30">
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>