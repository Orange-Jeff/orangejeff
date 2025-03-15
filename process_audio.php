<?php

// Prevent PHP errors from breaking JSON output
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set higher PHP limits for audio processing
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
    file_put_contents('audio_splitter.log', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

function logError($message)
{
    logMessage('ERROR: ' . $message);
}

// Get public URL for a file
function getPublicPath($filePath)
{
    // Convert server path to URL
    $baseDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $relativePath = str_replace('\\', '/', substr($filePath, strlen($_SERVER['DOCUMENT_ROOT'])));

    // Default to relative path if we can't determine the document root
    if (empty($relativePath) || $relativePath === $filePath) {
        $relativePath = 'output/' . basename($filePath);
    }

    return $relativePath;
}

// Generate output filenames based on input
function generateOutputFilenames($outputDir, $fileBaseName)
{
    $timestamp = date('YmdHis');
    return [
        'speaker1' => $outputDir . '/speaker1_' . $timestamp . '_' . $fileBaseName,
        'speaker2' => $outputDir . '/speaker2_' . $timestamp . '_' . $fileBaseName,
        'stereo' => $outputDir . '/combined_' . $timestamp . '_' . $fileBaseName,
    ];
}

// Extract a specific segment from a WAV file
function extractWavRegion($sourceFile, $outputFile, $startTime, $endTime)
{
    // Get file info
    $fileInfo = pathinfo($sourceFile);
    $extension = strtolower($fileInfo['extension']);

    if ($extension !== 'wav') {
        throw new Exception("Only WAV files are supported");
    }

    // Calculate how many bytes to skip and read
    $handle = fopen($sourceFile, 'rb');
    if (!$handle) {
        throw new Exception("Could not open source file: $sourceFile");
    }

    // Read header to find data chunk
    $header = fread($handle, 44);

    // Get sample rate, bit depth and channels from header
    $sampleRate = unpack('V', substr($header, 24, 4))[1];
    $channels = unpack('v', substr($header, 22, 2))[1];
    $bytesPerSample = unpack('v', substr($header, 34, 2))[1] / 8;

    // Calculate bytes per second for seeking
    $bytesPerSecond = $sampleRate * $channels * $bytesPerSample;

    // Position to skip from data chunk
    $startPos = 44 + round($startTime * $bytesPerSecond);
    $endPos = 44 + round($endTime * $bytesPerSecond);
    $dataSize = $endPos - $startPos;

    // Create output file
    $outHandle = fopen($outputFile, 'wb');
    if (!$outHandle) {
        fclose($handle);
        throw new Exception("Could not create output file: $outputFile");
    }

    // Copy header
    fwrite($outHandle, $header, 44);

    // Update header with new data size
    updateWavHeaders($outHandle, $dataSize);

    // Seek to start position
    fseek($handle, $startPos);

    // Copy data
    $bufferSize = 8192;
    $bytesLeft = $dataSize;

    while ($bytesLeft > 0) {
        $readSize = min($bufferSize, $bytesLeft);
        $buffer = fread($handle, $readSize);
        fwrite($outHandle, $buffer, $readSize);
        $bytesLeft -= $readSize;
    }

    fclose($outHandle);
    fclose($handle);

    return $outputFile;
}

// Update WAV headers with new data size
function updateWavHeaders($handle, $dataSize)
{
    // Update data chunk size
    fseek($handle, 40);
    fwrite($handle, pack('V', $dataSize));

    // Update RIFF chunk size
    $riffSize = 36 + $dataSize;
    fseek($handle, 4);
    fwrite($handle, pack('V', $riffSize));
}

// Concatenate multiple WAV files
function concatenateWavFiles($outputFile, $inputFiles)
{
    if (empty($inputFiles)) {
        throw new Exception("No input files provided for concatenation");
    }

    // Open first file to copy header
    $firstFile = fopen($inputFiles[0], 'rb');
    if (!$firstFile) {
        throw new Exception("Could not open first input file: {$inputFiles[0]}");
    }

    // Read header from first file
    $header = fread($firstFile, 44);

    // Get audio format details
    $sampleRate = unpack('V', substr($header, 24, 4))[1];
    $channels = unpack('v', substr($header, 22, 2))[1];
    $bytesPerSample = unpack('v', substr($header, 34, 2))[1] / 8;

    // Create output file
    $outHandle = fopen($outputFile, 'wb');
    if (!$outHandle) {
        fclose($firstFile);
        throw new Exception("Could not create output file: $outputFile");
    }

    // Write initial header
    fwrite($outHandle, $header, 44);

    // Calculate total data size
    $totalDataSize = 0;

    // Process each file
    foreach ($inputFiles as $file) {
        // Open file
        $handle = fopen($file, 'rb');
        if (!$handle) {
            fclose($outHandle);
            throw new Exception("Could not open file: $file");
        }

        // Skip header (44 bytes)
        fseek($handle, 44);

        // Get file size and calculate data size
        fseek($handle, 0, SEEK_END);
        $fileSize = ftell($handle);
        $dataSize = $fileSize - 44;

        // Reset to data start
        fseek($handle, 44);

        // Copy data
        $bufferSize = 8192;
        $bytesLeft = $dataSize;

        while ($bytesLeft > 0) {
            $readSize = min($bufferSize, $bytesLeft);
            $buffer = fread($handle, $readSize);
            fwrite($outHandle, $buffer, $readSize);
            $bytesLeft -= $readSize;
        }

        fclose($handle);
        $totalDataSize += $dataSize;
    }

    // Update headers with total data size
    updateWavHeaders($outHandle, $totalDataSize);
    fclose($outHandle);

    return $outputFile;
}

// Main processing code
try {
    // Verify request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    // Check for required data
    if (!isset($_POST['regions']) || empty($_POST['regions'])) {
        throw new Exception("No regions data provided");
    }

    // Create output directory
    $outputDir = __DIR__ . '/output';
    if (!file_exists($outputDir)) {
        if (!mkdir($outputDir, 0777, true)) {
            throw new Exception("Failed to create output directory");
        }
    }

    // Handle file upload
    $audioFile = null;
    if (isset($_FILES['audioFile']) && $_FILES['audioFile']['error'] === UPLOAD_ERR_OK) {
        // Use uploaded file
        $audioFile = $_FILES['audioFile']['tmp_name'];
        $originalFileName = $_FILES['audioFile']['name'];
    } else {
        // Check if we have a file name and the file exists in the output directory
        if (isset($_POST['fileName']) && !empty($_POST['fileName'])) {
            $potentialFile = $outputDir . '/' . basename($_POST['fileName']);
            if (file_exists($potentialFile)) {
                $audioFile = $potentialFile;
                $originalFileName = basename($_POST['fileName']);
            }
        }

        if (!$audioFile) {
            throw new Exception("No audio file provided");
        }
    }

    // Parse regions data
    $regions = json_decode($_POST['regions'], true);
    if (!$regions || !is_array($regions)) {
        throw new Exception("Invalid regions data format");
    }

    // Generate output filenames
    $outputFiles = generateOutputFilenames($outputDir, pathinfo($originalFileName, PATHINFO_FILENAME) . '.wav');

    // Process regions by type
    $speakerFiles = [
        'speaker1' => [],
        'speaker2' => [],
    ];

    foreach ($regions as $region) {
        if ($region['type'] === 'trash') {
            // Skip trash regions
            continue;
        }

        // Create temporary file for the segment
        $tempFile = $outputDir . '/temp_' . uniqid() . '.wav';

        // Extract the segment
        extractWavRegion($audioFile, $tempFile, $region['start'], $region['end']);

        // Add to appropriate speaker array
        $speakerFiles[$region['type']][] = $tempFile;
    }

    // Create speaker files by concatenating segments
    $createdFiles = [];

    // Process speaker 1
    if (!empty($speakerFiles['speaker1'])) {
        concatenateWavFiles($outputFiles['speaker1'], $speakerFiles['speaker1']);
        $createdFiles['speaker1'] = $outputFiles['speaker1'];

        // Clean up temp files
        foreach ($speakerFiles['speaker1'] as $tempFile) {
            @unlink($tempFile);
        }
    }

    // Process speaker 2
    if (!empty($speakerFiles['speaker2'])) {
        concatenateWavFiles($outputFiles['speaker2'], $speakerFiles['speaker2']);
        $createdFiles['speaker2'] = $outputFiles['speaker2'];

        // Clean up temp files
        foreach ($speakerFiles['speaker2'] as $tempFile) {
            @unlink($tempFile);
        }
    }

    // Generate URLs for created files
    $urls = [];
    foreach ($createdFiles as $type => $file) {
        $urls[$type . 'Url'] = getPublicPath($file);
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Audio processed successfully',
        'speaker1Url' => isset($urls['speaker1Url']) ? $urls['speaker1Url'] : null,
        'speaker2Url' => isset($urls['speaker2Url']) ? $urls['speaker2Url'] : null,
        'stereoUrl' => isset($urls['stereoUrl']) ? $urls['stereoUrl'] : null,
    ]);
} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
