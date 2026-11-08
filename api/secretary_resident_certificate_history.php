<?php
require_once __DIR__ . '/auth_guard.php';
header('Content-Type: application/json');

$residentId = (int)($_GET['resident_id'] ?? 0);

$stmt = $pdo->prepare('SELECT full_name, address FROM residents WHERE resident_id = ?');
$stmt->execute([$residentId]);
$resident = $stmt->fetch();

if (!$resident) {
    exit(json_encode(['success' => false, 'message' => 'Resident not found']));
}

$stmt = $pdo->prepare(
    "SELECT cr.request_id, cr.cert_number, cr.status, cr.issued_at, cr.released_at, cr.pdf_path,
            ct.name AS certificate_type
     FROM certificate_requests cr
     JOIN certificate_types ct ON ct.certificate_type_id = cr.certificate_type_id
     WHERE cr.resident_id = ?
     ORDER BY cr.created_at DESC"
);
$stmt->execute([$residentId]);
$certificates = $stmt->fetchAll();

echo json_encode(['success' => true, 'resident' => $resident, 'certificates' => $certificates]);
