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
                $fileA = basename($a);
                $fileB = basename($b);

                $aIsDir = is_dir($a);
                $bIsDir = is_dir($b);

                // Keep folders on top for name sort
                if ($aIsDir !== $bIsDir) {
                    return $aIsDir ? -1 : 1; // Folders at top for name sort
                }

                // If both are files, check for nb- prefix
                if (!$aIsDir && !$bIsDir) {
                    $isNbA = (strpos($fileA, 'nb-') === 0);
                    $isNbB = (strpos($fileB, 'nb-') === 0);

                    if ($isNbA !== $isNbB) {
                        return $isNbA ? -1 : 1;  // nb- files come first
                    }
                }

                // Otherwise sort alphabetically
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

            // Skip backup files and dot directories
            if (
                preg_match('/\(BAK-\w{3}\d{2}-S\d+\)\.\w+$/', $filename) ||
                $filename === '.' || $filename === '..'
                ||
                (is_dir($file) && $filename === 'backups')
            ) {
                continue;
            } else {
                $hasValidFiles = true;
            }

            $isDir = is_dir($file);
            $fileClass = $isDir ? getFolderClass($file) : getFileTypeClass($filename);
            $icon = $isDir ? 'fa-folder' : 'fa-file';
            $relativePath = $currentPath ? $currentPath . '/' . $filename : $filename;

            if ($isDir) {
                echo "<li class='file-entry folder-entry'>
                    <div class='file-controls'>
                        <a href='?folder=" . urlencode($relativePath) . "' title='Open Folder'>
                            <i class='fas fa-folder'></i>
                        </a>
                    </div>
                    <a href='?folder=" . urlencode($relativePath) . "'
                        class='filename'
                       title='" . htmlspecialchars($filename, ENT_QUOTES) . "'>
" .
                    htmlspecialchars($filename, ENT_QUOTES) . "
</a>
                    <div class='select-control'>
                        <input type='checkbox' class='delete-check' data-type='folder' data-name='" . htmlspecialchars($filename, ENT_QUOTES) . "'>
                    </div>
                </li>";
            } else {
                $isCurrentEdit = ($filename === $currentFilename);

                echo "<li class='file-entry " . ($isCurrentEdit ? "current-edit" : "") . "'>
                    <div class='file-controls'>
                        <button onclick='loadFile(\"" . addslashes($relativePath) . "\")' title='Edit File'><i class='fas fa-pencil-alt'></i></button>
                        <a href='#' onclick='openInNewTab(\"" . htmlspecialchars($relativePath, ENT_QUOTES) . "\")' title='Run File'><i class='fas fa-play'></i></a>

                    </div>
                    <a onclick='loadFile(\"" . addslashes($relativePath) . "\"); return false;' href='#'
                       class='filename " . $fileClass . "'
                       title='" . htmlspecialchars($filename, ENT_QUOTES) . "'><i class='fas " . $icon . "'></i> " .
                    htmlspecialchars($filename, ENT_QUOTES) . "</a>
                    <div class='select-control'>
                        <input type='checkbox' class='delete-check' data-type='file' data-name='" . htmlspecialchars($filename, ENT_QUOTES) . "'>
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
    exit;
} else {
    $sortBy = $_SESSION['sortBy'] ?? 'date';
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

    // Create folder handler
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
                    rrmdir($folderPath);
                    $response['status'] = 'success';
                    $response['message'] = 'Folder deleted: ' . $folderName;
                } catch (Exception $e) {
                    $response['message'] = 'Failed to delete folder: ' . $folderName;
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
        $filename = basename($_POST['filename'] ?? '');
        $content = $_POST['content'] ?? '';

        if (empty($filename)) {
            echo json_encode(['status' => 'error', 'message' => 'Filename required']);
        } else {
            $filePath = $currentDir . '/' . $filename;
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
    }
    // Backup retrieval handler
    else if ($action === 'getBackup') {
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
    }
    // File delete handler
    else if ($action === 'delete') {
        $filename = basename($_POST['filename'] ?? '');

        $response = ['status' => 'error', 'message' => ''];

        if (empty($filename)) {
            $response['message'] = 'Filename is required for deletion.';
        } else {
            $filePath = $currentDir . '/' . $filename;

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
/* -------------------------------------------------------------------------- */

$currentFilename = isset($_GET['file']) ? basename($_GET['file']) : '';
$content = '';
if (isset($_GET['file']) && !empty($_GET['file'])) {
    $content = file_get_contents($currentDir . '/' . $_GET['file']);
}

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
    <style>
        /* Main Styles from main-styles.css */
        html,

        /* Folder and Breadcrumb Styles */
        .folder-entry {
            background-color: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 2px;
        }

        .folder-entry:hover {
            background-color: #e9ecef;
        }

        .folder-normal {
            color: #b8860b !important;
        }

        .folder-special {
            color: #007bff !important;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            margin: 0 -15px 15px -15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .up-level {
            margin-left: -5px;
            padding: 5px;
            color: var(--primary-color);
            cursor: pointer;
            text-decoration: none;
        }

        .up-level:hover {
            background-color: rgba(0, 0, 0, 0.05);
            border-radius: 4px;
        }

        .folder-name {
            color: var(--primary-color);
            font-weight: bold;
        }


        .breadcrumb .separator {
            color: #6c757d;
        }

        body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #e9ecef;
            flex: 1;
            overflow: hidden;
        }

        :root {
            --primary-color: #0056b3;
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --background-color: #e9ecef;
            --header-height: 40px;
            --menu-width: 250px;
            --button-padding-y: 6px;
            --button-padding-x: 10px;
            --button-border-radius: 4px;
            --status-bar-padding: 8px 15px;
            --status-bar-margin: 10px 0;
            --success-color: #28a745;
            --error-color: #dc3545;
            --info-color: #17a2b8;
            --transition-speed: 0.3s;
        }

        /* Header Styles */
        .header {
            width: 100%;
            background-color: var(--primary-color);
            color: white;
            height: var(--header-height);
            box-sizing: border-box;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 0 20px;
            display: flex;
            box-shadow: 0 2px 5px rgb(0 0 0 / 10%);
        }

        .header .left-section,
        .header .right-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header .left-section .menu-title,
        .header .right-section .editor-title {
            font-size: 20px;
            font-weight: bold;
            line-height: 1;
        }

        /* Mobile menu toggle button */
        .mobile-menu-toggle {
            display: none;
            background: transparent;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 5px;
            margin-right: 10px;
        }

        /* File coloring */
        .nb-file {
            color: #007bff;
            font-weight: bold;
        }

        .php-file {
            color: #6f42c1;
        }

        .js-file {
            color: #e9b64d;
        }

        .css-file {
            color: #20c997;
        }

        .html-file {
            color: #e34c26;
        }

        .other-file {
            color: #6c757d;
        }

        .header .right-section button,
        .header .left-section .header-button {
            background-color: var(--secondary-color);
            color: var(--primary-color);
            border: none;
            padding: 4px 8px;
            border-radius: var(--button-border-radius);
            cursor: pointer;
            font-size: 14px;
        }

        .header .right-section button:hover,
        .header .left-section .header-button:hover {
            background-color: #e0e0e0;
        }

        .header .left-section .header-button.disabled {
            background-color: #ccc;
            color: #666;
            cursor: not-allowed;
        }

        /* Menu Layout */
        .menu {
            position: fixed;
            left: 0;
            top: var(--header-height);
            width: var(--menu-width);
            height: calc(100vh - var(--header-height));
            background-color: #fff;
            border-right: 1px solid #ddd;
            box-shadow: 2px 0 5px rgb(0 0 0 / 10%);
            z-index: 999;
            overflow-y: auto;
            transition: transform var(--transition-speed) ease;
        }

        /* Container Layout */
        .container {
            display: flex;
            margin-left: var(--menu-width);
            width: calc(100% - var(--menu-width));
            height: calc(100vh - var(--header-height));
            transition: margin-left var(--transition-speed) ease;
        }

        .menu-container {
            margin-top: 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 768px;
            margin-left: 0;
            margin-right: 0;
            padding: 0 20px;
            box-sizing: border-box;
        }

        .tool-iframe {
            width: 100%;
            height: calc(100vh - 160px);
            border: none;
            background: #fff;
        }

        .menu-tools {
            position: sticky;
            top: 0;
            background: #fff;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            z-index: 1003;
            height: 32px;
            margin-top: 5px;
            display: flex;
            gap: 8px;
        }

        .menu-content {
            padding: 15px;
            height: calc(100% - 52px);
            overflow: hidden auto;
            flex: 1;
        }

        .menu-header {
            padding: 10px;
            display: flex;
            justify-content: flex-end;
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 1003;
        }

        /* File List Styles */
        .file-list {
            padding: 10px 0;
            margin: 0;
            list-style: none;
        }

        .file-entry {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
        }

        .file-entry:hover {
            background-color: #e9ecef;
        }

        .file-entry.current-edit {
            background-color: #d1ecf1;
        }

        .file-controls {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .file-controls button,
        .file-controls a {
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 14px;
            cursor: pointer;
            padding: 2px;
        }

        .file-controls button:hover,
        .file-controls a:hover {
            color: #003d82;
        }

        .select-control {
            margin-left: 10px;
            display: flex;
            align-items: center;
        }

        .delete-check {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .filename {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding-left: 10px;
            font-size: 14px;
            color: var(--text-color);
            text-decoration: none;
            max-width: 160px;
        }

        /* Editor styles */
        .editor {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
            width: 100%;
            max-width: 768px;
            /* Set default max-width */
            margin: 0;
            box-sizing: border-box;
            height: calc(100vh - 160px);
            padding-bottom: 60px;
            transition: max-width 0.3s ease;
        }

        .editor.fullscreen {
            max-width: 100%;
            /* Override max-width for fullscreen */
        }

        .editor-container {
            position: relative;
            flex: 1;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 240px);
            min-height: 400px;
            margin: 20px 0;
            overflow: hidden;
        }

        #editor {
            flex: 1;
            width: 100%;
            height: 100%;
            min-height: 400px;
        }

        .button-row {
            position: sticky;
            bottom: 0;
            background: transparent;
            padding: 5px 0;
            z-index: 1000;
        }

        .editor-header {
            margin-top: 10px;
            padding: 5px 0;
            width: 100%;
            box-sizing: border-box;
        }

        .editor-title {
            margin: 0 0 15px;
            padding: 0;
            line-height: 1.2;
            color: var(--primary-color);
            font-weight: bold;
            font-size: 18px;
        }

        .header-top {
            display: flex;
            width: 100%;
            justify-content: space-between;
            align-items: center;
            gap: 5px;
        }

        .editor-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            align-items: center;
        }

        .label-line {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 8px;
            flex-wrap: nowrap;
        }

        .info-label {
            color: var(--text-color);
            font-weight: normal;
            width: 80px;
            font-size: 14px;
        }

        .info-input {
            flex: 1;
            font-size: 14px;
            padding: 6px 8px;
            border: 1px solid #ccc;
            border-radius: var(--button-border-radius);
        }

        /* Button Styles */
        .button-row {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
            margin-top: 5px;
        }

        .button-group {
            display: inline-flex;
            gap: 8px;
            flex-wrap: wrap;
            flex-shrink: 0;
            margin-top: 15px;
        }

        .command-button,
        .split-button {
            font-size: 14px;
            padding: var(--button-padding-y) var(--button-padding-x);
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            border-radius: var(--button-border-radius);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .command-button:hover,
        .split-button:hover {
            background-color: #003d82;
        }

        .split-button {
            display: flex;
            padding: 0;
            gap: 1px;
            background-color: white;
            align-items: stretch;
        }

        .split-button .main-part,
        .split-button .append-part {
            background-color: var(--primary-color);
            padding: var(--button-padding-y) var(--button-padding-x);
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .split-button .main-part {
            border-radius: var(--button-border-radius) 0 0 var(--button-border-radius);
        }

        .split-button .append-part {
            padding: var(--button-padding-y) 7px;
            border-radius: 0 var(--button-border-radius) var(--button-border-radius) 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .split-button i {
            margin-right: 5px;
        }

        .split-button .main-part:hover,
        .split-button .append-part:hover {
            background-color: #003d82;
        }

        /* Status Bar */
        .persistent-status-bar {
            width: 100%;
            height: 84px;
            min-height: 84px;
            max-height: 84px;
            overflow-y: auto;
            border: 1px solid #ddd;
            background: #fff;
            padding: 5px;
            margin: 10px 0;
            border-radius: 4px;
            display: flex;
            flex-direction: column-reverse;
            box-sizing: border-box;
        }

        .status-message {
            margin: 2px 0;
            padding: 2px 5px;
            border-radius: 3px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            font-size: 14px;
            color: var(--text-color);
        }

        .persistent-status-bar .status-message:first-child.success {
            background-color: var(--success-color);
            color: white;
        }

        .persistent-status-bar .status-message:first-child.error {
            background-color: var(--error-color);
            color: white;
        }

        .persistent-status-bar .status-message:first-child.info {
            background-color: var(--info-color);
            color: white;
        }

        /* Mobile adjustments */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }

            .menu {
                transform: translateX(-100%);
            }

            .menu.active {
                transform: translateX(0);
            }

            .container {
                margin-left: 0;
                width: 100%;
                transition: margin-left var(--transition-speed) ease;
            }

            .container.menu-active {
                margin-left: var(--menu-width);
            }

            .editor-view,
            .backup-view {
                left: 0;
                width: 100%;
            }

            .editor-view.hidden {
                transform: translateX(-100%);
            }

            .persistent-status-bar {
                margin: 10px -20px;
                width: calc(100% + 40px);
                border-left: none;
                border-right: none;
                border-radius: 0;
            }

            /* Prevent horizontal overflow */
            body.menu-active {
                overflow-x: hidden;
            }

            /* Overlay when menu is active */
            .menu-overlay {
                display: none;
                position: fixed;
                top: var(--header-height);
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 998;
            }

            .menu-overlay.active {
                display: block;
            }
        }

        /* Editor navigation controls */
        .editor-nav-controls {
            position: absolute;
            right: 25px;
            top: 15px;
            display: flex;
            flex-direction: row;
            z-index: 1000;
            background-color: rgba(50, 50, 50, 0.8);
            border-radius: 4px;
            padding: 2px;
        }

        .editor-nav-controls button {
            margin: 2px;
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 4px;
            background-color: rgba(80, 80, 80, 9);
            color: #ffffff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .editor-nav-controls button:hover {
            background-color: #0078d7;
        }

        /* Fullwidth mode */
        .editor-container.fullwidth {
            position: relative;
            width: 100%;
            max-width: 768px;
            margin-left: -10px;
            margin-right: -10px;
            z-index: 5;
            background-color: #272822;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }

        .editor-container.fullwidth .editor-nav-controls {
            top: 15px;
            right: 25px;
        }

        /* File type colors (specific) */
        .file-nb {
            color: #4287f5 !important;
            font-weight: bold;
        }

        .file-php {
            color: #9c27b0 !important;
        }

        .file-html {
            color: #e91e63 !important;
        }

        .file-css {
            color: #2196f3 !important;
        }

        .file-js {
            color: #ffc107 !important;
        }

        .file-json {
            color: #8bc34a !important;
        }

        .file-txt {
            color: #607d8b !important;
        }

        .file-other {
            color: #9e9e9e !important;
        }

        .empty-folder-message {
            text-align: center;
            padding: 30px 20px;
            margin-top: 20px;
            color: #666;
            font-style: italic;
            font-size: 15px;
            border: 1px dashed #ccc;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .folder-controls {
            display: flex;
            gap: 4px;
        }

        /* Views */
        .editor-view,
        .backup-view {
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 100%;
            position: absolute;
            top: var(--header-height);
            left: var(--menu-width);
            right: 0;
            transition: transform 0.3s ease-in-out;
        }

        .editor-view {
            transform: translateX(0);
            background: #fff;
            z-index: 2;
        }

        .backup-view {
            z-index: 1;
        }

        .editor-view.hidden {
            transform: translateX(-100%);
        }

        .backup-view.active {
            z-index: 3;
        }

        .backup-view iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="left-section">
            <button id="mobileMenuToggle" class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
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
            <button id="menuSortBtn" class="header-button" title="Toggle Sort (Currently: <?php echo $sortBy === 'date' ? 'by date' : 'alphabetical'; ?>)">
                <i class="fas <?php echo $sortIcon; ?>"></i>
            </button>
            <button id="menuDeleteBtn" class="header-button" title="Delete Selected" onclick="deleteSelected()">
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
            <div class="editor-view">
                <div class="editor" id="editorSection">
                    <div class="editor-header">
                        <div class="header-top">
                            <h1 class="editor-title">
                                <div class="editor-header">
                                    <div class="header-top">
                                        <h1 class="editor-title">
                                            NetBound Editor: <?php echo date("F j Y", filemtime(__FILE__)); ?></h1>
                                    </div>
                                </div>

                                <div class="persistent-status-bar" id="statusBar"></div>

                                <div class="button-group">
                                    <button type="button" class="command-button" onclick="openFileRequester()" title="From File">
                                        <i class="fas fa-folder-open"></i> From File
                                    </button>

                                    <div class="split-button">
                                        <div class="main-part" onclick="fromClipboard()">
                                            <i class="fas fa-clipboard"></i> From Clipboard
                                        </div>
                                        <div class="append-part" onclick="appendClipboard()">
                                            <i class="fas fa-plus"></i>
                                        </div>
                                    </div>
                                    <div class="split-button">
                                        <div class="main-part" onclick="undoFile()" title="Revert to last saved version">
                                            <i class="fas fa-undo"></i> Undo
                                        </div>
                                        <div class="append-part" onclick="fromBackupManager()" title="Open Backup Manager">
                                            <i class="fas fa-plus"></i>
                                        </div>
                                    </div>
                                </div>

                                <script>
                                    function undoFile() {
                                        const filename = document.getElementById('editorFilename').value;
                                        if (!filename) return updateStatus('Filename required', 'error');

                                        if (confirm(`Are you sure you wish to revert to: ${filename}?`)) {
                                            fromBackup();
                                        }
                                    }
                                </script>

                                <form method="POST" class="edit-form" style="display:flex;flex-direction:column;height:100%;" id="editorForm">
                                    <div class="editor-container" id="editorContainer">
                                        <div class="editor-nav-controls">
                                            <button type="button" onclick="scrollEditorTop()" title="Scroll to top">
                                                <i class="fas fa-arrow-up"></i>
                                            </button>
                                            <button type="button" onclick="scrollEditorBottom()" title="Scroll to bottom">
                                                <i class="fas fa-arrow-down"></i>
                                            </button>
                                            <button type="button" onclick="toggleEditorFullWidth()" title="Toggle fullscreen">
                                                <i class="fas fa-expand" id="fullwidthIcon"></i>
                                            </button>
                                        </div>
                                        <div id="editor"></div>
                                    </div>
                                    <div class="label-line">
                                        <span class="info-label">Filename:</span>
                                        <input type="text" id="editorFilename" class="info-input" value="" onchange="updateDisplayFilename()">
                                        <button type="button" class="command-button" onclick="updateVersionAndDate()">
                                            <i class="fas fa-sync-alt"></i> Rename
                                        </button>
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
                <?php if (!empty($content)): ?>
                    editor.setValue(<?php echo json_encode($content); ?>);
                    editorContent = editor.getValue();
                <?php endif; ?>

                // Core Functions
                function loadFile(filename) {
                    // Check if mobile view (window width <= 768px)
                    if (window.innerWidth <= 768) {
                        // Get the current folder from URL
                        const currentFolder = new URLSearchParams(window.location.search).get('folder') || '';
                        const filePath = currentFolder ? currentFolder + '/' + filename : filename;
                        // Close the menu if it's open
                        const menuPanel = document.getElementById('menuPanel');
                        if (menuPanel.classList.contains('active')) {
                            toggleMobileMenu();
                        }
                    }

                    const editorView = document.querySelector('.editor-view');
                    const backupView = document.querySelector('.backup-view');

                    // Restore editor view if hidden
                    editorView.classList.remove('hidden');
                    backupView.classList.remove('active');

                    fetch('main.php?file=' + encodeURIComponent(filePath))
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
                            setEditorMode(filename);
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
                        return updateStatus('Filename required', 'error');
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
                            editorContent = editor.getValue();
                            refreshFileList();
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
                    updateStatus('File saved to local machine', 'success');
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
                                updateStatus(result.message, result.status);
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
                    updateStatus(STATUS_MESSAGES.file.new(newFilename), 'success');
                    editorContent = editor.getValue();
                    setEditorMode(newFilename);
                }

                // Folder Functions
                function createNewFolder() {
                    const folderName = prompt('Enter folder name:');
                    if (!folderName || !/^[a-zA-Z0-9_-]+$/.test(folderName)) {
                        return updateStatus('Invalid folder name. Use only letters, numbers, underscore, and dash.', 'error');
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
                        .then(response => response.json())
                        .then(result => {
                            if (result.status === 'success') {
                                refreshFileList();
                                updateStatus(STATUS_MESSAGES.folder.created(folderName), 'success');
                            } else {
                                updateStatus(result.message, 'error');
                            }
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
                                updateStatus(result.message, 'success');
                            } else {
                                updateStatus(result.message, 'error');
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
                            updateStatus(STATUS_MESSAGES.clipboard.paste(), 'success');
                        })
                        .catch(() => updateStatus('Failed to read clipboard', 'error'));
                }

                function appendClipboard() {
                    navigator.clipboard.readText()
                        .then(text => {
                            const currentContent = editor.getValue();
                            editor.setValue(currentContent + '\n' + text, -1);
                            updateStatus(STATUS_MESSAGES.clipboard.append(), 'success');
                        })
                        .catch(() => updateStatus('Failed to read clipboard', 'error'));
                }

                function toClipboard() {
                    navigator.clipboard.writeText(editor.getValue())
                        .then(() => updateStatus(STATUS_MESSAGES.clipboard.copy(), 'success'))
                        .catch(() => updateStatus('Failed to copy to clipboard', 'error'));
                }

                // Backup Functions
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
                                updateStatus(STATUS_MESSAGES.backup.restored(), 'info');
                            } else {
                                updateStatus(result.message, 'error');
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
                    backupView.querySelector('iframe').src = 'backup-manager.php';

                    updateStatus('Backup manager loaded', 'success');
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
                                updateStatus(STATUS_MESSAGES.file.loaded(file.name), 'success');
                                editorContent = editor.getValue();
                                setEditorMode(file.name);
                            };
                            reader.readAsText(file);
                        }
                    };
                    input.click();
                }

                function openInNewTab(filename) {
                    if (!filename) return updateStatus('Filename required', 'error');

                    const fileExtension = filename.split('.').pop().toLowerCase();

                    // Check if trying to run main editor
                    if (filename === 'main.php' || filename.includes('main')) {
                        return updateStatus('Cannot run the editor interface directly', 'info');
                    }

                    // Only allow PHP and HTML files to be run directly
                    if (!['php', 'html', 'htm'].includes(fileExtension)) {
                        return updateStatus(`Cannot run ${fileExtension} files directly`, 'info');
                    }

                    const editorView = document.querySelector('.editor-view');
                    const backupView = document.querySelector('.backup-view');

                    // Get the current folder from URL
                    const currentFolder = new URLSearchParams(window.location.search).get('folder') || '';
                    const filePath = currentFolder ? currentFolder + '/' + filename : filename;

                    backupView.classList.add('active');
                    backupView.querySelector('iframe').src = filePath;
                }

                function updateDisplayFilename() {
                    // Placeholder for future functionality
                    const filename = document.getElementById('editorFilename').value;
                    setEditorMode(filename);
                }

                function updateVersionAndDate() {
                    const originalFilename = document.getElementById('editorFilename').value;
                    const newFilename = prompt('Enter new filename:', originalFilename);

                    if (newFilename && newFilename !== originalFilename) {
                        // First save content to new file, then delete old file
                        const content = editor.getValue();

                        // Save to new file
                        fetch('main.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `action=save&filename=${encodeURIComponent(newFilename)}&content=${encodeURIComponent(content)}`
                            })
                            .then(response => response.json())
                            .then(result => {
                                if (result.status === 'success' || result.status === 'info') {
                                    // Update UI first
                                    document.getElementById('editorFilename').value = newFilename;
                                    editorContent = editor.getValue();
                                    updateStatus(`File renamed to: ${newFilename}`, 'success');

                                    // Now delete the old file
                                    return fetch('main.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded'
                                        },
                                        body: `action=delete&filename=${encodeURIComponent(originalFilename)}`
                                    });
                                } else {
                                    throw new Error(result.message || 'Failed to save new file');
                                }
                            })
                            .then(response => response.json())
                            .then(deleteResult => {
                                if (deleteResult.status === 'success') {
                                    updateStatus(`Old file deleted: ${originalFilename}`, 'info');
                                } else {
                                    updateStatus(`Note: Could not delete old file: ${originalFilename}`, 'error');
                                }
                                refreshFileList();
                            })
                            .catch(error => {
                                updateStatus('Error during rename: ' + error.message, 'error');
                            });
                    }
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

                function refreshFileList() {
                    const params = new URLSearchParams(window.location.search);
                    fetch('main.php?getFileList=1' + (params.get('folder') ? '&folder=' + encodeURIComponent(params.get('folder')) : ''))
                        .then(response => response.text())
                        .then(html => {
                            document.querySelector('.file-list').innerHTML = html;
                        })
                        .catch(() => updateStatus('Failed to refresh file list', 'error'));
                }

                // Editor navigation functions
                function scrollEditorTop() {
                    editor.gotoLine(1);
                    editor.focus();
                    updateStatus('Scrolled to top', 'info');
                }

                function scrollEditorBottom() {
                    const lastRow = editor.session.getLength();
                    editor.gotoLine(lastRow, editor.session.getLine(lastRow - 1).length);
                    editor.focus();
                    updateStatus('Scrolled to bottom', 'info');
                }

                function toggleEditorFullWidth() {
                    const container = document.getElementById('editorContainer');
                    const icon = document.getElementById('fullwidthIcon');
                    const editorSection = document.getElementById('editorSection');

                    if (container.classList.contains('fullwidth')) {
                        container.classList.remove('fullwidth');
                        editorSection.classList.remove('fullscreen');
                        icon.classList.remove('fa-compress');
                        icon.classList.add('fa-expand');
                        updateStatus('Editor normal width', 'info');
                    } else {
                        container.classList.add('fullwidth');
                        editorSection.classList.add('fullscreen');
                        icon.classList.remove('fa-expand');
                        icon.classList.add('fa-compress');
                        updateStatus('Editor wide mode', 'info');
                    }

                    // Make sure the editor resizes correctly
                    setTimeout(() => editor.resize(), 100);
                }

                // Mobile menu toggle function
                function toggleMobileMenu() {
                    const menuPanel = document.getElementById('menuPanel');
                    const mainContainer = document.getElementById('mainContainer');
                    const menuOverlay = document.getElementById('menuOverlay');
                    const body = document.body;
                    const menuToggleIcon = document.querySelector('#mobileMenuToggle i');

                    menuPanel.classList.toggle('active');
                    mainContainer.classList.toggle('menu-active');
                    menuOverlay.classList.toggle('active');
                    body.classList.toggle('menu-active');

                    // Update icon based on menu state
                    if (menuPanel.classList.contains('active')) {
                        menuToggleIcon.classList.remove('fa-bars');
                        menuToggleIcon.classList.add('fa-times');
                    } else {
                        menuToggleIcon.classList.remove('fa-times');
                        menuToggleIcon.classList.add('fa-bars');
                    }
                }

                // Add event listener for mobile menu toggle
                document.getElementById('mobileMenuToggle').addEventListener('click', toggleMobileMenu);
                document.getElementById('menuOverlay').addEventListener('click', toggleMobileMenu);

                // Event Listeners
                document.getElementById('menuUpdateBtn').addEventListener('click', function() {
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.multiple = true;
                    input.onchange = async function(e) {
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
                                if (result.status === 'success' || result.status === 'partial') {
                                    refreshFileList();
                                }
                            });
                    };
                    input.click();
                });

                document.getElementById('menuSortBtn').addEventListener('click', function() {
                    fetch('main.php?toggleSort=1')
                        .then(() => {
                            refreshFileList();
                            // Toggle the sort button icon
                            const sortBtn = document.getElementById('menuSortBtn');
                            const sortIcon = sortBtn.querySelector('i');
                            if (sortIcon.classList.contains('fa-sort-alpha-down')) {
                                sortIcon.classList.remove('fa-sort-alpha-down');
                                sortIcon.classList.add('fa-clock');
                                sortBtn.setAttribute('title', 'Toggle Sort (Currently: by date)');
                            } else {
                                sortIcon.classList.remove('fa-clock');
                                sortIcon.classList.add('fa-sort-alpha-down');
                                sortBtn.setAttribute('title', 'Toggle Sort (Currently: alphabetical)');
                            }
                        });
                });

                document.getElementById('menuRefreshBtn').addEventListener('click', refreshFileList);

                window.addEventListener('beforeunload', function(e) {
                    if (editor.getValue() !== editorContent) {
                        e.preventDefault();
                        e.returnValue = '';
                    }
                });

                // Listen for messages from iframe
                window.addEventListener('message', function(event) {
                    if (event.data && event.data.action === 'switchToEditor') {
                        const editorView = document.querySelector('.editor-view');
                        const backupView = document.querySelector('.backup-view');

                        editorView.classList.remove('hidden');
                        backupView.classList.remove('active');

                        if (event.data.status) {
                            updateStatus(event.data.status, 'success');
                        }
                    }
                });

                // Listen for Escape key to exit fullscreen
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        const container = document.getElementById('editorContainer');
                        if (container.classList.contains('fullwidth')) {
                            toggleEditorFullWidth();
                        }
                    }
                });

                // Update resize handler to handle mobile menu state
                window.addEventListener('resize', function() {
                    // Reset menu state on larger screens
                    if (window.innerWidth > 768) {
                        const menuPanel = document.getElementById('menuPanel');
                        const mainContainer = document.getElementById('mainContainer');
                        const menuOverlay = document.getElementById('menuOverlay');
                        const body = document.body;
                        const menuToggleIcon = document.querySelector('#mobileMenuToggle i');

                        menuPanel.classList.remove('active');
                        mainContainer.classList.remove('menu-active');
                        menuOverlay.classList.remove('active');
                        body.classList.remove('menu-active');
                        menuToggleIcon.classList.remove('fa-times');
                        menuToggleIcon.classList.add('fa-bars');
                    }
                });
            </script>

            <script>
                // Update file transfer functionality to handle current folder
                function deleteSelected() {
                    const checks = document.querySelectorAll('.delete-check:checked');
                    if (checks.length === 0) {
                        updateStatus('No items selected', 'error');
                        return;
                    }

                    if (!confirm(`Delete ${checks.length} selected items?`)) {
                        return;
                    }

                    let promises = [];
                    let items = [];
                    checks.forEach(check => {
                        const type = check.dataset.type;
                        const name = check.dataset.name;
                        items.push(`${type}: ${name}`);

                        const promise = fetch('main.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `action=${type === 'folder' ? 'deleteFolder' : 'delete'}&${type}Name=${encodeURIComponent(name)}`
                        }).then(r => r.json());
                        promises.push(promise);
                    });

                    Promise.all(promises)
                        .then(() => {
                            updateStatus(`Deleted ${items.join(', ')}`, 'success');
                            refreshFileList();
                        })
                        .catch(() => updateStatus('Error deleting some items', 'error'));
                }
            </script>

            <script>
                function adjustEditorHeight() {
                    const headerHeight = document.querySelector('.header').offsetHeight;
                    const statusBarHeight = document.getElementById('statusBar').offsetHeight;
                    const filenameBoxHeight = document.querySelector('.label-line').offsetHeight;
                    const buttonRowHeight = document.querySelector('.button-row').offsetHeight;
                    const editorHeaderHeight = document.querySelector('.editor-header').offsetHeight;

                    const availableHeight = window.innerHeight - headerHeight - statusBarHeight - filenameBoxHeight - buttonRowHeight - editorHeaderHeight - 40;

                    document.getElementById('editorContainer').style.height = availableHeight + 'px';
                    editor.resize();
                }

                window.onload = adjustEditorHeight;
                window.addEventListener('resize', adjustEditorHeight);
            </script>
</body>

</html>
