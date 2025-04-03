<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập']);
    exit();
}

// Include các file cần thiết
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Notification.php';

// Kiểm tra ID thông báo
if (!isset($_POST['notification_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu ID thông báo']);
    exit();
}

$notification_id = $_POST['notification_id'];
$user_id = $_SESSION['user_id'];

// Khởi tạo đối tượng
$notification = new Notification();

// Đánh dấu thông báo đã đọc
try {
    $result = $notification->markAsRead($notification_id, $user_id);
    
    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Không thể đánh dấu thông báo']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();