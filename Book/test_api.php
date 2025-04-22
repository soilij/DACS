<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// API key - thay thế bằng API key của bạn
$api_key = 'AIzaSyAawbm9mEDrTfG1fj3ZSMDR15jxeZexSJY';

// API endpoint
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-001:generateContent?key=$api_key";

// Dữ liệu gửi tới API
$data = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                ['text' => 'Xin chào, bạn có thể giới thiệu cho tôi những cuốn sách hay về lập trình PHP không?']
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 500,
    ]
];

echo "<h2>Kiểm tra kết nối API Gemini</h2>";
echo "<pre>";
echo "API Key: " . substr($api_key, 0, 5) . "..." . substr($api_key, -5) . "\n";
echo "URL: $url\n";
echo "Dữ liệu gửi: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
echo "\n--- BẮT ĐẦU KẾT NỐI ---\n\n";

// Headers
$headers = [
    'Content-Type: application/json'
];

// Khởi tạo CURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Thực hiện request
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$errno = curl_errno($ch);

// Hiển thị kết quả
echo "Mã trạng thái: $status\n";
if ($error) {
    echo "Lỗi cURL: $error (Mã lỗi: $errno)\n";
}

echo "\nPhản hồi từ API:\n";
if ($response) {
    $json_response = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        // Hiển thị văn bản phản hồi (nếu có)
        if (isset($json_response['candidates'][0]['content']['parts'][0]['text'])) {
            echo "\n\n--- NỘI DUNG PHẢN HỒI ---\n\n";
            echo $json_response['candidates'][0]['content']['parts'][0]['text'];
            echo "\n\n--- JSON ĐẦY ĐỦ ---\n\n";
        }
        
        echo json_encode($json_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo "Lỗi khi phân tích JSON: " . json_last_error_msg() . "\n";
        echo "Phản hồi gốc: $response";
    }
} else {
    echo "Không nhận được phản hồi";
}

// Đóng kết nối
curl_close($ch);
echo "</pre>";
?>