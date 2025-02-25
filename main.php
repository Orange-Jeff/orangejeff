<?php
/* -------------------------------------------------------------------------- */
/*                                 Core Setup                                   */
/*                                 V-INC                                       */
/* -------------------------------------------------------------------------- */
// Error Reporting and Session Start
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure REQUEST_METHOD is set
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}

// Set up directory and backup directory
$dir = __DIR__;
$backupDir = $dir . '/backups/';

// Create backup directory if it doesn't exist
if (!is_dir($backupDir)) {
    if (!@mkdir($backupDir, 0755, true)) {
        die('Failed to create backup directory. Check permissions.');
    }
}

// Session namespace
$sessionNamespace = 'netbound_' . md5(__DIR__);

/* -------------------------------------------------------------------------- */
/*                            Cache Control Headers                           */
/* -------------------------------------------------------------------------- */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

/* -------------------------------------------------------------------------- */
/*                                 GET Handlers                               */
/* -------------------------------------------------------------------------- */

// File Serving Handler
if (isset($_GET['file'])) {
    $requestedFile = basename($_GET['file']);
    // Validate filename to prevent path traversal
    if (preg_match('/^[a-zA-Z0-9._-]+$/', $requestedFile) && file_exists($requestedFile)) {
        echo file_get_contents($requestedFile);
        exit;
    }
}

// Sort Mode Toggle Handler
if (isset($_GET['toggleSort'])) {
    $currentSort = $_SESSION['sortBy'] ?? 'date';
    $sortBy = ($currentSort === 'date') ? 'name' : 'date';
    $_SESSION['sortBy'] = $sortBy;
    exit;
} else {
    $sortBy = $_SESSION['sortBy'] ?? 'date';
}

// Get Folders Handler (currently duplicates getFileList)
if (isset($_GET['getFolders'])) {
    $folders = array_filter(glob('*'), 'is_dir');
    echo json_encode($folders);
    exit;
}

// Get File List Handler
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

            if (preg_match('/\(BAK-\w{3}\d{2}-S\d+\)\.\w+$/', $filename)) {
                continue;
            }

            if ($filename === '.' || $filename === '..') continue;

            $isCurrentEdit = ($filename === ($_GET['edit'] ?? ''));

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
        $filename = basename($_POST['filename'] ?? '');
        $content = $_POST['content'] ?? '';

        if (empty($filename)) {
            echo json_encode(['status' => 'error', 'message' => 'Filename required']);
        } else {
            $filePath = __DIR__ . '/' . $filename;
            $originalContent = file_exists($filePath) ? file_get_contents($filePath) : '';

            if ($content !== $originalContent) {
                // Backup logic
                $backupFilename = basename($filename);
                $version = 1;
                while (file_exists($backupDir . $backupFilename . '(V' . $version . ').php')) {
                    $version++;
                }
                $backupFilename = $backupFilename . '(V' . $version . ').php';

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
// Add this to the POST handlers section

/* -------------------------------------------------------------------------- */
/*                                HTML Output                                 */
/* -------------------------------------------------------------------------- */

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
    <title>NetBound Tools Menu</title>

    <link rel="stylesheet" href="./main-styles.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <div class="header">
        <div class="left-section">
            <button id="mobileMenuToggle" class="mobile-menu-toggle" title="Toggle Menu">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="menu-title">NetBound Tools</h2>
            <button id="menuUpdateBtn" class="header-button" title="Transfer files to server">
                <i class="fas fa-paper-plane"></i>
            </button>
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
    <div class="container">
        <div class="menu" id="menuPanel">
            <div class="menu-content">
                <ul class="file-list">
                    <?php
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
                            <div class="main-part" data-action="fromClipboard">
                                <i class="fas fa-clipboard"></i> From Clipboard
                            </div>
                            <div class="append-part" data-action="appendClipboard">
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
                                    onclick="openInNewTab(document.getElementById('editorFilename').value)">
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
            <script>
                let editorContent = '';

                function openFileRequester() {
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.accept = '.php,.css,.js,.html,.txt'; // Specify allowed file types
                    input.onchange = function(event) {
                        const file = event.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                editor.setValue(e.target.result, -1);
                                document.getElementById('editorFilename').value = file.name;
                                updateStatus(`File loaded: ${file.name}`, 'success');
                                editorContent = editor.getValue();

                                // Set Ace editor mode based on file extension
                                const fileExtension = file.name.split('.').pop().toLowerCase();
                                let mode = "ace/mode/php"; // Default mode
                                if (fileExtension === 'css') {
                                    mode = "ace/mode/css";
                                } else if (fileExtension === 'js') {
                                    mode = "ace/mode/javascript";
                                } else if (fileExtension === 'json') {
                                    mode = "ace/mode/json";
                                }
                                editor.session.setMode(mode);
                            };
                            reader.readAsText(file);
                        }
                    };
                    input.click();
                }
            </script>
        </div>
        <div class="nav-buttons">
            <button onclick="scrollToTop()" title="Scroll to Top">
                <i class="fas fa-arrow-up"></i>
            </button>
            <button onclick="scrollToBottom()" title="Scroll to Bottom">
                <i class="fas fa-arrow-down"></i>
            </button>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ace.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ext-language_tools.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ext-searchbox.js"></script>
        <script>
            // Add near the top of your script section
            const STATUS_MESSAGES = {
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
            <?php if (!empty($content)): ?>
                editor.setValue(<?php echo json_encode($content); ?>);
                editorContent = editor.getValue();
            <?php endif; ?>

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
            } // Consolidated save functions:
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
            document.getElementById('menuUpdateBtn').addEventListener('click', function() {
                const isLocal = ['127.0.0.1', '::1'].includes(location.hostname);
                const input = document.createElement('input');
                input.type = 'file';
                input.multiple = true;
                input.onchange = function(e) {
                    const formData = new FormData();
                    for (let file of e.target.files) {
                        formData.append('files[]', file);
                    }
                    formData.append('action', 'transferFiles');
                    formData.append('destination', window.location.pathname);

                    fetch('main.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(result => {
                            updateStatus(result.message, result.status === 'success' ? 'success' : 'error');
                            if (result.status === 'success') {
                                location.reload();
                            }
                        });
                };
                input.click();
            });

            // Toggle Sort Handler
            document.getElementById('menuSortBtn').addEventListener('click', function() {
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
                updateStatus(STATUS_MESSAGES.file.new(newFilename), 'success');
                editorContent = editor.getValue();
            }

            function fromClipboard() {
                navigator.clipboard.readText()
                    .then(text => {
                        if (editor.getValue() !== editorContent &&
                            !confirm('Unsaved changes will be lost. Continue?')) {
                            return;
                        }
                        editor.setValue(text, -1);
                        editorContent = text;
                        updateStatus(STATUS_MESSAGES.clipboard.paste(), 'success');
                    })
                    .catch(() => updateStatus('Failed to read clipboard', 'error'));
            }

            function appendClipboard() {
                navigator.clipboard.readText()
                    .then(text => {
                        const currentContent = editor.getValue();
                        editor.setValue(currentContent + '\n' + text, -1);
                        editorContent = editor.getValue();
                        updateStatus(STATUS_MESSAGES.clipboard.append(), 'success');
                    })
                    .catch(() => updateStatus('Failed to read clipboard', 'error'));
            }

            function toClipboard() {
                navigator.clipboard.writeText(editor.getValue())
                    .then(() => updateStatus(STATUS_MESSAGES.clipboard.copy(), 'success'))
                    .catch(() => updateStatus('Failed to copy to clipboard', 'error'));
            }

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

            // Replace the existing confirmDelete function
            function confirmDelete(filename) {
                if (confirm(`Are you sure you want to delete ${filename}?`)) {
                    fetch('main.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `action=delete&filename=${encodeURIComponent(filename)}`
                        })
                        .then(response => response.json())
                        .then(result => {
                            updateStatus(result.message, result.status);
                            if (result.status === 'success') {
                                document.querySelector('.file-list').innerHTML =
                                    document.querySelector('.file-list').innerHTML.replace(
                                        new RegExp(`<li[^>]*>${filename}</li>`), ''
                                    );
                                if (document.getElementById('editorFilename').value === filename) {
                                    editor.setValue('');
                                    document.getElementById('editorFilename').value = '';
                                }
                            }
                        });
                }
            }

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

            function fromBackupManager() {
                if (editor.getValue() !== editorContent) {
                    if (!confirm('Unsaved changes detected. Continue?')) return;
                }
                document.getElementById('editorSection').style.display = 'none';
                document.querySelector('.menu-container').innerHTML = '<iframe id="backupManagerIframe" src=""></iframe>';
                updateStatus('Backup manager loaded.', 'success');
            }
            window.addEventListener('beforeunload', function(e) {
                if (editor.getValue() !== editorContent) {
                    e.preventDefault();
                    e.returnValue = '';
                    updateStatus('Unsaved changes detected.', 'error');
                }
            });

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

            function toggleView() {
                const editorView = document.querySelector('.editor-view');
                const backupView = document.querySelector('.backup-view');

                editorView.classList.toggle('hidden');
                backupView.classList.toggle('active');
            }

            function refreshFileList() {
                fetch('main.php?getFileList=1')
                    .then(response => response.text())
                    .then(html => {
                        document.querySelector('.file-list').innerHTML = html;
                        updateStatus('File list refreshed', 'success');
                    })
                    .catch(() => updateStatus('Failed to refresh file list', 'error'));
            }

            // Listen for messages from the archive manager iframe
            window.addEventListener('message', function(event) {
                if (event.data && event.data.action === 'switchToEditor') {
                    const editorView = document.querySelector('.editor-view');
                    const backupView = document.querySelector('.backup-view');

                    // Switch back to editor view
                    editorView.classList.remove('hidden');
                    backupView.classList.remove('active');

                    // Show status message
                    if (event.data.status) {
                        updateStatus(event.data.status, 'success');
                    }
                }
            });
        </script>
</body>

</html>
