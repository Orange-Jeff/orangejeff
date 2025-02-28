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
            width: 550px;
        }

        .tool-container {
            background: #f4f4f9;
            height: auto;
            margin: 0;
            max-width: 600px;
            width: 100%;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .work-area {
            padding: 15px 15px 15px 0;
            height: auto;
            background: #f4f4f9;
            display: flex;
            width: 100%;
            flex-direction: column;
            align-items: flex-start;
        }

        .preview-area {
            padding: 10px;
            margin: 10px 0 10px 0;
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

        .joint-buttons {
            display: flex;
            width: 100%;
        }

        /* Button pair container */
        .button-pair {
            display: flex;
            overflow: hidden;
            width: fit-content;
        }

        /* Main action button */
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

        /* Plus button companion */
        .plus-button {
            background: #0056b3;
            color: white;
            border: none;
            border-left: 1px solid rgba(255, 255, 255, 0.2);
            padding: 6px 8px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-top-right-radius: 3px;
            border-bottom-right-radius: 3px;
        }

        /* Standard command buttons */
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
        }

        .preview-area {
            width: 100%;
            min-height: 200px;
            border: 1px solid #ddd;
            margin: 10px 0;
            padding: 10px;
            background: white;
        }

        .bottom-controls {
            width: 100%;
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
    </style>
    <style>
        .status-bar {
            height: 150px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            margin: 10px 0;
        }

        .status-message {
            padding: 5px;
            margin: 2px 0;
            border-radius: 3px;
        }

        .status-message.info {
            background: #e3f2fd;
        }

        .status-message.success {
            background: #e8f5e9;
        }

        .status-message.error {
            background: #ffebee;
        }

        .drop-zone {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }

        .drop-zone.drag-over {
            background: #e3f2fd;
            border-color: #2196f3;
        }
    </style>
    <script>
        const OrangeUI = {
            initStatusBar(containerId) {
                const container = document.getElementById(containerId);
                return {
                    update(message, type = 'info') {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `status-message ${type}`;
                        messageDiv.textContent = message;
                        container.appendChild(messageDiv);
                        container.scrollTop = container.scrollHeight;
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
            const status = OrangeUI.initStatusBar('statusBar');
            const fileInput = document.getElementById('fileInput');
            status.update('Open file to begin', 'info');

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
        });
    </script>
</head>

<!-- Add hidden file input -->
<input type="file" id="fileInput" style="display: none">
</head>

<body>
    <div class="tool-container">
        <div class="tool-header">
            <h1 class="tool-title">NetBound Tools: Template</h1>
            <div id="statusBar" class="status-bar"></div>
            <div class="button-controls">
                <button class="command-button" data-tooltip="Open file from computer" id="btnOpen">
                    <i class="fas fa-folder-open"></i> Open
                </button>
                <div class="button-pair">
                    <button class="action-button" data-tooltip="Load from clipboard">
                        <i class="fas fa-clipboard"></i> From Clipboard
                    </button>
                    <button class="plus-button" data-tooltip="Append from clipboard">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <button class="command-button" data-tooltip="Reset tool" id="btnRestart">
                    <i class="fas fa-redo"></i> Restart
                </button>
                <div class="button-pair">
                    <button class="action-button" data-tooltip="Save to server">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button class="plus-button" data-tooltip="Save to computer">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="work-area">
            <!-- Preview Area -->
            <div class="preview-area">
                Preview Content
            </div>

            <!-- Bottom Controls -->
            <div class="button-controls">
                <div class="button-pair">
                    <button class="action-button"><i class="fas fa-save"></i> Save</button>
                    <button class="plus-button"><i class="fas fa-plus"></i></button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
