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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <style>
        :root {
            --primary-color: #0056b3;
            --secondary-color: #333;
            --background-color: rgb(255, 255, 255);
            --text-color: #222;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--background-color);
        }

        #recorder-container {
            max-width: 600px;
            margin: 0;
            padding: 0 17px;
            background: var(--background-color);
            border-radius: 8px;
        }

        .header {
            background: var(--background-color);
            border-bottom: 1px solid #dee2e6;
            padding: 8px 17px;
        }

        .header h1 {
            color: #0056b3;
            margin-top: 20px;
            line-height: 1.2;
            font-weight: bold;
            font-size: 18px;
        }

        .content {
            padding: 0 17px;
            text-align: left;
            background: #ffffff;
            margin-bottom: 20px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            padding: 10px 0;
        }

        button {
            background: #0056b3;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }

        button:hover:not(:disabled) {
            background: #004494;
        }

        button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        #fileList {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            background: #f8f8f8;
        }

        .file-item {
            padding: 5px 0;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
        }

        .file-item:last-child {
            border-bottom: none;
        }

        .file-item a {
            color: #0056b3;
            text-decoration: none;
        }

        .file-item a:hover {
            text-decoration: underline;
        }

        /* Status Bar */
        .persistent-status-bar {
            width: 100%;
            height: 84px; /* Exactly 3.5 lines at 24px per line */
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
        }

        .status-message {
            margin: 0;
            font-size: 13px;
            color: #666;
            padding: 2px 5px;
            line-height: 24px; /* Fixed line height */
            height: 24px; /* Fixed height per message */
        }

        .status-message:first-child {
            background: #0056b3;
            color: white;
        }

        .status-message.error:first-child {
            background: #dc3545;
            color: white;
        }

        .status-message.success:first-child {
            background: #28a745;
            color: white;
        }

        .status-message.error:not(:first-child) {
            color: #dc3545;
            background: transparent;
        }

        .status-message.success:not(:first-child) {
            color: #28a745;
            background: transparent;
        }
        .file-item input[type="checkbox"] {
            margin-right: 10px;
            width: 16px;
            height: 16px;
            border: none;
            position: relative;
        }

        .file-item input[type="checkbox"].new:checked {
            accent-color: #28a745;
            background-color: #28a745;
        }

        .file-item input[type="checkbox"].replaced:checked {
            accent-color: #ffa500;
            background-color: #ffa500;
        }

        .file-item input[type="checkbox"]:checked::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0.2;
        }

        .file-item input[type="checkbox"].new:checked::after {
            background-color: #28a745;
        }

        .file-item input[type="checkbox"].replaced:checked::after {
            background-color: #ffa500;
        }

        /* Rest of the styles remain the same */
    </style>
</head>

<body>
    <div id="recorder-container">
        <div class="header">
            <h1>NetBound Tools: Transfer</h1>
        </div>
        <div class="content">
            <div class="button-group">
                <button id="transferButton">Transfer Files</button>
                <button id="zipButton">Create ZIP</button>
                <button id="repeatButton" disabled>Repeat Transfer</button>
                <button id="restartButton">Restart</button>
            </div>
            <input type="file" id="fileInput" style="display:none" multiple>
            <input type="file" id="zipFileInput" style="display:none" multiple>
            <div class="persistent-status-bar" id="statusBar"></div>
            <div id="fileList"></div>
        </div>
    </div>
    <script>
        const transferButton = document.getElementById('transferButton');
        const zipButton = document.getElementById('zipButton');
        const repeatButton = document.getElementById('repeatButton');
        const fileInput = document.getElementById('fileInput');
        const zipFileInput = document.getElementById('zipFileInput');
        const fileList = document.getElementById('fileList');
        const statusBar = document.getElementById('statusBar');
        const restartButton = document.getElementById('restartButton');

        // Store last successful transfer
        let lastTransferFiles = null;

        function updateStatus(message, type = 'info') {
            const statusMessage = document.createElement('div');
            statusMessage.className = `status-message${type !== 'info' ? ` ${type}` : ''}`;
            statusMessage.textContent = message;
            statusBar.insertBefore(statusMessage, statusBar.firstChild);
        }

        // Handle transfers
        fileInput.addEventListener('change', () => {
            fileList.innerHTML = "";
            const files = fileInput.files;
            if (files.length > 0) {
                updateStatus(`Processing ${files.length} file${files.length > 1 ? 's' : ''}...`, 'info');
                Array.from(files).forEach(file => {
                    updateStatus(`Selected: ${file.name} (${Math.round(file.size / 1024)} KB)`, 'info');
                });
                transferFiles(files);
            } else {
                updateStatus('No files selected.', 'info');
            }
        });

        // Handle zip operations
        zipFileInput.addEventListener('change', () => {
            fileList.innerHTML = "";
            const files = zipFileInput.files;
            if (files.length > 0) {
                createZip(Array.from(files));
            } else {
                updateStatus('No files selected.', 'info');
            }
        });

        async function createZip(files) {
            if (!files.length) {
                updateStatus('No files selected for ZIP', 'error');
                return;
            }

            const date = new Date();
            const month = date.toLocaleString('en-US', { month: 'short' }).toLowerCase();
            const day = date.getDate();
            const zipName = `Zipped-${month}${day}`;
            if (!zipName) return;

            const zip = new JSZip();
            const processEntries = [];

            updateStatus(`Processing ${files.length} file${files.length > 1 ? 's' : ''}...`, 'info');
            let filesProcessed = 0;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.webkitRelativePath) {
                    const path = file.webkitRelativePath;
                    processEntries.push(
                        new Promise((resolve) => {
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                zip.file(path, e.target.result);
                                updateStatus(`File added: ${file.name}`, 'info');
                                filesProcessed++;
                                resolve();
                            };
                            reader.readAsArrayBuffer(file);
                        })
                    );
                } else {
                    processEntries.push(
                        new Promise((resolve) => {
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                zip.file(file.name, e.target.result);
                                updateStatus(`File added: ${file.name}`, 'info');
                                filesProcessed++;
                                resolve();
                            };
                            reader.readAsArrayBuffer(file);
                        })
                    );
                }
            }

            try {
                await Promise.all(processEntries);
                updateStatus('Generating ZIP...', 'info');

                const blob = await zip.generateAsync({
                    type: 'blob',
                    compression: 'DEFLATE',
                    compressionOptions: { level: 6 }
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

                updateStatus(`Created and saved ${files.length} files in ${zipName}.zip (${sizeText})`, 'success');
            } catch (error) {
                console.error('ZIP Error:', error);
                updateStatus('Failed to create ZIP file: ' + error.message, 'error');
            }
        }

        // Event handlers for buttons
        transferButton.addEventListener('click', () => {
            lastClickedButton = transferButton;
            fileInput.value = "";
            fileInput.click();
        });

        zipButton.addEventListener('click', () => {
            lastClickedButton = zipButton;
            zipFileInput.value = "";
            zipFileInput.click();
        });

        repeatButton.addEventListener('click', async () => {
            if (lastTransferFiles) {
                updateStatus('Repeating last transfer...', 'info');
                await transferFiles(lastTransferFiles);
            }
        });

        function transferFiles(files) {
            updateStatus('Transferring...', 'info');
            const formData = new FormData();
            formData.append('action', 'transferFiles');
            for (let i = 0; i < files.length; i++) {
                formData.append('transferFiles[]', files[i]);
            }

            // Store files for repeat function
            lastTransferFiles = files;
            repeatButton.disabled = false;

            return fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    let fileHtml = "";
                    if (result.files && result.files.length > 0) {
                        result.files.forEach(file => {
                            if (file.success) {
                                const status = file.replaced ? 'Replaced' : 'Copied';
                                const checkboxClass = file.replaced ? 'replaced' : 'new';
                                updateStatus(`File ${status.toLowerCase()}: ${file.name}`, 'success');

                                let nameHtml = file.isPhp ?
                                    `<a href="${file.url}" target="_blank">${file.name}</a>` :
                                    file.name;

                                fileHtml += `<div class="file-item">
                                    <input type="checkbox" checked disabled class="${checkboxClass}">
                                    <span>${nameHtml}</span>
                                    <span style="margin-left: auto">${status}</span>
                                </div>`;
                            } else {
                                updateStatus(`Failed to save: ${file.name}`, 'error');
                                fileHtml += `<div class="file-item">
                                    <input type="checkbox" disabled>
                                    <span>${file.name}</span>
                                    <span style="margin-left: auto; color: #dc3545">${file.error}</span>
                                </div>`;
                            }
                        });
                        fileList.innerHTML = fileHtml;
                    }
                })
                .catch(error => {
                    updateStatus('Error: ' + error.message, 'error');
                });
        }

        // Restart button handler
        restartButton.addEventListener('click', () => {
            // Try to preserve lastTransferFiles through reload
            if (lastTransferFiles) {
                try {
                    sessionStorage.setItem('lastTransferFiles', JSON.stringify(Array.from(lastTransferFiles)));
                } catch (e) {
                    console.log('Could not save transfer state');
                }
            }
            location.reload();
        });

        // Check for saved state on load
        try {
            const savedFiles = sessionStorage.getItem('lastTransferFiles');
            if (savedFiles) {
                lastTransferFiles = JSON.parse(savedFiles);
                repeatButton.disabled = false;
                sessionStorage.removeItem('lastTransferFiles'); // Clear after loading
            }
        } catch (e) {
            console.log('No saved state found');
        }

        // Initialize with Ready status
        updateStatus('Ready', 'info');
    </script>
</body>

</html>
