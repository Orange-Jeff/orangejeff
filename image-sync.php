<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>NetBound Tools: Image Sync</title>
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
            flex-wrap: wrap;
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
        }

        .command-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .command-button:hover:not(:disabled) {
            background: #004494;
        }

        /* Status bar with less space below */
        .status-bar {
            width: 100%;
            height: 90px;
            min-height: 90px;
            max-height: 90px;
            overflow-y: auto;
            border: 1px solid #ddd;
            background: #fff;
            padding: 5px;
            margin: 10px 0 5px 0;
            /* Reduced bottom margin */
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

        /* Only the first/latest message has background color */
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

        /* Reduced file info spacing */
        .file-info {
            color: #666;
            font-size: 12px;
            margin: 2px 0;
            display: none;
            /* Hide this as we're moving info to status area */
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
            margin: 5px 0;
            height: 20px;
            background: #eee;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background: #0056b3;
            width: 0%;
            transition: width 0.1s linear;
        }

        .progress-text {
            position: absolute;
            width: 100%;
            text-align: center;
            line-height: 20px;
            color: #fff;
            font-size: 12px;
            text-shadow: 0 0 2px rgba(0, 0, 0, 0.5);
            z-index: 1;
        }

        /* Content area */
        .content-area {
            width: 100%;
            height: 432px;
            /* 16:9 aspect ratio */
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

        /* Audio section */
        .audio-container {
            width: 100%;
            margin: 10px 0 5px 0;
            background: #f8f8f8;
            border-radius: 4px;
        }

        .audio-waveform {
            width: 100%;
            height: 60px;
            background: #2a2a2a;
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

        /* Controls */
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
    </style>
</head>

<body>
    <div class="tool-container">
        <div class="tool-header">
            <h1 class="tool-title">NetBound Tools: Image Sync</h1>

            <!-- Status bar that doubles as drag zone -->
            <div id="statusBar" class="status-bar">
                <div id="status" class="status-message info">Ready</div>
            </div>

            <!-- Progress indicator -->
            <div class="progress">
                <div class="progress-bar" id="progress"></div>
                <div class="progress-text" id="progressText">0%</div>
            </div>
            <span class="timer" id="timer"></span>

            <!-- Button controls in top row - consistent with template -->
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

        <!-- Content preview area -->
        <div class="work-area">
            <div class="preview-area">
                <div class="content-area">
                    <canvas id="canvas"></canvas>
                </div>

                <!-- Move fileInfo to status messages -->
                <div id="fileInfo" class="file-info"></div>

                <!-- Audio waveform visualization -->
                <div class="audio-container">
                    <div class="audio-waveform" id="waveform">
                        <canvas id="audioCanvas"></canvas>
                        <div class="playhead"></div>
                    </div>
                </div>

                <!-- Filename control on its own line -->
                <div class="filename-control">
                    <input type="text" class="filename-input" id="filename" placeholder="Output filename">
                    <button class="command-button" id="btnRename">
                        <i class="fas fa-edit"></i> Rename
                    </button>
                </div>

                <!-- Output format info on its own line -->
                <div class="output-control">
                    <div class="output-info" id="outputInfo">Output format: No media loaded</div>
                    <label for="duration">Duration:</label>
                    <input type="text" class="duration-input" id="duration" value="00:05" pattern="[0-9]{2}:[0-9]{2}">
                </div>

                <!-- Action buttons -->
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
                    <!-- Save buttons -->
                    <div class="save-button-container">
                        <button class="save-button" id="btnSave" disabled>
                            <i class="fas fa-save"></i> SAVE MP4
                        </button>
                        <button class="download-button" id="btnDownload" disabled>
                            <i class="fas fa-save"></i> WEBM
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="file" id="imageInput" accept="image/*" style="display: none">
    <input type="file" id="audioInput" accept="audio/*" style="display: none">

    <!-- Main JavaScript -->
    <script>
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

        // Status message handling
        const status = {
            update(message, type = 'info') {
                const container = document.getElementById('statusBar');
                const messageDiv = document.createElement('div');
                messageDiv.className = `status-message ${type}`;
                messageDiv.textContent = message;
                container.insertBefore(messageDiv, container.firstChild); // Insert at top
                container.scrollTop = 0; // Keep scrolled to top
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            // Elements
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

            // Initialize drag and drop functionality
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

            // Initialize drag and drop
            initDragAndDrop(statusBar, els.imageInput);

            // Canvas context
            const ctx = els.canvas.getContext('2d');
            const actx = els.audioCanvas.getContext('2d');

            // State
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

            // Set canvas sizes
            els.canvas.width = 1280;
            els.canvas.height = 720;
            els.audioCanvas.width = 100; // Will be adjusted on resize
            els.audioCanvas.height = 60;

            // Function to validate and format duration
            function validateDuration(timeStr) {
                // If it's just a number, convert to MM:SS format
                if (/^\d+$/.test(timeStr)) {
                    const seconds = parseInt(timeStr, 10);
                    const mins = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                }
                // Already in MM:SS format
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

            // Improved drawImage function to properly fit any image aspect ratio
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
                    // Calculate scale to fit image properly within canvas
                    const scale = Math.min(
                        els.canvas.width / img.width,
                        els.canvas.height / img.height
                    );
                    const w = img.width * scale;
                    const h = img.height * scale;
                    const x = (els.canvas.width - w) / 2;
                    const y = (els.canvas.height - h) / 2;

                    // Clear canvas with black background
                    ctx.fillStyle = '#000';
                    ctx.fillRect(0, 0, els.canvas.width, els.canvas.height);

                    // Draw image centered
                    ctx.drawImage(img, x, y, w, h);
                };
                img.src = URL.createObjectURL(state.imageFile);
            }

            // Improved audio waveform drawing
            function drawAudioWaveform() {
                if (!state.audioBuffer) return;

                // Update canvas width to match container
                els.audioCanvas.width = els.waveform.clientWidth;

                const data = state.audioBuffer.getChannelData(0);
                const step = Math.ceil(data.length / els.audioCanvas.width);
                const amp = els.audioCanvas.height / 2;

                actx.fillStyle = '#2a2a2a';
                actx.fillRect(0, 0, els.audioCanvas.width, els.audioCanvas.height);
                actx.strokeStyle = '#2196f3';
                actx.lineWidth = 2;
                actx.beginPath();

                // Draw centerline
                actx.moveTo(0, amp);
                actx.lineTo(els.audioCanvas.width, amp);
                actx.stroke();

                // Draw waveform
                actx.beginPath();
                actx.moveTo(0, amp);

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
                    // Draw from min to max to show complete waveform range
                    const y1 = amp + (min * amp);
                    const y2 = amp + (max * amp);
                    actx.moveTo(i, y1);
                    actx.lineTo(i, y2);
                }

                actx.strokeStyle = '#4caf50';
                actx.stroke();
                els.waveform.style.display = 'block';
            }

            function updateProgress(now) {
                if (!state.startTime) state.startTime = now;
                const elapsed = now - state.startTime;
                const duration = parseTime(els.duration.value);
                const percent = Math.min(100, (elapsed / duration) * 100);

                els.progress.style.width = percent + '%';
                els.progressText.textContent = Math.round(percent) + '%';
                els.timer.textContent = formatTime(duration - elapsed) + ' remaining';

                if (elapsed < duration && state.recording) {
                    drawImage();
                    requestAnimationFrame(updateProgress);
                } else if (state.recording) {
                    stopRecording();
                }
            }

            // Update output format information
            function updateOutputInfo() {
                let info = "Output format: ";

                if (state.imageFile) {
                    const img = new Image();
                    img.onload = () => {
                        info += `${img.width}x${img.height}px`;
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

                    // Check if duration is valid
                    const duration = validateDuration(els.duration.value);
                    if (duration === '00:00') {
                        status.update('Duration cannot be zero', 'error');
                        return;
                    }

                    els.duration.value = duration; // Update with formatted value

                    state.recording = true;
                    state.startTime = 0;
                    els.startBtn.disabled = true;
                    els.stopBtn.disabled = false;
                    status.update('Starting video processing...', 'info');

                    // Setup stream
                    state.stream = els.canvas.captureStream();
                    if (state.audioBuffer) {
                        const audioCtx = new AudioContext();
                        const source = audioCtx.createMediaStreamDestination();
                        const gainNode = audioCtx.createGain();
                        const audioSource = audioCtx.createBufferSource();
                        audioSource.buffer = state.audioBuffer;
                        audioSource.connect(gainNode);
                        gainNode.connect(source);
                        gainNode.gain.value = 1;
                        audioSource.start();
                        state.stream.addTrack(source.stream.getAudioTracks()[0]);
                    }

                    state.recorder = new MediaRecorder(state.stream, {
                        mimeType: 'video/webm',
                        videoBitsPerSecond: 2500000
                    });

                    const chunks = [];
                    state.recorder.ondataavailable = e => chunks.push(e.data);
                    state.recorder.onstop = () => {
                        const blob = new Blob(chunks, {
                            type: 'video/webm'
                        });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = els.filename.value || 'recording.webm';
                        a.click();
                        URL.revokeObjectURL(url);

                        status.update('Video processing complete!', 'success');
                        els.timer.textContent = '';
                        els.progress.style.width = '0%';
                        els.progressText.textContent = '0%';
                        els.startBtn.disabled = false;
                        els.stopBtn.disabled = true;
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

            function stopRecording() {
                if (state.recorder?.state === 'recording') {
                    state.recording = false;
                    state.recorder.stop();
                    if (state.stream) {
                        state.stream.getTracks().forEach(track => track.stop());
                    }
                    status.update('Recording stopped', 'info');
                }
            }

            // Fixed previewAudio function
            function previewAudio() {
                if (!state.audioBuffer) {
                    status.update('No audio loaded to preview', 'error');
                    return;
                }

                // If already playing, stop it
                if (state.audioIsPlaying) {
                    if (state.audioSource) {
                        state.audioSource.stop();
                        state.audioIsPlaying = false;
                        els.previewBtn.innerHTML = '<i class="fas fa-play"></i> Preview Audio';
                        els.playhead.style.display = 'none';
                        return;
                    }
                }

                // Create new audio context and buffer source
                const audioCtx = new AudioContext();
                const source = audioCtx.createBufferSource();
                source.buffer = state.audioBuffer;
                source.connect(audioCtx.destination);

                // Store references to stop later
                state.audioCtx = audioCtx;
                state.audioSource = source;
                state.audioIsPlaying = true;

                // Update button
                els.previewBtn.innerHTML = '<i class="fas fa-stop"></i> Stop Preview';

                // Start playback
                source.start(0);
                const startTime = audioCtx.currentTime;
                const duration = state.audioBuffer.duration;

                // Show and animate playhead
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

                // Handle completion
                source.onended = () => {
                    els.playhead.style.display = 'none';
                    els.previewBtn.innerHTML = '<i class="fas fa-play"></i> Preview Audio';
                    state.audioIsPlaying = false;
                };

                requestAnimationFrame(updatePlayhead);
            }

            // File inputs
            els.imageInput.onchange = async e => {
                if (e.target.files.length > 0) {
                    state.imageFile = e.target.files[0];
                    els.filename.value = state.imageFile.name.replace(/\.[^/.]+$/, '.webm');

                    // Update file info in status area
                    const img = new Image();
                    img.onload = () => {
                        status.update(`Image loaded: ${img.width}x${img.height}px, ${Math.round(state.imageFile.size/1024)}KB`, 'success');
                        updateOutputInfo();

                        // Enable relevant buttons
                        els.startBtn.disabled = false;
                        els.btnSave.disabled = false;
                        els.btnDownload.disabled = false;
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

                        // Update duration field with actual audio duration
                        els.duration.value = formatTime(state.audioBuffer.duration * 1000);

                        // Lock duration field when audio is loaded
                        els.duration.disabled = true;

                        drawAudioWaveform();
                        els.previewBtn.disabled = false;

                        // Update file info with audio details
                        status.update(`Audio loaded: ${formatTime(state.audioBuffer.duration * 1000)} duration, ${Math.round(e.target.files[0].size/1024)}KB`, 'success');
                        updateOutputInfo();
                    } catch (err) {
                        status.update('Audio load error: ' + err.message, 'error');
                    }
                }
            };

            // Duration change handler
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

            // Initial setup
            drawImage();
        });
    </script>
</body>

</html>
