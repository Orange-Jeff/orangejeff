<?php
// Dependency file for nb-annotate-it.php - Deletes projects from the server
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $projectId = isset($_GET['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id']) : '';

    if (empty($projectId)) {
        echo json_encode(['success' => false, 'error' => 'No project ID provided']);
        exit;
    }

    $filename = "projects/{$projectId}.json";

    if (file_exists($filename)) {
        if (unlink($filename)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete project file']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Project not found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
