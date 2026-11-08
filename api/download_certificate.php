<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/certificate_helper.php';

$requestId = (int)($_GET['request_id'] ?? 0);
if (!$requestId) {
    http_response_code(400);
    exit('Missing request_id');
}

// Dynamically generate or refresh certificate document with latest officials
$docxPath = generate_or_refresh_certificate_document($requestId, $pdo);

if (!$docxPath || !file_exists($docxPath)) {
    http_response_code(404);
    exit('Certificate file not found on server');
}

$stmt = $pdo->prepare('SELECT cert_number FROM certificate_requests WHERE request_id = ?');
$stmt->execute([$requestId]);
$certNumber = $stmt->fetchColumn() ?: $requestId;

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="Certificate_' . $certNumber . '.docx"');
header('Content-Length: ' . filesize($docxPath));
readfile($docxPath);