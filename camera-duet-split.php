<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title>Dual Camera Recorder</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            background-color: #000;
            color: white;
            font-family: Arial, sans-serif;
        }

        #container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            width: 100vw;
        }

        #video-container {
            display: flex;
            flex: 1;
            position: relative;
        }

        .video-wrapper {
            position: relative;
            flex: 1;
            overflow: hidden;
            border: 1px solid #333;
        }

        .video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: #000;
        }

        .camera-selection {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            flex-direction: column;
            background-color: rgba(0, 0, 0, 0.7);
            border-radius: 10px;
            overflow: hidden;
            max-height: 90vh;
            max-width: 90vw;
            width: 320px;
            transition: transform 0.3s, opacity 0.3s;
            z-index: 100;
        }

        .camera-selection.hidden {
            transform: translateY(100%);
            opacity: 0;
        }

        .camera-list {
            margin: 10px 0;
            max-height: 350px;
            overflow-y: auto;
            padding-right: 15px;
            padding-left: 15px;
        }

        .camera-option {
            padding: 10px;
            margin: 5px 0;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .camera-option:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .camera-option.active {
            background-color: rgba(0, 150, 255, 0.4);
        }

        .controls {
            display: flex;
            justify-content: space-around;
            background-color: rgba(0, 0, 0, 0.85);
            padding: 10px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 10;
        }

        .video-controls {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.5);
            padding: 5px;
        }

        .main-controls {
            width: 100%;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
        }

        button {
            padding: 10px;
            font-size: 16px;
            background-color: #0056b3;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            touch-action: manipulation;
        }

        button:hover {
            background-color: #0062cc;
        }

        button.active {
            background-color: #e74c3c;
        }

        .layout-toggle {
            padding: 8px;
            margin-right: 5px;
        }

        .mode-indicator {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: rgba(0, 0, 0, 0.5);
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 50;
        }

        .record-timer {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255, 0, 0, 0.8);
            padding: 5px 10px;
            border-radius: 5px;
            z-index: 50;
            display: none;
        }

        /* Responsive layout adjustments */
        @media (max-width: 768px) {
            #video-container {
                flex-direction: column;
            }

            .video-wrapper {
                height: 50%;
            }

            .layout-options button {
                font-size: 12px;
                padding: 8px;
            }
        }

        .banner {
            width: 100%;
            background-color: #0056b3;
            color: white;
            text-align: left;
            padding: 10px;
            font-size: 18px;
            box-sizing: border-box;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        /* Screen recording specific styles */
        #screen-video {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            z-index: 200;
            background: #000;
        }

        .screen-controls {
            display: none;
            position: fixed;
            bottom: 20px;
            left: 0;
            width: 100%;
            z-index: 210;
            justify-content: center;
            padding: 10px 0;
            background: rgba(0, 0, 0, 0.7);
        }

        /* Add styles for source selectors */
        .source-selector {
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 60;
            background-color: rgba(0, 0, 0, 0.7);
            border-radius: 5px;
            padding: 5px;
        }

        .source-selector select {
            background-color: #333;
            color: white;
            border: 1px solid #555;
            border-radius: 3px;
            padding: 5px 10px;
            font-size: 14px;
        }

        .placeholder-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: rgba(255, 255, 255, 0.5);
            font-size: 16px;
            text-align: center;
        }

        /* Error message styles */
        .error-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(255, 0, 0, 0.7);
            padding: 15px;
            border-radius: 8px;
            max-width: 80%;
            text-align: center;
            z-index: 1000;
        }

        .refresh-button {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 60;
            padding: 5px 10px;
            background-color: #0056b3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        /* Debug panel */
        #debug-panel {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid #666;
            border-radius: 5px;
            padding: 8px;
            max-width: 300px;
            max-height: 150px;
            overflow-y: auto;
            font-size: 12px;
            z-index: 9999;
            display: none;
        }
    </style>
</head>

<body>
    <div id="container">
        <div id="video-container">
            <!-- Left camera view -->
            <div class="video-wrapper" id="left-wrapper">
                <video id="left-video" class="video" autoplay playsinline></video>
                <div class="mode-indicator" id="left-indicator">Camera 1</div>
                <div class="record-timer" id="left-timer">00:00</div>
                <div class="source-selector">
                    <select id="left-source-select">
                        <option value="" selected>Select Source...</option>
                        <!-- Options will be populated dynamically -->
                    </select>
                    <button class="refresh-button" id="left-refresh-btn" title="Refresh Sources">🔄</button>
                </div>
                <div class="placeholder-message" id="left-placeholder">Select a camera or screen source</div>
                <div class="video-controls">
                    <button id="left-camera-btn" data-tooltip="Select Camera">📷</button>
                    <button id="left-record" data-tooltip="Record">⏺</button>
                    <button id="left-snapshot" data-tooltip="Snapshot">📸</button>
                    <button id="left-flip" data-tooltip="Flip">⇄</button>
                </div>
            </div>

            <!-- Right camera view -->
            <div class="video-wrapper" id="right-wrapper">
                <video id="right-video" class="video" autoplay playsinline></video>
                <div class="mode-indicator" id="right-indicator">Camera 2</div>
                <div class="record-timer" id="right-timer">00:00</div>
                <div class="source-selector">
                    <select id="right-source-select">
                        <option value="" selected>Select Source...</option>
                        <!-- Options will be populated dynamically -->
                    </select>
                    <button class="refresh-button" id="right-refresh-btn" title="Refresh Sources">🔄</button>
                </div>
                <div class="placeholder-message" id="right-placeholder">Select a camera or screen source</div>
                <div class="video-controls">
                    <button id="right-camera-btn" data-tooltip="Select Camera">📷</button>
                    <button id="right-record" data-tooltip="Record">⏺</button>
                    <button id="right-snapshot" data-tooltip="Snapshot">📸</button>
                    <button id="right-flip" data-tooltip="Flip">⇄</button>
                </div>
            </div>

            <div id="debug-panel"></div>
        </div>

        <!-- Screen recording view -->
        <video id="screen-video" autoplay playsinline muted></video>
        <div class="screen-controls">
            <button id="stop-screen-record">Stop Screen Recording</button>
        </div>

        <div class="main-controls">
            <div class="layout-options">
                <button id="side-by-side" class="layout-toggle active">Side by Side</button>
                <button id="stacked" class="layout-toggle">Stacked</button>
            </div>
            <button id="record-both">Record Both</button>
            <button id="screen-record">Record Screen</button>
            <button id="fullscreen-toggle">Fullscreen</button>
        </div>
    </div>

    <script>
        // Browser detection
        const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
        const isiOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        const isAndroid = /Android/.test(navigator.userAgent);

        // DOM Elements
        const leftVideo = document.getElementById('left-video');
        const rightVideo = document.getElementById('right-video');
        const screenVideo = document.getElementById('screen-video');
        const leftWrapper = document.getElementById('left-wrapper');
        const rightWrapper = document.getElementById('right-wrapper');
        const leftTimer = document.getElementById('left-timer');
        const rightTimer = document.getElementById('right-timer');
        const container = document.getElementById('container');
        const videoContainer = document.getElementById('video-container');
        const leftSourceSelect = document.getElementById('left-source-select');
        const rightSourceSelect = document.getElementById('right-source-select');
        const leftPlaceholder = document.getElementById('left-placeholder');
        const rightPlaceholder = document.getElementById('right-placeholder');
        const leftRefreshBtn = document.getElementById('left-refresh-btn');
        const rightRefreshBtn = document.getElementById('right-refresh-btn');
        const debugPanel = document.getElementById('debug-panel');

        // Layout Toggles
        const sideBySideBtn = document.getElementById('side-by-side');
        const stackedBtn = document.getElementById('stacked');

        // Recording buttons
        const leftRecordBtn = document.getElementById('left-record');
        const rightRecordBtn = document.getElementById('right-record');
        const recordBothBtn = document.getElementById('record-both');
        const screenRecordBtn = document.getElementById('screen-record');
        const stopScreenRecordBtn = document.getElementById('stop-screen-record');

        // Other controls
        const leftCameraBtn = document.getElementById('left-camera-btn');
        const rightCameraBtn = document.getElementById('right-camera-btn');
        const leftSnapshotBtn = document.getElementById('left-snapshot');
        const rightSnapshotBtn = document.getElementById('right-snapshot');
        const leftFlipBtn = document.getElementById('left-flip');
        const rightFlipBtn = document.getElementById('right-flip');
        const fullscreenToggleBtn = document.getElementById('fullscreen-toggle');

        // State variables
        let cameras = [];
        let leftStream = null;
        let rightStream = null;
        let screenStream = null;
        let leftRecorder = null;
        let rightRecorder = null;
        let screenRecorder = null;
        let leftRecording = false;
        let rightRecording = false;
        let bothRecording = false;
        let screenRecording = false;
        let leftRecordingChunks = [];
        let rightRecordingChunks = [];
        let screenRecordingChunks = [];
        let leftRecordingStartTime;
        let rightRecordingStartTime;
        let screenRecordingStartTime;
        let leftRecordingTimer;
        let rightRecordingTimer;
        let screenRecordingTimer;
        let leftIsFlipped = false;
        let rightIsFlipped = false;
        let currentLeftCameraIndex = 0;
        let currentRightCameraIndex = 0;
        let sources = []; // Will contain both cameras and "Screen Capture" option
        let debugMode = false; // Set to true to enable debug panel
        let permissionGranted = false;

        // Additional state variables
        let permissionDenied = false;
        let initAttempts = 0;
        const MAX_INIT_ATTEMPTS = 3;

        // Initialize with debug mode detection from URL and enable debugging by default
        document.addEventListener('DOMContentLoaded', () => {
            // Always show debug panel during development to troubleshoot
            debugMode = true;
            debugPanel.style.display = 'block';

            if (window.location.search.includes('debug=true')) {
                debugMode = true;
                debugPanel.style.display = 'block';
            }

            // Try to initialize immediately
            initializeWithPermissionRequest();

            // Listen for device changes
            navigator.mediaDevices.addEventListener('devicechange', () => {
                log('Device change detected');
                refreshSources();
            });
        });

        // Revised initialization flow with direct camera request first
        async function initializeWithPermissionRequest() {
            try {
                log("Starting camera initialization with direct permission request...");
                initAttempts++;

                // First, try to get permission directly using getUserMedia
                // This is the most reliable way to get permission on most browsers
                try {
                    log("Requesting camera permission directly...");
                    const tempStream = await navigator.mediaDevices.getUserMedia({
                        video: true,
                        audio: false
                    });

                    log("Camera permission granted successfully");
                    permissionGranted = true;

                    // Keep this stream active temporarily
                    window.tempStream = tempStream;

                    // Now that we have permission, get the list of cameras
                    await getCameras();

                    // Stop the temporary stream after a short delay
                    setTimeout(() => {
                        if (window.tempStream) {
                            window.tempStream.getTracks().forEach(track => track.stop());
                            window.tempStream = null;
                            log("Temporary stream stopped");
                        }
                    }, 1000);

                    // Populate the source lists
                    populateSources();

                    // Set up event listeners
                    setupEventListeners();

                } catch (err) {
                    log(`Camera permission denied: ${err.message}`);
                    permissionDenied = true;

                    // Still try to enumerate devices to see what's available
                    await getCameras();
                    populateSources();

                    // Show permission error UI
                    showPermissionDeniedUI();
                }
            } catch (err) {
                log(`Initialization failed: ${err.message}`);

                if (initAttempts < MAX_INIT_ATTEMPTS) {
                    log(`Retrying initialization (attempt ${initAttempts})...`);
                    setTimeout(initializeWithPermissionRequest, 1000);
                } else {
                    log("Maximum initialization attempts reached");
                    showError("Failed to initialize camera. Please refresh the page and try again.");
                }
            }
        }

        // Simplified camera enumeration function from the working camera-duet.php
        async function getCameras() {
            try {
                log("Enumerating media devices...");
                const devices = await navigator.mediaDevices.enumerateDevices();
                log(`Found ${devices.length} media devices total`);

                // Filter video input devices
                const videoDevices = devices.filter(device => device.kind === 'videoinput');
                log(`Found ${videoDevices.length} video input devices`);

                // Log each device
                videoDevices.forEach((device, idx) => {
                    log(`Camera ${idx}: ID=${device.deviceId.substring(0,8)}..., Label=${device.label || 'No label'}`);
                });

                cameras = videoDevices;
                return cameras;
            } catch (err) {
                log(`Error enumerating devices: ${err.message}`);
                return [];
            }
        }

        // Populate sources from cameras array
        function populateSources() {
            log(`Populating sources from ${cameras.length} cameras`);

            // Clear existing sources
            sources = [];

            // Add cameras to sources array
            sources = cameras.map((camera, index) => ({
                id: camera.deviceId,
                label: formatCameraLabel(camera.label || `Camera ${index + 1}`, index),
                type: 'camera'
            }));

            // Add screen capture option
            sources.push({
                id: 'screen',
                label: 'Screen Capture',
                type: 'screen'
            });

            // Update the UI
            updateSourceSelects();

            log(`Source list populated with ${sources.length} options`);
        }

        // Format camera label (using the approach from camera-duet.php)
        function formatCameraLabel(label, index) {
            if (!label || label === 'No label' || label.indexOf('label') === 0) {
                return `Camera ${index + 1}`;
            }

            // Extract facing direction (front/back)
            const facingMatch = label.match(/facing\s+(front|back)/i);

            // Get the camera index
            const cameraIdMatch = label.match(/camera\s*(\d+)/i);
            const cameraIndex = cameraIdMatch ? ` ${cameraIdMatch[1]}` : '';

            // Check for resolution info
            let resolution = '';
            const resMatch = label.match(/(\d+)x(\d+)/);
            if (resMatch) {
                const width = parseInt(resMatch[1]);
                if (width > 3000) {
                    resolution = ' (High Res)';
                } else if (width > 1500) {
                    resolution = ' (Medium)';
                }
            }

            // Look for special camera types
            let cameraType = '';
            if (label.toLowerCase().includes('ultra') || label.toLowerCase().includes('wide')) {
                cameraType = ' Ultra-Wide';
            } else if (label.toLowerCase().includes('tele') || label.toLowerCase().includes('zoom')) {
                cameraType = ' Telephoto';
            }

            if (facingMatch) {
                const direction = facingMatch[1].charAt(0).toUpperCase() + facingMatch[1].slice(1);
                return `${direction}${cameraType || cameraIndex}${resolution} Camera`;
            }

            // Default format if no patterns match
            return `Camera ${index + 1}: ${label.split(',')[0]}`;
        }

        // Show UI when permission is denied
        function showPermissionDeniedUI() {
            log("Showing permission denied UI");

            const permissionUI = document.createElement('div');
            permissionUI.className = 'error-message';
            permissionUI.style.backgroundColor = 'rgba(200, 0, 0, 0.85)';
            permissionUI.innerHTML = `
                <h3>Camera Access Denied</h3>
                <p>This application needs access to your cameras to function properly.</p>
                <p>You can fix this by:</p>
                <ol>
                    <li>Clicking the camera icon in your browser's address bar and allowing access</li>
                    <li>Going to your browser settings and resetting permissions for this site</li>
                    <li>Using a different browser</li>
                </ol>
                <p>After enabling camera access, refresh this page.</p>
                <button onclick="window.location.reload()">Refresh Page</button>
                <button onclick="this.parentNode.remove()">Dismiss</button>
            `;

            document.body.appendChild(permissionUI);
        }

        // Completely override the startCamera function with a simpler, more reliable version
        async function startCamera(side, deviceId = null) {
            log(`Starting camera for ${side} side with deviceId: ${deviceId || 'default'}`);

            const videoElement = side === 'left' ? leftVideo : rightVideo;
            const currentStream = side === 'left' ? leftStream : rightStream;
            const indicator = side === 'left' ? document.getElementById('left-indicator') : document.getElementById('right-indicator');
            const placeholder = side === 'left' ? leftPlaceholder : rightPlaceholder;

            // Stop existing stream
            if (currentStream) {
                log(`Stopping existing ${side} stream`);
                currentStream.getTracks().forEach(track => track.stop());
            }

            try {
                // Use simple constraints first for higher compatibility
                const constraints = {
                    video: deviceId ? {
                        deviceId: {
                            exact: deviceId
                        }
                    } : true
                };

                log(`Requesting media with constraints: ${JSON.stringify(constraints)}`);
                const stream = await navigator.mediaDevices.getUserMedia(constraints);

                // Success! Now set up the video element
                videoElement.srcObject = stream;
                placeholder.style.display = 'none';

                // Get camera info for better labeling
                const videoTrack = stream.getVideoTracks()[0];
                const label = videoTrack ? videoTrack.label : 'Camera';
                indicator.textContent = label.split(' ')[0] || 'Camera';

                log(`${side} camera started successfully: ${label}`);

                // Save the stream reference
                if (side === 'left') {
                    leftStream = stream;
                } else {
                    rightStream = stream;
                }

                return stream;
            } catch (err) {
                log(`Error starting ${side} camera: ${err.message}`);
                placeholder.textContent = `Camera error: ${err.message}`;
                placeholder.style.display = 'block';

                // Reset dropdown
                const select = side === 'left' ? leftSourceSelect : rightSourceSelect;
                select.value = '';

                return null;
            }
        }

        // Override refreshSources to fix device enumeration
        async function refreshSources(side = null) {
            log(`Refreshing sources${side ? ` for ${side} side` : ''}`);

            // Stop any active streams first to ensure clean state
            if (side === 'left' || side === null) {
                if (leftStream) {
                    leftStream.getTracks().forEach(track => track.stop());
                    leftStream = null;
                }
            }

            if (side === 'right' || side === null) {
                if (rightStream) {
                    rightStream.getTracks().forEach(track => track.stop());
                    rightStream = null;
                }
            }

            // Request camera permission again to ensure we have fresh access
            try {
                const tempStream = await navigator.mediaDevices.getUserMedia({
                    video: true
                });

                // Keep the stream active briefly
                window.tempStream = tempStream;

                // Re-enumerate devices
                await getCameras();

                // Update sources
                populateSources();

                // Stop the temporary stream after a short delay
                setTimeout(() => {
                    if (window.tempStream) {
                        window.tempStream.getTracks().forEach(track => track.stop());
                        window.tempStream = null;
                    }
                }, 1000);

                // Show feedback
                if (side) {
                    const placeholder = side === 'left' ? leftPlaceholder : rightPlaceholder;
                    placeholder.textContent = "Refreshed sources list";
                    setTimeout(() => {
                        placeholder.textContent = "Select a camera or screen source";
                    }, 2000);
                }

                // Clear dropdowns
                leftSourceSelect.value = '';
                rightSourceSelect.value = '';

                // Log success
                log(`Source refresh complete, found ${cameras.length} cameras`);
            } catch (err) {
                log(`Error refreshing sources: ${err.message}`);
                showError(`Failed to refresh sources: ${err.message}`);
            }
        }

        // Add a user-friendly error with console access instructions
        function showSupportInfo() {
            const infoDiv = document.createElement('div');
            infoDiv.className = 'error-message';
            infoDiv.style.backgroundColor = 'rgba(50, 50, 150, 0.85)';
            infoDiv.innerHTML = `
                <h3>Troubleshooting Camera Access</h3>
                <p>If cameras aren't appearing:</p>
                <ol>
                    <li>Make sure you're using HTTPS or localhost</li>
                    <li>Press F12 to open browser developer tools</li>
                    <li>Check the Console tab for error messages</li>
                    <li>Use Ctrl+Shift+D to toggle debug panel</li>
                </ol>
                <p><strong>Debug status:</strong> ${debugMode ? 'Enabled' : 'Disabled'}</p>
                <p><strong>Cameras detected:</strong> ${cameras.length}</p>
                <p><strong>Permission granted:</strong> ${permissionGranted ? 'Yes' : 'No'}</p>
                <button onclick="refreshSources()">Refresh Cameras</button>
                <button onclick="debugMode=!debugMode; debugPanel.style.display=debugMode?'block':'none'; this.textContent=debugMode?'Hide Debug':'Show Debug';">
                    ${debugMode ? 'Hide Debug' : 'Show Debug'}
                </button>
                <button onclick="this.parentNode.remove()">Dismiss</button>
            `;

            document.body.appendChild(infoDiv);
        }

        // Add a Help button to the main controls
        function addHelpButton() {
            const mainControls = document.querySelector('.main-controls');
            if (mainControls) {
                const helpButton = document.createElement('button');
                helpButton.textContent = 'Help';
                helpButton.onclick = showSupportInfo;
                mainControls.appendChild(helpButton);
            }
        }

        // Call this function to add the help button
        document.addEventListener('DOMContentLoaded', addHelpButton);

        // Logging function that also outputs to debug panel
        function log(message) {
            console.log(message);
            if (debugMode) {
                const now = new Date().toLocaleTimeString();
                const logEntry = document.createElement('div');
                logEntry.textContent = `[${now}] ${message}`;
                debugPanel.appendChild(logEntry);
                debugPanel.scrollTop = debugPanel.scrollHeight;

                // Limit entries
                while (debugPanel.childElementCount > 20) {
                    debugPanel.removeChild(debugPanel.firstChild);
                }
            }
        }

        async function initializeSources() {
            try {
                log("Initializing sources...");
                await getCameras();

                if (cameras.length === 0) {
                    log("No cameras detected. Requesting permissions explicitly.");
                    await requestCameraPermission();
                    await getCameras(); // Try again after permission
                }

                // Add cameras to the sources array
                sources = cameras.map((camera, index) => ({
                    id: camera.deviceId,
                    label: formatCameraLabel(camera.label, index),
                    type: 'camera'
                }));

                log(`Found ${sources.length} camera sources`);

                // Add screen capture option
                sources.push({
                    id: 'screen',
                    label: 'Screen Capture',
                    type: 'screen'
                });

                // Populate the dropdowns
                updateSourceSelects();

                if (sources.length <= 1) {
                    // Only the screen capture option is available
                    showError("No camera devices detected. Please check your camera connections and browser permissions.");
                }

            } catch (err) {
                log(`Source initialization failed: ${err.message}`);
                showError("Failed to initialize sources. Please check permissions and try again.");
            }
        }

        async function requestCameraPermission() {
            try {
                log("Explicitly requesting camera permission");
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: true
                });
                permissionGranted = true;

                // Don't keep this stream around, just use it to get permissions
                stream.getTracks().forEach(track => track.stop());
                log("Camera permission granted");
                return true;
            } catch (err) {
                log(`Failed to get camera permission: ${err.message}`);
                permissionGranted = false;
                return false;
            }
        }

        function updateSourceSelects() {
            log("Updating source selection dropdowns");

            // Clear existing options except the first one
            while (leftSourceSelect.options.length > 1) {
                leftSourceSelect.remove(1);
            }
            while (rightSourceSelect.options.length > 1) {
                rightSourceSelect.remove(1);
            }

            // Add sources to both dropdowns
            sources.forEach(source => {
                const leftOption = document.createElement('option');
                leftOption.value = source.type + '-' + source.id;
                leftOption.textContent = source.label;
                leftSourceSelect.appendChild(leftOption);

                const rightOption = document.createElement('option');
                rightOption.value = source.type + '-' + source.id;
                rightOption.textContent = source.label;
                rightSourceSelect.appendChild(rightOption);

                log(`Added source: ${source.label} (${source.type})`);
            });
        }

        async function getCameras() {
            try {
                log("Enumerating media devices...");
                const devices = await navigator.mediaDevices.enumerateDevices();
                log(`Found ${devices.length} media devices total`);

                const videoDevices = devices.filter(device => device.kind === 'videoinput');
                log(`Found ${videoDevices.length} video input devices`);

                // If we have unlabeled devices and no permission, try requesting permission
                if (videoDevices.length > 0 && !videoDevices[0].label && !permissionGranted) {
                    log("Found unlabeled devices, requesting camera permission");
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({
                            video: true
                        });
                        permissionGranted = true;

                        // Store stream temporarily to keep permission active
                        window.tempStream = stream;

                        // Re-enumerate after getting permission
                        const newDevices = await navigator.mediaDevices.enumerateDevices();
                        cameras = newDevices.filter(device => device.kind === 'videoinput');
                        log(`After permission: found ${cameras.length} cameras`);

                        // Stop the temporary stream
                        window.tempStream.getTracks().forEach(track => track.stop());
                        window.tempStream = null;
                    } catch (permErr) {
                        log(`Permission error: ${permErr.message}`);
                        permissionGranted = false;
                    }
                } else {
                    cameras = videoDevices;
                }

                // Log each camera for debugging
                cameras.forEach((camera, idx) => {
                    log(`Camera ${idx}: ${camera.label || 'Unnamed camera'} (${camera.deviceId.substring(0, 8)}...)`);
                });

                return cameras;
            } catch (err) {
                log(`Error enumerating devices: ${err.message}`);
                return [];
            }
        }

        function setupEventListeners() {
            // Layout toggle
            sideBySideBtn.addEventListener('click', () => setLayout('side-by-side'));
            stackedBtn.addEventListener('click', () => setLayout('stacked'));

            // Record buttons
            leftRecordBtn.addEventListener('click', () => toggleRecording('left'));
            rightRecordBtn.addEventListener('click', () => toggleRecording('right'));
            recordBothBtn.addEventListener('click', toggleRecordingBoth);
            screenRecordBtn.addEventListener('click', startScreenRecording);
            stopScreenRecordBtn.addEventListener('click', stopScreenRecording);

            // Camera selection buttons
            leftCameraBtn.addEventListener('click', () => showCameraSelector('left'));
            rightCameraBtn.addEventListener('click', () => showCameraSelector('right'));

            // Snapshot buttons
            leftSnapshotBtn.addEventListener('click', () => takeSnapshot('left'));
            rightSnapshotBtn.addEventListener('click', () => takeSnapshot('right'));

            // Flip buttons
            leftFlipBtn.addEventListener('click', () => toggleFlipVideo('left'));
            rightFlipBtn.addEventListener('click', () => toggleFlipVideo('right'));

            // Fullscreen toggle
            fullscreenToggleBtn.addEventListener('click', toggleFullscreen);

            // Source selection dropdowns
            leftSourceSelect.addEventListener('change', () => handleSourceChange('left', leftSourceSelect.value));
            rightSourceSelect.addEventListener('change', () => handleSourceChange('right', rightSourceSelect.value));

            // Add refresh button handlers
            leftRefreshBtn.addEventListener('click', () => refreshSources('left'));
            rightRefreshBtn.addEventListener('click', () => refreshSources('right'));

            // Press 'd' key to toggle debug panel
            document.addEventListener('keydown', (e) => {
                if (e.key === 'd' && e.ctrlKey && e.shiftKey) {
                    debugMode = !debugMode;
                    debugPanel.style.display = debugMode ? 'block' : 'none';
                    log("Debug panel " + (debugMode ? "enabled" : "disabled"));
                }
            });
        }

        function setLayout(layout) {
            if (layout === 'side-by-side') {
                videoContainer.style.flexDirection = 'row';
                leftWrapper.style.height = '100%';
                rightWrapper.style.height = '100%';

                sideBySideBtn.classList.add('active');
                stackedBtn.classList.remove('active');
            } else if (layout === 'stacked') {
                videoContainer.style.flexDirection = 'column';
                leftWrapper.style.height = '50%';
                rightWrapper.style.height = '50%';

                stackedBtn.classList.add('active');
                sideBySideBtn.classList.remove('active');
            }
        }

        async function toggleRecording(side) {
            if (side === 'left') {
                if (!leftRecording) {
                    await startRecording('left');
                    leftRecordBtn.classList.add('active');
                    leftRecording = true;
                } else {
                    await stopRecording('left');
                    leftRecordBtn.classList.remove('active');
                    leftRecording = false;
                }
            } else {
                if (!rightRecording) {
                    await startRecording('right');
                    rightRecordBtn.classList.add('active');
                    rightRecording = true;
                } else {
                    await stopRecording('right');
                    rightRecordBtn.classList.remove('active');
                    rightRecording = false;
                }
            }
        }

        async function toggleRecordingBoth() {
            if (!bothRecording) {
                await startRecording('both');
                recordBothBtn.classList.add('active');
                leftRecordBtn.classList.add('active');
                rightRecordBtn.classList.add('active');
                bothRecording = true;
            } else {
                await stopRecording('both');
                recordBothBtn.classList.remove('active');
                leftRecordBtn.classList.remove('active');
                rightRecordBtn.classList.remove('active');
                bothRecording = false;
            }
        }

        async function startRecording(mode) {
            if (isiOS) {
                alert('Video recording is not supported on iOS. Please take snapshots instead.');
                return;
            }

            try {
                const options = {
                    mimeType: 'video/webm;codecs=vp8,opus'
                };

                if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                    options.mimeType = 'video/webm';
                    if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                        options.mimeType = 'video/mp4';
                    }
                }

                if (mode === 'left' || mode === 'both') {
                    if (!leftStream) return;

                    leftRecordingChunks = [];
                    leftRecorder = new MediaRecorder(leftStream, options);

                    leftRecorder.ondataavailable = (e) => {
                        if (e.data.size > 0) {
                            leftRecordingChunks.push(e.data);
                        }
                    };

                    leftRecorder.onstop = () => {
                        const blob = new Blob(leftRecordingChunks, {
                            type: leftRecorder.mimeType
                        });
                        saveRecording(blob, 'left');
                    };

                    leftRecordingStartTime = Date.now();
                    updateRecordingTimer('left');
                    leftRecordingTimer = setInterval(() => updateRecordingTimer('left'), 1000);
                    leftTimer.style.display = 'block';
                    leftRecorder.start();
                    leftRecording = true;
                }

                if (mode === 'right' || mode === 'both') {
                    if (!rightStream) return;

                    rightRecordingChunks = [];
                    rightRecorder = new MediaRecorder(rightStream, options);

                    rightRecorder.ondataavailable = (e) => {
                        if (e.data.size > 0) {
                            rightRecordingChunks.push(e.data);
                        }
                    };

                    rightRecorder.onstop = () => {
                        const blob = new Blob(rightRecordingChunks, {
                            type: rightRecorder.mimeType
                        });
                        saveRecording(blob, 'right');
                    };

                    rightRecordingStartTime = Date.now();
                    updateRecordingTimer('right');
                    rightRecordingTimer = setInterval(() => updateRecordingTimer('right'), 1000);
                    rightTimer.style.display = 'block';
                    rightRecorder.start();
                    rightRecording = true;
                }
            } catch (err) {
                console.error('Recording failed:', err);
                alert('Video recording failed. Please try again or use a different browser.');
            }
        }

        async function stopRecording(mode) {
            if (mode === 'left' || mode === 'both') {
                if (leftRecorder && leftRecorder.state !== 'inactive') {
                    leftRecorder.stop();
                    clearInterval(leftRecordingTimer);
                    leftTimer.style.display = 'none';
                    leftRecording = false;
                }
            }

            if (mode === 'right' || mode === 'both') {
                if (rightRecorder && rightRecorder.state !== 'inactive') {
                    rightRecorder.stop();
                    clearInterval(rightRecordingTimer);
                    rightTimer.style.display = 'none';
                    rightRecording = false;
                }
            }
        }

        function updateRecordingTimer(side) {
            const startTime = side === 'left' ? leftRecordingStartTime : rightRecordingStartTime;
            const timer = side === 'left' ? leftTimer : rightTimer;

            if (!startTime) return;

            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
            const seconds = (elapsed % 60).toString().padStart(2, '0');
            timer.textContent = `${minutes}:${seconds}`;
        }

        function saveRecording(blob, source) {
            try {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
                a.href = url;
                a.download = `${source}-recording-${timestamp}.webm`;
                a.click();
                URL.revokeObjectURL(url);
                console.log(`${source} recording saved successfully.`);
            } catch (err) {
                console.error('Error saving recording:', err);
                alert('Error saving recording. The recording may be available in a new tab.');
                window.open(URL.createObjectURL(blob));
            }
        }

        function takeSnapshot(side) {
            const videoElement = side === 'left' ? leftVideo : rightVideo;
            const isFlipped = side === 'left' ? leftIsFlipped : rightIsFlipped;

            const canvas = document.createElement('canvas');
            canvas.width = videoElement.videoWidth;
            canvas.height = videoElement.videoHeight;

            const ctx = canvas.getContext('2d');
            if (isFlipped) {
                ctx.scale(-1, 1);
                ctx.translate(-canvas.width, 0);
            }
            ctx.drawImage(videoElement, 0, 0);

            try {
                const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
                const link = document.createElement('a');
                link.download = `${side}-snapshot-${timestamp}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
            } catch (err) {
                console.error('Error saving snapshot:', err);

                // For iOS, show in new tab
                if (isiOS) {
                    const newTab = window.open();
                    if (newTab) {
                        newTab.document.write('<img src="' + canvas.toDataURL('image/png') + '" alt="Snapshot">');
                        newTab.document.close();
                    } else {
                        alert('Please allow pop-ups to save images');
                    }
                }
            }
        }

        function toggleFlipVideo(side) {
            if (side === 'left') {
                leftIsFlipped = !leftIsFlipped;
                leftVideo.style.transform = leftIsFlipped ? 'scaleX(-1)' : 'scaleX(1)';
            } else {
                rightIsFlipped = !rightIsFlipped;
                rightVideo.style.transform = rightIsFlipped ? 'scaleX(-1)' : 'scaleX(1)';
            }
        }

        function showCameraSelector(side) {
            // Instead of showing the dialog, just focus and open the dropdown
            const dropdown = side === 'left' ? leftSourceSelect : rightSourceSelect;
            dropdown.focus();
            dropdown.click();
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                container.requestFullscreen().catch(err => {
                    console.error(`Error attempting to enable fullscreen:`, err);
                });
            } else {
                document.exitFullscreen();
            }
        }

        async function startScreenRecording() {
            if (isiOS) {
                alert('Screen recording is not supported on iOS.');
                return;
            }

            try {
                screenStream = await navigator.mediaDevices.getDisplayMedia({
                    video: {
                        cursor: "always"
                    },
                    audio: false
                });

                screenVideo.srcObject = screenStream;
                screenVideo.style.display = 'block';
                document.querySelector('.screen-controls').style.display = 'flex';

                const options = {
                    mimeType: 'video/webm;codecs=vp8,opus'
                };

                if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                    options.mimeType = 'video/webm';
                    if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                        options.mimeType = 'video/mp4';
                    }
                }

                screenRecordingChunks = [];
                screenRecorder = new MediaRecorder(screenStream, options);

                screenRecorder.ondataavailable = (e) => {
                    if (e.data.size > 0) {
                        screenRecordingChunks.push(e.data);
                    }
                };

                screenRecorder.onstop = () => {
                    const blob = new Blob(screenRecordingChunks, {
                        type: screenRecorder.mimeType
                    });
                    saveRecording(blob, 'screen');

                    screenVideo.style.display = 'none';
                    document.querySelector('.screen-controls').style.display = 'none';

                    screenStream.getTracks().forEach(track => track.stop());
                    screenStream = null;
                };

                screenRecordingStartTime = Date.now();
                screenRecorder.start();
                screenRecordBtn.classList.add('active');
                screenRecording = true;

            } catch (err) {
                console.error('Screen recording failed:', err);
                alert('Screen recording failed or was cancelled. Please try again.');
            }
        }

        function stopScreenRecording() {
            if (screenRecorder && screenRecorder.state !== 'inactive') {
                screenRecorder.stop();
                screenRecordBtn.classList.remove('active');
                screenRecording = false;
            }
        }

        async function handleSourceChange(side, value) {
            log(`Source changed for ${side} side: ${value}`);

            if (!value) {
                // Selected the "Select Source..." option
                stopSource(side);
                return;
            }

            const [type, id] = value.split('-');

            try {
                if (type === 'camera') {
                    await startCamera(side, id);
                    if (side === 'left') {
                        leftPlaceholder.style.display = 'none';
                    } else {
                        rightPlaceholder.style.display = 'none';
                    }
                    log(`Started camera for ${side} side`);
                } else if (type === 'screen') {
                    await startScreenCapture(side);
                    if (side === 'left') {
                        leftPlaceholder.style.display = 'none';
                    } else {
                        rightPlaceholder.style.display = 'none';
                    }
                    log(`Started screen capture for ${side} side`);
                }
            } catch (err) {
                log(`Error changing source: ${err.message}`);
                showError(`Failed to start ${type} for ${side} side: ${err.message}`);

                // Reset the dropdown
                const select = side === 'left' ? leftSourceSelect : rightSourceSelect;
                select.value = '';
            }
        }

        function stopSource(side) {
            const videoElement = side === 'left' ? leftVideo : rightVideo;
            const currentStream = side === 'left' ? leftStream : rightStream;
            const placeholder = side === 'left' ? leftPlaceholder : rightPlaceholder;

            if (currentStream) {
                currentStream.getTracks().forEach(track => track.stop());
            }

            videoElement.srcObject = null;
            if (side === 'left') {
                leftStream = null;
            } else {
                rightStream = null;
            }

            placeholder.style.display = 'block';
        }

        async function startScreenCapture(side) {
            const videoElement = side === 'left' ? leftVideo : rightVideo;
            const currentStream = side === 'left' ? leftStream : rightStream;

            // Stop existing stream
            if (currentStream) {
                currentStream.getTracks().forEach(track => track.stop());
            }

            try {
                const stream = await navigator.mediaDevices.getDisplayMedia({
                    video: {
                        cursor: "always"
                    },
                    audio: false
                });

                videoElement.srcObject = stream;

                if (side === 'left') {
                    leftStream = stream;
                    leftPlaceholder.style.display = 'none';
                    document.getElementById('left-indicator').textContent = 'Screen';
                } else {
                    rightStream = stream;
                    rightPlaceholder.style.display = 'none';
                    document.getElementById('right-indicator').textContent = 'Screen';
                }

                // Add event listener for when user stops screen sharing
                stream.getVideoTracks()[0].addEventListener('ended', () => {
                    console.log(`Screen share ended for ${side} side`);
                    stopSource(side);
                    if (side === 'left') {
                        leftSourceSelect.value = '';
                    } else {
                        rightSourceSelect.value = '';
                    }
                });

                return stream;
            } catch (err) {
                console.error(`Error starting screen capture for ${side}:`, err);
                return null;
            }
        }

        async function refreshSources(side) {
            log(`Refreshing sources for ${side} side`);

            // Clear current devices cache to force re-enumeration
            if (navigator.mediaDevices && navigator.mediaDevices.dispatchEvent) {
                try {
                    // Try to trigger a devicechange event
                    navigator.mediaDevices.dispatchEvent(new Event('devicechange'));
                } catch (e) {
                    log("Could not dispatch devicechange event");
                }
            }

            // Reinitialize sources
            await initializeSources();

            // Show feedback
            const placeholder = side === 'left' ? leftPlaceholder : rightPlaceholder;
            placeholder.textContent = "Refreshed sources list";
            setTimeout(() => {
                placeholder.textContent = "Select a camera or screen source";
            }, 2000);
        }

        // Show error message
        function showError(message) {
            // Remove any existing error
            const existingError = document.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }

            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = `
                <p>${message}</p>
                <button onclick="this.parentNode.remove()">Dismiss</button>
                <button onclick="requestCameraPermission().then(() => initializeSources())">
                    Request Camera Permission
                </button>
            `;
            document.body.appendChild(errorDiv);

            log(`ERROR: ${message}`);
        }

        // Close and release resources when page closes
        window.addEventListener('beforeunload', () => {
            if (leftStream) {
                leftStream.getTracks().forEach(track => track.stop());
            }
            if (rightStream) {
                rightStream.getTracks().forEach(track => track.stop());
            }
            if (screenStream) {
                screenStream.getTracks().forEach(track => track.stop());
            }
        });
    </script>
</body>

</html>
