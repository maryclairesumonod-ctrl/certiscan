<?php
require_once __DIR__ . '/auth_guard.php';

$scanId = (int)($_GET['scan_id'] ?? 0);
if (!$scanId) {
    http_response_code(400);
    exit('Missing scan_id');
}

$stmt = $pdo->prepare('SELECT id_image, image_mime_type FROM scans WHERE scan_id = ?');
$stmt->execute([$scanId]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    exit('Image not found');
}

header('Content-Type: ' . $row['image_mime_type']);
echo $row['id_image'];
