<?php
/**
 * NetBound Tools: Speaker Audio Splitter Advanced Processing
 * Version: 1.4
 * Created by: NetBound Team
 *
 * This file handles advanced audio processing options for the Speaker Splitter tool.
 */

ob_start();
register_shutdown_function(function() {
    $error = error_get_last();
    $output = ob_get_clean();

    if ($error !== null || empty($output)) {
        $debug = "Debug Info:\n";
        $debug .= "Error: " . ($error ? json_encode($error) : "None") . "\n";
        $debug .= "Output Length: " . strlen($output) . "\n";
        $debug .= "Headers Sent: " . (headers_sent() ? "Yes" : "No") . "\n";
        if (headers_sent($file, $line)) {
            $debug .= "Headers sent in $file on line $line\n";
        }
        $debug .= "Memory Usage: " . memory_get_usage(true)/1024/1024 . "MB\n";

        error_log($debug);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Processing error occurred',
            'debug' => $debug
        ]);
        return;
    }

    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo $output;
});

// Set higher PHP limits for audio processing
ini_set('memory_limit', '1024M'); // Increased to match main file
ini_set('max_execution_time', '900'); // Increased to match main file
ini_set('max_input_time', '900'); // Increased to match main file
ini_set('upload_max_filesize', '500M');
ini_set('post_max_size', '500M');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper function for logging
function logMessage($message) {
    $logFile = __DIR__ . '/audio_processing.log';
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

function logError($message) {
    logMessage("ERROR: " . $message);
}

/**
 * Extract a portion of a WAV file
 * @param string $sourceFile Path to source WAV file
 * @param string $outputFile Path to output WAV file
 * @param float $startTime Start time in seconds
 * @param float $endTime End time in seconds
 */
function extractWavRegion($sourceFile, $outputFile, $startTime, $endTime) {
    logMessage("Extracting from $sourceFile to $outputFile");

    // Open the source file
    $handle = fopen($sourceFile, 'rb');
    if (!$handle) {
        throw new Exception("Failed to open source file: $sourceFile");
    }

    // Read WAV header (44 bytes for standard WAV format)
    $header = fread($handle, 44);

    // Extract important WAV parameters from header
    $channels = ord($header[22]) | (ord($header[23]) << 8);
    $sampleRate = ord($header[24]) | (ord($header[25]) << 8) | (ord($header[26]) << 16) | (ord($header[27]) << 24);
    $bytesPerSample = (ord($header[34]) | (ord($header[35]) << 8)) / 8;

    // Calculate positions
    $bytesPerSecond = $sampleRate * $channels * $bytesPerSample;
    $startPos = (int)($startTime * $bytesPerSecond) + 44; // Add header size
    $endPos = (int)($endTime * $bytesPerSecond) + 44;

    // Create output file
    $outputHandle = fopen($outputFile, 'wb');
    if (!$outputHandle) {
        fclose($handle);
        throw new Exception("Failed to create output file: $outputFile");
    }

    // Write header (we'll update this later)
    fwrite($outputHandle, $header);

    // Seek to start position
    fseek($handle, $startPos);

    // Calculate bytes to read
    $bytesLeft = $endPos - $startPos;
    $chunkSize = 8192; // Read in chunks
    $totalDataBytes = 0;

    logMessage("Extracting from $startTime to $endTime ($bytesLeft bytes)");

    // Read and write data
    while ($bytesLeft > 0) {
        $readSize = min($bytesLeft, $chunkSize);
        $data = fread($handle, $readSize);
        fwrite($outputHandle, $data);
        $bytesLeft -= strlen($data);
        $totalDataBytes += strlen($data);
    }

    logMessage("Extracted from $startTime to $endTime ($totalDataBytes bytes)");

    // Update file size in header
    $fileSize = ftell($outputHandle) - 8; // File size minus 8 bytes for RIFF header
    fseek($outputHandle, 4);
    fwrite($outputHandle, pack('V', $fileSize));

    // Update header with correct data size
    $dataSize = $totalDataBytes;
    fseek($outputHandle, 40);
    fwrite($outputHandle, pack('V', $dataSize));

    // Close files
    fclose($handle);
    fclose($outputHandle);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Only POST method is accepted");
    }

    if (empty($_FILES['audioFile']) || $_FILES['audioFile']['error'] > 0) {
        throw new Exception("No audio file uploaded or upload error: " . $_FILES['audioFile']['error']);
    }

    if (empty($_POST['regions']) || empty($_POST['speaker']) || empty($_POST['option'])) {
        throw new Exception("Missing required parameters");
    }

    // Get parameters
    $speaker = $_POST['speaker'];
    $option = $_POST['option'];
    $fileName = $_POST['fileName'];
    $regions = json_decode($_POST['regions'], true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid regions JSON data");
    }

    // Process the uploaded file
    $tempFile = $_FILES['audioFile']['tmp_name'];

    // Create directory for output files if it doesn't exist
    $outputDir = __DIR__ . '/processed_audio';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }

    // Generate output filename based on speaker and option
    $fileBaseName = pathinfo($fileName, PATHINFO_FILENAME);
    $outputFile = "$outputDir/{$fileBaseName}_{$speaker}_{$option}.wav";

    // Basic implementation - just copy the file for now as a placeholder
    // In a real implementation, you would process regions differently based on option
    if (!copy($tempFile, $outputFile)) {
        throw new Exception("Failed to create output file");
    }

    // Output response
    echo json_encode([
        'status' => 'success',
        'file' => "processed_audio/{$fileBaseName}_{$speaker}_{$option}.wav",
        'filename' => "{$fileBaseName}_{$speaker}_{$option}.wav"
    ]);

} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
