<?php
class Payment {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Tạo yêu cầu thanh toán mới
    public function createPaymentRequest($data) {
        $this->db->query('
            INSERT INTO payments 
            (user_id, book_id, amount, payment_method, transaction_code, status) 
            VALUES (:user_id, :book_id, :amount, :payment_method, :transaction_code, :status)
        ');
        
        // Sinh mã giao dịch duy nhất
        $transaction_code = 'BS-' . uniqid() . '-' . time();
        
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':book_id', $data['book_id']);
        $this->db->bind(':amount', $data['amount']);
        $this->db->bind(':payment_method', $data['payment_method']);
        $this->db->bind(':transaction_code', $transaction_code);
        $this->db->bind(':status', 'pending');
        
        if ($this->db->execute()) {
            return $transaction_code;
        }
        
        return false;
    }
    
    // Cập nhật trạng thái thanh toán
    public function updatePaymentStatus($transaction_code, $status) {
        $this->db->query('
            UPDATE payments 
            SET status = :status 
            WHERE transaction_code = :transaction_code
        ');
        
        $this->db->bind(':status', $status);
        $this->db->bind(':transaction_code', $transaction_code);
        
        return $this->db->execute();
    }
    
    // Lấy thông tin thanh toán
    public function getPaymentByTransactionCode($transaction_code) {
        $this->db->query('
            SELECT p.*, b.title as book_title, u.username 
            FROM payments p
            JOIN books b ON p.book_id = b.id
            JOIN users u ON p.user_id = u.id
            WHERE p.transaction_code = :transaction_code
        ');
        
        $this->db->bind(':transaction_code', $transaction_code);
        
        return $this->db->single();
    }
    
    // Lấy lịch sử thanh toán của người dùng
    public function getUserPaymentHistory($user_id) {
        $this->db->query('
            SELECT p.*, b.title as book_title 
            FROM payments p
            JOIN books b ON p.book_id = b.id
            WHERE p.user_id = :user_id
            ORDER BY p.created_at DESC
        ');
        
        $this->db->bind(':user_id', $user_id);
        
        return $this->db->resultSet();
    }
}