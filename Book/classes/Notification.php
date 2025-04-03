<?php
class Notification {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Tạo thông báo mới
    public function create($data) {
        $this->db->query('INSERT INTO notifications (user_id, message, link) VALUES (:user_id, :message, :link)');
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':message', $data['message']);
        $this->db->bind(':link', $data['link']);
        
        return $this->db->execute();
    }
    
    // Lấy thông báo của người dùng
    public function getAll($user_id) {
        $this->db->query('SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC');
        $this->db->bind(':user_id', $user_id);
        
        return $this->db->resultSet();
    }
    
    // Lấy thông báo gần đây
    public function getRecent($user_id, $limit = 5) {
        $this->db->query('SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit');
        $this->db->bind(':user_id', $user_id);
        $this->db->bind(':limit', $limit);
        
        return $this->db->resultSet();
    }
    
    // Đánh dấu thông báo đã đọc
    public function markAsRead($id, $user_id) {
        $this->db->query('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id');
        $this->db->bind(':id', $id);
        $this->db->bind(':user_id', $user_id);
        
        return $this->db->execute();
    }
    
    // Đánh dấu tất cả thông báo đã đọc
    public function markAllAsRead($user_id) {
        $this->db->query('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0');
        $this->db->bind(':user_id', $user_id);
        
        return $this->db->execute();
    }
    
    // Xóa thông báo
    public function delete($id, $user_id) {
        $this->db->query('DELETE FROM notifications WHERE id = :id AND user_id = :user_id');
        $this->db->bind(':id', $id);
        $this->db->bind(':user_id', $user_id);
        
        return $this->db->execute();
    }
    
    // Đếm số thông báo chưa đọc
    public function countUnread($user_id) {
        $this->db->query('SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0');
        $this->db->bind(':user_id', $user_id);
        
        $result = $this->db->single();
        return $result['count'];
    }
}