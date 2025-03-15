<?php
// Simple upload test
header('Content-Type: application/json');

file_put_contents('test_log.txt', 'Test request at ' . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents('test_log.txt', 'POST: ' . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents('test_log.txt', 'FILES: ' . print_r($_FILES, true) . "\n", FILE_APPEND);

if (isset($_FILES['testFile']) && $_FILES['testFile']['error'] === UPLOAD_ERR_OK) {
    $uploadedFile = $_FILES['testFile']['tmp_name'];
    $fileSize = filesize($uploadedFile);

    echo json_encode([
        'success' => true,
        'message' => "File received: {$_FILES['testFile']['name']}, size: $fileSize bytes"
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No file received or error occurred',
        'error' => isset($_FILES['testFile']) ? $_FILES['testFile']['error'] : 'No file in request'
    ]);
}
?>
