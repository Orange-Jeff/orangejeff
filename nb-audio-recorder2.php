<!DOCTYPE html>
<html lang="en">

<head>
    <!--
// filename: dual-audio-record.php
// Version 6.16 - May 2 2025 // Increment version
// Created by OrangeJeff with the assistance of Claude
// Description: Records desktop audio separate from mic
// Not functional on mobile
-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetBound Tools: Dual Audio Recorder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Removed shared-styles.css reference -->
    <style>
        /* Internal CSS - Version 2.6 */ /* Increment CSS version */
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
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #e9ecef;
            text-align: left;
        }

        /* Menu container - ensure consistent width */
        .menu-container {
            position: relative;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }

        /* Content container - no horizontal padding inside main container */
        .content-area {
            width: 100%;
            padding: 0;
            box-sizing: border-box;
            overflow: hidden; /* Remove any excess height in containers */
        }

        /* Title container */
        .title-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .editor-title {
            margin: 0;
            padding: 0;
            line-height: 1.2;
            color: var(--primary-color);
            font-weight: bold;
            font-size: 18px;
        }

        /* Hamburger menu */
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

        /* Status box */
        .status-box {
            width: 100%;
            height: 90px;
            min-height: 90px;
            max-height: 90px;
            overflow-y: auto;
            border: 1px solid var(--primary-color);
            background: #fff;
            padding: 10px 5px;
            margin: 15px 0; /* Adjusted margin */
            border-radius: 4px;
            display: flex;
            flex-direction: column-reverse;
            box-sizing: border-box;
        }

        /* Status messages */
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
            border-left: 3px solid #2196f3;
        }

        .message.success {
            border-left: 3px solid #4caf50;
        }

        .message.error {
            border-left: 3px solid #f44336;
        }

        .message.warning {
            border-left: 3px solid #f39c12;
        }

        .message.latest {
            color: white;
            font-weight: bold;
        }

        .message.latest.info {
            background-color: #4a9eff;
        }

        .message.latest.success {
            background-color: #28a745;
        }

        .message.latest.error {
            background-color: #dc3545;
        }

        /* Button controls */
        .button-controls {
            margin: 15px 0;
        }

        .button-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
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

        .command-button.right-aligned {
            margin-left: auto; /* Push to the far right */
        }

        .command-button:hover {
            background-color: #003d82;
        }

        .command-button:disabled {
            background-color: var(--primary-color); /* Changed to blue */
            opacity: 0.65; /* Add opacity to indicate disabled state */
            cursor: not-allowed;
        }

        .command-button.recording {
            background-color: #dc3545;
            animation: pulse-recording 1.5s infinite;
        }

        @keyframes pulse-recording {
            0% { background-color: #dc3545; }
            50% { background-color: #a71d2a; }
            100% { background-color: #dc3545; }
        }

        /* Editor view */
        .editor-view {
            margin-top: 15px;
        }

        /* Waveform styles - improved containment */
        #waveform-section {
            display: block;
            width: 100%;
            margin-top: 15px;
            padding: 0;
            box-sizing: border-box;
        }

        .waveform-container {
            width: 100%;
            margin: 15px 0;
            padding: 10px;
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            position: relative; /* Add position relative to contain the playhead */
            border: 1px solid var(--primary-color); /* Add blue border */
            overflow: hidden; /* Prevent content overflow */
        }

        /* Added for dynamically created waveforms */
        .waveform {
            display: block; /* Ensure canvas behaves like a block element */
            width: 100%; /* Take full width of parent */
            height: 37px; /* Fixed height */
            background-color: #f5f5f5;
            margin-top: 10px; /* Add margin like placeholder */
        }

        /* Specific heights for Video and Merged containers */
        #video-save-container,
        #merged-audio-container {
            height: 60px; /* Shorter height for header-only containers */
            display: flex; /* Use flexbox for vertical centering */
            align-items: center; /* Vertically center the header */
        }

        #video-save-container .waveform-header,
        #merged-audio-container .waveform-header {
             margin-bottom: 0; /* Remove bottom margin as there's no content below */
             width: 100%; /* Ensure header takes full width */
        }

        .waveform-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .waveform-title {
            font-size: 16px;
            margin: 0;
            flex-grow: 0; /* Don't allow title to grow */
            flex-shrink: 0; /* Don't allow title to shrink */
            width: 200px; /* Fixed width for title */
            text-align: left; /* Ensure left alignment */
        }

        .waveform-controls {
            display: flex;
            gap: 8px;
            flex-grow: 1; /* Allow controls to take up remaining space */
            justify-content: center; /* Center buttons within the controls area */
        }

        .waveform-save {
            margin-left: 10px;
            flex-grow: 0; /* Don't allow save button to grow */
            flex-shrink: 0; /* Don't allow save button to shrink */
            width: 80px; /* Fixed width for save button */
            text-align: center; /* Center icon/text */
        }

        .waveform-placeholder {
            width: 100%;
            height: 37px; /* Match waveform canvas height */
            background-color: #f8f9fa;
            border: 1px dashed #ccc;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-style: italic;
            box-sizing: border-box;
            margin-top: 10px; /* Add top margin to separate from header */
        }

        /* Video container */
        #video-container {
            position: relative;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            border: 1px solid var(--primary-color); /* Add blue border */
            border-radius: 4px;
            overflow: hidden;
            background-color: #000;
            transition: all 0.3s ease;
            min-height: 250px; /* Ensure a minimum height */
            height: 30vh; /* Use viewport height percentage */
        }

        #video-container.video-minimized {
            height: 25vh; /* Slightly smaller when "minimized" */
            min-height: 200px;
        }

        #video-container.video-zoomed {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            max-width: none;
            max-height: none;
            min-height: 0;
            z-index: 1000;
            border-radius: 0;
            border: none;
        }

        #main-video {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Changed from cover to contain */
            background-color: #000;
        }

        /* Close button for zoomed video */
        .close-zoom-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 1.5rem;
            color: white;
            background-color: rgba(0, 0, 0, 0.5);
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            cursor: pointer;
            z-index: 1001;
            display: none; /* Hidden by default */
        }

        #video-container.video-zoomed .close-zoom-btn {
            display: block; /* Visible when zoomed */
        }

        /* Recording indicator */
        .recording-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            display: none;
            font-weight: bold;
            animation: pulse 1s infinite;
        }

        .recording-indicator.active {
            display: block;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        /* Camera selection */
        .camera-selection {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px;
            box-sizing: border-box;
            display: none;
        }

        .camera-selection.hidden {
            display: none;
        }

        .camera-option {
            padding: 8px;
            cursor: pointer;
            border-radius: 4px;
        }

        .camera-option:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .camera-option.active {
            background-color: var(--primary-color);
        }

        .camera-controls {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .control-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Media queries for responsiveness */
        @media (max-width: 768px) {
            .menu-container {
                padding: 10px;
            }

            .button-row {
                flex-direction: column;
                align-items: stretch;
            }

            .command-button {
                width: 100%;
            }
        }

        /* Support for iframe mode - fixed to maintain 900px width */
        body.in-iframe .menu-container {
            padding: 10px 20px; /* Keep vertical padding, maintain horizontal */
            margin: 0 auto; /* Keep it centered */
            max-width: 900px; /* Maintain 900px width in iframe */
        }

        .iframe-mode .hamburger-menu {
            font-size: 1rem;
        }

        /* When has content class is added */
        .editor-view.has-content {
            overflow-y: auto;
            max-height: calc(100vh - 180px);
            overflow-x: hidden; /* Prevent horizontal scrollbar */
        }

        /* Mobile blocker - now hidden by default, handled by JS */
        #mobile-blocker {
            display: none;
        }
    </style>

    <script>
        // More robust iframe detection - run immediately
        (function detectIframe() {
            try {
                // Check if we're in an iframe
                const isInIframe = window.self !== window.top;

                // Apply class right away
                if (isInIframe) {
                    // Force immediate application
                    document.documentElement.className += ' in-iframe';

                    // Also set to be applied to body as soon as it exists
                    document.addEventListener('DOMContentLoaded', function() {
                        document.body.className += ' in-iframe';
                        console.log("Applied in-iframe class to body");
                    });

                    // Failsafe - check again after a slight delay
                    setTimeout(function() {
                        if (!document.body.classList.contains('in-iframe')) {
                            document.body.className += ' in-iframe';
                            console.log("Applied in-iframe class to body (delayed)");
                        }
                    }, 50);
                }
            } catch (e) {
                // If there's a security error, we're definitely in an iframe
                console.log("Security exception - definitely in iframe");
                document.documentElement.className += ' in-iframe';

                document.addEventListener('DOMContentLoaded', function() {
                    document.body.className += ' in-iframe';
                });
            }
        })();

        // Existing iframe check function - keep as backup
        function checkIframe() {
            try {
                if (window.self !== window.top) {
                    // Page is in an iframe
                    document.documentElement.classList.add('in-iframe');
                    document.body.classList.add('in-iframe');
                    console.log("Running in iframe mode");
                } else {
                    console.log("Running in standalone mode");
                }
            } catch (e) {
                // If security error, we're in an iframe
                document.documentElement.classList.add('in-iframe');
                document.body.classList.add('in-iframe');
                console.log("Running in iframe mode (security exception)");
            }
        }

        // Also run on DOMContentLoaded to ensure body exists
        document.addEventListener('DOMContentLoaded', checkIframe);

        // Final safety check - check again after fully loaded
        window.addEventListener('load', function() {
            // One more check after everything has loaded
            checkIframe();

            // Force layout recalculation if in iframe
            if (window.self !== window.top) {
                setTimeout(function() {
                    const container = document.querySelector('.tool-container');
                    if (container) {
                        container.style.display = 'none';
                        // Force reflow
                        void container.offsetHeight;
                        container.style.display = '';
                    }
                }, 100);
            }

            // Set video container to minimized size initially
            document.getElementById('video-container').classList.add('video-minimized');
        });
    </script>
</head>

<body>
    <div class="menu-container">
        <div class="title-container">
            <h1 class="editor-title">NetBound Tools: Dual Audio Recorder</h1>
            <a href="main.php?app=nb-audio-recorder.php" class="hamburger-menu" title="Go to Main Menu">
                <i class="fas fa-bars"></i>
            </a>
        </div>

        <div id="statusBox" class="status-box"></div>

        <div class="button-controls">
            <div class="button-row">
                <button class="command-button" id="btnCameraSource">
                    <i class="fas fa-video"></i> Camera On
                </button>
                <button class="command-button" id="flip-video">
                    <i class="fas fa-sync"></i> Flip Camera
                </button>
                <button class="command-button" id="btnZoom">
                    <i class="fas fa-search-plus"></i> Zoom
                </button>
                <button class="command-button right-aligned" id="btnReset">
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>
        </div>

        <div class="editor-view content-area">
            <div class="preview-area">
                <!-- Content preview area -->
                <div id="video-container">
                    <video id="main-video" autoplay playsinline muted></video>
                    <button id="closeZoomBtn" class="close-zoom-btn" title="Close Zoom">&times;</button>
                    <div class="recording-indicator">REC</div>
                    <div class="camera-selection">
                        <div id="camera-list"></div>
                        <div class="camera-controls">
                            <div class="control-group">
                                <label for="camera-quality">Quality:</label>
                                <select id="camera-quality">
                                    <option value="qvga">Low (QVGA)</option>
                                    <option value="vga">Medium (VGA)</option>
                                    <option value="hd" selected>High (HD)</option>
                                    <option value="fhd">Full HD</option>
                                </select>
                            </div>
                            <div class="control-group">
                                <label for="camera-zoom">Zoom:</label>
                                <input type="range" id="camera-zoom" min="100" max="400" value="100" step="10">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recording controls -->
                <div class="button-controls">
                    <div class="button-row">
                        <button id="startRecording" class="command-button">
                            <i class="fas fa-record-vinyl"></i> Start Recording
                        </button>
                        <button id="stopRecording" class="command-button" disabled>
                            <i class="fas fa-stop"></i> Stop Recording
                        </button>
                        <span id="timer">00:00</span>
                    </div>
                </div>

                <!-- Audio players section - Reordered -->
                <div id="waveform-section">
                    <!-- Video Save Container (First) -->
                    <div id="video-save-container" class="waveform-container">
                        <div class="waveform-header">
                            <h3 class="waveform-title">Video</h3>
                            <div class="waveform-controls">
                                <!-- No play/stop for video -->
                            </div>
                            <button id="saveVideoButton" class="command-button waveform-save" disabled title="Save Video with Mic Audio">
                                <i class="fas fa-film"></i> Save
                            </button>
                        </div>
                        <!-- No waveform placeholder for video -->
                    </div>

                    <!-- Merged Audio Container (Second) -->
                    <div id="merged-audio-container" class="waveform-container">
                        <div class="waveform-header">
                            <h3 class="waveform-title">Merged Stereo</h3>
                            <div class="waveform-controls">
                                <button id="playMerged" class="command-button icon-button" disabled><i class="fas fa-play"></i></button>
                                <button id="stopMerged" class="command-button icon-button" disabled><i class="fas fa-stop"></i></button>
                            </div>
                            <button id="saveMergedButton" class="command-button waveform-save" disabled><i class="fas fa-save"></i> Save
                            </button>
                        </div>
                        <audio id="mergedAudioPlayer" style="display: none;"></audio>
                    </div>

                    <!-- Placeholder Waveform Containers (Third and Fourth) -->
                    <div id="placeholder-mic" class="waveform-container">
                        <div class="waveform-header">
                            <h3 class="waveform-title">Microphone Recording</h3>
                            <div class="waveform-controls">
                                <button class="command-button icon-button" disabled><i class="fas fa-play"></i></button>
                                <button class="command-button icon-button" disabled><i class="fas fa-stop"></i></button>
                            </div>
                            <button class="command-button waveform-save" disabled><i class="fas fa-save"></i> Save</button>
                        </div>
                        <div class="waveform-placeholder">Waveform will appear here</div>
                    </div>
                    <div id="placeholder-tab" class="waveform-container">
                        <div class="waveform-header">
                            <h3 class="waveform-title">Desktop Audio Recording</h3>
                            <div class="waveform-controls">
                                <button class="command-button icon-button" disabled><i class="fas fa-play"></i></button>
                                <button class="command-button icon-button" disabled><i class="fas fa-stop"></i></button>
                            </div>
                            <button class="command-button waveform-save" disabled><i class="fas fa-save"></i> Save</button>
                        </div>
                        <div class="waveform-placeholder">Waveform will appear here</div>
                    </div>

                    <!-- Actual Audio Players will be inserted here (Last) -->
                    <div id="audioPlayers"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden file inputs -->
    <input type="file" id="imageInput" accept="video/*" style="display: none">

    <!-- Include RecordRTC -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/RecordRTC/5.6.2/RecordRTC.min.js"></script>
    <script>
        let micRecorder, tabRecorder, videoRecorder;
        let micStream, tabStream, videoStream;
        let isRecording = false;
        let micBlob, tabBlob, videoBlob, mergedBlob;
        let timerInterval;
        let startTime;
        let cameras = [];
        let currentCameraIndex = 0;
        let isFlipped = false;
        let hasUnsavedRecordings = false;
        let audioPlayers = [];
        let isMergedPlaying = false;
        let isGeneratingMerged = false;

        // Status message handling
        const statusManager = {
            update(id, message, type = 'info') {
                const container = document.getElementById('statusBox');

                // First, check if a message with this ID already exists
                const existingMsg = document.getElementById(id);
                if (existingMsg) {
                    // Update existing message
                    existingMsg.textContent = message;
                    existingMsg.className = `message ${type}`;

                    // Remove latest class from all messages
                    document.querySelectorAll('.message.latest').forEach(msg => {
                        msg.classList.remove('latest');
                    });

                    // Add latest class to this message
                    existingMsg.classList.add('latest');
                    return id;
                }

                // Create new message
                const messageDiv = document.createElement('div');
                messageDiv.id = id;
                messageDiv.className = `message ${type} latest`;
                messageDiv.textContent = message;

                // Remove latest class from all messages
                document.querySelectorAll('.message.latest').forEach(msg => {
                    msg.classList.remove('latest');
                });

                container.insertBefore(messageDiv, container.firstChild); // Insert at top
                return id;
            },

            // Remove a tracked status message
            remove(id) {
                const message = document.getElementById(id);
                if (message) {
                    message.remove();
                }
            }
        };

        // Keep backward compatibility with old status function
        function updateStatus(message, type = 'info') {
            // Generate a random ID for one-time messages
            const id = 'msg_' + Math.random().toString(36).substr(2, 9);
            return statusManager.update(id, message, type);
        }

        function detectMobile() {
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

            if (isMobile) {
                // Show warning in status box instead of separate element
                updateStatus("This tool does not work on mobile devices. Please use a desktop computer.", 'error');
                document.getElementById('startRecording').disabled = true;
            }
        }

        window.onload = function() {
            detectMobile();
            initializeRecorder();
        };

        document.addEventListener('DOMContentLoaded', () => {
            // Connect camera source button to toggle camera selection panel
            document.getElementById('btnCameraSource').addEventListener('click', () => {
                const cameraPanel = document.querySelector('.camera-selection');
                const cameraButton = document.getElementById('btnCameraSource');

                if (cameraPanel.style.display === 'block') {
                    cameraPanel.style.display = 'none';
                    cameraButton.innerHTML = '<i class="fas fa-video"></i> Camera On';
                } else {
                    cameraPanel.style.display = 'block';
                    cameraButton.innerHTML = '<i class="fas fa-video-slash"></i> Camera Off';

                    // Make sure we've loaded the camera list
                    if (cameras.length === 0) {
                        getCameras();
                    }
                }
            });

            // Add iframe detection function
            function isInIframe() {
                try {
                    return window.self !== window.top;
                } catch (e) {
                    return true;
                }
            }

            // Apply iframe-specific settings
            if (isInIframe()) {
                document.body.classList.add('iframe-mode');

                // Configure hamburger menu to break out of iframe when clicked
                const hamburgerMenu = document.querySelector('.hamburger-menu');
                if (hamburgerMenu) {
                    const currentPath = window.location.pathname.split('/').pop();
                    hamburgerMenu.href = currentPath;
                    hamburgerMenu.setAttribute('target', '_top');
                    hamburgerMenu.setAttribute('title', 'Exit iframe mode');
                    hamburgerMenu.innerHTML = '<i class="fas fa-external-link-alt"></i>';
                }
            }

            // Initialize with welcome message
            updateStatus('Dual Audio Recorder ready. Click Camera Source to begin.', 'info');
        });

        function initializeRecorder() {
            const startRecordingButton = document.getElementById('startRecording');
            const stopRecordingButton = document.getElementById('stopRecording');
            const timerElement = document.getElementById('timer');
            const audioPlayersElement = document.getElementById('audioPlayers');
            const saveVideoButton = document.getElementById('saveVideoButton');
            const saveMergedButton = document.getElementById('saveMergedButton');
            const playMergedButton = document.getElementById('playMerged');
            const stopMergedButton = document.getElementById('stopMerged');
            const mergedAudioPlayer = document.getElementById('mergedAudioPlayer');
            const videoContainer = document.getElementById('video-container'); // Get video container
            const zoomButton = document.getElementById('btnZoom'); // Get zoom button
            const closeZoomButton = document.getElementById('closeZoomBtn'); // Get close zoom button

            // Make sure we attach our event handlers
            startRecordingButton.addEventListener('click', startRecording);
            stopRecordingButton.addEventListener('click', stopRecording);

            // Add event listener for the save video button
            saveVideoButton.addEventListener('click', () => {
                if (videoBlob && micBlob) {
                    downloadVideoWithAudio();
                } else if (videoBlob) {
                    downloadVideo();
                } else {
                    updateStatus("No video recording available to save.", 'error');
                }
            });

            // Add event listener for the save merged button
            saveMergedButton.addEventListener('click', async () => {
                if (isGeneratingMerged) return;
                if (mergedBlob) {
                    downloadBlob(mergedBlob, `${baseName}-merged-audio.wav`);
                } else if (micBlob && tabBlob) {
                    updateStatus("Generating merged audio for download...", 'info');
                    isGeneratingMerged = true;
                    try {
                        mergedBlob = await generateMergedAudio();
                        if (mergedBlob) {
                            mergedAudioPlayer.src = URL.createObjectURL(mergedBlob);
                            downloadBlob(mergedBlob, `${baseName}-merged-audio.wav`);
                            updateStatus("Merged audio downloaded.", 'success');
                        }
                    } catch (error) {
                        updateStatus("Error generating merged audio for download: " + error.message, 'error');
                    } finally {
                        isGeneratingMerged = false;
                    }
                } else {
                    updateStatus("Mic and Tab audio recordings needed to generate merged file.", 'warning');
                }
            });

            // Add event listeners for merged playback
            playMergedButton.addEventListener('click', async () => {
                if (isGeneratingMerged) return;

                if (!mergedBlob && micBlob && tabBlob) {
                    updateStatus("Generating merged audio for playback...", 'info');
                    isGeneratingMerged = true;
                    try {
                        mergedBlob = await generateMergedAudio();
                        if (mergedBlob) {
                            mergedAudioPlayer.src = URL.createObjectURL(mergedBlob);
                            updateStatus("Merged audio generated. Starting playback.", 'success');
                            playMergedAudio();
                        }
                    } catch (error) {
                        updateStatus("Error generating merged audio: " + error.message, 'error');
                    } finally {
                        isGeneratingMerged = false;
                    }
                } else if (mergedBlob) {
                    if (!isMergedPlaying) {
                        playMergedAudio();
                    } else {
                        pauseMergedAudio();
                    }
                } else {
                    updateStatus("Mic and Tab audio recordings needed to generate merged file.", 'warning');
                }
            });

            stopMergedButton.addEventListener('click', () => {
                if (mergedBlob) {
                    stopMergedAudio();
                }
            });

            // Add event listener for zoom button
            zoomButton.addEventListener('click', () => {
                videoContainer.classList.toggle('video-zoomed');
                if (videoContainer.classList.contains('video-zoomed')) {
                    zoomButton.innerHTML = '<i class="fas fa-search-minus"></i> Unzoom';
                } else {
                    zoomButton.innerHTML = '<i class="fas fa-search-plus"></i> Zoom';
                }
            });

            // Add event listener for close zoom button
            closeZoomButton.addEventListener('click', () => {
                videoContainer.classList.remove('video-zoomed');
                zoomButton.innerHTML = '<i class="fas fa-search-plus"></i> Zoom';
            });

            // Add restart button functionality
            document.getElementById('btnReset').addEventListener('click', () => {
                if (hasUnsavedRecordings) {
                    if (!confirm('You have unsaved recordings. Are you sure you want to restart?')) {
                        return;
                    }
                }
                mergedBlob = null;
                isMergedPlaying = false;
                mergedAudioPlayer.src = '';
                playMergedButton.disabled = true;
                stopMergedButton.disabled = true;
                saveMergedButton.disabled = true;

                location.reload();
            });

            async function startRecording() {
                if (hasUnsavedRecordings) {
                    updateStatus("Please save or reset before starting a new recording", 'error');
                    return;
                }

                if (isRecording) {
                    audioPlayersElement.innerHTML = '';
                }
                try {
                    updateStatus("Requesting permissions...", 'info');
                    micStream = await navigator.mediaDevices.getUserMedia({
                        audio: true
                    });
                    tabStream = await navigator.mediaDevices.getDisplayMedia({
                        audio: true,
                        video: true
                    });
                    if (!videoStream) {
                        await startCamera();
                    }
                    updateStatus("Permissions granted. Starting recording...");

                    micRecorder = new RecordRTC(micStream, {
                        type: 'audio',
                        mimeType: 'audio/wav',
                        recorderType: RecordRTC.StereoAudioRecorder
                    });
                    tabRecorder = new RecordRTC(tabStream, {
                        type: 'audio',
                        mimeType: 'audio/wav',
                        recorderType: RecordRTC.StereoAudioRecorder
                    });
                    videoRecorder = new RecordRTC(videoStream, {
                        type: 'video',
                        mimeType: 'video/webm',
                        recorderType: RecordRTC.MediaStreamRecorder
                    });

                    micRecorder.startRecording();
                    tabRecorder.startRecording();
                    videoRecorder.startRecording();

                    isRecording = true;
                    startRecordingButton.disabled = true;
                    startRecordingButton.classList.add('recording');
                    startRecordingButton.innerHTML = '<i class="fas fa-record-vinyl"></i> RECORDING';
                    stopRecordingButton.disabled = false;
                    document.querySelector('.recording-indicator').classList.add('active');
                    document.body.classList.add('recording');
                    startTimer();
                    updateStatus("Recording in progress...", 'info');
                } catch (error) {
                    console.error('Error starting recording:', error);
                    updateStatus("Error: " + error.message, 'error');
                }
            }

            function stopRecording() {
                updateStatus("Stopping recording...", 'info');
                console.log("stopRecording called.");

                document.getElementById('video-container').classList.add('video-minimized');
                document.getElementById('placeholder-mic').style.display = 'none';
                document.getElementById('placeholder-tab').style.display = 'none';

                startRecordingButton.classList.remove('recording');
                startRecordingButton.innerHTML = '<i class="fas fa-record-vinyl"></i> Start Recording';

                // Use Promise.all to wait for all recorders to finish
                Promise.all([
                    new Promise(resolve => micRecorder.stopRecording(resolve)),
                    new Promise(resolve => tabRecorder.stopRecording(resolve)),
                    new Promise(resolve => videoRecorder.stopRecording(resolve))
                ]).then(() => {
                    micBlob = micRecorder.getBlob();
                    tabBlob = tabRecorder.getBlob();
                    videoBlob = videoRecorder.getBlob();

                    console.log("All recorders stopped.");
                    console.log("Mic Blob:", micBlob);
                    console.log("Tab Blob:", tabBlob);
                    console.log("Video Blob:", videoBlob);

                    if (micBlob && micBlob.size > 0) {
                        createWaveformPlayer(micBlob, 'Microphone Recording');
                    } else {
                        console.error("Mic blob is invalid or empty.");
                        updateStatus("Error processing microphone recording.", 'error');
                    }

                    if (tabBlob && tabBlob.size > 0) {
                        createWaveformPlayer(tabBlob, 'Desktop Audio Recording'); // Corrected title
                    } else {
                        console.error("Tab blob is invalid or empty.");
                        updateStatus("Error processing desktop audio recording.", 'error');
                    }

                    if (videoBlob && videoBlob.size > 0) {
                        document.getElementById('saveVideoButton').disabled = false;
                    } else {
                        console.warn("Video blob might be invalid or empty.");
                    }

                    // Now check if merged can be enabled
                    checkEnableMerged();

                    // Scroll after a short delay to ensure elements are rendered
                    setTimeout(() => {
                        const audioPlayersElement = document.getElementById('audioPlayers');
                        if (audioPlayersElement.childNodes.length > 0) {
                            audioPlayersElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }, 300);

                    isRecording = false;
                    hasUnsavedRecordings = true;
                    startRecordingButton.disabled = true; // Keep disabled until reset
                    stopRecordingButton.disabled = true;
                    document.querySelector('.recording-indicator').classList.remove('active');
                    document.body.classList.remove('recording');
                    stopTimer();

                    // Stop media tracks
                    micStream?.getTracks().forEach(track => track.stop());
                    tabStream?.getTracks().forEach(track => track.stop());
                    // Keep videoStream active for preview unless explicitly stopped/reset

                    updateStatus("Recording completed successfully. Save files or reset to record again.", 'success');
                    document.querySelector('.editor-view').classList.add('has-content');

                }).catch(error => {
                    console.error("Error stopping recorders:", error);
                    updateStatus("Error stopping recording: " + error.message, 'error');
                    // Still try to clean up UI state
                    isRecording = false;
                    startRecordingButton.disabled = false; // Allow retry? Or keep disabled?
                    stopRecordingButton.disabled = true;
                    document.querySelector('.recording-indicator').classList.remove('active');
                    document.body.classList.remove('recording');
                    stopTimer();
                });
            }

            function checkEnableMerged() {
                console.log("checkEnableMerged called. MicBlob:", !!micBlob, "TabBlob:", !!tabBlob);
                if (micBlob && micBlob.size > 0 && tabBlob && tabBlob.size > 0) {
                    console.log("Enabling merged buttons.");
                    document.getElementById('playMerged').disabled = false;
                    document.getElementById('saveMergedButton').disabled = false;
                } else {
                    console.log("Merged buttons remain disabled.");
                }
            }

            function playMergedAudio() {
                mergedAudioPlayer.play();
                isMergedPlaying = true;
                playMergedButton.innerHTML = '<i class="fas fa-pause"></i>';
                stopMergedButton.disabled = false;
            }

            function pauseMergedAudio() {
                mergedAudioPlayer.pause();
                isMergedPlaying = false;
                playMergedButton.innerHTML = '<i class="fas fa-play"></i>';
            }

            function stopMergedAudio() {
                mergedAudioPlayer.pause();
                mergedAudioPlayer.currentTime = 0;
                isMergedPlaying = false;
                playMergedButton.innerHTML = '<i class="fas fa-play"></i>';
                stopMergedButton.disabled = true;
            }

            async function drawWaveform(blob, canvas) {
                if (!canvas) {
                    console.error("Canvas element not provided for drawing waveform.");
                    return;
                }
                if (!blob || blob.size === 0) {
                     console.error("Invalid blob provided for drawing waveform.");
                     return;
                }
                console.log(`Drawing waveform on canvas:`, canvas, " with blob:", blob);
                try {
                    const audioContext = new AudioContext();
                    let audioBuffer;
                    try {
                        audioBuffer = await audioContext.decodeAudioData(await blob.arrayBuffer());
                    } catch (decodeError) {
                        console.error("Error decoding audio data:", decodeError, "Blob type:", blob.type, "Blob size:", blob.size);
                        updateStatus(`Error decoding audio: ${decodeError.message}`, 'error');
                        const ctx = canvas.getContext('2d');
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                        ctx.fillStyle = 'red';
                        ctx.font = '12px Arial';
                        ctx.textAlign = 'center';
                        ctx.fillText('Error decoding audio', canvas.width / 2, canvas.height / 2);
                        return;
                    }

                    const channelData = audioBuffer.getChannelData(0);
                    const waveformData = [];
                    const canvasWidth = canvas.width;
                    const canvasHeight = canvas.height;
                    const center = canvasHeight / 2;
                    const blockSize = Math.max(1, Math.floor(audioBuffer.length / canvasWidth));

                    console.log(`Canvas dimensions: ${canvasWidth}x${canvasHeight}, Block size: ${blockSize}`);

                    for (let i = 0; i < canvasWidth; i++) {
                        let min = 1.0;
                        let max = -1.0;
                        const start = i * blockSize;
                        const end = Math.min(start + blockSize, audioBuffer.length);

                        if (start >= audioBuffer.length) break;

                        for (let j = start; j < end; j++) {
                            const datum = channelData[j];
                            if (datum < min) min = datum;
                            if (datum > max) max = datum;
                        }
                        if (min === 1.0) min = 0;
                        if (max === -1.0) max = 0;
                        waveformData.push([min, max]);
                    }

                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#0056b3';

                    ctx.clearRect(0, 0, canvasWidth, canvasHeight);
                    ctx.beginPath();

                    waveformData.forEach(([min, max], i) => {
                        const absMax = Math.max(Math.abs(min), Math.abs(max));
                        const lineHeight = absMax * canvasHeight;
                        const finalHeight = Math.max(1, lineHeight);
                        const y = center - (finalHeight / 2);

                        ctx.fillRect(i, y, 1, finalHeight);
                    });
                    console.log(`Finished drawing waveform for canvas:`, canvas);

                } catch (error) {
                    console.error('Error drawing waveform:', error);
                    updateStatus(`Error drawing waveform: ${error.message}`, 'error');
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.fillStyle = 'red';
                    ctx.font = '12px Arial';
                    ctx.textAlign = 'center';
                    ctx.fillText('Error drawing waveform', canvas.width / 2, canvas.height / 2);
                }
            }

            async function generateMergedAudio() {
                if (!micBlob || !tabBlob) {
                    throw new Error("Missing microphone or tab audio data.");
                }
                if (mergedBlob) {
                    return mergedBlob;
                }

                updateStatus("Merging audio tracks...", 'info');
                try {
                    const micBuffer = await micBlob.arrayBuffer();
                    const tabBuffer = await tabBlob.arrayBuffer();

                    const audioContext = new AudioContext();

                    const [micAudio, tabAudio] = await Promise.all([
                        audioContext.decodeAudioData(micBuffer.slice(0)),
                        audioContext.decodeAudioData(tabBuffer.slice(0))
                    ]);

                    const maxLength = Math.max(micAudio.length, tabAudio.length);
                    const offlineContext = new OfflineAudioContext(2, maxLength, audioContext.sampleRate);

                    const micSource = offlineContext.createBufferSource();
                    const tabSource = offlineContext.createBufferSource();
                    micSource.buffer = micAudio;
                    tabSource.buffer = tabAudio;

                    const micPanner = offlineContext.createStereoPanner();
                    micPanner.pan.value = -1;
                    const tabPanner = offlineContext.createStereoPanner();
                    tabPanner.pan.value = 1;

                    micSource.connect(micPanner).connect(offlineContext.destination);
                    tabSource.connect(tabPanner).connect(offlineContext.destination);

                    micSource.start(0);
                    tabSource.start(0);

                    const renderedBuffer = await offlineContext.startRendering();

                    const wavEncoder = new WavAudioEncoder(renderedBuffer.sampleRate, renderedBuffer.numberOfChannels);
                    for (let i = 0; i < renderedBuffer.numberOfChannels; i++) {
                        wavEncoder.addChannel(renderedBuffer.getChannelData(i));
                    }
                    const finalMergedBlob = wavEncoder.finish();

                    updateStatus("Audio tracks merged successfully.", 'success');
                    return finalMergedBlob;

                } catch (error) {
                    console.error('Error merging audio:', error);
                    updateStatus("Error merging audio files: " + error.message, 'error');
                    throw error;
                }
            }

            function createWaveformPlayer(blob, title) {
                console.log(`createWaveformPlayer called for: ${title}`);
                const container = document.createElement('div');
                container.classList.add('waveform-container');

                const header = document.createElement('div');
                header.classList.add('waveform-header');
                const titleElement = document.createElement('h3');
                titleElement.classList.add('waveform-title');
                titleElement.textContent = title;
                header.appendChild(titleElement);

                const controls = document.createElement('div');
                controls.classList.add('waveform-controls');
                const playButton = document.createElement('button');
                playButton.classList.add('command-button', 'icon-button');
                playButton.innerHTML = '<i class="fas fa-play"></i>';
                const stopButton = document.createElement('button');
                stopButton.classList.add('command-button', 'icon-button');
                stopButton.innerHTML = '<i class="fas fa-stop"></i>';
                controls.appendChild(playButton);
                controls.appendChild(stopButton);
                header.appendChild(controls);

                const saveButton = document.createElement('button');
                saveButton.classList.add('command-button', 'waveform-save');
                saveButton.innerHTML = '<i class="fas fa-save"></i> Save';
                header.appendChild(saveButton);

                container.appendChild(header);

                const canvas = document.createElement('canvas');
                canvas.classList.add('waveform');
                container.appendChild(canvas);

                setTimeout(() => {
                    try {
                        const containerWidth = container.clientWidth;
                        if (containerWidth > 20) {
                             canvas.width = containerWidth - 20;
                             canvas.height = 37;
                             console.log(`Canvas dimensions set for ${title}: ${canvas.width}x${canvas.height}`);
                             drawWaveform(blob, canvas);
                        } else {
                             console.warn(`Container width for ${title} is too small or zero: ${containerWidth}`);
                        }
                    } catch (error) {
                        console.error(`Error setting canvas size or drawing waveform for ${title}:`, error);
                        updateStatus(`Error preparing waveform for ${title}`, 'error');
                    }
                }, 0);

                const audioElement = document.createElement('audio');
                audioElement.style.display = 'none';
                audioElement.src = URL.createObjectURL(blob);
                container.appendChild(audioElement);

                playButton.addEventListener('click', () => {
                    audioElement.play();
                });

                stopButton.addEventListener('click', () => {
                    audioElement.pause();
                    audioElement.currentTime = 0;
                });

                saveButton.addEventListener('click', () => {
                    downloadBlob(blob, `${baseName}-${title.replace(/\s+/g, '-').toLowerCase()}.wav`);
                });

                const audioPlayersElement = document.getElementById('audioPlayers');
                if (audioPlayersElement) {
                    audioPlayersElement.appendChild(container);
                    console.log(`Appended waveform container for ${title} to #audioPlayers.`);
                } else {
                    console.error("#audioPlayers element not found in DOM!");
                }
            }
        }

        class WavAudioEncoder {
            constructor(sampleRate, numChannels) {
                this.sampleRate = sampleRate;
                this.numChannels = numChannels;
                this.chunks = [];
                this.dataViews = [];
            }

            addChannel(data) {
                this.chunks.push(data);
            }

            finish() {
                const dataSize = this.chunks[0].length * this.numChannels * 2;
                const buffer = new ArrayBuffer(44 + dataSize);
                const view = new DataView(buffer);

                writeString(view, 0, 'RIFF');
                view.setUint32(4, 36 + dataSize, true);
                writeString(view, 8, 'WAVE');
                writeString(view, 12, 'fmt ');
                view.setUint32(16, 16, true);
                view.setUint16(20, 1, true);
                view.setUint16(22, this.numChannels, true);
                view.setUint32(24, this.sampleRate, true);
                view.setUint32(28, this.sampleRate * this.numChannels * 2, true);
                view.setUint16(32, this.numChannels * 2, true);
                view.setUint16(34, 16, true);
                writeString(view, 36, 'data');
                view.setUint32(40, dataSize, true);

                const offset = 44;
                for (let i = 0; i < this.chunks[0].length; i++) {
                    for (let channel = 0; channel < this.numChannels; channel++) {
                        const sample = Math.max(-1, Math.min(1, this.chunks[channel][i]));
                        view.setInt16(offset + (i * this.numChannels + channel) * 2,
                            sample < 0 ? sample * 0x8000 : sample * 0x7FFF, true);
                    }
                }

                return new Blob([buffer], {
                    type: 'audio/wav'
                });
            }
        }

        function writeString(view, offset, string) {
            for (let i = 0; i < string.length; i++) {
                view.setUint8(offset + i, string.charCodeAt(i));
            }
        }

        function setCookie(name, value, days = 30) {
            const d = new Date();
            d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = "expires=" + d.toUTCString();
            document.cookie = name + "=" + value + ";" + expires + ";path=/";
        }

        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }

        function formatCameraLabel(label, index) {
            if (!label) return `Camera ${index + 1}`;

            const facingMatch = label.match(/facing\s+(front|back)/i);
            const facingDir = facingMatch ? facingMatch[1].charAt(0).toUpperCase() + facingMatch[1].slice(1) : '';

            const resMatch = label.match(/(\d+)x(\d+)/);
            let resolution = '';
            if (resMatch) {
                const width = parseInt(resMatch[1]);
                if (width > 3000) {
                    resolution = ' (High Res)';
                } else if (width > 1500) {
                    resolution = ' (Medium)';
                }
            }

            let cameraType = '';
            if (label.toLowerCase().includes('ultra') || label.toLowerCase().includes('wide')) {
                cameraType = ' Ultra-Wide';
            } else if (label.toLowerCase().includes('tele') || label.toLowerCase().includes('zoom')) {
                cameraType = ' Telephoto';
            }

            if (facingDir) {
                return `${facingDir}${cameraType || ''}${resolution} Camera`;
            }

            const cleanLabel = label.split(',')[0].replace(/\([^)]*\)/g, '').trim();
            return cleanLabel || `Camera ${index + 1}`;
        }

        async function getCameras() {
            try {
                console.log("Enumerating media devices...");
                const devices = await navigator.mediaDevices.enumerateDevices();
                const videoDevices = devices.filter(device => device.kind === 'videoinput');

                if (videoDevices.length > 0 && !videoDevices[0].label) {
                    console.log("Unlabeled cameras detected. Requesting permission first...");
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                facingMode: {
                                    ideal: "environment"
                                }
                            }
                        });
                        window.tempStream = stream;
                        const newDevices = await navigator.mediaDevices.enumerateDevices();
                        cameras = newDevices.filter(device => device.kind === 'videoinput');
                    } catch (permErr) {
                        console.error("Could not get camera permission:", permErr);
                    }
                } else {
                    cameras = videoDevices;
                }

                const lastUsedId = getCookie('lastUsedCamera');
                if (lastUsedId) {
                    const lastUsedIndex = cameras.findIndex(cam => cam.deviceId === lastUsedId);
                    if (lastUsedIndex >= 0) {
                        currentCameraIndex = lastUsedIndex;
                    }
                }

                updateCameraList();
                if (cameras.length > 0) {
                    await startCamera(cameras[currentCameraIndex].deviceId);
                }
                return cameras;
            } catch (err) {
                console.error("Error enumerating devices:", err);
                return [];
            }
        }

        function updateCameraList() {
            const cameraList = document.getElementById('camera-list');
            cameraList.innerHTML = '';
            cameras.forEach((camera, index) => {
                const div = document.createElement('div');
                div.className = `camera-option ${index === currentCameraIndex ? 'active' : ''}`;
                div.textContent = formatCameraLabel(camera.label, index);
                div.onclick = () => switchToCamera(index);
                cameraList.appendChild(div);
            });
        }

        async function startCamera(deviceId = null) {
            try {
                if (videoStream) {
                    videoStream.getTracks().forEach(track => track.stop());
                }

                const quality = document.getElementById('camera-quality').value;
                let constraints = {
                    video: {
                        deviceId: deviceId ? {
                            exact: deviceId
                        } : undefined,
                        zoom: true
                    }
                };

                switch (quality) {
                    case 'qvga':
                        constraints.video.width = {
                            ideal: 320
                        };
                        constraints.video.height = {
                            ideal: 240
                        };
                        break;
                    case 'vga':
                        constraints.video.width = {
                            ideal: 640
                        };
                        constraints.video.height = {
                            ideal: 480
                        };
                        break;
                    case 'hd':
                        constraints.video.width = {
                            ideal: 1280
                        };
                        constraints.video.height = {
                            ideal: 720
                        };
                        break;
                    case 'fhd':
                        constraints.video.width = {
                            ideal: 1920
                        };
                        constraints.video.height = {
                            ideal: 1080
                        };
                        break;
                }

                videoStream = await navigator.mediaDevices.getUserMedia(constraints);
                const mainVideo = document.getElementById('main-video');
                mainVideo.srcObject = videoStream;
                mainVideo.style.transform = isFlipped ? 'scaleX(-1)' : 'scaleX(1)';

                const videoTrack = videoStream.getVideoTracks()[0];
                if (videoTrack) {
                    const capabilities = videoTrack.getCapabilities();
                    const settings = videoTrack.getSettings();

                    if (capabilities.zoom) {
                        const zoomSlider = document.getElementById('camera-zoom');
                        zoomSlider.min = capabilities.zoom.min * 100;
                        zoomSlider.max = capabilities.zoom.max * 100;
                        zoomSlider.value = (settings.zoom || 1) * 100;
                        zoomSlider.style.display = 'block';

                        await videoTrack.applyConstraints({
                            advanced: [{
                                zoom: settings.zoom || 1
                            }]
                        });
                    } else {
                        document.getElementById('camera-zoom').style.display = 'none';
                    }
                }

                return videoStream;
            } catch (err) {
                console.error("Camera access error:", err);
                return null;
            }
        }

        async function switchToCamera(index) {
            if (index >= 0 && index < cameras.length) {
                currentCameraIndex = index;
                const camera = cameras[index];
                await startCamera(camera.deviceId);
                updateCameraList();
                setCookie('lastUsedCamera', camera.deviceId);
                document.querySelector('.camera-selection').style.display = 'none';
                document.getElementById('btnCameraSource').innerHTML = '<i class="fas fa-video"></i> Camera On';
            }
        }

        function toggleFlipVideo() {
            isFlipped = !isFlipped;
            const mainVideo = document.getElementById('main-video');
            mainVideo.style.transform = isFlipped ? 'scaleX(-1)' : 'scaleX(1)';
        }

        window.addEventListener('DOMContentLoaded', () => {
            getCameras();
            document.getElementById('flip-video').addEventListener('click', toggleFlipVideo);

            document.getElementById('btnReset').addEventListener('click', () => {
                if (hasUnsavedRecordings) {
                    if (!confirm('You have unsaved recordings. Are you sure you want to reset?')) {
                        return;
                    }
                }
                location.reload();
            });

            const contentAreas = document.querySelectorAll('.content-area');
            contentAreas.forEach(area => {
                area.style.overflow = 'hidden';
            });

            document.querySelector('.editor-view').style.overflowX = 'hidden';
        });

        document.getElementById('camera-quality').addEventListener('change', () => {
            startCamera(cameras[currentCameraIndex].deviceId);
        });

        document.getElementById('camera-zoom').addEventListener('input', async (e) => {
            const zoomLevel = e.target.value / 100;
            const videoTrack = videoStream?.getVideoTracks()[0];
            if (videoTrack) {
                try {
                    await videoTrack.applyConstraints({
                        advanced: [{
                            zoom: zoomLevel
                        }]
                    });
                } catch (err) {
                    console.error("Could not apply zoom:", err);
                    updateStatus("This camera does not support zoom control", "error");
                }
            }
        });

        let baseName = 'recording-' + new Date().toISOString().slice(0, 19).replace(/[:]/g, '-');
    </script>
</body>

</html>
