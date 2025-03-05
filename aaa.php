<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

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
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1500;
            background: rgba(0, 0, 0, 0.5);
            padding: 10px;
            border-radius: 8px;
        }

        :fullscreen .camera-selection {
            position: fixed;
            top: 20px;
            z-index: 2000;
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
        }

        .camera-selection {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2000;
            background: rgba(0, 0, 0, 0.8);
            padding: 20px;
            border-radius: 10px;
            max-width: 90%;
            width: 400px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }

        .camera-selection.hidden {
            display: none;
        }

        .camera-list {
            margin: 10px 0;

            .controls {
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                z-index: 2000;
                background: rgba(0, 0, 0, 0.5);
                padding: 10px;
                border-radius: 8px;
            }

            max-height: 350px;
            overflow-y: auto;
            padding-right: 15px;
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
            margin-top: 15px;
            display: flex;
            gap: 7px;
            justify-content: space-between;
        }

        .controls-row {
            display: flex;
            gap: 7px;
            width: 100%;
            justify-content: space-between;
        }

        button {
            padding: 10px;
            font-size: 16px;
            background-color: rgba(0, 150, 255, 0.6);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            touch-action: manipulation;
        }

        button:hover {
            background-color: rgba(0, 150, 255, 0.8);
        }

        /* Add these new styles here */
        button#record,
        button#snapshot,
        button#fullscreen-toggle,
        button#flip-video {
            flex: 1;
        }

        button#prev-camera,
        button#next-camera {
            width: 40px;
            padding: 10px 0;
        }

        button#prev-camera,
        button#next-camera {
            padding: 10px 12px;
            min-width: 40px;
        }

        button#record {
            background-color: rgba(0, 150, 255, 0.6);
            transition: background-color 0.3s;
            font-size: 12px;
        }

        button#record.recording {
            background-color: #e74c3c;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
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

        /* Browser warning style */
        .browser-warning {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 165, 0, 0.9);
            padding: 10px;
            text-align: center;
            z-index: 2000;
            font-size: 14px;
        }

        /* Add these styles */
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

        /* Add status box styles */
        .status-box {
            position: fixed;
            top: 60px;
            /* Adjusted from calc(20px + 400px) */
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.8);
            padding: 15px;
            border-radius: 10px;
            z-index: 2000;
            /* Increased from 1000 */
            width: 90%;
            /* Changed from fixed width for responsiveness */
            max-width: 400px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            transition: opacity 0.3s ease;
            opacity: 0;
            pointer-events: none;
        }

        .status-box.show {
            opacity: 1;
        }

        .status-box.error {
            border-left: 4px solid #e74c3c;
        }

        .status-box.warning {
            border-left: 4px solid #f39c12;
            background-color: rgba(243, 156, 18, 0.9);
            /* Enhanced visibility */
            color: #fff;
        }

        .status-box.info {
            border-left: 4px solid #3498db;
            background-color: rgba(52, 152, 219, 0.9);
            color: #fff;
        }

        .tooltip[data-for="prev-camera"] {
            left: 0 !important;
            transform: translateX(0) !important;
        }

        :fullscreen::before {
            content: "FULLSCREEN MODE ACTIVE";
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: red;
            color: white;
            padding: 5px 10px;
            font-size: 20px;
            z-index: 3000;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div id="video-container"><video id="main-video" autoplay playsinline></video>
        <div class="record-timer" id="record-timer">00:00</div>
        <div class="camera-selection" id="camera-selection">
            <div class="banner"><span>NetBound Tools: Camera Plus</span>
                <button class="close-btn" onclick="toggleCamera()">×</button>
            </div>
            <div class="camera-list" id="camera-list"></div>

            <div class="permanent-status-box" id="permanent-status-box"><span id="camera-status">Active Camera: None</span><span id="selection-status">Cameras Selected: 0</span></div>
            <div class="controls"><button id="prev-camera" data-tooltip="Previous Camera">←</button><button id="record" data-tooltip="Start/Stop Recording">🔴</button><button id="snapshot" data-tooltip="Take Photo">📷</button><button id="flip-video" data-tooltip="Flip Camera">⇄</button><button id="fullscreen-toggle" data-tooltip="Toggle Fullscreen">□</button><button id="next-camera" data-tooltip="Next Camera">→</button></div>
        </div>
        <div class="status-box" id="status-box"></div>
    </div>
    <script>
        // Add browser detection at the start
        const isSafari = /^((? !chrome|android).)*safari/i.test(navigator.userAgent);
        const isiOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

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

        async function getCameras() {
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                cameras = devices.filter(device => device.kind === 'videoinput');
                updateCameraList();
            } catch (err) {
                console.error("Error enumerating devices:", err);
            }
        }

        async function startCamera(deviceId = null) {
            try {
                if (mainStream) {
                    mainStream.getTracks().forEach(track => track.stop());
                }

                if (isRecording) {
                    await stopRecording();
                }

                const constraints = {
                    video: deviceId ? {
                            deviceId: {
                                exact: deviceId
                            }
                        }

                        :
                        true
                }

                ;

                mainStream = await navigator.mediaDevices.getUserMedia(constraints);
                mainVideo.srcObject = mainStream;
                return mainStream;
            } catch (err) {
                console.error("Camera access error:", err);

                // Try fallback to default camera
                if (deviceId) {
                    console.log("Attempting fallback to default camera...");
                    return startCamera(null);
                }

                alert("Camera access failed. Please check permissions.");
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
                return `Camera $ {
                    index+1
                }

                `;
            }

            // Remove 'CameraX Y, facing Front/Back' to 'Front Camera' or 'Back Camera'
            const facingMatch = label.match(/facing\s+(front|back)/i);

            if (facingMatch) {
                return `$ {
                    facingMatch[1].charAt(0).toUpperCase()+facingMatch[1].slice(1)
                }

                Camera`;
            }

            // If no facing info, return the original label
            return label;
        }

        // Modify updateCameraList to use formatted labels
        function updateCameraList() {
            cameraList.innerHTML = '';

            cameras.forEach((camera, index) => {
                const div = document.createElement('div');

                div.className = `camera-option $ {
                        selectedCameras.has(index) ? 'active' : ''
                    }

                    `;
                div.textContent = formatCameraLabel(camera.label, index);
                div.onclick = () => toggleCameraSelection(index);
                cameraList.appendChild(div);
            });
        }

        function toggleCameraSelection(index) {
            if (selectedCameras.has(index)) {
                if (selectedCameras.size <= 1) {
                    showStatus('At least one camera must remain selected.', 'warning');
                    return;
                }

                selectedCameras.delete(index);

                showStatus(`Camera $ {
                        index + 1
                    }

                    removed from selection.`, 'info');
            } else {
                selectedCameras.add(index);
                startCamera(cameras[index].deviceId);
                currentCameraIndex = index;

                showStatus(`Camera $ {
                        index + 1
                    }

                    selected.`, 'info');
            }

            updateCameraList();
            updatePermanentStatus(); // Update permanent status after selection change
        }

        async function switchToNextCamera() {
            const selectedArray = Array.from(selectedCameras);

            if (selectedArray.length > 1) {
                try {
                    const currentIndex = selectedArray.indexOf(currentCameraIndex);
                    const nextIndex = (currentIndex + 1) % selectedArray.length;
                    currentCameraIndex = selectedArray[nextIndex];
                    const success = await startCamera(cameras[currentCameraIndex].deviceId);

                    if (!success) {
                        selectedCameras.delete(currentCameraIndex);
                        showStatus('Failed to switch camera. Camera removed from selection.', 'error');
                    }

                    updateCameraList();
                } catch (err) {
                    console.error("Camera switch failed:", err);
                    showStatus('Failed to switch camera. Please try again.', 'error');
                }
            } else {
                showStatus('Please select at least two cameras to switch between them.', 'warning');
            }
        }

        async function switchToPreviousCamera() {
            const selectedArray = Array.from(selectedCameras);

            if (selectedArray.length > 1) {
                const currentIndex = selectedArray.indexOf(currentCameraIndex);
                const prevIndex = (currentIndex - 1 + selectedArray.length) % selectedArray.length;
                currentCameraIndex = selectedArray[prevIndex];
                await startCamera(cameras[currentCameraIndex].deviceId);
                updateCameraList();
            }
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
                showStatus('No video stream available for recording.', 'error');
                return;
            }

            try {
                const videoTrack = mainVideoStream.getVideoTracks()[0];
                const stream = new MediaStream([videoTrack]);

                const options = {
                    mimeType: 'video/webm;codecs=vp8,opus'
                }

                ;

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
                }

                ;

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

                        a.download = `recording-$ {
                        new Date().toISOString()
                    }

                    .webm`;
                        a.click();
                        URL.revokeObjectURL(url);
                    }

                    showStatus('Recording saved successfully.', 'info');
                }

                ;

                recordingStartTime = Date.now();
                updateRecordingTimer();
                recordingTimer = setInterval(updateRecordingTimer, 1000);
                recordTimer.style.display = 'block';
                mediaRecorder.start();
                recordButton.classList.add('recording');
                isRecording = true;
                showStatus('Recording started.', 'info');
            } catch (err) {
                console.error('Recording failed:', err);
                showStatus('Video recording is not supported in your browser. Please try using Chrome or Firefox.', 'error');
            }
        }

        async function stopRecording() {
            return new Promise((resolve) => {
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    mediaRecorder.addEventListener('stop', () => {
                            resolve();
                        }

                        , {
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

            recordTimer.textContent = `$ {
                minutes
            }

            :$ {
                seconds
            }

            `;
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

                link.download = `snapshot-$ {
                    new Date().toISOString()
                }

                .png`;
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
                }

                , 1000);
        }

        // Debounce function to limit the rate at which a function can fire.
        function debounce(func, wait) {
            let timeout;

            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            }

            ;
        }

        // Updated handleSwipe with debounce
        function handleSwipe() {
            const swipeThreshold = 30; // Reduced threshold for more responsive swipes
            const differenceX = touchStartX - touchEndX;
            const differenceY = touchStartY - touchEndY;

            if (Math.abs(differenceY) > swipeThreshold && Math.abs(differenceY) > Math.abs(differenceX)) {
                if (differenceY > 0) {
                    cameraSelection.classList.add('hidden');
                } else {
                    cameraSelection.classList.remove('hidden');
                }
            } else if (Math.abs(differenceX) > swipeThreshold) {
                if (differenceX > 0) {
                    switchToNextCamera();
                } else {
                    switchToPreviousCamera();
                }
            }
        }

        // Apply debounce to handleSwipe
        const debouncedHandleSwipe = debounce(handleSwipe, 100);

        // Update event listener to use debouncedHandleSwipe
        videoContainer.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            touchEndY = e.changedTouches[0].screenY;
            debouncedHandleSwipe();
        });

        // === CRITICAL CAMERA SWITCHING CODE - DO NOT REMOVE ===
        prevButton.addEventListener('click', switchToPreviousCamera);
        nextButton.addEventListener('click', switchToNextCamera);

        videoContainer.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
            touchStartY = e.changedTouches[0].screenY;
        });

        videoContainer.addEventListener('dblclick', function(event) {
            cameraSelection.classList.toggle('hidden');
        });
        // === END CRITICAL CAMERA CODE ===        });

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
                }

                , 1000);
        });

        document.getElementById('record').addEventListener('click', () => {
            if (!isRecording) {
                startRecording();
            } else {
                stopRecording();
            }
        });

        document.getElementById('fullscreen-toggle').addEventListener('click', () => {
            console.log("Fullscreen toggle clicked");

            if (!document.fullscreenElement) {
                console.log("Requesting fullscreen");

                videoContainer.requestFullscreen().then(() => {
                    console.log("Fullscreen enabled");
                    updateControls();
                    mainVideo.style.objectFit = 'cover'; // Ensure cover is used when entering fullscreen

                    // cameraSelection.classList.add('hidden');
                }).catch(err => {
                    console.log(`Error attempting to enable fullscreen: $ {
                                    err.message
                                }

                                `);
                });
            } else {
                console.log("Exiting fullscreen");
                document.exitFullscreen();
            }
        });

        document.addEventListener('fullscreenchange', () => {
            if (!document.fullscreenElement) {
                mainVideo.style.objectFit = 'cover'; // Ensure
                mainVideo.style.objectFit = 'cover'; // Ensure cover is used when exiting fullscreen
            }
        });

        flipButton.addEventListener('click', toggleFlipVideo);
        prevButton.addEventListener('click', switchToPreviousCamera);
        nextButton.addEventListener('click', switchToNextCamera);

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
                showStatus('Camera turned off.', 'info');
            } else {
                getCameras().then(() => {
                    if (cameras.length > 0) {
                        selectedCameras.add(0);
                        updateCameraList();
                        startCamera(cameras[0].deviceId);
                        showStatus('Camera turned on.', 'info');
                    } else {
                        showStatus('No cameras found.', 'error');
                    }

                }).catch(() => {
                    showStatus('Failed to access cameras. Please check permissions.', 'error');
                });
            }
        }

        // Initialize
        getCameras().then(() => {
            if (cameras.length > 0) {
                selectedCameras.add(0);
                updateCameraList();
                startCamera(cameras[0].deviceId);
                updatePermanentStatus(); // Initial update
            }
        });

        navigator.mediaDevices.getUserMedia({
            video: true

        }).then(initialStream => {
            initialStream.getTracks().forEach(track => track.stop());
            getCameras();

        }).catch(err => {
            console.error("Error requesting initial camera permission:", err);
            alert("Please grant camera permissions to use this app.");
        });

        // Add cleanup on page unload
        window.addEventListener('beforeunload', () => {
            closeApp();
        });

        // Add browser warning if needed
        window.addEventListener('DOMContentLoaded', () => {
            if (isiOS) {
                const warningDiv = document.createElement('div');
                warningDiv.style.position = 'fixed';
                warningDiv.style.top = '0';
                warningDiv.style.width = '100%';
                warningDiv.style.background = 'rgba(255,165,0,0.9)';
                warningDiv.style.padding = '10px';
                warningDiv.style.textAlign = 'center';
                warningDiv.style.zIndex = '2000';
                warningDiv.innerHTML = 'Some features may be limited on iOS. For best experience, use Chrome or Firefox.';
                document.body.appendChild(warningDiv);

                // Auto-hide after 5 seconds
                setTimeout(() => warningDiv.remove(), 5000);
            }
        });

        // Add feature detection
        const features = {
            webm: MediaRecorder && MediaRecorder.isTypeSupported('video/webm'),
            mp4: MediaRecorder && MediaRecorder.isTypeSupported('video/mp4'),
            download: 'download' in document.createElement('a'),
            fullscreen: document.documentElement.requestFullscreen
        }

        ;

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
                    console.log(`Error attempting to enable fullscreen: $ {
                                    err.message
                                }

                                `);
                });
            } else {
                console.log("Exiting fullscreen");
                document.exitFullscreen();
            }
        });

        // Add browser detection and warning
        window.addEventListener('DOMContentLoaded', () => {
            updateControls();

            let warnings = [];

            if (isiOS) {
                warnings.push('Recording not available on iOS');

                if (!features.download) {
                    warnings.push('Downloads will open in new tab');
                }
            }

            if (warnings.length > 0) {
                const warningDiv = document.createElement('div');
                warningDiv.className = 'browser-warning';
                warningDiv.innerHTML = warnings.join('. ');
                document.body.appendChild(warningDiv);
                setTimeout(() => warningDiv.remove(), 5000);
            }
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
            const statusBox = document.getElementById('status-box');
            statusBox.textContent = message;
            statusBox.classList.add('show', type); // Add both 'show' and the message type classes

            clearTimeout(statusBox.timeout);

            statusBox.timeout = setTimeout(() => {
                    statusBox.classList.remove('show', type); // Remove both classes after duration
                }

                , duration);

            updatePermanentStatus();
        }

        // Function to update the permanent status box
        function updatePermanentStatus() {
            const cameraStatus = document.getElementById('camera-status');
            const selectionStatus = document.getElementById('selection-status');

            if (selectedCameras.size > 0 && cameras[currentCameraIndex]) {
                cameraStatus.textContent = `Active Camera: $ {
                    formatCameraLabel(cameras[currentCameraIndex].label, currentCameraIndex)
                }

                `;
            } else {
                cameraStatus.textContent = 'Active Camera: None';
            }

            selectionStatus.textContent = `Cameras Selected: $ {
                selectedCameras.size
            }

            `;
        }

        // Call updatePermanentStatus whenever cameras are added or removed
        function toggleCameraSelection(index) {
            if (selectedCameras.has(index)) {
                if (selectedCameras.size <= 1) {
                    showStatus('At least one camera must remain selected.', 'warning');
                    return;
                }

                selectedCameras.delete(index);

                showStatus(`Camera $ {
                        index + 1
                    }

                    removed from selection.`, 'info');
            } else {
                selectedCameras.add(index);
                startCamera(cameras[index].deviceId);
                currentCameraIndex = index;

                showStatus(`Camera $ {
                        index + 1
                    }

                    selected.`, 'info');
            }

            updateCameraList();
            updatePermanentStatus(); // Update permanent status after selection change
        }

        // Ensure permanent status is updated on initialization
        getCameras().then(() => {
            if (cameras.length > 0) {
                selectedCameras.add(0);
                updateCameraList();
                startCamera(cameras[0].deviceId);
                updatePermanentStatus(); // Initial update
            }
        });

        // Add status messages for features
        window.addEventListener('DOMContentLoaded', () => {
            if (isiOS) {
                showStatus('Some features are limited on iOS. Recording disabled.', 'warning', 5000);
            }

            if (!features.webm && !features.mp4) {
                showStatus('Video recording is not supported in this browser.', 'warning', 5000);
            }
        });
    </script>
</body>

</html>
