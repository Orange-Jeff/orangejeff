<!DOCTYPE html>
<html>

<head>
    <title>Image Sync</title>
    <style>
        body {
            font-family: system-ui;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        .container {
            width: 100%;
        }

        canvas {
            border: 1px solid #ddd;
            max-width: 100%;
        }

        .status {
            margin: 10px 0;
            padding: 10px;
            background: #f0f0f0;
            border-radius: 4px;
        }

        .progress {
            margin: 10px 0;
            height: 24px;
            background: #eee;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background: #2196f3;
            width: 0%;
        }

        .progress-text {
            position: absolute;
            width: 100%;
            text-align: center;
            line-height: 24px;
            color: #000;
            z-index: 1;
        }

        .audio-container {
            margin: 10px 0;
            padding: 10px;
            background: #f8f8f8;
            border-radius: 4px;
        }

        .audio-waveform {
            width: 100%;
            height: 60px;
            background: #2a2a2a;
            margin-top: 10px;
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

        .controls {
            margin: 10px 0;
        }

        button {
            padding: 8px 16px;
            margin: 5px;
            border: none;
            background: #2196f3;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        button:disabled {
            background: #ccc;
        }

        .filename-input {
            padding: 6px 10px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 300px;
        }

        .duration-input {
            width: 80px;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }

        .timer {
            color: #666;
            font-size: 14px;
        }

        .hidden {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Image Sync</h2>

        <div class="status">
            <span id="status">Ready</span>
            <span class="timer" id="timer"></span>
        </div>

        <div class="progress">
            <div class="progress-bar" id="progress"></div>
            <div class="progress-text" id="progressText">0%</div>
        </div>

        <div class="controls">
            <button onclick="document.getElementById('imageInput').click()">
                <i class="fas fa-image"></i> Load Image
            </button>
            <button onclick="document.getElementById('audioInput').click()">
                <i class="fas fa-music"></i> Load Audio
            </button>
            <input type="text" class="filename-input" id="filename" placeholder="Output filename">
            <input type="text" class="duration-input" id="duration" value="00:05" pattern="[0-9]{2}:[0-9]{2}">
        </div>

        <canvas id="canvas"></canvas>

        <div class="audio-container">
            <div class="audio-waveform" id="waveform">
                <canvas id="audioCanvas"></canvas>
                <div class="playhead"></div>
            </div>
        </div>

        <div class="controls">
            <button onclick="startRecording()" id="startBtn">Record Video</button>
            <button onclick="stopRecording()" id="stopBtn" disabled>Stop</button>
            <button onclick="previewAudio()" id="previewBtn" disabled>Preview Audio</button>
        </div>
    </div>

    <input type="file" id="imageInput" accept="image/*" style="display: none">
    <input type="file" id="audioInput" accept="audio/*" style="display: none">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script>
        // Elements
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
            audioInput: document.getElementById('audioInput')
        };

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
            audioElement: null
        };

        // Set canvas sizes
        els.canvas.width = 1280;
        els.canvas.height = 720;
        els.audioCanvas.width = els.waveform.clientWidth;
        els.audioCanvas.height = 60;

        function parseTime(timeStr) {
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

            const data = state.audioBuffer.getChannelData(0);
            const step = Math.ceil(data.length / els.audioCanvas.width);
            const amp = els.audioCanvas.height / 2;

            actx.fillStyle = '#2a2a2a';
            actx.fillRect(0, 0, els.audioCanvas.width, els.audioCanvas.height);
            actx.strokeStyle = '#2196f3';
            actx.beginPath();
            actx.moveTo(0, amp);

            for (let i = 0; i < els.audioCanvas.width; i++) {
                let max = 0;
                for (let j = 0; j < step; j++) {
                    const datum = data[(i * step) + j] || 0;
                    if (Math.abs(datum) > max) max = Math.abs(datum);
                }
                actx.lineTo(i, (1 - max) * amp);
            }

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

        async function startRecording() {
            try {
                if (!state.imageFile) {
                    els.status.textContent = 'Please load an image first';
                    return;
                }

                state.recording = true;
                state.startTime = 0;
                els.startBtn.disabled = true;
                els.stopBtn.disabled = false;
                els.status.textContent = 'Starting...';

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

                    els.status.textContent = 'Complete';
                    els.timer.textContent = '';
                    els.startBtn.disabled = false;
                    els.stopBtn.disabled = true;
                };

                state.recorder.start(100);
                drawImage();
                requestAnimationFrame(updateProgress);
                els.status.textContent = 'Recording';

            } catch (e) {
                console.error(e);
                els.status.textContent = 'Error: ' + e.message;
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
            }
        }

        function previewAudio() {
            if (!state.audioBuffer) return;

            const audioCtx = new AudioContext();
            const source = audioCtx.createBufferSource();
            source.buffer = state.audioBuffer;
            source.connect(audioCtx.destination);
            source.start();

            let startTime = audioCtx.currentTime;
            const duration = state.audioBuffer.duration;

            function updatePlayhead() {
                const elapsed = audioCtx.currentTime - startTime;
                if (elapsed <= duration) {
                    els.playhead.style.left = (elapsed / duration * 100) + '%';
                    requestAnimationFrame(updatePlayhead);
                } else {
                    els.playhead.style.left = '0';
                }
            }

            els.playhead.style.display = 'block';
            requestAnimationFrame(updatePlayhead);
        }

        // File inputs
        els.imageInput.onchange = async e => {
            if (e.target.files.length > 0) {
                state.imageFile = e.target.files[0];
                els.filename.value = state.imageFile.name.replace(/\.[^/.]+$/, '.webm');
                drawImage();
                els.status.textContent = 'Image loaded';
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
                    drawAudioWaveform();
                    els.previewBtn.disabled = false;
                    els.status.textContent = 'Audio loaded';
                } catch (err) {
                    els.status.textContent = 'Audio load error: ' + err.message;
                }
            }
        };

        // Initial setup
        drawImage();
    </script>
</body>

</html>
