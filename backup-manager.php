<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dir = __DIR__;
$backupDir = $dir . '/backups/';

// Create backup directory if it doesn't exist
if (!is_dir($backupDir)) {
    if (!@mkdir($backupDir, 0755, true)) {
        die('Failed to create backup directory. Check permissions.');
    }
}

// Get list of backup files
$backups = glob($backupDir . '*');

// Sort backups by modification time (newest first)
usort($backups, function ($a, $b) {
    return filemtime($b) - filemtime($a);
});

// Handle restore action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restore') {
    $backupFile = isset($_POST['backupFile']) ? $_POST['backupFile'] : '';
    $targetFile = isset($_POST['targetFile']) ? $_POST['targetFile'] : '';

    $response = ['status' => 'error', 'message' => ''];

    if (empty($backupFile) || empty($targetFile)) {
        $response['message'] = 'Missing backup or target file';
    } else {
        $backupPath = $backupDir . basename($backupFile);
        $targetPath = $dir . '/' . $targetFile;

        if (!file_exists($backupPath)) {
            $response['message'] = 'Backup file does not exist';
        } else {
            if (copy($backupPath, $targetPath)) {
                $response['status'] = 'success';
                $response['message'] = "Restored $backupFile to $targetFile";
            } else {
                $response['message'] = 'Failed to restore backup';
            }
        }
    }

    echo json_encode($response);
    exit;
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $backupFile = isset($_POST['backupFile']) ? $_POST['backupFile'] : '';

    $response = ['status' => 'error', 'message' => ''];

    if (empty($backupFile)) {
        $response['message'] = 'Missing backup file';
    } else {
        $backupPath = $backupDir . basename($backupFile);

        if (!file_exists($backupPath)) {
            $response['message'] = 'Backup file does not exist';
        } else {
            if (unlink($backupPath)) {
                $response['status'] = 'success';
                $response['message'] = "Deleted backup $backupFile";
            } else {
                $response['message'] = 'Failed to delete backup';
            }
        }
    }

    echo json_encode($response);
    exit;
}

// Get backups list for AJAX refresh
if (isset($_GET['getBackups'])) {
    $backups = glob($backupDir . '*');
    usort($backups, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    $html = '';
    if (count($backups) > 0) {
        foreach ($backups as $backup) {
            $filename = basename($backup);
            $filesize = filesize($backup);
            $modified = date('Y-m-d H:i:s', filemtime($backup));

            // Extract original filename from backup name
            $originalName = preg_replace('/\(V\d+\)\.php$/', '', $filename);

            $html .= "<tr>
                <td>{$filename}</td>
                <td>{$originalName}</td>
                <td>{$modified}</td>
                <td>" . formatSize($filesize) . "</td>
                <td>
                    <button class='btn btn-sm btn-primary view-backup' data-backup='{$filename}'>View</button>
                    <button class='btn btn-sm btn-success restore-backup' data-backup='{$filename}' data-original='{$originalName}'>Restore</button>
                    <button class='btn btn-sm btn-danger delete-backup' data-backup='{$filename}'>Delete</button>
                </td>
            </tr>";
        }
    } else {
        $html = "<tr><td colspan='5' class='text-center'>No backups found</td></tr>";
    }

    echo $html;
    exit;
}

// Helper function to format file size
function formatSize($size)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($size >= 1024 && $i < 4) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .content-view {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-top: 20px;
            min-height: 200px;
            max-height: 400px;
            overflow: auto;
            white-space: pre-wrap;
            font-family: monospace;
        }

        .status {
            margin-top: 20px;
            padding: 10px;
            border-radius: 4px;
        }

        .status.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .status.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            color: white;
            font-size: 24px;
        }
    </style>
</head>

<body>
    <div class="loading" style="display: none;">
        <div>
            <i class="fas fa-spinner fa-spin"></i> Processing...
        </div>
    </div>

    <div class="container">
        <div class="header">
            <h1>Backup Manager</h1>
            <div>
                <button id="backToEditor" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Editor
                </button>
                <button id="refreshBackups" class="btn btn-primary">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Backup Filename</th>
                        <th>Original File</th>
                        <th>Modified</th>
                        <th>Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="backups-list">
                    <?php if (count($backups) > 0): ?>
                        <?php foreach ($backups as $backup): ?>
                            <?php
                            $filename = basename($backup);
                            $filesize = filesize($backup);
                            $modified = date('Y-m-d H:i:s', filemtime($backup));

                            // Extract original filename from backup name
                            $originalName = preg_replace('/\(V\d+\)\.php$/', '', $filename);
                            ?>
                            <tr>
                                <td><?php echo $filename; ?></td>
                                <td><?php echo $originalName; ?></td>
                                <td><?php echo $modified; ?></td>
                                <td><?php echo formatSize($filesize); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary view-backup" data-backup="<?php echo $filename; ?>">View</button>
                                    <button class="btn btn-sm btn-success restore-backup" data-backup="<?php echo $filename; ?>" data-original="<?php echo $originalName; ?>">Restore</button>
                                    <button class="btn btn-sm btn-danger delete-backup" data-backup="<?php echo $filename; ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No backups found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="content-view" id="content-view" style="display: none;"></div>
        <div class="status" id="status" style="display: none;"></div>
    </div>

    <script>
        // Helper functions
        function showLoading() {
            document.querySelector('.loading').style.display = 'flex';
        }

        function hideLoading() {
            document.querySelector('.loading').style.display = 'none';
        }

        function showStatus(message, type) {
            const status = document.getElementById('status');
            status.textContent = message;
            status.className = 'status ' + type;
            status.style.display = 'block';

            // Auto hide after 5 seconds
            setTimeout(() => {
                status.style.display = 'none';
            }, 5000);
        }

        function refreshBackupsList() {
            showLoading();
            fetch('backup-manager.php?getBackups=1')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('backups-list').innerHTML = html;
                    attachEventListeners();
                    hideLoading();
                })
                .catch(error => {
                    showStatus('Failed to refresh backups list: ' + error.message, 'error');
                    hideLoading();
                });
        }

        function viewBackup(backupFile) {
            showLoading();
            fetch('backups/' + backupFile)
                .then(response => response.text())
                .then(content => {
                    const contentView = document.getElementById('content-view');
                    contentView.textContent = content;
                    contentView.style.display = 'block';
                    hideLoading();
                })
                .catch(error => {
                    showStatus('Failed to load backup: ' + error.message, 'error');
                    hideLoading();
                });
        }

        function restoreBackup(backupFile, targetFile) {
            if (!targetFile) {
                targetFile = prompt('Enter target filename:', backupFile.replace(/\(V\d+\)\.php$/, ''));
                if (!targetFile) return;
            }

            if (!confirm(`Restore ${backupFile} to ${targetFile}?`)) return;

            showLoading();
            fetch('backup-manager.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=restore&backupFile=${encodeURIComponent(backupFile)}&targetFile=${encodeURIComponent(targetFile)}`
                })
                .then(response => response.json())
                .then(result => {
                    showStatus(result.message, result.status);
                    hideLoading();

                    // Notify parent window to refresh
                    if (result.status === 'success') {
                        window.parent.postMessage({
                            action: 'switchToEditor',
                            status: `Backup ${backupFile} restored to ${targetFile}`
                        }, '*');
                    }
                })
                .catch(error => {
                    showStatus('Error restoring backup: ' + error.message, 'error');
                    hideLoading();
                });
        }

        function deleteBackup(backupFile) {
            if (!confirm(`Delete backup ${backupFile}?`)) return;

            showLoading();
            fetch('backup-manager.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&backupFile=${encodeURIComponent(backupFile)}`
                })
                .then(response => response.json())
                .then(result => {
                    showStatus(result.message, result.status);
                    if (result.status === 'success') {
                        refreshBackupsList();
                    }
                    hideLoading();
                })
                .catch(error => {
                    showStatus('Error deleting backup: ' + error.message, 'error');
                    hideLoading();
                });
        }

        function attachEventListeners() {
            // View backup buttons
            document.querySelectorAll('.view-backup').forEach(button => {
                button.addEventListener('click', function() {
                    viewBackup(this.dataset.backup);
                });
            });

            // Restore backup buttons
            document.querySelectorAll('.restore-backup').forEach(button => {
                button.addEventListener('click', function() {
                    restoreBackup(this.dataset.backup, this.dataset.original);
                });
            });

            // Delete backup buttons
            document.querySelectorAll('.delete-backup').forEach(button => {
                button.addEventListener('click', function() {
                    deleteBackup(this.dataset.backup);
                });
            });
        }

        // Initial setup
        document.addEventListener('DOMContentLoaded', function() {
                    attachEventListeners();

                                // Back to editor button
                                document.getElementById('backToEditor').addEventListener('click', function() {
                                    window.parent.postMessage({
                                        action: 'switchToEditor'
                                    }, '*');
                                });

                                // Refresh backups button
                                document.getElementById('refreshBackups').addEventListener('click', function() {
                                    refreshBackupsList();
                                });
                            });
                    </script>
