<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>NetBound Tools: Template</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f4f4f9;
            width: 768px;
            margin: 0 auto;
            /* Center the body in standalone mode */
        }

        /* In iframe, use left alignment */
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

        .work-area {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .preview-area {
            margin-top: 10px;
            width: 100%;
        }

        .tool-title {
            margin: 10px 0;
            padding: 0;
            color: #0056b3;
            line-height: 1.2;
            font-weight: bold;
            font-size: 18px;
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
            /* Ensure padding is included in width */
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

        .filename-control {
            width: 100%;
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }

        .filename-input {
            flex: 1;
            padding: 6px 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-family: inherit;
            font-size: 14px;
        }

        .status-bar.drag-over {
            background: #e3f2fd;
            border-color: #2196f3;
            border-style: dashed;
        }

        .button-pair {
            display: flex;
            overflow: hidden;
            width: fit-content;
        }

        .action-button {
            background: #0056b3;
            color: white;
            border: none;
            padding: 6px 8px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-top-left-radius: 3px;
            border-bottom-left-radius: 3px;
        }

        .plus-button {
            background: #0056b3;
            color: white;
            border: none;
            border-left: 1px solid rgba(255, 255, 255, 0.2);
            padding: 6px 8px;
            cursor: pointer;
            font-size: 14px;
            border-top-right-radius: 3px;
            border-bottom-right-radius: 3px;
        }

        .action-button:hover,
        .plus-button:hover {
            background: #004494;
        }

        .action-button:disabled,
        .plus-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* Content area styling - aspect ratio for landscape video */
        .content-area {
            width: 100%;
            height: 432px;
            /* 16:9 aspect ratio (768px * 9/16) */
            background: #2a2a2a;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            /* Ensure consistent sizing */
        }

        /* Save button styles */
        .save-button-container {
            display: flex;
            width: fit-content;
            margin: 10px 0;
        }

        .save-button {
            background: #0056b3;
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-top-left-radius: 3px;
            border-bottom-left-radius: 3px;
            transition: background-color 0.2s;
        }

        .download-button {
            background: #0056b3;
            color: white;
            border: none;
            border-left: 1px solid rgba(255, 255, 255, 0.3);
            padding: 6px 8px;
            cursor: pointer;
            font-size: 14px;
            border-top-right-radius: 3px;
            border-bottom-right-radius: 3px;
            transition: background-color 0.2s;
        }

        .save-button:hover {
            background: #004494;
        }

        .download-button:hover {
            background: #004494;
        }

        .save-button:disabled,
        .download-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* Ghosted button example */
        .ghosted-button {
            background: rgba(0, 86, 179, 0.3);
            color: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(0, 86, 179, 0.3);
            border-radius: 3px;
            padding: 6px 8px;
            cursor: not-allowed;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
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
            <h1 class="tool-title">NetBound Tools: Tool Template</h1>
            <div id="statusBar" class="status-bar"></div>
            <div class="button-controls">
                <button class="command-button" id="btnOpen">
                    <i class="fas fa-folder-open"></i> Open File
                </button>
                <button class="command-button" id="btnActionOne">
                    <i class="fas fa-play"></i> Action One
                </button>
                <button class="command-button" id="btnActionTwo">
                    <i class="fas fa-cog"></i> Action Two
                </button>
                <button class="command-button" id="btnActionThree">
                    <i class="fas fa-save"></i> Action Three
                </button>
                <button class="command-button" id="btnRestart">
                    <i class="fas fa-redo"></i> Restart
                </button>
            </div>
        </div>

        <div class="work-area">
            <div class="preview-area">
                <!-- Content area with 16:9 aspect ratio -->
                <div class="content-area" id="contentArea">
                    <!-- Tool-specific content goes here -->
                </div>

                <!-- Filename control below preview area -->
                <div class="filename-control">
                    <input type="text" id="filename" class="filename-input" placeholder="No file selected">
                    <button class="command-button" id="btnRename">
                        <i class="fas fa-edit"></i> Rename
                    </button>
                </div>

                <!-- Save button pair -->
                <div class="save-button-container">
                    <button class="save-button" id="btnSave" disabled>
                        <i class="fas fa-save"></i> SAVE
                    </button>
                    <button class="download-button" id="btnDownload" disabled>
                        <i class="fas fa-plus"></i>
                    </button>
                </div>

                <!-- Additional button controls -->
                <div class="button-controls">
                    <button class="command-button" id="btnProcess" disabled>
                        <i class="fas fa-check"></i> Process
                    </button>
                    <button class="ghosted-button" disabled>
                        <i class="fas fa-ghost"></i> Disabled
                    </button>
                </div>
            </div>
        </div>
    </div>

    <input type="file" id="fileInput" style="display: none">

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

        // Status message handling
        const status = {
            update(message, type = 'info') {
                const container = document.getElementById('statusBar');
                const messageDiv = document.createElement('div');
                messageDiv.className = `status-message ${type}`;
                messageDiv.textContent = message;
                container.insertBefore(messageDiv, container.firstChild); // Insert at top
                container.scrollTop = 0; // Keep scrolled to top
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('fileInput');
            const btnOpen = document.getElementById('btnOpen');
            const btnRename = document.getElementById('btnRename');
            const btnRestart = document.getElementById('btnRestart');
            const btnActionOne = document.getElementById('btnActionOne');
            const btnActionTwo = document.getElementById('btnActionTwo');
            const btnActionThree = document.getElementById('btnActionThree');
            const btnProcess = document.getElementById('btnProcess');
            const btnSave = document.getElementById('btnSave');
            const btnDownload = document.getElementById('btnDownload');
            const statusBar = document.getElementById('statusBar');
            const filename = document.getElementById('filename');

            let originalFilename = '';
            let currentFile = null;

            // Initialize drag and drop functionality
            function initDragAndDrop(statusBar, fileInput) {
                // Show initial status message
                status.update('Tool template ready. Drag files here or use buttons.', 'info');

                statusBar.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    statusBar.classList.add('drag-over');
                });

                statusBar.addEventListener('dragleave', () => {
                    statusBar.classList.remove('drag-over');
                });

                statusBar.addEventListener('drop', (e) => {
                    e.preventDefault();
                    statusBar.classList.remove('drag-over');
                    if (e.dataTransfer.files.length > 0) {
                        handleFile(e.dataTransfer.files[0]);
                    }
                });
            }

            // File handling function
            function handleFile(file) {
                if (file) {
                    currentFile = file;
                    originalFilename = file.name;
                    filename.value = file.name;
                    status.update(`File selected: ${file.name}`, 'success');

                    // Get file extension
                    const fileExt = originalFilename.split('.').pop();

                    // Enable relevant buttons
                    btnProcess.disabled = false;
                    btnSave.disabled = false;
                    btnDownload.disabled = false;
                    btnRename.disabled = false;
                }
            }

            // Button click handlers
            btnOpen.onclick = () => fileInput.click();
            btnRestart.onclick = () => location.reload();

            // Handle rename functionality
            btnRename.onclick = () => {
                if (currentFile) {
                    const newName = filename.value.trim();
                    if (newName) {
                        // Ensure file extension is preserved
                        const fileExt = originalFilename.split('.').pop();
                        if (!newName.endsWith(`.${fileExt}`)) {
                            filename.value = `${newName}.${fileExt}`;
                        }
                        status.update(`File renamed to: ${filename.value}`, 'info');
                    } else {
                        filename.value = originalFilename;
                        status.update('Filename cannot be empty, reverted to original', 'error');
                    }
                }
            };

            // Example button handlers
            btnActionOne.onclick = () => {
                status.update('Action One clicked', 'info');
            };

            btnActionTwo.onclick = () => {
                status.update('Action Two clicked', 'info');
            };

            btnActionThree.onclick = () => {
                status.update('Action Three clicked', 'info');
            };

            btnProcess.onclick = () => {
                status.update('Processing...', 'info');
                setTimeout(() => {
                    status.update('Process completed!', 'success');
                }, 1000);
            };

            // Save button handlers
            btnSave.onclick = () => {
                status.update(`Saving ${filename.value} to program folder...`, 'info');
                setTimeout(() => {
                    status.update('File saved to program folder', 'success');
                }, 500);
            };

            btnDownload.onclick = () => {
                status.update(`Saving ${filename.value} to downloads folder...`, 'info');
                setTimeout(() => {
                    status.update('File saved to downloads folder', 'success');
                }, 500);
            };

            // Set initial button states
            btnProcess.disabled = true;
            btnSave.disabled = true;
            btnDownload.disabled = true;
            btnRename.disabled = true;

            // File input handler
            fileInput.onchange = (e) => {
                if (e.target.files.length > 0) {
                    handleFile(e.target.files[0]);
                }
            };

            // Initialize drag and drop
            initDragAndDrop(statusBar, fileInput);
        });
    </script>
</body>

</html>
