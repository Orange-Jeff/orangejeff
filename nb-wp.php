<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetBound Tools: WordPress Poster</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="shared-styles.css">
    <style>
        /* v1.0 - Added support for centered layout and hamburger menu */
        body {
            font-family: sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
        }

        .menu-container {
            max-width: 768px;
            width: 100%;
            margin: 0 auto;
            padding: 15px;
            box-sizing: border-box;
        }

        .title-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            margin-bottom: 10px;
        }

        .editor-title {
            margin-top: 0;
            margin-bottom: 0;
            color: #0056b3;
            font-size: 18px;
            line-height: 1.2;
            font-weight: bold;
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
            color: #003d7e;
        }

        .status-box {
            height: 80px;
            min-height: 80px;
            max-height: 80px;
            overflow-y: auto;
            border: 1px solid #2196f3;
            background-color: #fff;
            padding: 5px;
            margin-bottom: 25px;
            border-radius: 4px;
            display: flex;
            flex-direction: column-reverse;
            text-align: left;
        }

        .container {
            width: 100%;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="url"],
        input[type="password"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            /* Include padding in width */
        }

        textarea {
            height: 200px;
            resize: vertical;
        }

        .content-area {
            height: 300px;
        }

        button {
            padding: 10px 20px;
            background-color: #0073aa;
            /* WP Blue */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        button:hover {
            background-color: #005a87;
        }

        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        #status {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
            font-weight: bold;
        }

        .status-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            border: 1px solid #ffeeba;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        /* Basic Responsive */
        @media (max-width: 600px) {
            body {
                margin: 0;
            }

            .container {
                padding: 15px;
            }

            button {
                width: 100%;
                margin-right: 0;
            }
        }
    </style>
</head>

<body>

    <div class="menu-container">
        <div class="title-container">
            <h1 class="editor-title">NetBound Tools: WordPress Poster</h1>
            <a href="main.php?app=nb-wp.php" class="hamburger-menu" title="Go to Main Menu">
                <i class="fas fa-bars"></i>
            </a>
        </div>

        <div id="statusBox" class="status-box"></div>

        <div class="container">
            <div class="warning">
                <strong>Security Warning:</strong> Never use your main WordPress password. Generate an <a href="https://wordpress.org/support/article/application-passwords/" target="_blank" rel="noopener noreferrer">Application Password</a> in your WP User Profile. Ensure your site allows REST API requests (CORS might need configuration). Credentials are handled client-side. Use on trusted devices only.
            </div>

            <h2>WordPress Connection Details</h2>
            <label for="wpUrl">WordPress Site URL (e.g., https://frogstar.tv):</label>
            <input type="url" id="wpUrl" placeholder="https://frogstar.tv">

            <label for="wpUser">WordPress Username:</label>
            <input type="text" id="wpUser" placeholder="Your WP Username">

            <label for="wpAppPassword">Application Password:</label>
            <input type="password" id="wpAppPassword" placeholder="Paste Application Password Here">

            <h2>Post Content</h2>
            <label for="postTitle">Post Title:</label>
            <input type="text" id="postTitle">

            <label for="postContent">Post Content (HTML allowed):</label>
            <textarea id="postContent" class="content-area" placeholder="Write your post content here..."></textarea>

            <label for="postExcerpt">Excerpt (Optional):</label>
            <textarea id="postExcerpt" rows="3" placeholder="Short summary of the post..."></textarea>

            <label for="postCategories">Categories (Comma-separated names):</label>
            <input type="text" id="postCategories" placeholder="e.g., News, Tech Tips">

            <label for="postTags">Tags (Comma-separated names):</label>
            <input type="text" id="postTags" placeholder="e.g., wordpress, javascript, webdev">

            <label for="featuredImage">Featured Image (Optional):</label>
            <input type="file" id="featuredImage" accept="image/*">
            <!-- Basic video upload - requires manual insertion -->
            <label for="mediaUpload">Upload Media (Image/Video - for manual insertion):</label>
            <input type="file" id="mediaUpload" accept="image/*,video/*">


            <h2>Actions</h2>
            <button id="saveDraftBtn">Save Draft Locally</button>
            <button id="loadDraftBtn">Load Local Draft</button>
            <button id="postBtn">Post to WordPress</button>
            <button id="clearFormBtn" style="background-color:#6c757d;">Clear Form</button>


            <div id="status"></div>
        </div>
    </div>

    <script>
        const wpUrlInput = document.getElementById('wpUrl');
        const wpUserInput = document.getElementById('wpUser');
        const wpAppPasswordInput = document.getElementById('wpAppPassword');
        const postTitleInput = document.getElementById('postTitle');
        const postContentInput = document.getElementById('postContent');
        const postExcerptInput = document.getElementById('postExcerpt');
        const postCategoriesInput = document.getElementById('postCategories');
        const postTagsInput = document.getElementById('postTags');
        const featuredImageInput = document.getElementById('featuredImage');
        const mediaUploadInput = document.getElementById('mediaUpload'); // For general media
        const statusBox = document.getElementById('statusBox');

        const saveDraftBtn = document.getElementById('saveDraftBtn');
        const loadDraftBtn = document.getElementById('loadDraftBtn');
        const postBtn = document.getElementById('postBtn');
        const clearFormBtn = document.getElementById('clearFormBtn');
        const statusDiv = document.getElementById('status');

        const DRAFT_KEY = 'wpBasicPosterDraft';

        // Update status function for both status areas
        function updateStatus(message, type = 'info') {
            // Update the old status area
            statusDiv.innerHTML = message;
            statusDiv.className = `status-${type}`;
            statusDiv.style.display = 'block';

            // Add to the new status box
            const statusMessage = document.createElement('div');
            statusMessage.className = `message ${type} latest`;
            statusMessage.textContent = message;

            // Remove 'latest' class from previous messages
            statusBox.querySelectorAll('.message').forEach(msg => {
                msg.classList.remove('latest');
            });

            statusBox.insertBefore(statusMessage, statusBox.firstChild);
            statusBox.scrollTop = 0; // Always show latest message
        }

        // --- Local Storage ---

        saveDraftBtn.addEventListener('click', () => {
            const draft = {
                wpUrl: wpUrlInput.value,
                wpUser: wpUserInput.value,
                // Do NOT save password in local storage for security
                title: postTitleInput.value,
                content: postContentInput.value,
                excerpt: postExcerptInput.value,
                categories: postCategoriesInput.value,
                tags: postTagsInput.value,
            };
            try {
                localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
                updateStatus('Draft saved locally (Credentials not saved).', 'info');
            } catch (e) {
                console.error("Error saving draft:", e);
                updateStatus(`Error saving draft: ${e.message}`, 'error');
            }
        });

        loadDraftBtn.addEventListener('click', () => {
            try {
                const draftJSON = localStorage.getItem(DRAFT_KEY);
                if (draftJSON) {
                    const draft = JSON.parse(draftJSON);
                    wpUrlInput.value = draft.wpUrl || '';
                    wpUserInput.value = draft.wpUser || '';
                    // Password is intentionally not loaded
                    wpAppPasswordInput.value = '';
                    postTitleInput.value = draft.title || '';
                    postContentInput.value = draft.content || '';
                    postExcerptInput.value = draft.excerpt || '';
                    postCategoriesInput.value = draft.categories || '';
                    postTagsInput.value = draft.tags || '';
                    // Clear file inputs
                    featuredImageInput.value = null;
                    mediaUploadInput.value = null;
                    updateStatus('Draft loaded from local storage.', 'info');
                } else {
                    updateStatus('No local draft found.', 'info');
                }
            } catch (e) {
                console.error("Error loading draft:", e);
                updateStatus(`Error loading draft: ${e.message}`, 'error');
                // Clear potentially corrupted data
                localStorage.removeItem(DRAFT_KEY);
            }
        });

        clearFormBtn.addEventListener('click', () => {
            if (confirm('Are you sure you want to clear the form? Unsaved changes will be lost.')) {
                wpUrlInput.value = '';
                wpUserInput.value = '';
                wpAppPasswordInput.value = '';
                postTitleInput.value = '';
                postContentInput.value = '';
                postExcerptInput.value = '';
                postCategoriesInput.value = '';
                postTagsInput.value = '';
                featuredImageInput.value = null;
                mediaUploadInput.value = null;
                updateStatus('Form cleared.', 'info');
            }
        });


        // --- WordPress Posting ---

        postBtn.addEventListener('click', async () => {
            const wpUrl = wpUrlInput.value.trim().replace(/\/$/, ""); // Remove trailing slash
            const user = wpUserInput.value.trim();
            const password = wpAppPasswordInput.value.trim(); // This is the Application Password
            const title = postTitleInput.value.trim();
            const content = postContentInput.value.trim();
            const excerpt = postExcerptInput.value.trim();
            const categories = postCategoriesInput.value.trim().split(',').map(c => c.trim()).filter(c => c);
            const tags = postTagsInput.value.trim().split(',').map(t => t.trim()).filter(t => t);
            const featuredImageFile = featuredImageInput.files[0];
            const mediaFile = mediaUploadInput.files[0]; // General media

            if (!wpUrl || !user || !password || !title || !content) {
                updateStatus('Please fill in WP URL, Username, Application Password, Title, and Content.', 'error');
                return;
            }

            const apiBase = `${wpUrl}/wp-json/wp/v2`;
            const credentials = btoa(`${user}:${password}`); // Base64 encode credentials

            updateStatus('Starting post process...', 'info');
            setLoading(true);

            try {
                let featuredMediaId = null;
                let mediaUploadUrl = null;

                // 1. Upload general media file if selected (for manual insertion)
                if (mediaFile) {
                    updateStatus('Uploading media file...', 'info');
                    try {
                        const mediaData = await uploadMedia(apiBase, credentials, mediaFile);
                        mediaUploadUrl = mediaData.source_url;
                        updateStatus(`Media uploaded successfully! URL (copy for insertion): ${mediaUploadUrl}`, 'success');
                        // Clear the file input after successful upload
                        mediaUploadInput.value = null;
                    } catch (mediaError) {
                        updateStatus(`Media upload failed: ${mediaError.message}. Continuing without it.`, 'error');
                        console.error("Media Upload Error:", mediaError);
                        // Optionally stop the process if media upload is critical:
                        // setLoading(false); return;
                    }
                }

                // 2. Upload Featured Image if selected
                if (featuredImageFile) {
                    updateStatus('Uploading featured image...', 'info');
                    try {
                        const featuredImageData = await uploadMedia(apiBase, credentials, featuredImageFile);
                        featuredMediaId = featuredImageData.id;
                        updateStatus('Featured image uploaded.', 'info');
                        // Clear the file input after successful upload
                        featuredImageInput.value = null;
                    } catch (featureError) {
                        updateStatus(`Featured Image upload failed: ${featureError.message}. Posting without featured image.`, 'error');
                        console.error("Featured Image Upload Error:", featureError);
                        // Continue without featured image
                    }
                }


                // 3. Prepare Post Data
                // NOTE: Sending category/tag *names* might work depending on WP setup,
                // but ideally, we should look up IDs first. This is the basic version.
                const postData = {
                    title: title,
                    content: content,
                    status: 'publish', // Or 'draft'
                    ...(excerpt && {
                        excerpt: excerpt
                    }), // Add excerpt only if it exists
                    // The REST API usually expects IDs, but let's try names first for simplicity.
                    // This might fail or create new terms depending on WP config/permissions.
                    ...(categories.length > 0 && {
                        categories: categories
                    }),
                    ...(tags.length > 0 && {
                        tags: tags
                    }),
                    ...(featuredMediaId && {
                        featured_media: featuredMediaId
                    }),
                };

                // 4. Create the Post
                updateStatus('Creating post...', 'info');
                const response = await fetch(`${apiBase}/posts`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Basic ${credentials}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(postData)
                });

                const responseData = await response.json();

                if (!response.ok) {
                    // Attempt to parse WP error message
                    const errorMessage = responseData.message || `HTTP Error: ${response.status} ${response.statusText}`;
                    throw new Error(errorMessage);
                }

                updateStatus(`Post created successfully! <a href="${responseData.link}" target="_blank">View Post</a>`, 'success');
                // Optional: Clear form after successful post
                // postTitleInput.value = '';
                // postContentInput.value = '';
                // postExcerptInput.value = '';
                // postCategoriesInput.value = '';
                // postTagsInput.value = '';
                // featuredImageInput.value = null; // Already cleared if uploaded
                // mediaUploadInput.value = null; // Already cleared if uploaded


            } catch (error) {
                console.error("Posting Error:", error);
                updateStatus(`Error posting to WordPress: ${error.message}`, 'error');
            } finally {
                setLoading(false);
            }
        });

        // --- Helper Functions ---

        async function uploadMedia(apiBase, credentials, file) {
            const formData = new FormData();
            formData.append('file', file);
            // Optional: Add title, caption, alt_text if needed
            formData.append('title', file.name); // Basic title from filename
            // formData.append('caption', 'Your caption here');
            // formData.append('alt_text', 'Alt text here');

            const response = await fetch(`${apiBase}/media`, {
                method: 'POST',
                headers: {
                    'Authorization': `Basic ${credentials}`,
                    // Content-Disposition helps with filename handling on the server
                    'Content-Disposition': `attachment; filename="${file.name}"`
                    // Don't set Content-Type, let the browser set it correctly for FormData
                },
                body: formData
            });

            const responseData = await response.json();

            if (!response.ok) {
                const errorMessage = responseData.message || `Media Upload Failed: ${response.status} ${response.statusText}`;
                throw new Error(errorMessage);
            }
            return responseData; // Contains ID, source_url, etc.
        }

        function setLoading(isLoading) {
            postBtn.disabled = isLoading;
            saveDraftBtn.disabled = isLoading;
            loadDraftBtn.disabled = isLoading;
            // Optionally disable other inputs
            wpUrlInput.disabled = isLoading;
            wpUserInput.disabled = isLoading;
            wpAppPasswordInput.disabled = isLoading;
            postTitleInput.disabled = isLoading;
            postContentInput.disabled = isLoading;
            // ... etc for other fields
        }

        // Initial state
        statusDiv.style.display = 'none';
        updateStatus('Ready to post to WordPress. Fill in the form above.', 'info');

        // Check if running in an iframe
        function inIframe() {
            try {
                return window.self !== window.top;
            } catch (e) {
                return true;
            }
        }

        // Apply iframe-specific styling if needed
        if (inIframe()) {
            document.body.classList.add('in-iframe');
        } else {
            // No special class needed for standalone
        }
    </script>

</body>

</html>
