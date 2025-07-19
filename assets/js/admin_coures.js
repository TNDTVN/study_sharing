javascript
document.addEventListener('DOMContentLoaded', function () {
    // Form validation
    (function () {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();

    // Edit button handler
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function () {
            const modal = document.getElementById('editCourseModal');
            document.getElementById('editCourseId').value = this.dataset.id;
            document.getElementById('editCourseName').value = this.dataset.courseName;
            document.getElementById('editDescription').value = this.dataset.description;
            document.getElementById('editCreatorId').value = this.dataset.creatorId;
            document.getElementById('editMaxMembers').value = this.dataset.maxMembers;
            document.getElementById('editLearnLink').value = this.dataset.learnLink;
            document.getElementById('editStartDate').value = this.dataset.startDate;
            document.getElementById('editEndDate').value = this.dataset.endDate;
            document.getElementById('editStatus').value = this.dataset.status;
        });
    });

    // Delete button handler
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function () {
            if (confirm('Bạn có chắc chắn muốn xóa khóa học này?')) {
                fetch('/study_sharing/AdminCourse/admin_delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ course_id: this.dataset.id })
                })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                        if (data.success) {
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Đã xảy ra lỗi khi xóa khóa học!');
                    });
            }
        });
    });

});