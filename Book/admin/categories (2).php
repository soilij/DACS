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
require_once '../classes/Category.php';

// Khởi tạo đối tượng Category
$category = new Category();

// Thiết lập biến
$msg = '';
$msg_type = '';

// Xử lý thêm danh mục
if (isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    // Xử lý upload ảnh
    $image = '';
    if ($_FILES['image']['error'] == 0) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['image']['name'];
        $file_size = $_FILES['image']['size'];
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_ext)) {
            if ($file_size <= 2097152) { // 2MB
                $new_file_name = 'category_' . time() . '.' . $file_ext;
                $upload_path = '../assets/images/categories/' . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $image = $new_file_name;
                } else {
                    $msg = 'Không thể tải ảnh lên!';
                    $msg_type = 'danger';
                }
            } else {
                $msg = 'Kích thước file quá lớn! Tối đa 2MB.';
                $msg_type = 'danger';
            }
        } else {
            $msg = 'Định dạng file không được hỗ trợ! Chỉ chấp nhận JPG, PNG và GIF.';
            $msg_type = 'danger';
        }
    }
    
    // Nếu không có lỗi, thêm danh mục
    if (empty($msg)) {
        $data = [
            'name' => $name,
            'description' => $description,
            'image' => $image
        ];
        
        if ($category->add($data)) {
            $msg = 'Đã thêm danh mục thành công!';
            $msg_type = 'success';
        } else {
            $msg = 'Có lỗi xảy ra khi thêm danh mục!';
            $msg_type = 'danger';
        }
    }
}

// Xử lý cập nhật danh mục
if (isset($_POST['update_category'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    // Kiểm tra danh mục tồn tại
    $category_info = $category->getById($id);
    if (!$category_info) {
        $msg = 'Danh mục không tồn tại!';
        $msg_type = 'danger';
    } else {
        // Xử lý upload ảnh nếu có
        $image = '';
        if ($_FILES['image']['error'] == 0) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $file_name = $_FILES['image']['name'];
            $file_size = $_FILES['image']['size'];
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (in_array($file_ext, $allowed_ext)) {
                if ($file_size <= 2097152) { // 2MB
                    $new_file_name = 'category_' . time() . '.' . $file_ext;
                    $upload_path = '../assets/images/categories/' . $new_file_name;
                    
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $image = $new_file_name;
                    } else {
                        $msg = 'Không thể tải ảnh lên!';
                        $msg_type = 'danger';
                    }
                } else {
                    $msg = 'Kích thước file quá lớn! Tối đa 2MB.';
                    $msg_type = 'danger';
                }
            } else {
                $msg = 'Định dạng file không được hỗ trợ! Chỉ chấp nhận JPG, PNG và GIF.';
                $msg_type = 'danger';
            }
        }
        
        // Nếu không có lỗi, cập nhật danh mục
        if (empty($msg)) {
            $data = [
                'id' => $id,
                'name' => $name,
                'description' => $description
            ];
            
            // Thêm ảnh vào dữ liệu nếu có
            if (!empty($image)) {
                $data['image'] = $image;
            }
            
            if ($category->update($data)) {
                $msg = 'Đã cập nhật danh mục thành công!';
                $msg_type = 'success';
            } else {
                $msg = 'Có lỗi xảy ra khi cập nhật danh mục!';
                $msg_type = 'danger';
            }
        }
    }
}

// Xử lý xóa danh mục
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Kiểm tra có sách trong danh mục này không
    $book_count = $category->countBooks($id);
    
    if ($book_count > 0) {
        $msg = 'Không thể xóa danh mục này vì có ' . $book_count . ' sách thuộc danh mục này!';
        $msg_type = 'danger';
    } else {
        if ($category->delete($id)) {
            $msg = 'Đã xóa danh mục thành công!';
            $msg_type = 'success';
        } else {
            $msg = 'Có lỗi xảy ra khi xóa danh mục!';
            $msg_type = 'danger';
        }
    }
}

// Lấy tất cả danh mục
$categories = $category->getAll();

// Include header
include('includes/header.php');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Quản lý danh mục sách</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-plus me-1"></i> Thêm danh mục
        </button>
    </div>
    
    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <!-- Danh sách danh mục -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách danh mục</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover datatable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">Hình ảnh</th>
                            <th width="20%">Tên danh mục</th>
                            <th width="40%">Mô tả</th>
                            <th width="10%">Số sách</th>
                            <th width="10%">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($categories as $cat): ?>
                        <tr>
                            <td><?php echo $cat['id']; ?></td>
                            <td>
                                <?php if($cat['image']): ?>
                                <img src="../assets/images/categories/<?php echo $cat['image']; ?>" alt="<?php echo $cat['name']; ?>" class="img-thumbnail" style="max-height: 80px;">
                                <?php else: ?>
                                <img src="../assets/images/categories/default.jpg" alt="Default" class="img-thumbnail" style="max-height: 80px;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo $cat['name']; ?></td>
                            <td><?php echo $cat['description']; ?></td>
                            <td>
                                <?php echo $category->countBooks($cat['id']); ?> sách
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editCategoryModal" 
                                            data-id="<?php echo $cat['id']; ?>"
                                            data-name="<?php echo $cat['name']; ?>"
                                            data-description="<?php echo $cat['description']; ?>"
                                            data-image="<?php echo $cat['image']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?action=delete&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm Danh Mục -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Thêm danh mục mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Tên danh mục</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Hình ảnh</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div class="form-text">Chọn một hình ảnh đại diện cho danh mục. Kích thước tối đa: 2MB.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="add_category" class="btn btn-primary">Thêm danh mục</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sửa Danh Mục -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Chỉnh sửa danh mục</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Tên danh mục</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hình ảnh hiện tại</label>
                        <div id="current_image_container" class="mb-2">
                            <img id="current_image" src="" alt="Current image" class="img-thumbnail" style="max-height: 100px;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_image" class="form-label">Cập nhật hình ảnh</label>
                        <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                        <div class="form-text">Để trống nếu bạn không muốn thay đổi hình ảnh.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="update_category" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Script để truyền dữ liệu vào modal sửa
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editCategoryModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const description = button.getAttribute('data-description');
            const image = button.getAttribute('data-image');
            
            // Điền dữ liệu vào form
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            
            // Hiển thị hình ảnh hiện tại
            const currentImage = document.getElementById('current_image');
            const currentImageContainer = document.getElementById('current_image_container');
            
            if (image) {
                currentImage.src = '../assets/images/categories/' + image;
                currentImageContainer.style.display = 'block';
            } else {
                currentImage.src = '../assets/images/categories/default.jpg';
                currentImageContainer.style.display = 'block';
            }
        });
    }
});
</script>

<?php include('includes/footer.php'); ?>