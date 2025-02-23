/* main.php */

<?php
require_once 'main-server.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./main-styles.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <!-- Header Section -->
    <div class="header">
        <div class="left-section">
            <button id="mobileMenuToggle" class="mobile-menu-toggle" title="Toggle Menu">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="menu-title">NetBound Tools</h2>

            <button id="menuNewBtn" class="header-button" title="New" onclick="createNewFile()">
                <i class="fas fa-file"></i>
            </button>
            <button id="menuRefreshBtn" class="header-button" title="Reload Menu">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button id="menuSortBtn" class="header-button" title="Toggle Sort">
                <i class="fas fa-sort-alpha-down"></i>
            </button>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Menu Panel -->
        <div class="menu" id="menuPanel">
            <div class="menu-content">
                <ul class="file-list">
                    <?php
                    // Generate the file list for the menu.
                    $files = glob($dir . '/*.*', GLOB_BRACE);

                    if (!empty($files)) {
                        usort($files, function ($a, $b) {
                            return filemtime($b) - filemtime($a);
                        });
                    }

                    if (!empty($files)) {
                        foreach ($files as $file) {
                            $filename = basename($file);
                            $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);

                            if (preg_match('/\(BAK-\w{3}\d{2}-S\d+\)\.\w+$/', $filename)) {
                                continue;
                            }

                            if ($filename === '.' || $filename === '..') continue;

                            $isCurrentEdit = ($filename === $currentFilename);

                            echo "<li class='file-entry " . ($isCurrentEdit ? "current-edit" : "") . "'>
                                <div class='file-controls'>
                                    <button onclick='loadFile(\"" . addslashes($filename) . "\")' title='Edit File'><i class='fas fa-pencil-alt'></i></button>
                                    <a href='#' onclick='openInNewTab(\"" . htmlspecialchars($filename, ENT_QUOTES) . "\")' title='Run File'><i class='fas fa-play'></i></a>
                                    <button onclick='confirmDelete(\"" . htmlspecialchars($filename) . "\")' title='Delete File'><i class='fas fa-trash'></i></button>
                                </div>
                                <a onclick='loadFile(\"" . addslashes($filename) . "\"); return false;' href='#' class='filename'>" . htmlspecialchars($filename, ENT_QUOTES) . "</a>
                            </li>";
                        }
                    }
                    ?>
                </ul>
            </div>
        </div>

        <!-- Editor Section -->
        <div class="menu-container">
            <div class="editor-view">
                <div class="editor" id="editorSection">
                    <div class="editor-header">
                        <div class="header-top">
                            <h1 class="editor-title">
                                NetBound Editor: <?php echo date("F j Y", filemtime(__FILE__)); ?></h1>
                        </div>
                        <div class="label-line">
                            <span class="info-label">Filename:</span>
                            <input type="text" id="editorFilename" class="info-input" value="" onchange="updateDisplayFilename()">
                            <button type="button" class="command-button" onclick="updateVersionAndDate()">
                                <i class="fas fa-sync-alt"></i> Rename
                            </button>
                        </div>
                    </div>
                    <div class="button-group">
                        <button type="button" class="command-button" onclick="openFileRequester()" title="From File">
                            <i class="fas fa-folder-open"></i> From File
                        </button>

                        <div class="split-button">
                            <div class="main-part" onclick="fromClipboard()" title="Replace content with clipboard">
                                <i class="fas fa-clipboard"></i> From Clipboard
                            </div>
                            <div class="append-part" onclick="appendClipboard()" title="Append clipboard to current content">
                                <i class="fas fa-plus"></i>
                            </div>
                        </div>
                        <div class="split-button">
                            <div class="main-part" onclick="fromBackup()" title="Load content from latest backup">
                                <i class="fas fa-history"></i> From Backup
                            </div>
                            <div class="append-part" onclick="fromBackupManager()" title="Open Backup Manager">
                                <i class="fas fa-plus"></i>
                            </div>
                        </div>
                    </div>

                    <div class="persistent-status-bar" id="statusBar"></div>
                    <form method="POST" class="edit-form" style="display:flex;flex-direction:column;height:100%;" id="editorForm">
                        <div class="editor-container">
                            <div id="editor"></div>
                        </div>
                        <div class="button-row">
                            <div class="button-group">
                                <div class="split-button">
                                    <div class="main-part" onclick="saveFile()">
                                        <i class="fas fa-upload"></i> Save
                                    </div>
                                    <div class="append-part" onclick="saveAs()" title="Save file to local machine">
                                        <i class="fas fa-download"></i>
                                    </div>
                                </div>
                                <div class="split-button">
                                    <div class="main-part" onclick="toClipboard()" title="Copy all editor content to clipboard">
                                        <i class="fas fa-clipboard"></i> To Clipboard
                                    </div>
                                </div>
                                <button type="button" name="run" class="command-button" title="Run"
                                    onclick="window.open(document.getElementById('editorFilename').value, '_blank')">
                                    <i class="fas fa-play"></i> Run
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="backup-view">
                <iframe src="" style="width:100%; height:100%; border:none;"></iframe>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="nav-buttons">
            <button onclick="scrollToTop()" title="Scroll to Top">
                <i class="fas fa-arrow-up"></i>
            </button>
            <button onclick="scrollToBottom()" title="Scroll to Bottom">
                <i class="fas fa-arrow-down"></i>
            </button>
        </div>

        <!-- External Scripts -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ace.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ext-language_tools.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ext-searchbox.js"></script>
        <script src="main-editor-init.js"></script>
        <script src="main-shared-utils.js"></script>
        <script src="main-file-ops.js"></script>

        <!-- Event Handlers -->
        <script>
            // Toggle Sort Handler
            document.getElementById('menuSortBtn').addEventListener('click', function() {
                const sortIcon = this.querySelector('i');
                sortIcon.classList.toggle('fa-sort-alpha-down');
                sortIcon.classList.toggle('fa-clock');
                fetch('main.php?toggleSort=1')
                    .then(() => {
                        if (editor.getValue() !== editorContent) {
                            if (!confirm('Unsaved changes detected. Continue?')) return;
                        }
                        document.getElementById('editorSection').style.display = 'flex';
                        fetch('main.php?getFileList=1')
                            .then(response => response.text())
                            .then(html => {
                                document.querySelector('.file-list').innerHTML = html;
                            });
                    });
            });

            // Refresh Handler
            document.getElementById('menuRefreshBtn').addEventListener('click', function() {
                fetch('main.php?getFileList=1')
                    .then(() => {
                        if (editor.getValue() !== editorContent) {
                            if (!confirm('Unsaved changes detected. Continue?')) return;
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        document.querySelector('.file-list').innerHTML = html;
                        updateStatus('File list refreshed', 'success');
                    });
            });

            // Mobile Menu Toggle
            document.getElementById('mobileMenuToggle').addEventListener('click', function() {
                if (editor.getValue() !== editorContent) {
                    if (!confirm('Unsaved changes detected. Continue?')) return;
                }

                const editorView = document.querySelector('.editor-view');
                const backupView = document.querySelector('.backup-view');

                editorView.classList.toggle('hidden');
                backupView.classList.toggle('active');
                document.body.classList.toggle('menu-visible');
            });

            // Set initial content if exists
            <?php if (!empty($content)): ?>
                editor.setValue(<?php echo json_encode($content); ?>);
                editorContent = editor.getValue();
            <?php endif; ?>

            // Unsaved Changes Handler
            window.addEventListener('beforeunload', function(e) {
                if (editor.getValue() !== editorContent) {
                    e.preventDefault();
                    e.returnValue = '';
                    updateStatus('Unsaved changes detected.', 'error');
                }
            });
        </script>
    </body>
</html>
