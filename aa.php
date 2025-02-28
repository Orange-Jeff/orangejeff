<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>NetBound Tools: Video to Audio Converter</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f4f4f9;
            width: 550px;
        }

        .tool-container {
            background: #f4f4f9;
            height: auto;
            margin: 0;
            max-width: 600px;
            width: 100%;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .work-area {
            padding: 15px 15px 15px 0;
            height: auto;
            background: #f4f4f9;
            display: flex;
            width: 100%;
            flex-direction: column;
            align-items: flex-start;
        }

        .preview-area {
            padding: 10px;
            margin: 10px 0 10px 0;
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 4px;
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

        .status-bar {
            width: 100%;
            height: 80px;
            min-height: 80px;
            max-height: 80px;
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
            padding: 5px;
            margin: 2px 0;
            border-radius: 3px;
        }

        .status-message.info {
            background: #e3f2fd;
        }

        .status-message.success {
            background: #e8f5e9;
        }

        .status-message.error {
            background: #ffebee;
        }

        #video-preview {
            max-width: 100%;
            height: 400px;
            margin-top: 10px;
            display: none;
            object-fit: contain;
        }

        .status-bar.drag-over {
            background: #e3f2fd;
            border-color: #2196f3;
        }
    </style>
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lamejs/1.2.1/lame.all.js"></script>
</head>

<body>
    <div class="tool-container">
        <div class="tool-header">
            <h1 class="tool-title">NetBound Tools: Video to Audio Converter</h1>
            <div id="statusBar" class="status-bar"></div>
            <div class="button-controls">
                <button class="command-button" id="btnOpen">
                    <i class="fas fa-folder-open"></i> Open Video
                </button>
                <button class="command-button" id="btnRestart">
                    <i class="fas fa-redo"></i> Restart
                </button>
            </div>
        </div>

        <div class="work-area">
            <!-- Preview Area -->
            <div class="preview-area">
                <video id="video-preview" controls></video>
                <div class="button-controls" style="margin-top: 10px;">
                    <button class="command-button" id="btnSaveMp3" disabled>
                        <i class="fas fa-file-audio"></i> Save MP3
                    </button>
                    <button class="command-button" id="btnSaveWav" disabled>
                        <i class="fas fa-file-audio"></i> Save WAV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <input type="file" id="fileInput" accept=".mp4,.webm,.mkv" style="display: none">

    <script>
        const status = {
            update(message, type = 'info') {
                const container = document.getElementById('statusBar');
                const messageDiv = document.createElement('div');
                messageDiv.className = `status-message ${type}`;
                messageDiv.textContent = message;
                container.appendChild(messageDiv);
                container.scrollTop = container.scrollHeight;
            }
        };

        let currentFile = null;

        // Initialize drag and drop for status bar
        function initDragAndDrop(statusBar, fileInput) {
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
                if (e.dataTransfer.files.length > 0) {
                    fileInput.files = e.dataTransfer.files;
                    const event = new Event('change');
                    fileInput.dispatchEvent(event);
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('fileInput');
            const btnOpen = document.getElementById('btnOpen');
            const btnSaveMp3 = document.getElementById('btnSaveMp3');
            const btnSaveWav = document.getElementById('btnSaveWav');
            const btnRestart = document.getElementById('btnRestart');
            const videoPreview = document.getElementById('video-preview');
            const statusBar = document.getElementById('statusBar');
            initDragAndDrop(statusBar, fileInput);

            status.update('Open a video file to begin (or drag and drop here)', 'info');

            btnOpen.onclick = () => fileInput.click();
            btnRestart.onclick = () => location.reload();

            videoPreview.style.display = 'none';

            fileInput.onchange = (e) => {
                if (e.target.files.length > 0) {
                    currentFile = e.target.files[0];
                    const url = URL.createObjectURL(currentFile);
                    videoPreview.src = url;

                    // Format file size
                    const size = currentFile.size;
                    const sizeStr = size > 1024 * 1024 ?
                        `${(size/(1024*1024)).toFixed(1)} MB` :
                        `${(size/1024).toFixed(1)} KB`;

                    videoPreview.onloadedmetadata = () => {
                        const duration = Math.round(videoPreview.duration);
                        const minutes = Math.floor(duration / 60);
                        const seconds = duration % 60;
                        const durationStr = `${minutes}:${seconds.toString().padStart(2, '0')}`;

                        status.update(`Loaded: ${currentFile.name} (${sizeStr}, ${durationStr})`, 'success');
                    };

                    videoPreview.style.display = 'block';
                    btnSaveMp3.disabled = false;
                    btnSaveWav.disabled = false;
                }
            };

            btnSaveMp3.onclick = () => convertToAudio(currentFile, 'mp3');
            btnSaveWav.onclick = () => convertToAudio(currentFile, 'wav');
        });

        async function convertToAudio(videoFile, format) {
            const videoElement = document.getElementById('video-preview');
            btnSaveMp3.disabled = true;
            btnSaveWav.disabled = true;
            status.update('Starting audio extraction from video (video will play to capture audio)...', 'info');

            try {
                // Create audio context and media stream from video element
                const audioContext = new AudioContext();
                const mediaStream = videoElement.captureStream();
                const mediaStreamSource = audioContext.createMediaStreamSource(mediaStream);

                // Set up recorder to capture audio
                const chunks = [];
                const mediaRecorder = new MediaRecorder(mediaStream);

                mediaRecorder.ondataavailable = (e) => chunks.push(e.data);

                mediaRecorder.onstop = async () => {
                    status.update(`Extracted audio, converting to ${format.toUpperCase()}...`, 'info');

                    // Create blob from recorded chunks
                    const audioBlob = new Blob(chunks, {
                        type: 'audio/webm'
                    });
                    processAudioBlob(audioBlob, format, videoFile.name);
                };

                // Start playback and recording
                videoElement.play();
                mediaRecorder.start();

                // Stop recording when video ends
                videoElement.onended = () => {
                    mediaRecorder.stop();
                    videoElement.pause();
                };

                // For short videos, ensure we get enough audio
                setTimeout(() => {
                    if (mediaRecorder.state === "recording") {
                        videoElement.pause();
                        mediaRecorder.stop();
                    }
                }, videoElement.duration * 1000);

            } catch (error) {
                status.update(`Error: ${error.message}`, 'error');
                btnSaveMp3.disabled = false;
                btnSaveWav.disabled = false;
            }
        }

        async function processAudioBlob(audioBlob, format, originalFileName) {
            try {
                const arrayBuffer = await audioBlob.arrayBuffer();
                const audioContext = new AudioContext();

                audioContext.decodeAudioData(arrayBuffer, (buffer) => {
                    let resultBlob;
                    let extension;

                    if (format === 'mp3') {
                        status.update('Converting to MP3...', 'info');
                        resultBlob = convertBufferToMp3(buffer);
                        extension = '.mp3';
                    } else {
                        status.update('Converting to WAV...', 'info');
                        resultBlob = convertBufferToWav(buffer.getChannelData(0), buffer.sampleRate);
                        extension = '.wav';
                    }

                    // Extract original filename without extension
                    const originalName = originalFileName.substring(0, originalFileName.lastIndexOf('.'));

                    // Create download URL
                    // Trigger download automatically
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(resultBlob);
                    link.download = originalName + extension;
                    link.click();

                    status.update('Conversion complete!', 'success');
                    btnSaveMp3.disabled = false;
                    btnSaveWav.disabled = false;
                }, (error) => {
                    status.update(`Error decoding audio: ${error}`, 'error');
                    btnSaveMp3.disabled = false;
                    btnSaveWav.disabled = false;
                });
            } catch (error) {
                status.update(`Error processing audio: ${error}`, 'error');
                btnSaveMp3.disabled = false;
                btnSaveWav.disabled = false;
            }
        }

        function convertBufferToMp3(audioBuffer) {
            const channels = audioBuffer.numberOfChannels;
            const sampleRate = audioBuffer.sampleRate;
            const mp3encoder = new lamejs.Mp3Encoder(1, sampleRate, 128);
            const leftChannel = audioBuffer.getChannelData(0);
            const samples = new Int16Array(leftChannel.length);

            for (let i = 0; i < leftChannel.length; i++) {
                const s = Math.max(-1, Math.min(1, leftChannel[i]));
                samples[i] = s < 0 ? s * 0x8000 : s * 0x7FFF;
            }

            const mp3Data = [];
            const blockSize = 1152;

            for (let i = 0; i < samples.length; i += blockSize) {
                const sampleChunk = samples.subarray(i, i + blockSize);
                const mp3buf = mp3encoder.encodeBuffer(sampleChunk);
                if (mp3buf.length > 0) {
                    mp3Data.push(mp3buf);
                }
            }

            const mp3buf = mp3encoder.flush();
            if (mp3buf.length > 0) {
                mp3Data.push(mp3buf);
            }

            return new Blob(mp3Data, {
                type: 'audio/mp3'
            });
        }

        function convertBufferToWav(audioData, sampleRate) {
            const bitDepth = 16;
            const bytesPerSample = bitDepth / 8;
            const dataLength = audioData.length * bytesPerSample;

            const buffer = new ArrayBuffer(44 + dataLength);
            const view = new DataView(buffer);

            // Write WAV header
            writeString(view, 0, 'RIFF');
            view.setUint32(4, 36 + dataLength, true);
            writeString(view, 8, 'WAVE');
            writeString(view, 12, 'fmt ');
            view.setUint32(16, 16, true);
            view.setUint16(20, 1, true);
            view.setUint16(22, 1, true);
            view.setUint32(24, sampleRate, true);
            view.setUint32(28, sampleRate * bytesPerSample, true);
            view.setUint16(32, bytesPerSample, true);
            view.setUint16(34, bitDepth, true);
            writeString(view, 36, 'data');
            view.setUint32(40, dataLength, true);

            // Write audio data
            const floatTo16BitPCM = (output, offset, input) => {
                for (let i = 0; i < input.length; i++, offset += 2) {
                    const s = Math.max(-1, Math.min(1, input[i]));
                    output.setInt16(offset, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
                }
            };

            floatTo16BitPCM(view, 44, audioData);

            return new Blob([buffer], {
                type: 'audio/wav'
            });
        }

        function writeString(view, offset, string) {
            for (let i = 0; i < string.length; i++) {
                view.setUint8(offset + i, string.charCodeAt(i));
            }
        }
    </script>
</body>

</html>
