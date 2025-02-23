  <?php
    session_start();

    // Handle file uploads
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle file uploads
        if (isset($_FILES['files'])) {
            $results = [];
            foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                $filename = $_FILES['files']['name'][$key];
                $exists = file_exists($filename);
                if (move_uploaded_file($tmp_name, $filename)) {
                    $results[] = "<div class='file-item " . ($exists ? 'replaced' : 'success') . "'>"
                        . "<input type='checkbox' checked disabled>"
                        . "<span><a href='{$filename}' target='{$filename}'>{$filename}</a></span>"
                        . "<span>" . ($exists ? "REPLACED" : "COPIED") . "</span>"
                        . "</div>";
                }
            }
            $_SESSION['last_files'] = $_FILES;
            $_SESSION['results'] = $results;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // Handle REPEAT action
        if (isset($_POST['redo']) && isset($_SESSION['last_files'])) {
            $results = [];
            foreach ($_SESSION['last_files']['files']['tmp_name'] as $key => $tmp_name) {
                $filename = $_SESSION['last_files']['files']['name'][$key];
                $exists = file_exists($filename);
                if (copy($tmp_name, $filename)) {
                    $results[] = "<div class='file-item " . ($exists ? 'replaced' : 'success') . "'>"
                        . "<input type='checkbox' checked disabled>"
                        . "<span><a href='{$filename}' target='{$filename}'>{$filename}</a></span>"
                        . "<span>" . ($exists ? "REPLACED" : "COPIED") . "</span>"
                        . "</div>";
                }
            }
            $_SESSION['results'] = $results;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        // Handle backup action
        if ($_POST['action'] === 'backup') {
            $source = __DIR__;
            $destination = $_POST['destination'];

            // Ensure the destination directory exists
            if (!is_dir($destination)) {
                echo json_encode(['success' => false, 'message' => 'Invalid destination directory']);
                exit;
            }

            // Get total size first
            $totalSize = 0;
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($files as $file) {
                $totalSize += $file->getSize();
            }

            // Send progress updates during copy
            session_write_close(); // Allow multiple requests
            header('Content-Type: application/json');
            ob_implicit_flush(true);

            $copiedSize = 0;
            function copyWithProgress($src, $dst, &$copiedSize, $totalSize)
            {
                if (!file_exists($dst)) {
                    mkdir($dst, 0755, true);
                }
                foreach (scandir($src) as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $srcPath = $src . DIRECTORY_SEPARATOR . $file;
                        $dstPath = $dst . DIRECTORY_SEPARATOR . $file;
                        if (is_dir($srcPath)) {
                            copyWithProgress($srcPath, $dstPath, $copiedSize, $totalSize);
                        } else {
                            copy($srcPath, $dstPath);
                            $copiedSize += filesize($srcPath);
                            $progress = round(($copiedSize / $totalSize) * 100);
                            echo json_encode(['progress' => $progress]);
                            ob_flush();
                        }
                    }
                }
            }

            copyWithProgress($source, $destination, $copiedSize, $totalSize);
            echo json_encode(['success' => true, 'message' => 'Backup complete: ' . $destination]);
            exit;
        }
    }
    ?>
  <!DOCTYPE html>
  <html lang="en">

  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>NetBound Tools: File Sync</title>
      <style>
          body {
              font-family: Arial, sans-serif;
              margin: 0;
              padding: 20px;
              background-color: #f4f4f9;
              min-height: 100vh;
              box-sizing: border-box;
          }

          .container {
              background: white;
              border-radius: 10px;
              box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
              width: 800px;
              margin: 0 auto;
              box-sizing: border-box;
              overflow: hidden;
          }

          .header {
              background-color: #0056b3;
              color: white;
              padding: 15px 20px;
              display: flex;
              justify-content: space-between;
              align-items: center;
          }

          .header h1 {
              margin: 0;
              font-size: 18px;
              font-weight: normal;
          }

          .header-button {
              background-color: white;
              color: #0056b3;
              border: none;
              padding: 5px 15px;
              border-radius: 4px;
              cursor: pointer;
              font-size: 14px;
              margin-left: 10px;
              font-weight: bold;
          }

          .page-title {
              text-align: center;
              padding: 15px 0;
              border-bottom: 1px solid #eee;
          }

          .page-title h1 {
              margin: 0;
              font-size: 24px;
          }

          .content {
              padding: 20px;
          }

          .controls {
              display: flex;
              justify-content: center;
              gap: 10px;
              margin-bottom: 20px;
          }

          .button-blue {
              background-color: #0056b3;
              color: white;
              padding: 8px 12px;
              border: none;
              border-radius: 5px;
              cursor: pointer;
              min-width: 120px;
          }

          .file-item {
              display: grid;
              grid-template-columns: 30px auto 100px;
              gap: 10px;
              padding: 5px;
              margin: 2px 0;
              background: white;
              border-radius: 3px;
              align-items: center;
          }

          .file-item.replaced {
              color: orange;
          }

          .file-item.success {
              color: green;
          }

          .status-box {
              background-color: #f8f9fa;
              border: 1px solid #ddd;
              padding: 10px;
              margin: 10px 0;
              border-radius: 4px;
          }

          @media (max-width: 850px) {
              .container {
                  width: 95%;
              }

              .controls {
                  flex-direction: column;
              }

              .button-blue {
                  width: 100%;
                  margin: 5px 0;
              }
          }
      </style>
  </head>

  <body>
      <div class="container">
          <div class="header">
              <h1>NetBound Tools: File Sync</h1>
              <div>
                  <button class="header-button" onclick="window.location.href='/'">HOME</button>
                  <button class="header-button" onclick="location.reload()">RESET</button>
              </div>
          </div>

          <div class="page-title">
              <h1>File Sync</h1>
              <div class="version">Version 1.0</div>
          </div>

          <div class="content">
              <div class="controls">
                  <button class="button-blue" onclick="document.getElementById('fileInput').click()">TRANSFER TO HERE</button>
                  <form method="post" style="display: inline;">
                      <button class="button-blue" name="redo" type="submit"
                          <?php echo !isset($_SESSION['last_files']) ? 'disabled' : ''; ?>>
                          REPEAT LAST
                      </button>
                  </form>
              </div>

              <form method="post" enctype="multipart/form-data" id="uploadForm">
                  <input type="file" id="fileInput" name="files[]" multiple style="display: none"
                      onchange="document.getElementById('uploadForm').submit()">
              </form>

              <div class="status-box">
                  Status: <span id="status">
                      <?php
                        if (isset($_SESSION['results'])) {
                            echo implode("", $_SESSION['results']);
                            unset($_SESSION['results']);
                        } else {
                            echo "Ready";
                        }
                        ?>
                  </span>
              </div>
          </div>
      </div>
  </body>

  </html>

  </html>
