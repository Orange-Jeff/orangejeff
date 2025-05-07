<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annotate-It</title> <!-- Changed Title -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="~nb-annotate-it.css">
</head>

<body data-new-gr-c-s-check-loaded="14.1232.0" data-gr-ext-installed="">
    <div class="menu-container">
        <div class="title-container">
            <h1 class="editor-title">NetBound Tools: Annotate-It</h1> <!-- Changed Title -->
            <div class="header-buttons">
                <button id="btnRestart" class="header-button" title="Restart">
                    <i class="fas fa-redo"></i>
                </button>
            </div>
            <a href="./main.php?app=nb-annotate-it.php" target="_top" class="hamburger-menu" title="Go to Main Menu">
                <i class="fas fa-bars"></i>
            </a>
        </div>

        <!-- MODIFIED: Status Box now contains previews -->
        <div id="statusBox" class="status-box">
            <!-- Status messages will appear here -->
            <!-- MOVED: Image Preview Area -->
            <div id="imagePreviewArea" class="image-preview-area">
                <!-- Image previews will be added here by JavaScript -->
            </div>
        </div>

        <!-- Adding missing UI controls -->
        <div class="annotation-controls" style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;">
            <button id="btnOpenScreenshot" class="command-button">
                <i class="fas fa-image"></i> Open Image(s)
            </button>
            <button id="btnAddWebScreenshot" class="command-button">
                <i class="fas fa-globe"></i> Add Web Screenshot
            </button>
            <div class="annotation-color-toggles">
                <button id="toggleBlue" class="color-toggle blue active" title="Blue Annotations">
                    <i class="fas fa-pen"></i>
                </button>
                <button id="toggleOrange" class="color-toggle orange" title="Orange Annotations">
                    <i class="fas fa-pen"></i>
                </button>
            </div>
            <button id="btnClearAnnotations" class="command-button danger-btn">
                <i class="fas fa-trash-alt"></i> Clear All
            </button>
            <div class="scroll-controls">
                <button id="btnScrollTop" class="command-button mini-btn" title="Scroll to Top">
                    <i class="fas fa-arrow-up"></i>
                </button>
                <button id="btnScrollBottom" class="command-button mini-btn" title="Scroll to Bottom">
                    <i class="fas fa-arrow-down"></i>
                </button>
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
            imagePreviewArea: document.getElementById('imagePreviewArea'), // Ref remains valid
            btnAddWebScreenshot: document.getElementById('btnAddWebScreenshot'), // Added reference
            hamburgerMenu: document.querySelector('.hamburger-menu') // Added reference for hamburger menu
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

        // Function to load project from server
        // Argument is now the value of the 'proof' parameter from the URL
        async function loadProjectFromServer(proofParameter) {
            try {
                // Use the proofParameter in the status message
                status.update(`Loading project ${proofParameter}...`, 'info');

                // Use the correct parameter name 'proof' in the fetch URL
                // Also encode the parameter value just in case
                const response = await fetch(`~nb-annotate-it-load.php?proof=${encodeURIComponent(proofParameter)}`);
                const data = await response.json();

                if (data.success) {
                    // Clear current state
                    app.images = [];
                    els.imagePreviewArea.innerHTML = '';
                    els.annotationLayer.innerHTML = '';
                    els.annotationList.innerHTML = '';

                    // Load project data returned from the server
                    app.projectId = data.projectId; // Use the canonical ID from the server
                    app.projectDomain = data.projectDomain || '';
                    app.images = data.images || [];
                    app.loadedFromServer = true;

                    // ADDED: Find the highest annotation ID in all images to set lastAnnotationId
                    let maxAnnotationId = 0;
                    app.images.forEach(img => {
                        if (img.annotations && img.annotations.length > 0) {
                            img.annotations.forEach(ann => {
                                // Parse the ID as a number to compare properly
                                const annId = parseInt(ann.id, 10);
                                if (!isNaN(annId) && annId > maxAnnotationId) {
                                    maxAnnotationId = annId;
                                }
                            });
                        }
                    });

                    // Set lastAnnotationId to the highest found + 1
                    app.lastAnnotationId = maxAnnotationId;
                    console.log(`Set lastAnnotationId to ${app.lastAnnotationId} based on loaded annotations`);

                    // Hide the hamburger menu when loading from URL
                    if (els.hamburgerMenu) {
                        els.hamburgerMenu.style.display = 'none';
                    }

                    // Update UI and make project name read-only
                    els.projectDomain.value = app.projectDomain;
                    els.projectDomain.readOnly = true;

                    renderImagePreviews();

                    if (app.images.length > 0) {
                        showImage(0);
                    }

                    status.update(`Project \"${app.projectDomain}\" loaded successfully.`, 'success');

                    // Update URL to the canonical format using data from the server response
                    const sanitizedName = app.projectDomain.replace(/[^a-zA-Z0-9]/g, '_').toLowerCase();
                    const proofIdFromServer = data.projectId.replace('nbproof_', ''); // Extract ID part from server response
                    const newUrl = `${window.location.pathname}?proof=${sanitizedName}_${proofIdFromServer}`;

                    // Update the URL in the browser bar if it doesn't match the canonical one
                    if (window.location.search !== `?proof=${sanitizedName}_${proofIdFromServer}`) {
                        history.pushState({}, '', newUrl);
                    }
                } else {
                    status.update(`Error: ${data.error}`, 'error');
                }
            } catch (error) {
                console.error('Error loading project:', error);
                status.update('Failed to load project. Server error.', 'error');
            }
        }

        // Function to save project to server
        async function saveProjectToServer() {
            if (app.images.length === 0) {
                status.update('Nothing to save. Please add images first.', 'warning');
                return;
            }

            try {
                const projectName = els.projectDomain.value.trim();

                if (!projectName) {
                    status.update('Please enter a project name before saving.', 'warning');
                    els.projectDomain.focus();
                    return;
                }

                status.update('Saving project...', 'info');

                // Prepare project data
                const projectData = {
                    projectId: app.projectId || '',
                    projectDomain: projectName,
                    images: app.images.map(img => ({
                        id: img.id,
                        name: img.name,
                        src: img.src,
                        annotations: img.annotations || []
                    }))
                };

                // Send to server
                const response = await fetch('~nb-annotate-it-save.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(projectData)
                });

                const data = await response.json();

                if (data.success) {
                    app.projectId = data.projectId;
                    app.lastSaved = data.lastSaved;
                    app.loadedFromServer = true;

                    // Update URL with the new format: ?proof=projectname_id
                    const sanitizedName = projectName.replace(/[^a-zA-Z0-9]/g, '_').toLowerCase();
                    const proofId = data.projectId.replace('nbproof_', '');
                    const newUrl = `${window.location.pathname}?proof=${sanitizedName}_${proofId}`;
                    history.pushState({}, '', newUrl);

                    // Create the share URL with the new format
                    const shareUrl = `${window.location.origin}${newUrl}`;

                    status.update(`Project saved successfully. Share URL: ${shareUrl}`, 'success');

                    // Create a clickable link in the status box

                    const linkMsg = document.createElement('div');
                    linkMsg.className = 'message info';
                    linkMsg.innerHTML = `Share this link with clients: <a href="${shareUrl}" target="_blank">${shareUrl}</a>`;
                    els.statusBox.appendChild(linkMsg);
                    els.statusBox.scrollTop = els.statusBox.scrollHeight;
                } else {
                    status.update(`Error: ${data.error}`, 'error');
                }
            } catch (error) {
                console.error('Error saving project:', error);
                status.update('Failed to save project. Server error.', 'error');
            }
        }

        // Helper function to make URLs clickable and preserve line breaks
        function formatText(text) {
            if (!text) return 'No text'; // Display 'No text' if empty

            // Replace URLs with clickable links
            const urlRegex = /(https?:\/\/[^\s]+)/g;
            let formattedText = text.replace(urlRegex, url => `<a href="${url}" target="_blank" rel="noopener noreferrer">${url}</a>`);

            // Replace line breaks with <br> tags
            formattedText = formattedText.replace(/\n/g, '<br>');

            return formattedText;
        }

        // Create a single item in the sidebar list
        function createAnnotationListItem(annotation) {
            const item = document.createElement('div');
            item.className = `annotation-item ${annotation.color} ${annotation.completed ? 'completed' : ''}`;
            item.dataset.id = annotation.id;

            // Find the index of the annotation in the current image
            const currentImage = app.images[app.currentImageIndex];
            const annotationIndex = currentImage.annotations.findIndex(a => a.id === annotation.id);
            const number = annotationIndex !== -1 ? annotationIndex + 1 : '?';

            // Get formatted date - uses MMM DD YYYY format
            const now = new Date();
            const dateOptions = {
                month: 'short',
                day: '2-digit',
                year: 'numeric'
            };
            const formattedDate = now.toLocaleDateString('en-US', dateOptions);

            // MODIFIED: Better layout with standalone dot and indented text
            item.innerHTML = `
                <div class="annotation-layout">
                    <div class="annotation-left-column">
                        <div class="annotation-item-header">
                            <span class="annotation-marker-badge ${annotation.color}">${number}</span>
                            <div class="annotation-date-text">${formattedDate}</div>
                        </div>
                        <div class="annotation-item-text">${formatText(annotation.text)}</div>
                    </div>
                    <div class="annotation-right-column">
                        <div class="annotation-buttons">
                            <button class="annotation-action-btn complete-btn" title="Mark as ${annotation.completed ? 'Incomplete' : 'Complete'}">
                                <i class="fas fa-${annotation.completed ? 'undo' : 'check'}"></i>
                            </button>
                            <button class="annotation-action-btn delete-btn" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                            <button class="annotation-action-btn reply-btn" title="Reply">
                                <i class="fas fa-reply"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            // Add replies if they exist
            if (annotation.replies && annotation.replies.length > 0) {
                const repliesContainer = document.createElement('div');
                repliesContainer.className = 'replies-container';

                annotation.replies.forEach(reply => {
                    const replyItem = document.createElement('div');
                    replyItem.className = 'reply-item';
                    replyItem.innerHTML = `
                        <div class="reply-author">${reply.author || 'User'}: </div>
                        <div class="reply-text">${formatText(reply.text)}</div>
                    `;
                    repliesContainer.appendChild(replyItem);
                });

                const annotationLeftColumn = item.querySelector('.annotation-left-column');
                annotationLeftColumn.appendChild(repliesContainer);
            }

            // Hide reply input area by default - on its own line
            const replyArea = document.createElement('div');
            replyArea.style.display = 'none'; // Initially hidden
            replyArea.className = 'add-reply';
            replyArea.innerHTML = `
                <div class="reply-author">User ${app.sessionUser.split('_')[1]}:</div>
                <textarea class="reply-input" placeholder="Add a reply..."></textarea>
                <div class="reply-buttons">
                    <button class="reply-button">Send</button>
                    <button class="reply-cancel-button">Cancel</button>
                </div>
            `;

            item.querySelector('.annotation-left-column').appendChild(replyArea);

            // Click handler for the annotation text (now part of the header)
            const annotationTextContainer = item.querySelector('.annotation-item-header');
            if (annotationTextContainer) {
                annotationTextContainer.addEventListener('click', (e) => {
                    // Prevent triggering if a button inside was clicked
                    if (!e.target.closest('button') && !e.target.closest('.annotation-marker-badge')) {
                        showPostItNote(annotation);
                    }
                });
            }

            // Event handlers for action buttons
            const completeBtn = item.querySelector('.complete-btn');
            if (completeBtn) {
                completeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    toggleAnnotationCompletion(annotation.id);
                });
            }

            const replyBtn = item.querySelector('.reply-btn');
            const replyInput = item.querySelector('.add-reply');
            if (replyBtn && replyInput) {
                replyBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    // Toggle reply input visibility
                    replyInput.style.display = replyInput.style.display === 'none' ? 'flex' : 'none';
                    if (replyInput.style.display === 'flex') {
                        replyInput.querySelector('textarea').focus();

                        // Scroll the annotation item into view
                        item.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest'
                        });
                    }
                });
            }

            const deleteBtn = item.querySelector('.delete-btn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (confirm('Are you sure you want to delete this annotation?')) {
                        // Assuming removeAnnotation exists and returns true on success
                        if (removeAnnotation(annotation)) {
                            status.update('Annotation removed.', 'success');
                        }
                    }
                });
            }

            // Add event listener for the reply button
            const sendReplyBtn = item.querySelector('.reply-button');
            if (sendReplyBtn) {
                sendReplyBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const textarea = item.querySelector('.reply-input');
                    if (textarea && textarea.value.trim()) {
                        addReplyToAnnotation(annotation.id, textarea.value.trim());
                        textarea.value = '';
                        replyInput.style.display = 'none'; // Hide reply area after sending
                    }
                });
            }

            // Add event listener for the cancel button
            const cancelReplyBtn = item.querySelector('.reply-cancel-button');
            if (cancelReplyBtn) {
                cancelReplyBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const textarea = item.querySelector('.reply-input');
                    textarea.value = '';
                    replyInput.style.display = 'none'; // Hide reply area
                });
            }

            els.annotationList.appendChild(item);

            return item;
        }

        // Update the text for a specific annotation
        function updateAnnotationText(annotationId, newText) {
            const currentImage = app.images[app.currentImageIndex];

            if (!currentImage || !currentImage.annotations) {
                console.error(`[updateAnnotationText] Could not find current image or annotations for ID ${annotationId}.`);
                return false;
            }

            const annotation = currentImage.annotations.find(a => a.id === annotationId);
            if (annotation) {
                annotation.text = newText;
                const marker = els.annotationLayer.querySelector(`.annotation-marker[data-id="${annotationId}"]`);
                if (marker) {
                    marker.title = newText || `Annotation ${annotationId}`;
                }
                updateAnnotationList(); // Update the list immediately after text change
                return true;
            } else {
                console.error(`[updateAnnotationText] Could not find annotation with ID ${annotationId} in current image.`);
                return false;
            }
        }

        // Placeholder for updating the sidebar list (replace with actual implementation)
        function updateAnnotationList() {
            const currentImage = app.images[app.currentImageIndex];
            els.annotationList.innerHTML = ''; // Clear list first

            if (currentImage && currentImage.annotations) {
                // Iterate through annotations and prepend them to the list
                currentImage.annotations.forEach(annotation => {
                    const item = createAnnotationListItem(annotation);
                    els.annotationList.prepend(item); // Prepend to show newest first
                });
            }
        }

        // Show post-it note for an annotation
        function showPostItNote(annotation) {
            // Clear any existing post-its first
            const existingPostIts = document.querySelectorAll('.post-it-container');
            existingPostIts.forEach(el => el.remove());

            const marker = els.annotationLayer.querySelector(`.annotation-marker[data-id="${annotation.id}"]`);
            if (!marker) {
                console.error('Marker not found for annotation:', annotation.id);
                app.addingAnnotation = false; // Ensure flag is reset if marker is missing
                return;
            }

            // Create the post-it note HTML
            const postIt = document.createElement('div');
            postIt.className = `post-it-container ${annotation.color} visible`;
            postIt.dataset.id = annotation.id;
            postIt.style.position = 'absolute';

            // Get annotation number/index
            const currentImage = app.images[app.currentImageIndex];
            const annotationIndex = currentImage.annotations.findIndex(a => a.id === annotation.id);
            const number = annotationIndex !== -1 ? annotationIndex + 1 : '?';

            const type = annotation.color === 'blue' ? 'Blue' : 'Orange';

            postIt.innerHTML = `
                <div class="post-it-header">
                    <div class="post-it-title">
                        <span class="annotation-number-badge">${number}.</span>
                        <span class="annotation-color ${annotation.color}"></span>
                        ${type} #${annotation.id}
                    </div>
                    <div class="post-it-controls">
                        <button class="post-it-control" title="Mark as ${annotation.completed ? 'Incomplete' : 'Complete'}">
                            <i class="fas fa-${annotation.completed ? 'undo' : 'check'}"></i>
                        </button>
                        <button class="post-it-control" title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        <button class="post-it-control close-post-it" title="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="post-it-content">
                    <textarea class="post-it-input" placeholder="Add your annotation here...">${annotation.text || ''}</textarea>
                    <button class="command-button save-annotation"><i class="fas fa-check"></i> OK</button>
                </div>
            `;

            if (annotation.replies && annotation.replies.length > 0) {
                const repliesContainer = document.createElement('div');
                repliesContainer.className = 'replies-container post-it-replies';

                annotation.replies.forEach(reply => {
                    const replyItem = document.createElement('div');
                    replyItem.className = 'reply-item';
                    replyItem.innerHTML = `
                        <div class="reply-author">${reply.author || 'User'}</div>
                        <div class="reply-text">${formatText(reply.text)}</div>
                    `;
                    repliesContainer.appendChild(replyItem);
                });

                const postItContent = postIt.querySelector('.post-it-content');
                postItContent.appendChild(repliesContainer);

                // Add reply input directly in post-it
                const replyArea = document.createElement('div');
                replyArea.className = 'add-reply post-it-add-reply';
                replyArea.innerHTML = `
                    <input type="text" class="reply-input" placeholder="Add a reply...">
                    <button class="reply-button">Reply</button>
                `;
                postItContent.appendChild(replyArea);
            }

            els.annotationLayer.appendChild(postIt);

            setTimeout(() => {
                const markerRect = marker.getBoundingClientRect();
                const layerRect = els.annotationLayer.getBoundingClientRect();
                const containerRect = els.screenContainer.getBoundingClientRect();
                const postItRect = postIt.getBoundingClientRect();

                const markerTopInLayer = markerRect.top - layerRect.top;
                const markerLeftInLayer = markerRect.left - layerRect.left;

                let postItTop = markerTopInLayer + (markerRect.height || 15) + 5;
                let postItLeft = markerLeftInLayer + (markerRect.width || 15) + 5;

                const requiredLeftToShowFullyRight = containerRect.right - postItRect.width - 5;
                if (markerRect.left + (markerRect.width || 15) + 5 + postItRect.width > containerRect.right) {
                    postItLeft = markerLeftInLayer - postItRect.width - 5;
                    if (markerRect.left - postItRect.width - 5 < containerRect.left) {
                        postItLeft = containerRect.left - layerRect.left + 5;
                    }
                }

                const requiredTopToShowFullyBottom = containerRect.bottom - postItRect.height - 5;
                if (markerRect.top + (markerRect.height || 15) + 5 + postItRect.height > containerRect.bottom) {
                    postItTop = markerTopInLayer - postItRect.height - 5;
                    if (markerRect.top - postItRect.height - 5 < containerRect.top) {
                        postItTop = containerRect.top - layerRect.top + 5;
                    }
                }

                postItTop = Math.max(0, postItTop);
                postItLeft = Math.max(0, postItLeft);

                postIt.style.top = `${postItTop}px`;
                postIt.style.left = `${postItLeft}px`;

                const textarea = postIt.querySelector('.post-it-input');
                if (textarea) {
                    textarea.focus();
                } else {
                    console.error("[showPostItNote] Could not find textarea to focus.");
                }

            }, 0);

            postIt.addEventListener('click', (e) => {
                e.stopPropagation();
            });

            postIt.querySelector('.close-post-it').addEventListener('click', () => {
                const wasAdding = annotation.text === ''; // Check if we were adding this annotation
                if (wasAdding) {
                    removeAnnotation(annotation); // This resets the flag internally
                } else {
                    postIt.remove();
                    app.addingAnnotation = false; // Explicitly reset flag when closing an existing annotation's post-it
                }
            });

            postIt.querySelector('.post-it-control[title*="Mark as"]').addEventListener('click', () => {
                toggleAnnotationCompletion(annotation.id);
                postIt.remove(); // Close post-it after marking
                app.addingAnnotation = false; // Reset flag
            });

            postIt.querySelector('.post-it-control[title="Delete"]').addEventListener('click', () => {
                removeAnnotation(annotation); // This resets the flag internally
            });

            postIt.querySelector('.save-annotation').addEventListener('click', (e) => {
                const newText = postIt.querySelector('.post-it-input').value;
                if (updateAnnotationText(annotation.id, newText)) {
                    status.update('Annotation saved.', 'success');
                } else {
                    status.update('Failed to save annotation.', 'error');
                }
                postIt.remove(); // Close post-it after attempting save
                app.addingAnnotation = false; // Reset flag
            });

            // Handle reply button in post-it if it exists
            const replyBtn = postIt.querySelector('.reply-button');
            if (replyBtn) {
                replyBtn.addEventListener('click', (e) => {
                    const replyInput = replyBtn.previousElementSibling;

                    if (replyInput && replyInput.value.trim()) {
                        addReplyToAnnotation(annotation.id, replyInput.value.trim());
                        postIt.remove(); // Close the post-it and reopen to show updated replies
                        showPostItNote(annotation);
                    }
                });
            }
        }

        // Remove an annotation
        function removeAnnotation(annotation) {
            const currentImage = app.images[app.currentImageIndex];

            if (!currentImage || !currentImage.annotations) return;

            currentImage.annotations = currentImage.annotations.filter(a => a.id !== annotation.id);

            const marker = document.querySelector(`.annotation-marker[data-id="${annotation.id}"]`);
            const postIt = document.querySelector(`.post-it-container[data-id="${annotation.id}"]`);

            if (marker) marker.remove();
            if (postIt) postIt.remove();

            updateAnnotationList();

            app.addingAnnotation = false;

            const type = annotation.color === 'blue' ? 'Blue' : 'Orange';
            status.update(`${type} annotation #${annotation.id} removed`, 'success');
        }

        // Load annotations for the current image
        function loadAnnotationsForCurrentImage() {
            const currentImage = app.images[app.currentImageIndex];

            els.annotationLayer.innerHTML = '';
            els.annotationList.innerHTML = '';

            if (!currentImage || !currentImage.annotations || currentImage.annotations.length === 0) {
                return;
            }

            currentImage.annotations.forEach(annotation => {
                createAnnotationMarker(annotation);
                createAnnotationListItem(annotation);
            });

            app.addingAnnotation = false; // Reset flag when loading annotations for a new image
        }

        // Check if we're inside an iframe
        function isInIframe() {
            try {
                return window.self !== window.top;
            } catch (e) {
                return true;
            }
        }

        if (isInIframe()) {
            document.body.classList.add('in-iframe');
        }

        // Function to handle file selection
        function handleFileSelect(e) {
            const files = e.target.files;
            if (!files || files.length === 0) {
                status.update('No files selected.', 'warning');
                return;
            }

            status.update(`Processing ${files.length} file(s)...`, 'info');

            // Clear existing images if we're starting fresh
            if (app.images.length === 0) {
                app.images = [];
                els.imagePreviewArea.innerHTML = '';
            }

            let loadedCount = 0;
            const totalFiles = files.length;

            Array.from(files).forEach((file, index) => {
                if (!file.type.startsWith('image/')) {
                    status.update(`Skipping non-image file: ${file.name}`, 'warning');
                    return;
                }

                const reader = new FileReader();

                reader.onload = function(event) {
                    const imageId = 'img_' + Date.now() + '_' + index;
                    app.images.push({
                        id: imageId,
                        name: file.name,
                        src: event.target.result,
                        annotations: []
                    });

                    loadedCount++;

                    // Create image preview
                    addImageToPreviewArea(event.target.result, imageId, file.name);

                    // When all files are loaded
                    if (loadedCount === totalFiles) {
                        status.update(`Loaded ${loadedCount} image(s) successfully.`, 'success');
                        showImage(app.images.length - 1); // Show the last image
                        app.mode = 'annotate';
                        els.annotatorInterface.style.display = 'flex';
                    }
                };

                reader.onerror = function() {
                    status.update(`Failed to load file: ${file.name}`, 'error');
                };

                reader.readAsDataURL(file);
            });

            // Reset the input so the same file can be selected again
            e.target.value = '';
        }

        // Function to handle folder selection
        function handleFolderSelect(e) {
            const files = e.target.files;
            if (!files || files.length === 0) {
                status.update('No files selected.', 'warning');
                return;
            }

            handleFileSelect(e); // Reuse the file selection logic
        }

        // Add image to preview area
        function addImageToPreviewArea(src, id, name) {
            const imgPreview = document.createElement('div');
            imgPreview.className = 'image-preview';
            imgPreview.dataset.id = id;
            imgPreview.title = name;
            imgPreview.innerHTML = `<img src="${src}" alt="${name}">`;

            imgPreview.addEventListener('click', () => {
                const index = app.images.findIndex(img => img.id === id);
                if (index !== -1) {
                    showImage(index);
                }
            });

            els.imagePreviewArea.appendChild(imgPreview);
        }

        // Function to show a specific image
        function showImage(index) {
            if (index < 0 || index >= app.images.length) {
                return; // Out of bounds
            }

            app.currentImageIndex = index;
            const currentImage = app.images[index];

            els.screenImage.src = currentImage.src;
            els.screenImage.alt = currentImage.name;

            // ADDED: Check image size on load
            els.screenImage.onload = () => {
                const imgWidth = els.screenImage.naturalWidth;
                const imgHeight = els.screenImage.naturalHeight;
                const containerWidth = els.screenContainer.clientWidth;
                const containerHeight = els.screenContainer.clientHeight;

                if (imgWidth > containerWidth || imgHeight > containerHeight) {
                    status.update('Image dimensions exceed the view area. Scrolling may be required.', 'info');
                }
            };
            // ADDED: Handle potential load errors for the main image
            els.screenImage.onerror = () => {
                status.update(`Error loading image: ${currentImage.name}. It might be corrupted or inaccessible.`, 'error');
            };

            // Update sidebar
            updateSidebarInfo();

            // Update header thumbnail if needed
            if (els.headerThumbnail) {
                els.headerThumbnail.src = currentImage.src;
                els.headerThumbnail.style.display = 'block';
            }

            loadAnnotationsForCurrentImage();
            updateNavButtons();

            // Highlight current image in preview area
            document.querySelectorAll('.image-preview').forEach(preview => {
                preview.classList.remove('current');
            });

            const currentPreview = document.querySelector(`.image-preview[data-id="${currentImage.id}"]`);
            if (currentPreview) {
                currentPreview.classList.add('current');
                // Scroll the preview into view if needed
                currentPreview.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'nearest'
                });
            }

            // Make sure the annotator interface is visible
            els.annotatorInterface.style.display = 'flex';
            app.mode = 'annotate';
        }

        // Function to update navigation buttons (prev/next)
        function updateNavButtons() {
            const hasPrev = app.currentImageIndex > 0;
            const hasNext = app.currentImageIndex < app.images.length - 1;

            els.btnTopPrev.disabled = !hasPrev;
            els.btnTopNext.disabled = !hasNext;
        }

        // Function to update the image counter
        function updateImageCounter() {
            if (app.images.length === 0) {
                els.imageCounter.textContent = 'No images loaded';
            } else {
                els.imageCounter.textContent = `Image ${app.currentImageIndex + 1} of ${app.images.length}`;
            }
        }

        // Update the sidebar to include both date and filename
        function updateSidebarInfo() {
            // Set current date in MMM DD YYYY format
            const now = new Date();
            const dateOptions = {
                month: 'short',
                day: '2-digit',
                year: 'numeric'
            };
            const formattedDate = now.toLocaleDateString('en-US', dateOptions);

            els.sidebarTitleDate.textContent = `Notes: ${formattedDate}`;

            // Update filename if available
            if (app.images.length > 0) {
                const currentImage = app.images[app.currentImageIndex];
                els.sidebarTitleFilename.textContent = currentImage.name || '';
            } else {
                els.sidebarTitleFilename.textContent = '';
            }

            updateImageCounter();
        }

        // Function to set the current annotation color
        function setAnnotationColor(color) {
            app.annotationColor = color;

            // Update UI for color selection
            els.toggleBlue.classList.remove('active');
            els.toggleOrange.classList.remove('active');

            if (color === 'blue') {
                els.toggleBlue.classList.add('active');
            } else if (color === 'orange') {
                els.toggleOrange.classList.add('active');
            }

            status.update(`Selected ${color} annotation color`, 'info');
        }

        // Function to handle image clicks (for adding annotations)
        function handleImageClick(event) {
            if (app.mode !== 'annotate' || !app.isAnnotationEnabled || app.images.length === 0) {
                return;
            }

            // Check if we're clicking on the image and not on a marker or post-it
            if (event.target === els.screenImage && !app.addingAnnotation) {
                const rect = els.annotationLayer.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;

                addAnnotation(x, y);
            }
        }

        // Function to add an annotation
        function addAnnotation(x, y) {
            if (app.addingAnnotation) {
                return; // Don't allow adding another annotation while one is being added
            }

            app.addingAnnotation = true;
            app.lastAnnotationId++;

            const annotation = {
                id: app.lastAnnotationId,
                x: x,
                y: y,
                text: '',
                color: app.annotationColor,
                completed: false,
                replies: []
            };

            const currentImage = app.images[app.currentImageIndex];
            if (!currentImage.annotations) {
                currentImage.annotations = [];
            }

            currentImage.annotations.push(annotation);
            createAnnotationMarker(annotation);
            showPostItNote(annotation); // Show the post-it to add text
        }

        // Function to create an annotation marker on the image
        function createAnnotationMarker(annotation) {
            const marker = document.createElement('div');
            marker.className = `annotation-marker ${annotation.color} ${annotation.completed ? 'completed' : ''}`;
            marker.dataset.id = annotation.id;
            marker.style.left = annotation.x + 'px';
            marker.style.top = annotation.y + 'px';

            // Find the index of this annotation in the current image
            const currentImage = app.images[app.currentImageIndex];
            const annotationIndex = currentImage.annotations.findIndex(a => a.id === annotation.id);
            marker.textContent = annotationIndex + 1;

            marker.title = annotation.text || `Annotation ${annotation.id}`;

            marker.addEventListener('click', (e) => {
                e.stopPropagation();
                showPostItNote(annotation);
            });

            els.annotationLayer.appendChild(marker);
            return marker;
        }

        // Function to toggle annotation completion status
        function toggleAnnotationCompletion(annotationId) {
            const currentImage = app.images[app.currentImageIndex];
            if (!currentImage || !currentImage.annotations) return;

            const annotation = currentImage.annotations.find(a => a.id === annotationId);
            if (!annotation) return;

            annotation.completed = !annotation.completed;

            // Update marker
            const marker = els.annotationLayer.querySelector(`.annotation-marker[data-id="${annotationId}"]`);
            if (marker) {
                if (annotation.completed) {
                    marker.classList.add('completed');
                } else {
                    marker.classList.remove('completed');
                }
            }

            // Update list item
            const listItem = els.annotationList.querySelector(`.annotation-item[data-id="${annotationId}"]`);
            if (listItem) {
                if (annotation.completed) {
                    listItem.classList.add('completed');
                } else {
                    listItem.classList.remove('completed');
                }

                const completeBtn = listItem.querySelector('.complete-btn');
                if (completeBtn) {
                    completeBtn.title = `Mark as ${annotation.completed ? 'Incomplete' : 'Complete'}`;
                    completeBtn.innerHTML = `<i class="fas fa-${annotation.completed ? 'undo' : 'check'}"></i>`;
                }
            }

            status.update(`Annotation #${annotationId} marked as ${annotation.completed ? 'completed' : 'incomplete'}`, 'success');
        }

        // Function to add a reply to an annotation
        function addReplyToAnnotation(annotationId, text) {
            const currentImage = app.images[app.currentImageIndex];
            if (!currentImage || !currentImage.annotations) return false;

            const annotation = currentImage.annotations.find(a => a.id === annotationId);
            if (!annotation) return false;

            if (!annotation.replies) annotation.replies = [];

            annotation.replies.push({
                id: Date.now(),
                text: text,
                author: `User ${app.sessionUser.split('_')[1]}`,
                timestamp: new Date().toISOString()
            });

            // Update UI
            updateAnnotationList();

            status.update('Reply added.', 'success');
            return true;
        }

        // Function to clear all annotations for current image
        function clearAllAnnotationsForCurrentImage() {
            const currentImage = app.images[app.currentImageIndex];
            if (!currentImage) return;

            if (confirm('Are you sure you want to clear all annotations from this image?')) {
                currentImage.annotations = [];
                els.annotationLayer.innerHTML = '';
                els.annotationList.innerHTML = '';
                status.update('All annotations cleared.', 'success');
            }
        }

        // Function to render image thumbnails in the preview area
        function renderImagePreviews() {
            els.imagePreviewArea.innerHTML = '';

            app.images.forEach(image => {
                addImageToPreviewArea(image.src, image.id, image.name);
            });

            if (app.images.length > 0) {
                els.imagePreviewArea.style.display = 'flex';
            } else {
                els.imagePreviewArea.style.display = 'none';
            }
        }

        // Setup drag and drop
        function setupDragAndDrop() {
            const dragTarget = els.statusBox;

            dragTarget.addEventListener('dragover', (e) => {
                e.preventDefault();
                dragTarget.classList.add('drag-over');
            });

            dragTarget.addEventListener('dragleave', () => {
                dragTarget.classList.remove('drag-over');
            });

            dragTarget.addEventListener('drop', (e) => {
                e.preventDefault();
                dragTarget.classList.remove('drag-over');

                if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                    handleFiles(e.dataTransfer.files);
                }
            });

            // Function to handle dropped files
            function handleFiles(files) {
                // Create a fake event object with the files
                const event = {
                    target: {
                        files: files
                    }
                };
                handleFileSelect(event);
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

            // Setup Event Listeners
            els.btnOpenScreenshot.addEventListener('click', () => els.fileInput.click());
            els.fileInput.addEventListener('change', handleFileSelect);
            els.folderInput.addEventListener('change', handleFolderSelect);

            els.btnRestart.addEventListener('click', () => {
                if (confirm('Are you sure you want to restart? All unsaved changes will be lost.')) {
                    // Remove URL parameters when restarting
                    window.location.href = window.location.pathname;
                }
            });

            els.btnTopPrev.addEventListener('click', () => showImage(app.currentImageIndex - 1));
            els.btnTopNext.addEventListener('click', () => showImage(app.currentImageIndex + 1));

            els.toggleBlue.addEventListener('click', () => setAnnotationColor('blue'));
            els.toggleOrange.addEventListener('click', () => setAnnotationColor('orange'));

            els.screenContainer.addEventListener('click', handleImageClick);

            els.btnSaveProjectFolder.addEventListener('click', saveProjectToServer);

            els.btnClearAnnotations.addEventListener('click', clearAllAnnotationsForCurrentImage);

            els.btnScrollTop.addEventListener('click', () => els.annotationList.scrollTo({
                top: 0,
                behavior: 'smooth'
            }));
            els.btnScrollBottom.addEventListener('click', () => els.annotationList.scrollTo({
                top: els.annotationList.scrollHeight,
                behavior: 'smooth'
            }));

            // Setup drag and drop
            setupDragAndDrop();

            // Initial UI State
            setAnnotationColor(app.annotationColor); // Set initial color toggle state
            updateNavButtons();
            updateImageCounter();

            // CHANGED: Show annotator interface by default
            els.annotatorInterface.style.display = 'flex';

            // Check URL for existing project
            checkForProjectInUrl();

            // Set initial status message
            status.update('Ready. Open image(s) or load a project via URL.', 'info');
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
        // Add event listener after DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function() {
            const btnAddWebScreenshot = document.getElementById('btnAddWebScreenshot');
            if (btnAddWebScreenshot) {
                btnAddWebScreenshot.addEventListener('click', addWebPageScreenshot);
            }
        });
    </script>
</body>

</html>
