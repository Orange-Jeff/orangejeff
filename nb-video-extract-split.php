<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <input type="file" id="video-upload" accept="video/*">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f4f4f9;
            width: 550px;
        }

        /* Layout Components */
        .editor-view {
            background: #f4f4f9;
            height: auto;
            margin: 0;
            width: 100%;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .preview-area {
            padding: 15px;
            height: auto;
            background: #f4f4f9;
            display: flex;
            width: 100%;
            flex-direction: column;
            align-items: flex-start;
        }

        /* Video Container */
        #video-container {
            position: relative;
            width: 533px;
            height: 300px;
            margin: 0 0 20px 0;
            padding-left: 0px;
            background: #f4f4f9;
            display: block;
        }

        #video {
            width: 533px;
            height: 300px;
            background: #f4f4f9;
            object-fit: contain;
        }

        #video-upload {
            display: none;
        }

        /* Header Elements */
        .editor-header {
            background: #f4f4f9;
            border-bottom: 1px solid #dee2e6;
        }

        .editor-title {
            margin: 0 0 8px 0;
            color: #0056b3;
            margin-top: 20px;
            line-height: 1.2;
            font-weight: bold;
            font-size: 18px;
        }

        /* Controls and Buttons */
        .button-controls,
        .button-group {
            width: 100%;
            padding: 10px 0;
            display: flex;
            gap: 10px;
            flex-wrap: nowrap;
        }

        /* Button group container */
        .joint-buttons {
            display: flex;
            width: 100%;
        }

        /* Button pair container */
        .button-pair {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .button-pair:first-child {
            margin-right: 10px;
            /* Space after first pair */
        }

        /* Main action button */
        .action-button {
            flex: 3;
            /* Takes more space than clock button */
            background: #0056b3;
            color: white;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            position: relative;
            /* For connecting to clock button */
            z-index: 1;
            /* Ensure borders are over clock button */
        }

        /* Clock button */
        .clock-button {
            flex: 1;
            background: #0056b3;
            color: white;
            border: none;
            border-left: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Button group borders */
        .button-pair .action-button {
            border-top-left-radius: 3px;
            border-bottom-left-radius: 3px;
        }

        .button-pair .clock-button {
            border-top-right-radius: 3px;
            border-bottom-right-radius: 3px;
        }

        /* Hover states */
        .action-button:hover,
        .clock-button:hover {
            background: #004494;
        }

        /* Disabled states */
        .action-button:disabled,
        .clock-button:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }

        .command-button {
            background: #0056b3;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 8px;
            white-space: nowrap;
        }

        .command-button i {
            margin-right: 6px;
        }

        .command-button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* New styles for the done button container */
        .done-button-container {
            width: 100%;
            padding: 10px 0;
            display: flex;
        }

        #btnDone {
            width: 100%;
        }

        /* Status Bar */
        .persistent-status-bar {
            width: 100%;
            height: 84px;
            /* Exactly 3.5 lines at 24px per line */
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

        .status-message {
            margin: 0;
            font-size: 13px;
            color: #666;
            padding: 2px 5px;
            line-height: 24px;
            /* Fixed line height */
            height: 24px;
            /* Fixed height per message */
        }

        .status-message:first-child {
            background: #0056b3;
            color: white;
        }

        .status-message.error:first-child {
            background: #dc3545;
            color: white;
        }

        .status-message.success:first-child {
            background: #28a745;
            color: white;
        }

        .status-message.error:not(:first-child) {
            color: #dc3545;
            background: transparent;
        }

        .status-message.success:not(:first-child) {
            color: #28a745;
            background: transparent;
        }

        /* Log Area */
        #log {
            margin-top: 20px;
            overflow-y: auto;
            width: 533px;
            margin-left: 0px;
        }

        .log-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0;
            background-color: #f4f4f9;
            margin-bottom: 10px;
        }

        .log-item img {
            height: 60px;
            width: 150px;
            margin-right: 15px;
            object-fit: contain;
        }

        .log-item .frame-info {
            flex-grow: 1;
            text-align: left;
        }

        .log-item.divider hr {
            width: 550px;
            border: 2px solid #0056b3;
            margin: 10px 0;
        }

        /* Filename Input */
        .filename-container {
            width: 100%;
            padding: 10px 0;
            display: flex;
            gap: 10px;
        }

        #filename-input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="editor-view">
        <div class="editor-header">
            <div class="header-top">
                <h1 class="editor-title">NetBound Tools: Video Extraction & Split Tool</h1>
            </div>
            <div class="persistent-status-bar" id="statusBar"></div>
            <div class="button-controls">
                <button class="command-button" id="btnOpen"><i class="fas fa-folder-open"></i> Open File</button>
                <button class="command-button" id="btnAbort"><i class="fas fa-stop"></i> Abort</button>
                <button class="command-button" id="btnRestart"><i class="fas fa-redo"></i> Restart</button>
            </div>
        </div>
        <div class="preview-area" id="previewArea">
            <input type="file" id="video-upload" accept="video/*">
            <div id="video-container">
                <video id="video" controls preload="metadata"></video>
            </div>
            <div class="filename-container">
                <input type="text" id="filename-input">
                <button class="command-button" id="rename-video"><i class="fas fa-edit"></i> Rename</button>
            </div>
            <div class="button-group">
                <div class="joint-buttons">
                    <div class="button-pair">
                        <button class="action-button" id="extract-frame-btn">
                            <i class="fas fa-image"></i>
                            Extract Frame
                        </button>
                        <button class="clock-button" id="extract-interval-btn">
                            <i class="fas fa-clock"></i>
                        </button>
                    </div>
                    <div class="button-pair">
                        <button class="action-button" id="split-video-btn">
                            <i class="fas fa-cut"></i>
                            Split Video
                        </button>
                        <button class="clock-button" id="split-interval-btn">
                            <i class="fas fa-clock"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="done-button-container">
                <button class="command-button" id="btnDone" style="justify-content: center;">Done <i class="fas fa-hourglass"></i> Begin Processing</button>
            </div>
            <div class="video-controls"></div>
            <div id="log"></div>
        </div>
        <script>
            const video = document.getElementById('video');
            const videoUpload = document.getElementById('video-upload');
            const log = document.getElementById('log');
            const extractFrameBtn = document.getElementById('extract-frame-btn');
            const extractIntervalBtn = document.getElementById('extract-interval-btn');
            const splitVideoBtn = document.getElementById('split-video-btn');
            const splitIntervalBtn = document.getElementById('split-interval-btn');
            const doneButton = document.getElementById('btnDone');
            const renameButton = document.getElementById('rename-video');
            const btnAbort = document.getElementById('btnAbort');
            const statusBar = document.getElementById('statusBar');
            const btnOpen = document.getElementById('btnOpen');
            const btnRestart = document.getElementById('btnRestart');
            const filenameInput = document.getElementById('filename-input');

            // Global state variables
            let splits = [];
            let baseName = '';
            let processingComplete = false;

            document.addEventListener('DOMContentLoaded', () => {
                setButtonStates(false);
                btnRestart.disabled = false;

                // Basic controls
                btnOpen.onclick = () => videoUpload.click();
                btnAbort.onclick = handleAbort;
                btnAbort.disabled = true;
                videoUpload.addEventListener('change', handleFileChange);
                btnRestart.onclick = handleRestart;
                renameButton.addEventListener('click', handleRename);

                // Frame extraction controls
                extractFrameBtn.addEventListener('click', handleSingleFrameExtract);
                extractIntervalBtn.addEventListener('click', handleIntervalFrameExtract);

                // Video split controls
                splitVideoBtn.addEventListener('click', handleSingleSplit);
                splitIntervalBtn.addEventListener('click', handleIntervalSplit);
            });

            // New handler for single frame extraction
            async function handleSingleFrameExtract() {
                if (!video.src) {
                    updateStatus('No video loaded', 'error');
                    return;
                }
                await extractFrameUsingMP4System(`${baseName}-Frame-${formatTime(video.currentTime)}`, video.currentTime);
                updateStatus(`Frame extracted at ${formatTime(video.currentTime)}`, 'success');
            }

            // New handler for single split
            async function handleSingleSplit() {
                if (!video.src) {
                    updateStatus('No video loaded', 'error');
                    return;
                }

                const currentTime = video.currentTime;
                if (!splits.includes(0)) splits.unshift(0); // Ensure first split is at 0
                splits.push(currentTime);
                splits.sort((a, b) => a - b); // Keep splits in order
                const splitIndex = splits.indexOf(currentTime);

                // Add divider line for visual separation
                const divider = document.createElement('div');
                divider.className = 'log-item divider';
                divider.innerHTML = '<hr style="width:100%; border:0; border-top:2px solid #0056b3; margin:15px 0;">';
                log.appendChild(divider);

                // Add segment header
                const segmentHeader = document.createElement('div');
                segmentHeader.innerHTML = `<div style="color:#0056b3; font-weight:bold; font-size:14px; margin:10px 0; letter-spacing:0.5px;">
                    Split Point ${splitIndex} at ${formatTime(currentTime)}</div>`;
                log.appendChild(segmentHeader);

                // Capture frame at split point
                await captureFrame(`${baseName}-Split${splitIndex}`, currentTime);
                updateStatus(`Split point recorded at ${formatTime(currentTime)}`, 'success');
            }

            function handleFileChange(e) {
                const file = e.target.files[0];
                if (file) {
                    const existingFrames = document.querySelectorAll('.log-item');
                    if (existingFrames.length > 0) {
                        if (confirm('You have unsaved media. Do you wish to clear and load the new file?')) {
                            loadFile(file);
                        }
                    } else {
                        loadFile(file);
                    }
                }
            }

            function setProcessingState(isProcessing) {
                doneButton.innerHTML = isProcessing ? '<i class="fas fa-spinner fa-spin"></i> Processing...' : '<i class="fas fa-hourglass"></i> Begin Processing';
                btnAbort.disabled = !isProcessing;
            }

            function handleAbort() {
                processingComplete = true;
                updateStatus('Processing aborted by user', 'error');
                setProcessingState(false);
                setButtonStates(true);
                btnOpen.disabled = false;
            }



            function handleReset() {
                log.innerHTML = '';
                splits = [];
                video.pause();
                video.src = '';
                video.currentTime = 0;
                filenameInput.value = '';
                processingComplete = false;
            }

            function handleRestart() {
                log.innerHTML = '';
                splits = [];
                video.pause();
                video.src = '';
                video.currentTime = 0;
                filenameInput.value = '';
                processingComplete = false;
                baseName = '';
                setButtonStates(false);
                btnOpen.disabled = false;
                btnRestart.disabled = false;
                updateStatus('Program restarted', 'info');
                location.reload();
            }

            function setButtonStates(isEnabled) {
                [
                    extractFrameBtn,
                    extractIntervalBtn,
                    splitVideoBtn,
                    splitIntervalBtn,
                    renameButton,
                    doneButton,
                    btnOpen
                ].forEach(btn => {
                    if (btn) btn.disabled = !isEnabled;
                });
                btnOpen.disabled = isEnabled;
            }

            function loadFile(file) {
                handleReset();
                baseName = file.name.split('.')[0];
                filenameInput.value = file.name;
                updateStatus('Loading File: ' + file.name, 'info');

                if (file.type.startsWith('video/')) {
                    extractFrameBtn.disabled = false;
                    splitVideoBtn.disabled = false;
                    renameButton.disabled = false;
                    doneButton.disabled = false;

                    if (file.type === 'video/mp4') {
                        handleMp4Upload(file);
                    } else {
                        if (file.type === 'video/webm') {
                            updateStatus('WebM files have unknown duration and do not support video controls.', 'error');
                        }
                        handleVideoUpload(file);
                    }

                    video.addEventListener('loadedmetadata', () => {
                        if (isNaN(video.duration) || video.duration === Infinity) {
                            if (!file.type.includes('webm')) {
                                updateStatus('Video duration unknown. Full playback may be required.', 'warning');
                            }
                        } else {
                            updateStatus(`Video duration detected: ${formatTime(video.duration)}`, 'success');
                        }
                    });
                } else {
                    updateStatus('Unsupported file type', 'error');
                }
            }

            async function extractFrameUsingMP4System(frameNumber, time) {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;

                const videoBlob = await fetch(video.src).then(response => response.blob());
                const videoURL = URL.createObjectURL(videoBlob);
                const tempVideo = document.createElement('video');
                tempVideo.src = videoURL;

                await new Promise(resolve => {
                    tempVideo.addEventListener('loadedmetadata', () => {
                        tempVideo.currentTime = time;
                        tempVideo.addEventListener('seeked', () => {
                            ctx.drawImage(tempVideo, 0, 0, canvas.width, canvas.height);
                            URL.revokeObjectURL(videoURL);
                            resolve();
                        }, {
                            once: true
                        });
                    }, {
                        once: true
                    });
                });

                const previewCanvas = document.createElement('canvas');
                const previewCtx = previewCanvas.getContext('2d');
                previewCanvas.width = 150;
                previewCanvas.height = 60;
                previewCtx.drawImage(canvas, 0, 0, previewCanvas.width, previewCanvas.height);

                canvas.toBlob(blob => {
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = `${frameNumber}.jpg`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(a.href);
                }, 'image/jpeg', 0.95);

                const frameItem = document.createElement('div');
                frameItem.className = 'log-item';
                frameItem.style.display = 'flex';
                frameItem.style.alignItems = 'center';
                frameItem.innerHTML = `
                    <img src="${previewCanvas.toDataURL('image/jpeg', 0.9)}" alt="Extracted Frame" style="margin-right: 15px;">
                    <div class="frame-info">
                        <strong>Extracted Frame</strong><br>
                        Time: ${formatTime(time)}
                    </div>`;
                log.appendChild(frameItem);
            }

            function captureFrame(frameNumber, time) {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                const previewCanvas = document.createElement('canvas');
                const previewCtx = previewCanvas.getContext('2d');
                previewCanvas.width = 150;
                previewCanvas.height = 60;
                previewCtx.drawImage(canvas, 0, 0, previewCanvas.width, previewCanvas.height);

                let label = 'Extracted Frame';
                if (typeof frameNumber === 'string') {
                    if (frameNumber.includes('S')) {
                        const match = frameNumber.match(/S(\d+)/);
                        if (match && match[1]) {
                            const sectionNum = match[1];
                            label = `Section ${sectionNum}: First Frame`;
                        }
                    }
                    if (frameNumber.includes('LastFrame')) {
                        label = 'Last Frame';
                    }
                }

                canvas.toBlob(blob => {
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = `${frameNumber}.jpg`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(a.href);
                }, 'image/jpeg', 0.95);

                const frameItem = document.createElement('div');
                frameItem.className = 'log-item';
                frameItem.style.display = 'flex';
                frameItem.style.alignItems = 'center';
                frameItem.innerHTML = `
                    <img src="${previewCanvas.toDataURL('image/jpeg', 0.9)}" alt="${label}" style="margin-right: 15px;">
                    <div class="frame-info">
                        <strong>${label}</strong><br>
                        Time: ${formatTime(time)}
                    </div>`;
                log.appendChild(frameItem);
            }

            function formatTime(seconds) {
                const minutes = Math.floor(seconds / 60);
                const secs = Math.floor(seconds % 60);
                const tenths = Math.floor((seconds % 1) * 10);
                return `${minutes}:${String(secs).padStart(2, '0')}.${tenths}`;
            }

            function updateStatus(message, type = 'info') {
                const statusMessage = document.createElement('div');
                statusMessage.className = 'status-message' + (type !== 'info' ? ` ${type}` : '');
                statusMessage.textContent = message;
                statusBar.insertBefore(statusMessage, statusBar.firstChild);
                // No longer removing old messages to maintain history
            }

            function normalizeTimeInput(input) {
                try {
                    if (input.includes(':')) {
                        return validateAndConvertTime(input);
                    }
                    const seconds = parseInt(input);
                    if (isNaN(seconds) || seconds < 0) {
                        throw new Error('Invalid time value');
                    }
                    return seconds;
                } catch (error) {
                    throw new Error(`Invalid time format: ${error.message}`);
                }
            }

            function validateAndConvertTime(timeStr) {
                const timeRegex = /^(?:(\d+):)?([0-5]?\d)$/;
                const match = timeStr.trim().match(timeRegex);

                if (!match) {
                    throw new Error('Invalid time format. Use MM:SS or seconds');
                }

                const minutes = parseInt(match[1] || '0');
                const seconds = parseInt(match[2]);

                return (minutes * 60) + seconds;
            }

            async function handleIntervalFrameExtract() {
                if (!video.src) {
                    updateStatus('No video loaded', 'error');
                    return;
                }

                const input = prompt('Enter extraction interval (MM:SS or seconds):', '1:00');
                if (!input) return;

                let intervalSeconds;
                try {
                    intervalSeconds = normalizeTimeInput(input);
                    if (intervalSeconds <= 0) {
                        throw new Error('Interval must be greater than 0');
                    }
                } catch (error) {
                    updateStatus(error.message, 'error');
                    return;
                }

                setProcessingState(true);
                const totalFrames = Math.ceil(video.duration / intervalSeconds);
                let frameCount = 1;

                try {
                    for (let t = 0; t < video.duration; t += intervalSeconds) {
                        if (processingComplete) break;
                        await extractFrameUsingMP4System(`${baseName}-Frame${frameCount}`, t);
                        updateStatus(`Extracted frame ${frameCount} of ${totalFrames}`, 'info');
                        frameCount++;
                    }
                    if (!processingComplete) {
                        updateStatus(`Frame extraction complete: ${frameCount - 1} frames extracted.`, 'success');
                    }
                } finally {
                    setProcessingState(false);
                }
            }

            async function handleIntervalSplit() {
                if (!video.src) {
                    updateStatus('No video loaded', 'error');
                    return;
                }

                const input = prompt('Enter segment interval (MM:SS or seconds):', '1:00');
                if (!input) return;

                let intervalSeconds;
                try {
                    intervalSeconds = normalizeTimeInput(input);
                    if (intervalSeconds <= 0) {
                        throw new Error('Interval must be greater than 0');
                    }
                } catch (error) {
                    updateStatus(error.message, 'error');
                    return;
                }

                setProcessingState(true);
                try {
                    const totalSegments = Math.ceil(video.duration / intervalSeconds);
                    splits = [];
                    for (let segStart = 0; segStart < video.duration; segStart += intervalSeconds) {
                        splits.push(segStart);
                    }

                    for (let i = 0; i < splits.length; i++) {
                        if (processingComplete) break;
                        const startTime = splits[i];
                        const endTime = (i + 1 < splits.length) ? splits[i + 1] : video.duration;
                        updateStatus(`Processing MP4 segment ${i + 1} of ${totalSegments}`, 'info');
                        await processVideoSegment(startTime, endTime, i + 1);
                    }
                    if (!processingComplete) {
                        updateStatus(`MP4 segmentation complete: ${totalSegments} segments processed.`, 'success');
                    }
                } finally {
                    setProcessingState(false);
                }
            }

            function handleVideoUpload(file) {
                video.onloadedmetadata = null;
                video.onloadeddata = null;
                video.onseeked = null;

                const url = URL.createObjectURL(file);
                video.src = url;
                video.load();
                updateStatus('Loading video, please wait...', 'info');

                video.addEventListener('progress', () => {
                    if (video.buffered.length > 0) {
                        const percentLoaded = (video.buffered.end(0) / video.duration) * 100;
                        updateStatus(`Loading: ${Math.round(percentLoaded)}% complete`, 'info');
                    }
                });

                video.addEventListener('canplaythrough', () => {
                    updateStatus('Video loaded successfully.', 'success');
                    setButtonStates(true);
                }, {
                    once: true
                });

                video.addEventListener('error', () => {
                    updateStatus(`Error loading video: ${video.error.message}`, 'error');
                    setButtonStates(false);
                }, {
                    once: true
                });

                video.onloadedmetadata = () => {
                    video.currentTime = 0;
                    video.onseeked = () => {
                        captureFrame(`${baseName}-S1F1`, 0);
                        video.onseeked = null;
                        if (!isNaN(video.duration) && video.duration !== Infinity) {
                            video.currentTime = video.duration;
                            video.onseeked = () => {
                                captureFrame(`${baseName}-LastFrame`, video.duration);
                                video.currentTime = 0;
                                video.onseeked = null;
                            };
                        }
                    };
                };
            }

            function handleMp4Upload(file) {
                video.onloadedmetadata = null;
                video.onloadeddata = null;
                video.onseeked = null;

                const url = URL.createObjectURL(file);
                video.src = url;
                video.load();
                updateStatus('Loading MP4 video, please wait...', 'info');
                let isFullyLoaded = false;

                video.addEventListener('progress', () => {
                    if (video.buffered.length > 0) {
                        const percentLoaded = (video.buffered.end(0) / video.duration) * 100;
                        updateStatus(`Loading MP4: ${Math.round(percentLoaded)}% complete`, 'info');

                        if (percentLoaded >= 99.9) {
                            isFullyLoaded = true;
                            updateStatus('MP4 Video fully buffered', 'success');
                            setButtonStates(true);
                        }
                    }
                });

                video.addEventListener('canplaythrough', () => {
                    if (isFullyLoaded) {
                        updateStatus('MP4 Video ready for processing', 'success');
                    }
                }, {
                    once: true
                });

                video.addEventListener('error', () => {
                    updateStatus(`Error loading MP4: ${video.error.message}`, 'error');
                    setButtonStates(false);
                }, {
                    once: true
                });

                video.onloadedmetadata = () => {
                    updateStatus('Loading video data...', 'info');
                    video.currentTime = 0;
                    video.onseeked = () => {
                        captureFrame(`${baseName}-S1F1`, 0);
                        video.onseeked = null;
                        if (!isNaN(video.duration) && video.duration !== Infinity) {
                            video.currentTime = video.duration;
                            video.onseeked = () => {
                                captureFrame(`${baseName}-LastFrame`, video.duration);
                                video.currentTime = 0;
                                video.onseeked = null;
                            };
                        }
                    };
                };
            }

            async function processMp4Segment(startTime, endTime, segmentNumber) {
                return new Promise(async (resolve) => {
                    try {
                        video.currentTime = startTime;
                        await new Promise(resolve => video.addEventListener('seeked', resolve, {
                            once: true
                        }));
                        captureFrame(`${baseName}-Segment${segmentNumber}-Start`, startTime);

                        const sourceUrl = video.src;
                        const response = await fetch(sourceUrl);
                        const videoBlob = await response.blob();

                        const a = document.createElement('a');
                        a.href = URL.createObjectURL(videoBlob);
                        a.download = `${baseName}-Segment${segmentNumber}.mp4`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(a.href);

                        video.currentTime = endTime;
                        await new Promise(resolve => video.addEventListener('seeked', resolve, {
                            once: true
                        }));
                        captureFrame(`${baseName}-Segment${segmentNumber}-End`, endTime);

                        updateStatus(`MP4 Segment ${segmentNumber} processed`, 'success');
                        resolve();
                    } catch (error) {
                        updateStatus(`Error processing MP4 segment ${segmentNumber}: ${error.message}`, 'error');
                        throw error;
                    }
                });
            }

            async function processVideoSegment(startTime, endTime, segmentNumber) {
                updateStatus(`Processing MP4 segment ${segmentNumber} using native MP4 format`, 'info');
                await processMp4Segment(startTime, endTime, segmentNumber);
            }

            doneButton.addEventListener('click', async () => {
                if (processingComplete) return;
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
                video.pause();
                if (!video.src) {
                    updateStatus('No video loaded', 'error');
                    return;
                }
                if (splits.length < 2) {
                    updateStatus('No split points recorded', 'error');
                    return;
                }

                doneButton.textContent = 'Processing...';
                updateStatus('Starting video processing...', 'info');

                try {
                    // Make sure we have the end point
                    if (splits[splits.length - 1] !== video.duration) {
                        splits.push(video.duration);
                    }

                    // Process all segments
                    const totalSegments = splits.length - 1;
                    for (let i = 0; i < totalSegments; i++) {
                        const startTime = splits[i];
                        const endTime = splits[i + 1];
                        updateStatus(`Processing segment ${i + 1} of ${totalSegments}...`, 'info');
                        await processVideoSegment(startTime, endTime, i + 1);
                    }
                } catch (error) {
                    console.error("An error occurred:", error);
                    updateStatus(`Error: ${error.message}`, 'error');
                } finally {
                    video.pause();
                    updateStatus('Processing Complete. All segments have been downloaded.', 'success');
                    doneButton.textContent = 'Begin Processing';
                    processingComplete = true;
                    video.currentTime = 0;
                    setFinalState();
                }
            });

            function setFinalState() {
                [
                    extractFrameBtn,
                    extractIntervalBtn,
                    splitVideoBtn,
                    splitIntervalBtn,
                    renameButton,
                    doneButton,
                    btnOpen,
                ].forEach(btn => {
                    if (btn) btn.disabled = true;
                });

                filenameInput.disabled = true;
                btnRestart.disabled = false;
            }

            function handleRename() {
                if (!baseName) {
                    updateStatus('No file loaded', 'error');
                    return;
                }

                const newName = filenameInput.value.trim();
                if (!newName) {
                    updateStatus('Please enter a new filename', 'error');
                    return;
                }

                const currentExt = video.currentSrc.toLowerCase().includes('webm') ? 'webm' :
                    video.currentSrc.toLowerCase().includes('mp4') ? 'mp4' :
                    'mp4';

                const newBaseName = newName.includes('.') ? newName.split('.')[0] : newName;
                baseName = newBaseName;
                filenameInput.value = `${newBaseName}.${currentExt}`;
                updateStatus(`Filename updated to: ${filenameInput.value}`, 'success');
            }
        </script>
    </div>
</body>

</html>
