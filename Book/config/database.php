<?php
// Thông tin kết nối database
define('DB_HOST', 'localhost:3306');
define('DB_USER', 'root');
define('DB_PASS', 'Huycm0941744118');
define('DB_NAME', 'book');

// Tạo kết nối
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Đặt charset là utf8
$conn->set_charset("utf8");