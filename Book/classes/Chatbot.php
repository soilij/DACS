<?php
require_once __DIR__ . '/../config/chatbot_config.php';

class Chatbot {
    private $knowledge_base;

    public function __construct() {
        $this->loadKnowledgeBase();
    }

    private function loadKnowledgeBase() {
        $json = file_get_contents(KNOWLEDGE_BASE_PATH);
        $this->knowledge_base = json_decode($json, true);
    }

    public function findAnswer($question) {
        // Chuẩn hóa câu hỏi
        $question = mb_strtolower(trim($question));
        
        // Tìm kiếm câu trả lời trong knowledge base
        $bestMatch = null;
        $maxKeywords = 0;

        foreach ($this->knowledge_base as $entry) {
            $matchCount = 0;
            foreach ($entry['keywords'] as $keyword) {
                $keyword = mb_strtolower(trim($keyword));
                if (mb_stripos($question, $keyword) !== false) {
                    $matchCount++;
                }
            }
            
            // Nếu tìm thấy nhiều keyword hơn, cập nhật best match
            if ($matchCount > $maxKeywords) {
                $maxKeywords = $matchCount;
                $bestMatch = $entry;
            }
        }

        // Nếu tìm thấy ít nhất 1 keyword match
        if ($bestMatch !== null) {
            return $bestMatch['content'];
        }

        // Nếu không tìm thấy, sử dụng Gemini API
        return $this->askGemini($question);
    }

    private function askGemini($question) {
        $url = CHATBOT_API_ENDPOINT . '?key=' . CHATBOT_API_KEY;
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $question
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => CHATBOT_TEMPERATURE,
                'maxOutputTokens' => CHATBOT_MAX_TOKENS
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => CHATBOT_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            error_log("Gemini API Error: " . $error);
            return "Xin lỗi, tôi không thể trả lời câu hỏi này lúc này. Lỗi: " . $error;
        }

        if ($httpCode !== 200) {
            error_log("Gemini API HTTP Error: " . $httpCode . "\nResponse: " . $response);
            return "Xin lỗi, có lỗi khi gọi API (HTTP " . $httpCode . ")";
        }

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Decode Error: " . json_last_error_msg());
            error_log("Response: " . $response);
            return "Xin lỗi, có lỗi xảy ra khi xử lý câu trả lời.";
        }

        if (isset($result['error'])) {
            error_log("Gemini API Error Response: " . json_encode($result['error']));
            return "Xin lỗi, có lỗi từ API: " . $result['error']['message'];
        }

        return $result['candidates'][0]['content']['parts'][0]['text'];
    }
} 