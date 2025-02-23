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
        }

        #recorder-container {
            max-width: 600px;
            margin: 0;
            padding: 0 17px;
            background: var(--background-color);
            border-radius: 8px;
            box-shadow: none;
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
            max-width: 700px;
            margin: 0;
            padding: 0 17px;
            background: #ffffff;
        }

        .button-group {
            display: flex;
            gap: 10px;
            padding: 10px 0;
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
        }

        #audioPlayers {
            margin: 20px 0;
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
            <div class="button-group">
                <button id="startRecording">Start Recording</button>
                <button id="stopRecording" disabled>Stop Recording</button>
                <button id="btnReset">Reset</button>
                <span id="timer">00:00</span>
            </div>
            <div id="status"></div>
            <div id="audioPlayers"></div>
            <div id="mergeOption">
                <h3>Download Options:</h3>
                <button id="downloadSeparate" disabled>Download Separate Files</button>
                <button id="downloadCombined" disabled>Download Combined File</button>
            </div>
        </div>
        <script>
            let micRecorder, tabRecorder;
            let micStream, tabStream;
            let isRecording = false;
            let micBlob, tabBlob;
            let timerInterval;
            let startTime;

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
                const timerElement = document.getElementById('timer');
                const statusElement = document.getElementById('status');
                const audioPlayersElement = document.getElementById('audioPlayers');

                startRecordingButton.addEventListener('click', startRecording);
                stopRecordingButton.addEventListener('click', stopRecording);
                downloadSeparateButton.addEventListener('click', downloadSeparateFiles);
                downloadCombinedButton.addEventListener('click', downloadCombinedFile);

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

                        micRecorder.startRecording();
                        tabRecorder.startRecording();

                        isRecording = true;
                        startRecordingButton.disabled = true;
                        stopRecordingButton.disabled = false;
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

                    isRecording = false;
                    startRecordingButton.disabled = false;
                    stopRecordingButton.disabled = true;
                    stopTimer();

                    micStream.getTracks().forEach(track => track.stop());
                    tabStream.getTracks().forEach(track => track.stop());

                    downloadSeparateButton.disabled = false;
                    downloadCombinedButton.disabled = false;
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
                startRecordingButton.disabled = false;
                stopRecordingButton.disabled = true;
                timerElement.textContent = "00:00";
                statusElement.textContent = "Reset complete";
            });
        </script>
</body>

</html>
