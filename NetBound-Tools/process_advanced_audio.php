<?php
// Set error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log function
function log_debug($message) {
    file_put_contents('audio_processing.log', date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

log_debug("Request received");

// Ensure output directory exists
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

// Get processing parameters
$speaker = $_POST['speaker'] ?? 'speaker1';
$option = $_POST['option'] ?? 'default';
$timestamp = date('YmdHis');
$input_filename = basename($_FILES['audioFile']['name']);
$output_filename = "{$input_filename}_{$speaker}_{$option}_{$timestamp}.wav";
$temp_output = $output_dir . '/temp_' . uniqid() . '.wav';
$output_path = $output_dir . '/' . $output_filename;

// Parse regions data
$regions = json_decode($_POST['regions'], true);
if (!$regions || !isset($regions['speaker1']) || !isset($regions['speaker2'])) {
    log_debug("Invalid regions data provided");
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid regions data format'
    ]);
    exit;
}

try {
    // Move uploaded file to temp location
    if (!move_uploaded_file($_FILES['audioFile']['tmp_name'], $temp_output)) {
        throw new Exception("Failed to create temporary file");
    }

    // Process based on option
    switch ($option) {
        case 'default':
            // Create output file
            if (!copy($temp_output, $output_path)) {
                throw new Exception("Failed to create output file");
            }

            // Mute regions of other speaker
            $other_speaker = $speaker === 'speaker1' ? 'speaker2' : 'speaker1';
            if (isset($regions[$other_speaker])) {
                foreach ($regions[$other_speaker] as $region) {
                    muteRegion($output_path, $region['start'], $region['end']);
                }
            }
            break;

        case 'full_lr':
            // Create stereo version with speakers on different channels
            if (!createStereoSeparation($temp_output, $regions, $output_path)) {
                throw new Exception("Failed to create stereo separation");
            }
            break;

        default:
            throw new Exception("Unknown processing option: $option");
    }

    // Success response
    log_debug("Processing successful: $output_filename");
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Audio processed successfully',
        'fileUrl' => 'output/' . $output_filename,
        'fileName' => $output_filename
    ]);

} catch (Exception $e) {
    log_debug("Error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Clean up temp file
    if (file_exists($temp_output)) {
        @unlink($temp_output);
    }
}

// Audio processing functions
function muteRegion($file, $start, $end) {
    $handle = fopen($file, 'r+b');
    if (!$handle) {
        throw new Exception("Could not open file for muting");
    }

    // Read WAV header
    $header = fread($handle, 44);
    if (strlen($header) !== 44) {
        fclose($handle);
        throw new Exception("Invalid WAV header");
    }

    // Get format info
    $channels = unpack('v', substr($header, 22, 2))[1];
    $sampleRate = unpack('V', substr($header, 24, 4))[1];
    $bitsPerSample = unpack('v', substr($header, 34, 2))[1];
    $bytesPerSample = $bitsPerSample / 8;

    // Calculate positions
    $startByte = 44 + floor($start * $sampleRate) * $channels * $bytesPerSample;
    $endByte = 44 + floor($end * $sampleRate) * $channels * $bytesPerSample;
    $length = $endByte - $startByte;

    // Write silence
    fseek($handle, $startByte);
    $silence = str_repeat("\0", 8192);

    while ($length > 0) {
        $writeSize = min(strlen($silence), $length);
        fwrite($handle, substr($silence, 0, $writeSize));
        $length -= $writeSize;
    }

    fclose($handle);
}

function createStereoSeparation($input_file, $regions, $output_file) {
    $handle = fopen($input_file, 'rb');
    if (!$handle) {
        throw new Exception("Could not open input file");
    }

    // Read WAV header
    $header = fread($handle, 44);
    if (strlen($header) !== 44) {
        fclose($handle);
        throw new Exception("Invalid WAV header");
    }

    // Get format info
    $channels = unpack('v', substr($header, 22, 2))[1];
    $sampleRate = unpack('V', substr($header, 24, 4))[1];
    $bitsPerSample = unpack('v', substr($header, 34, 2))[1];
    $bytesPerSample = $bitsPerSample / 8;

    // Create stereo output
    $output = fopen($output_file, 'wb');
    if (!$output) {
        fclose($handle);
        throw new Exception("Could not create output file");
    }

    // Write modified header for stereo
    $header = substr($header, 0, 22) .
             pack('v', 2) . // NumChannels = 2
             pack('V', $sampleRate) . // SampleRate
             pack('V', $sampleRate * 4) . // ByteRate = SampleRate * NumChannels * BytesPerSample
             pack('v', 4) . // BlockAlign = NumChannels * BytesPerSample
             substr($header, 34);
    fwrite($output, $header);

    // Process audio data
    while (!feof($handle)) {
        $data = fread($handle, 8192);
        if ($data === false) break;

        $pos = ftell($handle) - strlen($data) - 44;
        $time = $pos / ($sampleRate * $bytesPerSample);

        // Find active speaker for this position
        $activeSpeaker = null;
        foreach (['speaker1', 'speaker2'] as $spk) {
            foreach ($regions[$spk] as $region) {
                if ($time >= $region['start'] && $time < $region['end']) {
                    $activeSpeaker = $spk;
                    break 2;
                }
            }
        }

        // Write stereo data
        for ($i = 0; $i < strlen($data); $i += $bytesPerSample) {
            $sample = substr($data, $i, $bytesPerSample);
            if ($activeSpeaker === 'speaker1') {
                fwrite($output, $sample . str_repeat("\0", $bytesPerSample));
            } elseif ($activeSpeaker === 'speaker2') {
                fwrite($output, str_repeat("\0", $bytesPerSample) . $sample);
            } else {
                fwrite($output, str_repeat("\0", $bytesPerSample * 2));
            }
        }
    }

    fclose($handle);
    fclose($output);
    return true;
}
