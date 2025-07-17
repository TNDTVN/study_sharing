    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('uploadForm');
        const tagsInput = document.getElementById('documentTags');
        const tagsDropdown = tagsInput.nextElementSibling;
        const fileInput = document.getElementById('documentFile');
        const fileNameDisplay = document.getElementById('fileName');
        const previewContainer = document.getElementById('previewContainer');
        const previewMessage = document.getElementById('previewMessage');
        const previewContent = document.getElementById('previewContent');

        // Initialize tags
        let selectedTags = [];
        let isDropdownVisible = false;

        function updateTagsInput() {
            tagsInput.value = selectedTags.join(', ');
        }

        function toggleTagsDropdown(show) {
            isDropdownVisible = show;
            tagsDropdown.style.display = show ? 'block' : 'none';
        }

        tagsInput.addEventListener('click', () => {
            if (!isDropdownVisible) {
                toggleTagsDropdown(true);
            }
        });

        tagsDropdown.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', () => {
                const tag = item.dataset.value;
                if (selectedTags.includes(tag)) {
                    selectedTags = selectedTags.filter(t => t !== tag);
                    item.querySelector('.tick').classList.add('d-none');
                } else {
                    selectedTags.push(tag);
                    item.querySelector('.tick').classList.remove('d-none');
                }
                updateTagsInput();
            });
        });

        document.addEventListener('click', (e) => {
            if (!tagsInput.contains(e.target) && !tagsDropdown.contains(e.target)) {
                toggleTagsDropdown(false);
            }
        });

        // Handle file input and preview
        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            const fileName = file ? file.name : '';
            fileNameDisplay.textContent = fileName || '';
            previewContainer.style.display = 'none'; // Ẩn ô xem trước mặc định
            previewContent.innerHTML = '';
            previewMessage.textContent = '';

            if (file) {
                const fileExt = file.name.split('.').pop().toLowerCase();
                previewContainer.style.display = 'block'; // Hiện ô xem trước khi có tệp

                if (fileExt === 'pdf') {
                    previewMessage.textContent = 'Đang tải bản xem trước PDF...';
                    const fileReader = new FileReader();
                    fileReader.onload = function() {
                        const typedArray = new Uint8Array(this.result);
                        pdfjsLib.getDocument(typedArray).promise.then(pdf => {
                            previewMessage.textContent = '';
                            const numPages = pdf.numPages;
                            for (let pageNum = 1; pageNum <= numPages; pageNum++) {
                                pdf.getPage(pageNum).then(page => {
                                    const canvas = document.createElement('canvas');
                                    canvas.style.marginBottom = '1rem';
                                    previewContent.appendChild(canvas);
                                    const context = canvas.getContext('2d');
                                    const viewport = page.getViewport({
                                        scale: 1.0
                                    });
                                    canvas.height = viewport.height;
                                    canvas.width = viewport.width;
                                    page.render({
                                        canvasContext: context,
                                        viewport: viewport
                                    }).promise.then(() => {
                                        if (pageNum === numPages) {
                                            previewMessage.textContent = 'Bản xem trước toàn bộ tài liệu PDF';
                                        }
                                    });
                                });
                            }
                        }).catch(error => {
                            previewMessage.textContent = 'Lỗi khi tải bản xem trước PDF: ' + error.message;
                        });
                    };
                    fileReader.readAsArrayBuffer(file);
                } else if (fileExt === 'docx') {
                    previewMessage.textContent = 'Đang tải bản xem trước DOCX...';
                    const fileReader = new FileReader();
                    fileReader.onload = function() {
                        const arrayBuffer = this.result;
                        previewContent.innerHTML = '';
                        docx.renderAsync(arrayBuffer, previewContent).then(() => {
                            previewMessage.textContent = 'Bản xem trước tài liệu DOCX';
                        }).catch(error => {
                            previewMessage.textContent = 'Lỗi khi tải bản xem trước DOCX: ' + error.message;
                        });
                    };
                    fileReader.readAsArrayBuffer(file);
                } else if (fileExt === 'pptx') {
                    previewMessage.textContent = 'Tệp PPTX không hỗ trợ xem trước, nhưng bạn vẫn có thể tải lên.';
                } else {
                    previewMessage.textContent = 'Định dạng tệp không được hỗ trợ xem trước.';
                }
            } else {
                previewContainer.style.display = 'none'; // Ẩn ô xem trước nếu không có tệp
            }
        });

        // Handle form submission
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const submitButton = form.querySelector('button[type="submit"]');
            const spinner = submitButton.querySelector('.spinner-border');
            const messageDiv = document.getElementById('uploadMessage');

            if (!form.checkValidity()) {
                event.stopPropagation();
                form.classList.add('was-validated');
                return;
            }

            submitButton.disabled = true;
            spinner.classList.remove('d-none');

            const formData = new FormData(form);
            fetch('/study_sharing/document/create', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    messageDiv.innerHTML = `<div class="alert alert-${data.success ? 'success' : 'danger'} mt-3">${data.message}</div>`;
                    if (data.success) {
                        form.reset();
                        form.classList.remove('was-validated');
                        fileNameDisplay.textContent = '';
                        previewContainer.style.display = 'none';
                        previewContent.innerHTML = '';
                        previewMessage.textContent = '';
                        selectedTags = [];
                        updateTagsInput();
                        tagsDropdown.querySelectorAll('.tick').forEach(tick => tick.classList.add('d-none'));
                        if (data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 2000);
                        }
                    }
                })
                .catch(error => {
                    messageDiv.innerHTML = '<div class="alert alert-danger mt-3">Lỗi server, vui lòng thử lại!</div>';
                })
                .finally(() => {
                    submitButton.disabled = false;
                    spinner.classList.add('d-none');
                });

            form.classList.add('was-validated');
        });
    });