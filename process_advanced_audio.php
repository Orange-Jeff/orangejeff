<?php
// Set error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log function for debugging
function log_debug($message)
{
    file_put_contents('process_debug.log', date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

log_debug("Request received");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    log_debug("Error: Only POST method is accepted");
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Only POST method is accepted'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    log_debug("Error: Only POST method is accepted");
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Only POST method is accepted'
    ]);
    exit;
}

// Ensure we have output directory
$output_dir = __DIR__ . '/output';
if (!file_exists($output_dir)) {
    mkdir($output_dir, 0777, true);
}

// Check for file upload
if (!isset($_FILES['audioFile']) || $_FILES['audioFile']['error'] !== UPLOAD_ERR_OK) {
    log_debug("No file or file upload error: " . json_encode($_FILES));
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'No audio file received or upload error'
    ]);
    exit;
}

// Log uploaded file info
log_debug("File received: " . $_FILES['audioFile']['name'] . ", size: " . $_FILES['audioFile']['size']);
log_debug("Speaker: " . ($_POST['speaker'] ?? 'unknown') . ", Option: " . ($_POST['option'] ?? 'unknown'));

// Get processing parameters
$speaker = $_POST['speaker'] ?? 'speaker1';
$option = $_POST['option'] ?? 'default';
$source_file = $_FILES['audioFile']['tmp_name'];
$timestamp = date('YmdHis');
$output_filename = "{$speaker}_{$option}_{$timestamp}.wav";
$output_path = $output_dir . '/' . $output_filename;

// For now, just copy the file to simulate processing
if (copy($source_file, $output_path)) {
    log_debug("File processed and saved: $output_path");

    // Get the web-accessible URL
    $web_path = 'output/' . $output_filename;

    // Return success with download URL
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'fileUrl' => $web_path,
        'fileName' => $output_filename,
        'message' => 'Audio processed successfully'
    ]);
} else {
    log_debug("Failed to save file to: $output_path");
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process audio file'
    ]);
}
