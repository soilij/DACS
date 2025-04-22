<?php
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

// Thay API_KEY bằng API key của bạn
$api_key = '';

// Gọi API Claude của Anthropic
function callClaudeApi($message, $api_key) {
    $url = 'https://api.anthropic.com/v1/messages';
    
    $data = [
        'model' => 'claude-3-5-haiku-20241022',
        'messages' => [
            ['role' => 'user', 'content' => $message]
        ],
        'system' => 'Bạn là BookSwap Assistant, một trợ lý thông minh trên nền tảng trao đổi và mua bán sách. Hãy trả lời ngắn gọn, thân thiện và hữu ích. 
- Nếu người dùng hỏi về cách sử dụng BookSwap (tìm kiếm, đăng sách, trao đổi, chính sách), hãy cung cấp hướng dẫn rõ ràng liên quan đến BookSwap. 
- Nếu người dùng hỏi về sách (ví dụ: gợi ý sách, thể loại sách), hãy đưa ra câu trả lời cụ thể, sáng tạo, và khuyến khích họ tìm sách trên BookSwap (ví dụ: "Bạn có thể tìm các sách này trên BookSwap bằng cách vào trang tìm kiếm!").
- Nếu câu hỏi liên quan đến y tế, khuyên họ tham khảo ý kiến bác sĩ hoặc dược sĩ.
- Nếu không chắc chắn, đưa ra gợi ý phù hợp hoặc đề xuất liên hệ hỗ trợ qua contact@bookswap.vn.',
        'max_tokens' => 500,
        'temperature' => 0.7,
    ];
    
    $headers = [
        'Content-Type: application/json',
        'x-api-key: ' . $api_key,
        'anthropic-version: 2023-06-01'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
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
    return isset($result['content'][0]['text']) ? $result['content'][0]['text'] : null;
}

// Thử gọi API Claude
try {
    $ai_response = callClaudeApi($message, $api_key);
    
    // Nếu có phản hồi từ AI
    if ($ai_response) {
        echo json_encode(['response' => $ai_response]);
        exit;
    }
    
    // Nếu không có phản hồi từ AI, sử dụng phản hồi đơn giản (fallback)
    $response = getSimpleResponse($message);
    echo json_encode(['response' => $response]);
    
} catch (Exception $e) {
    error_log('Exception: ' . $e->getMessage());
    $response = getSimpleResponse($message);
    echo json_encode(['response' => $response]);
}

// Hàm phản hồi đơn giản (fallback) khi không kết nối được API
function getSimpleResponse($message) {
    $message = strtolower($message);
    
    // Từ khóa và phản hồi - tương tự như trong JavaScript của chatbot
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