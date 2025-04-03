<footer class="bg-white text-center p-3 mt-auto shadow-sm">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> BookSwap. Tất cả quyền được bảo lưu.</p>
            </footer>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- DataTables JS (nếu sử dụng DataTables) -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Chart.js (nếu sử dụng biểu đồ) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Admin JS -->
    <script>
        // Toggle sidebar
        $(document).ready(function () {
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
            });
            
            // Initialize DataTables if exists
            if ($.fn.DataTable && $('.datatable').length) {
                $('.datatable').DataTable({
                    "language": {
                        "lengthMenu": "Hiển thị _MENU_ mục",
                        "zeroRecords": "Không tìm thấy dữ liệu",
                        "info": "Hiển thị _START_ đến _END_ của _TOTAL_ mục",
                        "infoEmpty": "Hiển thị 0 đến 0 của 0 mục",
                        "infoFiltered": "(lọc từ _MAX_ mục)",
                        "search": "Tìm kiếm:",
                        "paginate": {
                            "first": "Đầu",
                            "last": "Cuối",
                            "next": "Sau",
                            "previous": "Trước"
                        }
                    }
                });
            }
        });
        
        // Confirm Delete
        function confirmDelete(message) {
            return confirm(message || 'Bạn có chắc chắn muốn xóa?');
        }
    </script>
</body>
</html>