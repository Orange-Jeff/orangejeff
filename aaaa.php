<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>NetBound Tools: Audio Extractor</title>
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
            overflow-anchor: none;
        }

        .status-message {
            padding: 5px;
            margin: 2px 0;
            border-radius: 3px;
            color: #666;
            background: white;
        }

        .status-message.info {
            border-left: 3px solid #2196f3;
        }

        .status-message.info:last-child {
            background: #2196f3;
            color: white;
            border-left: none;
        }

        .status-message.success {
            border-left: 3px solid #4caf50;
        }

        .status-message.success:last-child {
            background: #4caf50;
            color: white;
            border-left: none;
        }

        .status-message.error {
            border-left: 3px solid #f44336;
        }

        .status-message.error:last-child {
            background: #f44336;
            color: white;
            border-left: none;
        }

        .filename-control {
            width: 100%;
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }

        .filename-input {
            flex: 1;
            padding: 6px 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-family: inherit;
            font-size: 14px;
        }

        .filename-input:read-only {
            background: #f8f8f8;
        }

        #video-preview {
            width: 100%;
            height: 400px;
            background: #2a2a2a;
            object-fit: contain;
        }

        .status-bar.drag-over {
            background: #e3f2fd;
            border-color: #2196f3;
        }

        .format-select {
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }
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
                <button class="command-button" id="btnBulk">
                    <i class="fas fa-folder-open"></i> Bulk Convert
                </button>
                <button class="command-button" id="btnRestart">
                    <i class="fas fa-redo"></i> Restart
                </button>
            </div>
        </div>

        <div class="work-area">
            <div class="preview-area">
                <video id="video-preview" controls poster="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 9'%3E%3C/svg%3E"></video>
                <div class="filename-control">
                    <input type="text" id="filename" class="filename-input" readonly placeholder="No file selected">
                    <button class="command-button" id="btnRename" disabled>
                        <i class="fas fa-edit"></i> Rename
                    </button>
                </div>
                <div class="button-controls">
                    <button class="command-button" id="btnSaveMp3" disabled>
                        <i class="fas fa-file-audio"></i> Save MP3
                        <small style="opacity: 0.8; margin-left: 4px">(SLOW)</small>
                    </button>
                    <button class="command-button" id="btnSaveWav" disabled>
                        <i class="fas fa-file-audio"></i> Save WAV
                        <small style="opacity: 0.8; margin-left: 4px">(FAST)</small>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <input type="file" id="fileInput" accept=".mp4,.webm,.mkv" style="display: none">
    <input type="file" id="bulkInput" accept=".mp4,.webm,.mkv" style="display: none" multiple>

    <script>
        const status = {
            update(message, type = 'info') {
                const container = document.getElementById('statusBar');
                const messageDiv = document.createElement('div');
                messageDiv.className = `status-message ${type}`;
                messageDiv.textContent = message;
                container.appendChild(messageDiv);
                // Auto-scroll only if already near bottom
                if (container.scrollTop > container.scrollHeight - container.clientHeight - 50) {
                    container.scrollTop = container.scrollHeight;
                }
            }
        };

        let currentFile = null;
        let isProcessing = false;
        let bulkFiles = [];
        let currentBulkIndex = 0;

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
                    if (e.dataTransfer.files.length > 1) {
                        handleBulkFiles(Array.from(e.dataTransfer.files));
                    } else {
                        handleSingleFile(e.dataTransfer.files[0]);
                    }
                }
            });
        }

        async function handleBulkFiles(files) {
            if (isProcessing) {
                status.update('Please wait for current process to complete', 'error');
                return;
            }

            // Clean up any previous state
            const videoElement = document.getElementById('video-preview');
            const filename = document.getElementById('filename');
            const btnRename = document.getElementById('btnRename');

            videoElement.src = '';
            filename.value = '';
            btnRename.disabled = true;
            isProcessing = true;

            // Ask for format
            const format = await new Promise((resolve) => {
                const formatDiv = document.createElement('div');
                formatDiv.className = 'format-select';

                status.update(`Select format for ${files.length} files:`, 'info');
                const mp3Btn = document.createElement('button');
                mp3Btn.className = 'command-button';
                mp3Btn.innerHTML = '<i class="fas fa-file-audio"></i> MP3 (SLOW)';
                mp3Btn.onclick = () => resolve('mp3');

                const wavBtn = document.createElement('button');
                wavBtn.className = 'command-button';
                wavBtn.style.marginLeft = '10px';
                wavBtn.innerHTML = '<i class="fas fa-file-audio"></i> WAV (FAST)';
                wavBtn.onclick = () => resolve('wav');

                formatDiv.appendChild(mp3Btn);
                formatDiv.appendChild(wavBtn);
                document.querySelector('.work-area').appendChild(formatDiv);

                // Clean up on selection
                const cleanup = () => formatDiv.remove();
                mp3Btn.addEventListener('click', cleanup);
                wavBtn.addEventListener('click', cleanup);
            });

            bulkFiles = files;
            currentBulkIndex = 0;

            try {
                status.update(`Starting bulk conversion of ${files.length} files to ${format.toUpperCase()}...`, 'info');

                for (let i = 0; i < files.length; i++) {
                    currentBulkIndex = i;
                    currentFile = files[i];
                    filename.value = currentFile.name;

                    await processFile(currentFile, format);
                    status.update(`Completed ${i + 1} of ${files.length}: ${currentFile.name}`, 'success');
                }

                status.update('Bulk conversion complete!', 'success');
            } catch (error) {
                status.update(`Error during bulk conversion: ${error.message}`, 'error');
            } finally {
                // Clean up
                isProcessing = false;
                currentFile = null;
                bulkFiles = [];
                currentBulkIndex = 0;
                filename.value = '';
                btnRename.disabled = true;
                videoElement.src = '';
            }
        }

        function handleSingleFile(file) {
            if (isProcessing) return;

            currentFile = file;
            const videoElement = document.getElementById('video-preview');
            const filename = document.getElementById('filename');
            const btnRename = document.getElementById('btnRename');

            // Update filename display
            filename.value = file.name;
            btnRename.disabled = false;

            const url = URL.createObjectURL(file);
            videoElement.src = url;

            videoElement.onloadeddata = () => {
                videoElement.currentTime = 0;
            };

            videoElement.onloadedmetadata = () => {
                const duration = Math.round(videoElement.duration);
                const minutes = Math.floor(duration / 60);
                const seconds = duration % 60;
                const durationStr = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                const size = file.size;
                const sizeStr = size > 1024 * 1024 ?
                    `${(size/(1024*1024)).toFixed(1)} MB` :
                    `${(size/1024).toFixed(1)} KB`;

                status.update(`Loaded: ${file.name} (${sizeStr}, ${durationStr})`, 'success');
            };

            document.getElementById('btnSaveMp3').disabled = false;
            document.getElementById('btnSaveWav').disabled = false;

            // Clean up the object URL after video loads
            setTimeout(() => {
                URL.revokeObjectURL(url);
            }, 100);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('fileInput');
            const bulkInput = document.getElementById('bulkInput');
            const btnOpen = document.getElementById('btnOpen');
            const btnBulk = document.getElementById('btnBulk');
            const btnSaveMp3 = document.getElementById('btnSaveMp3');
            const btnSaveWav = document.getElementById('btnSaveWav');
            const btnRestart = document.getElementById('btnRestart');
            const btnRename = document.getElementById('btnRename');
            const filename = document.getElementById('filename');
            const videoPreview = document.getElementById('video-preview');
            const statusBar = document.getElementById('statusBar');

            // Initialize rename functionality
            btnRename.onclick = () => {
                const wasReadOnly = filename.readOnly;
                filename.readOnly = !wasReadOnly;

                if (wasReadOnly) {
                    // Enter edit mode
                    filename.focus();
                    filename.select();
                    btnRename.innerHTML = '<i class="fas fa-save"></i> Save';
                } else {
                    // Save changes
                    if (currentFile && filename.value) {
                        const newName = filename.value;
                        currentFile = new File([currentFile], newName, {
                            type: currentFile.type
                        });
                        status.update(`File renamed to: ${newName}`, 'success');
                        btnRename.innerHTML = '<i class="fas fa-edit"></i> Rename';
                    }
                }
            };

            // Handle filename enter key
            filename.onkeydown = (e) => {
                if (e.key === 'Enter' && !filename.readOnly) {
                    btnRename.click();
                }
            };

            initDragAndDrop(statusBar, fileInput);
            btnOpen.onclick = () => fileInput.click();
            btnBulk.onclick = () => {
                if (!isProcessing) {
                    bulkInput.click();
                } else {
                    status.update('Please wait for current process to complete', 'error');
                }
            };
            btnRestart.onclick = () => location.reload();

            fileInput.onchange = (e) => {
                if (e.target.files.length > 0) {
                    handleSingleFile(e.target.files[0]);
                }
            };

            bulkInput.onchange = (e) => {
                if (e.target.files.length > 0) {
                    handleBulkFiles(Array.from(e.target.files));
                }
            };

            btnSaveMp3.onclick = () => convertToAudio(currentFile, 'mp3');
            btnSaveWav.onclick = () => convertToAudio(currentFile, 'wav');
        });

        async function convertToAudio(videoFile, format) {
            const videoElement = document.getElementById('video-preview');
            const btnSaveMp3 = document.getElementById('btnSaveMp3');
            const btnSaveWav = document.getElementById('btnSaveWav');
            btnRename = document.getElementById('btnRename');

            btnSaveMp3.disabled = true;
            btnSaveWav.disabled = true;
            btnRename.disabled = true;
            isProcessing = true;

            try {
                if (format === 'wav') {
                    // Fast WAV - direct file reading
                    status.update('Reading audio from video file...', 'info');
                    // Proper WAV extraction using audio context
                    const audioContext = new AudioContext();
                    const videoReader = new FileReader();

                    await new Promise((resolve, reject) => {
                        videoReader.onload = async (e) => {
                            try {
                                const audioBuffer = await audioContext.decodeAudioData(e.target.result);
                                const wavBlob = audioBufferToWav(audioBuffer);
                                const link = document.createElement('a');
                                link.href = URL.createObjectURL(wavBlob);
                                link.download = videoFile.name.replace(/\.[^.]+$/, '.wav');
                                link.click();
                                URL.revokeObjectURL(link.href);
                                status.update('WAV conversion complete!', 'success');
                                status.update(`File saved as: ${link.download}`, 'info');
                                resolve();
                            } catch (error) {
                                reject(error);
                            }
                        };
                        videoReader.onerror = reject;
                        videoReader.readAsArrayBuffer(videoFile);
                    });
                } else {
                    // Direct MP3 conversion from file
                    status.update('Extracting audio from video file...', 'info');
                    const audioContext = new AudioContext();
                    const fileReader = new FileReader();

                    await new Promise((resolve, reject) => {
                        fileReader.onload = async (e) => {
                            try {
                                const audioBuffer = await audioContext.decodeAudioData(e.target.result);
                                const mp3Blob = await convertToMp3(audioBuffer);
                                createDownloadLink(mp3Blob, videoFile.name, 'mp3');
                                resolve();
                            } catch (error) {
                                reject(error);
                            } finally {
                                audioContext.close();
                            }
                        };
                        fileReader.readAsArrayBuffer(videoFile);
                    });
                }
            } catch (error) {
                status.update(`Error: ${error.message}`, 'error');
            } finally {
                btnSaveMp3.disabled = false;
                btnSaveWav.disabled = false;
                btnRename.disabled = false;
                isProcessing = false;
            }
        }

        async function processAudioBlob(audioBlob, originalFileName, audioContext) {
            try {
                const arrayBuffer = await audioBlob.arrayBuffer();
                await new Promise((resolve, reject) => {
                    audioContext.decodeAudioData(arrayBuffer, async (buffer) => {
                        try {
                            status.update('Processing MP3 conversion...', 'info');
                            let totalChunks = 0;
                            const resultBlob = await convertToMp3(buffer, (current, total) => {
                                if (totalChunks !== total) totalChunks = total;
                                const percent = Math.round((current / total) * 100);
                                if (current % 100 === 0) {
                                    status.update(`Processing MP3: ${percent}% complete`, 'info');
                                }
                            });

                            const blobUrl = URL.createObjectURL(resultBlob);
                            const link = document.createElement('a');
                            link.href = blobUrl;
                            link.download = originalFileName.replace(/\.[^.]+$/, '.mp3');
                            link.click();
                            URL.revokeObjectURL(blobUrl);

                            status.update('MP3 conversion complete!', 'success');
                            status.update(`File saved as: ${link.download}`, 'info');
                            resolve();
                        } catch (error) {
                            reject(error);
                        } finally {
                            audioContext.close();
                        }
                    }, reject);
                });
            } catch (error) {
                throw new Error(`Error processing audio: ${error.message}`);
            }
        }

        function audioBufferToWav(audioBuffer) {
            const numChannels = 1;
            const sampleRate = audioBuffer.sampleRate;
            const format = 3; // Float32
            const bitDepth = 32;

            const wavHeader = new DataView(new ArrayBuffer(44));
            const bytesPerSample = bitDepth / 8;
            const blockAlign = numChannels * bytesPerSample;

            // RIFF identifier
            writeString(wavHeader, 0, 'RIFF');
            // RIFF chunk length
            wavHeader.setUint32(4, 36 + audioBuffer.length * bytesPerSample, true);
            // RIFF type
            writeString(wavHeader, 8, 'WAVE');
            // Format chunk identifier
            writeString(wavHeader, 12, 'fmt ');
            // Format chunk length
            wavHeader.setUint32(16, 16, true);
            // Sample format (3 = float)
            wavHeader.setUint16(20, format, true);
            // Channel count
            wavHeader.setUint16(22, numChannels, true);
            // Sample rate
            wavHeader.setUint32(24, sampleRate, true);
            // Byte rate (sample rate * block align)
            wavHeader.setUint32(28, sampleRate * blockAlign, true);
            // Block align
            wavHeader.setUint16(32, blockAlign, true);
            // Bits per sample
            wavHeader.setUint16(34, bitDepth, true);
            // Data chunk identifier
            writeString(wavHeader, 36, 'data');
            // Data chunk length
            wavHeader.setUint32(40, audioBuffer.length * bytesPerSample, true);

            const interleaved = new Float32Array(audioBuffer.length);
            for (let i = 0; i < audioBuffer.length; i++) {
                interleaved[i] = audioBuffer.getChannelData(0)[i];
            }

            const wavBytes = new Uint8Array(wavHeader.byteLength + interleaved.buffer.byteLength);
            wavBytes.set(new Uint8Array(wavHeader.buffer), 0);
            wavBytes.set(new Uint8Array(interleaved.buffer), wavHeader.byteLength);

            return new Blob([wavBytes], {
                type: 'audio/wav'
            });

            function writeString(view, offset, string) {
                for (let i = 0; i < string.length; i++) {
                    view.setUint8(offset + i, string.charCodeAt(i));
                }
            }
        }

        function convertToMp3(audioBuffer, progressCallback) {
            return new Promise((resolve) => {
                const sampleRate = audioBuffer.sampleRate;
                const mp3encoder = new lamejs.Mp3Encoder(1, sampleRate, 128);
                const leftChannel = audioBuffer.getChannelData(0);
                const samples = new Int16Array(leftChannel.length);
                const blockSize = 1152;
                const blocks = Math.ceil(leftChannel.length / blockSize);

                for (let i = 0; i < leftChannel.length; i++) {
                    const s = Math.max(-1, Math.min(1, leftChannel[i]));
                    samples[i] = s < 0 ? s * 0x8000 : s * 0x7FFF;
                }

                const mp3Data = [];
                for (let i = 0; i < samples.length; i += blockSize) {
                    const sampleChunk = samples.subarray(i, i + blockSize);
                    const mp3buf = mp3encoder.encodeBuffer(sampleChunk);
                    if (mp3buf.length > 0) {
                        mp3Data.push(mp3buf);
                    }
                    if (progressCallback) {
                        progressCallback(Math.floor(i / blockSize), blocks);
                    }
                }

                const mp3buf = mp3encoder.flush();
                if (mp3buf.length > 0) {
                    mp3Data.push(mp3buf);
                }

                resolve(new Blob(mp3Data, {
                    type: 'audio/mp3'
                }));
            });
        }
    </script>
</body>

</html>
