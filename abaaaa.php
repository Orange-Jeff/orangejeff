Sounds like a great plan! While you’re getting the API key, I’ll work on **Part 3** of the integration. This part will focus on adding **Natural Language to Code** and **Code Optimization** features to your editor. These features will make your editor even more powerful and user-friendly.

---

### **Part 3: Adding Natural Language to Code and Code Optimization**

#### **Changes Made:**
1. Added a **"Generate Code" button** to the UI for natural language to code conversion.
2. Added a **"Optimize Code" button** to the UI for code optimization.
3. Added JavaScript functions to call the DeepSeek API for natural language to code and optimization.
4. Displayed AI-generated code and optimization suggestions in the sidebar panel.

---

#### **Updated Source Code (Part 3)**

```php
<?php
/* -------------------------------------------------------------------------- */
/*                                 Core Setup                                 */
/* -------------------------------------------------------------------------- */

// Error Reporting and Session Start
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure REQUEST_METHOD is set
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}

// Set up directory and backup directory
$dir = __DIR__;
$backupDir = $dir . '/backups/';

// Create backup directory if it doesn't exist
if (!is_dir($backupDir)) {
    if (!@mkdir($backupDir, 0755, true)) {
        die('Failed to create backup directory. Check permissions.');
    }
}

// Session namespace
$sessionNamespace = 'netbound_' . md5(__DIR__);

/* -------------------------------------------------------------------------- */
/*                            Cache Control Headers                           */
/* -------------------------------------------------------------------------- */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

/* -------------------------------------------------------------------------- */
/*                                 GET Handlers                               */
/* -------------------------------------------------------------------------- */

// File Serving Handler
if (isset($_GET['file'])) {
    $requestedFile = basename($_GET['file']);
    // Validate filename to prevent path traversal
    if (preg_match('/^[a-zA-Z0-9._-]+$/', $requestedFile) && file_exists($requestedFile)) {
        echo file_get_contents($requestedFile);
        exit;
    }
}

// Sort Mode Toggle Handler
if (isset($_GET['toggleSort'])) {
    $currentSort = $_SESSION['sortBy'] ?? 'date';
    $sortBy = ($currentSort === 'date') ? 'name' : 'date';
    $_SESSION['sortBy'] = $sortBy;
    exit;
} else {
    $sortBy = $_SESSION['sortBy'] ?? 'date';
}

// Get Folders Handler (currently duplicates getFileList)
if (isset($_GET['getFolders'])) {
    $folders = array_filter(glob('*'), 'is_dir');
    echo json_encode($folders);
    exit;
}

// Get File List Handler
if (isset($_GET['getFileList'])) {
    $files = glob($dir . '/*.*', GLOB_BRACE);

    if (!empty($files)) {
        if ($sortBy === 'date') {
            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });
        } else {
            natcasesort($files);
            $files = array_values($files);
        }
    }

    ob_start();

    if (!empty($files)) {
        foreach ($files as $file) {
            $filename = basename($file);
            $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);

            if (preg_match('/\(BAK-\w{3}\d{2}-S\d+\)\.\w+$/', $filename)) {
                continue;
            }

            if ($filename === '.' || $filename === '..') continue;

            $isCurrentEdit = ($filename === ($_GET['edit'] ?? ''));

            echo "<li class='file-entry " . ($isCurrentEdit ? "current-edit" : "") . "'>
                <div class='file-controls'>
                    <button onclick='loadFile(\"" . addslashes($filename) . "\")' title='Edit File'><i class='fas fa-pencil-alt'></i></button>
                    <a href='" . htmlspecialchars($filename, ENT_QUOTES) . "' target='_blank' title='Run File'><i class='fas fa-play'></i></a>
                    <button onclick='confirmDelete(\"" . htmlspecialchars($filename) . "\")' title='Delete File'><i class='fas fa-trash'></i></button>
                </div>
                <a onclick='loadFile(\"" . addslashes($filename) . "\"); return false;' href='#' class='filename'>" . htmlspecialchars($filename, ENT_QUOTES) . "</a>
            </li>";
        }
    } else {
        echo "<li class='no-files'>No files available.</li>";
    }
    $fileListHTML = ob_get_clean();
    echo $fileListHTML;
    exit;
}

/* -------------------------------------------------------------------------- */
/*                                POST Handler                                */
/* -------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $filename = basename($_POST['filename'] ?? '');
        $content = $_POST['content'] ?? '';

        if (empty($filename)) {
            echo json_encode(['status' => 'error', 'message' => 'Filename required']);
        } else {
            $filePath = __DIR__ . '/' . $filename;
            $originalContent = file_exists($filePath) ? file_get_contents($filePath) : '';

            if ($content !== $originalContent) {
                // Backup logic
                $backupFilename = $filename;
                $version = 1;
                while (file_exists($backupDir . $backupFilename . '(v' . $version . ').php')) {
                    $version++;
                }
                $backupFilename = $backupFilename . '(v' . $version . ').php';
                copy($filePath, $backupDir . $backupFilename);

                if (file_put_contents($filePath, $content, LOCK_EX) !== false) {
                    echo json_encode(['status' => 'success', 'message' => 'File saved: ' . $filename . ' (backup created: ' . $backupFilename . ')']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Save failed: ' . $filename]);
                }
            } else {
                echo json_encode(['status' => 'info', 'message' => 'No changes detected, file not saved: ' . $filename]);
            }
        }
        exit;
    } elseif ($action === 'getBackup') {
        $filename = basename($_POST['filename'] ?? '');
        if (empty($filename)) {
            echo json_encode(['status' => 'error', 'message' => 'Filename required']);
            exit;
        }
        $backupFilename = '';
        $version = 1;
        while (file_exists($backupDir . $filename . '(v' . $version . ').php')) {
            $backupFilename = $backupDir . $filename . '(v' . $version . ').php';
            $version++;
        }
        if ($backupFilename) {
            $content = file_get_contents($backupFilename);
            echo json_encode(['status' => 'success', 'content' => $content, 'backupFilename' => basename($backupFilename)]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No backups found for this file.']);
        }
        exit;
    } elseif ($action === 'delete') {
        $filename = basename($_POST['filename'] ?? '');

        $response = ['status' => 'error', 'message' => ''];

        if (empty($filename)) {
            $response['message'] = 'Filename is required for deletion.';
        } else {
            $filePath = __DIR__ . '/' . $filename;

            if (!file_exists($filePath)) {
                $response['message'] = 'File does not exist: ' . $filename;
            } elseif (is_dir($filePath)) {
                $response['message'] = 'Cannot delete directories: ' . $filename;
            } else {
                if (unlink($filePath)) {
                    $response['status'] = 'success';
                    $response['message'] = 'File deleted successfully: ' . $filename;
                } else {
                    $response['message'] = 'Failed to delete the file: ' . $filename;
                }
            }
        }

        echo json_encode($response);
        exit;
    }
}
// Add this to the POST handlers section

/* -------------------------------------------------------------------------- */
/*                                HTML Output                                 */
/* -------------------------------------------------------------------------- */

$currentFilename = isset($_GET['file']) ? basename($_GET['file']) : '';
$content = '';
if (isset($_GET['file']) && !empty($_GET['file'])) {
    $content = file_get_contents(basename($_GET['file']));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetBound Tools Menu</title>
    <link rel="stylesheet" href="main-styles.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <div class="header">
        <div class="left-section">
            <button id="mobileMenuToggle" class="mobile-menu-toggle" title="Toggle Menu">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="menu-title">NetBound Tools</h2>
            <button id="menuUpdateBtn" class="header-button" title="Transfer files to server">
                <i class="fas fa-paper-plane"></i>
            </button>
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
    <div class="container">
        <div class="menu" id="menuPanel">
            <div class="menu-content">
                <ul class="file-list">
                    <?php
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
                                    <a href='" . htmlspecialchars($filename, ENT_QUOTES) . "' target='_blank' title='Run File'><i class='fas fa-play'></i></a>
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
        <div class="menu-container">
            <div class="editor" id="editorSection">
                <div class="editor-header">
                    <div class="header-top">
                        <h1 class="editor-title">NetBound Editor: <?php echo date("F j Y", filemtime(__FILE__)); ?></h1>
                    </div>
                    <div class="label-line">
                        <span class="info-label">Filename:</span>
                        <input type="text" id="editorFilename" class="info-input" value="" onchange="updateDisplayFilename()">
                        <button type="button" class="small-icon" title="Update filename and save" onclick="updateVersionAndDate()">
                            <i class="fas fa-sync-alt"></i>
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
                    <!-- Add AI Suggestions Button -->
                    <button id="aiSuggestButton" class="command-button" title="Get AI Suggestions">
                        <i class="fas fa-robot"></i> AI Suggestions
                    </button>
                    <!-- Add Refactor Code Button -->
                    <button id="aiRefactorButton" class="command-button" title="Refactor Code">
                        <i class="fas fa-code"></i> Refactor Code
                    </button>
                    <!-- Add Detect Errors Button -->
                    <button id="aiDetectErrorsButton" class="command-button" title="Detect Errors">
                        <i class="fas fa-bug"></i> Detect Errors
                    </button>
                    <!-- Add Generate Code Button -->
                    <button id="aiGenerateCodeButton" class="command-button" title="Generate Code">
                        <i class="fas fa-magic"></i> Generate Code
                    </button>
                    <!-- Add Optimize Code Button -->
                    <button id="aiOptimizeCodeButton" class="command-button" title="Optimize Code">
                        <i class="fas fa-tachometer-alt"></i> Optimize Code
                    </button>
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
                                    <i class="fas fa-save"></i> Save
                                </div>
                                <div class="divider"></div>
                                <div class="append-part" onclick="saveAs()" title="Save file to local machine">
                                    <i class="fas fa-plus"></i>
                                </div>
                            </div>
                            <div class="split-button">
                                <div class="main-part" onclick="toClipboard()" title="Copy all editor content to clipboard">
                                    <i class="fas fa-clipboard"></i> To Clipboard
                                </div>
                            </div>
                            <button type="button" name="run" class="command-button" title="Run" onclick="openInNewTab(document.getElementById('editorFilename').value)">
                                <i class="fas fa-play"></i> Run
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- Add AI Suggestions Panel -->
        <div id="aiSuggestionsPanel" class="ai-panel">
            <h3>AI Suggestions</h3>
            <div id="aiSuggestionsContent"></div>
        </div>
    </div>
    <div class="nav-buttons">
        <button onclick="scrollToTop()" title="Scroll to Top">
            <i class="fas fa-arrow-up"></i>
        </button>
        <button onclick="scrollToBottom()" title="Scroll to Bottom">
            <i class="fas fa-arrow-down"></i>
        </button>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ace.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ext-language_tools.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ext-searchbox.js"></script>
    <script>
        // Initialize Ace Editor
        var editor = ace.edit("editor");
        editor.setTheme("ace/theme/monokai");
        editor.session.setMode("ace/mode/php");
        editor.setOptions({
            enableBasicAutocompletion: true,
            enableSnippets: true,
            enableLiveAutocompletion: true,
            useSoftTabs: true,
            tabSize: 4,
            fontSize: "14px",
            showPrintMargin: false
        });

        // Set initial content if exists
        <?php if (!empty($content)): ?>
            editor.setValue(<?php echo json_encode($content); ?>);
            editorContent = editor.getValue();
        <?php endif; ?>

        // Function to fetch AI suggestions
        async function getAISuggestion(code, prompt) {
                const apiKey = 'YOUR_DEEPSEEK_API_KEY'; // Replace with your API key
                const apiUrl = 'https://api.deepseek.com/v1/completions'; // Example API endpoint

                try {
                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${apiKey}`
                        },
                        body: JSON.stringify({
                            prompt: `${prompt}: ${code}`,
                            max_tokens: 200 // Adjust based on the desired response length
                        })
                    });

                    if (!response.ok) {
                        throw new Error(`API request failed: ${response.statusText}`);
                    }

                    const data = await response.json();
                    return data.choices[0].text.trim();
                } catch (error) {
                    console.error('Error fetching AI suggestion:', error);
                    updateStatus('Failed to fetch AI suggestion.', 'error');
                    return null;
