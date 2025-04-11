<?php
// Đường dẫn đến thư viện PHPMailer
require_once __DIR__ . '/../libraries/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libraries/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libraries/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    // Cấu hình email
    private static $smtp_host = 'smtp.gmail.com';
    private static $smtp_username = 'nguyenminhhuy2002cm@gmail.com';
    private static $smtp_password = 'djinajaycendxldh'; // Lưu ý: Đây nên là mật khẩu ứng dụng (app password) nếu bạn đang sử dụng Gmail
    private static $smtp_port = 587;
    private static $smtp_secure = 'tls';
    private static $smtp_from_email = 'nguyenminhhuy2002cm@gmail.com';
    private static $smtp_from_name = 'BookSwap';

    /**
     * Cấu hình PHPMailer với các thông số cần thiết
     * 
     * @return PHPMailer Đối tượng PHPMailer đã cấu hình
     */
    private static function setupMailer() {
        $mail = new PHPMailer(true);
        
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host = self::$smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = self::$smtp_username;
        $mail->Password = self::$smtp_password;
        $mail->SMTPSecure = self::$smtp_secure;
        $mail->Port = self::$smtp_port;
        $mail->CharSet = 'UTF-8';
        
        // Tùy chọn SSL
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Người gửi
        $mail->setFrom(self::$smtp_from_email, self::$smtp_from_name);
        
        return $mail;
    }

    /**
     * Gửi email đặt lại mật khẩu
     * 
     * @param string $email Địa chỉ email của người nhận
     * @param string $reset_link Đường dẫn đặt lại mật khẩu
     * @return bool Trả về true nếu gửi email thành công, ngược lại trả về false
     */
    public static function sendPasswordReset($email, $reset_link) {
        try {
            $mail = self::setupMailer();
            
            // Người nhận
            $mail->addAddress($email);
            
            // Nội dung
            $mail->isHTML(true);
            $mail->Subject = 'Đặt lại mật khẩu - BookSwap';
            
            $mailContent = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #4285f4; color: white; padding: 15px; text-align: center; }
                        .content { padding: 20px; background-color: #f9f9f9; }
                        .button { background-color: #4285f4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 15px 0; }
                        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Đặt lại mật khẩu BookSwap</h2>
                        </div>
                        <div class='content'>
                            <p>Xin chào,</p>
                            <p>Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản BookSwap của mình.</p>
                            <p>Vui lòng nhấp vào nút bên dưới để đặt lại mật khẩu của bạn:</p>
                            <p style='text-align: center;'>
                                <a href='$reset_link' class='button'>Đặt lại mật khẩu</a>
                            </p>
                            <p>Hoặc sao chép đường dẫn sau vào trình duyệt của bạn:</p>
                            <p>$reset_link</p>
                            <p><strong>Lưu ý:</strong> Liên kết này sẽ hết hạn sau 1 giờ.</p>
                            <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
                        </div>
                        <div class='footer'>
                            <p>Trân trọng,<br>Đội ngũ BookSwap</p>
                            <p>&copy; " . date('Y') . " BookSwap. Tất cả quyền được bảo lưu.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            $mail->Body = $mailContent;
            $mail->AltBody = strip_tags(str_replace("<br>", "\n", $mailContent));
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            // Ghi log lỗi
            error_log("Không thể gửi email đặt lại mật khẩu. Lỗi: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Gửi email thông báo hoặc email liên hệ
     * 
     * @param string $email Địa chỉ email của người nhận
     * @param string $subject Tiêu đề email
     * @param string $message Nội dung email (HTML)
     * @param array $attachments Danh sách các tệp đính kèm (tùy chọn)
     * @return bool Trả về true nếu gửi email thành công, ngược lại trả về false
     */
    public static function sendNotification($email, $subject, $message, $attachments = []) {
        try {
            $mail = self::setupMailer();
            
            // Người nhận
            $mail->addAddress($email);
            
            // Thêm tệp đính kèm nếu có
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (file_exists($attachment['path'])) {
                        $mail->addAttachment($attachment['path'], $attachment['name'] ?? '');
                    }
                }
            }
            
            // Nội dung
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags(str_replace("<br>", "\n", $message));
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            // Ghi log lỗi
            error_log("Không thể gửi email thông báo. Lỗi: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Gửi email liên hệ từ form liên hệ
     * 
     * @param string $name Tên người gửi
     * @param string $email Email người gửi
     * @param string $phone Số điện thoại người gửi
     * @param string $subject Chủ đề
     * @param string $message Nội dung tin nhắn
     * @return bool Trả về true nếu gửi email thành công, ngược lại trả về false
     */
    public static function sendContactForm($name, $email, $phone, $subject, $message) {
        try {
            $mail = self::setupMailer();
            
            // Thêm email admin
            $mail->addAddress(self::$smtp_from_email);
            
            // Thiết lập Reply-To để admin có thể trả lời trực tiếp cho người gửi
            $mail->addReplyTo($email, $name);
            
            // Nội dung
            $mail->isHTML(true);
            $mail->Subject = "Liên hệ từ website: $subject";
            
            $mailContent = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #4285f4; color: white; padding: 15px; text-align: center; }
                        .content { padding: 20px; background-color: #f9f9f9; }
                        .message-box { background-color: #fff; padding: 15px; border-left: 4px solid #4285f4; margin: 15px 0; }
                        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
                        .info { margin-bottom: 10px; }
                        .label { font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Tin nhắn mới từ form liên hệ</h2>
                        </div>
                        <div class='content'>
                            <div class='info'><span class='label'>Họ và tên:</span> $name</div>
                            <div class='info'><span class='label'>Email:</span> $email</div>
                            <div class='info'><span class='label'>Số điện thoại:</span> $phone</div>
                            <div class='info'><span class='label'>Chủ đề:</span> $subject</div>
                            
                            <div class='label'>Nội dung tin nhắn:</div>
                            <div class='message-box'>
                                " . nl2br(htmlspecialchars($message)) . "
                            </div>
                        </div>
                        <div class='footer'>
                            <p>Email này được gửi tự động từ form liên hệ BookSwap.</p>
                            <p>&copy; " . date('Y') . " BookSwap. Tất cả quyền được bảo lưu.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            $mail->Body = $mailContent;
            $mail->AltBody = "Tin nhắn từ: $name ($email)\nSố điện thoại: $phone\nChủ đề: $subject\n\nNội dung:\n$message";
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            // Ghi log lỗi
            error_log("Không thể gửi email liên hệ. Lỗi: {$mail->ErrorInfo}");
            return false;
        }
    }
}