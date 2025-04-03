<?php
class Category {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Lấy tất cả danh mục
    public function getAll() {
        $this->db->query('SELECT * FROM categories ORDER BY name ASC');
        return $this->db->resultSet();
    }
    
    // Lấy danh mục theo ID
    public function getById($id) {
        $this->db->query('SELECT * FROM categories WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    // Lấy các danh mục nổi bật
    public function getFeatured() {
        $this->db->query('SELECT * FROM categories LIMIT 6');
        return $this->db->resultSet();
    }
    
    // Thêm danh mục mới
    public function add($data) {
        $this->db->query('INSERT INTO categories (name, description, image) VALUES (:name, :description, :image)');
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':image', $data['image']);
        
        return $this->db->execute();
    }
    
    // Cập nhật danh mục
    public function update($data) {
        $this->db->query('UPDATE categories SET name = :name, description = :description WHERE id = :id');
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':id', $data['id']);
        
        // Cập nhật ảnh nếu có
        if(isset($data['image']) && !empty($data['image'])) {
            $this->db->query('UPDATE categories SET image = :image WHERE id = :id');
            $this->db->bind(':image', $data['image']);
            $this->db->bind(':id', $data['id']);
            $this->db->execute();
        }
        
        return $this->db->execute();
    }
    
    // Xóa danh mục
    public function delete($id) {
        $this->db->query('DELETE FROM categories WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    // Đếm số sách trong danh mục
    public function countBooks($category_id) {
        $this->db->query('SELECT COUNT(*) as count FROM books WHERE category_id = :category_id');
        $this->db->bind(':category_id', $category_id);
        
        $result = $this->db->single();
        return $result['count'];
    }
}