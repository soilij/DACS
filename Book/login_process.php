<?php
// Khởi động session
session_start();

// Include các file cần thiết
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';

// Kiểm tra nếu form đăng nhập được submit
if (isset($_POST['login'])) {
    $username_email = trim($_POST['username_email']);
    $password = $_POST['password'];
    
    // Kiểm tra dữ liệu trống
    if (empty($username_email) || empty($password)) {
        $_SESSION['error'] = 'Vui lòng nhập đầy đủ tên đăng nhập/email và mật khẩu!';
        header('Location: login.php');
        exit();
    }
    
    // Kiểm tra đăng nhập
    $user = new User();
    $userData = $user->login($username_email, $password);
    
    if ($userData) {
        // Kiểm tra tài khoản bị khóa
        if ($userData['is_blocked'] == 1) {
            $_SESSION['error'] = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên để biết thêm thông tin.';
            header('Location: login.php');
            exit();
        }

        // Kiểm tra tài khoản bị tạm khóa
        if ($userData['suspended_until'] && strtotime($userData['suspended_until']) > time()) {
            $_SESSION['error'] = 'Tài khoản của bạn hiện đang bị tạm khóa đến ' . date('d/m/Y H:i', strtotime($userData['suspended_until'])) . '. Vui lòng thử lại sau.';
            header('Location: login.php');
            exit();
        }
        
        // Đăng nhập thành công, thiết lập session
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['username'] = $userData['username'];
        $_SESSION['is_admin'] = $userData['is_admin'];
        
        // Cập nhật thời gian đăng nhập cuối
        $user->updateLastLogin($userData['id']);
        
        // Chuyển hướng tùy theo vai trò
        if ($userData['is_admin'] == 1) {
            header('Location: admin/index.php');
        } else {
            header('Location: index.php');
        }
        exit();
    } else {
        $_SESSION['error'] = 'Tên đăng nhập/email hoặc mật khẩu không đúng!';
        header('Location: login.php');
        exit();
    }
} else {
    // Nếu không phải POST request, chuyển hướng về trang đăng nhập
    header('Location: login.php');
    exit();
}
?>