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
session_start();

/*
====================================================================================
  THIS IS A TEMPLATE-DERIVED FILE FOR ARCHIVE MANAGER. DO NOT MODIFY THE TEMPLATE.
  IF YOU NEED TO UPDATE THE TEMPLATE, EDIT tool-template.php INSTEAD.
====================================================================================
| NetBound Tools: Archive Manager
| Part of the NetBoundToolSuite NetBound.cad
| version  : 24.1.20
| author   : OrangeJeff@frogstar.com
| updated  : March 6 2025
+---------------------------------------------------------------------------- */

// Define base paths relative to current directory
define('LOCAL_PATH', __DIR__); // Current directory where script runs
define('SERVER_PATH', __DIR__); // Same as LOCAL_PATH since everything is in same directory
define('VSCODE_HISTORY_PATH', __DIR__ . '/.history');
define('NETBOUND_BACKUPS_PATH', __DIR__ . '/backups');

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function parseFileName($basename)
{
    $patterns = [
        '/^(.+?)\.(\w+)\(V(\d+)\)\.(\w+)$/i' => function ($matches) {
            return [
                'original' => $matches[1] . '.' . $matches[2],
                'version' => $matches[3],
                'base' => $matches[1],
                'ext' => $matches[2],
                'fullExt' => $matches[2] . '.' . $matches[4]
            ];
        },
        '/^(.+?)\(V(\d+)\)\.([^.]+)$/i' => function ($matches) {
            return [
                'original' => $matches[1] . '.' . $matches[3],
                'version' => $matches[2],
                'base' => $matches[1],
                'ext' => $matches[3]
            ];
        },
        '/^(.+?)_(\d{14})\.([^.]+)$/' => function ($matches) {
            return [
                'original' => $matches[1] . '.' . $matches[3],
                'timestamp' => $matches[2],
                'base' => $matches[1],
                'ext' => $matches[3]
            ];
        },
        '/^(.+?)\s*\((\d+)\)\.([^.]+)$/' => function ($matches) {
            return [
                'original' => $matches[1] . '.' . $matches[3],
                'version' => $matches[2],
                'base' => $matches[1],
                'ext' => $matches[3]
            ];
        },
        '/^(.+?)(\d+[a-z]?)\.([^.]+)$/' => function ($matches) {
            return [
                'original' => $matches[1] . '.' . $matches[3],
                'version' => $matches[2],
                'base' => $matches[1],
                'ext' => $matches[3]
            ];
        }
    ];

    foreach ($patterns as $pattern => $handler) {
        if (preg_match($pattern, $basename, $matches)) {
            return $handler($matches);
        }
    }

    $pathInfo = pathinfo($basename);
    return [
        'original' => $basename,
        'base' => $pathInfo['filename'],
        'ext' => $pathInfo['extension'] ?? '',
        'version' => null
    ];
}

function groupFilesWithOthers($files, $maxMainGroups = 50)
{
    $backupGroups = [];
    $othersGroup = [];
    $groupCounts = [];

    foreach ($files as $file) {
        $basename = basename($file);
        $fileInfo = parseFileName($basename);
        $originalName = $fileInfo['original'];

        if (!isset($groupCounts[$originalName])) {
            $groupCounts[$originalName] = 0;
        }
        $groupCounts[$originalName]++;
    }

    arsort($groupCounts);
    $mainGroups = array_slice($groupCounts, 0, $maxMainGroups, true);

    foreach ($files as $file) {
        $basename = basename($file);
        $fileInfo = parseFileName($basename);
        $originalName = $fileInfo['original'];

        $filePath = realpath($file);
        $fileDate = filemtime($filePath);
        $date = $fileDate ? DateTime::createFromFormat('U', $fileDate) : null;

        $fileData = [
            'name' => $basename,
            'path' => $file,
            'date' => $date ? $date->format('Y-m-d H:i:s') : 'Unknown',
            'timestamp' => $fileDate ?: 0,
            'version' => $fileInfo['version'] ?? null,
            'size' => filesize($filePath)
        ];

        if (isset($mainGroups[$originalName])) {
            if (!isset($backupGroups[$originalName])) {
                $backupGroups[$originalName] = [];
            }
            $backupGroups[$originalName][] = $fileData;
        } else {
            $othersGroup[] = $fileData;
        }
    }

    foreach ($backupGroups as &$group) {
        usort($group, function ($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
    }

    usort($othersGroup, function ($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });

    if (!empty($othersGroup)) {
        $backupGroups['Others'] = $othersGroup;
    }

    return $backupGroups;
}

function groupFiles($files)
{
    $backupGroups = [];

    foreach ($files as $file) {
        $basename = basename($file);
        $fileInfo = parseFileName($basename);
        $originalName = $fileInfo['original'];

        if (!isset($backupGroups[$originalName])) {
            $backupGroups[$originalName] = [];
        }

        $filePath = realpath($file);
        $fileDate = filemtime($filePath);
        $date = $fileDate ? DateTime::createFromFormat('U', $fileDate) : null;

        $backupGroups[$originalName][] = [
            'name' => $basename,
            'path' => $file,
            'date' => $date ? $date->format('Y-m-d H:i:s') : 'Unknown',
            'timestamp' => $fileDate ?: 0,
            'version' => $fileInfo['version'] ?? null,
            'size' => filesize($filePath)
        ];
    }

    foreach ($backupGroups as &$group) {
        usort($group, function ($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
    }

    return $backupGroups;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'getFolderContents':
                $path = isset($_POST['path']) ? $_POST['path'] : '.';
                $path = realpath($path);

                if (!$path || !is_dir($path)) {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Invalid path']);
                    exit;
                }

                $files = glob($path . '/*');
                $fileData = [];

                foreach ($files as $file) {
                    $basename = basename($file);
                    if ($basename === '.' || $basename === '..') continue;

                    $fileData[] = [
                        'name' => $basename,
                        'path' => $file,
                        'date' => date('Y-m-d H:i:s', filemtime($file)),
                        'timestamp' => filemtime($file),
                        'size' => filesize($file)
                    ];
                }

                usort($fileData, function ($a, $b) {
                    return $b['timestamp'] - $a['timestamp'];
                });

                header('Content-Type: application/json');
                echo json_encode(['files' => $fileData]);
                exit;

            case 'getHistory':
                $historyPath = realpath('.history/');
                $fileGroups = [];
                if (is_dir($historyPath)) {
                    $files = glob($historyPath . '/*_*.{php,html,js,css}', GLOB_BRACE);
                    $fileGroups = groupFiles($files);
                }
                header('Content-Type: application/json');
                echo json_encode($fileGroups);
                exit;

            case 'getBackups':
                $backupsPath = realpath('backups/');
                $backupGroups = [];

                if (is_dir($backupsPath)) {
                    $files = glob($backupsPath . '/*.*');
                    $backupGroups = groupFilesWithOthers($files);
                    ksort($backupGroups);
                }

                header('Content-Type: application/json');
                echo json_encode($backupGroups);
                exit;

            case 'delete':
                $files = json_decode($_POST['files'], true);
                $result = ['success' => true, 'deleted' => []];

                foreach ($files as $file) {
                    if (file_exists($file) && is_file($file)) {
                        if (unlink($file)) {
                            $result['deleted'][] = $file;
                        }
                    }
                }

                header('Content-Type: application/json');
                echo json_encode($result);
                exit;

            case 'getFileContent':
                if (!isset($_POST['file'])) {
                    http_response_code(400);
                    exit('File parameter missing');
                }

                $file = $_POST['file'];
                if (!file_exists($file) || !is_file($file)) {
                    http_response_code(404);
                    exit('File not found');
                }

                $content = file_get_contents($file);
                if ($content === false) {
                    http_response_code(500);
                    exit('Failed to read file');
                }

                echo $content;
                exit;

            case 'retrieve':
                $files = json_decode($_POST['files'], true);
                $result = ['success' => true, 'retrieved' => []];

                foreach ($files as $file) {
                    if (file_exists($file) && is_file($file)) {
                        $newPath = basename($file);
                        if (copy($file, $newPath)) {
                            $result['retrieved'][] = $newPath;
                        }
                    }
                }

                header('Content-Type: application/json');
                echo json_encode($result);
                exit;

            case 'zip':
                $files = json_decode($_POST['files'], true);
                $zipName = isset($_POST['zipName']) ? $_POST['zipName'] : 'archive_' . date('YmdHis') . '.zip';
                $result = ['success' => false, 'filename' => null, 'error' => ''];

                if (!class_exists('ZipArchive')) {
                    $result['error'] = 'ZIP extension not available';
                    echo json_encode($result);
                    exit;
                }

                $zip = new ZipArchive();
                $zipResult = $zip->open($zipName, ZipArchive::CREATE);
                if ($zipResult !== TRUE) {
                    $result['error'] = 'Failed to create ZIP file: ' . $zipResult;
                    echo json_encode($result);
                    exit;
                }

                $addedFiles = 0;
                foreach ($files as $file) {
                    if (file_exists($file) && is_file($file)) {
                        $zip->addFile($file, basename($file));
                        $addedFiles++;
                    }
                }

                $zip->close();

                if ($addedFiles > 0) {
                    if (file_exists($zipName)) {
                        header('Content-Type: application/zip');
                        header('Content-Disposition: attachment; filename="' . basename($zipName) . '"');
                        header('Content-Length: ' . filesize($zipName));
                        readfile($zipName);
                        unlink($zipName);
                        exit;
                    } else {
                        $result['error'] = 'ZIP file was not created successfully';
                    }
                } else {
                    $result['error'] = 'No files were added to the ZIP archive';
                }

                header('Content-Type: application/json');
                echo json_encode($result);
                exit;

            case 'checkPath':
                if (!isset($_POST['path'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['exists' => false]);
                    exit;
                }

                $path = $_POST['path'];
                // Only allow checking .history and backups directories
                if ($path !== '.history' && $path !== 'backups') {
                    header('Content-Type: application/json');
                    echo json_encode(['exists' => false]);
                    exit;
                }

                $fullPath = realpath($path);
                $exists = $fullPath && is_dir($fullPath);

                header('Content-Type: application/json');
                echo json_encode(['exists' => $exists]);
                exit;
        }
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetBound Tools: Archive Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="archive-manager.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <style>
        .editor-view {
            background: #fff !important;
            border: 1px solid var(--color-primary);
            border-radius: var(--border-radius);
            /* Set height based on viewport, minus header and footer (adjust 260px as needed) */
            height: calc(100vh - 260px);
            min-height: 400px;
            max-height: calc(100vh - 260px);
            margin-bottom: 15px;
            padding: 0;
            width: 100%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="menu-container">
        <div class="title-container">
            <h1 class="editor-title">NetBound Tools: Archive Manager</h1>
            <a href="main.php?app=nb-archive-manager.php" class="hamburger-menu" title="Go to Main Menu">
                <i class="fas fa-bars"></i>
            </a>
        </div>
        <div class="status-box" id="statusBox"></div>
        <div class="button-controls">
            <div class="button-row">
                <button class="command-button" id="btnVSCodeHistory" title="Display VS Code History">
                    <i class="fas fa-code"></i> VS Code History
                </button>
                <button class="command-button" id="btnNetboundBackups" title="Display Netbound Backups">
                    <i class="fas fa-archive"></i> Netbound Backups
                </button>
                <button class="command-button" id="btnRestart" title="Restart Archive Manager">
                    <i class="fas fa-sync"></i> Restart
                </button>
                <span class="checked-counter" id="checkedCounter">0 Files Selected</span>
            </div>
        </div>
        <div class="editor-view">
            <div class="file-tree" id="fileTree"></div>
        </div>
        <div class="bottom-action-bar" id="contextualButtons">
            <div class="button-row">
                <button class="command-button ghosted" id="btnDeleteSelected" title="Delete selected files">
                    <i class="fas fa-trash"></i> Delete
                </button>
                <button class="command-button ghosted" id="btnRetrieveSelected" title="Retrieve selected files">
                    <i class="fas fa-download"></i> Retrieve
                </button>
                <button class="command-button ghosted" id="btnToClipboard" title="Copy selected file to clipboard">
                    <i class="fas fa-clipboard"></i> TO CLIPBOARD
                </button>
                <div class="save-button-container">
                    <button class="save-button ghosted" id="btnZip" title="Create ZIP archive">
                        <i class="fas fa-file-archive"></i> ZIP
                    </button>
                    <button class="download-button ghosted" id="btnZipAs" title="Save ZIP as...">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <input type="file" id="fileInput" style="display: none">
    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
        let currentView = null;

        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('fileInput');

            // Initialize with ghosted buttons
            document.querySelectorAll('#contextualButtons .command-button, .save-button, .download-button').forEach(button => {
                button.classList.add('ghosted');
            });

            document.getElementById('btnVSCodeHistory').addEventListener('click', () => {
                clearCheckedItems();
                closeAllTreeItems();
                showVSCodeHistory();
            });

            document.getElementById('btnDeleteSelected').addEventListener('click', handleDeleteSelected);

            document.getElementById('btnToClipboard').addEventListener('click', async () => {
                const selectedFiles = Array.from(document.querySelectorAll('.file-check:checked'));

                if (selectedFiles.length === 0) {
                    updateStatus('No file selected', 'error');
                    return;
                }

                if (selectedFiles.length > 1) {
                    updateStatus('Please select only one file', 'error');
                    return;
                }

                const filePath = selectedFiles[0].dataset.path;

                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: new URLSearchParams({
                            'action': 'getFileContent',
                            'csrf_token': csrfToken,
                            'file': filePath
                        })
                    });

                    if (!response.ok) throw new Error('Failed to fetch file content');

                    const content = await response.text();

                    await navigator.clipboard.writeText(content);
                    updateStatus('File content copied to clipboard', 'success');
                } catch (error) {
                    console.error('Error:', error);
                    updateStatus('Failed to copy file content: ' + error.message, 'error');
                }
            });

            document.getElementById('btnNetboundBackups').addEventListener('click', () => {
                updateStatus('Clearing file selection checkboxes...', 'info');
                clearCheckedItems();
                updateStatus('Collapsing all expanded folders...', 'info');
                closeAllTreeItems();
                updateStatus('Clearing file tree area...', 'info');
                document.getElementById('fileTree').innerHTML = '';
                showNetboundBackups();
            });

            document.getElementById('fileTree').addEventListener('change', handleCheckboxChange);

            document.getElementById('btnRetrieveSelected').addEventListener('click', async () => {
                const selectedFiles = Array.from(document.querySelectorAll('.file-check:checked'))
                    .map(checkbox => checkbox.dataset.path);

                if (!selectedFiles.length) {
                    updateStatus('Select files to retrieve', 'info');
                    return;
                }

                const result = await postData('retrieve', {
                    files: selectedFiles
                });

                if (result.success) {
                    updateStatus(`${result.retrieved.length} file(s) copied to current folder`, 'success');
                    const newGroups = await postData(currentView === 'vscode' ? 'getHistory' : 'getBackups');
                    setupTreeView(newGroups);
                    clearCheckedItems();
                }
            });

            document.getElementById('btnRestart').addEventListener('click', () => {
                window.location.reload();
            });

            document.getElementById('btnZip').addEventListener('click', zipSelected);
            document.getElementById('btnZipAs').addEventListener('click', zipSelectedAs);

            updateStatus('Ready', 'info');
            statusManager.update('initial_status', 'Select a button above to view history or backups', 'info');
            updateStatus('Large folders may take time to load...', 'info');
        });

        function clearCheckedItems() {
            document.querySelectorAll('.file-check, .date-check, .folder-check').forEach(checkbox => {
                checkbox.checked = false;
            });
            updateCheckedCount();
        }

        function closeAllTreeItems() {
            document.querySelectorAll('.tree-item').forEach(item => {
                item.classList.remove('expanded');
            });
        }

        async function zipSelected() {
            const selectedFiles = Array.from(document.querySelectorAll('.file-check:checked'))
                .map(checkbox => checkbox.dataset.path);
            if (!selectedFiles.length) {
                updateStatus('No files selected for zipping', 'info');
                return;
            }
            createZip(selectedFiles);
        }

        async function zipSelectedAs() {
            const selectedFiles = Array.from(document.querySelectorAll('.file-check:checked'))
                .map(checkbox => checkbox.dataset.path);
            if (!selectedFiles.length) {
                updateStatus('No files selected for zipping', 'info');
                return;
            }

            const prefix = currentView === 'vscode' ? 'history' : 'backup';
            const zipName = prompt('Enter ZIP filename:', `${prefix}_${new Date().toISOString().slice(0, 19).replace(/[-:]/g, '')}`);
            if (zipName) {
                createZip(selectedFiles, zipName);
            }
        }

        async function createZip(files, zipName = null) {
            try {
                statusManager.update('zip_progress', `Adding 0/${files.length} files to archive...`, 'info');

                const zip = new JSZip();
                const BATCH_SIZE = 50;

                for (let i = 0; i < files.length; i += BATCH_SIZE) {
                    const batch = files.slice(i, i + BATCH_SIZE);
                    const fetchPromises = batch.map(file =>
                        fetch(window.location.href, {
                            method: 'POST',
                            body: new URLSearchParams({
                                'action': 'getFileContent',
                                'file': file,
                                'csrf_token': csrfToken
                            })
                        })
                        .then(response => {
                            if (!response.ok) throw new Error(`Failed to fetch ${file}`);
                            return response.text().then(content => ({
                                file,
                                content
                            }));
                        })
                    );

                    const results = await Promise.all(fetchPromises);

                    results.forEach(({
                        file,
                        content
                    }) => {
                        zip.file(file.split('/').pop(), content);
                    });

                    const filesProcessed = Math.min(i + BATCH_SIZE, files.length);
                    const progress = ((filesProcessed / files.length) * 100).toFixed(1);
                    statusManager.update('zip_progress', `Adding ${filesProcessed}/${files.length} files to archive... (${progress}%)`, 'info');

                    await new Promise(resolve => setTimeout(resolve, 0));
                }

                statusManager.update('zip_progress', 'Generating ZIP file...', 'info');
                const blob = await zip.generateAsync({
                    type: 'blob',
                    compression: "DEFLATE",
                    compressionOptions: {
                        level: 6
                    }
                });

                const prefix = currentView === 'vscode' ? 'history' : 'backup';
                const filename = zipName ? `${zipName}.zip` : `${prefix}_${Date.now()}.zip`;

                const size = blob.size;
                const sizeText = size > 1024 * 1024 ?
                    `${(size/1024/1024).toFixed(2)} MB` :
                    `${(size/1024).toFixed(2)} KB`;

                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                statusManager.remove('zip_progress');
                updateStatus(`Created and downloaded ${filename} (${sizeText})`, 'success');

            } catch (error) {
                console.error('ZIP Error:', error);
                statusManager.remove('zip_progress');
                updateStatus('Failed to create ZIP file: ' + error.message, 'error');
            }
        }

        async function postData(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('csrf_token', csrfToken);

            Object.entries(data).forEach(([key, value]) => {
                if (typeof value === 'object') {
                    formData.append(key, JSON.stringify(value));
                } else {
                    formData.append(key, value);
                }
            });

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        }

        const statusManager = {
            elements: {},

            update(id, message, type = 'info') {
                const statusBox = document.getElementById('statusBox');

                if (this.elements[id]) {
                    this.elements[id].textContent = message;
                    return this.elements[id];
                } else {
                    const statusMessage = document.createElement('div');
                    statusMessage.className = `message ${type} latest`;
                    statusMessage.textContent = message;

                    statusBox.insertBefore(statusMessage, statusBox.firstChild);

                    const messages = statusBox.querySelectorAll('.message');
                    if (messages.length > 1) {
                        messages.forEach((msg, index) => {
                            if (index !== 0) {
                                msg.classList.remove('latest');
                            }
                        });
                    }

                    this.elements[id] = statusMessage;

                    return statusMessage;
                }
            },

            remove(id) {
                if (this.elements[id]) {
                    const element = this.elements[id];
                    if (element.parentNode) {
                        element.parentNode.removeChild(element);
                    }
                    delete this.elements[id];
                }
            }
        };

        function updateStatus(message, type = 'info') {
            const id = 'msg_' + Math.random().toString(36).substr(2, 9);
            return statusManager.update(id, message, type);
        }

        function renderFileGroups(fileGroups) {
            return Object.entries(fileGroups).map(([filename, versions]) => {
                const dateGroups = versions.reduce((groups, file) => {
                    const dateObj = new Date(file.date);
                    const date = dateObj.toLocaleDateString('en-CA', {
                        month: 'short',
                        day: '2-digit',
                        year: 'numeric'
                    });
                    if (!groups[date]) groups[date] = [];
                    groups[date].push(file);
                    return groups;
                }, {});

                return `
                    <div class="tree-item">
                        <div class="tree-header">
                            <input type="checkbox" class="folder-check" data-type="folder" data-name="${filename}">
                            <i class="fas fa-folder"></i>
                            <span class="tree-label">${filename} (${versions.length})</span>
                        </div>
                        <div class="tree-children">
                            ${Object.entries(dateGroups).map(([date, files]) => `
                                <div class="tree-item">
                                    <div class="tree-header">
                                        <input type="checkbox" class="date-check" data-type="date" data-name="${date}">
                                        <i class="fas fa-calendar"></i>
                                        <span class="tree-label">${date} (${files.length})</span>
                                    </div>
                                    <div class="tree-children">
                                        ${files.map(file => `
                                            <div class="tree-file">
                                                <input type="checkbox" class="file-check" data-path="${file.path}" id="file-check-${file.path.replace(/[^a-zA-Z0-9_-]/g, '')}">
                                                <span class="file-label" data-path="${file.path}">${file.name}${currentView === 'vscode' ? ` (${new Date(file.date).toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit'})})` : ''}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>`;
            }).join('') || '<div>No history files found</div>';
        }

        function handleTreeItemClick(e) {
            if (e.target.matches('input[type="checkbox"]')) return;

            const treeItem = e.currentTarget.closest('.tree-item');
            if (!treeItem) return;

            const wasExpanded = treeItem.classList.contains('expanded');
            treeItem.classList.toggle('expanded', !wasExpanded);

            const children = treeItem.querySelector('.tree-children');
            if (children) {
                children.style.display = wasExpanded ? 'none' : 'block';
            }
        }

        function setupTreeView(fileGroups) {
            const tree = document.getElementById('fileTree');
            if (!fileGroups || !tree) return;

            const selectAllHeader = `
                <div class="tree-header">
                    <input type="checkbox" id="selectAllCheckbox">
                    <i class="fas fa-folder" style="color: #66a3ff;"></i>
                    <span class="tree-label">Select All Files</span>
                </div>
            `;

            tree.innerHTML = selectAllHeader + renderFileGroups(fileGroups);

            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', () => {
                    document.querySelectorAll('.file-check, .folder-check, .date-check').forEach(checkbox => {
                        checkbox.checked = selectAllCheckbox.checked;
                    });
                    updateCheckedCount();
                });
            }

            tree.querySelectorAll('.tree-header').forEach(header => {
                if (!header.classList.contains('select-all-header')) {
                    header.addEventListener('click', handleTreeItemClick);
                }
            });

            tree.querySelectorAll('.folder-check').forEach(checkbox => {
                checkbox.addEventListener('change', handleFolderCheck);
            });

            tree.querySelectorAll('.date-check').forEach(checkbox => {
                checkbox.addEventListener('change', handleDateCheck);
            });

            // Make only file-labels (final filenames) toggle their checkbox and update counter
            tree.querySelectorAll('.file-label').forEach(label => {
                label.addEventListener('click', function(e) {
                    const path = label.getAttribute('data-path');
                    const checkbox = tree.querySelector(`.file-check[data-path="${path}"]`);
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        updateCheckedCount();
                        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    e.stopPropagation();
                });
            });
            // Also update counter when clicking the checkbox directly
            tree.querySelectorAll('.file-check').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateCheckedCount();
                });
            });

            const totalFiles = Object.values(fileGroups).reduce((sum, versions) => sum + versions.length, 0);
            const folderType = currentView === 'vscode' ? 'VS Code History' : 'Netbound Backups';

            statusManager.update('filecount', `Found ${totalFiles} files in ${folderType}`, 'success');
        }

        async function showVSCodeHistory() {
            try {
                document.getElementById('fileTree').innerHTML = '';
                updateStatus('Processing Folder Files', 'info');
                currentView = 'vscode';

                if (!await fetch(window.location.href, {
                        method: 'POST',
                        body: new URLSearchParams({
                            'action': 'checkPath',
                            'path': '.history',
                            'csrf_token': csrfToken
                        })
                    }).then(r => r.json()).then(data => data.exists)) {
                    updateStatus('VS Code History folder not found (.history)', 'error');
                    return;
                }

                document.querySelectorAll('#contextualButtons .command-button, .save-button, .download-button').forEach(button => {
                    button.classList.remove('ghosted');
                });

                const fileGroups = await postData('getHistory');
                if (!fileGroups) return;

                setupTreeView(fileGroups);
            } catch (error) {
                console.error('Error loading VS Code history:', error);
                updateStatus('Failed to load VS Code history: ' + error.message, 'error');
            }
        }

        async function showNetboundBackups() {
            try {
                updateStatus('Processing Folder Files. Please Wait', 'info');
                currentView = 'netbound';
                updateStatus('Checking for backups folder...', 'info');
                const pathCheck = await fetch(window.location.href, {
                    method: 'POST',
                    body: new URLSearchParams({
                        'action': 'checkPath',
                        'path': 'backups',
                        'csrf_token': csrfToken
                    })
                }).then(r => r.json());
                if (!pathCheck.exists) {
                    updateStatus('Netbound Backups folder not found', 'error');
                    return;
                }
                updateStatus('Backups folder found.', 'success');
                document.querySelectorAll('#contextualButtons .command-button, .save-button, .download-button').forEach(button => {
                    button.classList.remove('ghosted');
                });
                updateStatus('Loading file list...', 'info');
                const fileGroups = await postData('getBackups');
                if (!fileGroups) return;
                setupTreeView(fileGroups);
            } catch (error) {
                console.error('Error loading Netbound backups:', error);
                updateStatus('Failed to load Netbound backups: ' + error.message, 'error');
            }
        }

        function handleDateCheck(event) {
            const dateCheckbox = event.target;
            const treeItem = dateCheckbox.closest('.tree-item');
            const childCheckboxes = treeItem.querySelectorAll('.file-check');

            childCheckboxes.forEach(checkbox => {
                checkbox.checked = dateCheckbox.checked;
            });

            updateSelectAllCheckboxState();
            updateCheckedCount();
        }

        function handleFolderCheck(event) {
            const folderCheckbox = event.target;
            const treeItem = folderCheckbox.closest('.tree-item');
            const childCheckboxes = treeItem.querySelectorAll('.file-check, .date-check');

            childCheckboxes.forEach(checkbox => {
                checkbox.checked = folderCheckbox.checked;
            });

            updateSelectAllCheckboxState();
            updateCheckedCount();
        }

        function handleCheckboxChange(event) {
            if (event.target.classList.contains('date-check')) {
                handleDateCheck(event);
            } else if (event.target.classList.contains('folder-check')) {
            } else if (event.target.classList.contains('file-check')) {
                updateSelectAllCheckboxState();
            }

            updateCheckedCount();
        }

        function updateSelectAllCheckboxState() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (!selectAllCheckbox) return;

            const fileCheckboxes = document.querySelectorAll('.file-check');
            if (fileCheckboxes.length === 0) return;

            const allChecked = Array.from(fileCheckboxes).every(checkbox => checkbox.checked);

            selectAllCheckbox.checked = allChecked;
        }

        function updateCheckedCount() {
            const selectedCount = document.querySelectorAll('.file-check:checked').length;
            const btnDeleteSelected = document.getElementById('btnDeleteSelected');
            const btnRetrieveSelected = document.getElementById('btnRetrieveSelected');
            const btnToClipboard = document.getElementById('btnToClipboard');
            const btnZip = document.getElementById('btnZip');
            const btnZipAs = document.getElementById('btnZipAs');

            if (selectedCount > 0) {
                btnDeleteSelected.classList.remove('ghosted');
                btnRetrieveSelected.classList.remove('ghosted');
                btnZip.classList.remove('ghosted');
                btnZipAs.classList.remove('ghosted');

                if (selectedCount === 1) {
                    btnToClipboard.classList.remove('ghosted');
                } else {
                    btnToClipboard.classList.add('ghosted');
                }

                statusManager.update('selection', `${selectedCount} file${selectedCount > 1 ? 's' : ''} selected`, 'info');
            } else {
                btnDeleteSelected.classList.add('ghosted');
                btnRetrieveSelected.classList.add('ghosted');
                btnToClipboard.classList.add('ghosted');
                btnZip.classList.add('ghosted');
                btnZipAs.classList.add('ghosted');

                statusManager.remove('selection');
            }

            // Update the live counter
            const counter = document.getElementById('checkedCounter');
            if (counter) {
                counter.textContent = selectedCount + (selectedCount === 1 ? ' File Selected' : ' Files Selected');
            }
        }

        // Remove download icon from status box (if present)
        const statusDownloadIcon = document.getElementById('statusDownloadIcon');
        if (statusDownloadIcon) statusDownloadIcon.remove();

        // Drag and drop to copy files to NetBound backups folder
        document.querySelector('.editor-view').addEventListener('dragover', function(e) {
            e.preventDefault();
        });
        document.querySelector('.editor-view').addEventListener('drop', function(e) {
            e.preventDefault();
            if (e.dataTransfer.files.length > 0) {
                const formData = new FormData();
                for (const file of e.dataTransfer.files) {
                    formData.append('uploadedFiles[]', file);
                }
                fetch('nb-archive-manager.php', {
                    method: 'POST',
                    body: formData
                }).then(r => r.ok ? updateStatus('Files uploaded to NetBound backups folder.', 'success') : updateStatus('Upload failed.', 'error'));
            }
        });
    </script>
</body>
</html>
