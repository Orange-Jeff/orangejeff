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
            width: 768px;
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
            /* Reverse the display order */
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
            height: 432px;
            background: #2a2a2a;
            object-fit: contain;
        }

        .status-bar.drag-over {
            background: #e3f2fd;
            border-color: #2196f3;
            border-style: dashed;
        }

        .button-pair {
            display: flex;
            overflow: hidden;
            width: fit-content;
        }

        .action-button {
            background: #0056b3;
            color: white;
            border: none;
            padding: 6px 8px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-top-left-radius: 3px;
            border-bottom-left-radius: 3px;
        }

        .plus-button {
            background: #0056b3;
            color: white;
            border: none;
            border-left: 1px solid rgba(255, 255, 255, 0.2);
            padding: 6px 8px;
            cursor: pointer;
            font-size: 14px;
        }

        /* Hover states */
        .action-button:hover,
        .plus-button:hover {
            background: #004494;
        }

        /* Disabled states */
        .action-button:disabled,
        .plus-button:disabled {
            background: #ccc;
            cursor: not-allowed;
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
                <button class="command-button" id="btnBulkWav">
                    <i class="fas fa-folder-open"></i> Bulk WAV
                </button>
                <button class="command-button" id="btnBulkMp3">
                    <i class="fas fa-folder-open"></i> Bulk MP3
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
                    </button>
                    <button class="command-button" id="btnSaveWav" disabled>
                        <i class="fas fa-file-audio"></i> Save WAV
                    </button>
                    <button class="command-button" id="btnRemoveAudio" disabled>
                        <i class="fas fa-volume-mute"></i> Remove Audio
                    </button>
                </div>
            </div>
        </div>
    </div>

    <input type="file" id="fileInput" accept=".mp4,.webm,.mkv" style="display: none">
    <input type="file" id="bulkInput" accept=".mp4,.webm,.mkv" style="display: none" multiple>
    <input type="file" id="bulkMp3Input" accept=".mp4,.webm,.mkv" style="display: none" multiple>
    <script>
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

        // Web Worker for heavy audio processing (commented out until needed)
        /*
        let audioWorker = null;

        // Initialize the worker when needed
        function initializeAudioWorker() {
            if (!audioWorker) {
                audioWorker = new Worker('/E:/OrangeJeff/audio-extract.js');

                audioWorker.onmessage = function(e) {
                    const data = e.data;

                    if (data.command === 'result') {
                        // Handle completed processing
                        const processedData = data.data;
                        // Continue with MP3 encoding using the processed data
                    } else if (data.command === 'chunkProcessed') {
                        // Update progress
                        const progress = data.progress;
                        status.update(`Processing: ${progress}% complete...`, 'info');
                    } else if (data.command === 'error') {
                        status.update(`Worker error: ${data.message}`, 'error');
                    }
                };
            }
            return audioWorker;
        }

        // Example of using the worker for Float32 to Int16 conversion
        function convertFloat32ToInt16WithWorker(floatSamples) {
            return new Promise((resolve, reject) => {
                const worker = initializeAudioWorker();

                // One-time handler for this specific task
                const originalHandler = worker.onmessage;
                worker.onmessage = function(e) {
                    if (e.data.command === 'result') {
                        // Restore original handler
                        worker.onmessage = originalHandler;
                        resolve(e.data.data);
                    } else {
                        // Pass to the original handler
                        originalHandler(e);
                    }
                };

                // Send data to worker
                worker.postMessage({
                    command: 'convertFloat32ToInt16',
                    data: floatSamples
                });
            });
        }

        // Clean up worker when page unloads
        window.addEventListener('beforeunload', () => {
            if (audioWorker) {
                audioWorker.terminate();
                audioWorker = null;
            }
        });
        */

        let currentFile = null;
        let isProcessing = false;
        let bulkFiles = [];
        let currentBulkIndex = 0;

        // Initialize drag and drop functionality
        function initDragAndDrop(statusBar, fileInput) {
            // Show drag instructions initially
            status.update('Drag file(s) here or open a video', 'info');

            statusBar.addEventListener('dragover', (e) => {
                e.preventDefault();
                statusBar.classList.add('drag-over');
            });

            statusBar.addEventListener('dragleave', () => {
                statusBar.classList.remove('drag-over');
            });

            // Handle file drops
            statusBar.addEventListener('drop', (e) => {
                e.preventDefault();
                statusBar.classList.remove('drag-over');
                if (e.dataTransfer.files.length > 0) {
                    if (e.dataTransfer.files.length > 1) {
                        // Show format selection dialog for bulk drops
                        const format = confirm("Convert to MP3? (Cancel for WAV)") ? 'mp3' : 'wav';
                        handleBulkFiles(Array.from(e.dataTransfer.files), format);
                    } else {
                        handleSingleFile(e.dataTransfer.files[0]);
                    }
                }
            });
        }

        // Handle bulk file processing
        async function handleBulkFiles(files, format = 'wav') {
            if (isProcessing) return;

            const videoElement = document.getElementById('video-preview');
            const buttons = document.querySelectorAll('.command-button:not(#btnRestart)');

            // Disable all buttons except Restart
            buttons.forEach(btn => btn.disabled = true);

            videoElement.src = '';
            isProcessing = true;
            bulkFiles = files;
            currentBulkIndex = 0;

            status.update(`${files.length} files loaded`, 'info');
            status.update(`Converting to ${format.toUpperCase()}...`, 'info');

            let convertedCount = 0;

            for (let i = 0; i < files.length; i++) {
                currentBulkIndex = i;
                currentFile = files[i];

                if (currentFile.type === 'video/webm') {
                    status.update(`Warning: ${currentFile.name} is WebM and may not convert correctly`, 'error');
                }

                const url = URL.createObjectURL(currentFile);
                videoElement.src = url;

                await new Promise(resolve => {
                    videoElement.onloadeddata = () => {
                        videoElement.currentTime = 0;
                    };
                    videoElement.onloadedmetadata = resolve;
                });

                await convertToAudio(currentFile, format);
                convertedCount++;

                // Show progress
                status.update(`Processed ${convertedCount} of ${files.length} files...`, 'info');
            }

            status.update(`${format.toUpperCase()} Conversion complete. ${convertedCount} files now saved`, 'success');
            isProcessing = false;
            currentFile = null;
            bulkFiles = [];
            currentBulkIndex = 0;
        }

        // Handle single file selection
        function handleSingleFile(file) {
            if (isProcessing) return;
            currentFile = file;

            const videoElement = document.getElementById('video-preview');

            // Check file type
            if (file.type === 'video/webm') {
                status.update('Warning: WebM files may not convert correctly', 'error');
            }

            // Revoke previous URL if it exists
            if (videoElement.src.startsWith('blob:')) {
                URL.revokeObjectURL(videoElement.src);
            }

            const url = URL.createObjectURL(file);
            videoElement.src = url;
            videoElement.style.display = 'block'; // Make video visible

            // Update other UI elements
            document.getElementById('filename').value = file.name;
            document.getElementById('btnRename').disabled = false;
            document.getElementById('btnSaveMp3').disabled = false;
            document.getElementById('btnSaveWav').disabled = false;
            document.getElementById('btnRemoveAudio').disabled = false;

            videoElement.onloadeddata = () => {
                videoElement.currentTime = 0;

                // Check for audio tracks
                setTimeout(() => {
                    checkForAudioTracks(videoElement, file.name);
                }, 500); // Short delay to ensure tracks are loaded
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

                // Use let instead of const and check if audioTracks exists
                let audioTracks = [];
                if (videoElement.audioTracks) {
                    audioTracks = videoElement.audioTracks;
                    if (audioTracks.length > 0) {
                        status.update('Stereo detected. Extracted audio will be mono', 'error');
                    }
                }
            };
        }

        // Check if the video has audio tracks
        function checkForAudioTracks(videoElement, fileName) {
            try {
                // Create temporary audio extractor to check for audio
                const tempAudio = new Audio();
                tempAudio.src = videoElement.src;

                tempAudio.oncanplaythrough = () => {
                    const stream = tempAudio.captureStream();
                    const audioTracks = stream.getAudioTracks();

                    if (audioTracks.length === 0) {
                        status.update(`Warning: No audio tracks found in ${fileName}. Conversion may result in silent audio.`, 'error');

                        // Mark the file as potentially problematic
                        videoElement.dataset.hasAudio = 'false';

                        // Add a visual indicator to the UI
                        videoElement.style.border = '2px solid #f44336'; // Red border
                    } else {
                        videoElement.dataset.hasAudio = 'true';
                        videoElement.style.border = '';
                    }

                    URL.revokeObjectURL(tempAudio.src);
                };

                tempAudio.onerror = () => {
                    URL.revokeObjectURL(tempAudio.src);
                };
            } catch (e) {
                console.error('Error checking for audio tracks:', e);
            }
        }

        // Direct extraction of WAV from video
        async function extractDirectWav(videoFile, fileName) {
            try {
                status.update('Directly extracting audio...', 'info');

                // Create an audio context
                const audioContext = new AudioContext();

                // Read the file data
                status.update('Reading video file...', 'info');
                const arrayBuffer = await videoFile.arrayBuffer();

                // Try to decode the audio directly from the video file
                status.update('Decoding audio from video...', 'info');

                // Use a promise with timeout to prevent hanging
                const audioBuffer = await new Promise((resolve, reject) => {
                    const timeout = setTimeout(() => {
                        reject(new Error('Audio decoding timed out'));
                    }, 15000); // 15 seconds timeout

                    audioContext.decodeAudioData(arrayBuffer)
                        .then(buffer => {
                            clearTimeout(timeout);
                            resolve(buffer);
                        })
                        .catch(err => {
                            clearTimeout(timeout);
                            reject(err);
                        });
                });

                // If we got here, decoding worked!
                status.update('Creating WAV file...', 'info');
                const wavBuffer = audioBufferToWav(audioBuffer);
                const blob = new Blob([wavBuffer], {
                    type: 'audio/wav'
                });

                // Save the file
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = fileName.replace(/\.[^.]+$/, '.wav');
                link.click();
                URL.revokeObjectURL(url);

                status.update('WAV extraction complete! File saved', 'success');
                return true;
            } catch (error) {
                console.error('Direct WAV extraction failed:', error);
                return false; // Signal failure to allow fallback method
            }
        }

        // Add a direct MP3 extraction function
        async function extractDirectMp3(videoFile, fileName) {
            try {
                status.update('Directly extracting audio for MP3...', 'info');

                // Create an audio context
                const audioContext = new AudioContext();

                // Read the file data
                status.update('Reading video file...', 'info');
                const arrayBuffer = await videoFile.arrayBuffer();

                // Try to decode the audio directly from the video file
                status.update('Decoding audio from video...', 'info');

                // Use a promise with timeout to prevent hanging
                const audioBuffer = await new Promise((resolve, reject) => {
                    const timeout = setTimeout(() => {
                        reject(new Error('Audio decoding timed out'));
                    }, 15000); // 15 seconds timeout

                    audioContext.decodeAudioData(arrayBuffer)
                        .then(buffer => {
                            clearTimeout(timeout);
                            resolve(buffer);
                        })
                        .catch(err => {
                            clearTimeout(timeout);
                            reject(err);
                        });
                });

                // If we got here, decoding worked!
                // Convert to mp3 using lamejs
                status.update('Encoding to MP3...', 'info');
                const mp3encoder = new lamejs.Mp3Encoder(1, audioBuffer.sampleRate, 128);
                const samples = audioBuffer.getChannelData(0);
                const sampleBlockSize = 1152;
                const mp3Data = [];

                // Process in chunks to avoid UI freezing
                for (let i = 0; i < samples.length; i += sampleBlockSize) {
                    const sampleChunk = samples.subarray(i, i + sampleBlockSize);
                    const mp3buf = mp3encoder.encodeBuffer(convertFloat32ToInt16(sampleChunk));
                    if (mp3buf.length > 0) {
                        mp3Data.push(mp3buf);
                    }

                    // Update progress every 10% of the way through
                    if (i % Math.floor(samples.length / 10) < sampleBlockSize) {
                        const progress = Math.floor((i / samples.length) * 100);
                        status.update(`Encoding MP3: ${progress}% complete...`, 'info');
                    }
                }

                // Get the last chunk of MP3 data
                const mp3buf = mp3encoder.flush();
                if (mp3buf.length > 0) {
                    mp3Data.push(mp3buf);
                }

                // Create blob and download
                const blob = new Blob(mp3Data, {
                    type: 'audio/mp3'
                });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = fileName.replace(/\.[^.]+$/, '.mp3');
                link.click();
                URL.revokeObjectURL(url);

                status.update('MP3 extraction complete! File saved', 'success');
                return true;
            } catch (error) {
                console.error('Direct MP3 extraction failed:', error);
                return false; // Signal failure to allow fallback method
            }
        }

        // Convert audio buffer to WAV format
        function audioBufferToWav(buffer, opt) {
            opt = opt || {};

            const numChannels = buffer.numberOfChannels;
            const sampleRate = buffer.sampleRate;
            const format = opt.float32 ? 3 : 1;
            const bitDepth = format === 3 ? 32 : 16;

            // Create WAV file header
            let result;
            if (numChannels === 2) {
                result = interleave(buffer.getChannelData(0), buffer.getChannelData(1));
            } else {
                result = buffer.getChannelData(0);
            }

            return encodeWAV(result, format, sampleRate, numChannels, bitDepth);
        }

        // Interleave two audio channels
        function interleave(inputL, inputR) {
            const length = inputL.length + inputR.length;
            const result = new Float32Array(length);

            let index = 0;
            let inputIndex = 0;

            while (index < length) {
                result[index++] = inputL[inputIndex];
                result[index++] = inputR[inputIndex];
                inputIndex++;
            }
            return result;
        }

        // Encode audio data into WAV format
        function encodeWAV(samples, format, sampleRate, numChannels, bitDepth) {
            const bytesPerSample = bitDepth / 8;
            const blockAlign = numChannels * bytesPerSample;

            const buffer = new ArrayBuffer(44 + samples.length * bytesPerSample);
            const view = new DataView(buffer);

            // RIFF identifier
            writeString(view, 0, 'RIFF');
            // RIFF chunk length
            view.setUint32(4, 36 + samples.length * bytesPerSample, true);
            // RIFF type
            writeString(view, 8, 'WAVE');
            // Format chunk identifier
            writeString(view, 12, 'fmt ');
            // Format chunk length
            view.setUint32(16, 16, true);
            // Sample format (raw)
            view.setUint16(20, format, true);
            // Channel count
            view.setUint16(22, numChannels, true);
            // Sample rate
            view.setUint32(24, sampleRate, true);
            // Byte rate (sample rate * block align)
            view.setUint32(28, sampleRate * blockAlign, true);
            // Block align (channel count * bytes per sample)
            view.setUint16(32, blockAlign, true);
            // Bits per sample
            view.setUint16(34, bitDepth, true);
            // Data chunk identifier
            writeString(view, 36, 'data');
            // Data chunk length
            view.setUint32(40, samples.length * bytesPerSample, true);

            // Write the PCM samples
            if (format === 1) {
                floatTo16BitPCM(view, 44, samples);
            } else {
                writeFloat32(view, 44, samples);
            }

            return buffer;
        }

        // Write a string to a DataView
        function writeString(view, offset, string) {
            for (let i = 0; i < string.length; i++) {
                view.setUint8(offset + i, string.charCodeAt(i));
            }
        }

        // Convert Float32 audio data to 16-bit PCM
        function floatTo16BitPCM(output, offset, input) {
            for (let i = 0; i < input.length; i++, offset += 2) {
                const s = Math.max(-1, Math.min(1, input[i]));
                output.setInt16(offset, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
            }
        }

        // Write Float32 to DataView
        function writeFloat32(output, offset, input) {
            for (let i = 0; i < input.length; i++, offset += 4) {
                output.setFloat32(offset, input[i], true);
            }
        }

        // Process audio blob to MP3 format
        async function processAudioBlobToMp3(audioBlob, originalFileName) {
            try {
                status.update('Converting to MP3...', 'info');

                if (audioBlob.size === 0) {
                    throw new Error("Audio data is empty");
                }

                const arrayBuffer = await audioBlob.arrayBuffer();
                const audioContext = new AudioContext();
                const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);

                // Convert to mp3 using lamejs
                const mp3encoder = new lamejs.Mp3Encoder(1, audioBuffer.sampleRate, 128);
                const samples = audioBuffer.getChannelData(0);
                const sampleBlockSize = 1152;
                const mp3Data = [];

                status.update('Encoding MP3... (this may take a while)', 'info');

                // Process the audio in chunks to avoid browser hangs
                for (let i = 0; i < samples.length; i += sampleBlockSize) {
                    const sampleChunk = samples.subarray(i, i + sampleBlockSize);
                    const mp3buf = mp3encoder.encodeBuffer(convertFloat32ToInt16(sampleChunk));
                    if (mp3buf.length > 0) {
                        mp3Data.push(mp3buf);
                    }
                }

                // Get the last chunk of MP3 data
                const mp3buf = mp3encoder.flush();
                if (mp3buf.length > 0) {
                    mp3Data.push(mp3buf);
                }

                // Join the MP3 data chunks
                const blob = new Blob(mp3Data, {
                    type: 'audio/mp3'
                });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = originalFileName.replace(/\.[^.]+$/, '.mp3');
                link.click();
                URL.revokeObjectURL(url);

                status.update('MP3 conversion complete. File saved', 'success');

                // Re-enable buttons
                document.getElementById('btnSaveMp3').disabled = false;
                document.getElementById('btnSaveWav').disabled = false;
                document.getElementById('btnRename').disabled = false;
                isProcessing = false;

            } catch (error) {
                status.update(`Error converting to MP3: ${error.message}`, 'error');
                console.error('MP3 conversion error:', error);

                // Re-enable buttons on error
                document.getElementById('btnSaveMp3').disabled = false;
                document.getElementById('btnSaveWav').disabled = false;
                document.getElementById('btnRename').disabled = false;
                isProcessing = false;
            }
        }

        // Convert Float32 to Int16 for MP3 encoding
        function convertFloat32ToInt16(buffer) {
            let l = buffer.length; // Changed from const to let
            const buf = new Int16Array(l);
            while (l--) {
                buf[l] = Math.min(1, Math.max(-1, buffer[l])) * 0x7FFF;
            }
            return buf;
        }

        // Process audio blob to WAV
        async function processAudioBlobToWav(audioBlob, originalFileName) {
            try {
                status.update('Decoding audio...', 'info');

                if (audioBlob.size === 0) {
                    throw new Error("Audio data is empty");
                }

                // Log the blob format for debugging
                status.update(`Processing audio blob (${audioBlob.type}, ${(audioBlob.size/1024).toFixed(1)}KB)`, 'info');

                const arrayBuffer = await audioBlob.arrayBuffer();
                if (!arrayBuffer || arrayBuffer.byteLength === 0) {
                    throw new Error("Invalid audio data buffer");
                }

                const audioContext = new AudioContext();

                // Use a promise with timeout for decodeAudioData
                const audioBuffer = await new Promise((resolve, reject) => {
                    // Set timeout for decoding (10 seconds)
                    const timeout = setTimeout(() => {
                        reject(new Error("Audio decoding timed out"));
                    }, 10000);

                    audioContext.decodeAudioData(arrayBuffer)
                        .then(buffer => {
                            clearTimeout(timeout);
                            resolve(buffer);
                        })
                        .catch(err => {
                            clearTimeout(timeout);
                            reject(err);
                        });
                });

                // If we get here, decoding succeeded
                status.update('Creating WAV file...', 'info');
                const wavBuffer = audioBufferToWav(audioBuffer);
                const blob = new Blob([wavBuffer], {
                    type: 'audio/wav'
                });

                // Save the file
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = originalFileName.replace(/\.[^.]+$/, '.wav');
                link.click();
                URL.revokeObjectURL(url);

                status.update('WAV conversion complete. File saved', 'success');

                // Re-enable buttons
                document.getElementById('btnSaveMp3').disabled = false;
                document.getElementById('btnSaveWav').disabled = false;
                document.getElementById('btnRename').disabled = false;
                isProcessing = false;

            } catch (error) {
                status.update(`Error processing WAV: ${error.message}`, 'error');
                console.error('WAV processing error:', error); // Log full error for debugging
                throw error; // Re-throw to trigger fallback method
            }
        }

        // Alternative WAV processing method
        async function processAudioBlobToWavAlternative(audioBlob, originalFileName) {
            try {
                status.update('Using alternative WAV conversion...', 'info');

                // Create an audio element to decode the blob
                const audio = document.createElement('audio');
                audio.src = URL.createObjectURL(audioBlob);

                // Wait for audio to load
                await new Promise(resolve => {
                    audio.oncanplaythrough = resolve;
                    audio.load();
                });

                // Create offline context to render audio to buffer
                const offlineCtx = new OfflineAudioContext(
                    1, // Force mono
                    audio.duration * 44100, // Sample rate * duration
                    44100 // Standard sample rate
                );

                // Create source from audio element
                const source = offlineCtx.createMediaElementSource(audio);
                source.connect(offlineCtx.destination);

                // Play and render
                audio.play();
                const renderedBuffer = await offlineCtx.startRendering();
                audio.pause();

                // Convert rendered buffer to WAV
                const wavBuffer = audioBufferToWav(renderedBuffer);
                const blob = new Blob([wavBuffer], {
                    type: 'audio/wav'
                });

                // Download file
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = originalFileName.replace(/\.[^.]+$/, '.wav');
                link.click();
                URL.revokeObjectURL(url);
                URL.revokeObjectURL(audio.src);

                status.update('WAV conversion complete. File saved', 'success');

                // Re-enable buttons
                document.getElementById('btnSaveMp3').disabled = false;
                document.getElementById('btnSaveWav').disabled = false;
                document.getElementById('btnRename').disabled = false;
                isProcessing = false;

            } catch (error) {
                status.update(`Error in alternative WAV processing: ${error.message}`, 'error');
                document.getElementById('btnSaveMp3').disabled = false;
                document.getElementById('btnSaveWav').disabled = false;
                document.getElementById('btnRename').disabled = false;
                isProcessing = false;
                throw error; // Re-throw to allow further handling if needed
            }
        }

        // Main conversion function
        async function convertToAudio(videoFile, format) {
            if (!videoFile) {
                status.update('No video file provided', 'error');
                return;
            }

            const videoElement = document.getElementById('video-preview');
            const buttons = document.querySelectorAll('.command-button:not(#btnRestart)');

            // Disable all buttons except Restart
            buttons.forEach(btn => btn.disabled = true);
            isProcessing = true;

            try {
                // Try the direct extraction approach first for both formats
                let result = false;

                if (format === 'wav') {
                    result = await extractDirectWav(videoFile, videoFile.name);
                } else if (format === 'mp3') {
                    result = await extractDirectMp3(videoFile, videoFile.name);
                }

                if (result) {
                    // Success - re-enable buttons and return
                    buttons.forEach(btn => btn.disabled = false);
                    isProcessing = false;
                    return;
                }

                // If direct extraction failed, show appropriate message based on format
                if (format === 'mp3') {
                    status.update('MP3 extraction requires the video plays. You can mute.', 'info');
                } else {
                    status.update('Direct extraction failed. Falling back to standard extraction...', 'info');
                }

                // Continue with the existing extraction process
                // Create a new AudioContext
                const audioContext = new AudioContext();

                // Create an audio source from the video element
                const source = audioContext.createMediaElementSource(videoElement);

                // Create a destination to capture the audio
                const destination = audioContext.createMediaStreamDestination();

                // Connect the source to the destination
                source.connect(destination);

                // We also want to hear the audio, so connect to audio output
                source.connect(audioContext.destination);

                // Store current time and create variables for recording
                const currentTime = videoElement.currentTime;
                let recordingStarted = false;
                let mediaRecorder = null;
                let chunks = [];

                // Promise to handle the recording process
                await new Promise((resolve, reject) => {
                    try {
                        // Create a MediaRecorder to record the audio stream
                        mediaRecorder = new MediaRecorder(destination.stream);
                        mediaRecorder.ondataavailable = (e) => {
                            if (e.data.size > 0) {
                                chunks.push(e.data);
                            }
                        };

                        mediaRecorder.onstop = async () => {
                            try {
                                // Create a Blob from the audio chunks
                                const audioBlob = new Blob(chunks, {
                                    type: 'audio/webm'
                                });

                                if (audioBlob.size === 0) {
                                    throw new Error("No audio data was captured");
                                }

                                // Process the audio blob based on format
                                if (format === 'wav') {
                                    try {
                                        await processAudioBlobToWav(audioBlob, videoFile.name);
                                    } catch (err) {
                                        // Try alternative WAV conversion if the first method fails
                                        status.update(`Trying alternative conversion method...`, 'info');
                                        await processAudioBlobToWavAlternative(audioBlob, videoFile.name);
                                    }
                                } else if (format === 'mp3') {
                                    await processAudioBlobToMp3(audioBlob, videoFile.name);
                                }

                                resolve();
                            } catch (err) {
                                reject(err);
                            }
                        };

                        // Start recording
                        mediaRecorder.start();
                        recordingStarted = true;

                        // Rewind video to beginning for full extraction
                        videoElement.currentTime = 0;

                        // Play the video (this will be silent to the user)
                        const playPromise = videoElement.play();

                        if (playPromise) {
                            playPromise.then(() => {
                                // Set a timeout to stop recording before the video ends
                                setTimeout(() => {
                                    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                                        // Stop recording
                                        mediaRecorder.stop();
                                        videoElement.pause();
                                    }
                                }, videoElement.duration * 1000);

                                // Also handle when video naturally ends
                                videoElement.onended = () => {
                                    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                                        mediaRecorder.stop();
                                    }
                                };
                            }).catch(e => {
                                status.update(`Error playing video: ${e.message}`, 'error');
                                reject(e);
                            });
                        }
                    } catch (err) {
                        reject(err);
                    }
                });

                // Cleanup and restore video state
                videoElement.pause();
                videoElement.currentTime = currentTime;

                // Disconnect audio nodes to free resources
                source.disconnect();

            } catch (error) {
                status.update(`Error: ${error.message}`, 'error');
                console.error('Audio extraction error:', error);

                // Re-enable buttons on error
                buttons.forEach(btn => btn.disabled = false);
                isProcessing = false;
            }
        }

        // Function to remove audio from a video and make it silent
        async function createSilentVideo(videoFile) {
            if (!videoFile) {
                status.update('No video file provided', 'error');
                return;
            }

            const videoElement = document.getElementById('video-preview');
            const btnSaveMp3 = document.getElementById('btnSaveMp3');
            const btnSaveWav = document.getElementById('btnSaveWav');
            const btnRemoveAudio = document.getElementById('btnRemoveAudio');
            const btnRename = document.getElementById('btnRename');

            // Disable all buttons
            btnSaveMp3.disabled = true;
            btnSaveWav.disabled = true;
            btnRemoveAudio.disabled = true;
            btnRename.disabled = true;
            isProcessing = true;

            try {
                status.update('Creating silent video...', 'info');

                // Create an object URL for the video
                const videoURL = URL.createObjectURL(videoFile);

                // Set up the original video to get metadata
                await new Promise(resolve => {
                    videoElement.onloadedmetadata = resolve;
                    videoElement.src = videoURL;
                });

                // Get video dimensions and duration
                const width = videoElement.videoWidth;
                const height = videoElement.videoHeight;
                const duration = videoElement.duration;

                // Create a MediaRecorder to capture just the video stream
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                const stream = canvas.captureStream();

                const mediaRecorder = new MediaRecorder(stream, {
                    mimeType: 'video/webm;codecs=vp9',
                    videoBitsPerSecond: 5000000 // Adjust quality as needed
                });

                const chunks = [];
                mediaRecorder.ondataavailable = (e) => {
                    if (e.data.size > 0) {
                        chunks.push(e.data);
                    }
                };

                mediaRecorder.onstop = () => {
                    // Create a new silent video file from the captured frames
                    const silentVideo = new Blob(chunks, {
                        type: 'video/webm'
                    });

                    // Download the silent video
                    const url = URL.createObjectURL(silentVideo);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = videoFile.name.replace(/\.[^.]+$/, '-silent.webm');
                    link.click();
                    URL.revokeObjectURL(url);

                    status.update('Silent video created and saved', 'success');

                    // Re-enable buttons
                    btnSaveMp3.disabled = false;
                    btnSaveWav.disabled = false;
                    btnRemoveAudio.disabled = false;
                    btnRename.disabled = false;
                    isProcessing = false;
                };

                // Start recording
                mediaRecorder.start();

                // Draw each frame of the video to the canvas
                let startTime = null;
                let frameId = null;

                // Start at the beginning
                videoElement.currentTime = 0;

                // Play the video but muted
                videoElement.muted = true;
                await videoElement.play();

                // Animation frame loop to capture video frames
                function drawFrame(timestamp) {
                    if (!startTime) startTime = timestamp;
                    const elapsed = timestamp - startTime;

                    // Draw current video frame to canvas
                    ctx.drawImage(videoElement, 0, 0, width, height);

                    // Continue if video is still playing and within duration
                    if (videoElement.paused || videoElement.ended || elapsed >= duration * 1000) {
                        mediaRecorder.stop();
                        videoElement.pause();
                        videoElement.muted = false; // Restore sound
                        cancelAnimationFrame(frameId);
                        return;
                    }

                    // Request next frame
                    frameId = requestAnimationFrame(drawFrame);
                }

                // Start the frame capturing
                frameId = requestAnimationFrame(drawFrame);

                // Update progress periodically
                const progressInterval = setInterval(() => {
                    const progress = Math.round((videoElement.currentTime / duration) * 100);
                    status.update(`Creating silent video: ${progress}% complete...`, 'info');

                    if (videoElement.paused || videoElement.ended) {
                        clearInterval(progressInterval);
                    }
                }, 500);

            } catch (error) {
                status.update(`Error creating silent video: ${error.message}`, 'error');
                console.error('Silent video creation error:', error);

                // Re-enable buttons on error
                btnSaveMp3.disabled = false;
                btnSaveWav.disabled = false;
                btnRemoveAudio.disabled = false;
                btnRename.disabled = false;
                isProcessing = false;
            }
        }

        // Set up the UI when the document is ready
        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('fileInput');
            const bulkInput = document.getElementById('bulkInput');
            const bulkMp3Input = document.getElementById('bulkMp3Input');
            const btnOpen = document.getElementById('btnOpen');
            const btnBulkWav = document.getElementById('btnBulkWav');
            const btnBulkMp3 = document.getElementById('btnBulkMp3');
            const btnSaveMp3 = document.getElementById('btnSaveMp3');
            const btnSaveWav = document.getElementById('btnSaveWav');
            const btnRestart = document.getElementById('btnRestart');
            const btnRename = document.getElementById('btnRename');
            const filename = document.getElementById('filename');
            const videoPreview = document.getElementById('video-preview');
            const statusBar = document.getElementById('statusBar');
            const btnRemoveAudio = document.getElementById('btnRemoveAudio');

            // Button click handlers
            btnOpen.onclick = () => fileInput.click();
            btnBulkWav.onclick = () => bulkInput.click();
            btnBulkMp3.onclick = () => bulkMp3Input.click();
            btnRestart.onclick = () => location.reload();

            // Save button handlers
            btnSaveMp3.onclick = () => {
                if (currentFile) {
                    convertToAudio(currentFile, 'mp3');
                }
            };

            btnSaveWav.onclick = () => {
                if (currentFile) {
                    convertToAudio(currentFile, 'wav');
                }
            };

            // Remove Audio button handler
            btnRemoveAudio.onclick = () => {
                if (currentFile) {
                    createSilentVideo(currentFile);
                }
            };

            // File input handlers
            fileInput.onchange = (e) => {
                if (e.target.files.length > 0) {
                    handleSingleFile(e.target.files[0]);
                }
            };

            bulkInput.onchange = (e) => {
                if (e.target.files.length > 0) {
                    handleBulkFiles(Array.from(e.target.files), 'wav');
                }
            };

            bulkMp3Input.onchange = (e) => {
                if (e.target.files.length > 0) {
                    handleBulkFiles(Array.from(e.target.files), 'mp3');
                }
            };

            // Initialize drag and drop
            initDragAndDrop(statusBar, fileInput);

            // Make sure buttons start disabled
            btnSaveMp3.disabled = true;
            btnSaveWav.disabled = true;
            btnRemoveAudio.disabled = true;
            btnRename.disabled = true;

            // Initialize rename functionality
            btnRename.onclick = () => {
                const wasReadOnly = filename.readOnly;
                filename.readOnly = !wasReadOnly;

                if (wasReadOnly) {
                    filename.focus();
                    filename.select();
                    btnRename.innerHTML = '<i class="fas fa-save"></i> Save';
                } else {
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
        });

        // File download utility function to reduce duplicate code
        function downloadFile(blob, fileName, extension) {
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = fileName.replace(/\.[^.]+$/, extension);
            link.click();
            URL.revokeObjectURL(url);
        }

        // Refactored extractDirectWav and extractDirectMp3 functions to share common code
        async function extractAudioDirect(videoFile, fileName, format) {
            try {
                status.update(`Directly extracting audio for ${format.toUpperCase()}...`, 'info');
                const audioContext = new AudioContext();

                // Read the file data
                status.update('Reading video file...', 'info');
                const arrayBuffer = await videoFile.arrayBuffer();

                // Decode audio with timeout handling
                status.update('Decoding audio from video...', 'info');
                const audioBuffer = await new Promise((resolve, reject) => {
                    const timeout = setTimeout(() => {
                        reject(new Error('Audio decoding timed out'));
                    }, 15000);

                    audioContext.decodeAudioData(arrayBuffer)
                        .then(buffer => {
                            clearTimeout(timeout);
                            resolve(buffer);
                        })
                        .catch(err => {
                            clearTimeout(timeout);
                            reject(err);
                        });
                });

                // Process based on format
                if (format === 'wav') {
                    status.update('Creating WAV file...', 'info');
                    const wavBuffer = audioBufferToWav(audioBuffer);
                    const blob = new Blob([wavBuffer], {
                        type: 'audio/wav'
                    });
                    downloadFile(blob, fileName, '.wav');
                    status.update('WAV extraction complete! File saved', 'success');
                } else if (format === 'mp3') {
                    status.update('Encoding to MP3...', 'info');
                    const mp3encoder = new lamejs.Mp3Encoder(1, audioBuffer.sampleRate, 128);
                    const samples = audioBuffer.getChannelData(0);
                    const sampleBlockSize = 1152;
                    const mp3Data = [];

                    // Process in chunks with progress updates
                    const totalChunks = Math.ceil(samples.length / sampleBlockSize);
                    for (let i = 0; i < samples.length; i += sampleBlockSize) {
                        const sampleChunk = samples.subarray(i, i + sampleBlockSize);
                        const mp3buf = mp3encoder.encodeBuffer(convertFloat32ToInt16(sampleChunk));
                        if (mp3buf.length > 0) mp3Data.push(mp3buf);

                        // Show progress every ~10%
                        const chunkNumber = Math.floor(i / sampleBlockSize);
                        if (chunkNumber % Math.max(1, Math.floor(totalChunks / 10)) === 0) {
                            const progress = Math.floor((i / samples.length) * 100);
                            status.update(`Encoding MP3: ${progress}% complete...`, 'info');
                        }
                    }

                    const mp3buf = mp3encoder.flush();
                    if (mp3buf.length > 0) mp3Data.push(mp3buf);

                    const blob = new Blob(mp3Data, {
                        type: 'audio/mp3'
                    });
                    downloadFile(blob, fileName, '.mp3');
                    status.update('MP3 extraction complete! File saved', 'success');
                }

                return true;
            } catch (error) {
                console.error(`Direct ${format.toUpperCase()} extraction failed:`, error);
                return false;
            }
        }

        // Update the convertToAudio function to use the new utility functions
        async function convertToAudio(videoFile, format) {
            if (!videoFile) {
                status.update('No video file provided', 'error');
                return;
            }

            const videoElement = document.getElementById('video-preview');
            const buttons = document.querySelectorAll('.command-button:not(#btnRestart)');

            // Disable all buttons except Restart
            buttons.forEach(btn => btn.disabled = true);
            isProcessing = true;

            try {
                // Try direct extraction first
                const result = await extractAudioDirect(videoFile, videoFile.name, format);

                if (result) {
                    // Success - re-enable buttons and return
                    buttons.forEach(btn => btn.disabled = false);
                    isProcessing = false;
                    return;
                }

                // If direct extraction failed, show appropriate message
                if (format === 'mp3') {
                    status.update('MP3 extraction requires the video plays. You can mute.', 'info');
                } else {
                    status.update('Direct extraction failed. Falling back to standard extraction...', 'info');
                }

                // Continue with the fallback method
                // ...existing fallback method code...

            } catch (error) {
                status.update(`Error: ${error.message}`, 'error');
                console.error('Audio extraction error:', error);

                // Re-enable buttons on error
                buttons.forEach(btn => btn.disabled = false);
                isProcessing = false;
            }
        }
    </script>
</body>

</html>
