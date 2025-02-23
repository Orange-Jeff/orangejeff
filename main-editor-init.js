/**
 * Part of NetBound Tools - main.php
 * https://netbound.ca
 * Free to modify and shared
 *
 * editor-init.js
 *
 * Initializes the Ace Editor and sets up event listeners.
 * Dependencies:
 * - Ace Editor (https://ace.c9.io/)
 * - Shared utility functions (shared-utils.js)
 */

// Initialize Ace Editor
var editor = ace.edit("editor");
editor.setTheme("ace/theme/monokai");
editor.session.setMode("ace/mode/php");
editor.setOptions({
    enableBasicAutocompletion: true,
    enableSnippets: true,
    enableLiveAutocompletion: true,
    useSoftTabs: true,
    tabSize: 4,
    fontSize: "14px",
    showPrintMargin: false
});

/**
 * Loads a file into the editor with proper view management.
 * @param {string} filename - The name of the file to load.
 */
function loadFile(filename) {
    const editorView = document.querySelector('.editor-view');
    const backupView = document.querySelector('.backup-view');
    editorView.classList.remove('hidden');
    backupView.classList.remove('active');

    fetch('main.php?file=' + encodeURIComponent(filename))
        .then(response => response.text())
        .then(content => {
            if (editor.getValue() !== editorContent) {
                if (!confirm('Unsaved changes detected. Continue?')) return;
            }
            editor.setValue(content, -1);
            document.getElementById('editorFilename').value = filename;
            updateStatus(STATUS_MESSAGES.file.loaded(filename), 'success');
            editorContent = editor.getValue();
            setEditorMode(filename);
            document.body.classList.remove('menu-visible');
        });
}

/**
 * Sets the Ace Editor mode based on the file extension.
 * @param {string} filename - The name of the file.
 */
function setEditorMode(filename) {
    const fileExtension = filename.split('.').pop().toLowerCase();
    let mode = "ace/mode/php";  // Default mode is PHP

    // Set the editor mode based on the file extension
    switch (fileExtension) {
        case 'css':
            mode = "ace/mode/css";
            break;
        case 'js':
            mode = "ace/mode/javascript";
            break;
        case 'json':
            mode = "ace/mode/json";
            break;
        case 'html':
        case 'htm':
            mode = "ace/mode/html";
            break;
        case 'txt':
            mode = "ace/mode/text";
            break;
    }
    editor.session.setMode(mode);
}

// Export necessary functions to the global scope
window.loadFile = loadFile;
window.editor = editor;

// Listen for syntax errors in the editor
editor.getSession().on("changeAnnotation", () => {
    const annotations = editor.getSession().getAnnotations();
    const errors = annotations.filter(a => a.type === 'error');

    // Display the first syntax error in the status bar
    if (errors.length > 0) {
        updateStatus(`Syntax: ${errors[0].text}`, 'error');
    }
});

/**
 * Updates the status bar with a message.
 * @param {string} message - The message to display.
 * @param {string} type - The type of message (e.g., 'success', 'error').
 */

document.getElementById('menuSortBtn').addEventListener('click', async function () {
    console.log("Sort button clicked!"); // Debug log
    const sortIcon = this.querySelector('i');
    await fetch('main.php?toggleSort=1');
    if (sortIcon.classList.contains('fa-sort-alpha-down')) {
        sortIcon.classList.remove('fa-sort-alpha-down');
        sortIcon.classList.add('fa-clock');
    } else {
        sortIcon.classList.remove('fa-clock');
        sortIcon.classList.add('fa-sort-alpha-down');
    }
    await updateFileList();
});

async function updateFileList() {
    console.log("Fetching file list..."); // Debug log
    const response = await fetch('main.php?getFileList=1');
    const html = await response.text();
    document.querySelector('.file-list').innerHTML = html;
}

// Load the file list initially
updateFileList();
