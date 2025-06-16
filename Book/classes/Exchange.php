<?php
require_once 'Notification.php';
class Exchange {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Tạo yêu cầu trao đổi mới
    public function createRequest($data) {
        // Bắt đầu transaction
        $this->db->beginTransaction();
        
        try {
            // Tạo yêu cầu trao đổi
            $this->db->query('
                INSERT INTO exchange_requests 
                (requester_id, owner_id, requester_book_id, owner_book_id, message, is_money_involved, amount) 
                VALUES 
                (:requester_id, :owner_id, :requester_book_id, :owner_book_id, :message, :is_money_involved, :amount)
            ');
            
            $this->db->bind(':requester_id', $data['requester_id']);
            $this->db->bind(':owner_id', $data['owner_id']);
            $this->db->bind(':requester_book_id', $data['requester_book_id']);
            $this->db->bind(':owner_book_id', $data['owner_book_id']);
            $this->db->bind(':message', $data['message']);
            $this->db->bind(':is_money_involved', $data['is_money_involved'] ? 1 : 0);
            $this->db->bind(':amount', $data['amount'] ?? 0);
            
            $this->db->execute();
            $exchange_id = $this->db->lastInsertId();
            
            // Cập nhật trạng thái sách
            $book = new Book();
            
            // Sách của chủ sở hữu
            $book->updateStatus($data['owner_book_id'], 'pending');
            
            // Sách của người yêu cầu (nếu có)
            if($data['requester_book_id']) {
                $book->updateStatus($data['requester_book_id'], 'pending');
            }
            
            // Tạo thông báo cho chủ sở hữu sách
            $notification = new Notification();
            $notification_data = [
                'user_id' => $data['owner_id'],
                'message' => 'Bạn có một yêu cầu trao đổi sách mới',
                'link' => 'pages/exchange_requests.php?id=' . $exchange_id
            ];
            $notification->create($notification_data);
            
            // Commit transaction
            $this->db->endTransaction();
            
            return $exchange_id;
        } catch (Exception $e) {
            // Rollback transaction nếu có lỗi
            $this->db->cancelTransaction();
            throw $e;
        }
    }
    
    // Lấy thông tin yêu cầu trao đổi theo ID
    public function getById($id) {
        $this->db->query('
            SELECT er.*, 
                   owner.username as owner_username, owner.profile_image as owner_image,
                   requester.username as requester_username, requester.profile_image as requester_image,
                   ob.title as owner_book_title, ob.image as owner_book_image, ob.author as owner_book_author,
                   rb.title as requester_book_title, rb.image as requester_book_image, rb.author as requester_book_author
            FROM exchange_requests er
            JOIN users owner ON er.owner_id = owner.id
            JOIN users requester ON er.requester_id = requester.id
            JOIN books ob ON er.owner_book_id = ob.id
            LEFT JOIN books rb ON er.requester_book_id = rb.id
            WHERE er.id = :id
        ');
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    // Xử lý yêu cầu trao đổi (chấp nhận/từ chối/hoàn thành)
    public function handleRequest($id, $action, $user_id) {
        // Lấy thông tin yêu cầu
        $request = $this->getById($id);
        
        // Kiểm tra quyền xử lý yêu cầu
        if(!$request || ($request['owner_id'] != $user_id && $request['requester_id'] != $user_id)) {
            return false;
        }
        
        // Bắt đầu transaction
        $this->db->beginTransaction();
        
        try {
            $status = '';
            $notification_user_id = 0;
            $notification_message = '';
            
            switch($action) {
                case 'accept':
                    // Chỉ chủ sở hữu mới có thể chấp nhận
                    if($request['owner_id'] != $user_id) {
                        throw new Exception('Bạn không có quyền thực hiện hành động này');
                    }
                    
                    $status = 'accepted';
                    $notification_user_id = $request['requester_id'];
                    $notification_message = 'Yêu cầu trao đổi sách của bạn đã được chấp nhận';
                    break;
                    
                case 'reject':
                    // Chỉ chủ sở hữu mới có thể từ chối
                    if($request['owner_id'] != $user_id) {
                        throw new Exception('Bạn không có quyền thực hiện hành động này');
                    }
                    
                    $status = 'rejected';
                    $notification_user_id = $request['requester_id'];
                    $notification_message = 'Yêu cầu trao đổi sách của bạn đã bị từ chối';
                    
                    // Cập nhật trạng thái sách
                    $book = new Book();
                    $book->updateStatus($request['owner_book_id'], 'available');
                    if($request['requester_book_id']) {
                        $book->updateStatus($request['requester_book_id'], 'available');
                    }
                    break;
                    
                case 'complete':
                    // Cả chủ sở hữu và người yêu cầu đều có thể đánh dấu hoàn thành
                    // nhưng chỉ khi yêu cầu đã được chấp nhận
                    if($request['status'] != 'accepted') {
                        throw new Exception('Yêu cầu trao đổi này chưa được chấp nhận');
                    }
                    
                    $status = 'completed';
                    $notification_user_id = ($user_id == $request['owner_id']) ? $request['requester_id'] : $request['owner_id'];
                    $notification_message = 'Giao dịch trao đổi sách đã được đánh dấu hoàn thành';
                    
                    // Cập nhật trạng thái sách
                    $book = new Book();
                    $book->updateStatus($request['owner_book_id'], 'exchanged');
                    if($request['requester_book_id']) {
                        $book->updateStatus($request['requester_book_id'], 'exchanged');
                    }
                    break;
                    
                default:
                    throw new Exception('Hành động không hợp lệ');
            }
            
            // Cập nhật trạng thái yêu cầu
            $this->db->query('UPDATE exchange_requests SET status = :status WHERE id = :id');
            $this->db->bind(':status', $status);
            $this->db->bind(':id', $id);
            $this->db->execute();
            
            // Tạo thông báo
            $notification = new Notification();
            $notification_data = [
                'user_id' => $notification_user_id,
                'message' => $notification_message,
                'link' => 'pages/exchange_requests.php?id=' . $id
            ];
            $notification->create($notification_data);
            
            // Commit transaction
            $this->db->endTransaction();
            
            return true;
        } catch (Exception $e) {
            // Rollback transaction nếu có lỗi
            $this->db->cancelTransaction();
            throw $e;
        }
    }
    
    // Lấy các yêu cầu trao đổi nhận được
    public function getReceivedRequests($user_id) {
        $this->db->query('
            SELECT er.*, 
                   requester.username as requester_username, requester.profile_image as requester_image,
                   ob.title as owner_book_title, ob.image as owner_book_image,
                   rb.title as requester_book_title, rb.image as requester_book_image
            FROM exchange_requests er
            JOIN users requester ON er.requester_id = requester.id
            JOIN books ob ON er.owner_book_id = ob.id
            LEFT JOIN books rb ON er.requester_book_id = rb.id
            WHERE er.owner_id = :user_id
            ORDER BY er.created_at DESC
        ');
        $this->db->bind(':user_id', $user_id);
        
        return $this->db->resultSet();
    }
    
    // Lấy các yêu cầu trao đổi đã gửi
    public function getSentRequests($user_id) {
        $this->db->query('
            SELECT er.*, 
                   owner.username as owner_username, owner.profile_image as owner_image,
                   ob.title as owner_book_title, ob.image as owner_book_image,
                   rb.title as requester_book_title, rb.image as requester_book_image
            FROM exchange_requests er
            JOIN users owner ON er.owner_id = owner.id
            JOIN books ob ON er.owner_book_id = ob.id
            LEFT JOIN books rb ON er.requester_book_id = rb.id
            WHERE er.requester_id = :user_id
            ORDER BY er.created_at DESC
        ');
        $this->db->bind(':user_id', $user_id);
        
        return $this->db->resultSet();
    }
    
    // Đếm số yêu cầu trao đổi đang chờ xử lý
    public function countPending($user_id) {
        $this->db->query('
            SELECT COUNT(*) as count
            FROM exchange_requests
            WHERE owner_id = :user_id AND status = "pending"
        ');
        $this->db->bind(':user_id', $user_id);
        
        $result = $this->db->single();
        return $result['count'];
    }
    
    // Đếm số lần trao đổi thành công
    public function countCompleted($user_id) {
        $this->db->query('
            SELECT COUNT(*) as count
            FROM exchange_requests
            WHERE (owner_id = :user_id OR requester_id = :user_id) AND status = "completed"
        ');
        $this->db->bind(':user_id', $user_id);
        
        $result = $this->db->single();
        return $result['count'];
    }
    
    // Kiểm tra người dùng đã có đánh giá cho giao dịch này chưa
    public function hasReview($exchange_id, $reviewer_id) {
        $this->db->query('
            SELECT * FROM reviews
            WHERE exchange_id = :exchange_id AND reviewer_id = :reviewer_id
        ');
        $this->db->bind(':exchange_id', $exchange_id);
        $this->db->bind(':reviewer_id', $reviewer_id);
        
        $row = $this->db->single();
        return $row ? true : false;
    }
    
    // Tạo đánh giá cho giao dịch
    public function createReview($data) {
        // Kiểm tra giao dịch đã hoàn thành chưa
        $exchange = $this->getById($data['exchange_id']);
        
        if(!$exchange || $exchange['status'] != 'completed') {
            throw new Exception('Bạn chỉ có thể đánh giá giao dịch đã hoàn thành');
        }
        
        // Kiểm tra người đánh giá có quyền không
        if($exchange['owner_id'] != $data['reviewer_id'] && $exchange['requester_id'] != $data['reviewer_id']) {
            throw new Exception('Bạn không có quyền đánh giá giao dịch này');
        }
        
        // Xác định người được đánh giá
        $reviewed_id = ($data['reviewer_id'] == $exchange['owner_id']) ? $exchange['requester_id'] : $exchange['owner_id'];
        
        // Bắt đầu transaction
        $this->db->beginTransaction();
        
        try {
            // Tạo đánh giá
            $this->db->query('
                INSERT INTO reviews 
                (reviewer_id, reviewed_id, exchange_id, rating, comment) 
                VALUES 
                (:reviewer_id, :reviewed_id, :exchange_id, :rating, :comment)
            ');
            
            $this->db->bind(':reviewer_id', $data['reviewer_id']);
            $this->db->bind(':reviewed_id', $reviewed_id);
            $this->db->bind(':exchange_id', $data['exchange_id']);
            $this->db->bind(':rating', $data['rating']);
            $this->db->bind(':comment', $data['comment']);
            
            $this->db->execute();
            
            // Cập nhật đánh giá trung bình của người dùng
            $user = new User();
            $user->updateUserRating($reviewed_id);
            
            // Tạo thông báo cho người được đánh giá
            $notification = new Notification();
            $notification_data = [
                'user_id' => $reviewed_id,
                'message' => 'Bạn đã nhận được một đánh giá mới',
                'link' => 'pages/profile.php?id=' . $reviewed_id . '#reviews'
            ];
            $notification->create($notification_data);
            
            // Commit transaction
            $this->db->endTransaction();
            
            return true;
        } catch (Exception $e) {
            // Rollback transaction nếu có lỗi
            $this->db->cancelTransaction();
            throw $e;
        }
    }
    
    // Hủy yêu cầu trao đổi
    public function cancelRequest($id, $user_id) {
        // Lấy thông tin yêu cầu
        $request = $this->getById($id);
        
        // Kiểm tra quyền hủy yêu cầu (chỉ người yêu cầu mới có thể hủy)
        if(!$request || $request['requester_id'] != $user_id || $request['status'] != 'pending') {
            return false;
        }
        
        // Bắt đầu transaction
        $this->db->beginTransaction();
        
        try {
            // Cập nhật trạng thái yêu cầu
            $this->db->query('UPDATE exchange_requests SET status = "rejected" WHERE id = :id');
            $this->db->bind(':id', $id);
            $this->db->execute();
            
            // Cập nhật trạng thái sách
            $book = new Book();
            $book->updateStatus($request['owner_book_id'], 'available');
            if($request['requester_book_id']) {
                $book->updateStatus($request['requester_book_id'], 'available');
            }
            
            // Tạo thông báo cho chủ sở hữu
            $notification = new Notification();
            $notification_data = [
                'user_id' => $request['owner_id'],
                'message' => 'Yêu cầu trao đổi sách đã bị hủy',
                'link' => 'pages/exchange_requests.php?id=' . $id
            ];
            $notification->create($notification_data);
            
            // Commit transaction
            $this->db->endTransaction();
            
            return true;
        } catch (Exception $e) {
            // Rollback transaction nếu có lỗi
            $this->db->cancelTransaction();
            throw $e;
        }
    }

        // Đếm số giao dịch thành công của người dùng
    public function countCompletedByUser($user_id) {
        $this->db->query('
            SELECT COUNT(*) as count
            FROM exchange_requests
            WHERE (owner_id = :user_id OR requester_id = :user_id) AND status = "completed"
        ');
        $this->db->bind(':user_id', $user_id);
        
        $result = $this->db->single();
        return $result['count'];
    }

        // Trong file Exchange.php
    public function getUserExchanges($user_id) {
        $this->db->query('
            SELECT er.*, 
                owner.username as owner_username, 
                requester.username as requester_username,
                ob.title as owner_book_title, 
                rb.title as requester_book_title,
                ob.image as owner_book_image,
                rb.image as requester_book_image
            FROM exchange_requests er
            JOIN users owner ON er.owner_id = owner.id
            JOIN users requester ON er.requester_id = requester.id
            JOIN books ob ON er.owner_book_id = ob.id
            LEFT JOIN books rb ON er.requester_book_id = rb.id
            WHERE er.owner_id = :user_id OR er.requester_id = :user_id
            ORDER BY er.created_at DESC
        ');
        $this->db->bind(':user_id', $user_id);
        
        return $this->db->resultSet();
    }

    // lọc ra các giao dịch đã hoàn thành
    public function getCompletedExchanges($user_id) {
        $this->db->query('
            SELECT er.*, 
                   owner.username as owner_username, 
                   requester.username as requester_username,
                   ob.title as owner_book_title, 
                   rb.title as requester_book_title,
                   ob.image as owner_book_image,
                   rb.image as requester_book_image
            FROM exchange_requests er
            JOIN users owner ON er.owner_id = owner.id
            JOIN users requester ON er.requester_id = requester.id
            JOIN books ob ON er.owner_book_id = ob.id
            LEFT JOIN books rb ON er.requester_book_id = rb.id
            WHERE (er.owner_id = :user_id OR er.requester_id = :user_id) 
            AND er.status = "completed"
            ORDER BY er.created_at DESC
        ');
        $this->db->bind(':user_id', $user_id);
        
        return $this->db->resultSet();
    }
}