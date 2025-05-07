<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetBound Tools: Audio Extractor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Version 1.2 - Consolidated CSS */
        :root {
            --primary-color: #0056b3;
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --background-color: #e9ecef;
            --button-padding-y: 6px;
            --button-padding-x: 10px;
            --button-border-radius: 4px;
            --success-color: #28a745;
            --error-color: #dc3545;
            --info-color: #17a2b8;
            --transition-speed: 0.3s;
        }

        /* Base styles */
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            font-family: Arial, sans-serif;
            background-color: #e9ecef;
            text-align: left;
        }

        /* Layout and container styles */
        .menu-container {
            position: relative;
            width: 100%;
            max-width: 900px; /* Changed from 768px to 900px */
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }

        .title-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .editor-title {
            margin: 0 0 15px;
            padding: 0;
            line-height: 1.2;
            color: var(--primary-color);
            font-weight: bold;
            font-size: 18px;
        }

        .hamburger-menu {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 1.2rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Status box styles */
        .status-box {
            width: 100%;
            height: 90px;
            min-height: 90px;
            max-height: 90px;
            overflow-y: auto;
            border: 1px solid var(--primary-color);
            background: #fff;
            padding: 10px 5px;
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
            background-color: transparent;
            font-size: 0.9em;
            text-align: left;
            justify-content: flex-start;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .status-message.info {
            border-left: 3px solid #2196f3;
        }

        .status-message.success {
            border-left: 3px solid #4caf50;
        }

        .status-message.error {
            border-left: 3px solid #f44336;
        }

        /* Button styles */
        .button-controls {
            margin: 15px 0;
        }

        .button-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 10px 0;
        }

        .command-button {
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

        .command-button:hover {
            background-color: #003d82;
        }

        .command-button:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }

        /* Editor view styles */
        .editor-view {
            margin-top: 15px;
        }

        .preview-area {
            width: 100%;
            margin-bottom: 15px;
        }

        #video-preview {
            width: 100%;
            max-height: 500px;
            background-color: #000;
            border-radius: 4px;
        }

        .filename-control {
            display: flex;
            margin-top: 10px;
            gap: 10px;
        }

        .filename-input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }

        /* Bottom action bar */
        .bottom-action-bar {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
        }

        /* Drag and drop styles */
        .status-box.drag-over {
            background: #e3f2fd;
            border-color: #2196f3;
            border-style: dashed;
        }

        /* Media queries for responsiveness */
        @media screen and (max-width: 768px) {
            .menu-container {
                padding: 10px;
            }

            .button-row {
                flex-direction: column;
            }

            .command-button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="menu-container">
        <!-- Title with hamburger menu -->
        <div class="title-container">
            <h1 class="editor-title">NetBound Tools: Audio Extractor</h1>
            <a href="main.php?app=nb-audio-extract.php" class="hamburger-menu" title="Go to Main Menu">
                <i class="fas fa-bars"></i>
            </a>
        </div>

        <!-- Status box below title with consistent padding -->
        <div class="status-box" id="status"></div>

        <!-- Main buttons below status box -->
        <div class="button-controls">
            <div class="button-row">
                <button class="command-button" id="btnOpen">
                    <i class="fas fa-folder-open"></i> Open Video
                </button>
                <button class="command-button" id="btnBulkWav">
                    <i class="fas fa-folder-open"></i> Bulk WAV
                </button>
                <button class="command-button" id="btnBulkMp3">
                    <i class="fas fa-folder-open"></i> Bulk MP3
                </button>
                <button class="command-button" id="btnRestart">
                    <i class="fas fa-redo"></i> Restart
                </button>
            </div>
        </div>

        <!-- Main editor area -->
        <div class="editor-view">
            <div class="preview-area">
                <video id="video-preview" controls poster="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 9'%3E%3C/svg%3E"></video>
                <div class="filename-control">
                    <input type="text" id="filename" class="filename-input" readonly placeholder="No file selected">
                    <button class="command-button" id="btnRename" disabled>
                        <i class="fas fa-edit"></i> Rename
                    </button>
                </div>
            </div>
        </div>

        <!-- Bottom action bar -->
        <div class="bottom-action-bar">
            <div class="button-row">
                <button class="command-button" id="btnSaveMp3" disabled>
                    <i class="fas fa-file-audio"></i> Save MP3
                </button>
                <button class="command-button" id="btnSaveWav" disabled>
                    <i class="fas fa-file-audio"></i> Save WAV
                </button>
                <button class="command-button" id="btnRemoveAudio" disabled>
                    <i class="fas fa-volume-mute"></i> Remove Audio
                </button>
            </div>
        </div>
    </div>

    <input type="file" id="fileInput" accept=".mp4,.webm,.mkv" style="display: none">
    <input type="file" id="bulkInput" accept=".mp4,.webm,.mkv" style="display: none" multiple>
    <input type="file" id="bulkMp3Input" accept=".mp4,.webm,.mkv" style="display: none" multiple>

    <script>
        // Status message handling
        const statusManager = {
            elements: {},

            // Create or update a specific status message
            update(id, message, type = 'info') {
                const statusBox = document.getElementById('status');

                if (this.elements[id]) {
                    // Update existing status message
                    this.elements[id].textContent = message;
                    return this.elements[id];
                } else {
                    // Create new message element with shared style classes
                    const statusMessage = document.createElement('div');
                    statusMessage.className = `status-message ${type}`;
                    statusMessage.textContent = message;

                    // Add to the top of status box
                    statusBox.insertBefore(statusMessage, statusBox.firstChild);

                    // Store reference to this element
                    this.elements[id] = statusMessage;

                    // Limit the number of messages
                    while (statusBox.children.length > 10) {
                        const lastChild = statusBox.lastChild;
                        const ids = Object.keys(this.elements);
                        const isTracked = ids.some(id => this.elements[id] === lastChild);
                        if (!isTracked) {
                            statusBox.removeChild(lastChild);
                        } else {
                            const nextToLast = lastChild.previousSibling;
                            if (nextToLast) statusBox.removeChild(nextToLast);
                            else break;
                        }
                    }

                    return statusMessage;
                }
            },

            // Remove a tracked status message
            remove(id) {
                if (this.elements[id]) {
                    const element = this.elements[id];
                    if (element.parentNode) {
                        element.parentNode.removeChild(element);
                    }
                    delete this.elements[id];
                }
            }
        };

        // Keep backward compatibility with old status function
        function updateStatus(message, type = 'info') {
            const id = 'msg_' + Math.random().toString(36).substr(2, 9);
            return statusManager.update(id, message, type);
        }

        let currentFile = null;
        let isProcessing = false;
        let bulkFiles = [];
        let currentBulkIndex = 0;

        // Initialize drag and drop functionality
        function initDragAndDrop(statusBox, fileInput) {
            statusBox.addEventListener('dragover', (e) => {
                e.preventDefault();
                statusBox.classList.add('drag-over');
            });

            statusBox.addEventListener('dragleave', () => {
                statusBox.classList.remove('drag-over');
            });

            statusBox.addEventListener('drop', (e) => {
                e.preventDefault();
                statusBox.classList.remove('drag-over');
                if (e.dataTransfer.files.length > 0) {
                    if (e.dataTransfer.files.length > 1) {
                        const format = confirm("Convert to MP3? (Cancel for WAV)") ? 'mp3' : 'wav';
                        handleBulkFiles(Array.from(e.dataTransfer.files), format);
                    } else {
                        handleSingleFile(e.dataTransfer.files[0]);
                    }
                }
            });
        }

        // Set up the UI when the document is ready
        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('fileInput');
            const bulkInput = document.getElementById('bulkInput');
            const bulkMp3Input = document.getElementById('bulkMp3Input');
            const btnOpen = document.getElementById('btnOpen');
            const btnBulkWav = document.getElementById('btnBulkWav');
            const btnBulkMp3 = document.getElementById('btnBulkMp3');
            const btnSaveMp3 = document.getElementById('btnSaveMp3');
            const btnSaveWav = document.getElementById('btnSaveWav');
            const btnRestart = document.getElementById('btnRestart');
            const btnRename = document.getElementById('btnRename');
            const filename = document.getElementById('filename');
            const videoPreview = document.getElementById('video-preview');
            const statusBar = document.getElementById('status');
            const btnRemoveAudio = document.getElementById('btnRemoveAudio');

            btnOpen.onclick = () => fileInput.click();
            btnBulkWav.onclick = () => bulkInput.click();
            btnBulkMp3.onclick = () => bulkMp3Input.click();
            btnRestart.onclick = () => location.reload();

            btnSaveMp3.onclick = () => {
                if (currentFile) {
                    convertToAudio(currentFile, 'mp3');
                }
            };

            btnSaveWav.onclick = () => {
                if (currentFile) {
                    convertToAudio(currentFile, 'wav');
                }
            };

            btnRemoveAudio.onclick = () => {
                if (currentFile) {
                    createSilentVideo(currentFile);
                }
            };

            fileInput.onchange = (e) => {
                if (e.target.files.length > 0) {
                    handleSingleFile(e.target.files[0]);
                }
            };

            bulkInput.onchange = (e) => {
                if (e.target.files.length > 0) {
                    handleBulkFiles(Array.from(e.target.files), 'wav');
                }
            };

            bulkMp3Input.onchange = (e) => {
                if (e.target.files.length > 0) {
                    handleBulkFiles(Array.from(e.target.files), 'mp3');
                }
            };

            btnSaveMp3.disabled = true;
            btnSaveWav.disabled = true;
            btnRemoveAudio.disabled = true;
            btnRename.disabled = true;

            btnRename.onclick = () => {
                const wasReadOnly = filename.readOnly;
                filename.readOnly = !wasReadOnly;

                if (wasReadOnly) {
                    filename.focus();
                    filename.select();
                    btnRename.innerHTML = '<i class="fas fa-save"></i> Save';
                } else {
                    if (currentFile && filename.value) {
                        const newName = filename.value;
                        currentFile = new File([currentFile], newName, {
                            type: currentFile.type
                        });
                        updateStatus(`File renamed to: ${newName}`, 'success');
                        btnRename.innerHTML = '<i class="fas fa-edit"></i> Rename';
                    }
                }
            };

            initDragAndDrop(document.getElementById('status'), document.getElementById('fileInput'));

            updateStatus('Drag file(s) here or open a video', 'info');
        });
    </script>
</body>

</html>
