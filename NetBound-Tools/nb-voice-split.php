<?php
// Debug and header management
ob_start(function ($buffer) {
    if (strlen($buffer) === 0) {
        $error = "Empty output buffer detected\n";
        $error .= "Headers sent: " . (headers_sent() ? "Yes" : "No") . "\n";
        if (headers_sent($file, $line)) {
            $error .= "Headers sent in $file on line $line\n";
        }
        $error .= "PHP memory usage: " . memory_get_usage(true) / 1024 / 1024 . " MB\n";
        $error .= "Peak memory usage: " . memory_get_peak_usage(true) / 1024 / 1024 . " MB\n";
        $error .= "Error reporting level: " . error_reporting() . "\n";
        $error .= "Display errors: " . ini_get('display_errors') . "\n";

        error_log($error);

        echo "<div style='color:red; position:fixed; top:0; left:0; padding:10px; background:white; z-index:9999; white-space:pre;'>";
        echo htmlspecialchars($error);
        echo "</div>";
    }
    return $buffer;
});

// Check for UTF-8 BOM and other output issues
foreach ([__FILE__, 'nb-voice-split.css', 'process_audio.php', 'process_advanced_audio.php'] as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            error_log("UTF-8 BOM detected in $file");
            $content = substr($content, 3);
            file_put_contents($file, $content);
        }
    }
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null) {
        error_log("Fatal error: " . print_r($error, true));
        if (ini_get('display_errors')) {
            echo "<div style='color:red; position:fixed; top:0; left:0; padding:10px; background:white; z-index:9999; white-space:pre;'>";
            echo "Fatal error: " . htmlspecialchars(print_r($error, true));
            echo "</div>";
        }
    }
});

// Helper function to convert PHP ini settings to bytes
function return_bytes($val)
{
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    switch ($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

/**
 * NetBound Tools: Speaker Audio Splitter
 * Version: 1.4
 * Created by: NetBound Team
 *
 * DEPENDENCIES:
 * - Frontend:
 *   - WaveSurfer.js v6.6.4 (https://unpkg.com/wavesurfer.js@6.6.4)
 *   - WaveSurfer Regions Plugin (https://unpkg.com/wavesurfer.js@6.6.4/dist/plugin/wavesurfer.regions.min.js)
 *   - Font Awesome 6.4.0 (https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css)
 *   - nb-voice-split.css
 *
 * - Backend:
 *   - process_audio.php - Handles server-side audio processing
 *   - PHP 7.0+ with file handling capabilities
 *
 * DESCRIPTION:
 * This tool provides a browser-based interface for splitting audio recordings into
 * separate tracks based on speaker segments. Users can mark regions for Speaker 1,
 * Speaker 2, or mark sections as trash. The tool then processes these regions and
 * generates separate audio files for each speaker.
 *
 * FEATURES:
 * - Audio visualization with waveform display
 * - Speaker region marking and management
 * - Audio playback controls with zoom functionality
 * - Drag and drop file upload
 * - Responsive design for desktop and mobile use
 */

// Set higher PHP limits for audio processing - increased to handle larger files
// Set PHP error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set higher PHP limits for audio processing
ini_set('memory_limit', '1024M');  // Increased to handle larger files
ini_set('max_execution_time', '900');  // Increased for longer processing
ini_set('max_input_time', '900');  // Increased for longer uploads
ini_set('upload_max_filesize', '500M');  // Set to 500M
ini_set('post_max_size', '500M');  // Set to 500M
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetBound Tools: Audio Splitter</title>
    <link rel="stylesheet" href="nb-voice-split.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://unpkg.com/wavesurfer.js@6.6.4"></script>
    <script src="https://unpkg.com/wavesurfer.js@6.6.4/dist/plugin/wavesurfer.regions.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
                    <button id="undoRegion" class="button-blue" title="Undo Last Region" style="margin-right: 40px;">
                        <i class="fas fa-undo"></i>
                    </button>
                    <button id="processAudio" class="button-blue" title="Process Audio">
                        Process Audio
                    </button>
                </div>
            </div>
        </div>
        <!-- Process and Save buttons section -->

        <!-- Log section -->
        <div id="regions-log" class="regions-log">
            <!-- Add timeline visual here -->
            <div id="timeline-container" class="timeline-container"></div>
        </div>
        <!-- Advanced Save Options -->
        <div id="saveOptionsContainer" class="save-options-container" style="display: none;">
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
        <form id="regionForm" method="POST" action="process_audio.php" enctype="multipart/form-data" style="display: none;">
            <input type="hidden" name="regions" id="regions">
            <input type="hidden" name="fileName" id="fileName">
            <input type="file" name="audioFile" id="audioFileUpload">
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize elements
            const statusMessages = document.getElementById('status-messages');
            const btnOpen = document.getElementById('btnOpen');
            const fileInput = document.getElementById('fileInput');
            const regionsInput = document.getElementById('regions');
            const fileNameInput = document.getElementById('fileName');
            const durationDisplay = document.getElementById('duration-display');
            const windowDisplay = document.getElementById('window-display');
            const playPauseButton = document.getElementById('playPause');
            const playPauseIcon = playPauseButton.querySelector('i.fas');
            const waveformContainer = document.getElementById('waveform-container');
            const waveformWrapper = document.querySelector('.waveform-wrapper');
            // Initialize data
            let currentFileName = '',
                lastEndPoint = 0,
                lastRegion = null,
                isPlaying = false,
                selectedRegionType = 'speaker1'; // Default region type
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
                        // dragSelection: false, // Remove dragSelection
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
                // Handle newlines in messages by converting them to <br> tags
                messageDiv.innerHTML = message.replace(/\n/g, '<br>');

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

                    durationDisplay.textContent = `Duration: ${formatTime(duration)}  `;
                    windowDisplay.textContent = `Viewable: ${formatTime(startTime)} - ${formatTime(endTime)}`;
                } catch (err) {
                    console.error('Display update error:', err);
                }
            }
            // Add PHP server limits detection - update to higher values
            const phpLimits = {
                maxUploadSize: <?php
                                $upload_max_filesize = ini_get('upload_max_filesize');
                                $post_max_size = ini_get('post_max_size');
                                $upload_max_filesize = is_numeric(return_bytes($upload_max_filesize)) ? $upload_max_filesize : '100M';
                                $post_max_size = is_numeric(return_bytes($post_max_size)) ? $post_max_size : '100M';
                                echo min(
                                    return_bytes($upload_max_filesize),
                                    return_bytes($post_max_size)
                                ); ?>,
                maxUploadSizeMB: <?php
                                    $upload_max_filesize = ini_get('upload_max_filesize');
                                    $post_max_size = ini_get('post_max_size');
                                    $upload_max_filesize = is_numeric(return_bytes($upload_max_filesize)) ? $upload_max_filesize : '100M';
                                    $post_max_size = is_numeric(return_bytes($post_max_size)) ? $post_max_size : '100M';
                                    echo min(
                                        return_bytes($upload_max_filesize),
                                        return_bytes($post_max_size)
                                    ) / (1024 * 1024); ?>,
                // Add explicit check for 2MB restriction
                has2MBRestriction: <?php
                                    $upload_max_filesize = ini_get('upload_max_filesize');
                                    $post_max_size = ini_get('post_max_size');
                                    $upload_max_filesize = is_numeric(return_bytes($upload_max_filesize)) ? $upload_max_filesize : '100M';
                                    $post_max_size = is_numeric(return_bytes($post_max_size)) ? $post_max_size : '100M';
                                    echo (min(
                                        return_bytes($upload_max_filesize),
                                        return_bytes($post_max_size)
                                    ) / (1024 * 1024)) <= 2 ? 'true' : 'false'; ?>
            };

            function handleFile(file) {
                if (!file || !(file.name.toLowerCase().endsWith('.wav'))) {
                    updateStatus('Please select a valid WAV file', 'error');
                    return;
                }

                // Check file size against PHP limits
                if (file.size > phpLimits.maxUploadSize) {
                    let errorMsg;

                    // Check if we have the 2MB restriction
                    if (phpLimits.has2MBRestriction || phpLimits.maxUploadSizeMB <= 2) {
                        errorMsg = `The file is too large (${(file.size / (1024 * 1024)).toFixed(2)} MB). ` +
                            `Your PHP configuration is limiting uploads to 2MB.\n\n` +
                            `To fix this, these lines need to be added to php.ini ` +
                            `(usually at /etc/php/X.X/apache2/php.ini or /etc/php.ini):\n\n` +
                            `upload_max_filesize = 500M\n` +
                            `post_max_size = 500M\n\n` +
                            `After adding these lines, restart Apache/PHP-FPM.`;
                    } else {
                        errorMsg = `The file is too large (${(file.size / (1024 * 1024)).toFixed(2)} MB). ` +
                            `Server currently allows up to ${phpLimits.maxUploadSizeMB.toFixed(2)} MB uploads.`;
                    }

                    // Add debugging info
                    errorMsg += `\n\nDebug info: upload_max_filesize=${phpLimits.maxUploadSizeMB}MB, ` +
                        `post_max_size=${phpLimits.maxUploadSizeMB}MB`;

                    updateStatus(errorMsg, 'error');

                    // Create a more visible error message
                    const errorEl = document.createElement('div');
                    errorEl.className = 'error-message';
                    errorEl.innerHTML = `<b>Error:</b> ${errorMsg.replace(/\n/g, '<br>')}`;
                    document.getElementById('regions-log').innerHTML = '';
                    document.getElementById('regions-log').appendChild(errorEl);
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

            // Add event listeners for speaker region buttons
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

            // Prevent overlapping selections; if region-created intersects existing, remove it
            wavesurfer.on('region-created', (region) => {
                // Check for overlapping regions
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
            });

            // Handle region updates
            wavesurfer.on('region-update-end', (region) => {
                if (region) {
                    region.update({
                        drag: false,
                        resize: false,
                        data: {
                            type: selectedRegionType
                        }
                    });
                }
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
                    resetAudioState();
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

                    // Set the cursor position to the end (use seekTo(1) to go to end)
                    wavesurfer.seekTo(1);
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
                // Calculate proper zoom level based on audio duration
                const duration = wavesurfer.getDuration();
                const containerWidth = waveformContainer.clientWidth - 20;
                const pixelsPerSecond = containerWidth / duration;

                // Apply calculated zoom level
                wavesurfer.zoom(pixelsPerSecond);

                // Reset scroll position
                requestAnimationFrame(() => {
                    wavesurfer.drawer.wrapper.scrollLeft = 0;
                    updateDisplays();
                });
            });

            document.getElementById('btnRestart').addEventListener('click', () => {
                location.href = location.pathname;
            });

            // Fix the processing function to handle zoom state properly
            document.getElementById('processAudio').addEventListener('click', function() {
                // Check if we need to zoom out first
                const currentZoom = wavesurfer.params.minPxPerSec;
                const duration = wavesurfer.getDuration();
                const containerWidth = waveformContainer.clientWidth;
                const visibleDuration = containerWidth / currentZoom;

                // More generous threshold - if at least 90% is visible, consider it zoomed out enough
                if (visibleDuration < duration * 0.9) {
                    const confirmProcess = confirm(
                        "The waveform is currently zoomed in and you may not see all regions. " +
                        "Would you like to zoom out to see the entire audio before processing, or proceed anyway?\n\n" +
                        "Click 'OK' to zoom out first, or 'Cancel' to process with current view."
                    );

                    if (confirmProcess) {
                        // Zoom out to fit the entire audio - with a slight adjustment factor
                        const fitZoom = (containerWidth / duration) * 0.95; // Slightly less than full width
                        wavesurfer.zoom(fitZoom);
                        wavesurfer.drawer.wrapper.scrollLeft = 0;
                        updateDisplays();
                        return; // Don't process yet, let the user see the full waveform first
                    }
                    // Otherwise continue with processing
                }

                // Process the audio
                processAudioRegions();
            });

            // Revised processing function to ensure all regions are included
            function processAudioRegions() {
                // Check if we have an audio file loaded
                if (!originalAudioFile) {
                    updateStatus("Please load an audio file first.", "error");
                    return;
                }

                // Update status
                updateStatus("Processing audio regions...", "info");

                // Collect all regions from sequentialRegions array instead of wavesurfer.regions.list
                const allRegions = {
                    speaker1: [],
                    speaker2: [],
                    trash: []
                };

                // Use the sequentialRegions array which has all regions regardless of view
                sequentialRegions.forEach(region => {
                    const type = region.type;
                    if (type === 'speaker1' || type === 'speaker2' || type === 'trash') {
                        allRegions[type].push({
                            start: region.start,
                            end: region.end
                        });
                    }
                });

                // Clean and validate regions data before sending
                const cleanedRegions = {
                    speaker1: allRegions.speaker1.map(r => ({
                        start: parseFloat(r.start.toFixed(3)),
                        end: parseFloat(r.end.toFixed(3))
                    })),
                    speaker2: allRegions.speaker2.map(r => ({
                        start: parseFloat(r.start.toFixed(3)),
                        end: parseFloat(r.end.toFixed(3))
                    })),
                    trash: allRegions.trash.map(r => ({
                        start: parseFloat(r.start.toFixed(3)),
                        end: parseFloat(r.end.toFixed(3))
                    }))
                };

                // Create form data for submission
                const formData = new FormData();
                formData.append('regions', JSON.stringify(cleanedRegions));
                formData.append('fileName', currentFileName);
                formData.append('audioFile', originalAudioFile);

                updateStatus("Starting audio processing...", "info");

                // Create a debugging function to help diagnose server response issues
                function createDebugLink(text, debugData) {
                    const debugEl = document.createElement('div');
                    debugEl.classList.add('debug-info');

                    // Check for PHP content-length error pattern
                    if (debugData && debugData.includes("Content-Length") && debugData.includes("exceeds the limit")) {
                        const match = debugData.match(/(\d+) bytes exceeds the limit of (\d+) bytes/);
                        if (match) {
                            const uploadedSize = parseInt(match[1]) / (1024 * 1024);
                            const limitSize = parseInt(match[2]) / (1024 * 1024);

                            debugEl.innerHTML = `<div class="error-message">
                                <h4>File Upload Error</h4>
                                <p>The file you're trying to upload (${uploadedSize.toFixed(2)} MB) is larger than
                                the server allows (${limitSize.toFixed(2)} MB).</p>
                                <p>Solution: Ask your server administrator to increase the PHP limits in php.ini:</p>
                                <ul>
                                    <li>post_max_size (currently ${limitSize.toFixed(2)} MB)</li>
                                    <li>upload_max_filesize</li>
                                </ul>
                            </div>`;
                            document.getElementById('regions-log').innerHTML = '';
                            document.getElementById('regions-log').appendChild(debugEl);
                            return;
                        }
                    }

                    // Default debug display
                    const debugContent = document.createElement('pre');
                    debugContent.textContent = debugData;
                    debugContent.style.whiteSpace = 'pre-wrap';
                    debugContent.style.maxHeight = '200px';
                    debugContent.style.overflow = 'auto';
                    debugEl.appendChild(debugContent);

                    // Create a collapsible section
                    const toggle = document.createElement('button');
                    toggle.textContent = 'Show/Hide Debug Info';
                    toggle.onclick = () => {
                        debugContent.style.display = debugContent.style.display === 'none' ? 'block' : 'none';
                    };

                    document.getElementById('regions-log').appendChild(toggle);
                    document.getElementById('regions-log').appendChild(debugEl);
                }

                // Revised processing function with better error handling
                fetch('process_audio.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        // Store the original response for debugging
                        return response.text().then(text => {
                            // Try to parse as JSON
                            try {
                                // Check for the PHP error pattern at the beginning
                                if (text.trim().startsWith('<br') || text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                                    throw new Error('Server returned HTML instead of JSON');
                                }

                                // Attempt to parse as JSON
                                return JSON.parse(text);
                            } catch (e) {
                                // If parsing fails, show the raw response for debugging
                                updateStatus('Server returned invalid JSON. See debug info below.', 'error');
                                createDebugLink('Server Response', text.substring(0, 1000));
                                throw new Error(`Failed to parse server response: ${e.message}`);
                            }
                        });
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            updateStatus("Audio processing complete!", "success");

                            // Check if we have any speaker2 regions
                            const hasVoice2Regions = allRegions.speaker2.length > 0;

                            // Show advanced save options
                            document.getElementById('saveOptionsContainer').style.display = 'block';

                            // Show/hide Voice 2 options based on regions
                            document.getElementById('speaker2SaveOptions').style.display =
                                hasVoice2Regions ? 'flex' : 'none';

                            // Show a success message encouraging users to use the advanced save options
                            updateStatus("Please use the Advanced Save Options below to download your files", "info");
                        } else {
                            updateStatus(`Processing error: ${data.message}`, "error");
                        }
                    })
                    .catch(error => {
                        updateStatus(`Error: ${error.message}`, "error");
                        console.error("Processing error:", error);
                    });
            }

            // Add undo region functionality
            document.getElementById('undoRegion').addEventListener('click', () => {
                // Make sure we have regions to undo
                if (sequentialRegions.length === 0) {
                    updateStatus('Nothing to undo', 'info');
                    return;
                }

                // Get the last region
                const lastRegion = sequentialRegions.pop();

                // Remove from the appropriate category in regionsData
                const regionType = lastRegion.type;
                if (regionType === 'speaker1' || regionType === 'speaker2' || regionType === 'trash') {
                    // Find and remove the region from the appropriate array
                    const regionIndex = regionsData[regionType].findIndex(r =>
                        r.start === lastRegion.start && r.end === lastRegion.end);

                    if (regionIndex !== -1) {
                        regionsData[regionType].splice(regionIndex, 1);
                    }
                }

                // Remove the visual region from the waveform
                if (lastRegion.region) {
                    lastRegion.region.remove();
                }

                // Update the regions log
                updateRegionsLog();

                // Set the last end point to the previous region's end, or 0
                if (sequentialRegions.length > 0) {
                    lastEndPoint = sequentialRegions[sequentialRegions.length - 1].end;
                } else {
                    lastEndPoint = 0;
                }

                updateStatus(`Removed ${regionType} region`, 'info');
            });

            // Helper function to update the regions log - fixing the Node.appendChild error
            function updateRegionsLog() {
                const regionsLogElement = document.getElementById('regions-log');

                // First make sure the regions-log element exists
                if (!regionsLogElement) {
                    console.error("Regions log element not found");
                    return;
                }

                // Clear the contents
                regionsLogElement.innerHTML = '';

                // Create a fresh timeline container
                const timeline = document.createElement('div');
                timeline.id = 'timeline-container';
                timeline.className = 'timeline-container';

                // Add the timeline to the regions log
                regionsLogElement.appendChild(timeline);

                // Calculate total duration for timeline
                let totalDuration = 0;
                if (wavesurfer && wavesurfer.getDuration) {
                    totalDuration = wavesurfer.getDuration();
                } else if (sequentialRegions.length > 0) {
                    // Find the last end point among all regions
                    totalDuration = Math.max(...sequentialRegions.map(region => region.end));
                }

                if (totalDuration > 0) {
                    // Create and add segments to the timeline
                    sequentialRegions.forEach(region => {
                        const segment = document.createElement('div');
                        segment.className = `timeline-segment ${region.type}`;
                        const startPercent = (region.start / totalDuration) * 100;
                        const widthPercent = ((region.end - region.start) / totalDuration) * 100;
                        segment.style.left = `${startPercent}%`;
                        segment.style.width = `${widthPercent}%`;
                        timeline.appendChild(segment);
                    });
                }

                // Create region entries below timeline
                sequentialRegions.forEach((region, index) => {
                    // Convert speaker1/speaker2 to voice1/voice2 for display
                    let displayType = region.type;
                    if (displayType === 'speaker1') displayType = 'voice1';
                    if (displayType === 'speaker2') displayType = 'voice2';
                    if (displayType === 'trash') displayType = 'muted';

                    const regionElement = document.createElement('div');
                    regionElement.className = `region-entry ${region.type}`;
                    regionElement.innerHTML = `<span>${index + 1}: ${displayType} (${region.start.toFixed(2)}s - ${region.end.toFixed(2)}s)</span>`;

                    // Add click handler to jump to this region
                    regionElement.addEventListener('click', () => {
                        wavesurfer.seekTo(region.start / wavesurfer.getDuration());
                    });

                    regionsLogElement.appendChild(regionElement);
                });
            }

            // Add this to detect iframe and adjust positioning
            function adjustPositioningForFrame() {
                // Check if we're in an iframe
                const inFrame = window !== window.top;

                // Apply appropriate styles - maintain same width but adjust margins
                document.body.style.maxWidth = '768px'; // Same width in both cases

                if (inFrame) {
                    document.body.style.margin = '0 0 0 20px'; // In iframe: left-justified with 20px margin
                } else {
                    document.body.style.margin = '0 auto'; // Not in iframe: centered
                }

                // Add a class to body for additional CSS targeting
                document.body.classList.add(inFrame ? 'in-frame' : 'standalone');
            }

            // Call this function to adjust layout when the page loads
            adjustPositioningForFrame();

            // Add after your DOMContentLoaded event listener setup:
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

            initDragAndDrop(); // Call this function to initialize drag and drop

            // Add these event listeners after your DOMContentLoaded setup
            const speakers = ['speaker1', 'speaker2', 'stereo'];
            speakers.forEach(speaker => {
                document.getElementById(`save${speaker.charAt(0).toUpperCase() + speaker.slice(1)}`).addEventListener('click', function() {
                    const option = document.getElementById(`${speaker}SaveOption`).value;
                    saveProcessedAudio(speaker, option);
                });
            });

            // Function to handle advanced save options
            function saveProcessedAudio(speaker, option) {
                // Create form data for submission
                const formData = new FormData();
                formData.append('speaker', speaker);
                formData.append('option', option);
                formData.append('fileName', currentFileName);
                formData.append('regions', JSON.stringify(sequentialRegions));

                // For processing, you'll need the original audio file
                if (originalAudioFile) {
                    formData.append('audioFile', originalAudioFile);
                }

                updateStatus(`Processing ${speaker} with option: ${option}...`, "info");

                fetch('process_advanced_audio.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        // Store the original response for debugging
                        return response.text().then(text => {
                            // Try to parse as JSON
                            try {
                                // Check for the PHP error pattern at the beginning
                                if (text.trim().startsWith('<br') || text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                                    throw new Error('Server returned HTML instead of JSON');
                                }

                                // Attempt to parse as JSON
                                return JSON.parse(text);
                            } catch (e) {
                                // If parsing fails, show the raw response for debugging
                                updateStatus('Server returned invalid JSON. See debug info below.', 'error');
                                createDebugLink('Server Response', text.substring(0, 1000));
                                throw new Error(`Failed to parse server response: ${e.message}`);
                            }
                        });
                    })
                    .then(data => {
                        // ...existing code...
                    })
                    .catch(error => {
                        updateStatus(`Error: ${error.message}`, "error");
                        console.error("Processing error:", error);
                    });
            }

            // Handle keydown events for region shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.key === '1' || e.key === '2') {
                    selectedRegionType = e.key === '1' ? 'speaker1' : 'speaker2';
                    createRegionAtCursor();
                } else if (e.key === 'm') {
                    selectedRegionType = 'trash';
                    createRegionAtCursor();
                }
            });

            // Helper function to get region color based on type
            function getRegionColor(type) {
                switch (type) {
                    case 'speaker1':
                        return 'rgba(247, 124, 8, 0.3)';
                    case 'speaker2':
                        return 'rgba(0,255,0,0.3)';
                    case 'trash':
                        return 'rgba(108,117,125,0.3)';
                    default:
                        return 'rgba(255,165,0,0.3)';
                }
            }

            // Helper function to create region metadata
            function createRegionMetadata(startTime, currentTime) {
                const region = wavesurfer.addRegion({
                    start: startTime,
                    end: currentTime,
                    color: getRegionColor(selectedRegionType),
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

                // Track region in our arrays
                if (selectedRegionType === 'trash') {
                    regionsData.trash.push(regionObj);
                } else {
                    regionsData[selectedRegionType].push(regionObj);
                }

                sequentialRegions.push(regionObj);
                return regionObj;
            }

            // Function to create a region at the current cursor position
            function createRegionAtCursor() {
                const currentTime = wavesurfer.getCurrentTime();
                const startTime = sequentialRegions.length === 0 ? 0 : lastEndPoint;

                // Validate region creation
                if (!validateRegionCreation(currentTime, startTime)) {
                    return;
                }

                createRegionMetadata(startTime, currentTime);
                lastEndPoint = currentTime;
                updateStatus(`${selectedRegionType} region created (${startTime.toFixed(2)}s - ${currentTime.toFixed(2)}s)`, 'success');
                updateRegionsLog();
            }

            // Validation helper for region creation
            function validateRegionCreation(currentTime, startTime) {
                if (currentTime <= startTime) {
                    updateStatus('Please move the playhead forward before creating a region', 'error');
                    return false;
                }

                // Check for overlapping regions
                for (const r of Object.values(wavesurfer.regions.list)) {
                    if (currentTime > r.start && currentTime < r.end) {
                        updateStatus('Cannot create region over existing region', 'error');
                        return false;
                    }
                }

                return true;
            }

            // Add this function to indicate currently playing segment in the timeline
            wavesurfer.on('audioprocess', () => {
                const currentTime = wavesurfer.getCurrentTime();

                // Update visual indication of current position on timeline
                const timelineSegments = document.querySelectorAll('.timeline-segment');
                timelineSegments.forEach(segment => {
                    segment.classList.remove('current');
                });

                // Find the current segment being played
                const currentSegment = sequentialRegions.find(region =>
                    currentTime >= region.start && currentTime <= region.end);

                if (currentSegment && currentSegment.region) {
                    // Find the corresponding timeline segment and highlight it
                    const totalDuration = wavesurfer.getDuration();
                    const currentStartPercent = (currentSegment.start / totalDuration) * 100;

                    // Find the segment at this position
                    const matchingSegment = Array.from(timelineSegments).find(segment =>
                        parseFloat(segment.style.left) === currentStartPercent);

                    if (matchingSegment) {
                        matchingSegment.classList.add('current');
                    }
                }
            });

            // Enhance resetAudioState function to clear timeline as well
            function resetAudioState() {
                wavesurfer.clearRegions();
                regionsData = {
                    speaker1: [],
                    speaker2: [],
                    trash: []
                };
                sequentialRegions = [];
                lastEndPoint = 0;
                updateRegionsLog();
            }

            // Initialize the timeline on load
            updateRegionsLog();
        });
    </script>
    <style>
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
    </style>
</body>

</html>
