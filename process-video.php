<?php
// This file would handle server-side video processing
header('Content-Type: application/json');

// Error handling
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    // Check request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['videoFile']) || !isset($input['segments'])) {
        throw new Exception('Invalid input data');
    }

    $videoFile = $input['videoFile'];
    $segments = $input['segments'];

    // Process segments
    $results = [];
    foreach ($segments as $index => $segment) {
        $startTime = $segment['start'];
        $endTime = $segment['end'];
        $outputFile = "segment_" . ($index + 1) . ".mp4";

        // Execute FFmpeg command to extract segment
        $command = "ffmpeg -i \"$videoFile\" -ss $startTime -to $endTime -c copy \"$outputFile\" 2>&1";
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception('FFmpeg error: ' . implode("\n", $output));
        }

        $results[] = [
            'segment' => $index + 1,
            'file' => $outputFile,
            'start' => $startTime,
            'end' => $endTime
        ];
    }

    echo json_encode(['success' => true, 'results' => $results]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
