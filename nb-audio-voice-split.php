<?php
// Voice Splitter Tool v1.5
// Created by: NetBound Team
// Functionality: Audio segmentation for speaker separation
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetBound Tools: Audio Voice Splitter</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f4f4f9;
            width: 768px;
            /* Fixed width for standalone mode */
            margin: 0 auto;
            box-sizing: border-box;
            transition: margin 0.3s ease;
        }

        body.in-iframe {
            margin: 0;
            /* Left-aligned in iframe */
            width: 100%;
            max-width: 768px;
        }

        body.in-iframe .editor-header {
            padding-top: 15px;
            /* Add space above the title when in iframe */
        }

        /* Layout Components */
        .container {
            background: #f4f4f9;
            width: 100%;
            max-width: 768px;
            margin: 0 auto;
            box-sizing: border-box;
            padding: 0 20px;
        }

        .editor-header {
            background: #f4f4f9;
            padding: 0;
            border-bottom: 1px solid #dee2e6;
            width: 100%;
            box-sizing: border-box;
        }

        .editor-title {
            margin: 10px 0;
            padding: 0;
            color: #0056b3;
            line-height: 1.2;
            font-weight: bold;
            font-size: 18px;
        }

        /* Waveform container with responsive design */
        #waveform-container {
            position: relative;
            margin: 5px 0 !important;
            /* Reduced from 10px to 5px */
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
            background-color: #f0f8ff;
            border: 1px solid #0056b3;
            position: relative;
            transition: all 0.3s ease;
            overflow: hidden;
            box-sizing: border-box;
        }

        .button-controls {
            border-bottom: none !important;
            margin-bottom: 5px !important;
        }

        .editor-header {
            border-bottom: none !important;
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
            padding: 5px 0;
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
            justify-content: space-between;
            margin: 10px 0;
            gap: 5px !important;
            /* Reduced from 30px to make everything fit */
            flex-wrap: nowrap;
            /* Keep buttons on same row */
            align-items: center;
        }

        .button-group {
            display: flex;
            gap: 5px !important;
            /* Reduced from 10px */
        }

        /* Position the process button on the same row */
        #processAudio {
            margin: 0;
            margin-left: auto;
            /* Push to right side */
            width: auto !important;
            align-self: center;
            position: relative;
            top: 0;
        }

        /* Keep process button visible in the control area */
        .process-button-container {
            display: flex;
            align-items: center;
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
            cursor: pointer;
            position: relative;
        }

        .status-bar::before {
            content: '\f0a6';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 10px;
            top: 5px;
            color: #aaa;
            font-size: 14px;
        }

        .status-message {
            padding: 5px;
            margin: 2px 0;
            border-radius: 3px;
            color: #666;
        }

        .status-bar {
            margin: 5px 0 !important;
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
            border-left: 3px solid rgba(255, 140, 0, 0.7);
        }

        .region-entry.speaker2 {
            color: #28a745;
            border-left: 3px solid rgba(0, 255, 0, 0.7);
        }

        .region-entry.trash {
            color: #6c757d;
            border-left: 3px solid rgba(108, 117, 125, 0.7);
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

        /* Prevent flickering during page load */
        body {
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        body.loaded {
            opacity: 1;
        }

        /* Style for cursor position element */
        #cursor-position {
            background: #e9ecef;
            padding: 3px 5px !important;
            margin: 0 !important;
            font-size: 12px !important;
            font-family: monospace;
            white-space: nowrap;
            line-height: 1;
        }

        /* Process Controls */
        .process-controls .command-button {
            width: auto !important;
        }

        #processAudio {
            margin-top: 0;
            margin-left: 30px;
            width: auto !important;
        }

        /* Responsive Styles */
        @media (max-width: 576px) {
            .controls {
                flex-wrap: nowrap;
                padding: 0;
                gap: 5px;
            }

            .button-blue {
                min-width: calc(25% - 10px);
                flex-grow: 1;
            }

            .command-button {
                flex: 1 1 calc(50% - 5px);
                justify-content: center;
                white-space: normal;
            }

            .waveform-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

        /* Advanced Save Options */
        .save-options-container {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            display: none;
        }

        .save-option {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .save-option-label {
            width: 80px;
            font-weight: 500;
        }

        .save-option select {
            flex-grow: 1;
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #ced4da;
            background: #fff;
            margin-right: 10px;
        }

        .save-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }

        .save-button:hover {
            background-color: #218838;
        }

        .save-button i {
            margin-right: 5px;
        }

        /* Loading indicator */
        .loading-indicator {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            color: white;
        }

        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
    <script src="https://unpkg.com/wavesurfer.js@6.6.4"></script>
    <script src="https://unpkg.com/wavesurfer.js@6.6.4/dist/plugin/wavesurfer.regions.min.js"></script>
</head>

<body>
    <div class="container">
        <div class="editor-header">
            <h1 class="editor-title">NetBound Tools: Audio Voice Splitter</h1>
            <div class="status-bar" id="status-messages">
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
                    <button id="jumpBack" class="button-blue" title="Jump back 3s"><i class="fas fa-backward"></i></button>
                    <button id="playPause" class="button-blue" style="width: 50px" title="Play/Pause"><i class="fas fa-play"></i></button>
                    <button id="jumpForward" class="button-blue" title="Jump forward 3s"><i class="fas fa-forward"></i></button>
                    <button id="jumpEnd" class="button-blue" title="Jump to end"><i class="fas fa-step-forward"></i></button>
                </div>

                <span id="cursor-position">Position: 0:00</span>

                <div class="button-group" style="margin-left: 10px;">
                    <button id="speaker1Region" class="button-blue" title="Mark Speaker 1 Region" style="background-color: #ff8c00;"><i class="fas fa-user"></i></button>
                    <button id="speaker2Region" class="button-blue" title="Mark Speaker 2 Region" style="background-color: #28a745;"><i class="fas fa-user-alt"></i></button>
                    <button id="trashRegion" class="button-blue" title="Mark Trash Region" style="background-color: #6c757d;"><i class="fas fa-trash"></i></button>
                    <button id="undoRegion" class="button-blue" title="Undo Last Region"><i class="fas fa-undo"></i></button>


                    <!-- Add process button here -->
                    <button type="button" id="processAudio" class="command-button" style="margin-left: auto; padding: 6px 8px;">
                        <i class="fas fa-cogs"></i> Process
                    </button>
                </div>
            </div>
        </div>

        <div id="regions-log" class="regions-log">

        </div>

        <div class="processed-files" id="processedFiles">
            <h3>Processed Files:</h3>
            <p class="placeholder-text">Process audio to generate download links</p>
        </div>

        <form id="regionForm" method="POST" action="process_audio.php" enctype="multipart/form-data" style="display: none;">
            <input type="hidden" name="regions" id="regions">
            <input type="hidden" name="fileName" id="fileName">
            <input type="file" name="audioFile" id="audioFileUpload">
        </form>

        <!-- Advanced Save Options -->
        <div id="saveOptionsContainer" class="save-options-container">
            <h3>Advanced Save Options</h3>

            <div class="save-option">
                <span class="save-option-label">Voice 1:</span>
                <select id="speaker1SaveOption">
                    <option value="default">Full track with voice 2 muted</option>
                    <option value="edited">Full track with muted parts deleted</option>
                    <option value="speaker1">Voice 1 only</option>
                    <option value="full_lr">Full track with R/L separated voices</option>
                </select>
                <button id="saveSpeaker1" class="save-button">
                    <i class="fas fa-download"></i> Save
                </button>
            </div>

            <div id="speaker2SaveOptions" class="save-option">
                <span class="save-option-label">Voice 2:</span>
                <select id="speaker2SaveOption">
                    <option value="default">Full track with voice 1 muted</option>
                    <option value="edited">Full track with muted parts deleted</option>
                    <option value="speaker2">Voice 2 only</option>
                    <option value="full_lr">Full track with R/L separated voices</option>
                </select>
                <button id="saveSpeaker2" class="save-button">
                    <i class="fas fa-download"></i> Save
                </button>
            </div>

            <div class="save-option">
                <span class="save-option-label">Stereo Output:</span>
                <select id="stereoSaveOption">
                    <option value="full_lr">Full track with R/L separated voices</option>
                    <option value="default">Full track with all parts included</option>
                    <option value="edited">Full track with muted portions removed</option>
                </select>
                <button id="saveStereo" class="save-button">
                    <i class="fas fa-download"></i> Save
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Check if we're in an iframe and adjust styling
            function checkIfInIframe() {
                try {
                    const isInIframe = window.self !== window.top;
                    if (isInIframe) {
                        document.body.classList.add('in-iframe');
                    }
                    return isInIframe;
                } catch (e) {
                    // If we can't access window.top due to security restrictions, we're in an iframe
                    document.body.classList.add('in-iframe');
                    return true;
                }
            }

            const inIframe = checkIfInIframe();

            // Mark body as loaded to enable transitions
            document.body.classList.add('loaded');

            // Initialize elements
            const statusBar = document.getElementById('status-messages');
            const btnOpen = document.getElementById('btnOpen');
            const btnRestart = document.getElementById('btnRestart');
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
            const waveformContainer = document.getElementById('waveform-container');
            const waveformElement = document.getElementById('waveform');
            const waveformWrapper = document.querySelector('.waveform-wrapper');
            const regionsLog = document.getElementById('regions-log');
            const processAudioBtn = document.getElementById('processAudio');
            const cursorPosition = document.getElementById('cursor-position');

            // Initialize data
            let currentFileName = '';
            let lastEndPoint = 0;
            let lastRegion = null;
            let isPlaying = false;
            let regionsData = {
                speaker1: [],
                speaker2: [],
                trash: []
            };
            let sequentialRegions = [];
            let originalAudioFile = null;

            // Initialize WaveSurfer
            const wavesurfer = WaveSurfer.create({
                container: '#waveform',
                waveColor: '#0056b3',
                progressColor: '#0066cc',
                cursorWidth: 2,
                responsive: true,
                height: 150,
                scrollParent: true,
                minPxPerSec: 50,
                fillParent: true,
                normalize: true,
                splitChannels: true,
                splitChannelsOptions: {
                    channels: [{
                            waveColor: '#4488cc',
                            progressColor: '#2266aa',
                            height: 75,
                            label: 'Left'
                        },
                        {
                            waveColor: '#99bbdd',
                            progressColor: '#6699cc',
                            height: 75,
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
                    cursorPosition.textContent = `Position: ${formatTime(wavesurfer.getCurrentTime())}`;
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

                // Clear placeholder text
                const placeholders = document.querySelectorAll('.placeholder-text');
                placeholders.forEach(placeholder => {
                    placeholder.style.display = 'none';
                });

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
                regionsLog.innerHTML = '';
                regionsLog.innerHTML = '<p class="placeholder-text">No regions created yet. Mark speaker segments using the buttons above.</p>';

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

                // Create region
                const color = speakerType === 1 ? 'rgba(255, 102, 0, 0.3)' :
                    speakerType === 2 ? 'rgba(40, 167, 69, 0.3)' :
                    'rgba(108, 117, 125, 0.3)';
                const label = speakerType === 'trash' ? 'Trash' : `Speaker ${speakerType}`;

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
                    regionsData['speaker' + speakerType].push(regionData);
                }

                sequentialRegions.push(regionData);

                const logEntry = document.createElement('div');
                logEntry.className = `region-entry ${speakerType === 'trash' ? 'trash' : 'speaker' + speakerType}`;
                logEntry.textContent = `${label}: ${formatTime(startTime)} - ${formatTime(currentTime)}`;
                document.getElementById('regions-log').style.cssText = 'height: 85px !important; min-height: 85px; overflow-y: auto; line-height: 16px;';
                regionsLog.appendChild(logEntry);
                regionsLog.scrollTop = regionsLog.scrollHeight;

                lastEndPoint = currentTime;
                lastRegion = region;
                updateStatus(`${label} region created`, 'success');
                updateDisplays();
            }

            function processAudio() {
                if (!originalAudioFile || sequentialRegions.length === 0) {
                    updateStatus('No audio file or regions to process', 'error');
                    return;
                }

                // Show processing options
                document.getElementById('saveOptionsContainer').style.display = 'block';
                updateStatus('Select processing options', 'info');
            }

            // Add right after your processAudio() function

            // Function to handle saving with selected options
            function saveProcessedAudio(speaker, option) {
                if (!originalAudioFile) {
                    updateStatus('No audio file loaded', 'error');
                    return;
                }

                if (sequentialRegions.length === 0) {
                    updateStatus('No regions defined', 'error');
                    return;
                }

                // Create form data for submission
                const formData = new FormData();
                formData.append('speaker', speaker);
                formData.append('option', option);
                formData.append('fileName', currentFileName);

                // Convert regions to the format expected by the backend
                const processedRegions = {
                    speaker1: regionsData.speaker1.map(r => ({
                        start: r.start,
                        end: r.end
                    })),
                    speaker2: regionsData.speaker2.map(r => ({
                        start: r.start,
                        end: r.end
                    })),
                    trash: regionsData.trash.map(r => ({
                        start: r.start,
                        end: r.end
                    }))
                };
                formData.append('regions', JSON.stringify(processedRegions));

                // For processing, you'll need the original audio file
                if (originalAudioFile instanceof File) {
                    // Make a fresh copy of the file to avoid issues
                    const fileClone = new File([originalAudioFile], originalAudioFile.name, {
                        type: originalAudioFile.type,
                        lastModified: originalAudioFile.lastModified
                    });
                    formData.append('audioFile', fileClone);
                    console.log("File added to form:", originalAudioFile.name, originalAudioFile.size);
                } else {
                    updateStatus('Error: No valid audio file available', 'error');
                    return;
                }

                updateStatus(`Processing ${speaker} with option: ${option}...`, "info");

                // Create loading indicator
                const loadingIndicator = document.createElement('div');
                loadingIndicator.className = 'loading-indicator';
                loadingIndicator.innerHTML = '<div class="spinner"></div><p>Processing audio...</p>';
                document.body.appendChild(loadingIndicator);

                console.log("Sending data to backend:", {
                    speaker,
                    option,
                    fileName: currentFileName,
                    regionCount: sequentialRegions.length,
                    fileSize: originalAudioFile ? originalAudioFile.size : 'No file'
                });

                fetch('process_advanced_audio.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log("Response status:", response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Remove loading indicator
                        document.body.removeChild(loadingIndicator);

                        console.log("Backend response:", data);
                        if (data.success) {
                            updateStatus(`${speaker} processed successfully!`, "success");

                            // Create download link
                            if (data.fileUrl) {
                                const a = document.createElement('a');
                                a.href = data.fileUrl;
                                a.download = data.fileName || `${speaker}_processed.wav`;
                                document.body.appendChild(a);
                                a.click();
                                document.body.removeChild(a);
                            }
                        } else {
                            updateStatus(`Error: ${data.message || 'Processing failed'}`, "error");
                        }
                    })
                    .catch(error => {
                        // Remove loading indicator
                        if (document.body.contains(loadingIndicator)) {
                            document.body.removeChild(loadingIndicator);
                        }
                        updateStatus(`Error: ${error.message}`, "error");
                        console.error("Processing error:", error);
                    });
            }

            // Handle stereo/mono switching
            wavesurfer.on('ready', () => {
                const audioInfo = wavesurfer.backend.buffer;
                const isStereo = audioInfo.numberOfChannels === 2;

                // Toggle stereo mode and adjust container
                if (isStereo) {
                    waveformElement.classList.add('stereo');
                    waveformWrapper.classList.add('stereo');
                    wavesurfer.setHeight(180);
                } else {
                    waveformElement.classList.remove('stereo');
                    waveformWrapper.classList.remove('stereo');
                    wavesurfer.setHeight(150);
                }

                // Fit entire audio in view with a slight margin to prevent scrollbars
                const duration = wavesurfer.getDuration();
                const containerWidth = waveformContainer.clientWidth - 20; // Subtract padding
                // Reduce zoom by 5% to ensure no scrollbar appears
                const pixelsPerSecond = (containerWidth / duration) * 0.95;
                wavesurfer.zoom(pixelsPerSecond);

                // Setup scroll handler
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
            playPauseButton.addEventListener('click', () => {
                wavesurfer.playPause();
            });

            // Forward/Back 3 seconds
            document.getElementById('jumpForward').addEventListener('click', () => {
                const currentTime = wavesurfer.getCurrentTime();
                const duration = wavesurfer.getDuration();
                const newTime = Math.min(currentTime + 3, duration);
                wavesurfer.seekTo(newTime / duration);
                updateDisplays();
            });

            document.getElementById('jumpBack').addEventListener('click', () => {
                const currentTime = wavesurfer.getCurrentTime();
                const duration = wavesurfer.getDuration();
                const newTime = Math.max(currentTime - 3, 0);
                wavesurfer.seekTo(newTime / duration);
                updateDisplays();
            });

            // Play/Pause events
            wavesurfer.on('play', () => {
                playPauseButton.classList.add('playing');
                playPauseIcon.classList.remove('fa-play');
                playPauseIcon.classList.add('fa-pause');
                isPlaying = true;
                updateDisplays();
            });

            wavesurfer.on('pause', () => {
                playPauseButton.classList.remove('playing');
                playPauseIcon.classList.remove('fa-pause');
                playPauseIcon.classList.add('fa-play');
                isPlaying = false;
                updateDisplays();
            });

            // File handling
            btnOpen.addEventListener('click', () => {
                fileInput.click();
            });
            fileInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files[0]) {
                    handleFile(e.target.files[0]);
                }
            });

            // Make status bar drag-ready instead of separate area
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                statusBar.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                statusBar.addEventListener(eventName, () => {
                    statusBar.classList.add('drag-over');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                statusBar.addEventListener(eventName, () => {
                    statusBar.classList.remove('drag-over');
                });
            });

            statusBar.addEventListener('drop', (e) => {
                const file = e.dataTransfer.files[0];
                if (file) {
                    handleFile(file);
                }
            });

            statusBar.addEventListener('click', () => {
                if (!wavesurfer.isReady) {
                    fileInput.click();
                }
            });

            // Speaker region buttons
            document.getElementById('speaker1Region').addEventListener('click', () => {
                if (!wavesurfer.isReady) {
                    updateStatus('Please load an audio file first', 'error');
                    return;
                }
                createSpeakerRegion(1);
            });

            document.getElementById('speaker2Region').addEventListener('click', () => {
                if (!wavesurfer.isReady) {
                    updateStatus('Please load an audio file first', 'error');
                    return;
                }
                createSpeakerRegion(2);
            });

            document.getElementById('trashRegion').addEventListener('click', () => {
                if (!wavesurfer.isReady) {
                    updateStatus('Please load an audio file first', 'error');
                    return;
                }
                createSpeakerRegion('trash');
            });

            // Undo last region
            document.getElementById('undoRegion').addEventListener('click', () => {
                if (sequentialRegions.length === 0) {
                    updateStatus('No regions to undo', 'error');
                    return;
                }

                const lastRegionData = sequentialRegions.pop();
                lastRegionData.region.remove();

                if (lastRegionData.type === 'trash') {
                    regionsData.trash.pop();
                } else {
                    regionsData[lastRegionData.type].pop();
                }

                // Update last endpoint
                lastEndPoint = sequentialRegions.length > 0 ?
                    sequentialRegions[sequentialRegions.length - 1].end : 0;

                // Remove last log entry
                if (regionsLog.lastChild) {
                    regionsLog.removeChild(regionsLog.lastChild);
                }

                updateStatus('Last region removed', 'info');
            });

            // Process audio button
            processAudioBtn.addEventListener('click', processAudio);

            // Clear regions handler
            document.getElementById('clearRegions').addEventListener('click', () => {
                if (confirm('Are you sure you want to clear all segments?')) {
                    wavesurfer.clearRegions();
                    regionsData = {
                        speaker1: [],
                        speaker2: [],
                        trash: []
                    };
                    sequentialRegions = [];
                    regionsLog.innerHTML = '';
                    lastEndPoint = 0;
                    document.getElementById('saveOptionsContainer').style.display = 'none';
                    processedFiles.style.display = 'none';
                }
            });

            // Restart button functionality
            btnRestart.addEventListener('click', () => {
                if (!originalAudioFile) {
                    return;
                }

                document.getElementById('saveOptionsContainer').style.display = 'none';
                processedFiles.style.display = 'none';

                if (confirm('Are you sure you want to restart? All segments will be cleared.')) {
                    handleFile(originalAudioFile);
                }
            });

            // Zoom controls
            document.getElementById('zoomIn').addEventListener('click', () => {
                wavesurfer.zoom(wavesurfer.params.minPxPerSec + 10);
            });

            document.getElementById('zoomOut').addEventListener('click', () => {
                wavesurfer.zoom(Math.max(wavesurfer.params.minPxPerSec - 10, 10));
            });

            document.getElementById('zoomFit').addEventListener('click', () => {
                if (!wavesurfer.isReady) return;

                const duration = wavesurfer.getDuration();
                const containerWidth = waveformContainer.clientWidth - 20;
                const pixelsPerSecond = containerWidth / duration;

                wavesurfer.zoom(pixelsPerSecond);
                requestAnimationFrame(() => {
                    wavesurfer.drawer.wrapper.scrollLeft = 0;
                    updateDisplays();
                });
            });

            // Updated jump start functionality
            document.getElementById('jumpStart').addEventListener('click', () => {
                if (sequentialRegions.length > 0) {
                    // If regions exist, move to the end of the last region
                    wavesurfer.seekTo(lastEndPoint / wavesurfer.getDuration());
                } else {
                    // Otherwise go to beginning
                    wavesurfer.seekTo(0);
                }
                updateDisplays();
            });

            // Fixed jump to end functionality
            document.getElementById('jumpEnd').addEventListener('click', () => {
                // Get the total duration
                const duration = wavesurfer.getDuration();

                // Jump to end
                wavesurfer.seekTo(1); // 1 = 100% of audio

                // Wait for seek to complete and ensure displays update
                setTimeout(() => {
                    updateDisplays();
                }, 50);
            });

            // Initial status message
            updateStatus('Ready to load audio file', 'info');

            // Add this to your DOMContentLoaded callback
            // Advanced save options handlers
            const speakers = ['speaker1', 'speaker2', 'stereo'];
            speakers.forEach(speaker => {
                document.getElementById(`save${speaker.charAt(0).toUpperCase() + speaker.slice(1)}`).addEventListener('click', function() {
                    const option = document.getElementById(`${speaker}SaveOption`).value;
                    saveProcessedAudio(speaker, option);
                });
            });
        }); // Close the DOMContentLoaded event listener
    </script>
</body>

</html>
