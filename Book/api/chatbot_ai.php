<?php
// Book/api/chatbot_ai.php

// Bật hiển thị lỗi (chỉ dùng trong môi trường phát triển)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Khởi tạo session nếu chưa được khởi tạo
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tải API key từ file cấu hình
$config_file = __DIR__ . '/../config/api_keys.php';
$api_key = '';

if (file_exists($config_file)) {
    require_once($config_file);
    $api_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
} else {
    logError('Không tìm thấy file cấu hình API key');
}

// Kiểm tra method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse(405, 'Method not allowed');
}

// Đọc dữ liệu JSON từ request
$input = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu
if (!isset($input['message']) || empty($input['message'])) {
    sendErrorResponse(400, 'Message is required');
}

$message = $input['message'];

// Xử lý session ID
$session_id = isset($input['session_id']) && !empty($input['session_id']) 
    ? $input['session_id'] 
    : ($_SESSION['chatbot_session_id'] ?? md5(uniqid(mt_rand(), true)));

// Lưu session ID vào PHP session
$_SESSION['chatbot_session_id'] = $session_id;

// Lấy lịch sử trò chuyện nếu có
$conversation_history = [];
if (isset($input['history']) && is_array($input['history'])) {
    $conversation_history = $input['history'];
}

/**
 * Hệ thống RAG - Retrieval-Augmented Generation
 * Tìm kiếm thông tin liên quan đến câu hỏi từ kho kiến thức
 */
function retrieveRelevantInfo($query) {
    // Đường dẫn đến file JSON chứa dữ liệu kiến thức
    $knowledge_base_file = __DIR__ . '/../data/knowledge_base.json';
    $relevant_info = '';
    
    if (file_exists($knowledge_base_file)) {
        try {
            $knowledge_base = json_decode(file_get_contents($knowledge_base_file), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                logError('Lỗi khi đọc knowledge base: ' . json_last_error_msg());
                return '';
            }
            
            // Tìm kiếm thông tin liên quan
            $query = strtolower($query);
            $matches = [];
            
            foreach ($knowledge_base as $item) {
                // Tính điểm tương đồng đơn giản dựa trên số từ trùng khớp
                $keywords = isset($item['keywords']) ? $item['keywords'] : [];
                $score = 0;
                
                foreach ($keywords as $keyword) {
                    if (strpos($query, strtolower($keyword)) !== false) {
                        $score += 1;
                    }
                }
                
                if ($score > 0) {
                    $matches[] = [
                        'score' => $score,
                        'content' => $item['content']
                    ];
                }
            }
            
            // Sắp xếp kết quả theo điểm giảm dần
            usort($matches, function($a, $b) {
                return $b['score'] - $a['score'];
            });
            
            // Lấy tối đa 3 kết quả có điểm cao nhất
            $top_matches = array_slice($matches, 0, 3);
            
            if (!empty($top_matches)) {
                $relevant_info = "Thông tin liên quan:\n";
                foreach ($top_matches as $match) {
                    $relevant_info .= "- " . $match['content'] . "\n";
                }
            }
        } catch (Exception $e) {
            logError('Lỗi khi xử lý knowledge base: ' . $e->getMessage());
            return '';
        }
    }
    
    return $relevant_info;
}

/**
 * Tạo system prompt với thông tin về BookSwap
 */
function createSystemPrompt() {
    return "Bạn là trợ lý ảo của BookSwap - nền tảng trao đổi sách trực tuyến tại Việt Nam. " .
           "Nhiệm vụ của bạn là giúp đỡ người dùng trong việc trao đổi sách, đăng sách, tìm kiếm sách, " .
           "và hỗ trợ các vấn đề khác liên quan đến nền tảng. " .
           "Hãy trả lời ngắn gọn, chính xác và thân thiện. " .
           "Nếu bạn không biết câu trả lời, hãy thừa nhận điều đó và đề nghị người dùng liên hệ với đội hỗ trợ. " .
           "Các tính năng chính của BookSwap bao gồm:\n" .
           "1. Đăng sách: Người dùng có thể đăng sách họ muốn trao đổi\n" .
           "2. Tìm kiếm sách: Tìm kiếm sách theo tên, tác giả, thể loại\n" .
           "3. Yêu cầu trao đổi: Gửi yêu cầu trao đổi sách với người dùng khác\n" .
           "4. Quản lý sách: Xem và quản lý sách đã đăng và yêu cầu trao đổi\n" .
           "5. Đánh giá: Đánh giá người dùng sau khi trao đổi sách\n";
}

// Gọi API Gemini
function callGeminiApi($message, $api_key, $conversation_history = []) {
    if (empty($api_key)) {
        logError('API key không được cấu hình');
        return null;
    }
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-001:generateContent?key=$api_key";
    
    // Tìm thông tin liên quan từ knowledge base
    $relevant_info = retrieveRelevantInfo($message);
    
    // Tạo system prompt
    $system_prompt = createSystemPrompt();
    
    // Kết hợp system prompt với thông tin liên quan
    $enhanced_prompt = $system_prompt;
    if (!empty($relevant_info)) {
        $enhanced_prompt .= "\n\n" . $relevant_info;
    }
    
    // Chuẩn bị nội dung tin nhắn từ lịch sử
    $contents = [];
    
    // Thêm system prompt vào đầu cuộc trò chuyện
    $contents[] = [
        'role' => 'model',
        'parts' => [
            ['text' => $enhanced_prompt]
        ]
    ];
    
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
    
    try {
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
        
        if (curl_errno($ch)) {
            logError('Lỗi cURL: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }
        
        if ($status !== 200) {
            logError('Lỗi API (' . $status . '): ' . $response);
            curl_close($ch);
            return null;
        }
        
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        // Trích xuất phản hồi từ cấu trúc JSON của Gemini
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            // Ghi log tương tác để cải thiện chatbot
            logInteraction($message, $result['candidates'][0]['content']['parts'][0]['text']);
            return $result['candidates'][0]['content']['parts'][0]['text'];
        } else {
            logError('Cấu trúc phản hồi API không đúng: ' . json_encode($result));
            return null;
        }
    } catch (Exception $e) {
        logError('Exception khi gọi API: ' . $e->getMessage());
        return null;
    }
}

/**
 * Ghi log tương tác để cải thiện chatbot
 */
function logInteraction($user_message, $ai_response) {
    $log_file = __DIR__ . '/../logs/chatbot_interactions.log';
    $log_dir = dirname($log_file);
    
    // Tạo thư mục logs nếu chưa tồn tại
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_message' => $user_message,
        'ai_response' => $ai_response,
        'session_id' => $_SESSION['chatbot_session_id'] ?? ''
    ];
    
    file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND);
}

// Hàm ghi log lỗi
function logError($message) {
    error_log('[ChatbotAI Error] ' . date('Y-m-d H:i:s') . ' - ' . $message);
}

// Hàm gửi phản hồi lỗi
function sendErrorResponse($code, $message) {
    http_response_code($code);
    echo json_encode(['error' => $message, 'status' => 'error']);
    exit;
}

// Hàm phản hồi đơn giản (fallback) khi không kết nối được API
function getSimpleResponse($message) {
    $message = strtolower($message);
    
    if (strpos($message, 'xin chào') !== false || strpos($message, 'hello') !== false || strpos($message, 'hi') !== false) {
        return 'Xin chào! Tôi có thể giúp gì cho bạn?';
    } else if (strpos($message, 'trao đổi sách') !== false) {
        return 'Để trao đổi sách, bạn cần đăng nhập, tìm sách bạn muốn, và nhấn nút "Yêu cầu trao đổi". Sau đó chọn sách của bạn để trao đổi và gửi yêu cầu cho chủ sách.';
    } else if (strpos($message, 'đăng sách') !== false) {
        return 'Để đăng sách, bạn cần đăng nhập, nhấp vào nút "Đăng sách", điền đầy đủ thông tin và tải lên hình ảnh sách. Sách sẽ được duyệt trước khi hiển thị công khai.';
    } else if (strpos($message, 'nhức đầu') !== false || strpos($message, 'đau đầu') !== false) {
        return 'Đối với triệu chứng nhức đầu, tôi khuyên bạn nên tham khảo ý kiến của bác sĩ hoặc dược sĩ để được tư vấn loại thuốc phù hợp. Nếu bạn cần thêm thông tin hoặc hỗ trợ, tôi luôn sẵn sàng giúp đỡ!';
    } else if (strpos($message, 'danh mục') !== false || strpos($message, 'loại sách') !== false) {
        return 'BookSwap có nhiều danh mục sách như Tiểu thuyết, Sách thiếu nhi, Khoa học, Lịch sử, v.v. Bạn có thể vào trang chủ hoặc trang tìm kiếm để xem các danh mục.';
    } else if (strpos($message, 'sách mới') !== false) {
        return 'Bạn có thể xem các sách mới nhất trên trang chủ của BookSwap, trong mục "Sách mới".';
    } else if (strpos($message, 'sách phổ biến') !== false) {
        return 'Sách phổ biến được hiển thị trên trang chủ, trong mục "Lựa chọn phổ biến". Bạn có thể nhấp vào "Xem tất cả" để khám phá thêm.';
    } else {
        return 'Tôi chưa hiểu câu hỏi của bạn. Vui lòng thử diễn đạt theo cách khác hoặc hỏi về cách trao đổi sách, đăng sách, hoặc chính sách của BookSwap.';
    }
}

// Thử gọi API Gemini
try {
    $ai_response = callGeminiApi($message, $api_key, $conversation_history);
    
    // Nếu có phản hồi từ AI
    if ($ai_response) {
        echo json_encode([
            'response' => $ai_response,
            'session_id' => $session_id,
            'status' => 'success'
        ]);
        exit;
    }
    
    // Nếu không có phản hồi từ AI, sử dụng phản hồi đơn giản (fallback)
    $response = getSimpleResponse($message);
    echo json_encode([
        'response' => $response,
        'session_id' => $session_id,
        'status' => 'fallback'
    ]);
    
} catch (Exception $e) {
    logError('Exception: ' . $e->getMessage());
    $response = getSimpleResponse($message);
    echo json_encode([
        'response' => $response,
        'session_id' => $session_id,
        'status' => 'error',
        'message' => 'Đã xảy ra lỗi, nhưng chúng tôi vẫn cố gắng trả lời bạn'
    ]);
}
?>