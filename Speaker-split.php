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
            text-align: left;
        }

        .container {
            background: #f4f4f4;
            width: 100%;
            margin: 0 auto;
            box-sizing: border-box;
            overflow: hidden;
            padding: 0 20px;
        }

        .editor-header {
            background: #f4f4f4;
            padding: 15px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .editor-title {
            margin: 0;
            color: #0056b3;
            line-height: 1.2;
            font-weight: bold;
            font-size: 1.5em;
        }

        /* Waveform container with stereo support */
        #waveform-container {
            position: relative;
            margin: 20px 0;
            padding: 10px;
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
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
        }

        #waveform {
            width: 100%;
            height: 150px; /* Default height for mono */
            background-color: #e0f0ff;
            border: 2px solid #0056b3;
            position: relative;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        #waveform.stereo {
            height: 180px; /* Increased height for stereo */
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
            flex-wrap: nowrap;
        }

        .button-blue {
            background-color: #0056b3;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            min-width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            padding: 0;
            font-size: 1em;
            position: relative;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .button-blue:hover {
            background-color: #004494;
        }

        .button-blue:active,
        .button-blue.playing {
            transform: translateY(1px);
            box-shadow: 0 1px 2px rgba(0,0,0,0.2);
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

        /* Controls spacing */
        .controls {
            display: flex;
            justify-content: center;
            margin: 10px 0;
            gap: 20px;
        }

        .zoom-controls {
            margin: 0;
            gap: 10px;
        }

        /* Status bar and logs */
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

        /* Region entries */
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
            color: #6c757d;
        }

        /* Processed files */
        .processed-files {
            margin-top: 20px;
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
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            white-space: nowrap;
        }

        .command-button:hover {
            background-color: #004494;
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
            gap: 10px;
            flex-wrap: nowrap;
        }

        .waveform-header span {
            white-space: nowrap;
            flex-shrink: 0;
            min-width: 120px;
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
                <button type="button" id="jumpStart" class="button-blue" title="Jump to Start"><i class="fas fa-fast-backward"></i></button>
                <button type="button" id="playPause" class="button-blue" title="Play/Pause">
                    <i class="fas fa-play"></i>
                </button>
                <button type="button" id="backward" class="button-blue" title="Skip Backward"><i class="fas fa-backward"></i></button>
                <button type="button" id="forward" class="button-blue" title="Skip Forward"><i class="fas fa-forward"></i></button>
                <button type="button" id="speaker1Region" class="button-blue" title="Mark Speaker 1"><i class="fas fa-user"></i>1</button>
                <button type="button" id="speaker2Region" class="button-blue" title="Mark Speaker 2"><i class="fas fa-user"></i>2</button>
                <button type="button" id="trashRegion" class="button-blue warning" title="Mark for Deletion"><i class="fas fa-ban"></i></button>
            </div>
        </div>

        <div id="regions-log" class="regions-log"></div>

        <button type="button" id="processAudio" class="command-button">Process Audio</button>

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
            // Initialize elements
            const statusBar = document.getElementById('statusBar');
            const btnOpen = document.getElementById('btnOpen');
            const fileInput = document.getElementById('fileInput');
            const regionsInput = document.getElementById('regions');
            const fileNameInput = document.getElementById('fileName');
            const processedFiles = document.getElementById('processedFiles');
            const speaker1File = document.getElementById('speaker1File');
            const speaker2File = document.getElementById('speaker2File');
            const durationDisplay = document.getElementById('duration-display');
            const windowDisplay = document.getElementById('window-display');
            const playPauseButton = document.getElementById('playPause');
            const playPauseIcon = playPauseButton.querySelector('i.fas');
            const waveformContainer = document.getElementById('waveform');
            const waveformWrapper = document.querySelector('.waveform-wrapper');

            // Initialize data
            let currentFileName = '', lastEndPoint = 0, lastRegion = null, isPlaying = false;
            let regionsData = { speaker1: [], speaker2: [], trash: [] };
            let sequentialRegions = [];

            // Initialize WaveSurfer with optimized stereo support
            const wavesurfer = WaveSurfer.create({
                container: '#waveform',
                waveColor: 'blue',
                progressColor: 'darkblue',
                responsive: true,
                height: 150,
                scrollParent: true,
                minPxPerSec: 50,
                fillParent: false,
                normalize: true,
                splitChannels: true,
                splitChannelsOptions: {
                    channels: [
                        {
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
                const messageDiv = document.createElement('div');
                messageDiv.className = `status-message ${type}`;
                messageDiv.textContent = message;
                statusBar.appendChild(messageDiv);
                statusBar.scrollTop = statusBar.scrollHeight;
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

                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                updateStatus(`Loading: ${file.name} (${fileSizeMB} MB)`, 'info');
                currentFileName = file.name;
                fileNameInput.value = file.name;

                const audioUrl = URL.createObjectURL(file);
                wavesurfer.load(audioUrl);
                lastEndPoint = 0;

                // Clear previous regions
                wavesurfer.clearRegions();
                regionsData = { speaker1: [], speaker2: [], trash: [] };
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

                // Setup scroll handler and update displays
                const wrapper = wavesurfer.drawer.wrapper;
                if (wrapper) {
                    wrapper.addEventListener('scroll', () => requestAnimationFrame(updateDisplays));
                }

                updateDisplays();
                const duration = wavesurfer.getDuration().toFixed(2);
                updateStatus(`Loaded ${isStereo ? 'stereo' : 'mono'} audio: ${duration}s`, 'success');
            });

            // Event listeners for display updates
            ['audioprocess', 'seek', 'zoom', 'interaction'].forEach(event => {
                wavesurfer.on(event, () => requestAnimationFrame(updateDisplays));
            });

            // Play/Pause handling
            playPauseButton.addEventListener('click', () => wavesurfer.playPause());

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
                    regionsData = { speaker1: [], speaker2: [], trash: [] };
                    sequentialRegions = [];
                    lastEndPoint = 0;
                    lastRegion = null;
                    document.getElementById('regions-log').innerHTML = '';
                    updateStatus('All segments cleared', 'info');
                }
            });

            // Navigation controls
            document.getElementById('backward').addEventListener('click', () => wavesurfer.skip(-0.5));
            document.getElementById('forward').addEventListener('click', () => wavesurfer.skip(0.5));
            document.getElementById('jumpStart').addEventListener('click', () => {
                wavesurfer.setTime(0);
                updateDisplays();
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

            document.getElementById('btnRestart').addEventListener('click', () => {
                location.href = location.pathname;
            });

            // Region buttons
            document.getElementById('speaker1Region').addEventListener('click', () => createSpeakerRegion(1));
            document.getElementById('speaker2Region').addEventListener('click', () => createSpeakerRegion(2));
            document.getElementById('trashRegion').addEventListener('click', () => createSpeakerRegion('trash'));

            // Process audio
            document.getElementById('processAudio').addEventListener('click', () => {
                if (sequentialRegions.length === 0) {
                    updateStatus('No regions marked for processing', 'error');
                    return;
                }

                const duration = wavesurfer.getDuration();
                const lastRegion = sequentialRegions[sequentialRegions.length - 1];

                if (lastRegion.end < duration) {
                    const trashRegion = wavesurfer.addRegion({
                        start: lastRegion.end,
                        end: duration,
                        color: 'rgba(108, 117, 125, 0.3)',
                        drag: false,
                        resize: false
                    });

                    const trashData = {
                        start: lastRegion.end,
                        end: duration,
                        type: 'trash',
                        region: trashRegion
                    };

                    regionsData.trash.push(trashData);
                    sequentialRegions.push(trashData);
                }

                const processData = {
                    speaker1: sequentialRegions
                        .filter(r => r.type === 'speaker1')
                        .map(r => ({ start: r.start, end: r.end })),
                    speaker2: sequentialRegions
                        .filter(r => r.type === 'speaker2')
                        .map(r => ({ start: r.start, end: r.end })),
                    trash: sequentialRegions
                        .filter(r => r.type === 'trash')
                        .map(r => ({ start: r.start, end: r.end }))
                };

                regionsInput.value = JSON.stringify(processData);
                updateStatus('Processing audio regions...', 'info');

                const formData = new FormData(document.getElementById('regionForm'));
                fetch('process_audio.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) throw new Error('Processing failed');
                    return response.json();
                })
                .then(data => {
                    speaker1File.href = data.speaker1;
                    speaker2File.href = data.speaker2;
                    processedFiles.style.display = 'block';
                    updateStatus('Processing complete! Files ready for download.', 'success');
                })
                .catch(error => {
                    updateStatus('Error processing audio: ' + error.message, 'error');
                    console.error('Processing error:', error);
                });
            });
        });
    </script>
</body>
</html>
