<?php
// Include cần thiết
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Book.php';

// Kiểm tra request
if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['query'])) {
    $query = trim($_GET['query']);
    
    // Cho phép tìm kiếm với từ ngắn
    if(strlen($query) < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Từ khóa tìm kiếm quá ngắn']);
        exit();
    }
    
    $db = new Database();
    
    $db->query('
        SELECT b.id, b.title, b.author, b.image, b.price, b.exchange_type,
               c.name as category_name
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        WHERE b.status = "available" AND (
            LOWER(REPLACE(REPLACE(REPLACE(b.title, " ", ""), "-", ""), "đ", "d")) 
            LIKE LOWER(REPLACE(REPLACE(REPLACE(:query, " ", ""), "-", ""), "đ", "d")) OR 
            LOWER(REPLACE(REPLACE(REPLACE(b.author, " ", ""), "-", ""), "đ", "d")) 
            LIKE LOWER(REPLACE(REPLACE(REPLACE(:query, " ", ""), "-", ""), "đ", "d")) OR
            LOWER(REPLACE(REPLACE(REPLACE(c.name, " ", ""), "-", ""), "đ", "d")) 
            LIKE LOWER(REPLACE(REPLACE(REPLACE(:query, " ", ""), "-", ""), "đ", "d"))
        )
        ORDER BY 
            CASE 
                WHEN LOWER(b.title) LIKE LOWER(:exact_query) THEN 1
                WHEN LOWER(b.title) LIKE LOWER(:start_query) THEN 2
                WHEN LOWER(b.author) LIKE LOWER(:exact_query) THEN 3
                WHEN LOWER(b.author) LIKE LOWER(:start_query) THEN 4
                ELSE 5
            END,
            b.created_at DESC
        LIMIT 5
    ');
    
    // Loại bỏ khoảng trắng, dấu gạch ngang và chuyển đổi "đ" thành "d"
    $normalized_query = '%' . str_replace([' ', '-', 'đ'], ['', '', 'd'], mb_strtolower($query, 'UTF-8')) . '%';
    
    // Các tham số để tìm kiếm mờ và chính xác
    $db->bind(':query', $normalized_query);
    $db->bind(':exact_query', $query);
    $db->bind(':start_query', $query . '%');
    
    $suggestions = $db->resultSet();
    
    echo json_encode(['status' => 'success', 'suggestions' => $suggestions]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ']);
}