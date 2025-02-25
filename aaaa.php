
<?php
$toolName = 'Image Extraction Tool';
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

/*
// NetBound Tools - Netbound.ca
// Filename: nb-image-extraction.php
// Written by Orange Jeff and Cody AI tools
// Last saved:
// 
// Dependants: none
*/

?>

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
            gap: 8px;
            padding: 8px 12px;
        }

        .command-button i {
            margin-right: 6px;
        }

        .command-button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* Status Bar */
        .persistent-status-bar {
            width: 100%;
            min-height: 100px;
            max-height: 100px;
            overflow-y: auto;
            border: 1px solid #ddd;
            background: #fff;
            padding: 5px;
            margin: 10px 0;
            border-radius: 4px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column-reverse;
        }

        .status-message {
            margin: 1px 0;
            font-size: 14px;
            color: #333;
        }

        .status-message:first-child {
            background: #0056b3;
            color: white;
            padding: 5px;
        }

        .status-message.error {
            background: #dc3545;
            color: white;
            padding: 5px;
        }

        .status-message.success {
            background: #28a745;
            color: white;
            padding: 5px;
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
        }

        #filename-input {
            width: 100%;
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
                <h1 class="editor-title">NetBound Tools: <?php echo $toolName; ?></h1>
            </div>
            <div class="persistent-status-bar" id="statusBar"></div>
            <div class="button-controls">
                <button class="command-button" id="btnOpen"><i class="fas fa-folder-open"></i> Open File</button>
                <button class="command-button" id="btnReset"><i class="fas fa-redo"></i> Reset</button>
            </div>

        </div>
        <div class="preview-area" id="previewArea">
            <input type="file" id="video-upload" accept="video/*">
            <div id="video-container">
                <video id="video" controls preload="metadata"></video>
            </div>
            <div class="filename-container">
                <input type="text" id="filename-input">
            </div>
            <div class="button-group">
                <button class="command-button extract-frame" id="extract-frame"><i class="fas fa-camera"></i> Extract Frame</button>
                <button class="command-button" id="split-video"><i class="fas fa-cut"></i> Split Here</button>
                <button class="command-button" id="rename-video"><i class="fas fa-edit"></i> Rename</button>
                <button class="command-button" id="btnDone"><i class="fas fa-save"></i> Done</button>
            </div>
            <div class="video-controls"></div>
            <div id="log"></div>
        </div>
        <div id="log"></div>
        <script>
            const video = document.getElementById('video');
            const videoUpload = document.getElementById('video-upload');
            const log = document.getElementById('log');
            const extractButton = document.getElementById('extract-frame');
            const doneButton = document.getElementById('btnDone');
            const splitButton = document.getElementById('split-video');
            const renameButton = document.getElementById('rename-video');
            const statusBar = document.getElementById('statusBar');
            const btnOpen = document.getElementById('btnOpen');
            const btnReset = document.getElementById('btnReset');
            const filenameInput = document.getElementById('filename-input');

            // Global state variables
            let splits = [];
            let baseName = '';
            let processingComplete = false;

            document.addEventListener('DOMContentLoaded', () => {
                // Initialize buttons
                setButtonStates(false);
                btnReset.disabled = false;
                addIntervalSplitButton();

                // Attach event listeners
                btnOpen.onclick = () => videoUpload.click();
                videoUpload.addEventListener('change', handleFileChange);
                btnReset.onclick = handleReset;

                renameButton.addEventListener('click', () => {
                    if (baseName) {
                        const newName = filenameInput.value.trim();
                        const originalExt = video.src.split('.').pop();
                        const newExt = newName.split('.').pop();

                        if (!newName.includes('.')) {
                            filenameInput.value = `${newName}.${originalExt}`;
                            baseName = newName;
                        } else if (newExt !== originalExt) {
                            filenameInput.value = `${newName.split('.')[0]}.${originalExt}`;
                            baseName = newName.split('.')[0];
                        } else {
                            baseName = newName.split('.')[0];
                        }
                        updateStatus('Filename updated: ' + filenameInput.value, 'success');
                    } else {
                        updateStatus('No file loaded', 'error');
                    }
                });
            });

            // Add these handler functions
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

            function handleReset() {
                log.innerHTML = '';
                splits = [];
                video.pause();
                video.src = '';
                video.currentTime = 0;
                setButtonStates(false);
                filenameInput.value = '';
                processingComplete = false;
                updateStatus('Reset complete', 'info');
            }

            function setButtonStates(isEnabled) {
                [extractButton, splitButton, renameButton, doneButton, btnOpen].forEach(btn =>
                    btn.disabled = !isEnabled
                );
                btnOpen.disabled = isEnabled; // Invert for Open button - disable when video loaded
            }
            // Add this after other button declarations
            function addIntervalSplitButton() {
                const buttonGroup = document.querySelector('.button-group');
                if (!buttonGroup) {
                    console.error('Button group not found');
                    return;
                }

                // Remove existing interval button if present
                const existingButton = document.getElementById('interval-split');
                if (existingButton) {
                    existingButton.remove();
                }

                const intervalButton = document.createElement('button');
                intervalButton.className = 'command-button';
                intervalButton.id = 'interval-split';
                intervalButton.innerHTML = '<i class="fas fa-clock"></i> Interval Split';
                buttonGroup.appendChild(intervalButton);

                intervalButton.addEventListener('click', handleIntervalSplit);
            }

            async function handleIntervalSplit() {
                try {
                    const input = prompt('Enter interval (MM:SS or seconds):', '1:00');
                    if (!input) return;

                    const intervalSeconds = normalizeTimeInput(input);
                    if (intervalSeconds <= 0) {
                        throw new Error('Interval must be greater than 0');
                    }

                    createIntervalSplits(intervalSeconds);
                } catch (error) {
                    updateStatus(error.message, 'error');
                }
            }

            function createIntervalSplits(intervalSeconds) {
                if (!video.duration || isNaN(video.duration)) {
                    updateStatus('Video duration not available', 'error');
                    return;
                }

                splits.length = 0; // Clear existing splits
                const duration = video.duration;
                let currentTime = intervalSeconds;

                while (currentTime < duration) {
                    splits.push(currentTime);
                    // Preview frame at split point
                    captureFrame(`${baseName}-Split${splits.length}`, currentTime);
                    currentTime += intervalSeconds;
                }

                updateStatus(`Created ${splits.length} splits at ${formatTime(intervalSeconds)} intervals`, 'success');
            }
            // Initialize button states
            setButtonStates(false);
            btnReset.disabled = false;

            function validateAndConvertTime(timeStr) {
                // Handle both "MM:SS" and seconds-only format
                const timeRegex = /^(?:(\d+):)?([0-5]?\d)$/;
                const match = timeStr.trim().match(timeRegex);

                if (!match) {
                    throw new Error('Invalid time format. Use MM:SS or seconds');
                }

                const minutes = parseInt(match[1] || '0');
                const seconds = parseInt(match[2]);

                return (minutes * 60) + seconds;
            }

            function normalizeTimeInput(input) {
                try {
                    // If input contains ":", treat as MM:SS
                    if (input.includes(':')) {
                        return validateAndConvertTime(input);
                    }
                    // Otherwise treat as seconds
                    const seconds = parseInt(input);
                    if (isNaN(seconds) || seconds < 0) {
                        throw new Error('Invalid time value');
                    }
                    return seconds;
                } catch (error) {
                    throw new Error(`Invalid time format: ${error.message}`);
                }
            }
            // Unified loader: check file type and load via the appropriate handler.
            function loadFile(file) {
                btnReset.click();
                baseName = file.name.split('.')[0];
                filenameInput.value = file.name;
                updateStatus('Loading File: ' + file.name, 'info');

                if (file.type.startsWith('video/')) {
                    extractButton.disabled = false;
                    splitButton.disabled = false;
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
                                updateStatus('Webm Video duration unknown. Full playback may be required.', 'warning');
                            }
                        } else {
                            updateStatus(`Video duration detected: ${formatTime(video.duration)}`, 'success');
                        }
                    });
                } else {
                    updateStatus('Unsupported file type', 'error');
                }
            }
            // Video handling (e.g., MP4).
            function handleVideoUpload(file) {
                // Remove existing event listeners to prevent issues with multiple loads.
                video.onloadedmetadata = null;
                video.onloadeddata = null;
                video.onseeked = null;

                const url = URL.createObjectURL(file);
                video.src = url;
                video.load();
                updateStatus('Video loaded successfully.', 'success');
                setButtonStates(true);

                video.onloadedmetadata = () => {
                    video.currentTime = 0;

                    video.onseeked = () => {
                        captureFrame(`${baseName}-S1F1`, 0);
                        video.onseeked = null;

                        // Handle last frame capture (as before)
                        if (!isNaN(video.duration) && video.duration !== Infinity) {
                            video.currentTime = video.duration;
                            video.onseeked = () => {
                                captureFrame(`${baseName}-LastFrame`, video.duration);
                                video.currentTime = 0; // Reset to beginning
                                video.onseeked = null;
                            };
                        }
                    };
                };
            }

            function handleMp4Upload(file) {
                // Remove stale event listeners as in handleVideoUpload.
                video.onloadedmetadata = null;
                video.onloadeddata = null;
                video.onseeked = null;

                const url = URL.createObjectURL(file);
                video.src = url;
                video.load();
                updateStatus('MP4 Video loaded successfully.', 'success');
                setButtonStates(true);

                video.onloadedmetadata = () => {
                    video.currentTime = 0;
                    // Optionally, capture the initial frame once metadata is loaded.
                    video.onseeked = () => {
                        captureFrame(`${baseName}-S1F1`, 0);
                        video.onseeked = null;
                    };
                };

                // Depending on the browser and MP4 handling,
                // consider a different approach for processVideoSegment.
            }

            function captureFrame(frameNumber, time) {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = 100;
                canvas.height = 56;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                let label = 'Extracted Frame';
                if (frameNumber.includes('S')) {
                    const sectionNum = frameNumber.match(/S(\d+)/)[1];
                    label = `Section ${sectionNum}: First Frame`;
                }
                if (frameNumber.includes('LastFrame')) label = 'Split Section End';
                if (time === video.duration) label = 'Last Frame';

                const frameItem = document.createElement('div');
                frameItem.className = 'log-item';
                frameItem.style.display = 'flex';
                frameItem.style.alignItems = 'center';
                frameItem.innerHTML = `
        <img src="${canvas.toDataURL('image/jpeg', 0.9)}" alt="${label}" style="margin-right: 15px;">
        <div class="frame-info">
            <strong>${label}</strong><br>
            Time: ${formatTime(time)}
        </div>`;
                log.appendChild(frameItem);
            }
            // Split button works only for video files.
            splitButton.addEventListener('click', function() {
                const currentTime = video.currentTime;
                const sectionNumber = splits.length + 1;

                const divider = document.createElement('div');
                divider.className = 'log-item divider';
                divider.innerHTML = '<hr style="border: 2px solid #0056b3; margin: 10px 0;">';
                log.appendChild(divider);

                // Only capture the first frame of next section
                captureFrame(`${baseName}-S${sectionNumber + 1}F1`, currentTime);

                splits.push(currentTime);
                updateStatus(`Split created at ${formatTime(currentTime)}`, 'success');
            });

            function cleanupVideoListeners() {
                video.onloadedmetadata = null;
                video.onloadeddata = null;
                video.onseeked = null;
            }
            // Usage in handlers:
            cleanupVideoListeners();

            extractButton.addEventListener('click', () => {
                // For video files, capture the current frame.
                if (video.src) {
                    captureFrame('Extracted Frame', video.currentTime);
                }
                updateStatus('Frame extracted successfully', 'success');
            });

            // Add new helper functions before the DONE handler
            function processMp4Video() {
                video.currentTime = video.duration;
                return new Promise(resolve => {
                    video.onseeked = () => {
                        captureFrame('Final Frame', video.duration);
                        resolve();
                    };
                });
            }

            function processWebmVideo() {
                return new Promise(resolve => {
                    if (isNaN(video.duration) || video.duration === Infinity) {
                        video.currentTime = 0;
                        video.play().then(() => {
                            video.pause();
                            captureFrame('Final Frame', video.currentTime);
                            resolve();
                        }).catch(() => {
                            captureFrame('Final Frame', video.currentTime);
                            resolve();
                        });
                    } else {
                        video.currentTime = video.duration;
                        video.onseeked = () => {
                            captureFrame('Final Frame', video.duration);
                            resolve();
                        };
                    }
                });
            }

            // Replace existing doneButton click event listener with:
            doneButton.addEventListener('click', async () => {
                if (processingComplete) return;
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
                video.pause();
                doneButton.textContent = 'Processing...';
                updateStatus('Processing frames...', 'info');

                try {
                    if (video.src) {
                        if (video.src.includes('.mp4')) {
                            await processMp4Video();
                        } else {
                            await processWebmVideo();
                        }

                        addDivider();
                        await processAndSaveAllFrames();

                        if (splits.length > 0) {
                            updateStatus('Processing segments...', 'info');
                            for (let i = 0; i < splits.length; i++) {
                                const startTime = splits[i];
                                const endTime = splits[i + 1] || video.duration;
                                await processVideoSegment(startTime, endTime, i + 1);
                            }
                        }
                    } else {
                        updateStatus('No file loaded', 'error');
                    }
                } catch (error) {
                    console.error("An error occurred:", error);
                    updateStatus(`Error: ${error.message}`, 'error');
                } finally {
                    video.pause();
                    updateStatus('Processing Complete. All files have been downloaded.', 'success');
                    doneButton.textContent = 'Done';
                    doneButton.disabled = true;
                    processingComplete = true;
                    video.currentTime = 0;
                    cleanupVideoListeners();
                }
            });

            function addDivider() {
                const divider = document.createElement('div');
                divider.className = 'log-item divider';
                divider.innerHTML = '<hr style="border: 2px dashed #0056b3; margin: 10px 0;">';
                log.appendChild(divider);
            }
            async function processAndSaveAllFrames() {
                splits.sort((a, b) => a - b);
                const segments = [0, ...splits, video.duration];

                for (let i = 0; i < segments.length - 1; i++) {
                    const startTime = segments[i];
                    const endTime = segments[i + 1];
                    if (!video.src.includes('.mp4')) {
                        await processVideoSegment(startTime, endTime, i + 1);
                    }
                }
            }


            function formatTime(seconds) {
                const minutes = Math.floor(seconds / 60);
                const secs = Math.floor(seconds % 60);
                const tenths = Math.floor((seconds - Math.floor(seconds)) * 10);
                return `${minutes}:${String(secs).padStart(2, '0')}.${tenths}`;
            }

            function updateStatus(message, type = 'info') {
                const statusMessage = document.createElement('div');
                statusMessage.className = 'status-message' + (type !== 'info' ? ` ${type}` : '');
                statusMessage.textContent = message;
                statusBar.insertBefore(statusMessage, statusBar.firstChild);
                while (statusBar.children.length > 5) {
                    statusBar.removeChild(statusBar.lastChild);
                }
            }

            async function processVideoSegment(startTime, endTime, segmentNumber) {
                if (video.src.includes('.mp4')) {
                    // Fast MP4 processing
                    // First capture start frame
                    video.currentTime = startTime;
                    await new Promise(resolve =>
                        video.addEventListener('seeked', () => {
                            captureFrame(`${baseName}-S${segmentNumber}Start`, startTime);
                            // Then capture end frame
                            video.currentTime = endTime;
                            video.addEventListener('seeked', () => {
                                captureFrame(`${baseName}-S${segmentNumber}End`, endTime);
                                resolve();
                            }, {
                                once: true
                            });
                        }, {
                            once: true
                        })
                    );
                    return;
                }

                // Existing WebM processing code remains unchanged
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');

                const stream = canvas.captureStream();
                const mediaRecorder = new MediaRecorder(stream, {
                    mimeType: 'video/webm;codecs=vp8,opus',
                });

                const chunks = [];
                mediaRecorder.ondataavailable = e => {
                    if (e.data.size > 0) chunks.push(e.data);
                };

                return new Promise(resolve => {
                    let attempt = 0;
                    mediaRecorder.onstop = () => {
                        const blob = new Blob(chunks, {
                            type: 'video/webm'
                        });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `${baseName}-ScS${segmentNumber}.webm`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                        resolve();
                    };

                    mediaRecorder.start();
                    video.currentTime = startTime;

                    video.onseeked = function drawFrame() {
                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                        if (video.currentTime >= endTime) {
                            mediaRecorder.stop();
                            return;
                        }

                        let lastTime = video.currentTime;
                        video.currentTime += 1 / 30;
                        attempt++;
                        if (attempt > 300 || video.currentTime === lastTime) { // e.g., 10 seconds max at 30fps
                            mediaRecorder.stop();
                        }
                    };
                });
            }
        </script>

    </div>
</body>

</html>
