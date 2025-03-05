<?php
session_start();

/*
| NetBound Tools: Archive Manager
| Part of the NetBoundToolSuite NetBound.cad
| version  : 24.1.20
| author   : OrangeJeff@frogstar.com
| updated  : 16/04/2022 23:39:57
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            background-color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .menu-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 10px;
            box-sizing: border-box;
            overflow: hidden;
            max-width: 600px;
        }

        .editor-view {
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 20px);
            overflow: hidden;
        }

        .editor {
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
        }

        .file-tree {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            margin: 0 10px 10px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            height: calc(100vh - 280px);
            min-height: 200px;
        }

        .editor-header {
            background: #f8f9fa;
            padding: 8px;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .editor-title {
            margin: 0;
            padding: 0 0 10px;
            color: #0056b3;
            line-height: 1.2;
            font-weight: bold;
            font-size: 18px;
        }

        .button-controls {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .button-row {
            display: flex;
            gap: 8px;
            align-items: center;
            line-height: 1;
            margin-bottom: 5px;
            /* Adjusted spacing between rows */
        }

        .command-button {
            background: #0056b3;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
            height: 28px;
            line-height: 1;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .command-button:hover:not(:disabled) {
            background: #003d82;
        }

        .command-button:disabled {
            background: #cccccc;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .command-button.active {
            box-shadow: 0 2px 0 #4a9eff;
        }

        .full-width {
            width: 100%;
            margin-top: 10px;
        }

        .status-box {
            padding: 8px;
            margin: 8px 0 5px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            font-size: 14px;
            height: 4.5em;
            min-height: 60px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            background: white;
            scrollbar-gutter: stable;
        }

        .tree-item {
            padding: 3px;
            cursor: pointer;
        }

        .tree-children {
            margin-left: 20px;
            display: none;
        }

        .tree-file,
        .tree-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px;
            cursor: pointer;
        }

        .tree-header .fa-folder {
            color: #dcb67a;
        }

        .tree-header .fa-calendar-alt,
        .tree-file .fa-file-code {
            color: #0056b3;
        }

        .tree-file:hover,
        .tree-header:hover {
            background: #f0f0f0;
            border-radius: 3px;
        }

        .expanded>.tree-children {
            display: block;
        }

        .tree-label {
            flex: 1;
            user-select: none;
        }

        .split-button {
            display: inline-flex;
            margin-right: 10px;
        }

        .split-button .zip-main {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            margin-right: 1px;
        }

        .split-button .zip-extra {
            padding: 5px 10px;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .file-tree::-webkit-scrollbar {
            width: 8px;
        }

        .file-tree::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .file-tree::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .file-tree::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .status-box::-webkit-scrollbar {
            width: 8px;
        }

        .status-box::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .status-box::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .message {
            padding: 4px 8px;
            border-radius: 3px;
            margin: 2px 0;
            flex-shrink: 0;
            color: #505050;
            background-color: transparent;
        }

        .message.latest {
            color: white;
        }

        .message.latest.info {
            background-color: #4a9eff;
        }

        .message.latest.success {
            background-color: #28a745;
        }

        .message.latest.error {
            background-color: #dc3545;
        }

        .contextual-controls {
            padding: 8px 10px;
            margin-bottom: 5px;
            background: #f8f9fa;
        }
    </style>
</head>

<body>
    <div class="menu-container">
        <div class="editor-view">
            <div class="editor" id="editorSection">
                <div class="editor-header">
                    <div class="header-top">
                        <h1 class="editor-title">NetBound Tools: Archive Manager</h1>
                    </div>
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
                        </div>
                    </div>
                    <div class="status-box" id="status"></div>
                </div>
                <div class="contextual-controls" id="contextualButtons" style="display: none;">
                    <div class="button-container">
                        <button class="command-button" id="btnSelectAll" title="Select All Files">
                            <i class="fas fa-check-square"></i> Select All
                        </button>
                        <button class="command-button" id="btnDeleteSelected" title="Delete selected files">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        <button class="command-button" id="btnRetrieveSelected" title="Retrieve selected files">
                            <i class="fas fa-download"></i> Retrieve
                        </button>
                        <button class="command-button" id="btnToClipboard" title="Copy selected file to clipboard" disabled>
                            <i class="fas fa-clipboard"></i> TO CLIPBOARD
                        </button>
                        <div class="split-button">
                            <button class="command-button zip-main" onclick="zipSelected()" title="Create ZIP archive">
                                <i class="fas fa-file-archive"></i> ZIP
                            </button>
                            <button class="command-button zip-extra" onclick="zipSelectedAs()" title="Save ZIP as...">+</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="file-tree" id="fileTree"></div>
        </div>
    </div>
    </div>

    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
        let currentView = null;

        const STATUS_MESSAGES = {
            folder: {
                processing: 'Folder Processing',
                displayingHistory: 'Displaying VS Code History',
                displayingBackups: 'Displaying Backups'
            }
        };

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
                updateStatus(`Adding 0/${files.length} files to archive...`, 'info');

                const zip = new JSZip();
                // Fetch and add each file with progress
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const progress = ((i + 1) / files.length * 100).toFixed(1);
                    const statusBox = document.getElementById('status');
                    if (statusBox.lastChild && statusBox.lastChild.textContent.includes('Adding')) {
                        statusBox.lastChild.textContent = `Adding ${i + 1}/${files.length} files to archive... (${progress}%)`;
                    } else {
                        updateStatus(`Adding ${i + 1}/${files.length} files to archive... (${progress}%)`, 'info');
                    }

                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: new URLSearchParams({
                            'action': 'getFileContent',
                            'file': file,
                            'csrf_token': csrfToken
                        })
                    });

                    if (!response.ok) throw new Error(`Failed to fetch ${file}`);
                    const content = await response.text();
                    zip.file(file.split('/').pop(), content);
                }

                updateStatus('Generating ZIP file...', 'info');
                const blob = await zip.generateAsync({
                    type: 'blob'
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

                updateStatus(`Created and downloaded ${filename} (${sizeText})`, 'success');

            } catch (error) {
                console.error('ZIP Error:', error);
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

        function updateStatus(message, type = 'info') {
            const statusBox = document.getElementById('status');

            // Remove 'latest' class from previous messages
            statusBox.querySelectorAll('.message.latest').forEach(msg => {
                msg.classList.remove('latest');
            });

            const statusMessage = document.createElement('div');
            statusMessage.className = `message latest ${type}`;
            statusMessage.textContent = message;

            // Add new message at the bottom
            statusBox.appendChild(statusMessage);

            // Keep a maximum of 15 messages
            while (statusBox.children.length > 15) {
                statusBox.removeChild(statusBox.firstChild);
            }

            // Auto-scroll to bottom
            statusBox.scrollTop = statusBox.scrollHeight;
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
                                                <input type="checkbox" class="file-check" data-path="${file.path}">
                                                <span>${file.name}${currentView === 'vscode' ? ` (${new Date(file.date).toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit'})})` : ''}</span>
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
            Array.from(treeItem.parentElement.children)
                .forEach(sibling => sibling.classList.remove('expanded'));
            if (!wasExpanded) {
                treeItem.classList.add('expanded');
            }
        }

        function setupTreeView(fileGroups) {
            const tree = document.getElementById('fileTree');
            if (!fileGroups || !tree) return;

            tree.innerHTML = renderFileGroups(fileGroups);
            // Add click handlers for file rows
            tree.querySelectorAll('.tree-file').forEach(fileRow => {
                fileRow.addEventListener('click', (e) => {
                    // Don't trigger if clicking the checkbox directly
                    if (e.target.type !== 'checkbox') {
                        const checkbox = fileRow.querySelector('input[type="checkbox"]');
                        checkbox.checked = !checkbox.checked;
                        updateCheckedCount();
                    }
                });
            });

            // Keep existing header click behavior for folders/dates
            tree.querySelectorAll('.tree-header').forEach(header => {
                header.addEventListener('click', handleTreeItemClick);
            });

            const totalFiles = Object.values(fileGroups).reduce((sum, versions) => sum + versions.length, 0);
            const folderType = currentView === 'vscode' ? 'VS Code History' : 'Netbound Backups';
            updateStatus(`Found ${totalFiles} files in ${folderType}`, 'info');
        }

        async function showVSCodeHistory() {
            document.getElementById('fileTree').innerHTML = '';
            updateStatus(STATUS_MESSAGES.folder.processing, 'info');
            currentView = 'vscode';

            // Check if .history directory exists
            if (!await fetch(window.location.href, {
                    method: 'POST',
                    body: new URLSearchParams({
                        'action': 'checkPath',
                        'csrf_token': csrfToken,
                        'path': '.history'
                    })
                }).then(r => r.json()).then(data => data.exists)) {
                updateStatus('VS Code History folder not found (.history)', 'error');
                return;
            }

            document.getElementById('contextualButtons').style.display = 'flex';
            const fileGroups = await postData('getHistory');
            if (!fileGroups) return;

            setupTreeView(fileGroups);
        }

        async function showNetboundBackups() {
            document.getElementById('fileTree').innerHTML = '';
            updateStatus(STATUS_MESSAGES.folder.processing, 'info');
            currentView = 'netbound';

            // Check if backups directory exists
            if (!await fetch(window.location.href, {
                    method: 'POST',
                    body: new URLSearchParams({
                        'action': 'checkPath',
                        'csrf_token': csrfToken,
                        'path': 'backups'
                    })
                }).then(r => r.json()).then(data => data.exists)) {
                updateStatus('Backups folder not found (backups)', 'error');
                return;
            }

            document.getElementById('contextualButtons').style.display = 'flex';
            const backupGroups = await postData('getBackups');
            if (!backupGroups) return;

            setupTreeView(backupGroups);
        }

        function handleDateCheck(event) {
            const dateCheckbox = event.target;
            const treeItem = dateCheckbox.closest('.tree-item');
            const childCheckboxes = treeItem.querySelectorAll('.file-check');

            childCheckboxes.forEach(checkbox => {
                checkbox.checked = dateCheckbox.checked;
            });
            updateCheckedCount();
        }

        function handleSelectAll() {
            let selector = '#fileTree .tree-file input[type="checkbox"]';
            const checkboxes = document.querySelectorAll(selector);
            const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);

            // Toggle: If all are checked, uncheck all. Otherwise, check all.
            const newState = !allChecked;

            // Update all checkboxes (files, folders, dates)
            document.querySelectorAll('.file-check, .folder-check, .date-check').forEach(checkbox => {
                checkbox.checked = newState;
            });

            updateCheckedCount();
        }

        function handleDeleteSelected() {
            const selectedFiles = Array.from(document.querySelectorAll('.file-check:checked'))
                .map(checkbox => checkbox.dataset.path);

            if (!selectedFiles.length) {
                updateStatus('No files selected for deletion', 'info');
                return;
            }

            if (confirm(`Delete ${selectedFiles.length} files?`)) {
                postData('delete', {
                        files: selectedFiles
                    })
                    .then(async result => {
                        if (result.success) {
                            updateStatus(`Deleted ${result.deleted.length} files`, 'success');
                            // Refresh file list without updating status
                            const newGroups = await postData(currentView === 'vscode' ? 'getHistory' : 'getBackups');
                            setupTreeView(newGroups);
                            clearCheckedItems();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        updateStatus('Failed to delete files', 'error');
                    });
            }
        }

        function handleFolderCheck(event) {
            const folderCheckbox = event.target;
            const treeItem = folderCheckbox.closest('.tree-item');
            const childCheckboxes = treeItem.querySelectorAll('.file-check, .date-check');

            childCheckboxes.forEach(checkbox => {
                checkbox.checked = folderCheckbox.checked;
            });
            updateCheckedCount();
        }

        function updateCheckedCount() {
            const count = document.querySelectorAll('.file-check:checked').length;
            const statusBox = document.getElementById('status');
            // For selection counts, just update text directly without adding to history
            if (statusBox.lastChild && statusBox.lastChild.textContent.includes('Selected:')) {
                statusBox.lastChild.textContent = count ? `Selected: ${count} files` : 'Ready';
            } else {
                updateStatus(count ? `Selected: ${count} files` : 'Ready', 'info');
            }
        }

        function handleCheckboxChange(event) {
            if (event.target.classList.contains('date-check')) {
                handleDateCheck(event);
            } else if (event.target.classList.contains('folder-check')) {
                handleFolderCheck(event);
            }
            updateCheckedCount();
        }

        function closeAllTreeItems() {
            document.querySelectorAll('.tree-item').forEach(item => {
                item.classList.remove('expanded');
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('btnVSCodeHistory').addEventListener('click', () => {
                clearCheckedItems();
                closeAllTreeItems();
                showVSCodeHistory();
            });

            document.getElementById('btnSelectAll').addEventListener('click', handleSelectAll);

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

                    // Check if we're running in an iframe
                    const isIframe = window !== window.parent;

                    if (isIframe) {
                        // Signal parent window to switch back to editor
                        window.parent.postMessage({
                            action: 'switchToEditor',
                            status: 'Archived file content copied to clipboard'
                        }, '*');
                    } else {
                        // Just show status if running independently
                        updateStatus('File content copied to clipboard', 'success');
                    }

                } catch (error) {
                    console.error('Error:', error);
                    updateStatus('Failed to copy file content', 'error');
                }
            });

            document.getElementById('btnNetboundBackups').addEventListener('click', () => {
                clearCheckedItems();
                closeAllTreeItems();
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
                    // Refresh file list without updating status
                    const newGroups = await postData(currentView === 'vscode' ? 'getHistory' : 'getBackups');
                    setupTreeView(newGroups);
                    clearCheckedItems();
                }
            });

            document.getElementById('btnRestart').addEventListener('click', () => {
                window.location.reload();
            });
        });

        function clearCheckedItems() {
            document.querySelectorAll('.file-check, .date-check, .folder-check').forEach(checkbox => {
                checkbox.checked = false;
            });
            updateCheckedCount();
        }
    </script>
</body>

</html>
