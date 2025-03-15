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
$timestamp = date('YmdHis');
$input_filename = basename($_FILES['audioFile']['name']);
$output_filename = "{$input_filename}_{$speaker}_{$option}_{$timestamp}.wav";
$output_path = $output_dir . '/' . $output_filename;

// Parse regions data
$regions = json_decode($_POST['regions'], true);
if (!$regions) {
    log_debug("No valid regions data provided");
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'No valid regions data provided'
    ]);
    exit;
}

// Temp file for processing
$temp_output = $output_dir . '/temp_' . uniqid() . '.wav';

try {
    // Copy source file to temp
    if (!copy($_FILES['audioFile']['tmp_name'], $temp_output)) {
        throw new Exception("Failed to create temporary file");
    }

    // Process based on option
    switch ($option) {
        case 'default':
            // Mute other speaker's regions
            copy($temp_output, $output_path);
            $other_speaker = $speaker === 'speaker1' ? 'speaker2' : 'speaker1';
            foreach ($regions as $region) {
                if ($region['type'] === $other_speaker) {
                    muteRegion($output_path, $region['start'], $region['end']);
                }
            }
            break;

        case 'edited':
            // Delete non-speaking regions
            $speaking_regions = [];
            foreach ($regions as $region) {
                if ($region['type'] === $speaker) {
                    $speaking_regions[] = [
                        'start' => $region['start'],
                        'end' => $region['end']
                    ];
                }
            }
            concatenateRegions($temp_output, $speaking_regions, $output_path);
            break;

        case $speaker:
            // Extract only this speaker's segments
            $speaker_regions = [];
            foreach ($regions as $region) {
                if ($region['type'] === $speaker) {
                    $speaker_regions[] = [
                        'start' => $region['start'],
                        'end' => $region['end']
                    ];
                }
            }
            extractRegions($temp_output, $speaker_regions, $output_path);
            break;

        case 'full_lr':
            // Route speakers to left/right channels
            if (!createStereoSeparation($temp_output, $regions, $output_path)) {
                throw new Exception("Failed to create stereo separation");
            }
            break;

        default:
            throw new Exception("Unknown processing option: $option");
    }

    // Clean up temp file
    @unlink($temp_output);

    log_debug("Successfully processed file: $output_path");

    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'fileUrl' => 'output/' . $output_filename,
        'fileName' => $output_filename,
        'message' => 'Audio processed successfully'
    ]);

} catch (Exception $e) {
    // Clean up temp file if it exists
    if (file_exists($temp_output)) {
        @unlink($temp_output);
    }

    log_debug("Processing error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Read WAV header and get format info
function getWavInfo($handle) {
    // Read RIFF header
    fseek($handle, 0);
    $header = unpack('NChunkID/VChunkSize/NFormat', fread($handle, 12));

    // Read format chunk
    fseek($handle, 12);
    $format = unpack('NSubchunk1ID/VSubchunk1Size/vAudioFormat/vNumChannels/VSampleRate/VByteRate/vBlockAlign/vBitsPerSample',
        fread($handle, 24));

    // Find data chunk
    $dataOffset = 36;
    while (true) {
        fseek($handle, $dataOffset);
        $chunkHeader = fread($handle, 8);
        if (strlen($chunkHeader) < 8) break;

        $chunk = unpack('NID/VSize', $chunkHeader);
        if ($chunk['ID'] === 0x64617461) { // 'data' chunk
            break;
        }
        $dataOffset += 8 + $chunk['Size'];
    }

    return [
        'channels' => $format['NumChannels'],
        'sampleRate' => $format['SampleRate'],
        'bitsPerSample' => $format['BitsPerSample'],
        'dataOffset' => $dataOffset + 8,
        'bytesPerSample' => $format['BitsPerSample'] / 8,
        'blockAlign' => $format['BlockAlign']
    ];
}

function muteRegion($file, $start, $end) {
    $handle = fopen($file, 'r+b');
    if (!$handle) {
        throw new Exception("Could not open file for muting: $file");
    }

    $info = getWavInfo($handle);

    // Calculate positions
    $startByte = $info['dataOffset'] + floor($start * $info['sampleRate']) * $info['blockAlign'];
    $endByte = $info['dataOffset'] + floor($end * $info['sampleRate']) * $info['blockAlign'];
    $length = $endByte - $startByte;

    // Create silence buffer
    $silence = str_repeat("\0", min(8192, $length));

    // Write silence
    fseek($handle, $startByte);
    $remaining = $length;
    while ($remaining > 0) {
        $writeSize = min(strlen($silence), $remaining);
        fwrite($handle, substr($silence, 0, $writeSize));
        $remaining -= $writeSize;
    }

    fclose($handle);
}

function concatenateRegions($input_file, $regions, $output_file) {
    $input = fopen($input_file, 'rb');
    if (!$input) {
        throw new Exception("Could not open input file: $input_file");
    }

    $info = getWavInfo($input);

    // Create output file with WAV header
    $output = fopen($output_file, 'wb');
    if (!$output) {
        fclose($input);
        throw new Exception("Could not create output file: $output_file");
    }

    // Copy WAV header
    fseek($input, 0);
    $header = fread($input, $info['dataOffset']);
    fwrite($output, $header);

    // Calculate total output size
    $totalSamples = 0;
    foreach ($regions as $region) {
        $regionSamples = floor(($region['end'] - $region['start']) * $info['sampleRate']);
        $totalSamples += $regionSamples;
    }

    // Update data chunk size
    $dataSize = $totalSamples * $info['blockAlign'];
    fseek($output, 40);
    fwrite($output, pack('V', $dataSize));

    // Update RIFF chunk size
    $riffSize = $dataSize + 36;
    fseek($output, 4);
    fwrite($output, pack('V', $riffSize));

    // Copy regions
    foreach ($regions as $region) {
        $startSample = floor($region['start'] * $info['sampleRate']);
        $endSample = floor($region['end'] * $info['sampleRate']);
        $numSamples = $endSample - $startSample;

        fseek($input, $info['dataOffset'] + $startSample * $info['blockAlign']);

        $remaining = $numSamples * $info['blockAlign'];
        while ($remaining > 0) {
            $readSize = min(8192, $remaining);
            $data = fread($input, $readSize);
            fwrite($output, $data);
            $remaining -= $readSize;
        }
    }

    fclose($input);
    fclose($output);
}

function extractRegions($input_file, $regions, $output_file) {
    // For single speaker extraction, use concatenateRegions
    concatenateRegions($input_file, $regions, $output_file);
}

function createStereoSeparation($input_file, $regions, $output_file) {
    $input = fopen($input_file, 'rb');
    if (!$input) {
        throw new Exception("Could not open input file: $input_file");
    }

    $info = getWavInfo($input);

    // Create output file
    $output = fopen($output_file, 'wb');
    if (!$output) {
        fclose($input);
        throw new Exception("Could not create output file: $output_file");
    }

    // Copy and modify header for stereo
    fseek($input, 0);
    $header = fread($input, $info['dataOffset']);

    // Update header for stereo output
    $header = substr($header, 0, 22) .
             pack('v', 2) . // NumChannels = 2
             substr($header, 24, 8) .
             pack('V', $info['sampleRate'] * 4) . // ByteRate = SampleRate * NumChannels * BitsPerSample/8
             pack('v', 4) . // BlockAlign = NumChannels * BitsPerSample/8
             substr($header, 36);

    fwrite($output, $header);

    // Process audio data
    $bufferSize = 8192;
    $totalSamples = filesize($input_file) - $info['dataOffset'];
    $totalSamples = floor($totalSamples / $info['blockAlign']);

    // Create stereo buffer
    $stereoData = str_repeat("\0", $bufferSize * 2);

    for ($sample = 0; $sample < $totalSamples; $sample += $bufferSize / $info['blockAlign']) {
        // Read input data
        fseek($input, $info['dataOffset'] + $sample * $info['blockAlign']);
        $data = fread($input, $bufferSize);
        if (!$data) break;

        // Get current time position
        $time = $sample / $info['sampleRate'];

        // Determine which speaker is active
        $activeSpeaker = null;
        foreach ($regions as $region) {
            if ($time >= $region['start'] && $time < $region['end']) {
                $activeSpeaker = $region['type'];
                break;
            }
        }

        // Route audio to appropriate channel
        if ($activeSpeaker === 'speaker1') {
            // Route to left channel
            for ($i = 0; $i < strlen($data); $i += $info['blockAlign']) {
                fwrite($output, substr($data, $i, $info['blockAlign']) . str_repeat("\0", $info['blockAlign']));
            }
        } elseif ($activeSpeaker === 'speaker2') {
            // Route to right channel
            for ($i = 0; $i < strlen($data); $i += $info['blockAlign']) {
                fwrite($output, str_repeat("\0", $info['blockAlign']) . substr($data, $i, $info['blockAlign']));
            }
        } else {
            // No active speaker - write silence to both channels
            fwrite($output, str_repeat("\0", strlen($data) * 2));
        }
    }

    fclose($input);
    fclose($output);

    return true;
}
