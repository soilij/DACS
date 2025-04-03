<?php
// Initialize session
session_start();

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Include necessary files
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Mailer.php';

// Initialize error and success messages
$error = '';
$success = '';

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // Validate email
    if(empty($email)) {
        $error = 'Vui lòng nhập địa chỉ email.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Địa chỉ email không hợp lệ.';
    } else {
        // Create user object
        $user = new User();
        
        // Check if email exists
        if($user->emailExists($email)) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Save reset token to database
            if($user->saveResetToken($email, $token, $expiry)) {
                // Xây dựng link đặt lại mật khẩu
               
                $reset_link = "http://localhost/DACS/Book/reset_password.php?token=" . $token;

                // Thêm log
                error_log("Reset link created: " . $reset_link);
                
                // Gửi email
                $email_sent = Mailer::sendPasswordReset($email, $reset_link);
                
                if ($email_sent) {
                    $success = 'Hệ thống đã gửi email đặt lại mật khẩu. Vui lòng kiểm tra hộp thư của bạn.';
                } else {
                    $error = 'Không thể gửi email đặt lại mật khẩu. Vui lòng thử lại sau.';
                }
            } else {
                $error = 'Đã xảy ra lỗi khi xử lý yêu cầu của bạn. Vui lòng thử lại sau.';
            }
        } else {
            // Don't reveal that email doesn't exist for security reasons
            $success = 'Nếu địa chỉ email tồn tại trong hệ thống, chúng tôi sẽ gửi hướng dẫn đặt lại mật khẩu.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - BookSwap</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
</head>
<body>
    <!-- Header -->
    <header class="bg-white py-3 border-bottom">
        <div class="container">
            <div class="row align-items-center">
                <!-- Logo -->
                <div class="col-auto">
                    <a href="index.php" class="text-decoration-none">
                        <h1 class="mb-0 fw-bold">BOOKSWAP</h1>
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Forgot Password Form -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="card-title text-center mb-4">Quên mật khẩu</h2>
                        <p class="text-center mb-4">Vui lòng nhập địa chỉ email bạn đã sử dụng để đăng ký. Chúng tôi sẽ gửi cho bạn một liên kết để đặt lại mật khẩu.</p>
                        
                        <?php if($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($success): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                            <div class="mb-4">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2">Gửi liên kết đặt lại</button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p><a href="login.php" class="text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Quay lại đăng nhập</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="py-3 bg-light mt-auto">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="small mb-0">&copy; <?php echo date('Y'); ?> BookSwap. Tất cả quyền được bảo lưu.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>