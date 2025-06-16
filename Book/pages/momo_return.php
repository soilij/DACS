<?php
// Khởi động session
session_start();

// Include các file cần thiết
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Book.php';
require_once '../classes/Payment.php';
require_once '../classes/User.php';
require_once '../classes/Notification.php';
require_once '../classes/MomoService.php';

// Khởi tạo đối tượng
$book = new Book();
$payment = new Payment();
$notification = new Notification();
$db = new Database();

// Ghi log dữ liệu nhận từ MoMo để debug
$logData = "MoMo return data: " . json_encode($_GET) . "\n";
file_put_contents('../logs/momo_return.log', date('Y-m-d H:i:s') . ': ' . $logData, FILE_APPEND);

// Lấy kết quả từ MoMo
$resultCode = isset($_GET['resultCode']) ? $_GET['resultCode'] : '';
$orderId = isset($_GET['orderId']) ? $_GET['orderId'] : '';
$amount = isset($_GET['amount']) ? $_GET['amount'] : 0;
$transId = isset($_GET['transId']) ? $_GET['transId'] : '';
$orderInfo = isset($_GET['orderInfo']) ? $_GET['orderInfo'] : '';

// Kiểm tra thông tin thanh toán trong session
if (!isset($_SESSION['momo_payment'])) {
    $_SESSION['error'] = 'Không tìm thấy thông tin thanh toán!';
    header('Location: ../index.php');
    exit();
}

$momo_payment = $_SESSION['momo_payment'];
$transaction_code = $momo_payment['transaction_code'];
$book_id = $momo_payment['book_id'];

// Cấu hình MoMo với URL callback để xác thực
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

// Kiểm tra kết quả thanh toán
if ($resultCode == '0') {
    // Thanh toán thành công
    // Cập nhật trạng thái thanh toán thành "đã thanh toán"
    if ($payment->updatePaymentStatus($transaction_code, 'paid')) {
        // Cập nhật trạng thái sách thành đã bán/trao đổi
        $book->updateStatus($book_id, 'exchanged');
         // Cập nhật cả trạng thái và phương thức thanh toán
        $db->query('UPDATE payments SET status = :status, payment_method = :payment_method WHERE transaction_code = :transaction_code');
        $db->bind(':status', 'paid');
        $db->bind(':payment_method', 'momo');  // Đảm bảo cập nhật phương thức thanh toán thành MoMo
        $db->bind(':transaction_code', $transaction_code);
        $db->execute();
        // Lưu thông tin thanh toán MoMo vào cơ sở dữ liệu
        try {
            $db->query('
                INSERT INTO momo_payments 
                (transaction_code, amount, momo_trans_id, momo_order_id, payment_date) 
                VALUES (:transaction_code, :amount, :transId, :orderId, NOW())
            ');
            $db->bind(':transaction_code', $transaction_code);
            $db->bind(':amount', $amount);
            $db->bind(':transId', $transId);
            $db->bind(':orderId', $orderId);
            $db->execute();
        } catch (Exception $e) {
            // Ghi log lỗi nhưng không dừng quy trình
            file_put_contents('../logs/momo_error.log', date('Y-m-d H:i:s') . ': ' . $e->getMessage() . "\n", FILE_APPEND);
        }
        
        // Lấy thông tin chi tiết của đơn hàng
        $payment_info = $payment->getPaymentByTransactionCode($transaction_code);
        
        // Gửi thông báo cho người bán
        if ($payment_info) {
            $book_info = $book->getById($book_id);
            if ($book_info) {
                $notification_data = [
                    'user_id' => $book_info['user_id'],
                    'message' => 'Đơn hàng mới cho sách "' . $book_info['title'] . '" đã được thanh toán qua MoMo.',
                    'link' => 'pages/seller_orders.php'
                ];
                $notification->create($notification_data);
            }
        }
        
        // Xóa thông tin thanh toán khỏi session
        unset($_SESSION['momo_payment']);
        
        // Chuyển hướng đến trang thành công
        $_SESSION['success'] = 'Thanh toán qua MoMo thành công!';
        header('Location: payment_success.php?transaction_code=' . $transaction_code);
        exit();
    } else {
        $_SESSION['error'] = 'Cập nhật trạng thái thanh toán thất bại!';
        
        // Ghi log lỗi
        file_put_contents('../logs/momo_error.log', date('Y-m-d H:i:s') . ': Cập nhật trạng thái thanh toán thất bại. Transaction code: ' . $transaction_code . "\n", FILE_APPEND);
        
        // Xóa thông tin thanh toán khỏi session
        unset($_SESSION['momo_payment']);
        
        header('Location: payment_confirm.php?transaction_code=' . $transaction_code);
        exit();
    }
} else {
    // Thanh toán thất bại
    // Đặt lại trạng thái sách thành available
    $book->updateStatus($book_id, 'available');
    
    // Ghi log lỗi
    file_put_contents('../logs/momo_error.log', date('Y-m-d H:i:s') . ': Thanh toán thất bại. ResultCode: ' . $resultCode . ', Transaction code: ' . $transaction_code . "\n", FILE_APPEND);
    
    // Xóa thông tin thanh toán khỏi session
    unset($_SESSION['momo_payment']);
    
    $_SESSION['error'] = 'Thanh toán qua MoMo thất bại! Mã lỗi: ' . $resultCode;
    header('Location: payment_confirm.php?transaction_code=' . $transaction_code);
    exit();
}