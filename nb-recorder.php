<!DOCTYPE html>
<html lang="en">

<head>
    <!--
// filename: dual-audio-record.php
// Version 4.4 - December 1, 2024
// Created by OrangeJeff with the assistance of Claude
// Description: Records desktop audio separate from mic
// Not functional on mobile
-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetBound Tools: Dual Audio Recorder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="nb-recorder.css">
</head>

<body>
    <div class="tool-container">
        <div class="tool-header">
            <h1 class="tool-title">NetBound Tools: Dual Audio Recorder</h1>

            <!-- Status bar that doubles as drag zone -->
            <div id="statusBar" class="status-bar">
                <!-- Progress is now shown inside status bar -->
                <div id="status" class="status-message info">Ready</div>
            </div>

            <!-- Top row buttons -->
            <div class="button-controls">
                <button class="command-button" onclick="document.getElementById('imageInput').click()">
                    <i class="fas fa-video"></i> Camera Source
                </button>
                <button class="command-button" onclick="location.reload()">
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>
        </div>

        <div class="work-area">
            <div class="preview-area">
                <div id="mobile-blocker">
                    Notice: This tool does not work on mobile devices. Please use a desktop computer.
                </div>

                <!-- Content preview area -->
                <div id="video-container">
                    <video id="main-video" autoplay playsinline muted></video>
                    <div class="recording-indicator">REC</div>
                    <div class="camera-selection hidden">
                        <div id="camera-list"></div>
                        <div class="camera-controls">
                            <div class="control-group">
                                <label for="camera-quality">Quality:</label>
                                <select id="camera-quality">
                                    <option value="qvga">Low (QVGA)</option>
                                    <option value="vga">Medium (VGA)</option>
                                    <option value="hd" selected>High (HD)</option>
                                    <option value="fhd">Full HD</option>
                                </select>
                            </div>
                            <div class="control-group">
                                <label for="camera-zoom">Zoom:</label>
                                <input type="range" id="camera-zoom" min="100" max="400" value="100" step="10">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recording controls -->
                <div class="button-controls">
                    <button id="startRecording" class="command-button">
                        <i class="fas fa-record-vinyl"></i> Start Recording
                    </button>
                    <button id="stopRecording" class="command-button" disabled>
                        <i class="fas fa-stop"></i> Stop Recording
                    </button>
                    <button id="btnReset" class="command-button">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                    <button id="flip-video" class="command-button">
                        <i class="fas fa-sync"></i> Flip Camera
                    </button>
                    <span id="timer">00:00</span>
                </div>

                <!-- Audio players section -->
                <div id="audioPlayers"></div>

                <!-- Playback sync controls -->
                <div class="playback-controls">
                    <div class="button-controls">
                        <button id="playBothSync" class="command-button" disabled>
                            <i class="fas fa-play-circle"></i> Play Both In Sync
                        </button>
                        <button id="pauseBoth" class="command-button" disabled>
                            <i class="fas fa-pause-circle"></i> Pause
                        </button>
                        <button id="stopBoth" class="command-button" disabled>
                            <i class="fas fa-stop-circle"></i> Stop
                        </button>
                    </div>
                </div>

                <!-- Download options -->
                <div id="mergeOption">
                    <h3>Download Options:</h3>
                    <div class="button-controls">
                        <button id="downloadSeparate" class="command-button" disabled>
                            <i class="fas fa-file-audio"></i> Download Separate Audio Files
                        </button>
                        <button id="downloadCombined" class="command-button" disabled>
                            <i class="fas fa-layer-group"></i> Download Combined Audio
                        </button>
                        <button id="downloadVideo" class="command-button" disabled>
                            <i class="fas fa-video"></i> Download Silent Video
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden file inputs -->
    <input type="file" id="imageInput" accept="video/*" style="display: none">

    <!-- Include RecordRTC -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/RecordRTC/5.6.2/RecordRTC.min.js"></script>
    <script>
        let micRecorder, tabRecorder, videoRecorder;
        let micStream, tabStream, videoStream;
        let isRecording = false;
        let micBlob, tabBlob, videoBlob;
        let timerInterval;
        let startTime;
        let cameras = [];
        let currentCameraIndex = 0;
        let isFlipped = false;
        let hasUnsavedRecordings = false;
        let audioPlayers = [];
        let isPlayingSync = false;

        function detectMobile() {
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            const mobileBlocker = document.getElementById('mobile-blocker');
            if (isMobile) {
                mobileBlocker.style.display = 'block';
                document.getElementById('startRecording').disabled = true;
            }
        }

        window.onload = detectMobile;

        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/RecordRTC/5.6.2/RecordRTC.min.js';
        script.onload = initializeRecorder;
        document.head.appendChild(script);

        // Add timestamp-based baseName for downloads
        let baseName = 'recording-' + new Date().toISOString().slice(0, 19).replace(/[:]/g, '-');

        function initializeRecorder() {
            const startRecordingButton = document.getElementById('startRecording');
            const stopRecordingButton = document.getElementById('stopRecording');
            const downloadSeparateButton = document.getElementById('downloadSeparate');
            const downloadCombinedButton = document.getElementById('downloadCombined');
            const downloadVideoButton = document.getElementById('downloadVideo');
            const timerElement = document.getElementById('timer');
            const statusElement = document.getElementById('status');
            const audioPlayersElement = document.getElementById('audioPlayers');

            // Update status handling to match template
            const status = {
                update(message, type = 'info') {
                    const container = document.getElementById('statusBar');
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `status-message ${type}`;
                    messageDiv.textContent = message;
                    container.insertBefore(messageDiv, container.firstChild);
                    container.scrollTop = 0;
                }
            };

            // Initialize with welcome message
            status.update('Dual Audio Recorder ready. Click Camera Source to begin.', 'info');

            startRecordingButton.addEventListener('click', startRecording);
            stopRecordingButton.addEventListener('click', stopRecording);
            downloadSeparateButton.addEventListener('click', downloadSeparateFiles);
            downloadCombinedButton.addEventListener('click', downloadCombinedFile);
            downloadVideoButton.addEventListener('click', downloadVideo);

            async function startRecording() {
                if (hasUnsavedRecordings) {
                    status.update("Please save or reset before starting a new recording", 'error');
                    return;
                }

                if (isRecording) {
                    audioPlayersElement.innerHTML = '';
                }
                try {
                    status.update("Requesting permissions...", 'info');
                    micStream = await navigator.mediaDevices.getUserMedia({
                        audio: true
                    });
                    tabStream = await navigator.mediaDevices.getDisplayMedia({
                        audio: true,
                        video: true
                    });
                    // Ensure we have video stream
                    if (!videoStream) {
                        await startCamera();
                    }
                    status.update("Permissions granted. Starting recording...");

                    micRecorder = new RecordRTC(micStream, {
                        type: 'audio',
                        mimeType: 'audio/wav',
                        recorderType: RecordRTC.StereoAudioRecorder
                    });
                    tabRecorder = new RecordRTC(tabStream, {
                        type: 'audio',
                        mimeType: 'audio/wav',
                        recorderType: RecordRTC.StereoAudioRecorder
                    });
                    videoRecorder = new RecordRTC(videoStream, {
                        type: 'video',
                        mimeType: 'video/webm',
                        recorderType: RecordRTC.MediaStreamRecorder
                    });

                    micRecorder.startRecording();
                    tabRecorder.startRecording();
                    videoRecorder.startRecording();

                    isRecording = true;
                    startRecordingButton.disabled = true;
                    stopRecordingButton.disabled = false;
                    document.querySelector('.recording-indicator').classList.add('active');
                    startTimer();
                    status.update("Recording in progress...", 'info');
                } catch (error) {
                    console.error('Error starting recording:', error);
                    status.update("Error: " + error.message, 'error');
                }
            }

            function stopRecording() {
                status.update("Stopping recording...", 'info');
                micRecorder.stopRecording(() => {
                    micBlob = micRecorder.getBlob();
                    createWaveformPlayer(micBlob, 'Microphone Recording');
                });
                tabRecorder.stopRecording(() => {
                    tabBlob = tabRecorder.getBlob();
                    createWaveformPlayer(tabBlob, 'Tab Audio Recording');
                });
                videoRecorder.stopRecording(() => {
                    videoBlob = videoRecorder.getBlob();
                });

                isRecording = false;
                hasUnsavedRecordings = true;
                startRecordingButton.disabled = true; // Disable until reset
                stopRecordingButton.disabled = true;
                document.querySelector('.recording-indicator').classList.remove('active');
                stopTimer();

                micStream.getTracks().forEach(track => track.stop());
                tabStream.getTracks().forEach(track => track.stop());

                downloadSeparateButton.disabled = false;
                downloadCombinedButton.disabled = false;
                downloadVideoButton.disabled = false;
                status.update("Recording completed successfully. Save files or reset to record again.", 'success');
            }

            function createWaveformPlayer(blob, title) {
                const container = document.createElement('div');
                container.classList.add('waveform-container');

                // Add title and controls
                const header = document.createElement('div');
                header.className = 'waveform-header';

                const titleEl = document.createElement('h3');
                titleEl.textContent = title;
                titleEl.className = 'waveform-title';

                const controls = document.createElement('div');
                controls.className = 'waveform-controls';

                const playButton = document.createElement('button');
                playButton.className = 'command-button';
                playButton.innerHTML = '<i class="fas fa-play"></i>';

                const stopButton = document.createElement('button');
                stopButton.className = 'command-button';
                stopButton.innerHTML = '<i class="fas fa-stop"></i>';
                stopButton.disabled = true;

                controls.appendChild(playButton);
                controls.appendChild(stopButton);

                // Create save button
                const saveButton = document.createElement('button');
                saveButton.className = 'command-button waveform-save';
                saveButton.innerHTML = '<i class="fas fa-save"></i> Save';
                saveButton.addEventListener('click', () => {
                    downloadBlob(blob, title.replace(' ', '-').toLowerCase() + '.wav');
                });

                header.appendChild(titleEl);
                header.appendChild(controls);
                header.appendChild(saveButton);
                container.appendChild(header);

                // Create waveform canvas
                const canvas = document.createElement('canvas');
                canvas.classList.add('waveform');
                canvas.width = container.clientWidth || 768;
                canvas.height = 37;

                // Create playhead
                const playhead = document.createElement('div');
                playhead.classList.add('playhead');

                // Create hidden audio element
                const audio = document.createElement('audio');
                audio.src = URL.createObjectURL(blob);

                // Add elements
                container.appendChild(canvas);
                container.appendChild(playhead);
                container.appendChild(audio);

                // Draw waveform
                drawWaveform(blob, canvas);

                // Setup playback controls
                let isPlaying = false;

                // Individual track controls
                playButton.addEventListener('click', () => {
                    if (!isPlaying) {
                        if (isPlayingSync) stopSyncedPlayback();
                        audio.play();
                        isPlaying = true;
                        playButton.innerHTML = '<i class="fas fa-pause"></i>';
                        stopButton.disabled = false;
                        playhead.style.display = 'block';
                    } else {
                        audio.pause();
                        isPlaying = false;
                        playButton.innerHTML = '<i class="fas fa-play"></i>';
                    }
                });

                stopButton.addEventListener('click', () => {
                    audio.pause();
                    audio.currentTime = 0;
                    isPlaying = false;
                    playButton.innerHTML = '<i class="fas fa-play"></i>';
                    stopButton.disabled = true;
                    playhead.style.display = 'none';
                    playhead.style.left = '0';
                });

                // Click to seek
                canvas.addEventListener('click', (e) => {
                    const rect = canvas.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const percentage = x / rect.width;

                    audio.currentTime = audio.duration * percentage;
                    if (!isPlaying && !isPlayingSync) {
                        playButton.click();
                    }
                });

                // Update playhead
                audio.addEventListener('timeupdate', () => {
                    const percentage = (audio.currentTime / audio.duration) * 100;
                    playhead.style.left = `${percentage}%`;
                });

                // Handle playback end
                audio.addEventListener('ended', () => {
                    isPlaying = false;
                    playButton.innerHTML = '<i class="fas fa-play"></i>';
                    stopButton.disabled = true;
                    playhead.style.left = '0';
                    playhead.style.display = 'none';
                });

                // Store player info for sync functionality
                const playerInfo = {
                    audio,
                    playButton,
                    stopButton,
                    playhead,
                    container,
                    title
                };
                audioPlayers.push(playerInfo);

                // Enable sync controls if we have both recordings
                if (audioPlayers.length === 2) {
                    document.getElementById('playBothSync').disabled = false;
                    document.getElementById('pauseBoth').disabled = false;
                    document.getElementById('stopBoth').disabled = false;
                }

                audioPlayersElement.appendChild(container);
                return playerInfo;
            }

            // Add synced playback controls
            function startSyncedPlayback() {
                if (audioPlayers.length !== 2) return;

                stopAllPlayback(); // Stop any current playback
                isPlayingSync = true;

                // Start both players simultaneously
                audioPlayers.forEach(player => {
                    player.audio.currentTime = 0;
                    player.audio.play();
                    player.playButton.innerHTML = '<i class="fas fa-pause"></i>';
                    player.stopButton.disabled = false;
                    player.playhead.style.display = 'block';
                });

                document.getElementById('playBothSync').innerHTML = '<i class="fas fa-pause-circle"></i> Pause Both';
            }

            function pauseSyncedPlayback() {
                audioPlayers.forEach(player => {
                    player.audio.pause();
                    player.playButton.innerHTML = '<i class="fas fa-play"></i>';
                });
                isPlayingSync = false;
                document.getElementById('playBothSync').innerHTML = '<i class="fas fa-play-circle"></i> Play Both In Sync';
            }

            function stopSyncedPlayback() {
                audioPlayers.forEach(player => {
                    player.audio.pause();
                    player.audio.currentTime = 0;
                    player.playButton.innerHTML = '<i class="fas fa-play"></i>';
                    player.stopButton.disabled = true;
                    player.playhead.style.display = 'none';
                    player.playhead.style.left = '0';
                });
                isPlayingSync = false;
                document.getElementById('playBothSync').innerHTML = '<i class="fas fa-play-circle"></i> Play Both In Sync';
            }

            function stopAllPlayback() {
                audioPlayers.forEach(player => {
                    player.audio.pause();
                    player.playButton.innerHTML = '<i class="fas fa-play"></i>';
                });
                isPlayingSync = false;
            }

            // Add sync control event listeners
            document.getElementById('playBothSync').addEventListener('click', () => {
                if (!isPlayingSync) {
                    startSyncedPlayback();
                } else {
                    pauseSyncedPlayback();
                }
            });

            document.getElementById('pauseBoth').addEventListener('click', pauseSyncedPlayback);
            document.getElementById('stopBoth').addEventListener('click', stopSyncedPlayback);

            async function drawWaveform(blob, canvas) {
                const audioContext = new AudioContext();
                const audioBuffer = await audioContext.decodeAudioData(await blob.arrayBuffer());
                const channelData = audioBuffer.getChannelData(0);
                const waveformData = [];
                const blockSize = Math.floor(audioBuffer.length / canvas.width);

                // Process audio data
                for (let i = 0; i < canvas.width; i++) {
                    let min = 0;
                    let max = 0;
                    for (let j = 0; j < blockSize; j++) {
                        const datum = channelData[(i * blockSize) + j];
                        if (datum < min) min = datum;
                        if (datum > max) max = datum;
                    }
                    waveformData.push([min, max]);
                }

                // Draw waveform
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#0056b3';
                const center = canvas.height / 2;

                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.beginPath();

                // Draw the waveform using both min and max values
                waveformData.forEach(([min, max], i) => {
                    const height = Math.max(1, Math.abs(max - min) * center);
                    ctx.fillRect(i, center - height/2, 1, height);
                });
            }

            function downloadSeparateFiles() {
                status.update("Downloading separate audio files...", 'info');
                downloadSeparateButton.textContent = 'Downloading...';
                downloadSeparateButton.style.backgroundColor = '#004494';
                downloadBlob(micBlob, 'microphone-recording.wav');
                downloadBlob(tabBlob, 'tab-audio-recording.wav');
                downloadSeparateButton.textContent = 'Download Separate Files';
                downloadSeparateButton.style.backgroundColor = '';
                hasUnsavedRecordings = false;
                startRecordingButton.disabled = false;
                status.update("Files downloaded successfully. Ready for new recording.", 'success');
            }

            async function downloadCombinedFile() {
                if (micBlob && tabBlob) {
                    status.update("Combining and downloading audio...", 'info');
                    downloadCombinedButton.textContent = 'Downloading...';
                    downloadCombinedButton.style.backgroundColor = '#004494';
                    try {
                        const micBuffer = await micBlob.arrayBuffer();
                        const tabBuffer = await tabBlob.arrayBuffer();

                        const audioContext = new AudioContext();

                        const [micAudio, tabAudio] = await Promise.all([
                            audioContext.decodeAudioData(micBuffer),
                            audioContext.decodeAudioData(tabBuffer)
                        ]);

                        const maxLength = Math.max(micAudio.length, tabAudio.length);
                        const offlineContext = new OfflineAudioContext(
                            2,
                            maxLength,
                            audioContext.sampleRate
                        );

                        const micSource = offlineContext.createBufferSource();
                        const tabSource = offlineContext.createBufferSource();

                        micSource.buffer = micAudio;
                        tabSource.buffer = tabAudio;

                        micSource.connect(offlineContext.destination);
                        tabSource.connect(offlineContext.destination);

                        micSource.start(0);
                        tabSource.start(0);

                        const renderedBuffer = await offlineContext.startRendering();

                        const length = renderedBuffer.length;
                        const numberOfChannels = renderedBuffer.numberOfChannels;
                        const sampleRate = renderedBuffer.sampleRate;
                        const wav = new WavAudioEncoder(sampleRate, numberOfChannels);

                        for (let channel = 0; channel < numberOfChannels; channel++) {
                            wav.addChannel(renderedBuffer.getChannelData(channel));
                        }

                        const combinedBlob = wav.finish();
                        downloadBlob(combinedBlob, 'combined-audio.wav');
                        hasUnsavedRecordings = false;
                        startRecordingButton.disabled = false;
                        status.update("Combined audio downloaded successfully. Ready for new recording.", 'success');

                    } catch (error) {
                        console.error('Error combining audio:', error);
                        status.update("Error combining audio files: " + error.message, 'error');
                    } finally {
                        downloadCombinedButton.textContent = 'Download Combined File';
                        downloadCombinedButton.style.backgroundColor = '';
                    }
                }
            }

            async function downloadVideo() {
                if (videoBlob) {
                    status.update("Downloading video file...", 'info');
                    downloadVideoButton.textContent = 'Downloading...';
                    downloadVideoButton.style.backgroundColor = '#004494';
                    try {
                        const a = document.createElement('a');
                        a.href = URL.createObjectURL(videoBlob);
                        a.download = `${baseName}-video.mp4`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(a.href);
                        hasUnsavedRecordings = false;
                        startRecordingButton.disabled = false;
                        status.update("Video downloaded successfully. Ready for new recording.", 'success');
                    } catch (error) {
                        console.error('Error downloading video:', error);
                        status.update("Error downloading video: " + error.message, 'error');
                    } finally {
                        downloadVideoButton.textContent = 'Download Silent Video';
                        downloadVideoButton.style.backgroundColor = '';
                    }
                }
            }

            function downloadBlob(blob, fileName) {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                document.body.appendChild(a);
                a.style = 'display: none';
                a.href = url;
                a.download = fileName;
                a.click();
                window.URL.revokeObjectURL(url);
            }

            function startTimer() {
                startTime = Date.now();
                updateTimer();
                timerInterval = setInterval(updateTimer, 1000);
            }

            function stopTimer() {
                clearInterval(timerInterval);
            }

            function updateTimer() {
                const elapsedTime = Date.now() - startTime;
                const minutes = Math.floor(elapsedTime / 60000);
                const seconds = Math.floor((elapsedTime % 60000) / 1000);
                timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
        }

        class WavAudioEncoder {
            constructor(sampleRate, numChannels) {
                this.sampleRate = sampleRate;
                this.numChannels = numChannels;
                this.chunks = [];
                this.dataViews = [];
            }

            addChannel(data) {
                this.chunks.push(data);
            }

            finish() {
                const dataSize = this.chunks[0].length * this.numChannels * 2;
                const buffer = new ArrayBuffer(44 + dataSize);
                const view = new DataView(buffer);

                writeString(view, 0, 'RIFF');
                view.setUint32(4, 36 + dataSize, true);
                writeString(view, 8, 'WAVE');
                writeString(view, 12, 'fmt ');
                view.setUint32(16, 16, true);
                view.setUint16(20, 1, true);
                view.setUint16(22, this.numChannels, true);
                view.setUint32(24, this.sampleRate, true);
                view.setUint32(28, this.sampleRate * this.numChannels * 2, true);
                view.setUint16(32, this.numChannels * 2, true);
                view.setUint16(34, 16, true);
                writeString(view, 36, 'data');
                view.setUint32(40, dataSize, true);

                const offset = 44;
                for (let i = 0; i < this.chunks[0].length; i++) {
                    for (let channel = 0; channel < this.numChannels; channel++) {
                        const sample = Math.max(-1, Math.min(1, this.chunks[channel][i]));
                        view.setInt16(offset + (i * this.numChannels + channel) * 2,
                            sample < 0 ? sample * 0x8000 : sample * 0x7FFF, true);
                    }
                }

                return new Blob([buffer], {
                    type: 'audio/wav'
                });
            }
        }

        function writeString(view, offset, string) {
            for (let i = 0; i < string.length; i++) {
                view.setUint8(offset + i, string.charCodeAt(i));
            }
        }
        document.getElementById('btnReset').addEventListener('click', () => {
            if (hasUnsavedRecordings) {
                if (!confirm('You have unsaved recordings. Are you sure you want to reset?')) {
                    return;
                }
            }

            location.reload();
            micStream?.getTracks().forEach(track => track.stop());
            tabStream?.getTracks().forEach(track => track.stop());
            audioPlayersElement.innerHTML = '';
            downloadSeparateButton.disabled = true;
            downloadCombinedButton.disabled = true;
            downloadVideoButton.disabled = true;
            startRecordingButton.disabled = false;
            stopRecordingButton.disabled = true;
            timerElement.textContent = "00:00";
            statusElement.textContent = "Reset complete";
            hasUnsavedRecordings = false;
        });

        // Add cookie handling functions
        function setCookie(name, value, days = 30) {
            const d = new Date();
            d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = "expires=" + d.toUTCString();
            document.cookie = name + "=" + value + ";" + expires + ";path=/";
        }

        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }

        // Enhance camera label formatting
        function formatCameraLabel(label, index) {
            if (!label) return `Camera ${index + 1}`;

            // Extract facing direction
            const facingMatch = label.match(/facing\s+(front|back)/i);
            const facingDir = facingMatch ? facingMatch[1].charAt(0).toUpperCase() + facingMatch[1].slice(1) : '';

            // Look for resolution info
            const resMatch = label.match(/(\d+)x(\d+)/);
            let resolution = '';
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

            // Combine all information
            if (facingDir) {
                return `${facingDir}${cameraType || ''}${resolution} Camera`;
            }

            // If no specific info found, use a cleaned up version of the label
            const cleanLabel = label.split(',')[0].replace(/\([^)]*\)/g, '').trim();
            return cleanLabel || `Camera ${index + 1}`;
        }

        async function getCameras() {
            try {
                console.log("Enumerating media devices...");
                const devices = await navigator.mediaDevices.enumerateDevices();
                const videoDevices = devices.filter(device => device.kind === 'videoinput');

                if (videoDevices.length > 0 && !videoDevices[0].label) {
                    console.log("Unlabeled cameras detected. Requesting permission first...");
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                facingMode: {
                                    ideal: "environment"
                                }
                            }
                        });
                        window.tempStream = stream;
                        const newDevices = await navigator.mediaDevices.enumerateDevices();
                        cameras = newDevices.filter(device => device.kind === 'videoinput');
                    } catch (permErr) {
                        console.error("Could not get camera permission:", permErr);
                    }
                } else {
                    cameras = videoDevices;
                }

                // Try to restore last used camera
                const lastUsedId = getCookie('lastUsedCamera');
                if (lastUsedId) {
                    const lastUsedIndex = cameras.findIndex(cam => cam.deviceId === lastUsedId);
                    if (lastUsedIndex >= 0) {
                        currentCameraIndex = lastUsedIndex;
                    }
                }

                updateCameraList();
                if (cameras.length > 0) {
                    await startCamera(cameras[currentCameraIndex].deviceId);
                }
                return cameras;
            } catch (err) {
                console.error("Error enumerating devices:", err);
                return [];
            }
        }

        function updateCameraList() {
            const cameraList = document.getElementById('camera-list');
            cameraList.innerHTML = '';
            cameras.forEach((camera, index) => {
                const div = document.createElement('div');
                div.className = `camera-option ${index === currentCameraIndex ? 'active' : ''}`;
                div.textContent = formatCameraLabel(camera.label, index);
                div.onclick = () => switchToCamera(index);
                cameraList.appendChild(div);
            });
        }

        async function startCamera(deviceId = null) {
            try {
                if (videoStream) {
                    videoStream.getTracks().forEach(track => track.stop());
                }

                const quality = document.getElementById('camera-quality').value;
                let constraints = {
                    video: {
                        deviceId: deviceId ? { exact: deviceId } : undefined,
                        zoom: true // Request zoom capability
                    }
                };

                // Add quality constraints
                switch (quality) {
                    case 'qvga':
                        constraints.video.width = { ideal: 320 };
                        constraints.video.height = { ideal: 240 };
                        break;
                    case 'vga':
                        constraints.video.width = { ideal: 640 };
                        constraints.video.height = { ideal: 480 };
                        break;
                    case 'hd':
                        constraints.video.width = { ideal: 1280 };
                        constraints.video.height = { ideal: 720 };
                        break;
                    case 'fhd':
                        constraints.video.width = { ideal: 1920 };
                        constraints.video.height = { ideal: 1080 };
                        break;
                }

                videoStream = await navigator.mediaDevices.getUserMedia(constraints);
                const mainVideo = document.getElementById('main-video');
                mainVideo.srcObject = videoStream;
                mainVideo.style.transform = isFlipped ? 'scaleX(-1)' : 'scaleX(1)';

                // Configure zoom if available
                const videoTrack = videoStream.getVideoTracks()[0];
                if (videoTrack) {
                    const capabilities = videoTrack.getCapabilities();
                    const settings = videoTrack.getSettings();

                    if (capabilities.zoom) {
                        const zoomSlider = document.getElementById('camera-zoom');
                        zoomSlider.min = capabilities.zoom.min * 100;
                        zoomSlider.max = capabilities.zoom.max * 100;
                        zoomSlider.value = (settings.zoom || 1) * 100;
                        zoomSlider.style.display = 'block';

                        // Apply initial zoom
                        await videoTrack.applyConstraints({
                            advanced: [{ zoom: settings.zoom || 1 }]
                        });
                    } else {
                        document.getElementById('camera-zoom').style.display = 'none';
                    }
                }

                return videoStream;
            } catch (err) {
                console.error("Camera access error:", err);
                return null;
            }
        }

        async function switchToCamera(index) {
            if (index >= 0 && index < cameras.length) {
                currentCameraIndex = index;
                const camera = cameras[index];
                await startCamera(camera.deviceId);
                updateCameraList();
                // Save camera preference
                setCookie('lastUsedCamera', camera.deviceId);
                // Hide camera panel after selection
                document.querySelector('.camera-selection').classList.add('hidden');
            }
        }

        function toggleFlipVideo() {
            isFlipped = !isFlipped;
            const mainVideo = document.getElementById('main-video');
            mainVideo.style.transform = isFlipped ? 'scaleX(-1)' : 'scaleX(1)';
        }

        // Initialize camera on load
        window.addEventListener('DOMContentLoaded', () => {
            getCameras();
            document.getElementById('flip-video').addEventListener('click', toggleFlipVideo);
        });

        // Add event listeners for controls
        document.getElementById('camera-quality').addEventListener('change', () => {
            startCamera(cameras[currentCameraIndex].deviceId);
        });

        document.getElementById('camera-zoom').addEventListener('input', async (e) => {
            const zoomLevel = e.target.value / 100;
            const videoTrack = videoStream?.getVideoTracks()[0];
            if (videoTrack) {
                try {
                    await videoTrack.applyConstraints({
                        advanced: [{ zoom: zoomLevel }]
                    });
                } catch (err) {
                    console.error("Could not apply zoom:", err);
                    status.update("This camera does not support zoom control", "error");
                }
            }
        });

        // Add camera panel toggle
        document.getElementById('imageInput').addEventListener('click', () => {
            const cameraPanel = document.querySelector('.camera-selection');
            cameraPanel.classList.toggle('hidden');
        });
    </script>
</body>

</html>
