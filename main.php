<?php

/**
 * NetBound Tools: main.php
 * Part of the NetBound Tool Suite
 * Written by Orange Jeff and AI Tools in 2024 and 2025
 * https://netbound.ca jeff@frogstar.com
 * Free to modify and share
 * 
 * A lightweight PHP-based file editor with backup functionality.
 * Features:
 * - File loading, saving, and deletion.
 * - Backup creation and restoration.
 * - Mobile-friendly interface with Ace Editor.
 * 
 * Dependencies:
 * - Ace Editor (https://ace.c9.io/)
 * - Font Awesome (https://fontawesome.com/)
 * - PHP 7.0+ with file and session support.
 * - main-styles.css, editor-init.js, file-ops.js, shared-utils.js
 */
// Error Reporting and Session Start
// Enable full error reporting for debugging and ensure sessions are started if not already.
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start session if not already started
}

// Ensure REQUEST_METHOD is set
// Fallback to 'GET' if REQUEST_METHOD is not set (e.g., CLI execution).
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}

// Set up directory and backup directory
// Define the main directory and backup directory paths.
$dir = __DIR__;
$backupDir = $dir . '/backups/';

// Create backup directory if it doesn't exist
// Attempt to create the backup directory with appropriate permissions.
if (!is_dir($backupDir)) {
    if (!@mkdir($backupDir, 0755, true)) {
        die('Failed to create backup directory. Check permissions.');
    }
}

// Session namespace
// Create a unique session namespace based on the directory path to avoid conflicts.
$sessionNamespace = 'netbound_' . md5(__DIR__);

/* -------------------------------------------------------------------------- */
/*                            Cache Control Headers                           */
/* -------------------------------------------------------------------------- */
// Prevent caching of the page to ensure fresh content is always served.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

/* -------------------------------------------------------------------------- */
/*                                 GET Handlers                               */
/* -------------------------------------------------------------------------- */

// File Serving Handler
// Serve the requested file if it exists and has a valid filename.
if (isset($_GET['file'])) {
    $requestedFile = basename($_GET['file']);
    // Validate filename to prevent path traversal attacks.
    if (preg_match('/^[a-zA-Z0-9._-]+$/', $requestedFile) && file_exists($requestedFile)) {
        echo file_get_contents($requestedFile);
        exit;
    }
}

// Sort Mode Toggle Handler
// Toggle between sorting files by date or name.
if (isset($_GET['toggleSort'])) {
    $currentSort = $_SESSION['sortBy'] ?? 'date';
    $sortBy = ($currentSort === 'date') ? 'name' : 'date';
    $_SESSION['sortBy'] = $sortBy;
    exit;
} else {
    $sortBy = $_SESSION['sortBy'] ?? 'date';
}

// Get Folders Handler
// Retrieve and return a list of directories in the current folder.
if (isset($_GET['getFolders'])) {
    $folders = array_filter(glob('*'), 'is_dir');
    echo json_encode($folders);
    exit;
}

// Get File List Handler
// Retrieve and return a list of files, sorted by date or name.
if (isset($_GET['getFileList'])) {
    $files = glob($dir . '/*.*', GLOB_BRACE);

    if (!empty($files)) {
        if ($sortBy === 'date') {
            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });
        } else {
            natcasesort($files);
            $files = array_values($files);
        }
    }

    ob_start();

    if (!empty($files)) {
        foreach ($files as $file) {
            $filename = basename($file);
            $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);

            // Skip backup files (files with BAK in the name).
            if (preg_match('/\(BAK-\w{3}\d{2}-S\d+\)\.\w+$/', $filename)) {
                continue;
            }

            if ($filename === '.' || $filename === '..') continue;

            $isCurrentEdit = ($filename === ($_GET['edit'] ?? ''));

            // Generate HTML for each file entry with controls.
            echo "<li class='file-entry " . ($isCurrentEdit ? "current-edit" : "") . "'>
                <div class='file-controls'>
                    <button onclick='loadFile(\"" . addslashes($filename) . "\")' title='Edit File'><i class='fas fa-pencil-alt'></i></button>
                    <a href='#' onclick='openInNewTab(\"" . htmlspecialchars($filename, ENT_QUOTES) . "\")' title='Run File'><i class='fas fa-play'></i></a>
                    <button onclick='confirmDelete(\"" . htmlspecialchars($filename) . "\")' title='Delete File'><i class='fas fa-trash'></i></button>
                </div>
                <a onclick='loadFile(\"" . addslashes($filename) . "\"); return false;' href='#' class='filename'>" . htmlspecialchars($filename, ENT_QUOTES) . "</a>
            </li>";
        }
    } else {
        echo "<li class='no-files'>No files available.</li>";
    }
    $fileListHTML = ob_get_clean();
    echo $fileListHTML;
    exit;
}

/* -------------------------------------------------------------------------- */
/*                                POST Handler                                */
/* -------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        // Save file content and create a backup if changes are detected.
        $filename = basename($_POST['filename'] ?? '');
        $content = $_POST['content'] ?? '';

        if (empty($filename)) {
            echo json_encode(['status' => 'error', 'message' => 'Filename required']);
        } else {
            $filePath = __DIR__ . '/' . $filename;
            $originalContent = file_exists($filePath) ? file_get_contents($filePath) : '';

            if ($content !== $originalContent) {
                // Backup logic: Create a versioned backup of the file.
                $backupFilename = basename($filename);
                $fileExtension = pathinfo($backupFilename, PATHINFO_EXTENSION);
                $backupFilename = substr($backupFilename, 0, strlen($backupFilename) - strlen($fileExtension) - 1);
                $version = 1;
                while (file_exists($backupDir . $backupFilename . '(V' . $version . ').' . $fileExtension)) {
                    $version++;
                }
                $backupFilename = $backupFilename . '(V' . $version . ').' . $fileExtension;

                if (copy($filePath, $backupDir . $backupFilename)) {
                    if (file_put_contents($filePath, $content, LOCK_EX) !== false) {
                        echo json_encode(['status' => 'success', 'message' => 'File saved: ' . $filename . ' (backup created: ' . $backupFilename . ')']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Save failed: ' . $filename]);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Backup failed: ' . $filename]);
                }
            } else {
                echo json_encode(['status' => 'info', 'message' => 'No changes detected, file not saved: ' . $filename]);
            }
        }
        exit;
    } elseif ($action === 'getBackup') {
        // Retrieve the latest backup of a file.
        $filename = basename($_POST['filename'] ?? '');
        if (empty($filename)) {
            echo json_encode(['status' => 'error', 'message' => 'Filename required']);
            exit;
        }
        $backupFilename = '';
        $version = 1;
        while (file_exists($backupDir . $filename . '(v' . $version . ').php')) {
            $backupFilename = $backupDir . $filename . '(v' . $version . ').php';
            $version++;
        }
        if ($backupFilename) {
            $content = file_get_contents($backupFilename);
            echo json_encode(['status' => 'success', 'content' => $content, 'backupFilename' => basename($backupFilename)]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No backups found for this file.']);
        }
        exit;
    } elseif ($action === 'delete') {
        // Delete a file after confirmation.
        $filename = basename($_POST['filename'] ?? '');

        $response = ['status' => 'error', 'message' => ''];

        if (empty($filename)) {
            $response['message'] = 'Filename is required for deletion.';
        } else {
            $filePath = __DIR__ . '/' . $filename;

            if (!file_exists($filePath)) {
                $response['message'] = 'File does not exist: ' . $filename;
            } elseif (is_dir($filePath)) {
                $response['message'] = 'Cannot delete directories: ' . $filename;
            } else {
                if (unlink($filePath)) {
                    $response['status'] = 'success';
                    $response['message'] = 'File deleted successfully: ' . $filename;
                } else {
                    $response['message'] = 'Failed to delete the file: ' . $filename;
                }
            }
        }

        echo json_encode($response);
        exit;
    }
}

/* -------------------------------------------------------------------------- */
/*                                HTML Output                                 */
/* -------------------------------------------------------------------------- */

// Set the current filename and load its content if requested.
$currentFilename = isset($_GET['file']) ? basename($_GET['file']) : '';
$content = '';
if (isset($_GET['file']) && !empty($_GET['file'])) {
    $content = file_get_contents(basename($_GET['file']));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./main-styles.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <!-- Header Section -->
    <div class="header">
        <div class="left-section">
            <button id="mobileMenuToggle" class="mobile-menu-toggle" title="Toggle Menu">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="menu-title">NetBound Tools</h2>

            <button id="menuNewBtn" class="header-button" title="New" onclick="createNewFile()">
                <i class="fas fa-file"></i>
            </button>
            <button id="menuRefreshBtn" class="header-button" title="Reload Menu">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button id="menuSortBtn" class="header-button" title="Toggle Sort">
                <i class="fas fa-sort-alpha-down"></i>
            </button>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Menu Panel -->
        <div class="menu" id="menuPanel">
            <div class="menu-content">
                <ul class="file-list">
                    <?php
                    // Generate the file list for the menu.
                    $files = glob($dir . '/*.*', GLOB_BRACE);

                    if (!empty($files)) {
                        usort($files, function ($a, $b) {
                            return filemtime($b) - filemtime($a);
                        });
                    }

                    if (!empty($files)) {
                        foreach ($files as $file) {
                            $filename = basename($file);
                            $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);

                            if (preg_match('/\(BAK-\w{3}\d{2}-S\d+\)\.\w+$/', $filename)) {
                                continue;
                            }

                            if ($filename === '.' || $filename === '..') continue;

                            $isCurrentEdit = ($filename === $currentFilename);

                            echo "<li class='file-entry " . ($isCurrentEdit ? "current-edit" : "") . "'>
                                <div class='file-controls'>
                                    <button onclick='loadFile(\"" . addslashes($filename) . "\")' title='Edit File'><i class='fas fa-pencil-alt'></i></button>
                                    <a href='#' onclick='openInNewTab(\"" . htmlspecialchars($filename, ENT_QUOTES) . "\")' title='Run File'><i class='fas fa-play'></i></a>
                                    <button onclick='confirmDelete(\"" . htmlspecialchars($filename) . "\")' title='Delete File'><i class='fas fa-trash'></i></button>
                                </div>
                                <a onclick='loadFile(\"" . addslashes($filename) . "\"); return false;' href='#' class='filename'>" . htmlspecialchars($filename, ENT_QUOTES) . "</a>
                            </li>";
                        }
                    }
                    ?>
                </ul>
            </div>
        </div>

        <!-- Editor Section -->
        <div class="menu-container">
            <div class="editor-view">
                <div class="editor" id="editorSection">
                    <div class="editor-header">
                        <div class="header-top">
                            <h1 class="editor-title">
                                NetBound Editor: <?php echo date("F j Y", filemtime(__FILE__)); ?></h1>
                        </div>
                        <div class="label-line">
                            <span class="info-label">Filename:</span>
                            <input type="text" id="editorFilename" class="info-input" value="" onchange="updateDisplayFilename()">
                            <button type="button" class="command-button" onclick="updateVersionAndDate()">
                                <i class="fas fa-sync-alt"></i> Rename
                            </button>
                        </div>
                    </div>
                    <div class="button-group">
                        <button type="button" class="command-button" onclick="openFileRequester()" title="From File">
                            <i class="fas fa-folder-open"></i> From File
                        </button>

                        <div class="split-button">
                            <div class="main-part" onclick="fromClipboard()" title="Replace content with clipboard">
                                <i class="fas fa-clipboard"></i> From Clipboard
                            </div>
                            <div class="append-part" onclick="appendClipboard()" title="Append clipboard to current content">
                                <i class="fas fa-plus"></i>
                            </div>
                        </div>
                        <div class="split-button">
                            <div class="main-part" onclick="fromBackup()" title="Load content from latest backup">
                                <i class="fas fa-history"></i> From Backup
                            </div>
                            <div class="append-part" onclick="fromBackupManager()" title="Open Backup Manager">
                                <i class="fas fa-plus"></i>
                            </div>
                        </div>
                    </div>

                    <div class="persistent-status-bar" id="statusBar"></div>
                    <form method="POST" class="edit-form" style="display:flex;flex-direction:column;height:100%;" id="editorForm">
                        <div class="editor-container">
                            <div id="editor"></div>
                        </div>
                        <div class="button-row">
                            <div class="button-group">
                                <div class="split-button">
                                    <div class="main-part" onclick="saveFile()">
                                        <i class="fas fa-upload"></i> Save
                                    </div>
                                    <div class="append-part" onclick="saveAs()" title="Save file to local machine">
                                        <i class="fas fa-download"></i>
                                    </div>
                                </div>
                                <div class="split-button">
                                    <div class="main-part" onclick="toClipboard()" title="Copy all editor content to clipboard">
                                        <i class="fas fa-clipboard"></i> To Clipboard
                                    </div>
                                </div>
                                <button type="button" name="run" class="command-button" title="Run"
                                    onclick="window.open(document.getElementById('editorFilename').value, '_blank')">
                                    <i class="fas fa-play"></i> Run
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="backup-view">
                <iframe src="" style="width:100%; height:100%; border:none;"></iframe>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="nav-buttons">
            <button onclick="scrollToTop()" title="Scroll to Top">
                <i class="fas fa-arrow-up"></i>
            </button>
            <button onclick="scrollToBottom()" title="Scroll to Bottom">
                <i class="fas fa-arrow-down"></i>
            </button>
        </div>

        <!-- External Scripts -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ace.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ext-language_tools.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ext-searchbox.js"></script>
        <script src="main-editor-init.js"></script>
        <script src="main-shared-utils.js"></script>
        <script src="main-file-ops.js"></script>

        <!-- Inline Scripts -->
        <script>
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

            // Set initial content if exists
            // Set initial content if exists
            <?php if (!empty($content)): ?>
                editor.setValue(<?php echo json_encode($content); ?>);
                editorContent = editor.getValue();
            <?php endif; ?>

            // Function to load a file into the editor
            function loadFile(filename) {
                const editorView = document.querySelector('.editor-view');
                const backupView = document.querySelector('.backup-view');

                // Restore editor view if hidden
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

                        // Set Ace editor mode based on file extension
                        const fileExtension = filename.split('.').pop().toLowerCase();
                        let mode = "ace/mode/php";
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
                        document.body.classList.remove('menu-visible');
                    });
            }

            // Function to save the current file
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

            // Function to save the file locally
            function saveAs() {
                const defaultName = document.getElementById('editorFilename').value || 'newfile.php';
                const content = editor.getValue();
                const blob = new Blob([content], {
                    type: 'text/plain'
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = defaultName;
                a.click();
                URL.revokeObjectURL(url);
                updateStatus('File saved to local machine', 'success');
            }

            // Toggle Sort Handler
            document.getElementById('menuSortBtn').addEventListener('click', function() {
                const sortIcon = this.querySelector('i');
                sortIcon.classList.toggle('fa-sort-alpha-down');
                sortIcon.classList.toggle('fa-clock');
                fetch('main.php?toggleSort=1')
                    .then(() => {
                        if (editor.getValue() !== editorContent) {
                            if (!confirm('Unsaved changes detected. Continue?')) return;
                        }
                        document.getElementById('editorSection').style.display = 'flex';
                        fetch('main.php?getFileList=1')
                            .then(response => response.text())
                            .then(html => {
                                document.querySelector('.file-list').innerHTML = html;
                            });
                    });
            });

            // Refresh Handler
            document.getElementById('menuRefreshBtn').addEventListener('click', function() {
                fetch('main.php?getFileList=1')
                    .then(() => {
                        if (editor.getValue() !== editorContent) {
                            if (!confirm('Unsaved changes detected. Continue?')) return;
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        document.querySelector('.file-list').innerHTML = html;
                        updateStatus('File list refreshed', 'success');
                    });
            });

            // Mobile Menu Toggle
            document.getElementById('mobileMenuToggle').addEventListener('click', function() {
                if (editor.getValue() !== editorContent) {
                    if (!confirm('Unsaved changes detected. Continue?')) return;
                }

                const editorView = document.querySelector('.editor-view');
                const backupView = document.querySelector('.backup-view');

                editorView.classList.toggle('hidden');
                backupView.classList.toggle('active');
                document.body.classList.toggle('menu-visible');
            });

            // New File Handler
            function createNewFile() {
                const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
                const newFilename = `newfile_${timestamp}.php`;
                if (editor.getValue() !== editorContent) {
                    if (!confirm('Unsaved changes detected. Continue?')) return;
                }
                document.getElementById('editorSection').style.display = 'flex';
                editor.setValue('');
                document.getElementById('editorFilename').value = newFilename;
                document.getElementById('editorFilename').onclick = function(event) {
                    openInNewWindow(this.value, event);
                };
                updateStatus(STATUS_MESSAGES.file.new(newFilename), 'success');
                editorContent = editor.getValue();
            }

            // Function to paste content from clipboard
            function fromClipboard() {
                if (editor.getValue() !== editorContent) {
                    if (!confirm('Unsaved changes detected. Continue?')) return;
                }
                document.getElementById('editorSection').style.display = 'flex';
                const filename = document.getElementById('editorFilename').value;
                navigator.clipboard.readText().then(text => {
                    if (editor.getValue() !== editorContent) {
                        if (!confirm('Unsaved changes detected. Continue?')) return;
                    }
                    document.getElementById('editorSection').style.display = 'flex';
                    editor.setValue(text);
                    updateStatus(STATUS_MESSAGES.clipboard.paste(filename), 'success');
                    editorContent = editor.getValue();
                });
            }

            // Function to copy content to clipboard
            function toClipboard() {
                const filename = document.getElementById('editorFilename').value;
                navigator.clipboard.writeText(editor.getValue()).then(() => {
                    updateStatus(STATUS_MESSAGES.clipboard.copy(filename), 'success');
                });
            }

            // Function to update the status bar
            function updateStatus(message, type = 'info') {
                const statusBar = document.getElementById('statusBar');
                const statusMessage = document.createElement('div');
                statusMessage.className = `status-message ${type}`;
                statusMessage.textContent = message;
                statusBar.insertBefore(statusMessage, statusBar.firstChild);

                // Keep only last 5 messages
                while (statusBar.children.length > 5) {
                    statusBar.removeChild(statusBar.lastChild);
                }
            }

            // Function to append clipboard content to the editor
            function appendClipboard() {
                navigator.clipboard.readText().then(text => {
                    if (editor.getValue() !== editorContent) {
                        if (!confirm('Unsaved changes detected. Continue?')) return;
                    }
                    document.getElementById('editorSection').style.display = 'flex';
                    const currentContent = editor.getValue();
                    editor.setValue(currentContent + '\n' + text);
                    updateStatus('Content appended from clipboard.', 'success');
                    editorContent = editor.getValue();
                }).catch(() => {
                    updateStatus('Failed to read from clipboard.', 'error');
                });
            }

            // Function to confirm file deletion
            function confirmDelete(filename) {
                if (confirm('Are you sure you want to delete ' + filename + '?')) {
                    if (editor.getValue() !== editorContent) {
                        if (!confirm('Unsaved changes detected. Continue?')) return;
                    }
                    document.getElementById('editorSection').style.display = 'flex';
                    fetch('main.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'action=delete&filename=' + encodeURIComponent(filename)
                        })
                        .then(response => response.json())
                        .then(result => {
                            updateStatus(result.message, result.status);
                            if (result.status === 'success') {
                                // Refresh the file list
                                fetch('main.php?getFileList=1')
                                    .then(response => response.text())
                                    .then(html => {
                                        document.querySelector('.file-list').innerHTML = html;
                                    });
                            }
                        });
                }
            }

            // Function to load content from the latest backup
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
                            updateStatus('Last backup restored. Not saved.', 'info');
                            editorContent = editor.getValue();
                        } else {
                            updateStatus(result.message, 'error');
                        }
                    });
            }

            // Function to open the backup manager
            function fromBackupManager() {
                if (editor.getValue() !== editorContent) {
                    if (!confirm('Unsaved changes detected. Continue?')) return;
                }
                document.getElementById('editorSection').style.display = 'none';
                document.querySelector('.menu-container').innerHTML = '<iframe id="backupManagerIframe" src="backup-manager.php"></iframe>';
                updateStatus('Backup manager loaded.', 'success');
            }

            // Prevent leaving the page with unsaved changes
            window.addEventListener('beforeunload', function(e) {
                if (editor.getValue() !== editorContent) {
                    e.preventDefault();
                    e.returnValue = '';
                    updateStatus('Unsaved changes detected.', 'error');
                }
            });

            // Status messages object
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

            // Function to open a file in a new window
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

            // Function to open a file in a new tab
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

            // Function to toggle between editor and backup views
            function toggleView() {
                const editorView = document.querySelector('.editor-view');
                const backupView = document.querySelector('.backup-view');

                editorView.classList.toggle('hidden');
                backupView.classList.toggle('active');
            }
        </script>
</body>

</html>