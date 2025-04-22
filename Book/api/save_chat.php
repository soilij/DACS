<?php
// Book/api/save_chat.php

// Enable error reporting (for development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once '../config/database.php';

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read JSON data from request
$input = json_decode(file_get_contents('php://input'), true);

// Check data
if (!isset($input['user_message']) || !isset($input['bot_response'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Get user data
$user_id = null;
$session_id = isset($input['session_id']) ? $input['session_id'] : session_id();

// If user is logged in, get user ID
session_start();
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}

// Prepare data for insertion
$user_message = $input['user_message'];
$bot_response = $input['bot_response'];

// Insert into database
$stmt = $conn->prepare("INSERT INTO chat_history (user_id, session_id, user_message, bot_response) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $user_id, $session_id, $user_message, $bot_response);

// Execute query
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Chat history saved']);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Failed to save chat history', 'details' => $stmt->error]);
}

// Close connection
$stmt->close();
$conn->close();
?>