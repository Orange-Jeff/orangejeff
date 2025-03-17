<?php
error_reporting(0);
ini_set('display_errors', 0);
// If a POST file upload is desired, handle file uploads here.
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_FILES)) {
    // Process uploaded files - save to current directory
    $uploadedFiles = [];
    foreach ($_FILES['transferFiles']['error'] as $key => $error) {
        if ($error === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['transferFiles']['tmp_name'][$key];
            $name = basename($_FILES['transferFiles']['name'][$key]);
            $isReplacing = file_exists($name);

            // Move file to current directory
            if (move_uploaded_file($tmp_name, $name)) {
                $uploadedFiles[] = [
                    "name" => $name,
                    "url" => $name,
                    "success" => true,
                    "replaced" => $isReplacing,
                    "isPhp" => strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'php'
                ];
            } else {
                $uploadedFiles[] = [
                    "name" => $name,
                    "success" => false,
                    "error" => "Failed to save file"
                ];
            }
        }
    }
    header("Content-Type: application/json");
    echo json_encode([
        "status" => "success",
        "files" => $uploadedFiles,
        "message" => "Files transferred successfully."
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetBound Tools: Transfer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <style>
        body {
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f4f4f9;
            width: 768px;
            margin: 0 auto;
        }

        body.in-iframe {
            margin: 0;
        }

        .tool-container {
            background: #f4f4f9;
            height: auto;
            margin: 0;
            max-width: 768px;
            width: 100%;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .tool-title {
            margin: 10px 0;
            padding: 0;
            color: #0056b3;
            line-height: 1.2;
            font-weight: bold;
            font-size: 18px;
        }

        .status-bar {
            width: 100%;
            height: 90px;
            min-height: 90px;
            max-height: 90px;
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
            padding: 5px;
            margin: 2px 0;
            border-radius: 3px;
            color: #666;
        }

        .status-message:first-child {
            color: white;
        }

        .status-message.info {
            border-left: 3px solid #2196f3;
        }

        .status-message.info:first-child {
            background: #2196f3;
        }

        .status-message.success {
            border-left: 3px solid #4caf50;
        }

        .status-message.success:first-child {
            background: #4caf50;
        }

        .status-message.error {
            border-left: 3px solid #f44336;
        }

        .status-message.error:first-child {
            background: #f44336;
        }

        .button-controls {
            width: 100%;
            padding: 10px 0;
            display: flex;
            gap: 10px;
            flex-wrap: nowrap;
        }

        .command-button {
            background: #0056b3;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 6px 8px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .command-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .command-button:hover:not(:disabled) {
            background: #004494;
        }

        /* File list area */
        .file-list {
            margin-top: 15px;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 3px;
            background-color: #fff;
            width: 100%;
            box-sizing: border-box;
            max-height: 300px;
            overflow-y: auto;
        }

        .file-item {
            padding: 8px 5px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }

        .file-item:last-child {
            border-bottom: none;
        }

        .file-item input[type="checkbox"] {
            margin-right: 10px;
            width: 16px;
            height: 16px;
        }

        .file-item a {
            color: #0056b3;
            text-decoration: none;
        }

        .file-item a:hover {
            text-decoration: underline;
        }

        .file-item .file-status {
            margin-left: auto;
            font-size: 12px;
            color: #666;
        }

        .file-item .file-status.replaced {
            color: #ff9800;
        }

        .file-item .file-status.copied {
            color: #4caf50;
        }

        .file-item .file-status.error {
            color: #f44336;
        }

        /* Apply consistent box-sizing to all elements */
        * {
            box-sizing: border-box;
        }
    </style>
</head>

<body>
    <div class="tool-container">
        <div class="tool-header">
            <h1 class="tool-title">NetBound Tools: File Transfer</h1>
        </div>
        <div id="statusBar" class="status-bar"></div>
        <div class="button-controls">
            <button class="command-button" id="transferButton">
                <i class="fas fa-file-import"></i> Transfer Files
            </button>
            <button class="command-button" id="recopyButton" disabled>
                <i class="fas fa-copy"></i> Re-copy Files
            </button>
            <button class="command-button" id="zipButton">
                <i class="fas fa-file-archive"></i> Create ZIP
            </button>
            <button class="command-button" id="restartButton">
                <i class="fas fa-redo"></i> Restart
            </button>
        </div>

        <div id="fileList" class="file-list" style="display: none;"></div>

        <input type="file" id="fileInput" style="display:none" multiple>
        <input type="file" id="zipFileInput" style="display:none" multiple>
    </div>

    <script>
        // Check if running in an iframe
        function inIframe() {
            try {
                return window.self !== window.top;
            } catch (e) {
                return true;
            }
        }

        // Apply iframe-specific styling if needed
        if (inIframe()) {
            document.body.classList.add('in-iframe');
        }

        const transferButton = document.getElementById('transferButton');
        const recopyButton = document.getElementById('recopyButton');
        const zipButton = document.getElementById('zipButton');
        const fileInput = document.getElementById('fileInput');
        const zipFileInput = document.getElementById('zipFileInput');
        const fileList = document.getElementById('fileList');
        const statusBar = document.getElementById('statusBar');
        const restartButton = document.getElementById('restartButton');

        // Store processed files for potential re-copy
        let processedFiles = [];

        function updateStatus(message, type = 'info') {
            const statusMessage = document.createElement('div');
            statusMessage.className = `status-message ${type}`;
            statusMessage.textContent = message;

            // Clear the status bar if we're starting a new operation
            if (message.includes('Ready') || message.includes('Selected') || message.includes('files copied') ||
                message.includes('Creating ZIP')) {
                statusBar.innerHTML = '';
            }

            statusBar.insertBefore(statusMessage, statusBar.firstChild);
            statusBar.scrollTop = 0; // Always show latest message
        }

        // Handle transfers
        fileInput.addEventListener('change', () => {
            const files = fileInput.files;
            if (files.length > 0) {
                updateStatus(`Selected ${files.length} file${files.length > 1 ? 's' : ''}`, 'info');
                transferFiles(files);
            } else {
                updateStatus('No files selected', 'info');
            }
        });

        // Handle zip file selection
        zipFileInput.addEventListener('change', () => {
            const files = zipFileInput.files;
            if (files.length > 0) {
                createZip(Array.from(files));
            } else {
                updateStatus('No files selected for ZIP', 'info');
            }
        });

        // Event handlers for buttons
        transferButton.addEventListener('click', () => {
            fileInput.value = "";
            fileInput.click();
        });

        recopyButton.addEventListener('click', () => {
            // Get all checked checkboxes
            const checkedItems = document.querySelectorAll('#fileList input[type="checkbox"]:checked');
            if (checkedItems.length === 0) {
                updateStatus('No files selected for re-copy', 'error');
                return;
            }

            // Filter processed files to include only checked ones
            const filesToCopy = [];
            checkedItems.forEach(checkbox => {
                const fileName = checkbox.getAttribute('data-filename');
                const fileObj = processedFiles.find(f => f.name === fileName);
                if (fileObj) {
                    filesToCopy.push(fileObj);
                }
            });

            if (filesToCopy.length > 0) {
                updateStatus(`Re-copying ${filesToCopy.length} file${filesToCopy.length > 1 ? 's' : ''}...`, 'info');
                transferFiles(filesToCopy);
            } else {
                updateStatus('No valid files to re-copy', 'error');
            }
        });

        zipButton.addEventListener('click', () => {
            const checkedItems = document.querySelectorAll('#fileList input[type="checkbox"]:checked');
            if (checkedItems.length === 0) {
                // If no files are checked, open file selection dialog
                zipFileInput.value = "";
                zipFileInput.click();
            } else {
                // If files are checked, create ZIP with those files
                const filesToZip = [];
                checkedItems.forEach(checkbox => {
                    const fileName = checkbox.getAttribute('data-filename');
                    const fileObj = processedFiles.find(f => f.name === fileName);
                    if (fileObj) {
                        filesToZip.push(fileObj);
                    }
                });

                if (filesToZip.length > 0) {
                    createZip(filesToZip);
                } else {
                    updateStatus('No valid files to include in ZIP', 'error');
                }
            }
        });

        // Restart button handler
        restartButton.addEventListener('click', () => {
            location.reload();
        });

        async function createZip(files) {
            if (!files.length) {
                updateStatus('No files selected for ZIP', 'error');
                return;
            }

            const date = new Date();
            const month = date.toLocaleString('en-US', {
                month: 'short'
            }).toLowerCase();
            const day = date.getDate();
            const zipName = `Zipped-${month}${day}`;

            updateStatus(`Creating ZIP with ${files.length} file${files.length > 1 ? 's' : ''}`, 'info');

            const zip = new JSZip();
            const processEntries = [];

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                processEntries.push(
                    new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            zip.file(file.name || file.fileName, e.target.result);
                            resolve();
                        };
                        reader.readAsArrayBuffer(file);
                    })
                );
            }

            try {
                await Promise.all(processEntries);

                const blob = await zip.generateAsync({
                    type: 'blob',
                    compression: 'DEFLATE',
                    compressionOptions: {
                        level: 6
                    }
                });

                const size = blob.size;
                const sizeText = size > 1024 * 1024 ?
                    `${(size/1024/1024).toFixed(2)} MB` :
                    `${(size/1024).toFixed(2)} KB`;

                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = zipName + '.zip';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                updateStatus(`${files.length} files zipped successfully (${sizeText})`, 'success');
            } catch (error) {
                console.error('ZIP Error:', error);
                updateStatus('Failed to create ZIP file', 'error');
            }
        }

        function transferFiles(files) {
            const formData = new FormData();
            formData.append('action', 'transferFiles');

            // Store files for future operations
            processedFiles = Array.from(files);

            for (let i = 0; i < files.length; i++) {
                formData.append('transferFiles[]', files[i]);
            }

            // Show the file list area
            fileList.style.display = 'block';
            fileList.innerHTML = '<div style="text-align:center;padding:10px;">Processing...</div>';

            return fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    let fileHtml = "";
                    let successCount = 0;
                    let replaceCount = 0;
                    let errorCount = 0;

                    if (result.files && result.files.length > 0) {
                        result.files.forEach(file => {
                            if (file.success) {
                                const status = file.replaced ? 'Replaced' : 'Copied';
                                if (file.replaced) {
                                    replaceCount++;
                                } else {
                                    successCount++;
                                }

                                let nameHtml = file.isPhp ?
                                    `<a href="${file.url}" target="_blank">${file.name}</a>` :
                                    file.name;

                                fileHtml += `<div class="file-item">
                                    <input type="checkbox" checked data-filename="${file.name}">
                                    <span>${nameHtml}</span>
                                    <span class="file-status ${file.replaced ? 'replaced' : 'copied'}">${status}</span>
                                </div>`;
                            } else {
                                errorCount++;
                                fileHtml += `<div class="file-item">
                                    <input type="checkbox" data-filename="${file.name}">
                                    <span>${file.name}</span>
                                    <span class="file-status error">Error: ${file.error || 'Unknown error'}</span>
                                </div>`;
                            }
                        });
                        fileList.innerHTML = fileHtml;

                        // Update summary status
                        const totalSuccess = successCount + replaceCount;
                        let statusMsg = `${totalSuccess} file${totalSuccess !== 1 ? 's' : ''} copied`;
                        if (errorCount > 0) {
                            statusMsg += `, ${errorCount} error${errorCount !== 1 ? 's' : ''}`;
                        }
                        updateStatus(statusMsg, totalSuccess > 0 ? 'success' : 'error');

                        // Enable re-copy button
                        recopyButton.disabled = false;
                    }
                })
                .catch(error => {
                    updateStatus('Transfer failed', 'error');
                    fileList.innerHTML = '<div style="color:red;padding:10px;">Transfer failed. Please try again.</div>';
                });
        }

        // Initialize with Ready status
        updateStatus('Ready', 'info');
    </script>
</body>

</html>
