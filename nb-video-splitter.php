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
            width: 768px;
            margin: 0 auto;
            /* Center the body in standalone mode */
        }

        /* In iframe, use left alignment */
        body.in-iframe {
            margin: 0;
        }

        /* Layout Components */
        .editor-view {
            background: #f4f4f9;
            height: auto;
            margin: 0;
            max-width: 768px;
            width: 100%;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .preview-area {
            margin-top: 10px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        /* Video Container */
        #video-container {
            position: relative;
            width: 100%;
            height: 432px;
            /* 16:9 aspect ratio for 768px width */
            margin: 0;
            background: #2a2a2a;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        #video-upload {
            display: none;
        }

        /* Header Elements */
        .editor-header {
            background: #f4f4f9;
            border-bottom: 1px solid #dee2e6;
            padding: 0 0 10px 0;
            width: 100%;
        }

        .editor-title {
            margin: 10px 0;
            color: #0056b3;
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
        }

        /* Main action button */
        .action-button {
            flex: 3;
            background: #0056b3;
            color: white;
            border: none;
            padding: 6px 8px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-top-left-radius: 3px;
            border-bottom-left-radius: 3px;
        }

        /* Clock button */
        .clock-button {
            flex: 1;
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

        /* Done button container */
        .done-button-container {
            width: 100%;
            padding: 10px 0;
            display: flex;
        }

        #btnDone {
            width: 100%;
            justify-content: center;
        }

        /* Status Bar */
        .persistent-status-bar {
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

        /* Log Area */
        #log {
            margin-top: 20px;
            overflow-y: auto;
            width: 100%;
            margin-left: 0;
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
            width: 100%;
            border: 0;
            border-top: 2px solid #0056b3;
            margin: 10px 0;
            padding: 0;
        }

        /* Filename Input */
        .filename-container {
            width: 100%;
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }

        #filename-input {
            flex: 1;
            padding: 6px 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-family: inherit;
            font-size: 14px;
        }

        /* Apply consistent box-sizing to all elements */
        * {
            box-sizing: border-box;
        }

        /* Add this to your existing CSS */
        .first-last-container {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin-bottom: 10px;
            background: #f4f4f9;
            padding: 10px;
            border-radius: 4px;
        }

        .first-last-frame {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 48%;
        }

        .first-last-frame img {
            width: 100%;
            height: auto;
            object-fit: contain;
            border: 1px solid #ddd;
        }

        .frame-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            text-align: center;
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
            let isProcessing = false;

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

                // Add a direct way to get first/last frames without processing
                doneButton.addEventListener('click', handleDoneButtonClick);
            });

            // Combined frame extraction function to reduce code duplication
            async function captureAndSaveFrame(frameLabel, time, saveToFile = true, skipLogEntry = false, isFirstLastPair = false) {
                // Ensure video is at the correct position
                if (video.currentTime !== time) {
                    video.currentTime = time;
                    await new Promise(resolve => {
                        video.addEventListener('seeked', resolve, {
                            once: true
                        });
                    });
                }

                // Create main canvas for full resolution capture
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                // Create smaller preview canvas
                const previewCanvas = document.createElement('canvas');
                const previewCtx = previewCanvas.getContext('2d');
                previewCanvas.width = 150;
                previewCanvas.height = 60;
                previewCtx.drawImage(canvas, 0, 0, previewCanvas.width, previewCanvas.height);

                // Determine label for display and filename
                let displayLabel = 'Extracted Frame';
                let filename = '';

                // Updated label interpretation for S#F# format
                if (typeof frameLabel === 'string') {
                    if (frameLabel.includes('S') && frameLabel.includes('F')) {
                        // Extract section and frame numbers
                        const sectionMatch = frameLabel.match(/S(\d+)/i);
                        const frameMatch = frameLabel.match(/F(\d+)/i);

                        if (sectionMatch && sectionMatch[1]) {
                            const sectionNum = sectionMatch[1];
                            const frameNum = frameMatch && frameMatch[1] ? frameMatch[1] : '';

                            if (frameLabel.toLowerCase().includes('last')) {
                                displayLabel = `Section ${sectionNum}: Last Frame`;
                                filename = `${baseName}_S${sectionNum}_Last`;
                            } else if (frameNum) {
                                displayLabel = `Section ${sectionNum}: Frame ${frameNum}`;
                                filename = `${baseName}_S${sectionNum}F${frameNum}`;
                            } else {
                                displayLabel = `Section ${sectionNum} Frame`;
                                filename = `${baseName}_S${sectionNum}`;
                            }
                        }
                    } else if (frameLabel.toLowerCase().includes('first')) {
                        displayLabel = 'First Frame';
                        filename = `${baseName}_First`;
                    } else if (frameLabel.toLowerCase().includes('last')) {
                        displayLabel = 'Last Frame';
                        filename = `${baseName}_Last`;
                    }
                }

                // If no custom filename was set, use the original frameLabel with basename
                if (!filename) {
                    filename = `${baseName}_${frameLabel}`;
                }

                // Save file if requested
                if (saveToFile) {
                    canvas.toBlob(blob => {
                        const a = document.createElement('a');
                        a.href = URL.createObjectURL(blob);
                        a.download = `${filename}.jpg`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(a.href);
                    }, 'image/jpeg', 0.95);
                }

                // Add to log display only if not skipping log entry
                if (!skipLogEntry) {
                    if (isFirstLastPair === 'first') {
                        // Store for later when we have both frames
                        window.firstFrameData = {
                            label: displayLabel,
                            time: time,
                            dataUrl: previewCanvas.toDataURL('image/jpeg', 0.9)
                        };
                    } else if (isFirstLastPair === 'last') {
                        // Create side-by-side container for first and last frames
                        if (window.firstFrameData) {
                            const framesContainer = document.createElement('div');
                            framesContainer.className = 'first-last-container';

                            // First frame
                            const firstFrame = document.createElement('div');
                            firstFrame.className = 'first-last-frame';
                            firstFrame.innerHTML = `
                                <img src="${window.firstFrameData.dataUrl}" alt="First Frame">
                                <div class="frame-label">First Frame (${formatTime(window.firstFrameData.time)})</div>
                            `;

                            // Last frame
                            const lastFrame = document.createElement('div');
                            lastFrame.className = 'first-last-frame';
                            lastFrame.innerHTML = `
                                <img src="${previewCanvas.toDataURL('image/jpeg', 0.9)}" alt="Last Frame">
                                <div class="frame-label">Last Frame (${formatTime(time)})</div>
                            `;

                            framesContainer.appendChild(firstFrame);
                            framesContainer.appendChild(lastFrame);
                            log.appendChild(framesContainer);

                            // Clear the stored first frame
                            window.firstFrameData = null;
                        }
                    } else {
                        // Standard single frame display
                        const frameItem = document.createElement('div');
                        frameItem.className = 'log-item';
                        frameItem.style.display = 'flex';
                        frameItem.style.alignItems = 'center';
                        frameItem.innerHTML = `
                            <img src="${previewCanvas.toDataURL('image/jpeg', 0.9)}" alt="${displayLabel}" style="margin-right: 15px;">
                            <div class="frame-info">
                                <strong>${displayLabel}</strong><br>
                                Time: ${formatTime(time)}
                            </div>`;
                        log.appendChild(frameItem);
                    }
                }

                updateStatus(`${displayLabel} at ${formatTime(time)}`, 'success');
                return canvas; // Return the canvas in case it's needed
            }

            // Handle single frame extraction
            async function handleSingleFrameExtract() {
                if (!video.src) {
                    updateStatus('No video loaded', 'error');
                    return;
                }

                // Get the nearest section number based on time
                const currentTime = video.currentTime;
                let sectionNumber = 1; // Default

                if (splits.length > 0) {
                    // Find which section we're in
                    for (let i = 0; i < splits.length; i++) {
                        if (currentTime >= splits[i] && (i === splits.length - 1 || currentTime < splits[i + 1])) {
                            sectionNumber = i + 1;
                            break;
                        }
                    }
                }

                // Determine frame number within this section
                // For manual extractions, use incrementing numbers
                if (!window.sectionFrameCounts) {
                    window.sectionFrameCounts = {};
                }

                if (!window.sectionFrameCounts[sectionNumber]) {
                    window.sectionFrameCounts[sectionNumber] = 1;
                } else {
                    window.sectionFrameCounts[sectionNumber]++;
                }

                const frameNumber = window.sectionFrameCounts[sectionNumber];

                await captureAndSaveFrame(`S${sectionNumber}F${frameNumber}`, currentTime);
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

            function setProcessingState(isActive) {
                isProcessing = isActive;
                doneButton.innerHTML = isActive ? '<i class="fas fa-spinner fa-spin"></i> Processing...' : '<i class="fas fa-hourglass"></i> Begin Processing';
                btnAbort.disabled = !isActive;

                // Disable other controls during processing
                extractFrameBtn.disabled = isActive;
                extractIntervalBtn.disabled = isActive;
                splitVideoBtn.disabled = isActive;
                splitIntervalBtn.disabled = isActive;
                renameButton.disabled = isActive;
                btnOpen.disabled = isActive;
            }

            function handleAbort() {
                if (!isProcessing) return;
                processingComplete = true;
                updateStatus('Processing aborted by user', 'error');
                setProcessingState(false);
                setButtonStates(true);
            }

            function handleReset() {
                log.innerHTML = '';
                splits = [];
                video.pause();
                video.src = '';
                video.currentTime = 0;
                filenameInput.value = '';
                processingComplete = false;
                isProcessing = false;
            }

            function handleRestart() {
                try {
                    // Either do a clean reload or reset state, not both
                    if (confirm('Restart the application?')) {
                        // Option 1: Just reload without trying to reset state first
                        location.reload();
                        return;
                    }

                    // Option 2: Reset everything manually without reload
                    handleReset();
                    baseName = '';
                    setButtonStates(false);
                    btnOpen.disabled = false;
                    btnRestart.disabled = false;
                    updateStatus('Program restarted', 'info');
                } catch (error) {
                    console.error("Restart error:", error);
                    updateStatus(`Error during restart: ${error.message}`, 'error');
                }
            }

            function setButtonStates(isEnabled) {
                [
                    extractFrameBtn,
                    extractIntervalBtn,
                    splitVideoBtn,
                    splitIntervalBtn,
                    renameButton,
                    doneButton
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
                    if (file.type === 'video/mp4') {
                        handleMp4Upload(file);
                    } else {
                        updateStatus('Unsupported file type. Only MP4 is currently supported.', 'error');
                    }

                    video.addEventListener('loadedmetadata', () => {
                        if (isNaN(video.duration) || video.duration === Infinity) {
                            updateStatus('Video duration unknown. Full playback may be required.', 'warning');
                        } else {
                            updateStatus(`Video duration detected: ${formatTime(video.duration)}`, 'success');
                        }
                    });
                } else {
                    updateStatus('Unsupported file type. Please select a video file.', 'error');
                }
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

                // Limit history to prevent excessive DOM nodes
                if (statusBar.childElementCount > 20) {
                    statusBar.removeChild(statusBar.lastChild);
                }
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
                processingComplete = false;

                // Reset section frame counts
                window.sectionFrameCounts = {};

                try {
                    // If no splits exist, consider the whole video as section 1
                    if (splits.length < 2) {
                        splits = [0, video.duration];
                    }

                    // Go through each section
                    for (let sectionIdx = 0; sectionIdx < splits.length - 1 && !processingComplete; sectionIdx++) {
                        const sectionStart = splits[sectionIdx];
                        const sectionEnd = splits[sectionIdx + 1];
                        const sectionNumber = sectionIdx + 1;

                        let frameCount = 1;

                        // Extract frames within this section at regular intervals
                        for (let t = sectionStart; t < sectionEnd && !processingComplete; t += intervalSeconds) {
                            await captureAndSaveFrame(`S${sectionNumber}F${frameCount}`, t);
                            updateStatus(`Extracted S${sectionNumber}F${frameCount} at ${formatTime(t)}`, 'info');
                            frameCount++;
                        }
                    }

                    if (!processingComplete) {
                        updateStatus(`Frame extraction complete.`, 'success');
                    }
                } catch (error) {
                    updateStatus(`Error during frame extraction: ${error.message}`, 'error');
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
                processingComplete = false;

                try {
                    // Generate split points at regular intervals
                    splits = [0]; // Start with 0
                    for (let t = intervalSeconds; t < video.duration; t += intervalSeconds) {
                        splits.push(t);
                    }
                    if (splits[splits.length - 1] < video.duration - 1) {
                        splits.push(video.duration); // Add end point if needed
                    }

                    const totalSegments = splits.length - 1;
                    updateStatus(`Created ${totalSegments} split points`, 'info');

                    // Reset section frame counts
                    window.sectionFrameCounts = {};

                    // Visualize split points
                    for (let i = 0; i < splits.length && !processingComplete; i++) {
                        const splitTime = splits[i];
                        const sectionNumber = i + 1;

                        // Add visual divider for each split
                        const divider = document.createElement('div');
                        divider.className = 'log-item divider';
                        divider.innerHTML = '<hr style="width:100%; border:0; border-top:2px solid #0056b3; margin:15px 0;">';
                        log.appendChild(divider);

                        // Add split header
                        const segmentHeader = document.createElement('div');
                        segmentHeader.innerHTML = `<div style="color:#0056b3; font-weight:bold; font-size:14px; margin:10px 0; letter-spacing:0.5px;">
                            Split Point: Section ${sectionNumber} at ${formatTime(splitTime)}</div>`;
                        log.appendChild(segmentHeader);

                        // Use appropriate frame naming
                        const isLast = i === splits.length - 1;
                        const frameName = isLast ? `S${i}-Last` : `S${sectionNumber}F1`;

                        // Capture frame at split point
                        await captureAndSaveFrame(frameName, splitTime);
                    }

                    if (!processingComplete) {
                        updateStatus(`Split points created successfully. Click "Done" to process all segments.`, 'success');
                    }
                } catch (error) {
                    updateStatus(`Error creating split points: ${error.message}`, 'error');
                } finally {
                    setProcessingState(false);
                }
            }

            function handleMp4Upload(file) {
                const url = URL.createObjectURL(file);
                video.src = url;
                video.load();
                updateStatus('Loading video, please wait...', 'info');

                video.addEventListener('error', () => {
                    updateStatus(`Error loading video: ${video.error?.message || 'Unknown error'}`, 'error');
                    URL.revokeObjectURL(url);
                    setButtonStates(false);
                }, {
                    once: true
                });

                video.onloadedmetadata = () => {
                    video.currentTime = 0;
                    video.onseeked = async () => {
                        // Reset section frame counts
                        window.sectionFrameCounts = {};

                        // Capture first frame with special handling
                        await captureAndSaveFrame('First', 0, true, false, 'first');

                        if (!isNaN(video.duration) && video.duration !== Infinity) {
                            video.currentTime = video.duration - 0.1; // Slightly before end to ensure frame is available
                            video.onseeked = async () => {
                                // Capture last frame with special handling to create side-by-side display
                                await captureAndSaveFrame('Last', video.currentTime, true, false, 'last');

                                // Add another divider after the first/last frame pair
                                const endDivider = document.createElement('div');
                                endDivider.className = 'log-item divider';
                                endDivider.innerHTML = '<hr style="width:100%; border:0; border-top:2px solid #0056b3; margin:15px 0;">';
                                log.appendChild(endDivider);

                                video.currentTime = 0;
                                video.onseeked = null;
                                updateStatus('Video loaded and ready. First and last frames extracted.', 'success');
                                setButtonStates(true);

                                // Initialize splits with first and last points
                                splits = [0, video.duration];
                            };
                        } else {
                            updateStatus('Video loaded but duration unknown.', 'warning');
                            setButtonStates(true);

                            // Initialize splits with first point
                            splits = [0];
                        }
                    };
                };
            }

            // Improved segment processing with proper time slicing
            async function processVideoSegment(startTime, endTime, segmentNumber) {
                updateStatus(`Processing segment ${segmentNumber}...`, 'info');

                try {
                    // Avoid duplicating frames in the log during final processing
                    const skipLogEntry = true;

                    // Capture start frame
                    video.currentTime = startTime;
                    await new Promise(resolve => video.addEventListener('seeked', resolve, {
                        once: true
                    }));
                    await captureAndSaveFrame(`S${segmentNumber}F1`, startTime, true, skipLogEntry);

                    // Extract segment using MediaSource API (limited browser support)
                    // Note: This is a simplified approach - for true video splitting,
                    // server-side processing would be more reliable

                    // For now, we'll use the download approach but with a disclaimer
                    updateStatus(`Note: Currently downloading full video file. Server-side processing needed for true splitting.`, 'warning');

                    const response = await fetch(video.src);
                    const videoBlob = await response.blob();

                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(videoBlob);
                    a.download = `${baseName}-S${segmentNumber}.mp4`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(a.href);

                    // Capture end frame
                    video.currentTime = endTime;
                    await new Promise(resolve => video.addEventListener('seeked', resolve, {
                        once: true
                    }));

                    // If this is the last segment, mark it as last frame
                    const isLastSegment = segmentNumber === splits.length - 1;
                    const frameName = isLastSegment ? `S${segmentNumber}-Last` : `S${segmentNumber}F2`;

                    await captureAndSaveFrame(frameName, endTime, true, skipLogEntry);

                    updateStatus(`Segment ${segmentNumber} processed`, 'success');
                } catch (error) {
                    updateStatus(`Error processing segment ${segmentNumber}: ${error.message}`, 'error');
                    throw error;
                }
            }

            async function handleDoneButtonClick() {
                if (processingComplete || isProcessing) return;

                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
                video.pause();

                if (!video.src) {
                    updateStatus('No video loaded', 'error');
                    return;
                }

                // Check if we have any split points beyond the initial capture
                const hasSplitPoints = splits.length >= 2;

                if (!hasSplitPoints) {
                    // Just finish with the first and last frames that are already captured
                    updateStatus('Processing complete with first and last frames only.', 'success');
                    processingComplete = true;
                    setFinalState();
                    return;
                }

                setProcessingState(true);
                processingComplete = false;
                updateStatus('Starting video processing with split points...', 'info');

                try {
                    // Make sure we have the end point
                    if (splits[splits.length - 1] !== video.duration) {
                        splits.push(video.duration);
                        splits.sort((a, b) => a - b); // Re-sort to ensure order
                    }

                    // Process all segments
                    const totalSegments = splits.length - 1;
                    for (let i = 0; i < totalSegments && !processingComplete; i++) {
                        const startTime = splits[i];
                        const endTime = splits[i + 1];
                        await processVideoSegment(startTime, endTime, i + 1);
                        updateStatus(`Completed segment ${i + 1} of ${totalSegments}`, 'info');
                    }

                    if (!processingComplete) {
                        updateStatus('Processing Complete! All segments have been downloaded.', 'success');
                    }
                } catch (error) {
                    console.error("An error occurred:", error);
                    updateStatus(`Error: ${error.message}`, 'error');
                } finally {
                    video.pause();
                    processingComplete = true;
                    video.currentTime = 0;
                    setProcessingState(false);
                    setFinalState();
                }
            }

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
                updateStatus('Processing completed. Click "Restart" to process another video.', 'success');
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
                    video.currentSrc.toLowerCase().includes('mp4') ? 'mp4' : 'mp4';

                const newBaseName = newName.includes('.') ? newName.split('.')[0] : newName;
                baseName = newBaseName;
                filenameInput.value = `${newBaseName}.${currentExt}`;
                updateStatus(`Filename updated to: ${filenameInput.value}`, 'success');
            }

            // Handle single split
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
                const sectionNumber = splitIndex + 1;

                // Add divider line for visual separation
                const divider = document.createElement('div');
                divider.className = 'log-item divider';
                divider.innerHTML = '<hr style="width:100%; border:0; border-top:2px solid #0056b3; margin:15px 0;">';
                log.appendChild(divider);

                // Add segment header
                const segmentHeader = document.createElement('div');
                segmentHeader.innerHTML = `<div style="color:#0056b3; font-weight:bold; font-size:14px; margin:10px 0; letter-spacing:0.5px;">
                    Split Point: Section ${sectionNumber} at ${formatTime(currentTime)}</div>`;
                log.appendChild(segmentHeader);

                // Capture frame at split point - use section number in the name
                await captureAndSaveFrame(`S${sectionNumber}F1`, currentTime);

                // Reset section frame counts when adding new splits
                window.sectionFrameCounts = {};
            }

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
        </script>
    </div>
</body>

</html>
