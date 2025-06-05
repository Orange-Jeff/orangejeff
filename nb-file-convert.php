<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>NetBound Tools: File Converter</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/wavesurfer.js@7"></script>
    <style>
        body {
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f4f4f9;
            width: 100%;
            max-width: 768px;
            margin: 0 auto;
            /* Center when standalone */
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        body.in-iframe {
            margin: 0 auto;
            /* Center in iframe */
            max-width: 100%;
            /* Take full width in iframe */
        }

        .tool-container {
            background: #f4f4f9;
            height: auto;
            margin: 0 auto;
            max-width: 768px;
            width: 100%;
            padding: 20px;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }

        .tool-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-bottom: 10px;
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

        .hamburger-menu {
            color: #0056b3;
            font-size: 18px;
            cursor: pointer;
            text-decoration: none;
            padding: 5px 10px;
            display: flex;
            align-items: center;
        }

        .hamburger-menu:hover {
            color: #004494;
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

        .command-button:hover:not(:disabled) {
            background: #004494;
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

        .status-message.progress {
            border-left: 3px solid #2196f3;
        }

        .status-message.progress:first-child {
            background: #2196f3;
            color: white;
            /* Ensure text is white on blue background */
        }

        .filename-control {
            width: 100%;
            display: flex;
            gap: 10px;
            margin: 10px 0;
            align-items: center;
        }

        .filename-input {
            flex: 1;
            padding: 6px 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-family: inherit;
            font-size: 14px;
        }

        .status-bar.drag-over {
            background: #e3f2fd;
            border-color: #2196f3;
            border-style: dashed;
        }

        .format-select {
            padding: 6px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
            margin: 10px 0 20px 0;
            width: 100%;
        }

        .preview-window {
            width: 100%;
            min-height: 200px;
            background: #2a2a2a;
            border-radius: 4px;
            margin: 10px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            overflow: hidden;
        }

        .preview-window.audio {
            height: 150px;
        }

        .preview-window.video {
            height: 432px;
        }

        .preview-window video {
            max-width: 100%;
            max-height: 100%;
        }

        .preview-window audio {
            width: 100%;
        }

        #waveform {
            width: 100%;
            height: 128px;
            background: #1a1a1a;
        }

        * {
            box-sizing: border-box;
        }

        /* Make sure the spinner animation works properly */
        @keyframes fa-spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .fa-spin {
            animation: fa-spin 2s linear infinite;
        }
    </style>
</head>

<body>
    <div class="tool-container">
        <div class="tool-header">
            <h1 class="tool-title">NetBound Tools: File Converter</h1>
            <a href="main.php?app=nb-file-convert.php" class="hamburger-menu" title="Go to Main Menu">
                <i class="fas fa-bars"></i>
            </a>
        </div>

        <div id="statusBar" class="status-bar"></div>

        <div class="work-area">
            <div class="preview-area">
                <!-- Open/Reset buttons above preview -->
                <div class="button-controls">
                    <button class="command-button" id="btnOpen">
                        <i class="fas fa-folder-open"></i> Open File(s)
                    </button>
                    <button class="command-button" id="btnRestart">
                        <i class="fas fa-redo"></i> Restart
                    </button>
                </div>

                <div id="previewWindow" class="preview-window">
                    <i class="fas fa-file-upload fa-3x"></i>
                </div>

                <!-- Filename field with rename button -->
                <div class="filename-control">
                    <input type="text" id="filename" class="filename-input" placeholder="No file selected">
                    <button class="command-button" id="btnRename" disabled>
                        <i class="fas fa-edit"></i> Rename
                    </button>
                </div>

                <!-- Convert/Save buttons below filename, left-justified -->
                <div class="button-controls">
                    <button class="command-button" id="btnConvert" disabled>
                        <i class="fas fa-exchange-alt"></i> Convert
                    </button>
                    <button class="command-button" id="btnSave" disabled>
                        <i class="fas fa-save"></i> Save
                    </button>
                    <span id="fileCount" style="margin-left: auto; color: #666;"></span>
                </div>
            </div>
        </div>
    </div>

    <input type="file" id="fileInput" style="display: none" multiple accept=".mp3,.wav,.mp4,.webm">

    <script>
        function inIframe() {
            try {
                return window.self !== window.top;
            } catch (e) {
                return true;
            }
        }

        if (inIframe()) {
            document.body.classList.add('in-iframe');

            // Configure hamburger menu to break out of iframe when clicked
            const hamburgerMenu = document.querySelector('.hamburger-menu');
            if (hamburgerMenu) {
                hamburgerMenu.setAttribute('target', '_top');
            }
        }

        const status = {
            update(message, type = 'info') {
                const container = document.getElementById('statusBar');

                // Remove any identical progress messages to prevent duplicates
                if (type === 'progress') {
                    document.querySelectorAll('.status-message.progress').forEach(el => {
                        if (el.innerHTML.includes(message.split(' ')[0])) {
                            el.remove();
                        }
                    });
                }

                const messageDiv = document.createElement('div');
                messageDiv.className = `status-message ${type}`;
                messageDiv.innerHTML = message;
                container.insertBefore(messageDiv, container.firstChild);
                container.scrollTop = 0;
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('fileInput');
            const btnOpen = document.getElementById('btnOpen');
            const btnConvert = document.getElementById('btnConvert');
            const btnSave = document.getElementById('btnSave');
            const btnRename = document.getElementById('btnRename');
            const btnRestart = document.getElementById('btnRestart');
            const statusBar = document.getElementById('statusBar');
            const filename = document.getElementById('filename');
            const fileCount = document.getElementById('fileCount');

            let originalFilename = '';
            let currentFiles = [];
            let wavesurfer = null;
            let convertedFiles = [];
            let targetFormat = '';
            let conversionInProgress = false;

            function initDragAndDrop(statusBar, fileInput) {
                status.update('File converter MP3/WAV and MP4/Webm', 'info');

                const dropTargets = [statusBar, document.getElementById('previewWindow')];

                dropTargets.forEach(target => {
                    target.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        target.classList.add('drag-over');
                    });

                    target.addEventListener('dragleave', () => {
                        target.classList.remove('drag-over');
                    });

                    target.addEventListener('drop', (e) => {
                        e.preventDefault();
                        target.classList.remove('drag-over');
                        if (e.dataTransfer.files.length > 0) {
                            handleFiles(e.dataTransfer.files);
                        }
                    });
                });
            }

            function determineTargetFormat(fileExt) {
                switch (fileExt.toLowerCase()) {
                    case 'wav':
                        return 'mp3';
                    case 'mp3':
                        return 'wav';
                    case 'webm':
                        return 'mp4';
                    case 'mp4':
                        return 'webm';
                    default:
                        return '';
                }
            }

            function getConvertedFilename(originalName, targetExt) {
                const baseName = originalName.substring(0, originalName.lastIndexOf('.'));
                return `${baseName}.${targetExt}`;
            }

            function updatePreview(file) {
                const previewWindow = document.getElementById('previewWindow');
                const fileType = file.type.split('/')[0];
                const extension = file.name.split('.').pop().toLowerCase();

                // Clear previous preview
                previewWindow.innerHTML = '';
                previewWindow.className = 'preview-window';

                if (fileType === 'audio' || extension === 'wav' || extension === 'mp3') {
                    previewWindow.className = 'preview-window audio';

                    // Only add waveform - no audio element
                    const waveform = document.createElement('div');
                    waveform.id = 'waveform';
                    previewWindow.appendChild(waveform);

                    // Initialize or reload wavesurfer
                    if (wavesurfer) {
                        wavesurfer.destroy();
                    }

                    wavesurfer = WaveSurfer.create({
                        container: '#waveform',
                        waveColor: '#4a9eff',
                        progressColor: '#006ee6',
                        height: 128,
                        normalize: true
                    });

                    wavesurfer.loadBlob(file);
                } else if (fileType === 'video' || extension === 'webm' || extension === 'mp4') {
                    previewWindow.className = 'preview-window video';
                    const video = document.createElement('video');
                    video.controls = true;
                    video.src = URL.createObjectURL(file);
                    previewWindow.appendChild(video);
                }
            }

            function isSlowConversion(sourceFormat, targetFormat) {
                // Identify conversions that are typically slow
                return (sourceFormat === 'webm' && targetFormat === 'mp3') ||
                    (sourceFormat === 'webm' && targetFormat === 'wav');
            }

            function handleFiles(files) {
                if (files.length > 0) {
                    // Convert FileList to Array for easier handling
                    const allFiles = Array.from(files);
                    const supportedExtensions = ['wav', 'mp3', 'webm', 'mp4'];

                    // Check for unsupported files first
                    const unsupportedFiles = allFiles.filter(file => {
                        const fileExt = file.name.split('.').pop().toLowerCase();
                        return !supportedExtensions.includes(fileExt);
                    });

                    // Filter to only supported files
                    currentFiles = allFiles.filter(file => {
                        const fileExt = file.name.split('.').pop().toLowerCase();
                        return supportedExtensions.includes(fileExt);
                    });

                    // Show warning if some files were skipped
                    if (unsupportedFiles.length > 0) {
                        status.update(`Skipped ${unsupportedFiles.length} unsupported files. Only WAV, MP3, WebM, and MP4 files are supported.`, 'error');
                    }

                    if (currentFiles.length === 0) {
                        status.update('No supported files found. Please select WAV, MP3, WebM, or MP4 files.', 'error');
                        btnConvert.disabled = true;
                        btnRename.disabled = true;
                        btnSave.disabled = true;
                        return;
                    }

                    // Check for mixed file types warning
                    const fileTypes = new Set(currentFiles.map(file => file.name.split('.').pop().toLowerCase()));
                    if (fileTypes.size > 1) {
                        status.update(`Warning: Mixed file types detected. Each file will be converted to its corresponding format.`, 'error');
                    }

                    // Preview first file
                    const firstFile = currentFiles[0];
                    const fileExt = firstFile.name.split('.').pop().toLowerCase();
                    targetFormat = determineTargetFormat(fileExt);

                    // Set filename to the converted version
                    originalFilename = firstFile.name;
                    const convertedName = getConvertedFilename(originalFilename, targetFormat);

                    // Handle multiple files differently
                    if (currentFiles.length > 1) {
                        filename.value = "Bulk Files Selected";
                        filename.disabled = true;
                        btnRename.disabled = true;
                        fileCount.textContent = `${currentFiles.length} files selected`;
                    } else {
                        filename.value = convertedName;
                        filename.disabled = false;
                        btnRename.disabled = false;
                        fileCount.textContent = '';
                    }

                    updatePreview(firstFile);

                    const msg = currentFiles.length > 1 ?
                        `Selected ${currentFiles.length} files.` :
                        `File selected: ${firstFile.name}`;

                    status.update(msg, 'success');
                    btnConvert.disabled = false;
                    btnSave.disabled = true;
                }
            }

            // Update the open button to show alert if there are unsaved files
            btnOpen.onclick = () => {
                // Check if there are unsaved converted files
                if (convertedFiles.length > 0) {
                    if (!confirm("You have unsaved converted files. Opening new files will discard your current conversions. Continue?")) {
                        return; // User clicked Cancel, abort file open
                    }
                }

                fileInput.click();
            };

            // Update the restart button to show alert if there are unsaved files
            btnRestart.onclick = () => {
                // Check if there are unsaved converted files
                if (convertedFiles.length > 0) {
                    if (!confirm("You have unsaved converted files. Are you sure you want to restart?")) {
                        return; // User clicked Cancel, abort restart
                    }
                }

                // Show cleanup status
                status.update("Cleaning up temporary files...", "info");

                // Call cleanup endpoint before reloading
                fetch('convert.php?cleanup=true')
                    .then(response => response.text())
                    .then(result => {
                        status.update("Temporary files cleaned up, restarting...", "success");
                        setTimeout(() => location.reload(), 500);
                    })
                    .catch(error => {
                        console.error("Cleanup error:", error);
                        status.update("Error cleaning up files, restarting anyway...", "error");
                        setTimeout(() => location.reload(), 1000);
                    });
            };

            btnRename.onclick = () => {
                if (currentFiles.length > 0) {
                    const newName = filename.value.trim();
                    if (newName) {
                        // If we're dealing with multiple files, rename just affects display
                        // If it's a single file, it will be used during conversion
                        status.update(`Converted files will use this naming pattern: ${newName}`, 'info');
                    } else {
                        // Reset to converted name of first file
                        const convertedName = getConvertedFilename(originalFilename, targetFormat);
                        filename.value = convertedName;
                        status.update('Filename cannot be empty, reverted to default', 'error');
                    }
                }
            };

            // Create a spinner for loading indicator
            function createSpinner() {
                return `<i class="fas fa-spinner fa-spin"></i>`;
            }

            // Update status with progress
            function updateProgress(current, total) {
                const percent = Math.round((current / total) * 100);
                status.update(`Converting: ${current}/${total} files (${percent}%) ${createSpinner()}`, 'info');
            }

            btnConvert.onclick = () => {
                if (currentFiles.length > 0 && !conversionInProgress) {
                    convertedFiles = [];
                    conversionInProgress = true;
                    btnConvert.disabled = true;

                    // Clear previous conversion status messages
                    document.querySelectorAll('.status-message.progress').forEach(el => el.remove());

                    // Create a counter for tracking conversions
                    let completedConversions = 0;

                    // Process each file
                    currentFiles.forEach((file, index) => {
                        const formData = new FormData();
                        formData.append('file', file);

                        // Get source format from file extension
                        const sourceFormat = file.name.split('.').pop().toLowerCase();
                        const fileTargetFormat = determineTargetFormat(sourceFormat);

                        // Format string for PHP processing
                        formData.append('format', `${sourceFormat}-${fileTargetFormat}`);
                        formData.append('submit', 'true');

                        if (index === 0) {
                            // Make the slow conversion warning RED by using 'error' type
                            if (isSlowConversion(sourceFormat, fileTargetFormat)) {
                                status.update(`Starting conversion of ${file.name}... This might take several minutes. ${createSpinner()}`, 'error');
                            } else {
                                status.update(`Converting files (${sourceFormat} to ${fileTargetFormat})... ${createSpinner()}`, 'info');
                            }
                        }

                        // Keep conversion progress in blue
                        status.update(`Converting [${index + 1}/${currentFiles.length}]: ${file.name} - This might take a few minutes ${createSpinner()}`, 'progress');

                        fetch('convert.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.text())
                            .then(result => {
                                completedConversions++;

                                // Remove progress status for this file
                                document.querySelectorAll('.status-message.progress').forEach(el => {
                                    if (el.textContent.includes(file.name)) {
                                        el.remove();
                                    }
                                });

                                // IMPROVED: Better detection of success with debug info
                                const success = result.includes('successfully');

                                // Extract link if present - FIXING LINK EXTRACTION
                                const linkMatch = result.match(/<a href=['"](.*?)['"]/);
                                if (linkMatch && linkMatch[1]) {
                                    convertedFiles.push(linkMatch[1]);
                                    console.log("Found download link:", linkMatch[1]); // Debug info
                                } else if (success) {
                                    // If success but no link found, log the issue and still count as success
                                    console.log("Conversion reported success but no link found in:", result);
                                    // Add a fallback link based on known pattern
                                    const outputFile = `uploads/${file.name.substring(0, file.name.lastIndexOf('.'))}.${fileTargetFormat}`;
                                    convertedFiles.push(outputFile);
                                }

                                // Extract text message
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = result;
                                const message = tempDiv.textContent || result;

                                // Update status message
                                if (success) {
                                    status.update(`Converted [${completedConversions}/${currentFiles.length}]: ${file.name}`, 'success');
                                } else {
                                    status.update(`Error converting ${file.name}: ${message}`, 'error');
                                }

                                // Check if all conversions are complete
                                if (completedConversions === currentFiles.length) {
                                    conversionInProgress = false;

                                    // Always enable the save button if we have any converted files
                                    if (convertedFiles.length > 0) {
                                        btnSave.disabled = false;
                                        status.update(`${convertedFiles.length} files ready for download`, 'success');
                                    } else {
                                        status.update('No files were converted successfully', 'error');
                                    }

                                    btnConvert.disabled = false;
                                }
                            })
                            .catch(error => {
                                completedConversions++;

                                // Remove progress status for this file
                                document.querySelectorAll('.status-message.progress').forEach(el => {
                                    if (el.textContent.includes(file.name)) {
                                        el.remove();
                                    }
                                });

                                status.update(`Error converting ${file.name}: ${error}`, 'error');

                                // Check if all conversions are complete
                                if (completedConversions === currentFiles.length) {
                                    conversionInProgress = false;

                                    // Enable save button for any successful conversions
                                    if (convertedFiles.length > 0) {
                                        btnSave.disabled = false;
                                        status.update(`${convertedFiles.length} files ready for download, ${currentFiles.length - convertedFiles.length} failed`, 'info');
                                    }

                                    btnConvert.disabled = false;
                                }
                            });
                    });
                }
            };

            btnSave.onclick = () => {
                if (convertedFiles.length > 0) {
                    // If there's just one file, use the filename field name
                    if (convertedFiles.length === 1) {
                        // Ensure we have an absolute path to the file
                        const downloadURL = new URL(convertedFiles[0], window.location.href).href;

                        const downloadLink = document.createElement('a');
                        downloadLink.href = downloadURL;
                        downloadLink.download = convertedFiles[0].split('/').pop(); // Extract filename
                        downloadLink.click();
                        status.update('Download started', 'success');
                    } else {
                        // For multiple files, download each one with small delay to prevent browser blocking
                        status.update(`Downloading ${convertedFiles.length} files...`, 'info');

                        convertedFiles.forEach((file, index) => {
                            setTimeout(() => {
                                // Ensure we have an absolute path to the file
                                const downloadURL = new URL(file, window.location.href).href;

                                const downloadLink = document.createElement('a');
                                downloadLink.href = downloadURL;
                                downloadLink.download = file.split('/').pop(); // Extract filename
                                downloadLink.click();

                                if (index === convertedFiles.length - 1) {
                                    status.update(`All ${convertedFiles.length} files download started`, 'success');
                                }
                            }, index * 500); // 500ms delay between downloads
                        });
                    }
                }
            };

            fileInput.onchange = (e) => {
                if (e.target.files.length > 0) {
                    handleFiles(e.target.files);
                }
            };

            initDragAndDrop(statusBar, fileInput);
        });
    </script>

    <?php
    if (isset($_POST['submit'])) {
        $targetDir = "uploads/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $targetFile = $targetDir . basename($_FILES["file"]["name"]);
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $format = $_POST['format'];
        list($sourceFormat, $targetFormat) = explode('-', $format);

        if ($fileType === $sourceFormat) {
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
                $outputFile = $targetDir . pathinfo(basename($_FILES["file"]["name"]), PATHINFO_FILENAME) . "." . $targetFormat;

                // Audio conversion
                if (in_array($sourceFormat, ["wav", "mp3", "webm"]) && in_array($targetFormat, ["wav", "mp3"])) {
                    // Handle webm to audio conversion through ffmpeg first
                    if ($sourceFormat === "webm") {
                        exec("where ffmpeg 2>nul", $output, $returnVar);
                        if ($returnVar === 0) {
                            // First convert webm to wav as intermediate step
                            $tempWav = $targetDir . pathinfo($targetFile, PATHINFO_FILENAME) . ".temp.wav";
                            $ffmpegCommand = "ffmpeg -i " . escapeshellarg($targetFile) . " " . escapeshellarg($tempWav);
                            exec($ffmpegCommand, $output, $returnVar);

                            if ($returnVar !== 0) {
                                echo "Error extracting audio from WebM.";
                                exit;
                            }

                            // Now use the temp WAV for lame conversion if target is MP3
                            if ($targetFormat === "mp3") {
                                exec("where lame 2>nul", $output, $returnVar);
                                if ($returnVar === 0) {
                                    $command = "lame " . escapeshellarg($tempWav) . " " . escapeshellarg($outputFile);
                                } else {
                                    echo "Error: LAME encoder not found. Please install LAME to convert audio files.";
                                    exit;
                                }
                            } else {
                                // If target is WAV, we already have it
                                rename($tempWav, $outputFile);
                                $returnVar = 0; // Success
                            }
                        } else {
                            echo "Error: FFmpeg not found. Please install FFmpeg to convert video files.";
                            exit;
                        }
                    } else {
                        // Regular audio conversion between WAV and MP3
                        exec("where lame 2>nul", $output, $returnVar);
                        if ($returnVar === 0) {
                            if ($targetFormat === "mp3") {
                                $command = "lame " . escapeshellarg($targetFile) . " " . escapeshellarg($outputFile);
                            } else {
                                $command = "lame --decode " . escapeshellarg($targetFile) . " " . escapeshellarg($outputFile);
                            }
                        } else {
                            echo "Error: LAME encoder not found. Please install LAME to convert audio files.";
                            exit;
                        }
                    }
                } elseif (in_array($sourceFormat, ["webm", "mp4"]) && in_array($targetFormat, ["webm", "mp4"])) {
                    // Video conversion
                    exec("where ffmpeg 2>nul", $output, $returnVar);
                    if ($returnVar === 0) {
                        $command = "ffmpeg -i " . escapeshellarg($targetFile) . " " . escapeshellarg($outputFile);
                    } else {
                        echo "Error: FFmpeg not found. Please install FFmpeg to convert video files.";
                        exit;
                    }
                } else {
                    echo "Unsupported conversion: {$sourceFormat} to {$targetFormat}";
                    exit;
                }

                // Execute command if it's set
                if (isset($command)) {
                    exec($command, $output, $returnVar);
                }

                if ($returnVar == 0) {
                    echo "File converted successfully: <a href='$outputFile'>$outputFile</a>";
                } else {
                    echo "Error converting file.";
                }
            } else {
                echo "Error uploading file.";
            }
        } else {
            echo "Invalid file format.";
        }
    }
    ?>
</body>

</html>
