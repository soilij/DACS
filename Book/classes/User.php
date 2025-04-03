<?php
class User {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Đăng ký người dùng
    public function register($data) {
        // Kiểm tra email đã tồn tại chưa
        $this->db->query('SELECT * FROM users WHERE email = :email');
        $this->db->bind(':email', $data['email']);
        $row = $this->db->single();
        
        if($row) {
            return false; // Email đã tồn tại
        }
        
        // Kiểm tra username đã tồn tại chưa
        $this->db->query('SELECT * FROM users WHERE username = :username');
        $this->db->bind(':username', $data['username']);
        $row = $this->db->single();
        
        if($row) {
            return false; // Username đã tồn tại
        }
        
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Thêm người dùng vào database
        $this->db->query('INSERT INTO users (username, email, password, full_name) VALUES(:username, :email, :password, :full_name)');
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password', $data['password']);
        $this->db->bind(':full_name', $data['full_name']);
        
        // Execute
        if($this->db->execute()) {
            return $this->db->lastInsertId();
        } else {
            return false;
        }
    }
    
    // Đăng nhập
    public function login($username, $password) {
        // Ghi log để debug
        error_log("Attempting login for: " . $username);
        
        $this->db->query('SELECT * FROM users WHERE username = :username OR email = :email');
        $this->db->bind(':username', $username);
        $this->db->bind(':email', $username);
        
        $row = $this->db->single();
        
        if(!$row) {
            error_log("User not found: " . $username);
            return false;
        }
        
        $hashed_password = $row['password'];
        error_log("Password verification: " . password_verify($password, $hashed_password));
        
        if(password_verify($password, $hashed_password)) {
            return $row;
        } else {
            return false;
        }
    }
    
    // Tìm người dùng theo ID
    public function getUserById($id) {
        $this->db->query('SELECT * FROM users WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    // THÊM MỚI: Kiểm tra email tồn tại
    public function emailExists($email) {
        $this->db->query('SELECT * FROM users WHERE email = :email');
        $this->db->bind(':email', $email);
        
        $row = $this->db->single();
        
        return $row ? true : false;
    }
    
    // THÊM MỚI: Lưu token đặt lại mật khẩu
    public function saveResetToken($email, $token, $expiry) {
        // Kiểm tra xem bảng password_resets đã có chưa, nếu chưa thì tạo mới
        $this->db->query("
            CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $this->db->execute();
        
        // Xóa các token cũ của email này
        $this->db->query('DELETE FROM password_resets WHERE email = :email');
        $this->db->bind(':email', $email);
        $this->db->execute();
        
        // Thêm token mới
        $this->db->query('INSERT INTO password_resets (email, token, expires_at) VALUES(:email, :token, :expires_at)');
        $this->db->bind(':email', $email);
        $this->db->bind(':token', $token);
        $this->db->bind(':expires_at', $expiry);
        
        return $this->db->execute();
    }
    
    // THÊM MỚI: Xác thực token đặt lại mật khẩu
    public function verifyResetToken($token) {
        // Chỉ kiểm tra token tồn tại, bỏ qua điều kiện hết hạn
        $this->db->query('SELECT * FROM password_resets WHERE token = :token');
        $this->db->bind(':token', $token);
        
        $row = $this->db->single();
        
        if($row) {
            // Kiểm tra thời gian hết hạn và ghi log
            $expires_at = new DateTime($row['expires_at']);
            $now = new DateTime();
            
            if($expires_at < $now) {
                error_log("Token found but expired. Expires at: " . $row['expires_at'] . ", Now: " . $now->format('Y-m-d H:i:s'));
                return false;
            }
            
            return true;
        }
        
        return false;
    }
    
    // THÊM MỚI: Đặt lại mật khẩu
    public function resetPassword($token, $password) {
        // Lấy email từ token (bỏ điều kiện thời gian hết hạn)
        $this->db->query('SELECT email FROM password_resets WHERE token = :token');
        $this->db->bind(':token', $token);
        
        $row = $this->db->single();
        
        if(!$row) {
            error_log("No email found for token: " . $token);
            return false;
        }
        
        $email = $row['email'];
        error_log("Found email for token: " . $email);
        
        // Hash password mới
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Cập nhật mật khẩu
        $this->db->query('UPDATE users SET password = :password WHERE email = :email');
        $this->db->bind(':password', $hashed_password);
        $this->db->bind(':email', $email);
        
        $result = $this->db->execute();
        error_log("Password update result: " . ($result ? "Success" : "Failed"));
        
        if($result) {
            // Xóa token đã sử dụng
            $this->db->query('DELETE FROM password_resets WHERE token = :token');
            $this->db->bind(':token', $token);
            $this->db->execute();
            
            return true;
        }
        
        return false;
    }
    
    // Cập nhật thông tin người dùng
    public function updateProfile($data) {
        // Kiểm tra email đã tồn tại với người dùng khác chưa
        if(isset($data['email'])) {
            $this->db->query('SELECT * FROM users WHERE email = :email AND id != :id');
            $this->db->bind(':email', $data['email']);
            $this->db->bind(':id', $data['id']);
            $row = $this->db->single();
            
            if($row) {
                return false; // Email đã tồn tại với người dùng khác
            }
        }
        
        // Xây dựng câu truy vấn cập nhật
        $sql = 'UPDATE users SET ';
        $params = [];
        
        foreach($data as $key => $value) {
            if($key != 'id' && $key != 'password') {
                $params[] = $key . ' = :' . $key;
            }
        }
        
        $sql .= implode(', ', $params);
        $sql .= ' WHERE id = :id';
        
        $this->db->query($sql);
        
        // Bind các giá trị
        foreach($data as $key => $value) {
            if($key != 'password') {
                $this->db->bind(':' . $key, $value);
            }
        }
        
        // Execute
        return $this->db->execute();
    }
    
    // Cập nhật mật khẩu
    public function updatePassword($id, $new_password) {
        $this->db->query('UPDATE users SET password = :password WHERE id = :id');
        $this->db->bind(':password', password_hash($new_password, PASSWORD_DEFAULT));
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    // Kiểm tra mật khẩu hiện tại
    public function checkPassword($id, $password) {
        $this->db->query('SELECT password FROM users WHERE id = :id');
        $this->db->bind(':id', $id);
        $row = $this->db->single();
        
        if(!$row) {
            return false;
        }
        
        if(password_verify($password, $row['password'])) {
            return true;
        } else {
            return false;
        }
    }
    
    // Cập nhật ảnh đại diện
    public function updateProfileImage($id, $image) {
        $this->db->query('UPDATE users SET profile_image = :image WHERE id = :id');
        $this->db->bind(':image', $image);
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    // Lấy sách mà người dùng đã đăng
    public function getUserBooks($user_id) {
        $this->db->query('SELECT * FROM books WHERE user_id = :user_id ORDER BY created_at DESC');
        $this->db->bind(':user_id', $user_id);
        
        return $this->db->resultSet();
    }
    
    // Lấy danh sách sách yêu thích của người dùng
    public function getUserWishlist($user_id) {
        $this->db->query('
            SELECT b.*, w.created_at as wishlist_date 
            FROM wishlist w
            JOIN books b ON w.book_id = b.id
            WHERE w.user_id = :user_id
            ORDER BY w.created_at DESC
        ');
        $this->db->bind(':user_id', $user_id);
        
        return $this->db->resultSet();
    }
    
    // Thêm sách vào danh sách yêu thích
    public function addToWishlist($user_id, $book_id) {
        // Kiểm tra xem đã tồn tại trong wishlist chưa
        $this->db->query('SELECT * FROM wishlist WHERE user_id = :user_id AND book_id = :book_id');
        $this->db->bind(':user_id', $user_id);
        $this->db->bind(':book_id', $book_id);
        $row = $this->db->single();
        
        if($row) {
            return true; // Đã tồn tại trong wishlist
        }
        
        // Thêm vào wishlist
        $this->db->query('INSERT INTO wishlist (user_id, book_id) VALUES(:user_id, :book_id)');
        $this->db->bind(':user_id', $user_id);
        $this->db->bind(':book_id', $book_id);
        
        return $this->db->execute();
    }
    
    // Xóa sách khỏi danh sách yêu thích
    public function removeFromWishlist($user_id, $book_id) {
        $this->db->query('DELETE FROM wishlist WHERE user_id = :user_id AND book_id = :book_id');
        $this->db->bind(':user_id', $user_id);
        $this->db->bind(':book_id', $book_id);
        
        return $this->db->execute();
    }
    
    // Kiểm tra sách có trong danh sách yêu thích không
    public function isInWishlist($user_id, $book_id) {
        $this->db->query('SELECT * FROM wishlist WHERE user_id = :user_id AND book_id = :book_id');
        $this->db->bind(':user_id', $user_id);
        $this->db->bind(':book_id', $book_id);
        
        $row = $this->db->single();
        
        return $row ? true : false;
    }
    
    // Lấy các yêu cầu trao đổi của người dùng (cả gửi và nhận)
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
    
    // Lấy các đánh giá về người dùng
    public function getUserReviews($user_id) {
        $this->db->query('
            SELECT r.*, u.username, u.profile_image
            FROM reviews r
            JOIN users u ON r.reviewer_id = u.id
            WHERE r.reviewed_id = :user_id
            ORDER BY r.created_at DESC
        ');
        $this->db->bind(':user_id', $user_id);
        
        return $this->db->resultSet();
    }
    
    // Cập nhật đánh giá trung bình cho người dùng
    public function updateUserRating($user_id) {
        $this->db->query('
            SELECT AVG(rating) as avg_rating
            FROM reviews
            WHERE reviewed_id = :user_id
        ');
        $this->db->bind(':user_id', $user_id);
        
        $row = $this->db->single();
        
        if($row && isset($row['avg_rating'])) {
            $avg_rating = $row['avg_rating'] ? round($row['avg_rating'], 2) : 0;
            
            $this->db->query('UPDATE users SET rating = :rating WHERE id = :id');
            $this->db->bind(':rating', $avg_rating);
            $this->db->bind(':id', $user_id);
            
            return $this->db->execute();
        }
        
        return false;
    }
    
    // Theo dõi người dùng
    public function followUser($follower_id, $following_id) {
        // Kiểm tra xem đã theo dõi chưa
        $this->db->query('SELECT * FROM followers WHERE follower_id = :follower_id AND following_id = :following_id');
        $this->db->bind(':follower_id', $follower_id);
        $this->db->bind(':following_id', $following_id);
        $row = $this->db->single();
        
        if($row) {
            return true; // Đã theo dõi
        }
        
        // Thêm vào bảng followers
        $this->db->query('INSERT INTO followers (follower_id, following_id) VALUES(:follower_id, :following_id)');
        $this->db->bind(':follower_id', $follower_id);
        $this->db->bind(':following_id', $following_id);
        
        return $this->db->execute();
    }
    
    // Bỏ theo dõi người dùng
    public function unfollowUser($follower_id, $following_id) {
        $this->db->query('DELETE FROM followers WHERE follower_id = :follower_id AND following_id = :following_id');
        $this->db->bind(':follower_id', $follower_id);
        $this->db->bind(':following_id', $following_id);
        
        return $this->db->execute();
    }
    
    // Kiểm tra người dùng có đang theo dõi không
    public function isFollowing($follower_id, $following_id) {
        $this->db->query('SELECT * FROM followers WHERE follower_id = :follower_id AND following_id = :following_id');
        $this->db->bind(':follower_id', $follower_id);
        $this->db->bind(':following_id', $following_id);
        
        $row = $this->db->single();
        
        return $row ? true : false;
    }
    
    // Đếm số người theo dõi
    public function countFollowers($user_id) {
        $this->db->query('SELECT COUNT(*) as count FROM followers WHERE following_id = :user_id');
        $this->db->bind(':user_id', $user_id);
        
        $result = $this->db->single();
        return $result['count'];
    }
    
    // Đếm số người đang theo dõi
    public function countFollowing($user_id) {
        $this->db->query('SELECT COUNT(*) as count FROM followers WHERE follower_id = :user_id');
        $this->db->bind(':user_id', $user_id);
        
        $result = $this->db->single();
        return $result['count'];
    }
}