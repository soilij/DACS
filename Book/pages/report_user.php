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
require_once '../classes/Report.php';
require_once '../classes/Notification.php';

// Xử lý báo cáo người dùng
if (isset($_POST['report_user'])) {
    $reported_user_id = $_POST['reported_user_id'];
    $reporter_user_id = $_SESSION['user_id'];
    $report_type = $_POST['report_type'];
    $report_reason = trim($_POST['report_reason']);
    
    // Kiểm tra dữ liệu
    if (empty($report_reason) || empty($report_type)) {
        $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin báo cáo!';
        header('Location: profile.php?id=' . $reported_user_id);
        exit();
    }
    
    // Kiểm tra xem người dùng có tự báo cáo mình không
    if ($reported_user_id == $reporter_user_id) {
        $_SESSION['error'] = 'Bạn không thể báo cáo chính mình!';
        header('Location: profile.php?id=' . $reported_user_id);
        exit();
    }
    
    // Tạo báo cáo mới
    $report = new Report();
    
    $data = [
        'reported_user_id' => $reported_user_id,
        'reporter_user_id' => $reporter_user_id,
        'report_type' => $report_type,
        'report_reason' => $report_reason,
        'book_id' => isset($_POST['book_id']) ? $_POST['book_id'] : null,
        'exchange_id' => isset($_POST['exchange_id']) ? $_POST['exchange_id'] : null
    ];
    
    if ($report->createReport($data)) {
        // Thông báo cho admin có báo cáo mới
        // Lấy danh sách admin
        $db = new Database();
        $db->query('SELECT id FROM users WHERE is_admin = 1');
        $admins = $db->resultSet();
        
        $notification = new Notification();
        foreach ($admins as $admin) {
            $notifyData = [
                'user_id' => $admin['id'],
                'message' => 'Có báo cáo người dùng mới cần xử lý!',
                'link' => 'admin/reports_list.php'
            ];
            $notification->create($notifyData);
        }
        
        $_SESSION['success'] = 'Đã gửi báo cáo thành công. Quản trị viên sẽ xem xét báo cáo của bạn trong thời gian sớm nhất!';
    } else {
        $_SESSION['error'] = 'Có lỗi xảy ra khi gửi báo cáo!';
    }
    
    // Quay lại trang profile
    header('Location: profile.php?id=' . $reported_user_id);
    exit();
} else {
    // Nếu không phải POST request, chuyển hướng về trang chủ
    header('Location: ../index.php');
    exit();
}
?>