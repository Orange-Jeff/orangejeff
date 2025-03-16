<?php
/*
| Library of functions and utilities of the application
|
| version  : 1.1.20
| author   : Antonio da Silva
| copyright: (c) 2022 by AdS Microsistemas
| created  : 14/04/2022 20:24:36
| updated  : 16/04/2022 23:39:57
+---------------------------------------------------------------------------- */
/*
| Filename: main.php
| Dependants: main.css, shared-styles.css, nb-*.php (loaded dynamically)
|
| Core file manager and loader for NetBound Tools. Handles file operations
| (create, delete, rename, backup), folder navigation, and dynamic loading
| of other PHP tools as applications.
|
| Functions:
| - getFileList($dir, $sortBy): Gets a sorted list of files and folders.
| - is_dir_empty($dir): Checks if a directory is empty.
| - rrmdir($dir): Recursively removes a directory and its contents.
| - getFolderClass($path): Returns a CSS class based on folder type.
| - getFileTypeClass($filename): Returns a CSS class based on file extension.
| - generateFileListHTML($files, $currentFilename, $currentPath): Generates HTML for the file list.
| - (Various request handlers for GET and POST actions)
*/

/* -------------------------------------------------------------------------- */
/*                                 Core Setup                                   */
/*                                 V-INC                                       */
/* -------------------------------------------------------------------------- */
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
                            <h1 class="editor-title">
                                NetBound Editor: <?php echo date("F j Y", filemtime(__FILE__)); ?></h1>
                        </div>
                    </div>

                    <div class="persistent-status-bar" id="statusBar"></div>

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
                backupView.querySelector('iframe').src = appToLoad + '"';
            } else {
                editorView.classList.remove('hidden');
                backupView.classList.remove('active');
            }
        });
    </script>
