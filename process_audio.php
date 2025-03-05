<?php
// Increase upload limits at runtime
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '50M');
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');

// Note: upload_max_filesize cannot typically be changed at runtime
// Prevent any output before our JSON response
ob_start();

// Set proper content type for JSON response
header('Content-Type: application/json');

// Completely disable output of PHP errors to the browser
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Function to log errors to a file instead of displaying them
function logError($message)
{
    file_put_contents('error_log.txt', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

try {
    logError("Script started - basic test version");

    // Create directories
    $uploadDir = 'uploads/';
    $outputDir = 'processed_audio/';

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

    // Log received data
    logError("POST data: " . json_encode($_POST));
    logError("FILES data: " . json_encode($_FILES));

    // Skip all processing for debugging - just create output files
    if (isset($_FILES['audioFile']['name'])) {
        $baseName = pathinfo($_FILES['audioFile']['name'], PATHINFO_FILENAME);
    } else {
        $baseName = "test";
    }

    $speaker1File = "{$outputDir}{$baseName}_speaker1.wav";
    $speaker2File = "{$outputDir}{$baseName}_speaker2.wav";
    $stereoFile = "{$outputDir}{$baseName}_stereo.wav";

    // Just touch the files (don't try to move or process)
    file_put_contents($speaker1File, "test");
    file_put_contents($speaker2File, "test");
    file_put_contents($stereoFile, "test");

    logError("Created temporary output files");

    // Output the success response
    echo json_encode([
        'status' => 'success',
        'speaker1' => $speaker1File,
        'speaker2' => $speaker2File,
        'stereo' => $stereoFile
    ]);
} catch (Throwable $e) {
    // Log any errors
    logError("ERROR: " . $e->getMessage());
    logError("Stack trace: " . $e->getTraceAsString());

    // Clear any previous output
    ob_clean();

    // Return error response
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
