/* main-server.php */

<?php
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

// Set the current filename and load its content if requested.
$currentFilename = isset($_GET['file']) ? basename($_GET['file']) : '';
$content = '';
if (isset($_GET['file']) && !empty($_GET['file'])) {
    $content = file_get_contents(basename($_GET['file']));
}
?>
