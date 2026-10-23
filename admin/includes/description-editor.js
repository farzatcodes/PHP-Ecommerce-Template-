/**
 * Lightweight rich-text editor for the product Description field.
 * Supports basic formatting plus image upload via toolbar button or
 * paste-from-clipboard (similar to Alibaba's product description editor).
 * Keeps a hidden <textarea name="description"> in sync so the existing
 * PHP form-handling code (which expects raw HTML in $_POST['description'])
 * needs no changes.
 */
(function () {
    const editor = document.getElementById('descEditor');
    const textarea = document.getElementById('descTextarea');
    const imageInput = document.getElementById('descImageInput');
    const imageBtn = document.getElementById('descImageBtn');
    const status = document.getElementById('descUploadStatus');
    const toolbar = document.getElementById('descToolbar');
    const form = editor ? editor.closest('form') : null;

    if (!editor || !textarea) return;

    function syncTextarea() {
        textarea.value = editor.innerHTML.trim();
    }

    // Keep the hidden textarea updated as the user types
    editor.addEventListener('input', syncTextarea);
    syncTextarea(); // capture any pre-filled content on load

    // Make sure the latest content is flushed right before submit,
    // and require some content since the real <textarea> is hidden
    // (a hidden "required" field can't be validated by the browser).
    if (form) {
        form.addEventListener('submit', function (e) {
            syncTextarea();
            if (editor.innerText.trim() === '') {
                e.preventDefault();
                editor.focus();
                setStatus('Description is required', true);
            }
        });
    }

    // Toolbar formatting buttons (Bold / Italic / List / etc.)
    if (toolbar) {
        toolbar.querySelectorAll('[data-cmd]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                editor.focus();
                document.execCommand(btn.getAttribute('data-cmd'), false, null);
                syncTextarea();
            });
        });
    }

    function setStatus(msg, isError) {
        if (!status) return;
        status.textContent = msg;
        status.className = 'text-xs ml-2 ' + (isError ? 'text-red-500' : 'text-gray-400');
        if (msg) setTimeout(function () { status.textContent = ''; }, 3000);
    }

    function uploadImage(file) {
        if (!file || file.type.indexOf('image/') !== 0) return;

        const csrfInput = form ? form.querySelector('input[name="csrf_token"]') : null;
        const formData = new FormData();
        formData.append('image', file);
        formData.append('csrf_token', csrfInput ? csrfInput.value : '');

        setStatus('Uploading image...', false);

        fetch('upload_description_image.php', {
            method: 'POST',
            body: formData
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success && data.url) {
                editor.focus();
                document.execCommand('insertHTML', false, '<img src="' + data.url + '" style="max-width:100%;height:auto;" alt="">');
                syncTextarea();
                setStatus('Image inserted', false);
            } else {
                setStatus(data.error || 'Upload failed', true);
            }
        })
        .catch(function () {
            setStatus('Upload failed', true);
        });
    }

    // Toolbar "Insert Image" button
    if (imageBtn && imageInput) {
        imageBtn.addEventListener('click', function () {
            imageInput.click();
        });
        imageInput.addEventListener('change', function () {
            if (imageInput.files && imageInput.files[0]) {
                uploadImage(imageInput.files[0]);
            }
            imageInput.value = '';
        });
    }

    // Paste-to-upload: if the clipboard contains an image (e.g. copied from
    // another site or a screenshot), upload it instead of pasting raw data
    editor.addEventListener('paste', function (e) {
        const items = (e.clipboardData || window.clipboardData).items;
        if (!items) return;
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image/') === 0) {
                e.preventDefault();
                uploadImage(items[i].getAsFile());
                return;
            }
        }
        // Otherwise let the default paste happen, then sync
        setTimeout(syncTextarea, 0);
    });

    // Drag-and-drop images straight into the editor
    editor.addEventListener('dragover', function (e) { e.preventDefault(); });
    editor.addEventListener('drop', function (e) {
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
            const file = e.dataTransfer.files[0];
            if (file.type.indexOf('image/') === 0) {
                e.preventDefault();
                uploadImage(file);
            }
        }
    });
})();
