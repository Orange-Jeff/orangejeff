<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires"="0">

    <title>Camera Plus</title>
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

        :fullscreen #video-container {
            width: 100vw;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
        }

        :fullscreen #main-video {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #000;
        }

        :fullscreen .controls {
            position: fixed;
            bottom: 20px;
            left: 0;
            width: 100%;
            z-index: 1500;
            background: rgba(0, 0, 0, 0.5);
            padding: 10px 0;
        }

        :fullscreen .camera-selection {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2000;
        }

        /* Remove the red banner in fullscreen */
        :fullscreen::before {
            display: none !important;
        }

        #video-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #000;
            z-index: 1;
        }

        #main-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block !important;
            /* Ensure video is always displayed */
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
            /* Add left padding to match right padding */
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
            position: relative;
            width: 100%;
            display: flex;
            justify-content: space-around;
            background-color: rgba(0, 0, 0, 0.85);
            padding: 10px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 10;
        }

        .fullscreen-active .controls {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 15px 0;
        }

        button {
            padding: 10px;
            font-size: 16px;
            background-color: #0056b3;
            /* Match the standard blue color */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            touch-action: manipulation;
        }

        button:hover {
            background-color: #0062cc;
            /* Slightly lighter on hover */
        }

        button#record,
        button#snapshot,
        button#fullscreen-toggle,
        button#flip-video {
            flex: 1;
        }

        button#prev-camera,
        button#next-camera {
            width: 40px;
            padding: 10px 12px;
            min-width: 40px;
        }

        button#record {
            position: relative;
            background-color: rgba(0, 0, 0, 0.7);
            transition: all 0.3s ease;
            font-size: 12px;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        button#record::before {
            content: "⏺";
            /* Circle symbol for not recording */
            font-size: 18px;
            color: rgba(255, 0, 0, 0.6);
            /* Dull red dot when not recording */
            transition: all 0.3s ease;
        }

        button#record.recording {
            background-color: rgba(231, 76, 60, 0.8);
            border: 2px solid #ff3b30;
            animation: pulse 1.5s infinite;
        }

        button#record.recording::before {
            content: "■";
            /* Square/cube symbol for recording */
            color: #ff0000;
            /* Bright red when recording */
            text-shadow: 0 0 5px rgba(255, 0, 0, 0.8);
            /* Glow effect */
        }

        button#record.recording::after {
            content: "REC";
            position: absolute;
            bottom: 2px;
            right: 5px;
            font-size: 10px;
            color: white;
            font-weight: bold;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.6;
            }

            100% {
                opacity: 1;
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

        .record-timer {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: rgba(255, 0, 0, 0.8);
            padding: 5px 10px;
            border-radius: 5px;
            z-index: 1000;
            display: none;
        }

        .snapshot-countdown {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 48px;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            z-index: 1001;
        }

        .tooltip {
            position: fixed;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px;
            border-radius: 4px;
            font-size: 14px;
            max-width: 200px;
            z-index: 2000;
            pointer-events: none;
            transition: opacity 0.3s;
            opacity: 0;
        }

        .tooltip.visible {
            opacity: 1;
        }

        /* Hide all warning banners */
        .browser-warning,
        .red-warning,
        div[style*="background: rgba(255,165,0,0.9)"],
        div[style*="color: red"] {
            display: none !important;
        }

        /* Make buttons more tappable on mobile */
        .controls button {
            min-width: 44px;
            min-height: 44px;
            margin: 0 5px;
            font-size: 20px;
            cursor: pointer;
            border-radius: 5px;
            /* Square with rounded corners instead of circle */
            background-color: #0056b3;
            /* Standard blue */
            color: white;
            border: none;
            transition: all 0.2s ease;
        }

        .controls button:active {
            transform: scale(0.95);
            background-color: #0062cc;
            /* Slightly lighter when active */
        }

        /* Simple fullscreen mode for mobile */
        .mobile-fullscreen {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            z-index: 9999 !important;
            background: #000 !important;
        }

        .mobile-fullscreen-body {
            overflow: hidden !important;
            position: fixed;
            width: 100%;
            height: 100%;
        }

        .mobile-fullscreen #main-video {
            display: block !important;
            width: 100% !important;
            height: 100% !important;
            object-fit: contain !important;
        }

        .mobile-fullscreen .controls {
            position: fixed !important;
            bottom: 20px !important;
            left: 0 !important;
            width: 100% !important;
            display: flex !important;
            padding: 15px 0 !important;
            background: rgba(0, 0, 0, 0.7) !important;
            z-index: 10000 !important;
        }

        /* Mobile-friendly mode for recovery */
        .mobile-friendly {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: #000 !important;
            display: flex !important;
            flex-direction: column !important;
        }

        .mobile-friendly #main-video {
            width: 100% !important;
            height: calc(100% - 80px) !important;
            object-fit: contain !important;
        }

        .mobile-friendly .controls {
            height: 60px !important;
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            width: 100% !important;
            background: rgba(0, 0, 0, 0.8) !important;
        }

        /* Add these styles directly to the HTML to fix the controls positioning */
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
            /* Ensures contents stay within border radius */
            transition: transform 0.3s, opacity 0.3s;
        }

        .controls {
            position: relative;
            /* Changed from absolute to relative */
            bottom: 0;
            width: 100%;
            display: flex;
            justify-content: space-around;
            background-color: rgba(0, 0, 0, 0.85);
            /* Slightly darker background */
            padding: 10px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            /* Add a subtle separator */
        }

        /* Add a class for fullscreen mode */
        .fullscreen-active .controls {
            position: fixed;
            bottom: 0;
            left: 0;
            z-index: 1000;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 15px 0;
        }

        /* Hide any warning banner */
        .browser-warning,
        .red-warning {
            display: none !important;
        }
    </style>
</head>

<body>
    <div id="video-container">
        <video id="main-video" autoplay playsinline></video>
        <div class="record-timer" id="record-timer">00:00</div>
        <div class="camera-selection" id="camera-selection">
            <div class="banner">
                <span>NetBound Tools: Camera Plus</span>
                <!-- Fix the close button to properly turn off camera and attempt to close tab -->
                <button class="close-btn" onclick="handleClose()">×</button>
            </div>
            <div class="camera-list" id="camera-list"></div>
            <!-- Removed Permanent Status Area -->
            <div class="controls">
                <button id="prev-camera" data-tooltip="Previous Camera">←</button>
                <button id="record" data-tooltip="Start/Stop Recording"></button>
                <button id="snapshot" data-tooltip="Take Photo">📷</button>
                <button id="flip-video" data-tooltip="Flip Camera">⇄</button>
                <button id="fullscreen-toggle" data-tooltip="Toggle Fullscreen">□</button>
                <button id="next-camera" data-tooltip="Next Camera">→</button>
            </div>
        </div>
        <!-- Removed status-box div -->
    </div>
    <script>
        // Add Android detection along with browser detection at the start
        const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
        const isiOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        const isAndroid = /Android/.test(navigator.userAgent);
        const isInIframe = window.self !== window.top;

        const mainVideo = document.getElementById('main-video');
        const videoContainer = document.getElementById('video-container');
        const cameraSelection = document.getElementById('camera-selection');
        const cameraList = document.getElementById('camera-list');
        const recordTimer = document.getElementById('record-timer');
        const recordButton = document.getElementById('record');
        const prevButton = document.getElementById('prev-camera');
        const nextButton = document.getElementById('next-camera');
        const flipButton = document.getElementById('flip-video');

        let mainStream;
        let cameras = [];
        let selectedCameras = new Set();
        let currentCameraIndex = 0;
        let mediaRecorder;
        let recordingChunks = [];
        let isRecording = false;
        let recordingStartTime;
        let recordingTimer;
        let touchStartX = 0;
        let touchStartY = 0;
        let touchEndX = 0;
        let touchEndY = 0;
        let isFlipped = false;
        let permissionAlreadyAsked = false;

        if (isInIframe) {
            window.addEventListener('unload', () => {
                closeApp(); // Make sure resources are released
            });

            // Listen for parent frame messages
            window.addEventListener('message', (event) => {
                if (event.data === 'release') {
                    closeApp();
                }
            });
        }
        async function getCameras() {
            try {
                console.log("Enumerating media devices...");
                const devices = await navigator.mediaDevices.enumerateDevices();

                // Filter video input devices
                const videoDevices = devices.filter(device => device.kind === 'videoinput');
                console.log(`Found ${videoDevices.length} video input devices:`);

                // Log device information
                videoDevices.forEach((device, idx) => {
                    console.log(`Camera ${idx}: ID=${device.deviceId.substring(0,8)}..., Label=${device.label || 'No label'}`);
                });

                // IMPROVED: Get permission first for all devices
                if (videoDevices.length > 0 && !videoDevices[0].label) {
                    console.log("Unlabeled cameras detected. Requesting permission first...");
                    try {
                        // Request with ideal facing mode to try to get the back camera first
                        const stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                facingMode: {
                                    ideal: "environment"
                                }
                            }
                        });

                        // Keep this stream active until we start the actual camera
                        window.tempStream = stream;

                        // Re-enumerate after getting permission
                        const newDevices = await navigator.mediaDevices.enumerateDevices();
                        cameras = newDevices.filter(device => device.kind === 'videoinput');

                        // Don't stop the stream yet - we'll use it when starting the camera
                    } catch (permErr) {
                        console.error("Could not get camera permission:", permErr);
                    }
                } else {
                    cameras = videoDevices;
                }

                // If we have multiple cameras, select all by default
                if (cameras.length > 0) {
                    // Select all cameras by default
                    for (let i = 0; i < Math.min(cameras.length, 4); i++) {
                        selectedCameras.add(i);
                    }
                    console.log(`Selected ${selectedCameras.size} cameras by default`);
                }

                updateCameraList();
                return cameras;
            } catch (err) {
                console.error("Error enumerating devices:", err);
                return [];
            }
        }

        async function startCamera(deviceId = null) {
            try {
                console.log(`Starting camera with deviceId: ${deviceId || 'default'}`);

                if (mainStream) {
                    console.log("Stopping existing camera stream");
                    mainStream.getTracks().forEach(track => {
                        track.stop();
                        console.log(`Stopped track: ${track.kind} (${track.label})`);
                    });
                }

                if (isRecording) {
                    console.log("Stopping active recording");
                    await stopRecording();
                }

                // Use more specific constraints for Android
                let constraints;
                if (isAndroid) {
                    constraints = {
                        video: deviceId ? {
                            deviceId: {
                                exact: deviceId
                            }
                        } : {
                            facingMode: {
                                ideal: "environment"
                            }
                        }
                    };
                } else {
                    constraints = {
                        video: deviceId ? {
                            deviceId: {
                                exact: deviceId
                            }
                        } : true
                    };
                }

                console.log("Requesting media with constraints:", JSON.stringify(constraints));

                mainStream = await navigator.mediaDevices.getUserMedia(constraints);
                console.log("Stream obtained successfully:", mainStream.id);
                console.log("Video tracks:", mainStream.getVideoTracks().map(t => `${t.label} (${t.id})`).join(', '));

                mainVideo.srcObject = mainStream;
                hidePermissionUI(); // Hide permission UI if present
                return mainStream;
            } catch (err) {
                console.error("Camera access error:", err);
                // Try fallback to default camera
                if (deviceId) {
                    console.log("Attempting fallback to default camera...");
                    return startCamera(null);
                }

                // Don't show alert if we've already asked for permission
                if (!permissionAlreadyAsked) {
                    permissionAlreadyAsked = true;
                    showPermissionUI();
                }
                return null;
            }
        }

        /**
         * Formats camera labels by removing numeric prefixes and simplifying the name.
         * @param {string} label - The original label from MediaDeviceInfo.
         * @param {number} index - The camera index.
         * @returns {string} - The formatted label.
         */
        function formatCameraLabel(label, index) {
            if (!label) {
                return `Camera ${index + 1}`;
            }

            // Extract facing direction (front/back)
            const facingMatch = label.match(/facing\s+(front|back)/i);

            // Get the camera index to differentiate between multiple cameras of the same type
            const cameraIdMatch = label.match(/camera\s*(\d+)/i);
            const cameraIndex = cameraIdMatch ? ` ${cameraIdMatch[1]}` : '';

            // Get width/resolution info if available
            let resolution = '';
            const resMatch = label.match(/(\d+)x(\d+)/);
            if (resMatch) {
                const width = parseInt(resMatch[1]);
                // Add resolution info for cameras with higher resolution
                if (width > 3000) {
                    resolution = ' (High Res)';
                } else if (width > 1500) {
                    resolution = ' (Medium)';
                }
            }

            // Look for ultra-wide, telephoto keywords
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

            // If no pattern matches, return a formatted version of the original
            return `Camera ${index + 1}: ${label.split(',')[0]}`;
        }

        // Modify updateCameraList to use formatted labels
        function updateCameraList() {
            cameraList.innerHTML = '';
            cameras.forEach((camera, index) => {
                const div = document.createElement('div');
                div.className = `camera-option ${selectedCameras.has(index) ? 'active' : ''}`;
                div.textContent = formatCameraLabel(camera.label, index);
                div.onclick = () => toggleCameraSelection(index);
                cameraList.appendChild(div);
            });
        }

        function toggleCameraSelection(index) {
            if (selectedCameras.has(index)) {
                if (selectedCameras.size <= 1) {
                    console.log('At least one camera must remain selected.');
                    return;
                }
                selectedCameras.delete(index);
                console.log(`Camera ${index + 1} removed from selection.`);
            } else {
                selectedCameras.add(index);
                startCamera(cameras[index].deviceId);
                currentCameraIndex = index;
                console.log(`Camera ${index + 1} selected.`);
            }
            updateCameraList();
            // Removed updatePermanentStatus() call
        }

        // Add this new function:
        async function switchCamera(direction) {
            const selectedArray = Array.from(selectedCameras);
            if (selectedArray.length > 1) {
                try {
                    const currentIndex = selectedArray.indexOf(currentCameraIndex);
                    const nextIndex = (currentIndex + direction + selectedArray.length) % selectedArray.length;
                    const nextCameraIndex = selectedArray[nextIndex];

                    console.log(`Switching from camera ${currentCameraIndex} to camera ${nextCameraIndex}`);

                    // Stop current stream first for Android
                    if (isAndroid && mainStream) {
                        mainStream.getTracks().forEach(track => track.stop());
                        await new Promise(resolve => setTimeout(resolve, 300)); // Small delay for Android
                    }

                    currentCameraIndex = nextCameraIndex;

                    // Use try/catch for startCamera to handle failures
                    try {
                        const success = await startCamera(cameras[currentCameraIndex].deviceId);
                        if (success) {
                            console.log(`Successfully switched to camera ${currentCameraIndex}`);
                            updateCameraList();
                        } else {
                            console.error(`Failed to start camera ${currentCameraIndex}`);
                            selectedCameras.delete(currentCameraIndex);
                            updateCameraList();
                        }
                    } catch (cameraErr) {
                        console.error(`Error starting camera ${currentCameraIndex}:`, cameraErr);
                        selectedCameras.delete(currentCameraIndex);
                        updateCameraList();
                    }
                } catch (err) {
                    console.error("Camera switch failed:", err);
                }
            } else {
                console.log('Please select at least two cameras to switch between them.');
            }
        }

        // Then replace switchToNextCamera and switchToPreviousCamera with:
        async function switchToNextCamera() {
            switchCamera(1);
        }

        async function switchToPreviousCamera() {
            switchCamera(-1);
        }

        function toggleFlipVideo() {
            isFlipped = !isFlipped;
            mainVideo.style.transform = isFlipped ? 'scaleX(-1)' : 'scaleX(1)';
        }

        // Modify the recording function to handle iOS
        async function startRecording() {
            if (isiOS) {
                alert('Video recording is not supported on iOS. Please take snapshots instead.');
                return;
            }

            const mainVideoStream = mainVideo.srcObject;
            if (!mainVideoStream) {
                console.log('No video stream available for recording.');
                return;
            }

            try {
                const videoTrack = mainVideoStream.getVideoTracks()[0];
                const stream = new MediaStream([videoTrack]);

                const options = {
                    mimeType: 'video/webm;codecs=vp8,opus'
                };

                // Check if browser supports webm
                if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                    options.mimeType = 'video/webm';
                    // If still not supported, try mp4
                    if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                        options.mimeType = 'video/mp4';
                    }
                }

                mediaRecorder = new MediaRecorder(stream, options);
                recordingChunks = [];

                mediaRecorder.ondataavailable = (e) => {
                    if (e.data.size > 0) {
                        recordingChunks.push(e.data);
                    }
                };

                mediaRecorder.onstop = () => {
                    const blob = new Blob(recordingChunks, {
                        type: mediaRecorder.mimeType
                    });
                    if (isiOS) {
                        // For iOS, open in new tab
                        const url = URL.createObjectURL(blob);
                        window.open(url, '_blank');
                    } else {
                        // For other browsers, download
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `recording-${new Date().toISOString()}.webm`;
                        a.click();
                        URL.revokeObjectURL(url);
                    }
                    console.log('Recording saved successfully.');
                };

                recordingStartTime = Date.now();
                updateRecordingTimer();
                recordingTimer = setInterval(updateRecordingTimer, 1000);
                recordTimer.style.display = 'block';
                mediaRecorder.start();
                recordButton.classList.add('recording');
                console.log('Recording started. Button class added:', recordButton.className); // Add this line
                isRecording = true;
                console.log('Recording started.');
            } catch (err) {
                console.error('Recording failed:', err);
                console.log('Video recording is not supported in your browser. Please try using Chrome or Firefox.');
            }
        }

        // Fix the stopRecording function - missing event listener
        async function stopRecording() {
            return new Promise((resolve) => {
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    mediaRecorder.addEventListener('stop', () => { // Added missing line
                        resolve();
                    }, {
                        once: true
                    });
                    mediaRecorder.stop();
                    clearInterval(recordingTimer);
                    recordTimer.style.display = 'none';
                    recordButton.classList.remove('recording');
                    isRecording = false;
                } else {
                    resolve();
                }
            });
        }

        function updateRecordingTimer() {
            const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
            const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
            const seconds = (elapsed % 60).toString().padStart(2, '0');
            recordTimer.textContent = `${minutes}:${seconds}`;
        }

        // Modify the snapshot function to handle iOS
        function takeSnapshot() {
            const canvas = document.createElement('canvas');
            canvas.width = mainVideo.videoWidth;
            canvas.height = mainVideo.videoHeight;
            const ctx = canvas.getContext('2d');
            if (isFlipped) {
                ctx.scale(-1, 1);
                ctx.translate(-canvas.width, 0);
            }
            ctx.drawImage(mainVideo, 0, 0);

            if (isiOS) {
                // For iOS, show the image in a new tab
                const newTab = window.open();
                if (newTab) {
                    newTab.document.write('<img src="' + canvas.toDataURL('image/png') + '" alt="Snapshot">');
                    newTab.document.close();
                } else {
                    alert('Please allow pop-ups to save images');
                }
            } else {
                // For other browsers, use download attribute
                const link = document.createElement('a');
                link.download = `snapshot-${new Date().toISOString()}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
            }
        }

        function showCountdown(count) {
            const countdown = document.createElement('div');
            countdown.className = 'snapshot-countdown';
            countdown.textContent = count;
            document.body.appendChild(countdown);

            setTimeout(() => {
                document.body.removeChild(countdown);
            }, 1000);
        }

        // Debounce function to limit the rate at which a function can fire.
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        // Improve handleSwipe function for better detection on Android
        function handleSwipe() {
            const horizontalThreshold = isAndroid ? 20 : 30; // Lower threshold for Android
            const verticalThreshold = isAndroid ? 40 : 30; // Higher vertical threshold for Android

            const differenceX = touchStartX - touchEndX;
            const differenceY = touchStartY - touchEndY;

            console.log(`Swipe detected: X diff: ${differenceX}, Y diff: ${differenceY}`);

            // Check for horizontal swipe for camera switching (keep this functionality)
            if (isAndroid && Math.abs(differenceX) > horizontalThreshold) {
                if (differenceX > 0) {
                    console.log("Detected right-to-left swipe, switching to next camera");
                    switchToNextCamera();
                } else {
                    console.log("Detected left-to-right swipe, switching to previous camera");
                    switchToPreviousCamera();
                }
            }
            // For non-Android, handle horizontal swipes if not already handled
            else if (!isAndroid && Math.abs(differenceX) > horizontalThreshold) {
                if (differenceX > 0) {
                    console.log("Detected right-to-left swipe, switching to next camera");
                    switchToNextCamera();
                } else {
                    console.log("Detected left-to-right swipe, switching to previous camera");
                    switchToPreviousCamera();
                }
            }
        }

        // Apply debounce to handleSwipe
        const debouncedHandleSwipe = debounce(handleSwipe, 100);

        // Fix the touchend event handler by not preventing default all the time
        videoContainer.addEventListener('touchend', function(e) {
            // Only prevent default for swipes, not for all touches
            touchEndX = e.changedTouches[0].screenX;
            touchEndY = e.changedTouches[0].screenY;

            // Allow some interactions to work normally
            const dx = Math.abs(touchStartX - touchEndX);
            const dy = Math.abs(touchStartY - touchEndY);

            // Only prevent default if it's a swipe gesture
            if (dx > 20 || dy > 20) {
                e.preventDefault();
            }

            // Use direct function call instead of debounce for Android
            if (isAndroid) {
                handleSwipe();
            } else {
                debouncedHandleSwipe();
            }
        });

        // Improve touchstart event for Android - don't prevent all defaults
        videoContainer.addEventListener('touchstart', function(e) {
            // Don't prevent all touch events by default
            touchStartX = e.changedTouches[0].screenX;
            touchStartY = e.changedTouches[0].screenY;
        });

        // Update button event listeners to be more responsive on Android
        // === CRITICAL CAMERA SWITCHING CODE - DO NOT REMOVE ===
        // Remove this block because it's causing issues:
        // prevButton.removeEventListener('click', switchToPreviousCamera); // Remove old listeners
        // nextButton.removeEventListener('click', switchToNextCamera);

        // Make sure we only add the event listeners once
        function setupButtonListeners() {
            console.log('Setting up button event listeners');

            // Clear any existing listeners by cloning and replacing elements
            const newPrevButton = prevButton.cloneNode(true);
            const newNextButton = nextButton.cloneNode(true);
            prevButton.parentNode.replaceChild(newPrevButton, prevButton);
            nextButton.parentNode.replaceChild(newNextButton, nextButton);

            // Update references - use different variable names to avoid shadowing
            const prevBtn = document.getElementById('prev-camera');
            const nextBtn = document.getElementById('next-camera');

            // Add event listeners
            prevBtn.addEventListener('click', function(e) {
                console.log('Previous camera button clicked');
                switchToPreviousCamera();
            });

            nextBtn.addEventListener('click', function(e) {
                console.log('Next camera button clicked');
                switchToNextCamera();
            });

            // For Android touch events
            if (isAndroid) {
                prevBtn.addEventListener('touchend', function(e) {
                    e.preventDefault();
                    console.log('Previous camera button touched');
                    switchToPreviousCamera();
                });

                nextBtn.addEventListener('touchend', function(e) {
                    e.preventDefault();
                    console.log('Next camera button touched');
                    switchToNextCamera();
                });
            }
        }

        // === END CRITICAL CAMERA CODE ===

        document.getElementById('snapshot').addEventListener('click', () => {
            let count = 3;
            cameraSelection.classList.add('hidden');
            const countdownInterval = setInterval(() => {
                showCountdown(count);
                count--;
                if (count === 0) {
                    clearInterval(countdownInterval);
                    setTimeout(takeSnapshot, 1000);
                }
            }, 1000);
        });

        // Improve fullscreen handling for mobile
        document.getElementById('fullscreen-toggle').addEventListener('click', function() {
            console.log("Fullscreen toggle clicked");

            // For mobile devices, use a different approach
            if (isAndroid || isiOS) {
                console.log("Mobile device detected, using mobile fullscreen approach");

                // Toggle a class instead of actual fullscreen API on mobile
                if (!videoContainer.classList.contains('mobile-fullscreen')) {
                    console.log("Entering mobile fullscreen mode");
                    videoContainer.classList.add('mobile-fullscreen');
                    document.body.classList.add('mobile-fullscreen-body');

                    // Make sure the video is visible
                    mainVideo.style.display = 'block';
                    mainVideo.style.objectFit = 'contain';

                    // Ensure controls are visible
                    document.querySelector('.controls').style.display = 'flex';
                } else {
                    console.log("Exiting mobile fullscreen mode");
                    videoContainer.classList.remove('mobile-fullscreen');
                    document.body.classList.remove('mobile-fullscreen-body');
                }
                return;
            }

            // Regular fullscreen handling for desktop
            if (!document.fullscreenElement) {
                console.log("Requesting fullscreen");
                videoContainer.requestFullscreen().then(() => {
                    console.log("Fullscreen enabled");
                    videoContainer.classList.add('fullscreen-active');
                    mainVideo.style.objectFit = 'contain'; // Changed from 'cover' for better viewing

                    // Make sure any red warning elements are removed
                    const warnings = document.querySelectorAll('.browser-warning, .red-warning');
                    warnings.forEach(warning => warning.style.display = 'none');

                    // Re-setup button listeners to ensure they work in fullscreen
                    setupButtonListeners();
                }).catch(err => {
                    console.log(`Error attempting to enable fullscreen: ${err.message}`);
                    // Fallback to mobile fullscreen mode on error
                    videoContainer.classList.add('mobile-fullscreen');
                    document.body.classList.add('mobile-fullscreen-body');
                });
            } else {
                console.log("Exiting fullscreen");
                document.exitFullscreen();
                videoContainer.classList.remove('fullscreen-active');
            }
        });

        // Improve setupButtonListeners to fix the issue with camera switching buttons
        function setupButtonListeners() {
            console.log('Setting up button event listeners');

            // Get fresh references to the buttons
            const prevBtn = document.getElementById('prev-camera');
            const nextBtn = document.getElementById('next-camera');

            if (prevBtn && nextBtn) {
                // Use direct function references for simplicity
                prevBtn.onclick = function() {
                    console.log('Previous camera button clicked');
                    switchToPreviousCamera();
                };

                nextBtn.onclick = function() {
                    console.log('Next camera button clicked');
                    switchToNextCamera();
                };

                // For mobile touch events
                if (isAndroid || isiOS) {
                    prevBtn.ontouchend = function(e) {
                        e.preventDefault();
                        console.log('Previous camera button touched');
                        switchToPreviousCamera();
                    };

                    nextBtn.ontouchend = function(e) {
                        e.preventDefault();
                        console.log('Next camera button touched');
                        switchToNextCamera();
                    };
                }
            } else {
                console.error('Camera navigation buttons not found');
            }

            if (recordBtn) {
                recordBtn.onclick = function() {
                    console.log('Record button clicked');
                    if (!isRecording) {
                        startRecording();
                    } else {
                        stopRecording();
                    }
                };
            }

            // Make sure double click/tap works in all modes
            let lastClickTime = 0;
            videoContainer.addEventListener('click', function(e) {
                const currentTime = new Date().getTime();
                const timeDiff = currentTime - lastClickTime;

                if (timeDiff < 300 && timeDiff > 0) {
                    toggleMenu();
                    e.preventDefault();
                }

                lastClickTime = currentTime;
            });

            let lastTapTime = 0;
            videoContainer.addEventListener('touchend', function(e) {
                const currentTime = new Date().getTime();
                const timeDiff = currentTime - lastTapTime;

                if (timeDiff < 300 && timeDiff > 0) {
                    toggleMenu();
                    e.preventDefault();
                }

                lastTapTime = currentTime;
            });
        }

        // Add this initialization to the DOMContentLoaded event
        window.addEventListener('DOMContentLoaded', () => {
            updateControls();
            setupButtonListeners();
        });

        document.addEventListener('fullscreenchange', () => {
            if (!document.fullscreenElement) {
                videoContainer.classList.remove('fullscreen-active');
                mainVideo.style.objectFit = 'cover';
            }
        });

        function closeApp() {
            if (isRecording) {
                stopRecording();
            }
            if (mainStream) {
                mainStream.getTracks().forEach(track => track.stop());
            }
            mainVideo.srcObject = null;
            videoContainer.style.backgroundColor = '#1a1a1a';
            if (document.fullscreenElement) {
                document.exitFullscreen();
            }
        }

        // Add toggleCamera function
        /**
         * Toggles the camera on and off.
         * If the camera is active, it stops the stream and recording.
         * If the camera is inactive, it initializes the camera stream.
         */
        function toggleCamera() {
            if (mainStream) {
                closeApp();
                console.log('Camera turned off.');
            } else {
                getCameras().then(() => {
                    if (cameras.length > 0) {
                        selectedCameras.add(0);
                        updateCameraList();
                        startCamera(cameras[0].deviceId);
                        console.log('Camera turned on.');
                    } else {
                        console.log('No cameras found.');
                    }
                }).catch(() => {
                    console.log('Failed to access cameras. Please check permissions.');
                });
            }
        }

        // Initialize
        getCameras().then(() => {
            if (cameras.length > 0) {
                selectedCameras.add(0);
                updateCameraList();
                startCamera(cameras[0].deviceId);
                // Removed updatePermanentStatus() call
            }
        });

        navigator.mediaDevices.getUserMedia({
                video: true
            })
            .then(initialStream => {
                initialStream.getTracks().forEach(track => track.stop());
                getCameras();
            })
            .catch(err => {
                console.error("Error requesting initial camera permission:", err);
                // Don't show alert, instead show a permission UI element
                permissionAlreadyAsked = true;
                showPermissionUI();
            });

        // Add cleanup on page unload
        window.addEventListener('beforeunload', () => {
            closeApp();
        });

        // Add browser warning if needed
        window.addEventListener('DOMContentLoaded', () => {
            updateControls();
        });

        // Add feature detection
        const features = {
            webm: MediaRecorder && MediaRecorder.isTypeSupported('video/webm'),
            mp4: MediaRecorder && MediaRecorder.isTypeSupported('video/mp4'),
            download: 'download' in document.createElement('a'),
            fullscreen: document.documentElement.requestFullscreen
        };

        // Modify the controls creation
        function updateControls() {
            const controls = document.querySelector('.controls');
            const recordBtn = document.getElementById('record');
            const snapshotBtn = document.getElementById('snapshot');
            const fullscreenBtn = document.getElementById('fullscreen-toggle');

            // Handle record button
            if (!features.webm && !features.mp4) {
                recordBtn.classList.add('unsupported');
                recordBtn.dataset.tooltip = 'Recording not supported in this browser';
            }

            // Handle snapshot button
            if (!features.download && isiOS) {
                snapshotBtn.dataset.tooltip = 'Snapshots will open in new tab';
            }

            // Handle fullscreen button
            if (!features.fullscreen) {
                fullscreenBtn.classList.add('unsupported');
                fullscreenBtn.dataset.tooltip = 'Fullscreen not supported in this browser';
            }
        }

        // Modify fullscreen toggle
        document.getElementById('fullscreen-toggle').addEventListener('click', () => {
            console.log("Fullscreen toggle clicked");
            if (!document.fullscreenElement) {
                console.log("Requesting fullscreen");
                videoContainer.requestFullscreen().then(() => {
                    // cameraSelection.classList.add('hidden');
                    mainVideo.style.objectFit = 'cover';
                    // Hide any other UI elements you want to hide in fullscreen
                }).catch(err => {
                    console.log(`Error attempting to enable fullscreen: ${err.message}`);
                });
            } else {
                console.log("Exiting fullscreen");
                document.exitFullscreen();
            }
        });

        // Add browser detection and warning
        window.addEventListener('DOMContentLoaded', () => {
            updateControls();
        });

        // Add tooltip functionality
        function createTooltip() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            document.body.appendChild(tooltip);
            return tooltip;
        }

        function showTooltip(element, tooltip) {
            const message = element.dataset.tooltip;
            if (!message) return;

            tooltip.textContent = message;
            tooltip.classList.add('visible');

            // Position tooltip above the element
            const rect = element.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
        }

        function hideTooltip(tooltip) {
            tooltip.classList.remove('visible');
        }

        // Initialize tooltips
        window.addEventListener('DOMContentLoaded', () => {
            const tooltip = createTooltip();
            const buttons = document.querySelectorAll('button[data-tooltip]');

            buttons.forEach(button => {
                // For touch devices
                button.addEventListener('touchstart', () => {
                    showTooltip(button, tooltip);
                    setTimeout(() => hideTooltip(tooltip), 2000); // Hide after 2 seconds
                });

                // For desktop (optional)
                button.addEventListener('mouseenter', () => showTooltip(button, tooltip));
                button.addEventListener('mouseleave', () => hideTooltip(tooltip));
            });

            // Update controls to use data-tooltip instead of title
            function updateControls() {
                const controls = document.querySelector('.controls');
                const recordBtn = document.getElementById('record');
                const snapshotBtn = document.getElementById('snapshot');
                const fullscreenBtn = document.getElementById('fullscreen-toggle');

                if (!features.webm && !features.mp4) {
                    recordBtn.classList.add('unsupported');
                    recordBtn.dataset.tooltip = 'Recording not supported in this browser';
                }

                if (!features.download && isiOS) {
                    snapshotBtn.dataset.tooltip = 'Snapshots will open in new tab';
                }

                if (!features.fullscreen) {
                    fullscreenBtn.classList.add('unsupported');
                    fullscreenBtn.dataset.tooltip = 'Fullscreen not supported in this browser';
                }
            }
        });

        // Add status box functionality
        function showStatus(message, type = 'info', duration = 3000) {
            console.log(message);
            // No longer updating the removed status elements
        }

        // Function to update the permanent status box
        function updatePermanentStatus() {
            // Completely empty - do not display any status information
        }

        // Call updatePermanentStatus whenever cameras are added or removed
        function toggleCameraSelection(index) {
            if (selectedCameras.has(index)) {
                if (selectedCameras.size <= 1) {
                    console.log('At least one camera must remain selected.');
                    return;
                }
                selectedCameras.delete(index);
                console.log(`Camera ${index + 1} removed from selection.`);
            } else {
                selectedCameras.add(index);
                startCamera(cameras[index].deviceId);
                currentCameraIndex = index;
                console.log(`Camera ${index + 1} selected.`);
            }
            updateCameraList();
        }

        // Ensure permanent status is updated on initialization
        getCameras().then(() => {
            if (cameras.length > 0) {
                selectedCameras.add(0);
                updateCameraList();
                startCamera(cameras[0].deviceId);
            }
        });

        // Add status messages for features
        window.addEventListener('DOMContentLoaded', () => {});

        // Simplify the fullscreen toggle function to make it more reliable
        document.getElementById('fullscreen-toggle').addEventListener('click', function() {
            console.log("Fullscreen toggle clicked");

            // Use simple approach for all devices for consistency
            if (!videoContainer.classList.contains('mobile-fullscreen')) {
                console.log("Entering simplified fullscreen mode");

                // Clean up any existing states first
                if (document.fullscreenElement) {
                    document.exitFullscreen().catch(err => console.log("Error exiting fullscreen:", err));
                }

                // Add our custom fullscreen classes
                videoContainer.classList.add('mobile-fullscreen');
                document.body.classList.add('mobile-fullscreen-body');

                // Make sure camera selection menu is visible
                cameraSelection.classList.remove('hidden');

                // Make sure the video is visible
                mainVideo.style.display = 'block';
                mainVideo.style.objectFit = 'contain';

                // Refresh the buttons to ensure they work
                setupButtonListeners();

                // Ensure controls are visible and positioned correctly
                const controlsEl = document.querySelector('.controls');
                if (controlsEl) {
                    controlsEl.style.display = 'flex';
                    controlsEl.style.position = 'fixed';
                    controlsEl.style.bottom = '20px';
                    controlsEl.style.left = '0';
                    controlsEl.style.width = '100%';
                    controlsEl.style.zIndex = '10000';
                }
            } else {
                console.log("Exiting simplified fullscreen mode");
                videoContainer.classList.remove('mobile-fullscreen');
                document.body.classList.remove('mobile-fullscreen-body');

                // Reset video styles
                mainVideo.style.objectFit = 'cover';

                // Reset controls position
                const controlsEl = document.querySelector('.controls');
                if (controlsEl) {
                    controlsEl.style.position = 'relative';
                }
            }
        });

        // Always use a simplified version of setupButtonListeners
        function setupButtonListeners() {
            console.log('Setting up button event listeners - simplified version');

            // Get direct references to buttons
            const prevBtn = document.getElementById('prev-camera');
            const nextBtn = document.getElementById('next-camera');
            const flipBtn = document.getElementById('flip-video');
            const snapshotBtn = document.getElementById('snapshot');
            const recordBtn = document.getElementById('record');

            if (prevBtn) {
                prevBtn.onclick = function() {
                    console.log('Previous camera button clicked');
                    switchToPreviousCamera();
                };
            }

            if (nextBtn) {
                nextBtn.onclick = function() {
                    console.log('Next camera button clicked');
                    switchToNextCamera();
                };
            }

            if (flipBtn) {
                flipBtn.onclick = function() {
                    console.log('Flip video button clicked');
                    toggleFlipVideo();
                };
            }

            if (snapshotBtn) {
                snapshotBtn.onclick = function() {
                    console.log('Snapshot button clicked');
                    let count = 3;
                    cameraSelection.classList.add('hidden');
                    const countdownInterval = setInterval(() => {
                        showCountdown(count);
                        count--;
                        if (count === 0) {
                            clearInterval(countdownInterval);
                            setTimeout(takeSnapshot, 1000);
                        }
                    }, 1000);
                };
            }

            if (recordBtn) {
                recordBtn.onclick = function() {
                    console.log('Record button clicked');
                    if (!isRecording) {
                        startRecording();
                    } else {
                        stopRecording();
                    }
                };
            }

            // Make sure double click/tap works in all modes
            let lastClickTime = 0;
            videoContainer.addEventListener('click', function(e) {
                const currentTime = new Date().getTime();
                const timeDiff = currentTime - lastClickTime;

                if (timeDiff < 300 && timeDiff > 0) {
                    toggleMenu();
                    e.preventDefault();
                }

                lastClickTime = currentTime;
            });

            let lastTapTime = 0;
            videoContainer.addEventListener('touchend', function(e) {
                const currentTime = new Date().getTime();
                const timeDiff = currentTime - lastTapTime;

                if (timeDiff < 300 && timeDiff > 0) {
                    toggleMenu();
                    e.preventDefault();
                }

                lastTapTime = currentTime;
            });
        }

        // Add these new functions for permission handling
        function showPermissionUI() {
            // Remove any existing permission UI
            const existingUI = document.getElementById('permission-ui');
            if (existingUI) existingUI.remove();

            // Create permission UI
            const permissionUI = document.createElement('div');
            permissionUI.id = 'permission-ui';
            permissionUI.style.position = 'fixed';
            permissionUI.style.top = '50%';
            permissionUI.style.left = '50%';
            permissionUI.style.transform = 'translate(-50%, -50%)';
            permissionUI.style.background = 'rgba(0, 0, 0, 0.8)';
            permissionUI.style.padding = '20px';
            permissionUI.style.borderRadius = '10px';
            permissionUI.style.zIndex = '10000';
            permissionUI.style.textAlign = 'center';
            permissionUI.style.maxWidth = '80%';

            permissionUI.innerHTML = `
        <h3 style="color: white; margin-top: 0;">Camera Access Required</h3>
        <p style="color: white;">Please allow camera access to use this app.</p>
        <button id="retry-permission" style="padding: 10px 20px; background: #0056b3; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Enable Camera
        </button>
    `;

            document.body.appendChild(permissionUI);

            // Add event listener to button
            document.getElementById('retry-permission').addEventListener('click', function() {
                permissionAlreadyAsked = false; // Reset so we can try again
                getCameras().then(() => {
                    if (cameras.length > 0) {
                        selectedCameras.add(0);
                        updateCameraList();
                        startCamera(cameras[0].deviceId);
                    }
                });
            });
        }

        function hidePermissionUI() {
            const permissionUI = document.getElementById('permission-ui');
            if (permissionUI) permissionUI.remove();
        }

        // Add to the top of your script, right after variable declarations
        window.addEventListener('DOMContentLoaded', () => {
            // Auto-initialize if in iframe - this helps with automatic release
            if (isInIframe) {
                console.log("Running in iframe, setting up automatic handling");
                permissionAlreadyAsked = true; // Prevent permission alerts in iframe

                // Auto-initialize camera after a short delay
                setTimeout(() => {
                    getCameras().then(() => {
                        if (cameras.length > 0) {
                            selectedCameras.add(0);
                            updateCameraList();
                            startCamera(cameras[0].deviceId);
                        }
                    });
                }, 1000);
            }
        });

        // Add this function to detect iframe unload and release camera
        if (isInIframe) {
            window.addEventListener('unload', () => {
                closeApp(); // Make sure resources are released
            });

            // Listen for parent frame messages
            window.addEventListener('message', (event) => {
                if (event.data === 'release') {
                    closeApp();
                }
            });
        }

        /**
         * Handles the close button action.
         * First turns off the camera, then attempts to close the tab if possible.
         * If tab can't be closed, it at least ensures the camera is off.
         */
        function handleClose() {
            // First turn off the camera
            if (mainStream) {
                closeApp();
                console.log('Camera turned off.');
            }

            // Then try to close the tab/window (this may not work in all browsers due to security restrictions)
            try {
                // For windows opened via window.open()
                if (window.opener) {
                    window.close();
                } else if (isInIframe) {
                    // If in iframe, send message to parent
                    window.parent.postMessage('close', '*');
                } else {
                    // Show message that we can't close the tab automatically
                    const message = document.createElement('div');
                    message.style.position = 'fixed';
                    message.style.top = '50%';
                    message.style.left = '50%';
                    message.style.transform = 'translate(-50%, -50%)';
                    message.style.background = 'rgba(0, 0, 0, 0.8)';
                    message.style.color = 'white';
                    message.style.padding = '20px';
                    message.style.borderRadius = '10px';
                    message.style.zIndex = '10000';
                    message.style.textAlign = 'center';
                    message.innerHTML = `
                <h3>Camera has been turned off</h3>
                <p>You can now safely close this tab manually.</p>
                <button onclick="this.parentNode.style.display='none'"
                        style="padding: 10px; background: #0056b3; border: none; color: white;
                               border-radius: 5px; margin-top: 10px; cursor: pointer;">
                    OK
                </button>
            `;
                    document.body.appendChild(message);
                }
            } catch (err) {
                console.log('Could not close tab automatically:', err);
            }
        }

        // Modify toggleCamera function to just handle toggling the camera
        function toggleCamera() {
            if (mainStream) {
                closeApp();
                console.log('Camera turned off.');
            } else {
                getCameras().then(() => {
                    if (cameras.length > 0) {
                        selectedCameras.add(0);
                        updateCameraList();
                        startCamera(cameras[0].deviceId);
                        console.log('Camera turned on.');
                    } else {
                        console.log('No cameras found.');
                    }
                }).catch(() => {
                    console.log('Failed to access cameras. Please check permissions.');
                });
            }
        }

        /**
         * Toggles the camera selection menu visibility
         */
        function toggleMenu() {
            console.log('Toggling menu visibility');
            if (cameraSelection.classList.contains('hidden')) {
                cameraSelection.classList.remove('hidden');
            } else {
                cameraSelection.classList.add('hidden');
            }
        }

        // Add double-click detection for desktop
        let lastClickTime = 0;
        videoContainer.addEventListener('click', function(e) {
            const currentTime = new Date().getTime();
            const timeDiff = currentTime - lastClickTime;

            // Detect double click (time between clicks less than 300ms)
            if (timeDiff < 300 && timeDiff > 0) {
                console.log('Double click detected');
                toggleMenu();
                e.preventDefault(); // Prevent any default behavior
            }

            lastClickTime = currentTime;
        });

        // Add double-tap detection for mobile
        let lastTapTime = 0;
        videoContainer.addEventListener('touchend', function(e) {
            const currentTime = new Date().getTime();
            const timeDiff = currentTime - lastTapTime;

            // Detect double tap (time between taps less than 300ms)
            if (timeDiff < 300 && timeDiff > 0) {
                console.log('Double tap detected');
                toggleMenu();
                e.preventDefault(); // Prevent any default behavior
            }

            lastTapTime = currentTime;
        });
    </script>
</body>

</html>
