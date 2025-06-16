<?php
/**
 * Cấu hình API key cho chatbot
 */

// Cấu hình API key Google
define('CHATBOT_API_KEY', 'AIzaSyAawbm9mEDrTfG1fj3ZSMDR15jxeZexSJY');

// Cấu hình endpoint API
define('CHATBOT_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent');

// Cấu hình knowledge base
define('KNOWLEDGE_BASE_PATH', __DIR__ . '/../data/knowledge_base.json');

// Cấu hình timeout
define('CHATBOT_TIMEOUT', 30);

// Cấu hình max tokens
define('CHATBOT_MAX_TOKENS', 1000);

// Cấu hình temperature
define('CHATBOT_TEMPERATURE', 0.7);

