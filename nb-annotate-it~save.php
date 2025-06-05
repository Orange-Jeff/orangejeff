<?php
// Dependency file for nb-annotate-it.php - Saves projects to the server
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['projectDomain']) || empty($data['projectDomain'])) {
        echo json_encode(['success' => false, 'error' => 'Project name is required']);
        exit;
    }

    if (!isset($data['images']) || !is_array($data['images']) || empty($data['images'])) {
        echo json_encode(['success' => false, 'error' => 'No images to save']);
        exit;
    }

    // Create projects directory if it doesn't exist
    $projectsDir = 'projects';
    if (!file_exists($projectsDir)) {
        if (!mkdir($projectsDir, 0755, true)) {
            echo json_encode(['success' => false, 'error' => 'Failed to create projects directory']);
            exit();
        }
    }

    // Generate or use existing project ID
    $projectId = isset($data['projectId']) && !empty($data['projectId'])
        ? $data['projectId']
        : 'nbproof_' . uniqid();

    // Add timestamp
    $data['lastSaved'] = date('Y-m-d H:i:s');
    $data['projectId'] = $projectId; // Ensure projectId is saved within the data

    // Save to file in the correct directory
    $filename = "{$projectsDir}/{$projectId}.json"; // Use $projectsDir instead of $dataDir
    $success = file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT)); // Added JSON_PRETTY_PRINT

    if ($success) {
        // Create the new URL format
        $sanitizedName = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($data['projectDomain']));
        $proofId = str_replace('nbproof_', '', $projectId);
        $shareUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") .
            "://{$_SERVER['HTTP_HOST']}" .
            dirname($_SERVER['PHP_SELF']) .
            "/nb-annotate-it.php?proof={$sanitizedName}_{$proofId}";

        echo json_encode([
            'success' => true,
            'projectId' => $projectId,
            'lastSaved' => $data['lastSaved'],
            'shareUrl' => $shareUrl
        ]);
    } else {
        // Get the last error for more details
        $errorInfo = error_get_last();
        $errorMessage = 'Failed to save project';
        if ($errorInfo !== null) {
            $errorMessage .= ' - System Error: ' . $errorInfo['message']; // Append system error message
        }
        echo json_encode(['success' => false, 'error' => $errorMessage]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
