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

// Initialize error and success messages
$error = '';
$success = '';

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    
    // Validate form data
    if(empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name)) {
        $error = 'Vui lòng điền đầy đủ thông tin.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } elseif(strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        // Create user object
        $user = new User();
        
        // Set user data
        $user_data = [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'full_name' => $full_name
        ];
        
        // Register the user
        $result = $user->register($user_data);
        
        if($result) {
            $success = 'Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.';
            
            // Redirect to login page after 2 seconds
            header('refresh:2;url=login.php' . (isset($_GET['redirect']) ? '?redirect=' . $_GET['redirect'] : ''));
        } else {
            $error = 'Tên đăng nhập hoặc email đã tồn tại. Vui lòng thử lại.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - BookSwap</title>
    
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
    
    <!-- Register Form -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="card-title text-center mb-4">Đăng ký tài khoản</h2>
                        
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
                        
                        <form action="<?php echo $_SERVER['PHP_SELF'] . (isset($_GET['redirect']) ? '?redirect=' . $_GET['redirect'] : ''); ?>" method="post">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Họ và tên</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Tên đăng nhập</label>
                                <input type="text" class="form-control" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                <div class="form-text">Tên đăng nhập phải là duy nhất và không chứa khoảng trắng.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Mật khẩu</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Mật khẩu phải có ít nhất 6 ký tự.</div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">Tôi đồng ý với <a href="#" class="text-decoration-none">Điều khoản sử dụng</a> và <a href="#" class="text-decoration-none">Chính sách bảo mật</a></label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2">Đăng ký</button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p>Đã có tài khoản? <a href="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . $_GET['redirect'] : ''; ?>" class="text-decoration-none">Đăng nhập ngay</a></p>
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