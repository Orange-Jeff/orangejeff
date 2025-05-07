<?php
// Dependency file for nb-annotate-it.php - Loads projects from the server
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Get the project parameter from the query string
$proofParam = isset($_GET['proof']) ? $_GET['proof'] : null;

if (!$proofParam) {
    echo json_encode(['success' => false, 'error' => 'No project parameter provided']);
    exit();
}

// Extract the project ID from the proof parameter
// Format is expected to be: projectname_id
$parts = explode('_', $proofParam);
$projectId = null;

if (count($parts) > 1) {
    // Get the last part as the ID
    $idPart = end($parts);
    $projectId = 'nbproof_' . $idPart;
} else {
    // Fallback to the old format or direct ID
    $projectId = $proofParam;
}

// Create projects directory if it doesn't exist
$projectsDir = 'projects';
if (!file_exists($projectsDir)) {
    if (!mkdir($projectsDir, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'Failed to create projects directory']);
        exit();
    }
}

// Path to the project file
$filename = $projectsDir . '/' . $projectId . '.json';

// For debugging
error_log("Looking for project file: " . $filename);

if (!file_exists($filename)) {
    // Try alternative formats if the first attempt fails
    $alternativeIds = [
        $proofParam,                    // Original parameter as-is
        'nbproof_' . $proofParam,       // With nbproof_ prefix
        str_replace('nbproof_', '', $projectId)  // Without nbproof_ prefix
    ];

    $found = false;
    foreach ($alternativeIds as $altId) {
        $altFilename = $projectsDir . '/' . $altId . '.json';
        if (file_exists($altFilename)) {
            $filename = $altFilename;
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo json_encode(['success' => false, 'error' => 'Project not found']);
        exit();
    }
}

// Read the project file
$jsonData = file_get_contents($filename);
$projectData = json_decode($jsonData, true);

if (!$projectData) {
    echo json_encode(['success' => false, 'error' => 'Invalid project data']);
    exit();
}

// Return the project data
echo json_encode([
    'success' => true,
    'projectId' => $projectData['projectId'],
    'projectDomain' => $projectData['projectDomain'],
    'images' => $projectData['images'],
    'lastSaved' => $projectData['lastSaved'] ?? null
]);
?>
