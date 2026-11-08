<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/officials_helper.php';

if (file_exists(__DIR__ . '/officials_helper.php')) require_once __DIR__ . '/officials_helper.php';
use PhpOffice\PhpWord\TemplateProcessor;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$data = json_decode(file_get_contents('php://input'), true);

$residentId        = (int)($data['resident_id'] ?? 0);
$certificateTypeId = (int)($data['certificate_type_id'] ?? 0);
$extraValues        = $data['extra_values'] ?? [];

if (!$residentId || !$certificateTypeId) {
    exit(json_encode(['success' => false, 'message' => 'Missing resident or certificate type']));
}

$stmt = $pdo->prepare('SELECT * FROM residents WHERE resident_id = ?');
$stmt->execute([$residentId]);
$resident = $stmt->fetch();

if (!$resident) {
    exit(json_encode(['success' => false, 'message' => 'Resident not found']));
}

$stmt = $pdo->prepare('SELECT * FROM certificate_types WHERE certificate_type_id = ?');
$stmt->execute([$certificateTypeId]);
$certType = $stmt->fetch();

if (!$certType) {
    exit(json_encode(['success' => false, 'message' => 'Certificate type not found']));
}

if (empty($certType['is_active'])) {
    exit(json_encode(['success' => false, 'message' => 'This certificate type is currently inactive and cannot be issued.']));
}

require_once __DIR__ . '/certificate_helper.php';

$templateFile = !empty($certType['template_file']) ? basename($certType['template_file']) : null;

if (!$templateFile) {
    exit(json_encode([
        'success' => false,
        'message' => 'No Word template has been configured yet for "' . $certType['name'] . '". Please upload a template in Certificate Management.',
    ]));
}

$templatePath = __DIR__ . '/../frontend/templates/' . $templateFile;

if (!file_exists($templatePath)) {
    exit(json_encode(['success' => false, 'message' => 'Template file missing on server: ' . $templateFile]));
}

try {
    $pdo->beginTransaction();

    $certNumber = sprintf('%09d-%s-%04d', random_int(1, 999999999), date('Y-m'), random_int(1, 9999));
    $today = new DateTime();

    $finalData = array_merge([
        'full_name'      => $resident['full_name'],
        'address'        => $resident['address'],
        'birthdate'      => $resident['birthdate'],
        'civil_status'   => $resident['civil_status'],
        'place_of_birth' => $resident['place_of_birth'],
    ], $extraValues);

    // 1. Save the database record first — walk-in certificates are
    // generated and handed to the resident on the spot, so they go
    // straight to "released" rather than sitting in a review queue.
    $stmt = $pdo->prepare(
        'INSERT INTO certificate_requests
            (resident_id, certificate_type_id, scan_id, final_data, status, cert_number, issued_at, released_at, handled_by)
         VALUES (?,?,NULL,?,"released",?,CURDATE(),NOW(),?)'
    );
    $stmt->execute([
        $residentId,
        $certificateTypeId,
        json_encode($finalData),
        $certNumber,
        $_SESSION['staff_id'],
    ]);
    $requestId = $pdo->lastInsertId();

    // 2. Fill the Word template with the real data
    $processor = new TemplateProcessor($templatePath);
    populate_certificate_template($processor, $resident, $certType['name'], $certNumber, $extraValues, $pdo);

    // 3. Save the filled document to disk
    $outputDir = __DIR__ . '/../frontend/generated_certificates';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    $outputFilename = 'certificate_' . $requestId . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $certNumber) . '.docx';
    $outputPath = $outputDir . '/' . $outputFilename;
    $processor->saveAs($outputPath);

    // 4. Store the path so it can be re-downloaded later from Records
    $stmt = $pdo->prepare('UPDATE certificate_requests SET pdf_path = ? WHERE request_id = ?');
    $stmt->execute(['generated_certificates/' . $outputFilename, $requestId]);

    $stmt = $pdo->prepare('INSERT INTO audit_log (staff_id, action, target_table, target_id) VALUES (?,?,?,?)');
    $stmt->execute([$_SESSION['staff_id'], 'generated certificate document', 'certificate_requests', $requestId]);

    $pdo->commit();

    echo json_encode([
        'success'      => true,
        'request_id'   => $requestId,
        'cert_number'  => $certNumber,
        'download_url' => 'download_certificate.php?request_id=' . $requestId,
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}