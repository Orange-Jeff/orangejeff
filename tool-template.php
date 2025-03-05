<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetBound Tools: Template</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f4f4f9;
            width: 100%;
            max-width: 768px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        /* Layout Components */
        .tool-container {
            background: #f4f4f9;
            width: 100%;
            margin: 0;
            padding: 0 20px;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }

        .tool-header {
            background: #f4f4f9;
            padding: 0 0 10px 0;
            border-bottom: 1px solid #dee2e6;
            width: 100%;
            box-sizing: border-box;
        }

        .work-area {
            padding: 0;
            height: auto;
            background: #f4f4f9;
            display: flex;
            width: 100%;
            flex-direction: column;
            align-items: flex-start;
            box-sizing: border-box;
        }

        .preview-area {
            width: 100%;
            min-height: 200px;
            border: 1px solid #ddd;
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .tool-title {
            margin: 20px 0 8px 0;
            color: #0056b3;
            line-height: 1.2;
            font-weight: bold;
            font-size: 18px;
        }

        /* Button Controls */
        .button-controls {
            width: 100%;
            padding: 10px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Joint button container */
        .joint-buttons {
            display: flex;
            width: 100%;
            flex-wrap: wrap;
            gap: 10px;
        }

        /* Button pair container */
        .button-pair {
            display: flex;
            overflow: hidden;
            flex: 1 1 calc(50% - 5px);
        }

        @media (max-width: 576px) {
            .button-pair {
                flex: 1 1 100%;
                margin-bottom: 5px;
            }
        }

        /* Main action button */
        .action-button {
            flex: 3;
            background: #0056b3;
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            border-top-left-radius: 3px;
            border-bottom-left-radius: 3px;
            transition: background-color 0.2s;
        }

        /* Plus/secondary button */
        .plus-button {
            background: #0056b3;
            color: white;
            border: none;
            border-left: 1px solid rgba(255, 255, 255, 0.2);
            padding: 6px 8px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-top-right-radius: 3px;
            border-bottom-right-radius: 3px;
            transition: background-color 0.2s;
        }

        /* Command buttons */
        .command-button {
            background: #0056b3;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        /* Button states */
        .action-button:hover,
        .plus-button:hover,
        .command-button:hover {
            background-color: #004494;
        }

        .action-button:disabled,
        .plus-button:disabled,
        .command-button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* Make buttons more responsive */
        @media (max-width: 576px) {
            .command-button {
                flex: 1 1 calc(50% - 5px);
                justify-content: center;
                white-space: normal;
            }
        }

        /* Status bar */
        .status-bar {
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
            margin: 0;
            font-size: 13px;
            color: #666;
            padding: 2px 5px;
            line-height: 24px;
            height: 24px;
        }

        .status-message.info {
            background: transparent;
        }

        .status-message.success {
            background: transparent;
            color: #28a745;
        }

        .status-message.error {
            background: transparent;
            color: #dc3545;
        }

        .status-message:first-child {
            background-color: #0056b3;
            color: white;
        }

        .status-message.success:first-child {
            background-color: #28a745;
            color: white;
        }

        .status-message.error:first-child {
            background-color: #dc3545;
            color: white;
        }

        /* Drop zone */
        .drop-zone {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            width: 100%;
            box-sizing: border-box;
            margin: 10px 0;
            border-radius: 4px;
        }

        .drop-zone.drag-over {
            background: #e3f2fd;
            border-color: #2196f3;
        }

        /* Bottom controls */
        .bottom-controls {
            width: 100%;
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media (max-width: 576px) {
            .bottom-controls {
                flex-direction: column;
            }
        }

        /* Frame-specific adjustments */
        body.in-frame {
            max-width: 100% !important;
            margin-left: 20px !important;
            margin-right: 0 !important;
        }

        body.standalone {
            max-width: 768px !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        /* Prevent flickering during page load */
        body {
            opacity: 0;
            transition: opacity 0.2s ease;
            max-width: 768px;
        }

        /* Add this to ensure visibility */
        body.loaded {
            opacity: 1;
        }
    </style>
</head>

<body>
    <div class="tool-container">
        <div class="tool-header">
            <h1 class="tool-title">NetBound Tools: Template</h1>
        </div>

        <div id="statusBar" class="status-bar"></div>

        <div class="button-controls">
            <div class="button-pair">
                <button class="command-button" data-tooltip="Open file from computer" id="btnOpen">
                    <i class="fas fa-folder-open"></i> Open
                </button>
            </div>

            <div class="button-pair">
                <button class="action-button" data-tooltip="Load from clipboard">
                    <i class="fas fa-clipboard"></i> From Clipboard
                </button>
                <button class="plus-button" data-tooltip="Append from clipboard">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <div class="button-pair">
                <button class="action-button" data-tooltip="Save to server">
                    <i class="fas fa-save"></i> Save
                </button>
                <button class="plus-button" data-tooltip="Save to computer">
                    <i class="fas fa-download"></i>
                </button>
            </div>

            <button class="command-button" data-tooltip="Reset tool" id="btnRestart">
                <i class="fas fa-redo"></i> Restart
            </button>
        </div>

        <div class="work-area">
            <div class="drop-zone" id="dropZone">
                Drag & drop files here or click the Open button
            </div>

            <!-- Preview Area -->
            <div class="preview-area">
                Preview Content
            </div>
        </div>

        <!-- Bottom Controls -->
        <div class="bottom-controls">
            <div class="button-pair">
                <button class="action-button">
                    <i class="fas fa-save"></i> Save
                </button>
                <button class="plus-button">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <button class="command-button">
                <i class="fas fa-check"></i> Done
            </button>
        </div>
    </div>

    <!-- Add hidden file input -->
    <input type="file" id="fileInput" style="display: none">

    <script>
        const OrangeUI = {
            initStatusBar(containerId) {
                const container = document.getElementById(containerId);
                return {
                    update(message, type = 'info') {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `status-message ${type}`;
                        messageDiv.textContent = message;
                        container.insertBefore(messageDiv, container.firstChild);

                        // Limit history to prevent excessive DOM nodes
                        if (container.childElementCount > 20) {
                            container.removeChild(container.lastChild);
                        }
                    }
                };
            },

            initFileDropZone(dropZoneId, fileCallback, acceptTypes = '.wav') {
                const dropZone = document.getElementById(dropZoneId);

                dropZone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    dropZone.classList.add('drag-over');
                });

                dropZone.addEventListener('dragleave', () => {
                    dropZone.classList.remove('drag-over');
                });

                dropZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropZone.classList.remove('drag-over');
                    const files = e.dataTransfer.files;
                    if (files[0]) fileCallback(files[0]);
                });
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            // Initialize status bar
            const status = OrangeUI.initStatusBar('statusBar');

            // Setup buttons
            const fileInput = document.getElementById('fileInput');

            document.getElementById('btnOpen').onclick = () => {
                fileInput.click();
            };

            fileInput.addEventListener('change', (e) => {
                if (e.target.files[0]) {
                    handleFile(e.target.files[0]);
                }
            });

            document.getElementById('btnRestart').onclick = () => {
                location.reload();
            };

            // Function to detect iframe and adjust positioning
            function adjustPositioningForFrame() {
                // Check if we're in an iframe
                const inFrame = window !== window.top;

                if (inFrame) {
                    // In iframe: left-justified with 20px margin
                    document.body.style.maxWidth = '100%';
                    document.body.style.margin = '0 0 0 20px';
                } else {
                    // Not in iframe: left-justified with no margin
                    document.body.style.maxWidth = '768px';
                    document.body.style.margin = '0';
                }

                // Add a class to body for additional CSS targeting
                document.body.classList.add(inFrame ? 'in-frame' : 'standalone');

                // Ensure the body is visible
                document.body.classList.add('loaded');
            }

            // Initialize the drop zone
            OrangeUI.initFileDropZone('dropZone', handleFile);

            function handleFile(file) {
                // Add your file handling code here
                status.update(`File loaded: ${file.name}`, 'success');
            }

            // Check if we're in an iframe and adjust positioning
            adjustPositioningForFrame();

            status.update('Open file to begin', 'info');
        });
    </script>
</body>

</html>
