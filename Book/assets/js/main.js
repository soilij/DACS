$(document).ready(function() {
    // Toggle Wishlist
    $('.toggle-wishlist').on('click', function(e) {
        e.preventDefault();
        const bookId = $(this).data('book-id');
        const button = $(this);
        
        $.ajax({
            url: 'api/toggle_wishlist.php',
            type: 'POST',
            data: {
                book_id: bookId
            },
            success: function(response) {
                const data = JSON.parse(response);
                
                if (data.status === 'success') {
                    if (data.action === 'added') {
                        button.addClass('active');
                        button.html('<i class="fas fa-heart"></i>');
                        showToast('Đã thêm vào danh sách yêu thích');
                    } else {
                        button.removeClass('active');
                        button.html('<i class="far fa-heart"></i>');
                        showToast('Đã xóa khỏi danh sách yêu thích');
                    }
                } else if (data.status === 'error' && data.message === 'not_logged_in') {
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                } else {
                    showToast('Có lỗi xảy ra, vui lòng thử lại!', 'error');
                }
            },
            error: function() {
                showToast('Có lỗi xảy ra, vui lòng thử lại!', 'error');
            }
        });
    });
    
    // Initialize active wishlist buttons
    $('.toggle-wishlist.active').html('<i class="fas fa-heart"></i>');
    
    // Search suggestions
    $('#searchInput').on('keyup', function() {
        const query = $(this).val();
        
        if (query.length > 2) {
            $.ajax({
                url: 'api/search_suggestions.php',
                type: 'GET',
                data: {
                    query: query
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    
                    if (data.status === 'success') {
                        let html = '';
                        
                        if (data.suggestions.length > 0) {
                            data.suggestions.forEach(function(suggestion) {
                                html += `<a href="pages/book_details.php?id=${suggestion.id}" class="list-group-item list-group-item-action">
                                            <div class="d-flex align-items-center">
                                                <img src="uploads/books/${suggestion.image}" alt="${suggestion.title}" class="me-3" style="width: 40px; height: 60px; object-fit: cover;">
                                                <div>
                                                    <h6 class="mb-1">${suggestion.title}</h6>
                                                    <small class="text-muted">${suggestion.author}</small>
                                                </div>
                                            </div>
                                        </a>`;
                            });
                        } else {
                            html = '<span class="list-group-item">Không tìm thấy kết quả</span>';
                        }
                        
                        $('#searchSuggestions').html(html).show();
                    }
                }
            });
        } else {
            $('#searchSuggestions').hide();
        }
    });
    
    // Hide search suggestions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-container').length) {
            $('#searchSuggestions').hide();
        }
    });
    
    // Book condition stars
    $('.condition-rating').on('change', function() {
        const value = $(this).val();
        const starsContainer = $('.stars-container');
        
        starsContainer.find('i').removeClass('fas far').addClass('far');
        
        for (let i = 1; i <= value; i++) {
            starsContainer.find(`[data-rating="${i}"]`).removeClass('far').addClass('fas');
        }
    });
    
    // Initialize condition stars
    if ($('.condition-rating').length) {
        const initialValue = $('.condition-rating').val();
        
        for (let i = 1; i <= initialValue; i++) {
            $(`.stars-container [data-rating="${i}"]`).removeClass('far').addClass('fas');
        }
    }
    
    // Star rating display
    $('.star-rating i').on('click', function() {
        const value = $(this).data('rating');
        $('#ratingInput').val(value);
        
        $('.star-rating i').removeClass('fas').addClass('far');
        
        for (let i = 1; i <= value; i++) {
            $(`.star-rating [data-rating="${i}"]`).removeClass('far').addClass('fas');
        }
    });
    
    // File input preview
    $('.custom-file-input').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
        
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result).show();
            }
            
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    // Toast notifications
    function showToast(message, type = 'success') {
        const toast = $(`
            <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
                <div class="toast-header bg-${type === 'success' ? 'success' : 'danger'} text-white">
                    <strong class="me-auto">${type === 'success' ? 'Thành công' : 'Lỗi'}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `);
        
        $('.toast-container').append(toast);
        toast.toast('show');
        
        toast.on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }
    
    // Exchange request form
    $('#exchangeForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: 'api/create_exchange.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                const data = JSON.parse(response);
                
                if (data.status === 'success') {
                    $('#exchangeModal').modal('hide');
                    showToast('Yêu cầu trao đổi đã được gửi!');
                    setTimeout(function() {
                        window.location.href = 'pages/exchange_requests.php';
                    }, 2000);
                } else {
                    showToast(data.message, 'error');
                }
            },
            error: function() {
                showToast('Có lỗi xảy ra, vui lòng thử lại!', 'error');
            }
        });
    });
    
    // Messages auto-scroll
    if ($('.message-container').length) {
        $('.message-container').scrollTop($('.message-container')[0].scrollHeight);
    }
    
    // Follow user
    $('.follow-btn').on('click', function() {
        const userId = $(this).data('user-id');
        const button = $(this);
        
        $.ajax({
            url: 'api/toggle_follow.php',
            type: 'POST',
            data: {
                user_id: userId
            },
            success: function(response) {
                const data = JSON.parse(response);
                
                if (data.status === 'success') {
                    if (data.action === 'followed') {
                        button.removeClass('btn-primary').addClass('btn-outline-primary');
                        button.html('<i class="fas fa-user-check"></i> Đang theo dõi');
                    } else {
                        button.removeClass('btn-outline-primary').addClass('btn-primary');
                        button.html('<i class="fas fa-user-plus"></i> Theo dõi');
                    }
                } else if (data.status === 'error' && data.message === 'not_logged_in') {
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                } else {
                    showToast('Có lỗi xảy ra, vui lòng thử lại!', 'error');
                }
            },
            error: function() {
                showToast('Có lỗi xảy ra, vui lòng thử lại!', 'error');
            }
        });
    });
    
    // Handle exchange actions
    $('.handle-exchange').on('click', function() {
        const exchangeId = $(this).data('exchange-id');
        const action = $(this).data('action');
        const button = $(this);
        
        $.ajax({
            url: 'api/handle_exchange.php',
            type: 'POST',
            data: {
                exchange_id: exchangeId,
                action: action
            },
            success: function(response) {
                const data = JSON.parse(response);
                
                if (data.status === 'success') {
                    showToast(data.message);
                    
                    // Update UI
                    if (action === 'accept') {
                        button.closest('.card').find('.exchange-status').text('Đã chấp nhận');
                        button.closest('.card').find('.exchange-status').removeClass('badge-warning').addClass('badge-success');
                    } else if (action === 'reject') {
                        button.closest('.card').find('.exchange-status').text('Đã từ chối');
                        button.closest('.card').find('.exchange-status').removeClass('badge-warning').addClass('badge-danger');
                    } else if (action === 'complete') {
                        button.closest('.card').find('.exchange-status').text('Đã hoàn thành');
                        button.closest('.card').find('.exchange-status').removeClass('badge-success').addClass('badge-info');
                    }
                    
                    // Hide buttons
                    button.closest('.action-buttons').hide();
                    
                    // Show review button if completed
                    if (action === 'complete') {
                        button.closest('.card').find('.review-button').removeClass('d-none');
                    }
                } else {
                    showToast(data.message, 'error');
                }
            },
            error: function() {
                showToast('Có lỗi xảy ra, vui lòng thử lại!', 'error');
            }
        });
    });
});