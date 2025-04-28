<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    header("Location: ../index.php");
    exit;
}

// Check if the request is POST and ID is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // Prepare the delete query
    $query = "DELETE FROM chat_history WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    
    // Execute the query
    if ($stmt->execute()) {
        // Set a success message in session
        $_SESSION['success_message'] = "Xóa cuộc trò chuyện thành công!";
        
        // Redirect to chat_history.php
        header("Location: chat_history.php");
        exit;
    } else {
        // Set an error message in session
        $_SESSION['error_message'] = "Có lỗi xảy ra khi xóa cuộc trò chuyện.";
        
        // Redirect to chat_history.php
        header("Location: chat_history.php");
        exit;
    }
} else {
    // If no ID is provided, redirect back with an error
    $_SESSION['error_message'] = "Không tìm thấy ID cuộc trò chuyện để xóa.";
    header("Location: chat_history.php");
    exit;
}
?>