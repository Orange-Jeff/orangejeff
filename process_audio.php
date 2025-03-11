<?php

/**
 * NetBound Speaker Splitter - Audio Processing Backend
 * Version: 1.0
 *
 * DEPENDENCIES:
 * - PHP 7.0+ with:
 *   - file handling functions (fopen, fread, fwrite, fclose)
 *   - JSON handling (json_decode, json_encode)
 *   - Directory management (mkdir, is_dir)
 *   - Upload handling capabilities
 *
 * - File system:
 *   - Write permissions to 'uploads/' directory
 *   - Write permissions to 'processed_audio/' directory
 *
 * - Frontend:
 *   - nb-voice-split.php provides the UI and sends data to this script
 *   - Requires form submission with audioFile upload and regions JSON data
 *
 * DESCRIPTION:
 * This script handles the server-side processing of audio files for speaker separation.
 * It extracts portions of WAV files based on region markers and creates separate files
 * for each speaker.
 */

// Increase upload limits at runtime
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '50M');
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');

// Set proper content type for JSON response
header('Content-Type: application/json');

// Better error logging setup
ini_set('display_errors', 0);
error_reporting(E_ALL);

function logError($message)
{
    file_put_contents('error_log.txt', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

/**
 * Extract a portion of a WAV file
 * @param string $sourceFile Path to source WAV file
 * @param string $outputFile Path to output WAV file
 * @param array $regions Array of start/end time regions to extract in seconds
 */
function extractWavRegions($sourceFile, $outputFile, $regions)
{
    logError("Extracting regions from $sourceFile to $outputFile");

    // Open the source file
    $handle = fopen($sourceFile, 'rb');
    if (!$handle) {
        logError("Failed to open source file: $sourceFile");
        return false;
    }

    // Read WAV header (44 bytes for standard WAV format)
    $header = fread($handle, 44);

    // Parse header to get format info
    $channels = ord($header[22]) | (ord($header[23]) << 8);
    $sampleRate = ord($header[24]) | (ord($header[25]) << 8) | (ord($header[26]) << 16) | (ord($header[27]) << 24);
    $bitsPerSample = ord($header[34]) | (ord($header[35]) << 8);
    $bytesPerSample = $bitsPerSample / 8;
    $bytesPerSecond = $sampleRate * $channels * $bytesPerSample;

    logError("WAV Info: Channels=$channels, Rate=$sampleRate, Bits=$bitsPerSample");

    // Create output file with the same header
    $outHandle = fopen($outputFile, 'wb');
    if (!$outHandle) {
        logError("Failed to create output file: $outputFile");
        fclose($handle);
        return false;
    }

    // Write temporary header (we'll update this later)
    fwrite($outHandle, $header);

    // Keep track of total data bytes written
    $totalDataBytes = 0;

    // Process each region and extract audio
    foreach ($regions as $region) {
        $startTime = floatval($region['start']);
        $endTime = floatval($region['end']);
        $duration = $endTime - $startTime;

        if ($duration <= 0) continue;

        // Calculate byte positions
        $startByte = 44 + (int)($startTime * $bytesPerSecond);
        $regionBytes = (int)($duration * $bytesPerSecond);

        // Seek to start position
        fseek($handle, $startByte);

        // Read and write chunk by chunk to avoid memory issues
        $chunkSize = 8192; // 8KB chunks
        $bytesLeft = $regionBytes;

        while ($bytesLeft > 0) {
            $readSize = min($bytesLeft, $chunkSize);
            $data = fread($handle, $readSize);
            if ($data === false || strlen($data) === 0) break;

            fwrite($outHandle, $data);
            $bytesLeft -= strlen($data);
            $totalDataBytes += strlen($data);
        }

        logError("Extracted region: $startTime - $endTime ($duration sec)");
    }

    // Update header with correct file size
    $fileSize = $totalDataBytes + 36; // +36 for WAV header minus 8 bytes for RIFF header
    fseek($outHandle, 4);
    fwrite($outHandle, pack('V', $fileSize));

    // Update header with correct data size
    fseek($outHandle, 40);
    fwrite($outHandle, pack('V', $totalDataBytes));

    // Close files
    fclose($handle);
    fclose($outHandle);

    logError("Finished extracting to $outputFile, size: $totalDataBytes bytes");
    return true;
}

try {
    logError("Script started - processing audio file");

    // Create directories
    $uploadDir = 'uploads/';
    $outputDir = 'processed_audio/';

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

    // Log received data
    logError("POST data: " . json_encode($_POST));
    logError("FILES data: " . json_encode($_FILES));

    // Process the uploaded file
    if (!isset($_FILES['audioFile']) || $_FILES['audioFile']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload failed: " .
            ($_FILES['audioFile']['error'] ?? "No file uploaded"));
    }

    // Save uploaded file to our uploads directory
    $baseName = pathinfo($_FILES['audioFile']['name'], PATHINFO_FILENAME);
    $tempFile = $_FILES['audioFile']['tmp_name'];
    $uploadedFile = $uploadDir . $baseName . '_original.wav';

    // Move the uploaded file to a permanent location
    if (!move_uploaded_file($tempFile, $uploadedFile)) {
        throw new Exception("Failed to move uploaded file");
    }

    logError("File saved to: " . $uploadedFile);

    // Parse regions data
    $regions = json_decode($_POST['regions'] ?? '{}', true);
    logError("Regions data: " . json_encode($regions));

    // Define output files
    $speaker1File = "{$outputDir}{$baseName}_speaker1.wav";
    $speaker2File = "{$outputDir}{$baseName}_speaker2.wav";
    $stereoFile = "{$outputDir}{$baseName}_stereo.wav"; // We'll implement this later

    // Process speaker 1 regions
    $speaker1Regions = $regions['speaker1'] ?? [];
    if (count($speaker1Regions) > 0) {
        extractWavRegions($uploadedFile, $speaker1File, $speaker1Regions);
    }

    // Process speaker 2 regions
    $speaker2Regions = $regions['speaker2'] ?? [];
    if (count($speaker2Regions) > 0) {
        extractWavRegions($uploadedFile, $speaker2File, $speaker2Regions);
    }

    // For now we'll skip stereo file creation - that's more complex
    // and would require interleaving the two speaker files

    // Output the success response
    echo json_encode([
        'status' => 'success',
        'speaker1' => $speaker1File,
        'speaker2' => $speaker2File,
        'stereo' => '' // Skip stereo for now
    ]);
} catch (Throwable $e) {
    // Log any errors
    logError("ERROR: " . $e->getMessage());
    logError("Stack trace: " . $e->getTraceAsString());

    // Return error response
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
