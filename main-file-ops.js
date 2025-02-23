/* main-file-ops.js */

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

// Export functions
window.saveFile = saveFile;
