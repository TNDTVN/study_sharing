document.addEventListener('DOMContentLoaded', function () {
    const joinCourseBtn = document.getElementById('joinCourseBtn');
    if (joinCourseBtn) {
        joinCourseBtn.addEventListener('click', function () {
            const courseId = joinCourseBtn.getAttribute('data-course-id');
            
            fetch('/study_sharing/course/joinCourse', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `course_id=${courseId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    joinCourseBtn.disabled = true;
                    joinCourseBtn.textContent = 'Đã tham gia';
                    // Reload trang để cập nhật danh sách thành viên
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi khi tham gia khóa học');
            });
        });
    }
});