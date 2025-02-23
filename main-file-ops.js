function saveFile(newFilename = null) {
    const filename = newFilename || document.getElementById('editorFilename').value;
    if (!filename) {
        updateStatus('Filename required', 'error');
        return;
    }

    const content = editor.getValue();
    fetch('main.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=save&filename=${encodeURIComponent(filename)}&content=${encodeURIComponent(content)}`
    })
    .then(response => response.json())
    .then(result => {
        updateStatus(result.message, result.status);
        if (newFilename) {
            document.getElementById('editorFilename').value = newFilename;
        }
        document.getElementById('editorSection').style.display = 'flex';
        editorContent = editor.getValue();
    });
}

function fromClipboard() {
    if (editor.getValue() !== editorContent) {
        if (!confirm('Unsaved changes detected. Continue?')) return;
    }
    document.getElementById('editorSection').style.display = 'flex';
    navigator.clipboard.readText().then(text => {
        editor.setValue(text);
        updateStatus(STATUS_MESSAGES.clipboard.paste(document.getElementById('editorFilename').value), 'success');
        editorContent = editor.getValue();
    });
}

function toClipboard() {
    navigator.clipboard.writeText(editor.getValue()).then(() => {
        updateStatus(STATUS_MESSAGES.clipboard.copy(document.getElementById('editorFilename').value), 'success');
    });
}

// Export functions
window.saveFile = saveFile;
window.fromClipboard = fromClipboard;
window.toClipboard = toClipboard;
