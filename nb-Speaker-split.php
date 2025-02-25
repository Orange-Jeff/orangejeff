<?php
// filepath: /e:/orangejeff/Speaker-split.php
// filename: audio_waveform_editor.php
// Version 1.4
// Created by: NetBound Team
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetBound Tools: Audio Splitter</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f9;
            min-height: 100vh;
            box-sizing: border-box;
            width: 600px;
            /* Set body width */

            text-align: left;
            /* Left justify the content */
        }

        .container {
            background: #f4f4f4;
            width: 100%;
            /* Use full width of the body */
            margin: 0 auto;
            box-sizing: border-box;
            overflow: hidden;
            padding: 0 20px;
            /* Add padding to the container */
        }

        .editor-header {
            background: #f4f4f4;
            padding: 15px 0;
            /* Adjust padding */
            border-bottom: 1px solid #dee2e6;
        }

        .editor-title {
            margin: 0;
            color: #0056b3;
            line-height: 1.2;
            font-weight: bold;
            font-size: 1.5em;
            /* Slightly larger */
        }

        #waveform {
            width: 100%;
            height: 150px;
            margin: 20px 0;
            background-color: #e0f0ff;
            /* Lighter blue */
            border: 2px solid #0056b3;
            /* Added border */
        }

        .button-controls,
        .button-group {
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
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            white-space: nowrap;
        }

        .command-button i {
            margin-right: 5px;
        }

        .command-button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .controls {
            display: flex;
            justify-content: center;
            /* Center the controls */
            margin: 10px 0;
            gap: 20px;
            /* Increased spacing */
        }

        .button-blue {
            background-color: #0056b3;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 30px;
            /* Square buttons */
            height: 30px;
            /* Square buttons */
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
            padding: 0;
            /* Remove padding */
            font-size: 1em;
            /* Adjust font size if needed */
        }

        .button-blue:hover {
            background-color: #004494;
        }

        .button-blue i {
            margin-right: 0;
            /* Remove icon margin */
        }

        .persistent-status-bar {
            height: 80px;
            border: 1px solid #ddd;
            background: #fff;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            overflow-y: auto;
            font-family: monospace;
            line-height: normal;
        }

        .status-message {
            padding: 3px 8px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s, color 0.3s;
        }

        .status-message:last-child {
            background-color: #0056b3;
            color: white;
        }

        .status-message:last-child.error {
            background-color: #dc3545;
            color: white;
        }

        .status-message:last-child.success {
            background-color: #28a745;
            color: white;
        }

        .status-message:not(:last-child) {
            background-color: transparent;
            color: #333;
        }

        .status-box {
            height: 150px;
            border: 1px solid #ddd;
            background: #fff;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            overflow-y: auto;
            font-family: monospace;
        }

        .status-message {
            padding: 3px 8px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s, color 0.3s;
        }

        .status-message:last-child {
            background-color: #0056b3;
            color: white;
        }

        .status-message:last-child.error {
            background-color: #dc3545;
            color: white;
        }

        .status-message:last-child.success {
            background-color: #28a745;
            color: white;
        }

        .status-message:not(:last-child) {
            background-color: transparent;
            color: #333;
        }

        .status-message.error {
            color: #dc3545;
        }

        .status-message.success {
            color: #28a745;
        }

        .status-box {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }

        .processed-files {
            margin-top: 20px;
        }

        .processed-files a {
            display: block;
            margin: 10px 0;
            color: #0056b3;
            text-decoration: none;
        }

        /* Full width button */
        #processAudio {
            width: 100%;
            margin-top: 10px;
        }

        /* Add button group styling */
        .button-group {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        /* Add to existing styles */
        .button-blue.warning {
            background-color: #e67e22;
        }

        .button-blue.warning:hover {
            background-color: #d35400;
        }

        .waveform-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-family: monospace;
        }

        .regions-log {
            margin-top: 20px;
            border: 1px solid #ddd;
            padding: 10px;
            font-family: monospace;
            max-height: 200px;
            overflow-y: auto;
        }

        .region-entry {
            padding: 5px;
            border-bottom: 1px solid #eee;
        }

        .region-entry.speaker1 {
            color: #ff8c00;
        }

        .region-entry.speaker2 {
            color: #28a745;
        }

        .region-entry.trash {
            color: #dc3545;
            /* Red color for trash regions */
        }

        .button-blue.warning {
            background-color: #dc3545;
        }

        .button-blue.warning:hover {
            background-color: #c82333;
        }
    </style>
    <script src="https://unpkg.com/wavesurfer.js@6.6.4"></script>
    <script src="https://unpkg.com/wavesurfer.js@6.6.4/dist/plugin/wavesurfer.regions.min.js"></script>
</head>

<body>
    <div class="container">
        <div class="editor-header">
            <h1 class="editor-title">NetBound Tools: Speaker Splitter</h1>
            <div class="persistent-status-bar" id="statusBar">Waiting for audio...</div>
            <div class="button-controls">
                <div class="button-group">
                    <button class="command-button" id="btnOpen">
                        <i class="fas fa-microphone"></i> Open Wav
                    </button>
                    <button class="command-button" id="btnRestart">
                        <i class="fas fa-redo"></i> Restart
                    </button>
                </div>
            </div>
        </div>

        <input type="file" id="fileInput" accept=".wav" style="display: none;">

        <div id="waveform-container">
            <div class="waveform-header">
                <span id="duration-display">Duration: 0:00</span>
                <span id="window-display">Viewable: 0:00 - 0:00</span>
                <button type="button" id="zoomIn" class="button-blue"><i class="fas fa-search-plus"></i></button>
                <button type="button" id="zoomOut" class="button-blue"><i class="fas fa-search-minus"></i></button>
                <button type="button" id="zoomReset" class="button-blue"><i class="fas fa-compress-arrows-alt"></i></button>
            </div>
            <div id="waveform"></div>
            <div class="controls">
                <button type="button" id="jumpStart" class="button-blue"><i class="fas fa-fast-backward"></i></button>
                <button type="button" id="playPause" class="button-blue"><i class="fas fa-play"></i></button>
                <button type="button" id="backward" class="button-blue"><i class="fas fa-backward"></i></button>
                <button type="button" id="forward" class="button-blue"><i class="fas fa-forward"></i></button>
                <button type="button" id="speaker1Region" class="button-blue" title="Mark Speaker 1"><i class="fas fa-user"></i>1</button>
                <button type="button" id="speaker2Region" class="button-blue" title="Mark Speaker 2"><i class="fas fa-user"></i>2</button>
                <button type="button" id="trashRegion" class="button-blue warning" title="Mark for deletion">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>

        <div id="regions-log" class="regions-log"></div>

        <button type="button" id="processAudio" class="button-blue">Process Audio</button>

        <div class="processed-files" id="processedFiles" style="display:none;">
            <h3>Processed Files:</h3>
            <a href="#" id="speaker1File" target="_blank">Download Speaker 1 File</a>
            <a href="#" id="speaker2File" target="_blank">Download Speaker 2 File</a>
        </div>

        <form id="regionForm" method="POST" action="process_audio.php" enctype="multipart/form-data" style="display: none;">
            <input type="hidden" name="regions" id="regions">
            <input type="hidden" name="fileName" id="fileName">
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize elements first
            const statusBar = document.getElementById('statusBar');
            const btnOpen = document.getElementById('btnOpen');
            const fileInput = document.getElementById('fileInput');
            const regionsInput = document.getElementById('regions');
            const fileNameInput = document.getElementById('fileName');
            const processedFiles = document.getElementById('processedFiles');
            const speaker1File = document.getElementById('speaker1File');
            const speaker2File = document.getElementById('speaker2File');

            // Single regionsData declaration
            let regionsData = {
                speaker1: [], // orange regions
                speaker2: [], // green regions
                trash: [] // Add trash array
            };
            let currentFileName = '';
            let lastEndPoint = 0;

            // Initialize WaveSurfer
            const wavesurfer = WaveSurfer.create({
                container: '#waveform',
                waveColor: 'blue',
                progressColor: 'darkblue',
                responsive: true,
                height: 150,
                plugins: [
                    WaveSurfer.regions.create({
                        dragSelection: true, // Enable drag selection
                        slop: 5 // Makes it easier to select regions
                    })
                ]
            });

            // Add these event listeners right after wavesurfer initialization
            wavesurfer.on('ready', () => {
                lastEndPoint = 0;
                updateStatus('Audio loaded. Ready to mark speakers.', 'success');
            });

            wavesurfer.on('error', (err) => {
                console.error('WaveSurfer error:', err);
                updateStatus('Error loading audio file.', 'error');
            });

            // Add zoom event listener after other wavesurfer event listeners
            wavesurfer.on('zoom', function(minPxPerSec) {
                const viewDuration = wavesurfer.getDuration();
                const viewStart = wavesurfer.getCurrentTime();
                const viewEnd = Math.min(viewStart + (wavesurfer.container.clientWidth / minPxPerSec), viewDuration);
                updateStatus(`View: ${viewStart.toFixed(1)}s - ${viewEnd.toFixed(1)}s`, 'info');
            });

            // Add region creation handler
            wavesurfer.on('region-created', region => {
                // Default color for dragged regions
                region.color = 'rgba(0, 123, 255, 0.2)';
                region.drag = true;
                region.resize = true;
                updateStatus(`Region created: ${formatTime(region.start)} - ${formatTime(region.end)}`, 'info');
            });

            // Function declarations
            function updateStatus(message, type = 'info') {
                const statusBar = document.getElementById('statusBar');
                const messageDiv = document.createElement('div');
                messageDiv.className = `status-message ${type}`;
                messageDiv.textContent = message;
                statusBar.appendChild(messageDiv);
                statusBar.scrollTop = statusBar.scrollHeight;
            }

            function handleFile(file) {
                if (!file || !(file.name.toLowerCase().endsWith('.wav'))) {
                    updateStatus('Please select a valid WAV file', 'error');
                    return;
                }

                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                updateStatus(`Loading: ${file.name} (${fileSizeMB} MB)`, 'info');

                const audioUrl = URL.createObjectURL(file);
                wavesurfer.load(audioUrl);

                wavesurfer.on('ready', () => {
                    const duration = wavesurfer.getDuration().toFixed(2);
                    updateStatus(`Loaded: ${file.name}`, 'success');
                    updateStatus(`Duration: ${duration} seconds`, 'success');
                    URL.revokeObjectURL(audioUrl);
                });
            }

            // Event listeners
            btnOpen.addEventListener('click', (e) => {
                e.preventDefault();
                fileInput.click();
                console.log('Open button clicked');
            });

            fileInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files[0]) {
                    handleFile(e.target.files[0]);
                    console.log('File selected:', e.target.files[0].name);
                }
            });

            document.getElementById('playPause').addEventListener('click', () => wavesurfer.playPause());
            document.getElementById('backward').addEventListener('click', () => wavesurfer.skip(-0.5)); // Half second back
            document.getElementById('forward').addEventListener('click', () => wavesurfer.skip(0.5)); // Half second forward
            document.getElementById('zoomIn').addEventListener('click', () => {
                wavesurfer.zoom(wavesurfer.params.minPxPerSec + 10);
            });
            document.getElementById('zoomOut').addEventListener('click', () => {
                wavesurfer.zoom(Math.max(wavesurfer.params.minPxPerSec - 10, 1));
            });

            document.getElementById('processAudio').addEventListener('click', () => {
                regionsInput.value = JSON.stringify({
                    speaker1: regionsData.speaker1.sort((a, b) => a.start - b.start),
                    speaker2: regionsData.speaker2.sort((a, b) => a.start - b.start),
                    trash: regionsData.trash.sort((a, b) => a.start - b.start)
                });
                const formData = new FormData(document.getElementById('regionForm'));

                fetch('process_audio.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        speaker1File.href = data.speaker1;
                        speaker2File.href = data.speaker2;
                        processedFiles.style.display = 'block';
                        updateStatus('Processing complete!', 'success');
                    })
                    .catch(error => {
                        updateStatus('Error processing audio.', 'error');
                        console.error(error);
                    });
            });

            document.getElementById('trimLeft').addEventListener('click', () => {
                const regions = wavesurfer.regions.list;
                const currentTime = wavesurfer.getCurrentTime();
                let trimPerformed = false;

                Object.values(regions).forEach(region => {
                    if (currentTime >= region.start && currentTime <= region.end) {
                        region.update({
                            start: currentTime
                        });
                        updateStatus(`Region trimmed: ${currentTime.toFixed(2)}s to ${region.end.toFixed(2)}s`, 'success');
                        trimPerformed = true;
                    }
                });

                if (!trimPerformed) {
                    updateStatus('To trim: Place cursor inside a region first', 'error');
                }
            });

            document.getElementById('trimRight').addEventListener('click', () => {
                const regions = wavesurfer.regions.list;
                const currentTime = wavesurfer.getCurrentTime();
                let trimPerformed = false;

                Object.values(regions).forEach(region => {
                    if (currentTime >= region.start && currentTime <= region.end) {
                        region.update({
                            end: currentTime
                        });
                        updateStatus(`Region trimmed: ${region.start.toFixed(2)}s to ${currentTime.toFixed(2)}s`, 'success');
                        trimPerformed = true;
                    }
                });

                if (!trimPerformed) {
                    updateStatus('To trim: Place cursor inside a region first', 'error');
                }
            });

            document.getElementById('jumpStart').addEventListener('click', () => {
                wavesurfer.setTime(0);
                updateStatus('Playhead moved to start');
            });

            // Inside your DOMContentLoaded event listener, update the restart button:
            document.getElementById('btnRestart').addEventListener('click', () => {
                location.href = location.pathname;
            });

            // Add these event listeners in your DOMContentLoaded section

            document.getElementById('speaker1Region').addEventListener('click', () => {
                const currentTime = wavesurfer.getCurrentTime();

                const region = wavesurfer.addRegion({
                    start: lastEndPoint,
                    end: currentTime,
                    color: 'rgba(255, 165, 0, 0.3)', // orange
                    drag: false, // disable dragging for consistency
                    resize: false // disable resizing for consistency
                });

                regionsData.speaker1.push({
                    start: lastEndPoint,
                    end: currentTime
                });

                lastEndPoint = currentTime;
                updateStatus(`Speaker 1: ${lastEndPoint.toFixed(2)}s - ${currentTime.toFixed(2)}s`, 'success');
            });

            document.getElementById('speaker2Region').addEventListener('click', () => {
                const currentTime = wavesurfer.getCurrentTime();

                const region = wavesurfer.addRegion({
                    start: lastEndPoint,
                    end: currentTime,
                    color: 'rgba(0, 255, 0, 0.3)', // green
                    drag: false,
                    resize: false
                });

                regionsData.speaker2.push({
                    start: lastEndPoint,
                    end: currentTime
                });

                lastEndPoint = currentTime;
                updateStatus(`Speaker 2: ${lastEndPoint.toFixed(2)}s - ${currentTime.toFixed(2)}s`, 'success');
            });

            document.getElementById('trashRegion').addEventListener('click', () => {
                const currentTime = wavesurfer.getCurrentTime();

                if (currentTime <= lastEndPoint) {
                    updateStatus('Cannot create region: Move playhead forward', 'error');
                    return;
                }

                const region = wavesurfer.addRegion({
                    start: lastEndPoint,
                    end: currentTime,
                    color: 'rgba(220, 53, 69, 0.3)', // Red with transparency
                    drag: true,
                    resize: true
                });

                regionsData.trash.push({
                    start: lastEndPoint,
                    end: currentTime
                });

                // Add to log
                const logEntry = document.createElement('div');
                logEntry.className = 'region-entry trash';
                logEntry.textContent = `Trash: ${formatTime(lastEndPoint)} - ${formatTime(currentTime)}`;
                document.getElementById('regions-log').appendChild(logEntry);

                lastEndPoint = currentTime;
                updateStatus(`Trash region created: ${formatTime(lastEndPoint)} - ${formatTime(currentTime)}`, 'success');
            });

            let currentSpeaker = 1; // Tracks which speaker is next

            // Update time display function
            function formatTime(seconds) {
                return new Date(seconds * 1000).toISOString().substr(14, 5);
            }

            function updateDisplays() {
                const duration = wavesurfer.getDuration() || 0;
                const currentView = {
                    start: wavesurfer.getCurrentTime(),
                    end: Math.min(wavesurfer.getCurrentTime() + (wavesurfer.container.clientWidth / wavesurfer.params.minPxPerSec), duration)
                };

                document.getElementById('duration-display').textContent = `Duration: ${formatTime(duration)}`;
                document.getElementById('window-display').textContent =
                    `Viewable: ${formatTime(currentView.start)} - ${formatTime(currentView.end)}`;
            }

            // Update play/pause button
            const playPauseButton = document.getElementById('playPause');
            wavesurfer.on('play', () => {
                playPauseButton.innerHTML = '<i class="fas fa-pause"></i>';
            });
            wavesurfer.on('pause', () => {
                playPauseButton.innerHTML = '<i class="fas fa-play"></i>';
            });

            // Speaker region creation
            function createSpeakerRegion(speakerNum) {
                const currentTime = wavesurfer.getCurrentTime();
                if (currentTime <= lastEndPoint) {
                    updateStatus('Cannot create region: Move playhead forward', 'error');
                    return;
                }

                const color = speakerNum === 1 ? 'rgba(255, 165, 0, 0.3)' : 'rgba(0, 255, 0, 0.3)';
                const region = wavesurfer.addRegion({
                    start: lastEndPoint,
                    end: currentTime,
                    color: color,
                    drag: true, // Allow dragging
                    resize: true // Allow resizing
                });

                regionsData[`speaker${speakerNum}`].push({
                    start: lastEndPoint,
                    end: currentTime
                });

                // Add to log
                const logEntry = document.createElement('div');
                logEntry.className = `region-entry speaker${speakerNum}`;
                logEntry.textContent = `Speaker ${speakerNum}: ${formatTime(lastEndPoint)} - ${formatTime(currentTime)}`;
                document.getElementById('regions-log').appendChild(logEntry);

                lastEndPoint = currentTime;
                updateStatus(`Speaker ${speakerNum} region created`, 'success');
            }

            document.getElementById('speaker1Region').addEventListener('click', () => createSpeakerRegion(1));
            document.getElementById('speaker2Region').addEventListener('click', () => createSpeakerRegion(2));

            // Reset zoom
            document.getElementById('zoomReset').addEventListener('click', () => {
                wavesurfer.zoom(wavesurfer.params.minPxPerSec);
                updateDisplays();
            });

            // Update displays on zoom
            wavesurfer.on('zoom', updateDisplays);
            wavesurfer.on('ready', updateDisplays);
        });
    </script>
</body>

</html>
