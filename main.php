<?php

// Error Reporting and Session Start
error_reporting(E_ALL);
// Only show errors for regular page views, not for JSON/AJAX responses
ini_set('display_errors', !isset($_SERVER['HTTP_X_REQUESTED_WITH']));

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

// Script to handle menu toggle
$scriptContent = <<<EOT
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
</script>
EOT;

// Include Font Awesome for hamburger icon
$fontAwesomeLink = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">';

// Session namespace
$sessionNamespace = 'netbound_' . md5(__DIR__);

/* -------------------------------------------------------------------------- */
/*                            Cache Control Headers                           */
/* -------------------------------------------------------------------------- */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Output the script and Font Awesome link
echo $scriptContent;
echo $fontAwesomeLink;

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

/**
 * Get CSS class based on file type
 */
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
function generateFileListHTML($files, $currentFilename = '', $currentPath = '', $isUserMode = false, $isNbFilesOnlyMode = false)
{
    ob_start();

    if ($isNbFilesOnlyMode) {
        // NB Files Only Mode: only show nb- files with simplified names and clean UI
        $nbFiles = [];

        // First collect all nb- files
        foreach ($files as $file) {
            $filename = basename($file);

            // Only include nb- prefixed PHP files
            if (
                is_file($file) && strpos($filename, 'nb-') === 0 &&
                pathinfo($filename, PATHINFO_EXTENSION) === 'php'
            ) {
                // Get simplified name
                $simplifiedName = str_replace('-', ' ', substr($filename, 3, -4));
                $simplifiedName = ucwords($simplifiedName);

                $nbFiles[] = [
                    'file' => $file,
                    'filename' => $filename,
                    'simplifiedName' => $simplifiedName,
                    'relativePath' => trim($currentPath ? $currentPath . '/' . $filename : $filename, '/')
                ];
            }
        }

        // Sort alphabetically by simplified name
        usort($nbFiles, function ($a, $b) {
            return strcasecmp($a['simplifiedName'], $b['simplifiedName']);
        });

        // Output the sorted files with a clean interface for end users
        foreach ($nbFiles as $fileData) {
            echo "<li class='file-entry'>
                <a href='#' onclick='openInIframe(\"" . htmlspecialchars($fileData['relativePath'], ENT_QUOTES) . "\"); return false;'
                   class='filename file-nb'
                   title='" . htmlspecialchars($fileData['filename'], ENT_QUOTES) . "'>
                   <i class='fas fa-folder'></i> " . htmlspecialchars($fileData['simplifiedName'], ENT_QUOTES) . "
                </a>
            </li>";
        }

        // Show message if no nb-files found
        if (count($nbFiles) === 0) {
            echo "<li class='empty-folder-message'>No tools available in this folder</li>";
        }
    } else if ($isUserMode) {
        // User mode: show regular file listing as per existing code
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
    } else {
        // Developer mode: show all files with controls
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
    // Ensure no output before headers
    if (ob_get_level()) ob_clean();

    // Set proper JSON headers
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');

    $currentSort = $_SESSION['sortBy'] ?? 'name'; // Default to 'name' if not set

    // Simple toggle between name and date modes only
    if ($currentSort === 'name') {
        $sortBy = 'date';
    } else {
        $sortBy = 'name';
    }

    $_SESSION['sortBy'] = $sortBy;

    echo json_encode(['status' => 'success', 'sortBy' => $sortBy]);
    exit;
} else {
    $sortBy = $_SESSION['sortBy'] ?? 'name'; // Default to alphabetical sort
}

// Get Folders Handler (combined with getFileList for efficiency)
if (isset($_GET['getFolders'])) {
    $folders = array_filter(glob('*'), 'is_dir');
    echo json_encode($folders);
    exit;
}

// Get File List Handler - now using centralized function
if (isset($_GET['getFileList'])) {
    $isUserMode = isset($_GET['userMode']) ? $_GET['userMode'] === '1' : false;
    $isNbFilesOnlyMode = isset($_GET['nbFilesOnlyMode']) ? $_GET['nbFilesOnlyMode'] === '1' : false;
    $files = getFileList($dir, $sortBy);
    $fileListHTML = generateFileListHTML($files, $_GET['edit'] ?? '', $currentPath, $isUserMode, $isNbFilesOnlyMode);
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
        // Ensure clean output buffer before sending JSON
        if (ob_get_level()) ob_clean();

        // Set proper JSON headers
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');

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
    <link href="main-styles.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>

<body>
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
            <div class="menu-vertical-bar">
                <button id="verticalMenuToggle" class="vertical-bar-button" title="Toggle Menu">
                    <i class="fas fa-bars"></i>
                </button>
                <button id="verticalHomeButton" class="vertical-bar-button" title="Home">
                    <i class="fas fa-home"></i>
                </button>
                <button id="verticalNewFolderButton" class="vertical-bar-button" title="New Folder">
                    <i class="fas fa-folder-plus"></i>
                </button>
                <button id="verticalRefreshButton" class="vertical-bar-button" title="Refresh File List">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button id="verticalSortButton" class="vertical-bar-button" title="Toggle Sort (Currently: <?php echo $sortIcon === 'fa-clock' ? 'sorted by date' : 'sorted alphabetically'; ?>)">
                    <i class="fas <?php echo $sortIcon; ?>"></i>
                </button>
                <button id="verticalTrashButton" class="vertical-bar-button" title="Delete Selected">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>
        <div class="menu-container">
            <div class="editor-view<?php echo $appToLoad ? ' hidden' : ''; ?>">
                <div class="editor" id="editorSection">

                    <div class="tool-header">
                        <div class="header-flex">
                            <!-- Title in middle - Hamburger menu button removed -->
                            <h1 class="tool-title">NetBound Tools: Editor</h1>

                            <!-- Former blue header buttons now in title area with swapped positions -->
                            <div class="header-buttons">
                                <button id="menuNewBtn" class="header-button" title="New file to edit" onclick="createNewFile()">
                                    <i class="fas fa-file"></i>
                                </button>
                                <button id="menuHomeBtn" class="header-button" title="Home - Reload program fresh without iframes" onclick="goHome()">
                                    <i class="fas fa-home"></i>
                                </button>
                                <?php if ($currentPath): ?>
                                    <a href="?folder=<?php echo dirname($currentPath); ?>" class="header-button up-level" title="Up Level"><i class="fas fa-level-up-alt"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div id="statusBox" class="status-box"></div>
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

                        <button type="button" class="command-button" onclick="loadTemplate()" title="Load template file">
                            <i class="fas fa-download"></i> From Template
                        </button>

                        <button type="button" class="command-button" onclick="fromAI()" title="Apply suggested changes in DIF format">
                            <i class="fas fa-robot"></i> From AI
                        </button>

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
                            <input type="text" id="editorFilename" class="info-input" value="" onchange="updateDisplayFilename()" placeholder="Filename" style="border: 1px solid blue;">
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
            file: {
                loaded: (filename) => `File loaded: ${filename}`,
                saved: (filename) => `File saved: ${filename}`,
                new: (filename) => `New file created: ${filename}`,
                deleted: (filename) => `File deleted: ${filename}`
            },
            clipboard: {
                copy: () => 'Content copied to clipboard',
                paste: () => 'Content pasted from clipboard',
                append: () => 'Content appended from clipboard'
            },
            backup: {
                loaded: (version) => `Backup loaded: ${version}`,
                notFound: () => 'No backup found for this file'
            }
        };

        // Add this near the beginning of your script section
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
        isUserMode = false; // Always use developer mode
        let isNbFilesOnlyMode = false; // Always use developer mode

        // Define the status object globally to make it accessible everywhere
        const status = {
            update: function(message, type = 'info') {
                const statusBox = document.getElementById('statusBox');
                if (!statusBox) return;

                const messageElement = document.createElement('div');
                messageElement.className = `message ${type}`;
                messageElement.textContent = message;

                // Check for existing messages and remove 'latest' class
                const existingMessages = statusBox.querySelectorAll('.message.latest');
                existingMessages.forEach(msg => msg.classList.remove('latest'));

                // Add 'latest' class to the new message
                messageElement.classList.add('latest');

                // Insert at the beginning (since display is flex-direction: column-reverse)
                statusBox.insertBefore(messageElement, statusBox.firstChild);

                // Limit the number of messages to keep the box manageable
                while (statusBox.children.length > 10) {
                    statusBox.removeChild(statusBox.lastChild);
                }

                return message;
            }
        };

        // Handle browser back/forward navigation
        window.addEventListener('popstate', function(event) {
            const params = new URLSearchParams(window.location.search);
            const appToLoad = params.get('app');
            const editorView = document.querySelector('.editor-view');
            const backupView = document.querySelector('.backup-view');

            if (appToLoad) {
                editorView.classList.add('hidden');
                backupView.classList.add('active');
                backupView.querySelector('iframe').src = appToLoad;
            } else {
                editorView.classList.remove('hidden');
                backupView.classList.remove('active');
                // Clear iframe src when switching back to editor
                setTimeout(() => {
                    backupView.querySelector('iframe').src = '';
                }, 300);
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
            }

            // Set the editor mode
            editor.session.setMode(mode);
        }

        function saveFile(newFilename) {
            const filename = newFilename || document.getElementById('editorFilename').value;
            const content = editor.getValue();

            if (!filename) {
                return status.update('Filename required', 'error');
            }

            status.update(`Saving: ${filename}...`, 'info');

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

            // Create a properly escaped date string for PHP comment
            const dateStr = new Date().toLocaleString().replace(/'/g, "\\'");
            // Use string concatenation instead of template literals to avoid PHP parser confusion
            editor.setValue("<?php\n// New file created: " + dateStr + "\n\n?>", -1);
            document.getElementById('editorFilename').value = newFilename;
            status.update(STATUS_MESSAGES.file.new(newFilename), 'success');
            editorContent = editor.getValue();
            setEditorMode(newFilename);

            // Show editor view if it was hidden
            const editorView = document.querySelector('.editor-view');
            const backupView = document.querySelector('.backup-view');
            editorView.classList.remove('hidden');
            backupView.classList.remove('active');
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

            // Set onchange handler for the file input
            input.onchange = function(event) {
                if (event.target.files && event.target.files.length > 0) {
                    const file = event.target.files[0];
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        editor.setValue(e.target.result, -1);
                        document.getElementById('editorFilename').value = file.name;
                        status.update(`File loaded for editing: ${file.name}`, 'success');
                        editorContent = editor.getValue();
                        setEditorMode(file.name);
                    };
                    reader.readAsText(file);
                }
            };

            // Click to open file selection dialog
            input.click();
        }

        function openInNewTab(filename) {
            const checkResult = canRunFileDirectly(filename);
            if (!checkResult.valid) {
                return status.update(checkResult.message, 'error');
            }

            // Get the current folder from URL
            const params = new URLSearchParams(window.location.search);
            const currentFolder = params.get('folder') || '';
            const filePath = currentFolder ? currentFolder + '/' + filename : filename;

            // Update URL for any tool (not just nb- files)
            const url = new URL(window.location.href);
            url.searchParams.set('app', filePath);
            window.history.pushState({}, '', url);

            // Hide editor view, show iframe view
            const editorView = document.querySelector('.editor-view');
            const backupView = document.querySelector('.backup-view');

            // Set the iframe source before showing it to avoid flicker
            const iframe = backupView.querySelector('iframe');

            // Force iframe refresh even if URL is the same
            iframe.src = 'about:blank';
            setTimeout(() => {
                iframe.src = filePath;

                // Now make the changes to visibility
                editorView.classList.add('hidden');
                backupView.classList.add('active');

                status.update(`Opened ${filename} in viewer`, 'success');
            }, 50);

            // Refresh file list without showing status message
            refreshFileList(false);
        }

        function openInIframe(filename) {
            // This function is similar to openInNewTab but more specifically for nb- files
            const checkResult = canRunFileDirectly(filename);
            if (!checkResult.valid) {
                return status.update(checkResult.message, 'error');
            }

            // Get the current folder from URL
            const params = new URLSearchParams(window.location.search);
            const currentFolder = params.get('folder') || '';
            const filePath = currentFolder ? currentFolder + '/' + filename : filename;

            // Update URL for the app
            const url = new URL(window.location.href);
            url.searchParams.set('app', filePath);
            window.history.pushState({}, '', url);

            // Hide editor view, show iframe view
            const editorView = document.querySelector('.editor-view');
            const backupView = document.querySelector('.backup-view');

            // Set the iframe source before showing it to avoid flicker
            const iframe = backupView.querySelector('iframe');

            // Force iframe refresh even if URL is the same
            iframe.src = 'about:blank';
            setTimeout(() => {
                iframe.src = filePath;

                // Now make the changes to visibility
                editorView.classList.add('hidden');
                backupView.classList.add('active');

                status.update(`Opened ${filename} in viewer`, 'success');
            }, 50);

            // Refresh file list without showing status message
            refreshFileList(false);
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
        const statusHelper = {
            update(message, type = 'info') {
                // Generate a random ID for one-time messages
                const id = 'msg_' + Math.random().toString(36).substr(2, 9);
                return statusManager.update(id, message, type);
            }
        };

        // Add drag-drop functionality to status box
        document.addEventListener('DOMContentLoaded', () => {
            const statusBox = document.getElementById('statusBox'); // Changed from statusBar
            if (statusBox) {
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
                        status.update(`File dropped: ${file.name}`, 'info');

                        // Read the file
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            editor.setValue(e.target.result, -1);
                            document.getElementById('editorFilename').value = file.name;
                            status.update(`Loaded: ${file.name}`, 'success');
                            editorContent = editor.getValue();
                            setEditorMode(file.name);
                        };
                        reader.readAsText(file);
                    }
                });
            }
        });

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
        const refreshFileList = debounce(function(showStatus = true) {
            const params = new URLSearchParams(window.location.search);
            const currentFolder = params.get('folder') || '';

            // Set the mode parameter based on current state
            let modeParam = 'userMode=0';
            if (isUserMode) {
                modeParam = 'userMode=1';
            } else if (isNbFilesOnlyMode) {
                modeParam = 'nbFilesOnlyMode=1';
            }

            // Add cache-busting parameter
            const timestamp = new Date().getTime();

            fetch(`main.php?getFileList=1&${modeParam}${currentFolder ? '&folder=' + encodeURIComponent(currentFolder) : ''}&_=${timestamp}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
                    const fileListContainer = document.querySelector('.menu-content'); // Target the container holding breadcrumbs and list
                    if (!fileListContainer) {
                        console.error("Error: '.menu-content' container not found for file list update.");
                        status.update('UI Error: Could not refresh file list container.', 'error');
                        return;
                    }
                    // Replace the content of the container, which includes breadcrumbs and the file list itself
                    fileListContainer.innerHTML = html;

                    // Add nb-files-only-mode class to file list *element* if needed (assuming list has class .file-list)
                    const fileListElement = fileListContainer.querySelector('.file-list');
                    if (fileListElement) {
                        if (isNbFilesOnlyMode) {
                            fileListElement.classList.add('nb-files-only-mode');
                        } else {
                            fileListElement.classList.remove('nb-files-only-mode');
                        }
                    } else {
                        console.warn("'.file-list' element not found within refreshed content.");
                    }


                    // Update status only when explicitly requested
                    if (showStatus) {
                        status.update('File list refreshed', 'success');
                    }

                    // Highlight currently loaded file if it's visible in the new list
                    if (currentLoadedFilename) {
                        const currentFileEntry = fileListContainer.querySelector(`.file-entry[data-filename="${currentLoadedFilename}"]`);
                        if (currentFileEntry) {
                            currentFileEntry.classList.add('current-edit');
                        }
                    }

                })
                .catch(error => {
                    console.error('Error refreshing file list:', error);
                    if (showStatus) {
                        status.update(`Error refreshing file list: ${error.message}`, 'error');
                    }
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

        // Add this function to your JavaScript
        function setupEventListeners() {
            const fileList = document.querySelector('.file-list');

            // Use event delegation for file list interactions
            if (fileList) {
                fileList.addEventListener('click', function(event) {
                    const target = event.target;
                    const fileEntry = target.closest('.file-entry, .folder-entry');

                    if (!fileEntry) return; // Clicked outside an entry

                    const filename = fileEntry.dataset.filename;
                    const type = fileEntry.dataset.type; // 'file' or 'folder'

                    // Handle folder clicks
                    if (type === 'folder' && target.closest('a.folder-link')) {
                        event.preventDefault();
                        const folderPath = fileEntry.dataset.path;
                        // Update URL and refresh list for the new folder
                        const url = new URL(window.location);
                        url.searchParams.set('folder', folderPath);
                        window.history.pushState({}, '', url);
                        refreshFileList(true); // Refresh for the new folder
                        return; // Stop further processing
                    }

                    // Handle file load clicks (on the filename link itself)
                    if (type === 'file' && target.closest('a.filename')) {
                        event.preventDefault();
                        loadFile(filename);
                        // Highlight the selected file
                        document.querySelectorAll('.file-entry.current-edit').forEach(el => el.classList.remove('current-edit'));
                        fileEntry.classList.add('current-edit');
                        return; // Stop further processing
                    }

                    // Handle "Open in Tab" clicks
                    if (target.closest('button.open-tab-btn')) {
                        openInNewTab(filename);
                        return;
                    }

                    // Handle "Open in Window" clicks
                    if (target.closest('button.open-window-btn')) {
                        openInNewWindow(filename);
                        return;
                    }

                    // Handle "Delete" button clicks (individual file/folder)
                    if (target.closest('button.delete-btn')) {
                        // We need the processDelete function or similar logic here
                        // For now, let's assume confirmDelete handles single item deletion confirmation
                        // and processDelete handles the actual fetch call.
                        if (confirm(`Are you sure you want to delete ${type}: ${filename}?`)) {
                            status.update(`Deleting ${type}: ${filename}...`, 'info');
                            processDelete(type, filename)
                                .then(result => {
                                    if (result.success) {
                                        status.update(result.message || `Deleted ${type} ${filename}`, 'success');
                                        refreshFileList(false); // Refresh list without status
                                    } else {
                                        status.update(result.message || `Failed to delete ${type} ${filename}`, 'error');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error deleting item:', error);
                                    status.update(`Error deleting ${type} ${filename}: ${error}`, 'error');
                                });
                        }
                        return;
                    }
                });

                // Handle Up Level click (delegated to breadcrumb container)
                const breadcrumb = document.querySelector('.breadcrumb'); // Assuming breadcrumb is outside file-list but updated with it
                if (breadcrumb) {
                    breadcrumb.addEventListener('click', function(event) {
                        if (event.target.closest('a.up-level')) {
                            event.preventDefault();
                            const currentFolder = new URLSearchParams(window.location.search).get('folder') || '';
                            if (!currentFolder) return; // Already at root

                            const parentFolder = currentFolder.substring(0, currentFolder.lastIndexOf('/'));
                            const url = new URL(window.location);
                            if (parentFolder) {
                                url.searchParams.set('folder', parentFolder);
                            } else {
                                url.searchParams.delete('folder');
                            }
                            window.history.pushState({}, '', url);
                            refreshFileList(true);
                        }
                    });
                }
            }


            // Set up the sort button functionality
            setupSortButton();

            // Set up delete button (for selected items)
            const deleteBtn = document.getElementById('menuDeleteBtn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', deleteSelected);
            }

            // Set up refresh button
            const refreshBtn = document.getElementById('menuRefreshBtn');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', () => refreshFileList(true));
            }

            // File transfer button (Upload)
            const updateBtn = document.getElementById('menuUpdateBtn');
            if (updateBtn) {
                updateBtn.addEventListener('click', function() {
                    openFileRequester(); // Assuming this handles file upload input
                });
            }

            // Set up menu toggle button (for mobile view)
            const menuToggleBtn = document.getElementById('menuToggle');
            if (menuToggleBtn) {
                menuToggleBtn.addEventListener('click', toggleMenu); // Assuming toggleMenu exists
            }

            // Set modeToggleBtn to do nothing - always stay in developer mode
            const modeToggleBtn = document.getElementById('menuToggleMode');
            if (modeToggleBtn) {
                modeToggleBtn.innerHTML = '<i class="fas fa-code"></i>';
                modeToggleBtn.title = "Developer Mode";
                // No event listener - we want to keep it in developer mode
            }

            // Set up create folder button
            const createFolderBtn = document.getElementById('menuCreateFolderBtn');
            if (createFolderBtn) {
                createFolderBtn.addEventListener('click', createNewFolder);
            }

            // Add other general listeners not tied to file-list content here
            // e.g., listeners for editor buttons, clipboard, etc.
            document.getElementById('saveBtn')?.addEventListener('click', () => saveFile());
            document.getElementById('saveAsBtn')?.addEventListener('click', saveAs);
            document.getElementById('newFileBtn')?.addEventListener('click', createNewFile);
            document.getElementById('fromClipboardBtn')?.addEventListener('click', fromClipboard);
            document.getElementById('appendClipboardBtn')?.addEventListener('click', appendClipboard);
            document.getElementById('toClipboardBtn')?.addEventListener('click', toClipboard);
            document.getElementById('fromBackupBtn')?.addEventListener('click', fromBackup);
            document.getElementById('backupManagerBtn')?.addEventListener('click', fromBackupManager);
            document.getElementById('renameBtn')?.addEventListener('click', updateVersionAndDate);
            // Add listener for the template button if it exists or is created dynamically
            // document.getElementById('templateBtn')?.addEventListener('click', loadTemplate); // Example
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
                        if (sortIcon) {
                            sortIcon.classList.remove(data.sortBy === 'name' ? 'fa-clock' : 'fa-sort-alpha-down');
                            sortIcon.classList.add(data.sortBy === 'name' ? 'fa-sort-alpha-down' : 'fa-clock');
                        }

                        // Add a clear status message about the sort mode
                        status.update(`Files are now sorted by ${data.sortBy === 'name' ? 'name' : 'date'}`, 'success');

                        // Use the main refreshFileList function instead of duplicating code
                        refreshFileList(false);
                    })
                    .catch(function(error) {
                        status.update(`Error: ${error.message}`, 'error');
                        console.error(error);
                    });
            };
        }

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

        // Add these functions for cookie management
        function setModeCookie(mode) {
            // Set cookie to expire in 30 days
            const expiryDate = new Date();
            expiryDate.setDate(expiryDate.getDate() + 30);
            document.cookie = `editorViewMode=${mode};expires=${expiryDate.toUTCString()};path=/`;
        }

        function getModeCookie() {
            const cookieValue = document.cookie
                .split('; ')
                .find(row => row.startsWith('editorViewMode='));

            if (cookieValue) {
                return cookieValue.split('=')[1];
            }
            return null;
        }

        // Update the DOMContentLoaded event handler to check for cookie
        document.addEventListener('DOMContentLoaded', function() {
            // Setup all event listeners in one place using delegation where possible
            setupEventListeners(); // Call the revised setup function

            // Check for saved mode preference in cookie
            const savedMode = getModeCookie();
            const toggleButton = document.getElementById('menuToggleMode');

            if (savedMode === 'developer') {
                // Set to developer mode
                isUserMode = false;
                isNbFilesOnlyMode = false;

                if (toggleButton) {
                    toggleButton.innerHTML = '<i class="fas fa-code"></i>';
                    toggleButton.title = "Developer Mode";
                }
            } else if (savedMode === 'user') {
                // Set to user mode
                isUserMode = true;
                isNbFilesOnlyMode = false;

                if (toggleButton) {
                    toggleButton.innerHTML = '<i class="fas fa-user"></i>';
                    toggleButton.title = "User Mode";
                }
            } else {
                // Default to nbFilesOnly mode
                isUserMode = false;
                isNbFilesOnlyMode = true;

                if (toggleButton) {
                    toggleButton.innerHTML = '<i class="fas fa-tools"></i>';
                    toggleButton.title = "Tool Files Only Mode";
                }
            }

            // Clear any lingering status messages on page load
            const statusBox = document.getElementById('statusBox');
            if (statusBox) {
                statusBox.innerHTML = '';
            }

            // Display initial status message
            status.update('Editor active. Select a file to edit or run, or drag here.', 'success');

            // Load the file list with the correct mode
            refreshFileList(false); // Load silently

            // FORCE hide menu on initial load
            const menu = document.getElementById('menuPanel');
            const menuOverlayElement = document.getElementById('menuOverlay');

            // Always hide the menu on load, regardless of screen size
            if (menu) {
                menu.style.left = '-250px';
                menu.classList.remove('active');
            }

            if (menuOverlayElement) {
                menuOverlayElement.classList.remove('active');
                menuOverlayElement.style.display = 'none';
            }

            // Remove any resize event listeners that might override our settings
            const oldResize = window.onresize;
            window.onresize = null;

            // Add our own resize handler that keeps the menu hidden
            window.addEventListener('resize', function() {
                // Keep menu hidden on resize unless explicitly opened by user
                if (menu && !menu.classList.contains('active')) {
                    menu.style.left = '-250px';
                }
            });

            // Add vertical tab functionality
            const homeButton = document.getElementById('homeButton');
            if (homeButton) {
                homeButton.addEventListener('click', function() {
                    goHome();
                });
            }

            const menuToggleBtn = document.getElementById('menuToggleBtn');
            if (menuToggleBtn) {
                menuToggleBtn.addEventListener('click', function() {
                    toggleMenu();
                });
            }

            if (menuOverlayElement) {
                menuOverlayElement.addEventListener('click', function() {
                    closeMenu();
                });
            }

            closeMenu();
        });

        // Updated toggleMenu function - v2.4
        function toggleMenu() {
            const menu = document.getElementById('menuPanel');
            const menuOverlay = document.getElementById('menuOverlay');

            if (menu.classList.contains('active')) {
                // Menu is already open, close it
                menu.classList.remove('active');
                menu.style.left = '-250px';
                menuOverlay.style.display = 'none';
            } else {
                // Menu is closed, open it
                menu.classList.add('active');
                menu.style.left = '0';
                menuOverlay.style.display = 'block';
            }
        }

        // Function to close the menu - v2.4
        function closeMenu() {
            const menu = document.getElementById('menuPanel');
            const menuOverlay = document.getElementById('menuOverlay');

            if (menu && menuOverlay) {
                menu.classList.remove('active');
                menu.style.left = '-250px';
                menuOverlay.style.display = 'none';
            }
        }

        // Close menu when clicking outside, opening a program, or adding content - v2.4
        function attachMenuCloseHandlers() {
            // Add overlay click handler
            const menuOverlay = document.getElementById('menuOverlay');
            if (menuOverlay) {
                menuOverlay.addEventListener('click', function() {
                    closeMenu();
                });
            }

            // Close when running a file or opening in iframe
            const runButtons = document.querySelectorAll('a[onclick*="openInNewTab"], a[onclick*="openInNewWindow"]');
            runButtons.forEach(btn => {
                btn.addEventListener('click', closeMenu);
            });

            // Close when adding content to editor
            document.querySelectorAll('button[onclick*="fromClipboard"], button[onclick*="openFileRequester"]').forEach(btn => {
                btn.addEventListener('click', closeMenu);
            });

            // Make entire vertical bar clickable to show menu
            const verticalBar = document.querySelector('.menu-vertical-bar');
            if (verticalBar) {
                verticalBar.addEventListener('click', function(e) {
                    // Only toggle the menu if clicking on the bar itself, not its buttons
                    if (e.target === verticalBar) {
                        toggleMenu();
                    }
                });
            }
        }

        // Add vertical tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Setup the vertical tab buttons
            const verticalHomeButton = document.getElementById('verticalHomeButton');
            const verticalMenuToggle = document.getElementById('verticalMenuToggle');

            if (verticalHomeButton) {
                verticalHomeButton.addEventListener('click', function() {
                    goHome();
                });
            }

            if (verticalMenuToggle) {
                verticalMenuToggle.addEventListener('click', function() {
                    toggleMenu();
                });
            }

            // Get the menu overlay element without redeclaring it
            const menuOverlayElement = document.getElementById('menuOverlay');
            if (menuOverlayElement) {
                menuOverlayElement.addEventListener('click', function() {
                    closeMenu();
                });
            }

            // Make sure the menu is initially closed
            closeMenu();
        });

        // Add event listeners for all vertical tab buttons
        document.addEventListener('DOMContentLoaded', function() {
            // Set up the vertical tab buttons
            document.getElementById('verticalHomeButton')?.addEventListener('click', function() {
                goHome();
            });

            document.getElementById('verticalNewFolderButton')?.addEventListener('click', function() {
                // First open the menu if it's not already open
                if (!menu.classList.contains('active')) {
                    toggleMenu();
                    // We set a flag to avoid double prompting
                    window.folderPromptPending = true;

                    // Wait for menu animation to complete
                    setTimeout(() => {
                        if (window.folderPromptPending) {
                            window.folderPromptPending = false;
                            createNewFolder();
                        }
                    }, 300);
                } else {
                    // If menu is already open, just create the folder
                    createNewFolder();
                }
            });

            document.getElementById('verticalRefreshButton')?.addEventListener('click', function() {
                refreshFileList(true);
            });

            document.getElementById('verticalSortButton')?.addEventListener('click', function() {
                fetch('main.php?toggleSort=1')
                    .then(response => response.json())
                    .then(data => {
                        const sortIcon = document.querySelector('#verticalSortButton i');
                        if (sortIcon) {
                            sortIcon.classList.remove(data.sortBy === 'name' ? 'fa-clock' : 'fa-sort-alpha-down');
                            sortIcon.classList.add(data.sortBy === 'name' ? 'fa-sort-alpha-down' : 'fa-clock');
                        }
                        status.update(`Files are now sorted by ${data.sortBy === 'name' ? 'name' : 'date'}`, 'success');
                        refreshFileList(false);
                    })
                    .catch(error => {
                        status.update(`Error toggling sort: ${error.message}`, 'error');
                    });
            });

            document.getElementById('verticalTrashButton')?.addEventListener('click', function() {
                deleteSelected();
            });

            document.getElementById('verticalMenuToggle')?.addEventListener('click', function() {
                toggleMenu();
            });
        });

        // Add touch gesture support for the vertical tab
        document.addEventListener('DOMContentLoaded', function() {
            const menu = document.getElementById('menuPanel');
            const verticalTab = document.querySelector('.menu-vertical-bar');
            const menuOverlay = document.getElementById('menuOverlay');

            if (!verticalTab || !menu || !menuOverlay) return;

            // Touch variables
            let touchStartX = 0;
            let touchEndX = 0;
            let isDragging = false;

            // Touch start handler
            verticalTab.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
                isDragging = true;
                verticalTab.style.transition = 'none'; // Disable transitions during drag
            }, {
                passive: true
            });

            // Touch move handler - animate the menu following the finger
            document.addEventListener('touchmove', function(e) {
                if (!isDragging) return;

                const currentX = e.changedTouches[0].screenX;
                const diffX = currentX - touchStartX;

                // Only move if dragging right (opening menu)
                if (diffX > 0 && diffX <= 250) {
                    menu.style.left = `${diffX - 250}px`;
                    menuOverlay.style.opacity = diffX / 250 * 0.5;
                    menuOverlay.style.display = 'block';
                }
            }, {
                passive: true
            });

            // Touch end handler
            document.addEventListener('touchend', function(e) {
                if (!isDragging) return;

                touchEndX = e.changedTouches[0].screenX;
                isDragging = false;
                verticalTab.style.transition = ''; // Re-enable transitions

                // Determine whether to open or close the menu
                const diffX = touchEndX - touchStartX;

                if (diffX > 70) { // Threshold for opening the menu
                    menu.style.left = '0px';
                    menu.classList.add('active');
                    menuOverlay.style.opacity = '0.5';
                    menuOverlay.style.display = 'block';
                } else {
                    menu.style.left = '-250px';
                    menu.classList.remove('active');
                    menuOverlay.style.opacity = '0';
                    menuOverlay.style.display = 'none';
                }
            }, {
                passive: true
            });

            // Touch cancel handler
            document.addEventListener('touchcancel', function() {
                if (!isDragging) return;

                isDragging = false;
                verticalTab.style.transition = ''; // Re-enable transitions
                menu.style.left = '-250px';
                menu.classList.remove('active');
                menuOverlay.style.opacity = '0';
                menuOverlay.style.display = 'none';
            }, {
                passive: true
            });
        });

        // Position the vertical bar to stay visible when menu is closed - v1.1
        document.addEventListener('DOMContentLoaded', function() {
            const menu = document.getElementById('menuPanel');
            const verticalBar = document.querySelector('.menu-vertical-bar');

            if (menu && verticalBar) {
                // Set the initial position
                verticalBar.style.left = '0px';

                // Update the position whenever the menu changes
                function updateVerticalBarPosition() {
                    if (menu.classList.contains('active')) {
                        // When menu is open, position bar at right edge of menu
                        verticalBar.style.left = '250px';
                    } else {
                        // When menu is closed, position bar at left edge of screen
                        verticalBar.style.left = '0px';
                    }
                }

                // Call on page load
                updateVerticalBarPosition();

                // Create a mutation observer to watch for menu class changes
                const observer = new MutationObserver(mutations => {
                    mutations.forEach(mutation => {
                        if (mutation.attributeName === 'class') {
                            updateVerticalBarPosition();
                        }
                    });
                });

                // Start observing the menu for class changes
                observer.observe(menu, {
                    attributes: true
                });

                // Also update position when menu is toggled
                document.addEventListener('click', function(e) {
                    if (e.target.closest('#menuToggle') ||
                        e.target.closest('#verticalMenuToggle')) {
                        setTimeout(updateVerticalBarPosition, 10);
                    }
                });
            }
        });

        // Updated vertical bar click handling - v2.7
        document.addEventListener('DOMContentLoaded', function() {
            const menu = document.getElementById('menuPanel');
            const verticalBar = document.querySelector('.menu-vertical-bar');
            const menuOverlay = document.getElementById('menuOverlay');

            // Direct click handler for the vertical bar itself
            if (verticalBar) {
                verticalBar.addEventListener('click', function(e) {
                    // Only toggle if clicking directly on the bar, not its children
                    if (e.target === verticalBar) {
                        e.preventDefault();
                        e.stopPropagation();
                        toggleMenu();
                    }
                });

                // Setup event delegation for all vertical bar buttons
                document.getElementById('verticalMenuToggle')?.addEventListener('click', toggleMenu);

                // Home button should load main.php directly
                document.getElementById('verticalHomeButton')?.addEventListener('click', goHome);

                // New Folder button should open menu first, then create folder without double-prompting - v2.8
                document.getElementById('verticalNewFolderButton')?.addEventListener('click', function() {
                    // First open the menu if it's not already open
                    if (!menu.classList.contains('active')) {
                        toggleMenu();
                        // We set a flag to avoid double prompting
                        window.folderPromptPending = true;

                        // Wait for menu animation to complete
                        setTimeout(() => {
                            if (window.folderPromptPending) {
                                window.folderPromptPending = false;
                                createNewFolder();
                            }
                        }, 300);
                    } else {
                        // If menu is already open, just create the folder
                        createNewFolder();
                    }
                });

                document.getElementById('verticalRefreshButton')?.addEventListener('click', function() {
                    refreshFileList(true);
                });
                document.getElementById('verticalSortButton')?.addEventListener('click', toggleSortMode);
                document.getElementById('verticalTrashButton')?.addEventListener('click', deleteSelected);
            }

            // Make sure the overlay closes the menu
            if (menuOverlay) {
                menuOverlay.addEventListener('click', closeMenu);
            }
        });

        // Function to go home - v2.9
        function goHome() {
            // Ensure we break out of any iframe and load main.php at the top level
            if (window !== window.top) {
                // We're in an iframe, so break out
                window.top.location.href = 'main.php';
            } else {
                // Already at top level, just reload main.php
                window.location.href = 'main.php';
            }
        }
    </script>
</body>

</html>
