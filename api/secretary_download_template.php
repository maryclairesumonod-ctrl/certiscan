<?php
// ============================================================
// Secretary API — Download Certificate Template (.docx)
// ============================================================

require_once __DIR__ . '/auth_guard.php';

// Secretary / Admin check
if (empty($_SESSION['role']) || ($_SESSION['role'] !== 'secretary' && $_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    exit('Unauthorized: Secretary privileges required.');
}

$certificateTypeId = (int)($_GET['certificate_type_id'] ?? 0);

if (!$certificateTypeId) {
    http_response_code(400);
    exit('Missing certificate_type_id');
}

$stmt = $pdo->prepare('SELECT name, template_file FROM certificate_types WHERE certificate_type_id = ?');
$stmt->execute([$certificateTypeId]);
$cert = $stmt->fetch();

if (!$cert || empty($cert['template_file'])) {
    http_response_code(404);
    exit('No template assigned for this certificate type.');
}

$cleanFile = basename($cert['template_file']);
$filePath = __DIR__ . '/../frontend/templates/' . $cleanFile;

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('Template file not found on server: ' . htmlspecialchars($cleanFile));
}

$downloadName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $cert['name']) . '_template.docx';

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($filePath);
