<?php

// Prevent PHP errors from breaking JSON output
ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * NetBound Tools: Speaker Audio Splitter Processing Backend
 * Version: 1.4
 * Created by: NetBound Team
 *
 * This file handles the server-side processing of audio files for speaker separation.
 * It extracts portions of WAV files based on region markers and creates separate files
 * for each speaker.
 */

// Set higher PHP limits for audio processing - increased to handle larger files
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '600');
ini_set('max_input_time', '600');
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');

// Set content type for JSON response
header('Content-Type: application/json; charset=utf-8');

// Add required functions directly to avoid dependency
function logMessage($message)
{
    $logFile = __DIR__ . '/audio_processing.log';
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

function logError($message)
{
    logMessage("ERROR: " . $message);
}

/**
 * Get public path for a file by removing the directory prefix
 * @param string $filePath Full file path
 * @return string Public URL path
 */
function getPublicPath($filePath)
{
    return str_replace(__DIR__ . '/', '', $filePath);
}

/**
 * Generate output filenames for processed audio files
 * @param string $outputDir Directory for output files
 * @param string $fileBaseName Base name for output files
 * @return array Array with paths for speaker1, speaker2, and stereo files
 */
function generateOutputFilenames($outputDir, $fileBaseName)
{
    return [
        'speaker1' => "$outputDir/{$fileBaseName}_speaker1.wav",
        'speaker2' => "$outputDir/{$fileBaseName}_speaker2.wav",
        'stereo' => "$outputDir/{$fileBaseName}_stereo.wav"
    ];
}

/**
 * Extract a portion of a WAV file
 * @param string $sourceFile Path to source WAV file
 * @param string $outputFile Path to output WAV file
 * @param float $startTime Start time in seconds
 * @param float $endTime End time in seconds
 */
function extractWavRegion($sourceFile, $outputFile, $startTime, $endTime)
{
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

/**
 * Update WAV file headers with correct sizes
 * @param resource $handle File handle
 * @param int $dataSize Size of audio data
 */
function updateWavHeaders($handle, $dataSize)
{
    $fileSize = $dataSize + 36; // Total file size minus 8 bytes for RIFF header
    fseek($handle, 4);
    fwrite($handle, pack('V', $fileSize));
    fseek($handle, 40);
    fwrite($handle, pack('V', $dataSize));
}

/**
 * Concatenate WAV files while preserving header information
 * @param string $outputFile Path to output WAV file
 * @param array $inputFiles Array of input WAV files to concatenate
 */
function concatenateWavFiles($outputFile, $inputFiles)
{
    if (empty($inputFiles)) {
        throw new Exception("No input files provided for concatenation");
    }

    // Read first file's header for format info
    $firstFile = fopen($inputFiles[0], 'rb');
    $header = fread($firstFile, 44);
    fclose($firstFile);

    // Create output file
    $output = fopen($outputFile, 'wb');
    fwrite($output, $header); // Write initial header

    $totalDataSize = 0;

    // Process each file
    foreach ($inputFiles as $file) {
        $handle = fopen($file, 'rb');
        fseek($handle, 44); // Skip header

        // Copy audio data
        while (!feof($handle)) {
            $data = fread($handle, 8192);
            $totalDataSize += strlen($data);
            fwrite($output, $data);
        }
        fclose($handle);
    }

    // Update headers with total size
    updateWavHeaders($output, $totalDataSize);
    fclose($output);
}

// Main processing code
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Only POST method is accepted");
    }

    if (empty($_FILES['audioFile']) || $_FILES['audioFile']['error'] > 0) {
        throw new Exception("No audio file uploaded or upload error");
    }

    if (empty($_POST['regions'])) {
        throw new Exception("No regions data provided");
    }

    // Parse regions data
    $regions = null;
    try {
        // Clean the JSON data first
        $data = trim($_POST['regions']);
        // Remove UTF-8 BOM if present
        if (substr($data, 0, 3) == pack('CCC', 0xEF, 0xBB, 0xBF)) {
            $data = substr($data, 3);
        }
        // Strip any control characters
        $data = preg_replace('/[\x00-\x1F\x7F]/u', '', $data);

        $regions = json_decode($data, true);

        if ($regions === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON Error: " . json_last_error_msg());
        }
    } catch (Exception $e) {
        throw new Exception("Failed to parse regions data: " . $e->getMessage());
    }

    if (!is_array($regions)) {
        throw new Exception("Invalid regions data format");
    }

    // Process the uploaded file
    $tempFile = $_FILES['audioFile']['tmp_name'];
    $fileName = $_FILES['audioFile']['name'];

    // Create directory for output files if it doesn't exist
    $outputDir = __DIR__ . '/processed_audio';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }

    // Base names for output files
    $fileBaseName = pathinfo($fileName, PATHINFO_FILENAME);
    $outputFiles = generateOutputFilenames($outputDir, $fileBaseName);

    $tempDir = sys_get_temp_dir();
    $regionFiles = [];

    // Process speaker1 regions
    if (!empty($regions['speaker1'])) {
        $regionFiles['speaker1'] = [];
        foreach ($regions['speaker1'] as $region) {
            $tempOutput = $tempDir . '/region_' . uniqid() . '.wav';
            extractWavRegion($tempFile, $tempOutput, $region['start'], $region['end']);
            $regionFiles['speaker1'][] = $tempOutput;
        }
        // Concatenate all speaker1 regions
        concatenateWavFiles($outputFiles['speaker1'], $regionFiles['speaker1']);
        // Cleanup temp files
        foreach ($regionFiles['speaker1'] as $file) {
            unlink($file);
        }
    }

    // Process speaker2 regions
    if (!empty($regions['speaker2'])) {
        $regionFiles['speaker2'] = [];
        foreach ($regions['speaker2'] as $region) {
            $tempOutput = $tempDir . '/region_' . uniqid() . '.wav';
            extractWavRegion($tempFile, $tempOutput, $region['start'], $region['end']);
            $regionFiles['speaker2'][] = $tempOutput;
        }
        // Concatenate all speaker2 regions
        concatenateWavFiles($outputFiles['speaker2'], $regionFiles['speaker2']);
        // Cleanup temp files
        foreach ($regionFiles['speaker2'] as $file) {
            unlink($file);
        }
    }

    // Create stereo output by combining speaker1 and speaker2
    if (!empty($regions['speaker1']) && !empty($regions['speaker2'])) {
        // For now just copy the original file
        // TODO: Implement proper stereo mixing of speaker1 to left channel
        // and speaker2 to right channel
        copy($tempFile, $outputFiles['stereo']);
        logMessage("Created stereo mix file");
    } else {
        // If only one speaker, just copy their audio
        copy(
            !empty($regions['speaker1']) ? $outputFiles['speaker1'] : $outputFiles['speaker2'],
            $outputFiles['stereo']
        );
    }

    logMessage("Audio processing completed for $fileName");

    // Output response
    echo json_encode([
        'status' => 'success',
        'speaker1' => getPublicPath($outputFiles['speaker1']),
        'speaker2' => getPublicPath($outputFiles['speaker2']),
        'stereo' => getPublicPath($outputFiles['stereo'])
    ]);
} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
