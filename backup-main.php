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
$backupDir = __DIR__ . '/backups/';

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
 * Get sorted file list and directories based on current sort preference
 * Centralizes file listing logic to avoid duplication
 */
function getFileList($dir, $sortBy = 'date')
{
    // Get directories first (excluding . and .. and backups)
    $dirs = array_filter(glob($dir . '/*', GLOB_ONLYDIR), function ($d) {
        $basename = basename($d);
        return $basename !== '.' && $basename !== '..' && $basename !== 'backups';
    });

    // Get files
    $files = glob($dir . '/*.*', GLOB_BRACE);

    // Sort directories
    if (!empty($dirs)) {
        if ($sortBy === 'date') {
            usort($dirs, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });
        } else {
            usort($dirs, 'strcasecmp');
        }
    }

    // Sort files
    if (!empty($files)) {
        if ($sortBy === 'date') {
            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });
        } else {
            usort($files, function ($a, $b) {
                $fileA = basename($a);
                $fileB = basename($b);

                $isNbA = (strpos($fileA, 'nb-') === 0);
                $isNbB = (strpos($fileB, 'nb-') === 0);

                if ($isNbA && !$isNbB) return -1;
                if (!$isNbA && $isNbB) return 1;

                return strcasecmp($fileA, $fileB);
            });
        }
    }

    return ['directories' => $dirs, 'files' => $files];
}

/**
 * Get CSS class based on file extension
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
 * Generate HTML for file and directory list
 */
function generateFileListHTML($items, $currentFilename = '', $currentPath = '')
{
    ob_start();

    // Show directories first
    if (!empty($items['directories'])) {
        foreach ($items['directories'] as $dir) {
            $dirname = basename($dir);
            echo "<li class='file-entry folder-entry'>
                <div class='file-controls'>
                    <button onclick='openFolder(\"" . addslashes($dirname) . "\")' title='Open Folder'>
                        <i class='fas fa-folder'></i>
                    </button>
                </div>
                <a onclick='openFolder(\"" . addslashes($dirname) . "\"); return false;' href='#'
                   class='filename folder-name'
                   title='" . htmlspecialchars($dirname, ENT_QUOTES) . "'>
                   <i class='fas fa-folder me-2'></i> " .
                htmlspecialchars($dirname, ENT_QUOTES) . "</a>
            </li>";
        }
    }

    // Then show files
    if (!empty($items['files'])) {
        foreach ($items['files'] as $file) {
            $filename = basename($file);

            // Skip backup files
            if (preg_match('/\(BAK-\w{3}\d{2}-S\d+\)\.\w+$/', $filename)) {
                continue;
            }

            $isCurrentEdit = ($filename === $currentFilename);
            $fileTypeClass = getFileTypeClass($filename);

            echo "<li class='file-entry " . ($isCurrentEdit ? "current-edit" : "") . "'>
                <div class='file-controls'>
                    <button onclick='loadFile(\"" . addslashes($filename) . "\")' title='Edit File'>
                        <i class='fas fa-pencil-alt'></i>
                    </button>
                    <a href='#' onclick='openInNewTab(\"" . htmlspecialchars($filename, ENT_QUOTES) . "\")' title='Run File'>
                        <i class='fas fa-play'></i>
                    </a>
                    <button onclick='confirmDelete(\"" . htmlspecialchars($filename) . "\")' title='Delete File'>
                        <i class='fas fa-trash'></i>
                    </button>
                </div>
                <a onclick='loadFile(\"" . addslashes($filename) . "\"); return false;' href='#'
                   class='filename " . $fileTypeClass . "'
                   title='" . htmlspecialchars($filename, ENT_QUOTES) . "'>
                   <i class='fas fa-file me-2'></i> " .
                htmlspecialchars($filename, ENT_QUOTES) . "</a>
            </li>";
        }
    }

    if (empty($items['directories']) && empty($items['files'])) {
        echo "<li class='no-files'>No files available.</li>";
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

// Get Folders Handler (combined with getFileList for efficiency)
if (isset($_GET['getFolders'])) {
    $folders = array_filter(glob('*'), 'is_dir');
    echo json_encode($folders);
    exit;
}

// Get File List Handler - now using centralized function
if (isset($_GET['getFileList'])) {
    $files = getFileList($dir, $sortBy);
    $fileListHTML = generateFileListHTML($files, $_GET['edit'] ?? '');
    echo $fileListHTML;
    exit;
}

/* -------------------------------------------------------------------------- */
/*                                POST Handler                                */
/* -------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Set current path handler
    if ($action === 'setPath') {
        $path = $_POST['path'] ?? '';
        $normalizedPath = trim($path, '/');
        $_SESSION['current_path'] = $normalizedPath;
        echo json_encode(['status' => 'success']);
        exit;
    }

    // File save handler
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
    // Handle file transfers (retained but improved)
    else if ($action === 'transferFiles') {
        $response = ['status' => 'error', 'message' => 'No files uploaded'];

        if (!empty($_FILES['files']['name'][0])) {
            $successCount = 0;
            $failCount = 0;

            foreach ($_FILES['files']['name'] as $key => $name) {
                $tmpName = $_FILES['files']['tmp_name'][$key];
                $targetPath = __DIR__ . '/' . basename($name);

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
    $content = file_get_contents(basename($_GET['file']));
}

// Set up current working directory based on path
$currentPath = $_SESSION['current_path'] ?? '';
$workingDir = $currentPath ? __DIR__ . '/' . trim($currentPath, '/') : __DIR__;

// Get file list for initial display
$files = getFileList($workingDir, $sortBy);

// Set sort icon based on current sort mode
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
            margin: 0;
            box-sizing: border-box;
            height: calc(100vh - 160px);
            padding-bottom: 60px;
            transition: max-width 0.3s ease;
        }

        .editor.fullscreen {
            max-width: 100%;
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

        /* Breadcrumb styles */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 5px;
            color: white;
            font-size: 14px;
        }

        .breadcrumb a {
            color: white;
            text-decoration: none;
            padding: 2px 5px;
            border-radius: 3px;
            transition: background-color 0.2s;
        }

        .breadcrumb a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .breadcrumb i {
            font-size: 16px;
        }

        /* Folder specific styles */
        .folder-entry .folder-name {
            color: var(--primary-color) !important;
            font-weight: bold;
        }

        .folder-entry .fa-folder {
            color: #ffd700;
            margin-right: 5px;
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
        .editor-container {
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
            <button id="menuNewBtn" class="header-button" title="New" onclick="createNewFile()">
                <i class="fas fa-file"></i>
            </button>
            <button id="menuRefreshBtn" class="header-button" title="Reload Menu">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button id="menuSortBtn" class="header-button" title="Toggle Sort (Currently: <?php echo $sortBy === 'date' ? 'by date' : 'alphabetical'; ?>)">
                <i class="fas fa-<?php echo $sortIcon; ?>"></i>
            </button>
        </div>
        <div class="right-section">
            <div class="breadcrumb">
                <a href="#" onclick="navigateToRoot(); return false;"><i class="fas fa-home"></i></a>
                <?php
                $currentPath = $_SESSION['current_path'] ?? '';
                if ($currentPath) {
                    echo ' / ';
                    $parts = explode('/', trim($currentPath, '/'));
                    $path = '';
                    foreach ($parts as $part) {
                        $path .= '/' . $part;
                        echo "<a href=\"#\" onclick=\"navigateToFolder('" . addslashes($path) . "'); return false;\">" . htmlspecialchars($part) . "</a> / ";
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <div class="menu-overlay" id="menuOverlay"></div>

    <div id="menuPanel" class="menu">
        <div class="menu-content">
            <ul class="file-list">
                <?php echo generateFileListHTML($files, $currentFilename); ?>
            </ul>
        </div>
    </div>

    <div id="mainContainer" class="container">
        <div class="editor-view">
            <div class="menu-container">
                <div class="editor-header">
                    <div class="header-top">
                        <h2 class="editor-title">Editor</h2>
                    </div>
                    <div class="label-line">
                        <span class="info-label">File</span>
                        <input type="text" id="editorFilename" class="info-input" readonly />
                    </div>
                </div>

                <div class="persistent-status-bar">
                    <div class="status-message"></div>
                </div>

                <div id="editorContainer" class="editor-container">
                    <div id="editor" class="editor"></div>
                    <div class="editor-nav-controls">
                        <button onclick="scrollEditorTop()" title="Scroll to Top">
                            <i class="fas fa-chevron-up"></i>
                        </button>
                        <button onclick="scrollEditorBottom()" title="Scroll to Bottom">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <button onclick="toggleEditorFullWidth()" title="Toggle Full Width" id="fullwidthButton">
                            <i id="fullwidthIcon" class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="backup-view"></div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ace.js"></script>
    <script>
        let currentPath = '<?php echo addslashes($_SESSION['current_path'] ?? ''); ?>';

        // Initialize editor
        const editor = ace.edit("editor");
        editor.setTheme("ace/theme/monokai");
        editor.session.setMode("ace/mode/php");
        editor.setFontSize(14);
        editor.setShowPrintMargin(false);

        // Status message types object
        const STATUS_MESSAGES = {
            file: {
                loaded: filename => `Loaded ${filename}`,
                saved: filename => `Saved ${filename}`,
                deleted: filename => `Deleted ${filename}`,
            },
            editor: {
                scrolledTop: "Scrolled to top",
                scrolledBottom: "Scrolled to bottom"
            },
            folder: {
                opened: foldername => `Opened folder: ${foldername}`,
                navigated: path => `Navigated to: ${path}`
            }
        };

        // Update status with type-based styling
        function updateStatus(message, type = 'info') {
            const statusBar = document.querySelector('.persistent-status-bar');
            const newMessage = document.createElement('div');
            newMessage.classList.add('status-message', type);
            newMessage.textContent = message;
            statusBar.insertBefore(newMessage, statusBar.firstChild);
        }

        function navigateToRoot() {
            currentPath = '';
            refreshFileList();
            updateStatus('Navigated to root directory', 'info');
        }

        function navigateToFolder(path) {
            currentPath = path;
            refreshFileList();
            updateStatus(STATUS_MESSAGES.folder.navigated(path), 'info');
        }

        function openFolder(foldername) {
            const newPath = currentPath ? `${currentPath}/${foldername}` : foldername;
            currentPath = newPath;

            fetch('main.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=setPath&path=${encodeURIComponent(newPath)}`
                })
                .then(() => {
                    refreshFileList();
                    updateStatus(STATUS_MESSAGES.folder.opened(foldername), 'info');
                });
        }
