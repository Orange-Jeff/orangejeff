<?php
// If a POST file upload is desired, handle file uploads here.
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_FILES)) {
    // Process uploaded files. For demonstration, we simulate moving files to an "uploads" directory.
    $uploadedFiles = [];
    foreach ($_FILES['transferFiles']['error'] as $key => $error) {
        if ($error === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['transferFiles']['tmp_name'][$key];
            $name = basename($_FILES['transferFiles']['name'][$key]);
            // In real usage, move the uploaded file to a permanent location, e.g.:
            // move_uploaded_file($tmp_name, "uploads/$name");
            // Build a URL to the file (assuming 'uploads' is web-accessible).
            $uploadUrl = "uploads/" . $name;
            $uploadedFiles[] = [
                "name" => $name,
                "url" => $uploadUrl
            ];
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
            margin-left: 250px;
            margin-right: 0;
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
        }
        .file-item:last-child {
            border-bottom: none;
        }
        #status {
            margin-top: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div id="recorder-container">
        <div class="header">
            <h1>NetBound Tools: Transfer</h1>
        </div>
        <div class="content">
            <div class="button-group">
                <button id="transferButton">Transfer</button>
            </div>
            <input type="file" id="fileInput" style="display:none" multiple>
            <div id="fileList"></div>
                    <div class="status-box">
                        Status: <span id="status">Ready</span>
                    </div>
        </div>
    </div>
    <script>
        const transferButton = document.getElementById('transferButton');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const statusDiv = document.getElementById('status');

        transferButton.addEventListener('click', () => {
            // Open file requester.
            fileInput.value = ""; // Reset previous selection.
            fileInput.click();
        });

        fileInput.addEventListener('change', () => {
            // Display selected files.
            fileList.innerHTML = "";
            const files = fileInput.files;
            if (files.length > 0) {
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const item = document.createElement('div');
                    item.className = 'file-item';
                    item.textContent = file.name + " (" + Math.round(file.size / 1024) + " KB)";
                    fileList.appendChild(item);
                }
                // Optionally trigger file transfer.
                transferFiles(files);
            } else {
                fileList.textContent = "No files selected.";
            }
        });

        function transferFiles(files) {
            statusDiv.textContent = 'Transferring...';
            const formData = new FormData();
            formData.append('action', 'transferFiles');
            for (let i = 0; i < files.length; i++) {
                formData.append('transferFiles[]', files[i]);
            }
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                let fileHtml = "";
                if (result.files && result.files.length > 0) {
                    result.files.forEach(file => {
                        fileHtml += `<div class="file-item success">
                            <input type="checkbox" checked disabled>
                            <span><a href="${file.url}" target="${file.url}">${file.name}</a></span>
                            <span>COPIED</span>
                        </div>`;
                    });
                }
                statusDiv.innerHTML = fileHtml || result.message;
            })
            .catch(error => {
                statusDiv.textContent = 'Error: ' + error.message;
            });
        }
    </script>
</body>
</html>
