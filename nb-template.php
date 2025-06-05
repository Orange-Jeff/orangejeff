<?php
// NetBound Tool Template v1.8
// A standardized template matching the main.php styling for new tools

// Error reporting setup - comment out for production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle any AJAX or form requests here
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'exampleAction') {
            $response = ['status' => 'success', 'message' => 'Action processed successfully'];
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }
}

// Any PHP processing code goes here
$toolTitle = "NetBound Template";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetBound Tools: <?php echo $toolTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ========== ROOT VARIABLES ========== */
        :root {
            --primary-color: #0056b3;
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --background-color: #e9ecef;
            --header-height: 40px;
            --button-padding-y: 6px;
            --button-padding-x: 10px;
            --button-border-radius: 4px;
            --status-bar-padding: 8px 15px; /* Added to match main-styles.css */
            --status-bar-margin: 5px 0; /* Added to match main-styles.css */
            --success-color: #28a745;
            --error-color: #dc3545;
            --info-color: #17a2b8;
            --transition-speed: 0.3s; /* Keeping this as it's useful */
            --warning-color: #f39c12; /* main-styles.css doesn't have --warning-color but it's useful here */
        }

        /* ========== BASE STYLES ========== */
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: var(--background-color);
            flex: 1;
            overflow: hidden;
            text-align: left;
        }

        /* ========== LAYOUT ========== */
        .tool-container {
            max-width: 900px;
            width: 100%;
            height: calc(100vh - 15px); /* Adjusted from +25px to -15px (40px reduction) */
            margin: 0 auto;
            padding: 0;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            overflow: hidden;
        }

        /* Tool header with title */
        .tool-header {
            width: 100%;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 0;
            margin-top: 0; /* Reduced from 5px */
        }

        /* Style the tool title to match main.php */
        .tool-title {
            margin: 4px 0; /* Reduced from 6px */
            padding-left: 10px;
            padding-right: 10px;
            color: var(--primary-color);
            line-height: 1.2;
            font-weight: bold;
            font-size: 18px;
            flex: 1;
        }

        /* Header buttons - made consistent size */
        .header-button, .hamburger-menu {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--button-border-radius);
            padding: 4px 8px; /* Keep padding for icon spacing */
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            margin-left: 5px;
            text-decoration: none;
            box-sizing: border-box; /* Add this */
        }

        .header-button:hover,
        .hamburger-menu:hover {
            background-color: #003d82;
        }

        .header-buttons {
            display: flex;
            gap: 5px;
            align-items: center;
            padding-right: 10px;
        }

        /* Main content area with tighter padding */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 0 5px; /* Reduced from 10px */
            overflow: hidden;
        }

        /* ========== BUTTON STYLES ========== */
        .button-row {
            padding: 2px 0; /* Reduced from 5px */
            margin: 5px 0; /* Reduced from 10px */
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
        }

        .button-group {
            display: inline-flex;
            gap: 8px;
            flex-wrap: wrap;
            flex-shrink: 0;
            margin: 2px 0; /* Reduced from 5px */
        }

        .button-group.left {
            justify-content: flex-start;
        }

        .button-group.right {
            justify-content: flex-end;
            margin-left: auto;
        }

        .command-button,
        .split-button {
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

        .command-button:hover,
        .split-button:hover {
            background-color: #003d82;
        }

        .split-button {
            display: flex;
            padding: 0;
            gap: 1px;
            background-color: white;
            align-items: stretch;
        }

        .split-button .main-part,
        .split-button .append-part {
            background-color: var(--primary-color);
            padding: var(--button-padding-y) var(--button-padding-x);
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .split-button .main-part {
            border-radius: var(--button-border-radius) 0 0 var(--button-border-radius);
        }

        .split-button .append-part {
            padding: var(--button-padding-y) 7px;
            border-radius: 0 var(--button-border-radius) var(--button-border-radius) 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .split-button i {
            margin-right: 5px;
        }

        .split-button .main-part:hover,
        .split-button .append-part:hover {
            background-color: #003d82;
        }

        /* ========== STATUS BOX STYLES ========== */
        .status-box {
            width: calc(100% - 10px); /* Adjusted width to account for 5px padding on each side */
            height: 90px;
            min-height: 90px;
            max-height: 90px;
            overflow-y: auto;
            border: 1px solid var(--primary-color);
            background: #fff;
            padding: 5px;
            margin: 5px auto; /* Changed horizontal margin to auto to center it */
            border-radius: 4px;
            display: flex;
            flex-direction: column-reverse;
            box-sizing: border-box;
            position: relative;
        }

        /* Status message styling */
        .message {
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

        .message.info {
            border-left: 3px solid var(--info-color);
        }

        .message.success {
            border-left: 3px solid var(--success-color);
        }

        .message.error {
            border-left: 3px solid var(--error-color);
        }

        .message.warning {
            border-left: 3px solid var(--warning-color);
        }

        /* Latest message highlight */
        .message.latest {
            color: white;
            font-weight: bold;
        }

        .message.latest.info {
            background-color: var(--info-color);
        }

        .message.latest.success {
            background-color: var(--success-color);
        }

        .message.latest.error {
            background-color: var(--error-color);
        }

        .message.latest.warning {
            background-color: var(--warning-color);
        }

        /* Content container with tighter margins and no bottom border/padding */
        .content-container {
            flex: 1;
            background-color: white;
            border: 1px solid var(--primary-color);
            border-radius: 4px;
            padding: 5px 5px 5px 5px; /* Same padding for all sides */
            margin: 5px 0 5px 0; /* No extra bottom margin */
            overflow: hidden;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Smart resize container for media */
        .media-container {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        /* Media content with smart aspect ratio */
        .media-content {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: all 0.3s ease;
        }

        /* Text content container */
        .text-content {
            width: 100%;
            height: 100%;
            overflow-y: auto;
            padding: 10px;
            box-sizing: border-box;
        }

        /* Drag and drop status box */
        .status-box.drag-over {
            background: #e3f2fd;
            border-color: #2196f3;
            border-style: dashed;
        }

        /* Status box drag indicator */
        .status-box::after {
            content: "Drop files here";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(33, 150, 243, 0.7);
            color: white;
            font-size: 16px;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }

        .status-box.drag-over::after {
            opacity: 1;
        }

        /* Fixed bottom button bar with no top border/padding and transparent background */
        .bottom-button-bar {
            background-color: transparent; /* Changed from var(--background-color) to transparent */
            padding: 5px 0;
            width: 100%;
            border-top: none;
            position: relative;
            z-index: 10;
            flex-shrink: 0;
            margin-top: 0;
        }

        /* Responsive design - fixed mobile issues */
        @media (max-width: 768px) {
            .button-row {
                flex-wrap: nowrap; /* Changed from wrap to nowrap to prevent wrapping */
                justify-content: space-between; /* Keep buttons on one line */
                width: 100%;
            }

            .button-group {
                flex-wrap: nowrap; /* Prevent button wrapping on mobile */
                flex-shrink: 1; /* Allow buttons to shrink if needed */
            }

            .command-button, .split-button {
                font-size: 12px;
                white-space: nowrap; /* Prevent text wrapping in buttons */
                min-width: 0; /* Allow buttons to shrink if needed */
            }

            .split-button {
                flex-shrink: 1; /* Allow split buttons to shrink */
            }

            .content-container {
                padding: 8px;
            }

            .bottom-button-bar {
                padding: 5px 0;
                background-color: transparent; /* Ensure transparent background on mobile too */
            }
        }

        @media (max-width: 480px) {
            .button-row {
                flex-direction: row; /* Changed from column to row to keep buttons on one line */
                align-items: center;
                flex-wrap: nowrap;
            }

            .button-group {
                flex-direction: row; /* Keep button groups horizontal */
                width: auto; /* Don't force full width */
                flex-wrap: nowrap;
            }

            .command-button, .split-button {
                width: auto; /* Allow buttons to size naturally */
                justify-content: center;
                padding: 4px 6px; /* Slightly smaller padding on very small screens */
                min-width: 0;
            }

            .button-group.left,
            .button-group.right {
                width: auto; /* Don't force full width */
            }
        }
    </style>
</head>
<body>
    <div class="tool-container">
        <div class="tool-header">
            <div class="header-flex">
                <h1 class="tool-title">NetBound Tools: <?php echo $toolTitle; ?></h1>

                <!-- Header buttons with consistent sizing -->
                <div class="header-buttons">
                    <button id="rerunBtn" class="header-button" title="Re-run and reset all data">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <a href="main.php?app=nb-template.php" target="_top" class="hamburger-menu" title="Open in main menu">
                        <i class="fas fa-bars"></i>
                    </a>
                </div>
            </div>

            <!-- Status box with less margin -->
            <div id="statusBox" class="status-box"></div>
        </div>

        <div class="main-content">
            <!-- Top row buttons - for getting data in -->
            <div class="button-row">
                <div class="button-group left">
                    <button type="button" class="command-button" onclick="fromFile()" title="Load from a file">
                        <i class="fas fa-folder-open"></i> From File
                    </button>

                    <!-- Split button example for clipboard operations -->
                    <div class="split-button">
                        <div class="main-part" onclick="fromClipboard()" title="Load from clipboard">
                            <i class="fas fa-clipboard"></i> From Clipboard
                        </div>
                        <div class="append-part" onclick="appendClipboard()" title="Append from clipboard">
                            <i class="fas fa-plus"></i>
                        </div>
                    </div>
                </div>

                <div class="button-group right">
                    <button type="button" class="command-button" onclick="fromTemplate()" title="Load from template">
                        <i class="fas fa-download"></i> From Template
                    </button>
                </div>
            </div>

            <!-- Main content area - smart resizing container -->
            <div id="contentContainer" class="content-container">
                <!-- Media container for smart aspect ratio -->
                <div class="media-container">
                    <!-- Example content - will be replaced with actual content -->
                    <img id="sampleMedia" class="media-content" style="display: none;" alt="Sample media">

                    <!-- Text content area -->
                    <div id="textContent" class="text-content">
                        <p>Main tool content area. This container will smartly resize based on content type and browser dimensions.</p>
                        <p>The media container maintains aspect ratio for images and videos, while text content gets a scrollbar if needed.</p>
                    </div>
                </div>
            </div>

            <!-- Fixed bottom button bar -->
            <div class="bottom-button-bar">
                <div class="button-row">
                    <div class="button-group left">
                        <!-- Split button for save operations -->
                        <div class="split-button">
                            <div class="main-part" onclick="saveData()" title="Save data">
                                <i class="fas fa-save"></i> Save
                            </div>
                            <div class="append-part" onclick="saveAs()" title="Save as">
                                <i class="fas fa-download"></i>
                            </div>
                        </div>

                        <!-- Split button for clipboard output -->
                        <div class="split-button">
                            <div class="main-part" onclick="toClipboard()" title="Copy to clipboard">
                                <i class="fas fa-clipboard"></i> To Clipboard
                            </div>
                        </div>
                    </div>

                    <div class="button-group right">
                        <button type="button" class="command-button" onclick="processData()" title="Process the data">
                            <i class="fas fa-cog"></i> Process
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Define the status object for consistent message handling
        const status = {
            update: function(message, type = 'info') {
                const statusBox = document.getElementById('statusBox');
                if (!statusBox) return;

                const messageElement = document.createElement('div');
                messageElement.className = `message ${type}`;
                messageElement.textContent = message;

                // Remove 'latest' class from any existing messages
                const existingMessages = statusBox.querySelectorAll('.message.latest');
                existingMessages.forEach(msg => msg.classList.remove('latest'));

                // Add 'latest' class to the new message
                messageElement.classList.add('latest');

                // Insert at the beginning (since display is flex-direction: column-reverse)
                statusBox.insertBefore(messageElement, statusBox.firstChild);

                // Limit the number of messages to keep the box manageable
                while (statusBox.children.length > 10) {
                    statusBox.removeChild(statusBox.lastChild);
                }

                return message;
            }
        };

        // Handler functions for buttons
        function fromFile() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*,video/*,text/*,.json,.csv,.txt';
            input.onchange = (event) => {
                const file = event.target.files[0];
                if (file) {
                    handleFileUpload(file);
                }
            };
            input.click();
        }

        // Smart file handling based on type
        function handleFileUpload(file) {
            const fileType = file.type.split('/')[0];

            if (fileType === 'image') {
                const reader = new FileReader();
                reader.onload = (e) => {
                    showMediaContent(e.target.result, 'image');
                    status.update(`Image loaded: ${file.name}`, 'success');
                };
                reader.readAsDataURL(file);
            }
            else if (fileType === 'video') {
                const reader = new FileReader();
                reader.onload = (e) => {
                    showMediaContent(e.target.result, 'video');
                    status.update(`Video loaded: ${file.name}`, 'success');
                };
                reader.readAsDataURL(file);
            }
            else {
                const reader = new FileReader();
                reader.onload = (e) => {
                    showTextContent(e.target.result);
                    status.update(`File loaded: ${file.name}`, 'success');
                };
                reader.readAsText(file);
            }
        }

        // Show media in the container
        function showMediaContent(src, type) {
            const mediaContainer = document.querySelector('.media-container');
            const textContent = document.getElementById('textContent');
            let mediaElement;

            mediaContainer.innerHTML = '';

            if (type === 'image') {
                mediaElement = document.createElement('img');
                mediaElement.src = src;
                mediaElement.className = 'media-content';
                mediaElement.alt = 'Loaded image';
            }
            else if (type === 'video') {
                mediaElement = document.createElement('video');
                mediaElement.src = src;
                mediaElement.className = 'media-content';
                mediaElement.controls = true;
                mediaElement.autoplay = false;
            }

            mediaContainer.appendChild(mediaElement);

            optimizeMediaDisplay();
        }

        // Show text in the container
        function showTextContent(text) {
            const mediaContainer = document.querySelector('.media-container');

            const textDiv = document.createElement('div');
            textDiv.className = 'text-content';
            textDiv.textContent = text;

            mediaContainer.innerHTML = '';
            mediaContainer.appendChild(textDiv);
        }

        // Optimize media display based on container size and aspect ratio
        function optimizeMediaDisplay() {
            const mediaElements = document.querySelectorAll('.media-content');
            if (!mediaElements.length) return;

            const container = document.querySelector('.content-container');
            const containerWidth = container.clientWidth;
            const containerHeight = container.clientHeight;

            mediaElements.forEach(media => {
                if (media.tagName === 'IMG') {
                    if (media.complete) {
                        adjustMediaSize(media, containerWidth, containerHeight);
                    } else {
                        media.onload = () => adjustMediaSize(media, containerWidth, containerHeight);
                    }
                }
                else if (media.tagName === 'VIDEO') {
                    adjustMediaSize(media, containerWidth, containerHeight);
                }
            });
        }

        // Adjust media size to maintain aspect ratio
        function adjustMediaSize(media, containerWidth, containerHeight) {
            const mediaWidth = media.naturalWidth || media.videoWidth || media.clientWidth;
            const mediaHeight = media.naturalHeight || media.videoHeight || media.clientHeight;

            if (!mediaWidth || !mediaHeight) return;

            const mediaRatio = mediaWidth / mediaHeight;
            const containerRatio = containerWidth / containerHeight;

            if (mediaRatio > containerRatio) {
                media.style.width = '100%';
                media.style.height = 'auto';
            } else {
                media.style.height = '100%';
                media.style.width = 'auto';
            }
        }

        function fromClipboard() {
            navigator.clipboard.readText()
                .then(text => {
                    if (text) {
                        showTextContent(text);
                        status.update('Content pasted from clipboard', 'success');
                    } else {
                        status.update('Clipboard is empty', 'info');
                    }
                })
                .catch(err => {
                    status.update('Failed to read from clipboard', 'error');
                    console.error('Clipboard read failed: ', err);
                });
        }

        function appendClipboard() {
            navigator.clipboard.readText()
                .then(text => {
                    if (text) {
                        const textContent = document.querySelector('.text-content');
                        if (textContent) {
                            textContent.textContent += '\n' + text;
                            status.update('Content appended from clipboard', 'success');
                        } else {
                            showTextContent(text);
                            status.update('Content pasted from clipboard', 'success');
                        }
                    } else {
                        status.update('Clipboard is empty', 'info');
                    }
                })
                .catch(err => {
                    status.update('Failed to read from clipboard', 'error');
                    console.error('Clipboard read failed: ', err);
                });
        }

        function toClipboard() {
            let contentToClipboard = '';

            const textContent = document.querySelector('.text-content');
            if (textContent && textContent.textContent) {
                contentToClipboard = textContent.textContent;
            } else {
                const mediaElement = document.querySelector('.media-content');
                if (mediaElement) {
                    contentToClipboard = mediaElement.src;
                }
            }

            if (contentToClipboard) {
                navigator.clipboard.writeText(contentToClipboard)
                    .then(() => {
                        status.update('Content copied to clipboard', 'success');
                    })
                    .catch(err => {
                        status.update('Failed to copy to clipboard', 'error');
                        console.error('Clipboard write failed: ', err);
                    });
            } else {
                status.update('No content to copy', 'warning');
            }
        }

        function fromTemplate() {
            status.update('Loading from template...', 'info');

            const templateContent = "This is template content.\n\nYou can modify this to load actual templates.";
            showTextContent(templateContent);
            status.update('Template loaded', 'success');
        }

        function saveData() {
            status.update('Saving data...', 'info');

            setTimeout(() => {
                status.update('Data saved successfully', 'success');
            }, 500);
        }

        function saveAs() {
            let dataToSave = '';
            let filename = 'netbound-data.txt';
            let mimetype = 'text/plain';

            const textContent = document.querySelector('.text-content');
            if (textContent && textContent.textContent) {
                dataToSave = textContent.textContent;
            } else {
                const mediaElement = document.querySelector('.media-content');
                if (mediaElement) {
                    dataToSave = mediaElement.src;
                    if (mediaElement.tagName === 'IMG') {
                        mimetype = 'image/png';
                        filename = 'netbound-image.png';
                    } else if (mediaElement.tagName === 'VIDEO') {
                        mimetype = 'video/mp4';
                        filename = 'netbound-video.mp4';
                    }
                }
            }

            if (!dataToSave) {
                status.update('No data to save', 'warning');
                return;
            }

            const blob = new Blob([dataToSave], {type: mimetype});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            status.update(`File saved as ${filename}`, 'success');
        }

        function processData() {
            status.update('Processing data...', 'info');

            setTimeout(() => {
                status.update('Data processed successfully', 'success');
            }, 1000);
        }

        document.getElementById('rerunBtn').addEventListener('click', function() {
            if (confirm('This will reload the page and reset all data. Continue?')) {
                status.update('Reloading page...', 'info');
                setTimeout(() => {
                    location.reload();
                }, 500);
            } else {
                status.update('Reload cancelled', 'info');
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            const statusBox = document.getElementById('statusBox');
            if (statusBox) {
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
                        const file = e.dataTransfer.files[0];
                        status.update(`File dropped: ${file.name}`, 'info');
                        handleFileUpload(file);
                    }
                });
            }

            window.addEventListener('resize', optimizeMediaDisplay);

            status.update('Tool initialized and ready', 'success');
        });
    </script>
</body>
</html>
