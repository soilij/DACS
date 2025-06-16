<?php
// Khởi động session
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Include các file cần thiết
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Payment.php';
require_once '../classes/Book.php';
require_once '../classes/User.php';
require_once '../classes/Notification.php';

// Khởi tạo đối tượng
$payment = new Payment();
$book = new Book();
$user = new User();
$notification = new Notification();
$db = new Database();

// Kiểm tra tham số
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    $_SESSION['error'] = 'Thiếu thông tin cập nhật!';
    header('Location: seller_orders.php');
    exit();
}

$transaction_code = $_GET['id'];
$action = $_GET['action'];

// Lấy thông tin đơn hàng
$sql = '
    SELECT p.*, 
           b.title as book_title, b.user_id as seller_id,
           u.username as buyer_username, u.email as buyer_email
    FROM payments p
    JOIN books b ON p.book_id = b.id
    JOIN users u ON p.user_id = u.id
    WHERE p.transaction_code = ?
';

$db->query($sql);
$db->bind(1, $transaction_code);
$order = $db->single();

// Kiểm tra đơn hàng tồn tại
if (!$order) {
    $_SESSION['error'] = 'Không tìm thấy thông tin đơn hàng!';
    header('Location: seller_orders.php');
    exit();
}

// Kiểm tra quyền truy cập (chỉ người bán mới được cập nhật)
if ($_SESSION['user_id'] != $order['seller_id']) {
    $_SESSION['error'] = 'Bạn không có quyền cập nhật đơn hàng này!';
    header('Location: seller_orders.php');
    exit();
}

try {
    // Bắt đầu transaction
    $db->beginTransaction();

    switch ($action) {
        case 'confirm':
            // Xác nhận đơn hàng
            if ($order['status'] != 'pending') {
                throw new Exception('Không thể xác nhận đơn hàng ở trạng thái hiện tại!');
            }

             // Cập nhật trạng thái đơn hàng
            $db->query('UPDATE payments SET status = ?, updated_at = NOW() WHERE transaction_code = ?');
            $db->bind(1, 'processing');
            $db->bind(2, $transaction_code);
            $db->execute();

            // Cập nhật trạng thái sách thành đã bán
            $db->query('UPDATE books SET is_sold = 1 WHERE id = ?');
            $db->bind(1, $order['book_id']);
            $db->execute();

            // Gửi thông báo cho người mua
            $notification_data = [
                'user_id' => $order['user_id'],
                'message' => 'Đơn hàng #' . $transaction_code . ' đã được xác nhận',
                'link' => 'pages/payment_details.php?id=' . $transaction_code
            ];
            $notification->create($notification_data);

            $_SESSION['success'] = 'Đã xác nhận đơn hàng thành công!';
            break;

            case 'cancel':
                // Hủy đơn hàng
                if ($order['status'] != 'pending') {
                    throw new Exception('Không thể hủy đơn hàng ở trạng thái hiện tại!');
                }
            
                // Cập nhật trạng thái đơn hàng
                $db->query('UPDATE payments SET status = ?, updated_at = NOW() WHERE transaction_code = ?');
                $db->bind(1, 'cancelled');
                $db->bind(2, $transaction_code);
                $db->execute();
            
                // Cập nhật trạng thái sách thành available
                $book->updateStatus($order['book_id'], 'available');
            
                // Gửi thông báo cho người mua
                $notification_data = [
                    'user_id' => $order['user_id'],
                    'message' => 'Đơn hàng #' . $transaction_code . ' đã bị hủy',
                    'link' => 'pages/payment_details.php?id=' . $transaction_code
                ];
                $notification->create($notification_data);
            
                $_SESSION['success'] = 'Đã hủy đơn hàng thành công!';
                break;

        case 'ship':
            // Giao hàng
            if (!in_array($order['status'], ['paid', 'processing'])) {
                throw new Exception('Không thể giao hàng ở trạng thái hiện tại!');
            }

            // Cập nhật trạng thái
            $db->query('UPDATE payments SET shipping_status = ?, updated_at = NOW() WHERE transaction_code = ?');
            $db->bind(1, 'shipped');
            $db->bind(2, $transaction_code);
            $db->execute();

            // Gửi thông báo cho người mua
            $notification_data = [
                'user_id' => $order['user_id'],
                'message' => 'Đơn hàng #' . $transaction_code . ' đang được giao đến bạn',
                'link' => 'pages/payment_details.php?id=' . $transaction_code
            ];
            $notification->create($notification_data);

            $_SESSION['success'] = 'Đã cập nhật trạng thái giao hàng thành công!';
            break;

        default:
            throw new Exception('Hành động không hợp lệ!');
    }

    // Commit transaction
    $db->commit();

    // Chuyển hướng về trang chi tiết
    header('Location: payment_details.php?id=' . $transaction_code);
    exit();

} catch (Exception $e) {
    // Rollback transaction
    $db->rollBack();

    $_SESSION['error'] = $e->getMessage();
    header('Location: payment_details.php?id=' . $transaction_code);
    exit();
}
?> 