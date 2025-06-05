<?php
error_reporting(0);
ini_set('display_errors', 0);

// Detect if running on localhost
function isLocalhost()
{
    $localIPs = array(
        '127.0.0.1',
        '::1',
        'localhost'
    );
    $serverName = strtolower($_SERVER['SERVER_NAME'] ?? '');
    $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';

    return in_array($serverName, $localIPs) ||
        in_array($serverAddr, $localIPs) ||
        substr($serverAddr, 0, 7) == '192.168' ||
        substr($serverName, 0, 7) == '192.168';
}

$isLocalEnvironment = isLocalhost();

// Handle the ZIP file saving action
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'saveZip') {
    $result = ['success' => false, 'error' => ''];

    if (!isset($_POST['zipName']) || !isset($_POST['zipData'])) {
        $result['error'] = 'Missing required parameters';
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    $zipName = $_POST['zipName'];
    $zipData = $_POST['zipData'];

    // Convert base64 to binary data
    $binaryData = base64_decode($zipData);
    if ($binaryData === false) {
        $result['error'] = 'Invalid ZIP data provided';
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    // Save the file to the current directory
    if (file_put_contents($zipName, $binaryData) !== false) {
        $result['success'] = true;
        $result['path'] = $zipName;
    } else {
        $result['error'] = 'Failed to save ZIP file on server';
    }

    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

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
        :root {
            --primary-color: #0056b3;
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --background-color: #f4f4f9;
            --header-height: 40px;
            --menu-width: 250px;
        }

        body {
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--background-color);
            max-width: 768px;
            margin: 0 auto;
            width: 100%;
        }

        body.in-iframe {
            margin: 0;
        }

        .tool-container {
            background: var(--background-color);
            height: auto;
            margin: 0;
            max-width: 768px;
            width: 100%;
            padding: 20px;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }

        .tool-title {
            margin: 6px 0;
            padding: 0;
            color: var(--primary-color);
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
            border: 1px solid var(--primary-color);
            background: #fff;
            padding: 8px 12px;
            margin: 6px 0;
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
            padding: 6px 0;
            display: flex;
            gap: 10px;
            flex-wrap: nowrap;
        }

        .command-button {
            background: var(--primary-color);
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

        /* Processing container */
        .processing-container {
            width: 100%;
            height: 400px;
            background-color: #fff;
            border: 1px solid var(--primary-color);
            border-radius: 4px;
            padding: 8px 12px;
            margin-top: 6px;
            margin-bottom: 6px;
            box-sizing: border-box;
            overflow: auto;
            display: flex;
            flex-direction: column;
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
            color: var(--primary-color);
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

        .save-button-container {
            display: flex;
        }

        .icon-button {
            border-radius: 0 3px 3px 0;
            padding: 6px 10px;
            margin-left: 1px;
        }

        #zipButton {
            border-radius: 3px 0 0 3px;
            margin-right: 0;
        }

        /* Apply consistent box-sizing to all elements */
        * {
            box-sizing: border-box;
        }

        /* Media queries for mobile responsiveness */
        @media (max-width: 768px) {
            body {
                max-width: 100%;
                padding: 0;
            }

            .tool-container {
                padding: 10px;
                width: 100%;
            }

            .button-controls {
                flex-wrap: wrap;
            }

            .command-button {
                flex: 1;
                min-width: 120px;
                justify-content: center;
            }

            .processing-container {
                height: 300px;
                /* Smaller height on mobile */
            }

            /* Adjust status bar for mobile */
            .status-bar {
                height: 70px;
                min-height: 70px;
                max-height: 70px;
            }
        }

        /* Small phone adjustments */
        @media (max-width: 480px) {
            .button-controls {
                flex-direction: column;
                gap: 8px;
            }

            .command-button {
                width: 100%;
            }

            .tool-title {
                font-size: 16px;
            }

            .processing-container {
                height: 250px;
                padding: 5px;
            }
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
            <button class="command-button" id="restartButton">
                <i class="fas fa-redo"></i> Restart
            </button>
        </div>

        <div id="processingContainer" class="processing-container">
            <div style="text-align: center; color: #666;">
                <p>Ready to process files. Use the buttons above to begin.</p>
            </div>
        </div>

        <div class="button-controls" style="justify-content: flex-end;">
            <?php if (!$isLocalEnvironment): ?>
                <div class="save-button-container">
                    <button class="command-button" id="zipButton">
                        <i class="fas fa-file-archive"></i> Create ZIP
                    </button>
                    <button class="command-button icon-button" id="zipAsButton" title="Save ZIP as...">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            <?php endif; ?>
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
        const processingContainer = document.getElementById('processingContainer');

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

        // Add a function to update checked files count
        function updateCheckedCount() {
            const selectedCount = document.querySelectorAll('#processingContainer input[type="checkbox"]:checked').length;
            const totalCount = document.querySelectorAll('#processingContainer input[type="checkbox"]').length;

            if (totalCount > 0) {
                updateStatus(`${selectedCount} of ${totalCount} file${totalCount !== 1 ? 's' : ''} selected`, 'info');
                zipButton.disabled = (selectedCount === 0);
            } else {
                zipButton.disabled = true;
            }

            // Enable re-copy button only if files are selected
            recopyButton.disabled = (selectedCount === 0);
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
            const checkedItems = document.querySelectorAll('#processingContainer input[type="checkbox"]:checked');
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
            const checkedItems = document.querySelectorAll('#processingContainer input[type="checkbox"]:checked');
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

        // Add ZIP as button handler
        const zipAsButton = document.getElementById('zipAsButton');
        if (zipAsButton) {
            zipAsButton.addEventListener('click', () => {
                const checkedItems = document.querySelectorAll('#processingContainer input[type="checkbox"]:checked');
                if (checkedItems.length === 0) {
                    updateStatus('No files selected for ZIP', 'error');
                    return;
                }

                // Prompt for ZIP name
                const date = new Date();
                const defaultName = `Files-${date.getFullYear()}${String(date.getMonth() + 1).padStart(2, '0')}${String(date.getDate()).padStart(2, '0')}`;
                const zipName = prompt('Enter ZIP filename:', defaultName);

                if (!zipName) return; // User cancelled

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
                    createZipWithName(filesToZip, zipName);
                } else {
                    updateStatus('No valid files to include in ZIP', 'error');
                }
            });
        }

        async function createZipWithName(files, customName) {
            if (!files.length) {
                updateStatus('No files selected for ZIP', 'error');
                return;
            }

            const zipName = customName || `Zipped-${new Date().toISOString().slice(0, 10)}`;

            updateStatus(`Creating ZIP with ${files.length} file${files.length > 1 ? 's' : ''}...`, 'info');

            // Rest of zip creation process identical to createZip
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

                // 1. Download the ZIP locally
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = zipName + '.zip';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                // 2. Save the ZIP on the server
                try {
                    // Convert blob to base64 for server-side storage
                    const reader = new FileReader();
                    reader.readAsDataURL(blob);

                    reader.onloadend = async function() {
                        const base64data = reader.result.split(',')[1];
                        const formData = new FormData();
                        formData.append('action', 'saveZip');
                        formData.append('zipName', zipName + '.zip');
                        formData.append('zipData', base64data);

                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });

                        if (response.ok) {
                            const result = await response.json();
                            if (result.success) {
                                updateStatus(`ZIP saved on server as ${zipName}.zip`, 'success');
                            } else {
                                updateStatus(`Downloaded ZIP locally, but server save failed: ${result.error}`, 'warning');
                            }
                        } else {
                            updateStatus('Downloaded ZIP locally, but server save failed', 'warning');
                        }
                    };
                } catch (serverError) {
                    console.error('Server save error:', serverError);
                    updateStatus('Downloaded ZIP locally, but server save failed', 'warning');
                }

                updateStatus(`${files.length} files zipped successfully (${sizeText})`, 'success');
            } catch (error) {
                console.error('ZIP Error:', error);
                updateStatus('Failed to create ZIP file', 'error');
            }
        }

        async function createZip(files) {
            await createZipWithName(files);
        }

        function transferFiles(files) {
            const formData = new FormData();
            formData.append('action', 'transferFiles');

            // Store files for future operations
            processedFiles = Array.from(files);

            for (let i = 0; i < files.length; i++) {
                formData.append('transferFiles[]', files[i]);
            }

            // Show "Processing" message in the processing container
            processingContainer.innerHTML = '<div style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin" style="color:#0056b3;font-size:24px;margin-bottom:10px;"></i><p>Processing files...</p></div>';

            // Hide the file list area until we need it
            fileList.style.display = 'none';

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
                        // Create header for the processing container
                        fileHtml = `<div style="padding:10px 0 15px;border-bottom:1px solid #eee;margin-bottom:10px;">
                            <h3 style="margin:0;color:#0056b3;font-size:16px;">Processed Files</h3>
                        </div>`;

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
                                    <input type="checkbox" checked data-filename="${file.name}" class="file-checkbox">
                                    <span>${nameHtml}</span>
                                    <span class="file-status ${file.replaced ? 'replaced' : 'copied'}">${status}</span>
                                </div>`;
                            } else {
                                errorCount++;
                                fileHtml += `<div class="file-item">
                                    <input type="checkbox" data-filename="${file.name}" class="file-checkbox">
                                    <span>${file.name}</span>
                                    <span class="file-status error">Error: ${file.error || 'Unknown error'}</span>
                                </div>`;
                            }
                        });

                        // Update the processing container with the files
                        processingContainer.innerHTML = fileHtml;

                        // Also update the hidden file list (for compatibility with existing code)
                        fileList.innerHTML = fileHtml;

                        // Add event listeners to the checkboxes
                        processingContainer.querySelectorAll('.file-checkbox').forEach(checkbox => {
                            checkbox.addEventListener('change', updateCheckedCount);
                        });

                        // Update summary status
                        let totalSuccess = successCount + replaceCount;
                        let statusMsg = `${totalSuccess} file${totalSuccess !== 1 ? 's' : ''} copied`;
                        if (errorCount > 0) {
                            statusMsg += `, ${errorCount} error${errorCount !== 1 ? 's' : ''}`;
                        }
                        updateStatus(statusMsg, totalSuccess > 0 ? 'success' : 'error');

                        // Update checked count in status box
                        updateCheckedCount();
                    }
                })
                .catch(error => {
                    updateStatus('Transfer failed', 'error');
                    processingContainer.innerHTML = '<div style="color:red;padding:20px;text-align:center;">Transfer failed. Please try again.</div>';
                });
        }

        // Initialize with Ready status
        updateStatus('Ready', 'info');

        // Display localhost status message
        if (<?php echo json_encode($isLocalEnvironment); ?>) {
            updateStatus('Running on localhost', 'info');
        } else {
            updateStatus('Running on a remote server', 'info');
        }
    </script>
</body>

</html>
