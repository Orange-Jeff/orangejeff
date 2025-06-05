<?php
// Dependency file for nb-annotate-it.php - Lists available projects for the project manager
header("Content-Type: application/json");

$projectsDir = 'projects';
$projects = [];
$error = null;

if (!file_exists($projectsDir)) {
    // If the directory doesn't exist, return an empty list, not an error
    echo json_encode(['success' => true, 'projects' => []]);
    exit();
}

try {
    $files = scandir($projectsDir);
    if ($files === false) {
        throw new Exception("Could not scan projects directory.");
    }

    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $filePath = $projectsDir . '/' . $file;
            $jsonData = file_get_contents($filePath);
            if ($jsonData === false) {
                error_log("Could not read project file: " . $filePath);
                continue; // Skip this file if unreadable
            }

            $projectData = json_decode($jsonData, true);
            if ($projectData && isset($projectData['projectId']) && isset($projectData['projectDomain'])) {
                $imageCount = isset($projectData['images']) ? count($projectData['images']) : 0;
                $lastSaved = filemtime($filePath); // Use file modification time

                $projects[] = [
                    'id' => $projectData['projectId'],
                    'name' => $projectData['projectDomain'],
                    'imageCount' => $imageCount,
                    'lastSaved' => date('Y-m-d H:i:s', $lastSaved) // Format timestamp
                ];
            } else {
                error_log("Invalid or incomplete JSON data in file: " . $filePath);
            }
        }
    }

    // Sort projects by last saved date, descending (most recent first)
    usort($projects, function($a, $b) {
        return strtotime($b['lastSaved']) - strtotime($a['lastSaved']);
    });

} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Error listing projects: " . $error);
}

if ($error) {
    echo json_encode(['success' => false, 'error' => $error]);
} else {
    echo json_encode(['success' => true, 'projects' => $projects]);
}
?>
