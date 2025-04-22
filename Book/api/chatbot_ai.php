<?php
// Book/api/chatbot_ai.php

// Bật hiển thị lỗi (chỉ dùng trong môi trường phát triển)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kiểm tra method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Đọc dữ liệu JSON từ request
$input = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu
if (!isset($input['message']) || empty($input['message'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Message is required']);
    exit;
}

$message = $input['message'];

// Tạo session ID nếu chưa có
$session_id = isset($input['session_id']) ? $input['session_id'] : session_id();

// Lấy lịch sử trò chuyện nếu có
$conversation_history = [];
if (isset($input['history']) && is_array($input['history'])) {
    $conversation_history = $input['history'];
}

// Thay API key của Gemini vào đây
$api_key = 'AIzaSyAawbm9mEDrTfG1fj3ZSMDR15jxeZexSJY'; 

// Gọi API Gemini
function callGeminiApi($message, $api_key, $conversation_history = []) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-001:generateContent?key=$api_key";
    
    // Chuẩn bị nội dung tin nhắn từ lịch sử
    $contents = [];
    
    // Thêm lịch sử trò chuyện vào contents
    foreach ($conversation_history as $item) {
        if (isset($item['role']) && isset($item['content'])) {
            // Chuyển đổi từ format Claude sang format Gemini
            $role = ($item['role'] === 'assistant') ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => $item['content']]
                ]
            ];
        }
    }
    
    // Thêm tin nhắn hiện tại
    $contents[] = [
        'role' => 'user',
        'parts' => [
            ['text' => $message]
        ]
    ];
    
    // Chuẩn bị dữ liệu cho request
    $data = [
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 800,
        ],
        'safetySettings' => [
            [
                'category' => 'HARM_CATEGORY_HARASSMENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ]
        ]
    ];
    
    $headers = [
        'Content-Type: application/json'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch) || $status !== 200) {
        error_log('API Error: ' . curl_error($ch) . ' Status: ' . $status);
        error_log('Response: ' . $response);
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    // Trích xuất phản hồi từ cấu trúc JSON của Gemini
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return $result['candidates'][0]['content']['parts'][0]['text'];
    }
    
    return null;
}

// Thử gọi API Gemini
try {
    $ai_response = callGeminiApi($message, $api_key, $conversation_history);
    
    // Nếu có phản hồi từ AI
    if ($ai_response) {
        echo json_encode([
            'response' => $ai_response,
            'session_id' => $session_id
        ]);
        exit;
    }
    
    // Nếu không có phản hồi từ AI, sử dụng phản hồi đơn giản (fallback)
    $response = getSimpleResponse($message);
    echo json_encode([
        'response' => $response,
        'session_id' => $session_id
    ]);
    
} catch (Exception $e) {
    error_log('Exception: ' . $e->getMessage());
    $response = getSimpleResponse($message);
    echo json_encode([
        'response' => $response,
        'session_id' => $session_id
    ]);
}

// Hàm phản hồi đơn giản (fallback) khi không kết nối được API
function getSimpleResponse($message) {
    $message = strtolower($message);
    
    // Từ khóa và phản hồi - giữ nguyên code cũ
    if (strpos($message, 'xin chào') !== false || strpos($message, 'hello') !== false || strpos($message, 'hi') !== false) {
        return 'Xin chào! Tôi có thể giúp gì cho bạn?';
    } else if (strpos($message, 'trao đổi sách') !== false) {
        return 'Để trao đổi sách, bạn cần đăng nhập, tìm sách bạn muốn, và nhấn nút "Yêu cầu trao đổi". Sau đó chọn sách của bạn để trao đổi và gửi yêu cầu cho chủ sách.';
    } else if (strpos($message, 'đăng sách') !== false) {
        return 'Để đăng sách, bạn cần đăng nhập, nhấp vào nút "Đăng sách", điền đầy đủ thông tin và tải lên hình ảnh sách. Sách sẽ được duyệt trước khi hiển thị công khai.';
    } else if (strpos($message, 'nhức đầu') !== false || strpos($message, 'đau đầu') !== false) {
        return 'Đối với triệu chứng nhức đầu, tôi khuyên bạn nên tham khảo ý kiến của bác sĩ hoặc dược sĩ để được tư vấn loại thuốc phù hợp. Nếu bạn cần thêm thông tin hoặc hỗ trợ, tôi luôn sẵn sàng giúp đỡ!';
    } else {
        return 'Tôi chưa hiểu câu hỏi của bạn. Vui lòng thử diễn đạt theo cách khác hoặc hỏi về cách trao đổi sách, đăng sách, hoặc chính sách của BookSwap.';
    }
}
?>