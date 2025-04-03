<?php
class Report {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Lấy danh sách báo cáo với bộ lọc
    public function getReports($filters = []) {
        $sql = '
            SELECT r.*, 
                   ru.username as reported_username, 
                   ru.email as reported_email,
                   re.username as reporter_username,
                   b.title as book_title,
                   a.username as admin_username,
                   (SELECT COUNT(*) FROM user_reports WHERE reported_user_id = r.reported_user_id AND status != "dismissed") as report_count
            FROM user_reports r
            JOIN users ru ON r.reported_user_id = ru.id
            JOIN users re ON r.reporter_user_id = re.id
            LEFT JOIN books b ON r.book_id = b.id
            LEFT JOIN users a ON r.admin_id = a.id
            WHERE 1=1
        ';

        $params = [];

        // Thêm các điều kiện lọc
        if (isset($filters['status']) && !empty($filters['status'])) {
            $sql .= ' AND r.status = ?';
            $params[] = $filters['status'];
        }

        if (isset($filters['report_type']) && !empty($filters['report_type'])) {
            $sql .= ' AND r.report_type = ?';
            $params[] = $filters['report_type'];
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $sql .= ' AND (ru.username LIKE ? OR ru.email LIKE ? OR re.username LIKE ? OR b.title LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Sắp xếp
        $sql .= ' ORDER BY r.created_at DESC';

        $this->db->query($sql);

        // Bind parameters
        foreach ($params as $key => $param) {
            $this->db->bind($key + 1, $param);
        }

        return $this->db->resultSet();
    }

    // Lấy chi tiết báo cáo
    public function getReportById($id) {
        $this->db->query('
            SELECT r.*, 
                   ru.username as reported_username, 
                   ru.email as reported_email,
                   ru.full_name as reported_fullname,
                   ru.warning_level,
                   ru.can_sell,
                   ru.can_buy,
                   ru.suspended_until,
                   re.username as reporter_username,
                   b.title as book_title,
                   b.author as book_author,
                   e.id as exchange_id,
                   a.username as admin_username
            FROM user_reports r
            JOIN users ru ON r.reported_user_id = ru.id
            JOIN users re ON r.reporter_user_id = re.id
            LEFT JOIN books b ON r.book_id = b.id
            LEFT JOIN exchange_requests e ON r.exchange_id = e.id
            LEFT JOIN users a ON r.admin_id = a.id
            WHERE r.id = ?
        ');
        $this->db->bind(1, $id);
        return $this->db->single();
    }

    // Lấy danh sách báo cáo cho một người dùng
    public function getReportsByUserId($userId) {
        $this->db->query('
            SELECT r.*, 
                   re.username as reporter_username,
                   b.title as book_title,
                   a.username as admin_username
            FROM user_reports r
            JOIN users re ON r.reporter_user_id = re.id
            LEFT JOIN books b ON r.book_id = b.id
            LEFT JOIN users a ON r.admin_id = a.id
            WHERE r.reported_user_id = ?
            ORDER BY r.created_at DESC
        ');
        $this->db->bind(1, $userId);
        return $this->db->resultSet();
    }

    // Lấy số lượng báo cáo chưa xử lý
    public function getPendingReportsCount() {
        $this->db->query('SELECT COUNT(*) as count FROM user_reports WHERE status = "pending"');
        return $this->db->single()['count'];
    }

    // Tạo báo cáo mới
    public function createReport($data) {
        $this->db->query('
            INSERT INTO user_reports 
            (reported_user_id, reporter_user_id, book_id, exchange_id, report_type, report_reason, status) 
            VALUES (?, ?, ?, ?, ?, ?, "pending")
        ');
        $this->db->bind(1, $data['reported_user_id']);
        $this->db->bind(2, $data['reporter_user_id']);
        $this->db->bind(3, $data['book_id'] ?? null);
        $this->db->bind(4, $data['exchange_id'] ?? null);
        $this->db->bind(5, $data['report_type']);
        $this->db->bind(6, $data['report_reason']);
        
        return $this->db->execute();
    }

    // Áp dụng hành động từ admin
    public function applyAction($reportId, $actionType, $adminId, $adminNote) {
        $this->db->beginTransaction();
        
        try {
            // 1. Lấy thông tin báo cáo
            $report = $this->getReportById($reportId);
            if (!$report) {
                $this->db->rollBack();
                return false;
            }
            
            // 2. Cập nhật báo cáo
            $this->db->query('
                UPDATE user_reports 
                SET status = "resolved", 
                    admin_id = ?, 
                    admin_note = ?,
                    action_taken = ?
                WHERE id = ?
            ');
            $this->db->bind(1, $adminId);
            $this->db->bind(2, $adminNote);
            $this->db->bind(3, $actionType);
            $this->db->bind(4, $reportId);
            
            if (!$this->db->execute()) {
                $this->db->rollBack();
                return false;
            }
            
            // 3. Cập nhật người dùng dựa trên hành động
            $userId = $report['reported_user_id'];
            
            switch ($actionType) {
                case 'warning':
                    // Tăng cấp độ cảnh báo
                    $this->db->query('UPDATE users SET warning_level = warning_level + 1 WHERE id = ?');
                    $this->db->bind(1, $userId);
                    break;
                    
                case 'restrict_buy':
                    // Cấm mua sách
                    $this->db->query('UPDATE users SET can_buy = 0, warning_level = warning_level + 2 WHERE id = ?');
                    $this->db->bind(1, $userId);
                    break;
                    
                case 'restrict_sell':
                    // Cấm bán sách
                    $this->db->query('UPDATE users SET can_sell = 0, warning_level = warning_level + 2 WHERE id = ?');
                    $this->db->bind(1, $userId);
                    break;
                    
                case 'suspend':
                    // Tạm khóa tài khoản 30 ngày
                    $suspendUntil = date('Y-m-d H:i:s', strtotime('+30 days'));
                    $this->db->query('UPDATE users SET suspended_until = ?, warning_level = warning_level + 3 WHERE id = ?');
                    $this->db->bind(1, $suspendUntil);
                    $this->db->bind(2, $userId);
                    break;
                    
                case 'ban':
                    // Khóa vĩnh viễn
                    $this->db->query('UPDATE users SET is_blocked = 1, warning_level = warning_level + 5 WHERE id = ?');
                    $this->db->bind(1, $userId);
                    break;
                    
                default:
                    // Không làm gì
                    break;
            }
            
            if (!$this->db->execute()) {
                $this->db->rollBack();
                return false;
            }
            
            // 4. Tạo thông báo cho người dùng
            $notification = new Notification();
            $notificationData = [
                'user_id' => $userId,
                'message' => $this->getActionNotificationMessage($actionType),
                'link' => 'pages/profile.php'
            ];
            
            if (!$notification->create($notificationData)) {
                $this->db->rollBack();
                return false;
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // Sinh nội dung thông báo cho hành động
    private function getActionNotificationMessage($actionType) {
        switch ($actionType) {
            case 'warning':
                return 'Bạn đã nhận được một cảnh báo từ quản trị viên. Vui lòng kiểm tra lại các hoạt động của mình.';
            case 'restrict_buy':
                return 'Tài khoản của bạn đã bị hạn chế quyền mua sách do vi phạm quy định.';
            case 'restrict_sell':
                return 'Tài khoản của bạn đã bị hạn chế quyền bán/trao đổi sách do vi phạm quy định.';
            case 'suspend':
                return 'Tài khoản của bạn đã bị tạm khóa trong 30 ngày do vi phạm quy định.';
            case 'ban':
                return 'Tài khoản của bạn đã bị khóa vĩnh viễn do vi phạm nghiêm trọng quy định của chúng tôi.';
            default:
                return 'Quản trị viên đã xử lý báo cáo liên quan đến tài khoản của bạn.';
        }
    }

    // Từ chối báo cáo
    public function dismissReport($reportId, $adminId, $adminNote) {
        $this->db->query('
            UPDATE user_reports 
            SET status = "dismissed", 
                admin_id = ?, 
                admin_note = ?,
                action_taken = "none"
            WHERE id = ?
        ');
        $this->db->bind(1, $adminId);
        $this->db->bind(2, $adminNote);
        $this->db->bind(3, $reportId);
        
        return $this->db->execute();
    }
}
?>