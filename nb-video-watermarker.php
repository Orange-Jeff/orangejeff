<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>NetBound Tools: Video Watermarker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="shared-styles.css">
</head>

<body>
    <div class="menu-container">
        <div class="title-container">
            <h1 class="editor-title">NetBound Tools: Video Watermarker</h1>
            <a href="main.php?app=tool-template.php" class="hamburger-menu" title="Go to Main Menu">
                <i class="fas fa-bars"></i>
            </a>
        </div>
        <div id="statusBox" class="status-box"></div>
        <div class="button-controls">
            <div class="button-row">
                <button class="command-button" id="btnOpenVideo">
                    <i class="fas fa-folder-open"></i> Open Video
                </button>
                <button class="command-button" id="btnSelectWatermark">
                    <i class="fas fa-image"></i> Add Watermark
                </button>
                <button class="command-button" id="btnRestart">
                    <i class="fas fa-redo"></i> Restart
                </button>
            </div>
            <div class="progress-container" id="progressContainer">
                <div class="progress-bar" id="progressBar"></div>
                <div class="progress-finalizing" id="progressFinalizing"></div>
                <div class="progress-stats">
                    <span class="progress-percentage" id="progressPercentage">0%</span>
                    <span class="progress-frames" id="progressFrames">0/0 frames</span>
                    <span class="progress-time" id="progressTime">--:--</span>
                    <button class="cancel-processing" id="btnCancelProcessing">Cancel</button>
                </div>
            </div>
        </div>
        <div class="editor-view">
            <div class="video-container" id="video-preview">
                <video id="videoElement" controls></video>
                <canvas id="watermarkOverlay"></canvas>
            </div>
            <div class="filename-control">
                <input type="text" id="filename" class="filename-input" placeholder="No file selected">
                <button class="command-button" id="btnRename">
                    <i class="fas fa-edit"></i> Rename
                </button>
            </div>

            <div class="watermark-section">
                <div class="watermark-header">
                    <div class="watermark-position-controls" id="positionControls" style="display: none;">
                        <button class="position-btn" id="btnMoveUp" title="Move Up"><i class="fas fa-arrow-up"></i></button>
                        <button class="position-btn" id="btnMoveLeft" title="Move Left"><i class="fas fa-arrow-left"></i></button>
                        <button class="position-btn" id="btnMoveRight" title="Move Right"><i class="fas fa-arrow-right"></i></button>
                        <button class="position-btn" id="btnMoveDown" title="Move Down"><i class="fas fa-arrow-down"></i></button>
                    </div>
                </div>

                <div id="watermarkControls" style="display: none;">
                    <div class="control-row">
                        <div class="control-group">
                            <label class="control-label" for="positionSelect">Position</label>
                            <select id="positionSelect" class="filename-input">
                                <option value="top-left">Top Left</option>
                                <option value="top-right">Top Right</option>
                                <option value="bottom-left">Bottom Left</option>
                                <option value="bottom-right" selected>Bottom Right</option>
                                <option value="center">Center</option>
                                <option value="custom">Custom Position</option>
                            </select>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="sizeSlider">Size (%)</label>
                            <input type="range" id="sizeSlider" min="5" max="50" value="20" class="slider">
                            <output for="sizeSlider" id="sizeValue">20%</output>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="opacitySlider">Opacity</label>
                            <input type="range" id="opacitySlider" min="10" max="100" value="80" class="slider">
                            <output for="opacitySlider" id="opacityValue">80%</output>
                        </div>
                    </div>
                </div>

                <div id="watermarkGallery" class="compact-gallery" style="display: none;">
                    <div class="watermark-options" id="watermarkOptions">
                        <!-- Watermark options will be populated here -->
                        <div class="watermark-option add-new">
                            <div class="watermark-preview">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="watermark-label">Add New</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bottom-action-bar">
                <div class="button-row">
                    <button class="command-button" id="btnProcessVideo" disabled>
                        <i class="fas fa-film"></i> Process Video
                    </button>
                    <button class="command-button" id="btnDownload" disabled>
                        <i class="fas fa-download"></i> Save Video
                    </button>
                    <button class="command-button" id="btnCancelProcessingBottom">
                        <i class="fas fa-times"></i> Abort
                    </button>
                </div>
            </div>
        </div>
    </div>
    <input type="file" id="videoInput" accept="video/*" style="display: none">
    <input type="file" id="watermarkInput" accept="image/*" style="display: none">

    <script>
        function updateStatus(message, type = 'info') {
            const container = document.getElementById('statusBox');
            const id = 'msg_' + Math.random().toString(36).substr(2, 9);

            const messageDiv = document.createElement('div');
            messageDiv.id = id;
            messageDiv.className = `message ${type} latest`;
            messageDiv.textContent = message;

            // Remove latest class from all messages
            document.querySelectorAll('.message.latest').forEach(msg => {
                msg.classList.remove('latest');
            });

            // Insert at top
            container.insertBefore(messageDiv, container.firstChild);
            return id;
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Element references
            const videoInput = document.getElementById('videoInput');
            const watermarkInput = document.getElementById('watermarkInput');
            const btnOpenVideo = document.getElementById('btnOpenVideo');
            const btnSelectWatermark = document.getElementById('btnSelectWatermark');
            const btnRename = document.getElementById('btnRename');
            const btnRestart = document.getElementById('btnRestart');
            const btnProcessVideo = document.getElementById('btnProcessVideo');
            const btnDownload = document.getElementById('btnDownload');
            const statusBox = document.getElementById('statusBox');
            const filename = document.getElementById('filename');
            const videoElement = document.getElementById('videoElement');
            const watermarkOverlay = document.getElementById('watermarkOverlay');
            const progressContainer = document.getElementById('progressContainer');
            const progressBar = document.getElementById('progressBar');
            const progressFinalizing = document.getElementById('progressFinalizing');
            const progressPercentage = document.getElementById('progressPercentage');
            const progressFrames = document.getElementById('progressFrames');
            const progressTime = document.getElementById('progressTime');
            const watermarkControls = document.getElementById('watermarkControls');
            const watermarkGallery = document.getElementById('watermarkGallery');
            const watermarkOptions = document.getElementById('watermarkOptions');
            const positionSelect = document.getElementById('positionSelect');
            const sizeSlider = document.getElementById('sizeSlider');
            const sizeValue = document.getElementById('sizeValue');
            const opacitySlider = document.getElementById('opacitySlider');
            const opacityValue = document.getElementById('opacityValue');
            const positionControls = document.getElementById('positionControls');
            const btnMoveUp = document.getElementById('btnMoveUp');
            const btnMoveDown = document.getElementById('btnMoveDown');
            const btnMoveLeft = document.getElementById('btnMoveLeft');
            const btnMoveRight = document.getElementById('btnMoveRight');

            // State variables
            let currentVideo = null;
            let currentWatermark = null;
            let processedVideo = null;
            let watermarkList = [];
            let originalFilename = '';
            let watermarkPosition = 'bottom-right';
            let watermarkSize = 20;
            let watermarkOpacity = 0.8;
            let hasBeenSaved = false;
            let isProcessingCancelled = false;
            let ctx = watermarkOverlay.getContext('2d');
            let finalizingStarted = false;
            let customPositionX = 0; // Store custom X position
            let customPositionY = 0; // Store custom Y position
            let positionStep = 5; // Pixels to move per arrow click

            // Load available watermarks dynamically from user uploads
            async function loadAvailableWatermarks() {
                // First check localStorage for previously uploaded watermarks
                let savedWatermarks = [];
                try {
                    const savedData = localStorage.getItem('watermarkUploads');
                    if (savedData) {
                        savedWatermarks = JSON.parse(savedData);
                    }
                } catch (e) {
                    console.warn("Could not retrieve saved watermarks", e);
                }

                // Return user uploaded watermarks only - no presets
                return savedWatermarks;
            }

            // Save watermark to localStorage for persistence
            function saveWatermarkToLibrary(watermark) {
                try {
                    // Get current saved watermarks
                    let savedWatermarks = [];
                    const savedData = localStorage.getItem('watermarkUploads');
                    if (savedData) {
                        savedWatermarks = JSON.parse(savedData);
                    }

                    // Check if watermark with same name already exists
                    const existingIndex = savedWatermarks.findIndex(wm => wm.name === watermark.name);
                    if (existingIndex >= 0) {
                        savedWatermarks[existingIndex] = {
                            name: watermark.name,
                            id: savedWatermarks[existingIndex].id, // Keep existing ID
                            dateAdded: new Date().toISOString()
                        };
                    } else {
                        // Add new watermark metadata
                        savedWatermarks.push({
                            name: watermark.name,
                            id: 'wm_' + Date.now(), // Unique ID for the watermark
                            dateAdded: new Date().toISOString()
                        });
                    }

                    // Save back to localStorage
                    localStorage.setItem('watermarkUploads', JSON.stringify(savedWatermarks));
                } catch (e) {
                    console.warn("Could not save watermark to library", e);
                }
            }

            // Initialize watermark gallery with dynamically loaded watermarks
            async function initWatermarkGallery() {
                // Clear existing watermarks but keep the "Add New" button
                const addNewButton = document.querySelector('.watermark-option.add-new');
                watermarkOptions.innerHTML = '';
                watermarkOptions.appendChild(addNewButton);

                // Add "Add New" button event listener
                addNewButton.addEventListener('click', () => {
                    watermarkInput.click();
                });

                // Add all current session watermarks
                watermarkList.forEach((watermark, index) => {
                    const option = createWatermarkOption(watermark, index);
                    watermarkOptions.insertBefore(option, addNewButton);
                });

                // Load saved watermarks and merge with current session
                try {
                    const savedWatermarks = await loadAvailableWatermarks();
                    // Note: This just shows placeholders for saved watermarks
                    // In a real app, you'd load the actual files from server storage
                    savedWatermarks.forEach((savedWatermark, index) => {
                        // Skip if we already have this watermark in the current session
                        if (watermarkList.some(wm => wm.name === savedWatermark.name)) {
                            return;
                        }

                        // Create a placeholder option for the saved watermark
                        const option = document.createElement('div');
                        option.className = 'watermark-option saved-watermark';
                        option.dataset.id = savedWatermark.id;

                        const preview = document.createElement('div');
                        preview.className = 'watermark-preview';

                        // Default placeholder icon as we don't have the actual image
                        const icon = document.createElement('i');
                        icon.className = 'fas fa-image placeholder-icon';

                        const label = document.createElement('div');
                        label.className = 'watermark-label';
                        label.textContent = savedWatermark.name;

                        preview.appendChild(icon);
                        option.appendChild(preview);
                        option.appendChild(label);

                        option.addEventListener('click', () => {
                            // In a real app, you would fetch the image from storage here
                            updateStatus(`Please upload ${savedWatermark.name} again or select another watermark`, 'info');
                        });

                        watermarkOptions.insertBefore(option, addNewButton);
                    });
                } catch (error) {
                    console.error("Error loading watermarks:", error);
                    updateStatus("Could not load watermarks", "error");
                }

                // Add click event to each watermark option to hide the gallery after selection
                document.querySelectorAll('.watermark-option:not(.saved-watermark)').forEach(option => {
                    option.addEventListener('click', function() {
                        // Close the gallery after selection (except for Add New button)
                        if (!this.classList.contains('add-new')) {
                            watermarkGallery.style.display = 'none';
                        }
                    });
                });
            }

            // Create a watermark option element
            function createWatermarkOption(watermark, index) {
                const option = document.createElement('div');
                option.className = 'watermark-option';
                option.dataset.index = index;

                const preview = document.createElement('div');
                preview.className = 'watermark-preview';

                const img = document.createElement('img');
                img.src = URL.createObjectURL(watermark.file);
                img.alt = watermark.name;
                img.onerror = () => {
                    // If image fails to load, show a placeholder
                    img.src = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNlZWUiLz48dGV4dCB4PSI1MCIgeT0iNTAiIGZvbnQtc2l6ZT0iMTQiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiNhYWEiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiPkltYWdlIE5vdCBGb3VuZDwvdGV4dD48L3N2Zz4=';
                };

                const label = document.createElement('div');
                label.className = 'watermark-label';
                label.textContent = watermark.name;

                preview.appendChild(img);
                option.appendChild(preview);
                option.appendChild(label);

                option.addEventListener('click', () => {
                    selectWatermark(watermark);
                    document.querySelectorAll('.watermark-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    option.classList.add('selected');
                });

                return option;
            }

            // Select a watermark
            function selectWatermark(watermark) {
                currentWatermark = watermark;
                watermarkControls.style.display = 'block';
                positionControls.style.display = 'flex'; // Show position controls
                updateStatus(`Selected watermark: ${watermark.name}`, 'success');

                // Hide the watermark gallery after selection
                watermarkGallery.style.display = 'none';

                // Check if we can process
                btnProcessVideo.disabled = !(currentVideo && currentWatermark);

                // Reset custom position when a new watermark is selected
                customPositionX = 0;
                customPositionY = 0;

                // Update preview
                updateWatermarkPreview();
            }

            // Update the watermark preview on the video - improved positioning
            function updateWatermarkPreview() {
                if (!currentVideo || !currentWatermark) return;

                // Get the actual displayed dimensions of the video
                const videoElement = document.getElementById('videoElement');
                const videoRect = videoElement.getBoundingClientRect();

                // Calculate actual displayed video area (accounting for letterboxing)
                const videoAspect = videoElement.videoWidth / videoElement.videoHeight;
                const containerAspect = videoRect.width / videoRect.height;

                let displayWidth, displayHeight;
                let offsetX = 0, offsetY = 0;

                if (videoAspect > containerAspect) {
                    // Video is wider than container - will have black bars top and bottom
                    displayWidth = videoRect.width;
                    displayHeight = displayWidth / videoAspect;
                    offsetY = (videoRect.height - displayHeight) / 2;
                } else {
                    // Video is taller than container - will have black bars left and right
                    displayHeight = videoRect.height;
                    displayWidth = displayHeight * videoAspect;
                    offsetX = (videoRect.width - displayWidth) / 2;
                }

                // Set canvas dimensions to match actual video dimensions
                watermarkOverlay.width = videoElement.videoWidth;
                watermarkOverlay.height = videoElement.videoHeight;

                // Position the overlay to match the video display area
                watermarkOverlay.style.width = `${displayWidth}px`;
                watermarkOverlay.style.height = `${displayHeight}px`;
                watermarkOverlay.style.left = `${offsetX}px`;
                watermarkOverlay.style.top = `${offsetY}px`;

                // Clear previous
                ctx.clearRect(0, 0, watermarkOverlay.width, watermarkOverlay.height);

                // Load watermark image
                const watermarkImg = new Image();

                // Handle both file object and URL-based watermarks
                if (currentWatermark.file) {
                    watermarkImg.src = URL.createObjectURL(currentWatermark.file);
                } else if (currentWatermark.url) {
                    watermarkImg.src = currentWatermark.url;
                }

                watermarkImg.onload = () => {
                    // Calculate size based on percentage of video width
                    const maxSize = Math.min(watermarkOverlay.width, watermarkOverlay.height);
                    const size = (watermarkSize / 100) * maxSize;

                    // Maintain aspect ratio
                    const aspectRatio = watermarkImg.width / watermarkImg.height;
                    let width, height;

                    if (aspectRatio > 1) {
                        width = size;
                        height = size / aspectRatio;
                    } else {
                        height = size;
                        width = size * aspectRatio;
                    }

                    // Calculate position
                    let x, y;
                    const padding = 20; // Padding from edges

                    switch (watermarkPosition) {
                        case 'top-left':
                            x = padding;
                            y = padding;
                            break;
                        case 'top-right':
                            x = watermarkOverlay.width - width - padding;
                            y = padding;
                            break;
                        case 'bottom-left':
                            x = padding;
                            y = watermarkOverlay.height - height - padding;
                            break;
                        case 'bottom-right':
                            x = watermarkOverlay.width - width - padding;
                            y = watermarkOverlay.height - height - padding;
                            break;
                        case 'center':
                            x = (watermarkOverlay.width - width) / 2;
                            y = (watermarkOverlay.height - height) / 2;
                            break;
                        case 'custom':
                            // Use custom position with the arrow controls
                            x = (watermarkOverlay.width - width) / 2 + customPositionX;
                            y = (watermarkOverlay.height - height) / 2 + customPositionY;

                            // Ensure the watermark stays within bounds
                            x = Math.max(0, Math.min(x, watermarkOverlay.width - width));
                            y = Math.max(0, Math.min(y, watermarkOverlay.height - height));
                            break;
                    }

                    // Set global alpha for transparency
                    ctx.globalAlpha = watermarkOpacity;

                    // Draw the watermark
                    ctx.drawImage(watermarkImg, x, y, width, height);

                    // Reset alpha
                    ctx.globalAlpha = 1.0;
                };
            }

            // Process video to add watermark
            async function processVideo() {
                updateStatus('Processing video...', 'info');
                const progressTracker = initializeProgress();

                updateStatus('NOTE: Browser-based processing may have audio synchronization issues. For professional results, consider using ffmpeg.wasm.', 'warning');

                return new Promise((resolve, reject) => {
                    // Reset cancellation flag
                    isProcessingCancelled = false;

                    const video = document.createElement('video');
                    video.muted = true;
                    video.src = URL.createObjectURL(currentVideo);

                    video.onloadedmetadata = () => {
                        // Create canvas for output
                        const canvas = document.createElement('canvas');
                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;
                        const ctx = canvas.getContext('2d');

                        // Set up watermark image
                        const watermarkImg = new Image();
                        if (currentWatermark.file) {
                            watermarkImg.src = URL.createObjectURL(currentWatermark.file);
                        } else {
                            watermarkImg.src = currentWatermark.url;
                        }

                        watermarkImg.onload = () => {
                            // Calculate watermark size and position
                            const maxSize = Math.min(canvas.width, canvas.height);
                            const size = (watermarkSize / 100) * maxSize;

                            // Maintain aspect ratio
                            const aspectRatio = watermarkImg.width / watermarkImg.height;
                            let width, height;

                            if (aspectRatio > 1) {
                                width = size;
                                height = size / aspectRatio;
                            } else {
                                height = size;
                                width = size * aspectRatio;
                            }

                            // Calculate position
                            let x, y;
                            const padding = 20; // Padding from edges

                            switch (watermarkPosition) {
                                case 'top-left':
                                    x = padding;
                                    y = padding;
                                    break;
                                case 'top-right':
                                    x = canvas.width - width - padding;
                                    y = padding;
                                    break;
                                case 'bottom-left':
                                    x = padding;
                                    y = canvas.height - height - padding;
                                    break;
                                case 'bottom-right':
                                    x = canvas.width - width - padding;
                                    y = canvas.height - height - padding;
                                    break;
                                case 'center':
                                    x = (canvas.width - width) / 2;
                                    y = (canvas.height - height) / 2;
                                    break;
                                case 'custom':
                                    // For now, default to center
                                    x = (canvas.width - width) / 2;
                                    y = (canvas.height - height) / 2;
                                    break;
                            }

                            const duration = video.duration;
                            const fps = 30;
                            const totalFrames = Math.floor(duration * fps);
                            progressTracker.total = totalFrames;

                            // IMPROVED AUDIO HANDLING
                            // Try to get the original audio stream directly from the video element
                            let audioStream = null;
                            try {
                                if (video.captureStream) {
                                    const videoStream = video.captureStream();
                                    const audioTracks = videoStream.getAudioTracks();
                                    if (audioTracks.length > 0) {
                                        audioStream = new MediaStream([audioTracks[0]]);
                                    }
                                }
                            } catch (e) {
                                console.warn("Could not capture original audio stream", e);
                            }

                            // Create visual stream from canvas
                            const visualStream = canvas.captureStream(fps);

                            // Combine streams if we have audio
                            let combinedStream;
                            if (audioStream && audioStream.getAudioTracks().length > 0) {
                                combinedStream = new MediaStream([
                                    ...visualStream.getVideoTracks(),
                                    ...audioStream.getAudioTracks()
                                ]);
                                updateStatus("Audio track detected and included", "info");
                            } else {
                                combinedStream = visualStream;
                                updateStatus("No audio track detected or audio processing not supported by your browser", "warning");
                            }

                            // Try different codec options based on browser support
                            let options = {};
                            const mimeTypes = [
                                'video/webm; codecs=vp9,opus',
                                'video/webm; codecs=vp8,opus',
                                'video/webm',
                                'video/mp4'
                            ];

                            for (let mimeType of mimeTypes) {
                                if (MediaRecorder.isTypeSupported(mimeType)) {
                                    options = { mimeType };
                                    updateStatus(`Using codec: ${mimeType}`, "info");
                                    break;
                                }
                            }

                            const recorder = new MediaRecorder(combinedStream, options);
                            const chunks = [];

                            recorder.ondataavailable = e => chunks.push(e.data);

                            recorder.onstop = () => {
                                const blob = new Blob(chunks, { type: recorder.mimeType || 'video/webm' });
                                processedVideo = blob;
                                resolve(blob);
                            };

                            let currentFrame = 0;
                            recorder.start();
                            video.currentTime = 0;
                            video.play();

                            function processFrame() {
                                if (isProcessingCancelled) {
                                    video.pause();
                                    recorder.stop();
                                    reject("Processing cancelled");
                                    return;
                                }

                                if (video.currentTime >= duration) {
                                    recorder.stop();
                                    video.pause();
                                    finalizeProgress(progressTracker);
                                    return;
                                }

                                // Draw the current video frame
                                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                                // Add watermark
                                ctx.globalAlpha = watermarkOpacity;
                                ctx.drawImage(watermarkImg, x, y, width, height);
                                ctx.globalAlpha = 1.0;

                                currentFrame++;
                                updateProgress(progressTracker, currentFrame, totalFrames);

                                // Request next frame
                                requestAnimationFrame(processFrame);
                            }

                            processFrame();
                        };

                        watermarkImg.onerror = () => {
                            reject("Error loading watermark image");
                        };
                    };

                    video.onerror = () => {
                        reject("Error processing video");
                    };
                });
            }

            // Drag and drop functionality for video
            function initDragAndDrop() {
                updateStatus('Video Watermarker ready. Add a watermark to your video.', 'info');

                statusBox.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    statusBox.classList.add('drag-over');
                });

                statusBox.addEventListener('dragleave', () => {
                    statusBox.classList.remove('drag-over');
                });

                statusBox.addEventListener('drop', (e) => {
                    e.preventDefault();
                    statusBox.classList.remove('drag-over');

                    if (e.dataTransfer.files.length > 0) {
                        const file = e.dataTransfer.files[0];
                        if (file.type.includes('video/')) {
                            handleVideoFile(file);
                        } else if (file.type.includes('image/')) {
                            handleWatermarkFile(file);
                        } else {
                            updateStatus('Please drop a valid video or image file.', 'error');
                        }
                    }
                });

                // Watermark gallery drag & drop
                watermarkGallery.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    watermarkGallery.classList.add('drag-over');
                });

                watermarkGallery.addEventListener('dragleave', () => {
                    watermarkGallery.classList.remove('drag-over');
                });

                watermarkGallery.addEventListener('drop', (e) => {
                    e.preventDefault();
                    watermarkGallery.classList.remove('drag-over');

                    if (e.dataTransfer.files.length > 0) {
                        const files = e.dataTransfer.files;
                        let validImagesFound = 0;

                        for(let i = 0; i < files.length; i++) {
                            const file = files[i];
                            if (file.type.includes('image/')) {
                                handleWatermarkFile(file);
                                validImagesFound++;
                            }
                        }

                        if (validImagesFound > 0) {
                            updateStatus(`Added ${validImagesFound} new watermark${validImagesFound > 1 ? 's' : ''}`, 'success');
                        } else {
                            updateStatus('Please drop valid image files for watermarks.', 'error');
                        }
                    }
                });

                // Allow adding watermarks through clicking the gallery area
                watermarkGallery.addEventListener('click', (e) => {
                    // Only trigger file dialog if clicked outside of existing watermark options
                    if (!e.target.closest('.watermark-option')) {
                        watermarkInput.click();
                    }
                });
            }

            // Handle video file selection
            function handleVideoFile(file) {
                if (file) {
                    currentVideo = file;
                    originalFilename = file.name;

                    // Create clean filename for saving (replace spaces with underscores)
                    const cleanedFilename = file.name.replace(/\s+/g, '_');
                    const outputFilename = 'watermarked_' + cleanedFilename;

                    // Update display filename
                    filename.value = outputFilename;

                    updateStatus(`Video loaded: ${file.name}`, 'success');

                    // Create video preview
                    const videoURL = URL.createObjectURL(file);
                    videoElement.src = videoURL;

                    // Enable buttons
                    btnRename.disabled = false;

                    // Check if we can process
                    btnProcessVideo.disabled = !(currentVideo && currentWatermark);

                    // Reset save state
                    hasBeenSaved = false;
                    processedVideo = null;
                    btnDownload.disabled = true;

                    // Update watermark preview if we have one
                    if (currentWatermark) {
                        updateWatermarkPreview();
                    }
                }
            }

            // Handle watermark file selection
            function handleWatermarkFile(file) {
                if (file) {
                    // Only accept image files, preferably PNG or GIF
                    if (!file.type.match('image/(png|gif|jpeg|jpg|webp)')) {
                        updateStatus('Please select PNG or GIF images for best results', 'warning');
                        return;
                    }

                    // Create a watermark object
                    const watermark = {
                        name: file.name,
                        file: file,
                        dateAdded: new Date().toISOString()
                    };

                    // Add to list for current session
                    watermarkList.push(watermark);

                    // Save to library for persistence
                    saveWatermarkToLibrary(watermark);

                    // Select this watermark
                    selectWatermark(watermark);

                    // Refresh gallery
                    initWatermarkGallery();

                    updateStatus(`Added "${file.name}" to your watermarks library`, 'success');
                }
            }

            // Format seconds to MM:SS
            function formatTime(seconds) {
                const mins = Math.floor(seconds / 60);
                const secs = Math.floor(seconds % 60);
                return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
            }

            // Progress tracker functions
            function initializeProgress() {
                progressContainer.style.display = 'block';
                progressBar.style.width = '0%';
                progressFinalizing.style.width = '0%';
                progressPercentage.textContent = '0%';
                progressFrames.textContent = '0/0 frames';
                progressTime.textContent = 'Estimating...';
                finalizingStarted = false;

                return {
                    startTime: Date.now(),
                    lastUpdate: Date.now(),
                    processed: 0,
                    total: 0,
                    etaSeconds: 0,
                    lastProcessedFrame: 0,
                    stalledSince: null,
                    stalledTimeout: 5000
                };
            }

            function updateProgress(tracker, current, total, forceUpdate = false) {
                // Only update UI every 500ms to avoid performance issues
                const now = Date.now();
                if (now - tracker.lastUpdate < 500 && !forceUpdate) return;

                // Check for stalling
                if (current === tracker.lastProcessedFrame) {
                    if (!tracker.stalledSince) {
                        tracker.stalledSince = now;
                    } else if (now - tracker.stalledSince > tracker.stalledTimeout) {
                        progressTime.classList.add('progress-stalled');
                        progressTime.textContent = `Stalled for ${Math.floor((now - tracker.stalledSince)/1000)}s`;
                    }
                } else {
                    tracker.stalledSince = null;
                    progressTime.classList.remove('progress-stalled');
                }

                tracker.lastProcessedFrame = current;
                tracker.processed = current;
                tracker.total = total;
                tracker.lastUpdate = now;

                // Update UI with enhanced progress indication
                const percent = Math.min(98, Math.floor((current / total) * 98)); // Cap at 98% to show finalizing state

                if (percent >= 98 && !finalizingStarted) {
                    finalizingStarted = true;
                    progressFinalizing.classList.add('active');
                    progressTime.textContent = "Finalizing...";
                    updateStatus("Almost done, finalizing video...", "info");
                }

                progressBar.style.width = `${percent}%`;
                progressPercentage.textContent = `${percent}%`;
                progressFrames.textContent = `${current}/${total} frames`;

                // Calculate ETA
                if (!tracker.stalledSince && current > 0) {
                    const elapsedMs = now - tracker.startTime;
                    const msPerFrame = elapsedMs / current;
                    const remainingFrames = total - current;
                    const remainingMs = msPerFrame * remainingFrames;

                    if (remainingMs > 0 && current > 5) {
                        // Add 30 seconds (30000ms) to account for final processing
                        tracker.etaSeconds = Math.ceil((remainingMs + 30000) / 1000);
                        progressTime.textContent = `ETA: ${formatTime(tracker.etaSeconds)}`;
                    }
                }
            }

            function finalizeProgress(tracker) {
                const totalTime = Math.ceil((Date.now() - tracker.startTime) / 1000);
                progressTime.textContent = `Completed in ${formatTime(totalTime)}`;
                progressBar.style.width = '100%';
                progressFinalizing.style.width = '0%';
                progressFinalizing.classList.remove('active');
                progressPercentage.textContent = '100%';
                tracker.stalledSince = null;
                progressTime.classList.remove('progress-stalled');
                finalizingStarted = false;
            }

            // Add cancel button functionality
            document.getElementById('btnCancelProcessing').addEventListener('click', () => {
                isProcessingCancelled = true;
                updateStatus('Processing cancelled', 'warning');
            });

            document.getElementById('btnCancelProcessingBottom').addEventListener('click', () => {
                isProcessingCancelled = true;
                updateStatus('Processing cancelled', 'warning');
            });

            btnRestart.addEventListener('click', () => {
                location.reload();
            });

            btnProcessVideo.addEventListener('click', async () => {
                if (currentVideo && currentWatermark) {
                    btnProcessVideo.disabled = true;
                    try {
                        const result = await processVideo();
                        updateStatus('Video processing complete! Your watermarked video is ready.', 'success');

                        // Preview the processed video
                        videoElement.src = URL.createObjectURL(result);

                        // Enable download button
                        btnDownload.disabled = false;
                    } catch (error) {
                        updateStatus(`Error: ${error}`, 'error');
                    } finally {
                        btnProcessVideo.disabled = false;
                    }
                }
            });

            btnDownload.addEventListener('click', () => {
                if (processedVideo) {
                    hasBeenSaved = true;
                    updateStatus('Downloading watermarked video...', 'info');

                    const url = URL.createObjectURL(processedVideo);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename.value; // Use the displayed filename which already has prefix
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);

                    setTimeout(() => {
                        updateStatus('Download complete!', 'success');
                    }, 1000);
                }
            });

            // File input handlers
            videoInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    handleVideoFile(e.target.files[0]);
                }
            });

            watermarkInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    handleWatermarkFile(e.target.files[0]);
                }
            });

            // Control event handlers
            positionSelect.addEventListener('change', () => {
                watermarkPosition = positionSelect.value;

                // Show/hide position controls based on custom selection
                positionControls.style.display = watermarkPosition === 'custom' ? 'flex' : 'none';

                // Reset custom position when changing position type
                if (watermarkPosition !== 'custom') {
                    customPositionX = 0;
                    customPositionY = 0;
                }

                updateWatermarkPreview();
            });

            sizeSlider.addEventListener('input', () => {
                watermarkSize = parseInt(sizeSlider.value);
                sizeValue.textContent = `${watermarkSize}%`;
                updateWatermarkPreview();
            });

            opacitySlider.addEventListener('input', () => {
                watermarkOpacity = parseInt(opacitySlider.value) / 100;
                opacityValue.textContent = `${opacitySlider.value}%`;
                updateWatermarkPreview();
            });

            // Position arrow controls
            btnMoveUp.addEventListener('click', () => {
                if (watermarkPosition !== 'custom') {
                    watermarkPosition = 'custom';
                    positionSelect.value = 'custom';
                }
                customPositionY -= positionStep;
                updateWatermarkPreview();
            });

            btnMoveDown.addEventListener('click', () => {
                if (watermarkPosition !== 'custom') {
                    watermarkPosition = 'custom';
                    positionSelect.value = 'custom';
                }
                customPositionY += positionStep;
                updateWatermarkPreview();
            });

            btnMoveLeft.addEventListener('click', () => {
                if (watermarkPosition !== 'custom') {
                    watermarkPosition = 'custom';
                    positionSelect.value = 'custom';
                }
                customPositionX -= positionStep;
                updateWatermarkPreview();
            });

            btnMoveRight.addEventListener('click', () => {
                if (watermarkPosition !== 'custom') {
                    watermarkPosition = 'custom';
                    positionSelect.value = 'custom';
                }
                customPositionX += positionStep;
                updateWatermarkPreview();
            });

            // Also allow keyboard arrow keys for positioning when in custom mode
            document.addEventListener('keydown', (e) => {
                if (watermarkPosition === 'custom' && currentWatermark) {
                    switch (e.key) {
                        case 'ArrowUp':
                            customPositionY -= positionStep;
                            updateWatermarkPreview();
                            e.preventDefault();
                            break;
                        case 'ArrowDown':
                            customPositionY += positionStep;
                            updateWatermarkPreview();
                            e.preventDefault();
                            break;
                        case 'ArrowLeft':
                            customPositionX -= positionStep;
                            updateWatermarkPreview();
                            e.preventDefault();
                            break;
                        case 'ArrowRight':
                            customPositionX += positionStep;
                            updateWatermarkPreview();
                            e.preventDefault();
                            break;
                    }
                }
            });

            // Handle window resize to update watermark position
            window.addEventListener('resize', () => {
                if (currentWatermark && videoElement.readyState >= 1) {
                    updateWatermarkPreview();
                }
            });

            // Handle video metadata loaded to update watermark position
            videoElement.addEventListener('loadedmetadata', () => {
                if (currentWatermark) {
                    updateWatermarkPreview();
                }
            });

            // Initialize
            initDragAndDrop();
            updateStatus('Welcome! Add a watermark or logo to your video.', 'info');
            initWatermarkGallery(); // Load watermarks on startup

            // Add missing button handlers
            btnOpenVideo.addEventListener('click', () => {
                videoInput.click();
            });

            btnSelectWatermark.addEventListener('click', () => {
                watermarkGallery.style.display = watermarkGallery.style.display === 'none' ? 'block' : 'none';
                if (watermarkGallery.style.display === 'block') {
                    initWatermarkGallery();
                }
            });

            // Rename button functionality
            btnRename.addEventListener('click', () => {
                if (filename.value.trim() === '') {
                    // Make sure we always have a filename with the watermarked_ prefix
                    const cleanedOriginalName = originalFilename.replace(/\s+/g, '_');
                    filename.value = 'watermarked_' + cleanedOriginalName;
                } else if (!filename.value.startsWith('watermarked_')) {
                    // If user removed the prefix, add it back
                    filename.value = 'watermarked_' + filename.value;
                }

                // Replace spaces with underscores
                filename.value = filename.value.replace(/\s+/g, '_');

                updateStatus(`Filename updated to: ${filename.value}`, 'info');
            });
        });
    </script>
</body>

</html>
