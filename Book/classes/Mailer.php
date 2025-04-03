<?php
// Đường dẫn đến thư viện PHPMailer
require_once 'libraries/PHPMailer/src/Exception.php';
require_once 'libraries/PHPMailer/src/PHPMailer.php';
require_once 'libraries/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    /**
     * Gửi email đặt lại mật khẩu
     * 
     * @param string $email Địa chỉ email của người nhận
     * @param string $reset_link Đường dẫn đặt lại mật khẩu
     * @return bool Trả về true nếu gửi email thành công, ngược lại trả về false
     */
    public static function sendPasswordReset($email, $reset_link) {
        $mail = new PHPMailer(true);
        
        try {
            // Debug
            $mail->SMTPDebug = 0;
           // $mail->Debugoutput = 'html';
            
            // Cấu hình SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'nguyenminhhuy2002cm@gmail.com';
            $mail->Password = 'djinajaycendxldh'; 
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            
            // Tùy chọn SSL
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Người gửi và người nhận
            $mail->setFrom('nguyenminhhuy2002cm@gmail.com', 'BookSwap'); // Thay đổi người gửi thành email thật
            $mail->addAddress($email);
            
            // Phần còn lại giữ nguyên
            
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
            error_log("Không thể gửi email. Lỗi Mailer: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Gửi email thông báo
     * 
     * @param string $email Địa chỉ email của người nhận
     * @param string $subject Tiêu đề email
     * @param string $message Nội dung email
     * @return bool Trả về true nếu gửi email thành công, ngược lại trả về false
     */
    public static function sendNotification($email, $subject, $message) {
        $mail = new PHPMailer(true);
        
        try {
            // Cấu hình SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Thay bằng SMTP server của bạn
            $mail->SMTPAuth = true;
            $mail->Username = 'nguyenminhhuy2002cm@gmail.com'; // Email SMTP của bạn
            $mail->Password = 'djinajaycendxldh'; // Mật khẩu ứng dụng (nếu dùng Gmail)
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8'; // Hỗ trợ tiếng Việt
            
            // Người gửi và người nhận
            $mail->setFrom('nguyenminhhuy2002cm@gmail.com', 'BookSwap');
            $mail->addAddress($email);
            
            // Nội dung
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags(str_replace("<br>", "\n", $message));
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            // Ghi log lỗi
            error_log("Không thể gửi email. Lỗi Mailer: {$mail->ErrorInfo}");
            return false;
        }
    }
}