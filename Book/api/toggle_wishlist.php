<?php
// Khởi tạo session
session_start();

// Kiểm tra đăng nhập
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'not_logged_in']);
    exit();
}

// Include cần thiết
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/User.php';

// Kiểm tra request
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    $user_id = $_SESSION['user_id'];
    $book_id = $_POST['book_id'];
    
    $user = new User();
    
    // Kiểm tra sách có trong danh sách yêu thích không
    if($user->isInWishlist($user_id, $book_id)) {
        // Xóa khỏi danh sách yêu thích
        if($user->removeFromWishlist($user_id, $book_id)) {
            echo json_encode(['status' => 'success', 'action' => 'removed']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Không thể xóa khỏi danh sách yêu thích.']);
        }
    } else {
        // Thêm vào danh sách yêu thích
        if($user->addToWishlist($user_id, $book_id)) {
            echo json_encode(['status' => 'success', 'action' => 'added']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Không thể thêm vào danh sách yêu thích.']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
}