<?php
// Khởi động session
session_start();

// Hủy tất cả các biến session
$_SESSION = array();

// Hủy session
session_destroy();

// Chuyển hướng về trang chủ
header("Location: index.php");
exit();
?>