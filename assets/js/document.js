pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

// Xử lý form bình luận chính
(function() {
    'use strict';
    const commentForm = document.querySelector('#commentForm');
    if (commentForm) {
        commentForm.addEventListener('submit', function(event) {
            const form = this;
            const submitButton = form.querySelector('button[type="submit"]');
            let spinner = submitButton.querySelector('.spinner-border');

            if (!spinner) {
                spinner = document.createElement('span');
                spinner.classList.add('spinner-border', 'spinner-border-sm', 'me-2', 'd-none');
                spinner.setAttribute('role', 'status');
                spinner.setAttribute('aria-hidden', 'true');
                submitButton.prepend(spinner);
            }

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                event.preventDefault();
                submitButton.disabled = true;
                spinner.classList.remove('d-none');
                const formData = new FormData(form);
                fetch('/study_sharing/document/comment', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `alert alert-${data.success ? 'success' : 'danger'} mt-3`;
                    messageDiv.textContent = data.message;
                    form.before(messageDiv);
                    if (data.success) {
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    }
                })
                .catch(error => {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'alert alert-danger mt-3';
                    messageDiv.textContent = 'Lỗi server, vui lòng thử lại!';
                    form.before(messageDiv);
                })
                .finally(() => {
                    submitButton.disabled = false;
                    spinner.classList.add('d-none');
                });
            }
            form.classList.add('was-validated');
        }, false);
    }
})();

// Xử lý bình luận và trả lời
document.addEventListener('DOMContentLoaded', function() {
    const commentsContainer = document.getElementById('comments-container');
    const isLoggedIn = commentsContainer.dataset.isLoggedIn === 'true';
    const currentUserId = parseInt(commentsContainer.dataset.currentUserId) || 0;

    // Xử lý sự kiện click cho trả lời, hủy, và xóa
    commentsContainer.addEventListener('click', function(event) {
        if (event.target.classList.contains('reply-comment')) {
            event.preventDefault();
            const commentId = event.target.dataset.commentId;
            const userId = event.target.dataset.userId;
            const userName = event.target.dataset.userName;
            const commentItem = event.target.closest('.comment-item, .reply-item');
            const replyForm = commentItem.querySelector('.reply-form');

            // Ẩn tất cả form trả lời khác
            document.querySelectorAll('.reply-form').forEach(form => {
                form.classList.add('d-none');
            });

            // Kiểm tra xem bình luận là cấp 2 hay cấp 3
            const isLevelTwo = commentItem.classList.contains('reply-item') && !commentItem.classList.contains('reply-level-3');
            replyForm.querySelector('input[name="tagged_user_id"]').value = isLevelTwo ? userId : 0;
            const textarea = replyForm.querySelector('textarea[name="comment_text"]');
            textarea.placeholder = `Trả lời ${userName}...`;

            // Hiển thị form trả lời và cuộn đến đó
            replyForm.classList.remove('d-none');
            replyForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        if (event.target.classList.contains('cancel-reply')) {
            event.preventDefault();
            const replyForm = event.target.closest('.reply-form');
            replyForm.classList.add('d-none');
            replyForm.querySelector('textarea[name="comment_text"]').placeholder = 'Trả lời bình luận...';
        }

        if (event.target.classList.contains('delete-comment')) {
            event.preventDefault();
            const commentId = event.target.dataset.commentId;
            if (confirm('Bạn có chắc muốn xóa bình luận này?')) {
                fetch('/study_sharing/document/deleteComment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `comment_id=${commentId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Lỗi server, vui lòng thử lại!');
                });
            }
        }
    });

    // Xử lý gửi form trả lời
    commentsContainer.addEventListener('submit', function(event) {
        if (event.target.classList.contains('reply-form')) {
            event.preventDefault();
            const form = event.target;
            const submitButton = form.querySelector('button[type="submit"]');
            let spinner = submitButton.querySelector('.spinner-border');

            if (!spinner) {
                spinner = document.createElement('span');
                spinner.classList.add('spinner-border', 'spinner-border-sm', 'me-2', 'd-none');
                spinner.setAttribute('role', 'status');
                spinner.setAttribute('aria-hidden', 'true');
                submitButton.prepend(spinner);
            }

            if (!form.checkValidity()) {
                event.stopPropagation();
                form.classList.add('was-validated');
                return;
            }

            submitButton.disabled = true;
            spinner.classList.remove('d-none');
            const formData = new FormData(form);
            console.log('Form data:', Object.fromEntries(formData));
            fetch('/study_sharing/document/replyComment', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Reply response:', data);
                const messageDiv = document.createElement('div');
                messageDiv.className = `alert alert-${data.success ? 'success' : 'danger'} mt-2`;
                messageDiv.textContent = data.message;
                form.before(messageDiv);
                if (data.success) {
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const messageDiv = document.createElement('div');
                messageDiv.className = 'alert alert-danger mt-2';
                messageDiv.textContent = 'Lỗi server, vui lòng thử lại!';
                form.before(messageDiv);
            })
            .finally(() => {
                submitButton.disabled = false;
                spinner.classList.add('d-none');
                form.classList.remove('was-validated');
            });
        }
    });

    // Xử lý nút "Tải thêm bình luận"
    const loadMoreButton = document.getElementById('loadMoreComments');
    if (loadMoreButton) {
        loadMoreButton.addEventListener('click', function() {
            const documentId = this.dataset.documentId;
            const offset = parseInt(this.dataset.offset);
            const commentsContainer = document.getElementById('comments-container');

            const noCommentsMsg = commentsContainer.querySelector('.text-muted');
            if (noCommentsMsg) {
                noCommentsMsg.remove();
            }

            fetch('/study_sharing/document/loadMoreComments', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `document_id=${documentId}&offset=${offset}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.comments.forEach(comment => {
                        const commentDiv = document.createElement('div');
                        commentDiv.className = 'border-bottom mb-3 pb-3 comment-item';
                        commentDiv.dataset.commentId = comment.comment_id;

                        let dropdownHtml = '';
                        if (isLoggedIn) {
                            const commentTime = new Date(comment.comment_date).getTime();
                            const currentTime = new Date().getTime();
                            const withinOneHour = (currentTime - commentTime) / 1000 <= 3600;

                            dropdownHtml = `
                                <div class="dropdown ms-auto">
                                    <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item reply-comment" href="#" data-comment-id="${comment.comment_id}" data-user-id="${comment.account_id}" data-user-name="${comment.user.full_name || 'Ẩn danh'}">Trả lời</a></li>
                                        ${
                                            comment.account_id === currentUserId && withinOneHour
                                            ? `<li><a class="dropdown-item delete-comment" href="#" data-comment-id="${comment.comment_id}">Xóa</a></li>`
                                            : ''
                                        }
                                    </ul>
                                </div>
                            `;
                        }

                        function generateRepliesHtml(replies, documentId, level = 1) {
                            console.log(`Rendering replies at level ${level}:`, replies);
                            let repliesHtml = '';
                            if (replies && Object.keys(replies).length > 0) {
                                repliesHtml = `<div class="replies mt-3 ms-${level * 4}">`;
                                Object.values(replies).forEach(reply => {
                                    let replyDropdownHtml = '';
                                    if (isLoggedIn) {
                                        const replyTime = new Date(reply.comment_date).getTime();
                                        const currentTime = new Date().getTime();
                                        const replyWithinOneHour = (currentTime - replyTime) / 1000 <= 3600;

                                        replyDropdownHtml = `
                                            <div class="dropdown ms-auto">
                                                <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item reply-comment" href="#" data-comment-id="${reply.comment_id}" data-user-id="${reply.account_id}" data-user-name="${reply.user.full_name || 'Ẩn danh'}">Trả lời</a></li>
                                                    ${
                                                        reply.account_id === currentUserId && replyWithinOneHour
                                                        ? `<li><a class="dropdown-item delete-comment" href="#" data-comment-id="${reply.comment_id}">Xóa</a></li>`
                                                        : ''
                                                    }
                                                </ul>
                                            </div>
                                        `;
                                    }

                                    repliesHtml += `
                                        <div class="border-bottom mb-2 pb-2 reply-item reply-level-${level}" data-comment-id="${reply.comment_id}">
                                            <div class="d-flex align-items-center mb-1 position-relative">
                                                <img src="/study_sharing/assets/images/${reply.user.avatar || 'profile.png'}" alt="Avatar" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                                <div>
                                                    <strong>${reply.user.full_name || 'Ẩn danh'}</strong>
                                                    <small class="text-muted ms-2">${reply.comment_date}</small>
                                                </div>
                                                ${replyDropdownHtml}
                                            </div>
                                            <p class="mb-0">${reply.comment_text}</p>
                                            <form class="reply-form mt-3 d-none" data-parent-comment-id="${reply.comment_id}">
                                                <input type="hidden" name="document_id" value="${documentId}">
                                                <input type="hidden" name="parent_comment_id" value="${reply.comment_id}">
                                                <input type="hidden" name="tagged_user_id" value="${reply.account_id}">
                                                <div class="mb-3">
                                                    <textarea class="form-control" name="comment_text" rows="3" required placeholder="Trả lời ${reply.user.full_name || 'Ẩn danh'}..."></textarea>
                                                    <div class="invalid-feedback">Vui lòng nhập nội dung trả lời.</div>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                                    Gửi trả lời
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-sm cancel-reply">Hủy</button>
                                            </form>
                                            ${generateRepliesHtml(reply.replies, documentId, level + 1)}
                                        </div>
                                    `;
                                });
                                repliesHtml += '</div>';
                            }
                            return repliesHtml;
                        }

                        commentDiv.innerHTML = `
                            <div class="d-flex align-items-center mb-2 position-relative">
                                <img src="/study_sharing/assets/images/${comment.user.avatar || 'profile.png'}" alt="Avatar" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                <div>
                                    <strong>${comment.user.full_name || 'Ẩn danh'}</strong>
                                    <small class="text-muted ms-2">${comment.comment_date}</small>
                                </div>
                                ${dropdownHtml}
                            </div>
                            <p class="mb-0">${comment.comment_text}</p>
                            <form class="reply-form mt-3 d-none" data-parent-comment-id="${comment.comment_id}">
                                <input type="hidden" name="document_id" value="${documentId}">
                                <input type="hidden" name="parent_comment_id" value="${comment.comment_id}">
                                <input type="hidden" name="tagged_user_id" value="${comment.account_id}">
                                <div class="mb-3">
                                    <textarea class="form-control" name="comment_text" rows="3" required placeholder="Trả lời ${comment.user.full_name || 'Ẩn danh'}..."></textarea>
                                    <div class="invalid-feedback">Vui lòng nhập nội dung trả lời.</div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    Gửi trả lời
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm cancel-reply">Hủy</button>
                            </form>
                            ${generateRepliesHtml(comment.replies, documentId)}
                        `;
                        commentsContainer.appendChild(commentDiv);
                    });

                    this.dataset.offset = offset + data.comments.length;
                    if (!data.hasMore) {
                        this.style.display = 'none';
                    }
                } else {
                    alert('Lỗi khi tải bình luận: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Lỗi server, vui lòng thử lại!');
            });
        });
    }
});

function loadVersion(fileUrl, fileExt) {
    const documentContainer = document.getElementById('document-container');
    documentContainer.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Đang tải...</span></div></div>';
    if (fileExt === 'pdf') {
        // Hiển thị PDF bằng pdf.js
        pdfjsLib.getDocument(fileUrl).promise.then(function(pdf) {
            documentContainer.innerHTML = ''; // Xóa spinner
            const numPages = pdf.numPages;
            for (let pageNum = 1; pageNum <= numPages; pageNum++) {
                pdf.getPage(pageNum).then(function(page) {
                    const scale = 1.0; // Giảm scale để tránh vỡ layout
                    const viewport = page.getViewport({ scale: scale });
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    canvas.style.maxWidth = '100%';
                    canvas.style.margin = '0 auto';
                    canvas.style.display = 'block';
                    documentContainer.appendChild(canvas);
                    page.render({ canvasContext: context, viewport: viewport });
                });
            }
            documentContainer.scrollTop = 0;
        }).catch(function(error) {
            console.error('Error loading PDF:', error);
            documentContainer.innerHTML = '<p>Tài liệu không thể hiển thị. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
        });
    } else if (fileExt === 'docx') {
        // Kiểm tra xem docx-preview có được tải đúng không
        if (typeof docx === 'undefined' || typeof docx.renderAsync !== 'function') {
            console.error('docx-preview library is not loaded or renderAsync is not available');
            documentContainer.innerHTML = '<p>Thư viện docx-preview không được tải. Vui lòng kiểm tra kết nối mạng hoặc CDN. <a href="' + fileUrl + '" download>Tải xuống để xem.</a></p>';
            return;
        }

        // Hiển thị DOCX bằng docx-preview
        fetch(fileUrl)
            .then(response => {
                console.log('Fetch response:', response);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.arrayBuffer();
            })
            .then(buffer => {
                console.log('Buffer loaded:', buffer.byteLength);
                docx.renderAsync(buffer, documentContainer, null, {
                    ignoreWidth: false,
                    ignoreHeight: false,
                    breakPages: true,
                    renderHeaders: true,
                    renderFooters: true,
                    useBase64URL: true
                }).then(() => {
                    console.log('DOCX rendered successfully');
                    documentContainer.scrollTop = 0;
                }).catch(error => {
                    console.error('Error rendering DOCX:', error);
                    documentContainer.innerHTML = '<p>Tài liệu DOCX không thể hiển thị. Có thể file bị hỏng hoặc không tương thích. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
                });
            })
            .catch(error => {
                console.error('Error loading DOCX:', error);
                documentContainer.innerHTML = '<p>Tài liệu không thể hiển thị. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
            });
        } else if (fileExt === 'pptx') {
            // Gọi API chuyển đổi PPTX sang PDF
            console.log('Requesting PPTX conversion for file:', fileUrl);
            fetch('/study_sharing/convert_pptx_to_pdf.php?file=' + encodeURIComponent(fileUrl))
                .then(response => {
                    // Kiểm tra phản hồi có phải JSON không
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            console.error('Phản hồi không phải JSON:', text);
                            throw new Error('Phản hồi không phải JSON: ' + text);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        console.log('PDF converted successfully:', data.pdfPath);
                        // Hiển thị PDF bằng pdf.js
                        pdfjsLib.getDocument(data.pdfPath).promise.then(function(pdf) {
                            documentContainer.innerHTML = ''; // Xóa spinner
                            const numPages = pdf.numPages;
                            for (let pageNum = 1; pageNum <= numPages; pageNum++) {
                                pdf.getPage(pageNum).then(function(page) {
                                    const scale = 1.0;
                                    const viewport = page.getViewport({ scale: scale });
                                    const canvas = document.createElement('canvas');
                                    const context = canvas.getContext('2d');
                                    canvas.height = viewport.height;
                                    canvas.width = viewport.width;
                                    canvas.style.maxWidth = '100%';
                                    canvas.style.margin = '0 auto';
                                    canvas.style.display = 'block';
                                    documentContainer.appendChild(canvas);
                                    page.render({ canvasContext: context, viewport: viewport });
                                });
                            }
                            documentContainer.scrollTop = 0;
                        }).catch(function(error) {
                            console.error('Error loading converted PDF:', error);
                            documentContainer.innerHTML = '<p>Tài liệu không thể hiển thị. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
                        });
                    } else {
                        console.error('Conversion failed:', data.message);
                        documentContainer.innerHTML = '<p>Tài liệu PPTX không thể hiển thị: ' + data.message + '. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
                    }
                })
                .catch(error => {
                    console.error('Error converting PPTX:', error);
                    documentContainer.innerHTML = '<p>Tài liệu PPTX không thể hiển thị: ' + error.message + '. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
                });
        } else {
        console.error('Unsupported file extension:', fileExt);
        documentContainer.innerHTML = '<p>Định dạng file không được hỗ trợ. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const versionSelect = document.getElementById('versionSelect');
    if (versionSelect) {
        const fileUrl = versionSelect.value;
        const fileExt = versionSelect.options[versionSelect.selectedIndex].value.split('.').pop().toLowerCase();
        if (fileUrl) {
            loadVersion(fileUrl, fileExt);
        }
    }

    const ratingStars = document.getElementById('rating-stars');
    if (ratingStars) {
        const documentId = ratingStars.dataset.documentId;
        const userRating = parseInt(ratingStars.dataset.userRating);
        initializeRatingStars(ratingStars, documentId, userRating);
    }
});

function initializeRatingStars(ratingStars, documentId, userRating) {
    const stars = ratingStars.querySelectorAll('.star');
    if (userRating > 0) {
        for (let i = 0; i < userRating; i++) {
            stars[i].classList.add('filled');
        }
    }
    stars.forEach(star => {
        star.addEventListener('mouseover', function() {
            const value = parseInt(this.dataset.value);
            highlightStars(value);
        });
        star.addEventListener('mouseout', function() {
            resetStars(userRating);
        });
        star.addEventListener('click', function() {
            const value = parseInt(this.dataset.value);
            submitRating(documentId, value);
        });
    });
}

function highlightStars(value) {
    const stars = document.getElementById('rating-stars').querySelectorAll('.star');
    stars.forEach(star => {
        const starValue = parseInt(star.dataset.value);
        if (starValue <= value) {
            star.classList.add('filled');
        } else {
            star.classList.remove('filled');
        }
    });
}

function resetStars(userRating) {
    const stars = document.getElementById('rating-stars').querySelectorAll('.star');
    stars.forEach(star => {
        star.classList.remove('filled');
    });
    if (userRating > 0) {
        for (let i = 0; i < userRating; i++) {
            stars[i].classList.add('filled');
        }
    }
}

function submitRating(documentId, ratingValue) {
    const isLoggedIn = document.querySelector('.nav-item.dropdown.ms-lg-2 .nav-link.dropdown-toggle')?.textContent.trim() !== 'Tài khoản';
    if (!isLoggedIn) {
        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
        return;
    }

    fetch('/study_sharing/document/rateDocument', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `document_id=${documentId}&rating_value=${ratingValue}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Đánh giá đã được gửi!');
            location.reload();
        } else {
            alert('Lỗi khi gửi đánh giá: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Lỗi server, vui lòng thử lại!');
    });
}

function recordDownload(documentId, event) {
    event.preventDefault();
    const downloadUrl = document.getElementById('versionSelect').value;
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = '';
    document.body.appendChild(link);

    fetch('/study_sharing/document/recordDownload', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `document_id=${documentId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            link.click();
            alert('Tải xuống thành công!');
        } else {
            alert('Lỗi khi ghi nhận tải xuống: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Lỗi server, vui lòng thử lại!');
    })
    .finally(() => {
        document.body.removeChild(link);
    });
}