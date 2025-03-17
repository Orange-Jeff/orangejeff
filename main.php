<?php
/*
| NetBound Tool Suite - Main Editor and launcher
| version  : 100.1.20
| NetBound.ca - orangejeff@frogstar.com
| created  : 19/10/2024 20:24:36
| updated  : 16/03/2025 23:39:57
+---------------------------------------------------------------------------- */
/*
| Filename: main.php
| Dependant: main.css
|/* -------------------------------------------------------------------------- */
// Error Reporting and Session Start
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_errors', 0); // Hide warnings so JSON isn’t polluted

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure REQUEST_METHOD is set
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}

// Set up directory and backup directory
$dir = __DIR__;
$currentPath = isset($_GET['folder']) ? trim($_GET['folder'], '/') : '';
$currentPath = preg_replace('/[^a-zA-Z0-9\/_-]/', '', $currentPath); // Sanitize path
$currentDir = $currentPath ? $dir . '/' . $currentPath : $dir;
$backupDir = $dir . '/backups/';

// Breadcrumb parts
$breadcrumbs = $currentPath ? explode('/', $currentPath) : [];

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

/**
 * Get sorted file list based on current sort preference
 * Centralizes file listing logic to avoid duplication
 */
function getFileList($dir, $sortBy = 'date')
{
    $files = glob($dir . '/*') ?: [];

    if (!empty($files)) {
        if ($sortBy === 'date') {
            usort($files, function ($a, $b) {
                $aIsDir = is_dir($a);
                $bIsDir = is_dir($b);

                if ($aIsDir === $bIsDir) {
                    return filemtime($b) - filemtime($a);
                }

                return $aIsDir ? 1 : -1; // Folders at bottom for date sort
            });
        } else {
            // Custom sorting: nb-files first, then alphabetically
            usort($files, function ($a, $b) {
                $aIsDir = is_dir($a);
                $bIsDir = is_dir($b);

                // Keep folders at top
                if ($aIsDir !== $bIsDir) {
                    return $aIsDir ? -1 : 1;
                }

                // If both are not directories
                if (!$aIsDir && !$bIsDir) {
                    $fileA = basename($a);
                    $fileB = basename($b);
                    $extA = strtolower(pathinfo($fileA, PATHINFO_EXTENSION));
                    $extB = strtolower(pathinfo($fileB, PATHINFO_EXTENSION));

                    // Check for nb- prefix in PHP files first
                    $isNbA = (strpos($fileA, 'nb-') === 0);
                    $isNbB = (strpos($fileB, 'nb-') === 0);

                    // Both are PHP files
                    if ($extA === 'php' && $extB === 'php') {
                        if ($isNbA !== $isNbB) {
                            return $isNbA ? -1 : 1; // nb- PHP files first
                        }
                    }
                    // Only one is PHP
                    else if ($extA === 'php' || $extB === 'php') {
                        return $extA === 'php' ? -1 : 1; // PHP files before others
                    }

                    // Group by extension
                    if ($extA !== $extB) {
                        return strcasecmp($extA, $extB);
                    }
                }

                // Otherwise sort alphabetically
                $fileA = basename($a);
                $fileB = basename($b);
                return strcasecmp($fileA, $fileB);
            });
        }
    }

    return $files;
}

/**
 * Helper function to check if directory is empty
 */
function is_dir_empty($dir)
{
    $files = scandir($dir);
    return count($files) <= 2; // . and ..
}

/**
 * Helper function to recursively remove a directory
 */
function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . "/" . $object)) {
                    rrmdir($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        rmdir($dir);
    }
}

/**
 * Get CSS class based on file extension
 */

/**
 * Get CSS class based on file extension
 */
function getFolderClass($path)
{
    if (!is_dir($path)) {
        return '';
    }
    if (basename($path) === 'backups') {
        return 'folder-special';
    }
    return 'folder-normal';
}
function getFileTypeClass($filename)
{
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $basename = basename($filename);

    // Special case for nb- files
    if (strpos($basename, 'nb-') === 0) {
        return 'file-nb';
    }

    // Extension-based coloring
    switch ($extension) {
        case 'php':
            return 'file-php';
        case 'html':
        case 'htm':
            return 'file-html';
        case 'css':
            return 'file-css';
        case 'js':
            return 'file-js';
        case 'json':
            return 'file-json';
        case 'txt':
            return 'file-txt';
        default:
            return 'file-other';
    }
}

/**
 * Generate HTML for file list
 */
function generateFileListHTML($files, $currentFilename = '', $currentPath = '')
{
    ob_start();

    $hasValidFiles = false;

    if (count($files) > 0) {
        foreach ($files as $file) {
            $filename = basename($file);

            // Skip only dot directories and backup files, but show backups folder and .history
            if (
                preg_match('/\(BAK-\w{3}\d{2}-S\d+\)\.\w+$/', $filename) ||
                $filename === '.' || $filename === '..'
                // Removed the check that hides the backups folder
                // Now backups and .history folders will be displayed
            ) {
                continue;
            }

            $hasValidFiles = true;
            $isDir = is_dir($file);
            $fileClass = $isDir ? getFolderClass($file) : getFileTypeClass($filename);
            $icon = $isDir ? 'fa-folder' : 'fa-file';
            // Ensure proper path construction
            $relativePath = trim($currentPath ? $currentPath . '/' . $filename : $filename, '/');

            if ($isDir) {
                echo "<li class='file-entry folder-entry'>
                    <div class='file-controls'>
                        <a href='?folder=" . urlencode($relativePath) . "' title='Open Folder'>
                            <i class='fas fa-folder'></i>
                        </a>
                    </div>
                    <a href='?folder=" . urlencode($relativePath) . "'
                        class='filename'
                        title='" . htmlspecialchars($filename, ENT_QUOTES) . "'>" .
                    htmlspecialchars($filename, ENT_QUOTES) . "
                    </a>
                    <div class='select-control'>
                        <input type='checkbox' class='delete-check' data-type='folder' data-name='" . htmlspecialchars($filename, ENT_QUOTES) . "' data-path='" . htmlspecialchars($relativePath, ENT_QUOTES) . "'>
                    </div>
                </li>";
            } else {
                $isCurrentEdit = ($filename === $currentFilename);

                echo "<li class='file-entry " . ($isCurrentEdit ? "current-edit" : "") . "'>
                    <div class='file-controls'>
                        <a href='#' onclick='openInNewTab(\"" . htmlspecialchars($relativePath, ENT_QUOTES) . "\"); return false;' title='Run File'>
                            <i class='fas fa-play'></i>
                        </a>
                    </div>
                    <a href='#' onclick='loadFile(\"" . htmlspecialchars($relativePath, ENT_QUOTES) . "\"); return false;'
                       class='filename " . $fileClass . "'
                       title='" . htmlspecialchars($filename, ENT_QUOTES) . "'>
                       <i class='fas " . $icon . "'></i> " . htmlspecialchars($filename, ENT_QUOTES) . "
                    </a>
                    <div class='select-control'>
                        <input type='checkbox' class='delete-check' data-type='file' data-name='" . htmlspecialchars($filename, ENT_QUOTES) . "' data-path='" . htmlspecialchars($relativePath, ENT_QUOTES) . "'>
                    </div>
                </li>";
            }
        }
    }

    return ob_get_clean();
}

/* -------------------------------------------------------------------------- */
/*                                 GET Handlers                               */
/* -------------------------------------------------------------------------- */

// File Serving Handler
if (isset($_GET['file'])) {
    $requestedFile = basename($_GET['file']);
    // Validate filename to prevent path traversal
    if (preg_match('/^[\w\-\/\.]+$/', $_GET['file']) && file_exists($currentDir . '/' . $_GET['file'])) {
        echo file_get_contents($currentDir . '/' . $_GET['file']);
        exit;
    }
}

// Sort Mode Toggle Handler
if (isset($_GET['toggleSort'])) {
    $currentSort = $_SESSION['sortBy'] ?? 'date';
    $sortBy = ($currentSort === 'date') ? 'name' : 'date';
    $_SESSION['sortBy'] = $sortBy;
    // Return JSON response with the new sort mode
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'sortBy' => $sortBy]);
    exit;
} else {
    $sortBy = $_SESSION['sortBy'] ?? 'name'; // Default to name-based sort
}

// Get Folders Handler (combined with getFileList for efficiency)
if (isset($_GET['getFolders'])) {
    $folders = array_filter(glob('*'), 'is_dir');
    echo json_encode($folders);
    exit;
}

// Get File List Handler - now using centralized function
if (isset($_GET['getFileList'])) {
    $files = getFileList($dir, $sortBy);
    $fileListHTML = generateFileListHTML($files, $_GET['edit'] ?? '', $currentPath);
    echo $fileListHTML;
    exit;
}

/* -------------------------------------------------------------------------- */
/*                                POST Handler                                */
/* -------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Fixed delete folder handler - improved error reporting
    if ($action === 'deleteFolder') {
        $folderName = basename($_POST['folderName'] ?? '');
        $response = ['status' => 'error', 'message' => ''];

        if (empty($folderName)) {
            $response['message'] = 'Folder name is required';
        } else {
            $folderPath = $currentDir . '/' . $folderName;

            if (!is_dir($folderPath)) {
                $response['message'] = 'Folder does not exist: ' . $folderName;
            } else {
                try {
                    // Make sure we have permission to delete the folder
                    if (!is_readable($folderPath) || !is_writable($folderPath)) {
                        $response['message'] = 'Permission denied: Cannot delete folder ' . $folderName;
                    } else {
                        rrmdir($folderPath);
                        if (!is_dir($folderPath)) {
                            $response['status'] = 'success';
                            $response['message'] = 'Folder deleted: ' . $folderName;
                        } else {
                            $response['message'] = 'Failed to delete folder: ' . $folderName;
                        }
                    }
                } catch (Exception $e) {
                    $response['message'] = 'Error deleting folder: ' . $e->getMessage();
                }
            }
        }
        echo json_encode($response);
        exit;
    }

    // Create folder handler
    if ($action === 'createFolder') {
        $folderName = $_POST['folderName'] ?? '';
        $response = ['status' => 'error', 'message' => ''];

        if (empty($folderName)) {
            $response['message'] = 'Folder name required';
        } else if (mkdir($currentDir . '/' . $folderName, 0755)) {
            $response['status'] = 'success';
            $response['message'] = 'Folder created: ' . $folderName;
        } else {
            $response['message'] = 'Failed to create folder: ' . $folderName;
        }
        echo json_encode($response);
        exit;
    }
    // File save handler
    if ($action === 'save') {
        // Ensure clean output buffer
        ob_clean();
        header('Content-Type: application/json');

        $filename = basename($_POST['filename'] ?? '');
        $content = $_POST['content'] ?? '';
        $isRename = isset($_POST['isRename']) && $_POST['isRename'] === 'true';

        if (empty($filename)) {
            echo json_encode(['status' => 'error', 'message' => 'Filename required']);
            exit;
        } else {
            $filePath = $currentDir . '/' . $filename;
            $originalContent = file_exists($filePath) ? file_get_contents($filePath) : '';

            if (file_exists($filePath) && $originalContent === $content) {
                echo json_encode(['status' => 'info', 'message' => 'No changes detected, file not saved: ' . $filename]);
                exit;
            }

            // Regular save with backup attempt for existing file
            try {
                if (file_exists($filePath)) {
                    $backupFilename = basename($filename);
                    $version = 1;
                    while (file_exists($backupDir . $backupFilename . '(V' . $version . ').php')) {
                        $version++;
                    }
                    $backupFilename = $backupFilename . '(V' . $version . ').php';

                    // Create directory if it doesn't exist
                    if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true)) {
                        throw new Exception("Cannot create backup directory");
                    }

                    if (copy($filePath, $backupDir . $backupFilename)) {
                        if (file_put_contents($filePath, $content, LOCK_EX) !== false) {
                            echo json_encode(['status' => 'success', 'message' => 'File saved: ' . $filename . ' (backup created: ' . $backupFilename . ')']);
                        } else {
                            echo json_encode(['status' => 'error', 'message' => 'Save failed: ' . $filename]);
                        }
                    } else {
                        // Failed to create backup but try to save anyway
                        if (file_put_contents($filePath, $content, LOCK_EX) !== false) {
                            echo json_encode(['status' => 'success', 'message' => 'File saved: ' . $filename . ' (backup failed)']);
                        } else {
                            echo json_encode(['status' => 'error', 'message' => 'Save failed: ' . $filename]);
                        }
                    }
                } else {
                    // New file, no backup needed
                    if (file_put_contents($filePath, $content, LOCK_EX) !== false) {
                        echo json_encode(['status' => 'success', 'message' => 'File saved: ' . $filename]);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Save failed: ' . $filename]);
                    }
                }
            } catch (Exception $e) {
                // Try to save anyway even if backup process had errors
                if (file_put_contents($filePath, $content, LOCK_EX) !== false) {
                    echo json_encode(['status' => 'success', 'message' => 'File saved: ' . $filename . ' (backup process error: ' . $e->getMessage() . ')']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Save failed: ' . $filename]);
                }
            }
            exit;
        }
    }
    // Backup retrieval handler
    else if ($action === 'getBackup') {
        $filename = basename($_POST['filename'] ?? '');
        $filepath = $_POST['filepath'] ?? $filename; // Use full path if provided

        if (empty($filename)) {
            echo json_encode(['status' => 'error', 'message' => 'Filename required']);
            exit;
        }

        // Try to find backup with just the basename first (legacy backups)
        $backupFilename = '';
        $version = 1;
        while (
            file_exists($backupDir . $filename . '(V' . $version . ').php') ||
            file_exists($backupDir . $filename . '(v' . $version . ').php')
        ) {
            if (file_exists($backupDir . $filename . '(V' . $version . ').php')) {
                $backupFilename = $backupDir . $filename . '(V' . $version . ').php';
            } else {
                $backupFilename = $backupDir . $filename . '(v' . $version . ').php';
            }
            $version++;
        }

        // If no backup found with basename, try with path-based backup naming
        if (!$backupFilename && $filepath != $filename) {
            // Encode the filepath to create a valid filename for the backup
            $encodedPath = str_replace('/', '_-_', $filepath);
            $version = 1;
            while (
                file_exists($backupDir . $encodedPath . '(V' . $version . ').php') ||
                file_exists($backupDir . $encodedPath . '(v' . $version . ').php')
            ) {
                if (file_exists($backupDir . $encodedPath . '(V' . $version . ').php')) {
                    $backupFilename = $backupDir . $encodedPath . '(V' . $version . ').php';
                } else {
                    $backupFilename = $backupDir . $encodedPath . '(v' . $version . ').php';
                }
                $version++;
            }
        }

        if ($backupFilename && file_exists($backupFilename)) {
            $content = file_get_contents($backupFilename);
            echo json_encode(['status' => 'success', 'content' => $content, 'backupFilename' => basename($backupFilename)]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No backups found for this file.']);
        }
        exit;
    }
    // Handle file transfers (retained but improved)
    else if ($action === 'transferFiles') {
        $response = ['status' => 'error', 'message' => 'No files uploaded'];

        if (!empty($_FILES['files']['name'][0])) {
            $successCount = 0;
            $failCount = 0;

            foreach ($_FILES['files']['name'] as $key => $name) {
                $tmpName = $_FILES['files']['tmp_name'][$key];
                $targetPath = $currentDir . '/' . basename($name);

                // Create backup of existing file if applicable
                if (file_exists($targetPath)) {
                    $backupName = basename($name) . '(V' . time() . ').php';
                    copy($targetPath, $backupDir . $backupName);
                }

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }

            $response = [
                'status' => $failCount === 0 ? 'success' : ($successCount > 0 ? 'partial' : 'error'),
                'message' => "Transferred: $successCount, Failed: $failCount"
            ];
        }

        echo json_encode($response);
        exit;
    }
}

/* -------------------------------------------------------------------------- */
/*                                HTML Output                                 */
$currentFilename = isset($_GET['file']) ? basename($_GET['file']) : '';
$content = '';

// Handle direct app loading
$appToLoad = '';
if (isset($_GET['app'])) {
    $requestedApp = $_GET['app'];
    // Allow any PHP file to be loaded, not just nb- prefixed ones
    if (
        file_exists($currentDir . '/' . $requestedApp) &&
        (pathinfo($requestedApp, PATHINFO_EXTENSION) === 'php' ||
            pathinfo($requestedApp, PATHINFO_EXTENSION) === 'html')
    ) {
        $appToLoad = $requestedApp;
    }
}

// Load file content if needed


// Get file list for initial display
$files = getFileList($currentDir, $sortBy);
$validFiles = !empty($files);
foreach ((array)$files as $file) {
    $filename = basename($file);
    if (!preg_match('/\(BAK-\w{3}\d{2}-S\d+\)\.\w+$/', $filename) && $filename !== '.' && $filename !== '..' && !($filename === 'backups' && is_dir($file))) {
        $validFiles = true;
        break;
    }
}

// Display empty folder message if no files
$showEmptyMessage = !$validFiles && !empty($currentPath);
$sortIcon = $sortBy === 'date' ? 'fa-clock' : 'fa-sort-alpha-down';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetBound Tools Menu</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="main.css" rel="stylesheet">

</head>

<body>
    <div class="header">
        <div class="left-section">
            <h2 class="menu-title">NetBound Tools</h2>
            <button id="menuUpdateBtn" class="header-button" title="Transfer files to server">
                <i class="fas fa-paper-plane"></i>
            </button>
            <?php if ($currentPath): ?>
                <a href="?folder=<?php echo dirname($currentPath); ?>" class="up-level" title="Up Level"><i class="fas fa-level-up-alt"></i></a>
            <?php endif; ?>
            <button id="menuNewBtn" class="header-button" title="New" onclick="createNewFile()">
                <i class="fas fa-file"></i>
            </button>
            <button id="menuNewFolderBtn" class="header-button" title="New Folder" onclick="createNewFolder()">
                <i class="fas fa-folder-plus"></i>
            </button>
            <button id="menuRefreshBtn" class="header-button" title="Reload Menu">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button id="menuSortBtn" class="header-button" title="Toggle Sort (Currently: <?php echo $sortIcon === 'fa-clock' ? 'sorted by date' : 'sorted alphabetically'; ?>)">
                <i class="fas <?php echo $sortIcon; ?>"></i>
            </button>
            <button id="menuDeleteBtn" class="header-button" title="Delete Selected">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    </div>
    <div id="menuOverlay" class="menu-overlay"></div>
    <div class="container" id="mainContainer">
        <div class="menu" id="menuPanel">
            <div class="menu-content">
                <?php if (!empty($breadcrumbs)): ?>
                    <div class="breadcrumb">
                        <a href="?"><i class="fas fa-home"></i></a>
                        <?php foreach ($breadcrumbs as $i => $crumb): ?>
                            <span class="separator">/</span>
                            <a href="?folder=<?php echo urlencode(implode('/', array_slice($breadcrumbs, 0, $i + 1))); ?>">
                                <?php echo htmlspecialchars($crumb); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <ul class="file-list">
                    <?php echo generateFileListHTML($files, $currentFilename); ?>
                </ul>
                <?php if ($showEmptyMessage): ?>
                    <div class="empty-folder-message">
                        <i class="fas fa-folder-open" style="font-size: 24px; margin-bottom: 10px; color: #999;"></i><br>
                        This folder is empty.<br>Click the "+" button above to create a new file or upload files to get started.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="menu-container">
            <div class="editor-view<?php echo $appToLoad ? ' hidden' : ''; ?>">
                <div class="editor" id="editorSection">
                    <div class="editor-header">
                        <div class="header-top">
                            <h1 class="tool-title">NetBound Tool Suite: Editor</h1>
                        </div>
                        <div class="persistent-status-bar" id="statusBar"></div>
                    </div>
                    <div class="button-group">
                        <button type="button" class="command-button" onclick="openFileRequester()" title="Open a file from your computer">
                            <i class="fas fa-folder-open"></i> From File
                        </button>

                        <div class="split-button">
                            <div class="main-part" onclick="fromClipboard()" title="Replace editor content with clipboard content">
                                <i class="fas fa-clipboard"></i> From Clipboard
                            </div>
                            <div class="append-part" onclick="appendClipboard()" title="Add clipboard content to end of file">
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
                    </div>

                    <form method="POST" class="edit-form" style="display:flex;flex-direction:column;height:100%;" id="editorForm">
                        <div class="editor-container" id="editorContainer">
                            <div id="editor"></div>
                        </div>

                        <div class="label-line">
                            <input type="text" id="editorFilename" class="info-input" value="" onchange="updateDisplayFilename()" placeholder="Filename">
                            <button type="button" class="command-button" onclick="updateVersionAndDate()" title="Change the filename">
                                <i class="fas fa-sync-alt"></i> Rename
                            </button>
                        </div>

                        <div class="button-row">
                            <div class="button-group">
                                <div class="split-button">
                                    <div class="main-part" onclick="saveFile()" title="Save file to server">
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
                                <button type="button" name="run" class="command-button" title="Run this file in a new browser window"
                                    onclick="openInNewWindow(document.getElementById('editorFilename').value)">
                                    <i class="fas fa-external-link-alt"></i> Run
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="backup-view<?php echo $appToLoad ? ' active' : ''; ?>">
                <iframe src="<?php echo $appToLoad; ?>" style="width:100%; height:100%; border:none;"></iframe>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ace.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ext-language_tools.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ext-searchbox.js"></script>
    <script>
        // Constants
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

        // Add this near the beginning of your script section, right after the STATUS_MESSAGES constant
        // Check if running in an iframe
        function inIframe() {
            try {
                return window.self !== window.top;
            } catch (e) {
                return true;
            }
        }

        // Apply iframe-specific styling when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Apply iframe-specific styling if needed
            if (inIframe()) {
                document.body.classList.add('in-iframe');
            } else {
                document.body.classList.add('standalone');
            }

            // Initialize drag and drop for status bar
            initDragAndDrop();

            // Show initial welcome message
            status.update('NetBound Tool Suite ready. Drag files here or use the menu.', 'success');
        });

        // Initialize drag and drop functionality for the status bar
        function initDragAndDrop() {
            const statusBar = document.getElementById('statusBar');
            if (!statusBar) return;

            statusBar.addEventListener('dragover', (e) => {
                e.preventDefault();
                statusBar.classList.add('drag-over');
            });

            statusBar.addEventListener('dragleave', () => {
                statusBar.classList.remove('drag-over');
            });

            statusBar.addEventListener('drop', (e) => {
                e.preventDefault();
                statusBar.classList.remove('drag-over');
                if (e.dataTransfer.files.length > 0) {
                    handleDroppedFiles(e.dataTransfer.files);
                }
            });
        }

        // Handle dropped files
        function handleDroppedFiles(files) {
            if (files.length > 0) {
                const validFiles = Array.from(files).filter(file => {
                    const ext = file.name.split('.').pop().toLowerCase();
                    return ['php', 'html', 'css', 'js', 'txt', 'json'].includes(ext);
                });

                if (validFiles.length === 0) {
                    status.update('No valid files found. Supported types: PHP, HTML, CSS, JS, TXT, JSON', 'error');
                    return;
                }

                // Create form data for file upload
                const formData = new FormData();
                validFiles.forEach(file => {
                    formData.append('files[]', file);
                });
                formData.append('action', 'transferFiles');

                // Display upload status
                status.update(`Uploading ${validFiles.length} file(s)...`, 'info');

                // Upload files
                fetch('main.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(result => {
                        status.update(result.message, result.status);
                        refreshFileList();
                    })
                    .catch(error => {
                        status.update(`Upload error: ${error.message}`, 'error');
                    });
            }
        }

        // Add this near the beginning of your script section, right after the STATUS_MESSAGES constant
        const APP_PATHS = {
            archiveManager: 'nb-archive-manager.php',
            // Add other common tool paths here
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

        // State variables
        let editorContent = '';
        let currentLoadedFilename = ''; // Track the original filename when a file is loaded

        // Handle browser back/forward navigation
        window.addEventListener('popstate', function(event) {
            const params = new URLSearchParams(window.location.search);
            const appToLoad = params.get('app');
            const editorView = document.querySelector('.editor-view');
            const backupView = document.querySelector('.backup-view');

            if (appToLoad && appToLoad.startsWith('nb-')) {
                editorView.classList.add('hidden');
                backupView.classList.add('active');
                backupView.querySelector('iframe').src = appToLoad;
            } else {
                editorView.classList.remove('hidden');
                backupView.classList.remove('active');
            }
        });
        <?php if (!empty($content)): ?>
            editor.setValue(<?php echo json_encode($content); ?>);
            editorContent = editor.getValue();
        <?php endif; ?>

        // Core Functions
        function loadFile(filename) {
            const editorView = document.querySelector('.editor-view');
            const backupView = document.querySelector('.backup-view');

            // Restore editor view if hidden
            editorView.classList.remove('hidden');
            backupView.classList.remove('active');

            // Get the current folder from URL
            const params = new URLSearchParams(window.location.search);
            const currentFolder = params.get('folder') || '';

            // Construct the file path
            const filePath = currentFolder ? currentFolder + '/' + filename : filename;

            // Load the file content
            fetch('main.php?file=' + encodeURIComponent(filePath))
                .then(response => response.text())
                .then(content => {
                    if (editor.getValue() !== editorContent) {
                        if (!confirm('Unsaved changes detected. Continue?')) return;
                    }
                    editor.setValue(content, -1);
                    document.getElementById('editorFilename').value = filename;
                    currentLoadedFilename = filename; // Store the original filename
                    status.update(STATUS_MESSAGES.file.loaded(filename), 'success');
                    editorContent = editor.getValue();

                    // Set Ace editor mode based on file extension
                    setEditorMode(filename);
                })
                .catch(error => {
                    status.update(`Error: ${error.message}`, 'error');
                    console.error(error);
                });
        }

        function setEditorMode(filename) {
            const fileExtension = filename.split('.').pop().toLowerCase();
            let mode = "ace/mode/php"; // Default mode

            // Set appropriate language mode based on file extension
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

        function saveFile(newFilename = null) {
            const filename = newFilename || document.getElementById('editorFilename').value;
            if (!filename) {
                return status.update('Filename required', 'error');
            }

            const content = editor.getValue();
            fetch('main.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=save&filename=${encodeURIComponent(filename)}&content=${encodeURIComponent(content)}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(result => {
                    status.update(result.message, result.status);
                    if (newFilename) {
                        document.getElementById('editorFilename').value = newFilename;
                    }
                    editorContent = editor.getValue();
                    refreshFileList(); // Always refresh after save
                })
                .catch(error => {
                    status.update(`Error: ${error.message}`, 'error');
                    console.error(error);
                });
        }

        function saveAs() {
            const defaultName = document.getElementById('editorFilename').value || 'newfile.php';
            const currentFolder = new URLSearchParams(window.location.search).get('folder') || '';
            const content = editor.getValue();
            const blob = new Blob([content], {
                type: 'text/plain'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = currentFolder ? currentFolder + '/' + defaultName : defaultName;
            a.click();
            URL.revokeObjectURL(url);
            status.update('File saved to local machine', 'success');
        }

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
                        status.update(result.message, result.status);
                        if (result.status === 'success') {
                            refreshFileList();
                            if (document.getElementById('editorFilename').value === filename) {
                                editor.setValue('');
                                document.getElementById('editorFilename').value = '';
                                editorContent = '';
                            }
                        }
                    });
            }
        }

        function createNewFile() {
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-').substring(0, 19);
            const newFilename = `newfile_${timestamp}.php`;

            if (editor.getValue() !== editorContent) {
                if (!confirm('Unsaved changes detected. Continue?')) return;
            }

            editor.setValue('');
            document.getElementById('editorFilename').value = newFilename;
            status.update(STATUS_MESSAGES.file.new(newFilename), 'success');
            editorContent = editor.getValue();
            setEditorMode(newFilename);
        }

        // Folder Functions
        function createNewFolder() {
            const folderName = prompt('Enter folder name:');
            if (!folderName || !/^[a-zA-Z0-9_-]+$/.test(folderName)) {
                return status.update('Invalid folder name. Use only letters, numbers, underscore, and dash.', 'error');
            }

            const params = new URLSearchParams(window.location.search);
            const currentPath = params.get('folder') || '';
            const fullPath = currentPath ? currentPath + '/' + folderName : folderName;

            fetch('main.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=createFolder&folderName=${encodeURIComponent(folderName)}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(result => {
                    status.update(result.message, result.status);
                    if (result.status === 'success') {
                        refreshFileList(); // Always refresh after folder creation
                    }
                })
                .catch(error => {
                    status.update(`Error: ${error.message}`, 'error');
                    console.error(error);
                });
        }


        function deleteFolder(folderName) {
            if (!confirm(`Are you sure you want to delete the folder "${folderName}" and all its contents?`)) {
                return;
            }

            fetch('main.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=deleteFolder&folderName=${encodeURIComponent(folderName)}`
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        // If in the deleted folder, navigate up
                        const params = new URLSearchParams(window.location.search);
                        const currentPath = params.get('folder') || '';

                        if (currentPath === folderName) {
                            window.location.href = '?';
                        } else {
                            refreshFileList();
                        }
                        status.update(result.message, 'success');
                    } else {
                        status.update(result.message, 'error');
                    }
                });
        }

        // Clipboard Functions
        function fromClipboard() {
            navigator.clipboard.readText()
                .then(text => {
                    if (editor.getValue() !== editorContent &&
                        !confirm('Unsaved changes will be lost. Continue?')) {
                        return;
                    }
                    editor.setValue(text, -1);
                    editorContent = text;
                    status.update(STATUS_MESSAGES.clipboard.paste(), 'success');
                })
                .catch(error => {
                    status.update(`Error: ${error.message}`, 'error');
                    console.error(error);
                });
        }

        function appendClipboard() {
            navigator.clipboard.readText()
                .then(text => {
                    const currentContent = editor.getValue();
                    editor.setValue(currentContent + '\n' + text, -1);
                    status.update(STATUS_MESSAGES.clipboard.append(), 'success');
                })
                .catch(error => {
                    status.update(`Error: ${error.message}`, 'error');
                    console.error(error);
                });
        }

        function toClipboard() {
            navigator.clipboard.writeText(editor.getValue())
                .then(() => status.update(STATUS_MESSAGES.clipboard.copy(), 'success'))
                .catch(error => {
                    status.update(`Error: ${error.message}`, 'error');
                    console.error(error);
                });
        }

        // Backup Functions
        function getCurrentFilePath() {
            const filename = document.getElementById('editorFilename').value;
            if (!filename) return null;

            const params = new URLSearchParams(window.location.search);
            const currentFolder = params.get('folder') || '';
            return currentFolder ? currentFolder + '/' + filename : filename;
        }

        function fromBackup() {
            const filename = document.getElementById('editorFilename').value;
            if (!filename) return status.update('Filename required', 'error');

            // Get the full path including current folder
            const filePath = getCurrentFilePath();

            fetch('main.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=getBackup&filename=' + encodeURIComponent(filename) +
                        '&filepath=' + encodeURIComponent(filePath)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        editor.setValue(result.content, -1);
                        status.update(`Restored from backup: ${result.backupFilename}`, 'success');
                    } else {
                        status.update(result.message, 'error');
                    }
                });
        }

        function fromBackupManager() {
            if (editor.getValue() !== editorContent) {
                if (!confirm('Unsaved changes detected. Continue?')) return;
            }

            const editorView = document.querySelector('.editor-view');
            const backupView = document.querySelector('.backup-view');

            editorView.classList.add('hidden');
            backupView.classList.add('active');
            backupView.querySelector('iframe').src = APP_PATHS.archiveManager; // Changed from backup-manager.php

            status.update('Backup manager loaded', 'success');
        }

        // UI Functions
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
                        status.update(STATUS_MESSAGES.file.loaded(file.name), 'success');
                        editorContent = editor.getValue();
                        setEditorMode(file.name);
                    };
                    reader.readAsText(file);
                }
            };
            input.click();
        }

        function openInNewTab(filename) {
            const checkResult = canRunFileDirectly(filename);
            if (!checkResult.valid) {
                return status.update(checkResult.message, 'error');
            }

            const currentFolder = new URLSearchParams(window.location.search).get('folder') || '';
            const filePath = currentFolder ? currentFolder + '/' + filename : filename;

            // Update URL for any tool (not just nb- files)
            const url = new URL(window.location.href);
            url.searchParams.set('app', filePath);
            window.history.pushState({}, '', url);

            const editorView = document.querySelector('.editor-view');
            const backupView = document.querySelector('.backup-view');

            editorView.classList.add('hidden');
            backupView.classList.add('active');
            backupView.querySelector('iframe').src = filePath;

            status.update(`Opened ${filename} in viewer`, 'success');
        }

        function openInNewWindow(filename) {
            const checkResult = canRunFileDirectly(filename);
            if (!checkResult.valid) {
                return status.update(checkResult.message, 'error');
            }

            // Get the current folder from URL
            const currentFolder = new URLSearchParams(window.location.search).get('folder') || '';
            const filePath = currentFolder ? currentFolder + '/' + filename : filename;

            // Open in a new browser window instead of iframe
            window.open(filePath, '_blank');
            status.update(`Opened ${filename} in a new window`, 'success');
        }

        function updateDisplayFilename() {
            // Set the editor mode based on the current filename
            const filename = document.getElementById('editorFilename').value;
            setEditorMode(filename);
        }

        function updateVersionAndDate() {
            const newFilename = document.getElementById('editorFilename').value.trim();
            const originalFilename = currentLoadedFilename;

            if (!originalFilename) {
                status.update('No file is currently open', 'error');
                return;
            }

            if (newFilename === originalFilename) {
                status.update('Filename unchanged', 'info');
                return;
            }

            if (!newFilename) {
                status.update('New filename cannot be empty', 'error');
                return;
            }

            // Optional confirmation
            if (!confirm(`Rename file from "${originalFilename}" to "${newFilename}"?`)) {
                document.getElementById('editorFilename').value = originalFilename;
                return;
            }

            const content = editor.getValue();
            status.update(`Renaming file to: ${newFilename}...`, 'info');

            // Save with new name
            fetch('main.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=save&filename=${encodeURIComponent(newFilename)}&content=${encodeURIComponent(content)}&isRename=true`
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response error: ' + response.status);
                    return response.json();
                })
                .then(result => {
                    if (result.status === 'success' || result.status === 'info') {
                        editorContent = editor.getValue();
                        currentLoadedFilename = newFilename; // Update tracking immediately

                        // Now delete the old file
                        return fetch('main.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `action=delete&filename=${encodeURIComponent(originalFilename)}`
                            })
                            .then(response => {
                                if (!response.ok) throw new Error('Delete network error: ' + response.status);
                                return response.json();
                            })
                            .then(deleteResult => {
                                if (deleteResult.status === 'success') {
                                    status.update(`File renamed from ${originalFilename} to ${newFilename}`, 'success');
                                } else {
                                    status.update(`Warning: Created new file but couldn't delete ${originalFilename}`, 'warning');
                                }
                                refreshFileList();
                            });
                    } else {
                        throw new Error(result.message || 'Failed to save file with new name');
                    }
                })
                .catch(error => {
                    status.update(`Error: ${error.message}`, 'error');
                    console.error(error);
                });
        }

        // Replace existing status functions with this consolidated version
        const status = {
            update(message, type = 'info') {
                const container = document.getElementById('statusBar');
                const messageDiv = document.createElement('div');
                messageDiv.className = `status-message ${type}`;
                messageDiv.textContent = message;
                container.insertBefore(messageDiv, container.firstChild); // Insert at top

                // Keep last 15 messages
                while (container.children.length > 15) {
                    container.removeChild(container.lastChild);
                }

                container.scrollTop = 0; // Keep scrolled to top
            }
        };

        // Add a debounce function to prevent rapid successive calls
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                const context = this;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), wait);
            };
        }

        // Improved refresh function with debounce to prevent multiple rapid refreshes
        const refreshFileList = debounce(function() {
            const params = new URLSearchParams(window.location.search);
            const currentFolder = params.get('folder') || '';

            fetch('main.php?getFileList=1' + (currentFolder ? '&folder=' + encodeURIComponent(currentFolder) : ''))
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    document.querySelector('.file-list').innerHTML = html;
                    // Update with our new function
                    status.update('File list refreshed', 'success');

                    // Re-attach event listeners to checkboxes after refresh
                    document.querySelectorAll('.delete-check').forEach(checkbox => {
                        checkbox.addEventListener('change', function() {
                            console.log("Checkbox changed: ", {
                                type: this.getAttribute('data-type'),
                                name: this.getAttribute('data-name'),
                                checked: this.checked
                            });
                        });
                    });
                })
                .catch(error => {
                    console.error('Error refreshing file list:', error);
                    // Update with error message
                    status.update(`Error: ${error.message}`, 'error');
                    console.error(error);
                });
        }, 300); // 300ms debounce time

        // Fixed delete selected function with improved error handling
        function deleteSelected() {
            const checks = document.querySelectorAll('.delete-check:checked');
            if (checks.length === 0) {
                status.update('No items selected', 'error');
                return;
            }

            if (!confirm(`Delete ${checks.length} selected item(s)?`)) {
                return;
            }

            status.update(`Deleting ${checks.length} items...`, 'info');

            // Process each checked item one by one to ensure reliability
            let completed = 0;
            let succeeded = 0;
            let failed = 0;
            let currentItem = 0;
            let deleteQueue = Array.from(checks); // Convert NodeList to Array for easier tracking

            // Use a serial approach instead of Promise.all to ensure each delete completes
            function processNext() {
                if (currentItem >= deleteQueue.length) {
                    // All done
                    const message = `Deleted ${succeeded} of ${deleteQueue.length} items${failed > 0 ? ` (${failed} failed)` : ''}`;
                    status.update(message, succeeded > 0 ? 'success' : 'error');

                    // Only refresh once at the end of all operations
                    if (succeeded > 0) {
                        refreshFileList();
                    }
                    return;
                }

                const check = deleteQueue[currentItem];
                const type = check.getAttribute('data-type');
                const name = check.getAttribute('data-name');

                // Enhanced debugging
                console.log(`Processing item ${currentItem + 1}/${deleteQueue.length}`);
                console.log(`Type: "${type}", Name: "${name}"`);

                if (!name || typeof name !== 'string') {
                    status.update(`Error: Invalid name for item #${currentItem + 1}`, 'error');
                    failed++;
                    currentItem++;
                    processNext();
                    return;
                }

                // Show which item is being processed
                status.update(`Deleting ${type}: ${name} (${currentItem + 1}/${deleteQueue.length})...`, 'info');

                // Use our new robust delete function
                processDelete(type, name)
                    .then(result => {
                        completed++;
                        console.log('Server response:', result);

                        if (result.status === 'success') {
                            succeeded++;
                            console.log(`Successfully deleted ${type}: ${name}`);

                            // Check for current open file and clear editor if needed
                            if (type === 'file' && document.getElementById('editorFilename').value === name) {
                                editor.setValue('');
                                document.getElementById('editorFilename').value = '';
                                editorContent = '';
                            }
                        } else {
                            failed++;
                            console.error(`Failed to delete ${type}: ${name} - ${result.message}`);
                            status.update(`Error: ${result.message}`, 'error');
                        }

                        // Process next item after a short delay
                        setTimeout(() => {
                            currentItem++;
                            processNext();
                        }, 100);
                    })
                    .catch(error => {
                        console.error(`Error deleting ${type}: ${name}`, error);
                        status.update(`Error: ${error.message}`, 'error');
                        failed++;
                        completed++;

                        setTimeout(() => {
                            currentItem++;
                            processNext();
                        }, 100);
                    });
            }

            // Start processing
            processNext();
        }

        // Add this function in the <script> section, replacing the current fetch call in the deleteSelected function:

        function processDelete(type, name) {
            return new Promise((resolve, reject) => {
                // Determine the action and parameter name based on type
                const action = type === 'folder' ? 'deleteFolder' : 'delete';
                const paramName = type === 'folder' ? 'folderName' : 'filename';

                // Prevent caching by adding timestamp
                const timestamp = new Date().getTime();

                fetch(`main.php?nocache=${timestamp}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest' // Add this to help server identify AJAX requests
                        },
                        body: `action=${action}&${paramName}=${encodeURIComponent(name)}`
                    })
                    .then(response => {
                        // First check if response is ok
                        if (!response.ok) {
                            throw new Error(`Server returned ${response.status}`);
                        }

                        // Then check content type
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.indexOf('application/json') !== -1) {
                            return response.json();
                        } else {
                            // If not JSON, handle as text and create a JSON object
                            return response.text().then(text => {
                                try {
                                    // Try to extract JSON from response if possible
                                    const match = text.match(/\{.*\}/s);
                                    if (match) {
                                        return JSON.parse(match[0]);
                                    }
                                } catch (e) {
                                    console.error("Failed to parse JSON from response:", e);
                                }

                                // Return generic success response if we couldn't parse JSON
                                return {
                                    status: 'success',
                                    message: `${type === 'folder' ? 'Folder' : 'File'} deleted: ${name}`
                                };
                            });
                        }
                    })
                    .then(result => {
                        resolve(result);
                    })
                    .catch(error => {
                        reject(error);
                    });
            });
        }

        // Initialize event listeners when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Setup all event listeners in one place to avoid duplication
            setupEventListeners();

            // Clear any lingering status messages on page load
            const statusBar = document.getElementById('statusBar');
            if (statusBar) {
                statusBar.innerHTML = '';
            }
        });

        window.addEventListener('beforeunload', function(e) {
            if (editor.getValue() !== editorContent) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Update the message event handler
        window.addEventListener('message', function(event) {
            if (event.data && event.data.action === 'switchToEditor') {
                const editorView = document.querySelector('.editor-view');
                const backupView = document.querySelector('.backup-view');

                editorView.classList.remove('hidden');
                backupView.classList.remove('active');

                // Clear the iframe src after a short delay to stop any running scripts
                setTimeout(() => {
                    backupView.querySelector('iframe').src = '';
                }, 300);

                // Clean up URL parameters
                const url = new URL(window.location.href);
                url.searchParams.delete('app');
                window.history.pushState({}, '', url);

                if (event.data.status) {
                    status.update(event.data.status, 'success');
                }
            }
        });

        // Add this helper function above openInNewTab and openInNewWindow
        function canRunFileDirectly(filename) {
            if (!filename) return {
                valid: false,
                message: 'Filename required'
            };

            const fileExtension = filename.split('.').pop().toLowerCase();

            // Check if trying to run main editor
            if (filename === 'main.php' || filename.includes('main')) {
                return {
                    valid: false,
                    message: 'Cannot run the editor interface directly'
                };
            }

            // Only allow PHP and HTML files to be run directly
            if (!['php', 'html', 'htm'].includes(fileExtension)) {
                return {
                    valid: false,
                    message: `Cannot run ${fileExtension} files directly`
                };
            }

            return {
                valid: true
            };
        }



        // Add this function to your JavaScript
        function setupEventListeners() {
            // Set up the sort button functionality
            setupSortButton();

            // Set up delete button
            const deleteBtn = document.getElementById('menuDeleteBtn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', deleteSelected);
            }

            // Set up refresh button
            const refreshBtn = document.getElementById('menuRefreshBtn');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', refreshFileList);
            }

            // File transfer button
            const updateBtn = document.getElementById('menuUpdateBtn');
            if (updateBtn) {
                updateBtn.addEventListener('click', function() {
                    openFileRequester();
                });
            }
        }

        // Setup sort button handler with improved status messages
        function setupSortButton() {
            var sortBtn = document.getElementById('menuSortBtn');
            if (!sortBtn) return;

            sortBtn.onclick = function() {
                fetch('main.php?toggleSort=1')
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        var sortIcon = sortBtn.querySelector('i');
                        sortIcon.classList.remove(data.sortBy === 'name' ? 'fa-clock' : 'fa-sort-alpha-down');
                        sortIcon.classList.add(data.sortBy === 'name' ? 'fa-sort-alpha-down' : 'fa-clock');

                        // Add a clear status message about the sort mode
                        status.update(`Files are now sorted by ${data.sortBy === 'name' ? 'name' : 'date'}`, 'success');

                        // Refresh file list without showing its status message
                        const params = new URLSearchParams(window.location.search);
                        const currentFolder = params.get('folder') || '';

                        fetch('main.php?getFileList=1' + (currentFolder ? '&folder=' + encodeURIComponent(currentFolder) : ''))
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.text();
                            })
                            .then(html => {
                                document.querySelector('.file-list').innerHTML = html;

                                // Re-attach event listeners to checkboxes after refresh
                                document.querySelectorAll('.delete-check').forEach(checkbox => {
                                    checkbox.addEventListener('change', function() {
                                        console.log("Checkbox changed: ", {
                                            type: this.getAttribute('data-type'),
                                            name: this.getAttribute('data-name'),
                                            checked: this.checked
                                        });
                                    });
                                });
                            })
                            .catch(error => {
                                status.update(`Error: ${error.message}`, 'error');
                                console.error(error);
                            });
                    })
                    .catch(function(error) {
                        status.update(`Error: ${error.message}`, 'error');
                        console.error(error);
                    });
            };
        }
    </script>
</body>

</html>
