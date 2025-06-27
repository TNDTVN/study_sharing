document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('courseFilterForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(form);
            const query = formData.get('query').trim();
            const url = new URL(window.location.href);
            url.searchParams.set('query', query);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });
    }
});