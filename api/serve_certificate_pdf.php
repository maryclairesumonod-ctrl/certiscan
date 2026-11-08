<?php
// ============================================================
// Converts the generated .docx into a real PDF using LibreOffice
// (exact match to the Word file), caches the result, and streams
// it inline so it can be shown in an <iframe> or opened directly.
// Automatically syncs with latest officials settings.
// ============================================================
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/certificate_helper.php';

define('LIBREOFFICE_PATH', 'C:\\Program Files\\LibreOffice\\program\\soffice.exe');

$requestId = (int)($_GET['request_id'] ?? 0);
if (!$requestId) {
    http_response_code(400);
    exit('Missing request_id');
}

// Dynamically generate or refresh certificate document with latest officials
$docxPath = generate_or_refresh_certificate_document($requestId, $pdo);

if (!$docxPath || !file_exists($docxPath)) {
    http_response_code(404);
    exit('Certificate document has not been generated yet or template is missing.');
}

$outputDir = dirname($docxPath);
$pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);

$needsConversion = !file_exists($pdfPath) || filemtime($pdfPath) < filemtime($docxPath);

if ($needsConversion) {
    $sofficeExe = escapeshellarg(LIBREOFFICE_PATH);
    $docxEsc = escapeshellarg($docxPath);
    $outDirEsc = escapeshellarg($outputDir);

    $command = "{$sofficeExe} --headless --convert-to pdf --outdir {$outDirEsc} {$docxEsc} 2>&1";
    $output = shell_exec($command);

    if (!file_exists($pdfPath)) {
        http_response_code(500);
        exit('PDF conversion failed. LibreOffice output: ' . htmlspecialchars($output ?? 'no output'));
    }
}

// Set cache-busting headers so browsers never render outdated PDF caches
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="certificate_' . $requestId . '.pdf"');
header('Content-Length: ' . filesize($pdfPath));
readfile($pdfPath);