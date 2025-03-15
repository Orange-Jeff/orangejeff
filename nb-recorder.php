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
    <style>
        :root {
            --primary-color: #0056b3;
            --secondary-color: #333;
            --background-color: rgb(255, 255, 255);
            --text-color: #222;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--background-color);
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        #recorder-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 17px;
            background: var(--background-color);
            border-radius: 8px;
            box-shadow: none;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: var(--background-color);
            border-bottom: 1px solid #dee2e6;
            padding: 8px 17px;
        }

        .header h1 {
            color: #0056b3;
            margin-top: 20px;
            line-height: 1.2;
            font-weight: bold;
            font-size: 18px;
        }

        .content {
            padding: 0 17px;
            text-align: left;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Video container styles */
        #video-container {
            width: 100%;
            aspect-ratio: 16/9;
            background: #000;
            margin: 10px 0;
            position: relative;
            border-radius: 8px;
            overflow: hidden;
        }

        #main-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: #000;
        }

        .button-group {
            display: flex;
            gap: 10px;
            padding: 10px 0;
            flex-wrap: wrap;
        }

        button {
            background: #0056b3;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
            min-width: 100px;
        }

        button:hover:not(:disabled) {
            background: #004494;
        }

        button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        #timer {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: var(--secondary-color);
            min-width: 80px;
        }

        #audioPlayers {
            margin: 20px 0;
            flex: 1;
        }

        #audioPlayers div {
            margin: 15px 0;
        }

        #audioPlayers div h3 {
            margin-bottom: 5px;
        }

        audio {
            width: 100%;
            margin: 10px 0;
        }

        #mobile-blocker {
            display: none;
            background-color: #ffcccc;
            color: #333;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }

        canvas {
            display: block;
            width: 100%;
            margin: 5px 0;
            border: 1px solid #ccc;
            background-color: #e0f0ff;
            border: 2px solid #0056b3;
            height: 150px;
            position: relative;
            transition: all 0.3s ease;
            overflow: hidden;
            box-sizing: border-box;
        }

        /* Camera selection styles */
        .camera-selection {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            padding: 10px;
            border-radius: 5px;
            color: white;
            z-index: 100;
            min-width: 200px;
        }

        .camera-option {
            padding: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .camera-option:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .camera-option.active {
            background: rgba(0, 150, 255, 0.4);
        }

        /* Recording indicator */
        .recording-indicator {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(255, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            display: none;
        }

        .recording-indicator.active {
            display: block;
            animation: pulse 1.5s infinite;
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

        .camera-controls {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .control-group {
            margin: 5px 0;
        }

        .control-group label {
            display: block;
            margin-bottom: 3px;
            font-size: 12px;
            opacity: 0.8;
        }

        .control-group input[type="range"] {
            width: 100%;
            margin: 2px 0;
        }

        .control-group select {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 3px;
            border-radius: 3px;
        }
    </style>
</head>

<body>
    <div id="recorder-container">
        <div class="header">
            <h1>NetBound Tools: Dual Audio Recorder</h1>
        </div>
        <div class="content">
            <div id="mobile-blocker">
                Notice: This tool does not work on mobile devices. Please use a desktop computer.
            </div>
            <div id="video-container">
                <video id="main-video" autoplay playsinline muted></video>
                <div class="recording-indicator">REC</div>
                <div class="camera-selection">
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
            <div class="button-group">
                <button id="startRecording">Start Recording</button>
                <button id="stopRecording" disabled>Stop Recording</button>
                <button id="btnReset">Reset</button>
                <button id="flip-video">Flip Camera</button>
                <span id="timer">00:00</span>
            </div>
            <div id="status"></div>
            <div id="audioPlayers"></div>
            <div id="mergeOption">
                <h3>Download Options:</h3>
                <div class="button-group">
                    <button id="downloadSeparate" disabled>Download Separate Audio Files</button>
                    <button id="downloadCombined" disabled>Download Combined Audio</button>
                    <button id="downloadVideo" disabled>Download Silent Video</button>
                </div>
            </div>
        </div>
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

            function initializeRecorder() {
                const startRecordingButton = document.getElementById('startRecording');
                const stopRecordingButton = document.getElementById('stopRecording');
                const downloadSeparateButton = document.getElementById('downloadSeparate');
                const downloadCombinedButton = document.getElementById('downloadCombined');
                const downloadVideoButton = document.getElementById('downloadVideo');
                const timerElement = document.getElementById('timer');
                const statusElement = document.getElementById('status');
                const audioPlayersElement = document.getElementById('audioPlayers');

                startRecordingButton.addEventListener('click', startRecording);
                stopRecordingButton.addEventListener('click', stopRecording);
                downloadSeparateButton.addEventListener('click', downloadSeparateFiles);
                downloadCombinedButton.addEventListener('click', downloadCombinedFile);
                downloadVideoButton.addEventListener('click', downloadVideo);

                async function startRecording() {
                    if (isRecording) {
                        audioPlayersElement.innerHTML = '';
                    }
                    try {
                        statusElement.textContent = "Requesting permissions...";
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
                        statusElement.textContent = "Permissions granted. Starting recording...";

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
                        statusElement.textContent = "Recording in progress...";
                    } catch (error) {
                        console.error('Error starting recording:', error);
                        statusElement.textContent = "Error: " + error.message;
                    }
                }

                function stopRecording() {
                    statusElement.textContent = "Stopping recording...";
                    micRecorder.stopRecording(() => {
                        micBlob = micRecorder.getBlob();
                        createAudioPlayer(micBlob, 'Microphone Recording', 'wav');
                    });
                    tabRecorder.stopRecording(() => {
                        tabBlob = tabRecorder.getBlob();
                        createAudioPlayer(tabBlob, 'Tab Audio Recording', 'wav');
                    });
                    videoRecorder.stopRecording(() => {
                        videoBlob = videoRecorder.getBlob();
                    });

                    isRecording = false;
                    startRecordingButton.disabled = false;
                    stopRecordingButton.disabled = true;
                    document.querySelector('.recording-indicator').classList.remove('active');
                    stopTimer();

                    micStream.getTracks().forEach(track => track.stop());
                    tabStream.getTracks().forEach(track => track.stop());
                    // Don't stop videoStream as we want to keep the preview running

                    downloadSeparateButton.disabled = false;
                    downloadCombinedButton.disabled = false;
                    downloadVideoButton.disabled = false;
                    statusElement.textContent = "Recording stopped.";
                }

                function createAudioPlayer(blob, title, format) {
                    const audioUrl = URL.createObjectURL(blob);
                    const audioPlayer = document.createElement('audio');
                    audioPlayer.src = audioUrl;
                    audioPlayer.controls = true;

                    const container = document.createElement('div');
                    container.appendChild(document.createElement('h3')).textContent = title;
                    container.appendChild(audioPlayer);

                    // Add trim controls
                    const startTrimLabel = document.createElement('label');
                    startTrimLabel.textContent = 'Start Trim (seconds):';
                    const startTrimSlider = document.createElement('input');
                    startTrimSlider.type = 'range';
                    startTrimSlider.min = '0';
                    startTrimSlider.max = '5';
                    startTrimSlider.step = '0.1';
                    startTrimSlider.value = '0';
                    container.appendChild(startTrimLabel);
                    container.appendChild(startTrimSlider);

                    const canvas = document.createElement('canvas');
                    canvas.width = 300;
                    canvas.height = 50;
                    container.appendChild(canvas);

                    startTrimSlider.addEventListener('input', () => {
                        trimAudio(blob, audioPlayer, startTrimSlider.value, canvas);
                    });

                    drawWaveform(blob, canvas);

                    audioPlayersElement.appendChild(container);
                }

                async function trimAudio(blob, audioPlayer, startTrim, canvas) {
                    const audioContext = new AudioContext();
                    const audioBuffer = await audioContext.decodeAudioData(await blob.arrayBuffer());

                    const sampleRate = audioBuffer.sampleRate;
                    const numChannels = audioBuffer.numberOfChannels;
                    const trimTime = parseFloat(startTrim);
                    const startSample = Math.floor(trimTime * sampleRate);
                    const bufferLength = audioBuffer.length;

                    const trimmedBufferLength = bufferLength - startSample;

                    const offlineContext = new OfflineAudioContext(numChannels, trimmedBufferLength, sampleRate);
                    const trimmedBuffer = offlineContext.createBuffer(numChannels, trimmedBufferLength, sampleRate);

                    for (let channel = 0; channel < numChannels; channel++) {
                        const channelData = audioBuffer.getChannelData(channel);
                        const trimmedChannelData = trimmedBuffer.getChannelData(channel);
                        for (let i = 0; i < trimmedBufferLength; i++) {
                            trimmedChannelData[i] = channelData[startSample + i];
                        }
                    }

                    const source = offlineContext.createBufferSource();
                    source.buffer = trimmedBuffer;
                    source.connect(offlineContext.destination);
                    source.start();

                    const renderedBuffer = await offlineContext.startRendering();

                    const wav = new WavAudioEncoder(sampleRate, numChannels);
                    for (let channel = 0; channel < numChannels; channel++) {
                        wav.addChannel(renderedBuffer.getChannelData(channel));
                    }
                    const trimmedBlob = wav.finish();

                    const trimmedAudioUrl = URL.createObjectURL(trimmedBlob);
                    audioPlayer.src = trimmedAudioUrl;

                    drawWaveform(trimmedBlob, canvas);
                }

                async function drawWaveform(blob, canvas) {
                    const audioContext = new AudioContext();
                    const audioBuffer = await audioContext.decodeAudioData(await blob.arrayBuffer());
                    const channelData = audioBuffer.getChannelData(0);
                    const waveformData = [];
                    const blockSize = Math.floor(audioBuffer.length / canvas.width);
                    for (let i = 0; i < canvas.width; i++) {
                        let sum = 0;
                        for (let j = 0; j < blockSize; j++) {
                            sum += Math.abs(channelData[(i * blockSize) + j]);
                        }
                        waveformData.push(sum / blockSize);
                    }

                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#0056b3';
                    const scale = 10; // Adjust this value to increase/decrease waveform height
                    for (let i = 0; i < canvas.width; i++) {
                        const x = i;
                        const y = canvas.height / 2 + waveformData[i] * canvas.height / scale;
                        const height = Math.abs(waveformData[i] * canvas.height) * scale;
                        ctx.fillRect(x, canvas.height / 2 - height / 2, 1, height);
                    }
                }

                function downloadSeparateFiles() {
                    downloadSeparateButton.textContent = 'Downloading...';
                    downloadSeparateButton.style.backgroundColor = '#004494';
                    downloadBlob(micBlob, 'microphone-recording.wav');
                    downloadBlob(tabBlob, 'tab-audio-recording.wav');
                    downloadSeparateButton.textContent = 'Download Separate Files';
                    downloadSeparateButton.style.backgroundColor = '';
                }

                async function downloadCombinedFile() {
                    if (micBlob && tabBlob) {
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

                        } catch (error) {
                            console.error('Error combining audio:', error);
                            statusElement.textContent = "Error combining audio files: " + error.message;
                        } finally {
                            downloadCombinedButton.textContent = 'Download Combined File';
                            downloadCombinedButton.style.backgroundColor = '';
                        }
                    }
                }

                async function downloadVideo() {
                    if (videoBlob) {
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
                        } catch (error) {
                            console.error('Error downloading video:', error);
                            statusElement.textContent = "Error downloading video: " + error.message;
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
                            deviceId: deviceId ? {
                                exact: deviceId
                            } : undefined
                        }
                    };

                    // Add quality constraints
                    switch (quality) {
                        case 'qvga':
                            constraints.video.width = {
                                ideal: 320
                            };
                            constraints.video.height = {
                                ideal: 240
                            };
                            break;
                        case 'vga':
                            constraints.video.width = {
                                ideal: 640
                            };
                            constraints.video.height = {
                                ideal: 480
                            };
                            break;
                        case 'hd':
                            constraints.video.width = {
                                ideal: 1280
                            };
                            constraints.video.height = {
                                ideal: 720
                            };
                            break;
                        case 'fhd':
                            constraints.video.width = {
                                ideal: 1920
                            };
                            constraints.video.height = {
                                ideal: 1080
                            };
                            break;
                    }

                    videoStream = await navigator.mediaDevices.getUserMedia(constraints);
                    const mainVideo = document.getElementById('main-video');
                    mainVideo.srcObject = videoStream;
                    mainVideo.style.transform = isFlipped ? 'scaleX(-1)' : 'scaleX(1)';

                    // Try to set zoom if available
                    const videoTrack = videoStream.getVideoTracks()[0];
                    if (videoTrack) {
                        const capabilities = videoTrack.getCapabilities();
                        if (capabilities.zoom) {
                            const zoomSlider = document.getElementById('camera-zoom');
                            zoomSlider.min = capabilities.zoom.min * 100;
                            zoomSlider.max = capabilities.zoom.max * 100;
                            zoomSlider.value = 100;
                            zoomSlider.style.display = 'block';
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

            document.getElementById('camera-zoom').addEventListener('input', (e) => {
                const zoomLevel = e.target.value / 100;
                const videoTrack = videoStream?.getVideoTracks()[0];
                if (videoTrack) {
                    try {
                        videoTrack.applyConstraints({
                            advanced: [{
                                zoom: zoomLevel
                            }]
                        });
                    } catch (err) {
                        console.error("Could not apply zoom:", err);
                    }
                }
            });
        </script>
</body>

</html>
