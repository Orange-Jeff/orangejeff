<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const menu = document.querySelector('.menu');
    const container = document.querySelector('.container');

    menuToggle.addEventListener('click', function() {
        menu.classList.toggle('open');
        container.classList.toggle('menu-open');
    });
});
</script><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"><?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NetBound Tools: Ace Editor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="shared-styles.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ace.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ext-language_tools.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ext-searchbox.js"></script>
</head>

<body>
    <div class="menu-container">
        <div class="title-container">
            <h1 class="editor-title">NetBound Tools: Ace Editor <span id="versionNumber"></span></h1>
            <a href="#" onclick="toggleIframeMode(); return false;" class="hamburger-menu" title="Toggle Position">
                <i class="fas fa-bars"></i>
            </a>
        </div>

        <div id="statusBox" class="status-box"></div>

        <div class="button-controls">
            <div class="button-row">
                <div class="split-button">
                    <div class="main-part" onclick="fromClipboard()" title="Replace editor content with clipboard content">
                        <i class="fas fa-clipboard"></i> From Clipboard
                    </div>
                    <div class="append-part" onclick="appendClipboard()" title="Add clipboard content to end of file">
                        <i class="fas fa-plus"></i>
                    </div>
                </div>

                <button type="button" class="command-button" onclick="openFileRequester()" title="Open a file">
                    <i class="fas fa-folder-open"></i> From File
                </button>

                <div class="split-button">
                    <div class="main-part" onclick="loadTemplate()" title="Load template file">
                        <i class="fas fa-file-code"></i> From Template
                    </div>
                    <div class="append-part" onclick="loadSharedStyles()" title="Load shared-styles.css in a new tab">
                        <i class="fas fa-plus"></i>
                    </div>
                </div>

                <div class="split-button">
                    <div class="main-part" onclick="fromBackup()" title="Load content from latest backup">
                        <i class="fas fa-history"></i> OOPS
                    </div>
                    <div class="append-part" onclick="fromBackupManager()" title="Open the full backup manager">
                        <i class="fas fa-plus"></i>
                    </div>
                </div>

                <button class="command-button" id="btnRestart">
                    <i class="fas fa-redo"></i> Restart
                </button>
            </div>
        </div>

        <div class="editor-view">
            <!-- Tab bar -->
            <div class="tab-bar">
                <div class="tab-list">
                    <div class="tab active" data-file="untitled">
                        <span class="tab-title">untitled</span>
                        <button class="tab-close"><i class="fas fa-times"></i></button>
                    </div>
                    <button class="new-tab" title="New Tab (Ctrl+T)">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div class="tab-controls">
                    <button onclick="toggleSplitView()" title="Split View (Alt+S)">
                        <i class="fas fa-columns"></i>
                    </button>
                    <button onclick="toggleSync()" title="Toggle Sync (Alt+Y)" id="syncButton" style="display: none;">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>

            <!-- Editor containers -->
            <div class="editor-containers">
                <div class="editor-container" id="editor1">
                    <div class="editor-controls">
                        <button onclick="toggleTheme()" title="Switch Theme (Light/Dark)">
                            <i class="fas fa-adjust"></i>
                        </button>
                        <button onclick="toggleWrap()" title="Toggle Word Wrap (Alt+W)">
                            <i class="fas fa-paragraph"></i>
                        </button>
                        <button onclick="showSearchReplace()" title="Search & Replace (Ctrl+F or F3, Alt+Enter for replace all)">
                            <i class="fas fa-search"></i>
                        </button>
                        <button onclick="toggleLineNumbers()" title="Toggle Line Numbers">
                            <i class="fas fa-list-ol"></i>
                        </button>
                        <button onclick="toggleIndentGuides()" title="Toggle Indent Guides">
                            <i class="fas fa-indent"></i>
                        </button>
                        <button onclick="addCommentLine()" title="Add Comment Line (Ctrl+/)">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button onclick="editor.undo()" title="Undo (Ctrl+Z)">
                            <i class="fas fa-undo"></i>
                        </button>
                        <button onclick="editor.redo()" title="Redo (Ctrl+Y)">
                            <i class="fas fa-redo"></i>
                        </button>
                        <button onclick="gotoTop()" title="Go to Top">
                            <i class="fas fa-angle-up"></i>
                        </button>
                        <button onclick="gotoBottom()" title="Go to Bottom">
                            <i class="fas fa-angle-down"></i>
                        </button>
                        <button onclick="toggleFullscreen()" title="Toggle Fullscreen">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                    <div id="editor"></div>
                </div>
                <div class="editor-container hidden" id="editor2">
                    <div class="editor-controls">
                        <button onclick="toggleTheme()" title="Switch Theme (Light/Dark)">
                            <i class="fas fa-adjust"></i>
                        </button>
                        <button onclick="toggleWrap()" title="Toggle Word Wrap (Alt+W)">
                            <i class="fas fa-paragraph"></i>
                        </button>
                        <button onclick="showSearchReplace()" title="Search & Replace">
                            <i class="fas fa-search"></i>
                        </button>
                        <button onclick="toggleLineNumbers()" title="Toggle Line Numbers">
                            <i class="fas fa-list-ol"></i>
                        </button>
                        <button onclick="toggleIndentGuides()" title="Toggle Indent Guides">
                            <i class="fas fa-indent"></i>
                        </button>
                        <button onclick="editor2.undo()" title="Undo">
                            <i class="fas fa-undo"></i>
                        </button>
                        <button onclick="editor2.redo()" title="Redo">
                            <i class="fas fa-redo"></i>
                        </button>
                    </div>
                    <div id="editor-secondary"></div>
                </div>
            </div>

            <!-- Filename control -->
            <div class="filename-container">
                <input type="text" id="editorFilename" class="filename-input" value="" placeholder="Filename" onchange="updateDisplayFilename()" onblur="checkUniqueFilename()">
                <button type="button" class="command-button" onclick="updateVersionAndDate()" title="Change the filename">
                    <i class="fas fa-sync-alt"></i> Rename
                </button>
            </div>

            <!-- Bottom action bar -->
            <div class="button-row">
                <div class="split-button">
                    <div class="main-part" onclick="saveFile()" title="Save current file (Ctrl+S)">
                        <i class="fas fa-upload"></i> Save
                    </div>
                    <div class="append-part" onclick="saveAllTabs()" title="Save all open files">
                        <i class="fas fa-save"></i>
                    </div>
                </div>

                <div class="split-button">
                    <div class="main-part" onclick="toClipboard()" title="Copy editor content to clipboard">
                        <i class="fas fa-clipboard"></i> To Clipboard
                    </div>
                </div>

                <button type="button" class="command-button" onclick="openInNewWindow()" title="Run this file in new window">
                    <i class="fas fa-external-link-alt"></i> Run
                </button>

                <button type="button" class="command-button" onclick="exportCurrentTab()" title="Save current tab to your downloads">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>
    </div>

    <input type="file" id="fileInput" style="display: none">

    <script>
        // Toggle between iframe and non-iframe mode
        function toggleIframeMode() {
            const isInIframe = window.location.href.includes('?app=');

            if (isInIframe) {
                // If we're in an iframe, navigate the parent window
                parent.window.location.href = 'nb-ace-editor.php';
            } else {
                // If we're not in an iframe, load in iframe mode
                window.location.href = 'main.php?app=nb-ace-editor.php';
            }

            // No need to toggle classes here as the page will reload
            // with the correct state based on the URL
        }

        // State variables
        let editorContent = '';
        let currentLoadedFilename = '';

        // Add status messages from main.php
        const STATUS_MESSAGES = {
            folder: {
                created: (name) => `Folder created: ${name}`,
                error: (name) => `Failed to create folder: ${name}`,
                deleted: (name) => `Folder deleted: ${name}`
            },
            parent: "parent",
            file: {
                loaded: (filename) => `File loaded: ${filename}`,
                saved: (filename) => `File saved: ${filename}`,
                new: (filename) => `New file created: ${filename}`,
                deleted: (filename) => `File deleted: ${filename}`
            },
            clipboard: {
                paste: () => `Content pasted from clipboard`,
                append: () => `Content appended from clipboard`,
                copy: () => `Content copied to clipboard`
            },
            backup: {
                restored: () => `Backup restored but not saved`,
                created: (version) => `Backup created (V${version})`
            }
        };

        // Update the status box with messages
        function updateStatus(message, type = 'info') {
            const statusBox = document.querySelector('.status-box');
            if (statusBox) {
                const messageElement = document.createElement('div');
                messageElement.className = `status-message ${type}`;
                messageElement.textContent = message;
                statusBox.appendChild(messageElement);

                // Auto-scroll to the bottom of the status box
                statusBox.scrollTop = statusBox.scrollHeight;
            }
        }

        // Filename handling
        function generateUniqueFilename(baseName) {
            const tabs = document.querySelectorAll('.tab');
            const existingNames = Array.from(tabs).map(tab => tab.dataset.file);

            if (!existingNames.includes(baseName)) return baseName;

            let counter = 1;
            let newName = '';

            do {
                const ext = baseName.includes('.') ? baseName.split('.').pop() : '';
                const name = baseName.includes('.') ? baseName.slice(0, -(ext.length + 1)) : baseName;
                newName = `${name}_${counter}${ext ? `.${ext}` : ''}`;
                counter++;
            } while (existingNames.includes(newName));

            return newName;
        }

        function setEditorMode(filename) {
            const fileExtension = filename.split('.').pop().toLowerCase();
            let mode = "ace/mode/php"; // Default mode

            if (fileExtension === 'css') {
                mode = "ace/mode/css";
            } else if (fileExtension === 'js') {
                mode = "ace/mode/javascript";
            } else if (fileExtension === 'json') {
                mode = "ace/mode/json";
            } else if (fileExtension === 'html' || fileExtension === 'htm') {
                mode = "ace/mode/html";
            } else if (fileExtension === 'txt') {
                mode = "ace/mode/text";
            }

            editor.session.setMode(mode);
        }

        // Core file operations
        function aceLoadFile(filename) {
            fetch('main.php?file=' + encodeURIComponent(filename))
                .then(response => response.text())
                .then(content => {
                    const uniqueFilename = generateUniqueFilename(filename);
                    const tab = tabManager.createTab(uniqueFilename, content);
                    tab.session.on('change', () => {
                        tab.hasUnsavedChanges = true;
                        tab.filename = tab.hasUnsavedChanges ? `${tab.filename}*` : tab.filename;
                        tabManager.renderTabs();
                    });
                    setEditorMode(uniqueFilename);
                    document.getElementById('editorFilename').value = uniqueFilename;
                    updateStatus('File loaded: ' + uniqueFilename, 'success');
                    getNextVersion(filename);

                    // Check for related files
                    checkRelatedFiles(filename);
                })
                .catch(error => {
                    updateStatus('Error: ' + error.message, 'error');
                    console.error(error);
                });
        }

        function checkRelatedFiles(filename) {
            const base = filename.replace(/\.[^/.]+$/, "");
            const related = {
                '.php': ['.css', '.js'],
                '.html': ['.css', '.js'],
                '.css': ['.php', '.html'],
                '.js': ['.php', '.html']
            };

            const ext = '.' + filename.split('.').pop();
            if (related[ext]) {
                related[ext].forEach(relatedExt => {
                    const relatedFile = base + relatedExt;
                    fetch('main.php?file=' + encodeURIComponent(relatedFile))
                        .then(response => {
                            if (response.ok) {
                                if (confirm(`Found related file: ${relatedFile}. Open it?`)) {
                                    aceLoadFile(relatedFile);
                                }
                            }
                        })
                        .catch(() => {}); // Silently ignore missing related files
                });
            }
        }

        function saveFile(newFilename = null) {
            // Save either current tab or specified file
            let filename = newFilename || activeTab;
            if (!filename) {
                return updateStatus('Filename required', 'error');
            }

            // Get correct editor instance and content
            const currentEditor = editors[filename] || editor;
            const fileContent = currentEditor.getValue();
            const currentTab = tabManager.getCurrentTab();

            fetch('main.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=save&filename=${encodeURIComponent(filename)}&content=${encodeURIComponent(fileContent)}&createBackup=1`
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(result => {
                    // Update current tab state
                    const tab = tabManager.getCurrentTab();
                    if (tab) {
                        tab.hasUnsavedChanges = false;
                        if (newFilename) {
                            tab.filename = newFilename;
                        }
                    }
                    tabManager.renderTabs();

                    // Show appropriate save message
                    if (newFilename) {
                        updateStatus(`File renamed and saved as: ${newFilename}`, 'success');
                    } else {
                        updateStatus(`${result.message} (Ctrl+S to save quickly)`, 'success');
                    }

                    // Update editor state
                    if (newFilename) {
                        document.getElementById('editorFilename').value = newFilename;
                    }

                    // Update version
                    if (result.version) {
                        currentVersion = result.version;
                        updateVersionDisplay();
                    } else {
                        getNextVersion(filename);
                    }
                })
                .catch(error => {
                    updateStatus('Error: ' + error.message, 'error');
                    console.error(error);
                });
        }

        function saveAs() {
            const filename = document.getElementById('editorFilename').value || 'newfile.php';
            const content = editor.getValue();
            const blob = new Blob([content], {
                type: 'text/plain'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(url);
            updateStatus('File saved to downloads', 'success');
        }

        // Clipboard operations
        function fromClipboard() {
            navigator.clipboard.readText()
                .then(text => {
                    if (editor.getValue() !== editorContent &&
                        !confirm('Unsaved changes will be lost. Continue?')) {
                        return;
                    }
                    editor.setValue(text, -1);
                    editorContent = text;
                    updateStatus('Content pasted from clipboard', 'success');
                })
                .catch(error => {
                    updateStatus('Error: ' + error.message, 'error');
                });
        }

        function appendClipboard() {
            navigator.clipboard.readText()
                .then(text => {
                    const currentContent = editor.getValue();
                    editor.setValue(currentContent + '\n' + text, -1);
                    updateStatus('Content appended from clipboard', 'success');
                })
                .catch(error => {
                    updateStatus('Error: ' + error.message, 'error');
                });
        }

        function toClipboard() {
            navigator.clipboard.writeText(editor.getValue())
                .then(() => updateStatus('Copied to clipboard', 'success'))
                .catch(error => updateStatus('Error: ' + error.message, 'error'));
        }

        // Backup operations
        function fromBackup() {
            const filename = document.getElementById('editorFilename').value;
            if (!filename) return updateStatus('Filename required', 'error');

            fetch('main.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=getBackup&filename=' + encodeURIComponent(filename)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        editor.setValue(result.content, -1);
                        if (result.version) {
                            currentVersion = result.version;
                            updateVersionDisplay();
                        }
                        updateStatus('Restored from backup: ' + result.backupFilename, 'success');
                    } else {
                        updateStatus(result.message, 'error');
                    }
                });
        }

        function fromBackupManager() {
            if (editor.getValue() !== editorContent) {
                if (!confirm('Unsaved changes detected. Continue?')) return;
            }
            window.location.href = 'nb-archive-manager.php';
        }

        // File Operations
        function loadDependencyFile(filePath) {
            fetch(filePath)
                .then(response => response.text())
                .then(content => {
                    addNewTab(filePath.split('/').pop(), content);
                    updateStatus('Loaded dependency: ' + filePath, 'success');
                })
                .catch(() => updateStatus('Failed to load: ' + filePath, 'error'));
        }

        function detectCssDependency(content, fileName) {
            const matches = content.match(/href=["']([^"']+\.css)["']/g);
            if (matches && fileName.endsWith('.php')) {
                matches.forEach(match => {
                    const cssFile = match.match(/["']([^"']+)["']/)[1];
                    if (cssFile === 'shared-styles.css') {
                        loadDependencyFile(cssFile);
                    }
                });
            }
        }

        function openFileRequester() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.php,.css,.js,.html,.txt';
            input.onchange = function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        editor.setValue(e.target.result, -1);
                        document.getElementById('editorFilename').value = file.name;
                        updateStatus('File loaded: ' + file.name, 'success');
                        editorContent = editor.getValue();
                        setEditorMode(file.name);
                        detectCssDependency(e.target.result, file.name);
                    };
                    reader.readAsText(file);
                }
            };
            input.click();
        }

        function openInNewWindow() {
            const filename = document.getElementById('editorFilename').value;
            if (!filename) return updateStatus('Save file first', 'error');

            const fileExt = filename.split('.').pop().toLowerCase();
            if (!['php', 'html', 'htm'].includes(fileExt)) {
                return updateStatus('Can only run PHP or HTML files', 'error');
            }

            window.open(filename, '_blank');
            updateStatus('Opened ' + filename + ' in new window', 'success');
        }

        // UI Operations
        function updateDisplayFilename() {
            const filename = document.getElementById('editorFilename').value;
            setEditorMode(filename);
        }

        function updateVersionAndDate() {
            const newFilename = document.getElementById('editorFilename').value.trim();
            const originalFilename = currentLoadedFilename;

            if (!originalFilename) {
                updateStatus('No file is currently open', 'error');
                return;
            }

            if (newFilename === originalFilename) {
                updateStatus('Filename unchanged', 'info');
                return;
            }

            if (!newFilename) {
                updateStatus('New filename cannot be empty', 'error');
                return;
            }

            if (!confirm(`Rename file from "${originalFilename}" to "${newFilename}"?`)) {
                document.getElementById('editorFilename').value = originalFilename;
                return;
            }

            saveFile(newFilename);
        }

        // Update the "From Template" button functionality
        function loadTemplate() {
            // Load the template file
            const templatePath = 'tool-template.php';
            fetch('main.php?file=' + encodeURIComponent(templatePath))
                .then(response => response.text())
                .then(content => {
                    editor.setValue(content, -1);
                    document.getElementById('editorFilename').value = 'tool-template.php';
                    updateStatus('Template loaded: tool-template.php', 'success');
                    editorContent = editor.getValue();
                    setEditorMode('tool-template.php');
                })
                .catch(() => updateStatus('Failed to load template', 'error'));
        }

        // Add a half button to load shared-styles.css in a new tab
        function loadSharedStyles() {
            const stylesPath = 'shared-styles.css';
            fetch('main.php?file=' + encodeURIComponent(stylesPath))
                .then(response => response.text())
                .then(content => {
                    addNewTab('shared-styles.css', content);
                    updateStatus('Styles loaded: shared-styles.css', 'success');
                })
                .catch(() => updateStatus('Failed to load styles', 'error'));
        }

        // Editor controls
        let currentTheme = 'dracula';
        let editors = {};
        let activeTab = 'untitled';

        function gotoTop() {
            editor.gotoLine(1);
            editor.focus();
        }

        function gotoBottom() {
            const lastLine = editor.session.getLength();
            editor.gotoLine(lastLine);
            editor.focus();
        }

        function toggleFullscreen() {
            const editorContainer = document.querySelector('.editor-container');
            const isFullscreen = editorContainer.style.position === 'fixed';

            if (isFullscreen) {
                // Exit fullscreen
                editorContainer.style.position = 'relative';
                editorContainer.style.top = '';
                editorContainer.style.left = '';
                editorContainer.style.right = '';
                editorContainer.style.bottom = '';
                editorContainer.style.zIndex = '';
                editorContainer.style.height = 'calc(100vh - 320px)';
                document.body.style.overflow = '';
            } else {
                // Enter fullscreen
                editorContainer.style.position = 'fixed';
                editorContainer.style.top = '0';
                editorContainer.style.left = '0';
                editorContainer.style.right = '0';
                editorContainer.style.bottom = '0';
                editorContainer.style.zIndex = '9999';
                editorContainer.style.height = '100vh';
                document.body.style.overflow = 'hidden';
            }

            // Toggle button icon
            const icon = document.querySelector('button[onclick="toggleFullscreen()] i');
            icon.classList.toggle('fa-expand');
            icon.classList.toggle('fa-compress');

            // Resize editor
            editor.resize();
        }

        // Version tracking
        let currentVersion = 1;

        function initNewEditor(containerId, filename) {
            const newEditor = ace.edit(containerId);
            newEditor.setTheme(`ace/theme/${currentTheme}`);
            newEditor.session.setMode("ace/mode/php");
            newEditor.setOptions({
                enableBasicAutocompletion: true,
                enableSnippets: true,
                enableLiveAutocompletion: true
            });
            editors[filename] = newEditor;
            return newEditor;
        }

        function addNewTab(filename, content) {
            const tabList = document.querySelector('.tab-list');
            const newTab = document.createElement('div');
            newTab.className = 'tab';
            newTab.dataset.file = filename;

            newTab.innerHTML = `
                <span class="tab-title">${filename}</span>
                <button class="tab-close"><i class="fas fa-times"></i></button>
            `;

            tabList.insertBefore(newTab, tabList.querySelector('.new-tab'));

            // Create editor container
            const editorContainers = document.querySelector('.editor-containers');
            const newEditorContainer = document.createElement('div');
            newEditorContainer.className = 'editor-container hidden';
            newEditorContainer.id = `editor-${filename}`;
            editorContainers.appendChild(newEditorContainer);

            // Initialize editor
            const newEditor = initNewEditor(`editor-${filename}`, filename);
            if (content) {
                newEditor.setValue(content, -1);
            }

            switchToTab(filename);
        }

        function switchToTab(filename) {
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.toggle('active', tab.dataset.file === filename);
            });

            document.querySelectorAll('.editor-container').forEach(container => {
                container.classList.add('hidden');
            });

            document.getElementById(`editor-${filename}`).classList.remove('hidden');
            activeTab = filename;
            editors[filename].focus();
        }

        function saveTab(filename) {
            const editor = editors[filename];
            if (!editor) return;

            const content = editor.getValue();
            fetch('main.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=saveFile&filename=${encodeURIComponent(filename)}&content=${encodeURIComponent(content)}`
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    updateStatus(`Saved: ${filename}`, 'success');
                } else {
                    updateStatus(`Failed to save ${filename}: ${result.message}`, 'error');
                }
            });
        }

        function saveAllTabs() {
            Object.keys(editors).forEach(filename => saveTab(filename));
            updateStatus('All files saved', 'success');
        }

        function updateVersionDisplay() {
            const versionSpan = document.getElementById('versionNumber');
            if (versionSpan) {
                versionSpan.textContent = `(V${String(currentVersion).padStart(2, '0')})`;
            }
        }

        function getNextVersion(filename) {
            return fetch('main.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=getNextVersion&filename=${encodeURIComponent(filename)}`
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        currentVersion = result.version;
                        updateVersionDisplay();
                        return currentVersion;
                    }
                    return 1;
                })
                .catch(() => 1);
        }

        // Editor preferences and controls
        function loadEditorPreferences() {
            try {
                const prefs = JSON.parse(localStorage.getItem('aceEditorPrefs')) || {};

                // Apply saved preferences
                if (prefs.theme) {
                    currentTheme = prefs.theme;
                    editor.setTheme(`ace/theme/${currentTheme}`);
                }
                if (prefs.wrap !== undefined) {
                    editor.getSession().setUseWrapMode(prefs.wrap);
                }
                if (prefs.lineNumbers !== undefined) {
                    editor.renderer.setShowGutter(prefs.lineNumbers);
                }
                if (prefs.tabSize) {
                    editor.getSession().setTabSize(prefs.tabSize);
                }
                if (prefs.indentGuides !== undefined) {
                    editor.renderer.setShowInvisibles(prefs.indentGuides);
                }
            } catch (e) {
                console.error('Error loading preferences:', e);
            }
        }

        function saveEditorPreferences() {
            const prefs = {
                theme: currentTheme,
                wrap: editor.getSession().getUseWrapMode(),
                lineNumbers: editor.renderer.getShowGutter(),
                tabSize: editor.getSession().getTabSize(),
                indentGuides: editor.renderer.getShowInvisibles()
            };
            localStorage.setItem('aceEditorPrefs', JSON.stringify(prefs));
        }

        function toggleIndentGuides() {
            editor.renderer.setShowInvisibles(!editor.renderer.getShowInvisibles());
            updateStatus('Indent guides: ' + (editor.renderer.getShowInvisibles() ? 'ON' : 'OFF'), 'info');
            saveEditorPreferences();
        }

        // Add comment line
        function addCommentLine() {
            const pos = editor.getCursorPosition();
            const line = "<!-- ---------------------------------------- -->";
            editor.session.insert(pos, line);
            editor.focus();
        }

        // Enable worker for specific file types
        function setEditorMode(filename) {
            const fileExtension = filename.split('.').pop().toLowerCase();
            let mode = "ace/mode/php"; // Default mode
            let useWorker = true; // Enable syntax checking by default

            if (fileExtension === 'css') {
                mode = "ace/mode/css";
            } else if (fileExtension === 'js') {
                mode = "ace/mode/javascript";
            } else if (fileExtension === 'json') {
                mode = "ace/mode/json";
            } else if (fileExtension === 'html' || fileExtension === 'htm') {
                mode = "ace/mode/html";
            } else if (fileExtension === 'txt') {
                mode = "ace/mode/text";
                useWorker = false; // Disable for plain text
            }

            editor.session.setMode(mode);
            editor.session.setUseWorker(useWorker);
        }

        function toggleTheme() {
            currentTheme = currentTheme === 'monokai' ? 'chrome' : 'monokai';
            editor.setTheme(`ace/theme/${currentTheme}`);
            updateStatus(`Theme: ${currentTheme}`, 'info');
            saveEditorPreferences();
        }

        function toggleWrap() {
            const wrap = !editor.getSession().getUseWrapMode();
            editor.getSession().setUseWrapMode(wrap);
            updateStatus('Word wrap: ' + (wrap ? 'ON' : 'OFF'), 'info');
            saveEditorPreferences();
        }

        function toggleLineNumbers() {
            editor.renderer.setShowGutter(!editor.renderer.getShowGutter());
            updateStatus('Line numbers: ' + (editor.renderer.getShowGutter() ? 'ON' : 'OFF'), 'info');
            saveEditorPreferences();
        }

        // Export current tab
        function exportCurrentTab() {
            const currentTab = tabManager.getCurrentTab();
            if (!currentTab) {
                updateStatus('No active tab to export', 'error');
                return;
            }

            const filename = currentTab.filename || 'untitled.txt';
            const content = currentTab.session.getValue();
            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(url);
            updateStatus(`Exported: ${filename}`, 'success');
        }

        // Initialize keyboard shortcuts at startup
        function initializeKeyboardShortcuts() {
            editor.commands.addCommand({
                name: 'save',
                bindKey: {win: 'Ctrl-S', mac: 'Command-S'},
                exec: () => {
                    saveFile();
                    updateStatus('File saved using Ctrl+S shortcut', 'success');
                }
            });

            editor.commands.addCommand({
                name: 'toggleWrap',
                bindKey: {win: 'Alt-W', mac: 'Alt-W'},
                exec: () => toggleWrap()
            });

        }

        // Tab Management
        class EditorTab {
            constructor(id, filename = 'untitled', content = '') {
                this.id = id;
                this.filename = filename;
                this.content = content;
                this.session = ace.createEditSession(content);
                this.hasUnsavedChanges = false;
            }
        }

        const tabManager = {
            tabs: [],
            activeTab: null,

            createTab(filename = 'untitled', content = '') {
                const id = 'tab_' + Math.random().toString(36).substr(2, 9);
                const tab = new EditorTab(id, filename, content);
                this.tabs.push(tab);

                // Initialize editor session
                if (this.tabs.length === 1) {
                    editor.setValue(content, -1);
                }

                this.renderTabs();
                this.switchToTab(id);
                return tab;
            },

            closeTab(id) {
                const tab = this.tabs.find(t => t.id === id);
                if (tab && tab.hasUnsavedChanges) {
                    if (!confirm('This tab has unsaved changes. Close anyway?')) {
                        return;
                    }
                }

                this.tabs = this.tabs.filter(t => t.id !== id);
                if (this.activeTab === id) {
                    this.switchToTab(this.tabs[this.tabs.length - 1]?.id);
                }
                this.renderTabs();
            },

            switchToTab(id) {
                const tab = this.tabs.find(t => t.id === id);
                if (!tab) return;

                this.activeTab = id;
                editor.setSession(tab.session);
                document.getElementById('editorFilename').value = tab.filename;
                this.renderTabs();

                // Update file mode
                setEditorMode(tab.filename);
            },

            renderTabs() {
                const tabList = document.querySelector('.tab-list');
                const newTabBtn = document.querySelector('.new-tab');

                // Clear existing tabs but keep new tab button
                tabList.innerHTML = '';

                this.tabs.forEach(tab => {
                    const tabEl = document.createElement('div');
                    tabEl.className = `tab${tab.id === this.activeTab ? ' active' : ''}`;
                    tabEl.dataset.id = tab.id;

                    const title = document.createElement('span');
                    title.className = 'tab-title';
                    title.textContent = tab.filename + (tab.hasUnsavedChanges ? ' •' : '');

                    const closeBtn = document.createElement('button');
                    closeBtn.className = 'tab-close';
                    closeBtn.innerHTML = '<i class="fas fa-times"></i>';
                    closeBtn.onclick = (e) => {
                        e.stopPropagation();
                        this.closeTab(tab.id);
                    };

                    tabEl.appendChild(title);
                    tabEl.appendChild(closeBtn);
                    tabEl.onclick = () => this.switchToTab(tab.id);
                    tabList.appendChild(tabEl);
                });

                tabList.appendChild(newTabBtn);
            },

            getCurrentTab() {
                return this.tabs.find(t => t.id === this.activeTab);
            }
        };

        // Initialize application
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize editors
            window.editor = ace.edit("editor");
            window.editor2 = ace.edit("editor-secondary");

            // Clear status box and show ready message
            document.getElementById('statusBox').innerHTML = '';
            updateStatus('Editor Ready', 'success');

            // Configure editors with dark theme
            [editor, editor2].forEach(ed => {
                ed.setTheme("ace/theme/dracula");
                ed.session.setMode("ace/mode/php");
                ed.session.setUseWrapMode(true);
                ed.setOptions({
                    enableBasicAutocompletion: true,
                    enableSnippets: true,
                    enableLiveAutocompletion: true,
                    showPrintMargin: false,
                    fontSize: "14px",
                    displayIndentGuides: true,
                    highlightActiveLine: true,
                    showGutter: true,
                    useSoftTabs: true,
                    tabSize: 4
                });
                ed.renderer.setScrollMargin(10, 10);
                ed.setValue('');
                ed.clearSelection();
            });

            editor.focus();

            // Initialize editor state
            loadEditorPreferences();
            tabManager.createTab();

            // Setup drag and drop functionality
            function setupDragAndDrop() {
                const statusBox = document.getElementById('statusBox');

                statusBox.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    statusBox.classList.add('drag-over');
                });

                statusBox.addEventListener('dragleave', () => {
                    statusBox.classList.remove('drag-over');
                });

                statusBox.addEventListener('drop', (e) => {
                    e.preventDefault();
                    statusBox.classList.remove('drag-over');
                    if (e.dataTransfer.files.length > 0) {
                        const file = e.dataTransfer.files[0];
                        const fileExt = file.name.split('.').pop().toLowerCase();
                        const allowedExts = ['php', 'css', 'js', 'html', 'htm', 'txt', 'json'];

                        if (!allowedExts.includes(fileExt)) {
                            updateStatus(`Unsupported file type: ${fileExt}. Please use: ${allowedExts.join(', ')}`, 'error');
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = function(e) {
                            editor.setValue(e.target.result, -1);
                            document.getElementById('editorFilename').value = file.name;
                            updateStatus('File loaded: ' + file.name, 'success');
                            editorContent = editor.getValue();
                            setEditorMode(file.name);
                        };
                        reader.readAsText(file);
                    }
                });
            }

            // Set up event handlers
            setupDragAndDrop();
            initializeKeyboardShortcuts();
            document.querySelector('.new-tab').onclick = () => tabManager.createTab();
            document.getElementById('btnRestart').onclick = () => location.reload();

            // Handle iframe mode
            // Set initial iframe state
            function setIframeState() {
                const isInIframe = window.location.href.includes('?app=');
                const body = document.body;
                const menuContainer = document.querySelector('.menu-container');

                if (isInIframe) {
                    body.classList.add('in-iframe');
                    menuContainer.classList.add('in-iframe');
                } else {
                    body.classList.remove('in-iframe');
                    menuContainer.classList.remove('in-iframe');
                }
            }

            // Set initial state
            setIframeState();

            // Setup navigation warning
            window.addEventListener('beforeunload', (e) => {
                if (editor.getValue() !== editorContent) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            // Load file from URL if specified
            const fileToLoad = new URLSearchParams(window.location.search).get('file');
            if (fileToLoad) {
                getNextVersion(fileToLoad);
                if (fileToLoad.endsWith('.css')) {
                    // CSS files open in second tab
                    fetch(fileToLoad)
                        .then(response => response.text())
                        .then(content => {
                            editor2.setValue(content, -1);
                            editor2.session.setMode("ace/mode/css");
                            updateStatus('CSS file loaded in second tab: ' + fileToLoad, 'success');
                        });
                } else {
                    aceLoadFile(fileToLoad);
                }
            }
        });

        // Set default theme and status box message
        document.addEventListener('DOMContentLoaded', function() {
            const editor = ace.edit("editor");
            editor.setTheme("ace/theme/monokai"); // Default to dark theme
            editor.session.setMode("ace/mode/javascript"); // Example mode, adjust as needed

            // Ensure the status box is functional
            const statusBox = document.querySelector('.status-box');
            if (statusBox) {
                statusBox.textContent = "Editor loaded successfully.";
            }
        });
    </script>
</body>

</html>
