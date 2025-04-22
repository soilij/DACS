<!-- Chatbot UI -->
<div class="chatbot-container">
    <div class="chatbot-icon" id="chatbotIcon">
        <img src="assets/images/chat-icon.png" alt="Chat Icon" style="width: 100%; height: 100%;">
    </div>
    <div class="chatbot-box" id="chatbotBox">
        <div class="chatbot-header">
            <h5 class="mb-0">BookSwap Assistant</h5>
            <button class="close-btn" id="closeChatbot"><i class="fas fa-times"></i></button>
        </div>
        <div class="chatbot-body" id="chatbotBody">
            <div class="chat-message bot">
                <div class="chat-bubble">
                    Xin chào! Tôi là trợ lý ảo của BookSwap. Tôi có thể giúp gì cho bạn?
                </div>
            </div>
            <div class="chat-message bot">
                <div class="chat-bubble">
                    Bạn có thể hỏi tôi về:
                    <ul>
                        <li>Cách trao đổi sách</li>
                        <li>Tìm kiếm sách</li>
                        <li>Đăng sách mới</li>
                        <li>Chính sách trao đổi</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="chatbot-footer">
            <input type="text" id="chatbotInput" placeholder="Nhập câu hỏi của bạn..." class="form-control">
            <button id="sendButton" class="btn btn-primary ms-2">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<!-- CSS cho Chatbot -->
<style>
.chatbot-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
}

.chatbot-icon {
    width: 60px;
    height: 60px;
    background-color: #00a884; /* Màu xanh lá giống trong hình */
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
    overflow: hidden; /* Đảm bảo hình ảnh không tràn ra ngoài */
}
.chatbot-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Đảm bảo hình ảnh vừa với khung tròn */
}
.chatbot-icon i {
    color: white;
    font-size: 24px;
}

.chatbot-icon:hover {
    transform: translateY(-3px);
}

.chatbot-box {
    position: absolute;
    bottom: 70px;
    right: 0;
    width: 350px;
    height: 500px;
    background-color: #222;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    transform: scale(0);
    transform-origin: bottom right;
    opacity: 0;
    overflow: hidden;
    color: white;
}

.chatbot-box.active {
    transform: scale(1);
    opacity: 1;
}

.chatbot-header {
    padding: 15px;
    background-color: #222;
    color: white;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chatbot-header .close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
}

.chatbot-body {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.chat-message {
    margin-bottom: 15px;
    display: flex;
    flex-direction: column;
}

.chat-message.user {
    align-items: flex-end;
}

.chat-message.bot {
    align-items: flex-start;
}

.chat-bubble {
    padding: 10px 15px;
    border-radius: 18px;
    max-width: 80%;
    word-wrap: break-word;
}

.chat-message.user .chat-bubble {
    background-color: #0084ff;
    color: white;
    border-bottom-right-radius: 5px;
}

.chat-message.bot .chat-bubble {
    background-color: #333;
    color: white;
    border-bottom-left-radius: 5px;
}

.chat-bubble ul {
    padding-left: 20px;
    margin-bottom: 0;
}

.chatbot-footer {
    padding: 10px 15px;
    border-top: 1px solid #444;
    display: flex;
    align-items: center;
    background-color: #222;
}

.chatbot-footer input {
    flex: 1;
    background-color: #333;
    border: none;
    color: white;
}

.chatbot-footer input:focus {
    background-color: #333;
    color: white;
    box-shadow: none;
    border-color: #555;
}

.chatbot-footer button {
    background-color: #00a884;
    border-color: #00a884;
}

.chatbot-typing {
    display: flex;
    margin-bottom: 15px;
    align-items: flex-start;
}

.typing-indicator {
    padding: 10px 15px;
    background-color: #333;
    border-radius: 18px;
    border-bottom-left-radius: 5px;
    display: flex;
    align-items: center;
}

.typing-indicator span {
    width: 8px;
    height: 8px;
    background-color: #bbb;
    border-radius: 50%;
    display: inline-block;
    margin-right: 3px;
    animation: typing 1s infinite ease-in-out;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
    margin-right: 0;
}

@keyframes typing {
    0% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
    100% { transform: translateY(0); }
}

/* Media Query for smaller screens */
@media (max-width: 576px) {
    .chatbot-box {
        width: 300px;
        right: 0;
    }
}
</style>

<!-- JavaScript cho Chatbot -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatbotIcon = document.getElementById('chatbotIcon');
    const chatbotBox = document.getElementById('chatbotBox');
    const closeChatbot = document.getElementById('closeChatbot');
    const chatbotInput = document.getElementById('chatbotInput');
    const sendButton = document.getElementById('sendButton');
    const chatbotBody = document.getElementById('chatbotBody');
    
    // Hiển thị/ẩn chatbot
    chatbotIcon.addEventListener('click', function() {
        chatbotBox.classList.toggle('active');
    });
    
    closeChatbot.addEventListener('click', function() {
        chatbotBox.classList.remove('active');
    });
    
    // Xử lý gửi tin nhắn
    function sendMessage() {
    const message = chatbotInput.value.trim();
    if (message === '') return;

    // Hiển thị tin nhắn người dùng
    addMessage(message, 'user');
    chatbotInput.value = '';

    // Hiển thị đang nhập
    showTypingIndicator();

    // Gửi tin nhắn đến API AI
    fetch('api/chatbot_ai.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ message: message })
    })
    .then(response => response.json())
    .then(data => {
        removeTypingIndicator();
        // Chỉ sử dụng phản hồi từ API nếu có
        if (data && data.response) {
            addMessage(data.response, 'bot');
        } else {
            // Fallback tối thiểu
            addMessage('Xin lỗi, tôi chưa hiểu rõ câu hỏi. Bạn có thể hỏi cụ thể hơn hoặc liên hệ hỗ trợ qua contact@bookswap.vn.', 'bot');
        }
    })
    .catch(error => {
        removeTypingIndicator();
        console.error('Error:', error);
        // Fallback tối thiểu khi lỗi mạng
        addMessage('Có lỗi kết nối. Vui lòng thử lại hoặc liên hệ hỗ trợ qua contact@bookswap.vn.', 'bot');
    });
}
    
    // Thêm tin nhắn vào chatbox
    function addMessage(message, sender) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `chat-message ${sender}`;
        
        const bubbleDiv = document.createElement('div');
        bubbleDiv.className = 'chat-bubble';
        bubbleDiv.innerHTML = message;
        
        msgDiv.appendChild(bubbleDiv);
        chatbotBody.appendChild(msgDiv);
        
        // Cuộn xuống dưới
        chatbotBody.scrollTop = chatbotBody.scrollHeight;
    }
    
    // Hiển thị đang nhập
    function showTypingIndicator() {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'chatbot-typing';
        typingDiv.innerHTML = `
            <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
        `;
        typingDiv.id = 'typingIndicator';
        chatbotBody.appendChild(typingDiv);
        chatbotBody.scrollTop = chatbotBody.scrollHeight;
    }
    
    // Xóa hiệu ứng đang nhập
    function removeTypingIndicator() {
        const typingIndicator = document.getElementById('typingIndicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }
    
    // Xử lý câu trả lời cơ bản (khi không kết nối được AI)
    function getBotResponse(message) {
        message = message.toLowerCase();
        
        // Dựa trên từ khóa để trả lời
        if (message.includes('xin chào') || message.includes('hello') || message.includes('hi')) {
            return 'Xin chào! Tôi có thể giúp gì cho bạn?';
        } else if (message.includes('cách trao đổi') || message.includes('trao đổi sách')) {
            return `
                Để trao đổi sách, bạn cần thực hiện các bước sau:
                <ol>
                    <li>Đăng nhập vào tài khoản của bạn</li>
                    <li>Tìm sách bạn muốn trao đổi</li>
                    <li>Nhấn nút "Yêu cầu trao đổi" và chọn sách của bạn</li>
                    <li>Viết lời nhắn cho chủ sách và gửi yêu cầu</li>
                    <li>Đợi phản hồi từ chủ sách</li>
                </ol>
                Bạn có thể xem hướng dẫn chi tiết tại <a href="pages/how_it_works.php" style="color: #00a884;">đây</a>.
            `;
        } else if (message.includes('đăng sách') || message.includes('thêm sách')) {
            return `
                Để đăng sách mới:
                <ol>
                    <li>Đăng nhập vào tài khoản của bạn</li>
                    <li>Nhấn vào "Đăng sách của bạn" ở trang chủ</li>
                    <li>Điền đầy đủ thông tin sách và tải lên hình ảnh</li>
                    <li>Nhấn "Đăng sách" để hoàn tất</li>
                </ol>
                Lưu ý: Sau khi đăng, sách sẽ được kiểm duyệt trước khi hiển thị công khai.
            `;
        } else if (message.includes('tìm sách') || message.includes('tìm kiếm')) {
            return `
                Bạn có thể tìm kiếm sách bằng nhiều cách:
                <ul>
                    <li>Sử dụng thanh tìm kiếm ở đầu trang</li>
                    <li>Duyệt qua danh mục sách</li>
                    <li>Xem các sách mới nhất hoặc phổ biến trên trang chủ</li>
                </ul>
                Bạn có thể lọc kết quả theo danh mục, tình trạng sách và hình thức trao đổi.
            `;
        } else if (message.includes('chính sách') || message.includes('điều khoản')) {
            return `
                BookSwap có các chính sách sau:
                <ul>
                    <li>Người dùng chịu trách nhiệm về tình trạng sách đăng tải</li>
                    <li>Chúng tôi không can thiệp vào quá trình giao dịch giữa các bên</li>
                    <li>Sách phải đúng với mô tả và hình ảnh</li>
                    <li>Không đăng sách có nội dung vi phạm pháp luật</li>
                </ul>
                Vui lòng xem đầy đủ <a href="#" style="color: #00a884;">Điều khoản sử dụng</a> để biết thêm chi tiết.
            `;
        } else if (message.includes('nhức đầu') || message.includes('đau đầu')) {
            return `
                Đối với triệu chứng nhức đầu, tôi khuyên bạn nên tham khảo ý kiến của bác sĩ hoặc dược sĩ để được tư vấn loại thuốc phù hợp. Nếu bạn cần thêm thông tin hoặc hỗ trợ, tôi luôn sẵn sàng giúp đỡ!
            `;
        } else if (message.includes('liên hệ') || message.includes('hỗ trợ')) {
            return `
                Bạn có thể liên hệ với đội ngũ hỗ trợ của BookSwap qua:
                <ul>
                    <li>Email: contact@bookswap.vn</li>
                    <li>Điện thoại: 028 1234 5678</li>
                    <li>Trang <a href="pages/contact.php" style="color: #00a884;">Liên hệ</a></li>
                </ul>
                Chúng tôi sẽ phản hồi trong vòng 24 giờ làm việc.
            `;
        } else {
            return `
                Tôi chưa hiểu câu hỏi của bạn. Vui lòng thử diễn đạt theo cách khác hoặc chọn một trong những chủ đề sau:
                <ul>
                    <li>Cách trao đổi sách</li>
                    <li>Tìm kiếm sách</li>
                    <li>Đăng sách mới</li>
                    <li>Chính sách trao đổi</li>
                </ul>
            `;
        }
    }
    
    // Xử lý sự kiện click vào nút gửi
    sendButton.addEventListener('click', sendMessage);
    
    // Xử lý sự kiện nhấn Enter
    chatbotInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
});
</script>