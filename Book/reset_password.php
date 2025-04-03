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


// Thêm đoạn code debug này ở đầu file (sau khi đã include các file cần thiết)
if(isset($_GET['token']) && !empty($_GET['token'])) {
    error_log("Token from URL: " . $_GET['token']);
}
// Initialize error and success messages
$error = '';
$success = '';
$token_valid = false;
$token = '';

// Check if token exists
if(isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    

     // Thêm đoạn code debug này ở đây
     $db = new Database();
     $db->query('SELECT * FROM password_resets WHERE token = :token');
     $db->bind(':token', $token);
     $row = $db->single();
     error_log("Direct DB check for token: " . ($row ? "Found" : "Not found"));
     if($row) {
         error_log("Token details: " . json_encode($row));
     }
     
    // Create user object
    $user = new User();
    
    // Verify token
    $token_valid = $user->verifyResetToken($token);
    
    if(!$token_valid) {
        $error = 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.';
    }
} else {
    $error = 'Không tìm thấy mã đặt lại mật khẩu.';
}

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate form data
    if(empty($password) || empty($confirm_password)) {
        $error = 'Vui lòng điền đầy đủ thông tin.';
    } elseif(strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        // Create user object
        $user = new User();
        
        // Reset password
        if($user->resetPassword($token, $password)) {
            $success = 'Mật khẩu của bạn đã được đặt lại thành công. Vui lòng đăng nhập bằng mật khẩu mới.';
            
            // Redirect to login page after 3 seconds
            header('refresh:3;url=login.php');
        } else {
            $error = 'Đã xảy ra lỗi khi đặt lại mật khẩu. Vui lòng thử lại.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - BookSwap</title>
    
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
    
    <!-- Reset Password Form -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="card-title text-center mb-4">Đặt lại mật khẩu</h2>
                        
                        <?php if($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error; ?>
                                <?php if(strpos($error, 'không hợp lệ') !== false || strpos($error, 'hết hạn') !== false || strpos($error, 'Không tìm thấy') !== false): ?>
                                    <p class="mt-2 mb-0">
                                        <a href="forgot_password.php" class="alert-link">Yêu cầu liên kết đặt lại mật khẩu mới</a>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($success): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success; ?>
                            </div>
                        <?php elseif($token_valid): ?>
                            <form action="<?php echo $_SERVER['PHP_SELF'] . '?token=' . $token; ?>" method="post">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Mật khẩu mới</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">Mật khẩu phải có ít nhất 6 ký tự.</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 py-2">Đặt lại mật khẩu</button>
                            </form>
                        <?php endif; ?>
                        
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