<?php
// admin/chat_delete.php

// Include header and check admin permission


require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    header("Location: ../index.php");
    exit;
}

// Xử lý yêu cầu xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // Xóa cuộc trò chuyện từ database
    $stmt = $conn->prepare("DELETE FROM chat_history WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Thêm thông báo thành công
        $_SESSION['success_message'] = "Đã xóa cuộc trò chuyện thành công.";
    } else {
        // Thêm thông báo lỗi
        $_SESSION['error_message'] = "Không thể xóa cuộc trò chuyện. Lỗi: " . $stmt->error;
    }
    
    $stmt->close();
}



require_once '../includes/admin_footer.php';
?>