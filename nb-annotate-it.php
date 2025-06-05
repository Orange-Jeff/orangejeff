<!-- Version: 2025-05-04.7 -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annotate-It</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="nb-annotate-it~styles.css">
</head>

<body>
    <div class="menu-container">
        <div class="title-container">
            <h1 class="editor-title">NetBound Tools: Annotate-It</h1>
            <div class="header-buttons">
                <!-- New button to clear status box -->
                <button type="button" id="btnClearStatus" class="header-button" title="Clear Status Messages">
                    <i class="fas fa-broom"></i>
                </button>
                <a href="nb-annotate-it~admin.php" id="adminLink" class="header-button" title="Admin">
                    <i class="fas fa-cog"></i>
                </a>
                <button type="button" class="header-button" id="btnRestart" title="Restart">
                    <i class="fas fa-redo"></i>
                </button>
                <a href="./main.php?app=nb-annotate-it.php" target="_top" class="header-button" title="Go to Main Menu">
                    <i class="fas fa-bars"></i>
                </a>
            </div>
        </div>

        <!-- MODIFIED: Status Box now contains previews -->
        <div id="statusBox" class="status-box">
            <!-- Status messages will appear here -->
            <!-- MOVED: Image Preview Area -->
            <div id="imagePreviewArea" class="image-preview-area">
                <!-- Image previews will be added here by JavaScript -->
            </div>
        </div>

        <div class="button-controls">
            <div class="button-row">
                <button type="button" class="command-button" id="btnOpenScreenshot">
                    <i class="fas fa-folder-open"></i> From Image
                </button>
                <button type="button" class="command-button" id="btnAddWebScreenshot">
                    <i class="fas fa-globe"></i> From Web
                </button>
                <button type="button" class="command-button" id="btnClearAnnotations">
                    <i class="fas fa-eraser"></i> Clear
                </button>
                <div class="button-separator"></div>
                <span class="user-icon blue" id="toggleBlue" title="Blue annotations">
                    <i class="fas fa-comment"></i>
                </span>
                <span class="user-icon orange" id="toggleOrange" title="Orange annotations">
                    <i class="fas fa-comment"></i>
                </span>
            </div>
        </div>

        <div id="annotatorInterface">
            <div class="screen-container preview-container" id="screenContainer"> <!-- Added preview-container class -->
                <img id="screenImage" class="screen-image" alt="">
                <div id="annotationLayer" class="annotation-layer"></div>
            </div>

            <!-- Project domain input (file name input) -->
            <div class="project-domain-row">
                <input type="text" id="projectDomain" class="project-domain-input" placeholder="Project Name / Folder" value="">
                <button type="button" class="project-save-button" id="btnSaveProjectFolder">
                    <i class="fas fa-save"></i> Save Project
                </button>
            </div>

            <!-- Notes sidebar -->
            <div class="annotation-sidebar">
                <div class="sidebar-header">
                    <!-- MODIFIED: Added counter and nav buttons -->
                    <div class="sidebar-header-content">
                        <img id="headerThumbnail" class="annotation-header-thumbnail" src="" alt="Thumbnail" style="display: none;">
                        <div class="sidebar-title-container">
                            <span id="sidebarTitleDate">Notes:</span>
                            <span id="sidebarTitleFilename"></span>
                        </div>
                        <!-- MOVED: Image Counter -->
                        <span class="screen-toolbar-label sidebar-counter" id="imageCounter">No images loaded</span> <!-- Added sidebar-counter class -->
                        <!-- MOVED: Nav Controls -->
                        <div class="nav-controls sidebar-nav"> <!-- Added sidebar-nav class -->
                            <button type="button" class="top-nav-button" id="btnTopPrev" title="Previous Image">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button type="button" class="top-nav-button" id="btnTopNext" title="Next Image">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="annotation-list" id="annotationList">
                    <!-- Notes items will appear here -->
                </div>
            </div>
        </div>

        <!-- Hidden file input -->
        <input type="file" id="fileInput" accept="image/*" style="display: none;" multiple="">
        <!-- Hidden folder input -->
        <input type="file" id="folderInput" webkitdirectory="" directory="" multiple="" style="display: none;">
    </div>

    <script>
        // Main application state
        const app = {
            mode: 'upload', // 'upload' or 'annotate'
            images: [], // Array of image objects
            currentImageIndex: 0,
            activeAnnotation: null, // Currently active annotation
            lastAnnotationId: 0, // For generating unique IDs
            annotationColor: 'blue', // Default annotation color: 'blue' or 'orange'
            isAnnotationEnabled: true, // Whether annotation mode is active
            addingAnnotation: false, // Flag to track if we're in the process of adding an annotation
            projectDomain: '', // Project domain for server saving
            projectId: null, // Server-side project ID
            lastSaved: null, // When the project was last saved to server
            loadedFromServer: false, // Flag to track if project was loaded from server
            sessionUser: 'user_' + Math.random().toString(36).substr(2, 5) // Random user ID for this session
        };

        // DOM references
        const els = {
            statusBox: document.getElementById('statusBox'),
            annotatorInterface: document.getElementById('annotatorInterface'),
            fileInput: document.getElementById('fileInput'),
            folderInput: document.getElementById('folderInput'),
            screenContainer: document.getElementById('screenContainer'),
            screenImage: document.getElementById('screenImage'),
            annotationLayer: document.getElementById('annotationLayer'),
            annotationList: document.getElementById('annotationList'),
            imageCounter: document.getElementById('imageCounter'),
            toggleBlue: document.getElementById('toggleBlue'),
            toggleOrange: document.getElementById('toggleOrange'),
            btnOpenScreenshot: document.getElementById('btnOpenScreenshot'),
            btnRestart: document.getElementById('btnRestart'),
            btnTopPrev: document.getElementById('btnTopPrev'),
            btnTopNext: document.getElementById('btnTopNext'),
            btnSaveProjectFolder: document.getElementById('btnSaveProjectFolder'),
            projectDomain: document.getElementById('projectDomain'),
            headerThumbnail: document.getElementById('headerThumbnail'),
            sidebarTitleDate: document.getElementById('sidebarTitleDate'),
            sidebarTitleFilename: document.getElementById('sidebarTitleFilename'),
            btnScrollTop: document.getElementById('btnScrollTop'),
            btnScrollBottom: document.getElementById('btnScrollBottom'),
            btnClearAnnotations: document.getElementById('btnClearAnnotations'),
            imagePreviewArea: document.getElementById('imagePreviewArea'),
            btnAddWebScreenshot: document.getElementById('btnAddWebScreenshot'),
            adminLink: document.getElementById('adminLink'),
            hamburgerMenu: document.querySelector('.hamburger-menu'),
            btnClearStatus: document.getElementById('btnClearStatus') // Add reference to the new button
        };

        // Status message handler
        const status = {
            update(message, type = 'info') {
                const id = 'msg_' + Math.random().toString(36).substr(2, 9);

                const messageDiv = document.createElement('div');
                messageDiv.id = id;
                messageDiv.className = `message ${type} latest`;
                messageDiv.textContent = message;

                // Remove latest class from all messages
                document.querySelectorAll('.message.latest').forEach(msg => {
                    msg.classList.remove('latest');
                });

                // CHANGED: Append to bottom instead of inserting at the top
                els.statusBox.appendChild(messageDiv);

                // ADDED: Scroll to bottom after adding new message
                els.statusBox.scrollTop = els.statusBox.scrollHeight;

                return id;
            },

            // New function to clear all status messages
            clear() {
                // Keep the imagePreviewArea but remove all status messages
                const imagePreviewArea = els.imagePreviewArea;
                els.statusBox.innerHTML = '';
                els.statusBox.appendChild(imagePreviewArea);
                status.update('Status messages cleared', 'info');
            }
        };

        // Function to check for project ID in URL
        function checkForProjectInUrl() {
            const urlParams = new URLSearchParams(window.location.search);

            // Check for the proof parameter
            const proofParam = urlParams.get('proof'); // Get the original 'proof' value

            if (proofParam) {
                // Pass the original proofParam directly to loadProjectFromServer
                loadProjectFromServer(proofParam);
                return;
            }

            // Fallback to the old format for backward compatibility
            const projectId = urlParams.get('nbproject');
            if (projectId) {
                // Send the old ID using the 'proof' parameter name for consistency
                // The PHP script's fallback logic should handle this.
                loadProjectFromServer(projectId);
            }
        }

        // Define the missing initApp function
        function initApp() {
            status.update('Initializing...', 'info');

            // Debug logging to help diagnose frame detection issues
            console.log("initApp - URL:", window.location.href);
            console.log("initApp - Search params:", window.location.search);
            console.log("initApp - Referrer:", document.referrer);
            console.log("initApp - Parent:", window.parent !== window ? "Has parent frame" : "No parent frame");

            // Enhanced detection logic for different loading scenarios
            const urlParams = new URLSearchParams(window.location.search);
            const appParam = urlParams.get('app');
            const proofParam = urlParams.get('proof');
            const isActuallyInIframe = isInIframe();

            // Log all detection factors
            console.log("Detection factors:", {
                appParam,
                proofParam,
                isActuallyInIframe,
                referrer: document.referrer,
                parentEq: window.parent === window
            });

            // Check three different loading conditions with improved detection
            // 1. Loaded via main.php - check app parameter AND referrer
            // 2. Loaded standalone with proof parameter - customer review mode
            // 3. Regular standalone mode
            const isAppParamMatch = appParam === 'nb-annotate-it.php' || (appParam && appParam.includes('nb-annotate-it.php'));
            const isMainPhpReferrer = document.referrer.includes('main.php');
            const isLoadedViaMainApp = isAppParamMatch || (isActuallyInIframe && isMainPhpReferrer);
            const isLoadedWithProof = proofParam && proofParam.length > 0;

            console.log("Final detection results:", {
                isAppParamMatch,
                isMainPhpReferrer,
                isLoadedViaMainApp,
                isLoadedWithProof
            });

            // Apply appropriate UI modifications based on detection
            if (isLoadedViaMainApp) {
                // NetBound Tools Menu integration mode
                document.body.classList.add('in-iframe');
                status.update('Detected NetBound Tools Menu', 'info');
                if (els.hamburgerMenu) {
                    els.hamburgerMenu.style.display = 'none';
                }
            } else if (isLoadedWithProof) {
                // Customer Review mode via URL parameter
                status.update('Customer Review version loaded', 'info');
                // Keep hamburger menu visible for navigation
            } else if (isActuallyInIframe) {
                // Fallback iframe detection
                document.body.classList.add('in-iframe');
                status.update('Running in embedded mode', 'info');
                if (els.hamburgerMenu) {
                    els.hamburgerMenu.style.display = 'none';
                }
            } else {
                // Standard standalone mode
                status.update('Stand-alone version initialized', 'info');
            }

            // Check for admin file and set up the admin gear icon
            if (els.adminLink) {
                // Only show admin button if not in review mode (no proof parameter)
                if (!isLoadedWithProof) {
                    // Check if admin file exists by trying to fetch it
                    fetch('nb-annotate-it~admin.php', { method: 'HEAD' })
                        .then(response => {
                            if (response.ok) {
                                // Admin file exists, show the button and set the link
                                els.adminLink.href = 'nb-annotate-it~admin.php';
                                els.adminLink.style.display = 'flex';
                                console.log("Admin file found, showing settings button");
                            } else {
                                console.log("Admin file not found, hiding settings button");
                            }
                        })
                        .catch(error => {
                            console.log("Error checking for admin file:", error);
                        });
                } else {
                    console.log("In review mode, hiding settings button");
                }
            }

            // Setup Event Listeners - Adding null checks
            if (els.btnOpenScreenshot) {
                els.btnOpenScreenshot.addEventListener('click', () => els.fileInput.click());
            }

            // Add event listener for the new Clear Status button
            if (els.btnClearStatus) {
                els.btnClearStatus.addEventListener('click', () => status.clear());
            }

            if (els.fileInput) {
                els.fileInput.addEventListener('change', handleFileSelect);
            }

            if (els.folderInput) {
                els.folderInput.addEventListener('change', handleFolderSelect);
            }

            if (els.btnRestart) {
                els.btnRestart.addEventListener('click', () => {
                    if (confirm('Are you sure you want to restart? All unsaved changes will be lost.')) {
                        // Remove URL parameters when restarting
                        window.location.href = window.location.pathname;
                    }
                });
            }

            if (els.btnTopPrev) {
                els.btnTopPrev.addEventListener('click', () => showImage(app.currentImageIndex - 1));
            }

            if (els.btnTopNext) {
                els.btnTopNext.addEventListener('click', () => showImage(app.currentImageIndex + 1));
            }

            if (els.toggleBlue) {
                els.toggleBlue.addEventListener('click', () => setAnnotationColor('blue'));
            }

            if (els.toggleOrange) {
                els.toggleOrange.addEventListener('click', () => setAnnotationColor('orange'));
            }

            if (els.screenContainer) {
                els.screenContainer.addEventListener('click', handleImageClick);
            }

            if (els.btnSaveProjectFolder) {
                els.btnSaveProjectFolder.addEventListener('click', saveProjectToServer);
            }

            if (els.btnClearAnnotations) {
                els.btnClearAnnotations.addEventListener('click', clearAllAnnotationsForCurrentImage);
            }

            // Adding null checks for scroll buttons
            if (els.btnScrollTop) {
                els.btnScrollTop.addEventListener('click', () => {
                    if (els.annotationList) {
                        els.annotationList.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    }
                });
            }

            if (els.btnScrollBottom) {
                els.btnScrollBottom.addEventListener('click', () => {
                    if (els.annotationList) {
                        els.annotationList.scrollTo({
                            top: els.annotationList.scrollHeight,
                            behavior: 'smooth'
                        });
                    }
                });
            }

            // Also add event listener for web screenshot button here
            if (els.btnAddWebScreenshot) {
                els.btnAddWebScreenshot.addEventListener('click', addWebPageScreenshot);
            }

            // Setup drag and drop
            setupDragAndDrop();

            // Initial UI State
            setAnnotationColor(app.annotationColor); // Set initial color toggle state
            updateNavButtons();
            updateImageCounter();

            // CHANGED: Show annotator interface by default
            if (els.annotatorInterface) {
                els.annotatorInterface.style.display = 'flex';
            }

            // Check URL for existing project
            checkForProjectInUrl();

            // Set initial status message
            status.update('Ready. Open image(s) or load a project via URL.', 'info');
        }

        // Define the missing isInIframe function
        function isInIframe() {
            try {
                return window.self !== window.top;
            } catch (e) {
                return true;
            }
        }

        document.addEventListener('DOMContentLoaded', initApp);

        // --- Add Web Page Screenshot logic ---
        function addWebPageScreenshot() {
            const url = prompt('Enter the full URL of the web page to capture (e.g. https://yahoo.com):');
            if (!url) return;
            status.update('Requesting screenshot for ' + url + '...', 'info');
            // ScreenshotMachine API (demo key, 1200xfull)
            const apiKey = 'aaa446';
            const apiUrl = `https://api.screenshotmachine.com?key=${apiKey}&url=${encodeURIComponent(url)}&dimension=1200x3000`; // Preload image to check if valid
            const img = new window.Image();
            img.crossOrigin = 'anonymous';
            img.onload = function() {
                const imageId = 'web_' + Date.now();
                app.images.push({
                    id: imageId,
                    name: url,
                    src: apiUrl,
                    annotations: []
                });
                addImageToPreviewArea(apiUrl, imageId, url);
                showImage(app.images.length - 1);
                status.update('Web page screenshot added.', 'success');
            };
            img.onerror = function() {
                // ENHANCED: More detailed error message
                status.update('Failed to load screenshot. Possible reasons: Invalid URL format, website blocking screenshots, API limit reached (using demo key), or the service is temporarily unavailable. Check console for details.', 'error');
                console.error('ScreenshotMachine Error: Failed to load image from API for URL:', url);
            };
            img.src = apiUrl;
        }

        // Handle file selection from file input
        function handleFileSelect(e) {
            const files = e.target.files;
            if (!files || files.length === 0) return;

            status.update(`Processing ${files.length} file(s)...`, 'info');

            // Process each file
            Array.from(files).forEach(file => {
                if (!file.type.startsWith('image/')) {
                    status.update(`Skipping non-image file: ${file.name}`, 'warning');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(event) {
                    const imageId = 'img_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
                    app.images.push({
                        id: imageId,
                        name: file.name,
                        src: event.target.result,
                        annotations: []
                    });

                    // Add to preview area
                    addImageToPreviewArea(event.target.result, imageId, file.name);

                    // Show the first image if this is the first one added
                    if (app.images.length === 1) {
                        showImage(0);
                    }
                };
                reader.onerror = function() {
                    status.update(`Error reading file: ${file.name}`, 'error');
                };
                reader.readAsDataURL(file);
            });
        }

        // Handle folder selection
        function handleFolderSelect(e) {
            const files = e.target.files;
            if (!files || files.length === 0) return;

            status.update(`Processing folder with ${files.length} file(s)...`, 'info');

            // Filter only image files and sort by name
            const imageFiles = Array.from(files).filter(file => file.type.startsWith('image/'))
                .sort((a, b) => a.name.localeCompare(b.name));

            if (imageFiles.length === 0) {
                status.update('No image files found in the selected folder.', 'warning');
                return;
            }

            status.update(`Found ${imageFiles.length} image files.`, 'info');

            // Process each image file
            imageFiles.forEach(file => {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const imageId = 'img_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
                    app.images.push({
                        id: imageId,
                        name: file.name,
                        src: event.target.result,
                        annotations: []
                    });

                    // Add to preview area
                    addImageToPreviewArea(event.target.result, imageId, file.name);

                    // Show the first image if this is the first one added
                    if (app.images.length === 1) {
                        showImage(0);
                    }
                };
                reader.onerror = function() {
                    status.update(`Error reading file: ${file.name}`, 'error');
                };
                reader.readAsDataURL(file);
            });
        }

        // Helper function to add image thumbnail to preview area
        function addImageToPreviewArea(src, id, name) {
            const thumbnail = document.createElement('div');
            thumbnail.className = 'image-preview-thumbnail';
            thumbnail.dataset.id = id;
            thumbnail.title = name;

            const img = document.createElement('img');
            img.src = src;
            img.alt = name;
            img.addEventListener('click', () => {
                // Find the index of this image
                const index = app.images.findIndex(img => img.id === id);
                if (index !== -1) {
                    showImage(index);
                }
            });

            thumbnail.appendChild(img);
            els.imagePreviewArea.appendChild(thumbnail);

            // Update the image counter
            updateImageCounter();
        }

        // Update image counter display
        function updateImageCounter() {
            if (app.images.length === 0) {
                els.imageCounter.textContent = 'No images loaded';
                return;
            }
            els.imageCounter.textContent = `Image ${app.currentImageIndex + 1} of ${app.images.length}`;
        }

        // Show image at specified index
        function showImage(index) {
            if (app.images.length === 0) return;

            // Ensure index is within bounds
            if (index < 0) index = 0;
            if (index >= app.images.length) index = app.images.length - 1;

            app.currentImageIndex = index;
            const image = app.images[index];

            // Update main image display
            els.screenImage.src = image.src;
            els.screenImage.alt = image.name;

            // Update sidebar title
            els.sidebarTitleFilename.textContent = image.name;

            // Update header thumbnail if it exists
            if (els.headerThumbnail) {
                els.headerThumbnail.src = image.src;
                els.headerThumbnail.style.display = 'block';
            }

            // Update nav buttons
            updateNavButtons();

            // Update image counter
            updateImageCounter();

            // Highlight current thumbnail in preview area
            const thumbnails = els.imagePreviewArea.querySelectorAll('.image-preview-thumbnail');
            thumbnails.forEach(thumb => {
                if (thumb.dataset.id === image.id) {
                    thumb.classList.add('active');
                } else {
                    thumb.classList.remove('active');
                }
            });

            // Clear any active annotation
            app.activeAnnotation = null;

            // Load annotations for this image
            loadAnnotations(image.annotations || []);

            status.update(`Showing image: ${image.name}`, 'info');
        }

        // Load annotations for the current image
        function loadAnnotations(annotations) {
            // Clear existing annotation markers and list
            els.annotationLayer.innerHTML = '';
            els.annotationList.innerHTML = '';

            if (!annotations || annotations.length === 0) {
                els.annotationList.innerHTML = '<div class="annotation-list-empty">No annotations for this image.</div>';
                return;
            }

            // Add each annotation to the UI
            annotations.forEach(annotation => {
                addAnnotationToUI(annotation);
            });
        }

        // Add annotation to UI (both marker and list item)
        function addAnnotationToUI(annotation) {
            // Create marker on image
            const marker = document.createElement('div');
            marker.className = `annotation-marker ${annotation.color || 'blue'}`;
            if (annotation.completed) marker.classList.add('completed');
            marker.dataset.id = annotation.id;
            marker.style.left = annotation.x + '%';
            marker.style.top = annotation.y + '%';

            // Add annotation number to marker
            const number = document.createElement('span');
            number.className = 'annotation-number-badge';
            number.textContent = annotation.id;
            marker.appendChild(number);

            // Add marker to layer
            els.annotationLayer.appendChild(marker);

            // Add click handler to marker
            marker.addEventListener('click', (e) => {
                e.stopPropagation();
                activateAnnotation(annotation.id);
            });

            // Create list item
            const listItem = createAnnotationListItem(annotation);
            els.annotationList.appendChild(listItem);
        }

        // Create annotation list item
        function createAnnotationListItem(annotation) {
            const listItem = document.createElement('div');
            listItem.className = `annotation-item ${annotation.color || 'blue'}`;
            listItem.dataset.id = annotation.id;
            if (annotation.completed) listItem.classList.add('completed');

            // Create the two-column layout container
            const layoutDiv = document.createElement('div');
            layoutDiv.className = 'annotation-layout';

            // Left column: marker badge
            const leftCol = document.createElement('div');
            leftCol.className = 'annotation-left-column';

            const markerBadge = document.createElement('span');
            markerBadge.className = `annotation-marker-badge ${annotation.color || 'blue'}`;
            markerBadge.textContent = annotation.id;
            leftCol.appendChild(markerBadge);

            // Right column: title, date, text, buttons
            const rightCol = document.createElement('div');
            rightCol.className = 'annotation-right-column';

            // Header section with title and buttons
            const headerDiv = document.createElement('div');
            headerDiv.className = 'annotation-item-header';

            // Title section
            const titleSection = document.createElement('div');
            titleSection.className = 'annotation-title-section';

            const titleText = document.createElement('span');
            titleText.className = 'annotation-title-text';
            titleText.textContent = `Note #${annotation.id}`;
            titleSection.appendChild(titleText);

            const dateText = document.createElement('span');
            dateText.className = 'annotation-date-text';
            dateText.textContent = annotation.date || new Date().toLocaleString();
            titleSection.appendChild(dateText);

            headerDiv.appendChild(titleSection);

            // Action buttons
            const buttonsDiv = document.createElement('div');
            buttonsDiv.className = 'annotation-buttons';

            const editBtn = document.createElement('button');
            editBtn.className = 'annotation-action-btn';
            editBtn.innerHTML = '<i class="fas fa-edit"></i>';
            editBtn.title = 'Edit';
            editBtn.onclick = () => editAnnotation(annotation.id);
            buttonsDiv.appendChild(editBtn);

            const completeBtn = document.createElement('button');
            completeBtn.className = 'annotation-action-btn';
            completeBtn.innerHTML = '<i class="fas fa-check"></i>';
            completeBtn.title = annotation.completed ? 'Mark as incomplete' : 'Mark as complete';
            completeBtn.onclick = () => toggleAnnotationComplete(annotation.id);
            buttonsDiv.appendChild(completeBtn);

            const replyBtn = document.createElement('button');
            replyBtn.className = 'annotation-action-btn';
            replyBtn.innerHTML = '<i class="fas fa-reply"></i>';
            replyBtn.title = 'Reply';
            replyBtn.onclick = () => {
                const addReplyDiv = document.querySelector(`.annotation-item[data-id="${annotation.id}"] .add-reply`);
                if (addReplyDiv) {
                    addReplyDiv.style.display = 'block';
                    addReplyDiv.querySelector('.reply-input').focus();
                }
            };
            buttonsDiv.appendChild(replyBtn);

            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'annotation-action-btn delete-btn';
            deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
            deleteBtn.title = 'Delete';
            deleteBtn.onclick = () => deleteAnnotation(annotation.id);
            buttonsDiv.appendChild(deleteBtn);

            headerDiv.appendChild(buttonsDiv);
            rightCol.appendChild(headerDiv);

            // Annotation text
            const textDiv = document.createElement('div');
            textDiv.className = 'annotation-item-text';
            textDiv.textContent = annotation.text || '';
            rightCol.appendChild(textDiv);

            // Replies container
            if (annotation.replies && annotation.replies.length > 0) {
                const repliesContainer = document.createElement('div');
                repliesContainer.className = 'replies-container';

                // Add each reply
                annotation.replies.forEach(reply => {
                    const replyItem = document.createElement('div');
                    replyItem.className = 'reply-item';

                    const replyAuthor = document.createElement('div');
                    replyAuthor.className = 'reply-author';
                    replyAuthor.textContent = reply.author || 'User';
                    replyItem.appendChild(replyAuthor);

                    const replyText = document.createElement('div');
                    replyText.className = 'reply-text';
                    replyText.textContent = reply.text;
                    replyItem.appendChild(replyText);

                    repliesContainer.appendChild(replyItem);
                });

                rightCol.appendChild(repliesContainer);
            }

            // Add reply section
            const addReplyDiv = document.createElement('div');
            addReplyDiv.className = 'add-reply';
            addReplyDiv.style.display = 'none'; // Hide by default

            const replyAuthor = document.createElement('div');
            replyAuthor.className = 'reply-author';
            replyAuthor.textContent = 'You';
            addReplyDiv.appendChild(replyAuthor);

            const replyInput = document.createElement('textarea');
            replyInput.className = 'reply-input';
            replyInput.placeholder = 'Add a reply...';
            addReplyDiv.appendChild(replyInput);

            const replyButtons = document.createElement('div');
            replyButtons.className = 'reply-buttons';

            const addReplyBtn = document.createElement('button');
            addReplyBtn.className = 'reply-button';
            addReplyBtn.textContent = 'Reply';
            addReplyBtn.onclick = () => addReply(annotation.id, replyInput.value);
            replyButtons.appendChild(addReplyBtn);

            const cancelReplyBtn = document.createElement('button');
            cancelReplyBtn.className = 'reply-cancel-button';
            cancelReplyBtn.textContent = 'Cancel';
            cancelReplyBtn.onclick = () => {
                addReplyDiv.style.display = 'none';
                replyInput.value = '';
            };
            replyButtons.appendChild(cancelReplyBtn);

            addReplyDiv.appendChild(replyButtons);
            rightCol.appendChild(addReplyDiv);

            // Add "Reply" button to show the reply form
            const showReplyBtn = document.createElement('button');
            showReplyBtn.className = 'reply-button';
            showReplyBtn.textContent = 'Reply';
            showReplyBtn.onclick = () => {
                addReplyDiv.style.display = 'block';
                replyInput.focus();
            };

            const replyButtonContainer = document.createElement('div');
            replyButtonContainer.className = 'reply-buttons';
            replyButtonContainer.appendChild(showReplyBtn);
            rightCol.appendChild(replyButtonContainer);

            // Add columns to layout
            layoutDiv.appendChild(leftCol);
            layoutDiv.appendChild(rightCol);
            listItem.appendChild(layoutDiv);

            return listItem;
        }

        // Set active annotation color
        function setAnnotationColor(color) {
            app.annotationColor = color;

            // Update UI to show active color
            els.toggleBlue.classList.toggle('active', color === 'blue');
            els.toggleOrange.classList.toggle('active', color === 'orange');
        }

        // Update navigation buttons based on current state
        function updateNavButtons() {
            const hasPrev = app.currentImageIndex > 0;
            const hasNext = app.currentImageIndex < app.images.length - 1;

            els.btnTopPrev.disabled = !hasPrev;
            els.btnTopNext.disabled = !hasNext;
        }

        // Setup drag and drop functionality
        function setupDragAndDrop() {
            const dropZone = els.statusBox;

            // Prevent default behaviors for drag events
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            // Add visual feedback during drag
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });

            // Handle drop event
            dropZone.addEventListener('drop', handleDrop, false);

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            function highlight() {
                dropZone.classList.add('drag-over');
            }

            function unhighlight() {
                dropZone.classList.remove('drag-over');
            }

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;

                // Handle the dropped files
                handleDroppedFiles(files);
            }
        }

        // Handle dropped files
        function handleDroppedFiles(files) {
            if (!files || files.length === 0) return;

            status.update(`Processing ${files.length} dropped file(s)...`, 'info');

            // Process each file
            Array.from(files).forEach(file => {
                if (!file.type.startsWith('image/')) {
                    status.update(`Skipping non-image file: ${file.name}`, 'warning');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(event) {
                    const imageId = 'img_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
                    app.images.push({
                        id: imageId,
                        name: file.name,
                        src: event.target.result,
                        annotations: []
                    });

                    // Add to preview area
                    addImageToPreviewArea(event.target.result, imageId, file.name);

                    // Show the first image if this is the first one added
                    if (app.images.length === 1) {
                        showImage(0);
                    }
                };
                reader.onerror = function() {
                    status.update(`Error reading file: ${file.name}`, 'error');
                };
                reader.readAsDataURL(file);
            });
        }

        // Handle click on image
        function handleImageClick(e) {
            // Only allow adding annotations when in annotation mode and an image is loaded
            if (!app.isAnnotationEnabled || app.images.length === 0 || app.addingAnnotation) return;

            // Get click coordinates relative to the image accounting for scroll position
            const rect = els.screenContainer.getBoundingClientRect();
            const scrollLeft = els.screenContainer.scrollLeft;
            const scrollTop = els.screenContainer.scrollTop;
            const x = ((e.clientX - rect.left + scrollLeft) / els.screenImage.width) * 100;
            const y = ((e.clientY - rect.top + scrollTop) / els.screenImage.height) * 100;

            // Create a new annotation
            const annotationId = (app.lastAnnotationId + 1).toString();
            app.lastAnnotationId++;

            // Create new annotation object
            const annotation = {
                id: annotationId,
                x: x,
                y: y,
                color: app.annotationColor,
                text: '',
                date: new Date().toLocaleString(),
                replies: [],
                completed: false
            };

            // Add to current image annotations
            const currentImage = app.images[app.currentImageIndex];
            if (!currentImage.annotations) {
                currentImage.annotations = [];
            }
            currentImage.annotations.push(annotation);

            // Add to UI
            addAnnotationToUI(annotation);

            // Activate this annotation and show the edit form
            activateAnnotation(annotationId);
            editAnnotation(annotationId);

            status.update(`Added annotation #${annotationId}`, 'success');
        }

        // Activate an annotation (highlight it)
        function activateAnnotation(id) {
            // Deactivate all annotations
            document.querySelectorAll('.annotation-marker').forEach(marker => {
                marker.classList.remove('active');
            });
            document.querySelectorAll('.annotation-item').forEach(item => {
                item.classList.remove('active');
            });

            // Activate the selected annotation
            const marker = document.querySelector(`.annotation-marker[data-id="${id}"]`);
            const listItem = document.querySelector(`.annotation-item[data-id="${id}"]`);

            if (marker) marker.classList.add('active');
            if (listItem) {
                listItem.classList.add('active');
                // Scroll into view
                listItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            // Update app state
            app.activeAnnotation = id;
        }

        // Edit annotation
        function editAnnotation(id) {
            // Find the annotation in the current image
            const currentImage = app.images[app.currentImageIndex];
            const annotationIndex = currentImage.annotations.findIndex(a => a.id === id);
            if (annotationIndex === -1) return;

            const annotation = currentImage.annotations[annotationIndex];

            // Create popup for editing
            createPostItNote(annotation);
        }

        // Create a post-it note popup for editing an annotation
        function createPostItNote(annotation) {
            // Remove any existing post-it notes
            document.querySelectorAll('.post-it-container').forEach(el => el.remove());

            app.addingAnnotation = true;

            // Create post-it note container
            const postIt = document.createElement('div');
            postIt.className = `post-it-container ${annotation.color}`;
            postIt.dataset.id = annotation.id;

            // Header with title and close button
            const header = document.createElement('div');
            header.className = 'post-it-header';

            const title = document.createElement('div');
            title.className = 'post-it-title';
            title.textContent = `Note #${annotation.id}`;
            header.appendChild(title);

            const controls = document.createElement('div');
            controls.className = 'post-it-controls';

            const closeBtn = document.createElement('button');
            closeBtn.className = 'post-it-control';
            closeBtn.innerHTML = '<i class="fas fa-times"></i>';
            closeBtn.onclick = () => {
                // If the annotation has no text, remove it
                if (!annotation.text || annotation.text.trim() === '') {
                    deleteAnnotation(annotation.id);
                }
                postIt.remove();
                app.addingAnnotation = false;
            };
            controls.appendChild(closeBtn);
            header.appendChild(controls);

            postIt.appendChild(header);

            // Content area with textarea
            const content = document.createElement('div');
            content.className = 'post-it-content';

            const textarea = document.createElement('textarea');
            textarea.className = 'post-it-input';
            textarea.placeholder = 'Enter your note here...';
            textarea.value = annotation.text || '';
            textarea.onchange = () => {
                annotation.text = textarea.value;
                // Update the text in the annotation list item
                const textDiv = document.querySelector(`.annotation-item[data-id="${annotation.id}"] .annotation-item-text`);
                if (textDiv) textDiv.textContent = textarea.value;
            };
            content.appendChild(textarea);

            // Add OK button
            const okButton = document.createElement('button');
            okButton.className = 'post-it-ok-button';
            okButton.innerHTML = 'OK';
            okButton.onclick = () => {
                annotation.text = textarea.value;
                // Update the text in the annotation list item
                const textDiv = document.querySelector(`.annotation-item[data-id="${annotation.id}"] .annotation-item-text`);
                if (textDiv) textDiv.textContent = textarea.value;
                postIt.remove();
                app.addingAnnotation = false;
            };
            content.appendChild(okButton);

            postIt.appendChild(content);

            // Position near the annotation marker
            const marker = document.querySelector(`.annotation-marker[data-id="${annotation.id}"]`);
            if (marker) {
                const markerRect = marker.getBoundingClientRect();
                const screenRect = els.screenContainer.getBoundingClientRect();

                // Calculate position - place to the right of the marker if space allows
                let left = markerRect.right - screenRect.left + 10;

                // If it would go off the right edge, place it on the left side of the marker
                if (left + 300 > screenRect.width) {
                    left = markerRect.left - screenRect.left - 310;
                }

                // If it would go off the left edge, place it centrally
                if (left < 0) {
                    left = (screenRect.width - 300) / 2;
                }

                // Calculate top position - center vertically with the marker
                let top = markerRect.top - screenRect.top - 50;

                // If it would go above the top edge, place it below the marker
                if (top < 0) {
                    top = markerRect.bottom - screenRect.top + 10;
                }

                // Apply position
                postIt.style.left = `${left}px`;
                postIt.style.top = `${top}px`;
            } else {
                // Default position if marker not found
                postIt.style.left = '50%';
                postIt.style.top = '50%';
                postIt.style.transform = 'translate(-50%, -50%)';
            }

            // Add to screen container
            els.screenContainer.appendChild(postIt);

            // Show the post-it with animation
            setTimeout(() => {
                postIt.classList.add('visible');
                textarea.focus();
            }, 10);
        }

        // Add reply to annotation
        function addReply(annotationId, text) {
            if (!text || text.trim() === '') return;

            // Find the annotation
            const currentImage = app.images[app.currentImageIndex];
            const annotation = currentImage.annotations.find(a => a.id === annotationId);
            if (!annotation) return;

            // Add reply
            const reply = {
                author: 'You',
                text: text.trim(),
                date: new Date().toLocaleString()
            };

            if (!annotation.replies) annotation.replies = [];
            annotation.replies.push(reply);

            // Update UI
            // Hide the reply form
            const replyForm = document.querySelector(`.annotation-item[data-id="${annotationId}"] .add-reply`);
            if (replyForm) {
                replyForm.style.display = 'none';
                const textarea = replyForm.querySelector('.reply-input');
                if (textarea) textarea.value = '';
            }

            // Add the reply to the UI
            const repliesContainer = document.querySelector(`.annotation-item[data-id="${annotationId}"] .replies-container`);

            // Create container if it doesn't exist
            if (!repliesContainer) {
                const newRepliesContainer = document.createElement('div');
                newRepliesContainer.className = 'replies-container';

                const annotationItem = document.querySelector(`.annotation-item[data-id="${annotationId}"] .annotation-right-column`);
                if (annotationItem) {
                    const replyButtonContainer = annotationItem.querySelector('.reply-buttons');
                    if (replyButtonContainer) {
                        annotationItem.insertBefore(newRepliesContainer, replyButtonContainer);
                    } else {
                        annotationItem.appendChild(newRepliesContainer);
                    }
                }
            }

            // Add the reply to the existing or new container
            const container = document.querySelector(`.annotation-item[data-id="${annotationId}"] .replies-container`) ||
                              document.createElement('div'); // Fallback if not found

            const replyItem = document.createElement('div');
            replyItem.className = 'reply-item';

            const replyAuthor = document.createElement('div');
            replyAuthor.className = 'reply-author';
            replyAuthor.textContent = reply.author;
            replyItem.appendChild(replyAuthor);

            const replyText = document.createElement('div');
            replyText.className = 'reply-text';
            replyText.textContent = reply.text;
            replyItem.appendChild(replyText);

            container.appendChild(replyItem);

            status.update(`Reply added to note #${annotationId}`, 'success');
        }

        // Toggle annotation complete state
        function toggleAnnotationComplete(id) {
            // Find the annotation
            const currentImage = app.images[app.currentImageIndex];
            const annotation = currentImage.annotations.find(a => a.id === id);
            if (!annotation) return;

            // Toggle state
            annotation.completed = !annotation.completed;

            // Update UI
            const marker = document.querySelector(`.annotation-marker[data-id="${id}"]`);
            const listItem = document.querySelector(`.annotation-item[data-id="${id}"]`);

            if (marker) {
                if (annotation.completed) {
                    marker.classList.add('completed');
                } else {
                    marker.classList.remove('completed');
                }
            }

            if (listItem) {
                if (annotation.completed) {
                    listItem.classList.add('completed');
                } else {
                    listItem.classList.remove('completed');
                }
            }

            status.update(`Note #${id} marked as ${annotation.completed ? 'completed' : 'incomplete'}`, 'success');
        }

        // Delete annotation
        function deleteAnnotation(id) {
            if (!confirm(`Are you sure you want to delete note #${id}?`)) return;

            // Find and remove from data
            const currentImage = app.images[app.currentImageIndex];
            const annotationIndex = currentImage.annotations.findIndex(a => a.id === id);

            if (annotationIndex !== -1) {
                currentImage.annotations.splice(annotationIndex, 1);

                // Remove from UI
                const marker = document.querySelector(`.annotation-marker[data-id="${id}"]`);
                const listItem = document.querySelector(`.annotation-item[data-id="${id}"]`);

                if (marker) marker.remove();
                if (listItem) listItem.remove();

                // Remove any associated post-it
                const postIt = document.querySelector(`.post-it-container[data-id="${id}"]`);
                if (postIt) {
                    postIt.remove();
                    app.addingAnnotation = false;
                }

                // If this was the active annotation, clear it
                if (app.activeAnnotation === id) {
                    app.activeAnnotation = null;
                }

                // If no annotations left, show empty message
                if (currentImage.annotations.length === 0) {
                    els.annotationList.innerHTML = '<div class="annotation-list-empty">No annotations for this image.</div>';
                }

                status.update(`Note #${id} deleted`, 'success');
            }
        }

        // Clear all annotations for current image
        function clearAllAnnotationsForCurrentImage() {
            if (app.images.length === 0) return;

            // Get current image
            const currentImage = app.images[app.currentImageIndex];

            if (!currentImage.annotations || currentImage.annotations.length === 0) {
                status.update('No annotations to clear', 'info');
                return;
            }

            if (!confirm(`Are you sure you want to clear all ${currentImage.annotations.length} annotations from this image?`)) return;

            // Clear annotations from data
            currentImage.annotations = [];

            // Clear from UI
            els.annotationLayer.innerHTML = '';
            els.annotationList.innerHTML = '<div class="annotation-list-empty">No annotations for this image.</div>';

            // Clear any post-it notes
            document.querySelectorAll('.post-it-container').forEach(el => el.remove());
            app.addingAnnotation = false;

            // Clear active annotation
            app.activeAnnotation = null;

            status.update('All annotations cleared', 'success');
        }

        // Save project to server
        function saveProjectToServer() {
            if (app.images.length === 0) {
                status.update('No images to save. Add images first.', 'warning');
                return;
            }

            // Get project domain/name from input
            const projectDomain = els.projectDomain.value.trim();
            if (!projectDomain) {
                status.update('Please enter a project name', 'error');
                els.projectDomain.focus();
                return;
            }

            app.projectDomain = projectDomain;

            const saveMsg = status.update('Saving project...', 'info');

            // Prepare data for saving
            const saveData = {
                projectDomain: projectDomain,
                projectId: app.projectId, // May be null for new projects
                images: app.images.map(img => ({
                    id: img.id,
                    name: img.name,
                    src: img.src,
                    annotations: img.annotations || []
                }))
            };

            // Send to server
            fetch('nb-annotate-it~save.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(saveData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update project ID if it's a new project
                    if (data.projectId) {
                        app.projectId = data.projectId;
                    }

                    app.lastSaved = new Date();

                    // Update URL to include this project ID for sharing
                    if (data.shareUrl) {
                        const shareUrl = new URL(data.shareUrl, window.location.href).href;

                        // Update message with share link
                        const successMessage = `Project saved. Share with this link: <a href="${shareUrl}" target="_blank">${shareUrl}</a>`;

                        // Replace loading message with success message
                        if (saveMsg) {
                            const saveMessageEl = document.getElementById(saveMsg);
                            if (saveMessageEl) {
                                saveMessageEl.innerHTML = successMessage;
                                saveMessageEl.className = 'message success latest';
                            } else {
                                status.update(successMessage, 'success');
                            }
                        } else {
                            status.update(successMessage, 'success');
                        }

                        // Add the project ID to the URL without reloading
                        const url = new URL(window.location.href);
                        url.searchParams.set('proof', data.projectId);
                        window.history.replaceState({}, '', url);
                    } else {
                        status.update('Project saved successfully', 'success');
                    }
                } else {
                    status.update(`Error saving project: ${data.error || 'Unknown error'}`, 'error');
                }
            })
            .catch(error => {
                console.error('Save error:', error);
                status.update(`Error saving project: ${error.message}`, 'error');
            });
        }

        // Load project from server
        function loadProjectFromServer(projectId) {
            if (!projectId) return;

            status.update(`Loading project ${projectId}...`, 'info');

            fetch(`nb-annotate-it~load.php?proof=${encodeURIComponent(projectId)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear existing data
                        app.images = [];
                        app.currentImageIndex = 0;
                        app.lastAnnotationId = 0;
                        els.imagePreviewArea.innerHTML = '';

                        // Set project info
                        app.projectId = data.projectId;
                        app.projectDomain = data.projectDomain || '';
                        els.projectDomain.value = app.projectDomain;

                        // Load images
                        if (data.images && data.images.length > 0) {
                            data.images.forEach(img => {
                                // Add to app state
                                app.images.push({
                                    id: img.id,
                                    name: img.name,
                                    src: img.src,
                                    annotations: img.annotations || []
                                });

                                // Track highest annotation ID
                                if (img.annotations && img.annotations.length > 0) {
                                    img.annotations.forEach(ann => {
                                        const annId = parseInt(ann.id);
                                        if (!isNaN(annId) && annId > app.lastAnnotationId) {
                                            app.lastAnnotationId = annId;
                                        }
                                    });
                                }

                                // Add to preview area
                                addImageToPreviewArea(img.src, img.id, img.name);
                            });

                            // Show first image
                            showImage(0);

                            status.update(`Project "${app.projectDomain}" loaded with ${app.images.length} images`, 'success');
                            app.loadedFromServer = true;
                        } else {
                            status.update('Project loaded, but no images found', 'warning');
                        }
                    } else {
                        status.update(`Error loading project: ${data.error || 'Unknown error'}`, 'error');
                    }
                })
                .catch(error => {
                    console.error('Load error:', error);
                    status.update(`Error loading project: ${error.message}`, 'error');
                });
        }
    </script>
</body>

</html>
