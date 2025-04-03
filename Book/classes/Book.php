<?php
class Book {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Lấy tất cả sách
    public function getAll() {
        $this->db->query('
            SELECT b.*, c.name as category_name, u.username
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            JOIN users u ON b.user_id = u.id
            ORDER BY b.created_at DESC
        ');
        
        return $this->db->resultSet();
    }
    
    
    // Lấy sách theo ID
    public function getById($id) {
        $this->db->query('
            SELECT b.*, c.name as category_name, u.username, u.profile_image, u.rating, u.address as user_address
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            JOIN users u ON b.user_id = u.id
            WHERE b.id = :id
        ');
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    
    // Lấy sách mới nhất
    public function getLatest($limit = 8) {
        $this->db->query('
            SELECT b.*, c.name as category_name, u.username, u.address as city
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            JOIN users u ON b.user_id = u.id
            WHERE b.status = "available"
            ORDER BY b.created_at DESC
            LIMIT :limit
        ');
        $this->db->bind(':limit', $limit);
        
        return $this->db->resultSet();
    }
    
    // Lấy sách phổ biến (dựa trên lượt xem hoặc thêm vào yêu thích)
    public function getPopular($limit = 5) {
        $this->db->query('
            SELECT b.*, c.name as category_name, u.username, u.address as city,
            (SELECT COUNT(*) FROM wishlist WHERE book_id = b.id) as wishlist_count
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            JOIN users u ON b.user_id = u.id
            WHERE b.status = "available"
            ORDER BY wishlist_count DESC, b.created_at DESC
            LIMIT :limit
        ');
        $this->db->bind(':limit', $limit);
        
        return $this->db->resultSet();
    }
    
    // Tìm kiếm sách
    public function search($search_query, $category_id = null, $condition = null, $exchange_type = null, $price_min = null, $price_max = null, $sort = 'newest') {
        $sql = '
            SELECT b.*, c.name as category_name, u.username, u.address as city
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            JOIN users u ON b.user_id = u.id
            WHERE b.status = "available"
        ';
    
        // Mảng để lưu các điều kiện và tham số
        $conditions = [];
        $params = [];
    
        // Xử lý từ khóa tìm kiếm
        if (!empty($search_query)) {
            $normalized_search_query = '%' . str_replace([' ', '-', 'đ'], ['', '', 'd'], mb_strtolower($search_query, 'UTF-8')) . '%';
            $conditions[] = '(
                LOWER(REPLACE(REPLACE(REPLACE(b.title, " ", ""), "-", ""), "đ", "d")) 
                LIKE LOWER(REPLACE(REPLACE(REPLACE(:search_query, " ", ""), "-", ""), "đ", "d")) OR 
                LOWER(REPLACE(REPLACE(REPLACE(b.author, " ", ""), "-", ""), "đ", "d")) 
                LIKE LOWER(REPLACE(REPLACE(REPLACE(:search_query, " ", ""), "-", ""), "đ", "d"))
            )';
            $params[':search_query'] = $normalized_search_query;
        }
    
        // Các điều kiện lọc khác
        if (!empty($category_id)) {
            $conditions[] = 'b.category_id = :category_id';
            $params[':category_id'] = $category_id;
        }
    
        if (!empty($condition)) {
            $conditions[] = 'b.condition_rating = :condition';
            $params[':condition'] = $condition;
        }
    
        if (!empty($exchange_type)) {
            $conditions[] = 'b.exchange_type = :exchange_type';
            $params[':exchange_type'] = $exchange_type;
        }
    
        if (!empty($price_min)) {
            $conditions[] = 'b.price >= :price_min';
            $params[':price_min'] = $price_min;
        }
    
        if (!empty($price_max)) {
            $conditions[] = 'b.price <= :price_max';
            $params[':price_max'] = $price_max;
        }
    
        // Thêm điều kiện vào câu truy vấn
        if (!empty($conditions)) {
            $sql .= ' AND ' . implode(' AND ', $conditions);
        }
    
        // Sắp xếp
        switch($sort) {
            case 'price_asc':
                $sql .= ' ORDER BY b.price ASC';
                break;
            case 'price_desc':
                $sql .= ' ORDER BY b.price DESC';
                break;
            case 'condition':
                $sql .= ' ORDER BY b.condition_rating DESC';
                break;
            case 'popular':
                $sql .= ' ORDER BY (SELECT COUNT(*) FROM wishlist WHERE book_id = b.id) DESC';
                break;
            default:
                $sql .= ' ORDER BY b.created_at DESC';
        }
    
        // Thực hiện truy vấn
        $this->db->query($sql);
    
        // Bind các tham số
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
    
        return $this->db->resultSet();
    }
    
    
    // Thêm sách mới
    public function add($data) {
        $this->db->query('
            INSERT INTO books (title, author, description, condition_rating, isbn, image, category_id, user_id, exchange_type, price, status) 
            VALUES (:title, :author, :description, :condition_rating, :isbn, :image, :category_id, :user_id, :exchange_type, :price, :status)
        ');
        
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':author', $data['author']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':condition_rating', $data['condition_rating']);
        $this->db->bind(':isbn', $data['isbn']);
        $this->db->bind(':image', $data['image']);
        $this->db->bind(':category_id', $data['category_id']);
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':exchange_type', $data['exchange_type']);
        $this->db->bind(':price', $data['price']);
        $this->db->bind(':status', isset($data['status']) ? $data['status'] : 'pending_approval');
        
        if($this->db->execute()) {
            return $this->db->lastInsertId();
        } else {
            return false;
        }
    }
    
    // Cập nhật thông tin sách
    public function update($data) {
        $this->db->query('
            UPDATE books 
            SET title = :title, author = :author, description = :description, 
                condition_rating = :condition_rating, isbn = :isbn, category_id = :category_id, 
                exchange_type = :exchange_type, price = :price 
            WHERE id = :id AND user_id = :user_id
        ');
        
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':author', $data['author']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':condition_rating', $data['condition_rating']);
        $this->db->bind(':isbn', $data['isbn']);
        $this->db->bind(':category_id', $data['category_id']);
        $this->db->bind(':exchange_type', $data['exchange_type']);
        $this->db->bind(':price', $data['price']);
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':user_id', $data['user_id']);
        
        // Cập nhật ảnh nếu có
        if(isset($data['image']) && !empty($data['image'])) {
            $this->db->query('UPDATE books SET image = :image WHERE id = :id AND user_id = :user_id');
            $this->db->bind(':image', $data['image']);
            $this->db->bind(':id', $data['id']);
            $this->db->bind(':user_id', $data['user_id']);
            $this->db->execute();
        }
        
        return $this->db->execute();
    }
    
    // Cập nhật trạng thái sách
    public function updateStatus($book_id, $status) {
        $this->db->query('UPDATE books SET status = :status WHERE id = :id');
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $book_id);
        
        return $this->db->execute();
    }
    
    // Xóa sách
    public function delete($id, $user_id) {
        // Kiểm tra nếu sách thuộc về người dùng
        $this->db->query('SELECT * FROM books WHERE id = :id AND user_id = :user_id');
        $this->db->bind(':id', $id);
        $this->db->bind(':user_id', $user_id);
        $book = $this->db->single();
        
        if(!$book) {
            return false;
        }
        
        // Xóa sách
        $this->db->query('DELETE FROM books WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    // Lấy sách theo danh mục
    public function getByCategory($category_id, $limit = null) {
        $sql = '
            SELECT b.*, c.name as category_name, u.username 
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            JOIN users u ON b.user_id = u.id
            WHERE b.category_id = :category_id AND b.status = "available"
            ORDER BY b.created_at DESC
        ';
        
        if($limit) {
            $sql .= ' LIMIT :limit';
        }
        
        $this->db->query($sql);
        $this->db->bind(':category_id', $category_id);
        
        if($limit) {
            $this->db->bind(':limit', $limit);
        }
        
        return $this->db->resultSet();
    }
    
    // Lấy số lượng sách theo người dùng
    public function countByUser($user_id) {
        $this->db->query('SELECT COUNT(*) as count FROM books WHERE user_id = :user_id');
        $this->db->bind(':user_id', $user_id);
        
        $result = $this->db->single();
        return $result['count'];
    }
    
    // Thêm hình ảnh cho sách
    public function addImage($book_id, $image_path, $is_primary = false) {
        $this->db->query('
            INSERT INTO book_images (book_id, image_path, is_primary) 
            VALUES (:book_id, :image_path, :is_primary)
        ');
        
        $this->db->bind(':book_id', $book_id);
        $this->db->bind(':image_path', $image_path);
        $this->db->bind(':is_primary', $is_primary ? 1 : 0);
        
        return $this->db->execute();
    }
    
    // Lấy tất cả hình ảnh của sách
    public function getImages($book_id) {
        $this->db->query('SELECT * FROM book_images WHERE book_id = :book_id ORDER BY is_primary DESC');
        $this->db->bind(':book_id', $book_id);
        
        return $this->db->resultSet();
    }
        // Lấy tất cả sách có sẵn
    public function getAllAvailable($sort = 'newest') {
        $sql = '
            SELECT b.*, c.name as category_name, u.username, u.address as city
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            JOIN users u ON b.user_id = u.id
            WHERE b.status = "available"
        ';
        
        // Sắp xếp kết quả
        if($sort == 'newest') {
            $sql .= ' ORDER BY b.created_at DESC';
        } elseif($sort == 'price_asc') {
            $sql .= ' ORDER BY b.price ASC';
        } elseif($sort == 'price_desc') {
            $sql .= ' ORDER BY b.price DESC';
        } elseif($sort == 'condition') {
            $sql .= ' ORDER BY b.condition_rating DESC';
        } elseif($sort == 'popular') {
            $sql .= ' ORDER BY (SELECT COUNT(*) FROM wishlist WHERE book_id = b.id) DESC';
        }
        
        $this->db->query($sql);
        return $this->db->resultSet();
    }

        // Lấy sách theo người dùng
    public function getByUser($user_id, $limit = null) {
        $sql = '
            SELECT b.*, c.name as category_name
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            WHERE b.user_id = :user_id
            ORDER BY b.created_at DESC
        ';
        
        if($limit) {
            $sql .= ' LIMIT :limit';
        }
        
        $this->db->query($sql);
        $this->db->bind(':user_id', $user_id);
        
        if($limit) {
            $this->db->bind(':limit', $limit);
        }
        
        return $this->db->resultSet();
    }

        // Lấy sách đề xuất dựa trên một cuốn sách hoặc danh mục
    public function getRecommended($book_id, $category_id, $limit = 4) {
        $this->db->query('
            SELECT b.*, c.name as category_name, u.username
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            JOIN users u ON b.user_id = u.id
            WHERE b.status = "available" 
            AND b.id != :book_id
            AND (b.category_id = :category_id)
            ORDER BY RAND()
            LIMIT :limit
        ');
        
        $this->db->bind(':book_id', $book_id);
        $this->db->bind(':category_id', $category_id);
        $this->db->bind(':limit', $limit);
        
        return $this->db->resultSet();
    }
            // Trong file Book.php
    public function getAvailableBooksByUser($user_id) {
        $this->db->query('
            SELECT b.*, c.name as category_name
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            WHERE b.user_id = :user_id 
            AND b.status = "available" 
            AND b.exchange_type IN ("exchange_only", "both")
            ORDER BY b.created_at DESC
        ');
        $this->db->bind(':user_id', $user_id);
        
        return $this->db->resultSet();
    }
}