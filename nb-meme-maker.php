<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Meme Maker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="shared-styles.css?v=<?php echo time(); ?>">
    <style>
        /* Basic styles for Meme Maker - Adapt as needed */
        .meme-container {
            display: flex;
            flex-direction: column;
            /* Main layout: controls top, image center */
            align-items: center;
            padding: 10px;
            gap: 10px;
            /* Space between elements */
        }

        .image-display-area {
            position: relative;
            /* Needed for absolute positioning of text overlay */
            width: 100%;
            max-width: 500px;
            /* Adjust max width as needed */
            min-height: 300px;
            /* Placeholder height */
            border: 1px dashed #ccc;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #000000;
            /* Start with black background */
            overflow: hidden;
            /* Hide parts of image if larger than container */
        }

        .image-display-area img {
            display: block;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            /* Scale image while preserving aspect ratio */
        }

        .text-overlay {
            position: absolute;
            cursor: move;
            /* Indicate text is draggable */
            text-align: center;
            font-weight: bold;
            color: white;
            text-shadow: 2px 2px 0 #000, -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 0px 0 #000, -2px 0px 0 #000, 0px 2px 0 #000, 0px -2px 0 #000;
            /* Basic meme outline */
            /* Default position - will be updated by JS */
            top: 10%;
            left: 50%;
            transform: translateX(-50%);
            white-space: pre-wrap;
            /* Allow line breaks */
            width: 90%;
            /* Prevent text overflowing horizontally */
            user-select: none;
            /* Prevent text selection during drag */
        }

        .text-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            border-radius: 5px;
            width: 100%;
            max-width: 600px;
            /* Match controls width */
        }

        .text-controls label,
        .text-controls select,
        .text-controls input,
        .text-controls textarea {
            margin-right: 5px;
        }

        .text-controls textarea {
            width: 100%;
            /* Make textarea take full width */
            min-height: 50px;
            box-sizing: border-box;
            /* Include padding/border in width */
            margin-top: 5px;
        }

        .text-controls .control-group {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Style for phone size buttons */
        .size-button.active {
            background-color: #4CAF50;
            /* Highlight active size */
            color: white;
        }

        /* Hide file input */
        input[type="file"] {
            display: none;
        }
    </style>
</head>

<body>
    <div class="menu-container">
        <div class="title-container">
            <h1 class="editor-title">NetBound Tools: Meme Maker</h1>
            <a href="main.php?app=tool-template.php" class="hamburger-menu" title="Go to Main Menu">
                <i class="fas fa-bars"></i>
            </a>
        </div>

        <div id="statusBox" class="status-box">
            <!-- Status messages will appear here -->
        </div>

        <div class="button-controls">
            <div class="button-row">
                <button type="button" class="command-button" id="btnOpenImage">
                    <i class="fas fa-folder-open"></i> Open Image
                </button>
                <button type="button" class="command-button size-button" id="btnSizePortrait" data-width="360" data-height="640">
                    <i class="fas fa-mobile-alt"></i> Portrait
                </button>
                <button type="button" class="command-button size-button" id="btnSizeLandscape" data-width="640" data-height="360">
                    <i class="fas fa-mobile-alt fa-rotate-90"></i> Landscape
                </button>
                <button type="button" class="command-button size-button active" id="btnSizeOriginal">
                    <i class="fas fa-image"></i> Original Size
                </button>
                <button type="button" class="command-button" id="btnSaveMeme">
                    <i class="fas fa-save"></i> Save Meme
                </button>
                <button type="button" class="command-button" id="btnAddOnline" title="Add to Online Slideshow (Not Implemented)">
                    <i class="fas fa-cloud-upload-alt"></i> Add Online
                </button>
            </div>
        </div>

        <!-- Image Display Area -->
        <div id="imageDisplayArea" class="image-display-area">
            <img id="memeImage" src="" alt="Load an image to start">
            <div id="textOverlay" class="text-overlay" style="font-size: 48px; font-family: Impact, sans-serif;">Your Text Here</div>
        </div>

        <div class="meme-container">
            <!-- Text Controls -->
            <div class="text-controls">
                <textarea id="textInput" placeholder="Enter meme text here..."></textarea>
                <div class="control-group">
                    <label for="fontSelect">Font:</label>
                    <select id="fontSelect">
                        <option value="Impact, sans-serif" style="font-family: Impact, sans-serif;">Impact</option>
                        <option value="'Arial Black', sans-serif" style="font-family: 'Arial Black', sans-serif;">Arial Black</option>
                        <option value="'Comic Sans MS', cursive, sans-serif" style="font-family: 'Comic Sans MS', cursive, sans-serif;">Comic Sans MS</option>
                        <option value="Arial, sans-serif" style="font-family: Arial, sans-serif;">Arial</option>
                        <option value="Verdana, sans-serif" style="font-family: Verdana, sans-serif;">Verdana</option>
                        <!-- Add more fonts as needed -->
                    </select>
                </div>
                <div class="control-group">
                    <label for="fontSize">Size:</label>
                    <input type="number" id="fontSize" value="48" min="10" max="200">
                </div>
                <div class="control-group">
                    <label for="fontColor">Color:</label>
                    <input type="color" id="fontColor" value="#FFFFFF"> <!-- Default white -->
                </div>
                <div class="control-group">
                    <label for="outlineColor">Outline:</label>
                    <input type="color" id="outlineColor" value="#000000"> <!-- Default black -->
                </div>
            </div>
        </div>

        <!-- Hidden file input -->
        <input type="file" id="fileInput" accept="image/*">
    </div>

    <script>
        // Basic state and DOM elements
        const memeApp = {
            imageLoaded: false,
            currentImageSrc: null,
            originalWidth: 0,
            originalHeight: 0,
            displayWidth: 0,
            displayHeight: 0,
            text: 'Your Text Here',
            font: 'Impact, sans-serif',
            fontSize: 48,
            fontColor: '#FFFFFF',
            outlineColor: '#000000',
            textPosition: {
                x: 50,
                y: 10
            }, // Percentage based: x from left, y from top
            isDragging: false,
            dragStart: {
                x: 0,
                y: 0
            },
            currentTextElementPos: {
                x: 0,
                y: 0
            }
        };

        const memeEls = {
            statusBox: document.getElementById('statusBox'),
            btnOpenImage: document.getElementById('btnOpenImage'),
            fileInput: document.getElementById('fileInput'),
            imageDisplayArea: document.getElementById('imageDisplayArea'),
            memeImage: document.getElementById('memeImage'),
            textOverlay: document.getElementById('textOverlay'),
            textInput: document.getElementById('textInput'),
            fontSelect: document.getElementById('fontSelect'),
            fontSize: document.getElementById('fontSize'),
            fontColor: document.getElementById('fontColor'),
            outlineColor: document.getElementById('outlineColor'),
            btnSaveMeme: document.getElementById('btnSaveMeme'),
            btnAddOnline: document.getElementById('btnAddOnline'),
            btnSizePortrait: document.getElementById('btnSizePortrait'),
            btnSizeLandscape: document.getElementById('btnSizeLandscape'),
            btnSizeOriginal: document.getElementById('btnSizeOriginal'),
            sizeButtons: document.querySelectorAll('.size-button')
        };

        // --- Status Updates ---
        function updateStatus(message, type = 'info') {
            const id = 'msg_' + Math.random().toString(36).substr(2, 9);
            const messageDiv = document.createElement('div');
            messageDiv.id = id;
            messageDiv.className = `message ${type} latest`;
            messageDiv.textContent = message;

            document.querySelectorAll('.message.latest').forEach(msg => msg.classList.remove('latest'));
            memeEls.statusBox.appendChild(messageDiv);
            memeEls.statusBox.scrollTop = memeEls.statusBox.scrollHeight; // Scroll to bottom
            return id;
        }

        // --- Image Handling ---
        function handleImageLoad(event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    memeApp.currentImageSrc = e.target.result;
                    memeEls.memeImage.onload = () => {
                        // Store original dimensions once image metadata is loaded
                        memeApp.originalWidth = memeEls.memeImage.naturalWidth;
                        memeApp.originalHeight = memeEls.memeImage.naturalHeight;
                        memeApp.imageLoaded = true;
                        // Set initial display size to original
                        setDisplaySize(null, null); // Use original size
                        updateStatus(`Image "${file.name}" loaded (${memeApp.originalWidth}x${memeApp.originalHeight}).`, 'success');
                        memeEls.textInput.value = memeApp.text; // Set default text in input
                        updateTextOverlay(); // Apply initial text style
                    };
                    memeEls.memeImage.src = memeApp.currentImageSrc;
                    memeEls.memeImage.alt = file.name;
                }
                reader.readAsDataURL(file);
            } else {
                updateStatus('Please select a valid image file.', 'error');
            }
        }

        // --- Display Size Control ---
        function setDisplaySize(width, height) {
            if (!memeApp.imageLoaded) return;

            let targetWidth, targetHeight;

            if (width && height) {
                // Fixed size (Portrait/Landscape)
                targetWidth = width;
                targetHeight = height;
                memeEls.imageDisplayArea.style.maxWidth = width + 'px';
                memeEls.imageDisplayArea.style.height = height + 'px';
            } else {
                // Original size (or default max)
                targetWidth = memeApp.originalWidth;
                targetHeight = memeApp.originalHeight;
                // Use a sensible max-width for display, but store original dimensions
                const displayMaxWidth = memeEls.memeImage.parentElement.clientWidth || 600; // Get container width
                memeEls.imageDisplayArea.style.maxWidth = displayMaxWidth + 'px';
                // Calculate proportional height based on max-width
                const aspectRatio = memeApp.originalHeight / memeApp.originalWidth;
                memeEls.imageDisplayArea.style.height = Math.min(memeApp.originalHeight, displayMaxWidth * aspectRatio) + 'px';

            }
            // Store the *intended* display dimensions for canvas saving
            memeApp.displayWidth = targetWidth;
            memeApp.displayHeight = targetHeight;

            // Update active button state
            memeEls.sizeButtons.forEach(btn => btn.classList.remove('active'));
            if (width && height) {
                if (width > height) document.getElementById('btnSizeLandscape').classList.add('active');
                else document.getElementById('btnSizePortrait').classList.add('active');
            } else {
                document.getElementById('btnSizeOriginal').classList.add('active');
            }

            // Re-apply text position based on new container size
            applyTextPosition();
        }


        // --- Text Overlay Handling ---
        function updateTextOverlay() {
            if (!memeApp.imageLoaded) return;

            memeApp.text = memeEls.textInput.value;
            memeApp.font = memeEls.fontSelect.value;
            memeApp.fontSize = parseInt(memeEls.fontSize.value, 10);
            memeApp.fontColor = memeEls.fontColor.value;
            memeApp.outlineColor = memeEls.outlineColor.value;

            const overlay = memeEls.textOverlay;
            overlay.textContent = memeApp.text;
            overlay.style.fontFamily = memeApp.font;
            overlay.style.fontSize = memeApp.fontSize + 'px';
            overlay.style.color = memeApp.fontColor;

            // Generate text-shadow for outline effect
            const outlineWidth = Math.max(1, Math.round(memeApp.fontSize / 20)); // Scale outline width slightly
            const shadows = [
                `${outlineWidth}px ${outlineWidth}px 0 ${memeApp.outlineColor}`,
                `-${outlineWidth}px -${outlineWidth}px 0 ${memeApp.outlineColor}`,
                `${outlineWidth}px -${outlineWidth}px 0 ${memeApp.outlineColor}`,
                `-${outlineWidth}px ${outlineWidth}px 0 ${memeApp.outlineColor}`,
                `${outlineWidth}px 0px 0 ${memeApp.outlineColor}`,
                `-${outlineWidth}px 0px 0 ${memeApp.outlineColor}`,
                `0px ${outlineWidth}px 0 ${memeApp.outlineColor}`,
                `0px -${outlineWidth}px 0 ${memeApp.outlineColor}`
            ];
            overlay.style.textShadow = shadows.join(', ');

            // Re-apply position in case text size changed anchor point
            applyTextPosition();
        }

        function applyTextPosition() {
            const overlay = memeEls.textOverlay;
            const container = memeEls.imageDisplayArea;
            // Calculate pixel position based on percentage and container size
            // Note: This positions the top-left corner. Transform adjusts for centering.
            const posX = (memeApp.textPosition.x / 100) * container.clientWidth;
            const posY = (memeApp.textPosition.y / 100) * container.clientHeight;

            overlay.style.left = posX + 'px';
            overlay.style.top = posY + 'px';
            // Use transform to center the text block horizontally at the calculated X position
            overlay.style.transform = 'translateX(-50%)';
        }


        // --- Text Dragging ---
        function startDrag(e) {
            if (!memeApp.imageLoaded || !e.target.matches('#textOverlay')) return;
            memeApp.isDragging = true;
            const overlay = memeEls.textOverlay;
            const container = memeEls.imageDisplayArea;
            const containerRect = container.getBoundingClientRect();

            // Calculate initial offset from the element's top-left corner
            const startX = e.clientX || e.touches[0].clientX;
            const startY = e.clientY || e.touches[0].clientY;
            const overlayRect = overlay.getBoundingClientRect();

            memeApp.dragStart.x = startX - overlayRect.left + containerRect.left; // Adjust for container offset
            memeApp.dragStart.y = startY - overlayRect.top + containerRect.top; // Adjust for container offset

            overlay.style.cursor = 'grabbing';
            document.addEventListener('mousemove', drag);
            document.addEventListener('touchmove', drag, {
                passive: false
            }); // Use passive: false for touchmove
            document.addEventListener('mouseup', endDrag);
            document.addEventListener('touchend', endDrag);
        }

        function drag(e) {
            if (!memeApp.isDragging) return;
            e.preventDefault(); // Prevent page scroll on touch devices

            const overlay = memeEls.textOverlay;
            const container = memeEls.imageDisplayArea;
            const containerRect = container.getBoundingClientRect();

            const currentX = e.clientX || e.touches[0].clientX;
            const currentY = e.clientY || e.touches[0].clientY;

            // Calculate new position relative to the container's top-left corner
            let newX = currentX - containerRect.left - memeApp.dragStart.x;
            let newY = currentY - containerRect.top - memeApp.dragStart.y;

            // Constrain within container bounds (approximate)
            newX = Math.max(0, Math.min(newX, container.clientWidth - overlay.offsetWidth / 2)); // Adjust for centering transform?
            newY = Math.max(0, Math.min(newY, container.clientHeight - overlay.offsetHeight));

            // Update element position directly during drag
            overlay.style.left = newX + 'px';
            overlay.style.top = newY + 'px';
            overlay.style.transform = 'translateX(0)'; // Temporarily remove transform during direct pixel drag

            // Store current pixel position for potential endDrag update
            memeApp.currentTextElementPos = {
                x: newX,
                y: newY
            };
        }

        function endDrag() {
            if (!memeApp.isDragging) return;
            memeApp.isDragging = false;
            const overlay = memeEls.textOverlay;
            const container = memeEls.imageDisplayArea;

            // Convert final pixel position back to percentage for responsiveness
            const finalX = memeApp.currentTextElementPos.x + (overlay.offsetWidth / 2); // Account for centering
            const finalY = memeApp.currentTextElementPos.y;

            memeApp.textPosition.x = (finalX / container.clientWidth) * 100;
            memeApp.textPosition.y = (finalY / container.clientHeight) * 100;

            // Clamp percentages
            memeApp.textPosition.x = Math.max(0, Math.min(100, memeApp.textPosition.x));
            memeApp.textPosition.y = Math.max(0, Math.min(100, memeApp.textPosition.y));


            overlay.style.cursor = 'move';
            document.removeEventListener('mousemove', drag);
            document.removeEventListener('touchmove', drag);
            document.removeEventListener('mouseup', endDrag);
            document.removeEventListener('touchend', endDrag);

            // Re-apply position using percentages and transform for centering
            applyTextPosition();
        }


        // --- Saving ---
        function saveMeme() {
            if (!memeApp.imageLoaded) {
                updateStatus('Please load an image first.', 'warning');
                return;
            }

            updateStatus('Generating meme...', 'info');

            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            // Determine canvas dimensions based on selected size
            const targetWidth = memeApp.displayWidth || memeApp.originalWidth;
            const targetHeight = memeApp.displayHeight || memeApp.originalHeight;

            canvas.width = targetWidth;
            canvas.height = targetHeight;

            // Draw the image onto the canvas, scaled to fit the target dimensions
            ctx.drawImage(memeEls.memeImage, 0, 0, targetWidth, targetHeight);

            // --- Apply text styling to canvas ---
            ctx.font = `${memeApp.fontSize}px ${memeApp.font}`;
            ctx.fillStyle = memeApp.fontColor;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle'; // Adjust as needed (top, bottom, middle)

            // Calculate text position on canvas based on percentage
            const textCanvasX = (memeApp.textPosition.x / 100) * canvas.width;
            // Adjust Y position based on textBaseline choice. 'middle' works well here.
            const textCanvasY = (memeApp.textPosition.y / 100) * canvas.height + (memeApp.fontSize / 3); // Small adjustment often needed

            // Apply outline by drawing text multiple times with offset
            const outlineWidth = Math.max(1, Math.round(memeApp.fontSize / 20));
            ctx.strokeStyle = memeApp.outlineColor;
            ctx.lineWidth = outlineWidth * 2; // Canvas stroke is centered, so double the desired visual width
            ctx.lineJoin = 'round'; // Nicer corners for outline

            // Draw outline first (stroke) then fill
            ctx.strokeText(memeApp.text, textCanvasX, textCanvasY);
            ctx.fillText(memeApp.text, textCanvasX, textCanvasY);


            // --- Trigger download ---
            try {
                const dataURL = canvas.toDataURL('image/png'); // Or 'image/jpeg'
                const link = document.createElement('a');
                link.download = `meme_${Date.now()}.png`;
                link.href = dataURL;
                link.click();
                updateStatus('Meme saved successfully!', 'success');
            } catch (error) {
                console.error("Error saving canvas:", error);
                updateStatus('Error saving meme. See console for details.', 'error');
            }
        }

        // --- Event Listeners ---
        function setupEventListeners() {
            memeEls.btnOpenImage.addEventListener('click', () => memeEls.fileInput.click());
            memeEls.fileInput.addEventListener('change', handleImageLoad);

            // Text controls listeners
            memeEls.textInput.addEventListener('input', updateTextOverlay);
            memeEls.fontSelect.addEventListener('change', updateTextOverlay);
            memeEls.fontSize.addEventListener('input', updateTextOverlay);
            memeEls.fontColor.addEventListener('input', updateTextOverlay);
            memeEls.outlineColor.addEventListener('input', updateTextOverlay);

            // Size buttons
            memeEls.btnSizePortrait.addEventListener('click', () => setDisplaySize(
                parseInt(memeEls.btnSizePortrait.dataset.width, 10),
                parseInt(memeEls.btnSizePortrait.dataset.height, 10)
            ));
            memeEls.btnSizeLandscape.addEventListener('click', () => setDisplaySize(
                parseInt(memeEls.btnSizeLandscape.dataset.width, 10),
                parseInt(memeEls.btnSizeLandscape.dataset.height, 10)
            ));
            memeEls.btnSizeOriginal.addEventListener('click', () => setDisplaySize(null, null)); // Use null for original

            // Text dragging listeners
            memeEls.textOverlay.addEventListener('mousedown', startDrag);
            memeEls.textOverlay.addEventListener('touchstart', startDrag, {
                passive: false
            }); // Use passive: false for touchstart

            // Save button
            memeEls.btnSaveMeme.addEventListener('click', saveMeme);

            // Placeholder for Add Online
            memeEls.btnAddOnline.addEventListener('click', () => {
                updateStatus('Online slideshow functionality is not yet implemented.', 'warning');
            });

            // Update text position if window is resized
            window.addEventListener('resize', () => {
                if (memeApp.imageLoaded) {
                    // Recalculate display area size if it's based on container width
                    if (document.querySelector('.size-button.active')?.id === 'btnSizeOriginal') {
                        setDisplaySize(null, null);
                    }
                    applyTextPosition(); // Reapply position based on potentially new container size
                }
            });
        }

        // --- Initial Setup ---
        function initMemeMaker() {
            memeEls.statusBox.innerHTML = ''; // Clear status box
            updateStatus('Welcome to Meme Maker! Open an image to begin.', 'info');
            setupEventListeners();
            updateTextOverlay(); // Apply default text style initially
            applyTextPosition(); // Apply default text position
        }

        // Run initialization when the DOM is ready
        document.addEventListener('DOMContentLoaded', initMemeMaker);
    </script>
</body>

</html>
