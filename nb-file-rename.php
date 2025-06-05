<?php
// filepath: e:\OrangeJeff\file-renamer.php
// filename: bulk-file-renamer.php
// Version 3.5 - April 10, 2025
// Created by OrangeJeff with the assistance of GitHub Copilot
// Description: Truncate long filenames to a maximum of 16 characters plus extension, ensuring uniqueness and handling conflicts.
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>NetBound Tools: Bulk File Renamer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="shared-styles.css?v=<?php echo time(); ?>">
    <script>
        // Add iframe detection when document loads
        document.addEventListener('DOMContentLoaded', function() {
            // Check if running in iframe
            if (window.self !== window.top) {
                document.body.classList.add('in-iframe');
                document.body.classList.add('iframe-mode');

                // Configure hamburger menu to break out of iframe when clicked
                const hamburgerMenu = document.querySelector('.hamburger-menu');
                if (hamburgerMenu) {
                    hamburgerMenu.setAttribute('target', '_top');
                }

                // Center the content properly in iframe
                const menuContainer = document.querySelector('.menu-container');
                if (menuContainer) {
                    menuContainer.style.margin = '0 auto';
                    menuContainer.style.maxWidth = '768px';
                }
            } else {
                document.body.classList.add('standalone-mode');
            }
        });
    </script>
</head>

<body>
    <div class="menu-container">
        <div class="title-container">
            <h1 class="editor-title">NetBound Tools: File Renamer</h1>
            <a href="main.php?app=nb-file-rename.php" class="hamburger-menu" title="Go to Main Menu">
                <i class="fas fa-bars"></i>
            </a>
        </div>

        <div id="statusBar" class="status-box"></div>

        <div class="button-controls">
            <div class="button-row">
                <button class="command-button" id="openFilesBtn">
                    <i class="fas fa-folder-open"></i> Open Filenames
                </button>
                <button class="command-button" id="restartBtn">
                    <i class="fas fa-sync"></i> Restart
                </button>
            </div>
        </div>

        <div class="work-area">
            <div class="preview-area">
                <!-- Updated content area with responsive width -->
                <div class="content-area standard-container">
                    <div class="file-list-container">
                        <div class="file-list" id="fileList" tabindex="0">
                            <p>Select files to preview and edit filenames...</p>
                        </div>
                        <div class="preview-panel">
                            <div id="truncateOptions" class="truncate-options" style="display: none;">
                                <h3>Truncate Options</h3>
                                <div class="option-row">
                                    <label>Max Length:</label>
                                    <input type="number" id="maxLength" value="16" min="1" max="100">
                                </div>
                                <div class="option-row">
                                    <label>Prefix:</label>
                                    <input type="text" id="prefixText" placeholder="Add prefix">
                                </div>
                                <div class="option-row">
                                    <label>Suffix:</label>
                                    <input type="text" id="suffixText" placeholder="Add suffix">
                                </div>
                                <div class="option-row">
                                    <button id="applyTruncate" class="command-button">
                                        <i class="fas fa-check"></i> Apply
                                    </button>
                                    <button id="cancelTruncate" class="command-button secondary-button">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </div>
                            <div class="preview-container" id="previewContainer">
                                <div class="preview-placeholder">
                                    <i class="fas fa-file fa-3x"></i>
                                    <p>Select a file to preview</p>
                                </div>
                            </div>
                            <div class="preview-info" id="previewInfo"></div>
                        </div>
                    </div>
                </div>

                <!-- Action buttons below the preview area, matching template -->
                <div class="button-controls">
                    <div class="button-row">
                        <button class="command-button" id="renameBtn" disabled>
                            <i class="fas fa-pen"></i> Truncate
                        </button>
                        <button class="command-button" id="cleanBtn" disabled>
                            <i class="fas fa-broom"></i> Clean
                        </button>
                        <button class="command-button" id="deleteBtn" disabled>
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        <button class="command-button" id="doneBtn">
                            <i class="fas fa-check-circle"></i> Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="file" id="fileInput" style="display: none" multiple>

    <script>
        const fileInput = document.getElementById('fileInput');
        const openFilesBtn = document.getElementById('openFilesBtn');
        const restartBtn = document.getElementById('restartBtn');
        const renameBtn = document.getElementById('renameBtn');
        const cleanBtn = document.getElementById('cleanBtn');
        const doneBtn = document.getElementById('doneBtn');
        const deleteBtn = document.getElementById('deleteBtn');
        const fileList = document.getElementById('fileList');
        const statusBar = document.getElementById('statusBar');
        const previewContainer = document.getElementById('previewContainer');
        const previewInfo = document.getElementById('previewInfo');

        let files = [];
        let renamedFiles = [];
        let deleteConfirmationActive = false;
        let currentPreviewIndex = -1;
        let sortByExtension = false;
        let deleteConfirmState = false;

        // Status message handling
        const status = {
            update(message, type = 'info') {
                const messageDiv = document.createElement('div');
                messageDiv.className = `status-message ${type}`;
                messageDiv.textContent = message;
                statusBar.insertBefore(messageDiv, statusBar.firstChild);
                statusBar.scrollTop = 0;
            }
        };

        // Initialize status message
        status.update('Bulk File Renamer ready. Select files or drag them here to begin.', 'info');

        // Initialize drag and drop functionality
        function initDragAndDrop(element) {
            element.addEventListener('dragover', (e) => {
                e.preventDefault();
                element.classList.add('drag-over');
            });

            element.addEventListener('dragleave', () => {
                element.classList.remove('drag-over');
            });

            element.addEventListener('drop', (e) => {
                e.preventDefault();
                element.classList.remove('drag-over');
                if (e.dataTransfer.files.length > 0) {
                    handleFiles(Array.from(e.dataTransfer.files));
                }
            });
        }

        // Initialize drag and drop on the status bar
        initDragAndDrop(statusBar);

        openFilesBtn.addEventListener('click', () => fileInput.click());

        restartBtn.addEventListener('click', () => {
            location.reload();
        });

        fileInput.addEventListener('change', (event) => {
            if (event.target.files.length > 0) {
                handleFiles(Array.from(event.target.files));
            }
        });

        function handleFiles(selectedFiles) {
            files = selectedFiles;
            renamedFiles = [];
            sortFiles();
            displayFiles();
            updateButtonStates();
            status.update(`${files.length} files selected`, 'success');
        }

        function getFileTypeIcon(filename) {
            const extension = filename.substring(filename.lastIndexOf('.')).toLowerCase();
            const imageExts = ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.webp', '.svg'];
            const videoExts = ['.mp4', '.webm', '.ogg', '.mov', '.avi', '.wmv', '.flv', '.mkv'];
            const audioExts = ['.mp3', '.wav', '.ogg', '.m4a', '.flac', '.aac'];

            if (imageExts.some(ext => extension.includes(ext))) {
                return '<i class="fas fa-image file-icon image"></i>';
            } else if (videoExts.some(ext => extension.includes(ext))) {
                return '<i class="fas fa-video file-icon video"></i>';
            } else if (audioExts.some(ext => extension.includes(ext))) {
                return '<i class="fas fa-music file-icon audio"></i>';
            } else {
                return '<i class="fas fa-file file-icon document"></i>';
            }
        }

        function createMediaPreview(file) {
            if (!file) return '';

            const fileType = file.type.split('/')[0];
            const fileURL = URL.createObjectURL(file);
            let previewHTML = '';

            if (fileType === 'image') {
                previewHTML = `<img src="${fileURL}" style="max-width:200px; max-height:150px;">`;
            } else if (fileType === 'video') {
                previewHTML = `<video controls style="max-width:200px; max-height:150px;"><source src="${fileURL}"></video>`;
            } else if (fileType === 'audio') {
                previewHTML = `<audio controls style="width:190px;"><source src="${fileURL}"></audio>`;
            }

            return previewHTML ?
                `<div class="tooltip-content">${previewHTML}</div>` : '';
        }

        // Enhanced function to check for duplicate filenames across all files
        function checkDuplicateNames(newName, currentIndex) {
            // Check regular files list
            const isDuplicate = files.some((file, index) =>
                index !== currentIndex && file.name.toLowerCase() === newName.toLowerCase()
            );

            // Check renamed files list
            const isDuplicateInRenamed = renamedFiles.some((file, index) =>
                index !== currentIndex && file && file.renamed.toLowerCase() === newName.toLowerCase()
            );

            return isDuplicate || isDuplicateInRenamed;
        }

        // Function to generate a unique name if duplicate exists
        function makeUniqueName(baseName, extension) {
            let uniqueName = baseName;
            let counter = 1;

            // Keep adding counter until we find a unique name
            while (checkDuplicateNames(uniqueName + extension, -1)) {
                uniqueName = `${baseName}_${counter}`;
                counter++;
            }

            return uniqueName + extension;
        }

        // Enhanced ensure unique name function
        function ensureUniqueName(name, extension, index) {
            let uniqueName = name;
            let counter = 1;

            // Check both files array and renamedFiles array for duplicates
            while (
                files.some((file, i) => i !== index && file.name.toLowerCase() === (uniqueName + extension).toLowerCase()) ||
                renamedFiles.some((file, i) => file && i !== index && file.renamed.toLowerCase() === (uniqueName + extension).toLowerCase())
            ) {
                uniqueName = name + `_${counter}`;
                counter++;
            }

            return uniqueName;
        }

        function displayFiles() {
            if (files.length === 0) {
                fileList.innerHTML = '<p>Select files or drag them into the status bar to begin...</p>';
                return;
            }

            // Add select all checkbox and sort toggle button at the top of the file list
            let html = `
            <div class="file-controls">
                <div class="select-all-container">
                    <input type="checkbox" id="select-all-checkbox">
                    <span class="select-all-label">Select All Files</span>
                </div>
                <div class="sort-controls">
                    <button class="sort-toggle-btn" id="sortToggle">
                        <i class="fas fa-sort-alpha-down"></i>
                    </button>
                </div>
            </div>`;

            html += files.map((file, index) => {
                const name = file.name;
                const fileIcon = getFileTypeIcon(name);

                return `
                    <div class="file-row ${index === currentPreviewIndex ? 'selected-file' : ''}" data-index="${index}">
                        <input type="checkbox" class="file-checkbox" id="checkbox-${index}" onclick="event.stopPropagation()">
                        <span class="file-icon-container" onclick="selectFileForPreview(${index}); event.stopPropagation();">
                            ${fileIcon}
                        </span>
                        <input type="text" class="file-input" value="${name}" data-original="${name}" onclick="selectFileForPreview(${index}); event.stopPropagation();">
                    </div>
                `;
            }).join('');

            fileList.innerHTML = html;

            // Add event listeners for the whole row to be clickable
            document.querySelectorAll('.file-row').forEach(row => {
                row.addEventListener('click', function() {
                    const index = parseInt(this.dataset.index, 10);
                    selectFileForPreview(index);
                });
            });

            // Add event listener for select all checkbox
            document.getElementById('select-all-checkbox').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.file-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateButtonStates();
            });

            // Add event listener for sort toggle button
            document.getElementById('sortToggle').addEventListener('click', function() {
                sortByExtension = !sortByExtension;
                sortFiles();
                displayFiles();

                // Update button icon and status message
                const icon = this.querySelector('i');
                if (sortByExtension) {
                    icon.className = 'fas fa-sort';
                    status.update('Files sorted by extension', 'info');
                } else {
                    icon.className = 'fas fa-sort-alpha-down';
                    status.update('Files sorted alphabetically', 'info');
                }
            });

            // If there's a current preview index, make sure it stays highlighted
            if (currentPreviewIndex >= 0 && currentPreviewIndex < files.length) {
                highlightSelectedRow(currentPreviewIndex);
            }
        }

        // Function to select a file for preview
        window.selectFileForPreview = function(index) {
            // Update current preview index
            currentPreviewIndex = index;

            const file = files[index];
            if (!file) return;

            // Clear previous preview
            previewContainer.innerHTML = '';

            // Hide truncate options if visible
            document.getElementById('truncateOptions').style.display = 'none';

            // Create preview based on file type
            const fileType = file.type.split('/')[0];
            const fileURL = URL.createObjectURL(file);

            if (fileType === 'image') {
                previewContainer.innerHTML = `<img src="${fileURL}" alt="${file.name}">`;
            } else if (fileType === 'video') {
                previewContainer.innerHTML = `<video controls autoplay><source src="${fileURL}"></video>`;
            } else if (fileType === 'audio') {
                // Enhanced audio player with better visibility
                previewContainer.innerHTML = `
                    <div class="audio-preview">
                        <div class="audio-icon">
                            <i class="fas fa-wave-square fa-4x"></i>
                        </div>
                        <div class="audio-player">
                            <audio controls autoplay style="width: 100%;"><source src="${fileURL}"></audio>
                        </div>
                    </div>`;
            } else {
                // Generic file icon for other types
                previewContainer.innerHTML = `
                    <div class="file-preview">
                        <i class="fas fa-file-alt fa-4x"></i>
                    </div>`;
            }

            // Update file info
            const fileSize = (file.size / 1024).toFixed(2) + ' KB';
            previewInfo.innerHTML = `
                <strong>${file.name}</strong><br>
                Type: ${file.type || 'Unknown'}<br>
                Size: ${fileSize}
            `;

            // Enhanced highlighting for the selected row
            highlightSelectedRow(index);
        };

        // Function to highlight the selected row with enhanced visibility
        function highlightSelectedRow(index) {
            const rows = document.querySelectorAll('.file-row');
            rows.forEach(row => {
                row.classList.remove('selected-file');
                row.style.backgroundColor = '';
            });

            const selectedRow = document.querySelector(`.file-row[data-index="${index}"]`);
            if (selectedRow) {
                selectedRow.classList.add('selected-file');
                selectedRow.style.backgroundColor = '#d4e9ff';

                // Ensure the selected row is visible
                selectedRow.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }
        }

        // Modified function to apply filename changes and check for duplicates
        function applyFilenameChange(index, newName) {
            const row = fileList.querySelector(`[data-index="${index}"]`);
            if (!row) return;

            // Get original file name
            const originalName = files[index].name;

            // Check if the new name would create a duplicate
            if (checkDuplicateNames(newName, index)) {
                // Find extension
                const lastDot = newName.lastIndexOf('.');
                let baseName, extension;

                if (lastDot > -1) {
                    extension = newName.substring(lastDot);
                    baseName = newName.substring(0, lastDot);
                } else {
                    extension = '';
                    baseName = newName;
                }

                // Make name unique
                newName = makeUniqueName(baseName, extension);
                status.update(`Renamed with unique suffix: ${newName}`, 'info');
            }

            // Update the UI to show the rename was successful
            row.style.backgroundColor = '#e8f5e9';
            const input = row.querySelector('.file-input');
            if (input) {
                input.value = newName;
                input.dataset.original = newName;
            }

            // Create a new File object with the new name to update the files array
            try {
                const fileClone = new File([files[index]], newName, {
                    type: files[index].type
                });
                files[index] = fileClone;

                // Remove from renamedFiles since it's been applied in the UI
                delete renamedFiles[index];

                status.update(`Renamed: ${originalName} → ${newName}`, 'success');

                // Update preview info if this is the currently selected file
                if (currentPreviewIndex === index) {
                    const fileInfo = previewInfo.innerHTML.replace(/<strong>.*?<\/strong>/, `<strong>${newName}</strong>`);
                    previewInfo.innerHTML = fileInfo;
                }
            } catch (error) {
                status.update(`Error creating renamed file: ${error.message}`, 'error');
            }
        }

        // Event listener to monitor checkbox changes
        fileList.addEventListener('change', (e) => {
            // Reset delete confirmation when selection changes
            deleteConfirmationActive = false;

            if (e.target.classList.contains('file-checkbox')) {
                updateButtonStates();

                // Update select-all checkbox state based on individual checkboxes
                const checkboxes = document.querySelectorAll('.file-checkbox');
                const selectAllCheckbox = document.getElementById('select-all-checkbox');
                if (selectAllCheckbox) {
                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    const someChecked = Array.from(checkboxes).some(cb => cb.checked);

                    selectAllCheckbox.checked = allChecked;
                    selectAllCheckbox.indeterminate = someChecked && !allChecked;
                }
            }

            if (e.target.classList.contains('file-input')) {
                const row = e.target.closest('.file-row');
                const index = parseInt(row.dataset.index, 10);
                const newName = e.target.value;
                const originalName = e.target.dataset.original;

                // Update renamed files array when input changes
                if (!renamedFiles[index]) {
                    renamedFiles[index] = {
                        original: originalName,
                        renamed: newName
                    };
                } else {
                    renamedFiles[index].renamed = newName;
                }

                status.update(`File renamed to: ${newName}`, 'info');

                // Apply filename changes directly
                applyFilenameChange(index, newName);
            }
        });

        // Also capture manual input directly (for browsers that don't trigger change event until blur)
        fileList.addEventListener('input', (e) => {
            if (e.target.classList.contains('file-input')) {
                const row = e.target.closest('.file-row');
                const index = parseInt(row.dataset.index, 10);
                const newName = e.target.value;
                const originalName = e.target.dataset.original;

                // Update renamed files array when input changes
                if (!renamedFiles[index]) {
                    renamedFiles[index] = {
                        original: originalName,
                        renamed: newName
                    };
                } else {
                    renamedFiles[index].renamed = newName;
                }
            }
        });

        // Done button simply reloads the page
        doneBtn.addEventListener('click', () => {
            status.update('Operation completed.', 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        });

        function removeFiles(indices) {
            // Sort indices in descending order to avoid index issues when removing
            indices.sort((a, b) => b - a);

            indices.forEach(index => {
                files.splice(index, 1);
                if (renamedFiles[index]) {
                    renamedFiles.splice(index, 1);
                }
            });

            sortFiles();
            displayFiles();
            updateButtonStates();
            status.update(`Removed ${indices.length} files from list`, 'info');
        }

        // Delete button functionality
        deleteBtn.addEventListener('click', () => {
            const checkedIndices = getCheckedFiles();

            if (checkedIndices.length === 0) {
                status.update('No files selected for deletion', 'info');
                return;
            }

            if (!deleteConfirmState) {
                // First click - ask for confirmation
                deleteConfirmState = true;
                deleteBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Confirm Delete';
                deleteBtn.classList.add('confirm-delete');
                status.update(`Click delete again to confirm removal of ${checkedIndices.length} files`, 'error');

                // Reset confirmation state after 5 seconds
                setTimeout(() => {
                    if (deleteConfirmState) {
                        deleteConfirmState = false;
                        deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                        deleteBtn.classList.remove('confirm-delete');
                    }
                }, 5000);

            } else {
                // Second click - confirm deletion
                removeFiles(checkedIndices);
                deleteConfirmState = false;
                deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                deleteBtn.classList.remove('confirm-delete');
            }
        });

        // Clean filenames functionality
        cleanBtn.addEventListener('click', () => {
            const checkedIndices = getCheckedFiles();

            if (checkedIndices.length === 0) {
                status.update('No files selected for cleaning', 'info');
                return;
            }

            let cleanedCount = 0;

            checkedIndices.forEach(index => {
                const file = files[index];
                const originalName = file.name;

                // Clean the filename: replace spaces with underscores, fix multiple extensions
                let cleanName = originalName;

                // Replace spaces and special characters
                cleanName = cleanName.replace(/\s+/g, '_')
                    .replace(/[%#&{}\\<>*?/$!'":@+`|=]/g, '_');

                // Fix multiple dots
                const lastDot = cleanName.lastIndexOf('.');
                if (lastDot > -1) {
                    const baseName = cleanName.substring(0, lastDot).replace(/\./g, '_');
                    const extension = cleanName.substring(lastDot);
                    cleanName = baseName + extension;
                }

                // If filename changed, update it
                if (cleanName !== originalName) {
                    if (!renamedFiles[index]) {
                        renamedFiles[index] = {
                            original: originalName,
                            renamed: cleanName
                        };
                    } else {
                        renamedFiles[index].renamed = cleanName;
                    }
                    cleanedCount++;
                }
            });

            // Update UI with cleaned filenames
            updateFileInputs();

            if (cleanedCount > 0) {
                status.update(`Cleaned ${cleanedCount} filenames`, 'success');
            } else {
                status.update('No filenames needed cleaning', 'info');
            }
        });

        renameBtn.addEventListener('click', () => {
            const checkedIndices = getCheckedFiles();

            if (checkedIndices.length === 0) {
                status.update('No files selected for truncating', 'info');
                return;
            }

            // Show truncate options in the right panel
            const truncateOptions = document.getElementById('truncateOptions');
            const previewContainer = document.getElementById('previewContainer');

            truncateOptions.style.display = 'block';
            previewContainer.style.display = 'none';

            // Set up default values
            document.getElementById('maxLength').value = 16;
            document.getElementById('prefixText').value = '';
            document.getElementById('suffixText').value = '';

            // Handle apply button click
            document.getElementById('applyTruncate').onclick = function() {
                const maxLength = parseInt(document.getElementById('maxLength').value, 10);
                const prefix = document.getElementById('prefixText').value.trim();
                const suffix = document.getElementById('suffixText').value.trim();

                applyTruncateToFiles(checkedIndices, maxLength, prefix, suffix);

                // Hide options and show preview again
                truncateOptions.style.display = 'none';
                previewContainer.style.display = 'flex';
            };

            // Handle cancel button click
            document.getElementById('cancelTruncate').onclick = function() {
                truncateOptions.style.display = 'none';
                previewContainer.style.display = 'flex';
            };
        });

        // Function to apply truncate with options to selected files
        function applyTruncateToFiles(indices, maxLength, prefix, suffix) {
            if (!maxLength || maxLength < 1) maxLength = 16;

            let renamedCount = 0;

            indices.forEach(index => {
                const file = files[index];
                const name = file.name;
                const lastDot = name.lastIndexOf('.');

                // Handle files with and without extensions
                let baseName, extension;
                if (lastDot > -1) {
                    extension = name.substring(lastDot);
                    baseName = name.substring(0, lastDot);
                } else {
                    extension = '';
                    baseName = name;
                }

                // Apply truncation while preserving space for prefix and suffix
                const availableSpace = Math.max(1, maxLength - prefix.length - suffix.length);
                let truncatedName = baseName.slice(0, availableSpace);

                // Apply prefix and suffix
                truncatedName = prefix + truncatedName + suffix;

                // Ensure the name is unique
                truncatedName = ensureUniqueName(truncatedName, extension, index);
                const newName = truncatedName + extension;

                // Update UI
                const row = fileList.querySelector(`[data-index="${index}"]`);
                if (row) {
                    const input = row.querySelector('.file-input');
                    if (input && input.value !== newName) {
                        input.value = newName;
                        applyFilenameChange(index, newName);
                        renamedCount++;
                    }
                }
            });

            status.update(`Truncated ${renamedCount} filenames with custom settings`, 'success');
        }

        function getCheckedFiles() {
            const checkedIndices = [];
            const checkboxes = document.querySelectorAll('.file-checkbox');

            checkboxes.forEach((checkbox, index) => {
                if (checkbox.checked) {
                    checkedIndices.push(index);
                }
            });

            return checkedIndices;
        }

        function updateButtonStates() {
            const hasFiles = files.length > 0;
            const checkedFiles = getCheckedFiles();
            const hasChecked = checkedFiles.length > 0;

            // Update button states
            renameBtn.disabled = !hasChecked;
            cleanBtn.disabled = !hasChecked;
            deleteBtn.disabled = !hasChecked;

            // Update status with selection count
            if (hasFiles && hasChecked) {
                status.update(`${checkedFiles.length} of ${files.length} files selected`, 'info');
            }
        }

        function updateFileInputs() {
            const rows = fileList.querySelectorAll('.file-row');
            rows.forEach((row, index) => {
                const input = row.querySelector('.file-input');
                if (renamedFiles[index]) {
                    input.value = renamedFiles[index].renamed;
                    row.style.backgroundColor = '#e3f2fd';
                }
            });
        }

        // Modified function to handle the renaming UI without attempting actual file system changes
        function applyFilenameChange(index, newName) {
            const row = fileList.querySelector(`[data-index="${index}"]`);
            if (!row) return;

            // Get original file name
            const originalName = files[index].name;

            // Update the UI to show the rename was successful
            row.style.backgroundColor = '#e8f5e9';
            const input = row.querySelector('.file-input');
            if (input) {
                input.dataset.original = newName;
            }

            // Create a new File object with the new name to update the files array
            try {
                const fileClone = new File([files[index]], newName, {
                    type: files[index].type
                });
                files[index] = fileClone;

                // Remove from renamedFiles since it's been applied in the UI
                delete renamedFiles[index];

                status.update(`Renamed: ${originalName} → ${newName}`, 'success');
            } catch (error) {
                status.update(`Error creating renamed file: ${error.message}`, 'error');
            }
        }

        // Replace the Clean Names functionality to handle special characters better
        cleanBtn.addEventListener('click', () => {
            const checkedIndices = getCheckedFiles();

            if (checkedIndices.length === 0) {
                status.update('No files selected for cleaning', 'info');
                return;
            }

            let cleanedCount = 0;

            checkedIndices.forEach(index => {
                const file = files[index];
                const originalName = file.name;

                // Clean the filename with improved handling
                let cleanName = originalName;

                // Get extension
                const lastDot = cleanName.lastIndexOf('.');
                let extension = '';
                let baseName = cleanName;

                if (lastDot > -1) {
                    extension = cleanName.substring(lastDot);
                    baseName = cleanName.substring(0, lastDot);
                }

                // Clean the base name
                baseName = baseName
                    .replace(/\s+/g, '_') // Replace spaces with underscores
                    .replace(/[%#&{}\\<>*?/$!'":@+`|=]/g, '_') // Replace special chars
                    .replace(/\.+/g, '_') // Replace any dots in basename with underscores
                    .replace(/_+/g, '_') // Collapse multiple underscores
                    .replace(/^_|_$/g, ''); // Remove leading/trailing underscores

                // Reassemble filename with extension
                cleanName = baseName + extension;

                // If filename changed, update it
                if (cleanName !== originalName) {
                    // Directly apply the change to the UI
                    const row = fileList.querySelector(`[data-index="${index}"]`);
                    if (row) {
                        const input = row.querySelector('.file-input');
                        if (input) {
                            input.value = cleanName;
                            row.style.backgroundColor = '#e3f2fd';

                            // Register the change
                            applyFilenameChange(index, cleanName);
                        }
                    }
                    cleanedCount++;
                }
            });

            if (cleanedCount > 0) {
                status.update(`Cleaned ${cleanedCount} filenames`, 'success');
            } else {
                status.update('No filenames needed cleaning', 'info');
            }
        });

        // Modify the rename button to apply changes immediately without trying to access the file system
        renameBtn.addEventListener('click', () => {
            const checkedIndices = getCheckedFiles();

            if (checkedIndices.length === 0) {
                status.update('No files selected for renaming', 'info');
                return;
            }

            // Process only checked files
            let renamedCount = 0;
            checkedIndices.forEach(index => {
                const file = files[index];
                const name = file.name;
                const lastDot = name.lastIndexOf('.');

                // Handle files with and without extensions
                let baseName, extension;
                if (lastDot > -1) {
                    extension = name.substring(lastDot);
                    baseName = name.substring(0, lastDot);
                } else {
                    extension = '';
                    baseName = name;
                }

                let truncatedName = baseName.slice(0, 16);
                truncatedName = ensureUniqueName(truncatedName, extension, index);
                const newName = truncatedName + extension;

                // Update UI
                const row = fileList.querySelector(`[data-index="${index}"]`);
                if (row) {
                    const input = row.querySelector('.file-input');
                    if (input) {
                        input.value = newName;

                        // Apply the rename directly to the UI
                        applyFilenameChange(index, newName);
                    }
                }
                renamedCount++;
            });

            status.update(`Truncated ${renamedCount} filenames to 16 characters`, 'success');
        });

        function getCheckedFiles() {
            const checkedIndices = [];
            const checkboxes = document.querySelectorAll('.file-checkbox');

            checkboxes.forEach((checkbox, index) => {
                if (checkbox.checked) {
                    checkedIndices.push(index);
                }
            });

            return checkedIndices;
        }

        // Sort files alphabetically or by extension
        function sortFiles() {
            files.sort((a, b) => {
                if (sortByExtension) {
                    const extA = a.name.split('.').pop().toLowerCase();
                    const extB = b.name.split('.').pop().toLowerCase();
                    if (extA < extB) return -1;
                    if (extA > extB) return 1;
                    return a.name.localeCompare(b.name); // If extensions are the same, sort alphabetically
                } else {
                    return a.name.localeCompare(b.name); // Sort alphabetically
                }
            });
        }

        // Add keyboard navigation
        fileList.addEventListener('keydown', function(e) {
            if (files.length === 0) return;

            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();

                let newIndex;
                if (currentPreviewIndex === -1) {
                    // If nothing is selected, select the first or last item
                    newIndex = e.key === 'ArrowDown' ? 0 : files.length - 1;
                } else {
                    // Otherwise move up or down
                    newIndex = currentPreviewIndex + (e.key === 'ArrowDown' ? 1 : -1);

                    // Handle wrap around
                    if (newIndex < 0) newIndex = files.length - 1;
                    if (newIndex >= files.length) newIndex = 0;
                }

                selectFileForPreview(newIndex);
            }
        });

        // Ensure any clicks outside the file elements reset the delete confirmation
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#deleteBtn') && deleteConfirmState) {
                deleteConfirmState = false;
                deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                deleteBtn.classList.remove('confirm-delete');
            }
        });
    </script>

    <?php
    // Just echo success to avoid errors
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "success";
        exit;
    }
    ?>
</body>

</html>
