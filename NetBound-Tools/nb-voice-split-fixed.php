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
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f4f4f9;
            width: 100%;
            max-width: 768px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        /* Layout Components */
        .container {
            background: #f4f4f9;
            width: 100%;
            margin: 0;
            box-sizing: border-box;
            padding: 0 20px;
        }

        .editor-header {
            background: #f4f4f9;
            padding: 0 0 10px 0;
            border-bottom: 1px solid #dee2e6;
            width: 100%;
            box-sizing: border-box;
        }

        .editor-title {
            margin: 20px 0 8px 0;
            color: #0056b3;
            line-height: 1.2;
            font-weight: bold;
            font-size: 18px;
        }

        /* Waveform container with responsive design */
        #waveform-container {
            position: relative;
            margin: 20px 0;
            padding: 10px;
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            width: 100%;
            box-sizing: border-box;
        }

        .waveform-wrapper {
            position: relative;
            width: 100%;
            min-height: 150px;
            background: #fff;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
            transition: min-height 0.3s ease;
            padding: 5px;
            box-sizing: border-box;
        }

        #waveform {
            width: 100%;
            height: 150px;
            background-color: #e0f0ff;
            border: 2px solid #0056b3;
            position: relative;
            transition: all 0.3s ease;
            overflow: hidden;
            box-sizing: border-box;
        }

        #waveform.stereo {
            height: 180px;
        }

        .waveform-wrapper.stereo {
            min-height: 180px;
        }

        /* Channel labels */
        .channel-label {
            position: absolute;
            left: 5px;
            font-size: 12px;
            color: #666;
            z-index: 2;
            background-color: rgba(255, 255, 255, 0.8);
            padding: 2px 5px;
            border-radius: 3px;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        #waveform.stereo .channel-label {
            opacity: 1;
        }

        .channel-label.left {
            top: 5px;
        }

        .channel-label.right {
            bottom: 5px;
        }

        /* Button and controls styling */
        .button-controls,
        .button-group {
            width: 100%;
            padding: 10px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .button-blue {
            background-color: #0056b3;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            min-width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            padding: 0 8px;
            font-size: 14px;
            position: relative;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .button-blue:hover {
            background-color: #004494;
        }

        .button-blue:active,
        .button-blue.playing {
            transform: translateY(1px);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            background-color: #004494;
        }

        .button-blue.warning {
            background-color: #6c757d;
        }

        .button-blue.warning:hover {
            background-color: #5a6268;
        }

        /* Play button special styling */
        #playPause {
            width: 40px;
        }

        #playPause i.fas {
            transition: all 0.2s ease;
        }

        #playPause.playing i.fas {
            transform: scale(1.1);
        }

        /* Controls responsive layout */
        .controls {
            display: flex;
            justify-content: center;
            margin: 10px 0;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Make controls wrap better on mobile */
        @media (max-width: 576px) {
            .controls {
                justify-content: flex-start;
            }

            .button-blue {
                min-width: calc(25% - 10px);
                flex-grow: 1;
            }
        }

        .zoom-controls {
            margin: 0;
            gap: 10px;
            display: flex;
            flex-wrap: nowrap;
        }

        /* Status bar and logs */
        .persistent-status-bar {
            width: 100%;
            height: 84px;
            min-height: 84px;
            max-height: 84px;
            overflow-y: auto;
            border: 1px solid #ddd;
            background: #fff;
            padding: 5px;
            margin: 10px 0;
            border-radius: 4px;
            display: flex;
            flex-direction: column-reverse;
            box-sizing: border-box;
        }

        .status-message {
            margin: 0;
            font-size: 13px;
            color: #666;
            padding: 2px 5px;
            line-height: 24px;
            height: 24px;
        }

        .status-message:first-child {
            background-color: #0056b3;
            color: white;
        }

        .status-message:first-child.error {
            background-color: #dc3545;
            color: white;
        }

        .status-message:first-child.success {
            background: #28a745;
        }

        /* Region entries */
        .regions-log {
            margin-top: 20px;
            border: 1px solid #ddd;
            padding: 10px;
            font-family: monospace;
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
            box-sizing: border-box;
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
            color: #6c757d;
        }

        /* Processed files */
        .processed-files {
            margin-top: 20px;
            width: 100%;
        }

        .processed-files a {
            display: block;
            margin: 10px 0;
            color: #0056b3;
            text-decoration: none;
        }

        .command-button {
            background: #0056b3;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
            box-sizing: border-box;
        }

        .command-button:hover {
            background-color: #004494;
        }

        .command-button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* Make buttons more responsive */
        @media (max-width: 576px) {
            .command-button {
                flex: 1 1 calc(50% - 5px);
                justify-content: center;
                white-space: normal;
            }
        }

        /* Waveform header */
        .waveform-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-family: monospace;
            background-color: #f8f9fa;
            padding: 5px 10px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            gap: 5px;
            flex-wrap: wrap;
            width: 100%;
            box-sizing: border-box;
        }

        /* Responsive layout for waveform header */
        @media (max-width: 576px) {
            .waveform-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .waveform-header span {
                margin-bottom: 5px;
            }

            .zoom-controls {
                width: 100%;
                justify-content: space-between;
            }
        }

        .waveform-header span {
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* Add margin to processing button for better spacing */
        #processAudio {
            margin-top: 20px;
            width: 100%;
        }

        /* Frame-specific adjustments */
        body.in-frame {
            max-width: 768px !important;
            margin-left: 20px !important;
            margin-right: 0 !important;
        }

        body.standalone {
            max-width: 768px !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        /* Prevent flickering during page load */
        body {
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        body.in-frame,
        body.standalone {
            opacity: 1;
        }

        /* Add CSS in the <style> section */
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
            box-sizing: border-box;
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

        .status-bar.drag-over {
            background: #e3f2fd;
            border-color: #2196f3;
            border-style: dashed;
        }

        /* Add this to your style section */
        .controls {
            border-bottom: none;
            /* Remove underline */
            padding-bottom: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .button-group {
            display: flex;
            gap: 2px;
        }
    </style>
    <script src="https://unpkg.com/wavesurfer.js@6.6.4"></script>
    <script src="https://unpkg.com/wavesurfer.js@6.6.4/dist/plugin/wavesurfer.regions.min.js"></script>
</head>

<body>
    <div class="container">
        <div class="editor-header">
            <h1 class="editor-title">NetBound Tools: Speaker Splitter</h1>
            <div class="persistent-status-bar status-bar" id="status-messages">
                <div class="status-message info">Waiting for audio...</div>
            </div>
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
                <div class="controls zoom-controls">
                    <button type="button" id="zoomIn" class="button-blue" title="Zoom In"><i class="fas fa-search-plus"></i></button>
                    <button type="button" id="zoomOut" class="button-blue" title="Zoom Out"><i class="fas fa-search-minus"></i></button>
                    <button type="button" id="zoomFit" class="button-blue" title="Fit to Window"><i class="fas fa-expand"></i></button>
                    <button type="button" id="clearRegions" class="button-blue warning" title="Clear All Segments"><i class="fas fa-eraser"></i></button>
                </div>
            </div>
            <div class="waveform-wrapper">
                <div id="waveform">
                    <div class="channel-label left">Left Channel</div>
                    <div class="channel-label right">Right Channel</div>
                </div>
            </div>
            <div class="controls">
                <div class="button-group">
                    <button id="jumpStart" class="button-blue" title="Jump to start"><i class="fas fa-step-backward"></i></button>
                    <button id="jumpBack" class="button-blue" title="Jump back"><i class="fas fa-backward"></i></button>
                    <button id="playPause" class="button-blue" title="Play/Pause"><i class="fas fa-play-pause"></i></button>
                    <button id="jumpForward" class="button-blue" title="Jump forward"><i class="fas fa-forward"></i></button>
                    <button id="jumpEnd" class="button-blue" title="Jump to end"><i class="fas fa-step-forward"></i></button>
                </div>

                <div class="button-group" style="margin-left: 10px;">
                    <button id="speaker1Region" class="button-blue speaker1-btn" title="Mark Speaker 1 Region">1</button>
                    <button id="speaker2Region" class="button-blue speaker2-btn" title="Mark Speaker 2 Region">2</button>
                    <button id="trashRegion" class="button-blue trash-btn" title="Mark Trash Region"><i class="fas fa-trash"></i></button>
                    <button id="undoRegion" class="button-blue" title="Undo Last Region"><i class="fas fa-undo"></i></button>
                </div>
            </div>
        </div>

        <div id="regions-log" class="regions-log"></div>

        <button type="button" id="processAudio" class="command-button">Process Audio</button>

        <div class="processed-files" id="processedFiles" style="display:none;">
            <h3>Processed Files:</h3>
            <a href="#" id="speaker1File" target="_blank">Download Speaker 1 File</a>
            <a href="#" id="speaker2File" target="_blank">Download Speaker 2 File</a>
            <a href="#" id="stereoFile" target="_blank" style="display:none;">Download Stereo File (Speaker 1 Left, Speaker 2 Right)</a>
        </div>

        <form id="regionForm" method="POST" action="process_audio.php" enctype="multipart/form-data" style="display: none;">
            <input type="hidden" name="regions" id="regions">
            <input type="hidden" name="fileName" id="fileName">
            <input type="file" name="audioFile" id="audioFileUpload">
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize elements
            const statusBar = document.getElementById('status-messages');
            const btnOpen = document.getElementById('btnOpen');
            const fileInput = document.getElementById('fileInput');
            const regionsInput = document.getElementById('regions');
            const fileNameInput = document.getElementById('fileName');
            const processedFiles = document.getElementById('processedFiles');
            const speaker1File = document.getElementById('speaker1File');
            const speaker2File = document.getElementById('speaker2File');
            const stereoFile = document.getElementById('stereoFile');
            const durationDisplay = document.getElementById('duration-display');
            const windowDisplay = document.getElementById('window-display');
            const playPauseButton = document.getElementById('playPause');
            const playPauseIcon = playPauseButton.querySelector('i.fas');
            const waveformContainer = document.getElementById('waveform');
            const waveformWrapper = document.querySelector('.waveform-wrapper');

            // Initialize data
            let currentFileName = '',
                lastEndPoint = 0,
                lastRegion = null,
                isPlaying = false;
            let regionsData = {
                speaker1: [],
                speaker2: [],
                trash: []
            };
            let sequentialRegions = [];

            // Keep track of the original file
            let originalAudioFile = null;

            // Initialize WaveSurfer with optimized stereo support
            const wavesurfer = WaveSurfer.create({
                container: '#waveform',
                waveColor: 'blue',
                progressColor: 'darkblue',
                responsive: true,
                height: 150,
                scrollParent: true,
                minPxPerSec: 50,
                fillParent: true, // Ensure the waveform fits the container initially
                normalize: true,
                splitChannels: true,
                splitChannelsOptions: {
                    channels: [{
                            waveColor: 'blue',
                            progressColor: 'darkblue',
                            height: 65, // Adjusted for better fit
                            label: 'Left'
                        },
                        {
                            waveColor: '#4488cc',
                            progressColor: '#2266aa',
                            height: 65,
                            label: 'Right'
                        }
                    ]
                },
                plugins: [
                    WaveSurfer.regions.create({
                        dragSelection: true,
                        slop: 5
                    })
                ]
            });

            // Functions
            function formatTime(seconds) {
                if (!seconds || isNaN(seconds)) return '0:00';
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = Math.floor(seconds % 60);
                return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
            }

            function updateStatus(message, type = 'info') {
                const statusBar = document.getElementById('status-messages');

                if (!statusBar) {
                    console.error('Status bar element not found');
                    return;
                }

                const messageDiv = document.createElement('div');
                messageDiv.className = `status-message ${type}`;
                messageDiv.textContent = message;

                // Insert at top (newest messages appear at top)
                statusBar.insertBefore(messageDiv, statusBar.firstChild);

                // Keep scrolled to top to see newest messages
                statusBar.scrollTop = 0;
            }

            function updateDisplays() {
                try {
                    if (!wavesurfer.drawer?.wrapper) return;
                    const duration = wavesurfer.getDuration() || 0;
                    const wrapper = wavesurfer.drawer.wrapper;
                    const scrollLeft = wrapper.scrollLeft;
                    const viewWidth = wrapper.clientWidth;
                    const pixelsPerSecond = wavesurfer.params.minPxPerSec;

                    const startTime = scrollLeft / pixelsPerSecond;
                    const viewDuration = viewWidth / pixelsPerSecond;
                    const endTime = Math.min(startTime + viewDuration, duration);

                    durationDisplay.textContent = `Duration: ${formatTime(duration)}`;
                    windowDisplay.textContent = `Viewable: ${formatTime(startTime)} - ${formatTime(endTime)}`;
                } catch (err) {
                    console.error('Display update error:', err);
                }
            }

            function handleFile(file) {
                if (!file || !(file.name.toLowerCase().endsWith('.wav'))) {
                    updateStatus('Please select a valid WAV file', 'error');
                    return;
                }

                // Store the original file for later processing
                originalAudioFile = file;

                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                updateStatus(`Loading: ${file.name} (${fileSizeMB} MB)`, 'info');
                currentFileName = file.name;
                fileNameInput.value = file.name;

                const audioUrl = URL.createObjectURL(file);
                wavesurfer.load(audioUrl);
                lastEndPoint = 0;

                // Clear previous regions
                wavesurfer.clearRegions();
                regionsData = {
                    speaker1: [],
                    speaker2: [],
                    trash: []
                };
                sequentialRegions = [];
                document.getElementById('regions-log').innerHTML = '';

                wavesurfer.once('ready', () => {
                    URL.revokeObjectURL(audioUrl);
                });
            }

            function createSpeakerRegion(speakerType) {
                const currentTime = wavesurfer.getCurrentTime();
                const startTime = sequentialRegions.length === 0 ? 0 : lastEndPoint;

                if (currentTime <= startTime) {
                    updateStatus('Please move playhead forward to create region', 'error');
                    return;
                }

                // Fill gaps with trash regions automatically
                if (sequentialRegions.length > 0) {
                    const lastRegionEnd = sequentialRegions[sequentialRegions.length - 1].end;
                    if (startTime > lastRegionEnd) {
                        const trashRegion = wavesurfer.addRegion({
                            start: lastRegionEnd,
                            end: startTime,
                            color: 'rgba(108, 117, 125, 0.3)',
                            drag: false,
                            resize: false
                        });

                        const trashData = {
                            start: lastRegionEnd,
                            end: startTime,
                            type: 'trash',
                            region: trashRegion
                        };

                        regionsData.trash.push(trashData);
                        sequentialRegions.push(trashData);
                    }
                }

                // Create region
                const color = speakerType === 1 ? 'rgba(255, 165, 0, 0.3)' :
                    speakerType === 2 ? 'rgba(0, 255, 0, 0.3)' :
                    'rgba(108, 117, 125, 0.3)';
                const label = speakerType === 'trash' ? 'Unlabeled' : `Speaker ${speakerType}`;

                const region = wavesurfer.addRegion({
                    start: startTime,
                    end: currentTime,
                    color: color,
                    drag: false,
                    resize: false
                });

                const regionData = {
                    start: startTime,
                    end: currentTime,
                    type: speakerType === 'trash' ? 'trash' : `speaker${speakerType}`,
                    region: region
                };

                if (speakerType === 'trash') {
                    regionsData.trash.push(regionData);
                } else {
                    regionsData[`speaker${speakerType}`].push(regionData);
                }

                sequentialRegions.push(regionData);

                const logEntry = document.createElement('div');
                logEntry.className = `region-entry ${speakerType === 'trash' ? 'trash' : 'speaker' + speakerType}`;
                logEntry.textContent = `${label}: ${formatTime(startTime)} - ${formatTime(currentTime)}`;
                document.getElementById('regions-log').appendChild(logEntry);
                document.getElementById('regions-log').scrollTop = document.getElementById('regions-log').scrollHeight;

                lastEndPoint = currentTime;
                lastRegion = region;
                updateStatus(`${label} region created`, 'success');
                updateDisplays();

                if (isPlaying) {
                    wavesurfer.pause();
                }
            }

            // Handle stereo/mono switching
            wavesurfer.on('ready', () => {
                const audioInfo = wavesurfer.backend.buffer;
                const isStereo = audioInfo.numberOfChannels === 2;

                // Toggle stereo mode and adjust container
                if (isStereo) {
                    waveformContainer.classList.add('stereo');
                    waveformWrapper.classList.add('stereo');
                    wavesurfer.setHeight(180);
                    wavesurfer.drawer.params.height = 180;
                    wavesurfer.drawBuffer();
                } else {
                    waveformContainer.classList.remove('stereo');
                    waveformWrapper.classList.remove('stereo');
                    wavesurfer.setHeight(150);
                    wavesurfer.drawer.params.height = 150;
                    wavesurfer.drawBuffer();
                }

                // Calculate proper zoom level to fit entire audio in view
                const duration = wavesurfer.getDuration();
                const containerWidth = waveformContainer.clientWidth - 20; // Subtract padding
                const pixelsPerSecond = containerWidth / duration;

                // Set zoom level to fit entire audio
                wavesurfer.zoom(pixelsPerSecond);

                // Setup scroll handler and update displays
                const wrapper = wavesurfer.drawer.wrapper;
                if (wrapper) {
                    wrapper.addEventListener('scroll', () => requestAnimationFrame(updateDisplays));
                }

                updateDisplays();
                const durationText = wavesurfer.getDuration().toFixed(2);
                updateStatus(`Loaded ${isStereo ? 'stereo' : 'mono'} audio: ${durationText}s`, 'success');
            });

            // Event listeners for display updates
            ['audioprocess', 'seek', 'zoom', 'interaction'].forEach(event => {
                wavesurfer.on(event, () => requestAnimationFrame(updateDisplays));
            });

            // Play/Pause handling
            playPauseButton.addEventListener('click', () => wavesurfer.playPause());

            wavesurfer.on('play', () => {
                playPauseButton.classList.add('playing');
                playPauseIcon.classList.remove('play-pause');
                playPauseIcon.classList.add('fa-pause');
                isPlaying = true;
                updateDisplays();
            });

            wavesurfer.on('pause', () => {
                playPauseButton.classList.remove('playing');
                playPauseIcon.classList.remove('fa-pause');
                playPauseIcon.classList.add('fa-play-pause');
                isPlaying = false;
                updateDisplays();
            });

            // File handling
            btnOpen.addEventListener('click', e => {
                e.preventDefault();
                fileInput.click();
            });

            fileInput.addEventListener('change', e => {
                if (e.target.files && e.target.files[0]) {
                    handleFile(e.target.files[0]);
                }
            });

            // Clear regions with confirmation
            document.getElementById('clearRegions').addEventListener('click', () => {
                if (confirm('Are you sure you want to clear all segments?')) {
                    wavesurfer.clearRegions();
                    regionsData = {
                        speaker1: [],
                        speaker2: [],
                        trash: []
                    };
                    sequentialRegions = [];
                    lastEndPoint = 0;
                    lastRegion = null;
                    document.getElementById('regions-log').innerHTML = '';
                    updateStatus('All segments cleared', 'info');
                }
            });

            // Navigation controls
            document.getElementById('jumpBack').addEventListener('click', () => wavesurfer.skip(-0.5));
            document.getElementById('jumpForward').addEventListener('click', () => wavesurfer.skip(0.5));
            document.getElementById('jumpStart').addEventListener('click', () => {
                wavesurfer.seekTo(0); // Use seekTo(0) instead of setTime(0)
                updateDisplays();
            });
            document.getElementById('jumpEnd').addEventListener('click', () => {
                // Get the total duration
                const duration = wavesurfer.getDuration();

                // Zoom out to fit entire waveform first
                const containerWidth = waveformContainer.clientWidth - 20;
                const pixelsPerSecond = containerWidth / duration;
                wavesurfer.zoom(pixelsPerSecond);

                // Allow zoom to complete and then scroll to end and set cursor
                setTimeout(() => {
                    // Calculate the position to scroll to (all the way to the right)
                    const wrapper = wavesurfer.drawer.wrapper;
                    const scrollWidth = wrapper.scrollWidth;
                    const clientWidth = wrapper.clientWidth;

                    // Scroll to the end
                    wrapper.scrollLeft = scrollWidth - clientWidth;

                    // Set the cursor position to the end (use seekTo(1);

                    updateDisplays();
                }, 100);
            });

            // Zoom controls
            document.getElementById('zoomIn').addEventListener('click', () => {
                wavesurfer.zoom(wavesurfer.params.minPxPerSec + 10);
            });

            document.getElementById('zoomOut').addEventListener('click', () => {
                wavesurfer.zoom(Math.max(wavesurfer.params.minPxPerSec - 10, 1));
            });

            document.getElementById('zoomFit').addEventListener('click', () => {
                wavesurfer.zoom(wavesurfer.params.minPxPerSec);
                requestAnimationFrame(() => {
                    wavesurfer.drawer.wrapper.scrollLeft = 0;
                    updateDisplays();
                });
            });
        });
    </script>
</body>

</html>
