<?php
// Khởi động session
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../index.php');
    exit();
}

// Include các file cần thiết
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Book.php';
require_once '../classes/User.php';
require_once '../classes/Exchange.php';
require_once '../classes/Category.php';

// Khởi tạo đối tượng cần thiết
$db = new Database();
$category = new Category();

// Lấy thời gian cho báo cáo
$time_range = isset($_GET['time_range']) ? $_GET['time_range'] : 'this_month';
$start_date = '';
$end_date = '';

switch ($time_range) {
    case 'today':
        $start_date = date('Y-m-d 00:00:00');
        $end_date = date('Y-m-d 23:59:59');
        $time_label = 'Hôm nay';
        break;
    case 'yesterday':
        $start_date = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $end_date = date('Y-m-d 23:59:59', strtotime('-1 day'));
        $time_label = 'Hôm qua';
        break;
    case 'this_week':
        $start_date = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $end_date = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        $time_label = 'Tuần này';
        break;
    case 'last_week':
        $start_date = date('Y-m-d 00:00:00', strtotime('monday last week'));
        $end_date = date('Y-m-d 23:59:59', strtotime('sunday last week'));
        $time_label = 'Tuần trước';
        break;
    case 'this_month':
        $start_date = date('Y-m-01 00:00:00');
        $end_date = date('Y-m-t 23:59:59');
        $time_label = 'Tháng này';
        break;
    case 'last_month':
        $start_date = date('Y-m-01 00:00:00', strtotime('first day of last month'));
        $end_date = date('Y-m-t 23:59:59', strtotime('last day of last month'));
        $time_label = 'Tháng trước';
        break;
    case 'this_year':
        $start_date = date('Y-01-01 00:00:00');
        $end_date = date('Y-12-31 23:59:59');
        $time_label = 'Năm nay';
        break;
    case 'last_year':
        $start_date = date('Y-01-01 00:00:00', strtotime('-1 year'));
        $end_date = date('Y-12-31 23:59:59', strtotime('-1 year'));
        $time_label = 'Năm trước';
        break;
    case 'custom':
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] . ' 00:00:00' : date('Y-m-01 00:00:00');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] . ' 23:59:59' : date('Y-m-d 23:59:59');
        $time_label = 'Tùy chỉnh';
        break;
    default:
        $start_date = date('Y-m-01 00:00:00');
        $end_date = date('Y-m-t 23:59:59');
        $time_label = 'Tháng này';
        break;
}

// Lấy thống kê người dùng
$db->query('SELECT COUNT(*) as total FROM users WHERE created_at BETWEEN :start_date AND :end_date');
$db->bind(':start_date', $start_date);
$db->bind(':end_date', $end_date);
$new_users = $db->single()['total'];

// Lấy thống kê sách
$db->query('SELECT COUNT(*) as total FROM books WHERE created_at BETWEEN :start_date AND :end_date');
$db->bind(':start_date', $start_date);
$db->bind(':end_date', $end_date);
$new_books = $db->single()['total'];

// Lấy thống kê giao dịch
$db->query('SELECT COUNT(*) as total FROM exchange_requests WHERE created_at BETWEEN :start_date AND :end_date');
$db->bind(':start_date', $start_date);
$db->bind(':end_date', $end_date);
$new_exchanges = $db->single()['total'];

// Lấy thống kê giao dịch thành công
$db->query('SELECT COUNT(*) as total FROM exchange_requests WHERE status = "completed" AND created_at BETWEEN :start_date AND :end_date');
$db->bind(':start_date', $start_date);
$db->bind(':end_date', $end_date);
$completed_exchanges = $db->single()['total'];

// Lấy thống kê danh mục sách phổ biến
$db->query('
    SELECT c.id, c.name, COUNT(b.id) as book_count
    FROM categories c
    LEFT JOIN books b ON c.id = b.category_id AND b.created_at BETWEEN :start_date AND :end_date
    GROUP BY c.id
    ORDER BY book_count DESC
    LIMIT 5
');
$db->bind(':start_date', $start_date);
$db->bind(':end_date', $end_date);
$popular_categories = $db->resultSet();

// Lấy thống kê người dùng tích cực nhất
$db->query('
    SELECT u.id, u.username, u.email, u.full_name, COUNT(b.id) as book_count
    FROM users u
    LEFT JOIN books b ON u.id = b.user_id AND b.created_at BETWEEN :start_date AND :end_date
    GROUP BY u.id
    ORDER BY book_count DESC
    LIMIT 5
');
$db->bind(':start_date', $start_date);
$db->bind(':end_date', $end_date);
$active_users = $db->resultSet();

// Lấy thống kê theo tháng trong năm hiện tại (cho biểu đồ)
$current_year = date('Y');
$monthly_stats = [];

for ($month = 1; $month <= 12; $month++) {
    $month_start = sprintf('%s-%02d-01 00:00:00', $current_year, $month);
    $month_end = date('Y-m-t 23:59:59', strtotime($month_start));
    
    // Đếm số người dùng mới
    $db->query('SELECT COUNT(*) as count FROM users WHERE created_at BETWEEN :start_date AND :end_date');
    $db->bind(':start_date', $month_start);
    $db->bind(':end_date', $month_end);
    $users_count = $db->single()['count'];
    
    // Đếm số sách mới
    $db->query('SELECT COUNT(*) as count FROM books WHERE created_at BETWEEN :start_date AND :end_date');
    $db->bind(':start_date', $month_start);
    $db->bind(':end_date', $month_end);
    $books_count = $db->single()['count'];
    
    // Đếm số giao dịch mới
    $db->query('SELECT COUNT(*) as count FROM exchange_requests WHERE created_at BETWEEN :start_date AND :end_date');
    $db->bind(':start_date', $month_start);
    $db->bind(':end_date', $month_end);
    $exchanges_count = $db->single()['count'];
    
    // Đếm số giao dịch thành công
    $db->query('SELECT COUNT(*) as count FROM exchange_requests WHERE status = "completed" AND created_at BETWEEN :start_date AND :end_date');
    $db->bind(':start_date', $month_start);
    $db->bind(':end_date', $month_end);
    $completed_count = $db->single()['count'];
    
    $monthly_stats[] = [
        'month' => date('M', strtotime($month_start)),
        'users' => $users_count,
        'books' => $books_count,
        'exchanges' => $exchanges_count,
        'completed' => $completed_count
    ];
}

// Admin header
include('includes/header.php');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Báo cáo thống kê</h1>
        <a href="?action=export_csv&time_range=<?php echo $time_range; ?>" class="btn btn-success">
            <i class="fas fa-file-csv me-1"></i> Xuất CSV
        </a>
    </div>
    
    <!-- Bộ lọc thời gian -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Khoảng thời gian: <?php echo $time_label; ?></h6>
        </div>
        <div class="card-body">
            <form action="" method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="time_range" class="form-label">Khoảng thời gian</label>
                    <select class="form-select" id="time_range" name="time_range" onchange="toggleCustomDate(this.value)">
                        <option value="today" <?php echo $time_range == 'today' ? 'selected' : ''; ?>>Hôm nay</option>
                        <option value="yesterday" <?php echo $time_range == 'yesterday' ? 'selected' : ''; ?>>Hôm qua</option>
                        <option value="this_week" <?php echo $time_range == 'this_week' ? 'selected' : ''; ?>>Tuần này</option>
                        <option value="last_week" <?php echo $time_range == 'last_week' ? 'selected' : ''; ?>>Tuần trước</option>
                        <option value="this_month" <?php echo $time_range == 'this_month' ? 'selected' : ''; ?>>Tháng này</option>
                        <option value="last_month" <?php echo $time_range == 'last_month' ? 'selected' : ''; ?>>Tháng trước</option>
                        <option value="this_year" <?php echo $time_range == 'this_year' ? 'selected' : ''; ?>>Năm nay</option>
                        <option value="last_year" <?php echo $time_range == 'last_year' ? 'selected' : ''; ?>>Năm trước</option>
                        <option value="custom" <?php echo $time_range == 'custom' ? 'selected' : ''; ?>>Tùy chỉnh</option>
                    </select>
                </div>
                <div class="col-md-3 custom-date-range" style="display: <?php echo $time_range == 'custom' ? 'block' : 'none'; ?>">
                    <label for="start_date" class="form-label">Từ ngày</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); ?>">
                </div>
                <div class="col-md-3 custom-date-range" style="display: <?php echo $time_range == 'custom' ? 'block' : 'none'; ?>">
                    <label for="end_date" class="form-label">Đến ngày</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Áp dụng</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Thẻ thống kê -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Người dùng mới</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $new_users; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Sách mới</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $new_books; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Giao dịch mới</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $new_exchanges; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Giao dịch thành công</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $completed_exchanges; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Biểu đồ hoạt động theo tháng -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Hoạt động theo tháng (<?php echo date('Y'); ?>)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh mục sách phổ biến -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Danh mục sách phổ biến</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Người dùng tích cực nhất -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Người dùng tích cực nhất</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Tên đăng nhập</th>
                                    <th>Họ tên</th>
                                    <th>Email</th>
                                    <th>Số sách đã đăng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($active_users as $user): ?>
                                <tr>
                                    <td><?php echo $user['username']; ?></td>
                                    <td><?php echo $user['full_name']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo $user['book_count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh mục sách phổ biến chi tiết -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Danh mục sách phổ biến</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Danh mục</th>
                                    <th>Số sách</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($popular_categories as $category): ?>
                                <tr>
                                    <td><?php echo $category['name']; ?></td>
                                    <td><?php echo $category['book_count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript cho biểu đồ -->
<script>
    // Hiển thị/ẩn khung chọn ngày tùy chỉnh
    function toggleCustomDate(value) {
        const customDateElements = document.querySelectorAll('.custom-date-range');
        if (value === 'custom') {
            customDateElements.forEach(el => el.style.display = 'block');
        } else {
            customDateElements.forEach(el => el.style.display = 'none');
        }
    }

    // Biểu đồ hoạt động theo tháng
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('activityChart').getContext('2d');
        const monthlyStats = <?php echo json_encode($monthly_stats); ?>;
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyStats.map(item => item.month),
                datasets: [
                    {
                        label: 'Người dùng mới',
                        data: monthlyStats.map(item => item.users),
                        borderColor: 'rgba(78, 115, 223, 1)',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                        tension: 0.3
                    },
                    {
                        label: 'Sách mới',
                        data: monthlyStats.map(item => item.books),
                        borderColor: 'rgba(40, 167, 69, 1)',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                        tension: 0.3
                    },
                    {
                        label: 'Giao dịch mới',
                        data: monthlyStats.map(item => item.exchanges),
                        borderColor: 'rgba(23, 162, 184, 1)',
                        backgroundColor: 'rgba(23, 162, 184, 0.1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(23, 162, 184, 1)',
                        tension: 0.3
                    },
                    {
                        label: 'Giao dịch thành công',
                        data: monthlyStats.map(item => item.completed),
                        borderColor: 'rgba(255, 193, 7, 1)',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(255, 193, 7, 1)',
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Biểu đồ danh mục sách
        const ctxPie = document.getElementById('categoryChart').getContext('2d');
        const categoryData = <?php echo json_encode($popular_categories); ?>;
        
        const pieChart = new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: categoryData.map(item => item.name),
                datasets: [{
                    data: categoryData.map(item => item.book_count),
                    backgroundColor: [
                        '#4e73df',
                        '#1cc88a',
                        '#36b9cc',
                        '#f6c23e',
                        '#e74a3b'
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    });
</script>

<?php include('includes/footer.php'); ?>