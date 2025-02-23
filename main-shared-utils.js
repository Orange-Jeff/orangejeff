/* main-shared-utils.js */

// Status message constants
const STATUS_MESSAGES = {
    file: {
        loaded: (filename) => `File loaded: ${filename}`,
        new: (filename) => `New file created: ${filename}`
    },
    clipboard: {
        paste: (filename) => `Content pasted from clipboard: ${filename}`,
        copy: (filename) => `Content copied to clipboard: ${filename}`
    }
};

let editorContent = '';

function updateStatus(message, type = 'info') {
    const statusBar = document.getElementById('statusBar');
    const statusMessage = document.createElement('div');
    statusMessage.className = `status-message ${type}`;
    statusMessage.textContent = message;
    statusBar.insertBefore(statusMessage, statusBar.firstChild);

    while (statusBar.children.length > 5) {
        statusBar.removeChild(statusBar.lastChild);
    }
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

function openInNewTab(filename) {
    const fileExtension = filename.split('.').pop().toLowerCase();

    // Check if trying to run main editor
    if (filename === 'main.php' || filename.includes('main')) {
        updateStatus('Cannot run the editor interface directly', 'info');
        return;
    }

    if (!['php', 'html', 'htm'].includes(fileExtension)) {
        updateStatus(`Cannot run ${fileExtension} files directly`, 'info');
        return;
    }

    const editorView = document.querySelector('.editor-view');
    const backupView = document.querySelector('.backup-view');

    if (!backupView.classList.contains('active')) {
        editorView.classList.toggle('hidden');
        backupView.classList.toggle('active');
    }

    const iframe = backupView.querySelector('iframe');
    iframe.src = filename;
}

function openInNewWindow(filename) {
    const fileExtension = filename.split('.').pop().toLowerCase();

    // Check if trying to run main editor
    if (filename === 'main.php' || filename.includes('main')) {
        updateStatus('Cannot run the editor interface directly', 'info');
        return false;
    }

    if (!['php', 'html', 'htm'].includes(fileExtension)) {
        updateStatus(`Cannot run ${fileExtension} files directly`, 'info');
        return false;
    }

    window.open(filename, '_blank');
    return false;
}

// Export utilities
window.STATUS_MESSAGES = STATUS_MESSAGES;
window.editorContent = editorContent;
window.updateStatus = updateStatus;
window.fromClipboard = fromClipboard;
window.toClipboard = toClipboard;
window.openInNewTab = openInNewTab;
window.openInNewWindow = openInNewWindow;
