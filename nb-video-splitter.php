<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>NetBound Tools: Video Extraction &amp; Split Tool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="shared-styles.css">
    <style>
        /* Essential video-specific styles that don't override shared-styles.css */
        #video {
            width: 100%;
            height: 432px;
            background: #2a2a2a;
            object-fit: contain;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .log-item {
            padding: 8px;
            margin-bottom: 8px;
            background: #f9f9f9;
            border-radius: 4px;
        }

        /* Fix layout issues */
        #log {
            width: 100%;
            margin-top: 15px;
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            background-color: #fff;
            box-sizing: border-box;
        }

        /* Make sure all elements align correctly */
        .preview-area {
            padding: 0;
            margin: 0;
        }

        /* Ensure filename control stretches full width */
        .filename-control {
            width: 100%;
            box-sizing: border-box;
        }

        /* Comment out local #statusBar styling to rely on template’s CSS */
        /*
        #statusBar {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            overflow-y: auto;
        }
        .first-last-container { ... }
        */
    </style>
</head>

<body>
    <div class="menu-container">
        <div class="title-container">
            <h1 class="editor-title">NetBound Tools: Video Extraction &amp; Split Tool</h1>
            <a href="main.php?app=nb-video-splitter.php" class="hamburger-menu" title="Go to Main Menu">
                <i class="fas fa-bars"></i>
            </a>
        </div>

        <div id="statusBar" class="status-box"></div>

        <div class="button-controls">
            <div class="button-row">
                <button class="command-button" id="btnOpen"><i class="fas fa-folder-open"></i> Open File</button>
                <button class="command-button" id="btnRestart"><i class="fas fa-redo"></i> Restart</button>
            </div>
        </div>

        <div class="editor-view">
            <div class="preview-area" id="previewArea">
                <!-- Video with 16:9 aspect ratio like template -->
                <video id="video" controls preload="metadata"></video>

                <!-- Filename control - matched to template -->
                <div class="filename-control">
                    <input type="text" id="filename-input" class="filename-input" placeholder="No file selected">
                    <button class="command-button" id="rename-video"><i class="fas fa-edit"></i> Rename</button>
                </div>

                <div class="button-controls">
                    <div class="button-row">
                        <div class="split-button">
                            <button class="zip-main" id="extract-frame-btn">
                                <i class="fas fa-image"></i> Extract Frame
                            </button>
                            <button class="zip-extra" id="extract-interval-btn">
                                <i class="fas fa-clock"></i>
                            </button>
                        </div>

                        <div class="split-button">
                            <button class="zip-main" id="split-video-btn">
                                <i class="fas fa-cut"></i> Split Video
                            </button>
                            <button class="zip-extra" id="split-interval-btn">
                                <i class="fas fa-clock"></i>
                            </button>
                        </div>
                        <button class="command-button" id="btnDone"><i class="fas fa-hourglass"></i> Begin Processing</button>
                        <button class="command-button" id="btnAbort"><i class="fas fa-stop"></i> Abort</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Log output area -->
    <div id="log"></div>
    </div>
    </div>



    <input type="file" id="video-upload" accept="video/*" style="display: none;">

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
            // 1) Show initial status prompt
            updateStatus('Load or Drag video to open', 'info');

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
                if (isFirstLastPair) {
                    // For both 'first' and 'last', just append one line with small favicon
                    const statusMessage = document.createElement('div');
                    statusMessage.className = 'message success latest';
                    statusMessage.innerHTML = `
                        <img src="${previewCanvas.toDataURL('image/jpeg', 0.7)}" class="frame-favicon" alt="Frame">
                        ${frameLabel} extracted.
                    `;
                    // Remove 'latest' class from previous messages
                    statusBar.querySelectorAll('.message').forEach(msg => msg.classList.remove('latest'));
                    statusBar.appendChild(statusMessage);
                    statusBar.scrollTop = statusBar.scrollHeight;
                } else {
                    // For standard single frame
                    const statusMessage = document.createElement('div');
                    statusMessage.className = 'message success latest';
                    statusMessage.innerHTML = `
                        <img src="${previewCanvas.toDataURL('image/jpeg', 0.7)}" class="frame-favicon" alt="Frame">
                        ${displayLabel} extracted.
                    `;
                    statusBar.querySelectorAll('.message').forEach(msg => msg.classList.remove('latest'));
                    statusBar.appendChild(statusMessage);
                    statusBar.scrollTop = statusBar.scrollHeight;
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
                // 2) Show video loaded message
                updateStatus(`Video (${file.name}) loaded. (00:00) 000mb`, 'info');

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

        // Replace your existing updateStatus function with this
        function updateStatus(message, type = 'info') {
            const statusMessage = document.createElement('div');
            statusMessage.className = `message ${type}`;

            // Add "latest" class to highlight most recent message
            const existingMessages = statusBar.querySelectorAll('.message');
            existingMessages.forEach(msg => {
                msg.classList.remove('latest');
            });

            statusMessage.classList.add('latest');
            statusMessage.textContent = message;
            statusBar.appendChild(statusMessage);

            // Scroll to bottom to show latest message
            statusBar.scrollTop = statusBar.scrollHeight;

            // Limit history to prevent excessive DOM nodes
            if (statusBar.childElementCount > 20) {
                statusBar.removeChild(statusBar.firstChild);
            }

            return statusMessage.id;
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

                            // 3) After both frames extracted
                            updateStatus('First and Last Frames extracted with both thumbnails', 'success');

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
                updateStatus(`Error loading video: ${video.error?.message || 'Unknown error'}`, 'error');

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
        } else {
            // No special class needed for standalone
        }
    </script>
    </div>
</body>

</html>
