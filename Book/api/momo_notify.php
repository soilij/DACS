<?php
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Book.php';
require_once '../classes/Payment.php';
require_once '../classes/MomoService.php';

// Nhận dữ liệu webhook từ MoMo
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Đảm bảo thư mục logs tồn tại
if (!file_exists('../logs')) {
    mkdir('../logs', 0777, true);
}

// Log dữ liệu webhook để debug
file_put_contents('../logs/momo_webhook.log', date('Y-m-d H:i:s') . ': ' . $inputJSON . PHP_EOL, FILE_APPEND);

// Khởi tạo các đối tượng cần thiết
$db = new Database();
$book = new Book();
$payment = new Payment();

// Cấu hình MoMo để xác thực
$momoConfig = [
    'partnerCode' => 'MOMO',  // Thay bằng partner code thật khi triển khai
    'accessKey' => 'F8BBA842ECF85',  // Thay bằng access key thật khi triển khai
    'secretKey' => 'K951B6PE1waDMi640xX08PD3vg6EkVlz',  // Thay bằng secret key thật khi triển khai
    'momoApiUrl' => 'https://test-payment.momo.vn/v2/gateway/api/create',
    'returnUrl' => 'http://localhost/Book/pages/momo_return.php',
    'notifyUrl' => 'http://localhost/Book/api/momo_notify.php',
    'requestType' => 'captureWallet'
];
$momoService = new MomoService($momoConfig);

try {
    // Kiểm tra xem dữ liệu webhook có đầy đủ không
    if (!isset($input['resultCode']) || !isset($input['orderId']) || !isset($input['amount']) || !isset($input['transId'])) {
        throw new Exception("Dữ liệu webhook không hợp lệ: thiếu thông tin cần thiết");
    }
    
    // Kiểm tra kết quả thanh toán
    if ($input['resultCode'] == '0') {
        // Thanh toán thành công
        $orderId = $input['orderId'];
        $amount = $input['amount'];
        $transId = $input['transId'];
        
        // Trích xuất mã giao dịch BookSwap từ orderId của MoMo
        $parts = explode('-', $orderId);
        if (count($parts) >= 3) {
            $transaction_code = $parts[1];  // Lấy phần thứ hai sau khi tách chuỗi "BS-XXXX-TIMESTAMP"
            
            // Truy vấn thông tin giao dịch từ database
            $db->query('SELECT * FROM payments WHERE transaction_code = :transaction_code');
            $db->bind(':transaction_code', $transaction_code);
            $payment_info = $db->single();
            
            if ($payment_info) {
                // Cập nhật trạng thái thanh toán và sách
                $payment->updatePaymentStatus($transaction_code, 'paid');
                $book->updateStatus($payment_info['book_id'], 'exchanged');
                
                // Lưu thông tin webhook vào cơ sở dữ liệu
                $db->query('
                    INSERT INTO momo_webhooks 
                    (order_id, transaction_code, amount, trans_id, result_code, webhook_data, received_at) 
                    VALUES (:order_id, :transaction_code, :amount, :trans_id, :result_code, :webhook_data, NOW())
                ');
                $db->bind(':order_id', $orderId);
                $db->bind(':transaction_code', $transaction_code);
                $db->bind(':amount', $amount);
                $db->bind(':trans_id', $transId);
                $db->bind(':result_code', $input['resultCode']);
                $db->bind(':webhook_data', $inputJSON);
                $db->execute();
                
                file_put_contents('../logs/momo_webhook_success.log', date('Y-m-d H:i:s') . ': Thanh toán thành công cho ' . $transaction_code . PHP_EOL, FILE_APPEND);
            } else {
                throw new Exception("Không tìm thấy thông tin giao dịch với mã: " . $transaction_code);
            }
        } else {
            throw new Exception("Định dạng orderId không hợp lệ: " . $orderId);
        }
    } else {
        // Thanh toán thất bại, ghi log
        file_put_contents('../logs/momo_webhook_fail.log', date('Y-m-d H:i:s') . ': Thanh toán thất bại. ResultCode: ' . $input['resultCode'] . PHP_EOL, FILE_APPEND);
    }
} catch (Exception $e) {
    // Ghi log lỗi
    file_put_contents('../logs/momo_webhook_error.log', date('Y-m-d H:i:s') . ': ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
}

// Phản hồi lại cho MoMo - luôn trả về thành công để MoMo không gửi lại webhook
header('Content-Type: application/json');
echo json_encode(['status' => 'success']);