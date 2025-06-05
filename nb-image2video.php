<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>NetBound Tools: Image to Video</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f4f4f9;
            width: 768px;
            margin: 0 auto;
        }

        body.in-iframe {
            margin: 0;
        }

        .tool-container {
            background: #f4f4f9;
            height: auto;
            margin: 0;
            max-width: 768px;
            width: 100%;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .work-area {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .preview-area {
            margin-top: 10px;
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
            align-items: center;
        }

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
            white-space: nowrap;
        }

        .command-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .command-button:hover:not(:disabled) {
            background: #004494;
        }

        .status-bar {
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

        .file-info {
            color: #666;
            font-size: 12px;
            margin: 2px 0;
            display: none;
        }

        .save-button-container {
            display: flex;
            width: fit-content;
            margin-left: auto;
        }

        .save-button {
            background: #0056b3;
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-top-left-radius: 3px;
            border-bottom-left-radius: 3px;
        }

        .download-button {
            background: #0056b3;
            color: white;
            border: none;
            border-left: 1px solid rgba(255, 255, 255, 0.3);
            padding: 6px 8px;
            cursor: pointer;
            font-size: 14px;
            border-top-right-radius: 3px;
            border-bottom-right-radius: 3px;
        }

        .save-button:disabled,
        .download-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .progress {
            width: 100%;
            height: 4px;
            background: #eee;
            border-radius: 2px;
            overflow: hidden;
            position: relative;
            margin-top: 5px;
        }

        .progress-bar {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            /* Back to 100% height of container */
            background: #0056b3;
            /* Dark button blue color */
            width: 0%;
            transition: width 0.1s linear;
            border: none;
            /* Removed red border */
        }

        .progress {
            position: absolute;
            /* Positioned within audio-waveform */
            left: 0;
            top: 0;
            height: 4px;
            /* Reduced height for waveform area */
            width: 100%;
            background: rgba(204, 204, 204, 0.5);
            /* Semi-transparent light gray background */
            border-radius: 0;
            /* No border radius */
            overflow: hidden;
            margin-top: 0;
            /* Reset margin-top */
        }

        .audio-waveform .progress-text {
            top: -20px;
            /* Adjust position of progress text above progress bar */
            right: 10px;
            /* Adjust position of progress text */
            color: #fff;
            /* White progress text color for contrast on waveform */
            font-size: 11px;
            /* Slightly smaller progress text */
        }

        .progress-text {
            position: absolute;
            right: 5px;
            top: -18px;
            font-size: 12px;
            color: #666;
        }

        .content-area {
            width: 100%;
            height: 432px;
            background: #2a2a2a;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }

        canvas {
            max-width: 100%;
            max-height: 100%;
        }

        .audio-container {
            width: 100%;
            margin: 10px 0 5px 0;
            background: #f8f8f8;
            border-radius: 4px;
        }

        .audio-waveform {
            width: 100%;
            height: 60px;
            background: #f0f0f0;
            /* Light background to match the canvas */
            position: relative;
            display: none;
        }

        .playhead {
            position: absolute;
            top: 0;
            left: 0;
            width: 2px;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
        }

        .filename-control {
            width: 100%;
            display: flex;
            gap: 10px;
            margin: 5px 0;
        }

        .output-control {
            width: 100%;
            display: flex;
            gap: 10px;
            margin: 5px 0;
            align-items: center;
        }

        .output-info {
            flex: 1;
            font-size: 14px;
            color: #555;
        }

        .filename-input {
            flex: 1;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .duration-input {
            width: 80px;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            font-family: monospace;
        }

        .timer {
            color: #666;
            margin-left: 10px;
        }

        .hidden {
            display: none;
        }

        .status-bar.drag-over {
            background: #e3f2fd;
            border-color: #2196f3;
            border-style: dashed;
        }

        .status-message.processing {
            position: relative;
            overflow: hidden;
        }

        .processing-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: #4caf50;
            transition: width 0.1s linear;
            z-index: 1;
        }
    </style>
</head>

<body>
    <div class="tool-container">
        <div class="tool-header">
            <h1 class="tool-title">NetBound Tools: Image to Video</h1>

            <!-- Status bar now includes status -->
            <div id="statusBar" class="status-bar">
                <div id="status" class="status-message info">Ready</div>
            </div>

            <!-- Top row buttons -->
            <div class="button-controls">
                <button class="command-button" onclick="document.getElementById('imageInput').click()">
                    <i class="fas fa-image"></i> Open Image
                </button>
                <button class="command-button" onclick="document.getElementById('audioInput').click()">
                    <i class="fas fa-music"></i> Open Audio
                </button>
                <button class="command-button" onclick="location.reload()">
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>
        </div>

        <div class="work-area">
            <div class="preview-area">
                <!-- Content preview area -->
                <div class="content-area">
                    <canvas id="canvas"></canvas>
                </div>

                <!-- Filename control -->
                <div class="filename-control">
                    <input type="text" class="filename-input" id="filename" placeholder="Output filename">
                    <button class="command-button" id="btnRename">
                        <i class="fas fa-edit"></i> Rename
                    </button>
                </div>

                <!-- Stats and duration -->
                <div class="output-control">
                    <div class="output-info" id="outputInfo">Output format: No media loaded</div>
                    <label for="duration">Duration:</label>
                    <input type="text" class="duration-input" id="duration" value="00:05" pattern="[0-9]{2}:[0-9]{2}">
                    <span class="timer" id="timer"></span>
                </div>

                <!-- Audio waveform -->
                <div class="audio-container">
                    <div class="audio-waveform" id="waveform">
                        <div class="progress">
                            <div class="progress-bar" id="progress"></div>
                            <div class="progress-text" id="progressText">0%</div>
                        </div>
                        <canvas id="audioCanvas"></canvas>
                        <div class="playhead"></div>
                    </div>
                </div>

                <!-- Process and save controls -->
                <div class="button-controls">
                    <button class="command-button" id="startBtn">
                        <i class="fas fa-record-vinyl"></i> Process Video
                    </button>
                    <button class="command-button" id="stopBtn" disabled>
                        <i class="fas fa-stop"></i> Abort
                    </button>
                    <button class="command-button" id="previewBtn" disabled>
                        <i class="fas fa-play"></i> Preview Audio
                    </button>
                </div>
            </div>
        </div>
    </div>

    <input type="file" id="imageInput" accept="image/*" style="display: none">
    <input type="file" id="audioInput" accept="audio/*" style="display: none">

    <script>
        function inIframe() {
            try {
                return window.self !== window.top;
            } catch (e) {
                return true;
            }
        }

        if (inIframe()) {
            document.body.classList.add('in-iframe');
        }

        // Update the status object by removing the fancy processing method
        const status = {
            update(message, type = 'info') {
                const container = document.getElementById('statusBar');
                const messageDiv = document.createElement('div');
                messageDiv.className = `status-message ${type}`;
                messageDiv.textContent = message;
                container.insertBefore(messageDiv, container.firstChild);
                container.scrollTop = 0;
                return messageDiv; // Return the message div for potential updates later
            },

            // We can still update messages when needed
            updateMessage(element, message, type) {
                if (!element) return;
                element.textContent = message;
                if (type) {
                    element.className = `status-message ${type}`;
                }
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            const statusBar = document.getElementById('statusBar');
            const els = {
                canvas: document.getElementById('canvas'),
                audioCanvas: document.getElementById('audioCanvas'),
                status: document.getElementById('status'),
                timer: document.getElementById('timer'),
                progress: document.getElementById('progress'),
                progressText: document.getElementById('progressText'),
                startBtn: document.getElementById('startBtn'),
                stopBtn: document.getElementById('stopBtn'),
                previewBtn: document.getElementById('previewBtn'),
                filename: document.getElementById('filename'),
                duration: document.getElementById('duration'),
                waveform: document.getElementById('waveform'),
                playhead: document.querySelector('.playhead'),
                imageInput: document.getElementById('imageInput'),
                audioInput: document.getElementById('audioInput'),
                btnSave: document.getElementById('btnSave'),
                btnDownload: document.getElementById('btnDownload'),
                outputInfo: document.getElementById('outputInfo'),
                btnRename: document.getElementById('btnRename')
            };

            function initDragAndDrop(statusBar, fileInput) {
                status.update('Image Sync tool ready. Drag image files here or use buttons.', 'info');

                statusBar.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    statusBar.classList.add('drag-over');
                });

                statusBar.addEventListener('dragleave', () => {
                    statusBar.classList.remove('drag-over');
                });

                statusBar.addEventListener('drop', (e) => {
                    e.preventDefault();
                    statusBar.classList.remove('drag-over');
                    const file = e.dataTransfer.files[0];
                    if (file?.type.startsWith('image/')) {
                        els.imageInput.files = e.dataTransfer.files;
                        els.imageInput.dispatchEvent(new Event('change'));
                    }
                });
            }

            initDragAndDrop(statusBar, els.imageInput);

            const ctx = els.canvas.getContext('2d');
            const actx = els.audioCanvas.getContext('2d');

            let state = {
                recording: false,
                startTime: 0,
                recorder: null,
                stream: null,
                audioBuffer: null,
                imageFile: null,
                audioFile: null,
                audioElement: null,
                audioIsPlaying: false
            };

            els.canvas.width = 1280;
            els.canvas.height = 720;
            els.audioCanvas.width = 100;
            els.audioCanvas.height = 60;

            function validateDuration(timeStr) {
                if (/^\d+$/.test(timeStr)) {
                    const seconds = parseInt(timeStr, 10);
                    const mins = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                }
                return timeStr;
            }

            function parseTime(timeStr) {
                timeStr = validateDuration(timeStr);
                const [mins, secs] = timeStr.split(':').map(Number);
                return (mins * 60 + secs) * 1000;
            }

            function formatTime(ms) {
                const secs = Math.floor(ms / 1000);
                const mins = Math.floor(secs / 60);
                return `${mins.toString().padStart(2, '0')}:${(secs % 60).toString().padStart(2, '0')}`;
            }

            function drawImage() {
                if (!state.imageFile) {
                    ctx.fillStyle = '#2196f3';
                    ctx.fillRect(0, 0, els.canvas.width, els.canvas.height);
                    ctx.fillStyle = 'white';
                    ctx.textAlign = 'center';
                    ctx.font = '24px system-ui';
                    ctx.fillText('Load an image to begin', els.canvas.width / 2, els.canvas.height / 2);
                    return;
                }

                const img = new Image();
                img.onload = () => {
                    const scale = Math.min(
                        els.canvas.width / img.width,
                        els.canvas.height / img.height
                    );
                    const w = img.width * scale;
                    const h = img.height * scale;
                    const x = (els.canvas.width - w) / 2;
                    const y = (els.canvas.height - h) / 2;

                    ctx.fillStyle = '#000';
                    ctx.fillRect(0, 0, els.canvas.width, els.canvas.height);

                    ctx.drawImage(img, x, y, w, h);
                };
                img.src = URL.createObjectURL(state.imageFile);
            }

            function drawAudioWaveform() {
                if (!state.audioBuffer) return;

                els.audioCanvas.width = els.waveform.clientWidth;

                const data = state.audioBuffer.getChannelData(0);
                const step = Math.ceil(data.length / els.audioCanvas.width);
                const amp = els.audioCanvas.height / 2;

                // Use a lighter background color
                actx.fillStyle = '#f0f0f0';
                actx.fillRect(0, 0, els.audioCanvas.width, els.audioCanvas.height);

                // Draw center line
                actx.strokeStyle = '#cccccc';
                actx.lineWidth = 1;
                actx.beginPath();
                actx.moveTo(0, amp);
                actx.lineTo(els.audioCanvas.width, amp);
                actx.stroke();

                // Draw waveform
                actx.beginPath();

                // Draw filled waveform for better visibility
                let lastX = 0;
                let lastMax = amp;
                let lastMin = amp;

                for (let i = 0; i < els.audioCanvas.width; i++) {
                    let min = 0,
                        max = 0;
                    for (let j = 0; j < step; j++) {
                        const index = (i * step) + j;
                        if (index < data.length) {
                            const datum = data[index];
                            if (datum < min) min = datum;
                            if (datum > max) max = datum;
                        }
                    }

                    const y1 = amp + (min * amp * 0.8); // Scale by 0.8 to make waveform more visible
                    const y2 = amp + (max * amp * 0.8);

                    if (i > 0) {
                        // Create a connected waveform
                        actx.moveTo(lastX, lastMax);
                        actx.lineTo(i, y2);
                        actx.lineTo(i, y1);
                        actx.lineTo(lastX, lastMin);
                    }

                    lastX = i;
                    lastMin = y1;
                    lastMax = y2;
                }

                // Use blue for the waveform to match your other tools
                actx.strokeStyle = '#2196f3';
                actx.lineWidth = 2;
                actx.stroke();

                // Add a subtle fill to the waveform
                actx.fillStyle = 'rgba(33, 150, 243, 0.2)';
                actx.fill();

                els.waveform.style.display = 'block';
            }

            function updateProgress(now) {
                if (!state.startTime) state.startTime = now;
                const elapsed = now - state.startTime;
                const duration = parseTime(els.duration.value);
                const percent = Math.min(100, (elapsed / duration) * 100);

                // Update both the progress bar and our status message
                els.progress.style.width = percent + '%';
                els.progressText.textContent = Math.round(percent) + '%';
                els.timer.textContent = formatTime(duration - elapsed) + ' remaining';

                // Debugging log: Check progress bar width updates
                console.log('Progress Percent:', percent, 'Progress Bar Width:', percent + '%');

                // Update our new progress bar in the status message
                if (state.statusProgressBar) {
                    state.statusProgressBar.style.width = percent + '%';
                }

                if (elapsed < duration && state.recording) {
                    drawImage();
                    requestAnimationFrame(updateProgress);
                } else if (state.recording) {
                    // When complete, update our status message
                    finishRecording(); // Use finishRecording instead of stopRecording
                }
            }

            function updateOutputInfo() {
                let info = "Output format: ";

                if (state.imageFile) {
                    const img = new Image();
                    img.onload = () => {
                        // Add resolution
                        info += `${img.width}x${img.height}px`;

                        // Add audio status directly in the format information
                        const audioStatus = state.audioBuffer ? 'with audio' : 'without audio';
                        info += ` (${audioStatus})`;

                        // Add duration
                        if (state.audioBuffer) {
                            const duration = formatTime(state.audioBuffer.duration * 1000);
                            info += `, ${duration} duration`;
                        } else {
                            const duration = validateDuration(els.duration.value);
                            if (duration === '00:00') {
                                status.update('Duration cannot be zero', 'error');
                                els.startBtn.disabled = true;
                            } else {
                                info += `, ${duration} duration`;
                            }
                        }
                        els.outputInfo.textContent = info;
                    };
                    img.src = URL.createObjectURL(state.imageFile);
                } else {
                    els.outputInfo.textContent = "Output format: No media loaded";
                }
            }

            async function startRecording() {
                try {
                    if (!state.imageFile) {
                        status.update('Please load an image first', 'error');
                        return;
                    }

                    // Create processing status without progress bar
                    const statusMsg = status.update('Processing video...', 'info');

                    const duration = validateDuration(els.duration.value);
                    if (duration === '00:00') {
                        status.update('Duration cannot be zero', 'error');
                        return;
                    }

                    els.duration.value = duration;
                    state.recording = true;
                    state.startTime = 0;
                    els.startBtn.disabled = true;
                    els.stopBtn.disabled = false;

                    // With this conditional message:
                    if (state.audioBuffer) {
                        status.update('Creating video with audio track...', 'info');
                    } else {
                        status.update('Creating silent video...', 'info');
                    }

                    // Rest of the recording setup...
                    state.stream = els.canvas.captureStream();

                    // Set up audio monitoring
                    let monitorGainNode = null;
                    let audioMonitor = null;

                    if (state.audioBuffer) {
                        const audioCtx = new AudioContext();
                        const source = audioCtx.createMediaStreamDestination();
                        const gainNode = audioCtx.createGain();
                        const audioSource = audioCtx.createBufferSource();
                        audioSource.buffer = state.audioBuffer;
                        audioSource.connect(gainNode);
                        gainNode.connect(source);
                        gainNode.gain.value = 1; // Full volume for recording

                        // Add monitoring path
                        monitorGainNode = audioCtx.createGain();
                        audioSource.connect(monitorGainNode);
                        monitorGainNode.connect(audioCtx.destination);
                        monitorGainNode.gain.value = 1; // Start unmuted
                        audioMonitor = {
                            monitorGainNode,
                            audioCtx
                        };

                        // Store for mute toggling
                        state.audioMonitor = audioMonitor;

                        // Change preview button to mute button during recording
                        els.previewBtn.disabled = false;
                        els.previewBtn.innerHTML = '<i class="fas fa-volume-up"></i> Mute';
                        els.previewBtn.title = "Mute monitoring only - final video will still include audio";

                        audioSource.start();
                        state.stream.addTrack(source.stream.getAudioTracks()[0]);
                    } else {
                        // If no audio, disable the button during recording
                        els.previewBtn.disabled = true;
                    }

                    state.recorder = new MediaRecorder(state.stream, {
                        mimeType: 'video/webm',
                        videoBitsPerSecond: 2500000
                    });

                    const chunks = [];
                    state.recorder.ondataavailable = e => chunks.push(e.data);

                    // Update what happens when recording stops
                    const originalStopFn = state.recorder.onstop;
                    state.recorder.onstop = () => {
                        // Reset preview button to normal state
                        if (state.audioBuffer) {
                            els.previewBtn.innerHTML = '<i class="fas fa-play"></i> Preview Audio';
                            els.previewBtn.title = "";
                            els.previewBtn.disabled = false;
                        }

                        // Clean up audio monitoring
                        if (audioMonitor && audioMonitor.audioCtx) {
                            if (audioMonitor.audioCtx.state !== 'closed') {
                                audioMonitor.audioCtx.close();
                            }
                        }
                        state.audioMonitor = null;

                        // Call original onstop handler
                        originalStopFn();
                    };

                    state.recorder.onstop = () => {
                        // Check if this was an abort or a normal completion
                        if (!state.aborted) {
                            const blob = new Blob(chunks, {
                                type: 'video/webm'
                            });
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = els.filename.value || 'recording.webm';
                            a.click();

                            // Calculate size in KB or MB
                            const size = blob.size;
                            const sizeStr = size > 1024 * 1024 ?
                                (size / (1024 * 1024)).toFixed(2) + ' MB' :
                                (size / 1024).toFixed(2) + ' KB';

                            // Get resolution from canvas
                            const resolution = `${els.canvas.width}x${els.canvas.height}px`;

                            // Check if audio was included
                            const audioStatus = state.audioBuffer ? 'with audio' : 'without audio';

                            status.update(`Video processed successfully: ${sizeStr} WebM file (${resolution}) ${audioStatus}`, 'success');

                            URL.revokeObjectURL(url);
                        }
                        // Don't show success message or download if aborted

                        els.timer.textContent = '';
                        els.progress.style.width = '0%';
                        els.progressText.textContent = '0%';
                        els.startBtn.disabled = false;
                        els.stopBtn.disabled = true;

                        // Reset abort flag
                        state.aborted = false;
                    };

                    state.recorder.start(100);
                    drawImage();
                    requestAnimationFrame(updateProgress);
                    status.update('Processing video...', 'info');

                } catch (e) {
                    console.error(e);
                    status.update('Error: ' + e.message, 'error');
                    els.startBtn.disabled = false;
                    els.stopBtn.disabled = true;
                    state.recording = false;
                }
            }

            // Add this new function for normal completion
            function finishRecording() {
                if (state.recorder?.state === 'recording') {
                    state.recording = false;
                    state.recorder.stop();

                    if (state.stream) {
                        state.stream.getTracks().forEach(track => track.stop());
                    }
                }
            }

            // Keep the abort function separate
            function stopRecording() {
                if (state.recorder?.state === 'recording') {
                    // Set aborted flag before stopping recorder
                    state.aborted = true;
                    state.recording = false;
                    state.recorder.stop();

                    status.update('Recording aborted by user. No file was saved.', 'error');

                    if (state.stream) {
                        state.stream.getTracks().forEach(track => track.stop());
                    }
                }
            }

            function previewAudio() {
                // If we're recording, toggle mute instead of preview
                if (state.recording && state.audioMonitor) {
                    if (state.audioMonitor.monitorGainNode.gain.value > 0) {
                        // Mute
                        state.audioMonitor.monitorGainNode.gain.value = 0;
                        els.previewBtn.innerHTML = '<i class="fas fa-volume-mute"></i> Unmute';
                    } else {
                        // Unmute
                        state.audioMonitor.monitorGainNode.gain.value = 1;
                        els.previewBtn.innerHTML = '<i class="fas fa-volume-up"></i> Mute';
                    }
                    return;
                }

                // Original preview functionality when not recording
                if (!state.audioBuffer) {
                    status.update('No audio loaded to preview', 'error');
                    return;
                }

                if (state.audioIsPlaying) {
                    if (state.audioSource) {
                        state.audioSource.stop();
                        state.audioIsPlaying = false;
                        els.previewBtn.innerHTML = '<i class="fas fa-play"></i> Preview Audio';
                        els.playhead.style.display = 'none';
                        return;
                    }
                }

                const audioCtx = new AudioContext();
                const source = audioCtx.createBufferSource();
                source.buffer = state.audioBuffer;
                source.connect(audioCtx.destination);

                state.audioCtx = audioCtx;
                state.audioSource = source;
                state.audioIsPlaying = true;

                els.previewBtn.innerHTML = '<i class="fas fa-stop"></i> Stop Preview';

                source.start(0);
                const startTime = audioCtx.currentTime;
                const duration = state.audioBuffer.duration;

                els.playhead.style.display = 'block';

                function updatePlayhead() {
                    if (!state.audioIsPlaying) return;

                    const elapsed = audioCtx.currentTime - startTime;
                    const progress = Math.min(1, elapsed / duration);

                    if (progress < 1) {
                        els.playhead.style.left = (progress * 100) + '%';
                        requestAnimationFrame(updatePlayhead);
                    } else {
                        els.playhead.style.left = '0';
                        els.playhead.style.display = 'none';
                        els.previewBtn.innerHTML = '<i class="fas fa-play"></i> Preview Audio';
                        state.audioIsPlaying = false;
                    }
                }

                source.onended = () => {
                    els.playhead.style.display = 'none';
                    els.previewBtn.innerHTML = '<i class="fas fa-play"></i> Preview Audio';
                    state.audioIsPlaying = false;
                };

                requestAnimationFrame(updatePlayhead);
            }

            els.imageInput.onchange = async e => {
                if (e.target.files.length > 0) {
                    state.imageFile = e.target.files[0];
                    els.filename.value = state.imageFile.name.replace(/\.[^/.]+$/, '.webm');

                    const img = new Image();
                    img.onload = () => {
                        status.update(`Image loaded: ${img.width}x${img.height}px, ${Math.round(state.imageFile.size/1024)}KB`, 'success');
                        updateOutputInfo();

                        els.startBtn.disabled = false;
                        els.btnSave.disabled = false;
                        els.btnDownload.disabled = false;

                        // Add status message when no audio is present
                        if (!state.audioBuffer) {
                            status.update('No audio added. Video will be silent unless audio is added.', 'info');
                        }
                    };
                    img.src = URL.createObjectURL(state.imageFile);

                    drawImage();
                }
            };

            els.audioInput.onchange = async e => {
                if (e.target.files.length > 0) {
                    try {
                        const audioCtx = new AudioContext();
                        const arrayBuffer = await e.target.files[0].arrayBuffer();
                        state.audioBuffer = await audioCtx.decodeAudioData(arrayBuffer);
                        state.audioFile = e.target.files[0];

                        els.duration.value = formatTime(state.audioBuffer.duration * 1000);

                        els.duration.disabled = true;

                        // Force waveform display
                        els.waveform.style.display = 'block';
                        drawAudioWaveform();

                        // Force a small delay to ensure rendering happens
                        setTimeout(() => {
                            drawAudioWaveform(); // Draw again after a slight delay
                        }, 100);

                        els.previewBtn.disabled = false;

                        status.update(`Audio loaded: ${formatTime(state.audioBuffer.duration * 1000)} duration, ${Math.round(e.target.files[0].size/1024)}KB`, 'success');
                        updateOutputInfo();
                    } catch (err) {
                        status.update('Audio load error: ' + err.message, 'error');
                    }
                }
            };

            els.duration.onchange = () => {
                if (!state.audioBuffer) {
                    const duration = validateDuration(els.duration.value);
                    if (duration === '00:00') {
                        status.update('Duration cannot be zero', 'error');
                        els.startBtn.disabled = true;
                    } else {
                        els.duration.value = duration;
                        updateOutputInfo();
                        els.startBtn.disabled = false;
                    }
                }
            };

            drawImage();

            // Connect buttons to their functions
            els.startBtn.addEventListener('click', startRecording);
            els.stopBtn.addEventListener('click', stopRecording);
            els.previewBtn.addEventListener('click', previewAudio);

            // Ensure waveform resizes properly
            window.addEventListener('resize', () => {
                if (state.audioBuffer) {
                    drawAudioWaveform();
                }
            });

            // Initialize UI
            drawImage();

            // Add event listener for the rename button
            els.btnRename.addEventListener('click', () => {
                const newName = els.filename.value.trim();
                if (newName) {
                    status.update(`File renamed to "${newName}"`, 'info');
                } else {
                    status.update('Please enter a valid filename', 'error');
                }
            });
        });
    </script>
</body>

</html>
