<?php

/**
 * NetBound Tools: Speaker Audio Splitter Advanced Processing
 * Version: 1.4
 * Created by: NetBound Team
 *
 * This file handles advanced audio processing options for the Speaker Splitter tool.
 */

// Set higher PHP limits for audio processing
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');
ini_set('upload_max_filesize', '50M');

// Set content type for JSON response
header('Content-Type: application/json');

// Helper function for logging
function logMessage($message)
{
    $logFile = __DIR__ . '/audio_processing.log';
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

function logError($message)
{
    logMessage("ERROR: " . $message);
}

/**
 * Extract a portion of a WAV file
 * @param string $sourceFile Path to source WAV file
 * @param string $outputFile Path to output WAV file
 * @param float $startTime Start time in seconds
 * @param float $endTime End time in seconds
 */
function extractWavRegion($sourceFile, $outputFile, $startTime, $endTime)
{
    logMessage("Extracting from $sourceFile to $outputFile");

    // Open the source file
    $handle = fopen($sourceFile, 'rb');
    if (!$handle) {
        throw new Exception("Failed to open source file: $sourceFile");
    }

    // Read WAV header (44 bytes for standard WAV format)
    $header = fread($handle, 44);

    // Extract important WAV parameters from header
    $channels = ord($header[22]) | (ord($header[23]) << 8);
    $sampleRate = ord($header[24]) | (ord($header[25]) << 8) | (ord($header[26]) << 16) | (ord($header[27]) << 24);
    $bytesPerSample = (ord($header[34]) | (ord($header[35]) << 8)) / 8;

    // Calculate positions
    $bytesPerSecond = $sampleRate * $channels * $bytesPerSample;
    $startPos = (int)($startTime * $bytesPerSecond) + 44; // Add header size
    $endPos = (int)($endTime * $bytesPerSecond) + 44;

    // Create output file
    $outputHandle = fopen($outputFile, 'wb');
    if (!$outputHandle) {
        fclose($handle);
        throw new Exception("Failed to create output file: $outputFile");
    }

    // Write header (we'll update this later)
    fwrite($outputHandle, $header);

    // Seek to start position
    fseek($handle, $startPos);

    // Calculate bytes to read
    $bytesLeft = $endPos - $startPos;
    $chunkSize = 8192; // Read in chunks
    $totalDataBytes = 0;

    logMessage("Extracting from $startTime to $endTime ($bytesLeft bytes)");

    // Read and write data
    while ($bytesLeft > 0) {
        $readSize = min($bytesLeft, $chunkSize);
        $data = fread($handle, $readSize);
        fwrite($outputHandle, $data);
        $bytesLeft -= strlen($data);
        $totalDataBytes += strlen($data);
    }

    logMessage("Extracted from $startTime to $endTime ($totalDataBytes bytes)");

    // Update file size in header
    $fileSize = ftell($outputHandle) - 8; // File size minus 8 bytes for RIFF header
    fseek($outputHandle, 4);
    fwrite($outputHandle, pack('V', $fileSize));

    // Update header with correct data size
    $dataSize = $totalDataBytes;
    fseek($outputHandle, 40);
    fwrite($outputHandle, pack('V', $dataSize));

    // Close files
    fclose($handle);
    fclose($outputHandle);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Only POST method is accepted");
    }

    if (empty($_FILES['audioFile']) || $_FILES['audioFile']['error'] > 0) {
        throw new Exception("No audio file uploaded or upload error");
    }

    if (empty($_POST['regions']) || empty($_POST['speaker']) || empty($_POST['option'])) {
        throw new Exception("Missing required parameters");
    }

    // Get parameters
    $speaker = $_POST['speaker'];
    $option = $_POST['option'];
    $fileName = $_POST['fileName'];
    $regions = json_decode($_POST['regions'], true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid regions JSON data");
    }

    // Process the uploaded file
    $tempFile = $_FILES['audioFile']['tmp_name'];

    // Create directory for output files if it doesn't exist
    $outputDir = __DIR__ . '/processed_audio';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }

    // Generate output filename based on speaker and option
    $fileBaseName = pathinfo($fileName, PATHINFO_FILENAME);
    $outputFile = "$outputDir/{$fileBaseName}_{$speaker}_{$option}.wav";

    // Basic implementation - just copy the file for now as a placeholder
    // In a real implementation, you would process regions differently based on option
    if (!copy($tempFile, $outputFile)) {
        throw new Exception("Failed to create output file");
    }

    // Output response
    echo json_encode([
        'status' => 'success',
        'file' => "processed_audio/{$fileBaseName}_{$speaker}_{$option}.wav",
        'filename' => "{$fileBaseName}_{$speaker}_{$option}.wav"
    ]);
} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetBound Tools: Audio Splitter</title>
    <link rel="stylesheet" href="nb-voice-split.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <div class="zoom-buttons">
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
                    <button id="playPause" class="button-blue" title="Play/Pause"><i class="fas fa-play"></i></button>
                    <button id="jumpForward" class="button-blue" title="Jump forward"><i class="fas fa-forward"></i></button>
                    <button id="jumpEnd" class="button-blue" title="Jump to end"><i class="fas fa-step-forward"></i></button>
                </div>
                <div class="button-group">
                    <button id="speaker1Region" class="button-blue speaker1-btn" title="Mark Speaker 1 Region">
                        <i class="fas fa-user-circle"></i> 1
                    </button>
                    <button id="speaker2Region" class="button-blue speaker2-btn" title="Mark Speaker 2 Region">
                        <i class="fas fa-user-circle"></i> 2
                    </button>
                    <button id="mutedRegion" class="button-blue muted-btn" title="No Voice">
                        <i class="fas fa-microphone-slash"></i>
                    </button>
                    <button id="undoRegion" class="button-blue" title="Undo Last Region">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </div>
            <div class="button-group" style="margin-left: 30px;">
                <button type="button" id="processAudio" class="command-button">
                    <i class="fas fa-cogs"></i> Process Audio
                </button>
            </div>
        </div>
        <div class="button-group">
            <button type="button" id="saveRegions" class="command-button">
                <i class="fas fa-save"></i> Save Regions
            </button>
        </div>
        <div id="regions-log" class="regions-log"></div>
        <div class="processed-files" id="processedFiles" style="display:none;">
            <h3>Processed Files:</h3>
            <a href="#" id="speaker1File" target="_blank">Download Speaker 1 File</a>
            <a href="#" id="speaker2File" target="_blank">Download Speaker 2 File</a>
            <a href="#" id="stereoFile" target="_blank" style="display:none;">Download Stereo File (Speaker 1 Left, Speaker 2 Right)</a>
        </div>
        <div id="saveOptionsContainer" class="save-options-container">
            <h3>Advanced Save Options</h3>
            <div class="save-option">
                <span class="save-option-label">Speaker 1:</span>
                <select id="speaker1SaveOption">
                    <option value="default">Full track with non speaking parts included</option>
                    <option value="edited">Edited track with muted portions removed</option>
                    <option value="speaker1">Speaker 1 segments only</option>
                    <option value="full_lr">Full track with L/R separated voices</option>
                    <option value="edited_lr">Edited track with muted sections deleted</option>
                </select>
                <button id="saveSpeaker1" class="save-button">
                    <i class="fas fa-download"></i> Save
                </button>
            </div>
            <div id="speaker2SaveOptions" class="save-option">
                <span class="save-option-label">Speaker 2:</span>
                <select id="speaker2SaveOption">
                    <option value="default">Full track with non speaking parts included</option>
                    <option value="edited">Edited track with muted portions removed</option>
                    <option value="speaker1">Speaker 1 segments only</option>
                    <option value="full_lr">Full track with L/R separated voices</option>
                    <option value="edited_lr">Edited track with muted sections deleted</option>
                </select>
                <button id="saveSpeaker2" class="save-button">
                    <i class="fas fa-download"></i> Save
                </button>
            </div>
            <div class="save-option">
                <span class="save-option-label">Stereo Output:</span>
                <select id="stereoSaveOption">
                    <option value="default">Full track with non speaking parts included</option>
                    <option value="edited">Edited track with muted portions removed</option>
                    <option value="speaker1">Speaker 1 segments only</option>
                    <option value="full_lr">Full track with L/R separated voices</option>
                    <option value="edited_lr">Edited track with muted sections deleted</option>
                </select>
                <button id="saveStereo" class="save-button">
                    <i class="fas fa-download"></i> Save
                </button>
            </div>
        </div>
        <form id="regionForm" method="POST" action="process_audio.php" enctype="multipart/form-data" style="display: none;">
            <input type="hidden" name="regions" id="regions">
            <input type="hidden" name="fileName" id="fileName">
            <input type="file" name="audioFile" id="audioFileUpload">
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const statusBar = document.getElementById('statusBar');
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
            const waveformContainer = document.getElementById('waveform-container');
            const waveformWrapper = document.querySelector('.waveform-wrapper');
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
            let originalAudioFile = null;
            const wavesurfer = WaveSurfer.create({
                container: '#waveform',
                waveColor: 'blue',
                progressColor: 'darkblue',
                responsive: true,
                height: 150,
                scrollParent: true,
                minPxPerSec: 50,
                fillParent: true,
                normalize: true,
                splitChannels: true,
                splitChannelsOptions: {
                    channels: [{
                            waveColor: 'blue',
                            progressColor: 'darkblue',
                            height: 65,
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
                        slop: 5
                    })
                ]
            });

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
                statusBar.insertBefore(messageDiv, statusBar.firstChild);
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
                    durationDisplay.textContent = `Duration: ${formatTime(duration)}  `;
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
                originalAudioFile = file;
                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                updateStatus(`Loading: ${file.name} (${fileSizeMB} MB)`, 'info');
                currentFileName = file.name;
                fileNameInput.value = file.name;
                const audioUrl = URL.createObjectURL(file);
                wavesurfer.load(audioUrl);
                lastEndPoint = 0;
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

            function createSpeakerRegion(type) {
                const currentTime = wavesurfer.getCurrentTime();
                const startTime = sequentialRegions.length === 0 ? 0 : lastEndPoint;
                if (currentTime <= startTime) {
                    updateStatus('Please move the playhead forward before creating a region', 'error');
                    return;
                }
                let color, label, regionType;
                if (type === 1) {
                    color = 'rgba(255,165,0,0.3)';
                    label = 'Speaker 1';
                    regionType = 'speaker1';
                } else if (type === 2) {
                    color = 'rgba(0,255,0,0.3)';
                    label = 'Speaker 2';
                    regionType = 'speaker2';
                } else if (type === 'trash') {
                    color = 'rgba(108,117,125,0.3)';
                    label = 'Trash';
                    regionType = 'trash';
                }
                const region = wavesurfer.addRegion({
                    start: startTime,
                    end: currentTime,
                    color: color,
                    drag: false,
                    resize: false,
                    data: {
                        type: regionType
                    }
                });
                const regionObj = {
                    start: startTime,
                    end: currentTime,
                    type: regionType,
                    region: region
                };
                if (regionType === 'trash') {
                    regionsData.trash.push(regionObj);
                } else {
                    regionsData[regionType].push(regionObj);
                }
                sequentialRegions.push(regionObj);
                lastEndPoint = currentTime;
                updateStatus(`${label} region created (${startTime.toFixed(2)} - ${currentTime.toFixed(2)})`, 'success');
                updateRegionsLog();
            }

            wavesurfer.on('ready', () => {
                const audioInfo = wavesurfer.backend.buffer;
                const isStereo = audioInfo.numberOfChannels === 2;
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
                const duration = wavesurfer.getDuration();
                const containerWidth = waveformContainer.clientWidth - 20;
                const pixelsPerSecond = containerWidth / duration;
                wavesurfer.zoom(pixelsPerSecond);
                const wrapper = wavesurfer.drawer.wrapper;
                if (wrapper) {
                    wrapper.addEventListener('scroll', () => requestAnimationFrame(updateDisplays));
                }
                updateDisplays();
                const durationText = wavesurfer.getDuration().toFixed(2);
                updateStatus(`Loaded ${isStereo ? 'stereo' : 'mono'} audio: ${durationText}s`, 'success');
            });

            wavesurfer.on('ready', () => {
                const audioInfo = wavesurfer.backend.buffer;
                if (audioInfo.numberOfChannels === 2) {
                    updateStatus('File is stereo. Converted to mono', 'info');
                    wavesurfer.setOptions({
                        splitChannels: false
                    });
                    wavesurfer.drawBuffer();
                }
            });

            wavesurfer.on('region-created', (region) => {
                for (const r of Object.values(wavesurfer.regions.list)) {
                    if (r.id !== region.id) {
                        const overlap = Math.max(0, Math.min(r.end, region.end) - Math.max(r.start, region.start));
                        if (overlap > 0) {
                            region.remove();
                            updateStatus('Cannot select over existing section', 'error');
                            return;
                        }
                    }
                }
                let regionType;
                if (document.getElementById('speaker1Region').classList.contains('active')) {
                    regionType = 'speaker1';
                } else if (document.getElementById('speaker2Region').classList.contains('active')) {
                    regionType = 'speaker2';
                } else if (document.getElementById('mutedRegion').classList.contains('active')) {
                    regionType = 'trash';
                } else {
                    regionType = 'speaker1';
                }
                let color;
                switch (regionType) {
                    case 'speaker1':
                        color = 'rgba(255,165,0,0.3)';
                        break;
                    case 'speaker2':
                        color = 'rgba(0,255,0,0.3)';
                        break;
                    case 'trash':
                        color = 'rgba(108,117,125,0.3)';
                        break;
                    default:
                        color = 'rgba(255,165,0,0.3)';
                        break;
                }
                region.update({
                    drag: false,
                    resize: false,
                    color: color,
                    data: {
                        type: regionType
                    }
                });
                const regionObj = {
                    start: region.start,
                    end: region.end,
                    type: regionType,
                    region: region
                };
                sequentialRegions.push(regionObj);
                updateRegionsLog();
            });

            ['audioprocess', 'seek', 'zoom', 'interaction'].forEach(event => {
                wavesurfer.on(event, () => requestAnimationFrame(updateDisplays));
            });

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

            btnOpen.addEventListener('click', e => {
                e.preventDefault();
                fileInput.click();
            });
            fileInput.addEventListener('change', e => {
                if (e.target.files && e.target.files[0]) {
                    handleFile(e.target.files[0]);
                }
            });

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

            document.getElementById('jumpBack').addEventListener('click', () => wavesurfer.skip(-0.5));
            document.getElementById('jumpForward').addEventListener('click', () => wavesurfer.skip(0.5));
            document.getElementById('jumpStart').addEventListener('click', () => {
                wavesurfer.seekTo(0);
                updateDisplays();
            });
            document.getElementById('jumpEnd').addEventListener('click', () => {
                const duration = wavesurfer.getDuration();
                const containerWidth = waveformContainer.clientWidth - 20;
                const pixelsPerSecond = containerWidth / duration;
                wavesurfer.zoom(pixelsPerSecond);
                setTimeout(() => {
                    const wrapper = wavesurfer.drawer.wrapper;
                    const scrollWidth = wrapper.scrollWidth;
                    const clientWidth = wrapper.clientWidth;
                    wrapper.scrollLeft = scrollWidth - clientWidth;
                    wavesurfer.seekTo(1);
                    updateDisplays();
                }, 100);
            });

            document.getElementById('zoomIn').addEventListener('click', () => {
                wavesurfer.zoom(wavesurfer.params.minPxPerSec + 10);
            });
            document.getElementById('zoomOut').addEventListener('click', () => {
                wavesurfer.zoom(Math.max(wavesurfer.params.minPxPerSec - 10, 1));
            });
            document.getElementById('zoomFit').addEventListener('click', () => {
                const duration = wavesurfer.getDuration();
                const containerWidth = waveformContainer.clientWidth - 20;
                const pixelsPerSecond = containerWidth / duration;
                wavesurfer.zoom(pixelsPerSecond);
                requestAnimationFrame(() => {
                    wavesurfer.drawer.wrapper.scrollLeft = 0;
                    updateDisplays();
                });
            });
            document.getElementById('btnRestart').addEventListener('click', () => {
                location.href = location.pathname;
            });

            document.getElementById('speaker1Region').addEventListener('click', () => createSpeakerRegion(1));
            document.getElementById('speaker2Region').addEventListener('click', () => createSpeakerRegion(2));
            document.getElementById('mutedRegion').addEventListener('click', () => createSpeakerRegion('trash'));

            document.getElementById('processAudio').addEventListener('click', function() {
                const currentZoom = wavesurfer.params.minPxPerSec;
                const duration = wavesurfer.getDuration();
                const containerWidth = waveformContainer.clientWidth;
                const visibleDuration = containerWidth / currentZoom;
                if (visibleDuration < duration * 0.9) {
                    const confirmProcess = confirm(
                        "The waveform is currently zoomed in and you may not see all regions. " +
                        "Would you like to zoom out to see the entire audio before processing, or proceed anyway?\n\n" +
                        "Click 'OK' to zoom out first, or 'Cancel' to process with current view."
                    );
                    if (confirmProcess) {
                        const fitZoom = (containerWidth / duration) * 0.95;
                        wavesurfer.zoom(fitZoom);
                        wavesurfer.drawer.wrapper.scrollLeft = 0;
                        updateDisplays();
                        return;
                    }
                }
                processAudioRegions();
            });

            function processAudioRegions() {
                if (!originalAudioFile) {
                    updateStatus("Please load an audio file first.", "error");
                    return;
                }
                updateStatus("Processing audio regions...", "info");
                const allRegions = {
                    speaker1: [],
                    speaker2: [],
                    trash: []
                };
                sequentialRegions.forEach(region => {
                    const type = region.type;
                    if (type === 'speaker1' || type === 'speaker2' || type === 'trash') {
                        allRegions[type].push({
                            start: region.start,
                            end: region.end
                        });
                    }
                });
                const formData = new FormData();
                formData.append('regions', JSON.stringify(allRegions));
                formData.append('fileName', currentFileName);
                formData.append('audioFile', originalAudioFile);
                fetch('process_audio.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            updateStatus("Audio processing complete!", "success");
                            const hasSpeaker2Regions = allRegions.speaker2.length > 0;
                            document.getElementById('saveOptionsContainer').style.display = 'block';
                            document.getElementById('speaker2SaveOptions').style.display =
                                hasSpeaker2Regions ? 'flex' : 'none';
                            if (data.speaker1) {
                                speaker1File.href = data.speaker1;
                                speaker1File.download = `${currentFileName}_speaker1.wav`;
                            }
                            if (data.speaker2 && hasSpeaker2Regions) {
                                speaker2File.href = data.speaker2;
                                speaker2File.download = `${currentFileName}_speaker2.wav`;
                            } else {
                                speaker2File.style.display = 'none';
                            }
                            if (data.stereo && hasSpeaker2Regions) {
                                stereoFile.href = data.stereo;
                                stereoFile.style.display = 'block';
                                stereoFile.download = `${currentFileName}_stereo.wav`;
                            } else {
                                stereoFile.style.display = 'none';
                            }
                            processedFiles.style.display = 'block';
                        } else {
                            updateStatus(`Processing error: ${data.message}`, "error");
                        }
                    })
                    .catch(error => {
                        updateStatus(`Error: ${error.message}`, "error");
                        console.error("Processing error:", error);
                    });
            }

            document.getElementById('undoRegion').addEventListener('click', () => {
                if (sequentialRegions.length === 0) {
                    updateStatus('Nothing to undo', 'info');
                    return;
                }
                const lastRegion = sequentialRegions.pop();
                const regionType = lastRegion.type;
                if (regionType === 'speaker1' || regionType === 'speaker2' || regionType === 'trash') {
                    const regionIndex = regionsData[regionType].findIndex(r =>
                        r.start === lastRegion.start && r.end === lastRegion.end);
                    if (regionIndex !== -1) {
                        regionsData[regionType].splice(regionIndex, 1);
                    }
                    if (lastRegion.region) {
                        lastRegion.region.remove();
                    }
                    updateRegionsLog();
                    if (sequentialRegions.length > 0) {
                        lastEndPoint = sequentialRegions[sequentialRegions.length - 1].end;
                    } else {
                        lastEndPoint = 0;
                    }
                    updateStatus(`Removed ${regionType} region`, 'info');
                }
            });

            function updateRegionsLog() {
                const regionsLogElement = document.getElementById('regions-log');
                regionsLogElement.innerHTML = '';
                sequentialRegions.forEach((region, index) => {
                    const regionElement = document.createElement('div');
                    regionElement.className = `region-entry ${region.type}`;
                    regionElement.innerHTML = `<span>${index + 1}: ${region.type} (${region.start.toFixed(2)}s - ${region.end.toFixed(2)}s)</span>`;
                    regionsLogElement.appendChild(regionElement);
                });
            }

            function adjustPositioningForFrame() {
                const inFrame = window !== window.top;
                document.body.style.maxWidth = '768px';
                if (inFrame) {
                    document.body.style.margin = '0 0 0 20px';
                } else {
                    document.body.style.margin = '0 auto';
                }
                document.body.classList.add(inFrame ? 'in-frame' : 'standalone');
            }

            adjustPositioningForFrame();

            function initDragAndDrop() {
                const statusBar = document.getElementById('status-messages');
                if (!statusBar) {
                    console.error('Status bar element not found for drag-and-drop');
                    return;
                }
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
                        const file = e.dataTransfer.files[0];
                        if (file.name.toLowerCase().endsWith('.wav')) {
                            handleFile(file);
                        } else {
                            updateStatus('Please drop a WAV file', 'error');
                        }
                    }
                });
            }
            initDragAndDrop();

            document.getElementById('saveSpeaker1').addEventListener('click', function() {
                const option = document.getElementById('speaker1SaveOption').value;
                saveProcessedAudio('speaker1', option);
            });
            document.getElementById('saveSpeaker2').addEventListener('click', function() {
                const option = document.getElementById('speaker2SaveOption').value;
                saveProcessedAudio('speaker2', option);
            });
            document.getElementById('saveStereo').addEventListener('click', function() {
                const option = document.getElementById('stereoSaveOption').value;
                saveProcessedAudio('stereo', option);
            });

            function saveProcessedAudio(speaker, option) {
                const formData = new FormData();
                formData.append('speaker', speaker);
                formData.append('option', option);
                formData.append('fileName', currentFileName);
                formData.append('regions', JSON.stringify(sequentialRegions));
                if (originalAudioFile) {
                    formData.append('audioFile', originalAudioFile);
                }
                updateStatus(`Processing ${speaker} with option: ${option}...`, "info");
                fetch('process_advanced_audio.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success' && data.file) {
                            const link = document.createElement('a');
                            link.href = data.file;
                            link.download = data.filename || `${currentFileName}_${speaker}_${option}.wav`;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            updateStatus(`${speaker} audio saved successfully!`, "success");
                        } else {
                            updateStatus(`Error saving ${speaker}: ${data.message}`, "error");
                        }
                    })
                    .catch(error => {
                        updateStatus(`Error: ${error.message}`, "error");
                        console.error("Processing error:", error);
                    });
            }

            document.addEventListener('keydown', (e) => {
                if ((e.key === '1' || e.key === '2') && wavesurfer) {
                    const recentRegion = Object.values(wavesurfer.regions.list).pop();
                    if (!recentRegion) return;
                    let startPoint = lastEndPoint || 0;
                    if (sequentialRegions.length === 0) {
                        startPoint = 0;
                    }
                    if (sequentialRegions.length === 0 && wavesurfer.getCurrentTime() <= startPoint) {
                        recentRegion.remove();
                        updateStatus('Invalid selection', 'error');
                        return;
                    }
                    recentRegion.update({
                        start: startPoint,
                        end: wavesurfer.getCurrentTime()
                    });
                    sequentialRegions.push({
                        start: startPoint,
                        end: wavesurfer.getCurrentTime(),
                        type: 'muted',
                        region: recentRegion
                    });
                    lastEndPoint = wavesurfer.getCurrentTime();
                    updateStatus(`Created muted section (${startPoint.toFixed(2)}s - ${lastEndPoint.toFixed(2)}s)`, 'success');
                }
            });

            let selectedRegionType = 'speaker1';
            document.getElementById('speaker1Region').addEventListener('click', () => {
                selectedRegionType = 'speaker1';
                createRegionAtCursor();
            });
            document.getElementById('speaker2Region').addEventListener('click', () => {
                selectedRegionType = 'speaker2';
                createRegionAtCursor();
            });
            document.getElementById('mutedRegion').addEventListener('click', () => {
                selectedRegionType = 'trash';
                createRegionAtCursor();
            });

            function createRegionAtCursor() {
                const currentTime = wavesurfer.getCurrentTime();
                const startTime = sequentialRegions.length === 0 ? 0 : lastEndPoint;
                if (currentTime <= startTime) {
                    updateStatus('Please move the playhead forward before creating a region', 'error');
                    return;
                }
                for (const r of Object.values(wavesurfer.regions.list)) {
                    if (currentTime > r.start && currentTime < r.end) {
                        updateStatus('Cannot create region over existing region', 'error');
                        return;
                    }
                }
                let color;
                switch (selectedRegionType) {
                    case 'speaker1':
                        color = 'rgba(247, 124, 8, 0.3)';
                        break;
                    case 'speaker2':
                        color = 'rgba(0,255,0,0.3)';
                        break;
                    case 'trash':
                        color = 'rgba(108,117,125,0.3)';
                        break;
                    default:
                        color = 'rgba(255,165,0,0.3)';
                        break;
                }
                const region = wavesurfer.addRegion({
                    start: startTime,
                    end: currentTime,
                    color: color,
                    drag: false,
                    resize: false,
                    data: {
                        type: selectedRegionType
                    }
                });
                const regionObj = {
                    start: startTime,
                    end: currentTime,
                    type: selectedRegionType,
                    region: region
                };
                if (selectedRegionType === 'trash') {
                    regionsData.trash.push(regionObj);
                } else {
                    regionsData[selectedRegionType].push(regionObj);
                }
                sequentialRegions.push(regionObj);
                lastEndPoint = currentTime;
                updateStatus(`${selectedRegionType} region created (${startTime.toFixed(2)}s - ${currentTime.toFixed(2)}s)`, 'success');
                updateRegionsLog();
            }
        });
    </script>
</body>

</html>
