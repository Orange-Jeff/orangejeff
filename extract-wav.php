<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetBound Tools: Audio Extractor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f9;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            width: 800px;
            margin: 0 auto;
            box-sizing: border-box;
            overflow: hidden;
        }
        .header {
            background-color: #0056b3;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: normal;
        }
        .header-button {
            background-color: white;
            color: #0056b3;
            border: none;
            padding: 5px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
        }
        .page-title {
            text-align: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .page-title h1 {
            margin: 0;
            font-size: 24px;
        }
        .page-title .version {
            font-size: 14px;
            color: #666;
        }
        .content {
            padding: 20px;
        }
        .drop-zone {
            border: 2px dashed #0056b3;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 5px;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: background-color 0.3s, border-color 0.3s;
        }
        .drop-zone.drag-over {
            background-color: #e0f7fa;
            border-color: #00bcd4;
        }
        .audio-preview {
            margin: 10px 0;
            display: flex;
            align-items: center;
            position: relative;
            padding: 5px;
            background: #f0f0f0;
            border-radius: 5px;
            overflow: hidden;
        }
        .audio-preview .icon {
            font-size: 24px;
            color: #0056b3;
            margin-right: 10px;
        }
        .audio-preview .file-name {
            flex: 1;
            font-size: 14px;
        }
        .audio-preview .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background-color: #cce5ff;
        }
        .audio-preview .progress-bar-fill {
            height: 100%;
            background-color: #0056b3;
            width: 0%;
            transition: width 0.3s ease;
        }
        .status-box {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .button-blue {
            background-color: #0056b3;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 120px;
        }
        .button-blue:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>NetBound Tools: Audio Extractor</h1>
            <div>
                <button class="header-button" onclick="window.location.href='/'">HOME</button>
                <button class="header-button" onclick="location.reload()">REFRESH</button>
            </div>
        </div>

        <div class="page-title">
            <h1>Process Videos</h1>
            <div class="version">Version 6.0</div>
        </div>

        <div class="content">
            <div class="drop-zone" id="dropZone">
                <p>Drag and drop video files, or click to upload<br>
                Supported formats: MP4, WebM, MOV</p>
            </div>

            <div id="audioPreviews" class="audio-previews hidden"></div>

            <form id="main-form">
                <input type="file" id="fileInput" accept="video/*" style="display: none;" multiple>
                <div class="status-box" id="statusBox">Waiting for videos...</div>
                <div>
                    <button type="button" class="button-blue hidden" id="processVideosBtn">Process</button>
                    <button type="button" class="button-blue hidden" id="saveAllBtn">Save All</button>
                    <button type="button" class="button-blue hidden" id="newActionBtn">New</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const statusBox = document.getElementById('statusBox');
        const audioPreviews = document.getElementById('audioPreviews');
        const processVideosBtn = document.getElementById('processVideosBtn');
        const saveAllBtn = document.getElementById('saveAllBtn');
        const newActionBtn = document.getElementById('newActionBtn');
        let videoFiles = [];
        let wavBlobs = [];
        let mp3Blobs = [];

        dropZone.onclick = () => fileInput.click();
        dropZone.ondragover = e => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        };
        dropZone.ondragleave = () => dropZone.classList.remove('drag-over');
        dropZone.ondrop = e => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            handleFiles(e.dataTransfer.files);
        };

        fileInput.onchange = e => handleFiles(e.target.files);

        function handleFiles(files) {
            videoFiles = Array.from(files).filter(file => file.type.startsWith('video/'));
            if (videoFiles.length === 0) {
                statusBox.textContent = 'Please select valid video files.';
                return;
            }

            statusBox.textContent = `${videoFiles.length} video(s) loaded.`;
            processVideosBtn.classList.remove('hidden');
            audioPreviews.innerHTML = '';
            wavBlobs = [];
            mp3Blobs = [];

            videoFiles.forEach((file, index) => {
                const icon = document.createElement('div');
                icon.classList.add('icon');
                icon.innerHTML = '&#128266;'; // Unicode for a speaker icon

                const fileName = document.createElement('div');
                fileName.classList.add('file-name');
                fileName.textContent = `${file.name} (${(file.size / (1024 * 1024)).toFixed(2)} MB)`;

                const audioPreview = document.createElement('div');
                audioPreview.classList.add('audio-preview');
                audioPreview.appendChild(icon);
                audioPreview.appendChild(fileName);

                const progressBar = document.createElement('div');
                progressBar.classList.add('progress-bar');

                const progressBarFill = document.createElement('div');
                progressBarFill.classList.add('progress-bar-fill');
                progressBar.appendChild(progressBarFill);

                audioPreview.appendChild(progressBar);
                audioPreviews.appendChild(audioPreview);
            });

            audioPreviews.classList.remove('hidden');
        }

        processVideosBtn.onclick = () => {
            if (videoFiles.length === 0) return;
            processVideosBtn.disabled = true;
            statusBox.textContent = 'Processing videos...';

            videoFiles.forEach((file, index) => {
                processWAV(file, index);

                if (file.size <= 10 * 1024 * 1024) {
                    processMP3(file, index);
                } else {
                    statusBox.textContent += `\nSkipping MP3 conversion for ${file.name} (File too large).`;
                }
            });

            saveAllBtn.classList.remove('hidden');
            newActionBtn.classList.remove('hidden');
        };

        function processWAV(file, index) {
            const defaultFileName = file.name.replace(/\.[^/.]+$/, "");
            const reader = new FileReader();

            reader.onload = function(e) {
                const arrayBuffer = e.target.result;
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const progressBarFill = audioPreviews.children[index].querySelector('.progress-bar-fill');

                let progress = 5;
                progressBarFill.style.width = `${progress}%`;
                const progressInterval = setInterval(() => {
                    if (progress < 90) {
                        progress += 2;
                        progressBarFill.style.width = `${progress}%`;
                    }
                }, 300);

                audioContext.decodeAudioData(arrayBuffer, audioBuffer => {
                    clearInterval(progressInterval);

                    const wavBuffer = encodeWAV(audioBuffer);
                    const blob = new Blob([wavBuffer], { type: 'audio/wav' });
                    wavBlobs[index] = { blob, fileName: `${defaultFileName}.wav` };
                    markAsComplete(progressBarFill);
                }, () => {
                    clearInterval(progressInterval);
                    markAsFailed(progressBarFill);
                });
            };

            reader.readAsArrayBuffer(file);
        }

        function processMP3(file, index) {
            const defaultFileName = file.name.replace(/\.[^/.]+$/, "");
            const reader = new FileReader();

            reader.onload = function(e) {
                const arrayBuffer = e.target.result;
                const progressBarFill = audioPreviews.children[index].querySelector('.progress-bar-fill');

                let progress = 5;
                progressBarFill.style.width = `${progress}%`;
                const progressInterval = setInterval(() => {
                    if (progress < 90) {
                        progress += 2;
                        progressBarFill.style.width = `${progress}%`;
                    }
                }, 300);

                // Simulate MP3 encoding process
                setTimeout(() => {
                    clearInterval(progressInterval);
                    const blob = new Blob([arrayBuffer], { type: 'audio/mp3' }); // Placeholder
                    mp3Blobs[index] = { blob, fileName: `${defaultFileName}.mp3` };
                    markAsComplete(progressBarFill);
                }, 2000);
            };

            reader.readAsArrayBuffer(file);
        }

        function markAsComplete(progressBarFill) {
            progressBarFill.style.width = '100%';
            progressBarFill.style.backgroundColor = '#28a745';
        }

        function markAsFailed(progressBarFill) {
            progressBarFill.style.width = '100%';
            progressBarFill.style.backgroundColor = '#dc3545';
        }

        saveAllBtn.onclick = () => {
            wavBlobs.concat(mp3Blobs).forEach(({ blob, fileName }) => {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = fileName;
                a.click();
                URL.revokeObjectURL(url);
            });
            statusBox.textContent += '\nAll files saved.';
        };

        newActionBtn.onclick = () => {
            location.reload();
        };

        function encodeWAV(audioBuffer) {
            const numOfChannels = audioBuffer.numberOfChannels;
            const length = audioBuffer.length * numOfChannels * 2 + 44;
            const buffer = new ArrayBuffer(length);
            const view = new DataView(buffer);

            writeUTFBytes(view, 0, 'RIFF');
            view.setUint32(4, 36 + audioBuffer.length * numOfChannels * 2, true);
            writeUTFBytes(view, 8, 'WAVE');
            writeUTFBytes(view, 12, 'fmt ');
            view.setUint32(16, 16, true);
            view.setUint16(20, 1, true);
            view.setUint16(22, numOfChannels, true);
            view.setUint32(24, audioBuffer.sampleRate, true);
            view.setUint32(28, audioBuffer.sampleRate * numOfChannels * 2, true);
            view.setUint16(32, numOfChannels * 2, true);
            view.setUint16(34, 16, true);
            writeUTFBytes(view, 36, 'data');
            view.setUint32(40, audioBuffer.length * numOfChannels * 2, true);

            let offset = 44;
            for (let i = 0; i < audioBuffer.length; i++) {
                for (let channel = 0; channel < numOfChannels; channel++) {
                    const sample = Math.max(-1, Math.min(1, audioBuffer.getChannelData(channel)[i]));
                    view.setInt16(offset, sample < 0 ? sample * 0x8000 : sample * 0x7FFF, true);
                    offset += 2;
                }
            }

            return buffer;
        }

        function writeUTFBytes(view, offset, string) {
            for (let i = 0; i < string.length; i++) {
                view.setUint8(offset + i, string.charCodeAt(i));
            }
        }
    </script>
</body>
</html>

"workbench.editor.showTabs": true
